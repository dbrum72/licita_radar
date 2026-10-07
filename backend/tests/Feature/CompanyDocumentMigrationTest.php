<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\{Opportunity, Document, CompanyDocument, Requirement, User};

class CompanyDocumentMigrationTest extends TestCase
{
    use DatabaseMigrations;
    public function test_final_schema_keeps_documents_and_foreign_keys_independent(): void
    {
        $op=Opportunity::create(['pncp_id'=>'schema-test','cnpj'=>'12345678000100','year'=>2026,'sequence'=>1,
            'object'=>'Projetos','agency'=>'Prefeitura','modality_id'=>4,'modality'=>'Concorrência','scope_reason'=>'Projeto','source'=>[]]);
        $company=CompanyDocument::create(['name'=>'Empresa.pdf','kind'=>'rrt','path'=>'company-documents/company.pdf','sha256'=>str_repeat('a',64),
            'status'=>'ready','pages'=>[['number'=>1,'text'=>'Projeto de arquitetura']], 'expires_at'=>'2030-01-01']);
        $tender=Document::create(['opportunity_id'=>$op->id,'name'=>'Edital.pdf','kind'=>'tender','path'=>'documents/tender.pdf','sha256'=>str_repeat('b',64),
            'status'=>'ready','pages'=>[['number'=>1,'text'=>'Atestado para projeto de arquitetura']]]);
        $req=Requirement::create(['opportunity_id'=>$op->id,'source_document_id'=>$tender->id,'source_page'=>1,
            'description'=>'Projeto de arquitetura','status'=>'met','evidence_document_id'=>$company->id,'evidence_page'=>1,'notes'=>'Conferido']);
        $this->assertSame($company->id,$tender->id);
        $this->assertSame('Empresa.pdf',$req->fresh()->evidence->name);
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/documents/'.$company->id)->assertOk()->assertJsonPath('name','Empresa.pdf');
        $this->getJson('/api/tender-documents/'.$tender->id)->assertOk()->assertJsonPath('name','Edital.pdf');
        $company->delete();
        $this->assertNull($req->fresh()->evidence_document_id);
        $this->assertSame($tender->id,$req->fresh()->source_document_id);
        $this->assertNotNull(Document::find($tender->id));
        $tender->delete();
        $this->assertNull(Requirement::find($req->id));
    }
}