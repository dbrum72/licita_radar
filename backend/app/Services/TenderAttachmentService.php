<?php

namespace App\Services;

use App\Integrations\Pncp\Contracts\PncpClient;
use App\Models\Document;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenderAttachmentService
{
    public function __construct(private PncpClient $client) {}

    public function fetchRequested(Opportunity $opportunity): array
    {
        if (! $opportunity->hasOpenDeadline() || $opportunity->dismissal()->exists()
            || app(CompanyScopeService::class)->assess($opportunity->object, $opportunity->deadline)['status'] !== 'compatible') {
            abort(422, 'Compatibilidade com o acervo atual não demonstrada. Cadastre documentação válida antes de buscar anexos.');
        }
        $lock = Cache::lock('tender-attachments:'.$opportunity->id, 3600);
        if (! $lock->get()) {
            abort(409, 'A busca de anexos desta oportunidade já está em andamento.');
        }
        try {
            $this->fetch($opportunity->id);
            $opportunity->refresh();

            return ['message' => $opportunity->attachments_error ?: ($opportunity->attachments_status === 'empty' ? 'Nenhum anexo encontrado no PNCP.' : 'Busca de anexos concluída.'),
                'attachments_status' => $opportunity->attachments_status];
        } finally {
            $lock->release();
        }
    }

    public function fetch(int $opportunityId): void
    {
        $client = $this->client;
        $extractor = app(DocumentService::class);
        $op = Opportunity::find($opportunityId);
        if (! $op) {
            return;
        }
        if (! $op->hasOpenDeadline() || $op->dismissal()->exists() || app(CompanyScopeService::class)->assess($op->object, $op->deadline)['status'] !== 'compatible') {
            $op->update(['attachments_status' => 'pending', 'attachments_error' => 'Busca suspensa: compatibilidade com o acervo atual não demonstrada.']);

            return;
        }
        $base = 'https://pncp.gov.br/api/pncp/v1/orgaos/'.rawurlencode($op->cnpj)."/compras/{$op->year}/{$op->sequence}/arquivos";
        $op->update(['attachments_status' => 'running', 'attachments_error' => null]);
        try {
            $response = $client->request($base);
            $files = $response->status() === 204 ? [] : $response->json();
            if (! is_array($files) || ! array_is_list($files)) {
                throw new \RuntimeException('Lista de anexos PNCP inválida.');
            }
            $skipped = 0;
            foreach ($files as $file) {
                if (! isset($file['sequencialDocumento']) || ! is_numeric($file['sequencialDocumento'])) {
                    $skipped++;

                    continue;
                }
                // Construct the URL from the official identifier instead of following arbitrary remote URLs.
                $download = $client->request($base.'/'.(int) $file['sequencialDocumento'], [], [
                    'allow_redirects' => false,
                    'on_headers' => function ($response) {
                        if ((int) $response->getHeaderLine('Content-Length') > 20 * 1024 * 1024) {
                            throw new \RuntimeException('Anexo maior que 20 MB.');
                        }
                    },
                    'progress' => function ($total, $received) {
                        if ($received > 20 * 1024 * 1024) {
                            throw new \RuntimeException('Anexo maior que 20 MB.');
                        }
                    },
                ]);
                $bytes = $download->body();
                if (! str_starts_with(ltrim($bytes), '%PDF-')) {
                    $skipped++;

                    continue;
                }
                $sha = hash('sha256', $bytes);
                if (Document::where('opportunity_id', $op->id)->where('sha256', $sha)->exists()) {
                    continue;
                }
                $path = 'documents/'.Str::uuid().'.pdf';
                if (! Storage::disk('local')->put($path, $bytes)) {
                    throw new \RuntimeException('Não foi possível armazenar o anexo.');
                }
                try {
                    $doc = Document::create(['opportunity_id' => $op->id, 'name' => mb_substr($file['titulo'] ?? 'Anexo PNCP', 0, 240).'.pdf',
                        'kind' => 'tender', 'path' => $path, 'sha256' => $sha]);
                } catch (\Throwable $exception) {
                    Storage::disk('local')->delete($path);
                    throw $exception;
                }
                $extractor->extract($doc);
            }
            $incomplete = Document::where('opportunity_id', $op->id)->where('status', '!=', 'ready')->exists();
            $op->update(['attachments_status' => ($skipped || $incomplete) ? 'partial' : ($files ? 'ready' : 'empty'),
                'attachments_error' => $skipped ? 'Há anexos não PDF ou sem identificador. Confira todos os anexos no PNCP.'
                    : ($incomplete ? 'Há PDFs com extração incompleta ou falha. Revise os anexos e adicione os PDFs corrigidos se necessário.' : null)]);
        } catch (\Throwable $e) {
            report($e);
            $op->update(['attachments_status' => 'failed', 'attachments_error' => 'Não foi possível consultar todos os anexos. Tente novamente ou adicione os PDFs manualmente.']);
        }
    }
}
