<?php

namespace App\Services\Documents;

use App\Integrations\Pncp\Contracts\PncpClient;
use App\Models\Document;
use App\Models\Opportunity;
use App\Services\CompanyDocuments\CompanyScopeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenderAttachmentService
{
    public function __construct(private readonly PncpClient $client, private readonly CompanyScopeService $companyScopeService) {}

    public function fetchRequested(Opportunity $opportunity): array
    {
        if (! $opportunity->hasOpenDeadline() || $opportunity->dismissal()->exists()
            || $this->companyScopeService->assess($opportunity->object, $opportunity->deadline)['status'] !== 'compatible') {
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
        $op = Opportunity::find($opportunityId);
        if (! $op) {
            return;
        }
        if (! $op->hasOpenDeadline() || $op->dismissal()->exists() || $this->companyScopeService->assess($op->object, $op->deadline)['status'] !== 'compatible') {
            $op->update(['attachments_status' => 'pending', 'attachments_error' => 'Busca suspensa: compatibilidade com o acervo atual não demonstrada.']);

            return;
        }
        $base = 'https://pncp.gov.br/api/pncp/v1/orgaos/'.rawurlencode($op->cnpj)."/compras/{$op->year}/{$op->sequence}/arquivos";
        $op->update(['attachments_status' => 'running', 'attachments_error' => null]);
        try {
            $timeouts = ['connect_timeout' => config('radar.attachment_connect_timeout'), 'timeout' => config('radar.attachment_timeout')];
            $response = $client->request($base, [], $timeouts);
            $files = $response->status() === 204 ? [] : $response->json();
            if (! is_array($files) || ! array_is_list($files)) {
                throw new \RuntimeException('Lista de anexos PNCP inválida.');
            }
            $skipped = 0;
            $failed = 0;
            foreach ($files as $file) {
                if (! isset($file['sequencialDocumento']) || ! is_numeric($file['sequencialDocumento'])) {
                    $skipped++;

                    continue;
                }
                try {
                    // Construct the URL from the official identifier instead of following arbitrary remote URLs.
                    $download = $client->request($base.'/'.(int) $file['sequencialDocumento'], [], [
                        ...$timeouts,
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
                    $existing = Document::where('opportunity_id', $op->id)->where('sha256', $sha)->first();
                    if ($existing) {
                        if (! Storage::disk('local')->exists($existing->getRawOriginal('path'))) {
                            if (! Storage::disk('local')->put($existing->getRawOriginal('path'), $bytes)) {
                                throw new \RuntimeException('Não foi possível restaurar o arquivo do anexo.');
                            }
                            $existing->update(['status' => 'stored']);
                        }

                        continue;
                    }
                    $path = 'documents/opportunities/'.$op->id.'/'.Str::uuid().'.pdf';
                    if (! Storage::disk('local')->put($path, $bytes)) {
                        throw new \RuntimeException('Não foi possível armazenar o anexo.');
                    }
                    try {
                        $name = preg_replace('/(?:\.pdf)+$/i', '', trim($file['titulo'] ?? 'Anexo PNCP'));
                        Document::create(['opportunity_id' => $op->id, 'name' => mb_substr($name ?: 'Anexo PNCP', 0, 236).'.pdf',
                            'kind' => 'tender', 'path' => $path, 'sha256' => $sha, 'status' => 'stored']);
                    } catch (\Throwable $exception) {
                        Storage::disk('local')->delete($path);
                        throw $exception;
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                    $failed++;
                }
            }
            $op->update(['attachments_status' => $failed ? ($op->documents()->exists() ? 'partial' : 'failed') : ($skipped ? 'partial' : ($files ? 'ready' : 'empty')),
                'attachments_error' => $failed ? $failed.' anexo(s) não puderam ser baixados do PNCP. Os PDFs já armazenados foram preservados. Tente buscar novamente para completar os anexos.' : ($skipped ? 'Há anexos não PDF ou sem identificador. Confira todos os anexos no PNCP.'
                    : null)]);
        } catch (\Throwable $e) {
            report($e);
            $op->update(['attachments_status' => 'failed', 'attachments_error' => 'Não foi possível consultar todos os anexos. Tente novamente ou adicione os PDFs manualmente.']);
        }
    }
}
