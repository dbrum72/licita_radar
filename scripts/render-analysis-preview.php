<?php

require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Storage::fake('local');
$opportunity = (new App\Models\Opportunity)->forceFill(['id' => 999999, 'pncp_id' => 'EXEMPLO-FICTICIO', 'object' => 'EXEMPLO FICTÍCIO - projetos de arquitetura e engenharia para uma escola', 'agency' => 'Órgão fictício']);
$origin = ['source_key' => 'tender-1', 'source_name' => 'Edital-exemplo.pdf', 'page' => 8, 'quote' => 'Comprovar elaboração de projeto arquitetônico com área mínima de 100 m2.', 'verification' => 'text_match'];
$evidence = ['source_key' => 'company-1', 'source_name' => 'Atestado-exemplo.pdf', 'page' => 2, 'quote' => 'Elaboração de projeto arquitetônico de 150 m2 para a Empresa Exemplo.', 'verification' => 'text_match'];
$requirements = [];
foreach (['comprovada', 'parcial', 'nao_comprovada', 'inconclusiva'] as $index => $status) {
    $requirements[] = ['description' => ['Atestado de projeto arquitetônico com área mínima de 100 m2', 'Registro profissional e vínculo do responsável técnico', 'Comprovação de projeto elétrico para instalações escolares', 'Certificação mencionada em anexo com legibilidade insuficiente'][$index],
        'category' => $index === 1 ? 'tecnica_profissional' : 'tecnica_operacional', 'mandatory' => true, 'status' => $status,
        'reason' => ['O exemplo contém atestado com quantitativo superior ao mínimo indicado.', 'O registro aparece no acervo, mas o vínculo profissional precisa ser confirmado.', 'O conjunto fictício não contém comprovação específica de projeto elétrico.', 'A informação não é suficiente para concluir o atendimento.'][$index],
        'required_quantities' => $index === 0 ? '100 m2' : 'Conforme cláusula citada', 'sources' => [$origin], 'evidence' => $index < 2 ? [$evidence] : [],
        'gaps' => $index === 0 ? [] : ['Conferir o documento original e obter a evidência necessária antes de decidir pela participação.']];
}
$report = ['version' => 1, 'fingerprint' => 'demo', 'generated_at' => now('America/Sao_Paulo')->toIso8601String(),
    'opportunity' => ['id' => 999999, 'pncp_id' => 'EXEMPLO-FICTICIO', 'object' => $opportunity->object, 'agency' => $opportunity->agency, 'deadline_pncp' => '2026-11-05T10:00:00-03:00'],
    'model' => 'Resposta simulada - exemplo fictício', 'review_required' => true,
    'notice' => 'EXEMPLO FICTÍCIO PARA CONFERÊNCIA DE LAYOUT. Não foi produzido pela IA e não analisa documentos reais. Resultados e dados abaixo são simulados.',
    'usage' => ['input_tokens' => 1000, 'cached_input_tokens' => 100, 'output_tokens' => 200, 'estimated_cost_usd' => 0.001583],
    'sources' => [['key' => 'tender-1', 'scope' => 'tender', 'name' => 'Edital-exemplo.pdf', 'reading' => 'text'],
        ['key' => 'company-1', 'scope' => 'company', 'name' => 'Atestado-exemplo.pdf', 'reading' => 'text'],
        ['key' => 'company-2', 'scope' => 'company', 'name' => 'Certificação-exemplo.pdf', 'reading' => 'visual'],
        ['key' => 'tender-2', 'scope' => 'tender', 'name' => 'Retificação-ausente-exemplo.pdf', 'reading' => 'missing']],
    'analysis' => ['summary' => 'Este exemplo demonstra a classificação do acervo, o confronto entre exigências e evidências, os prazos e as pendências. Não constitui comprovação de habilitação.',
        'scope' => ['status' => 'parcial', 'reason' => 'A arquitetura está documentada no exemplo, mas as especialidades complementares ainda exigem comprovação.', 'sources' => [$origin, $evidence]],
        'warnings' => ['Retificação ausente no exemplo: confira se houve alteração de exigências ou prazos.', 'Documento lido visualmente: confira a referência no PDF original.'],
        'deadlines' => [['label' => 'Entrega de propostas', 'value' => '05/11/2026 às 10h', 'notes' => 'Data fictícia, exclusivamente para demonstrar a apresentação.', 'sources' => [$origin]],
            ['label' => 'Execução dos projetos', 'value' => '60 dias após a ordem de serviço', 'notes' => 'Prazo relativo fictício; depende da emissão da ordem de serviço.', 'sources' => [$origin]]],
        'qualifications' => [['name' => 'Atestado de capacidade técnica operacional', 'category' => 'tecnica_operacional', 'holder' => 'Empresa Exemplo', 'valid_until' => null,
            'specialties' => ['Arquitetura', 'Edificações'], 'quantities' => '150 m2', 'sources' => [$evidence]],
            ['name' => 'Registro do responsável técnico', 'category' => 'tecnica_profissional', 'holder' => 'Profissional fictício', 'valid_until' => '2026-12-31',
                'specialties' => ['Arquitetura'], 'quantities' => 'Sem quantitativo informado no exemplo', 'sources' => [$evidence]]],
        'requirements' => $requirements]];
Illuminate\Support\Facades\Storage::disk('local')->put('documents/opportunities/999999/analysis/report.json', json_encode($report));
$documents = Mockery::mock(App\Services\DocumentAnalysis\AnalysisDocuments::class);
$documents->shouldReceive('inventory')->andReturn([]);
$documents->shouldReceive('fingerprint')->andReturn('demo');
$service = new App\Services\DocumentAnalysis\OpportunityDocumentAnalysis($documents, new App\Integrations\OpenAi\DocumentAnalysisClient, new App\Services\DocumentAnalysis\AnalysisReportValidator);
$directory = __DIR__.'/../output/pdf';
if (! is_dir($directory)) mkdir($directory, 0775, true);
$path = $directory.'/exemplo-relatorio-documental.pdf';
file_put_contents($path, $service->pdf($opportunity));
echo realpath($path).PHP_EOL;
Mockery::close();
