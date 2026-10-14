<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
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

    public function test_download_does_not_parse_or_persist_pdf_text(): void
    {
        Storage::fake('local');
        $opportunity = $this->opportunity();
        $this->createCompanyDocument(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'test.pdf', 'sha256' => str_repeat('3', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projetos de arquitetura.']]]);
        Http::fake(fn ($request) => str_ends_with($request->url(), '/arquivos')
            ? Http::response([['sequencialDocumento' => 1, 'titulo' => 'Edital']])
            : Http::response('%PDF-1.4 invalid document'));
        $this->actingAs(User::factory()->create())->postJson('/api/opportunities/'.$opportunity->id.'/attachments')
            ->assertOk()->assertJsonPath('attachments_status', 'ready');
        $this->assertDatabaseHas('documents', ['opportunity_id' => $opportunity->id, 'status' => 'stored']);
    }
}
