<?php

namespace App\Services;

use App\Models\CompanyDocument;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentStorageService
{
    public function upload(UploadedFile $file, array $data): Document
    {
        if (($data['kind'] === 'tender') !== ! empty($data['opportunity_id'])) {
            throw ValidationException::withMessages(['kind' => 'Edital deve estar vinculado à oportunidade; documentos da empresa não devem.']);
        }
        $lock = ! empty($data['opportunity_id']) ? Cache::lock('tender-attachments:'.$data['opportunity_id'], 3600) : null;
        if ($lock && ! $lock->get()) {
            abort(409, 'Aguarde a busca ou exclusão de anexos terminar antes de enviar um PDF.');
        }
        try {
            $sha = hash_file('sha256', $file->getRealPath());
            $path = $file->store($data['kind'] === 'tender' ? 'documents' : 'company-documents', 'local');
            if (! $path) {
                throw ValidationException::withMessages(['file' => 'Não foi possível armazenar o PDF.']);
            }
            try {
                $attributes = ['name' => $file->getClientOriginalName(), 'kind' => $data['kind'], 'expires_at' => $data['expires_at'] ?? null, 'path' => $path, 'sha256' => $sha];
                $document = $data['kind'] === 'tender' ? Document::create([...$attributes, 'opportunity_id' => $data['opportunity_id']]) : CompanyDocument::create($attributes);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($path);
                throw $exception;
            }
            app(DocumentService::class)->extract($document);

            return $document->fresh();
        } finally {
            $lock?->release();
        }
    }

    public function deleteTender(Document $document): void
    {
        abort_unless($document->opportunity_id && $document->kind === 'tender', 404);
        $lock = Cache::lock('tender-attachments:'.$document->opportunity_id, 3600);
        if (! $lock->get()) {
            abort(409, 'Aguarde a busca de anexos terminar antes de excluir.');
        }
        try {
            DB::transaction(function () use ($document) {
                $path = $document->getRawOriginal('path');
                if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
                    throw ValidationException::withMessages(['document' => 'Não foi possível excluir o arquivo. Tente novamente.']);
                }
                $document->delete(); // Source requirements are removed by the foreign key cascade.
            });

        } finally {
            $lock->release();
        }
    }
}
