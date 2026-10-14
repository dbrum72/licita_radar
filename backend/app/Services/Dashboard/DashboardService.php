<?php

namespace App\Services\Dashboard;

use App\Models\CompanyDocument;
use App\Models\Opportunity;
use App\Models\SyncRun;
use App\Services\CompanyDocuments\CompanyScopeService;
use App\Services\Opportunities\OpportunityLifecycleService;

class DashboardService
{
    public function __construct(private readonly OpportunityLifecycleService $opportunityLifecycleService, private readonly CompanyScopeService $companyScopeService) {}

    public function summary(): array
    {
        $this->opportunityLifecycleService->pruneExpiredDiscarded();
        $ids = $this->companyScopeService->compatibleIds();

        return ['total' => count($ids), 'open' => Opportunity::whereIn('id', $ids)->where('deadline', '>=', Opportunity::openDeadlineBoundary())->count(),
            'relevant' => Opportunity::whereIn('id', $ids)->where('deadline', '>=', Opportunity::openDeadlineBoundary())->count(),
            'documents' => CompanyDocument::count(),
            'runs' => SyncRun::latest()->limit(8)->get(),
            'expiring' => CompanyDocument::whereNotNull('expires_at')->where('expires_at', '<=', today()->addDays(30))->get(['id', 'name', 'expires_at'])];
    }
}
