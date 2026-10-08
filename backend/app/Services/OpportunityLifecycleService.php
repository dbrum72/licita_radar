<?php

namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpportunityLifecycleService
{
    public function bookmark(Opportunity $opportunity, bool $bookmarked): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $bookmarked) {
            $current = Opportunity::lockForUpdate()->findOrFail($opportunity->id);
            if ($bookmarked && (! $current->deadline || ! $current->deadline->gt(now()))) {
                throw ValidationException::withMessages(['bookmarked' => 'Salvar permitido somente antes do vencimento.']);
            }
            $current->update(['bookmarked' => $bookmarked]);
            if ($bookmarked) {
                $current->dismissal()->delete();
            }

            return $current;
        });
    }

    public function pruneExpiredDiscarded(): int
    {
        $deleted = 0;
        Opportunity::whereHas('dismissal')->where('deadline', '<=', now())->select('id')
            ->chunkById(250, function ($items) use (&$deleted) {
                foreach ($items as $item) {
                    $deleted += DB::transaction(function () use ($item) {
                        $op = Opportunity::lockForUpdate()->find($item->id);
                        if (! $op || ! $op->deadline || $op->deadline->gt(now()) || ! $op->dismissal()->exists()) {
                            return 0;
                        }
                        DB::table('pncp_consultations')->insertOrIgnore(['pncp_id' => $op->pncp_id, 'first_seen_at' => $op->created_at ?? now()]);
                        $op->delete(); // Foreign keys remove tender documents, requirements, profile links and dismissal.

                        return 1;
                    });
                }
            });

        return $deleted;
    }

    public function discard(Opportunity $opportunity, bool $discarded, int $userId): array
    {
        $restorable = DB::transaction(function () use ($opportunity, $discarded, $userId) {
            $current = Opportunity::lockForUpdate()->find($opportunity->id);
            if (! $current) {
                return false;
            }
            if (! $discarded && (! $current->deadline || ! $current->deadline->gt(now()))) {
                return false;
            }
            if ($discarded) {
                $current->update(['bookmarked' => false]);
                $current->dismissal()->firstOrCreate([], ['user_id' => $userId]);
            } else {
                $current->dismissal()->delete();
            }

            return true;
        });
        $this->pruneExpiredDiscarded();
        if (! $restorable) {
            throw ValidationException::withMessages(['discarded' => 'Restauração permitida somente antes do vencimento. Descartadas vencidas são excluídas.']);
        }

        return ['id' => $opportunity->id, 'discarded' => $discarded];
    }
}
