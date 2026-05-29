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
        ]);

        $stage = \App\Models\AdminLeadStage::query()->firstOrNew(['lead_id' => $lead->id]);
        $stage->stage = $validated['stage'];
        $stage->save();

        return back()->with('success', 'Stage updated.');
    }
}
