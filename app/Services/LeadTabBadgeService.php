<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\AssignmentRoleLevel;
use App\Enums\LeadAssignmentStatus;
use App\Enums\LeadSalesStatus;
use App\Models\Admin;
use App\Models\Hirevo\HirevoCareerConsultationRequest;
use App\Models\Hirevo\HirevoLead;
use Illuminate\Database\Eloquent\Builder;

class LeadTabBadgeService
{
    public function __construct(
        private readonly LeadVisibilityService $visibility,
    ) {}

    /** @return Builder<HirevoLead> */
    private function visibleLeads(Admin $admin): Builder
    {
        $q = HirevoLead::query();
        $this->visibility->restrictVisibleLeads($q, $admin);

        return $q;
    }

    /**
     * Items that should show a notification dot on the Lead funnel tab.
     */
    public function leadAttentionCount(Admin $admin): int
    {
        if ($admin->role->hasUnrestrictedLeadVisibility()) {
            return $this->visibleLeads($admin)->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('assigned_to')
                        ->whereNull('sales_manager_id')
                        ->where('assignment_status', LeadAssignmentStatus::New);
                })->orWhere(function ($q2) {
                    $q2->where('assignment_status', LeadAssignmentStatus::Assigned)
                        ->where('updated_at', '>=', now()->subDays(3));
                });
            })->count();
        }

        return match ($admin->role) {
            AdminRole::SalesManager => $this->visibleLeads($admin)->where(function ($q) use ($admin) {
                $q->where(function ($q2) use ($admin) {
                    $q2->where('assigned_to', $admin->id)
                        ->where('assignment_status', LeadAssignmentStatus::Assigned)
                        ->where('updated_at', '>=', now()->subDays(7));
                })->orWhere(function ($q2) use ($admin) {
                    $q2->where('sales_manager_id', $admin->id)
                        ->whereNotNull('assigned_to')
                        ->where('assigned_to', '!=', $admin->id)
                        ->where('assignment_role_level', AssignmentRoleLevel::Employee)
                        ->where('sales_status', LeadSalesStatus::Pending);
                });
            })->count(),
            AdminRole::SalesEmployee => $this->visibleLeads($admin)
                ->where('assigned_to', $admin->id)
                ->where('sales_status', LeadSalesStatus::Pending)
                ->count(),
        };
    }

    public function pendingConsultationCount(): int
    {
        return HirevoCareerConsultationRequest::query()
            ->where('status', 'pending')
            ->count();
    }
}
