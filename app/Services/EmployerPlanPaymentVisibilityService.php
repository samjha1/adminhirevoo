<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\Hirevo\HirevoPayment;
use App\Modules\Leads\Models\CrmEmployerProspect;
use Illuminate\Database\Eloquent\Builder;

class EmployerPlanPaymentVisibilityService
{
    public function __construct(
        private readonly SalesTeamService $teams,
        private readonly EmployerProspectVisibilityService $prospectVisibility,
    ) {
    }

    public function canAccessList(Admin $admin): bool
    {
        if (! $admin->canPermission('employer_payments.view')) {
            return false;
        }

        if ($admin->role?->isPlatformAdmin() || $admin->role?->hasUnrestrictedLeadVisibility()) {
            return true;
        }

        return $this->teams->canAccessPipeline($admin, SalesTeam::Employer);
    }

    /** @param  Builder<HirevoPayment>  $query */
    public function restrictVisible(Builder $query, Admin $admin): void
    {
        if ($admin->role?->isPlatformAdmin() || $admin->role?->hasUnrestrictedLeadVisibility()) {
            return;
        }

        $prospectQuery = CrmEmployerProspect::query()->whereNotNull('user_id');
        $this->prospectVisibility->restrictVisible($prospectQuery, $admin);
        $userIds = $prospectQuery->pluck('user_id');

        $query->whereIn('user_id', $userIds);
    }

    public function canView(Admin $admin, HirevoPayment $payment): bool
    {
        if (! $this->canAccessList($admin)) {
            return false;
        }

        if ($admin->role?->isPlatformAdmin() || $admin->role?->hasUnrestrictedLeadVisibility()) {
            return true;
        }

        if ($payment->type !== HirevoPayment::TYPE_EMPLOYER_SUBSCRIPTION) {
            return false;
        }

        $prospect = CrmEmployerProspect::query()
            ->where('user_id', $payment->user_id)
            ->first();

        if ($prospect === null) {
            return false;
        }

        return $this->prospectVisibility->canView($admin, $prospect);
    }

    public function pendingCountFor(Admin $admin): int
    {
        if (! $this->canAccessList($admin)) {
            return 0;
        }

        $query = HirevoPayment::query()
            ->where('type', HirevoPayment::TYPE_EMPLOYER_SUBSCRIPTION)
            ->where('payment_gateway', HirevoPayment::GATEWAY_CHEQUE)
            ->where('status', HirevoPayment::STATUS_PENDING);

        $this->restrictVisible($query, $admin);

        return (int) $query->count();
    }
}
