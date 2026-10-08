<?php

namespace App\Services;

use App\Models\Opportunity;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OpportunityPdfService
{
    public function selection(array $ids): Collection
    {
        $company = app(CompanyScopeService::class);
        $items = Opportunity::whereIn('id', $ids)->whereDoesntHave('dismissal')->get()->keyBy('id');
        $ordered = collect($ids)->map(function ($id) use ($items, $company) {
            $item = $items->get($id);
            if (! $item) {
                throw ValidationException::withMessages(['ids' => 'Há oportunidades descartadas na seleção. Atualize a lista.']);
            }
            if (! $item->deadline || ! $item->deadline->gt(now())) {
                throw ValidationException::withMessages(['ids' => 'Há oportunidades com prazo encerrado ou desconhecido na seleção. Atualize a lista.']);
            }
            $match = $company->assess($item->object, $item->deadline);
            if ($match['status'] !== 'compatible') {
                throw ValidationException::withMessages(['ids' => 'A compatibilidade documental de uma oportunidade selecionada mudou. Atualize a lista.']);
            }
            $item->setAttribute('company_match', $match);

            return $item;
        });

        return $ordered;
    }

    public function render(Collection $opportunities): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', storage_path('app/private'));
        $options->set('fontCache', storage_path('app/private'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('pdf.opportunities', [
            'opportunities' => $opportunities, 'generatedAt' => now()->timezone('America/Sao_Paulo'),
        ])->render(), 'UTF-8');
        $pdf->render();
        $pdf->getCanvas()->page_text(440, 810, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 8, [0.35, 0.35, 0.35]);

        return $pdf->output();
    }
}
