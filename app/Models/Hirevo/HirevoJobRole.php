<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HirevoJobRole extends Model
{
    protected $table = 'job_roles';

    protected $guarded = [];

    public function applications(): HasMany
    {
        return $this->hasMany(HirevoJobApplication::class, 'job_role_id');
    }
}
