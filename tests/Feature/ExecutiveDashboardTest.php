<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Support\DashboardPeriod;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_admin_dashboard_shows_both_pipelines(): void
    {
        HirevoLead::query()->create(['candidate_id' => 1, 'status' => 'new']);
        CrmEmployerProspect::query()->create([
            'company_name' => 'Acme',
            'contact_name' => 'Jane',
        ]);

        $admin = Admin::query()->where('email', 'admin@themesdesign.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('CRM dashboard')
            ->assertSee('Talent (candidates)')
            ->assertSee('Company B2B');
    }

    public function test_super_admin_can_view_audit_logs(): void
    {
        $super = Admin::query()->where('email', 'superadmin@themesdesign.test')->firstOrFail();

        $this->actingAs($super, 'admin')
            ->get(route('admin.settings.audit-logs'))
            ->assertOk()
            ->assertSee('Audit logs');
    }

    public function test_admin_cannot_view_audit_logs(): void
    {
        $admin = Admin::query()->where('email', 'admin@themesdesign.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.audit-logs'))
            ->assertForbidden();
    }

    public function test_dashboard_period_presets_resolve(): void
    {
        $period = DashboardPeriod::forPreset('this_month');
        $this->assertSame('this_month', $period->key);
        $this->assertTrue($period->end->gte($period->start));
    }

    public function test_admin_dashboard_period_today_shows_both_pipelines_without_tabs(): void
    {
        HirevoLead::query()->create(['candidate_id' => 1, 'status' => 'new']);
        CrmEmployerProspect::query()->create([
            'company_name' => 'Acme',
            'contact_name' => 'Jane',
        ]);

        $admin = Admin::query()->where('email', 'admin@themesdesign.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Talent (candidates)')
            ->assertSee('Company B2B')
            ->assertSee('What happened · Talent')
            ->assertSee('What happened · Company');
    }

    public function test_talent_executive_sees_only_own_leads_in_period_list(): void
    {
        $exec = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();
        $other = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();

        $mine = HirevoLead::query()->create([
            'candidate_id' => 10,
            'status' => 'new',
            'assigned_to' => $exec->id,
            'updated_at' => now(),
        ]);
        HirevoLead::query()->create([
            'candidate_id' => 11,
            'status' => 'new',
            'assigned_to' => $other->id,
            'updated_at' => now(),
        ]);

        $feed = app(\App\Services\DashboardActivityFeed::class);
        $rows = $feed->myRecordsInPeriod($exec, DashboardPeriod::forPreset('today'), \App\Enums\SalesTeam::Candidate);

        $this->assertCount(1, $rows);
        $this->assertStringContainsString((string) $mine->id, $rows[0]['name']);
    }

    public function test_company_manager_scoped_dashboard_is_employer_pipeline(): void
    {
        $manager = Admin::query()->where('email', 'company.manager@themesdesign.test')->firstOrFail();

        $this->actingAs($manager, 'admin')
            ->get(route('admin.dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Company pipeline')
            ->assertDontSee('Talent (candidates)');
    }
}
