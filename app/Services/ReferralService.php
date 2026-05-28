<?php

namespace App\Services;

use App\Models\Hirevo\HirevoReferralRequest;

class ReferralService
{
    public function markStatus(HirevoReferralRequest $referral, string $status): HirevoReferralRequest
    {
        $referral->status = $status;
        if (in_array($status, ['accepted', 'rejected'], true) && ! $referral->responded_at) {
            $referral->responded_at = now();
        }
        $referral->save();

        return $referral;
    }
}

