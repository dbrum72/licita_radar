<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Storage};
use Illuminate\Http\UploadedFile;
use App\Models\{User, Opportunity, Document};
use App\Jobs\FetchTenderDocuments;
use App\Services\{PncpClient, DocumentService};

class DocumentImportTest extends TestCase
{
    use RefreshDatabase;
    private function pdf(): string
    {
        $stream = "BT /F1 12 Tf 40 700 Td (Atestado de capacidade tecnica para projetos de arquitetura) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
        ];
        $pdf="%PDF-1.4\n"; $offsets=[0];
        foreach ($objects as $i=>$object) { $offsets[]=strlen($pdf); $pdf.=($i+1)." 0 obj\n{$object}\nendobj\n"; }
        $xref=strlen($pdf); $pdf.="xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets,1) as $offset) $pdf.=sprintf('%010d 00000 n ',$offset)."\n";
        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
    public function test_upload_extracts_page_text_and_does_not_publish_private_path(): void
    {
        Storage::fake('local'); $this->actingAs(User::factory()->create());
        $response=$this->postJson('/api/documents',['kind'=>'attestation','file'=>UploadedFile::fake()->createWithContent('atestado.pdf',$this->pdf())]);
        $response->assertCreated()->assertJsonPath('status','ready')->assertJsonPath('pages.0.number',1)->assertJsonMissingPath('path');
        $this->assertStringContainsString('arquitetura',$response->json('pages.0.text'));
        $this->assertDatabaseCount('company_documents',1);
        $this->assertDatabaseCount('documents',0);
    }
    public function test_remote_documents_are_extracted_and_deduplicated(): void
    {
        \App\Models\CompanyDocument::create(['name'=>'acervo.pdf','kind'=>'rrt','path'=>'company.pdf','sha256'=>str_repeat('a',64),
            'status'=>'ready','pages'=>[['number'=>1,'text'=>'Registro de elaboração de projeto arquitetônico para edificação.']]]);
        Storage::fake('local');
        $op=Opportunity::create(['pncp_id'=>'test-2026-1','cnpj'=>'12345678000100','year'=>2026,'sequence'=>1,
            'object'=>'Projeto arquitetonico','agency'=>'Prefeitura','modality_id'=>4,'modality'=>'Concorrência',
            'scope_reason'=>'Projeto','scope_status'=>'relevant','deadline'=>now()->addDays(20),'source'=>[]]);
        $pdf=$this->pdf();
        Http::fake(function ($request) use ($pdf) {
            if (str_ends_with($request->url(),'/arquivos')) return Http::response([['sequencialDocumento'=>1,'titulo'=>'Edital','url'=>'https://untrusted.example/file']]);
            return Http::response($pdf,200,['Content-Type'=>'application/pdf']);
        });
        $job=new FetchTenderDocuments($op->id);
        $job->handle(app(PncpClient::class),app(DocumentService::class));
        $job->handle(app(PncpClient::class),app(DocumentService::class));
        $this->assertSame(1,Document::count()); $this->assertSame('ready',$op->fresh()->attachments_status);
        $this->assertDatabaseHas('requirements',['opportunity_id'=>$op->id,'source_page'=>1,'status'=>'pending']);
        Http::assertNotSent(fn ($r) => str_contains($r->url(),'untrusted.example'));
    }
    public function test_rate_limit_is_retried_and_reported(): void
    {
        Http::fakeSequence()->push([],429,['Retry-After'=>'1'])->push(['data'=>[],'totalPaginas'=>0]);
        $result=iterator_to_array(app(PncpClient::class)->publications(4,'20261005','20261006'));
        $this->assertSame([],$result); Http::assertSentCount(2);
    }
}
