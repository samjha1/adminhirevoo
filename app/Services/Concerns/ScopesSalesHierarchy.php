<?php

namespace App\Services\Concerns;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Services\OrgHierarchyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ScopesSalesHierarchy
{
    protected function orgHierarchy(): OrgHierarchyService
    {
        return app(OrgHierarchyService::class);
    }

    /** @return Collection<int, int> */
    protected function directReportIds(Admin $manager): Collection
    {
        return Admin::query()
            ->where('manager_id', $manager->id)
            ->pluck('id');
    }

    /** @return Collection<int, int> */
    protected function subtreeReportIds(Admin $manager): Collection
    {
        return $this->orgHierarchy()
            ->descendantIds($manager)
            ->reject(fn (int $id) => $id === $manager->id)
            ->values();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function scopeForSalesHierarchy(
        Builder $query,
        Admin $manager,
        string $salesManagerIdColumn,
        string $assignedToColumn,
    ): void {
        $reportIds = $manager->role === AdminRole::Asm
            ? $this->subtreeReportIds($manager)
            : $this->directReportIds($manager);

        $query->where(function (Builder $q) use ($manager, $reportIds, $salesManagerIdColumn, $assignedToColumn) {
            $q->where($salesManagerIdColumn, $manager->id)
                ->orWhere($assignedToColumn, $manager->id);

            if ($reportIds->isNotEmpty()) {
                $q->orWhereIn($assignedToColumn, $reportIds);

                if ($manager->role === AdminRole::Asm) {
                    $q->orWhereIn($salesManagerIdColumn, $reportIds);
                }
            }
        });
    }

    protected function canViewViaSalesHierarchy(
        Admin $admin,
        ?int $salesManagerId,
        ?int $assignedTo,
    ): bool {
        if ((int) $salesManagerId === (int) $admin->id || (int) $assignedTo === (int) $admin->id) {
            return true;
        }

        if ($admin->role === AdminRole::Asm) {
            $subtree = $this->subtreeReportIds($admin);

            return ($salesManagerId !== null && $subtree->contains($salesManagerId))
                || ($assignedTo !== null && $subtree->contains($assignedTo));
        }

        if ($admin->role === AdminRole::SalesManager && $assignedTo !== null) {
            return Admin::query()
                ->whereKey($assignedTo)
                ->where('manager_id', $admin->id)
                ->exists();
        }

        return false;
    }
}
