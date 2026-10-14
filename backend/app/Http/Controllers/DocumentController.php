<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentUploadRequest;
use App\Models\CompanyDocument;
use App\Models\Document;
use App\Services\Documents\DocumentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentStorageService $documentStorageService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(CompanyDocument::latest()->get());
    }

    public function store(DocumentUploadRequest $r): JsonResponse
    {
        $data = $r->validated();

        return response()->json($this->documentStorageService->upload($r->file('file'), $data), 201);
    }

    public function download(CompanyDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->getRawOriginal('path')), 404, 'Arquivo PDF não encontrado no storage.');

        return Storage::disk('local')->download($document->getRawOriginal('path'), $document->name);
    }

    public function deleteTenderDocument(Document $document): Response
    {
        $this->documentStorageService->deleteTender($document);

        return response()->noContent();
    }

    public function tenderDownload(Document $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->getRawOriginal('path')), 404, 'Arquivo PDF não encontrado no storage.');

        return Storage::disk('local')->download($document->getRawOriginal('path'), $document->name);
    }
}
