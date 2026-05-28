<?php

namespace App\Models\Hirevo;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HirevoEmployerJob extends Model
{
    protected $table = 'employer_jobs';

    protected $guarded = [];

    /**
     * Human-readable location when stored as JSON (area/city/state/country/pincode).
     */
    protected function locationDisplay(): Attribute
    {
        return Attribute::get(function (): string {
            $raw = $this->attributes['location'] ?? null;
            if ($raw === null || $raw === '') {
                return '—';
            }

            if (! is_string($raw)) {
                return (string) $raw;
            }

            $trimmed = trim($raw);
            if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
                return $trimmed;
            }

            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return $trimmed;
            }

            $area = isset($decoded['area']) ? trim((string) $decoded['area']) : '';
            $city = isset($decoded['city']) ? trim((string) $decoded['city']) : '';
            $state = isset($decoded['state']) ? trim((string) $decoded['state']) : '';
            $country = isset($decoded['country']) ? trim((string) $decoded['country']) : '';
            $pin = isset($decoded['pincode']) ? trim((string) $decoded['pincode']) : '';

            $cityLine = array_filter([$city, $state, $country], fn ($p) => $p !== '');
            $cityLineStr = implode(', ', $cityLine);

            $parts = [];
            if ($area !== '') {
                $parts[] = $area;
            }
            if ($cityLineStr !== '') {
                $parts[] = $cityLineStr;
            }
            if ($pin !== '') {
                $parts[] = $pin;
            }

            return $parts !== [] ? implode(' · ', $parts) : $trimmed;
        });
    }

    /**
     * City only (for compact lists). JSON locations use `city`; plain strings pass through.
     */
    protected function locationCity(): Attribute
    {
        return Attribute::get(function (): string {
            $raw = $this->attributes['location'] ?? null;
            if ($raw === null || $raw === '') {
                return '—';
            }

            if (! is_string($raw)) {
                return (string) $raw;
            }

            $trimmed = trim($raw);
            if ($trimmed === '') {
                return '—';
            }

            if ($trimmed[0] === '{' || $trimmed[0] === '[') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['city'])) {
                    $city = trim((string) $decoded['city']);

                    return $city !== '' ? $city : '—';
                }
            }

            return $trimmed;
        });
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(HirevoEmployerJobApplication::class, 'employer_job_id');
    }
}

