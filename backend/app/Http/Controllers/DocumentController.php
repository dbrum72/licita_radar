<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentUploadRequest;
use App\Models\CompanyDocument;
use App\Models\Document;
use App\Services\DocumentStorageService;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentStorageService $documentStorageService,
    ) {}

    public function documents()
    {
        return CompanyDocument::latest()->get();
    }

    public function upload(DocumentUploadRequest $r)
    {
        $data = $r->validated();

        return response()->json($this->documentStorageService->upload($r->file('file'), $data), 201);
    }

    public function download(CompanyDocument $document)
    {
        return Storage::disk('local')->download($document->getRawOriginal('path'), $document->name);
    }

    public function document(CompanyDocument $document)
    {
        return $document;
    }

    public function tenderDocument(Document $document)
    {
        return $document;
    }

    public function deleteTenderDocument(Document $document)
    {
        $this->documentStorageService->deleteTender($document);

        return response()->noContent();
    }

    public function tenderDownload(Document $document)
    {
        return Storage::disk('local')->download($document->getRawOriginal('path'), $document->name);
    }
}
