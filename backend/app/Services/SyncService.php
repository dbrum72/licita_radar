<?php
namespace App\Services;
use App\Models\{SearchProfile, Opportunity, SyncRun};
use Illuminate\Support\Facades\{Cache, DB};

class SyncService
{
    public function run(): SyncRun
    {
        return Cache::lock('pncp-sync', 21600)->block(1, function () {
            app(OpportunityLifecycleService::class)->pruneExpiredDiscarded();
            $run = SyncRun::create(['status'=>'running', 'started_at'=>now()]);
            try {
                $profiles = SearchProfile::where('active', true)->get();
                $modalities = collect(config('radar.modalities'));
                $company = app(CompanyScopeService::class);
                if (!$company->hasUsableDocuments()) {
                    $run->update(['status'=>'failed','finished_at'=>now(),'error'=>'Consulta não iniciada: cadastre documentos válidos da empresa com texto extraído. O acervo é a base obrigatória da busca.']);
                    return $run->fresh();
                }
                $client = app(PncpClient::class); $scope = app(ScopeClassifier::class);
                foreach ($modalities as $modality) {
                    foreach ($client->openProposals($modality) as $row) {
                        $data = $client->map($row);
                        DB::transaction(function () use ($data, $profiles, $company, $scope, $run) {
                            if (DB::table('pncp_consultations')->where('pncp_id',$data['pncp_id'])->exists()) return;
                            if (!DB::table('pncp_consultations')->insertOrIgnore(['pncp_id'=>$data['pncp_id'],'first_seen_at'=>now()])) return;
                            if (Opportunity::where('pncp_id',$data['pncp_id'])->exists()) return;
                            try { $deadline = $data['deadline'] ? \Carbon\Carbon::parse($data['deadline']) : null; }
                            catch (\Throwable) { return; }
                            if (!$deadline || !$deadline->gt(now())) return;
                            $data['deadline'] = $deadline->copy()->timezone(config('app.timezone'))->toDateTimeString();
                            if ($company->assess($data['object'],$deadline)['status'] !== 'compatible') return;
                            [$data['scope_status'], $data['scope_reason']] = $scope->classify($data['object']);
                            $matched = $profiles->filter(fn ($p) => $scope->matches($data, $p));
                            $opportunity = Opportunity::create($data);
                            $run->increment('imported');
                            if ($data['scope_status']==='relevant') {
                                \App\Jobs\FetchTenderDocuments::dispatch($opportunity->id)->afterCommit();
                            }
                            foreach ($matched as $profile) {
                                $count = DB::table('profile_opportunity')->insertOrIgnore([
                                    'search_profile_id'=>$profile->id,'opportunity_id'=>$opportunity->id,'created_at'=>now(),'updated_at'=>now(),
                                ]);
                                $run->increment('matched', $count);
                            }
                        });
                    }
                }
                $run->update(['status'=>'completed','finished_at'=>now()]);
            } catch (\Throwable $e) {
                report($e);
                $reason = $e instanceof \Illuminate\Http\Client\RequestException && $e->response->status()===429
                    ? 'PNCP limitou as consultas (HTTP 429). Os resultados parciais foram preservados; tente novamente mais tarde.'
                    : 'Falha na consulta PNCP. Resultados parciais preservados. Consulte o log técnico.';
                $run->update(['status'=>'failed','error'=>$reason,'finished_at'=>now()]);
            }
            return $run->fresh();
        });
    }
}
