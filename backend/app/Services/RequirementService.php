<?php

namespace App\Services;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\Requirement;

class RequirementService
{
    public function create(Opportunity $opportunity, array $data): Requirement
    {
        $doc = Document::findOrFail($data['source_document_id']);
        if ($doc->opportunity_id !== $opportunity->id || ! collect($doc->pages)->contains('number', $data['source_page'])) {
            abort(422, 'Documento/página inválidos para esta oportunidade.');
        }

        return $opportunity->requirements()->create($data);
    }

    public function review(Requirement $requirement, array $data, int $userId): Requirement
    {
        if ($data['status'] === 'met') {
            $doc = CompanyDocument::find($data['evidence_document_id']);
            $deadline = Opportunity::findOrFail($requirement->opportunity_id)->deadline?->copy()->max(now()) ?? now();
            if (! $doc || ! in_array($doc->status, ['ready', 'needs_ocr']) ||
                ($doc->expires_at && $doc->expires_at->copy()->endOfDay()->lt($deadline)) || ! collect($doc->pages)->contains('number', $data['evidence_page'])) {
                abort(422, 'Evidência inválida, vencida até o prazo do edital ou página inexistente.');
            }
        } else {
            $data['evidence_document_id'] = null;
            $data['evidence_page'] = null;
        }
        $requirement->update([...$data, 'reviewer_id' => $userId, 'reviewed_at' => now()]);

        return $requirement;
    }
}
