<?php
namespace App\Services;
use Illuminate\Support\Str;

class ScopeClassifier
{
    public function classify(string $object): array
    {
        $text = Str::lower(Str::ascii($object));
        $design = preg_match('/\b(projetos?|anteprojetos?)\b/u', $text);
        $work = preg_match('/\b(execucao|construcao|reforma|implantacao|manutencao)\b.{0,70}\b(obras?|edificac|predial|paviment|ponte)/u', $text);
        if ($work) return ['excluded', 'Objeto inclui execução de obra ou serviço associado. Revisar o edital se houver lotes independentes de projeto.'];
        if ($design && preg_match('/engenharia|arquitet|arquiteton|estrutural|executiv|basico|urbanis|complementar|elaboracao/u', $text)) {
            return ['relevant', 'Objeto menciona elaboração de projeto ou anteprojeto. Habilitação depende da leitura integral do edital.'];
        }
        return ['review', 'Objeto sem evidências suficientes para confirmar o escopo de projetos.'];
    }
    public function matches(array $data, \App\Models\SearchProfile $profile): bool
    {
        if ($profile->states && !in_array($data['state'], $profile->states)) return false;
        if ($profile->modalities && !in_array($data['modality_id'], $profile->modalities)) return false;
        if ($profile->agency && !str_contains(Str::lower(Str::ascii($data['agency'])), Str::lower(Str::ascii($profile->agency)))) return false;
        if (($profile->min_value !== null || $profile->max_value !== null) && $data['estimated_value'] === null) return false;
        if ($profile->min_value !== null && $data['estimated_value'] < $profile->min_value) return false;
        if ($profile->max_value !== null && $data['estimated_value'] > $profile->max_value) return false;
        return $data['deadline'] && \Carbon\Carbon::parse($data['deadline'])->gte(now()->addDays($profile->min_days));
    }
}
