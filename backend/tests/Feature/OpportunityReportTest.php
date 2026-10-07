<?php
namespace Tests\Feature;

use App\Models\{User, Opportunity, CompanyDocument, SearchProfile};
use App\Services\{SyncService, DigestService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http, Mail, Queue};
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class OpportunityReportTest extends TestCase
{
    use RefreshDatabase;

    private function opportunity(int $sequence = 1): Opportunity
    {
        return Opportunity::create(['pncp_id'=>'123-2026-'.$sequence,'cnpj'=>'12345678000100','year'=>2026,'sequence'=>$sequence,
            'object'=>'Elaboração de projeto arquitetônico '.$sequence,'agency'=>'Prefeitura de São Paulo','city'=>'São Paulo','state'=>'SP',
            'modality_id'=>4,'modality'=>'Concorrência','scope_status'=>'relevant','scope_reason'=>'Projeto',
            'estimated_value'=>125000.50,'deadline'=>now()->addDays(20),'source'=>[]]);
    }
    private function companyDocument(): CompanyDocument
    {
        return CompanyDocument::create(['name'=>'Atestado de arquitetura.pdf','kind'=>'attestation','path'=>'test.pdf','sha256'=>str_repeat('a',64),
            'status'=>'ready','pages'=>[['number'=>3,'text'=>'Atestado de elaboração de projetos arquitetônicos para edificações.']]]);
    }
    public function test_discard_and_export_require_authentication(): void
    {
        $op = $this->opportunity();
        $this->patchJson('/api/opportunities/'.$op->id.'/discard',['discarded'=>true])->assertUnauthorized();
        $this->postJson('/api/opportunities/export',['ids'=>[$op->id]])->assertUnauthorized();
    }
    public function test_discard_is_persistent_reversible_and_excluded_from_dashboard_and_digest(): void
    {
        Mail::fake(); config(['mail.default'=>'smtp']);
        $user = User::factory()->create(); $this->actingAs($user);
        $op = $this->opportunity(); $op->update(['bookmarked'=>true]); $this->companyDocument();
        $profile = SearchProfile::create(['name'=>'Projetos','keywords'=>[],'min_days'=>0,'active'=>true,'email'=>'test@example.test']);
        DB::table('profile_opportunity')->insert(['search_profile_id'=>$profile->id,'opportunity_id'=>$op->id,'created_at'=>now(),'updated_at'=>now()]);
        $this->patchJson('/api/opportunities/'.$op->id.'/discard',['discarded'=>true])->assertOk()->assertJsonPath('discarded',true);
        $this->patchJson('/api/opportunities/'.$op->id.'/discard',['discarded'=>true])->assertOk();
        $this->assertDatabaseCount('opportunity_dismissals',1);
        $this->assertDatabaseHas('opportunity_dismissals',['opportunity_id'=>$op->id,'user_id'=>$user->id]);
        $this->getJson('/api/opportunities')->assertJsonPath('total',0);
        $this->getJson('/api/dashboard')->assertJsonPath('total',0);
        $this->getJson('/api/opportunities?discarded=1')->assertJsonPath('total',1)->assertJsonPath('data.0.discarded',true);
        $this->assertSame(0,app(DigestService::class)->send());
        $this->assertTrue($op->fresh()->bookmarked);
        $this->patchJson('/api/opportunities/'.$op->id.'/discard',['discarded'=>false])->assertOk();
        $this->getJson('/api/opportunities')->assertJsonPath('total',1)->assertJsonPath('data.0.discarded',false);
        $this->assertSame(1,app(DigestService::class)->send());
    }
    public function test_sync_does_not_restore_a_discarded_opportunity(): void
    {
        Queue::fake(); config(['radar.modalities'=>[4]]);
        $op = $this->opportunity(); $this->companyDocument();
        $op->dismissal()->create();
        Http::fake(['*'=>Http::response(['data'=>[['numeroControlePNCP'=>$op->pncp_id,'orgaoEntidade'=>['cnpj'=>$op->cnpj,'razaoSocial'=>'Prefeitura'],
            'anoCompra'=>2026,'sequencialCompra'=>1,'objetoCompra'=>$op->object,'modalidadeId'=>4,
            'dataEncerramentoProposta'=>now()->addDays(20)->toIso8601String()]],'totalPaginas'=>1])]);
        $this->assertSame('completed',app(SyncService::class)->run()->status);
        $this->assertTrue($op->fresh()->dismissal()->exists());
        Queue::assertNothingPushed();
    }
    public function test_pdf_contains_only_selected_opportunities_and_their_evidence(): void
    {
        $this->actingAs(User::factory()->create()); $this->companyDocument();
        $first = $this->opportunity(); $second = $this->opportunity(2); $this->opportunity(3);
        $response = $this->postJson('/api/opportunities/export',['ids'=>[$second->id,$first->id]])
            ->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->assertStringStartsWith('%PDF-',$response->getContent());
        $pdf = (new Parser)->parseContent($response->getContent()); $text = $pdf->getText();
        $this->assertStringContainsString('123-2026-1',$text);
        $this->assertStringContainsString('123-2026-2',$text);
        $this->assertStringNotContainsString('123-2026-3',$text);
        $this->assertStringContainsString('125.000,50',$text);
        $this->assertStringContainsString('Atestado de arquitetura.pdf',$text);
        $this->assertStringContainsString('página 3',$text);
        $this->assertStringContainsString('https://pncp.gov.br/app/editais/12345678000100/2026/2',$text);
        $this->assertLessThan(strpos($text,'123-2026-1'),strpos($text,'123-2026-2'));
    }
    public function test_export_rejects_empty_duplicate_invalid_discarded_or_stale_selection(): void
    {
        $this->actingAs(User::factory()->create()); $doc = $this->companyDocument(); $op = $this->opportunity();
        $this->postJson('/api/opportunities/export',['ids'=>[]])->assertUnprocessable();
        $this->postJson('/api/opportunities/export',['ids'=>[$op->id,$op->id]])->assertUnprocessable();
        $this->postJson('/api/opportunities/export',['ids'=>[999999]])->assertUnprocessable();
        $op->dismissal()->create();
        $this->postJson('/api/opportunities/export',['ids'=>[$op->id]])->assertUnprocessable();
        $op->dismissal()->delete(); $doc->update(['expires_at'=>today()->subDay()]);
        $this->postJson('/api/opportunities/export',['ids'=>[$op->id]])->assertUnprocessable();
        $this->postJson('/api/opportunities/export',['ids'=>array_fill(0,51,$op->id)])->assertUnprocessable();
    }
}
