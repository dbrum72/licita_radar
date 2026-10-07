<?php
namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;

class OpportunityLifecycleService
{
    public function pruneExpiredDiscarded(): int
    {
        $deleted = 0;
        Opportunity::whereHas('dismissal')->where('deadline','<=',now())->select('id')
            ->chunkById(250, function ($items) use (&$deleted) {
                foreach ($items as $item) {
                    $deleted += DB::transaction(function () use ($item) {
                        $op = Opportunity::lockForUpdate()->find($item->id);
                        if (!$op || !$op->deadline || $op->deadline->gt(now()) || !$op->dismissal()->exists()) return 0;
                        DB::table('pncp_consultations')->insertOrIgnore(['pncp_id'=>$op->pncp_id,'first_seen_at'=>$op->created_at ?? now()]);
                        $op->delete(); // Foreign keys remove tender documents, requirements, profile links and dismissal.
                        return 1;
                    });
                }
            });
        return $deleted;
    }
}
