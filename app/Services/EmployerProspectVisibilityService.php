<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmEmployerProspect;
use Illuminate\Database\Eloquent\Builder;

class EmployerProspectVisibilityService
{
    public function __construct(
        private readonly SalesTeamService $teams,
    ) {
    }

    /** @param  Builder<CrmEmployerProspect>  $query */
    public function restrictVisible(Builder $query, Admin $admin): void
    {
        if ($admin->role?->hasUnrestrictedLeadVisibility()) {
            return;
        }

        match ($admin->role) {
            AdminRole::SalesManager => $this->scopeForSalesManager($query, $admin),
            AdminRole::SalesEmployee => $query->where('assigned_to', $admin->id),
            default => null,
        };
    }

    public function canView(Admin $admin, CrmEmployerProspect $prospect): bool
    {
        if ($admin->role?->hasUnrestrictedLeadVisibility()) {
            return true;
        }

        if (! $this->teams->canAccessPipeline($admin, \App\Enums\SalesTeam::Employer)) {
            return false;
        }

        if ($admin->role === AdminRole::SalesEmployee) {
            return (int) $prospect->assigned_to === (int) $admin->id;
        }

        if ($admin->role === AdminRole::SalesManager) {
            if ((int) $prospect->sales_manager_id === (int) $admin->id
                || (int) $prospect->assigned_to === (int) $admin->id) {
                return true;
            }

            if ($prospect->assigned_to) {
                return Admin::query()
                    ->whereKey($prospect->assigned_to)
                    ->where('manager_id', $admin->id)
                    ->exists();
            }

            return false;
        }

        return false;
    }

    /** @param  Builder<CrmEmployerProspect>  $query */
    private function scopeForSalesManager(Builder $query, Admin $manager): void
    {
        $reportIds = Admin::query()
            ->where('manager_id', $manager->id)
            ->pluck('id');

        $query->where(function (Builder $q) use ($manager, $reportIds) {
            $q->where('sales_manager_id', $manager->id)
                ->orWhere('assigned_to', $manager->id);

            if ($reportIds->isNotEmpty()) {
                $q->orWhereIn('assigned_to', $reportIds);
            }
        });
    }
}
