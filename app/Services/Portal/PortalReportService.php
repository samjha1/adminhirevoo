<?php

namespace App\Services\Portal;

use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoUser;
use Carbon\Carbon;

class PortalReportService
{
    public function __construct(
        private readonly PortalDashboardService $dashboard,
    ) {
    }

    /** @return array<string, mixed> */
    public function allReports(): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'companies' => [
                'total' => $this->companiesQuery()->count(),
                'today' => $this->companiesQuery()->where('created_at', '>=', $today)->count(),
                'monthly' => $this->companiesQuery()->where('created_at', '>=', $monthStart)->count(),
            ],
            'jobs' => [
                'total' => HirevoEmployerJob::query()->count(),
                'today' => HirevoEmployerJob::query()->where('created_at', '>=', $today)->count(),
                'monthly' => HirevoEmployerJob::query()->where('created_at', '>=', $monthStart)->count(),
                'active' => HirevoEmployerJob::query()->where('status', 'active')->count(),
                'expired' => HirevoEmployerJob::query()->where('status', 'closed')->count(),
                'draft' => HirevoEmployerJob::query()->where('status', 'draft')->count(),
            ],
            'candidates' => [
                'total' => $this->candidatesQuery()->count(),
                'today' => $this->candidatesQuery()->where('created_at', '>=', $today)->count(),
                'monthly' => $this->candidatesQuery()->where('created_at', '>=', $monthStart)->count(),
            ],
            'applications' => [
                'total' => HirevoEmployerJobApplication::query()->count(),
                'today' => HirevoEmployerJobApplication::query()->where('created_at', '>=', $today)->count(),
                'weekly' => HirevoEmployerJobApplication::query()->where('created_at', '>=', $weekStart)->count(),
                'monthly' => HirevoEmployerJobApplication::query()->where('created_at', '>=', $monthStart)->count(),
            ],
            'generatedAt' => $now->toIso8601String(),
        ];
    }

    /** @return list<array{section: string, metric: string, value: int|float|string}> */
    public function flatRows(): array
    {
        $reports = $this->allReports();
        $rows = [];

        foreach ($reports as $section => $metrics) {
            if ($section === 'generatedAt') {
                continue;
            }
            foreach ($metrics as $metric => $value) {
                $rows[] = [
                    'section' => ucfirst($section),
                    'metric' => str_replace('_', ' ', ucfirst($metric)),
                    'value' => $value,
                ];
            }
        }

        return $rows;
    }

    /** @return list<array{date: string, companies: int, jobs: int, candidates: int, applications: int}> */
    public function dailyBreakdown(Carbon $start, Carbon $end): array
    {
        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->endOfDay();

        while ($cursor->lte($endDay)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();

            $rows[] = [
                'date' => $cursor->toDateString(),
                'companies' => $this->companiesQuery()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'jobs' => HirevoEmployerJob::query()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'candidates' => $this->candidatesQuery()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'applications' => HirevoEmployerJobApplication::query()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            ];

            $cursor->addDay();
        }

        return $rows;
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
