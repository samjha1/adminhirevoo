<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Services\FollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadFollowUpController extends Controller
{
    public function myFollowUps(Request $request): View
    {
        $admin = $request->user('admin');

        $followUps = CrmFollowUp::query()
            ->with(['admin'])
            ->where('admin_id', $admin->id)
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('admin.leads.follow-ups.index', [
            'followUps' => $followUps,
            'filter' => 'all',
        ]);
    }

    public function today(Request $request): View
    {
        $admin = $request->user('admin');

        $followUps = CrmFollowUp::query()
            ->where('admin_id', $admin->id)
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('admin.leads.follow-ups.index', [
            'followUps' => $followUps,
            'filter' => 'today',
        ]);
    }

    public function store(Request $request, HirevoLead $lead, FollowUpService $followUpService): RedirectResponse
    {
        $this->authorize('manageFollowups', $lead);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admin_id' => ['nullable', 'exists:admins,id'],
        ]);

        $followUpService->schedule($lead, $request->user('admin'), $validated);

        return back()->with('success', 'Follow-up scheduled.');
    }

    public function complete(Request $request, CrmFollowUp $followUp, FollowUpService $followUpService): RedirectResponse
    {
        $lead = HirevoLead::query()->findOrFail($followUp->lead_id);
        $this->authorize('manageFollowups', $lead);

        $followUpService->complete($followUp, $request->user('admin'));

        return back()->with('success', 'Follow-up marked complete.');
    }
}
