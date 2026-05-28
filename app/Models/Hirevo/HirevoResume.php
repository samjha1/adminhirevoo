<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoResume extends Model
{
    protected $table = 'resumes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'extracted_skills' => 'array',
            'is_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }
}

