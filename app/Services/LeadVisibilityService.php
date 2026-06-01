<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Rbac\Services\PermissionResolver;
use Illuminate\Database\Eloquent\Builder;

class LeadVisibilityService
{
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
            AdminRole::SalesManager => $this->scopeForSalesManager($query, $admin),
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

        if ($admin->role === AdminRole::SalesManager) {
            if ((int) $lead->sales_manager_id === (int) $admin->id
                || (int) $lead->assigned_to === (int) $admin->id) {
                return true;
            }

            if ($lead->assigned_to) {
                return Admin::query()
                    ->whereKey($lead->assigned_to)
                    ->where('manager_id', $admin->id)
                    ->exists();
            }

            return false;
        }

        return false;
    }

    /** @param  Builder<HirevoLead>  $query */
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
