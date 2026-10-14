<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Services\CompanyDocuments\CompanyScopeService;

class OpportunityAnalysisService
{
    public function __construct(private readonly CompanyScopeService $companyScopeService, private readonly OpportunityLifecycleService $opportunityLifecycleService) {}

    public function detail(Opportunity $opportunity): array
    {
        $this->opportunityLifecycleService->pruneExpiredDiscarded();
        abort_unless(Opportunity::whereKey($opportunity->id)->exists(), 404);
        $opportunity->load('documents');
        $opportunity->setAttribute('discarded', $opportunity->dismissal()->exists());
        $opportunity->setAttribute('company_match', $this->companyScopeService->assess($opportunity->object, $opportunity->deadline));

        return ['opportunity' => $opportunity, 'notice' => $this->companyScopeService->notice()];
    }
}
