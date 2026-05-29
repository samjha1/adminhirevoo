<?php

namespace Database\Seeders;

use App\Enums\AssignmentRoleLevel;
use App\Enums\CompanyB2bPipelineStage;
use App\Enums\LeadAssignmentStatus;
use App\Enums\LeadSalesStatus;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Models\AdminLeadStage;
use App\Models\Hirevo\HirevoCareerConsultationRequest;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoUser;
use App\Modules\Leads\Enums\CallOutcome;
use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmCompanyActivity;
use App\Modules\Leads\Models\CrmCompanyClient;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmCompanyProposal;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Models\CrmLeadActivity;
use App\Modules\Leads\Models\CrmLeadNote;
use App\Modules\Leads\Models\CrmStandaloneLead;
use App\Modules\Leads\Services\CompanyB2bPipelineService;
use App\Services\LeadPipelineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmDemoDataSeeder extends Seeder
{
    public const EMAIL_PREFIX = 'crm-demo.';

    public const NOTE_TAG = '[CRM-DEMO]';

    private const COUNTS = [
        'talent_leads' => 30,
        'companies' => 30,
        'standalone' => 25,
        'consultations' => 12,
        'talent_calls' => 45,
        'talent_followups' => 28,
        'talent_notes' => 30,
        'talent_activities' => 25,
        'company_meetings' => 28,
        'company_proposals' => 22,
        'company_calls' => 18,
        'company_activities' => 35,
    ];

    /** @var array<string, Admin|null> */
    private array $staff = [];

    public function run(): void
    {
        $this->resolveStaff();

        $this->command?->info('Removing previous CRM demo records…');
        $this->purge();

        $this->command?->info('Seeding talent pipeline ('.self::COUNTS['talent_leads'].' leads)…');
        $leadIds = $this->seedTalentLeads();

        $this->command?->info('Seeding company B2B pipeline ('.self::COUNTS['companies'].' companies)…');
        $prospectIds = $this->seedCompanyProspects();

        $this->command?->info('Seeding marketing standalone leads…');
        $this->seedStandaloneLeads();

        $this->command?->info('Seeding talent ops (calls, follow-ups, notes)…');
        $this->seedTalentOps($leadIds);

        $this->command?->info('Seeding company ops (meetings, proposals, clients)…');
        $this->seedCompanyOps($prospectIds);

        $this->command?->info('Seeding career consultations…');
        $this->seedConsultations();

        $this->command?->newLine();
        $this->command?->info('CRM demo data ready. Log in as:');
        $this->command?->line('  • company.executive@themesdesign.test — Companies + dashboard');
        $this->command?->line('  • talent.executive@themesdesign.test — Candidates + kanban');
        $this->command?->line('  • marketing@themesdesign.test — Both pipelines + marketing leads');
        $this->command?->line('Password for all: password');
    }

    private function resolveStaff(): void
    {
        $this->staff = [
            'marketing' => Admin::query()->where('email', 'marketing@themesdesign.test')->first(),
            'talent_manager' => Admin::query()->where('email', 'talent.manager@themesdesign.test')->first(),
            'talent_executive' => Admin::query()->where('email', 'talent.executive@themesdesign.test')->first(),
            'company_manager' => Admin::query()->where('email', 'company.manager@themesdesign.test')->first(),
            'company_executive' => Admin::query()->where('email', 'company.executive@themesdesign.test')->first(),
        ];
    }

    private function purge(): void
    {
        $demoUserIds = HirevoUser::query()
            ->where('email', 'like', self::EMAIL_PREFIX.'%')
            ->pluck('id');

        $demoLeadIds = HirevoLead::query()
            ->whereIn('candidate_id', $demoUserIds)
            ->pluck('id');

        $demoProspectIds = CrmEmployerProspect::query()
            ->where('email', 'like', self::EMAIL_PREFIX.'%')
            ->orWhere('notes', 'like', '%'.self::NOTE_TAG.'%')
            ->pluck('id');

        if (Schema::hasTable('crm_company_activities')) {
            CrmCompanyActivity::query()->whereIn('employer_prospect_id', $demoProspectIds)->delete();
        }
        if (Schema::hasTable('crm_company_meetings')) {
            CrmCompanyMeeting::query()->whereIn('employer_prospect_id', $demoProspectIds)->delete();
        }
        if (Schema::hasTable('crm_company_proposals')) {
            CrmCompanyProposal::query()->whereIn('employer_prospect_id', $demoProspectIds)->delete();
        }
        if (Schema::hasTable('crm_company_clients')) {
            CrmCompanyClient::query()->whereIn('employer_prospect_id', $demoProspectIds)->delete();
        }

        if (Schema::hasTable('crm_call_logs')) {
            CrmCallLog::query()
                ->where(function ($q) use ($demoLeadIds, $demoProspectIds) {
                    $q->whereIn('lead_id', $demoLeadIds);
                    if (Schema::hasColumn('crm_call_logs', 'employer_prospect_id')) {
                        $q->orWhereIn('employer_prospect_id', $demoProspectIds);
                    }
                })
                ->delete();
        }

        if (Schema::hasTable('crm_follow_ups')) {
            CrmFollowUp::query()->whereIn('lead_id', $demoLeadIds)->forceDelete();
        }
        if (Schema::hasTable('crm_lead_activities')) {
            CrmLeadActivity::query()->whereIn('lead_id', $demoLeadIds)->delete();
        }
        if (Schema::hasTable('crm_lead_notes')) {
            CrmLeadNote::query()->whereIn('lead_id', $demoLeadIds)->forceDelete();
        }

        AdminLeadStage::query()->whereIn('lead_id', $demoLeadIds)->delete();
        HirevoLead::query()->whereIn('id', $demoLeadIds)->delete();

        CrmEmployerProspect::query()->whereIn('id', $demoProspectIds)->delete();

        CrmStandaloneLead::query()
            ->where('email', 'like', self::EMAIL_PREFIX.'%')
            ->delete();

        if (Schema::hasTable('career_consultation_requests')) {
            HirevoCareerConsultationRequest::query()
                ->whereIn('user_id', $demoUserIds)
                ->delete();
        }

        if (Schema::hasTable('candidate_profiles')) {
            DB::table('candidate_profiles')->whereIn('user_id', $demoUserIds)->delete();
        }

        HirevoUser::query()->whereIn('id', $demoUserIds)->delete();
    }

    /** @return list<int> */
    private function seedTalentLeads(): array
    {
        $stages = app(LeadPipelineService::class)->managementStages();
        $hirevoStatuses = ['available', 'bidding', 'sold', 'contact_unlocked'];
        $firstNames = ['Aarav', 'Diya', 'Vihaan', 'Ananya', 'Kabir', 'Isha', 'Rohan', 'Meera', 'Arjun', 'Sneha', 'Karan', 'Priya', 'Nikhil', 'Riya', 'Aditya'];
        $lastNames = ['Sharma', 'Patel', 'Gupta', 'Singh', 'Kumar', 'Reddy', 'Nair', 'Iyer', 'Joshi', 'Mehta'];
        $roles = ['Software Engineer', 'Product Manager', 'Data Analyst', 'UX Designer', 'DevOps Engineer', 'QA Engineer'];
        $cities = ['Bangalore', 'Mumbai', 'Delhi NCR', 'Hyderabad', 'Pune', 'Chennai', 'Ahmedabad'];

        $manager = $this->staff['talent_manager'];
        $executive = $this->staff['talent_executive'];
        $marketing = $this->staff['marketing'];

        $leadIds = [];

        for ($i = 1; $i <= self::COUNTS['talent_leads']; $i++) {
            $email = self::EMAIL_PREFIX.'candidate.'.$i.'@hirevoo.test';
            $name = $firstNames[($i - 1) % count($firstNames)].' '.$lastNames[($i - 1) % count($lastNames)];

            $userId = $this->insertHirevoUser([
                'name' => $name,
                'email' => $email,
                'phone' => '9'.str_pad((string) (8000000000 + $i), 9, '0', STR_PAD_LEFT),
                'role' => 'candidate',
                'status' => 'active',
            ]);

            $this->maybeInsertCandidateProfile($userId, $roles[$i % count($roles)], $cities[$i % count($cities)]);

            $assignExecutive = $i % 4 !== 0;
            $assignedTo = $assignExecutive ? $executive?->id : null;
            $managerId = $assignExecutive || $i % 7 === 0 ? $manager?->id : null;

            $lead = HirevoLead::query()->create([
                'candidate_id' => $userId,
                'status' => $hirevoStatuses[($i - 1) % count($hirevoStatuses)],
                'match_percentage' => random_int(42, 96),
                'intent_score' => random_int(25, 92),
                'missing_skills' => collect(['SQL', 'React', 'AWS', 'Communication', 'Python'])->random(random_int(1, 3))->values()->all(),
                'assigned_to' => $assignedTo,
                'assigned_by' => $assignedTo ? ($marketing?->id ?? $manager?->id) : null,
                'sales_manager_id' => $managerId,
                'assignment_role_level' => $assignedTo ? AssignmentRoleLevel::Employee->value : ($managerId ? AssignmentRoleLevel::Manager->value : null),
                'assignment_status' => $assignedTo ? LeadAssignmentStatus::Assigned->value : LeadAssignmentStatus::New->value,
                'sales_status' => collect(LeadSalesStatus::cases())->random()->value,
                'created_at' => now()->subDays(random_int(0, 45)),
                'updated_at' => now()->subDays(random_int(0, 5)),
            ]);

            $stage = $stages[($i - 1) % count($stages)];
            if ($stage !== 'new') {
                AdminLeadStage::query()->create([
                    'lead_id' => $lead->id,
                    'stage' => $stage,
                    'notes' => self::NOTE_TAG.' Demo kanban card',
                    'last_contacted_at' => now()->subDays(random_int(0, 14)),
                ]);
            }

            $leadIds[] = $lead->id;
        }

        return $leadIds;
    }

    /** @return list<int> */
    private function seedCompanyProspects(): array
    {
        $stages = array_merge(CompanyB2bPipelineStage::ordered(), [CompanyB2bPipelineStage::Lost, CompanyB2bPipelineStage::Lost]);
        $industries = ['SaaS', 'Fintech', 'E-commerce', 'Healthcare', 'EdTech', 'Manufacturing', 'Logistics', 'Retail'];
        $sizes = ['11-50', '51-200', '201-500', '500+'];
        $sources = ['LinkedIn', 'Website', 'Referral', 'Cold outreach', 'Event', 'Inbound'];
        $packages = ['Starter 5 roles', 'Growth 15 roles', 'Enterprise unlimited', 'Pilot 3 roles'];

        $manager = $this->staff['company_manager'];
        $executive = $this->staff['company_executive'];
        $marketing = $this->staff['marketing'];

        $pipeline = app(CompanyB2bPipelineService::class);
        $ids = [];

        for ($i = 1; $i <= self::COUNTS['companies']; $i++) {
            $stage = $stages[($i - 1) % count($stages)];
            $dealValue = (float) (random_int(8, 45) * 10000);
            $probability = $stage->winProbability();
            $assignExecutive = $i % 5 !== 0;

            $prospect = CrmEmployerProspect::query()->create([
                'company_name' => 'Demo '.($industries[$i % count($industries)]).' Co '.$i,
                'industry' => $industries[$i % count($industries)],
                'website' => 'https://demo-company-'.$i.'.hirevoo.test',
                'company_size' => $sizes[$i % count($sizes)],
                'location' => collect(['Bangalore', 'Mumbai', 'Gurgaon', 'Hyderabad', 'Pune'])->random(),
                'contact_name' => 'Contact '.$i,
                'contact_designation' => collect(['HR Head', 'Talent Lead', 'CEO', 'COO'])->random(),
                'email' => self::EMAIL_PREFIX.'company.'.$i.'@hirevoo.test',
                'phone' => '8'.str_pad((string) (7000000000 + $i), 9, '0', STR_PAD_LEFT),
                'linkedin_url' => 'https://linkedin.com/in/demo-contact-'.$i,
                'source' => $sources[$i % count($sources)],
                'notes' => self::NOTE_TAG.' B2B demo prospect for pipeline testing.',
                'created_by' => $marketing?->id,
                'assigned_to' => $assignExecutive ? $executive?->id : null,
                'assigned_by' => $assignExecutive ? ($manager?->id ?? $marketing?->id) : null,
                'sales_manager_id' => $manager?->id,
                'assignment_role_level' => $assignExecutive ? AssignmentRoleLevel::Employee->value : AssignmentRoleLevel::Manager->value,
                'assignment_status' => LeadAssignmentStatus::Assigned->value,
                'sales_status' => LeadSalesStatus::Contacted->value,
                'pipeline_stage' => $stage->value,
                'follow_up_at' => now()->addDays(random_int(-2, 10)),
                'last_activity_at' => $i <= 5 ? now() : now()->subDays(random_int(0, 20)),
                'deal_value' => $dealValue,
                'win_probability' => $probability,
                'expected_revenue' => round($dealValue * $probability / 100, 2),
                'proposal_status' => in_array($stage, [CompanyB2bPipelineStage::ProposalSent, CompanyB2bPipelineStage::Negotiation], true)
                    ? 'sent'
                    : null,
                'created_at' => now()->subDays(random_int(5, 60)),
            ]);

            if (in_array($stage, [CompanyB2bPipelineStage::Won, CompanyB2bPipelineStage::Onboarding, CompanyB2bPipelineStage::HiringActive, CompanyB2bPipelineStage::Renewed], true)) {
                CrmCompanyClient::query()->firstOrCreate(
                    ['employer_prospect_id' => $prospect->id],
                    [
                        'account_manager_id' => $executive?->id,
                        'package_purchased' => $packages[$i % count($packages)],
                        'start_date' => now()->subMonths(random_int(1, 8))->toDateString(),
                        'renewal_date' => now()->addMonths(random_int(2, 10))->toDateString(),
                        'active_positions' => random_int(2, 18),
                    ],
                );
            }

            $actor = $marketing ?? $manager ?? $executive;
            if ($actor) {
                $pipeline->moveToStage($prospect->fresh(), $stage->value, $actor);
            }

            $ids[] = $prospect->id;
        }

        return $ids;
    }

    private function seedStandaloneLeads(): void
    {
        $sources = ['Google Ads', 'Meta', 'Webinar', 'Partner', 'Organic'];
        $marketing = $this->staff['marketing'];
        $talentManager = $this->staff['talent_manager'];

        for ($i = 1; $i <= self::COUNTS['standalone']; $i++) {
            CrmStandaloneLead::query()->create([
                'name' => 'Marketing lead '.$i,
                'phone' => '7'.str_pad((string) (6000000000 + $i), 9, '0', STR_PAD_LEFT),
                'email' => self::EMAIL_PREFIX.'marketing.'.$i.'@hirevoo.test',
                'source' => $sources[$i % count($sources)],
                'notes' => self::NOTE_TAG.' Imported via marketing campaign.',
                'created_by' => $marketing?->id,
                'assigned_to' => $i % 3 === 0 ? $talentManager?->id : null,
                'sales_manager_id' => $i % 2 === 0 ? $talentManager?->id : null,
                'assignment_status' => $i % 3 === 0 ? LeadAssignmentStatus::Assigned->value : LeadAssignmentStatus::New->value,
                'sales_status' => collect(LeadSalesStatus::cases())->random()->value,
                'created_at' => now()->subDays(random_int(0, 30)),
            ]);
        }
    }

    /** @param  list<int>  $leadIds */
    private function seedTalentOps(array $leadIds): void
    {
        if ($leadIds === []) {
            return;
        }

        $executive = $this->staff['talent_executive'];
        $outcomes = CallOutcome::cases();

        for ($n = 0; $n < self::COUNTS['talent_calls']; $n++) {
            $leadId = $leadIds[array_rand($leadIds)];
            $calledAt = $n < 8 ? now()->setTime(random_int(9, 18), random_int(0, 59)) : now()->subDays(random_int(0, 20));

            CrmCallLog::query()->create([
                'lead_id' => $leadId,
                'admin_id' => $executive?->id ?? 1,
                'outcome' => $outcomes[array_rand($outcomes)]->value,
                'duration_seconds' => random_int(45, 900),
                'notes' => self::NOTE_TAG.' Demo call log.',
                'called_at' => $calledAt,
            ]);
        }

        for ($n = 0; $n < self::COUNTS['talent_followups']; $n++) {
            $leadId = $leadIds[array_rand($leadIds)];
            $scheduled = $n < 6
                ? now()->setTime(random_int(10, 17), 0)
                : now()->addDays(random_int(1, 14));

            CrmFollowUp::query()->create([
                'lead_id' => $leadId,
                'admin_id' => $executive?->id ?? 1,
                'scheduled_at' => $scheduled,
                'status' => $n % 4 === 0 ? FollowUpStatus::Completed->value : FollowUpStatus::Pending->value,
                'notes' => self::NOTE_TAG.' Follow-up reminder.',
                'completed_at' => $n % 4 === 0 ? now()->subDay() : null,
            ]);
        }

        foreach (array_slice($leadIds, 0, self::COUNTS['talent_notes']) as $leadId) {
            CrmLeadNote::query()->create([
                'lead_id' => $leadId,
                'admin_id' => $executive?->id ?? 1,
                'body' => self::NOTE_TAG.' Candidate interested in remote roles; send JD pack.',
            ]);
        }

        for ($n = 0; $n < self::COUNTS['talent_activities']; $n++) {
            CrmLeadActivity::query()->create([
                'lead_id' => $leadIds[array_rand($leadIds)],
                'admin_id' => $executive?->id,
                'type' => collect(['call', 'email', 'stage_change', 'note'])->random(),
                'title' => 'Demo activity '.($n + 1),
                'payload' => ['demo' => true],
            ]);
        }
    }

    /** @param  list<int>  $prospectIds */
    private function seedCompanyOps(array $prospectIds): void
    {
        if ($prospectIds === []) {
            return;
        }

        $executive = $this->staff['company_executive'];
        $types = ['discovery', 'demo', 'negotiation', 'check-in'];
        $outcomes = ['positive', 'neutral', 'follow_up_needed', 'no_show'];
        $proposalStatuses = ['draft', 'sent', 'sent', 'viewed', 'negotiation', 'won', 'lost'];

        for ($n = 0; $n < self::COUNTS['company_meetings']; $n++) {
            $prospectId = $prospectIds[array_rand($prospectIds)];
            $meetingAt = $n < 6
                ? now()->setTime(random_int(10, 17), 0)
                : now()->subDays(random_int(0, 25));

            CrmCompanyMeeting::query()->create([
                'employer_prospect_id' => $prospectId,
                'admin_id' => $executive?->id,
                'meeting_at' => $meetingAt,
                'meeting_type' => $types[$n % count($types)],
                'outcome' => $outcomes[$n % count($outcomes)],
                'attendees' => 'HR + Hiring manager',
                'notes' => self::NOTE_TAG.' Demo meeting notes.',
                'next_action' => 'Send proposal / schedule demo',
            ]);
        }

        for ($n = 0; $n < self::COUNTS['company_proposals']; $n++) {
            $prospectId = $prospectIds[array_rand($prospectIds)];
            $value = (float) (random_int(10, 50) * 10000);

            CrmCompanyProposal::query()->create([
                'employer_prospect_id' => $prospectId,
                'admin_id' => $executive?->id,
                'sent_at' => now()->subDays(random_int(0, 20))->toDateString(),
                'package_offered' => 'Hirevoo Growth — '.random_int(5, 20).' roles',
                'package_value' => $value,
                'discount_percent' => collect([0, 5, 10, 15])->random(),
                'expected_revenue' => $value * 0.9,
                'status' => $proposalStatuses[$n % count($proposalStatuses)],
            ]);
        }

        if (Schema::hasTable('crm_call_logs') && Schema::hasColumn('crm_call_logs', 'employer_prospect_id')) {
            $outcomes = CallOutcome::cases();
            for ($n = 0; $n < self::COUNTS['company_calls']; $n++) {
                $prospectId = $prospectIds[array_rand($prospectIds)];
                $calledAt = $n < 5 ? now() : now()->subDays(random_int(0, 10));

                CrmCallLog::query()->create([
                    'lead_id' => 0,
                    'employer_prospect_id' => $prospectId,
                    'admin_id' => $executive?->id ?? 1,
                    'outcome' => $outcomes[array_rand($outcomes)]->value,
                    'duration_seconds' => random_int(60, 1200),
                    'notes' => self::NOTE_TAG.' B2B outbound call.',
                    'called_at' => $calledAt,
                ]);
            }
        }

        for ($n = 0; $n < self::COUNTS['company_activities']; $n++) {
            CrmCompanyActivity::query()->create([
                'employer_prospect_id' => $prospectIds[array_rand($prospectIds)],
                'admin_id' => $executive?->id,
                'type' => collect(['call', 'email', 'meeting', 'proposal'])->random(),
                'title' => 'Company touchpoint '.($n + 1),
                'payload' => ['demo' => true],
            ]);
        }
    }

    private function seedConsultations(): void
    {
        if (! Schema::hasTable('career_consultation_requests')) {
            return;
        }

        for ($i = 1; $i <= self::COUNTS['consultations']; $i++) {
            $userId = $this->insertHirevoUser([
                'name' => 'Consultation user '.$i,
                'email' => self::EMAIL_PREFIX.'consult.'.$i.'@hirevoo.test',
                'phone' => '6'.str_pad((string) (5000000000 + $i), 9, '0', STR_PAD_LEFT),
                'role' => 'candidate',
                'status' => 'active',
            ]);

            HirevoCareerConsultationRequest::query()->create([
                'user_id' => $userId,
                'status' => $i <= 4 ? 'pending' : collect(['pending', 'contacted', 'closed'])->random(),
                'source' => collect(['website', 'app', 'referral'])->random(),
                'match_percentage' => random_int(50, 90),
                'created_at' => now()->subDays(random_int(0, 14)),
            ]);
        }
    }

  /** @param  array<string, mixed>  $data */
    private function insertHirevoUser(array $data): int
    {
        $row = ['created_at' => now(), 'updated_at' => now()];

        foreach (['name', 'email', 'phone', 'role', 'status'] as $col) {
            if (Schema::hasColumn('users', $col) && isset($data[$col])) {
                $row[$col] = $data[$col];
            }
        }

        if (Schema::hasColumn('users', 'password') && ! isset($row['password'])) {
            $row['password'] = bcrypt('password');
        }

        if (Schema::hasColumn('users', 'email_verified_at') && ! isset($row['email_verified_at'])) {
            $row['email_verified_at'] = now();
        }

        return (int) DB::table('users')->insertGetId($row);
    }

    private function maybeInsertCandidateProfile(int $userId, string $role, string $city): void
    {
        if (! Schema::hasTable('candidate_profiles')) {
            return;
        }

        $row = [
            'user_id' => $userId,
            'preferred_job_role' => $role,
            'preferred_job_location' => $city,
            'experience_years' => random_int(1, 12),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('candidate_profiles', 'headline')) {
            $row['headline'] = $role.' · '.$city;
        }

        DB::table('candidate_profiles')->insert($row);
    }
}
