<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentAnalysisRequest;
use App\Models\Opportunity;
use App\Services\DocumentAnalysis\OpportunityDocumentAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentAnalysisController extends Controller
{
    public function __construct(private readonly OpportunityDocumentAnalysis $analysis) {}

    public function show(Opportunity $opportunity): JsonResponse
    {
        return response()->json($this->analysis->state($opportunity));
    }

    public function store(DocumentAnalysisRequest $request, Opportunity $opportunity): JsonResponse
    {
        return response()->json($this->analysis->generate($opportunity, $request->boolean('force')));
    }

    public function download(Opportunity $opportunity): Response
    {
        return response($this->analysis->pdf($opportunity), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="analise-oportunidade-'.$opportunity->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    public function source(Opportunity $opportunity, string $source): StreamedResponse
    {
        $document = $this->analysis->source($opportunity, $source);

        return Storage::disk('local')->download($document['path'], $document['name'], ['Cache-Control' => 'private, no-store']);
    }
}
