<?php

namespace App\Http\Requests;

use App\Support\RadarOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:120', 'keywords' => 'sometimes|array', 'keywords.*' => 'required|string|max:120',
            'excluded_keywords' => 'sometimes|array', 'excluded_keywords.*' => 'required|string|max:120',
            'modalities' => 'nullable|array', 'modalities.*' => 'integer|between:1,13',
            'states' => 'nullable|array', 'states.*' => ['string', Rule::in(RadarOptions::STATES)],
            'agency' => 'nullable|string|max:255', 'min_value' => 'nullable|numeric|min:0', 'max_value' => 'nullable|numeric|min:0',
            'min_days' => 'required|integer|between:0,365', 'email' => 'nullable|email|max:255', 'active' => 'required|boolean'];
    }
}
