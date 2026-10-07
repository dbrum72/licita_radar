<?php
namespace App\Services;

use Dompdf\{Dompdf, Options};
use Illuminate\Support\Collection;

class OpportunityPdfService
{
    public function render(Collection $opportunities): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled',false);
        $options->set('isPhpEnabled',false);
        $options->set('defaultFont','DejaVu Sans');
        $options->set('tempDir',storage_path('app/private'));
        $options->set('fontCache',storage_path('app/private'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('pdf.opportunities',[
            'opportunities'=>$opportunities,'generatedAt'=>now()->timezone('America/Sao_Paulo'),
        ])->render(),'UTF-8');
        $pdf->render();
        $pdf->getCanvas()->page_text(440,810,'Página {PAGE_NUM} de {PAGE_COUNT}',null,8,[0.35,0.35,0.35]);
        return $pdf->output();
    }
}
