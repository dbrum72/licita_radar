<?php

namespace Tests\Feature;

use App\Integrations\Pncp\Contracts\PncpClient;
use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\TenderAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentImportTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(): string
    {
        $stream = 'BT /F1 12 Tf 40 700 Td (Atestado de capacidade tecnica para projetos de arquitetura) Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    public function test_only_explicit_technical_requirements_are_selected(): void
    {
        $service = app(DocumentService::class);
        foreach (['Atestado de capacidade técnica para projetos de arquitetura',
            'Comprovar registro da empresa no CREA ou CAU', 'Apresentar Certidão de Acervo Técnico (CAT)',
            'Indicar responsável técnico com RRT', 'Atestado de execução de projetos estruturais'] as $text) {
            $this->assertTrue($service->isTechnicalRequirement($text), $text);
        }
        foreach (['Certidão negativa de débitos fiscais', 'Habilitação jurídica e contrato social',
            'Certificado de regularidade do FGTS', 'Qualificação econômico-financeira e balanço patrimonial',
            'Apresentar CNPJ e certidão trabalhista', 'A empresa deverá habilitar-se no portal',
            'Certidão de regularidade fiscal emitida pelo CREA'] as $text) {
            $this->assertFalse($service->isTechnicalRequirement($text), $text);
        }
    }

    public function test_attachment_deletion_removes_file_and_source_requirements_preserving_company_documents(): void
    {
        Storage::fake('local');
        $op = Opportunity::create(['pncp_id' => 'delete-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Projeto arquitetonico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => []]);
        $doc = Document::create(['opportunity_id' => $op->id, 'name' => 'edital.pdf', 'kind' => 'tender', 'path' => 'documents/test.pdf', 'sha256' => str_repeat('c', 64)]);
        $company = CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company-documents/test.pdf', 'sha256' => str_repeat('d', 64)]);
        Storage::disk('local')->put('documents/test.pdf', $this->pdf());
        Storage::disk('local')->put('company-documents/test.pdf', $this->pdf());
        app(DocumentService::class)->extract($doc);
        $this->assertDatabaseCount('requirements', 1);
        $this->deleteJson('/api/tender-documents/'.$doc->id)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->deleteJson('/api/tender-documents/'.$doc->id)->assertNoContent();
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        $this->assertDatabaseCount('requirements', 0);
        $this->assertDatabaseHas('company_documents', ['id' => $company->id]);
        Storage::disk('local')->assertMissing('documents/test.pdf');
        Storage::disk('local')->assertExists('company-documents/test.pdf');
        $this->assertDatabaseHas('opportunities', ['id' => $op->id]);
        $this->deleteJson('/api/tender-documents/'.$doc->id)->assertNotFound();
    }

    public function test_upload_extracts_page_text_and_does_not_publish_private_path(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $response = $this->postJson('/api/documents', ['kind' => 'attestation', 'file' => UploadedFile::fake()->createWithContent('atestado.pdf', $this->pdf())]);
        $response->assertCreated()->assertJsonPath('status', 'ready')->assertJsonPath('pages.0.number', 1)->assertJsonMissingPath('path');
        $this->assertStringContainsString('arquitetura', $response->json('pages.0.text'));
        $this->assertDatabaseCount('company_documents', 1);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_remote_documents_are_extracted_and_deduplicated(): void
    {
        CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company.pdf', 'sha256' => str_repeat('a', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projeto arquitetônico para edificação.']]]);
        Storage::fake('local');
        $op = Opportunity::create(['pncp_id' => 'test-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Projeto arquitetonico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => []]);
        $pdf = $this->pdf();
        Http::fake(function ($request) use ($pdf) {
            if (str_ends_with($request->url(), '/arquivos')) {
                return Http::response([['sequencialDocumento' => 1, 'titulo' => 'Edital', 'url' => 'https://untrusted.example/file']]);
            }

            return Http::response($pdf, 200, ['Content-Type' => 'application/pdf']);
        });
        $job = app(TenderAttachmentService::class);
        Queue::fake();
        $this->actingAs(User::factory()->create())->postJson('/api/opportunities/'.$op->id.'/attachments')
            ->assertOk()->assertJsonPath('attachments_status', 'ready');
        Queue::assertNothingPushed();
        $this->getJson('/api/opportunities/'.$op->id)->assertOk()
            ->assertJsonCount(1, 'opportunity.documents')->assertJsonCount(1, 'opportunity.requirements');
        $job->fetch($op->id);
        $this->assertSame(1, Document::count());
        $this->assertSame('ready', $op->fresh()->attachments_status);
        $this->assertDatabaseHas('requirements', ['opportunity_id' => $op->id, 'source_page' => 1, 'status' => 'pending']);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'untrusted.example'));
    }

    public function test_rate_limit_is_retried_and_reported(): void
    {
        Http::fakeSequence()->push([], 429, ['Retry-After' => '1'])->push(['data' => [], 'totalPaginas' => 0]);
        $result = iterator_to_array(app(PncpClient::class)->openProposals(4));
        $this->assertSame([], $result);
        Http::assertSentCount(2);
    }

    public function test_manual_attachment_failure_is_reported_and_can_be_retried(): void
    {
        CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company.pdf', 'sha256' => str_repeat('b', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projeto arquitetônico para edificação.']]]);
        $op = Opportunity::create(['pncp_id' => 'test-2026-2', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 2,
            'object' => 'Projeto arquitetonico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => []]);
        Http::fakeSequence()->push([], 404)->push([], 204);
        Http::assertNothingSent();
        $this->actingAs(User::factory()->create())->postJson('/api/opportunities/'.$op->id.'/attachments')
            ->assertStatus(502)->assertJsonPath('attachments_status', 'failed');
        Http::assertSentCount(1);
        $this->postJson('/api/opportunities/'.$op->id.'/attachments')->assertOk()->assertJsonPath('attachments_status', 'empty');
    }
}
