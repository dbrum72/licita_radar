<?php

namespace App\Services\DocumentAnalysis;

use App\Models\Opportunity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AnalysisReportValidator
{
    public const REFERENCE_WARNING = 'Há referências da IA não confirmadas nos documentos, páginas ou textos dos PDFs. Elas foram removidas das evidências; confira os documentos originais antes de usar as conclusões.';

    public function validate(array $analysis, array $sources, Opportunity $opportunity, bool $partial = false, array $referenceWarnings = []): array
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
        if ($validator->fails()) {
            Log::warning('Formato inválido na análise documental', ['opportunity_id' => $opportunity->id,
                'partial' => $partial, 'failed_rules' => array_map('array_keys', $validator->failed())]);
            abort(502, 'O relatório da IA não corresponde ao formato esperado. O relatório anterior foi preservado.');
        }
        $analysis = $validator->validated();
        $unverifiedQuotes = $referenceWarnings;
        $byKey = collect($sources)->keyBy('key')->all();
        $analysis['scope']['sources'] = $this->references($analysis['scope']['sources'], $byKey, null, $unverifiedQuotes);
        foreach ($analysis['deadlines'] as &$deadline) {
            $deadline['sources'] = $this->references($deadline['sources'], $byKey, 'tender', $unverifiedQuotes);
            if (! $deadline['sources']) {
                $analysis['warnings'][] = 'Prazo sem referência documental: '.$deadline['label'].'. Confirme no edital.';
            }
        }
        unset($deadline);
        $covered = [];
        foreach ($analysis['qualifications'] as &$qualification) {
            $qualification['sources'] = $this->references($qualification['sources'], $byKey, 'company', $unverifiedQuotes);
            $covered = array_merge($covered, array_column($qualification['sources'], 'source_key'));
            if (! $qualification['sources']) {
                $analysis['warnings'][] = 'Classificação sem referência documental: '.$qualification['name'].'.';
            }
        }
        unset($qualification);
        $boundary = $opportunity->deadline?->copy()->max(now())->toDateString() ?? today()->toDateString();
        foreach ($analysis['requirements'] as &$requirement) {
            $requirement['sources'] = $this->references($requirement['sources'], $byKey, 'tender', $unverifiedQuotes);
            $requirement['evidence'] = $this->references($requirement['evidence'], $byKey, 'company', $unverifiedQuotes);
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
        if ($unverifiedQuotes) {
            $notice = self::REFERENCE_WARNING;
            $analysis['warnings'] = array_values(array_unique([...$analysis['warnings'], $notice, ...$unverifiedQuotes]));
            $analysis['summary'] = $notice.' '.$analysis['summary'];
            $analysis['scope']['status'] = 'inconclusivo';
            $analysis['scope']['reason'] = $notice;
            foreach ($analysis['requirements'] as &$requirement) {
                $requirement['status'] = 'inconclusiva';
                $requirement['gaps'][] = $notice;
            }
            unset($requirement);
        }
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

    private function references(array $references, array $sources, ?string $scope, array &$unverifiedQuotes): array
    {
        $verified = [];
        foreach ($references as &$reference) {
            $source = $sources[$reference['source_key']] ?? null;
            if (! $source || ! in_array($source['reading'], ['text', 'visual']) || ($scope && $source['scope'] !== $scope)) {
                $unverifiedQuotes[] = 'Referência removida: documento ausente, fora do lote ou incompatível com a origem exigida (edital/acervo).';

                continue;
            }
            if ($source['page_count'] && $reference['page'] > $source['page_count']) {
                $unverifiedQuotes[] = 'Referência removida: página inexistente em '.$source['name'].'.';

                continue;
            }
            $reference['source_name'] = $source['name'];
            $reference['verification'] = $source['page_count'] ? 'visual_review' : 'page_unverified';
            if ($source['reading'] === 'text') {
                $page = collect($source['pages'])->firstWhere('number', $reference['page']);
                $quote = $this->normalize($reference['quote']);
                if (! $page || $quote === '' || ! str_contains($this->normalize($page['text']), $quote)) {
                    $unverifiedQuotes[] = 'Citação não confirmada removida: '.$source['name'].', página '.$reference['page'].'.';

                    continue;
                }
                $reference['verification'] = 'text_match';
            }
            $verified[] = $reference;
        }
        unset($reference);

        return $verified;
    }

    private function normalize(string $text): string
    {
        // Normalize typography only; never drop words, numbers, accents or negation.
        $text = str_replace(["\u{00AD}", "\u{200B}", "\u{FEFF}"], '', $text);
        $text = strtr($text, ['“' => '"', '”' => '"', '‘' => "'", '’' => "'", 'ﬁ' => 'fi', 'ﬂ' => 'fl']);
        $text = preg_replace('/(\p{L})-\h*\R\h*(?=\p{L})/u', '$1', $text);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text)));
    }
}
