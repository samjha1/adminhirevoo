<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Rbac\Services\PermissionResolver;
use App\Services\Concerns\ScopesSalesHierarchy;
use Illuminate\Database\Eloquent\Builder;

class LeadVisibilityService
{
    use ScopesSalesHierarchy;

    public function __construct(
        private readonly PermissionResolver $permissions,
    ) {
    }

    /** @param  Builder<HirevoLead>  $query */
    public function restrictVisibleLeads(Builder $query, Admin $admin): void
    {
        if ($admin->role->hasUnrestrictedLeadVisibility() || $this->permissions->can($admin, 'leads.view_all')) {
            return;
        }

        match ($admin->role) {
            AdminRole::Asm, AdminRole::SalesManager => $this->scopeForSalesHierarchy($query, $admin, 'sales_manager_id', 'assigned_to'),
            AdminRole::SalesEmployee => $query->where('assigned_to', $admin->id),
            default => null,
        };
    }

    public function canViewLead(Admin $admin, HirevoLead $lead): bool
    {
        if ($admin->role->hasUnrestrictedLeadVisibility() || $this->permissions->can($admin, 'leads.view_all')) {
            return true;
        }

        if ($admin->role === AdminRole::SalesEmployee) {
            return (int) $lead->assigned_to === (int) $admin->id;
        }

        if (in_array($admin->role, [AdminRole::Asm, AdminRole::SalesManager], true)) {
            return $this->canViewViaSalesHierarchy(
                $admin,
                $lead->sales_manager_id,
                $lead->assigned_to,
            );
        }

        return false;
    }
}
