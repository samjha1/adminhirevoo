<?php

namespace App\Services;

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
        if (! Schema::hasTable('crm_employer_prospects')) {
            return 0;
        }

        if (! Schema::hasTable('users')) {
            return 0;
        }

        $count = 0;

        HirevoUser::query()
            ->where('role', 'referrer')
            ->with('referrerProfile')
            ->orderBy('id')
            ->chunk(100, function ($users) use (&$count): void {
                foreach ($users as $user) {
                    $profile = $user->referrerProfile;
                    $prospect = CrmEmployerProspect::query()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'company_name' => $profile?->company_name ?? $user->name ?? 'Company #'.$user->id,
                            'contact_name' => $user->name,
                            'email' => $profile?->company_email ?? $user->email,
                            'phone' => $user->phone,
                            'source' => 'hirevo_signup',
                        ],
                    );
                    if (! $prospect->pipeline_stage) {
                        $prospect->pipeline_stage = 'lead_generated';
                        $prospect->win_probability = 10;
                        $prospect->save();
                    }

                    $this->applyReferralAssignment($prospect->fresh(), $profile?->referral_code);

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
        $profile = $user->referrerProfile;

        $prospect = CrmEmployerProspect::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $profile?->company_name ?? $user->name ?? 'Company #'.$user->id,
                'contact_name' => $user->name,
                'email' => $profile?->company_email ?? $user->email,
                'phone' => $user->phone,
                'source' => 'hirevo_signup',
            ],
        );

        if (! $prospect->pipeline_stage) {
            $prospect->pipeline_stage = 'lead_generated';
            $prospect->win_probability = 10;
            $prospect->save();
        }

        $this->applyReferralAssignment($prospect->fresh(), $profile?->referral_code);

        return $prospect->fresh();
    }

    private function applyReferralAssignment(CrmEmployerProspect $prospect, ?string $referralCode): void
    {
        if ($prospect->assigned_to || ! $referralCode) {
            return;
        }

        $admin = $this->referralCodes->findAdminByCode($referralCode);
        if (! $admin) {
            return;
        }

        $this->assignment->assignFromReferralCode($prospect, $admin);
    }
}
