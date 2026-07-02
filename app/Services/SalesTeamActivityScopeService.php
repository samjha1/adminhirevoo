<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Illuminate\Support\Collection;

class SalesTeamActivityScopeService
{
    public function __construct(
        private readonly OrgHierarchyService $orgHierarchy,
    ) {
    }

    public function canViewTeamActivity(Admin $actor): bool
    {
        if ($actor->role?->hasUnrestrictedLeadVisibility() || $actor->role === AdminRole::Marketing) {
            return true;
        }

        return in_array($actor->role, [AdminRole::Asm, AdminRole::SalesManager], true);
    }

    /** @return Collection<int, int> */
    public function viewableAdminIds(Admin $actor, SalesTeam $salesTeam, bool $teamView): Collection
    {
        if (! $teamView) {
            return collect([$actor->id]);
        }

        if ($actor->role?->hasUnrestrictedLeadVisibility() || $actor->role === AdminRole::Marketing) {
            return Admin::query()
                ->whereIn('role', [
                    AdminRole::Asm->value,
                    AdminRole::SalesManager->value,
                    AdminRole::SalesEmployee->value,
                ])
                ->where('sales_team', $salesTeam->value)
                ->pluck('id');
        }

        if ($actor->role === AdminRole::Asm) {
            if (SalesTeam::normalize($actor->sales_team) !== $salesTeam->value) {
                return collect();
            }

            return $this->orgHierarchy->descendantIds($actor);
        }

        if ($actor->role === AdminRole::SalesManager) {
            if (SalesTeam::normalize($actor->sales_team) !== $salesTeam->value) {
                return collect([$actor->id]);
            }

            $employeeIds = Admin::query()
                ->where('manager_id', $actor->id)
                ->where('role', AdminRole::SalesEmployee)
                ->pluck('id');

            return collect([$actor->id])->merge($employeeIds)->unique()->values();
        }

        return collect([$actor->id]);
    }

    /** @return Collection<int, Admin> */
    public function filterableStaff(Admin $actor, SalesTeam $salesTeam): Collection
    {
        $ids = $this->viewableAdminIds($actor, $salesTeam, true);

        return Admin::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}
