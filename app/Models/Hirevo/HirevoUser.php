<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HirevoUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public function referrerProfile(): HasOne
    {
        return $this->hasOne(HirevoReferrerProfile::class, 'user_id');
    }

    public function employerJobs(): HasMany
    {
        return $this->hasMany(HirevoEmployerJob::class, 'user_id');
    }

    public function employerApplications(): HasMany
    {
        return $this->hasMany(HirevoEmployerJobApplication::class, 'user_id');
    }

    public function candidateProfile(): HasOne
    {
        return $this->hasOne(HirevoCandidateProfile::class, 'user_id');
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(HirevoResume::class, 'user_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(HirevoLead::class, 'candidate_id');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(HirevoJobApplication::class, 'user_id');
    }
}

