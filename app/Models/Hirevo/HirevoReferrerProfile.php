<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoReferrerProfile extends Model
{
    protected $table = 'referrer_profiles';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'company_email_verified' => 'boolean',
            'gst_verified' => 'boolean',
            'invoice_consent' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }
}

