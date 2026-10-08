<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchProfileRequest;
use App\Models\SearchProfile;
use App\Services\SearchProfileService;

class SearchProfileController extends Controller
{
    public function __construct(
        private SearchProfileService $searchProfileService,
    ) {}

    public function profiles()
    {
        return SearchProfile::latest()->get();
    }

    public function storeProfile(SearchProfileRequest $r, ?SearchProfile $profile = null)
    {
        $data = $r->validated();
        $existing = $profile && $profile->exists;
        $saved = $this->searchProfileService->save($data, $profile);

        return response()->json($saved, $existing ? 200 : 201);
    }

    public function deleteProfile(SearchProfile $profile)
    {
        $profile->delete();

        return response()->noContent();
    }
}
