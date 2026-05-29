<?php

namespace App\Modules\Leads\Services;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Services\LeadVisibilityService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HirevoLeadExportService
{
    public function __construct(
        private readonly LeadVisibilityService $visibility,
    ) {
    }

    public function export(Request $request, Admin $admin): StreamedResponse
    {
        $q = HirevoLead::query()->with(['candidate', 'adminStage', 'assignedTo', 'salesManager'])
            ->orderByDesc('created_at');
        $this->visibility->restrictVisibleLeads($q, $admin);

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        $filename = 'hirevo-leads-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($q): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'candidate', 'email', 'phone', 'status', 'mgmt_stage', 'sales_status', 'manager', 'assignee']);

            $q->chunk(200, function ($leads) use ($out): void {
                foreach ($leads as $lead) {
                    fputcsv($out, [
                        $lead->id,
                        $lead->candidate?->name,
                        $lead->candidate?->email,
                        $lead->candidate?->phone,
                        $lead->status,
                        $lead->adminStage?->stage ?? 'new',
                        $lead->sales_status?->value ?? $lead->sales_status,
                        $lead->salesManager?->name,
                        $lead->assignedTo?->name,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
