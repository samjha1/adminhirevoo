<?php

namespace App\Services\Portal;

use App\Models\AuditLog;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PortalDashboardService
{
    /** @return array<string, int> */
    public function overallStats(): array
    {
        $today = now()->startOfDay();

        return [
            'totalCompanies' => $this->companiesQuery()->count(),
            'totalJobs' => HirevoEmployerJob::query()->count(),
            'totalCandidates' => $this->candidatesQuery()->count(),
            'totalApplications' => HirevoEmployerJobApplication::query()->count(),
            'companiesToday' => $this->companiesQuery()->where('created_at', '>=', $today)->count(),
            'jobsToday' => HirevoEmployerJob::query()->where('created_at', '>=', $today)->count(),
            'candidatesToday' => $this->candidatesQuery()->where('created_at', '>=', $today)->count(),
            'applicationsToday' => HirevoEmployerJobApplication::query()->where('created_at', '>=', $today)->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function chartSeries(int $days = 30): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $labels = [];
        $companies = [];
        $jobs = [];
        $candidates = [];
        $applications = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            $labels[] = $date->format('M j');

            $companies[] = $this->companiesQuery()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
            $jobs[] = HirevoEmployerJob::query()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
            $candidates[] = $this->candidatesQuery()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
            $applications[] = HirevoEmployerJobApplication::query()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
        }

        return compact('labels', 'companies', 'jobs', 'candidates', 'applications');
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public function recentActivities(int $limit = 10)
    {
        $companyEvents = $this->companiesQuery()
            ->with('referrerProfile')
            ->select(['id', 'name', 'email', 'created_at'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'type' => 'company_registered',
                'label' => 'Company registered',
                'title' => $u->referrerProfile?->company_name ?? $u->name,
                'subtitle' => $u->email,
                'at' => $u->created_at,
            ]);

        $candidateEvents = $this->candidatesQuery()
            ->select(['id', 'name', 'email', 'created_at'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'type' => 'candidate_registered',
                'label' => 'Candidate registered',
                'title' => $u->name,
                'subtitle' => $u->email,
                'at' => $u->created_at,
            ]);

        $jobEvents = HirevoEmployerJob::query()
            ->with('employer.referrerProfile')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($j) => [
                'type' => 'job_posted',
                'label' => 'Job posted',
                'title' => $j->title,
                'subtitle' => $j->employer?->referrerProfile?->company_name ?? $j->company_name ?? '—',
                'at' => $j->created_at,
            ]);

        $applicationEvents = HirevoEmployerJobApplication::query()
            ->with(['candidate', 'job'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($a) => [
                'type' => 'application_submitted',
                'label' => 'Application submitted',
                'title' => $a->candidate?->name ?? 'Candidate',
                'subtitle' => $a->job?->title ?? 'Job',
                'at' => $a->created_at,
            ]);

        return $companyEvents
            ->concat($candidateEvents)
            ->concat($jobEvents)
            ->concat($applicationEvents)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, HirevoUser> */
    public function recentCompanies(int $limit = 8)
    {
        return $this->companiesQuery()
            ->with('referrerProfile')
            ->withCount('employerJobs')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, HirevoEmployerJob> */
    public function recentJobs(int $limit = 8)
    {
        return HirevoEmployerJob::query()
            ->with(['employer.referrerProfile'])
            ->withCount('applications')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /** @return \Illuminate\Support\Collection<int, AuditLog> */
    public function adminAuditActivities(int $limit = 8)
    {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        return AuditLog::query()
            ->with('admin')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /** @return array<string, mixed> */
    public function metrics(): array
    {
        return [
            'stats' => $this->overallStats(),
            'charts' => $this->chartSeries(),
            'recentActivities' => $this->recentActivities(),
            'recentCompanies' => $this->recentCompanies(),
            'recentJobs' => $this->recentJobs(),
            'adminActivities' => $this->adminAuditActivities(),
        ];
    }

    private function companiesQuery()
    {
        return HirevoUser::query()
            ->where('role', 'referrer')
            ->whereHas('referrerProfile');
    }

    private function candidatesQuery()
    {
        return HirevoUser::query()->where('role', 'candidate');
    }
}
