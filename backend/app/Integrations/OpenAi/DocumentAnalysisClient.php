<?php

namespace App\Integrations\OpenAi;

use App\Services\DocumentAnalysis\AnalysisSchema;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentAnalysisClient
{
    public function analyze(array $content, ?string $instructions = null): array
    {
        $started = hrtime(true);
        $transfer = [];
        try {
            $response = Http::withToken(config('document_analysis.api_key'))->acceptJson()->connectTimeout(15)
                ->timeout(config('document_analysis.timeout'))->withOptions(['allow_redirects' => false, 'on_stats' => function (TransferStats $stats) use (&$transfer) {
                    $transfer = $stats->getHandlerStats();
                    $transfer['errno'] = $stats->getHandlerErrorData();
                }])
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('document_analysis.model'), 'store' => false,
                    'reasoning' => ['effort' => config('document_analysis.reasoning_effort')],
                    'instructions' => $instructions ?? AnalysisSchema::instructions(), 'max_output_tokens' => config('document_analysis.max_output_tokens'),
                    'input' => [['role' => 'user', 'content' => $content]],
                    'text' => ['format' => ['type' => 'json_schema', 'name' => 'opportunity_document_analysis', 'strict' => true, 'schema' => AnalysisSchema::json()]],
                ]);
        } catch (ConnectionException $exception) {
            $previous = $exception->getPrevious();
            $transport = $previous && method_exists($previous, 'getHandlerContext') ? $previous->getHandlerContext() : $transfer;
            $errno = is_int($transport['errno'] ?? null) ? $transport['errno'] : null;
            if ($errno === null && preg_match('/\bcURL error (\d+):/', $exception->getMessage(), $matches)) {
                $errno = (int) $matches[1];
            }
            Log::warning('Falha de transporte na análise documental', [
                'timeout_seconds' => config('document_analysis.timeout'),
                'elapsed_seconds' => round((hrtime(true) - $started) / 1e9, 2),
                'input_parts' => count($content),
                'curl_errno' => $errno,
                'connect_seconds' => $transport['connect_time'] ?? null,
                'tls_seconds' => $transport['appconnect_time'] ?? null,
            ]);
            abort($errno === 28 ? 504 : 502, $errno === 28
                ? 'A conexão com a IA excedeu o tempo de espera. Os lotes concluídos foram preservados para a próxima tentativa manual; a chamada interrompida pode ter consumido créditos.'
                : 'A conexão com a OpenAI falhou ou foi interrompida. Os lotes concluídos foram preservados para a próxima tentativa manual. Confira a conexão de rede e tente novamente; a chamada interrompida pode ter consumido créditos.');
        }
        if (! $response->successful()) {
            $providerCode = $response->json('error.code');
            $quotaExceeded = in_array($providerCode, ['insufficient_quota', 'billing_hard_limit_reached'], true);
            Log::warning('Falha no provedor de análise documental', [
                'status' => $response->status(), 'request_id' => $response->header('x-request-id'),
                // Only record recognized codes, never the raw provider message or request.
                'code' => in_array($providerCode, ['insufficient_quota', 'billing_hard_limit_reached', 'rate_limit_exceeded'], true) ? $providerCode : null,
            ]);
            if ($response->status() === 429) {
                abort($quotaExceeded ? 503 : 429, $quotaExceeded
                    ? 'A conta ou projeto da API OpenAI está sem cota disponível. Verifique os créditos, o faturamento e o limite de gastos do projeto associado à chave no backend. Depois de regularizar, gere a análise novamente.'
                    : 'A OpenAI limitou temporariamente as chamadas de análise. Aguarde antes de tentar novamente. Se persistir, confira os limites do projeto associado à chave no backend.');
            }
            abort(502, match ($response->status()) {
                401, 403 => 'Não foi possível autenticar na OpenAI. Verifique a chave e as permissões no backend.',
                default => 'A OpenAI não concluiu a análise. Verifique o modelo configurado e a disponibilidade do serviço.',
            });
        }
        $data = $response->json();
        if (! is_array($data) || ($data['status'] ?? null) !== 'completed') {
            $reason = $data['incomplete_details']['reason'] ?? null;
            Log::warning('Resposta de análise documental não concluída', [
                'request_id' => $response->header('x-request-id'),
                'reason' => in_array($reason, ['max_output_tokens', 'content_filter'], true) ? $reason : 'unknown',
                'max_output_tokens' => config('document_analysis.max_output_tokens'),
                'output_tokens' => is_int($data['usage']['output_tokens'] ?? null) ? $data['usage']['output_tokens'] : null,
                'reasoning_tokens' => is_int($data['usage']['output_tokens_details']['reasoning_tokens'] ?? null) ? $data['usage']['output_tokens_details']['reasoning_tokens'] : null,
            ]);
            abort(502, match ($reason) {
                'max_output_tokens' => 'A IA atingiu o limite de tokens antes de concluir o relatório. Aumente OPENAI_ANALYSIS_MAX_OUTPUT_TOKENS no backend ou reduza o tamanho dos lotes em OPENAI_ANALYSIS_BATCH_TEXT_CHARS. O limite inclui raciocínio e relatório. O relatório anterior foi preservado; a chamada pode ter consumido créditos.',
                'content_filter' => 'O provedor interrompeu a análise por filtro de conteúdo. Revise os documentos enviados. O relatório anterior foi preservado.',
                default => 'A análise da IA não foi concluída pelo provedor. O relatório anterior foi preservado. Consulte o diagnóstico no log antes de tentar novamente.',
            });
        }
        $texts = [];
        foreach ($data['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $part) {
                abort_if(($part['type'] ?? '') === 'refusal', 422, 'A IA recusou a análise desse conjunto. O relatório anterior foi preservado.');
                if (($part['type'] ?? '') === 'output_text' && is_string($part['text'] ?? null)) {
                    $texts[] = $part['text'];
                }
            }
        }
        try {
            $analysis = json_decode(implode('', $texts), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            abort(502, 'A IA retornou um relatório inválido. O relatório anterior foi preservado.');
        }
        abort_unless(is_array($analysis), 502, 'A IA retornou um relatório inválido.');

        return ['analysis' => $analysis, 'model' => $data['model'] ?? config('document_analysis.model'), 'usage' => $data['usage'] ?? null];
    }
}
