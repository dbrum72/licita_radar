<?php

namespace App\Services\DocumentAnalysis;

use App\Models\CompanyDocument;
use App\Models\Opportunity;
use App\Services\Documents\PdfTextReader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AnalysisDocuments
{
    public function __construct(private readonly PdfTextReader $reader) {}

    public function inventory(Opportunity $opportunity): array
    {
        $sources = [];
        $paths = [];
        foreach (['tender' => $opportunity->documents()->orderBy('id')->get(), 'company' => CompanyDocument::orderBy('id')->get()] as $scope => $documents) {
            foreach ($documents as $document) {
                $path = $document->getRawOriginal('path');
                $paths[$path] = true;
                $sources[] = $this->source($scope.'-'.$document->id, $scope, $path, $document->name, $document->expires_at?->toDateString());
            }
        }
        foreach (['tender' => 'documents/opportunities/'.$opportunity->id, 'company' => 'company-documents'] as $scope => $directory) {
            foreach (Storage::disk('local')->allFiles($directory) as $path) {
                if (isset($paths[$path]) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }
                $paths[$path] = true;
                $sources[] = $this->source($scope.'-file-'.substr(hash('sha256', $path), 0, 24), $scope, $path, basename($path), null);
            }
        }
        usort($sources, fn ($a, $b) => strcmp($a['key'], $b['key']));

        return $sources;
    }

    private function source(string $key, string $scope, string $path, string $name, ?string $expiresAt): array
    {
        $disk = Storage::disk('local');
        $exists = $disk->exists($path);

        return ['key' => $key, 'scope' => $scope, 'path' => $path, 'name' => $name, 'expires_at' => $expiresAt,
            'exists' => $exists, 'bytes' => $exists ? $disk->size($path) : 0,
            'sha256' => $exists ? hash_file('sha256', $disk->path($path)) : null];
    }

    public function fingerprint(Opportunity $opportunity, array $sources): string
    {
        return hash('sha256', json_encode(['version' => 2, 'date' => today('America/Sao_Paulo')->toDateString(),
            'model' => config('document_analysis.model'), 'reasoning_effort' => config('document_analysis.reasoning_effort'),
            'opportunity' => $this->opportunity($opportunity), 'sources' => $sources], JSON_THROW_ON_ERROR));
    }

    public function opportunity(Opportunity $opportunity): array
    {
        return ['id' => $opportunity->id, 'pncp_id' => $opportunity->pncp_id, 'object' => $opportunity->object,
            'agency' => $opportunity->agency, 'state' => $opportunity->state, 'city' => $opportunity->city,
            'deadline_pncp' => $opportunity->deadline?->toIso8601String(), 'pncp_url' => $opportunity->pncp_url,
            'attachments_status' => $opportunity->attachments_status];
    }

    public function prepare(Opportunity $opportunity, array $sources): array
    {
        $textLimit = (int) config('document_analysis.max_text_chars');
        if ($textLimit < 8192 || config('document_analysis.max_calls') < 1) {
            throw ValidationException::withMessages(['analysis' => 'Configure pelo menos 8192 caracteres por chamada e uma chamada de IA no backend.']);
        }
        // Keep extraction batches smaller than the final consolidation budget.
        $textLimit = min($textLimit, max(8192, (int) config('document_analysis.batch_text_chars')));
        if (count($sources) > config('document_analysis.max_documents') || array_sum(array_column($sources, 'bytes')) > config('document_analysis.max_total_bytes')) {
            throw ValidationException::withMessages(['analysis' => 'O conjunto excede os limites de documentos ou tamanho da análise. Ajuste os limites no backend antes de prosseguir; nenhum documento foi omitido silenciosamente.']);
        }
        $units = [];
        $warnings = [];
        $prepared = [];
        $textChars = $visualBytes = 0;
        foreach ($sources as $source) {
            $source['page_count'] = null;
            $source['reading'] = 'missing';
            $source['pages'] = [];
            if (! $source['exists']) {
                $warnings[] = 'Arquivo ausente: '.$source['name'].'.';
                $prepared[] = $source;

                continue;
            }
            $bytes = Storage::disk('local')->get($source['path']);
            abort_unless(hash('sha256', $bytes) === $source['sha256'], 409, 'Um PDF mudou durante a leitura. Atualize a tela e tente novamente.');
            if (! str_starts_with(ltrim($bytes), '%PDF-')) {
                $source['reading'] = 'invalid';
                $warnings[] = 'Conteúdo não PDF: '.$source['name'].'.';
                $prepared[] = $source;

                continue;
            }
            $pages = $this->reader->fromBytes($bytes);
            $source['page_count'] = $pages ? count($pages) : null;
            $visual = ! $pages || collect($pages)->contains(fn ($page) => mb_strlen(trim($page['text'])) < 25);
            $source['reading'] = $visual ? 'visual' : 'text';
            $source['pages'] = $pages;
            $metadata = $this->publicSource($source);
            if ($visual) {
                $visualBytes += strlen($bytes);
                if (strlen($bytes) > config('document_analysis.max_visual_bytes')) {
                    throw ValidationException::withMessages(['analysis' => 'O PDF '.$source['name'].' excede o limite visual de uma chamada. Divida este arquivo mantendo a referência ao original antes de analisar.']);
                }
                $note = json_encode(['document' => $metadata, 'note' => 'O próximo PDF pertence a esta chave; cite as páginas físicas do PDF, começando em 1.'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $units[] = ['keys' => [$source['key']], 'text_chars' => mb_strlen($note), 'visual_bytes' => strlen($bytes), 'content' => [
                    ['type' => 'input_text', 'text' => $note],
                    ['type' => 'input_file', 'filename' => $source['key'].'.pdf', 'file_data' => 'data:application/pdf;base64,'.base64_encode($bytes), 'detail' => 'high'],
                ]];
                $warnings[] = 'Leitura visual utilizada: '.$source['name'].'. Confira as evidências citadas no original.';
            } else {
                $parts = $this->textUnits($metadata, $pages, $textLimit);
                $textChars += array_sum(array_column($parts, 'text_chars'));
                $units = [...$units, ...$parts];
            }
            $prepared[] = $source;
        }
        foreach (['tender', 'company'] as $scope) {
            if (! collect($prepared)->contains(fn ($source) => $source['scope'] === $scope && in_array($source['reading'], ['text', 'visual']))) {
                throw ValidationException::withMessages(['analysis' => $scope === 'tender' ? 'Adicione ou baixe os PDFs desta oportunidade antes de analisar.' : 'Adicione PDFs da empresa ao acervo antes de analisar.']);
            }
        }
        if ($opportunity->attachments_status !== 'ready') {
            $warnings[] = 'A busca PNCP não está marcada como completa. Podem existir anexos ou retificações ainda não baixados.';
        }

        $batches = $this->batches($units, $textLimit);
        $calls = count($batches) === 1 ? 1 : count($batches) + 1;
        if ($calls > config('document_analysis.max_calls')) {
            throw ValidationException::withMessages(['analysis' => 'Este conjunto exige '.$calls.' chamadas de IA, acima do limite de '.config('document_analysis.max_calls').'. Ajuste OPENAI_ANALYSIS_MAX_CALLS para autorizar esse volume; nenhum documento foi omitido ou enviado.']);
        }

        return ['sources' => $prepared, 'content' => array_merge(...array_column($units, 'content')), 'warnings' => $warnings,
            'batches' => $batches, 'text_chars' => $textChars, 'visual_bytes' => $visualBytes];
    }

    private function textUnits(array $metadata, array $pages, int $limit): array
    {
        $budget = intdiv($limit - min(16384, intdiv($limit, 4)), 2);
        $segmentSize = $budget - mb_strlen(json_encode($metadata, JSON_UNESCAPED_UNICODE)) - 512;
        $units = [];
        $group = [];
        foreach ($pages as $page) {
            // Very long physical pages are split with overlap; numbering and all text are preserved.
            $fragments = [];
            for ($offset = 0, $length = mb_strlen($page['text']); $offset < $length; $offset += max(1, $segmentSize - 256)) {
                $fragments[] = ['number' => $page['number'], 'text' => mb_substr($page['text'], $offset, $segmentSize)];
                if ($offset + $segmentSize >= $length) {
                    break;
                }
            }
            foreach ($fragments as $fragment) {
                $candidate = json_encode(['document' => $metadata, 'pages' => [...$group, $fragment]], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if ($group && mb_strlen($candidate) > $budget) {
                    $units[] = $this->textUnit($metadata, $group);
                    $group = [];
                }
                $group[] = $fragment;
            }
        }
        if ($group) {
            $units[] = $this->textUnit($metadata, $group);
        }

        return $units;
    }

    private function textUnit(array $metadata, array $pages): array
    {
        $text = json_encode(['document' => $metadata, 'pages' => $pages], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return ['keys' => [$metadata['key']], 'text_chars' => mb_strlen($text), 'visual_bytes' => 0, 'content' => [['type' => 'input_text', 'text' => $text]]];
    }

    private function batches(array $units, int $limit): array
    {
        $budget = $limit - min(16384, intdiv($limit, 4));
        $batches = [];
        $batch = ['keys' => [], 'content' => [], 'text_chars' => 0, 'visual_bytes' => 0];
        foreach ($units as $unit) {
            if ($batch['content'] && ($budget < $batch['text_chars'] + $unit['text_chars'] || config('document_analysis.max_visual_bytes') < $batch['visual_bytes'] + $unit['visual_bytes'])) {
                $batches[] = $batch;
                $batch = ['keys' => [], 'content' => [], 'text_chars' => 0, 'visual_bytes' => 0];
            }
            $batch['keys'] = array_values(array_unique([...$batch['keys'], ...$unit['keys']]));
            $batch['content'] = [...$batch['content'], ...$unit['content']];
            $batch['text_chars'] += $unit['text_chars'];
            $batch['visual_bytes'] += $unit['visual_bytes'];
        }
        if ($batch['content']) {
            $batches[] = $batch;
        }

        return $batches;
    }

    public function publicSource(array $source): array
    {
        return array_intersect_key($source, array_flip(['key', 'scope', 'name', 'expires_at', 'exists', 'bytes', 'sha256', 'page_count', 'reading']));
    }
}
