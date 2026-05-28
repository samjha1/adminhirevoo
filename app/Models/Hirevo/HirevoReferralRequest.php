<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoReferralRequest extends Model
{
    protected $table = 'referral_requests';

    protected $guarded = [];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'candidate_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'referrer_id');
    }
}

