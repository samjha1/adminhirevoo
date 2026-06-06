<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalDashboardService;
use App\Services\Portal\PortalReportService;
use App\Support\PortalDateFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalApiController extends Controller
{
    public function __construct(
        private readonly PortalDashboardService $dashboard,
        private readonly PortalReportService $reports,
    ) {
    }

    public function summary(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->overallStats()]);
    }

    public function charts(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->chartSeries()]);
    }

    public function recentActivities(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->recentActivities()]);
    }

    public function recentCompanies(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->recentCompanies()]);
    }

    public function recentJobs(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->recentJobs()]);
    }

    public function reports(): JsonResponse
    {
        return response()->json(['data' => $this->reports->allReports()]);
    }

    public function metrics(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->metrics()]);
    }
}
