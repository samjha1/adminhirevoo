<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
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

        $this->upsertAdmin('superadmin@themesdesign.test', 'Super Admin', AdminRole::SuperAdmin, 'super_admin', null, null, null, $password);
        $platformAdmin = $this->upsertAdmin('admin@themesdesign.test', 'Platform Admin', AdminRole::Admin, 'admin', null, null, null, $password);
        $this->upsertAdmin('recruiter@themesdesign.test', 'Portal Recruiter', AdminRole::Recruiter, 'recruiter', null, null, null, $password);
        $this->upsertAdmin('recruiter.manager@themesdesign.test', 'Recruiter Manager', AdminRole::RecruiterManager, 'recruiter_manager', null, null, null, $password);
        $this->upsertAdmin('marketing@themesdesign.test', 'Marketing User', AdminRole::Marketing, 'marketing', null, null, null, $password);

        $asmTalentNorth = $this->upsertAdmin(
            'asm.talent.north@themesdesign.test',
            'ASM Talent North',
            AdminRole::Asm,
            'asm',
            $platformAdmin->id,
            SalesTeam::Candidate,
            SalesRegion::North,
            $password,
        );

        $asmTalentSouth = $this->upsertAdmin(
            'asm.talent.south@themesdesign.test',
            'ASM Talent South',
            AdminRole::Asm,
            'asm',
            $platformAdmin->id,
            SalesTeam::Candidate,
            SalesRegion::South,
            $password,
        );

        $asmCompanyNorth = $this->upsertAdmin(
            'asm.company.north@themesdesign.test',
            'ASM Company North',
            AdminRole::Asm,
            'asm',
            $platformAdmin->id,
            SalesTeam::Employer,
            SalesRegion::North,
            $password,
        );

        $asmCompanySouth = $this->upsertAdmin(
            'asm.company.south@themesdesign.test',
            'ASM Company South',
            AdminRole::Asm,
            'asm',
            $platformAdmin->id,
            SalesTeam::Employer,
            SalesRegion::South,
            $password,
        );

        $talentManagerOne = $this->upsertAdmin(
            'talent.manager1@themesdesign.test',
            'Talent Manager North A',
            AdminRole::SalesManager,
            'sales_manager',
            $asmTalentNorth->id,
            SalesTeam::Candidate,
            SalesRegion::North,
            $password,
        );

        $talentManagerTwo = $this->upsertAdmin(
            'talent.manager2@themesdesign.test',
            'Talent Manager North B',
            AdminRole::SalesManager,
            'sales_manager',
            $asmTalentNorth->id,
            SalesTeam::Candidate,
            SalesRegion::North,
            $password,
        );

        $this->upsertAdmin(
            'talent.executive1@themesdesign.test',
            'Talent Executive North A1',
            AdminRole::SalesEmployee,
            'sales_employee',
            $talentManagerOne->id,
            SalesTeam::Candidate,
            SalesRegion::North,
            $password,
        );

        $this->upsertAdmin(
            'talent.executive2@themesdesign.test',
            'Talent Executive North A2',
            AdminRole::SalesEmployee,
            'sales_employee',
            $talentManagerOne->id,
            SalesTeam::Candidate,
            SalesRegion::North,
            $password,
        );

        $this->upsertAdmin(
            'talent.manager.south@themesdesign.test',
            'Talent Manager South',
            AdminRole::SalesManager,
            'sales_manager',
            $asmTalentSouth->id,
            SalesTeam::Candidate,
            SalesRegion::South,
            $password,
        );

        $companyManagerNorth = $this->upsertAdmin(
            'company.manager1@themesdesign.test',
            'Company Manager North A',
            AdminRole::SalesManager,
            'sales_manager',
            $asmCompanyNorth->id,
            SalesTeam::Employer,
            SalesRegion::North,
            $password,
        );

        $this->upsertAdmin(
            'company.executive1@themesdesign.test',
            'Company Executive North A1',
            AdminRole::SalesEmployee,
            'sales_employee',
            $companyManagerNorth->id,
            SalesTeam::Employer,
            SalesRegion::North,
            $password,
        );

        $this->upsertAdmin(
            'company.executive2@themesdesign.test',
            'Company Executive North A2',
            AdminRole::SalesEmployee,
            'sales_employee',
            $companyManagerNorth->id,
            SalesTeam::Employer,
            SalesRegion::North,
            $password,
        );

        $this->upsertAdmin(
            'company.manager2@themesdesign.test',
            'Company Manager North B',
            AdminRole::SalesManager,
            'sales_manager',
            $asmCompanyNorth->id,
            SalesTeam::Employer,
            SalesRegion::North,
            $password,
        );

        $this->upsertAdmin(
            'company.manager.south@themesdesign.test',
            'Company Manager South',
            AdminRole::SalesManager,
            'sales_manager',
            $asmCompanySouth->id,
            SalesTeam::Employer,
            SalesRegion::South,
            $password,
        );

        // Legacy emails for backward-compatible tests
        $this->upsertAdmin('talent.manager@themesdesign.test', 'Talent Team Manager (Legacy)', AdminRole::SalesManager, 'sales_manager', $asmTalentNorth->id, SalesTeam::Candidate, SalesRegion::North, $password);
        $legacyTalentManager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->first();
        $this->upsertAdmin('talent.executive@themesdesign.test', 'Talent Team Executive (Legacy)', AdminRole::SalesEmployee, 'sales_employee', $legacyTalentManager?->id, SalesTeam::Candidate, SalesRegion::North, $password);
        $this->upsertAdmin('sales.manager@themesdesign.test', 'Sales Manager (Talent Legacy)', AdminRole::SalesManager, 'sales_manager', $asmTalentNorth->id, SalesTeam::Candidate, SalesRegion::North, $password);
        $legacyManager = Admin::query()->where('email', 'sales.manager@themesdesign.test')->first();
        $this->upsertAdmin('sales.employee@themesdesign.test', 'Sales Employee (Talent Legacy)', AdminRole::SalesEmployee, 'sales_employee', $legacyManager?->id, SalesTeam::Candidate, SalesRegion::North, $password);
        $this->upsertAdmin('company.manager@themesdesign.test', 'Company Team Manager (Legacy)', AdminRole::SalesManager, 'sales_manager', $asmCompanyNorth->id, SalesTeam::Employer, SalesRegion::North, $password);
        $legacyCompanyManager = Admin::query()->where('email', 'company.manager@themesdesign.test')->first();
        $this->upsertAdmin('company.executive@themesdesign.test', 'Company Team Executive (Legacy)', AdminRole::SalesEmployee, 'sales_employee', $legacyCompanyManager?->id, SalesTeam::Employer, SalesRegion::North, $password);

        app(\App\Services\AdminReferralCodeService::class)->backfillEmployerTeamCodes();
    }

    private function upsertAdmin(
        string $email,
        string $name,
        AdminRole $role,
        string $crmSlug,
        ?int $managerId,
        ?SalesTeam $salesTeam,
        ?SalesRegion $salesRegion,
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
                'sales_region' => $salesRegion?->value,
            ],
        );
    }
}
