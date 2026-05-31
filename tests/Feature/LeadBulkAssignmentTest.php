<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\AssignmentRoleLevel;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadBulkAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_marketing_sees_bulk_assign_to_manager_on_leads_index(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();

        $this->actingAs($marketing, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Bulk assign to sales manager')
            ->assertSee('bulk-bar-managers', false);
    }

    public function test_marketing_can_bulk_assign_leads_to_talent_manager(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();

        $lead = HirevoLead::query()->create(['candidate_id' => 1, 'status' => 'new']);

        $this->actingAs($marketing, 'admin')
            ->post(route('admin.leads.bulk-assign-manager'), [
                'lead_ids' => [$lead->id],
                'manager_id' => $manager->id,
            ])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame($manager->id, $lead->sales_manager_id);
        $this->assertSame($manager->id, $lead->assigned_to);
        $this->assertSame(AssignmentRoleLevel::Manager, $lead->assignment_role_level);
    }

    public function test_sales_manager_sees_bulk_assign_to_employee_on_leads_index(): void
    {
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();

        $this->actingAs($manager, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Bulk assign to your executives');
    }

    public function test_sales_manager_can_bulk_assign_owned_leads_to_employee(): void
    {
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();
        $employee = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();

        $lead = HirevoLead::query()->create([
            'candidate_id' => 2,
            'status' => 'new',
            'sales_manager_id' => $manager->id,
            'assigned_to' => $manager->id,
            'assignment_role_level' => AssignmentRoleLevel::Manager,
        ]);

        $this->actingAs($manager, 'admin')
            ->post(route('admin.leads.bulk-assign-employee'), [
                'lead_ids' => [$lead->id],
                'employee_id' => $employee->id,
            ])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame($employee->id, $lead->assigned_to);
        $this->assertSame(AssignmentRoleLevel::Employee, $lead->assignment_role_level);
    }

    public function test_sales_employee_cannot_bulk_assign_to_manager(): void
    {
        $employee = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();

        $this->actingAs($employee, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertDontSee('Bulk assign to sales manager');
    }

    public function test_sales_manager_can_take_back_lead_from_employee(): void
    {
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();
        $employee = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();

        $lead = HirevoLead::query()->create([
            'candidate_id' => 3,
            'status' => 'new',
            'sales_manager_id' => $manager->id,
            'assigned_to' => $employee->id,
            'assignment_role_level' => AssignmentRoleLevel::Employee,
        ]);

        $this->actingAs($manager, 'admin')
            ->post(route('admin.leads.take-back', $lead))
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame($manager->id, $lead->assigned_to);
        $this->assertSame(AssignmentRoleLevel::Manager, $lead->assignment_role_level);
    }

    public function test_take_back_denied_when_lead_already_with_manager(): void
    {
        $superAdmin = Admin::query()->where('email', 'superadmin@themesdesign.test')->firstOrFail();
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();

        $lead = HirevoLead::query()->create([
            'candidate_id' => 4,
            'status' => 'new',
            'sales_manager_id' => $manager->id,
            'assigned_to' => $manager->id,
            'assignment_role_level' => AssignmentRoleLevel::Manager,
        ]);

        $this->actingAs($superAdmin, 'admin')
            ->post(route('admin.leads.take-back', $lead))
            ->assertForbidden();
    }

    public function test_assign_manager_shows_error_not_server_error_when_target_invalid(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        $employee = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();
        $lead = HirevoLead::query()->create(['candidate_id' => 6, 'status' => 'new']);

        $this->actingAs($marketing, 'admin')
            ->from(route('admin.leads.show', $lead))
            ->post(route('admin.leads.assign-manager', $lead), ['manager_id' => $employee->id])
            ->assertRedirect(route('admin.leads.show', $lead))
            ->assertSessionHas('error');
    }

    public function test_super_admin_can_take_back_employee_lead_to_its_manager(): void
    {
        $superAdmin = Admin::query()->where('email', 'superadmin@themesdesign.test')->firstOrFail();
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();
        $employee = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();

        $lead = HirevoLead::query()->create([
            'candidate_id' => 5,
            'status' => 'new',
            'sales_manager_id' => $manager->id,
            'assigned_to' => $employee->id,
            'assignment_role_level' => AssignmentRoleLevel::Employee,
        ]);

        $this->actingAs($superAdmin, 'admin')
            ->post(route('admin.leads.take-back', $lead))
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame($manager->id, $lead->assigned_to);
        $this->assertSame(AssignmentRoleLevel::Manager, $lead->assignment_role_level);
    }
}
