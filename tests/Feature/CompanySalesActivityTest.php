<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Modules\Leads\Models\CrmCompanyActivity;
use App\Modules\Leads\Models\CrmEmployerProspect;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySalesActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_sales_employee_can_view_own_activity_page(): void
    {
        $employee = Admin::query()->where('email', 'company.executive1@themesdesign.test')->firstOrFail();

        $this->actingAs($employee, 'admin')
            ->get(route('admin.employers.activity.my'))
            ->assertOk()
            ->assertSee('My activity');
    }

    public function test_sales_employee_is_redirected_from_team_activity(): void
    {
        $employee = Admin::query()->where('email', 'company.executive1@themesdesign.test')->firstOrFail();

        $this->actingAs($employee, 'admin')
            ->get(route('admin.employers.activity.team'))
            ->assertRedirect(route('admin.employers.activity.my'));
    }

    public function test_sales_manager_can_view_team_activity(): void
    {
        $manager = Admin::query()->where('email', 'company.manager1@themesdesign.test')->firstOrFail();

        $this->actingAs($manager, 'admin')
            ->get(route('admin.employers.activity.team'))
            ->assertOk()
            ->assertSee('Team activity');
    }

    public function test_manager_sees_employee_activity_in_team_feed(): void
    {
        $manager = Admin::query()->where('email', 'company.manager1@themesdesign.test')->firstOrFail();
        $employee = Admin::query()->where('email', 'company.executive1@themesdesign.test')->firstOrFail();

        $prospect = CrmEmployerProspect::query()->create([
            'company_name' => 'Activity Test Co',
            'assigned_to' => $employee->id,
            'sales_manager_id' => $manager->id,
        ]);

        CrmCompanyActivity::query()->create([
            'employer_prospect_id' => $prospect->id,
            'admin_id' => $employee->id,
            'type' => 'stage_change',
            'title' => 'Moved to Contacted',
            'payload' => ['stage' => 'contacted'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($manager, 'admin')
            ->get(route('admin.employers.activity.team', ['period' => 'today', 'staff_id' => $employee->id]))
            ->assertOk()
            ->assertSee('Activity Test Co')
            ->assertSee('Moved to Contacted')
            ->assertSee($employee->name);
    }

    public function test_admin_can_view_all_team_activity(): void
    {
        $admin = Admin::query()->where('email', 'admin@themesdesign.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.employers.activity.team'))
            ->assertOk()
            ->assertSee('Team activity');
    }
}
