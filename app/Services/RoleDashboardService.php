<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\LeadAssignmentStatus;
use App\Models\Admin;
use App\Models\Hirevo\HirevoCareerConsultationRequest;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoPayment;
use App\Models\Hirevo\HirevoReferralRequest;
use App\Models\Hirevo\HirevoUser;
use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Models\CrmStandaloneLead;
use App\Support\DashboardPeriod;
use App\Services\OrgHierarchyService;

class RoleDashboardService
{
    public function __construct(
        private readonly LeadPipelineService $leadPipeline,
        private readonly DashboardPipelineMetrics $pipelineMetrics,
        private readonly DashboardScopeService $scope,
    ) {
    }

    public function metricsFor(Admin $admin, ?DashboardPeriod $period = null): array
    {
        $period ??= DashboardPeriod::forPreset('this_month');

        return match ($admin->role) {
            AdminRole::SuperAdmin, AdminRole::Admin => $this->adminMetrics(),
            AdminRole::Marketing => $this->marketingMetrics($period),
            AdminRole::Asm, AdminRole::SalesManager => $this->salesManagerMetrics($admin, $period),
            AdminRole::SalesEmployee => $this->salesEmployeeMetrics($admin, $period),
            AdminRole::Recruiter => $this->recruiterMetrics(),
            AdminRole::RecruiterManager => $this->recruiterManagerMetrics(),
        };
    }

    private function recruiterManagerMetrics(): array
    {
        return [
            'role' => AdminRole::RecruiterManager,
        ];
    }

    private function recruiterMetrics(): array
    {
        return [
            'role' => AdminRole::Recruiter,
        ];
    }

    private function adminMetrics(): array
    {
        $totalUsers = HirevoUser::query()->count();
        $totalJobs = HirevoEmployerJob::query()->count();
        $totalApplications = HirevoEmployerJobApplication::query()->count();
        $totalReferrals = HirevoReferralRequest::query()->count();
        $acceptedReferrals = HirevoReferralRequest::query()->where('status', 'accepted')->count();
        $conversionRate = $totalReferrals > 0 ? round(($acceptedReferrals / $totalReferrals) * 100, 2) : 0;
        $revenue = (float) HirevoPayment::query()->where('status', 'completed')->sum('amount');
        $leadStages = HirevoLead::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();

        $assignmentByStatus = HirevoLead::query()
            ->selectRaw('assignment_status, COUNT(*) as aggregate')
            ->groupBy('assignment_status')
            ->pluck('aggregate', 'assignment_status')
            ->toArray();

        $crmPipeline = $this->leadPipeline->managementStageCounts();
        $totalLeadsTracked = $this->leadPipeline->totalLeads();
        $pendingConsultations = $this->leadPipeline->pendingConsultations();

        return [
            'role' => AdminRole::Admin,
            'totalUsers' => $totalUsers,
            'totalJobs' => $totalJobs,
            'totalApplications' => $totalApplications,
            'totalReferrals' => $totalReferrals,
            'acceptedReferrals' => $acceptedReferrals,
            'conversionRate' => $conversionRate,
            'revenue' => $revenue,
            'leadStages' => $leadStages,
            'assignmentByStatus' => $assignmentByStatus,
            'crmPipeline' => $crmPipeline,
            'totalLeadsTracked' => $totalLeadsTracked,
            'pendingConsultations' => $pendingConsultations,
            'totalLeads' => HirevoLead::query()->count(),
            'callsToday' => CrmCallLog::query()->whereDate('called_at', today())->count(),
            'overdueFollowUps' => CrmFollowUp::query()->where('status', FollowUpStatus::Overdue)->count(),
        ];
    }

    private function marketingMetrics(DashboardPeriod $period): array
    {
        // Marketing has unrestricted visibility — use unscoped queries
        $companySummary = $this->pipelineMetrics->companySummary(null, $period);
        $talentSummary = $this->pipelineMetrics->talentSummary(null, $period);

        $totalLeads = HirevoLead::query()->count();
        $totalConsultations = HirevoCareerConsultationRequest::query()->count();
        $unassigned = HirevoLead::query()
            ->whereNull('assigned_to')
            ->whereNull('sales_manager_id')
            ->count();
        $assigned = HirevoLead::query()
            ->whereNotNull('assigned_to')
            ->count();

        $importedStandalone = CrmStandaloneLead::query()->count();
        $poolSize = $unassigned + CrmStandaloneLead::query()->whereNull('sales_manager_id')->count();
        $assignmentRate = $totalLeads > 0 ? round(($assigned / $totalLeads) * 100, 1) : 0;

        return [
            'role' => AdminRole::Marketing,
            'totalLeads' => $totalLeads,
            'totalConsultationRequests' => $totalConsultations,
            'unassignedLeads' => $unassigned,
            'assignedLeads' => $assigned,
            'importedStandaloneLeads' => $importedStandalone,
            'poolSize' => $poolSize,
            'assignmentRate' => $assignmentRate,
            'period' => $period,
            'talentSummary' => $talentSummary,
            'companySummary' => $companySummary,
        ];
    }

    private function salesManagerMetrics(Admin $admin, DashboardPeriod $period): array
    {
        $subtreeIds = $this->subtreeAdminIds($admin);

        $base = HirevoLead::query()->where(function ($q) use ($admin, $subtreeIds) {
            $q->where('assigned_to', $admin->id);

            if ($subtreeIds->isNotEmpty()) {
                $q->orWhereIn('assigned_to', $subtreeIds)
                    ->orWhereIn('sales_manager_id', $subtreeIds);
            }

            if ($admin->role === AdminRole::SalesManager) {
                $q->orWhere('sales_manager_id', $admin->id);
            }
        });

        $leadIds = (clone $base)->pluck('id');

        $toMe = (clone $base)->where('assigned_to', $admin->id)->count();
        $toEmployees = HirevoLead::query()
            ->whereIn('assigned_to', $subtreeIds)
            ->whereNotNull('assigned_to')
            ->where('assigned_to', '!=', $admin->id)
            ->when($admin->role === AdminRole::SalesManager, fn ($q) => $q->where('sales_manager_id', $admin->id))
            ->count();
        $inProgress = (clone $base)->where('assignment_status', LeadAssignmentStatus::InProgress)->count();
        $closed = (clone $base)->where('assignment_status', LeadAssignmentStatus::Closed)->count();

        $teamAdminIds = $subtreeIds->push($admin->id);

        $mySummary = $this->pipelineMetrics->talentSummary(
            HirevoLead::query()->where('assigned_to', $admin->id),
            $period,
        );

        return [
            'role' => $admin->role,
            'period' => $period,
            'leadsAssignedToMe' => $toMe,
            'leadsWithEmployees' => $toEmployees,
            'inProgress' => $inProgress,
            'closed' => $closed,
            'totalInScope' => (clone $base)->count(),
            'myPerformance' => $mySummary,
            'callsToday' => CrmCallLog::query()
                ->whereIn('admin_id', $teamAdminIds)
                ->whereDate('called_at', today())
                ->count(),
            'overdueFollowUps' => CrmFollowUp::query()
                ->whereIn('admin_id', $teamAdminIds)
                ->whereIn('status', [FollowUpStatus::Overdue, FollowUpStatus::Pending])
                ->where('scheduled_at', '<', now())
                ->count(),
            'teamConversion' => $this->teamConversionPercent($leadIds),
        ];
    }

    private function salesEmployeeMetrics(Admin $admin, DashboardPeriod $period): array
    {
        $q = HirevoLead::query()->where('assigned_to', $admin->id);
        $leadIds = (clone $q)->pluck('id');

        $bySalesStatus = (clone $q)
            ->selectRaw('sales_status, COUNT(*) as aggregate')
            ->groupBy('sales_status')
            ->pluck('aggregate', 'sales_status')
            ->toArray();

        $recent = (clone $q)->orderByDesc('updated_at')->limit(5)->get();

        $total = (clone $q)->count();
        $converted = (clone $q)->where('sales_status', 'converted')->count();
        $conversionPct = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        $mySummary = $this->pipelineMetrics->talentSummary($q, $period);

        return [
            'role' => AdminRole::SalesEmployee,
            'period' => $period,
            'myPerformance' => $mySummary,
            'totalAssigned' => $total,
            'salesStatusBreakdown' => $bySalesStatus,
            'recentLeads' => $recent,
            'myCallsToday' => CrmCallLog::query()
                ->where('admin_id', $admin->id)
                ->whereDate('called_at', today())
                ->count(),
            'myFollowUpsToday' => CrmFollowUp::query()
                ->where('admin_id', $admin->id)
                ->whereDate('scheduled_at', today())
                ->where('status', FollowUpStatus::Pending)
                ->count(),
            'myOverdueFollowUps' => CrmFollowUp::query()
                ->where('admin_id', $admin->id)
                ->where('scheduled_at', '<', now())
                ->whereIn('status', [FollowUpStatus::Pending, FollowUpStatus::Overdue])
                ->count(),
            'conversionPercent' => $conversionPct,
        ];
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function subtreeAdminIds(Admin $admin): \Illuminate\Support\Collection
    {
        if ($admin->role === AdminRole::Asm) {
            return app(OrgHierarchyService::class)
                ->descendantIds($admin)
                ->reject(fn (int $id) => $id === $admin->id)
                ->values();
        }

        return Admin::query()->where('manager_id', $admin->id)->pluck('id');
    }

    /** @param  \Illuminate\Support\Collection<int, int>  $leadIds */
    private function teamConversionPercent($leadIds): float
    {
        if ($leadIds->isEmpty()) {
            return 0;
        }

        $total = HirevoLead::query()->whereIn('id', $leadIds)->count();
        $converted = HirevoLead::query()->whereIn('id', $leadIds)->where('sales_status', 'converted')->count();

        return $total > 0 ? round(($converted / $total) * 100, 1) : 0;
    }
}
