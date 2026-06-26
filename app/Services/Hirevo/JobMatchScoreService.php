<?php

namespace App\Services\Hirevo;

use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoResume;
use Illuminate\Support\Str;

class JobMatchScoreService
{
    public function resolveMatchPercent(HirevoEmployerJobApplication $application, ?HirevoResume $resume): int
    {
        $stored = $application->job_match_score ?? $application->match_percentage ?? null;
        if (is_numeric($stored)) {
            return max(0, min(100, (int) round((float) $stored)));
        }

        return $this->scoreResumeAgainstJob($resume, $application->job);
    }

    public function scoreResumeAgainstJob(?HirevoResume $resume, ?HirevoEmployerJob $job, ?string $profileSkills = null): int
    {
        $resumeSkills = $this->toSkillList($resume?->extracted_skills ?? []);
        if ($resumeSkills === [] && is_string($profileSkills) && trim($profileSkills) !== '') {
            $resumeSkills = $this->toSkillList($profileSkills);
        }

        if ($resumeSkills === [] || $job === null) {
            return 0;
        }

        $required = [];
        foreach (['required_skills', 'skills_required', 'must_have_skills', 'key_skills'] as $key) {
            $required = array_merge($required, $this->toSkillList($job->{$key} ?? null));
        }

        if ($required === []) {
            $required = $this->extractWords(
                (string) ($job->title ?? '').' '
                .(string) ($job->description ?? '').' '
                .(string) ($job->requirements ?? '')
            );
        }

        if ($required === []) {
            return 0;
        }

        $resumeSet = array_unique(array_map(fn ($s) => Str::lower(trim($s)), $resumeSkills));
        $requiredSet = array_unique(array_map(fn ($s) => Str::lower(trim($s)), $required));
        $hits = count(array_intersect($requiredSet, $resumeSet));

        return max(0, min(100, (int) round(($hits / max(1, count($requiredSet))) * 100)));
    }

    /** @param  mixed  $raw @return array<int, string> */
    public function toSkillList($raw): array
    {
        if (is_array($raw)) {
            $items = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $items = is_array($decoded) ? $decoded : (preg_split('/[,|\/]/', $raw) ?: []);
        } else {
            $items = [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (! is_scalar($item)) {
                return null;
            }
            $skill = trim((string) $item);

            return $skill !== '' ? $skill : null;
        }, $items)));
    }

    /** @return array<int, string> */
    public function extractWords(string $text): array
    {
        $parts = preg_split('/[^a-zA-Z0-9\+\#\.]+/', Str::lower($text)) ?: [];

        return array_values(array_filter($parts, fn ($w) => strlen($w) >= 3));
    }
}
