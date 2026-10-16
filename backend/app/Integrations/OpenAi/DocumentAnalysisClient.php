<?php

namespace App\Integrations\OpenAi;

use App\Services\DocumentAnalysis\AnalysisSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentAnalysisClient
{
    public function analyze(array $content, ?string $instructions = null): array
    {
        try {
            $response = Http::withToken(config('document_analysis.api_key'))->acceptJson()->connectTimeout(15)
                ->timeout(config('document_analysis.timeout'))->withOptions(['allow_redirects' => false])
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('document_analysis.model'), 'store' => false,
                    'reasoning' => ['effort' => config('document_analysis.reasoning_effort')],
                    'instructions' => $instructions ?? AnalysisSchema::instructions(), 'max_output_tokens' => config('document_analysis.max_output_tokens'),
                    'input' => [['role' => 'user', 'content' => $content]],
                    'text' => ['format' => ['type' => 'json_schema', 'name' => 'opportunity_document_analysis', 'strict' => true, 'schema' => AnalysisSchema::json()]],
                ]);
        } catch (ConnectionException $exception) {
            abort(504, 'A IA não respondeu a tempo. Confira o relatório antes de tentar novamente; a chamada pode ter consumido créditos.');
        }
        if (! $response->successful()) {
            Log::warning('Falha no provedor de análise documental', ['status' => $response->status(), 'request_id' => $response->header('x-request-id')]);
            abort(502, match ($response->status()) {
                401, 403 => 'Não foi possível autenticar na OpenAI. Verifique a chave e as permissões no backend.',
                429 => 'O limite de uso ou créditos da OpenAI foi atingido. Verifique a conta antes de tentar novamente.',
                default => 'A OpenAI não concluiu a análise. Verifique o modelo configurado e a disponibilidade do serviço.',
            });
        }
        $data = $response->json();
        abort_unless(is_array($data) && ($data['status'] ?? null) === 'completed', 502, 'A análise da IA ficou incompleta. O relatório anterior foi preservado; revise os limites antes de tentar novamente.');
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
