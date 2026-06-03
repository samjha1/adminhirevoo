<?php

namespace App\Services;

use App\Models\Hirevo\HirevoPayment;
use App\Models\Hirevo\HirevoReferrerProfile;
use Carbon\Carbon;
use InvalidArgumentException;

class EmployerPlanPaymentService
{
    public function completePayment(HirevoPayment $payment): HirevoPayment
    {
        if ($payment->status === HirevoPayment::STATUS_COMPLETED) {
            return $payment;
        }

        if ($payment->type !== HirevoPayment::TYPE_EMPLOYER_SUBSCRIPTION) {
            throw new InvalidArgumentException('Payment is not an employer subscription.');
        }

        $planKey = strtolower(trim((string) ($payment->meta['plan_key'] ?? '')));
        if ($planKey === '') {
            throw new InvalidArgumentException('Payment is missing plan_key in meta.');
        }

        $profile = HirevoReferrerProfile::query()
            ->where('user_id', $payment->user_id)
            ->first();

        if ($profile === null) {
            throw new InvalidArgumentException('Employer profile not found for this payment.');
        }

        $payment->update(['status' => HirevoPayment::STATUS_COMPLETED]);

        $this->activateSubscription($profile, $planKey);

        $plan = config("hirevo_plans.plans.{$planKey}");
        if (isset($plan['database_credits_included']) && is_numeric($plan['database_credits_included'])) {
            $credits = (int) $plan['database_credits_included'];
            if ($credits > 0) {
                $profile->increment('credits', $credits);
            }
        }

        return $payment->fresh();
    }

    public function activateSubscription(HirevoReferrerProfile $profile, string $planKey, ?Carbon $expiresAt = null): void
    {
        $planKey = strtolower(trim($planKey));

        if (config("hirevo_plans.plans.{$planKey}") === null) {
            throw new InvalidArgumentException("Unknown plan: {$planKey}");
        }

        $startsAt = now();
        $expiresAt ??= $startsAt->copy()->addMonth();

        $profile->update([
            'subscription_plan' => $planKey,
            'subscription_started_at' => $startsAt,
            'subscription_expires_at' => $expiresAt,
        ]);
    }
}
