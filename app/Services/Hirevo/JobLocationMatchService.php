<?php

namespace App\Services\Hirevo;

use App\Models\Hirevo\HirevoCandidateProfile;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoUser;

class JobLocationMatchService
{
    /** @var array{alias_to_canonical: array<string, string>, canonical_to_aliases: array<string, list<string>>}|null */
    private ?array $lookup = null;

    /**
     * Lower rank = closer location match. 0 = same metro/city, 1 = no match.
     */
    public function jobLocationSortRank(HirevoEmployerJob $job, ?HirevoCandidateProfile $profile): int
    {
        if (in_array((string) ($job->work_location_type ?? ''), ['remote'], true)) {
            return 0;
        }

        $jobCanonicals = $this->canonicalLabelsForTexts($this->jobLocationTexts($job));
        if ($jobCanonicals === []) {
            return 0;
        }

        return $this->candidateMatchesCanonicals($profile, $jobCanonicals) ? 0 : 1;
    }

    public function candidateMatchesJob(HirevoEmployerJob $job, ?HirevoCandidateProfile $profile): bool
    {
        return $this->jobLocationSortRank($job, $profile) === 0
            && $this->canonicalLabelsForTexts($this->jobLocationTexts($job)) !== [];
    }

    public function candidateLocationLabel(?HirevoCandidateProfile $profile): string
    {
        $preferred = trim((string) ($profile?->preferred_job_location ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        return trim((string) ($profile?->location ?? '')) ?: '—';
    }

    /** @return list<string> */
    private function jobLocationTexts(HirevoEmployerJob $job): array
    {
        $texts = [];
        $raw = $job->attributes['location'] ?? null;

        if (is_string($raw) && $raw !== '') {
            $trimmed = trim($raw);
            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    foreach (['area', 'city', 'state', 'country'] as $key) {
                        $part = trim((string) ($decoded[$key] ?? ''));
                        if ($part !== '') {
                            $texts[] = $part;
                        }
                    }
                }
            } else {
                $texts[] = $trimmed;
            }
        }

        $city = $job->location_city ?? null;
        if (is_string($city) && $city !== '' && $city !== '—') {
            $texts[] = $city;
        }

        return array_values(array_unique(array_filter($texts, fn (string $t) => $t !== '')));
    }

    /** @param  list<string>  $texts
     * @return list<string>
     */
    private function canonicalLabelsForTexts(array $texts): array
    {
        $canonicals = [];

        foreach ($texts as $text) {
            $canonical = $this->canonicalLabelForText($text);
            if ($canonical !== null) {
                $canonicals[] = $canonical;
            }
        }

        return array_values(array_unique($canonicals));
    }

    private function canonicalLabelForText(string $text): ?string
    {
        $text = mb_strtolower(trim($text));
        if ($text === '') {
            return null;
        }

        $lookup = $this->mainCityLookup();
        if (isset($lookup['alias_to_canonical'][$text])) {
            return $lookup['alias_to_canonical'][$text];
        }

        foreach ($lookup['canonical_to_aliases'] as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if ($alias !== '' && str_contains($text, $alias)) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /** @param  list<string>  $jobCanonicals */
    private function candidateMatchesCanonicals(?HirevoCandidateProfile $profile, array $jobCanonicals): bool
    {
        if ($profile === null || $jobCanonicals === []) {
            return false;
        }

        $candidateTexts = array_values(array_filter([
            trim((string) ($profile->preferred_job_location ?? '')),
            trim((string) ($profile->location ?? '')),
        ], fn (string $t) => $t !== ''));

        foreach ($candidateTexts as $text) {
            $candidateCanonical = $this->canonicalLabelForText($text);
            if ($candidateCanonical !== null && in_array($candidateCanonical, $jobCanonicals, true)) {
                return true;
            }

            $haystack = mb_strtolower($text);
            foreach ($jobCanonicals as $canonical) {
                if ($this->textContainsCanonical($haystack, $canonical)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function textContainsCanonical(string $haystack, string $canonical): bool
    {
        $aliases = $this->mainCityLookup()['canonical_to_aliases'][$canonical] ?? [mb_strtolower($canonical)];

        foreach ($aliases as $alias) {
            if ($alias !== '' && str_contains($haystack, $alias)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, HirevoUser>  $candidates
     * @return \Illuminate\Support\Collection<int, HirevoUser>
     */
    public function sortCandidatesByLocationThenScore($candidates, HirevoEmployerJob $job, JobMatchScoreService $matchScore)
    {
        return $candidates->sort(function (HirevoUser $a, HirevoUser $b) use ($job, $matchScore) {
            $rankA = $this->jobLocationSortRank($job, $a->candidateProfile);
            $rankB = $this->jobLocationSortRank($job, $b->candidateProfile);
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $scoreA = (int) ($a->profile_match_percent ?? $this->candidateMatchScore($a, $job, $matchScore));
            $scoreB = (int) ($b->profile_match_percent ?? $this->candidateMatchScore($b, $job, $matchScore));
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            return $b->id <=> $a->id;
        })->values();
    }

    private function candidateMatchScore(HirevoUser $candidate, HirevoEmployerJob $job, JobMatchScoreService $matchScore): int
    {
        $resume = $candidate->resumes?->first();
        $skills = is_string($candidate->candidateProfile?->skills) ? $candidate->candidateProfile->skills : null;

        return $matchScore->scoreResumeAgainstJob($resume, $job, $skills);
    }

    /** @return array{alias_to_canonical: array<string, string>, canonical_to_aliases: array<string, list<string>>} */
    private function mainCityLookup(): array
    {
        if (is_array($this->lookup)) {
            return $this->lookup;
        }

        $aliasToCanonical = [];
        $canonicalToAliases = [];

        foreach (config('hirevo_portal.main_cities', []) as $city) {
            if (! is_array($city)) {
                continue;
            }

            $canonical = trim((string) ($city['label'] ?? ''));
            if ($canonical === '') {
                continue;
            }

            $aliases = array_values(array_filter(array_map(
                fn ($alias) => mb_strtolower(trim((string) $alias)),
                $city['aliases'] ?? [$canonical]
            )));

            if ($aliases === []) {
                $aliases = [mb_strtolower($canonical)];
            }

            $canonicalToAliases[$canonical] = $aliases;

            foreach ($aliases as $alias) {
                $aliasToCanonical[$alias] = $canonical;
            }
        }

        $this->lookup = [
            'alias_to_canonical' => $aliasToCanonical,
            'canonical_to_aliases' => $canonicalToAliases,
        ];

        return $this->lookup;
    }
}
