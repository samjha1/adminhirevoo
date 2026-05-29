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
            AdminRole::SalesManager => $query->where(function (Builder $q) use ($admin) {
                $q->where('sales_manager_id', $admin->id)
                    ->orWhere('assigned_to', $admin->id);
            }),
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
            return $prospect->assigned_to === $admin->id;
        }

        if ($admin->role === AdminRole::SalesManager) {
            return $prospect->sales_manager_id === $admin->id
                || $prospect->assigned_to === $admin->id;
        }

        return false;
    }
}
