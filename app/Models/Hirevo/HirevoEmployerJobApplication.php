<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirevoEmployerJobApplication extends Model
{
    protected $table = 'employer_job_applications';

    protected $guarded = [];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(HirevoEmployerJob::class, 'employer_job_id');
    }
}

