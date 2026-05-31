<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmEmployerProspect;
use Illuminate\Database\Eloquent\Builder;

class DashboardScopeService
{
    public function __construct(
        private readonly LeadVisibilityService $leadVisibility,
        private readonly EmployerProspectVisibilityService $prospectVisibility,
    ) {
    }

    public function hasUnrestrictedScope(Admin $admin): bool
    {
        return $admin->role?->hasUnrestrictedLeadVisibility() ?? false;
    }

    /** @return Builder<HirevoLead> */
    public function talentLeadsQuery(Admin $admin): Builder
    {
        $query = HirevoLead::query();
        $this->leadVisibility->restrictVisibleLeads($query, $admin);

        return $query;
    }

    /** @return Builder<CrmEmployerProspect> */
    public function companyProspectsQuery(Admin $admin): Builder
    {
        $query = CrmEmployerProspect::query();
        $this->prospectVisibility->restrictVisible($query, $admin);

        return $query;
    }

    /** @return list<int> */
    public function visibleCompanyProspectIds(Admin $admin): array
    {
        return $this->companyProspectsQuery($admin)->pluck('id')->all();
    }

    /** @return list<int> */
    public function visibleTalentLeadIds(Admin $admin): array
    {
        return $this->talentLeadsQuery($admin)->pluck('id')->all();
    }
}
