<?php

namespace App\Services;

use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmLeadActivity;
use App\Support\PortalDateFilter;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TalentSalesActivityService
{
    private const LEAD_AUDIT_ACTIONS = [
        'lead.assign_manager',
        'lead.assign_employee',
        'lead.reassign_manager',
        'lead.reassign_employee',
        'lead.take_back',
        'lead.unassign_pool',
    ];

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            'stage_change' => 'Stage update',
            'follow_up' => 'Follow-up scheduled',
            'follow_up_completed' => 'Follow-up completed',
            'call' => 'Call logged',
            'email' => 'Email sent',
            'note' => 'Note added',
            'assign_manager' => 'Assigned to manager',
            'assign_employee' => 'Assigned to employee',
            'reassign_manager' => 'Reassigned manager',
            'reassign_employee' => 'Reassigned employee',
            'take_back' => 'Taken back from employee',
            'unassign_pool' => 'Released to pool',
        ];
    }

    public function __construct(
        private readonly SalesTeamActivityScopeService $scope,
        private readonly DashboardScopeService $dashboardScope,
    ) {
    }

    /**
     * @return LengthAwarePaginator<int, array{
     *     source: string,
     *     type: string,
     *     type_label: string,
     *     title: string,
     *     detail: string|null,
     *     admin_id: int|null,
     *     admin_name: string,
     *     subject_name: string,
     *     lead_id: int|null,
     *     at: Carbon,
     *     url: string|null,
     * }>
     */
    public function paginate(
        Admin $actor,
        bool $teamView,
        PortalDateFilter $dateFilter,
        ?int $staffId = null,
        ?int $leadId = null,
        ?string $type = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $adminIds = $this->scope->viewableAdminIds($actor, SalesTeam::Candidate, $teamView);

        if ($staffId !== null) {
            abort_unless($adminIds->contains($staffId), 403, 'You cannot view this staff member\'s activity.');
            $adminIds = collect([$staffId]);
        }

        if ($leadId !== null) {
            abort_unless(
                in_array($leadId, $this->dashboardScope->visibleTalentLeadIds($actor), true),
                403,
                'You cannot view activity for this lead.',
            );
        }

        $items = $this->collectItems($adminIds, $dateFilter, $leadId, $type);
        $page = max(1, Paginator::resolveCurrentPage());
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /** @return list<array{admin_id: int, admin_name: string, role_label: string, count: int}> */
    public function staffSummaryToday(Admin $actor): array
    {
        if (! $this->scope->canViewTeamActivity($actor)) {
            return [];
        }

        $today = PortalDateFilter::fromRequest(
            request()->duplicate(['period' => 'today']),
        );

        $adminIds = $this->scope->viewableAdminIds($actor, SalesTeam::Candidate, true);
        $counts = [];

        foreach ($this->collectItems($adminIds, $today, null, null) as $item) {
            $id = (int) ($item['admin_id'] ?? 0);
            if ($id === 0) {
                continue;
            }
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        $staff = Admin::query()
            ->whereIn('id', $adminIds)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $rows = [];
        foreach ($staff as $member) {
            $rows[] = [
                'admin_id' => $member->id,
                'admin_name' => $member->name,
                'role_label' => $member->role?->label() ?? 'Staff',
                'count' => $counts[$member->id] ?? 0,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['count'] <=> $a['count'] ?: strcmp($a['admin_name'], $b['admin_name']));

        return $rows;
    }

    /**
     * @param  Collection<int, int>  $adminIds
     * @return Collection<int, array<string, mixed>>
     */
    private function collectItems(
        Collection $adminIds,
        PortalDateFilter $dateFilter,
        ?int $leadId,
        ?string $type,
    ): Collection {
        if ($adminIds->isEmpty()) {
            return collect();
        }

        $items = collect();

        if ($this->shouldIncludeLeadActivities($type)) {
            $items = $items->merge($this->leadActivities($adminIds, $dateFilter, $leadId, $type));
        }

        if ($this->shouldIncludeAudit($type)) {
            $items = $items->merge($this->auditActivities($adminIds, $dateFilter, $leadId, $type));
        }

        return $items
            ->sortByDesc(fn (array $item) => $item['at']->getTimestamp())
            ->values();
    }

    private function shouldIncludeLeadActivities(?string $type): bool
    {
        if ($type === null || $type === '') {
            return true;
        }

        return in_array($type, [
            'stage_change', 'follow_up', 'follow_up_completed', 'call', 'email', 'note',
        ], true);
    }

    private function shouldIncludeAudit(?string $type): bool
    {
        if ($type === null || $type === '') {
            return true;
        }

        return in_array($type, [
            'assign_manager', 'assign_employee', 'reassign_manager',
            'reassign_employee', 'take_back', 'unassign_pool',
        ], true);
    }

    /**
     * @param  Collection<int, int>  $adminIds
     * @return Collection<int, array<string, mixed>>
     */
    private function leadActivities(
        Collection $adminIds,
        PortalDateFilter $dateFilter,
        ?int $leadId,
        ?string $type,
    ): Collection {
        $query = CrmLeadActivity::query()
            ->with([
                'admin:id,name',
                'lead.candidate:id,name',
            ])
            ->whereIn('admin_id', $adminIds)
            ->when($leadId, fn ($q) => $q->where('lead_id', $leadId))
            ->when($type, fn ($q) => $q->where('type', $type));

        if ($dateFilter->isActive()) {
            $dateFilter->apply($query, 'created_at');
        }

        return $query->get()->map(function (CrmLeadActivity $activity) {
            $lead = $activity->lead;

            return [
                'source' => 'activity',
                'type' => $activity->type,
                'type_label' => self::typeLabels()[$activity->type] ?? ucfirst(str_replace('_', ' ', $activity->type)),
                'title' => $activity->title,
                'detail' => $this->activityDetail($activity),
                'admin_id' => $activity->admin_id,
                'admin_name' => $activity->admin?->name ?? 'System',
                'subject_name' => $this->leadLabel($lead, $activity->lead_id),
                'lead_id' => $activity->lead_id,
                'at' => $activity->created_at ?? now(),
                'url' => $lead ? route('admin.leads.show', $lead) : null,
            ];
        });
    }

    /**
     * @param  Collection<int, int>  $adminIds
     * @return Collection<int, array<string, mixed>>
     */
    private function auditActivities(
        Collection $adminIds,
        PortalDateFilter $dateFilter,
        ?int $leadId,
        ?string $type,
    ): Collection {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        $actions = self::LEAD_AUDIT_ACTIONS;
        if ($type !== null && $type !== '') {
            $actions = array_values(array_filter(
                self::LEAD_AUDIT_ACTIONS,
                fn (string $action) => $this->auditTypeFromAction($action) === $type,
            ));
            if ($actions === []) {
                return collect();
            }
        }

        $query = AuditLog::query()
            ->with('admin:id,name')
            ->whereIn('admin_id', $adminIds)
            ->whereIn('action', $actions)
            ->where('auditable_type', HirevoLead::class)
            ->when($leadId, fn ($q) => $q->where('auditable_id', $leadId));

        if ($dateFilter->isActive()) {
            $dateFilter->apply($query, 'created_at');
        }

        $logs = $query->get();
        $leadLabels = $this->leadLabelsForIds($logs->pluck('auditable_id')->filter()->unique());

        return $logs->map(function (AuditLog $log) use ($leadLabels) {
            $type = $this->auditTypeFromAction($log->action);
            $metadata = is_array($log->metadata) ? $log->metadata : [];
            $detail = match ($type) {
                'assign_manager', 'reassign_manager' => isset($metadata['manager_id'])
                    ? 'Manager ID #'.$metadata['manager_id']
                    : null,
                'assign_employee', 'reassign_employee' => isset($metadata['employee_id'])
                    ? 'Employee ID #'.$metadata['employee_id']
                    : null,
                default => null,
            };

            $id = (int) $log->auditable_id;

            return [
                'source' => 'audit',
                'type' => $type,
                'type_label' => self::typeLabels()[$type] ?? 'Activity',
                'title' => self::typeLabels()[$type] ?? ucfirst(str_replace('_', ' ', $type)),
                'detail' => $detail,
                'admin_id' => $log->admin_id,
                'admin_name' => $log->admin?->name ?? 'System',
                'subject_name' => $leadLabels[$id] ?? 'Lead #'.$id,
                'lead_id' => $id ?: null,
                'at' => $log->created_at ?? now(),
                'url' => $id ? route('admin.leads.show', $id) : null,
            ];
        });
    }

    private function auditTypeFromAction(string $action): string
    {
        return match ($action) {
            'lead.assign_manager' => 'assign_manager',
            'lead.assign_employee' => 'assign_employee',
            'lead.reassign_manager' => 'reassign_manager',
            'lead.reassign_employee' => 'reassign_employee',
            'lead.take_back' => 'take_back',
            'lead.unassign_pool' => 'unassign_pool',
            default => 'audit',
        };
    }

    private function activityDetail(CrmLeadActivity $activity): ?string
    {
        $payload = is_array($activity->payload) ? $activity->payload : [];

        if (isset($payload['stage'])) {
            return 'Stage: '.str_replace('_', ' ', (string) $payload['stage']);
        }

        if (isset($payload['scheduled_at'])) {
            return 'Scheduled: '.Carbon::parse($payload['scheduled_at'])->format('M j, Y g:i A');
        }

        if (isset($payload['outcome'])) {
            return 'Outcome: '.str_replace('_', ' ', (string) $payload['outcome']);
        }

        return null;
    }

    private function leadLabel(?HirevoLead $lead, ?int $leadId): string
    {
        if ($lead) {
            return $lead->candidate?->name ?? 'Lead #'.$lead->id;
        }

        return $leadId ? 'Lead #'.$leadId : 'Lead';
    }

    /** @param  Collection<int, int>|iterable<int, int>  $ids */
    private function leadLabelsForIds(iterable $ids): array
    {
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return HirevoLead::query()
            ->with('candidate:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (HirevoLead $lead) => [$lead->id => $this->leadLabel($lead, $lead->id)])
            ->all();
    }
}
