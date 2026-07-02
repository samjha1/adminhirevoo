<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoLead;
use App\Services\DashboardScopeService;
use App\Services\SalesTeamActivityScopeService;
use App\Services\TalentSalesActivityService;
use App\Support\PortalDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TalentSalesActivityController extends Controller
{
    public function __construct(
        private readonly TalentSalesActivityService $activity,
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
            return redirect()->route('admin.leads.activity.my', $request->query());
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
            leadId: $request->filled('lead_id') ? (int) $request->query('lead_id') : null,
            type: $request->filled('type') ? (string) $request->query('type') : null,
        );

        return view($teamView ? 'admin.leads.activity.team' : 'admin.leads.activity.my', [
            'pipeline' => SalesTeam::Candidate,
            'activities' => $activities,
            'dateFilter' => $dateFilter,
            'teamView' => $teamView,
            'typeLabels' => TalentSalesActivityService::typeLabels(),
            'filterStaff' => $teamView ? $this->scope->filterableStaff($admin, SalesTeam::Candidate) : collect(),
            'filterLeads' => $this->filterLeads($admin),
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

    /** @return \Illuminate\Support\Collection<int, HirevoLead> */
    private function filterLeads($admin)
    {
        return $this->dashboardScope
            ->talentLeadsQuery($admin)
            ->with('candidate:id,name')
            ->orderByDesc('updated_at')
            ->limit(150)
            ->get(['id', 'candidate_id']);
    }
}
