<?php

namespace App\Http\Requests;

use App\Support\RadarOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpportunityFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
            'agency' => 'nullable|string|max:255',
            'state' => ['nullable', 'string', Rule::in(RadarOptions::STATES)],
            'modality' => 'nullable|integer|between:1,13',
            'scope' => 'nullable|in:relevant,review,excluded',
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => $this->filled('min_value') ? 'nullable|numeric|min:0|gte:min_value' : 'nullable|numeric|min:0',
            'deadline' => 'nullable|date_format:Y-m-d',
            'bookmarked' => 'sometimes|boolean',
            'discarded' => 'sometimes|boolean',
            'page' => 'sometimes|integer|min:1',
        ];
    }
}
