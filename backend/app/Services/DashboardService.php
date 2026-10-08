<?php

namespace App\Services;

use App\Models\CompanyDocument;
use App\Models\Opportunity;
use App\Models\Requirement;
use App\Models\SyncRun;

class DashboardService
{
    public function summary(): array
    {
        app(OpportunityLifecycleService::class)->pruneExpiredDiscarded();
        $ids = app(CompanyScopeService::class)->compatibleIds();

        return ['total' => count($ids), 'open' => Opportunity::whereIn('id', $ids)->where('deadline', '>', now())->count(),
            'relevant' => Opportunity::whereIn('id', $ids)->where('deadline', '>', now())->count(),
            'pending' => Requirement::where('status', 'pending')->count(), 'documents' => CompanyDocument::count(),
            'runs' => SyncRun::latest()->limit(8)->get(),
            'expiring' => CompanyDocument::whereNotNull('expires_at')->where('expires_at', '<=', today()->addDays(30))->get(['id', 'name', 'expires_at'])];
    }
}
