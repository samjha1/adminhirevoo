<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Services\SalesTeamActivityScopeService;
use App\Services\CompanySalesActivityService;
use App\Services\DashboardScopeService;
use App\Support\PortalDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySalesActivityController extends Controller
{
    public function __construct(
        private readonly CompanySalesActivityService $activity,
        private readonly SalesTeamActivityScopeService $scope,
        private readonly DashboardScopeService $dashboardScope,
    ) {
    }

    public function myActivity(Request $request): View
    {
        return $this->render($request, teamView: false);
    }

    public function teamActivity(Request $request): View|RedirectResponse
    {
        $admin = auth('admin')->user();

        if (! $this->scope->canViewTeamActivity($admin)) {
            return redirect()->route('admin.employers.activity.my', $request->query());
        }

        return $this->render($request, teamView: true);
    }

    private function render(Request $request, bool $teamView): View
    {
        $admin = auth('admin')->user();
        $dateFilter = $this->resolveDateFilter($request);

        $activities = $this->activity->paginate(
            $admin,
            $teamView,
            $dateFilter,
            staffId: $request->filled('staff_id') ? (int) $request->query('staff_id') : null,
            prospectId: $request->filled('prospect_id') ? (int) $request->query('prospect_id') : null,
            type: $request->filled('type') ? (string) $request->query('type') : null,
        );

        $filterStaff = $teamView ? $this->scope->filterableStaff($admin, SalesTeam::Employer) : collect();
        $filterCompanies = $this->filterCompanies($admin);

        return view($teamView ? 'admin.employers.activity.team' : 'admin.employers.activity.my', [
            'pipeline' => SalesTeam::Employer,
            'activities' => $activities,
            'dateFilter' => $dateFilter,
            'teamView' => $teamView,
            'typeLabels' => CompanySalesActivityService::typeLabels(),
            'filterStaff' => $filterStaff,
            'filterCompanies' => $filterCompanies,
            'staffSummaryToday' => $teamView ? $this->activity->staffSummaryToday($admin) : [],
            'canViewTeam' => $this->scope->canViewTeamActivity($admin),
        ]);
    }

    private function resolveDateFilter(Request $request): PortalDateFilter
    {
        $dateFilter = PortalDateFilter::fromRequest($request);

        if ($dateFilter->isActive() || $request->has('period')) {
            return $dateFilter;
        }

        $now = now();

        return new PortalDateFilter($now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today');
    }

    /** @return \Illuminate\Support\Collection<int, CrmEmployerProspect> */
    private function filterCompanies($admin)
    {
        return $this->dashboardScope
            ->companyProspectsQuery($admin)
            ->orderBy('company_name')
            ->limit(150)
            ->get(['id', 'company_name']);
    }
}
