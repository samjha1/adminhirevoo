<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Enums\LeadSalesStatus;
use App\Http\Controllers\Concerns\HandlesAssignmentFailures;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLeadStage;
use App\Models\Hirevo\HirevoCareerConsultationRequest;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Services\LeadTimelineService;
use App\Services\CandidateInsightService;
use App\Services\LeadAssignmentService;
use App\Services\LeadPipelineService;
use App\Services\LeadTabBadgeService;
use App\Services\LeadVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    use HandlesAssignmentFailures;

    public function __construct(
        private readonly CandidateInsightService $candidateInsightService,
        private readonly LeadPipelineService $leadPipeline,
        private readonly LeadVisibilityService $visibility,
        private readonly LeadAssignmentService $assignmentService,
        private readonly LeadTabBadgeService $tabBadges,
    ) {}

    public function index(Request $request): View
    {
        return $this->renderIndexPage($request, 'leads');
    }

    public function consultations(Request $request): View
    {
        abort_unless($request->user('admin')->canPermission('consultations.view'), 403);

        return $this->renderIndexPage($request, 'consultations');
    }

    private function renderIndexPage(Request $request, string $activeTab): View
    {
        $admin = auth('admin')->user();

        $leadQuery = HirevoLead::query()
            ->with(['candidate', 'adminStage', 'assignedTo', 'salesManager'])
            ->orderByDesc('created_at');

        $this->visibility->restrictVisibleLeads($leadQuery, $admin);

        if ($request->filled('status')) {
            $leadQuery->where('status', $request->string('status')->toString());
        }

        if ($request->filled('assignment_status')) {
            $leadQuery->where('assignment_status', $request->string('assignment_status')->toString());
        }

        if ($request->filled('assignee_id')) {
            $raw = $request->string('assignee_id')->toString();
            if (in_array($raw, ['0', 'unassigned'], true)) {
                $leadQuery->whereNull('assigned_to');
            } elseif (ctype_digit($raw)) {
                $leadQuery->where('assigned_to', (int) $raw);
            }
        }

        if ($request->filled('mgmt_stage')) {
            $stage = $request->string('mgmt_stage')->toString();
            if ($stage === 'new') {
                $leadQuery->where(function ($q) {
                    $q->whereDoesntHave('adminStage')
                        ->orWhereHas('adminStage', fn ($q2) => $q2->where('stage', 'new'));
                });
            } else {
                $leadQuery->whereHas('adminStage', fn ($q) => $q->where('stage', $stage));
            }
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $leadQuery->where(function ($outer) use ($q) {
                $outer->whereHas('candidate', function ($cq) use ($q) {
                    $cq->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                })
                    ->orWhere('referral_source', 'like', "%{$q}%")
                    ->orWhere('lead_summary', 'like', "%{$q}%");
            });
        }

        $leads = $leadQuery->paginate(12, ['*'], 'leads_page')->withQueryString();

        $consultations = null;
        if ($admin->canPermission('consultations.view')) {
            $consultationQuery = HirevoCareerConsultationRequest::query()
                ->with('user')
                ->orderByDesc('created_at');

            if ($request->filled('consultation_status')) {
                $consultationQuery->where('status', $request->string('consultation_status')->toString());
            }
            if ($request->filled('q')) {
                $q = $request->string('q')->toString();
                $consultationQuery->whereHas('user', function ($uq) use ($q) {
                    $uq->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            }

            $consultations = $consultationQuery->paginate(12, ['*'], 'consultations_page')->withQueryString();
        }

        $assignableManagers = $this->talentTeamManagers();
        $assignableEmployees = $this->talentTeamEmployees($admin);

        $leadAttentionCount = $this->tabBadges->leadAttentionCount($admin);
        $pendingConsultationCount = $admin->canPermission('consultations.view')
            ? $this->tabBadges->pendingConsultationCount()
            : 0;

        $showAssigneeFilter = $admin->hasAnyRole([
            AdminRole::Admin,
            AdminRole::Marketing,
            AdminRole::SalesManager,
        ]);

        return view('admin.leads.index', [
            'leads' => $leads,
            'consultations' => $consultations,
            'crmStageCounts' => $this->leadPipeline->managementStageCounts(),
            'managementStages' => $this->leadPipeline->managementStages(),
            'crmStageLabels' => $this->leadPipeline->managementStageLabels(),
            'assignableManagers' => $assignableManagers,
            'assignableEmployees' => $assignableEmployees,
            'leadAttentionCount' => $leadAttentionCount,
            'pendingConsultationCount' => $pendingConsultationCount,
            'activeTab' => $activeTab,
            'showAssigneeFilter' => $showAssigneeFilter,
            'assigneeFilterEmployees' => $this->talentTeamEmployees($admin),
            'pipeline' => SalesTeam::Candidate,
            'canBulkManagers' => $admin->canPermission('leads.assign_manager'),
            'canBulkEmployees' => $admin->canPermission('leads.assign_employee')
                && $admin->role === AdminRole::SalesManager,
            'bulkManagerActorLabel' => $this->bulkManagerActorLabel($admin),
        ]);
    }

    private function bulkManagerActorLabel(Admin $admin): string
    {
        return match ($admin->role) {
            AdminRole::Marketing => 'Marketing',
            AdminRole::SuperAdmin => 'Super Admin',
            AdminRole::Admin => 'Admin',
            default => $admin->role->label(),
        };
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Admin> */
    private function talentTeamManagers(): \Illuminate\Database\Eloquent\Collection
    {
        return Admin::query()
            ->where('role', AdminRole::SalesManager)
            ->where(function ($q) {
                $q->where('sales_team', SalesTeam::Candidate->value)
                    ->orWhereNull('sales_team');
            })
            ->orderBy('name')
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Admin> */
    private function talentTeamEmployees(Admin $admin): \Illuminate\Database\Eloquent\Collection
    {
        return Admin::query()
            ->where('role', AdminRole::SalesEmployee)
            ->where(function ($q) {
                $q->where('sales_team', SalesTeam::Candidate->value)
                    ->orWhereNull('sales_team');
            })
            ->when($admin->role === AdminRole::SalesManager, fn ($q) => $q->where('manager_id', $admin->id))
            ->orderBy('name')
            ->get();
    }

    public function bulkAssignManagers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:200'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
            'manager_id' => ['required', 'exists:admins,id'],
        ]);

        $actor = auth('admin')->user();
        abort_unless($actor->canPermission('leads.assign_manager'), 403);

        $manager = Admin::query()->findOrFail((int) $validated['manager_id']);
        $result = $this->assignmentService->bulkAssignToSalesManagers($validated['lead_ids'], $manager, $actor);

        return $this->bulkAssignmentRedirect($result, 'manager');
    }

    public function bulkAssignEmployees(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:200'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
            'employee_id' => ['required', 'exists:admins,id'],
        ]);

        $manager = auth('admin')->user();
        abort_unless($manager->canPermission('leads.assign_employee'), 403);

        $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
        $result = $this->assignmentService->bulkAssignToEmployees($validated['lead_ids'], $employee, $manager);

        return $this->bulkAssignmentRedirect($result, 'employee');
    }

    /**
     * @param  array{success: int, skipped: int, errors: array<int, string>}  $result
     */
    private function bulkAssignmentRedirect(array $result, string $kind): RedirectResponse
    {
        $redirect = back();

        if ($result['success'] === 0 && $result['skipped'] === 0 && $result['errors'] !== []) {
            return $redirect
                ->with('error', 'No leads were updated. Check the list below or pick different rows.')
                ->with('bulk_errors', $result['errors']);
        }

        if ($result['success'] > 0 || $result['skipped'] > 0) {
            $msg = $kind === 'manager'
                ? "Assigned {$result['success']} lead(s) to the sales manager."
                : "Assigned {$result['success']} lead(s) to your team member.";
            if ($result['skipped'] > 0) {
                $msg .= " {$result['skipped']} skipped (already assigned as selected).";
            }
            $redirect = $redirect->with('success', $msg);
        }

        if ($result['errors'] !== []) {
            $redirect = $redirect->with('bulk_errors', $result['errors']);
        }

        return $redirect;
    }

    public function showLead(HirevoLead $lead, LeadTimelineService $timelineService): View
    {
        $this->authorize('view', $lead);

        $admin = auth('admin')->user();

        $lead->load([
            'candidate',
            'candidate.candidateProfile',
            'candidate.resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at'),
            'adminStage',
            'assignedTo',
            'assignedBy',
            'salesManager',
            'assignmentHistory.fromAdmin',
            'assignmentHistory.toAdmin',
            'assignmentHistory.byAdmin',
        ]);

        $relatedConsultations = collect();
        if ($admin->canPermission('consultations.view')) {
            $relatedConsultations = HirevoCareerConsultationRequest::query()
                ->with('user')
                ->where('user_id', $lead->candidate_id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        $primaryResume = $lead->candidate?->resumes?->first();
        $insight = $this->candidateInsightService->buildLeadInsight($lead);

        $assignableManagers = $this->talentTeamManagers();
        $assignableEmployees = $this->talentTeamEmployees($admin);

        return view('admin.leads.show-lead', [
            'lead' => $lead,
            'relatedConsultations' => $relatedConsultations,
            'primaryResume' => $primaryResume,
            'insight' => $insight,
            'stages' => $this->leadPipeline->managementStages(),
            'crmStageLabels' => $this->leadPipeline->managementStageLabels(),
            'salesStatuses' => LeadSalesStatus::cases(),
            'assignableManagers' => $assignableManagers,
            'assignableEmployees' => $assignableEmployees,
            'timeline' => $timelineService->forLead($lead),
            'recentCalls' => CrmCallLog::query()->where('lead_id', $lead->id)->with('admin')->orderByDesc('called_at')->limit(5)->get(),
            'upcomingFollowUps' => CrmFollowUp::query()->where('lead_id', $lead->id)->with('admin')->orderBy('scheduled_at')->limit(5)->get(),
        ]);
    }

    public function updateStage(Request $request, HirevoLead $lead): RedirectResponse
    {
        $this->authorize('updateCrmStage', $lead);

        $validated = $request->validate([
            'stage' => ['required', Rule::in($this->leadPipeline->managementStages())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $stage = AdminLeadStage::query()->firstOrNew(['lead_id' => $lead->id]);
        $stage->stage = $validated['stage'];
        $stage->notes = $validated['notes'] ?? null;
        if (in_array($validated['stage'], ['called', 'follow_up'], true)) {
            $stage->last_contacted_at = now();
        }
        $stage->save();

        return back()->with('success', 'Lead management stage updated.');
    }

    public function updateSalesStatus(Request $request, HirevoLead $lead): RedirectResponse
    {
        $this->authorize('updateSalesStatus', $lead);

        $validated = $request->validate([
            'sales_status' => ['required', 'in:pending,contacted,converted'],
        ]);

        $lead->sales_status = LeadSalesStatus::from($validated['sales_status']);
        $lead->save();

        return back()->with('success', 'Sales status updated.');
    }

    public function assignManager(Request $request, HirevoLead $lead): RedirectResponse
    {
        $this->authorize('assignAsMarketing', $lead);

        $validated = $request->validate([
            'manager_id' => ['required', 'exists:admins,id'],
        ]);

        $manager = Admin::query()->findOrFail((int) $validated['manager_id']);
        $actor = auth('admin')->user();

        return $this->assignmentRedirect(
            fn () => $this->assignmentService->assignToSalesManager($lead, $manager, $actor),
            'Lead assigned to sales manager.',
        );
    }

    public function reassignManager(Request $request, HirevoLead $lead): RedirectResponse
    {
        $this->authorize('assignAsMarketing', $lead);

        $validated = $request->validate([
            'manager_id' => ['required', 'exists:admins,id'],
        ]);

        $manager = Admin::query()->findOrFail((int) $validated['manager_id']);
        $actor = auth('admin')->user();

        return $this->assignmentRedirect(
            fn () => $this->assignmentService->reassignSalesManager($lead, $manager, $actor),
            'Lead reassigned to another sales manager.',
        );
    }

    public function releaseToPool(HirevoLead $lead): RedirectResponse
    {
        $this->authorize('releaseToPool', $lead);
        $actor = auth('admin')->user();

        return $this->assignmentRedirect(
            fn () => $this->assignmentService->releaseToPool($lead, $actor),
            'Lead returned to the unassigned pool.',
        );
    }

    public function assignEmployee(Request $request, HirevoLead $lead): RedirectResponse
    {
        $this->authorize('assignAsManager', $lead);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:admins,id'],
        ]);

        $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
        $manager = auth('admin')->user();

        return $this->assignmentRedirect(
            fn () => $this->assignmentService->assignToEmployee($lead, $employee, $manager),
            'Lead assigned to sales employee.',
        );
    }

    public function reassignEmployee(Request $request, HirevoLead $lead): RedirectResponse
    {
        $this->authorize('assignAsManager', $lead);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:admins,id'],
        ]);

        $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
        $manager = auth('admin')->user();

        return $this->assignmentRedirect(
            fn () => $this->assignmentService->reassignEmployee($lead, $employee, $manager),
            'Lead reassigned to another employee.',
        );
    }

    public function takeBack(HirevoLead $lead): RedirectResponse
    {
        $this->authorize('takeBack', $lead);
        $actor = auth('admin')->user();

        return $this->assignmentRedirect(
            fn () => $this->assignmentService->takeBackFromEmployee($lead, $actor),
            'Lead taken back from employee.',
        );
    }

    public function showConsultation(HirevoCareerConsultationRequest $consultation): View
    {
        abort_unless(auth('admin')->user()->canPermission('consultations.view'), 403);

        $consultation->load('user');

        $relatedLeads = HirevoLead::query()
            ->with('candidate')
            ->where('candidate_id', $consultation->user_id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.leads.show-consultation', [
            'consultation' => $consultation,
            'relatedLeads' => $relatedLeads,
        ]);
    }

}
