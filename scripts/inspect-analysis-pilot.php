<?php

require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$documents = $app->make(App\Services\DocumentAnalysis\AnalysisDocuments::class);
$opportunities = App\Models\Opportunity::has('documents')->withCount('documents')->orderByDesc('documents_count')->get();
$candidates = [];
foreach ($opportunities as $opportunity) {
    $sources = $documents->inventory($opportunity);
    $available = array_filter($sources, fn ($source) => $source['scope'] === 'tender' && $source['exists']);
    if ($available) {
        $prepared = $documents->prepare($opportunity, $sources);
        $candidates[] = ['id' => $opportunity->id, 'object' => $opportunity->object, 'pdfs_available' => count($available), 'documents' => count($sources),
            'visual_pdfs' => count(array_filter($prepared['sources'], fn ($source) => $source['reading'] === 'visual')), 'warnings' => $prepared['warnings']];
    }
}
echo json_encode($candidates, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
