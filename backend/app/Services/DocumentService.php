<?php
namespace App\Services;
use App\Models\{Document, CompanyDocument, Requirement};
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;

class DocumentService
{
    public function extract(Document $document): void
    {
        try {
            $pdf = (new Parser())->parseFile(\Illuminate\Support\Facades\Storage::disk('local')->path($document->getRawOriginal('path')));
            $pages = [];
            foreach ($pdf->getPages() as $index=>$page) {
                $text = trim($page->getText());
                $pages[] = ['number'=>$index+1,'text'=>$text];
            }
            $empty = collect($pages)->contains(fn ($p) => mb_strlen($p['text']) < 25);
            $document->update(['pages'=>$pages,'status'=>$empty ? 'needs_ocr' : 'ready']);
            if (!$document instanceof CompanyDocument && $document->opportunity_id) {
                foreach ($pages as $page) {
                    foreach (preg_split('/\n+/u', $page['text']) as $line) {
                        if (mb_strlen($line) < 15 || !preg_match('/atestado|certid[aã]o|certifica|habilita|qualifica|acervo|CREA|CAU|capacidade t[eé]cnica/iu', $line)) continue;
                        Requirement::create(['opportunity_id'=>$document->opportunity_id,'source_document_id'=>$document->id,
                            'source_page'=>$page['number'],'description'=>mb_substr(trim($line),0,4000),'status'=>'pending']);
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e); $document->update(['status'=>'failed','error'=>'Não foi possível extrair o PDF. Verifique se está protegido ou corrompido.']);
        }
    }
    public function candidates(Requirement $requirement): array
    {
        $words = collect(preg_split('/\W+/u', Str::lower(Str::ascii($requirement->description))))->filter(fn ($w) => strlen($w)>4)->unique()->values();
        if ($words->isEmpty()) return [];
        $candidates = [];
        foreach (CompanyDocument::whereIn('status',['ready','needs_ocr'])->get() as $doc) {
            if ($doc->expires_at && $doc->expires_at->lt(today())) continue;
            foreach ($doc->pages ?? [] as $page) {
                $text = Str::lower(Str::ascii($page['text']));
                $hits = $words->filter(fn ($w) => str_contains($text,$w))->count();
                if ($hits >= 2) $candidates[] = ['document_id'=>$doc->id,'name'=>$doc->name,'page'=>$page['number'],'hits'=>$hits,'excerpt'=>mb_substr($page['text'],0,600)];
            }
        }
        usort($candidates, fn ($a,$b) => $b['hits'] <=> $a['hits']);
        return array_slice($candidates,0,5);
    }
}
