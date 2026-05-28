<?php

namespace App\Services;

use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoPayment;
use App\Models\Hirevo\HirevoReferralRequest;
use App\Models\Hirevo\HirevoUser;

class AdminDashboardService
{
    public function __construct(private readonly LeadPipelineService $leadPipeline)
    {
    }

    public function metrics(): array
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

        $crmPipeline = $this->leadPipeline->managementStageCounts();
        $totalLeadsTracked = $this->leadPipeline->totalLeads();
        $pendingConsultations = $this->leadPipeline->pendingConsultations();

        return [
            'totalUsers' => $totalUsers,
            'totalJobs' => $totalJobs,
            'totalApplications' => $totalApplications,
            'totalReferrals' => $totalReferrals,
            'acceptedReferrals' => $acceptedReferrals,
            'conversionRate' => $conversionRate,
            'revenue' => $revenue,
            'leadStages' => $leadStages,
            'crmPipeline' => $crmPipeline,
            'totalLeadsTracked' => $totalLeadsTracked,
            'pendingConsultations' => $pendingConsultations,
        ];
    }
}

