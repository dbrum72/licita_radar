<?php

namespace Tests\Feature;

use App\Integrations\Pncp\Contracts\PncpClient;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Documents\TenderAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
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

    public function test_attachment_deletion_preserves_company_documents(): void
    {
        $op = Opportunity::create(['pncp_id' => 'delete-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Projeto arquitetonico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => []]);
        $doc = Document::create(['opportunity_id' => $op->id, 'name' => 'edital.pdf', 'kind' => 'tender', 'path' => 'documents/test.pdf', 'sha256' => str_repeat('c', 64)]);
        $company = $this->createCompanyDocument(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company-documents/test.pdf', 'sha256' => str_repeat('d', 64)]);
        Storage::disk('local')->put('documents/test.pdf', $this->pdf());
        Storage::disk('local')->put('company-documents/test.pdf', $this->pdf());
        $this->deleteJson('/api/tender-documents/'.$doc->id)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->deleteJson('/api/tender-documents/'.$doc->id)->assertNoContent();
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        $this->assertDatabaseHas('company_documents', ['id' => $company->id]);
        Storage::disk('local')->assertMissing('documents/test.pdf');
        Storage::disk('local')->assertExists('company-documents/test.pdf');
        $this->assertDatabaseHas('opportunities', ['id' => $op->id]);
        $this->deleteJson('/api/tender-documents/'.$doc->id)->assertNotFound();
    }

    public function test_upload_stores_pdf_without_text_or_private_path(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson('/api/documents', ['kind' => 'attestation', 'file' => UploadedFile::fake()->createWithContent('atestado.pdf', $this->pdf())]);
        $response->assertCreated()->assertJsonPath('status', 'stored')->assertJsonMissingPath('pages')->assertJsonMissingPath('path');
        $this->assertDatabaseCount('company_documents', 1);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_remote_documents_are_grouped_and_deduplicated_without_text(): void
    {
        $this->createCompanyDocument(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company.pdf', 'sha256' => str_repeat('a', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projeto arquitetônico para edificação.']]]);
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
            ->assertJsonCount(1, 'opportunity.documents')->assertJsonMissingPath('opportunity.requirements')->assertJsonMissingPath('opportunity.documents.0.pages');
        $directory = 'documents/opportunities/'.$op->id;
        $path = Document::sole()->getRawOriginal('path');
        $this->assertSame($directory, dirname($path));
        Storage::disk('local')->assertExists($path);
        $job->fetch($op->id);
        $this->assertSame(1, Document::count());
        $this->assertSame([$path], Storage::disk('local')->allFiles($directory));
        $this->assertSame('ready', $op->fresh()->attachments_status);
        $documentId = Document::sole()->id;
        Storage::disk('local')->delete($path);
        $job->fetch($op->id);
        $this->assertSame($documentId, Document::sole()->id);
        Storage::disk('local')->assertExists($path);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'untrusted.example'));

        $other = $op->replicate();
        $other->pncp_id = 'test-2026-2';
        $other->sequence = 2;
        $other->save();
        $job->fetch($other->id);
        $otherPath = $other->documents()->sole()->getRawOriginal('path');
        $this->assertSame('documents/opportunities/'.$other->id, dirname($otherPath));
        Storage::disk('local')->assertExists($otherPath);
        $this->assertSame([$path], Storage::disk('local')->allFiles($directory));

        $this->postJson('/api/documents', ['kind' => 'tender', 'opportunity_id' => $op->id,
            'file' => UploadedFile::fake()->createWithContent('complemento.pdf', $pdf)])->assertCreated();
        $this->assertCount(2, Storage::disk('local')->allFiles($directory));
        $this->deleteJson('/api/tender-documents/'.Document::where('path', $path)->sole()->id)->assertNoContent();
        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertExists($otherPath);
        $this->assertCount(1, Storage::disk('local')->allFiles($directory));
    }

    public function test_rate_limit_is_retried_and_reported(): void
    {
        Http::fakeSequence()->push([], 429, ['Retry-After' => '1'])->push(['data' => [], 'totalPaginas' => 0]);
        $result = iterator_to_array(app(PncpClient::class)->openProposals(4));
        $this->assertSame([], $result);
        Http::assertSentCount(2);
    }

    public function test_connection_failures_are_retried_with_a_finite_limit(): void
    {
        $attempts = 0;
        $alwaysFail = false;
        Http::fake(function () use (&$attempts, &$alwaysFail) {
            $attempts++;
            if ($alwaysFail || $attempts === 1) {
                throw new ConnectionException('Simulated network failure');
            }

            return Http::response([], 204);
        });
        $this->assertSame(204, app(PncpClient::class)->request('https://pncp.gov.br/test')->status());
        $this->assertSame(2, $attempts);
        $attempts = 0;
        $alwaysFail = true;
        try {
            app(PncpClient::class)->request('https://pncp.gov.br/test');
            $this->fail('Expected exhausted connection attempts');
        } catch (ConnectionException $exception) {
            $this->assertSame(4, $attempts);
        }
    }

    public function test_one_failed_download_does_not_prevent_remaining_attachments(): void
    {
        $this->createCompanyDocument(['name' => 'acervo.pdf', 'kind' => 'rrt', 'sha256' => str_repeat('a', 64),
            'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projeto arquitetônico para edificação.']]]);
        $op = Opportunity::create(['pncp_id' => 'partial-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Projeto arquitetonico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => []]);
        $base = 'https://pncp.gov.br/api/pncp/v1/orgaos/12345678000100/compras/2026/1/arquivos';
        $optionsSeen = [];
        Http::fake(function ($request, $options) use ($base, &$optionsSeen) {
            $optionsSeen[] = $options;
            if ($request->url() === $base) {
                return Http::response([['sequencialDocumento' => 1, 'titulo' => 'Falha'], ['sequencialDocumento' => 2, 'titulo' => 'Edital']]);
            }
            if ($request->url() === $base.'/1') {
                throw new ConnectionException('Simulated network failure');
            }

            return Http::response($this->pdf());
        });
        $this->actingAs(User::factory()->create())->postJson('/api/opportunities/'.$op->id.'/attachments')
            ->assertOk()->assertJsonPath('attachments_status', 'partial');
        $this->assertSame('Edital.pdf', $op->documents()->sole()->name);
        Storage::disk('local')->assertExists($op->documents()->sole()->getRawOriginal('path'));
        foreach ($optionsSeen as $options) {
            $this->assertSame(180, $options['timeout']);
            $this->assertSame(30, $options['connect_timeout']);
        }
    }

    public function test_manual_attachment_failure_is_reported_and_can_be_retried(): void
    {
        $this->createCompanyDocument(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company.pdf', 'sha256' => str_repeat('b', 64),
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
