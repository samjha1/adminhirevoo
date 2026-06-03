<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoLead;
use App\Services\LeadPipelineService;
use App\Services\LeadVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadKanbanController extends Controller
{
    public function index(Request $request, LeadVisibilityService $visibility, LeadPipelineService $pipeline): View
    {
        $admin = $request->user('admin');
        $stages = $pipeline->managementStages();
        $labels = $pipeline->managementStageLabels();

        $columns = [];
        foreach ($stages as $stage) {
            $q = HirevoLead::query()
                ->with(['candidate', 'adminStage', 'assignedTo'])
                ->orderByDesc('updated_at');
            $visibility->restrictVisibleLeads($q, $admin);

            if ($stage === 'new') {
                $q->where(function ($inner) {
                    $inner->whereDoesntHave('adminStage')
                        ->orWhereHas('adminStage', fn ($s) => $s->where('stage', 'new'));
                });
            } else {
                $q->whereHas('adminStage', fn ($s) => $s->where('stage', $stage));
            }

            $columns[$stage] = [
                'label' => $labels[$stage] ?? $stage,
                'leads' => $q->limit(30)->get(),
            ];
        }

        return view('admin.leads.kanban', [
            'columns' => $columns,
            'stages' => $stages,
        ]);
    }

    public function moveStage(Request $request, HirevoLead $lead, LeadPipelineService $pipeline): RedirectResponse
    {
        $this->authorize('updateCrmStage', $lead);

        $validated = $request->validate([
            'stage' => ['required', 'in:'.implode(',', $pipeline->managementStages())],
            'follow_up_scheduled_at' => ['required_if:stage,follow_up,interview', 'nullable', 'date'],
            'follow_up_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (in_array($validated['stage'], ['follow_up', 'interview'], true) && ! $request->filled('follow_up_scheduled_at')) {
            return redirect()
                ->route('admin.leads.show', $lead)
                ->with('info', 'Set a date and notes on the lead page when moving to Follow up or Interview.');
        }

        $stage = \App\Models\AdminLeadStage::query()->firstOrNew(['lead_id' => $lead->id]);
        $stage->stage = $validated['stage'];
        if (in_array($validated['stage'], ['called', 'follow_up'], true)) {
            $stage->last_contacted_at = now();
        }
        $stage->save();

        if (in_array($validated['stage'], ['follow_up', 'interview'], true) && $request->user('admin')->canPermission('leads.manage_followups')) {
            app(\App\Modules\Leads\Services\FollowUpService::class)->schedule($lead, $request->user('admin'), [
                'scheduled_at' => $validated['follow_up_scheduled_at'],
                'notes' => $validated['follow_up_notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Stage updated.');
    }
}
