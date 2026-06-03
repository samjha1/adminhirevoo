<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoPayment extends Model
{
    public const TYPE_EMPLOYER_SUBSCRIPTION = 'employer_subscription';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const GATEWAY_CHEQUE = 'cheque';

    protected $table = 'payments';

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

