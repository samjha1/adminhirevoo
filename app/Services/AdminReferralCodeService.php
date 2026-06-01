<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Illuminate\Support\Str;

class AdminReferralCodeService
{
    public function findAdminByCode(?string $code): ?Admin
    {
        $normalized = $this->normalize($code);
        if ($normalized === '') {
            return null;
        }

        return Admin::query()
            ->whereRaw('UPPER(referral_code) = ?', [$normalized])
            ->where('sales_team', SalesTeam::Employer->value)
            ->whereIn('role', [AdminRole::SalesManager, AdminRole::SalesEmployee])
            ->first();
    }

    /**
     * Set a manual referral code or auto-generate when eligible and none provided.
     */
    public function assignCode(Admin $admin, ?string $requested = null): ?string
    {
        if (! $this->isEligible($admin)) {
            if ($admin->referral_code) {
                $admin->referral_code = null;
                $admin->save();
            }

            return null;
        }

        $normalized = $this->normalize($requested);
        if ($normalized !== '') {
            $admin->referral_code = $normalized;
            $admin->save();

            return $admin->referral_code;
        }

        return $this->ensureCode($admin);
    }

    public function ensureCode(Admin $admin): ?string
    {
        if (! $this->isEligible($admin)) {
            return null;
        }

        if ($admin->referral_code) {
            return $admin->referral_code;
        }

        $admin->referral_code = $this->generateUniqueCode();
        $admin->save();

        return $admin->referral_code;
    }

    public function generatePreviewCode(): string
    {
        return $this->generateUniqueCode();
    }

    public function backfillEmployerTeamCodes(): int
    {
        $count = 0;

        Admin::query()
            ->where('sales_team', SalesTeam::Employer->value)
            ->whereIn('role', [AdminRole::SalesManager, AdminRole::SalesEmployee])
            ->whereNull('referral_code')
            ->orderBy('id')
            ->each(function (Admin $admin) use (&$count): void {
                if ($this->ensureCode($admin)) {
                    $count++;
                }
            });

        return $count;
    }

    public function isEligible(Admin $admin): bool
    {
        return $admin->sales_team === SalesTeam::Employer
            && in_array($admin->role, [AdminRole::SalesManager, AdminRole::SalesEmployee], true);
    }

    private function normalize(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'EMP-'.strtoupper(Str::random(6));
        } while (Admin::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
