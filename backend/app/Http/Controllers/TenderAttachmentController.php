<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Services\Documents\TenderAttachmentService;
use Illuminate\Http\JsonResponse;

class TenderAttachmentController extends Controller
{
    public function store(Opportunity $opportunity, TenderAttachmentService $service): JsonResponse
    {
        set_time_limit(3600);
        $result = $service->fetchRequested($opportunity);

        return response()->json($result, $result['attachments_status'] === 'failed' ? 502 : 200);
    }
}
