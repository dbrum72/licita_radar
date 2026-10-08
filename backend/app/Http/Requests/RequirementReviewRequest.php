<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequirementReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => 'required|in:pending,met,unmet,not_applicable',
            'evidence_document_id' => 'nullable|required_if:status,met|integer|exists:company_documents,id',
            'evidence_page' => 'nullable|required_if:status,met|integer|min:1', 'notes' => 'required|string|max:4000'];
    }
}
