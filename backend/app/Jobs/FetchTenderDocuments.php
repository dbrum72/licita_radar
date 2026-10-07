<?php
namespace App\Jobs;
use App\Models\{Opportunity, Document};
use App\Services\{PncpClient, DocumentService};
use Illuminate\Contracts\Queue\{ShouldQueue, ShouldBeUnique};
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchTenderDocuments implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    public $timeout = 3600;
    public $tries = 1;
    public $uniqueFor = 21600;
    public function __construct(public int $opportunityId) {}
    public function uniqueId(): string { return (string)$this->opportunityId; }
    public function handle(PncpClient $client, DocumentService $extractor): void
    {
        $op = Opportunity::find($this->opportunityId);
        if (!$op) return; // Queued before expiration cleanup.
        if (!$op->deadline || !$op->deadline->gt(now()) || $op->dismissal()->exists() || app(\App\Services\CompanyScopeService::class)->assess($op->object,$op->deadline)['status'] !== 'compatible') {
            $op->update(['attachments_status'=>'pending','attachments_error'=>'Busca suspensa: compatibilidade com o acervo atual não demonstrada.']);
            return;
        }
        $base = 'https://pncp.gov.br/api/pncp/v1/orgaos/'.rawurlencode($op->cnpj)."/compras/{$op->year}/{$op->sequence}/arquivos";
        $op->update(['attachments_status'=>'running','attachments_error'=>null]);
        try {
            $response = $client->request($base);
            $files = $response->status() === 204 ? [] : $response->json();
            if (!is_array($files) || !array_is_list($files)) throw new \RuntimeException('Lista de anexos PNCP inválida.');
            $skipped = 0;
            foreach ($files as $file) {
                if (!isset($file['sequencialDocumento']) || !is_numeric($file['sequencialDocumento'])) { $skipped++; continue; }
                // Construct the URL from the official identifier instead of following arbitrary remote URLs.
                $download = $client->request($base.'/'.(int)$file['sequencialDocumento'],[],[
                    'allow_redirects'=>false,
                    'on_headers'=>function ($response) { if ((int)$response->getHeaderLine('Content-Length')>20*1024*1024) throw new \RuntimeException('Anexo maior que 20 MB.'); },
                    'progress'=>function ($total,$received) { if ($received>20*1024*1024) throw new \RuntimeException('Anexo maior que 20 MB.'); },
                ]);
                $bytes = $download->body();
                if (!str_starts_with(ltrim($bytes),'%PDF-')) { $skipped++; continue; }
                $sha = hash('sha256',$bytes);
                if (Document::where('opportunity_id',$op->id)->where('sha256',$sha)->exists()) continue;
                $path = 'documents/'.Str::uuid().'.pdf'; Storage::disk('local')->put($path,$bytes);
                $doc = Document::create(['opportunity_id'=>$op->id,'name'=>mb_substr($file['titulo'] ?? 'Anexo PNCP',0,240).'.pdf',
                    'kind'=>'tender','path'=>$path,'sha256'=>$sha]);
                $extractor->extract($doc);
            }
            $op->update(['attachments_status'=>$skipped ? 'partial' : ($files ? 'ready' : 'empty'),
                'attachments_error'=>$skipped ? 'Há anexos não PDF ou sem identificador. Confira todos os anexos no PNCP.' : null]);
        } catch (\Throwable $e) {
            report($e); $op->update(['attachments_status'=>'failed','attachments_error'=>'Não foi possível consultar todos os anexos. Tente novamente ou adicione os PDFs manualmente.']);
        }
    }
}
