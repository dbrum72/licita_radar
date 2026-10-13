<?php

namespace App\Services;

use App\Models\Opportunity;

class OpportunityAnalysisService
{
    public function detail(Opportunity $opportunity): array
    {
        app(OpportunityLifecycleService::class)->pruneExpiredDiscarded();
        abort_unless(Opportunity::whereKey($opportunity->id)->exists(), 404);
        $opportunity->load('documents', 'requirements.evidence');
        $opportunity->setAttribute('discarded', $opportunity->dismissal()->exists());
        $companyMatch = app(CompanyScopeService::class)->assess($opportunity->object, $opportunity->deadline);
        $opportunity->setAttribute('company_match', $companyMatch);
        $requirements = $opportunity->requirements;
        $incomplete = $opportunity->documents->isEmpty() || in_array($opportunity->attachments_status, ['failed', 'partial', 'running']) || $opportunity->documents->contains(fn ($d) => $d->status !== 'ready');
        $invalidEvidence = $requirements->contains(function ($req) use ($opportunity) {
            if ($req->status !== 'met') {
                return false;
            }
            $doc = $req->evidence;
            $date = $opportunity->deadline?->copy()->max(now()) ?? now();

            return ! $doc || ! in_array($doc->status, ['ready', 'needs_ocr'])
                || ! collect($doc->pages ?? [])->contains('number', $req->evidence_page)
                || ($doc->expires_at && $doc->expires_at->copy()->endOfDay()->lt($date));
        });
        $verdict = $opportunity->scope_status === 'excluded' ? 'Fora do escopo inicial' :
            (($opportunity->deadline && ! $opportunity->hasOpenDeadline()) ? 'Prazo encerrado' :
            ($requirements->contains('status', 'unmet') ? 'Há requisitos não atendidos' :
            (($incomplete || $invalidEvidence || $requirements->isEmpty() || $requirements->contains('status', 'pending')) ? 'Análise documental pendente' : 'Requisitos cadastrados revisados')));
        foreach ($requirements as $req) {
            $req->setAttribute('candidates', app(DocumentService::class)->candidates($req));
        }
        if ($companyMatch['status'] !== 'compatible') {
            $verdict = 'Compatibilidade com o acervo pendente ou não demonstrada';
        }

        return ['opportunity' => $opportunity, 'verdict' => $verdict, 'notice' => 'A extração é uma triagem e pode omitir exigências. Revise integralmente o edital e cadastre requisitos faltantes. Análise econômica ainda não implementada.'];
    }
}
