<?php

namespace App\Services;

use App\Enums\CompanyB2bPipelineStage;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmCompanyClient;
use App\Modules\Leads\Models\CrmCompanyProposal;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Support\DashboardPeriod;
use Illuminate\Support\Facades\Schema;

class CompanyB2bDashboardService
{
    public function __construct(
        private readonly EmployerProspectVisibilityService $visibility,
        private readonly DashboardPipelineMetrics $pipelineMetrics,
    ) {
    }

    public function metricsFor(Admin $admin, ?DashboardPeriod $period = null): array
    {
        $period ??= DashboardPeriod::forPreset('this_month');
        $base = CrmEmployerProspect::query();
        $this->visibility->restrictVisible($base, $admin);

        $today = now()->toDateString();
        $summary = $this->pipelineMetrics->companySummary($base, $period);

        $callsToday = 0;
        if (Schema::hasTable('crm_call_logs') && Schema::hasColumn('crm_call_logs', 'employer_prospect_id')) {
            $prospectIds = (clone $base)->pluck('id');
            $callsToday = CrmCallLog::query()
                ->whereIn('employer_prospect_id', $prospectIds)
                ->whereDate('called_at', $today)
                ->count();
        }

        $meetingsToday = Schema::hasTable('crm_company_meetings')
            ? CrmCompanyMeeting::query()
                ->whereIn('employer_prospect_id', (clone $base)->pluck('id'))
                ->whereDate('meeting_at', $today)
                ->count()
            : 0;

        $proposalsSent = Schema::hasTable('crm_company_proposals')
            ? CrmCompanyProposal::query()
                ->whereIn('employer_prospect_id', (clone $base)->pluck('id'))
                ->where('status', 'sent')
                ->count()
            : (clone $base)->where('pipeline_stage', CompanyB2bPipelineStage::ProposalSent->value)->count();

        $inNegotiation = (clone $base)->where('pipeline_stage', CompanyB2bPipelineStage::Negotiation->value)->count();
        $wonRevenue = (float) (clone $base)->whereIn('pipeline_stage', [
            CompanyB2bPipelineStage::Won->value,
            CompanyB2bPipelineStage::Onboarding->value,
            CompanyB2bPipelineStage::HiringActive->value,
        ])->sum('deal_value');

        $expectedRevenue = (float) (clone $base)
            ->whereNotIn('pipeline_stage', [CompanyB2bPipelineStage::Lost->value, CompanyB2bPipelineStage::Renewed->value])
            ->sum('expected_revenue');

        $pipelineValue = (float) (clone $base)
            ->whereNotIn('pipeline_stage', [
                CompanyB2bPipelineStage::Won->value,
                CompanyB2bPipelineStage::Lost->value,
                CompanyB2bPipelineStage::Renewed->value,
            ])
            ->sum('deal_value');

        $byStage = (clone $base)
            ->selectRaw('pipeline_stage, COUNT(*) as aggregate')
            ->groupBy('pipeline_stage')
            ->pluck('aggregate', 'pipeline_stage')
            ->toArray();

        $prospectIds = (clone $base)->pluck('id');
        $activeClients = Schema::hasTable('crm_company_clients') && $prospectIds->isNotEmpty()
            ? CrmCompanyClient::query()->whereIn('employer_prospect_id', $prospectIds)->count()
            : (clone $base)->whereIn('pipeline_stage', [
                CompanyB2bPipelineStage::HiringActive->value,
                CompanyB2bPipelineStage::Renewed->value,
            ])->count();

        return [
            'role' => $admin->role,
            'period' => $period,
            'summary' => $summary,
            'callsToday' => $callsToday,
            'meetingsToday' => $meetingsToday,
            'proposalsSent' => $proposalsSent,
            'inNegotiation' => $inNegotiation,
            'wonRevenue' => $wonRevenue,
            'expectedRevenue' => $expectedRevenue,
            'pipelineValue' => $pipelineValue,
            'totalCompanies' => (clone $base)->count(),
            'stageCounts' => $byStage,
            'activeClients' => $activeClients,
            'companiesContactedToday' => (clone $base)->where('pipeline_stage', CompanyB2bPipelineStage::Contacted->value)
                ->whereDate('last_activity_at', $today)->count(),
        ];
    }
}
