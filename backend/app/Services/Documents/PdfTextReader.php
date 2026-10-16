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
            return $this->fromBytes(Storage::disk('local')->get($document->getRawOriginal('path')));
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }
    }

    public function fromBytes(string $bytes): array
    {
        try {
            $pdf = (new Parser)->parseContent($bytes);
            $pages = array_values($pdf->getPages());

            return array_map(fn ($page, $index) => ['number' => $index + 1, 'text' => trim($page->getText())], $pages, array_keys($pages));
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }
    }
}
