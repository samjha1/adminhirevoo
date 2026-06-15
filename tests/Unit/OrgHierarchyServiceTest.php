<?php

namespace Tests\Unit;

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Modules\Rbac\Models\CrmRole;
use App\Services\OrgHierarchyService;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrgHierarchyServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrgHierarchyService $hierarchy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->hierarchy = app(OrgHierarchyService::class);
    }

    public function test_asm_requires_team_region_and_platform_admin_manager(): void
    {
        $admin = $this->makeAdmin(AdminRole::Admin);

        $errors = $this->hierarchy->validateAssignment(
            AdminRole::Asm,
            null,
            null,
            null,
        );
        $this->assertArrayHasKey('sales_team', $errors);
        $this->assertArrayHasKey('sales_region', $errors);
        $this->assertArrayHasKey('manager_id', $errors);

        $errors = $this->hierarchy->validateAssignment(
            AdminRole::Asm,
            SalesTeam::Candidate,
            SalesRegion::North,
            $admin->id,
        );
        $this->assertSame([], $errors);
    }

    public function test_sales_manager_must_report_to_asm_on_same_team(): void
    {
        $platformAdmin = $this->makeAdmin(AdminRole::Admin);
        $asm = $this->makeAdmin(AdminRole::Asm, SalesTeam::Candidate, SalesRegion::North, $platformAdmin->id);
        $wrongAsm = $this->makeAdmin(AdminRole::Asm, SalesTeam::Employer, SalesRegion::North, $platformAdmin->id, 'asm2@test.local');

        $errors = $this->hierarchy->validateAssignment(
            AdminRole::SalesManager,
            SalesTeam::Candidate,
            null,
            $wrongAsm->id,
        );
        $this->assertArrayHasKey('manager_id', $errors);

        $errors = $this->hierarchy->validateAssignment(
            AdminRole::SalesManager,
            SalesTeam::Candidate,
            null,
            $asm->id,
        );
        $this->assertSame([], $errors);
    }

    public function test_duplicate_asm_per_team_region_is_rejected(): void
    {
        $platformAdmin = $this->makeAdmin(AdminRole::Admin);
        $this->makeAdmin(AdminRole::Asm, SalesTeam::Candidate, SalesRegion::North, $platformAdmin->id);

        $errors = $this->hierarchy->validateAssignment(
            AdminRole::Asm,
            SalesTeam::Candidate,
            SalesRegion::North,
            $platformAdmin->id,
        );
        $this->assertArrayHasKey('sales_region', $errors);
    }

    public function test_descendant_ids_include_nested_employees(): void
    {
        $platformAdmin = $this->makeAdmin(AdminRole::Admin);
        $asm = $this->makeAdmin(AdminRole::Asm, SalesTeam::Candidate, SalesRegion::North, $platformAdmin->id);
        $manager = $this->makeAdmin(AdminRole::SalesManager, SalesTeam::Candidate, SalesRegion::North, $asm->id, 'mgr@test.local');
        $employee = $this->makeAdmin(AdminRole::SalesEmployee, SalesTeam::Candidate, SalesRegion::North, $manager->id, 'emp@test.local');

        $ids = $this->hierarchy->descendantIds($asm);

        $this->assertTrue($ids->contains($asm->id));
        $this->assertTrue($ids->contains($manager->id));
        $this->assertTrue($ids->contains($employee->id));
    }

    public function test_inherit_region_from_asm_manager_chain(): void
    {
        $platformAdmin = $this->makeAdmin(AdminRole::Admin);
        $asm = $this->makeAdmin(AdminRole::Asm, SalesTeam::Candidate, SalesRegion::South, $platformAdmin->id);
        $manager = $this->makeAdmin(AdminRole::SalesManager, SalesTeam::Candidate, SalesRegion::South, $asm->id, 'mgr2@test.local');

        $this->assertSame(SalesRegion::South, $this->hierarchy->inheritRegion($manager->id));
    }

    private function makeAdmin(
        AdminRole $role,
        ?SalesTeam $team = null,
        ?SalesRegion $region = null,
        ?int $managerId = null,
        ?string $email = null,
    ): Admin {
        $email ??= strtolower($role->value).'.'.uniqid().'@test.local';
        $crmRole = CrmRole::query()->where('slug', $role->crmRoleSlug())->first();

        return Admin::query()->create([
            'name' => $role->label(),
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'crm_role_id' => $crmRole?->id,
            'sales_team' => $team?->value,
            'sales_region' => $region?->value,
            'manager_id' => $managerId,
        ]);
    }
}
