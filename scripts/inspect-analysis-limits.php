<?php
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$documents = $app->make(App\Services\DocumentAnalysis\AnalysisDocuments::class);
$reader = $app->make(App\Services\Documents\PdfTextReader::class);
$results = [];
foreach (App\Models\Opportunity::has('documents')->withCount('documents')->orderByDesc('documents_count')->get() as $opportunity) {
    $sources = $documents->inventory($opportunity);
    $textChars = $visualBytes = 0;
    foreach ($sources as $source) {
        if (! $source['exists']) continue;
        $bytes = Illuminate\Support\Facades\Storage::disk('local')->get($source['path']);
        $pages = $reader->fromBytes($bytes);
        if (! $pages || collect($pages)->contains(fn ($page) => mb_strlen(trim($page['text'])) < 25)) $visualBytes += strlen($bytes);
        else $textChars += mb_strlen(json_encode(['document' => $source, 'pages' => $pages], JSON_UNESCAPED_UNICODE));
    }
    $results[] = ['opportunity_id' => $opportunity->id, 'documents' => count($sources), 'text_chars' => $textChars, 'visual_bytes' => $visualBytes];
}
echo json_encode(['limits' => ['max_text_chars' => config('document_analysis.max_text_chars'), 'max_visual_bytes' => config('document_analysis.max_visual_bytes'), 'max_documents' => config('document_analysis.max_documents')], 'sets' => $results], JSON_PRETTY_PRINT).PHP_EOL;
