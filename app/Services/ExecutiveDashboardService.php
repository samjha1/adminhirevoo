<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\CompanyB2bPipelineStage;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Support\DashboardPeriod;
use Illuminate\Support\Facades\Schema;

class ExecutiveDashboardService
{
    public function __construct(
        private readonly DashboardScopeService $scope,
        private readonly DashboardPipelineMetrics $metrics,
        private readonly DashboardActivityFeed $activityFeed,
    ) {
    }

    public function metricsFor(Admin $admin, DashboardPeriod $period): array
    {
        $talentBase = $this->scope->talentLeadsQuery($admin);
        $companyBase = $this->scope->companyProspectsQuery($admin);

        $talentSummary = $this->metrics->talentSummary($talentBase, $period);
        $companySummary = $this->metrics->companySummary($companyBase, $period);

        $combined = [
            'totalLeads' => $talentSummary['totalLeads'] + $companySummary['totalLeads'],
            'newLeads' => $talentSummary['newLeads'] + $companySummary['newLeads'],
            'meetings' => $talentSummary['meetings'] + $companySummary['meetings'],
            'calls' => ($talentSummary['calls'] ?? 0) + ($companySummary['calls'] ?? 0),
            'closed' => $talentSummary['closed'] + $companySummary['closed'],
            'activeInPeriod' => ($talentSummary['activeInPeriod'] ?? 0) + ($companySummary['activeInPeriod'] ?? 0),
            'revenue' => $talentSummary['revenue'] + $companySummary['revenue'],
            'revenueToday' => $talentSummary['revenueToday'] + $companySummary['revenueToday'],
            'conversionRate' => ($talentSummary['totalLeads'] + $companySummary['totalLeads']) > 0
                ? round(
                    (($talentSummary['closed'] + $companySummary['closed'])
                        / ($talentSummary['totalLeads'] + $companySummary['totalLeads'])) * 100,
                    1,
                )
                : 0.0,
            'revenueGrowth' => $companySummary['revenueGrowth'],
            'periodLabel' => $period->label(),
        ];

        $leadTrend = $this->metrics->leadTrendSeries($period, $talentBase, $companyBase);
        $revenueTrend = $this->metrics->revenueTrendSeries($period, $companyBase);
        $meetingsVsClosures = $this->metrics->meetingsVsClosuresWeekly($period, $talentBase, $companyBase);

        $talentFunnelPeriod = $this->metrics->talentFunnelInPeriod($talentBase, $period);
        $companyFunnelPeriod = $this->metrics->companyFunnelInPeriod($companyBase, $period);

        $statusDistribution = [
            'talent' => $talentFunnelPeriod,
            'company' => $companyFunnelPeriod,
        ];

        return [
            'role' => $admin->role,
            'period' => $period,
            'summary' => [
                'talent' => $talentSummary,
                'company' => $companySummary,
                'combined' => $combined,
            ],
            'funnel' => [
                'talent' => $talentFunnelPeriod,
                'talentAllTime' => $this->metrics->talentFunnel($talentBase),
                'company' => $this->buildCompanyFunnelLabels($companyFunnelPeriod),
                'companyAllTime' => $this->buildCompanyFunnelLabels($this->metrics->companyFunnel($companyBase)),
            ],
            'activityFeeds' => [
                'talent' => $this->activityFeed->forPeriod($admin, $period, SalesTeam::Candidate),
                'company' => $this->activityFeed->forPeriod($admin, $period, SalesTeam::Employer),
            ],
            'trends' => [
                'leadTrend' => $leadTrend,
                'revenueTrend' => $revenueTrend,
                'meetingsVsClosures' => $meetingsVsClosures,
                'statusDistribution' => $statusDistribution,
            ],
            'teamTables' => [
                'byTeam' => $this->teamPerformanceTable($talentSummary, $companySummary),
                'byManager' => $this->managerPerformanceTable($period, $talentBase, $companyBase),
                'byEmployee' => $this->employeePerformanceTable($period, $talentBase, $companyBase),
            ],
            'platform' => $this->platformSnapshot(),
            'recentActivities' => $this->recentActivities(),
        ];
    }

    /** @return list<array{team: string, pipeline: string, leads: int, meetings: int, closures: int, revenue: float}> */
    private function teamPerformanceTable(array $talentSummary, array $companySummary): array
    {
        return [
            [
                'team' => SalesTeam::Candidate->shortLabel(),
                'pipeline' => 'talent',
                'leads' => $talentSummary['totalLeads'],
                'meetings' => $talentSummary['meetings'],
                'closures' => $talentSummary['closed'],
                'revenue' => $talentSummary['revenue'],
            ],
            [
                'team' => SalesTeam::Employer->shortLabel(),
                'pipeline' => 'company',
                'leads' => $companySummary['totalLeads'],
                'meetings' => $companySummary['meetings'],
                'closures' => $companySummary['closed'],
                'revenue' => $companySummary['revenue'],
            ],
        ];
    }

    /**
     * @return list<array{id: int, name: string, team: string, leads: int, meetings: int, closures: int, revenue: float}>
     */
    private function managerPerformanceTable(
        DashboardPeriod $period,
        $talentBase,
        $companyBase,
    ): array {
        $rows = [];
        $managers = Admin::query()
            ->where('role', AdminRole::SalesManager)
            ->orderBy('name')
            ->get();

        foreach ($managers as $manager) {
            $isCompany = $manager->sales_team === SalesTeam::Employer;
            if ($isCompany) {
                $q = $companyBase ? (clone $companyBase) : CrmEmployerProspect::query();
                $q->where(function ($sub) use ($manager) {
                    $sub->where('sales_manager_id', $manager->id)
                        ->orWhere('assigned_to', $manager->id);
                });
                $memberMetrics = $this->aggregateCompanyQuery($q, $period);
            } else {
                $q = $talentBase ? (clone $talentBase) : HirevoLead::query();
                $q->where(function ($sub) use ($manager) {
                    $sub->where('sales_manager_id', $manager->id)
                        ->orWhere('assigned_to', $manager->id);
                });
                $memberMetrics = $this->aggregateTalentQuery($q, $period);
            }

            $rows[] = [
                'id' => $manager->id,
                'name' => $manager->name,
                'team' => $manager->sales_team?->shortLabel() ?? '—',
                ...$memberMetrics,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{id: int, name: string, team: string, manager: string, leads: int, meetings: int, closures: int, revenue: float}>
     */
    private function employeePerformanceTable(
        DashboardPeriod $period,
        $talentBase,
        $companyBase,
    ): array {
        $rows = [];
        $employees = Admin::query()
            ->where('role', AdminRole::SalesEmployee)
            ->with('manager')
            ->orderBy('name')
            ->get();

        foreach ($employees as $employee) {
            $isCompany = $employee->sales_team === SalesTeam::Employer;
            if ($isCompany) {
                $metrics = $this->metrics->memberCompanyMetrics($employee, $period, $companyBase);
            } else {
                $metrics = $this->metrics->memberTalentMetrics($employee, $period, $talentBase);
            }

            $rows[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'team' => $employee->sales_team?->shortLabel() ?? '—',
                'manager' => $employee->manager?->name ?? '—',
                'leads' => $metrics['leads'],
                'meetings' => $metrics['meetings'],
                'closures' => $metrics['closed'],
                'revenue' => $metrics['revenue'],
            ];
        }

        return $rows;
    }

    /** @return array{leads: int, meetings: int, closures: int, revenue: float} */
    private function aggregateTalentQuery($q, DashboardPeriod $period): array
    {
        $leadIds = (clone $q)->pluck('id');
        $leads = (clone $q)->whereBetween('created_at', [$period->start, $period->end])->count();
        $meetings = ! Schema::hasTable('crm_follow_ups') || $leadIds->isEmpty()
            ? 0
            : \App\Modules\Leads\Models\CrmFollowUp::query()
                ->whereIn('lead_id', $leadIds)
                ->whereBetween('scheduled_at', [$period->start, $period->end])
                ->count();
        $closures = (clone $q)
            ->where('sales_status', 'converted')
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->count();

        return [
            'leads' => $leads,
            'meetings' => $meetings,
            'closures' => $closures,
            'revenue' => 0.0,
        ];
    }

    /** @return array{leads: int, meetings: int, closures: int, revenue: float} */
    private function aggregateCompanyQuery($q, DashboardPeriod $period): array
    {
        $prospectIds = (clone $q)->pluck('id');
        $leads = (clone $q)->whereBetween('created_at', [$period->start, $period->end])->count();
        $meetings = Schema::hasTable('crm_company_meetings') && $prospectIds->isNotEmpty()
            ? \App\Modules\Leads\Models\CrmCompanyMeeting::query()
                ->whereIn('employer_prospect_id', $prospectIds)
                ->whereBetween('meeting_at', [$period->start, $period->end])
                ->count()
            : 0;
        $closures = (clone $q)
            ->whereIn('pipeline_stage', DashboardPipelineMetrics::COMPANY_WON_STAGES)
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->count();
        $revenue = $this->metrics->companyRevenueBetween($period->start, $period->end, $q);

        return [
            'leads' => $leads,
            'meetings' => $meetings,
            'closures' => $closures,
            'revenue' => $revenue,
        ];
    }

    /** @param  array<string, int>  $counts */
    private function buildCompanyFunnelLabels(array $counts): array
    {
        $labeled = [];
        foreach (CompanyB2bPipelineStage::ordered() as $stage) {
            $labeled[$stage->value] = [
                'label' => $stage->label(),
                'count' => $counts[$stage->value] ?? 0,
            ];
        }
        if (isset($counts[CompanyB2bPipelineStage::Lost->value])) {
            $labeled[CompanyB2bPipelineStage::Lost->value] = [
                'label' => CompanyB2bPipelineStage::Lost->label(),
                'count' => $counts[CompanyB2bPipelineStage::Lost->value],
            ];
        }

        return $labeled;
    }

    /** @return array<string, int|float> */
    private function platformSnapshot(): array
    {
        return [
            'totalTalentLeads' => HirevoLead::query()->count(),
            'totalCompanies' => CrmEmployerProspect::query()->count(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, AuditLog> */
    private function recentActivities()
    {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        return AuditLog::query()
            ->with('admin')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }
}
