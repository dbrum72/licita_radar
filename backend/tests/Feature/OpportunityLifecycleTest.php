<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\Requirement;
use App\Models\SearchProfile;
use App\Models\User;
use App\Services\OpportunityLifecycleService;
use App\Services\SyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OpportunityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function opportunity(int $id = 1, $deadline = null): Opportunity
    {
        return Opportunity::create(['pncp_id' => '123-2026-'.$id, 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => $id,
            'object' => 'Elaboração de projeto arquitetônico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_status' => 'relevant', 'scope_reason' => 'Projeto', 'deadline' => $deadline ?? now()->addDays(10), 'source' => []]);
    }

    private function companyDocument(): CompanyDocument
    {
        return CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company.pdf', 'sha256' => str_repeat('a', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projetos arquitetônicos para edificações.']]]);
    }

    private function row(int $id, $deadline = null, string $object = 'Elaboração de projeto arquitetônico'): array
    {
        return ['numeroControlePNCP' => '123-2026-'.$id, 'orgaoEntidade' => ['cnpj' => '12345678000100', 'razaoSocial' => 'Prefeitura'],
            'anoCompra' => 2026, 'sequencialCompra' => $id, 'objetoCompra' => $object, 'modalidadeId' => 4,
            'dataEncerramentoProposta' => $deadline];
    }

    public function test_sync_accepts_today_in_brasilia_and_rejects_yesterday(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 18:00:00', 'America/Sao_Paulo'));
        config(['radar.modalities' => [4]]);
        $this->companyDocument();
        $profile = SearchProfile::create(['name' => 'Projetos', 'keywords' => [], 'min_days' => 0, 'active' => true]);
        Http::fake(['*' => Http::response(['data' => [
            $this->row(1, '2026-10-13T03:00:00Z'),
            $this->row(2, '2026-10-13T02:59:59Z'),
        ], 'totalPaginas' => 1])]);
        $this->assertSame(1, app(SyncService::class)->run([$profile->id])->imported);
        $this->assertDatabaseHas('opportunities', ['sequence' => 1]);
        $this->assertDatabaseMissing('opportunities', ['sequence' => 2]);
    }

    public function test_today_remains_open_until_midnight_in_brasilia(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 23:59:59', 'America/Sao_Paulo'));
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->companyDocument();
        $today = $this->opportunity(1, now()->startOfDay());
        $yesterday = $this->opportunity(2, now()->subDay()->endOfDay());
        $discarded = $this->opportunity(3, now()->startOfDay());
        $discarded->dismissal()->create();
        $this->assertSame(0, app(OpportunityLifecycleService::class)->pruneExpiredDiscarded());
        $this->getJson('/api/opportunities')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $today->id);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('open', 1);
        $this->postJson('/api/opportunities/export', ['ids' => [$today->id]])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->postJson('/api/opportunities/export', ['ids' => [$yesterday->id]])->assertUnprocessable();
        $this->patchJson('/api/opportunities/'.$today->id, ['bookmarked' => true])->assertOk();
        $this->patchJson('/api/opportunities/'.$discarded->id.'/discard', ['discarded' => false])->assertOk();
        $discarded->dismissal()->create();
        $this->travelTo(now()->addSecond());
        $this->actingAs($user);
        $this->getJson('/api/opportunities?bookmarked=1')->assertOk()->assertJsonPath('total', 0);
        $this->assertNull($discarded->fresh());
        $this->assertNotNull($today->fresh());
        $this->postJson('/api/opportunities/export', ['ids' => [$today->id]])->assertUnprocessable();
    }

    public function test_cleanup_deletes_only_expired_discarded_records_and_keeps_the_company_and_consultation_id(): void
    {
        $this->travelTo(now()->startOfSecond());
        $op = $this->opportunity(1, now()->subDay());
        $op->dismissal()->create();
        $future = $this->opportunity(2);
        $future->dismissal()->create();
        $activeExpired = $this->opportunity(3, now()->subDay());
        $company = $this->companyDocument();
        $doc = Document::create(['opportunity_id' => $op->id, 'name' => 'edital.pdf', 'kind' => 'tender', 'path' => 'documents/test.pdf', 'sha256' => str_repeat('b', 64)]);
        $req = Requirement::create(['opportunity_id' => $op->id, 'source_document_id' => $doc->id, 'source_page' => 1, 'description' => 'Atestado', 'evidence_document_id' => $company->id]);
        $profile = SearchProfile::create(['name' => 'Projetos', 'keywords' => [], 'active' => true]);
        DB::table('profile_opportunity')->insert(['search_profile_id' => $profile->id, 'opportunity_id' => $op->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(1, app(OpportunityLifecycleService::class)->pruneExpiredDiscarded());
        $this->assertNull($op->fresh());
        $this->assertNull($doc->fresh());
        $this->assertNull($req->fresh());
        $this->assertDatabaseCount('profile_opportunity', 0);
        $this->assertNotNull($company->fresh());
        $this->assertNotNull($future->fresh());
        $this->assertNotNull($activeExpired->fresh());
        $this->assertDatabaseHas('pncp_consultations', ['pncp_id' => $op->pncp_id]);
        $this->assertSame(0, app(OpportunityLifecycleService::class)->pruneExpiredDiscarded());
        Http::assertNothingSent();
    }

    public function test_restore_on_the_day_after_the_deadline_is_rejected_and_deletes_the_expired_discard(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create();
        $this->actingAs($user);
        $op = $this->opportunity();
        $this->patchJson('/api/opportunities/'.$op->id.'/discard', ['discarded' => true])->assertOk();
        $this->travelTo($op->deadline->copy()->addDay()->startOfDay());
        $this->actingAs($user);
        $this->patchJson('/api/opportunities/'.$op->id.'/discard', ['discarded' => false])->assertUnprocessable();
        $this->assertNull($op->fresh());
        $this->assertDatabaseHas('pncp_consultations', ['pncp_id' => $op->pncp_id]);
    }

    public function test_list_cleans_expired_discards_and_hides_expired_active_items_even_without_open_filter(): void
    {
        $this->actingAs(User::factory()->create());
        $this->companyDocument();
        $op = $this->opportunity(1, now()->subDay());
        $op->dismissal()->create();
        $active = $this->opportunity(2, now()->subDay());
        $future = $this->opportunity(3);
        $future->dismissal()->create();
        $this->getJson('/api/opportunities?discarded=1&open=0')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $future->id);
        $this->assertNull($op->fresh());
        $this->assertNotNull($active->fresh());
        $this->getJson('/api/opportunities?open=0')->assertJsonPath('total', 0);
    }

    public function test_sync_uses_open_proposals_and_imports_only_new_valid_open_items(): void
    {
        $this->travelTo(now()->startOfSecond());
        Queue::fake();
        config(['radar.modalities' => [4]]);
        $this->companyDocument();
        $existing = $this->opportunity(6);
        $existing->update(['bookmarked' => true]);
        DB::table('pncp_consultations')->insert(['pncp_id' => '123-2026-7', 'first_seen_at' => now()]);
        $open = now()->addDays(20)->toIso8601String();
        $rows = [$this->row(1, $open), $this->row(1, $open), $this->row(2, now()->subDay()->toIso8601String()), $this->row(3),
            $this->row(4, $open, 'Aquisição de computadores'), $this->row(5, 'data inválida'),
            $this->row(6, $open, 'Elaboração de projeto elétrico'), $this->row(7, $open)];
        Http::fake(['*' => Http::response(['data' => $rows, 'totalPaginas' => 1])]);
        $run = app(SyncService::class)->run();
        $this->assertSame('completed', $run->status);
        $this->assertSame(1, $run->imported);
        $this->assertDatabaseCount('opportunities', 2);
        $this->assertDatabaseCount('pncp_consultations', 7);
        $this->assertSame('Elaboração de projeto arquitetônico', $existing->fresh()->object);
        $this->assertTrue($existing->fresh()->bookmarked);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/contratacoes/proposta') && $request['pagina'] == 1 && $request['tamanhoPagina'] == 50);
        $this->assertSame(0, app(SyncService::class)->run()->imported);
        // A rejected object's later amendment cannot cause its reimport.
        Http::fake(['*' => Http::response(['data' => [$this->row(4, $open)], 'totalPaginas' => 1])]);
        $this->assertSame(0, app(SyncService::class)->run()->imported);
    }

    public function test_purged_opportunity_cannot_be_reimported_even_if_pncp_extends_its_deadline(): void
    {
        Queue::fake();
        config(['radar.modalities' => [4]]);
        $this->companyDocument();
        $op = $this->opportunity(1, now()->subDay());
        $op->dismissal()->create();
        Http::fake(['*' => Http::response(['data' => [$this->row(1, now()->addDays(20)->toIso8601String())], 'totalPaginas' => 1])]);
        $this->assertSame(0, app(SyncService::class)->run()->imported);
        $this->assertDatabaseCount('opportunities', 0);
        $this->assertDatabaseHas('pncp_consultations', ['pncp_id' => $op->pncp_id]);
    }

    public function test_deadline_with_explicit_timezone_is_stored_as_the_same_instant(): void
    {
        $this->travelTo(now()->startOfSecond());
        Queue::fake();
        config(['radar.modalities' => [4]]);
        $this->companyDocument();
        $deadline = now()->addMinutes(2);
        Http::fake(['*' => Http::response(['data' => [$this->row(1, $deadline->copy()->utc()->toIso8601String())], 'totalPaginas' => 1])]);
        $this->assertSame(1, app(SyncService::class)->run()->imported);
        $this->assertSame($deadline->timestamp, Opportunity::first()->deadline->timestamp);
    }

    public function test_export_rejects_a_selection_that_expired_after_it_was_selected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->companyDocument();
        $op = $this->opportunity(1, now()->subDay());
        $this->postJson('/api/opportunities/export', ['ids' => [$op->id]])->assertUnprocessable();
    }

    public function test_manual_triage_accumulates_pending_and_never_reimports_previous_decisions(): void
    {
        $this->actingAs(User::factory()->create());
        config(['radar.modalities' => [4]]);
        Queue::fake();
        $this->companyDocument();
        $deadline = now()->addDays(20)->toIso8601String();
        $rows = [$this->row(1, $deadline), $this->row(2, $deadline), $this->row(3, $deadline)];
        Http::fake(function () use (&$rows) {
            return Http::response(['data' => $rows, 'totalPaginas' => 1]);
        });
        $this->assertSame(3, app(SyncService::class)->run()->imported);
        $saved = Opportunity::where('sequence', 1)->firstOrFail();
        $discarded = Opportunity::where('sequence', 2)->firstOrFail();
        $pending = Opportunity::where('sequence', 3)->firstOrFail();
        $this->patchJson('/api/opportunities/'.$saved->id, ['bookmarked' => true])->assertOk();
        $this->patchJson('/api/opportunities/'.$discarded->id.'/discard', ['discarded' => true])->assertOk();
        $rows[] = $this->row(4, $deadline);
        $this->assertSame(1, app(SyncService::class)->run()->imported);
        $this->getJson('/api/opportunities')->assertJsonPath('total', 2)->assertJsonFragment(['id' => $pending->id]);
        $this->getJson('/api/opportunities?bookmarked=1')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $saved->id);
        $this->getJson('/api/opportunities?discarded=1')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $discarded->id);
        $this->assertSame(0, app(SyncService::class)->run()->imported);
        $this->assertDatabaseCount('opportunities', 4);
        $this->assertDatabaseCount('pncp_consultations', 4);
        Queue::assertNothingPushed();
    }

    public function test_saved_and_discarded_are_mutually_exclusive_and_restore_returns_to_pending(): void
    {
        $this->actingAs(User::factory()->create());
        $this->companyDocument();
        $op = $this->opportunity();
        $this->patchJson('/api/opportunities/'.$op->id, ['bookmarked' => true])->assertOk();
        $this->patchJson('/api/opportunities/'.$op->id.'/discard', ['discarded' => true])->assertOk();
        $this->assertFalse($op->fresh()->bookmarked);
        $this->patchJson('/api/opportunities/'.$op->id, ['bookmarked' => true])->assertOk();
        $this->assertFalse($op->dismissal()->exists());
        $this->patchJson('/api/opportunities/'.$op->id, ['bookmarked' => false])->assertOk();
        $this->getJson('/api/opportunities')->assertJsonPath('total', 1);
        $this->patchJson('/api/opportunities/'.$op->id.'/discard', ['discarded' => true])->assertOk();
        $this->patchJson('/api/opportunities/'.$op->id.'/discard', ['discarded' => false])->assertOk();
        $this->getJson('/api/opportunities')->assertJsonPath('total', 1);
        $op->update(['deadline' => now()->subDay()]);
        $this->patchJson('/api/opportunities/'.$op->id, ['bookmarked' => true])->assertUnprocessable();
    }

    public function test_all_selected_profiles_must_match_and_rejected_ids_stay_consulted(): void
    {
        config(['radar.modalities' => [4]]);
        $this->companyDocument();
        $first = SearchProfile::create(['name' => 'Escolas', 'keywords' => ['escola'], 'excluded_keywords' => ['hospital'], 'min_days' => 0, 'active' => true]);
        $second = SearchProfile::create(['name' => 'Arquitetura', 'keywords' => ['arquitetônico'], 'min_days' => 0, 'active' => true]);
        SearchProfile::create(['name' => 'Pausado', 'keywords' => [], 'min_days' => 0, 'active' => false]);
        $deadline = now()->addDays(20)->toIso8601String();
        Http::fake(['*' => Http::response(['data' => [
            $this->row(1, $deadline, 'Elaboração de projeto arquitetônico de escola'),
            $this->row(2, $deadline, 'Elaboração de projeto arquitetônico de hospital'),
            $this->row(3, $deadline, 'Elaboração de projeto arquitetônico de praça'),
            $this->row(4, $deadline, 'Elaboração de projeto elétrico de escola'),
            $this->row(5, $deadline, 'Elaboração de projeto arquitetônico de escola e hospital'),
        ], 'totalPaginas' => 1])]);
        $this->assertSame(1, app(SyncService::class)->run([$first->id, $second->id])->imported);
        $this->assertDatabaseCount('profile_opportunity', 2);
        $this->assertDatabaseHas('profile_opportunity', ['search_profile_id' => $first->id, 'opportunity_id' => Opportunity::where('sequence', 1)->firstOrFail()->id]);
        $this->assertDatabaseHas('profile_opportunity', ['search_profile_id' => $second->id, 'opportunity_id' => Opportunity::where('sequence', 1)->firstOrFail()->id]);
        $this->assertDatabaseCount('pncp_consultations', 5);
        $first->update(['keywords' => []]);
        $this->assertSame(0, app(SyncService::class)->run([$first->id, $second->id])->imported);
    }

    public function test_consultation_applies_only_selected_profiles_and_links_only_them(): void
    {
        config(['radar.modalities' => [4]]);
        $this->actingAs(User::factory()->create());
        $this->companyDocument();
        $selected = SearchProfile::create(['name' => 'Escolas', 'keywords' => ['escola'], 'min_days' => 0, 'active' => true]);
        SearchProfile::create(['name' => 'Hospitais', 'keywords' => ['hospital'], 'min_days' => 0, 'active' => true]);
        SearchProfile::create(['name' => 'Genérico não selecionado', 'keywords' => [], 'min_days' => 0, 'active' => true]);
        $deadline = now()->addDays(20)->toIso8601String();
        Http::fake(['*' => Http::response(['data' => [
            $this->row(1, $deadline, 'Elaboração de projeto arquitetônico de escola'),
            $this->row(2, $deadline, 'Elaboração de projeto arquitetônico de hospital'),
        ], 'totalPaginas' => 1])]);
        $this->postJson('/api/sync', ['profile_ids' => [$selected->id]])->assertOk()->assertJsonPath('run.imported', 1);
        $this->assertDatabaseCount('profile_opportunity', 1);
        $this->assertDatabaseHas('profile_opportunity', ['search_profile_id' => $selected->id]);
        $this->assertDatabaseCount('pncp_consultations', 2);
    }

    public function test_empty_selection_uses_only_company_documents_without_profile_links(): void
    {
        config(['radar.modalities' => [4]]);
        $this->actingAs(User::factory()->create());
        $this->companyDocument();
        SearchProfile::create(['name' => 'Restritivo', 'keywords' => ['hospital'], 'min_days' => 0, 'active' => true]);
        Http::fake(['*' => Http::response(['data' => [$this->row(1, now()->addDays(20)->toIso8601String())], 'totalPaginas' => 1])]);
        $this->postJson('/api/sync', ['profile_ids' => []])->assertOk()->assertJsonPath('run.imported', 1);
        $this->assertDatabaseCount('profile_opportunity', 0);
    }

    public function test_consultation_requires_explicit_selection_and_rejects_invalid_or_paused_profiles(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->actingAs(User::factory()->create());
        Http::fake();
        $profile = SearchProfile::create(['name' => 'Pausado', 'keywords' => [], 'active' => false]);
        foreach ([[], ['profile_ids' => 'todos'], ['profile_ids' => [999999]], ['profile_ids' => [$profile->id]]] as $payload) {
            $this->postJson('/api/sync', $payload)->assertUnprocessable();
        }
        $profile->update(['active' => true]);
        $this->postJson('/api/sync', ['profile_ids' => [$profile->id, $profile->id]])->assertUnprocessable();
        $this->assertDatabaseCount('sync_runs', 0);
        Http::assertNothingSent();
    }

    public function test_service_rejects_profiles_paused_after_request_validation_without_consulting(): void
    {
        Http::fake();
        $profile = SearchProfile::create(['name' => 'Pausado', 'keywords' => [], 'active' => false]);
        try {
            app(SyncService::class)->run([$profile->id]);
            $this->fail('A consulta não deve ignorar um perfil selecionado que foi pausado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('profile_ids', $exception->errors());
        }
        $this->assertDatabaseCount('sync_runs', 0);
        Http::assertNothingSent();
    }

    public function test_known_ids_are_ignored_before_mapping_changed_or_incomplete_payloads(): void
    {
        config(['radar.modalities' => [4]]);
        $this->companyDocument();
        DB::table('pncp_consultations')->insert(['pncp_id' => '123-2026-1', 'first_seen_at' => now()]);
        Http::fake(['*' => Http::response(['data' => [
            ['numeroControlePNCP' => '123-2026-1'],
            $this->row(2, now()->addDays(20)->toIso8601String()),
        ], 'totalPaginas' => 1])]);
        $run = app(SyncService::class)->run([]);
        $this->assertSame('completed', $run->status);
        $this->assertSame(1, $run->imported);
        $this->assertDatabaseCount('opportunities', 1);
        $this->assertDatabaseCount('pncp_consultations', 2);
    }
}
