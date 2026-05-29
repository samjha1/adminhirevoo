<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Enums\CallOutcome;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Services\CallLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadCallApiController extends Controller
{
    public function index(HirevoLead $lead): JsonResponse
    {
        $this->authorize('logCall', $lead);

        $calls = CrmCallLog::query()
            ->where('lead_id', $lead->id)
            ->with('admin')
            ->orderByDesc('called_at')
            ->get();

        return response()->json($calls);
    }

    public function store(Request $request, HirevoLead $lead, CallLogService $service): JsonResponse
    {
        $this->authorize('logCall', $lead);

        $validated = $request->validate([
            'outcome' => ['required', Rule::enum(CallOutcome::class)],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'called_at' => ['nullable', 'date'],
        ]);

        $call = $service->log($lead, $request->user(), $validated);

        return response()->json($call->load('admin'), 201);
    }
}
