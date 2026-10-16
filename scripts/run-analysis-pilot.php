<?php

require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $opportunity = App\Models\Opportunity::findOrFail(70);
    $service = $app->make(App\Services\DocumentAnalysis\OpportunityDocumentAnalysis::class);
    $state = $service->generate($opportunity);
    $report = $state['report'];
    $directory = __DIR__.'/../output/pdf';
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    $pdfPath = $directory.'/analise-oportunidade-70.pdf';
    file_put_contents($pdfPath, $service->pdf($opportunity));
    echo json_encode(['success' => true, 'opportunity_id' => 70, 'cached' => $state['cached'], 'model' => $report['model'], 'usage' => $report['usage'],
        'scope' => $report['analysis']['scope']['status'], 'requirements' => count($report['analysis']['requirements']),
        'qualifications' => count($report['analysis']['qualifications']), 'deadlines' => count($report['analysis']['deadlines']),
        'warnings' => $report['analysis']['warnings'], 'pdf' => realpath($pdfPath)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
} catch (Throwable $exception) {
    echo json_encode(['success' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(1);
}
