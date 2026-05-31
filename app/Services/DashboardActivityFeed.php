<?php

namespace App\Services;

use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Support\DashboardPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardActivityFeed
{
    public function __construct(
        private readonly DashboardScopeService $scope,
    ) {
    }

    /**
     * @return list<array{type: string, label: string, detail: string, at: string, url: string|null}>
     */
    public function forPeriod(Admin $admin, DashboardPeriod $period, SalesTeam $pipeline, int $limit = 10): array
    {
        if ($pipeline === SalesTeam::Employer) {
            return $this->companyFeed($admin, $period, $limit);
        }

        return $this->talentFeed($admin, $period, $limit);
    }

    /**
     * @return list<array{name: string, stage: string, activity: string, at: string, url: string}>
     */
    public function myRecordsInPeriod(Admin $admin, DashboardPeriod $period, SalesTeam $pipeline, int $limit = 15): array
    {
        if ($pipeline === SalesTeam::Employer) {
            return $this->myCompanyProspectsInPeriod($admin, $period, $limit);
        }

        return $this->myTalentLeadsInPeriod($admin, $period, $limit);
    }

    /** @return list<array{type: string, label: string, detail: string, at: string, url: string|null}> */
    private function talentFeed(Admin $admin, DashboardPeriod $period, int $limit): array
    {
        $items = [];
        $base = $this->scope->talentLeadsQuery($admin);
        $leadIds = (clone $base)->pluck('id');

        if (Schema::hasTable('crm_call_logs') && $leadIds->isNotEmpty() && Schema::hasColumn('crm_call_logs', 'lead_id')) {
            $calls = CrmCallLog::query()
                ->with('admin')
                ->whereIn('lead_id', $leadIds)
                ->whereBetween('called_at', [$period->start, $period->end])
                ->orderByDesc('called_at')
                ->limit($limit)
                ->get();

            foreach ($calls as $call) {
                $lead = HirevoLead::query()->find($call->lead_id);
                $items[] = [
                    'type' => 'call',
                    'label' => 'Call logged',
                    'detail' => ($lead ? 'Lead #'.$lead->id : 'Lead').' · '.($call->admin?->name ?? 'Staff'),
                    'at' => $call->called_at?->toIso8601String() ?? '',
                    'url' => $lead ? route('admin.leads.show', $lead) : null,
                ];
            }
        }

        if (Schema::hasTable('crm_follow_ups') && $leadIds->isNotEmpty()) {
            $followUps = CrmFollowUp::query()
                ->whereIn('lead_id', $leadIds)
                ->whereBetween('scheduled_at', [$period->start, $period->end])
                ->orderByDesc('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($followUps as $fu) {
                $lead = HirevoLead::query()->find($fu->lead_id);
                $items[] = [
                    'type' => 'follow_up',
                    'label' => 'Follow-up scheduled',
                    'detail' => $lead ? 'Lead #'.$lead->id.' · '.$fu->status?->value : 'Follow-up',
                    'at' => $fu->scheduled_at?->toIso8601String() ?? '',
                    'url' => $lead ? route('admin.leads.show', $lead) : null,
                ];
            }
        }

        $converted = (clone $base)
            ->where('sales_status', 'converted')
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        foreach ($converted as $lead) {
            $items[] = [
                'type' => 'closed',
                'label' => 'Lead converted',
                'detail' => 'Lead #'.$lead->id.' · '.$lead->status,
                'at' => $lead->updated_at?->toIso8601String() ?? '',
                'url' => route('admin.leads.show', $lead),
            ];
        }

        return $this->sortAndLimit($items, $limit);
    }

    /** @return list<array{type: string, label: string, detail: string, at: string, url: string|null}> */
    private function companyFeed(Admin $admin, DashboardPeriod $period, int $limit): array
    {
        $items = [];
        $base = $this->scope->companyProspectsQuery($admin);
        $prospectIds = (clone $base)->pluck('id');

        if (Schema::hasTable('crm_call_logs') && $prospectIds->isNotEmpty()
            && Schema::hasColumn('crm_call_logs', 'employer_prospect_id')) {
            $calls = CrmCallLog::query()
                ->whereIn('employer_prospect_id', $prospectIds)
                ->whereBetween('called_at', [$period->start, $period->end])
                ->orderByDesc('called_at')
                ->limit($limit)
                ->get();

            foreach ($calls as $call) {
                $prospect = CrmEmployerProspect::query()->find($call->employer_prospect_id);
                $items[] = [
                    'type' => 'call',
                    'label' => 'Call logged',
                    'detail' => $prospect?->company_name ?? 'Company',
                    'at' => $call->called_at?->toIso8601String() ?? '',
                    'url' => $prospect ? route('admin.employers.pipeline.show', $prospect) : null,
                ];
            }
        }

        if (Schema::hasTable('crm_company_meetings') && $prospectIds->isNotEmpty()) {
            $meetings = CrmCompanyMeeting::query()
                ->whereIn('employer_prospect_id', $prospectIds)
                ->whereBetween('meeting_at', [$period->start, $period->end])
                ->orderByDesc('meeting_at')
                ->limit($limit)
                ->get();

            foreach ($meetings as $meeting) {
                $prospect = CrmEmployerProspect::query()->find($meeting->employer_prospect_id);
                $items[] = [
                    'type' => 'meeting',
                    'label' => 'Meeting',
                    'detail' => $prospect?->company_name ?? 'Company',
                    'at' => $meeting->meeting_at?->toIso8601String() ?? '',
                    'url' => $prospect ? route('admin.employers.pipeline.show', $prospect) : null,
                ];
            }
        }

        $active = (clone $base)->where(function (Builder $sub) use ($period) {
            $sub->whereBetween('updated_at', [$period->start, $period->end]);
            if (Schema::hasColumn('crm_employer_prospects', 'last_activity_at')) {
                $sub->orWhereBetween('last_activity_at', [$period->start, $period->end]);
            }
        })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        foreach ($active as $prospect) {
            $items[] = [
                'type' => 'prospect',
                'label' => 'Company activity',
                'detail' => $prospect->company_name.' · '.str_replace('_', ' ', $prospect->pipeline_stage ?? ''),
                'at' => $prospect->updated_at?->toIso8601String() ?? '',
                'url' => route('admin.employers.pipeline.show', $prospect),
            ];
        }

        return $this->sortAndLimit($items, $limit);
    }

    /** @return list<array{name: string, stage: string, activity: string, at: string, url: string}> */
    private function myTalentLeadsInPeriod(Admin $admin, DashboardPeriod $period, int $limit): array
    {
        $q = $this->scope->talentLeadsQuery($admin);
        $q->where('assigned_to', $admin->id);
        $q->where(function (Builder $sub) use ($period) {
            $sub->whereBetween('created_at', [$period->start, $period->end])
                ->orWhereBetween('updated_at', [$period->start, $period->end]);
        });

        $rows = [];
        $leads = $q->with('candidate')->orderByDesc('updated_at')->limit($limit)->get();
        foreach ($leads as $lead) {
            $name = $lead->candidate?->name ?? 'Lead #'.$lead->id;
            $rows[] = [
                'name' => $name,
                'stage' => (string) ($lead->sales_status?->value ?? $lead->status ?? '—'),
                'activity' => $lead->updated_at?->diffForHumans(short: true) ?? '—',
                'at' => $lead->updated_at?->toIso8601String() ?? '',
                'url' => route('admin.leads.show', $lead),
            ];
        }

        return $rows;
    }

    /** @return list<array{name: string, stage: string, activity: string, at: string, url: string}> */
    private function myCompanyProspectsInPeriod(Admin $admin, DashboardPeriod $period, int $limit): array
    {
        $q = $this->scope->companyProspectsQuery($admin);
        $q->where('assigned_to', $admin->id);
        $q->where(function (Builder $sub) use ($period) {
            $sub->whereBetween('created_at', [$period->start, $period->end])
                ->orWhereBetween('updated_at', [$period->start, $period->end]);
        });

        $rows = [];
        foreach ($q->orderByDesc('updated_at')->limit($limit)->get() as $prospect) {
            $rows[] = [
                'name' => $prospect->company_name,
                'stage' => str_replace('_', ' ', $prospect->pipeline_stage ?? '—'),
                'activity' => $prospect->updated_at?->diffForHumans(short: true) ?? '—',
                'at' => $prospect->updated_at?->toIso8601String() ?? '',
                'url' => route('admin.employers.pipeline.show', $prospect),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{type: string, label: string, detail: string, at: string, url: string|null}>  $items
     * @return list<array{type: string, label: string, detail: string, at: string, url: string|null}>
     */
    private function sortAndLimit(array $items, int $limit): array
    {
        usort($items, fn ($a, $b) => strcmp($b['at'], $a['at']));

        return array_slice($items, 0, $limit);
    }
}
