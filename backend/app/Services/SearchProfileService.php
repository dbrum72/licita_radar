<?php

namespace App\Services;

use App\Models\SearchProfile;
use Illuminate\Validation\ValidationException;

class SearchProfileService
{
    public function save(array $data, ?SearchProfile $profile = null): SearchProfile
    {
        foreach (['keywords', 'excluded_keywords'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = array_values(array_unique(array_filter(array_map('trim', $data[$field]), fn ($term) => $term !== '')));
            } elseif (! $profile || ! $profile->exists) {
                $data[$field] = [];
            }
        }
        if (isset($data['min_value'],$data['max_value']) && $data['max_value'] < $data['min_value']) {
            throw ValidationException::withMessages(['max_value' => 'Valor máximo deve ser maior ou igual ao mínimo.']);
        }
        if ($profile && $profile->exists) {
            $profile->update($data);

            return $profile;
        }

        return SearchProfile::create($data);
    }
}
