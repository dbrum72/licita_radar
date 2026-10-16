<?php

namespace App\Services\DocumentAnalysis;

use App\Models\Opportunity;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpportunityDocumentAnalysis
{
    public function __construct(private readonly AnalysisDocuments $documents, private readonly AnalysisPipeline $pipeline, private readonly AnalysisReportValidator $validator) {}

    private function path(Opportunity $opportunity): string
    {
        return 'documents/opportunities/'.$opportunity->id.'/analysis/report.json';
    }

    public function state(Opportunity $opportunity): array
    {
        $report = $this->read($opportunity);

        return ['configured' => filled(config('document_analysis.api_key')), 'model' => config('document_analysis.model'), 'report' => $report,
            'stale' => $report ? $report['fingerprint'] !== $this->documents->fingerprint($opportunity, $this->documents->inventory($opportunity)) : false];
    }

    private function read(Opportunity $opportunity): ?array
    {
        $disk = Storage::disk('local');
        $path = $this->path($opportunity);
        if (! $disk->exists($path)) {
            return null;
        }
        abort_if($disk->size($path) > 5 * 1024 * 1024, 500, 'O relatório salvo excede o tamanho permitido.');
        try {
            $report = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            abort(500, 'Não foi possível ler o relatório salvo.');
        }
        abort_unless(is_array($report) && ($report['opportunity']['id'] ?? null) === $opportunity->id && isset($report['fingerprint'], $report['analysis'], $report['sources']), 500, 'O relatório salvo é inválido.');

        return $report;
    }

    public function generate(Opportunity $opportunity, bool $force = false): array
    {
        $lock = Cache::lock('opportunity-document-analysis:'.$opportunity->id, config('document_analysis.timeout') * config('document_analysis.max_calls') + 600);
        abort_unless($lock->get(), 409, 'A análise desta oportunidade já está em andamento.');
        try {
            $sources = $this->documents->inventory($opportunity);
            $fingerprint = $this->documents->fingerprint($opportunity, $sources);
            $previous = $this->read($opportunity);
            if (! $force && $previous && $previous['fingerprint'] === $fingerprint) {
                return [...$this->state($opportunity), 'cached' => true];
            }
            if (! filled(config('document_analysis.api_key'))) {
                throw ValidationException::withMessages(['analysis' => 'Configure OPENAI_API_KEY no backend para gerar a análise. Não informe a chave no navegador.']);
            }
            $prepared = $this->documents->prepare($opportunity, $sources);
            $context = ['opportunity' => $this->documents->opportunity($opportunity), 'analysis_date' => today('America/Sao_Paulo')->toDateString(),
                'documents' => array_map($this->documents->publicSource(...), $prepared['sources']), 'warnings' => $prepared['warnings']];
            $result = $this->pipeline->analyze($opportunity, $context, $prepared);
            $analysis = $this->validator->validate($result['analysis'], $prepared['sources'], $opportunity);
            $analysis['warnings'] = array_values(array_unique([...$prepared['warnings'], ...$analysis['warnings']]));
            $opportunity->refresh();
            abort_unless($fingerprint === $this->documents->fingerprint($opportunity, $this->documents->inventory($opportunity)), 409, 'Os documentos ou dados da oportunidade mudaram durante a análise. O relatório anterior foi preservado; atualize e tente novamente.');
            $report = ['version' => 2, 'fingerprint' => $fingerprint, 'generated_at' => now('America/Sao_Paulo')->toIso8601String(), 'processing' => $result['processing'],
                'opportunity' => $this->documents->opportunity($opportunity), 'model' => $result['model'], 'usage' => $this->usage($result['usage']),
                'sources' => array_map($this->documents->publicSource(...), $prepared['sources']), 'analysis' => $analysis,
                'review_required' => true, 'notice' => 'Análise documental assistida por IA. Revise os documentos originais; este relatório não constitui aprovação de habilitação.'];
            $path = $this->path($opportunity);
            $temporary = dirname($path).'/.'.Str::uuid().'.tmp';
            $disk = Storage::disk('local');
            try {
                if (! $disk->put($temporary, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) || ! $disk->move($temporary, $path)) {
                    throw new \RuntimeException('Não foi possível armazenar o relatório.');
                }
            } finally {
                if ($disk->exists($temporary)) {
                    $disk->delete($temporary);
                }
            }

            return ['configured' => true, 'model' => config('document_analysis.model'), 'report' => $report, 'stale' => false, 'cached' => false];
        } finally {
            $lock->release();
        }
    }

    private function usage($usage): ?array
    {
        if (! is_array($usage) || ! is_int($usage['input_tokens'] ?? null) || ! is_int($usage['output_tokens'] ?? null)) {
            return null;
        }
        $input = max(0, $usage['input_tokens']);
        $output = max(0, $usage['output_tokens']);
        $cached = min($input, max(0, (int) ($usage['input_tokens_details']['cached_tokens'] ?? 0)));
        $estimate = config('document_analysis.model') === config('document_analysis.pricing_model')
            ? (($input - $cached) * config('document_analysis.input_usd_per_million') + $cached * config('document_analysis.cached_input_usd_per_million') + $output * config('document_analysis.output_usd_per_million')) / 1000000 : null;

        return ['input_tokens' => $input, 'cached_input_tokens' => $cached, 'output_tokens' => $output,
            'total_tokens' => $input + $output, 'estimated_cost_usd' => $estimate === null ? null : round($estimate, 6), 'pricing_model' => config('document_analysis.pricing_model'),
            'rates_usd_per_million' => ['input' => config('document_analysis.input_usd_per_million'), 'cached_input' => config('document_analysis.cached_input_usd_per_million'), 'output' => config('document_analysis.output_usd_per_million')]];
    }

    public function pdf(Opportunity $opportunity): string
    {
        $state = $this->state($opportunity);
        abort_unless($state['report'], 404, 'Ainda não existe relatório para esta oportunidade.');
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', storage_path('app/private'));
        $options->set('fontCache', storage_path('app/private'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('pdf.document-analysis', ['report' => $state['report'], 'stale' => $state['stale']])->render());
        $pdf->render();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $pdf->getCanvas()->page_text(36, 814, 'LicitaRadar - relatório documental - {PAGE_NUM}/{PAGE_COUNT}', $font, 8, [0.35, 0.42, 0.43]);

        return $pdf->output();
    }

    public function source(Opportunity $opportunity, string $key): array
    {
        $report = $this->read($opportunity);
        abort_unless($report, 404);
        $expected = collect($report['sources'])->firstWhere('key', $key);
        $current = collect($this->documents->inventory($opportunity))->firstWhere('key', $key);
        abort_unless($expected && $current && $current['exists'], 404, 'O PDF citado não está mais disponível no storage.');
        abort_unless($expected['sha256'] === $current['sha256'], 409, 'O PDF mudou desde a análise. Gere um relatório atualizado antes de abrir esta referência.');

        return $current;
    }
}
