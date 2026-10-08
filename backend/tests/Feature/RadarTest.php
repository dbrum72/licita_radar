<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\Requirement;
use App\Models\SearchProfile;
use App\Models\User;
use App\Services\CompanyScopeService;
use App\Services\DigestService;
use App\Services\ScopeClassifier;
use App\Services\SyncService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RadarTest extends TestCase
{
    use RefreshDatabase;

    private function opportunity(): Opportunity
    {
        return Opportunity::create(['pncp_id' => '123-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Elaboração de projetos de arquitetura', 'agency' => 'Prefeitura', 'state' => 'SP', 'city' => 'São Paulo',
            'modality_id' => 4, 'modality' => 'Concorrência', 'scope_status' => 'relevant', 'scope_reason' => 'Projeto',
            'deadline' => now()->addDays(20), 'source' => []]);
    }

    public function test_private_data_requires_authentication(): void
    {
        $this->getJson('/api/documents')->assertUnauthorized();
        $this->postJson('/api/sync', ['profile_ids' => []])->assertUnauthorized();
    }

    public function test_initial_access_does_not_allow_creating_another_user(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->postJson('/api/setup', [
            'name' => 'Administrador', 'email' => 'admin@example.test', 'password' => 'ExampleTesting123!',
        ])->assertCreated()->assertJsonMissingPath('user.password');
        $this->postJson('/api/setup', ['name' => 'Outro', 'email' => 'other@example.test', 'password' => 'ExampleTesting123!'])->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_scope_and_mixed_contracts_are_conservative(): void
    {
        $scope = new ScopeClassifier;
        $this->assertSame('relevant', $scope->classify('Elaboração de projetos de engenharia e arquitetura')[0]);
        $this->assertSame('excluded', $scope->classify('Elaboração de projeto e execução de obra de uma escola')[0]);
        $this->assertSame('review', $scope->classify('Aquisição de computadores')[0]);
    }

    public function test_pdf_evidence_cannot_be_expired_or_from_another_tender(): void
    {
        $this->actingAs(User::factory()->create());
        $op = $this->opportunity();
        $source = Document::create(['opportunity_id' => $op->id, 'name' => 'edital.pdf', 'kind' => 'tender', 'path' => 'test.pdf', 'sha256' => str_repeat('0', 64), 'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Atestado técnico']]]);
        $req = Requirement::create(['opportunity_id' => $op->id, 'source_document_id' => $source->id, 'source_page' => 1, 'description' => 'Atestado técnico de arquitetura']);
        $evidence = CompanyDocument::create(['name' => 'atestado.pdf', 'kind' => 'attestation', 'path' => 'e.pdf', 'sha256' => str_repeat('1', 64), 'status' => 'ready', 'expires_at' => today()->subDay(), 'pages' => [['number' => 1, 'text' => 'Projeto arquitetônico']]]);
        $payload = ['status' => 'met', 'evidence_document_id' => $evidence->id, 'evidence_page' => 1, 'notes' => 'Conferido'];
        $this->patchJson('/api/requirements/'.$req->id, $payload)->assertUnprocessable();
        $evidence->update(['expires_at' => today()->addDays(10)]);
        $this->patchJson('/api/requirements/'.$req->id, $payload)->assertUnprocessable();
        $evidence->update(['expires_at' => today()->addDays(30)]);
        $this->patchJson('/api/requirements/'.$req->id, $payload)->assertOk();
        $this->assertDatabaseHas('requirements', ['id' => $req->id, 'status' => 'met', 'reviewer_id' => auth()->id()]);
    }

    public function test_empty_analysis_never_confirms_qualification(): void
    {
        $this->actingAs(User::factory()->create());
        $op = $this->opportunity();
        $this->getJson('/api/opportunities/'.$op->id)->assertOk()->assertJsonPath('opportunity.company_match.status', 'pending');
    }

    public function test_sync_paginates_deduplicates_and_preserves_bookmarks(): void
    {
        Queue::fake();
        config(['cache.default' => 'array', 'radar.modalities' => [4]]);
        $this->companyDocument();
        $profile = SearchProfile::create(['name' => 'Projetos', 'keywords' => ['projeto'], 'modalities' => [4], 'states' => ['SP'], 'min_days' => 3, 'active' => true]);
        $row = ['numeroControlePNCP' => '123-2026-1', 'orgaoEntidade' => ['cnpj' => '12345678000100', 'razaoSocial' => 'Prefeitura'],
            'anoCompra' => 2026, 'sequencialCompra' => 1, 'objetoCompra' => 'Elaboração de projeto arquitetônico',
            'unidadeOrgao' => ['ufSigla' => 'SP', 'municipioNome' => 'São Paulo'], 'modalidadeId' => 4, 'modalidadeNome' => 'Concorrência',
            'valorTotalEstimado' => 100000, 'dataEncerramentoProposta' => now()->addDays(20)->toIso8601String()];
        Http::fake(fn ($request) => Http::response(['data' => [$row], 'totalPaginas' => 2]));
        $first = app(SyncService::class)->run([$profile->id]);
        $this->assertSame('completed', $first->status);
        $this->assertSame(1, $first->imported);
        Queue::assertNothingPushed();
        Opportunity::first()->update(['bookmarked' => true]);
        $second = app(SyncService::class)->run([$profile->id]);
        $this->assertSame(0, $second->imported);
        $this->assertSame(1, Opportunity::count());
        $this->assertSame(1, DB::table('profile_opportunity')->count());
        $this->assertTrue(Opportunity::first()->bookmarked);
        Http::assertSentCount(4);
    }

    public function test_browser_consultation_finishes_in_request_without_jobs_or_email(): void
    {
        Queue::fake();
        Mail::fake();
        config(['radar.modalities' => [4]]);
        $this->companyDocument();
        Http::fake(['*' => Http::response(['data' => [], 'totalPaginas' => 1])]);
        $this->actingAs(User::factory()->create())->postJson('/api/sync', ['profile_ids' => []])
            ->assertOk()->assertJsonPath('run.status', 'completed');
        Http::assertSentCount(1);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_browser_consultation_returns_failure_without_scheduled_tasks(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create())->postJson('/api/sync', ['profile_ids' => []])
            ->assertStatus(502)->assertJsonPath('run.status', 'failed');
        Http::assertNothingSent();
        Mail::fake();
        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('sync_runs', 1);
        $this->assertCount(0, app(Schedule::class)->events());
        $this->assertArrayNotHasKey('radar:sync', Artisan::all());
    }

    public function test_upstream_failure_is_recorded(): void
    {
        $this->companyDocument();
        SearchProfile::create(['name' => 'Projetos', 'keywords' => ['projeto'], 'modalities' => [4], 'min_days' => 0, 'active' => true]);
        Http::fake(['*' => Http::response(['unexpected' => true])]);
        $this->assertSame('failed', app(SyncService::class)->run()->status);
        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_profile_validation_and_editing(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = ['name' => 'Arquitetura', 'keywords' => ['projeto'], 'modalities' => [4], 'states' => ['SP'], 'min_days' => 3, 'active' => true];
        $id = $this->postJson('/api/profiles', $payload)->assertCreated()->assertJsonPath('keywords', ['projeto'])->json('id');
        $this->putJson('/api/profiles/'.$id, [...$payload, 'active' => false])->assertOk()->assertJsonPath('active', false);
        $this->postJson('/api/profiles', [...$payload, 'min_value' => 200, 'max_value' => 100])->assertUnprocessable();
    }

    public function test_profile_keywords_allow_generic_search_and_match_any_normalized_term(): void
    {
        $scope = app(ScopeClassifier::class);
        $data = ['object' => 'Elaboração de projeto arquitetônico', 'deadline' => now()->addDays(10)];
        $profile = new SearchProfile(['keywords' => ['elétrica', ' ARQUITETÔNICO '], 'min_days' => 0]);
        $this->assertTrue($scope->matches($data, $profile));
        $profile->keywords = ['eletrica'];
        $this->assertFalse($scope->matches($data, $profile));
        $profile->keywords = [];
        $this->assertTrue($scope->matches($data, $profile));
        $this->actingAs(User::factory()->create());
        $payload = ['name' => 'Genérico', 'min_days' => 0, 'active' => true];
        $id = $this->postJson('/api/profiles', $payload)->assertCreated()->assertJsonPath('keywords', [])->json('id');
        $this->putJson('/api/profiles/'.$id, [...$payload, 'keywords' => [' arquitetura ', 'arquitetura']])->assertOk()->assertJsonPath('keywords', ['arquitetura']);
        $this->putJson('/api/profiles/'.$id, $payload)->assertOk()->assertJsonPath('keywords', ['arquitetura']);
        $this->putJson('/api/profiles/'.$id, [...$payload, 'keywords' => []])->assertOk()->assertJsonPath('keywords', []);
    }

    public function test_excluded_keywords_override_inclusions_and_can_be_edited_and_cleared(): void
    {
        $scope = app(ScopeClassifier::class);
        $data = ['object' => 'Elaboração de projeto ARQUITETÔNICO de escola', 'deadline' => now()->addDays(10)];
        $profile = new SearchProfile(['keywords' => ['projeto'], 'excluded_keywords' => ['hospital', ' arquitetonico '], 'min_days' => 0]);
        $this->assertFalse($scope->matches($data, $profile));
        $profile->keywords = [];
        $this->assertFalse($scope->matches($data, $profile));
        $profile->excluded_keywords = ['elétrica'];
        $this->assertTrue($scope->matches($data, $profile));
        $profile->excluded_keywords = [];
        $this->assertTrue($scope->matches($data, $profile));
        $this->actingAs(User::factory()->create());
        $payload = ['name' => 'Genérico', 'min_days' => 0, 'active' => true];
        $id = $this->postJson('/api/profiles', [...$payload, 'excluded_keywords' => [' escola ', 'escola']])->assertCreated()->assertJsonPath('excluded_keywords', ['escola'])->json('id');
        $this->putJson('/api/profiles/'.$id, $payload)->assertOk()->assertJsonPath('excluded_keywords', ['escola']);
        $this->getJson('/api/profiles')->assertOk()->assertJsonPath('0.excluded_keywords', ['escola']);
        $this->putJson('/api/profiles/'.$id, [...$payload, 'excluded_keywords' => []])->assertOk()->assertJsonPath('excluded_keywords', []);
        $this->postJson('/api/profiles', [...$payload, 'excluded_keywords' => 'escola'])->assertUnprocessable();
        $this->postJson('/api/profiles', [...$payload, 'excluded_keywords' => [str_repeat('a', 121)]])->assertUnprocessable();
    }

    public function test_attachment_limits_are_isolated_by_opportunity_and_from_other_actions(): void
    {
        Http::fake(['*' => Http::response([], 204)]);
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $this->companyDocument();
        $first = $this->opportunity();
        $second = $first->replicate();
        $second->pncp_id = '123-2026-2';
        $second->sequence = 2;
        $second->save();
        $third = $first->replicate();
        $third->pncp_id = '123-2026-3';
        $third->sequence = 3;
        $third->save();
        $this->postJson('/api/sync', ['profile_ids' => []])->assertOk();
        $this->postJson('/api/opportunities/'.$first->id.'/attachments')->assertOk();
        $this->postJson('/api/opportunities/'.$second->id.'/attachments')->assertOk();
        $this->postJson('/api/opportunities/'.$third->id.'/attachments')->assertOk();
        $this->postJson('/api/opportunities/'.$first->id.'/attachments')->assertOk();
        $this->postJson('/api/opportunities/'.$first->id.'/attachments')->assertStatus(429)
            ->assertHeader('Retry-After')->assertJsonStructure(['message']);
        $this->travel(5)->minutes();
        $this->postJson('/api/opportunities/'.$first->id.'/attachments')->assertOk();
    }

    private function companyDocument(array $attributes = []): CompanyDocument
    {
        return CompanyDocument::create([...['name' => 'acervo.pdf', 'kind' => 'attestation', 'path' => 'test.pdf',
            'sha256' => str_repeat('2', 64), 'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Atestado de elaboração de projetos arquitetônicos para edificações.']]], ...$attributes]);
    }

    public function test_opportunities_require_current_documentary_evidence(): void
    {
        $this->actingAs(User::factory()->create());
        $op = $this->opportunity();
        $this->getJson('/api/opportunities')->assertJsonPath('total', 0);
        $doc = $this->companyDocument();
        $this->getJson('/api/opportunities')->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.company_match.evidence.0.document_id', $doc->id);
        $doc->update(['expires_at' => today()->addDays(10)]);
        $this->getJson('/api/opportunities')->assertJsonPath('total', 0);
        $doc->update(['expires_at' => null, 'status' => 'needs_ocr']);
        $this->getJson('/api/opportunities')->assertJsonPath('total', 0);
        $doc->update(['status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Certidão negativa de débitos da empresa regularmente habilitada.']]]);
        $this->getJson('/api/opportunities')->assertJsonPath('total', 0);
    }

    public function test_specialties_cannot_be_inferred_from_generic_words_or_profiles(): void
    {
        $this->companyDocument();
        $service = app(CompanyScopeService::class);
        $this->assertSame('incompatible', $service->assess('Elaboração de projetos elétricos')['status']);
        $this->assertSame('incompatible', $service->assess('Projeto arquitetônico e projeto elétrico')['status']);
        $this->assertSame('compatible', $service->assess('Elaboração de projeto arquitetônico')['status']);
        $this->assertSame('incompatible', $service->assess('Projeto arquitetônico e execução de obra')['status']);
        $profile = new SearchProfile(['keywords' => ['computadores'], 'min_days' => 0]);
        $this->assertFalse(app(ScopeClassifier::class)->matches(['object' => 'Projeto arquitetônico', 'deadline' => now()->addDays(10)], $profile));
    }

    public function test_sync_without_profiles_still_uses_company_documents(): void
    {
        Queue::fake();
        config(['radar.modalities' => [4]]);
        $this->companyDocument(['kind' => 'cnpj', 'pages' => [['number' => 1, 'text' => 'Atividade econômica principal: 7111-1/00 - Serviços de arquitetura.']]]);
        Http::fake(['*' => Http::response(['data' => [['numeroControlePNCP' => '123-2026-2', 'orgaoEntidade' => ['cnpj' => '12345678000100', 'razaoSocial' => 'Prefeitura'],
            'anoCompra' => 2026, 'sequencialCompra' => 2, 'objetoCompra' => 'Elaboração de projeto arquitetônico', 'unidadeOrgao' => ['ufSigla' => 'SP'],
            'modalidadeId' => 4, 'dataEncerramentoProposta' => now()->addDays(20)->toIso8601String()]], 'totalPaginas' => 1])]);
        $this->assertSame(1, app(SyncService::class)->run()->imported);
    }

    public function test_sync_without_readable_documents_does_not_query_pncp(): void
    {
        Http::fake();
        SearchProfile::create(['name' => 'Projetos', 'keywords' => ['projeto'], 'min_days' => 0, 'active' => true]);
        $this->assertSame('failed', app(SyncService::class)->run()->status);
        Http::assertNothingSent();
    }

    public function test_manual_attachments_require_company_selection(): void
    {
        Http::fake();
        $op = $this->opportunity();
        Http::assertNothingSent();
        $this->assertSame('pending', $op->fresh()->attachments_status);
        $this->actingAs(User::factory()->create())->postJson('/api/opportunities/'.$op->id.'/attachments')->assertUnprocessable();
    }

    public function test_digest_rechecks_documents_and_profile_refinements(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp']);
        $op = $this->opportunity();
        $profile = SearchProfile::create(['name' => 'Projetos', 'keywords' => ['computadores'], 'min_days' => 0, 'active' => true, 'email' => 'test@example.test']);
        DB::table('profile_opportunity')->insert(['opportunity_id' => $op->id, 'search_profile_id' => $profile->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(0, app(DigestService::class)->send());
        $doc = $this->companyDocument();
        $profile->update(['states' => ['RJ']]);
        $this->assertSame(0, app(DigestService::class)->send());
        $profile->update(['states' => ['SP']]);
        $this->assertSame(0, app(DigestService::class)->send());
        $profile->update(['keywords' => []]);
        $profile->update(['excluded_keywords' => [' ARQUITETÚRA ']]);
        $this->assertSame(0, app(DigestService::class)->send());
        $this->assertNull(DB::table('profile_opportunity')->first()->notified_at);
        $profile->update(['excluded_keywords' => []]);
        $this->assertSame(1, app(DigestService::class)->send());
        $this->assertNotNull(DB::table('profile_opportunity')->first()->notified_at);
    }

    public function test_previously_consulted_opportunity_is_not_overwritten_by_sync(): void
    {
        Queue::fake();
        config(['radar.modalities' => [4]]);
        $this->actingAs(User::factory()->create());
        $op = $this->opportunity();
        $op->update(['bookmarked' => true]);
        $this->companyDocument();
        Http::fake(['*' => Http::response(['data' => [['numeroControlePNCP' => $op->pncp_id, 'orgaoEntidade' => ['cnpj' => $op->cnpj, 'razaoSocial' => 'Prefeitura'],
            'anoCompra' => 2026, 'sequencialCompra' => 1, 'objetoCompra' => 'Elaboração de projetos elétricos', 'unidadeOrgao' => ['ufSigla' => 'SP'],
            'modalidadeId' => 4, 'dataEncerramentoProposta' => now()->addDays(20)->toIso8601String()]], 'totalPaginas' => 1])]);
        $this->assertSame('completed', app(SyncService::class)->run()->status);
        $this->getJson('/api/opportunities?bookmarked=1')->assertJsonPath('total', 1);
        $this->assertTrue($op->fresh()->bookmarked);
        $this->assertSame('Elaboração de projetos de arquitetura', $op->fresh()->object);
    }
}
