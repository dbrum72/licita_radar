<?php

namespace App\Services\Digests;

use App\Mail\OpportunityDigest;
use App\Models\Opportunity;
use App\Models\SearchProfile;
use App\Services\CompanyDocuments\CompanyScopeService;
use App\Services\Opportunities\ScopeClassifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DigestService
{
    public function __construct(private readonly ScopeClassifier $scopeClassifier, private readonly CompanyScopeService $companyScopeService) {}

    public function send(): int
    {
        if (in_array(config('mail.default'), ['log', 'array'])) {
            return 0;
        }

        return Cache::lock('radar-digest', 3600)->block(1, function () {
            $sent = 0;
            $compatibleIds = $this->companyScopeService->compatibleIds();
            foreach (SearchProfile::where('active', true)->whereNotNull('email')->get() as $profile) {
                $items = DB::table('profile_opportunity as p')->join('opportunities as o', 'o.id', '=', 'p.opportunity_id')
                    ->where('p.search_profile_id', $profile->id)->whereNull('p.notified_at')
                    ->whereIn('o.id', $compatibleIds)
                    ->where('o.scope_status', 'relevant')->where('o.deadline', '>=', Opportunity::openDeadlineBoundary())->get(['o.*', 'p.id as notification_id'])
                    ->filter(fn ($item) => $this->scopeClassifier->matches((array) $item, $profile));
                if ($items->isEmpty()) {
                    continue;
                }
                Mail::to($profile->email)->send(new OpportunityDigest($profile, $items, (string) collect(config('cors.allowed_origins'))->first()));
                DB::table('profile_opportunity')->whereIn('id', $items->pluck('notification_id'))->update(['notified_at' => now()]);
                $sent++;
            }

            return $sent;
        });
    }
}
