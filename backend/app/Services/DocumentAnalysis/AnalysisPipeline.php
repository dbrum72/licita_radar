<?php

namespace App\Services\DocumentAnalysis;

use App\Integrations\OpenAi\DocumentAnalysisClient;
use App\Models\Opportunity;
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
        foreach ($prepared['batches'] as $index => $batch) {
            $sources = array_values(array_filter($prepared['sources'], fn ($source) => in_array($source['key'], $batch['keys'])));
            $batchContext = ['type' => 'input_text', 'text' => json_encode([...$context, 'batch' => $index + 1, 'batch_count' => count($prepared['batches']), 'batch_sources' => $batch['keys']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
            $result = $this->client->analyze([$batchContext, ...$batch['content']], AnalysisSchema::extractionInstructions());
            $facts[] = $this->validator->validate($result['analysis'], $sources, $opportunity, true);
            $responses[] = $result;
        }
        $factsText = json_encode(['validated_batch_facts' => $facts], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (mb_strlen($factsText) + mb_strlen($contextPart['text']) > config('document_analysis.max_text_chars')) {
            throw ValidationException::withMessages(['analysis' => 'As evidências extraídas excedem o limite da consolidação. Aumente OPENAI_ANALYSIS_MAX_TEXT_CHARS; as etapas realizadas podem ter consumido créditos. O relatório anterior foi preservado e nenhum dado foi truncado.']);
        }
        $result = $this->client->analyze([$contextPart, ['type' => 'input_text', 'text' => $factsText]], AnalysisSchema::consolidationInstructions());
        $responses[] = $result;
        $result['usage'] = $this->combinedUsage($responses);
        $result['processing'] = ['mode' => 'staged', 'batches' => count($prepared['batches']), 'calls' => count($responses)];

        return $result;
    }

    private function combinedUsage(array $responses): ?array
    {
        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'input_tokens_details' => ['cached_tokens' => 0]];
        foreach ($responses as $response) {
            $part = $response['usage'] ?? null;
            if (! is_array($part) || ! is_int($part['input_tokens'] ?? null) || ! is_int($part['output_tokens'] ?? null)) return null;
            $usage['input_tokens'] += max(0, $part['input_tokens']);
            $usage['output_tokens'] += max(0, $part['output_tokens']);
            $usage['input_tokens_details']['cached_tokens'] += min(max(0, $part['input_tokens']), max(0, (int) ($part['input_tokens_details']['cached_tokens'] ?? 0)));
        }

        return $usage;
    }
}
