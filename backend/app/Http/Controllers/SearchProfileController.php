<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchProfileRequest;
use App\Models\SearchProfile;
use App\Services\SearchProfiles\SearchProfileService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SearchProfileController extends Controller
{
    public function __construct(
        private readonly SearchProfileService $searchProfileService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(SearchProfile::latest()->get());
    }

    public function store(SearchProfileRequest $request): JsonResponse
    {
        return response()->json($this->searchProfileService->save($request->validated()), 201);
    }

    public function update(SearchProfileRequest $request, SearchProfile $profile): JsonResponse
    {
        return response()->json($this->searchProfileService->save($request->validated(), $profile));
    }

    public function destroy(SearchProfile $profile): Response
    {
        $profile->delete();

        return response()->noContent();
    }
}
