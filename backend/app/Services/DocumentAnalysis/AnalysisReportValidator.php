<?php

namespace App\Services\DocumentAnalysis;

use App\Models\Opportunity;
use Illuminate\Support\Facades\Validator;

class AnalysisReportValidator
{
    public function validate(array $analysis, array $sources, Opportunity $opportunity, bool $partial = false): array
    {
        $rules = [
            'summary' => 'required|string|max:12000', 'scope' => 'required|array:status,reason,sources',
            'scope.status' => 'required|in:aderente,parcial,nao_aderente,inconclusivo', 'scope.reason' => 'required|string|max:12000', 'scope.sources' => 'present|array|max:100',
            'deadlines' => 'present|array|max:100', 'qualifications' => 'present|array|max:200', 'requirements' => 'present|array|max:200',
            'warnings' => 'present|array|max:100', 'warnings.*' => 'string|max:4000',
            'deadlines.*' => 'array:label,value,notes,sources', 'deadlines.*.label' => 'required|string|max:1000', 'deadlines.*.value' => 'required|string|max:1000',
            'deadlines.*.notes' => 'present|string|max:4000', 'deadlines.*.sources' => 'present|array|max:30',
            'qualifications.*' => 'array:name,category,holder,valid_until,specialties,quantities,sources',
            'qualifications.*.name' => 'required|string|max:1000', 'qualifications.*.holder' => 'present|string|max:1000',
            'qualifications.*.valid_until' => 'present|nullable|string|max:100', 'qualifications.*.specialties' => 'present|array|max:50',
            'qualifications.*.specialties.*' => 'string|max:1000', 'qualifications.*.quantities' => 'present|string|max:4000', 'qualifications.*.sources' => 'present|array|max:30',
            'requirements.*' => 'array:description,category,mandatory,status,reason,required_quantities,sources,evidence,gaps',
            'requirements.*.description' => 'required|string|max:8000', 'requirements.*.mandatory' => 'required|boolean',
            'requirements.*.status' => 'required|in:comprovada,parcial,nao_comprovada,inconclusiva', 'requirements.*.reason' => 'required|string|max:8000',
            'requirements.*.required_quantities' => 'present|string|max:4000', 'requirements.*.sources' => 'present|array|max:30', 'requirements.*.evidence' => 'present|array|max:30',
            'requirements.*.gaps' => 'present|array|max:50', 'requirements.*.gaps.*' => 'string|max:4000',
        ];
        foreach (['qualifications', 'requirements'] as $group) {
            $rules[$group.'.*.category'] = 'required|in:tecnica_profissional,tecnica_operacional,registro_certificacao,juridica,fiscal,economico_financeira,outros';
        }
        foreach (['scope.sources', 'deadlines.*.sources', 'qualifications.*.sources', 'requirements.*.sources', 'requirements.*.evidence'] as $path) {
            $rules[$path.'.*'] = 'array:source_key,page,quote';
            $rules[$path.'.*.source_key'] = 'required|string|max:100';
            $rules[$path.'.*.page'] = 'required|integer|min:1';
            $rules[$path.'.*.quote'] = 'required|string|max:1200';
        }
        $validator = Validator::make($analysis, $rules);
        abort_if($validator->fails(), 502, 'O relatório da IA não corresponde ao formato esperado. O relatório anterior foi preservado.');
        $analysis = $validator->validated();
        $byKey = collect($sources)->keyBy('key')->all();
        $analysis['scope']['sources'] = $this->references($analysis['scope']['sources'], $byKey);
        foreach ($analysis['deadlines'] as &$deadline) {
            $deadline['sources'] = $this->references($deadline['sources'], $byKey, 'tender');
            if (! $deadline['sources']) {
                $analysis['warnings'][] = 'Prazo sem referência documental: '.$deadline['label'].'. Confirme no edital.';
            }
        }
        unset($deadline);
        $covered = [];
        foreach ($analysis['qualifications'] as &$qualification) {
            $qualification['sources'] = $this->references($qualification['sources'], $byKey, 'company');
            $covered = array_merge($covered, array_column($qualification['sources'], 'source_key'));
            if (! $qualification['sources']) {
                $analysis['warnings'][] = 'Classificação sem referência documental: '.$qualification['name'].'.';
            }
        }
        unset($qualification);
        $boundary = $opportunity->deadline?->copy()->max(now())->toDateString() ?? today()->toDateString();
        foreach ($analysis['requirements'] as &$requirement) {
            $requirement['sources'] = $this->references($requirement['sources'], $byKey, 'tender');
            $requirement['evidence'] = $this->references($requirement['evidence'], $byKey, 'company');
            $unverified = collect([...$requirement['sources'], ...$requirement['evidence']])->contains('verification', 'page_unverified');
            if (! $requirement['sources'] || $unverified || ($requirement['status'] === 'comprovada' && ! $requirement['evidence'])) {
                $requirement['status'] = 'inconclusiva';
                $requirement['gaps'][] = 'Origem, página ou evidência insuficiente para comprovar a exigência. Confira os PDFs originais.';
            }
            if ($requirement['status'] === 'comprovada' && collect($requirement['evidence'])->contains(fn ($reference) => ($byKey[$reference['source_key']]['expires_at'] ?? null) && $byKey[$reference['source_key']]['expires_at'] < $boundary)) {
                $requirement['status'] = 'parcial';
                $requirement['gaps'][] = 'A validade cadastrada de uma evidência não cobre a data atual ou o prazo PNCP. Verifique a exigência de validade no edital.';
            }
        }
        unset($requirement);
        foreach ($sources as $source) {
            if (! $partial && $source['scope'] === 'company' && in_array($source['reading'], ['text', 'visual']) && ! in_array($source['key'], $covered)) {
                $analysis['warnings'][] = 'Documento do acervo não classificado pela IA: '.$source['name'].'.';
            }
            if ($source['reading'] === 'visual' && ! $source['page_count']) {
                $analysis['warnings'][] = 'Quantidade de páginas não verificada localmente: '.$source['name'].'. As referências desse PDF exigem conferência humana.';
            }
        }
        if (! $partial && ! $analysis['requirements']) {
            $analysis['scope']['status'] = 'inconclusivo';
            $analysis['warnings'][] = 'Nenhuma exigência foi identificada. Isso não demonstra habilitação; revise o edital integralmente.';
        }

        return $analysis;
    }

    private function references(array $references, array $sources, ?string $scope = null): array
    {
        foreach ($references as &$reference) {
            $source = $sources[$reference['source_key']] ?? null;
            abort_unless($source && in_array($source['reading'], ['text', 'visual']) && (! $scope || $source['scope'] === $scope), 502, 'A IA citou um documento ausente ou fora do conjunto analisado. O relatório anterior foi preservado.');
            abort_if($source['page_count'] && $reference['page'] > $source['page_count'], 502, 'A IA citou uma página inexistente. O relatório anterior foi preservado.');
            $reference['source_name'] = $source['name'];
            $reference['verification'] = $source['page_count'] ? 'visual_review' : 'page_unverified';
            if ($source['reading'] === 'text') {
                $page = collect($source['pages'])->firstWhere('number', $reference['page']);
                abort_unless($page && str_contains($this->normalize($page['text']), $this->normalize($reference['quote'])), 502, 'Uma citação da IA não foi encontrada em '.$source['name'].', página '.$reference['page'].'. O relatório anterior foi preservado.');
                $reference['verification'] = 'text_match';
            }
        }
        unset($reference);

        return $references;
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text)));
    }
}
