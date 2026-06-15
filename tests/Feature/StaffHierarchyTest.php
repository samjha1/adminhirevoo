<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_platform_admin_can_create_sales_manager_under_asm(): void
    {
        $admin = Admin::query()->where('email', 'admin@themesdesign.test')->firstOrFail();
        $asm = Admin::query()->where('email', 'asm.talent.south@themesdesign.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staff.store'), [
                'name' => 'Admin Created Manager',
                'email' => 'talent.manager.admin@themesdesign.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => AdminRole::SalesManager->value,
                'sales_team' => SalesTeam::Candidate->value,
                'manager_id' => $asm->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admins', [
            'email' => 'talent.manager.admin@themesdesign.test',
            'role' => AdminRole::SalesManager->value,
            'sales_team' => SalesTeam::Candidate->value,
            'sales_region' => SalesRegion::South->value,
            'manager_id' => $asm->id,
        ]);
    }

    public function test_asm_can_create_sales_manager_in_their_region(): void
    {
        $asm = Admin::query()->where('email', 'asm.talent.north@themesdesign.test')->firstOrFail();

        $this->actingAs($asm, 'admin')
            ->post(route('admin.staff.store'), [
                'name' => 'Extra Talent Manager',
                'email' => 'talent.manager.extra@themesdesign.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admins', [
            'email' => 'talent.manager.extra@themesdesign.test',
            'role' => AdminRole::SalesManager->value,
            'manager_id' => $asm->id,
            'sales_region' => SalesRegion::North->value,
        ]);
    }

    public function test_sales_manager_can_create_employee_only(): void
    {
        $manager = Admin::query()->where('email', 'talent.manager1@themesdesign.test')->firstOrFail();

        $this->actingAs($manager, 'admin')
            ->post(route('admin.staff.store'), [
                'name' => 'New Talent Executive',
                'email' => 'talent.executive.new@themesdesign.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admins', [
            'email' => 'talent.executive.new@themesdesign.test',
            'role' => AdminRole::SalesEmployee->value,
            'manager_id' => $manager->id,
        ]);
    }

    public function test_asm_sees_subtree_on_staff_index(): void
    {
        $asm = Admin::query()->where('email', 'asm.talent.north@themesdesign.test')->firstOrFail();

        $this->actingAs($asm, 'admin')
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Talent Manager North A')
            ->assertSee('Talent Executive North A1')
            ->assertDontSee('Talent Manager South');
    }

    public function test_cross_team_asm_assignment_is_rejected(): void
    {
        $admin = Admin::query()->where('email', 'admin@themesdesign.test')->firstOrFail();
        $companyAsm = Admin::query()->where('email', 'asm.company.north@themesdesign.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.staff.create'))
            ->post(route('admin.staff.store'), [
                'name' => 'Bad Talent Manager',
                'email' => 'bad.manager@themesdesign.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => AdminRole::SalesManager->value,
                'sales_team' => SalesTeam::Candidate->value,
                'manager_id' => $companyAsm->id,
            ])
            ->assertRedirect(route('admin.staff.create'))
            ->assertSessionHasErrors('manager_id');
    }
}
