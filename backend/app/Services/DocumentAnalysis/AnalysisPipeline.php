<?php

namespace App\Services\DocumentAnalysis;

use App\Integrations\OpenAi\DocumentAnalysisClient;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AnalysisPipeline
{
    public function __construct(private readonly DocumentAnalysisClient $client, private readonly AnalysisReportValidator $validator) {}

    public function analyze(Opportunity $opportunity, array $context, array $prepared): array
    {
        $contextPart = ['type' => 'input_text', 'text' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
        if (count($prepared['batches']) === 1) {
            return [...$this->client->analyze([$contextPart, ...$prepared['content']]), 'processing' => ['mode' => 'single', 'batches' => 1, 'calls' => 1]];
        }
        $facts = [];
        $responses = [];
        $reused = 0;
        $fingerprint = hash('sha256', json_encode([$context, array_column($prepared['batches'], 'keys'),
            config('document_analysis.model'), config('document_analysis.reasoning_effort'),
            config('document_analysis.max_output_tokens'), config('document_analysis.batch_text_chars'), config('document_analysis.max_text_chars'),
            AnalysisSchema::extractionInstructions()], JSON_THROW_ON_ERROR));
        $path = 'documents/opportunities/'.$opportunity->id.'/analysis/progress.json';
        $disk = Storage::disk('local');
        $progress = null;
        if ($disk->exists($path) && $disk->size($path) <= 20 * 1024 * 1024) {
            $progress = json_decode($disk->get($path), true);
        }
        $completed = ($progress['fingerprint'] ?? null) === $fingerprint && is_array($progress['batches'] ?? null) ? $progress['batches'] : [];
        foreach ($prepared['batches'] as $index => $batch) {
            $sources = array_values(array_filter($prepared['sources'], fn ($source) => in_array($source['key'], $batch['keys'])));
            $batchContext = ['type' => 'input_text', 'text' => json_encode([...$context,
                'documents' => array_values(array_filter($context['documents'], fn ($source) => in_array($source['key'], $batch['keys']))),
                'batch' => $index + 1, 'batch_count' => count($prepared['batches']), 'batch_sources' => $batch['keys']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
            $result = $completed[$index] ?? null;
            $cached = is_array($result) && isset($result['analysis'], $result['model']);
            Log::info('Etapa de análise documental', ['opportunity_id' => $opportunity->id, 'stage' => 'extraction', 'batch' => $index + 1, 'batches' => count($prepared['batches']), 'cached' => $cached]);
            if (! $cached) {
                $result = $this->client->analyze([$batchContext, ...$batch['content']], AnalysisSchema::extractionInstructions());
            }
            $facts[] = $this->validator->validate($result['analysis'], $sources, $opportunity, true);
            $responses[] = $result;
            if ($cached) {
                $reused++;
            } else {
                // Store only structured findings/citations, never PDF text or request payloads.
                $completed[$index] = $result;
                $temporary = dirname($path).'/.'.Str::uuid().'.tmp';
                try {
                    abort_unless($disk->put($temporary, json_encode(['fingerprint' => $fingerprint, 'batches' => $completed], JSON_THROW_ON_ERROR)) && $disk->move($temporary, $path), 503, 'Não foi possível preservar os lotes concluídos. O relatório anterior foi mantido.');
                } finally {
                    $disk->delete($temporary);
                }
            }
        }
        $factsText = json_encode(['validated_batch_facts' => $facts], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (mb_strlen($factsText) + mb_strlen($contextPart['text']) > config('document_analysis.max_text_chars')) {
            throw ValidationException::withMessages(['analysis' => 'As evidências extraídas excedem o limite da consolidação. Aumente OPENAI_ANALYSIS_MAX_TEXT_CHARS; as etapas realizadas podem ter consumido créditos. O relatório anterior foi preservado e nenhum dado foi truncado.']);
        }
        Log::info('Etapa de análise documental', ['opportunity_id' => $opportunity->id, 'stage' => 'consolidation', 'batches' => count($prepared['batches']), 'reused_batches' => $reused]);
        $result = $this->client->analyze([$contextPart, ['type' => 'input_text', 'text' => $factsText]], AnalysisSchema::consolidationInstructions());
        // The consolidation must not erase uncertainty detected in extraction.
        $result['reference_warnings'] = array_values(array_unique(array_merge(...array_map(
            fn ($fact) => in_array(AnalysisReportValidator::REFERENCE_WARNING, $fact['warnings']) ? $fact['warnings'] : [], $facts,
        ))));
        $responses[] = $result;
        $result['usage'] = $this->combinedUsage($responses);
        $result['processing'] = ['mode' => 'staged', 'batches' => count($prepared['batches']), 'calls' => count($responses) - $reused, 'reused_batches' => $reused];

        return $result;
    }

    private function combinedUsage(array $responses): ?array
    {
        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'input_tokens_details' => ['cached_tokens' => 0]];
        foreach ($responses as $response) {
            $part = $response['usage'] ?? null;
            if (! is_array($part) || ! is_int($part['input_tokens'] ?? null) || ! is_int($part['output_tokens'] ?? null)) {
                return null;
            }
            $usage['input_tokens'] += max(0, $part['input_tokens']);
            $usage['output_tokens'] += max(0, $part['output_tokens']);
            $usage['input_tokens_details']['cached_tokens'] += min(max(0, $part['input_tokens']), max(0, (int) ($part['input_tokens_details']['cached_tokens'] ?? 0)));
        }

        return $usage;
    }
}
