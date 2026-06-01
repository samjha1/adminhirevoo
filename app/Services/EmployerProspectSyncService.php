<?php

namespace App\Services;

use App\Models\Hirevo\HirevoReferrerProfile;
use App\Models\Hirevo\HirevoUser;
use App\Modules\Leads\Models\CrmEmployerProspect;
use Illuminate\Support\Facades\Schema;

class EmployerProspectSyncService
{
    public function __construct(
        private readonly AdminReferralCodeService $referralCodes,
        private readonly EmployerProspectAssignmentService $assignment,
    ) {
    }

    public function syncFromHirevo(): int
    {
        if (! Schema::hasTable('crm_employer_prospects') || ! Schema::hasTable('users')) {
            return 0;
        }

        $count = 0;

        HirevoUser::query()
            ->where('role', 'referrer')
            ->with('referrerProfile')
            ->orderBy('id')
            ->chunk(100, function ($users) use (&$count): void {
                foreach ($users as $user) {
                    $prospect = $this->upsertProspect($user, $user->referrerProfile);
                    $this->applyReferralAssignment($prospect, $user->referrerProfile?->referral_code);
                    $count++;
                }
            });

        return $count;
    }

    public function syncReferrerUser(HirevoUser $user): ?CrmEmployerProspect
    {
        if (! Schema::hasTable('crm_employer_prospects') || $user->role !== 'referrer') {
            return null;
        }

        $user->loadMissing('referrerProfile');
        $prospect = $this->upsertProspect($user, $user->referrerProfile);
        $this->applyReferralAssignment($prospect->fresh(), $user->referrerProfile?->referral_code);

        return $prospect->fresh();
    }

    public function backfillReferralAssignments(): int
    {
        if (! Schema::hasTable('crm_employer_prospects') || ! Schema::hasTable('referrer_profiles')) {
            return 0;
        }

        $count = 0;

        CrmEmployerProspect::query()
            ->whereNull('assigned_to')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunk(100, function ($prospects) use (&$count): void {
                foreach ($prospects as $prospect) {
                    $code = HirevoReferrerProfile::query()
                        ->where('user_id', $prospect->user_id)
                        ->value('referral_code');

                    if (! $code) {
                        continue;
                    }

                    $before = $prospect->assigned_to;
                    $this->applyReferralAssignment($prospect->fresh(), $code);

                    if ($prospect->fresh()->assigned_to && ! $before) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function upsertProspect(HirevoUser $user, ?HirevoReferrerProfile $profile): CrmEmployerProspect
    {
        $prospect = CrmEmployerProspect::query()->firstOrNew(['user_id' => $user->id]);

        $prospect->company_name = $profile?->company_name ?? $user->name ?? 'Company #'.$user->id;
        $prospect->contact_name = $user->name;
        $prospect->email = $profile?->company_email ?? $user->email;
        $prospect->phone = $user->phone;

        if (! $prospect->exists) {
            $prospect->source = 'hirevo_signup';
            $prospect->pipeline_stage = 'lead_generated';
            $prospect->win_probability = 10;
        }

        if (! $prospect->pipeline_stage) {
            $prospect->pipeline_stage = 'lead_generated';
            $prospect->win_probability = 10;
        }

        $prospect->save();

        return $prospect;
    }

    private function applyReferralAssignment(CrmEmployerProspect $prospect, ?string $referralCode): void
    {
        if ($prospect->assigned_to || ! filled($referralCode)) {
            return;
        }

        $admin = $this->referralCodes->findAdminByCode($referralCode);
        if (! $admin) {
            return;
        }

        $this->assignment->assignFromReferralCode($prospect, $admin);
    }
}
