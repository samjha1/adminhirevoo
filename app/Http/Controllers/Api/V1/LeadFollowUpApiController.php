<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Services\FollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadFollowUpApiController extends Controller
{
    public function index(HirevoLead $lead): JsonResponse
    {
        $this->authorize('manageFollowups', $lead);

        $items = CrmFollowUp::query()
            ->where('lead_id', $lead->id)
            ->with('admin')
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request, HirevoLead $lead, FollowUpService $service): JsonResponse
    {
        $this->authorize('manageFollowups', $lead);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admin_id' => ['nullable', 'exists:admins,id'],
        ]);

        $followUp = $service->schedule($lead, $request->user(), $validated);

        return response()->json($followUp->load('admin'), 201);
    }

    public function update(Request $request, HirevoLead $lead, CrmFollowUp $followUp, FollowUpService $service): JsonResponse
    {
        $this->authorize('manageFollowups', $lead);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(FollowUpStatus::class)],
            'scheduled_at' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (($validated['status'] ?? null) === FollowUpStatus::Completed->value) {
            $service->complete($followUp, $request->user());
        } else {
            $followUp->fill($validated);
            $followUp->save();
        }

        return response()->json($followUp->fresh('admin'));
    }
}
