<?php

namespace App\Services;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\Requirement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class DocumentService
{
    public function extract(Document $document): void
    {
        try {
            $pdf = (new Parser)->parseFile(Storage::disk('local')->path($document->getRawOriginal('path')));
            $pages = [];
            foreach ($pdf->getPages() as $index => $page) {
                $text = trim($page->getText());
                $pages[] = ['number' => $index + 1, 'text' => $text];
            }
            $empty = collect($pages)->contains(fn ($p) => mb_strlen($p['text']) < 25);
            $document->update(['pages' => $pages, 'status' => $empty ? 'needs_ocr' : 'ready']);
            if (! $document instanceof CompanyDocument && $document->opportunity_id) {
                foreach ($pages as $page) {
                    foreach (preg_split('/\n+/u', $page['text']) as $line) {
                        if (! $this->isTechnicalRequirement($line)) {
                            continue;
                        }
                        Requirement::create(['opportunity_id' => $document->opportunity_id, 'source_document_id' => $document->id,
                            'source_page' => $page['number'], 'description' => mb_substr(trim($line), 0, 4000), 'status' => 'pending']);
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
            $document->update(['status' => 'failed', 'error' => 'Não foi possível extrair o PDF. Verifique se está protegido ou corrompido.']);
        }
    }

    public function isTechnicalRequirement(string $line): bool
    {
        $text = Str::lower(Str::ascii(trim($line)));
        if (mb_strlen($text) < 15) {
            return false;
        }
        // Require explicit technical evidence rather than generic "habilitação" or "certidão".
        $technical = preg_match('/\b(?:capacidade|capacitacao|qualificacao|habilitacao|aptidao)\s+tecnic|\bacervo\s+tecnic|\bresponsab\w*\s+tecnic|\b(?:art|rrt|cat|crea|cau)\b|\batestad\w*.{0,100}\b(?:tecnic\w*|execucao|experiencia|servicos?|projetos?|obras?)\b|\bexperiencia\s+(?:profissional|tecnica)|\bcertifica\w*.{0,60}\b(?:tecnic\w*|profissional)\b/u', $text);
        if (! $technical) {
            return false;
        }
        // A council name in a generic fiscal/legal clause does not make it technical.
        if (preg_match('/\b(?:fiscal|tributari\w*|trabalhista|fgts|inss|falencia|recuperacao judicial|economico|financeir\w*|cnpj|contrato social)\b/u', $text)
            && ! preg_match('/capacidade\s+tecnic|qualificacao\s+tecnic|acervo\s+tecnic|responsab\w*\s+tecnic|atestad\w*.{0,80}tecnic/u', $text)) {
            return false;
        }

        return true;
    }

    public function candidates(Requirement $requirement): array
    {
        $words = collect(preg_split('/\W+/u', Str::lower(Str::ascii($requirement->description))))->filter(fn ($w) => strlen($w) > 4)->unique()->values();
        if ($words->isEmpty()) {
            return [];
        }
        $candidates = [];
        $deadline = Opportunity::find($requirement->opportunity_id)?->deadline ?? now();
        foreach (CompanyDocument::whereIn('status', ['ready', 'needs_ocr'])->get() as $doc) {
            if ($doc->expires_at && $doc->expires_at->copy()->endOfDay()->lt($deadline)) {
                continue;
            }
            foreach ($doc->pages ?? [] as $page) {
                $text = Str::lower(Str::ascii($page['text']));
                $hits = $words->filter(fn ($w) => str_contains($text, $w))->count();
                if ($hits >= 2) {
                    $candidates[] = ['document_id' => $doc->id, 'name' => $doc->name, 'page' => $page['number'], 'hits' => $hits, 'excerpt' => mb_substr($page['text'], 0, 600)];
                }
            }
        }
        usort($candidates, fn ($a, $b) => $b['hits'] <=> $a['hits']);

        return array_slice($candidates, 0, 5);
    }
}
