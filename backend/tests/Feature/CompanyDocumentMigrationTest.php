<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyDocumentMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_final_schema_keeps_pdf_files_without_text_or_requirements(): void
    {
        $this->assertFalse(Schema::hasColumn('documents', 'pages'));
        $this->assertFalse(Schema::hasColumn('company_documents', 'pages'));
        $this->assertFalse(Schema::hasTable('requirements'));
        $company = $this->createCompanyDocument(['name' => 'Empresa.pdf', 'kind' => 'rrt', 'path' => 'company.pdf', 'sha256' => str_repeat('a', 64)]);
        $this->getJson('/api/documents/'.$company->id.'/download')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->get('/api/documents/'.$company->id.'/download')->assertOk();
        $this->getJson('/api/documents/'.$company->id)->assertNotFound();
        $this->getJson('/api/tender-documents/1')->assertStatus(405);
        $this->patchJson('/api/requirements/1', [])->assertNotFound();
        Storage::disk('local')->delete($company->path);
        $this->getJson('/api/documents/'.$company->id.'/download')->assertNotFound()
            ->assertJsonPath('message', 'Arquivo PDF não encontrado no storage.');
    }

    public function test_cleanup_removes_legacy_text_and_requirements_preserving_records_and_files(): void
    {
        $op = Opportunity::create(['pncp_id' => 'cleanup-test', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Projetos', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência', 'scope_reason' => 'Projeto', 'source' => [],
            'attachments_status' => 'partial', 'attachments_error' => 'Há PDFs com extração incompleta ou falha.']);
        $company = $this->createCompanyDocument(['name' => 'Empresa.pdf', 'kind' => 'rrt', 'path' => 'company.pdf', 'sha256' => str_repeat('a', 64)]);
        $tender = Document::create(['opportunity_id' => $op->id, 'name' => 'Edital.pdf', 'kind' => 'tender', 'path' => 'documents/tender.pdf', 'sha256' => str_repeat('b', 64)]);
        Storage::disk('local')->put($tender->path, '%PDF-original');
        DB::statement('ALTER TABLE documents ADD COLUMN pages JSON NULL');
        DB::statement('ALTER TABLE company_documents ADD COLUMN pages JSON NULL');
        DB::table('documents')->update(['pages' => json_encode([['text' => 'Texto antigo']])]);
        DB::table('company_documents')->update(['pages' => json_encode([['text' => 'Acervo antigo']])]);
        DB::statement('CREATE TABLE requirements (id BIGINT PRIMARY KEY, description TEXT)');
        DB::table('requirements')->insert(['id' => 1, 'description' => 'Requisito antigo']);
        $this->artisan('radar:remove-pdf-text')->expectsOutputToContain('1 requisitos excluídos')->assertSuccessful();
        $this->assertFalse(Schema::hasColumn('documents', 'pages'));
        $this->assertFalse(Schema::hasColumn('company_documents', 'pages'));
        $this->assertFalse(Schema::hasTable('requirements'));
        $this->assertDatabaseCount('opportunities', 1);
        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('company_documents', 1);
        Storage::disk('local')->assertExists($company->path);
        $this->assertSame('%PDF-original', Storage::disk('local')->get($tender->path));
        $this->assertSame('ready', $op->fresh()->attachments_status);
        $this->artisan('radar:remove-pdf-text')->assertSuccessful();
    }
}
