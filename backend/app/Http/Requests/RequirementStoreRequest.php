<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequirementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['source_document_id' => 'required|integer|exists:documents,id', 'source_page' => 'required|integer|min:1', 'description' => 'required|string|max:4000'];
    }
}
