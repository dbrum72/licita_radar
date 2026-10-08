<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpportunityExportRequest;
use App\Http\Requests\OpportunityFiltersRequest;
use App\Models\Opportunity;
use App\Services\OpportunityAnalysisService;
use App\Services\OpportunityLifecycleService;
use App\Services\OpportunityPdfService;
use App\Services\OpportunityQueryService;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(
        private OpportunityQueryService $opportunityQueryService,
        private OpportunityAnalysisService $opportunityAnalysisService,
        private OpportunityLifecycleService $opportunityLifecycleService,
    ) {}

    public function opportunities(OpportunityFiltersRequest $r)
    {
        return response()->json($this->opportunityQueryService->paginate($r->validated()));
    }

    public function opportunity(Opportunity $opportunity)
    {
        return $this->opportunityAnalysisService->detail($opportunity);
    }

    public function bookmark(Opportunity $opportunity, Request $r)
    {
        $data = $r->validate(['bookmarked' => 'required|boolean']);

        return $this->opportunityLifecycleService->bookmark($opportunity, $data['bookmarked']);
    }

    public function discard(Opportunity $opportunity, Request $r)
    {
        $data = $r->validate(['discarded' => 'required|boolean']);

        return $this->opportunityLifecycleService->discard($opportunity, $data['discarded'], $r->user()->id);
    }

    public function exportOpportunities(OpportunityExportRequest $r, OpportunityPdfService $pdf)
    {
        $data = $r->validated();
        $ordered = $pdf->selection($data['ids']);

        return response($pdf->render($ordered), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="licitaradar-oportunidades.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
