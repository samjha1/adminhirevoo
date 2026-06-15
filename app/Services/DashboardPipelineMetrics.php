<?php

namespace App\Services;

use App\Enums\CompanyB2bPipelineStage;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoPayment;
use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Support\DashboardPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Shared pipeline KPI calculations for executive and API dashboards.
 */
class DashboardPipelineMetrics
{
    /** @var list<string> */
    public const COMPANY_WON_STAGES = [
        CompanyB2bPipelineStage::Won->value,
        CompanyB2bPipelineStage::Onboarding->value,
        CompanyB2bPipelineStage::HiringActive->value,
        CompanyB2bPipelineStage::Renewed->value,
    ];

    public function __construct(
        private readonly DashboardScopeService $scope,
    ) {
    }

    /**
     * @param  Builder<HirevoLead>|null  $base  null = unrestricted full table
     * @return array{
     *   totalLeads: int,
     *   meetings: int,
     *   closed: int,
     *   revenue: float,
     *   revenueToday: float,
     *   conversionRate: float,
     *   revenueGrowth: float,
     * }
     */
    public function talentSummary(?Builder $base, DashboardPeriod $period): array
    {
        $q = $base ? (clone $base) : HirevoLead::query();
        $leadIds = (clone $q)->pluck('id');

        $totalLeads = (clone $q)->whereBetween('created_at', [$period->start, $period->end])->count();

        $meetings = ! Schema::hasTable('crm_follow_ups') || $leadIds->isEmpty()
            ? 0
            : CrmFollowUp::query()
                ->whereIn('lead_id', $leadIds)
                ->whereBetween('scheduled_at', [$period->start, $period->end])
                ->count();

        $closed = (clone $q)
            ->where('sales_status', 'converted')
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->count();

        $revenue = $this->talentRevenueBetween($period->start, $period->end);
        $revenueToday = $this->talentRevenueBetween(today()->startOfDay(), today()->endOfDay());

        $totalInPeriod = max(1, (clone $q)->whereBetween('created_at', [$period->start, $period->end])->count());
        $conversionRate = $totalInPeriod > 0
            ? round(($closed / $totalInPeriod) * 100, 1)
            : 0.0;

        $prev = $period->previous();
        $prevRevenue = $this->talentRevenueBetween($prev->start, $prev->end);

        $calls = $this->talentCallsInPeriod($leadIds, $period);
        $activeInPeriod = (clone $q)->where(function (Builder $sub) use ($period) {
            $sub->whereBetween('created_at', [$period->start, $period->end])
                ->orWhereBetween('updated_at', [$period->start, $period->end]);
        })->count();

        return [
            'totalLeads' => $totalLeads,
            'newLeads' => $totalLeads,
            'meetings' => $meetings,
            'calls' => $calls,
            'closed' => $closed,
            'activeInPeriod' => $activeInPeriod,
            'revenue' => $revenue,
            'revenueToday' => $revenueToday,
            'conversionRate' => $conversionRate,
            'revenueGrowth' => $this->growthPercent($revenue, $prevRevenue),
            'periodLabel' => $period->label(),
        ];
    }

    /**
     * @param  Builder<CrmEmployerProspect>|null  $base
     * @return array{
     *   totalLeads: int,
     *   meetings: int,
     *   closed: int,
     *   revenue: float,
     *   revenueToday: float,
     *   conversionRate: float,
     *   revenueGrowth: float,
     * }
     */
    public function companySummary(?Builder $base, DashboardPeriod $period): array
    {
        $q = $base ? (clone $base) : CrmEmployerProspect::query();
        $prospectIds = (clone $q)->pluck('id');

        $totalLeads = (clone $q)->whereBetween('created_at', [$period->start, $period->end])->count();

        $meetings = Schema::hasTable('crm_company_meetings') && $prospectIds->isNotEmpty()
            ? CrmCompanyMeeting::query()
                ->whereIn('employer_prospect_id', $prospectIds)
                ->whereBetween('meeting_at', [$period->start, $period->end])
                ->count()
            : 0;

        $closed = (clone $q)
            ->whereIn('pipeline_stage', self::COMPANY_WON_STAGES)
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->count();

        $revenue = $this->companyRevenueBetween($period->start, $period->end, $q);

        $revenueToday = $this->companyRevenueBetween(today()->startOfDay(), today()->endOfDay(), $q);

        $totalInPeriod = max(1, $totalLeads);
        $conversionRate = $totalInPeriod > 0
            ? round(($closed / $totalInPeriod) * 100, 1)
            : 0.0;

        $prev = $period->previous();
        $prevRevenue = $this->companyRevenueBetween($prev->start, $prev->end, $q);

        $calls = $this->companyCallsInPeriod($prospectIds, $period);
        $activeInPeriod = (clone $q)->where(function (Builder $sub) use ($period) {
            $sub->whereBetween('created_at', [$period->start, $period->end])
                ->orWhereBetween('updated_at', [$period->start, $period->end]);
            if (Schema::hasColumn('crm_employer_prospects', 'last_activity_at')) {
                $sub->orWhereBetween('last_activity_at', [$period->start, $period->end]);
            }
        })->count();

        return [
            'totalLeads' => $totalLeads,
            'newLeads' => $totalLeads,
            'meetings' => $meetings,
            'calls' => $calls,
            'closed' => $closed,
            'activeInPeriod' => $activeInPeriod,
            'revenue' => $revenue,
            'revenueToday' => $revenueToday,
            'conversionRate' => $conversionRate,
            'revenueGrowth' => $this->growthPercent($revenue, $prevRevenue),
            'periodLabel' => $period->label(),
        ];
    }

    /** @return array<string, int> */
    public function talentFunnelInPeriod(?Builder $base, DashboardPeriod $period): array
    {
        $q = $base ? (clone $base) : HirevoLead::query();
        $q->where(function (Builder $sub) use ($period) {
            $sub->whereBetween('created_at', [$period->start, $period->end])
                ->orWhereBetween('updated_at', [$period->start, $period->end]);
        });

        return $q->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /** @return array<string, int> */
    public function companyFunnelInPeriod(?Builder $base, DashboardPeriod $period): array
    {
        $q = $base ? (clone $base) : CrmEmployerProspect::query();
        $q->where(function (Builder $sub) use ($period) {
            $sub->whereBetween('created_at', [$period->start, $period->end])
                ->orWhereBetween('updated_at', [$period->start, $period->end]);
            if (Schema::hasColumn('crm_employer_prospects', 'last_activity_at')) {
                $sub->orWhereBetween('last_activity_at', [$period->start, $period->end]);
            }
        });

        return $q->selectRaw('pipeline_stage, COUNT(*) as aggregate')
            ->groupBy('pipeline_stage')
            ->pluck('aggregate', 'pipeline_stage')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /** @return array<string, int> */
    public function talentFunnel(?Builder $base): array
    {
        $q = $base ? (clone $base) : HirevoLead::query();

        return $q->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /** @return array<string, int> */
    public function companyFunnel(?Builder $base): array
    {
        $q = $base ? (clone $base) : CrmEmployerProspect::query();

        return $q->selectRaw('pipeline_stage, COUNT(*) as aggregate')
            ->groupBy('pipeline_stage')
            ->pluck('aggregate', 'pipeline_stage')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /**
     * @return array{labels: list<string>, talent: list<int>, company: list<int>}
     */
    public function leadTrendSeries(DashboardPeriod $period, ?Builder $talentBase, ?Builder $companyBase): array
    {
        $labels = [];
        $talent = [];
        $company = [];
        $cursor = $period->start->copy()->startOfDay();
        $end = $period->end->copy()->endOfDay();

        while ($cursor->lte($end)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $labels[] = $cursor->format('M j');

            $tq = $talentBase ? (clone $talentBase) : HirevoLead::query();
            $talent[] = (int) (clone $tq)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $cq = $companyBase ? (clone $companyBase) : CrmEmployerProspect::query();
            $company[] = (int) (clone $cq)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $cursor->addDay();
            if (count($labels) > 62) {
                break;
            }
        }

        return compact('labels', 'talent', 'company');
    }

    /**
     * @return array{labels: list<string>, talent: list<float>, company: list<float>}
     */
    public function revenueTrendSeries(DashboardPeriod $period, ?Builder $companyBase): array
    {
        $labels = [];
        $talent = [];
        $company = [];
        $cursor = $period->start->copy()->startOfDay();
        $end = $period->end->copy()->endOfDay();

        while ($cursor->lte($end)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $labels[] = $cursor->format('M j');

            $talent[] = $this->talentRevenueBetween($dayStart, $dayEnd);

            $company[] = $this->companyRevenueBetween($dayStart, $dayEnd, $companyBase);

            $cursor->addDay();
            if (count($labels) > 62) {
                break;
            }
        }

        return compact('labels', 'talent', 'company');
    }

    /**
     * @return array{labels: list<string>, meetings: list<int>, closures: list<int>}
     */
    public function meetingsVsClosuresWeekly(
        DashboardPeriod $period,
        ?Builder $talentBase,
        ?Builder $companyBase,
    ): array {
        $labels = [];
        $meetings = [];
        $closures = [];
        $cursor = $period->start->copy()->startOfWeek();
        $end = $period->end->copy()->endOfWeek();

        while ($cursor->lte($end)) {
            $weekStart = $cursor->copy()->startOfWeek();
            $weekEnd = $cursor->copy()->endOfWeek();
            $labels[] = 'W'.$cursor->weekOfYear;

            $leadIds = $talentBase
                ? (clone $talentBase)->pluck('id')
                : HirevoLead::query()->pluck('id');
            $meetingsCount = ! Schema::hasTable('crm_follow_ups') || $leadIds->isEmpty()
                ? 0
                : CrmFollowUp::query()
                    ->whereIn('lead_id', $leadIds)
                    ->whereBetween('scheduled_at', [$weekStart, $weekEnd])
                    ->count();

            $prospectIds = $companyBase
                ? (clone $companyBase)->pluck('id')
                : CrmEmployerProspect::query()->pluck('id');
            if (Schema::hasTable('crm_company_meetings') && $prospectIds->isNotEmpty()) {
                $meetingsCount += CrmCompanyMeeting::query()
                    ->whereIn('employer_prospect_id', $prospectIds)
                    ->whereBetween('meeting_at', [$weekStart, $weekEnd])
                    ->count();
            }

            $meetings[] = $meetingsCount;

            $tq = $talentBase ? (clone $talentBase) : HirevoLead::query();
            $talentClosed = (int) (clone $tq)
                ->where('sales_status', 'converted')
                ->whereBetween('updated_at', [$weekStart, $weekEnd])
                ->count();

            $cq = $companyBase ? (clone $companyBase) : CrmEmployerProspect::query();
            $companyClosed = (int) (clone $cq)
                ->whereIn('pipeline_stage', self::COMPANY_WON_STAGES)
                ->whereBetween('updated_at', [$weekStart, $weekEnd])
                ->count();

            $closures[] = $talentClosed + $companyClosed;
            $cursor->addWeek();
            if (count($labels) > 16) {
                break;
            }
        }

        return compact('labels', 'meetings', 'closures');
    }

    /**
     * @param  Builder<HirevoLead>|null  $talentBase
     * @param  Builder<CrmEmployerProspect>|null  $companyBase
     */
    public function memberTalentMetrics(
        Admin $member,
        DashboardPeriod $period,
        ?Builder $talentBase,
    ): array {
        $q = $talentBase ? (clone $talentBase) : HirevoLead::query();
        $q->where('assigned_to', $member->id);
        $leadIds = (clone $q)->pluck('id');

        $leads = (clone $q)->whereBetween('created_at', [$period->start, $period->end])->count();
        $meetings = ! Schema::hasTable('crm_follow_ups') || $leadIds->isEmpty()
            ? 0
            : CrmFollowUp::query()
                ->whereIn('lead_id', $leadIds)
                ->where('admin_id', $member->id)
                ->whereBetween('scheduled_at', [$period->start, $period->end])
                ->count();
        $closed = (clone $q)
            ->where('sales_status', 'converted')
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->count();
        $revenue = 0.0;

        return compact('leads', 'meetings', 'closed', 'revenue');
    }

    /**
     * @param  Builder<CrmEmployerProspect>|null  $companyBase
     */
    public function memberCompanyMetrics(
        Admin $member,
        DashboardPeriod $period,
        ?Builder $companyBase,
    ): array {
        $q = $companyBase ? (clone $companyBase) : CrmEmployerProspect::query();
        $q->where('assigned_to', $member->id);
        $prospectIds = (clone $q)->pluck('id');

        $leads = (clone $q)->whereBetween('created_at', [$period->start, $period->end])->count();
        $meetings = Schema::hasTable('crm_company_meetings') && $prospectIds->isNotEmpty()
            ? CrmCompanyMeeting::query()
                ->whereIn('employer_prospect_id', $prospectIds)
                ->whereBetween('meeting_at', [$period->start, $period->end])
                ->count()
            : 0;
        $closed = (clone $q)
            ->whereIn('pipeline_stage', self::COMPANY_WON_STAGES)
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->count();
        $revenue = $this->companyRevenueBetween($period->start, $period->end, $q);

        return compact('leads', 'meetings', 'closed', 'revenue');
    }

    /**
     * @param  Builder<CrmEmployerProspect>|null  $companyBase
     */
    public function companyRevenueBetween(\Carbon\Carbon $start, \Carbon\Carbon $end, ?Builder $companyBase = null): float
    {
        if (! Schema::hasTable('payments')) {
            return 0.0;
        }

        $query = HirevoPayment::query()
            ->where('type', HirevoPayment::TYPE_EMPLOYER_SUBSCRIPTION)
            ->where('status', HirevoPayment::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end]);

        if ($companyBase !== null) {
            $userIds = (clone $companyBase)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->unique()
                ->filter()
                ->values();

            if ($userIds->isEmpty()) {
                return 0.0;
            }

            $query->whereIn('user_id', $userIds);
        }

        return (float) $query->sum('amount');
    }

    private function talentRevenueBetween(\Carbon\Carbon $start, \Carbon\Carbon $end): float
    {
        if (! Schema::hasTable('payments')) {
            return 0.0;
        }

        return (float) HirevoPayment::query()
            ->where('status', HirevoPayment::STATUS_COMPLETED)
            ->where('type', '!=', HirevoPayment::TYPE_EMPLOYER_SUBSCRIPTION)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    private function growthPercent(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** @param  \Illuminate\Support\Collection<int, int>|list<int>  $leadIds */
    private function talentCallsInPeriod($leadIds, DashboardPeriod $period): int
    {
        if (! Schema::hasTable('crm_call_logs') || $leadIds->isEmpty()) {
            return 0;
        }

        $query = CrmCallLog::query()->whereBetween('called_at', [$period->start, $period->end]);
        if (Schema::hasColumn('crm_call_logs', 'lead_id')) {
            $query->whereIn('lead_id', $leadIds);
        }

        return (int) $query->count();
    }

    /** @param  \Illuminate\Support\Collection<int, int>|list<int>  $prospectIds */
    private function companyCallsInPeriod($prospectIds, DashboardPeriod $period): int
    {
        if (! Schema::hasTable('crm_call_logs') || $prospectIds->isEmpty()) {
            return 0;
        }

        if (! Schema::hasColumn('crm_call_logs', 'employer_prospect_id')) {
            return 0;
        }

        return (int) CrmCallLog::query()
            ->whereIn('employer_prospect_id', $prospectIds)
            ->whereBetween('called_at', [$period->start, $period->end])
            ->count();
    }
}
