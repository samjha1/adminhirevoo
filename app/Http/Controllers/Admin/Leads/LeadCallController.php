<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Enums\CallOutcome;
use App\Modules\Leads\Services\CallLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadCallController extends Controller
{
    public function store(Request $request, HirevoLead $lead, CallLogService $callLogService): RedirectResponse
    {
        $this->authorize('logCall', $lead);

        $validated = $request->validate([
            'outcome' => ['required', Rule::enum(CallOutcome::class)],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'called_at' => ['nullable', 'date'],
        ]);

        $callLogService->log($lead, $request->user('admin'), $validated);

        return back()->with('success', 'Call logged successfully.');
    }
}
