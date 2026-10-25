<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\DocumentAnalysis\AnalysisDocuments;
use App\Services\DocumentAnalysis\AnalysisReportValidator;
use App\Services\DocumentAnalysis\OpportunityDocumentAnalysis;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    public function test_unknown_document_wrong_scope_or_page_is_removed_without_positive_conclusions(): void
    {
        $sequence = Http::fakeSequence();
        foreach (['company-9999', 'tender-'.$this->tender->id, null] as $key) {
            $analysis = $this->analysis();
            if ($key) {
                $analysis['requirements'][0]['evidence'][0]['source_key'] = $key;
            } else {
                $analysis['requirements'][0]['evidence'][0]['page'] = 999;
            }
            $sequence->push($this->response($analysis));
            $response = $this->actingAs(User::factory()->create())->postJson($this->endpoint(), ['force' => true])->assertOk()
                ->assertJsonPath('report.analysis.scope.status', 'inconclusivo')
                ->assertJsonPath('report.analysis.requirements.0.status', 'inconclusiva')
                ->assertJsonCount(0, 'report.analysis.requirements.0.evidence');
            $this->assertStringContainsString('Referência removida', implode(' ', $response->json('report.analysis.warnings')));
        }
    }

    public function test_unmatched_quotes_are_removed_and_never_support_a_positive_conclusion(): void
    {
        $analysis = $this->analysis();
        $analysis['scope']['sources'][0]['quote'] = 'Trecho inventado que não consta no documento.';
        $analysis['requirements'][0]['evidence'][0]['quote'] = 'Capacidade para 999999 m2.';
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->response($analysis))]);
        $response = $this->actingAs(User::factory()->create())->postJson($this->endpoint())->assertOk()
            ->assertJsonPath('report.analysis.scope.status', 'inconclusivo')
            ->assertJsonPath('report.analysis.requirements.0.status', 'inconclusiva')
            ->assertJsonCount(0, 'report.analysis.requirements.0.evidence')
            ->assertJsonCount(1, 'report.analysis.scope.sources');
        $this->assertStringContainsString('Citação não confirmada removida', implode(' ', $response->json('report.analysis.warnings')));
        $this->assertStringNotContainsString('999999', json_encode($response->json('report.analysis')));
        Http::assertSentCount(1);
    }

    public function test_pdf_typography_differences_do_not_reject_literal_citations(): void
    {
        $this->writeCompanyPdf($this->company, [['number' => 1, 'text' => 'Atestado de projeto arquitetônico com ‘fiscalização’ e 150 m2.']]);
        $analysis = $this->analysis();
        $reference = ['source_key' => 'company-'.$this->company->id, 'page' => 1, 'quote' => "Atestado de projeto arquitetônico com 'fiscalização' e 150 m2."];
        $analysis['scope']['sources'][1] = $reference;
        $analysis['qualifications'][0]['sources'][0] = $reference;
        $analysis['requirements'][0]['evidence'][0] = $reference;
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->response($analysis))]);
        $this->actingAs(User::factory()->create())->postJson($this->endpoint())->assertOk()
            ->assertJsonPath('report.analysis.requirements.0.status', 'comprovada')
            ->assertJsonPath('report.analysis.requirements.0.evidence.0.verification', 'text_match');
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

    public function test_analysis_extends_php_budget_and_returns_timeout_with_cors_and_releases_lock(): void
    {
        $originalLimit = (int) ini_get('max_execution_time');
        config(['document_analysis.timeout' => 180, 'document_analysis.max_calls' => 8]);
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            $this->assertGreaterThanOrEqual(180 * 8 + 600, (int) ini_get('max_execution_time'));

            throw new ConnectionException('cURL error 28: Simulated provider timeout');
        });
        try {
            set_time_limit(30);
            $this->actingAs(User::factory()->create())->withHeader('Origin', 'http://127.0.0.1:5173')
                ->postJson($this->endpoint())->assertStatus(504)
                ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173')
                ->assertJsonPath('message', 'A conexão com a IA excedeu o tempo de espera. Os lotes concluídos foram preservados para a próxima tentativa manual; a chamada interrompida pode ter consumido créditos.');
            $this->assertSame(30, (int) ini_get('max_execution_time'));
            $this->assertSame(1, $attempts);
            Storage::disk('local')->assertMissing('documents/opportunities/'.$this->opportunity->id.'/analysis/report.json');
            $lock = Cache::lock('opportunity-document-analysis:'.$this->opportunity->id, 60);
            $this->assertTrue($lock->get());
            $lock->release();
        } finally {
            set_time_limit($originalLimit);
        }
    }

    public function test_large_document_set_uses_small_batches_and_consolidates_all_sources(): void
    {
        config(['document_analysis.batch_text_chars' => 8192, 'document_analysis.max_text_chars' => 500000]);
        $this->writeCompanyPdf($this->company, [['number' => 1, 'text' => str_repeat('Capacidade técnica em projetos arquitetônicos. ', 220)]]);
        $documents = app(AnalysisDocuments::class);
        $prepared = $documents->prepare($this->opportunity, $documents->inventory($this->opportunity));
        $this->assertGreaterThan(1, count($prepared['batches']));
        $keys = [];
        foreach ($prepared['batches'] as $batch) {
            $this->assertLessThanOrEqual(8192, $batch['text_chars']);
            $keys = [...$keys, ...$batch['keys']];
        }
        $this->assertEqualsCanonicalizing(array_column($prepared['sources'], 'key'), array_values(array_unique($keys)));
        $analysis = $this->analysis();
        $analysis['scope']['sources'] = [];
        $analysis['qualifications'] = [];
        $analysis['requirements'] = [];
        Http::fake(function ($request) use ($analysis) {
            $context = json_decode($request['input'][0]['content'][0]['text'], true);
            if (isset($context['batch_sources'])) {
                $this->assertEqualsCanonicalizing($context['batch_sources'], array_column($context['documents'], 'key'));
            }

            if (($context['batch'] ?? null) === 1) {
                $analysis['scope']['sources'] = [['source_key' => 'outside-batch', 'page' => 1, 'quote' => 'Not evidence']];
            }

            return Http::response($this->response($analysis));
        });
        $state = app(OpportunityDocumentAnalysis::class)->generate($this->opportunity);
        $this->assertSame('staged', $state['report']['processing']['mode']);
        $this->assertContains(AnalysisReportValidator::REFERENCE_WARNING, $state['report']['analysis']['warnings']);
        $this->assertStringContainsString('Referência removida', implode(' ', $state['report']['analysis']['warnings']));
        $this->assertSame(count($prepared['batches']) + 1, $state['report']['processing']['calls']);
        $this->assertCount(count($prepared['sources']), $state['report']['sources']);
        Http::assertSentCount(count($prepared['batches']) + 1);
    }

    public static function resumeCases(): array
    {
        return ['unchanged' => [false], 'changed-document' => [true]];
    }

    #[DataProvider('resumeCases')]
    public function test_failed_consolidation_resumes_only_unchanged_validated_batches(bool $changeDocument): void
    {
        config(['document_analysis.batch_text_chars' => 8192]);
        $text = str_repeat('Capacidade técnica em projetos arquitetônicos. ', 220);
        $this->writeCompanyPdf($this->company, [['number' => 1, 'text' => $text]]);
        $documents = app(AnalysisDocuments::class);
        $prepared = $documents->prepare($this->opportunity, $documents->inventory($this->opportunity));
        $batchCount = count($prepared['batches']);
        $analysis = $this->analysis();
        $analysis['scope']['sources'] = [];
        $analysis['qualifications'] = [];
        $analysis['requirements'] = [];
        $calls = 0;
        Http::fake(function () use (&$calls, $batchCount, $analysis) {
            $calls++;
            if ($calls === $batchCount + 1) {
                throw new ConnectionException('cURL error 56: PRIVATE_CONNECTION_DETAILS');
            }

            return Http::response($this->response($analysis));
        });
        $service = app(OpportunityDocumentAnalysis::class);
        try {
            $service->generate($this->opportunity);
            $this->fail('Expected network failure during consolidation');
        } catch (HttpException $exception) {
            $this->assertSame(502, $exception->getStatusCode());
            $this->assertStringContainsString('conexão', $exception->getMessage());
            $this->assertStringNotContainsString('PRIVATE_CONNECTION_DETAILS', $exception->getMessage());
        }
        $path = 'documents/opportunities/'.$this->opportunity->id.'/analysis/';
        Storage::disk('local')->assertExists($path.'progress.json');
        Storage::disk('local')->assertMissing($path.'report.json');
        $this->assertStringNotContainsString($text, Storage::disk('local')->get($path.'progress.json'));
        if ($changeDocument) {
            Storage::disk('local')->put($this->company->path, Storage::disk('local')->get($this->company->path)."\n% changed");
        }
        $report = $service->generate($this->opportunity)['report'];
        $this->assertSame($changeDocument ? 0 : $batchCount, $report['processing']['reused_batches']);
        $this->assertSame($changeDocument ? $batchCount + 1 : 1, $report['processing']['calls']);
        $this->assertSame($batchCount + 1 + $report['processing']['calls'], $calls);
        Storage::disk('local')->assertMissing($path.'progress.json');
        Storage::disk('local')->assertExists($path.'report.json');
    }

    public static function providerFailures(): array
    {
        return [
            'output-limit' => [200, ['status' => 'incomplete', 'incomplete_details' => ['reason' => 'max_output_tokens'], 'usage' => ['output_tokens' => 16000, 'output_tokens_details' => ['reasoning_tokens' => 12000]]], 502],
            'content-filter' => [200, ['status' => 'incomplete', 'incomplete_details' => ['reason' => 'content_filter']], 502],
            'quota' => [429, ['error' => ['message' => 'PRIVATE_PROVIDER_MESSAGE', 'code' => 'insufficient_quota']], 503],
            'billing-limit' => [429, ['error' => ['message' => 'PRIVATE_PROVIDER_MESSAGE', 'code' => 'billing_hard_limit_reached']], 503],
            'rate-limit' => [429, ['error' => ['message' => 'PRIVATE_PROVIDER_MESSAGE', 'code' => 'rate_limit_exceeded']], 429],
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
        if (($body['incomplete_details']['reason'] ?? null) === 'max_output_tokens') {
            $this->assertStringContainsString('OPENAI_ANALYSIS_MAX_OUTPUT_TOKENS', $response->json('message'));
        }
        if (($body['incomplete_details']['reason'] ?? null) === 'content_filter') {
            $this->assertStringContainsString('filtro de conteúdo', $response->json('message'));
        }
        $this->assertStringNotContainsString('PRIVATE_PROVIDER_MESSAGE', $response->getContent());
        $this->assertStringNotContainsString('test-key-not-real', $response->getContent());
        Http::assertSentCount(1);
        Storage::disk('local')->assertMissing('documents/opportunities/'.$this->opportunity->id.'/analysis/report.json');
    }
}
