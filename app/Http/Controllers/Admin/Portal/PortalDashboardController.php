<?php

namespace App\Http\Controllers\Admin\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalDashboardService;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __construct(
        private readonly PortalDashboardService $dashboard,
    ) {
    }

    public function index(): View
    {
        return view('admin.portal.dashboard', $this->dashboard->metrics());
    }
}
