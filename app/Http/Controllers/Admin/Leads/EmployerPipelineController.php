<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Enums\AdminRole;
use App\Enums\CompanyB2bPipelineStage;
use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Hirevo\HirevoPayment;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Services\CompanyB2bPipelineService;
use App\Modules\Leads\Services\CompanyMeetingService;
use App\Modules\Leads\Services\FollowUpService;
use App\Services\EmployerProspectAssignmentService;
use App\Services\EmployerProspectSyncService;
use App\Services\EmployerProspectVisibilityService;
use App\Services\SalesTeamService;
use App\Support\PortalDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployerPipelineController extends Controller
{
    public function __construct(
        private readonly EmployerProspectVisibilityService $visibility,
        private readonly EmployerProspectAssignmentService $assignment,
        private readonly EmployerProspectSyncService $sync,
        private readonly CompanyB2bPipelineService $b2bPipeline,
        private readonly SalesTeamService $teams,
    ) {
    }

    public function index(Request $request): View
    {
        $this->syncIfStale();
        $admin = $request->user('admin');
        $dateFilter = PortalDateFilter::fromRequest($request);
        $query = $this->baseQuery($admin);
        $dateFilter->apply($query);

        if ($request->filled('pipeline_stage')) {
            $query->where('pipeline_stage', $request->string('pipeline_stage')->toString());
        }

        if ($request->filled('assignment_status')) {
            $query->where('assignment_status', $request->string('assignment_status')->toString());
        }

        $prospects = $query->paginate(15)->withQueryString();

        return view('admin.employers.pipeline.index', $this->indexPayload($admin, $prospects, $dateFilter));
    }

    public function kanban(Request $request): View
    {
        $this->syncIfStale();
        $admin = $request->user('admin');
        $columns = [];

        foreach ($this->b2bPipeline->stages() as $stage) {
            $q = $this->baseQuery($admin);
            $q->where('pipeline_stage', $stage->value);
            $columns[$stage->value] = [
                'label' => $stage->label(),
                'probability' => $stage->winProbability(),
                'prospects' => $q->limit(40)->get(),
            ];
        }

        return view('admin.employers.pipeline.kanban', [
            'columns' => $columns,
            'pipeline' => SalesTeam::Employer,
            'stageLabels' => $this->b2bPipeline->stageLabels(),
        ]);
    }

    public function show(CrmEmployerProspect $prospect): View
    {
        abort_unless($this->visibility->canView(auth('admin')->user(), $prospect), 403);

        $prospect->load(['assignedTo', 'salesManager', 'hirevoUser.referrerProfile', 'meetings', 'proposals', 'activities.admin', 'client']);

        return view('admin.employers.pipeline.show', [
            'prospect' => $prospect,
            'pipeline' => SalesTeam::Employer,
            'stages' => $this->b2bPipeline->stages(),
            'stageLabels' => $this->b2bPipeline->stageLabels(),
            'upcomingFollowUps' => CrmFollowUp::query()
                ->where('employer_prospect_id', $prospect->id)
                ->with('admin')
                ->orderBy('scheduled_at')
                ->limit(8)
                ->get(),
            'upcomingMeetings' => CrmCompanyMeeting::query()
                ->where('employer_prospect_id', $prospect->id)
                ->where(function ($q) {
                    $q->whereNull('outcome')->orWhere('outcome', '');
                })
                ->with('admin')
                ->orderBy('meeting_at')
                ->limit(8)
                ->get(),
            'planPayments' => $prospect->user_id
                ? HirevoPayment::query()
                    ->where('user_id', $prospect->user_id)
                    ->where('type', HirevoPayment::TYPE_EMPLOYER_SUBSCRIPTION)
                    ->where('payment_gateway', HirevoPayment::GATEWAY_CHEQUE)
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get()
                : collect(),
            'canCompletePlanPayments' => auth('admin')->user()?->canPermission('employer_payments.complete') ?? false,
        ]);
    }

    public function updateStage(
        Request $request,
        CrmEmployerProspect $prospect,
        FollowUpService $followUpService,
        CompanyMeetingService $meetingService,
    ): RedirectResponse {
        abort_unless($this->visibility->canView($request->user('admin'), $prospect), 403);

        $validated = $request->validate([
            'pipeline_stage' => ['required', Rule::enum(CompanyB2bPipelineStage::class)],
            'deal_value' => ['nullable', 'numeric', 'min:0'],
            'follow_up_scheduled_at' => ['required_if:pipeline_stage,follow_up', 'nullable', 'date'],
            'follow_up_notes' => ['nullable', 'string', 'max:5000'],
            'meeting_scheduled_at' => ['required_if:pipeline_stage,meeting_scheduled', 'nullable', 'date'],
            'meeting_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (isset($validated['deal_value'])) {
            $prospect->deal_value = $validated['deal_value'];
            $prospect->save();
        }

        $this->b2bPipeline->moveToStage($prospect, $validated['pipeline_stage'], $request->user('admin'));

        if ($validated['pipeline_stage'] === CompanyB2bPipelineStage::FollowUp->value
            && $request->user('admin')->canPermission('leads.manage_followups')) {
            $followUpService->scheduleForProspect($prospect, $request->user('admin'), [
                'scheduled_at' => $validated['follow_up_scheduled_at'],
                'notes' => $validated['follow_up_notes'] ?? null,
            ]);
        }

        if ($validated['pipeline_stage'] === CompanyB2bPipelineStage::MeetingScheduled->value
            && $request->user('admin')->canPermission('leads.manage_followups')) {
            $meetingService->schedule($prospect, $request->user('admin'), [
                'meeting_at' => $validated['meeting_scheduled_at'],
                'notes' => $validated['meeting_notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Company moved to '.$prospect->fresh()->pipelineStageEnum()->label());
    }

    public function storeMeeting(Request $request, CrmEmployerProspect $prospect, CompanyMeetingService $meetingService): RedirectResponse
    {
        abort_unless($this->visibility->canView($request->user('admin'), $prospect), 403);
        abort_unless($request->user('admin')->canPermission('leads.manage_followups'), 403);

        $validated = $request->validate([
            'meeting_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $meetingService->schedule($prospect, $request->user('admin'), $validated);

        return back()->with('success', 'Meeting scheduled.');
    }

    public function storeFollowUp(Request $request, CrmEmployerProspect $prospect, FollowUpService $followUpService): RedirectResponse
    {
        abort_unless($this->visibility->canView($request->user('admin'), $prospect), 403);
        abort_unless($request->user('admin')->canPermission('leads.manage_followups'), 403);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $followUpService->scheduleForProspect($prospect, $request->user('admin'), $validated);

        return back()->with('success', 'Follow-up scheduled.');
    }

    public function bulkAssignManagers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prospect_ids' => ['required', 'array', 'min:1', 'max:200'],
            'prospect_ids.*' => ['integer', 'exists:crm_employer_prospects,id'],
            'manager_id' => ['required', 'exists:admins,id'],
        ]);

        $actor = $request->user('admin');
        $manager = Admin::query()->findOrFail((int) $validated['manager_id']);
        $result = $this->assignment->bulkAssignToManagers($validated['prospect_ids'], $manager, $actor);

        return $this->bulkRedirect($result, 'company team manager');
    }

    public function bulkAssignEmployees(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prospect_ids' => ['required', 'array', 'min:1', 'max:200'],
            'prospect_ids.*' => ['integer', 'exists:crm_employer_prospects,id'],
            'employee_id' => ['required', 'exists:admins,id'],
        ]);

        $manager = $request->user('admin');
        $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
        $result = $this->assignment->bulkAssignToEmployees($validated['prospect_ids'], $employee, $manager);

        return $this->bulkRedirect($result, 'company team executive');
    }

    /** @return \Illuminate\Database\Eloquent\Builder<CrmEmployerProspect> */
    private function baseQuery(Admin $admin)
    {
        $query = CrmEmployerProspect::query()
            ->with(['assignedTo', 'salesManager', 'hirevoUser.referrerProfile'])
            ->orderByDesc('updated_at');

        $this->visibility->restrictVisible($query, $admin);

        if (request()->filled('q')) {
            $q = request()->string('q')->toString();
            $query->where(function ($inner) use ($q) {
                $inner->where('company_name', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function indexPayload(Admin $admin, $prospects, PortalDateFilter $dateFilter): array
    {
        $stageLabels = $this->b2bPipeline->stageLabels();
        $stageCounts = $this->stageCountsFor($admin, $dateFilter);

        return [
            'prospects' => $prospects,
            'dateFilter' => $dateFilter,
            'pipeline' => SalesTeam::Employer,
            'assignableManagers' => $this->assignableManagers(),
            'assignableEmployees' => $this->assignableEmployees($admin),
            'canBulkManagers' => $admin->canPermission('leads.assign_manager'),
            'canBulkEmployees' => (
                $admin->canPermission('leads.assign_employee')
                && $admin->role === AdminRole::SalesManager
                && $this->teams->teamFor($admin) === SalesTeam::Employer
            ),
            'stageLabels' => $stageLabels,
            'stageCounts' => $stageCounts,
            'pipelineStages' => CompanyB2bPipelineStage::ordered(),
        ];
    }

    /** @return array<string, int> */
    private function stageCountsFor(Admin $admin, ?PortalDateFilter $dateFilter = null): array
    {
        $query = CrmEmployerProspect::query();
        $this->visibility->restrictVisible($query, $admin);
        $dateFilter?->apply($query);

        return $query
            ->selectRaw('pipeline_stage, COUNT(*) as aggregate')
            ->groupBy('pipeline_stage')
            ->pluck('aggregate', 'pipeline_stage')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Admin> */
    private function assignableManagers(): \Illuminate\Database\Eloquent\Collection
    {
        return Admin::query()
            ->where('role', AdminRole::SalesManager)
            ->where('sales_team', SalesTeam::Employer->value)
            ->orderBy('name')
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Admin> */
    private function assignableEmployees(Admin $admin): \Illuminate\Database\Eloquent\Collection
    {
        return Admin::query()
            ->where('role', AdminRole::SalesEmployee)
            ->where('sales_team', SalesTeam::Employer->value)
            ->when($admin->role === AdminRole::SalesManager, fn ($q) => $q->where('manager_id', $admin->id))
            ->orderBy('name')
            ->get();
    }

    private function bulkRedirect(array $result, string $label): RedirectResponse
    {
        $redirect = back();

        if ($result['success'] === 0 && ($result['skipped'] ?? 0) === 0 && $result['errors'] !== []) {
            return $redirect
                ->with('error', 'No companies were updated. Check the list below or pick different rows.')
                ->with('bulk_errors', $result['errors']);
        }

        if ($result['success'] > 0 || ($result['skipped'] ?? 0) > 0) {
            $msg = "Assigned {$result['success']} company(ies) to {$label}.";
            if (($result['skipped'] ?? 0) > 0) {
                $msg .= ' '.($result['skipped']).' skipped (already assigned as selected).';
            }
            $redirect = $redirect->with('success', $msg);
        }

        if ($result['errors'] !== []) {
            $redirect = $redirect->with('bulk_errors', $result['errors']);
        }

        return $redirect;
    }

    private function syncIfStale(): void
    {
        $ttl = max(1, (int) config('crm.employer_prospect_sync_ttl_minutes', 5));
        $key = 'crm.employer_prospect_sync';

        if (Cache::has($key)) {
            return;
        }

        $this->sync->syncFromHirevo();
        Cache::put($key, 1, now()->addMinutes($ttl));
    }
}
