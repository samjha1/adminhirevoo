<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoPayment extends Model
{
    public const TYPE_EMPLOYER_SUBSCRIPTION = 'employer_subscription';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const GATEWAY_CHEQUE = 'cheque';

    public const GATEWAY_NETBANKING = 'netbanking';

    protected $table = 'payments';

    /** @return array<int, string> */
    public static function offlinePlanGateways(): array
    {
        return [self::GATEWAY_CHEQUE, self::GATEWAY_NETBANKING];
    }

    /** @param  Builder<static>  $query */
    public function scopeEmployerPlanCheckout(Builder $query): Builder
    {
        return $query
            ->where('type', self::TYPE_EMPLOYER_SUBSCRIPTION)
            ->whereIn('payment_gateway', self::offlinePlanGateways());
    }

    public function isEmployerPlanCheckout(): bool
    {
        return $this->type === self::TYPE_EMPLOYER_SUBSCRIPTION
            && in_array($this->payment_gateway, self::offlinePlanGateways(), true);
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }
}

