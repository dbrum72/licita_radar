<?php
namespace App\Services;
use App\Models\SearchProfile;
use Illuminate\Support\Facades\{Cache, DB, Mail};
class DigestService
{
    public function send(): int
    {
        if (in_array(config('mail.default'),['log','array'])) return 0;
        return Cache::lock('radar-digest',300)->block(1,function () {
            $sent = 0;
            $compatibleIds = app(CompanyScopeService::class)->compatibleIds();
            foreach (SearchProfile::where('active',true)->whereNotNull('email')->get() as $profile) {
                $items = DB::table('profile_opportunity as p')->join('opportunities as o','o.id','=','p.opportunity_id')
                    ->where('p.search_profile_id',$profile->id)->whereNull('p.notified_at')
                    ->whereIn('o.id',$compatibleIds)
                    ->where('o.scope_status','relevant')->where('o.deadline','>',now())->get(['o.*','p.id as notification_id'])
                    ->filter(fn ($item) => app(ScopeClassifier::class)->matches((array)$item,$profile));
                if ($items->isEmpty()) continue;
                $text = "LicitaRadar — {$profile->name}\n\nNovas oportunidades de projeto. Habilitação ainda depende de revisão documental.\n\n";
                foreach ($items as $item) $text .= "{$item->agency}\n{$item->object}\nPrazo: {$item->deadline}\n\n";
                $text .= 'Acesse o painel: '.config('app.url');
                Mail::raw($text, fn ($m) => $m->to($profile->email)->subject('LicitaRadar: novas oportunidades de projeto'));
                DB::table('profile_opportunity')->whereIn('id',$items->pluck('notification_id'))->update(['notified_at'=>now()]); $sent++;
            }
            return $sent;
        });
    }
}
