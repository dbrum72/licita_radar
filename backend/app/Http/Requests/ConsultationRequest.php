<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_ids' => ['present', 'array'],
            'profile_ids.*' => ['integer', 'distinct', Rule::exists('search_profiles', 'id')->where('active', true)],
        ];
    }
}
