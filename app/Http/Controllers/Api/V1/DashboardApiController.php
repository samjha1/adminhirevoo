<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\ExecutiveDashboardService;
use App\Services\ScopedDashboardService;
use App\Support\DashboardPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function __construct(
        private readonly ExecutiveDashboardService $executive,
        private readonly ScopedDashboardService $scoped,
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->resolve($request)['summary'] ?? []]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $data = $this->resolve($request);

        return response()->json([
            'data' => [
                'summary' => $data['summary'] ?? [],
                'trends' => $data['trends']['revenueTrend'] ?? [],
            ],
        ]);
    }

    public function leads(Request $request): JsonResponse
    {
        $data = $this->resolve($request);

        return response()->json(['data' => $data['trends']['leadTrend'] ?? []]);
    }

    public function funnel(Request $request): JsonResponse
    {
        $data = $this->resolve($request);

        return response()->json(['data' => $data['funnel'] ?? []]);
    }

    public function teamPerformance(Request $request): JsonResponse
    {
        $data = $this->resolve($request);

        return response()->json(['data' => $data['teamTables']['byTeam'] ?? []]);
    }

    public function managerPerformance(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin->role->hasUnrestrictedLeadVisibility() && $admin->role !== AdminRole::SalesManager) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $this->resolve($request);

        return response()->json(['data' => $data['teamTables']['byManager'] ?? []]);
    }

    public function employeePerformance(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin->role->hasUnrestrictedLeadVisibility()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $this->resolve($request);

        return response()->json(['data' => $data['teamTables']['byEmployee'] ?? $data['teamMembers'] ?? []]);
    }

    public function recentActivities(Request $request): JsonResponse
    {
        $data = $this->resolve($request);
        $activities = $data['recentActivities'] ?? collect();

        return response()->json([
            'data' => $activities->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'admin' => $log->admin?->name,
                'created_at' => $log->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function exportExcel(Request $request): JsonResponse
    {
        $data = $this->resolve($request);
        $rows = [];
        foreach (['totalLeads', 'meetings', 'closed', 'revenue'] as $key) {
            $rows[] = [
                'metric' => $key,
                'talent' => $data['summary']['talent'][$key] ?? 0,
                'company' => $data['summary']['company'][$key] ?? 0,
                'combined' => $data['summary']['combined'][$key] ?? 0,
            ];
        }

        return response()->json(['data' => $rows, 'period' => DashboardPeriod::fromRequest($request)->label()]);
    }

    public function exportPdf(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Open GET /dashboard/export?format=pdf in the admin panel for a printable HTML export.',
            'data' => $this->resolve($request)['summary'] ?? [],
        ]);
    }

    /** @return array<string, mixed> */
    private function resolve(Request $request): array
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $period = DashboardPeriod::fromRequest($request);

        if ($admin->role->isPlatformAdmin() || $admin->role === AdminRole::SuperAdmin) {
            return $this->executive->metricsFor($admin, $period);
        }

        return $this->scoped->metricsFor($admin, $period);
    }
}
