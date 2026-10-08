<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\Requirement;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function opportunity(): Opportunity
    {
        return Opportunity::create(['pncp_id' => 'review-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Elaboração de projeto arquitetônico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => []]);
    }

    public function test_upload_cannot_overlap_an_attachment_operation(): void
    {
        Storage::fake('local');
        $opportunity = $this->opportunity();
        $lock = Cache::lock('tender-attachments:'.$opportunity->id, 3600);
        $lock->get();
        try {
            $this->actingAs(User::factory()->create())->postJson('/api/documents', [
                'kind' => 'tender', 'opportunity_id' => $opportunity->id,
                'file' => UploadedFile::fake()->create('edital.pdf', 1, 'application/pdf'),
            ])->assertStatus(409);
            $this->assertDatabaseCount('documents', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        } finally {
            $lock->release();
        }
    }

    public function test_candidate_evidence_must_remain_valid_until_the_tender_deadline(): void
    {
        $opportunity = $this->opportunity();
        $source = Document::create(['opportunity_id' => $opportunity->id, 'name' => 'edital.pdf', 'kind' => 'tender', 'path' => 'test.pdf', 'sha256' => str_repeat('1', 64)]);
        $requirement = Requirement::create(['opportunity_id' => $opportunity->id, 'source_document_id' => $source->id, 'source_page' => 1, 'description' => 'Atestado técnico projetos arquitetura']);
        $document = CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'test.pdf', 'sha256' => str_repeat('2', 64),
            'status' => 'ready', 'expires_at' => today()->addDays(10), 'pages' => [['number' => 1, 'text' => 'Atestado técnico projetos arquitetura para edificações.']]]);
        $this->assertSame([], app(DocumentService::class)->candidates($requirement));
        $document->update(['expires_at' => today()->addDays(21)]);
        $this->assertCount(1, app(DocumentService::class)->candidates($requirement));
    }

    public function test_corrupt_download_is_reported_as_partial_instead_of_ready(): void
    {
        Storage::fake('local');
        $opportunity = $this->opportunity();
        CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'test.pdf', 'sha256' => str_repeat('3', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projetos de arquitetura.']]]);
        Http::fake(fn ($request) => str_ends_with($request->url(), '/arquivos')
            ? Http::response([['sequencialDocumento' => 1, 'titulo' => 'Edital']])
            : Http::response('%PDF-1.4 invalid document'));
        $this->actingAs(User::factory()->create())->postJson('/api/opportunities/'.$opportunity->id.'/attachments')
            ->assertOk()->assertJsonPath('attachments_status', 'partial');
        $this->assertDatabaseHas('documents', ['opportunity_id' => $opportunity->id, 'status' => 'failed']);
    }

    public function test_reviewed_verdict_is_reopened_when_evidence_fails_or_its_page_disappears(): void
    {
        $this->actingAs(User::factory()->create());
        $opportunity = $this->opportunity();
        CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'test.pdf', 'sha256' => str_repeat('4', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projetos de arquitetura para edificações.']]]);
        $source = Document::create(['opportunity_id' => $opportunity->id, 'name' => 'edital.pdf', 'kind' => 'tender', 'path' => 'test.pdf',
            'sha256' => str_repeat('5', 64), 'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Atestado técnico']]]);
        $evidence = CompanyDocument::create(['name' => 'evidencia.pdf', 'kind' => 'certificate', 'path' => 'test.pdf',
            'sha256' => str_repeat('6', 64), 'status' => 'ready', 'pages' => [['number' => 2, 'text' => 'Documento de comprovação técnica.']]]);
        $requirement = Requirement::create(['opportunity_id' => $opportunity->id, 'source_document_id' => $source->id,
            'source_page' => 1, 'description' => 'Comprovação técnica', 'status' => 'met', 'evidence_document_id' => $evidence->id, 'evidence_page' => 2]);
        $this->getJson('/api/opportunities/'.$opportunity->id)->assertJsonPath('verdict', 'Requisitos cadastrados revisados');
        $evidence->update(['status' => 'failed']);
        $this->getJson('/api/opportunities/'.$opportunity->id)->assertJsonPath('verdict', 'Análise documental pendente');
        $evidence->update(['status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Página diferente.']]]);
        $this->getJson('/api/opportunities/'.$opportunity->id)->assertJsonPath('verdict', 'Análise documental pendente');
        $this->patchJson('/api/requirements/'.$requirement->id, ['status' => 'met', 'evidence_document_id' => $evidence->id,
            'evidence_page' => 2, 'notes' => 'Revisão'])->assertUnprocessable();
    }
}
