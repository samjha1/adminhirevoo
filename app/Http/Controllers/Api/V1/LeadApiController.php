<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadSalesStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Services\LeadAssignmentService;
use App\Services\LeadVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiController extends Controller
{
    public function __construct(
        private readonly LeadVisibilityService $visibility,
        private readonly LeadAssignmentService $assignmentService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\Admin $admin */
        $admin = $request->user();
        $q = HirevoLead::query()
            ->with(['candidate', 'assignedTo', 'salesManager'])
            ->orderByDesc('created_at');

        $this->visibility->restrictVisibleLeads($q, $admin);

        if ($request->filled('assignment_status')) {
            $q->where('assignment_status', $request->string('assignment_status')->toString());
        }
        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $s = $request->string('q')->toString();
            $q->whereHas('candidate', function ($cq) use ($s) {
                $cq->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        return response()->json($q->paginate((int) $request->get('per_page', 15)));
    }

    public function assign(Request $request, HirevoLead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $validated = $request->validate([
            'manager_id' => ['sometimes', 'required', 'exists:admins,id'],
            'employee_id' => ['sometimes', 'required', 'exists:admins,id'],
        ]);

        if (isset($validated['manager_id'])) {
            $this->authorize('assignAsMarketing', $lead);
            $manager = Admin::query()->findOrFail((int) $validated['manager_id']);
            $lead = $this->assignmentService->assignToSalesManager($lead, $manager, $request->user());
        } elseif (isset($validated['employee_id'])) {
            $this->authorize('assignAsManager', $lead);
            $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
            $lead = $this->assignmentService->assignToEmployee($lead, $employee, $request->user());
        } else {
            return response()->json(['message' => 'Provide manager_id or employee_id.'], 422);
        }

        return response()->json($lead->load(['assignedTo', 'salesManager']));
    }

    public function unassign(Request $request, HirevoLead $lead): JsonResponse
    {
        $this->authorize('releaseToPool', $lead);
        $lead = $this->assignmentService->releaseToPool($lead, $request->user());

        return response()->json($lead);
    }

    public function reassign(Request $request, HirevoLead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $validated = $request->validate([
            'manager_id' => ['sometimes', 'required', 'exists:admins,id'],
            'employee_id' => ['sometimes', 'required', 'exists:admins,id'],
        ]);

        if (isset($validated['manager_id'])) {
            $this->authorize('assignAsMarketing', $lead);
            $manager = Admin::query()->findOrFail((int) $validated['manager_id']);
            $lead = $this->assignmentService->reassignSalesManager($lead, $manager, $request->user());
        } elseif (isset($validated['employee_id'])) {
            $this->authorize('assignAsManager', $lead);
            $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
            $lead = $this->assignmentService->reassignEmployee($lead, $employee, $request->user());
        } else {
            return response()->json(['message' => 'Provide manager_id or employee_id.'], 422);
        }

        return response()->json($lead->load(['assignedTo', 'salesManager']));
    }

    public function updateStatus(Request $request, HirevoLead $lead): JsonResponse
    {
        $this->authorize('updateSalesStatus', $lead);

        $validated = $request->validate([
            'sales_status' => ['required', 'in:pending,contacted,converted'],
        ]);

        $lead->sales_status = LeadSalesStatus::from($validated['sales_status']);
        $lead->save();

        return response()->json($lead);
    }
}
