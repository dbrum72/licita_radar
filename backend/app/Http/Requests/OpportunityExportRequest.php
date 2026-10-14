<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpportunityExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['ids' => 'required|array|min:1|max:50', 'ids.*' => 'required|integer|distinct|exists:opportunities,id'];
    }
}
