<?php

namespace Tests\Feature;

use App\Services\CompanyDocuments\CompanyScopeService;
use App\Services\Documents\PdfTextReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfMemoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_reads_current_file_without_saving_its_text(): void
    {
        $doc = $this->createCompanyDocument(['name' => 'acervo.pdf', 'kind' => 'rrt', 'status' => 'stored', 'sha256' => str_repeat('a', 64),
            'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projetos arquitetônicos para edificações.']]]);
        $pages = app(PdfTextReader::class)->pages($doc);
        $this->assertStringContainsString('arquitet', $pages[0]['text'] ?? '', json_encode($pages));
        $scope = app(CompanyScopeService::class);
        $this->assertTrue($scope->hasUsableDocuments());
        $this->assertSame('compatible', $scope->assess('Projeto arquitetônico')['status']);
        $this->assertArrayNotHasKey('pages', $doc->fresh()->getAttributes());
        $this->writeCompanyPdf($doc, [['number' => 1, 'text' => 'Certidão de regularidade fiscal da empresa.']]);
        $scope->refresh();
        $this->assertSame('incompatible', $scope->assess('Projeto arquitetônico')['status']);
        $this->writeCompanyPdf($doc, []);
        $this->assertFalse($scope->hasUsableDocuments());
    }
}
