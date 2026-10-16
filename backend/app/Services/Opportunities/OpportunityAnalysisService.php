<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Services\CompanyDocuments\CompanyScopeService;
use App\Services\DocumentAnalysis\OpportunityDocumentAnalysis;

class OpportunityAnalysisService
{
    public function __construct(private readonly CompanyScopeService $companyScopeService, private readonly OpportunityLifecycleService $opportunityLifecycleService, private readonly OpportunityDocumentAnalysis $documentAnalysis) {}

    public function detail(Opportunity $opportunity): array
    {
        $this->opportunityLifecycleService->pruneExpiredDiscarded();
        abort_unless(Opportunity::whereKey($opportunity->id)->exists(), 404);
        $opportunity->load('documents');
        $opportunity->setAttribute('discarded', $opportunity->dismissal()->exists());
        $opportunity->setAttribute('company_match', $this->companyScopeService->assess($opportunity->object, $opportunity->deadline));

        return ['opportunity' => $opportunity, 'notice' => $this->companyScopeService->notice(), 'analysis' => $this->documentAnalysis->state($opportunity)];
    }
}
