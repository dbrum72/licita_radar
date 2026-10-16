<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\DocumentAnalysis\OpportunityDocumentAnalysis;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private Opportunity $opportunity;

    private CompanyDocument $company;

    private Document $tender;

    private string $tenderQuote = 'Apresentar atestado de capacidade técnica para projeto arquitetônico de 100 m2.';

    private string $companyQuote = 'Atestado da Empresa Exemplo para projeto arquitetônico de 150 m2.';

    protected function setUp(): void
    {
        parent::setUp();
        config(['document_analysis.api_key' => 'test-key-not-real']);
        Http::preventStrayRequests();
        $this->opportunity = Opportunity::create(['pncp_id' => 'analysis-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Elaboração de projeto arquitetônico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => [], 'attachments_status' => 'ready']);
        $this->company = $this->createCompanyDocument(['name' => 'atestado.pdf', 'kind' => 'attestation', 'sha256' => str_repeat('a', 64), 'status' => 'stored',
            'pages' => [['number' => 1, 'text' => $this->companyQuote.' Texto integral do acervo não deve ser persistido no relatório.']]]);
        $this->tender = Document::create(['opportunity_id' => $this->opportunity->id, 'name' => 'edital.pdf', 'kind' => 'tender',
            'path' => 'documents/opportunities/'.$this->opportunity->id.'/edital.pdf', 'sha256' => str_repeat('b', 64), 'status' => 'stored']);
        Storage::disk('local')->put($this->tender->path, $this->pdf($this->tenderQuote.' Texto integral do edital não deve ser persistido no relatório.'));
    }

    private function pdf(string $text): string
    {
        $pdf = new Dompdf;
        $pdf->loadHtml('<meta charset="utf-8"><div style="font-family: DejaVu Sans">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>');
        $pdf->render();

        return $pdf->output();
    }

    private function analysis(): array
    {
        $origin = ['source_key' => 'tender-'.$this->tender->id, 'page' => 1, 'quote' => $this->tenderQuote];
        $evidence = ['source_key' => 'company-'.$this->company->id, 'page' => 1, 'quote' => $this->companyQuote];

        return ['summary' => 'Confronto preliminar de capacidade técnica; exige revisão humana.',
            'scope' => ['status' => 'aderente', 'reason' => 'Projeto arquitetônico documentado.', 'sources' => [$origin, $evidence]],
            'deadlines' => [], 'qualifications' => [['name' => 'Atestado técnico', 'category' => 'tecnica_operacional', 'holder' => 'Empresa Exemplo',
                'valid_until' => null, 'specialties' => ['Arquitetura'], 'quantities' => '150 m2', 'sources' => [$evidence]]],
            'requirements' => [['description' => 'Atestado de projeto arquitetônico de 100 m2', 'category' => 'tecnica_operacional', 'mandatory' => true,
                'status' => 'comprovada', 'reason' => 'Há atestado para 150 m2.', 'required_quantities' => '100 m2', 'sources' => [$origin], 'evidence' => [$evidence], 'gaps' => []]],
            'warnings' => []];
    }

    private function response(?array $analysis = null): array
    {
        return ['status' => 'completed', 'model' => 'gpt-5.4-mini', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($analysis ?? $this->analysis())]]]],
            'usage' => ['input_tokens' => 1000, 'output_tokens' => 200, 'input_tokens_details' => ['cached_tokens' => 100]]];
    }

    private function endpoint(string $suffix = ''): string
    {
        return '/api/opportunities/'.$this->opportunity->id.'/analysis'.$suffix;
    }

    public function test_analysis_and_reports_require_authentication_and_configuration_without_exposing_secrets(): void
    {
        $this->getJson($this->endpoint())->assertUnauthorized();
        $this->postJson($this->endpoint())->assertUnauthorized();
        $this->getJson($this->endpoint('/download'))->assertUnauthorized();
        $this->getJson($this->endpoint('/sources/company-'.$this->company->id))->assertUnauthorized();
        config(['document_analysis.api_key' => null]);
        $this->actingAs(User::factory()->create())->getJson($this->endpoint())->assertOk()->assertJsonPath('configured', false)->assertJsonMissingPath('api_key');
        $this->postJson($this->endpoint())->assertUnprocessable()->assertJsonValidationErrors('analysis');
        Http::assertNothingSent();
    }

    public function test_analysis_stores_only_final_report_and_reuses_unchanged_inputs_without_another_call(): void
    {
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->response())]);
        $this->actingAs(User::factory()->create());
        $response = $this->postJson($this->endpoint())->assertOk()->assertJsonPath('cached', false)->assertJsonPath('report.review_required', true)
            ->assertJsonPath('report.analysis.requirements.0.status', 'comprovada')->assertJsonPath('report.analysis.requirements.0.evidence.0.verification', 'text_match');
        $this->assertGreaterThan(0, $response->json('report.usage.estimated_cost_usd'));
        $stored = Storage::disk('local')->get('documents/opportunities/'.$this->opportunity->id.'/analysis/report.json');
        $this->assertStringNotContainsString('Texto integral do edital', $stored);
        $this->assertStringNotContainsString('Texto integral do acervo', $stored);
        $this->assertStringNotContainsString('test-key-not-real', $stored);
        $this->assertArrayNotHasKey('path', $response->json('report.sources.0'));
        $this->assertArrayNotHasKey('pages', $response->json('report.sources.0'));
        $this->assertFalse(Schema::hasColumn('documents', 'pages'));
        $this->assertFalse(Schema::hasColumn('company_documents', 'pages'));
        $this->assertFalse(Schema::hasTable('requirements'));
        $this->postJson($this->endpoint())->assertOk()->assertJsonPath('cached', true);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('stale', false);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/responses' && $request['store'] === false
                && $request['text']['format']['strict'] === true && $request['text']['format']['type'] === 'json_schema'
                && count($request['input'][0]['content']) === 3 && ! isset($request['tools']);
        });
        $this->get($this->endpoint('/download'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get($this->endpoint('/sources/company-'.$this->company->id))->assertOk()->assertDownload('atestado.pdf');
        $this->getJson($this->endpoint('/sources/company-9999'))->assertNotFound();
    }

    public function test_changed_acervo_or_date_marks_report_stale_and_source_cannot_open_different_bytes(): void
    {
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->response())]);
        $this->actingAs(User::factory()->create())->postJson($this->endpoint())->assertOk();
        $original = Storage::disk('local')->get($this->company->path);
        Storage::disk('local')->put($this->company->path, $original.' changed');
        $this->getJson($this->endpoint())->assertJsonPath('stale', true);
        $this->getJson($this->endpoint('/sources/company-'.$this->company->id))->assertStatus(409);
        Storage::disk('local')->put($this->company->path, $original);
        $this->travel(1)->days();
        $this->actingAs(User::factory()->create())->getJson($this->endpoint())->assertJsonPath('stale', true);
        Http::assertSentCount(1);
    }

    public function test_unknown_document_page_or_invented_quote_is_rejected_without_overwriting_report(): void
    {
        $sequence = Http::fakeSequence()->push($this->response());
        $service = app(OpportunityDocumentAnalysis::class);
        $service->generate($this->opportunity);
        $path = 'documents/opportunities/'.$this->opportunity->id.'/analysis/report.json';
        $before = Storage::disk('local')->get($path);
        foreach (['source_key' => 'company-9999', 'page' => 999, 'quote' => 'Comprovação inventada pela IA.'] as $field => $value) {
            $analysis = $this->analysis();
            $analysis['requirements'][0]['evidence'][0][$field] = $value;
            $sequence->push($this->response($analysis));
            $this->actingAs(User::factory()->create())->postJson($this->endpoint(), ['force' => true])->assertStatus(502);
            $this->assertSame($before, Storage::disk('local')->get($path));
        }
    }

    public function test_missing_files_and_expired_evidence_are_explicit_and_not_silently_approved(): void
    {
        $this->company->update(['expires_at' => today()->subDay()]);
        Document::create(['opportunity_id' => $this->opportunity->id, 'name' => 'anexo-ausente.pdf', 'kind' => 'tender', 'path' => 'missing.pdf', 'sha256' => str_repeat('c', 64)]);
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->response())]);
        $response = $this->actingAs(User::factory()->create())->postJson($this->endpoint())->assertOk()->assertJsonPath('report.analysis.requirements.0.status', 'parcial');
        $this->assertStringContainsString('anexo-ausente.pdf', implode(' ', $response->json('report.analysis.warnings')));
        $this->assertStringContainsString('validade', implode(' ', $response->json('report.analysis.requirements.0.gaps')));
    }

    public function test_unregistered_folder_pdfs_are_included_and_scanned_pages_use_visual_file_input(): void
    {
        Storage::disk('local')->put('company-documents/extra.pdf', $this->pdf('Certificação adicional da Empresa Exemplo para serviços técnicos.'));
        Storage::disk('local')->put('documents/opportunities/'.$this->opportunity->id.'/digitalizado.pdf', $this->pdf(''));
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->response())]);
        $response = $this->actingAs(User::factory()->create())->postJson($this->endpoint())->assertOk()->assertJsonCount(4, 'report.sources');
        $this->assertStringContainsString('extra.pdf', implode(' ', $response->json('report.analysis.warnings')));
        Http::assertSent(fn ($request) => collect($request['input'][0]['content'])->contains(fn ($part) => $part['type'] === 'input_file' && str_starts_with($part['file_data'], 'data:application/pdf;base64,') && $part['detail'] === 'high'));
    }

    public function test_limits_concurrency_and_incomplete_provider_response_preserve_previous_report(): void
    {
        $sequence = Http::fakeSequence()->push($this->response())->push(['status' => 'incomplete']);
        $service = app(OpportunityDocumentAnalysis::class);
        $service->generate($this->opportunity);
        $before = Storage::disk('local')->get('documents/opportunities/'.$this->opportunity->id.'/analysis/report.json');
        config(['document_analysis.max_text_chars' => 1]);
        $this->actingAs(User::factory()->create())->postJson($this->endpoint(), ['force' => true])->assertUnprocessable();
        Http::assertSentCount(1);
        config(['document_analysis.max_text_chars' => 300000]);
        $lock = Cache::lock('opportunity-document-analysis:'.$this->opportunity->id, 600);
        $lock->get();
        $this->postJson($this->endpoint())->assertStatus(409);
        $lock->release();
        $this->travel(5)->minutes();
        $this->postJson($this->endpoint(), ['force' => true])->assertStatus(502);
        $this->assertSame($before, Storage::disk('local')->get('documents/opportunities/'.$this->opportunity->id.'/analysis/report.json'));
    }

    public function test_document_change_during_external_call_does_not_save_a_misleading_report(): void
    {
        Http::fake(function () {
            Storage::disk('local')->put($this->company->path, $this->pdf('Documentação alterada durante a análise.'));

            return Http::response($this->response());
        });
        $this->actingAs(User::factory()->create())->postJson($this->endpoint())->assertStatus(409);
        Storage::disk('local')->assertMissing('documents/opportunities/'.$this->opportunity->id.'/analysis/report.json');
    }

    public static function providerFailures(): array
    {
        return [
            'quota' => [429, ['error' => ['message' => 'PRIVATE_PROVIDER_MESSAGE', 'code' => 'insufficient_quota']], 502],
            'credentials' => [401, ['error' => ['message' => 'PRIVATE_PROVIDER_MESSAGE']], 502],
            'refusal' => [200, ['status' => 'completed', 'output' => [['content' => [['type' => 'refusal', 'refusal' => 'PRIVATE_PROVIDER_MESSAGE']]]]], 422],
            'invalid-json' => [200, ['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => 'not-json']]]]], 502],
        ];
    }

    #[DataProvider('providerFailures')]
    public function test_provider_failures_are_not_retried_or_stored_and_do_not_expose_the_raw_response(int $httpStatus, array $body, int $expectedStatus): void
    {
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($body, $httpStatus)]);
        $response = $this->actingAs(User::factory()->create())->postJson($this->endpoint())->assertStatus($expectedStatus);
        $this->assertStringNotContainsString('PRIVATE_PROVIDER_MESSAGE', $response->getContent());
        $this->assertStringNotContainsString('test-key-not-real', $response->getContent());
        Http::assertSentCount(1);
        Storage::disk('local')->assertMissing('documents/opportunities/'.$this->opportunity->id.'/analysis/report.json');
    }
}
