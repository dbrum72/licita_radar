<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpportunityBookmarkRequest;
use App\Http\Requests\OpportunityDiscardRequest;
use App\Http\Requests\OpportunityExportRequest;
use App\Http\Requests\OpportunityFiltersRequest;
use App\Models\Opportunity;
use App\Services\Opportunities\OpportunityAnalysisService;
use App\Services\Opportunities\OpportunityLifecycleService;
use App\Services\Opportunities\OpportunityPdfService;
use App\Services\Opportunities\OpportunityQueryService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OpportunityController extends Controller
{
    public function __construct(
        private readonly OpportunityQueryService $opportunityQueryService,
        private readonly OpportunityAnalysisService $opportunityAnalysisService,
        private readonly OpportunityLifecycleService $opportunityLifecycleService,
    ) {}

    public function index(OpportunityFiltersRequest $r): JsonResponse
    {
        return response()->json($this->opportunityQueryService->paginate($r->validated()));
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        return response()->json($this->opportunityAnalysisService->detail($opportunity));
    }

    public function bookmark(Opportunity $opportunity, OpportunityBookmarkRequest $r): JsonResponse
    {
        $data = $r->validated();

        return response()->json($this->opportunityLifecycleService->bookmark($opportunity, $data['bookmarked']));
    }

    public function discard(Opportunity $opportunity, OpportunityDiscardRequest $r): JsonResponse
    {
        $data = $r->validated();

        return response()->json($this->opportunityLifecycleService->discard($opportunity, $data['discarded'], $r->user()->id));
    }

    public function export(OpportunityExportRequest $r, OpportunityPdfService $pdf): Response
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
