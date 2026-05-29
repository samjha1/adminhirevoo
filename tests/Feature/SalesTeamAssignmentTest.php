<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Services\EmployerProspectAssignmentService;
use App\Services\LeadAssignmentService;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTeamAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_talent_lead_cannot_assign_to_company_manager(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        $companyManager = Admin::query()->where('email', 'company.manager@themesdesign.test')->firstOrFail();
        $lead = HirevoLead::query()->create(['candidate_id' => 1, 'status' => 'new']);

        $this->expectException(\InvalidArgumentException::class);

        app(LeadAssignmentService::class)->assignToSalesManager($lead, $companyManager, $marketing);
    }

    public function test_company_prospect_assigns_only_to_company_manager(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        $companyManager = Admin::query()->where('email', 'company.manager@themesdesign.test')->firstOrFail();
        $talentManager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();

        $prospect = CrmEmployerProspect::query()->create([
            'company_name' => 'Acme Corp',
            'contact_name' => 'Jane',
        ]);

        app(EmployerProspectAssignmentService::class)->assignToSalesManager($prospect, $companyManager, $marketing);

        $prospect->refresh();
        $this->assertSame($companyManager->id, $prospect->sales_manager_id);

        $this->expectException(\InvalidArgumentException::class);
        app(EmployerProspectAssignmentService::class)->assignToSalesManager($prospect, $talentManager, $marketing);
    }

    public function test_company_executive_cannot_open_talent_pipeline(): void
    {
        $exec = Admin::query()->where('email', 'company.executive@themesdesign.test')->firstOrFail();

        $this->actingAs($exec, 'admin')
            ->get(route('admin.leads.index'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_company_executive_login_home_is_dashboard(): void
    {
        $exec = Admin::query()->where('email', 'company.executive@themesdesign.test')->firstOrFail();

        $this->actingAs($exec, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Company sales');
    }

    public function test_marketing_can_open_both_pipelines(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();

        $this->actingAs($marketing, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk();

        $this->actingAs($marketing, 'admin')
            ->get(route('admin.employers.pipeline.index'))
            ->assertOk();
    }

    public function test_talent_executive_cannot_open_company_pipeline(): void
    {
        $exec = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();

        $this->actingAs($exec, 'admin')
            ->get(route('admin.employers.pipeline.index'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
