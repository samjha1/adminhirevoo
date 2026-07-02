<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmCompanyActivity;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Support\PortalDateFilter;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CompanySalesActivityService
{
    private const EMPLOYER_AUDIT_ACTIONS = [
        'employer.assign_manager',
        'employer.assign_employee',
    ];

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            'stage_change' => 'Stage update',
            'follow_up' => 'Follow-up scheduled',
            'follow_up_completed' => 'Follow-up completed',
            'meeting_scheduled' => 'Meeting scheduled',
            'meeting_completed' => 'Meeting completed',
            'call' => 'Call logged',
            'assign_manager' => 'Assigned to manager',
            'assign_employee' => 'Assigned to employee',
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
     *     company_name: string,
     *     prospect_id: int|null,
     *     at: Carbon,
     *     url: string|null,
     * }>
     */
    public function paginate(
        Admin $actor,
        bool $teamView,
        PortalDateFilter $dateFilter,
        ?int $staffId = null,
        ?int $prospectId = null,
        ?string $type = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $adminIds = $this->scope->viewableAdminIds($actor, \App\Enums\SalesTeam::Employer, $teamView);

        if ($staffId !== null) {
            abort_unless($adminIds->contains($staffId), 403, 'You cannot view this staff member\'s activity.');
            $adminIds = collect([$staffId]);
        }

        if ($prospectId !== null) {
            abort_unless(
                in_array($prospectId, $this->dashboardScope->visibleCompanyProspectIds($actor), true),
                403,
                'You cannot view activity for this company.',
            );
        }

        $items = $this->collectItems($adminIds, $dateFilter, $prospectId, $type);
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

    /**
     * @return list<array{admin_id: int, admin_name: string, role_label: string, count: int}>
     */
    public function staffSummaryToday(Admin $actor): array
    {
        if (! $this->scope->canViewTeamActivity($actor)) {
            return [];
        }

        $today = PortalDateFilter::fromRequest(
            request()->duplicate(['period' => 'today']),
        );

        $adminIds = $this->scope->viewableAdminIds($actor, \App\Enums\SalesTeam::Employer, true);
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
     * @return Collection<int, array{
     *     source: string,
     *     type: string,
     *     type_label: string,
     *     title: string,
     *     detail: string|null,
     *     admin_id: int|null,
     *     admin_name: string,
     *     company_name: string,
     *     prospect_id: int|null,
     *     at: Carbon,
     *     url: string|null,
     * }>
     */
    private function collectItems(
        Collection $adminIds,
        PortalDateFilter $dateFilter,
        ?int $prospectId,
        ?string $type,
    ): Collection {
        if ($adminIds->isEmpty()) {
            return collect();
        }

        $items = collect();

        if ($this->shouldIncludeCompanyActivities($type)) {
            $items = $items->merge($this->companyActivities($adminIds, $dateFilter, $prospectId, $type));
        }

        if ($this->shouldIncludeCalls($type)) {
            $items = $items->merge($this->callLogs($adminIds, $dateFilter, $prospectId));
        }

        if ($this->shouldIncludeAudit($type)) {
            $items = $items->merge($this->auditActivities($adminIds, $dateFilter, $prospectId, $type));
        }

        return $items
            ->sortByDesc(fn (array $item) => $item['at']->getTimestamp())
            ->values();
    }

    /** @param  Collection<int, int>  $adminIds */
    private function shouldIncludeCompanyActivities(?string $type): bool
    {
        if ($type === null || $type === '') {
            return true;
        }

        return in_array($type, ['stage_change', 'follow_up', 'follow_up_completed', 'meeting_scheduled', 'meeting_completed'], true);
    }

    /** @param  Collection<int, int>  $adminIds */
    private function shouldIncludeCalls(?string $type): bool
    {
        return $type === null || $type === '' || $type === 'call';
    }

    /** @param  Collection<int, int>  $adminIds */
    private function shouldIncludeAudit(?string $type): bool
    {
        if ($type === null || $type === '') {
            return true;
        }

        return in_array($type, ['assign_manager', 'assign_employee'], true);
    }

    /**
     * @param  Collection<int, int>  $adminIds
     * @return Collection<int, array<string, mixed>>
     */
    private function companyActivities(
        Collection $adminIds,
        PortalDateFilter $dateFilter,
        ?int $prospectId,
        ?string $type,
    ): Collection {
        $query = CrmCompanyActivity::query()
            ->with([
                'admin:id,name',
                'employerProspect:id,company_name',
            ])
            ->whereIn('admin_id', $adminIds)
            ->when($prospectId, fn ($q) => $q->where('employer_prospect_id', $prospectId))
            ->when($type, fn ($q) => $q->where('type', $type));

        if ($dateFilter->isActive()) {
            $dateFilter->apply($query, 'created_at');
        }

        return $query->get()->map(function (CrmCompanyActivity $activity) {
            $prospect = $activity->employerProspect;

            return [
                'source' => 'activity',
                'type' => $activity->type,
                'type_label' => self::typeLabels()[$activity->type] ?? ucfirst(str_replace('_', ' ', $activity->type)),
                'title' => $activity->title,
                'detail' => $this->activityDetail($activity),
                'admin_id' => $activity->admin_id,
                'admin_name' => $activity->admin?->name ?? 'System',
                'company_name' => $prospect?->company_name ?? 'Company',
                'prospect_id' => $activity->employer_prospect_id,
                'at' => $activity->created_at ?? now(),
                'url' => $prospect ? route('admin.employers.pipeline.show', $prospect) : null,
            ];
        });
    }

    /**
     * @param  Collection<int, int>  $adminIds
     * @return Collection<int, array<string, mixed>>
     */
    private function callLogs(
        Collection $adminIds,
        PortalDateFilter $dateFilter,
        ?int $prospectId,
    ): Collection {
        if (! Schema::hasTable('crm_call_logs') || ! Schema::hasColumn('crm_call_logs', 'employer_prospect_id')) {
            return collect();
        }

        $query = CrmCallLog::query()
            ->with([
                'admin:id,name',
            ])
            ->whereNotNull('employer_prospect_id')
            ->whereIn('admin_id', $adminIds)
            ->when($prospectId, fn ($q) => $q->where('employer_prospect_id', $prospectId));

        if ($dateFilter->isActive()) {
            $dateFilter->apply($query, 'called_at');
        }

        $calls = $query->get();
        $prospectNames = CrmEmployerProspect::query()
            ->whereIn('id', $calls->pluck('employer_prospect_id')->filter()->unique())
            ->pluck('company_name', 'id');

        return $calls->map(function (CrmCallLog $call) use ($prospectNames) {
            $prospectId = $call->employer_prospect_id;
            $companyName = $prospectNames[$prospectId] ?? 'Company';

            return [
                'source' => 'call',
                'type' => 'call',
                'type_label' => self::typeLabels()['call'],
                'title' => 'Call logged: '.($call->outcome?->label() ?? 'Call'),
                'detail' => $call->notes,
                'admin_id' => $call->admin_id,
                'admin_name' => $call->admin?->name ?? 'Staff',
                'company_name' => $companyName,
                'prospect_id' => $prospectId,
                'at' => $call->called_at ?? $call->created_at ?? now(),
                'url' => $prospectId ? route('admin.employers.pipeline.show', $prospectId) : null,
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
        ?int $prospectId,
        ?string $type,
    ): Collection {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        $actions = self::EMPLOYER_AUDIT_ACTIONS;
        if ($type === 'assign_manager') {
            $actions = ['employer.assign_manager'];
        } elseif ($type === 'assign_employee') {
            $actions = ['employer.assign_employee'];
        }

        $query = AuditLog::query()
            ->with('admin:id,name')
            ->whereIn('admin_id', $adminIds)
            ->whereIn('action', $actions)
            ->where('auditable_type', CrmEmployerProspect::class)
            ->when($prospectId, fn ($q) => $q->where('auditable_id', $prospectId));

        if ($dateFilter->isActive()) {
            $dateFilter->apply($query, 'created_at');
        }

        $prospectIds = $query->pluck('auditable_id')->filter()->unique();
        $prospectNames = CrmEmployerProspect::query()
            ->whereIn('id', $prospectIds)
            ->pluck('company_name', 'id');

        return $query->get()->map(function (AuditLog $log) use ($prospectNames) {
            $type = match ($log->action) {
                'employer.assign_manager' => 'assign_manager',
                'employer.assign_employee' => 'assign_employee',
                default => 'audit',
            };

            $metadata = is_array($log->metadata) ? $log->metadata : [];
            $detail = match ($type) {
                'assign_manager' => isset($metadata['manager_id'])
                    ? 'Manager ID #'.$metadata['manager_id']
                    : null,
                'assign_employee' => isset($metadata['employee_id'])
                    ? 'Employee ID #'.$metadata['employee_id']
                    : null,
                default => null,
            };

            $prospectId = (int) $log->auditable_id;

            return [
                'source' => 'audit',
                'type' => $type,
                'type_label' => self::typeLabels()[$type] ?? 'Activity',
                'title' => self::typeLabels()[$type] ?? ucfirst(str_replace('_', ' ', $type)),
                'detail' => $detail,
                'admin_id' => $log->admin_id,
                'admin_name' => $log->admin?->name ?? 'System',
                'company_name' => $prospectNames[$prospectId] ?? 'Company',
                'prospect_id' => $prospectId ?: null,
                'at' => $log->created_at ?? now(),
                'url' => $prospectId ? route('admin.employers.pipeline.show', $prospectId) : null,
            ];
        });
    }

    private function activityDetail(CrmCompanyActivity $activity): ?string
    {
        $payload = is_array($activity->payload) ? $activity->payload : [];

        if (isset($payload['stage'])) {
            return 'Stage: '.str_replace('_', ' ', (string) $payload['stage']);
        }

        if (isset($payload['scheduled_at'])) {
            return 'Scheduled: '.Carbon::parse($payload['scheduled_at'])->format('M j, Y g:i A');
        }

        if (isset($payload['meeting_at'])) {
            return 'Meeting: '.Carbon::parse($payload['meeting_at'])->format('M j, Y g:i A');
        }

        return null;
    }
}
