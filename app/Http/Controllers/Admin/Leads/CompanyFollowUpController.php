<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Services\CompanyMeetingService;
use App\Services\EmployerProspectVisibilityService;
use App\Support\CompanyScheduleItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CompanyFollowUpController extends Controller
{
    public function __construct(
        private readonly EmployerProspectVisibilityService $visibility,
        private readonly CompanyMeetingService $meetingService,
    ) {
    }

    public function index(Request $request): View
    {
        $followUps = $this->followUpQuery($request)
            ->whereIn('status', [FollowUpStatus::Pending, FollowUpStatus::Overdue])
            ->orderBy('scheduled_at')
            ->get();

        $meetings = $this->openMeetings($this->meetingQuery($request))
            ->orderBy('meeting_at')
            ->get();

        $items = CompanyScheduleItem::merge($followUps, $meetings);
        $paginated = $this->paginateItems($items, 20);

        return view('admin.employers.follow-ups.index', array_merge(
            $this->statsPayload($request),
            [
                'scheduleItems' => $paginated,
                'overdueItems' => collect(),
                'filter' => 'all',
                'pipeline' => SalesTeam::Employer,
            ],
        ));
    }

    public function today(Request $request): View
    {
        $fuBase = $this->followUpQuery($request);
        $mtBase = $this->meetingQuery($request);

        $overdueFollowUps = (clone $fuBase)
            ->whereIn('status', [FollowUpStatus::Pending, FollowUpStatus::Overdue])
            ->where('scheduled_at', '<', today()->startOfDay())
            ->orderBy('scheduled_at')
            ->limit(30)
            ->get();

        $overdueMeetings = $this->openMeetings(clone $mtBase)
            ->where('meeting_at', '<', today()->startOfDay())
            ->orderBy('meeting_at')
            ->limit(30)
            ->get();

        $todayFollowUps = (clone $fuBase)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [FollowUpStatus::Pending, FollowUpStatus::Overdue, FollowUpStatus::Completed])
            ->orderBy('scheduled_at')
            ->get();

        $todayMeetings = (clone $mtBase)
            ->whereDate('meeting_at', today())
            ->orderBy('meeting_at')
            ->get();

        $overdueItems = CompanyScheduleItem::merge($overdueFollowUps, $overdueMeetings, forceOverdue: true);
        $todayItems = CompanyScheduleItem::merge($todayFollowUps, $todayMeetings);

        return view('admin.employers.follow-ups.index', array_merge(
            $this->statsPayload($request),
            [
                'scheduleItems' => $todayItems,
                'overdueItems' => $overdueItems,
                'filter' => 'today',
                'pipeline' => SalesTeam::Employer,
            ],
        ));
    }

    public function completeMeeting(Request $request, CrmCompanyMeeting $meeting): RedirectResponse
    {
        $prospect = CrmEmployerProspect::query()->findOrFail($meeting->employer_prospect_id);
        abort_unless($this->visibility->canView($request->user('admin'), $prospect), 403);
        abort_unless($request->user('admin')->canPermission('leads.manage_followups'), 403);

        $this->meetingService->complete($meeting, $request->user('admin'));

        return back()->with('success', 'Meeting marked complete.');
    }

    /** @return array<string, int> */
    private function statsPayload(Request $request): array
    {
        $fuBase = $this->followUpQuery($request);
        $mtBase = $this->meetingQuery($request);
        $openFu = [FollowUpStatus::Pending, FollowUpStatus::Overdue];

        $fuToday = (clone $fuBase)->whereDate('scheduled_at', today())->whereIn('status', $openFu)->count();
        $mtToday = $this->openMeetings(clone $mtBase)->whereDate('meeting_at', today())->count();

        $fuOverdue = (clone $fuBase)->whereIn('status', $openFu)->where('scheduled_at', '<', now())->count();
        $mtOverdue = $this->openMeetings(clone $mtBase)->where('meeting_at', '<', now())->count();

        $fuUpcoming = (clone $fuBase)->whereIn('status', $openFu)->where('scheduled_at', '>=', now())->count();
        $mtUpcoming = $this->openMeetings(clone $mtBase)->where('meeting_at', '>=', now())->count();

        return [
            'stats' => [
                'today' => $fuToday + $mtToday,
                'overdue' => $fuOverdue + $mtOverdue,
                'upcoming' => $fuUpcoming + $mtUpcoming,
                'total_open' => (clone $fuBase)->whereIn('status', $openFu)->count()
                    + $this->openMeetings(clone $mtBase)->count(),
                'meetings_today' => $mtToday,
            ],
        ];
    }

    /** @param  Collection<int, CompanyScheduleItem>  $items */
    private function paginateItems(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) request()->get('page', 1));
        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /** @return \Illuminate\Database\Eloquent\Builder<CrmFollowUp> */
    private function followUpQuery(Request $request)
    {
        $admin = $request->user('admin');

        $query = CrmFollowUp::query()
            ->with(['admin', 'employerProspect'])
            ->whereNotNull('employer_prospect_id')
            ->where('admin_id', $admin->id);

        $query->whereIn('employer_prospect_id', $this->visibleProspectIds($admin));

        return $query;
    }

    /** @return \Illuminate\Database\Eloquent\Builder<CrmCompanyMeeting> */
    private function meetingQuery(Request $request)
    {
        $admin = $request->user('admin');

        $query = CrmCompanyMeeting::query()
            ->with(['admin', 'prospect'])
            ->where('admin_id', $admin->id);

        $query->whereIn('employer_prospect_id', $this->visibleProspectIds($admin));

        return $query;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<CrmCompanyMeeting>  $query
     * @return \Illuminate\Database\Eloquent\Builder<CrmCompanyMeeting>
     */
    private function openMeetings($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('outcome')->orWhere('outcome', '');
        });
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function visibleProspectIds($admin)
    {
        $ids = CrmEmployerProspect::query()->select('id');
        $this->visibility->restrictVisible($ids, $admin);

        return $ids->pluck('id');
    }
}
