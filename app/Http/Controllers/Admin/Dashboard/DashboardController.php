<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;
use App\Services\RoleDashboardService;
use App\Services\ScopedDashboardService;
use App\Support\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ExecutiveDashboardService $executive,
        private readonly RoleDashboardService $roleDashboard,
        private readonly ScopedDashboardService $scoped,
    ) {
    }

    public function index(Request $request): View
    {
        $admin = auth('admin')->user();
        $period = DashboardPeriod::fromRequest($request);

        if ($admin->role->isPlatformAdmin() || $admin->role === AdminRole::SuperAdmin) {
            if ($admin->canPermission('analytics.view_executive') || $admin->role->isPlatformAdmin()) {
                return view('admin.dashboard.executive', $this->executive->metricsFor($admin, $period));
            }
        }

        if ($admin->role === AdminRole::Marketing) {
            return view('admin.dashboard.index', $this->roleDashboard->metricsFor($admin, $period));
        }

        if (in_array($admin->role, [AdminRole::SalesManager, AdminRole::SalesEmployee], true)) {
            return view('admin.dashboard.scoped', $this->scoped->metricsFor($admin, $period));
        }

        return view('admin.dashboard.index', $this->roleDashboard->metricsFor($admin, $period));
    }
}
