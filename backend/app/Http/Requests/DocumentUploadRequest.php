<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['file' => 'required|file|mimes:pdf|max:20480', 'kind' => 'required|in:certificate,attestation,rrt,cnpj,tender',
            'expires_at' => 'nullable|date', 'opportunity_id' => 'nullable|integer|exists:opportunities,id'];
    }
}
