<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\LeadAssignmentStatus;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoUser;
use App\Services\LeadAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentLeadPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CrmRbacSeeder::class);
        $this->seed(\Database\Seeders\AdminRbacSeeder::class);
    }

    public function test_marketing_sees_unassigned_lead_on_index(): void
    {
        $candidate = HirevoUser::query()->create([
            'name' => 'Pipeline Test Candidate',
            'email' => 'pipeline.test.'.uniqid().'@hirevoo.test',
            'phone' => '9999999999',
            'role' => 'candidate',
        ]);

        HirevoLead::query()->create([
            'candidate_id' => $candidate->id,
            'status' => 'available',
            'match_percentage' => 72,
            'intent_score' => 60,
            'missing_skills' => ['React'],
            'assignment_status' => LeadAssignmentStatus::New->value,
            'referral_source' => 'job_application',
            'lead_summary' => 'job_application',
        ]);

        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->first();
        $this->assertNotNull($marketing);

        $this->actingAs($marketing, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Pipeline Test Candidate')
            ->assertSee('job application');
    }

    public function test_stage_counts_only_include_visible_leads(): void
    {
        HirevoLead::query()->create([
            'candidate_id' => null,
            'status' => 'available',
            'assignment_status' => LeadAssignmentStatus::New->value,
        ]);
        HirevoLead::query()->create([
            'candidate_id' => null,
            'status' => 'available',
            'assignment_status' => LeadAssignmentStatus::New->value,
        ]);

        $exec = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();

        $this->actingAs($exec, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertViewHas('crmStageCounts', fn (array $counts) => array_sum($counts) === 0);
    }

    public function test_executive_does_not_see_unassigned_lead(): void
    {
        $candidate = HirevoUser::query()->create([
            'name' => 'Hidden From Executive',
            'email' => 'hidden.exec.'.uniqid().'@hirevoo.test',
            'role' => 'candidate',
        ]);

        HirevoLead::query()->create([
            'candidate_id' => $candidate->id,
            'status' => 'available',
            'match_percentage' => 50,
            'assignment_status' => LeadAssignmentStatus::New->value,
        ]);

        $exec = Admin::query()->where('email', 'talent.executive@themesdesign.test')->first();
        $this->assertNotNull($exec);

        $this->actingAs($exec, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertDontSee('Hidden From Executive');
    }

    public function test_executive_sees_lead_after_assignment(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        $exec = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();

        $candidate = HirevoUser::query()->create([
            'name' => 'Assigned Executive Lead',
            'email' => 'assigned.exec.'.uniqid().'@hirevoo.test',
            'role' => 'candidate',
        ]);

        $lead = HirevoLead::query()->create([
            'candidate_id' => $candidate->id,
            'status' => 'available',
            'match_percentage' => 80,
            'assignment_status' => LeadAssignmentStatus::New->value,
        ]);

        app(LeadAssignmentService::class)->assignToSalesManager($lead, $manager, $marketing);
        app(LeadAssignmentService::class)->assignToEmployee($lead->fresh(), $exec, $manager);

        $this->actingAs($exec, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Assigned Executive Lead');
    }

    public function test_sales_manager_sees_leads_assigned_to_team_employees(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        $manager = Admin::query()->where('email', 'talent.manager@themesdesign.test')->firstOrFail();
        $exec = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();

        $candidate = HirevoUser::query()->create([
            'name' => 'Manager Team Lead',
            'email' => 'manager.team.'.uniqid().'@hirevoo.test',
            'role' => 'candidate',
        ]);

        $lead = HirevoLead::query()->create([
            'candidate_id' => $candidate->id,
            'status' => 'available',
            'match_percentage' => 80,
            'assignment_status' => LeadAssignmentStatus::New->value,
        ]);

        app(LeadAssignmentService::class)->assignToSalesManager($lead, $manager, $marketing);
        app(LeadAssignmentService::class)->assignToEmployee($lead->fresh(), $exec, $manager);

        $this->actingAs($manager, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Manager Team Lead');

        $orphan = HirevoLead::query()->create([
            'candidate_id' => $candidate->id,
            'status' => 'available',
            'assignment_status' => LeadAssignmentStatus::InProgress->value,
            'assigned_to' => $exec->id,
            'sales_manager_id' => null,
        ]);

        $this->actingAs($manager, 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertViewHas('leads', fn ($paginator) => collect($paginator->items())
                ->contains(fn ($row) => (int) $row->id === (int) $orphan->id));
    }

    public function test_lead_search_by_referral_source(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();

        HirevoLead::query()->create([
            'candidate_id' => null,
            'status' => 'available',
            'referral_source' => 'unique_source_xyz_123',
            'lead_summary' => 'guest_test',
        ]);

        $this->actingAs($marketing, 'admin')
            ->get(route('admin.leads.index', ['q' => 'unique_source_xyz_123']))
            ->assertOk()
            ->assertSee('unique_source_xyz_123');
    }
}
