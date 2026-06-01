<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Hirevo\HirevoUser;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Services\AdminReferralCodeService;
use App\Services\EmployerProspectSyncService;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployerReferralCodeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_sync_auto_assigns_company_prospect_to_referral_code_owner(): void
    {
        $executive = Admin::query()->where('email', 'company.executive@themesdesign.test')->firstOrFail();
        $code = app(AdminReferralCodeService::class)->ensureCode($executive);
        $this->assertNotEmpty($code);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Referral Test Co',
            'email' => 'referral-test-co@hirevoo.test',
            'phone' => '9999999999',
            'role' => 'referrer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('referrer_profiles')->insert([
            'user_id' => $userId,
            'company_name' => 'Referral Test Co',
            'company_email' => 'referral-test-co@hirevoo.test',
            'referral_code' => $code,
            'is_approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(EmployerProspectSyncService::class)->syncFromHirevo();

        $prospect = CrmEmployerProspect::query()->where('user_id', $userId)->first();
        $this->assertNotNull($prospect);
        $this->assertSame($executive->id, $prospect->assigned_to);
        $this->assertSame('hirevo_referral', $prospect->source);

        $this->actingAs($executive, 'admin')
            ->get(route('admin.employers.pipeline.index'))
            ->assertOk()
            ->assertSee('Referral Test Co');
    }

    public function test_company_manager_referral_code_assigns_to_manager(): void
    {
        $manager = Admin::query()->where('email', 'company.manager@themesdesign.test')->firstOrFail();
        $code = app(AdminReferralCodeService::class)->ensureCode($manager);

        $user = HirevoUser::query()->create([
            'name' => 'Manager Referral Co',
            'email' => 'manager-referral-co@hirevoo.test',
            'phone' => '8888888888',
            'role' => 'referrer',
        ]);

        DB::table('referrer_profiles')->insert([
            'user_id' => $user->id,
            'company_name' => 'Manager Referral Co',
            'company_email' => 'manager-referral-co@hirevoo.test',
            'referral_code' => $code,
            'is_approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $prospect = app(EmployerProspectSyncService::class)->syncReferrerUser($user->fresh());

        $this->assertNotNull($prospect);
        $this->assertSame($manager->id, $prospect->assigned_to);
        $this->assertSame($manager->id, $prospect->sales_manager_id);
    }

    public function test_existing_assignment_is_not_overwritten_by_referral_sync(): void
    {
        $executive = Admin::query()->where('email', 'company.executive@themesdesign.test')->firstOrFail();
        $otherExecutive = Admin::query()->where('email', 'talent.executive@themesdesign.test')->firstOrFail();
        $code = app(AdminReferralCodeService::class)->ensureCode($executive);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Already Assigned Co',
            'email' => 'already-assigned-co@hirevoo.test',
            'phone' => '7777777777',
            'role' => 'referrer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('referrer_profiles')->insert([
            'user_id' => $userId,
            'company_name' => 'Already Assigned Co',
            'company_email' => 'already-assigned-co@hirevoo.test',
            'referral_code' => $code,
            'is_approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CrmEmployerProspect::query()->create([
            'user_id' => $userId,
            'company_name' => 'Already Assigned Co',
            'assigned_to' => $otherExecutive->id,
            'source' => 'manual',
        ]);

        app(EmployerProspectSyncService::class)->syncFromHirevo();

        $prospect = CrmEmployerProspect::query()->where('user_id', $userId)->firstOrFail();
        $this->assertSame($otherExecutive->id, $prospect->assigned_to);
        $this->assertSame('manual', $prospect->source);
    }

    public function test_sync_does_not_overwrite_referral_source_on_resync(): void
    {
        $executive = Admin::query()->where('email', 'company.executive@themesdesign.test')->firstOrFail();
        $code = app(AdminReferralCodeService::class)->ensureCode($executive);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Resync Source Co',
            'email' => 'resync-source-co@hirevoo.test',
            'phone' => '6666666666',
            'role' => 'referrer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('referrer_profiles')->insert([
            'user_id' => $userId,
            'company_name' => 'Resync Source Co',
            'company_email' => 'resync-source-co@hirevoo.test',
            'referral_code' => $code,
            'is_approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sync = app(EmployerProspectSyncService::class);
        $sync->syncFromHirevo();
        $sync->syncFromHirevo();

        $prospect = CrmEmployerProspect::query()->where('user_id', $userId)->firstOrFail();
        $this->assertSame('hirevo_referral', $prospect->source);
        $this->assertSame($executive->id, $prospect->assigned_to);
    }

    public function test_backfill_assigns_existing_unassigned_prospects(): void
    {
        $executive = Admin::query()->where('email', 'company.executive@themesdesign.test')->firstOrFail();
        $code = app(AdminReferralCodeService::class)->ensureCode($executive);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Backfill Co',
            'email' => 'backfill-co@hirevoo.test',
            'phone' => '5555555555',
            'role' => 'referrer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('referrer_profiles')->insert([
            'user_id' => $userId,
            'company_name' => 'Backfill Co',
            'company_email' => 'backfill-co@hirevoo.test',
            'referral_code' => $code,
            'is_approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CrmEmployerProspect::query()->create([
            'user_id' => $userId,
            'company_name' => 'Backfill Co',
            'source' => 'hirevo_signup',
        ]);

        $assigned = app(EmployerProspectSyncService::class)->backfillReferralAssignments();
        $this->assertSame(1, $assigned);

        $prospect = CrmEmployerProspect::query()->where('user_id', $userId)->firstOrFail();
        $this->assertSame($executive->id, $prospect->assigned_to);
        $this->assertSame('hirevo_referral', $prospect->source);
    }
}
