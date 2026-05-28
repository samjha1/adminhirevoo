<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoJobApplication extends Model
{
    protected $table = 'job_applications';

    protected $guarded = [];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }
}

