<?php

namespace App\Services\Consultations;

use App\Integrations\Pncp\Contracts\PncpClient;
use App\Integrations\Pncp\PncpOpportunityMapper;
use App\Models\Opportunity;
use App\Models\SearchProfile;
use App\Models\SyncRun;
use App\Services\CompanyDocuments\CompanyScopeService;
use App\Services\Opportunities\OpportunityLifecycleService;
use App\Services\Opportunities\ScopeClassifier;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncService
{
    public function __construct(private readonly PncpClient $client, private readonly PncpOpportunityMapper $mapper, private readonly ScopeClassifier $scopeClassifier, private readonly CompanyScopeService $companyScopeService, private readonly OpportunityLifecycleService $opportunityLifecycleService) {}

    public function run(array $profileIds = []): SyncRun
    {
        return Cache::lock('pncp-sync', 21600)->block(1, function () use ($profileIds) {
            $profiles = SearchProfile::where('active', true)->whereIn('id', $profileIds)->get();
            if ($profiles->count() !== count(array_unique($profileIds))) {
                throw ValidationException::withMessages(['profile_ids' => 'Um perfil selecionado foi pausado ou excluído. Atualize a seleção antes de consultar.']);
            }
            $this->opportunityLifecycleService->pruneExpiredDiscarded();
            $run = SyncRun::create(['status' => 'running', 'started_at' => now()]);
            try {
                $modalities = collect(config('radar.modalities'));
                $company = $this->companyScopeService;
                if (! $company->hasUsableDocuments()) {
                    $run->update(['status' => 'failed', 'finished_at' => now(), 'error' => 'Consulta não iniciada: cadastre documentos válidos da empresa com texto legível nos PDFs. O acervo é a base obrigatória da busca.']);

                    return $run->fresh();
                }
                $client = $this->client;
                $scope = $this->scopeClassifier;
                foreach ($modalities as $modality) {
                    foreach ($client->openProposals($modality) as $row) {
                        if (isset($row['numeroControlePNCP']) && is_string($row['numeroControlePNCP'])
                            && DB::table('pncp_consultations')->where('pncp_id', $row['numeroControlePNCP'])->exists()) {
                            continue;
                        }
                        $data = $this->mapper->map($row);
                        DB::transaction(function () use ($data, $profiles, $company, $scope, $run) {
                            if (DB::table('pncp_consultations')->where('pncp_id', $data['pncp_id'])->exists()) {
                                return;
                            }
                            if (! DB::table('pncp_consultations')->insertOrIgnore(['pncp_id' => $data['pncp_id'], 'first_seen_at' => now()])) {
                                return;
                            }
                            if (Opportunity::where('pncp_id', $data['pncp_id'])->exists()) {
                                return;
                            }
                            try {
                                $deadline = $data['deadline'] ? Carbon::parse($data['deadline']) : null;
                            } catch (\Throwable) {
                                return;
                            }
                            if (! $deadline || $deadline->copy()->timezone('America/Sao_Paulo')->lt(Opportunity::openDeadlineBoundary())) {
                                return;
                            }
                            $data['deadline'] = $deadline->copy()->timezone(config('app.timezone'))->toDateTimeString();
                            if ($company->assess($data['object'], $deadline)['status'] !== 'compatible') {
                                return;
                            }
                            [$data['scope_status'], $data['scope_reason']] = $scope->classify($data['object']);
                            $matched = $profiles->filter(fn ($p) => $scope->matches($data, $p));
                            if ($matched->count() !== $profiles->count()) {
                                return;
                            }
                            $opportunity = Opportunity::create($data);
                            $run->increment('imported');
                            foreach ($matched as $profile) {
                                $count = DB::table('profile_opportunity')->insertOrIgnore([
                                    'search_profile_id' => $profile->id, 'opportunity_id' => $opportunity->id, 'created_at' => now(), 'updated_at' => now(),
                                ]);
                                $run->increment('matched', $count);
                            }
                        });
                    }
                }
                $run->update(['status' => 'completed', 'finished_at' => now()]);
            } catch (\Throwable $e) {
                report($e);
                $reason = $e instanceof RequestException && $e->response->status() === 429
                    ? 'PNCP limitou as consultas (HTTP 429). Os resultados parciais foram preservados; tente novamente mais tarde.'
                    : 'Falha na consulta PNCP. Resultados parciais preservados. Consulte o log técnico.';
                $run->update(['status' => 'failed', 'error' => $reason, 'finished_at' => now()]);
            }

            return $run->fresh();
        });
    }
}
