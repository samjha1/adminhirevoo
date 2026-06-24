<?php

namespace App\Support;

class HirevoEmployerJobPayload
{
    public static function normalizeSkillsInput(?string $skills): ?array
    {
        if (! is_string($skills) || trim($skills) === '') {
            return null;
        }

        $items = preg_split('/[\r\n,;|]+/', $skills) ?: [];
        $normalized = [];
        $seen = [];
        foreach ($items as $item) {
            $skill = trim((string) $item);
            if ($skill === '') {
                continue;
            }
            $key = mb_strtolower($skill);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $normalized[] = $skill;
        }

        return count($normalized) > 0 ? array_slice($normalized, 0, 50) : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{area: ?string, city: ?string, state: ?string, country: ?string, pincode: ?string, radius_km: ?int}
     */
    public static function buildLocationPayload(array $validated): array
    {
        $radius = $validated['location_radius'] ?? null;
        $radiusKm = $radius !== null && $radius !== '' ? (int) $radius : null;
        if ($radiusKm !== null && $radiusKm < 1) {
            $radiusKm = null;
        }

        return [
            'area' => $validated['location_area'] ?? null,
            'city' => $validated['location_city'] ?? null,
            'state' => $validated['location_state'] ?? null,
            'country' => $validated['location_country'] ?? null,
            'pincode' => $validated['location_pincode'] ?? null,
            'radius_km' => $radiusKm,
        ];
    }

    /**
     * @param  array<string, mixed>  $location
     */
    public static function locationHasValue(array $location): bool
    {
        foreach (['area', 'city', 'state', 'country', 'pincode'] as $key) {
            $value = $location[$key] ?? null;
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return isset($location['radius_km']) && (int) $location['radius_km'] > 0;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function deriveSalaryAmount(array $validated): ?string
    {
        if (! isset($validated['salary_min']) && ! isset($validated['salary_max'])) {
            return null;
        }

        $min = $validated['salary_min'] ?? null;
        $max = $validated['salary_max'] ?? null;
        if ($min !== null && $max !== null) {
            return $min.'-'.$max;
        }
        if ($min !== null) {
            return (string) $min;
        }
        if ($max !== null) {
            return (string) $max;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function buildAttributesFromValidated(array $validated, ?string $companyName = null): array
    {
        $location = self::buildLocationPayload($validated);
        $hasLocationValue = self::locationHasValue($location);
        $requiredSkills = self::normalizeSkillsInput($validated['required_skills'] ?? null);

        $attributes = [
            'job_department' => $validated['job_department'] ?? null,
            'required_skills' => ! empty($requiredSkills)
                ? json_encode($requiredSkills, JSON_UNESCAPED_UNICODE)
                : null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'apply_link' => isset($validated['apply_link']) && $validated['apply_link'] !== ''
                ? $validated['apply_link']
                : null,
            'location' => $hasLocationValue
                ? json_encode($location, JSON_UNESCAPED_UNICODE)
                : null,
            'status' => $validated['status'] ?? 'active',
            'job_type' => $validated['job_type'] ?? null,
            'is_night_shift' => ! empty($validated['is_night_shift']) ? 1 : 0,
            'work_location_type' => $validated['work_location_type'] ?? null,
            'pay_type' => $validated['pay_type'] ?? null,
            'salary_min' => isset($validated['salary_min']) && $validated['salary_min'] !== ''
                ? (int) $validated['salary_min']
                : null,
            'salary_max' => isset($validated['salary_max']) && $validated['salary_max'] !== ''
                ? (int) $validated['salary_max']
                : null,
            'salary_amount' => self::deriveSalaryAmount($validated),
            'experience_years' => isset($validated['experience_years']) && $validated['experience_years'] !== ''
                ? (int) $validated['experience_years']
                : null,
            'perks' => $validated['perks'] ?? null,
            'joining_fee_required' => isset($validated['joining_fee_required'])
                ? (int) $validated['joining_fee_required']
                : 0,
        ];

        if ($companyName !== null && $companyName !== '') {
            $attributes['company_name'] = $companyName;
        }

        if (isset($validated['display_applications_count']) && $validated['display_applications_count'] !== '') {
            $attributes['display_applications_count'] = (int) $validated['display_applications_count'];
        }

        return $attributes;
    }
}
