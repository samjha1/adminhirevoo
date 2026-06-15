<?php

namespace App\Modules\Leads\Services;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Services\CandidateSectorService;
use App\Services\LeadVisibilityService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HirevoLeadExportService
{
    public function __construct(
        private readonly LeadVisibilityService $visibility,
        private readonly CandidateSectorService $sectors,
    ) {
    }

    public function export(Request $request, Admin $admin): StreamedResponse
    {
        $q = HirevoLead::query()->with(['candidate', 'adminStage', 'assignedTo', 'salesManager', 'jobRole'])
            ->orderByDesc('created_at');
        $this->visibility->restrictVisibleLeads($q, $admin);

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        if ($request->filled('sector')) {
            $this->sectors->applyLeadFilter($q, $request->string('sector')->toString());
        }

        $suffix = $request->filled('sector') ? '-'.$request->string('sector')->toString() : '';
        $filename = 'hirevo-leads'.$suffix.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($q): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'candidate', 'email', 'phone', 'sector', 'job_role',
                'status', 'mgmt_stage', 'sales_status', 'manager', 'assignee',
            ]);

            $q->chunk(200, function ($leads) use ($out): void {
                foreach ($leads as $lead) {
                    $sectorKey = $this->sectors->resolveForLead($lead);

                    fputcsv($out, [
                        $lead->id,
                        $lead->candidate?->name,
                        $lead->candidate?->email,
                        $lead->candidate?->phone,
                        $this->sectors->labelForCategory($sectorKey),
                        $lead->jobRole?->title,
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
