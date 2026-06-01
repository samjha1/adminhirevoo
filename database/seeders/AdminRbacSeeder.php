<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Modules\Rbac\Models\CrmRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminRbacSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $this->upsertAdmin('superadmin@themesdesign.test', 'Super Admin', AdminRole::SuperAdmin, 'super_admin', null, null, $password);
        $this->upsertAdmin('admin@themesdesign.test', 'Platform Admin', AdminRole::Admin, 'admin', null, null, $password);
        $this->upsertAdmin('marketing@themesdesign.test', 'Marketing User', AdminRole::Marketing, 'marketing', null, null, $password);

        $talentManager = $this->upsertAdmin(
            'talent.manager@themesdesign.test',
            'Talent Team Manager',
            AdminRole::SalesManager,
            'sales_manager',
            null,
            SalesTeam::Candidate,
            $password,
        );

        $this->upsertAdmin(
            'talent.executive@themesdesign.test',
            'Talent Team Executive',
            AdminRole::SalesEmployee,
            'sales_employee',
            $talentManager->id,
            SalesTeam::Candidate,
            $password,
        );

        // Legacy emails map to talent team
        $this->upsertAdmin('sales.manager@themesdesign.test', 'Sales Manager (Talent)', AdminRole::SalesManager, 'sales_manager', null, SalesTeam::Candidate, $password);
        $legacyManager = Admin::query()->where('email', 'sales.manager@themesdesign.test')->first();
        $this->upsertAdmin('sales.employee@themesdesign.test', 'Sales Employee (Talent)', AdminRole::SalesEmployee, 'sales_employee', $legacyManager?->id, SalesTeam::Candidate, $password);

        $companyManager = $this->upsertAdmin(
            'company.manager@themesdesign.test',
            'Company Team Manager',
            AdminRole::SalesManager,
            'sales_manager',
            null,
            SalesTeam::Employer,
            $password,
        );

        $this->upsertAdmin(
            'company.executive@themesdesign.test',
            'Company Team Executive',
            AdminRole::SalesEmployee,
            'sales_employee',
            $companyManager->id,
            SalesTeam::Employer,
            $password,
        );

        app(\App\Services\AdminReferralCodeService::class)->backfillEmployerTeamCodes();
    }

    private function upsertAdmin(
        string $email,
        string $name,
        AdminRole $role,
        string $crmSlug,
        ?int $managerId,
        ?SalesTeam $salesTeam,
        string $password,
    ): Admin {
        $crmRole = CrmRole::query()->where('slug', $crmSlug)->first();

        return Admin::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'crm_role_id' => $crmRole?->id,
                'manager_id' => $managerId,
                'sales_team' => $salesTeam?->value,
            ],
        );
    }
}
