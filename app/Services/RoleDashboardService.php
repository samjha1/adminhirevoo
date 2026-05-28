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

class RoleDashboardService
{
    public function __construct(
        private readonly LeadPipelineService $leadPipeline,
    ) {
    }

    public function metricsFor(Admin $admin): array
    {
        return match ($admin->role) {
            AdminRole::Admin => $this->adminMetrics(),
            AdminRole::Marketing => $this->marketingMetrics(),
            AdminRole::SalesManager => $this->salesManagerMetrics($admin),
            AdminRole::SalesEmployee => $this->salesEmployeeMetrics($admin),
        };
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
        ];
    }

    private function marketingMetrics(): array
    {
        $totalLeads = HirevoLead::query()->count();
        $totalConsultations = HirevoCareerConsultationRequest::query()->count();
        $unassigned = HirevoLead::query()
            ->whereNull('assigned_to')
            ->whereNull('sales_manager_id')
            ->count();
        $assigned = HirevoLead::query()
            ->whereNotNull('assigned_to')
            ->count();

        return [
            'role' => AdminRole::Marketing,
            'totalLeads' => $totalLeads,
            'totalConsultationRequests' => $totalConsultations,
            'unassignedLeads' => $unassigned,
            'assignedLeads' => $assigned,
        ];
    }

    private function salesManagerMetrics(Admin $admin): array
    {
        $base = HirevoLead::query()->where(function ($q) use ($admin) {
            $q->where('sales_manager_id', $admin->id)
                ->orWhere('assigned_to', $admin->id);
        });

        $toMe = (clone $base)->where('assigned_to', $admin->id)->count();
        $toEmployees = HirevoLead::query()
            ->where('sales_manager_id', $admin->id)
            ->whereNotNull('assigned_to')
            ->where('assigned_to', '!=', $admin->id)
            ->count();
        $inProgress = (clone $base)->where('assignment_status', LeadAssignmentStatus::InProgress)->count();
        $closed = (clone $base)->where('assignment_status', LeadAssignmentStatus::Closed)->count();

        return [
            'role' => AdminRole::SalesManager,
            'leadsAssignedToMe' => $toMe,
            'leadsWithEmployees' => $toEmployees,
            'inProgress' => $inProgress,
            'closed' => $closed,
            'totalInScope' => (clone $base)->count(),
        ];
    }

    private function salesEmployeeMetrics(Admin $admin): array
    {
        $q = HirevoLead::query()->where('assigned_to', $admin->id);

        $bySalesStatus = (clone $q)
            ->selectRaw('sales_status, COUNT(*) as aggregate')
            ->groupBy('sales_status')
            ->pluck('aggregate', 'sales_status')
            ->toArray();

        $recent = (clone $q)->orderByDesc('updated_at')->limit(5)->get();

        return [
            'role' => AdminRole::SalesEmployee,
            'totalAssigned' => (clone $q)->count(),
            'salesStatusBreakdown' => $bySalesStatus,
            'recentLeads' => $recent,
        ];
    }
}
