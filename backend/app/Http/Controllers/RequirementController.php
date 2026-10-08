<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequirementReviewRequest;
use App\Http\Requests\RequirementStoreRequest;
use App\Models\Opportunity;
use App\Models\Requirement;
use App\Services\RequirementService;

class RequirementController extends Controller
{
    public function __construct(
        private RequirementService $requirementService,
    ) {}

    public function storeRequirement(Opportunity $opportunity, RequirementStoreRequest $r)
    {
        $data = $r->validated();

        return response()->json($this->requirementService->create($opportunity, $data), 201);
    }

    public function review(Requirement $requirement, RequirementReviewRequest $r)
    {
        $data = $r->validated();

        return $this->requirementService->review($requirement, $data, $r->user()->id);
    }
}
