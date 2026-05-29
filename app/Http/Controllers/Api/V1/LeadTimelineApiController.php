<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Services\LeadTimelineService;
use Illuminate\Http\JsonResponse;

class LeadTimelineApiController extends Controller
{
    public function __invoke(HirevoLead $lead, LeadTimelineService $timeline): JsonResponse
    {
        $this->authorize('view', $lead);

        $items = $timeline->forLead($lead)->map(fn ($item) => [
            'at' => $item['at']->toIso8601String(),
            'type' => $item['type'],
            'title' => $item['title'],
            'meta' => $item['meta'],
            'actor' => $item['actor'],
        ]);

        return response()->json(['data' => $items]);
    }
}
