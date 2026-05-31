<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\LeadAssignmentStatus;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Support\DashboardPeriod;

/**
 * Sales manager / employee dashboards scoped to one pipeline (their sales_team).
 */
class ScopedDashboardService
{
    public function __construct(
        private readonly DashboardScopeService $scope,
        private readonly DashboardPipelineMetrics $pipelineMetrics,
        private readonly CompanyB2bDashboardService $companyDashboard,
        private readonly RoleDashboardService $roleDashboard,
        private readonly DashboardActivityFeed $activityFeed,
    ) {
    }

    public function metricsFor(Admin $admin, DashboardPeriod $period): array
    {
        if ($admin->sales_team === SalesTeam::Employer) {
            return $this->companyScoped($admin, $period);
        }

        return $this->talentScoped($admin, $period);
    }

    private function talentScoped(Admin $admin, DashboardPeriod $period): array
    {
        $base = $this->scope->talentLeadsQuery($admin);
        $myBase = HirevoLead::query()->where('assigned_to', $admin->id);
        $summary = $admin->role === AdminRole::SalesManager
            ? $this->pipelineMetrics->talentSummary($myBase, $period)
            : $this->pipelineMetrics->talentSummary($base, $period);
        $roleMetrics = $this->roleDashboard->metricsFor($admin, $period);

        $data = array_merge($roleMetrics, [
            'period' => $period,
            'pipeline' => SalesTeam::Candidate,
            'summary' => $summary,
            'funnel' => $this->pipelineMetrics->talentFunnelInPeriod($base, $period),
            'activityFeed' => $this->activityFeed->forPeriod($admin, $period, SalesTeam::Candidate),
            'myRecordsInPeriod' => $this->activityFeed->myRecordsInPeriod($admin, $period, SalesTeam::Candidate),
            'trends' => [
                'leadTrend' => $this->pipelineMetrics->leadTrendSeries($period, $base, null),
                'revenueTrend' => $this->pipelineMetrics->revenueTrendSeries($period, null),
                'statusDistribution' => ['talent' => $this->pipelineMetrics->talentFunnelInPeriod($base, $period)],
            ],
        ]);

        if ($admin->role === AdminRole::SalesManager) {
            $data['teamMembers'] = $this->talentTeamMemberTable($admin, $period, $base);
            $data['teamSummary'] = $this->talentTeamSummary($admin, $period, $base);
        }

        return $data;
    }

    private function companyScoped(Admin $admin, DashboardPeriod $period): array
    {
        $base = $this->scope->companyProspectsQuery($admin);
        $summary = $this->pipelineMetrics->companySummary($base, $period);
        $legacy = $this->companyDashboard->metricsFor($admin, $period);

        $myBase = \App\Modules\Leads\Models\CrmEmployerProspect::query()->where('assigned_to', $admin->id);
        $mySummary = $admin->role === AdminRole::SalesManager
            ? $this->pipelineMetrics->companySummary($myBase, $period)
            : $summary;

        $data = array_merge($legacy, [
            'role' => $admin->role,
            'period' => $period,
            'pipeline' => SalesTeam::Employer,
            'summary' => $mySummary,
            'funnel' => $this->pipelineMetrics->companyFunnelInPeriod($base, $period),
            'activityFeed' => $this->activityFeed->forPeriod($admin, $period, SalesTeam::Employer),
            'myRecordsInPeriod' => $this->activityFeed->myRecordsInPeriod($admin, $period, SalesTeam::Employer),
            'trends' => [
                'leadTrend' => $this->pipelineMetrics->leadTrendSeries($period, null, $base),
                'revenueTrend' => $this->pipelineMetrics->revenueTrendSeries($period, $base),
                'statusDistribution' => ['company' => $this->pipelineMetrics->companyFunnelInPeriod($base, $period)],
            ],
        ]);

        if ($admin->role === AdminRole::SalesManager) {
            $data['teamMembers'] = $this->companyTeamMemberTable($admin, $period, $base);
            $data['teamSummary'] = $this->companyTeamSummary($admin, $period, $base);
        }

        return $data;
    }

    /** @return array{leads: int, meetings: int, closed: int, revenue: float, conversionRate: float} */
    private function talentTeamSummary(Admin $manager, DashboardPeriod $period, $base): array
    {
        $reportIds = Admin::query()->where('manager_id', $manager->id)->pluck('id')->push($manager->id);
        $q = $base ? (clone $base) : HirevoLead::query();
        $q->where(function ($sub) use ($manager, $reportIds) {
            $sub->where('sales_manager_id', $manager->id)
                ->orWhereIn('assigned_to', $reportIds);
        });

        return $this->pipelineMetrics->talentSummary($q, $period);
    }

    /** @return array<string, mixed> */
    private function companyTeamSummary(Admin $manager, DashboardPeriod $period, $base): array
    {
        $reportIds = Admin::query()->where('manager_id', $manager->id)->pluck('id')->push($manager->id);
        $q = $base ? (clone $base) : \App\Modules\Leads\Models\CrmEmployerProspect::query();
        $q->where(function ($sub) use ($manager, $reportIds) {
            $sub->where('sales_manager_id', $manager->id)
                ->orWhereIn('assigned_to', $reportIds);
        });

        return $this->pipelineMetrics->companySummary($q, $period);
    }

    /** @return list<array<string, mixed>> */
    private function talentTeamMemberTable(Admin $manager, DashboardPeriod $period, $base): array
    {
        $reports = Admin::query()
            ->where('manager_id', $manager->id)
            ->where('role', AdminRole::SalesEmployee)
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($reports as $report) {
            $m = $this->pipelineMetrics->memberTalentMetrics($report, $period, $base);
            $rows[] = [
                'employee' => $report->name,
                'leads' => $m['leads'],
                'meetings' => $m['meetings'],
                'closed' => $m['closed'],
                'revenue' => $m['revenue'],
            ];
        }

        $my = $this->pipelineMetrics->memberTalentMetrics($manager, $period, $base);
        array_unshift($rows, [
            'employee' => $manager->name.' (you)',
            'leads' => $my['leads'],
            'meetings' => $my['meetings'],
            'closed' => $my['closed'],
            'revenue' => $my['revenue'],
        ]);

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function companyTeamMemberTable(Admin $manager, DashboardPeriod $period, $base): array
    {
        $reports = Admin::query()
            ->where('manager_id', $manager->id)
            ->where('role', AdminRole::SalesEmployee)
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($reports as $report) {
            $m = $this->pipelineMetrics->memberCompanyMetrics($report, $period, $base);
            $rows[] = [
                'employee' => $report->name,
                'leads' => $m['leads'],
                'meetings' => $m['meetings'],
                'closed' => $m['closed'],
                'revenue' => $m['revenue'],
            ];
        }

        $my = $this->pipelineMetrics->memberCompanyMetrics($manager, $period, $base);
        array_unshift($rows, [
            'employee' => $manager->name.' (you)',
            'leads' => $my['leads'],
            'meetings' => $my['meetings'],
            'closed' => $my['closed'],
            'revenue' => $my['revenue'],
        ]);

        return $rows;
    }
}
