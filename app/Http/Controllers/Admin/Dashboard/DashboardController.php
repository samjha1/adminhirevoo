<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;
use App\Services\Portal\PortalDashboardService;
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
        private readonly PortalDashboardService $portal,
    ) {
    }

    public function index(Request $request): View
    {
        $admin = auth('admin')->user();
        $period = DashboardPeriod::fromRequest($request);

        if ($admin->role->isPlatformAdmin() || $admin->role === AdminRole::SuperAdmin) {
            if ($admin->canPermission('analytics.view_executive') || $admin->role->isPlatformAdmin()) {
                $data = $this->executive->metricsFor($admin, $period);
                if ($admin->canPermission('portal.dashboard.view')) {
                    $data['portalStats'] = $this->portal->overallStats();
                }

                return view('admin.dashboard.executive', $data);
            }
        }

        if ($admin->role === AdminRole::Marketing) {
            return view('admin.dashboard.index', $this->roleDashboard->metricsFor($admin, $period));
        }

        if (in_array($admin->role, [AdminRole::Asm, AdminRole::SalesManager, AdminRole::SalesEmployee], true)) {
            return view('admin.dashboard.scoped', $this->scoped->metricsFor($admin, $period));
        }

        return view('admin.dashboard.index', $this->roleDashboard->metricsFor($admin, $period));
    }
}
