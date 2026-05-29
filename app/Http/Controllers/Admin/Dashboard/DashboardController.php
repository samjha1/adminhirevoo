<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Services\CompanyB2bDashboardService;
use App\Services\RoleDashboardService;
use App\Services\SalesTeamService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly RoleDashboardService $talentDashboard,
        private readonly CompanyB2bDashboardService $companyDashboard,
        private readonly SalesTeamService $teams,
    ) {
    }

    public function index(): View
    {
        $admin = auth('admin')->user();

        if ($this->teams->teamFor($admin) === SalesTeam::Employer) {
            return view('admin.dashboard.company-b2b', $this->companyDashboard->metricsFor($admin));
        }

        return view('admin.dashboard.index', $this->talentDashboard->metricsFor($admin));
    }
}
