<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use Illuminate\Database\Eloquent\Builder;

class LeadVisibilityService
{
    /** @param  Builder<HirevoLead>  $query */
    public function restrictVisibleLeads(Builder $query, Admin $admin): void
    {
        if ($admin->role->hasUnrestrictedLeadVisibility()) {
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

    public function canViewLead(Admin $admin, HirevoLead $lead): bool
    {
        if ($admin->role->hasUnrestrictedLeadVisibility()) {
            return true;
        }

        if ($admin->role === AdminRole::SalesEmployee) {
            return $lead->assigned_to === $admin->id;
        }

        return $lead->sales_manager_id === $admin->id
            || $lead->assigned_to === $admin->id;
    }
}
