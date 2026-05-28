<?php

namespace App\Policies;

use App\Enums\AdminRole;
use App\Enums\AssignmentRoleLevel;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Services\LeadVisibilityService;

class LeadPolicy
{
    public function __construct(
        private readonly LeadVisibilityService $visibility,
    ) {
    }

    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    public function view(Admin $admin, HirevoLead $lead): bool
    {
        return $this->visibility->canViewLead($admin, $lead);
    }

    public function updateCrmStage(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->view($admin, $lead)) {
            return false;
        }

        if ($admin->role === AdminRole::SalesEmployee) {
            return $lead->assigned_to === $admin->id;
        }

        return true;
    }

    public function updateSalesStatus(Admin $admin, HirevoLead $lead): bool
    {
        if ($admin->hasRole(AdminRole::Admin)) {
            return true;
        }
        if ($admin->role === AdminRole::SalesEmployee && $lead->assigned_to === $admin->id) {
            return true;
        }

        return false;
    }

    public function assignAsMarketing(Admin $admin, HirevoLead $lead): bool
    {
        if (! $admin->hasAnyRole([AdminRole::Admin, AdminRole::Marketing])) {
            return false;
        }

        return $this->view($admin, $lead);
    }

    public function assignAsManager(Admin $admin, HirevoLead $lead): bool
    {
        if ($admin->role !== AdminRole::SalesManager) {
            return false;
        }

        return $lead->sales_manager_id === $admin->id || $lead->assigned_to === $admin->id;
    }

    public function takeBack(Admin $admin, HirevoLead $lead): bool
    {
        if ($admin->role !== AdminRole::SalesManager) {
            return false;
        }

        return $lead->sales_manager_id === $admin->id
            && $lead->assignment_role_level === AssignmentRoleLevel::Employee;
    }

    public function releaseToPool(Admin $admin, HirevoLead $lead): bool
    {
        return $admin->hasAnyRole([AdminRole::Admin, AdminRole::Marketing])
            && $this->view($admin, $lead);
    }
}
