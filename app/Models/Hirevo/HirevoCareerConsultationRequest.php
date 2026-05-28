<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoCareerConsultationRequest extends Model
{
    protected $table = 'career_consultation_requests';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gap_skills' => 'array',
            'suggested_gap_skills' => 'array',
            'matched_skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }
}

