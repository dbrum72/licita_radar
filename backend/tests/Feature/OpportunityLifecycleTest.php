<?php
namespace Tests\Feature;

use App\Models\{Opportunity, CompanyDocument, Document, Requirement, SearchProfile, User};
use App\Services\{OpportunityLifecycleService, SyncService, PncpClient, DocumentService};
use App\Jobs\FetchTenderDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http, Queue};
use Tests\TestCase;

class OpportunityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function opportunity(int $id = 1, $deadline = null): Opportunity
    {
        return Opportunity::create(['pncp_id'=>'123-2026-'.$id,'cnpj'=>'12345678000100','year'=>2026,'sequence'=>$id,
            'object'=>'Elaboração de projeto arquitetônico','agency'=>'Prefeitura','modality_id'=>4,'modality'=>'Concorrência',
            'scope_status'=>'relevant','scope_reason'=>'Projeto','deadline'=>$deadline ?? now()->addDays(10),'source'=>[]]);
    }
    private function companyDocument(): CompanyDocument
    {
        return CompanyDocument::create(['name'=>'acervo.pdf','kind'=>'rrt','path'=>'company.pdf','sha256'=>str_repeat('a',64),
            'status'=>'ready','pages'=>[['number'=>1,'text'=>'Registro de elaboração de projetos arquitetônicos para edificações.']]]);
    }
    private function row(int $id, $deadline = null, string $object = 'Elaboração de projeto arquitetônico'): array
    {
        return ['numeroControlePNCP'=>'123-2026-'.$id,'orgaoEntidade'=>['cnpj'=>'12345678000100','razaoSocial'=>'Prefeitura'],
            'anoCompra'=>2026,'sequencialCompra'=>$id,'objetoCompra'=>$object,'modalidadeId'=>4,
            'dataEncerramentoProposta'=>$deadline];
    }
    public function test_cleanup_deletes_only_expired_discarded_records_and_keeps_the_company_and_consultation_id(): void
    {
        $this->travelTo(now()->startOfSecond());
        $op = $this->opportunity(1,now()); $op->dismissal()->create();
        $future = $this->opportunity(2); $future->dismissal()->create();
        $activeExpired = $this->opportunity(3,now()->subDay());
        $company = $this->companyDocument();
        $doc = Document::create(['opportunity_id'=>$op->id,'name'=>'edital.pdf','kind'=>'tender','path'=>'documents/test.pdf','sha256'=>str_repeat('b',64)]);
        $req = Requirement::create(['opportunity_id'=>$op->id,'source_document_id'=>$doc->id,'source_page'=>1,'description'=>'Atestado','evidence_document_id'=>$company->id]);
        $profile = SearchProfile::create(['name'=>'Projetos','keywords'=>[],'active'=>true]);
        DB::table('profile_opportunity')->insert(['search_profile_id'=>$profile->id,'opportunity_id'=>$op->id,'created_at'=>now(),'updated_at'=>now()]);
        $this->assertSame(1,app(OpportunityLifecycleService::class)->pruneExpiredDiscarded());
        $this->assertNull($op->fresh());
        $this->assertNull($doc->fresh()); $this->assertNull($req->fresh());
        $this->assertDatabaseCount('profile_opportunity',0);
        $this->assertNotNull($company->fresh()); $this->assertNotNull($future->fresh()); $this->assertNotNull($activeExpired->fresh());
        $this->assertDatabaseHas('pncp_consultations',['pncp_id'=>$op->pncp_id]);
        $this->assertSame(0,app(OpportunityLifecycleService::class)->pruneExpiredDiscarded());
        Http::fake(); (new FetchTenderDocuments($op->id))->handle(app(PncpClient::class),app(DocumentService::class));
        Http::assertNothingSent();
    }
    public function test_restore_at_the_deadline_is_rejected_and_deletes_the_expired_discard(): void
    {
        $this->travelTo(now()->startOfSecond()); $this->actingAs(User::factory()->create());
        $op = $this->opportunity();
        $this->patchJson('/api/opportunities/'.$op->id.'/discard',['discarded'=>true])->assertOk();
        $this->travelTo($op->deadline);
        $this->patchJson('/api/opportunities/'.$op->id.'/discard',['discarded'=>false])->assertUnprocessable();
        $this->assertNull($op->fresh());
        $this->assertDatabaseHas('pncp_consultations',['pncp_id'=>$op->pncp_id]);
    }
    public function test_list_cleans_expired_discards_and_hides_expired_active_items_even_without_open_filter(): void
    {
        $this->actingAs(User::factory()->create()); $this->companyDocument();
        $op = $this->opportunity(1,now()->subMinute()); $op->dismissal()->create();
        $active = $this->opportunity(2,now()->subMinute());
        $future = $this->opportunity(3); $future->dismissal()->create();
        $this->getJson('/api/opportunities?discarded=1&open=0')->assertJsonPath('total',1)->assertJsonPath('data.0.id',$future->id);
        $this->assertNull($op->fresh()); $this->assertNotNull($active->fresh());
        $this->getJson('/api/opportunities?open=0')->assertJsonPath('total',0);
    }
    public function test_sync_uses_open_proposals_and_imports_only_new_valid_open_items(): void
    {
        $this->travelTo(now()->startOfSecond()); Queue::fake(); config(['radar.modalities'=>[4]]); $this->companyDocument();
        $existing = $this->opportunity(6); $existing->update(['bookmarked'=>true]);
        DB::table('pncp_consultations')->insert(['pncp_id'=>'123-2026-7','first_seen_at'=>now()]);
        $open = now()->addDays(20)->toIso8601String();
        $rows = [$this->row(1,$open),$this->row(1,$open),$this->row(2,now()->toIso8601String()),$this->row(3),
            $this->row(4,$open,'Aquisição de computadores'),$this->row(5,'data inválida'),
            $this->row(6,$open,'Elaboração de projeto elétrico'),$this->row(7,$open)];
        Http::fake(['*'=>Http::response(['data'=>$rows,'totalPaginas'=>1])]);
        $run = app(SyncService::class)->run();
        $this->assertSame('completed',$run->status); $this->assertSame(1,$run->imported);
        $this->assertDatabaseCount('opportunities',2); $this->assertDatabaseCount('pncp_consultations',7);
        $this->assertSame('Elaboração de projeto arquitetônico',$existing->fresh()->object); $this->assertTrue($existing->fresh()->bookmarked);
        Http::assertSent(fn ($request) => str_contains($request->url(),'/contratacoes/proposta') && $request['pagina']==1 && $request['tamanhoPagina']==50);
        $this->assertSame(0,app(SyncService::class)->run()->imported);
        // A rejected object's later amendment cannot cause its reimport.
        Http::fake(['*'=>Http::response(['data'=>[$this->row(4,$open)],'totalPaginas'=>1])]);
        $this->assertSame(0,app(SyncService::class)->run()->imported);
    }
    public function test_purged_opportunity_cannot_be_reimported_even_if_pncp_extends_its_deadline(): void
    {
        Queue::fake(); config(['radar.modalities'=>[4]]); $this->companyDocument();
        $op = $this->opportunity(1,now()->subMinute()); $op->dismissal()->create();
        Http::fake(['*'=>Http::response(['data'=>[$this->row(1,now()->addDays(20)->toIso8601String())],'totalPaginas'=>1])]);
        $this->assertSame(0,app(SyncService::class)->run()->imported);
        $this->assertDatabaseCount('opportunities',0);
        $this->assertDatabaseHas('pncp_consultations',['pncp_id'=>$op->pncp_id]);
    }
    public function test_deadline_with_explicit_timezone_is_stored_as_the_same_instant(): void
    {
        $this->travelTo(now()->startOfSecond()); Queue::fake(); config(['radar.modalities'=>[4]]); $this->companyDocument();
        $deadline = now()->addMinutes(2);
        Http::fake(['*'=>Http::response(['data'=>[$this->row(1,$deadline->copy()->utc()->toIso8601String())],'totalPaginas'=>1])]);
        $this->assertSame(1,app(SyncService::class)->run()->imported);
        $this->assertSame($deadline->timestamp,Opportunity::first()->deadline->timestamp);
    }
    public function test_export_rejects_a_selection_that_expired_after_it_was_selected(): void
    {
        $this->actingAs(User::factory()->create()); $this->companyDocument();
        $op = $this->opportunity(1,now()->subMinute());
        $this->postJson('/api/opportunities/export',['ids'=>[$op->id]])->assertUnprocessable();
    }
}
