<?php

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfTextReader
{
    /** Read private PDFs in memory; never persist their text or pages. */
    public function pages(Document $document): array
    {
        try {
            $pdf = (new Parser)->parseFile(Storage::disk('local')->path($document->getRawOriginal('path')));

            return array_map(fn ($page, $index) => ['number' => $index + 1, 'text' => trim($page->getText())], $pdf->getPages(), array_keys($pdf->getPages()));
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }
    }
}
