<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Enums\AdminRole;
use App\Enums\CompanyOutreachStage;
use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmCompanyOutreachLead;
use App\Modules\Leads\Services\CompanyOutreachImportService;
use App\Modules\Leads\Services\CompanyOutreachPipelineService;
use App\Services\CompanyOutreachAssignmentService;
use App\Services\CompanyOutreachVisibilityService;
use App\Services\CompanySalesAssignmentSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyOutreachLeadController extends Controller
{
    public function __construct(
        private readonly CompanyOutreachPipelineService $pipeline,
        private readonly CompanyOutreachVisibilityService $visibility,
        private readonly CompanyOutreachAssignmentService $assignment,
        private readonly CompanySalesAssignmentSupport $assignmentSupport,
    ) {
    }

    public function index(Request $request): View
    {
        $admin = $request->user('admin');
        $query = CrmCompanyOutreachLead::query()
            ->with(['assignedTo', 'salesManager', 'creator'])
            ->orderByDesc('updated_at');

        $this->visibility->restrictVisible($query, $admin);

        if ($request->filled('outreach_stage')) {
            $query->where('outreach_stage', $request->string('outreach_stage')->toString());
        }

        if ($request->filled('assignment_status')) {
            $query->where('assignment_status', $request->string('assignment_status')->toString());
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($inner) use ($q) {
                $inner->where('company_name', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $leads = $query->paginate(20)->withQueryString();

        return view('admin.employers.outreach.index', [
            'leads' => $leads,
            'pipeline' => SalesTeam::Employer,
            'stageLabels' => $this->pipeline->stageLabels(),
            'stageCounts' => $this->stageCountsFor($admin),
            'outreachStages' => CompanyOutreachStage::ordered(),
            'assignableTeamLeads' => $this->assignmentSupport->assignableOutreachTeamLeads($admin),
            'assignableEmployees' => $this->assignmentSupport->assignableEmployees($admin),
            'canBulkTeamLeads' => $this->assignmentSupport->canAssignManagers($admin),
            'canBulkEmployees' => $this->assignmentSupport->canAssignEmployees($admin),
            'isAsmActor' => $admin->role === AdminRole::Asm,
        ]);
    }

    public function show(CrmCompanyOutreachLead $outreachLead): View
    {
        abort_unless($this->visibility->canView(auth('admin')->user(), $outreachLead), 403);

        $outreachLead->load(['assignedTo', 'salesManager', 'creator', 'hirevoUser.referrerProfile']);
        $admin = auth('admin')->user();

        return view('admin.employers.outreach.show', [
            'lead' => $outreachLead,
            'pipeline' => SalesTeam::Employer,
            'stages' => CompanyOutreachStage::cases(),
            'stageLabels' => $this->pipeline->stageLabels(),
            'assignableTeamLeads' => $this->assignmentSupport->assignableOutreachTeamLeads($admin),
            'assignableEmployees' => $this->assignmentSupport->assignableEmployees($admin),
            'canAssignTeamLeads' => $this->assignmentSupport->canAssignManagers($admin),
            'canAssignEmployees' => $this->assignmentSupport->canAssignEmployees($admin),
            'isAsmActor' => $admin->role === AdminRole::Asm,
        ]);
    }

    public function updateStage(Request $request, CrmCompanyOutreachLead $outreachLead): RedirectResponse
    {
        abort_unless($this->visibility->canView($request->user('admin'), $outreachLead), 403);

        $validated = $request->validate([
            'outreach_stage' => ['required', Rule::enum(CompanyOutreachStage::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $this->pipeline->moveToStage($outreachLead, $validated['outreach_stage']);

        if (! empty($validated['notes'])) {
            $outreachLead->update([
                'notes' => trim(($outreachLead->notes ? $outreachLead->notes."\n\n" : '').now()->format('Y-m-d H:i').': '.$validated['notes']),
            ]);
        }

        if (! empty($validated['follow_up_at'])) {
            $outreachLead->update(['follow_up_at' => $validated['follow_up_at']]);
        }

        return back()->with('success', 'Stage updated to '.$outreachLead->fresh()->outreachStageEnum()->label());
    }

    public function assignTeamLead(Request $request, CrmCompanyOutreachLead $outreachLead): RedirectResponse
    {
        abort_unless($this->visibility->canView($request->user('admin'), $outreachLead), 403);

        $validated = $request->validate([
            'team_lead_id' => ['required', 'exists:admins,id'],
        ]);

        $actor = $request->user('admin');
        $target = Admin::query()->findOrFail((int) $validated['team_lead_id']);
        $this->assignment->assignToTeamLead($outreachLead, $target, $actor);

        return back()->with('success', 'Assigned to '.$target->name.'.');
    }

    public function assignEmployee(Request $request, CrmCompanyOutreachLead $outreachLead): RedirectResponse
    {
        abort_unless($this->visibility->canView($request->user('admin'), $outreachLead), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:admins,id'],
        ]);

        $actor = $request->user('admin');
        $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
        $this->assignment->assignToEmployee($outreachLead, $employee, $actor);

        return back()->with('success', 'Assigned to '.$employee->name.'.');
    }

    public function bulkAssignTeamLeads(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:200'],
            'lead_ids.*' => ['integer', 'exists:crm_company_outreach_leads,id'],
            'team_lead_id' => ['required', 'exists:admins,id'],
        ]);

        $actor = $request->user('admin');
        $target = Admin::query()->findOrFail((int) $validated['team_lead_id']);
        $result = $this->assignment->bulkAssignToTeamLeads($validated['lead_ids'], $target, $actor);

        $label = $target->role === AdminRole::Asm ? 'ASM' : 'manager';

        return $this->bulkRedirect($result, $label);
    }

    public function bulkAssignEmployees(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:200'],
            'lead_ids.*' => ['integer', 'exists:crm_company_outreach_leads,id'],
            'employee_id' => ['required', 'exists:admins,id'],
        ]);

        $actor = $request->user('admin');
        $employee = Admin::query()->findOrFail((int) $validated['employee_id']);
        $result = $this->assignment->bulkAssignToEmployees($validated['lead_ids'], $employee, $actor);

        return $this->bulkRedirect($result, 'executive');
    }

    public function importForm(): View
    {
        return view('admin.employers.outreach.import', [
            'pipeline' => SalesTeam::Employer,
        ]);
    }

    public function import(Request $request, CompanyOutreachImportService $service): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $result = $service->import($request->file('file'), $request->user('admin'));

        return redirect()->route('admin.employers.outreach.index')
            ->with('success', "Imported {$result['imported']} company lead(s). Skipped {$result['skipped']}.");
    }

    public function template(CompanyOutreachImportService $service): StreamedResponse
    {
        return $service->downloadTemplate();
    }

    /** @return array<string, int> */
    private function stageCountsFor(Admin $admin): array
    {
        $query = CrmCompanyOutreachLead::query();
        $this->visibility->restrictVisible($query, $admin);

        return $this->pipeline->stageCounts($query);
    }

    private function bulkRedirect(array $result, string $label): RedirectResponse
    {
        $redirect = back();

        if ($result['success'] === 0 && ($result['skipped'] ?? 0) === 0 && $result['errors'] !== []) {
            return $redirect
                ->with('error', 'No leads were updated. Check the list below or pick different rows.')
                ->with('bulk_errors', $result['errors']);
        }

        if ($result['success'] > 0 || ($result['skipped'] ?? 0) > 0) {
            $msg = "Assigned {$result['success']} lead(s) to {$label}.";
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
}
