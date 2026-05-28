<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\RoleDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly RoleDashboardService $dashboardService)
    {
    }

    public function index(): View
    {
        $admin = auth('admin')->user();

        return view('admin.dashboard.index', $this->dashboardService->metricsFor($admin));
    }
}
