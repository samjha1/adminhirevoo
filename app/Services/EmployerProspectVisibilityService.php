<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Services\Concerns\ScopesSalesHierarchy;
use Illuminate\Database\Eloquent\Builder;

class EmployerProspectVisibilityService
{
    use ScopesSalesHierarchy;

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
            AdminRole::Asm, AdminRole::SalesManager => $this->scopeForSalesHierarchy($query, $admin, 'sales_manager_id', 'assigned_to'),
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

        if (in_array($admin->role, [AdminRole::Asm, AdminRole::SalesManager], true)) {
            return $this->canViewViaSalesHierarchy(
                $admin,
                $prospect->sales_manager_id,
                $prospect->assigned_to,
            );
        }

        return false;
    }
}
