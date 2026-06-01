<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Rbac\Services\PermissionResolver;
use App\Services\LeadVisibilityService;

class LeadPolicy
{
    public function __construct(
        private readonly LeadVisibilityService $visibility,
        private readonly PermissionResolver $permissions,
    ) {
    }

    public function viewAny(Admin $admin): bool
    {
        return $this->permissions->can($admin, 'leads.view');
    }

    public function view(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->permissions->can($admin, 'leads.view')) {
            return false;
        }

        if ($this->permissions->can($admin, 'leads.view_all')) {
            return true;
        }

        return $this->visibility->canViewLead($admin, $lead);
    }

    public function updateCrmStage(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->permissions->can($admin, 'leads.update_stage') || ! $this->view($admin, $lead)) {
            return false;
        }

        if ($admin->hasRole(\App\Enums\AdminRole::SalesEmployee)) {
            return $lead->assigned_to === $admin->id;
        }

        return true;
    }

    public function updateSalesStatus(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->permissions->can($admin, 'leads.update_sales_status')) {
            return false;
        }

        if ($this->permissions->can($admin, 'leads.view_all')) {
            return true;
        }

        if ($admin->hasRole(\App\Enums\AdminRole::SalesEmployee) && $lead->assigned_to === $admin->id) {
            return true;
        }

        return $this->view($admin, $lead);
    }

    public function assignAsMarketing(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->permissions->can($admin, 'leads.assign_manager')) {
            return false;
        }

        return $this->view($admin, $lead);
    }

    public function assignAsManager(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->permissions->can($admin, 'leads.assign_employee') || ! $this->view($admin, $lead)) {
            return false;
        }

        return $this->visibility->canViewLead($admin, $lead);
    }

    public function takeBack(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->permissions->can($admin, 'leads.take_back')) {
            return false;
        }

        if ($lead->assignment_role_level !== \App\Enums\AssignmentRoleLevel::Employee) {
            return false;
        }

        if ($admin->role?->isSuperAdmin() || $this->permissions->can($admin, 'leads.view_all')) {
            return $this->view($admin, $lead);
        }

        return $this->visibility->canViewLead($admin, $lead);
    }

    public function releaseToPool(Admin $admin, HirevoLead $lead): bool
    {
        if (! $this->permissions->can($admin, 'leads.release')) {
            return false;
        }

        return $this->view($admin, $lead);
    }

    public function logCall(Admin $admin, HirevoLead $lead): bool
    {
        return $this->permissions->can($admin, 'leads.log_call') && $this->view($admin, $lead);
    }

    public function manageFollowups(Admin $admin, HirevoLead $lead): bool
    {
        return $this->permissions->can($admin, 'leads.manage_followups') && $this->view($admin, $lead);
    }
}
