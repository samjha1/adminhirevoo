<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoUser;
use App\Modules\Leads\Models\CrmLeadActivity;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentSalesActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_sales_employee_can_view_own_talent_activity_page(): void
    {
        $employee = Admin::query()->where('email', 'talent.executive1@themesdesign.test')->firstOrFail();

        $this->actingAs($employee, 'admin')
            ->get(route('admin.leads.activity.my'))
            ->assertOk()
            ->assertSee('My activity');
    }

    public function test_sales_employee_is_redirected_from_talent_team_activity(): void
    {
        $employee = Admin::query()->where('email', 'talent.executive1@themesdesign.test')->firstOrFail();

        $this->actingAs($employee, 'admin')
            ->get(route('admin.leads.activity.team'))
            ->assertRedirect(route('admin.leads.activity.my'));
    }

    public function test_sales_manager_sees_employee_talent_activity(): void
    {
        $manager = Admin::query()->where('email', 'talent.manager1@themesdesign.test')->firstOrFail();
        $employee = Admin::query()->where('email', 'talent.executive1@themesdesign.test')->firstOrFail();

        $candidate = HirevoUser::query()->create([
            'name' => 'Activity Candidate',
            'email' => 'activity.candidate@themesdesign.test',
            'password' => bcrypt('password'),
            'role' => 'candidate',
        ]);

        $lead = HirevoLead::query()->withoutGlobalScopes()->create([
            'candidate_id' => $candidate->id,
            'status' => 'new',
            'assigned_to' => $employee->id,
            'sales_manager_id' => $manager->id,
        ]);

        CrmLeadActivity::query()->create([
            'lead_id' => $lead->id,
            'admin_id' => $employee->id,
            'type' => 'call',
            'title' => 'Call logged: Interested',
            'payload' => ['outcome' => 'interested'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($manager, 'admin')
            ->get(route('admin.leads.activity.team', ['period' => 'today', 'staff_id' => $employee->id]))
            ->assertOk()
            ->assertSee('Call logged: Interested')
            ->assertSee('Activity Candidate')
            ->assertSee($employee->name);
    }
}
