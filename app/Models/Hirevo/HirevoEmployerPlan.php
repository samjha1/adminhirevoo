<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HirevoEmployerPlan extends Model
{
    protected $table = 'employer_plans';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_inr' => 'integer',
            'is_popular' => 'boolean',
            'is_custom_price' => 'boolean',
            'is_active' => 'boolean',
            'job_credits_included' => 'integer',
            'unlimited_profile_unlocks' => 'boolean',
            'max_active_jobs' => 'integer',
            'features' => 'array',
            'extras' => 'array',
        ];
    }

    /** @param  Builder<static>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function findBySlug(string $planKey): ?self
    {
        $planKey = strtolower(trim($planKey));
        if ($planKey === '') {
            return null;
        }

        return static::query()
            ->where('slug', $planKey)
            ->where('is_active', true)
            ->first();
    }
}
