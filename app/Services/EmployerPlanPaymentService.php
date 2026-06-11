<?php

namespace App\Services;

use App\Models\Hirevo\HirevoEmployerPlan;
use App\Models\Hirevo\HirevoPayment;
use App\Models\Hirevo\HirevoReferrerProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployerPlanPaymentService
{
    public function needsActivation(HirevoPayment $payment): bool
    {
        return empty($payment->meta['subscription_activated_at']);
    }

    public function completePayment(HirevoPayment $payment): HirevoPayment
    {
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

        $meta = $payment->meta ?? [];
        if (! empty($meta['subscription_activated_at'])) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $profile, $planKey, $meta) {
            $this->activateSubscription(
                $profile,
                $planKey,
                $this->resolveExpiresAt($planKey, $meta),
            );

            $this->grantPlanJobCredits($profile, $planKey);

            $payment->update([
                'status' => HirevoPayment::STATUS_COMPLETED,
                'meta' => array_merge($meta, [
                    'subscription_activated_at' => now()->toIso8601String(),
                ]),
            ]);

            return $payment->fresh();
        });
    }

    public function activateSubscription(HirevoReferrerProfile $profile, string $planKey, ?Carbon $expiresAt = null): void
    {
        $planKey = strtolower(trim($planKey));

        if ($this->resolvePlan($planKey) === null) {
            throw new InvalidArgumentException("Unknown plan: {$planKey}");
        }

        $startsAt = now();
        $expiresAt ??= $this->resolveExpiresAt($planKey);

        $profile->update([
            'subscription_plan' => $planKey,
            'subscription_started_at' => $startsAt,
            'subscription_expires_at' => $expiresAt,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolvePlan(string $planKey): ?array
    {
        $planKey = strtolower(trim($planKey));
        if ($planKey === '') {
            return null;
        }

        $plan = HirevoEmployerPlan::findBySlug($planKey);
        if ($plan !== null) {
            return [
                'name' => $plan->name,
                'billing_period' => $plan->billing_period,
                'job_credits_included' => $plan->job_credits_included,
                'database_credits_included' => $plan->job_credits_included,
            ];
        }

        $legacy = config("hirevo_plans.plans.{$planKey}");

        return is_array($legacy) ? $legacy : null;
    }

    /**
     * @param  array<string, mixed>  $paymentMeta
     */
    public function resolveExpiresAt(string $planKey, array $paymentMeta = []): Carbon
    {
        $startsAt = now();
        $billingPeriod = (string) ($paymentMeta['billing_period'] ?? '');

        if ($billingPeriod === '') {
            $plan = $this->resolvePlan($planKey);
            $billingPeriod = (string) ($plan['billing_period'] ?? 'monthly');
        }

        return match ($billingPeriod) {
            'yearly', 'annual' => $startsAt->copy()->addYear(),
            'one_time_7d', 'launch_7d' => $startsAt->copy()->addDays(7),
            default => $startsAt->copy()->addMonth(),
        };
    }

    public function grantPlanJobCredits(HirevoReferrerProfile $profile, string $planKey): int
    {
        $plan = $this->resolvePlan($planKey);
        if ($plan === null) {
            return 0;
        }

        $credits = $plan['job_credits_included'] ?? $plan['database_credits_included'] ?? 0;
        $credits = is_numeric($credits) ? (int) $credits : 0;

        if ($credits > 0) {
            $profile->increment('credits', $credits);
        }

        return $credits;
    }
}
