<?php

namespace App\Services;

use App\Models\Hirevo\HirevoCandidateProfile;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidateSectorService
{
    /** @var array<string, array{label: string, short: string, role_sectors: list<string>, keywords?: list<string>}>|null */
    private ?array $catalog = null;

    /** @var list<string>|null */
    private ?array $profileTextColumns = null;

    /** @return array<string, array{label: string, short: string, role_sectors: list<string>, keywords?: list<string>}> */
    public function catalog(): array
    {
        return $this->catalog ??= config('candidate_sectors', []);
    }

    /** @return list<string> */
    public function roleSectorsForCategory(string $categoryKey): array
    {
        return $this->catalog()[$categoryKey]['role_sectors'] ?? [];
    }

    /** @return list<string> */
    public function keywordsForCategory(string $categoryKey): array
    {
        $category = $this->catalog()[$categoryKey] ?? [];
        $keywords = $category['keywords'] ?? [];
        $short = trim((string) ($category['short'] ?? ''));

        if ($short !== '' && ! in_array(mb_strtolower($short), array_map('mb_strtolower', $keywords), true)) {
            $keywords[] = $short;
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    public function labelForCategory(?string $categoryKey): string
    {
        if ($categoryKey === null || $categoryKey === '' || $categoryKey === 'uncategorized') {
            return 'Uncategorized';
        }

        return $this->catalog()[$categoryKey]['label'] ?? ucfirst(str_replace('_', ' ', $categoryKey));
    }

    public function categoryForRoleSector(?string $roleSector): ?string
    {
        if ($roleSector === null || trim($roleSector) === '') {
            return null;
        }

        foreach ($this->catalog() as $key => $category) {
            if (in_array($roleSector, $category['role_sectors'], true)) {
                return $key;
            }
        }

        return 'other';
    }

    public function applyCandidateFilter(Builder $query, string $categoryKey): void
    {
        if (! $this->sectorFeaturesAvailable() || $categoryKey === '' || $categoryKey === 'all') {
            return;
        }

        if ($categoryKey === 'uncategorized') {
            $query->where(function (Builder $outer) {
                $outer->whereDoesntHave('leads', fn (Builder $lq) => $lq->whereHas('jobRole', fn (Builder $rq) => $rq->whereNotNull('sector')))
                    ->whereDoesntHave('jobApplications', fn (Builder $jq) => $jq->whereHas('jobRole', fn (Builder $rq) => $rq->whereNotNull('sector')));

                if ($this->profilesAvailable()) {
                    $outer->whereDoesntHave('candidateProfile', fn (Builder $pq) => $this->profileMatchesKnownSector($pq));
                }

                if ($this->resumesAvailable()) {
                    $outer->whereDoesntHave('resumes', fn (Builder $rq) => $this->resumeMatchesKnownSector($rq));
                }
            });

            return;
        }

        $roleSectors = $this->roleSectorsForCategory($categoryKey);
        if ($roleSectors === []) {
            return;
        }

        $query->where(function (Builder $outer) use ($roleSectors, $categoryKey) {
            $outer->whereHas('leads', fn (Builder $lq) => $lq->whereHas('jobRole', fn (Builder $rq) => $rq->whereIn('sector', $roleSectors)))
                ->orWhereHas('jobApplications', fn (Builder $jq) => $jq->whereHas('jobRole', fn (Builder $rq) => $rq->whereIn('sector', $roleSectors)));

            if ($this->profilesAvailable()) {
                $outer->orWhereHas('candidateProfile', fn (Builder $pq) => $this->profileMatchesCategory($pq, $categoryKey));
            }

            if ($this->resumesAvailable()) {
                $outer->orWhereHas('resumes', fn (Builder $rq) => $this->resumeMatchesCategory($rq, $categoryKey));
            }
        });
    }

    public function applyLeadFilter(Builder $query, string $categoryKey): void
    {
        if (! $this->sectorFeaturesAvailable() || $categoryKey === '' || $categoryKey === 'all') {
            return;
        }

        if ($categoryKey === 'uncategorized') {
            $query->where(function (Builder $outer) {
                $outer->where(function (Builder $roleQ) {
                    $roleQ->whereDoesntHave('jobRole')
                        ->orWhereHas('jobRole', function (Builder $rq) {
                            $rq->whereNull('sector')
                                ->where(function (Builder $titleQ) {
                                    foreach ($this->allKeywords() as $keyword) {
                                        $titleQ->where('title', 'not like', '%'.$keyword.'%');
                                    }
                                });
                        });
                });

                if ($this->profilesAvailable()) {
                    $outer->whereDoesntHave('candidate.candidateProfile', fn (Builder $pq) => $this->profileMatchesKnownSector($pq));
                }

                if ($this->resumesAvailable()) {
                    $outer->whereDoesntHave('candidate.resumes', fn (Builder $rq) => $this->resumeMatchesKnownSector($rq));
                }

                $outer->where(function (Builder $textQ) {
                    $textQ->whereNull('lead_summary')
                        ->orWhere(function (Builder $summaryQ) {
                            foreach ($this->allKeywords() as $keyword) {
                                $summaryQ->where('lead_summary', 'not like', '%'.$keyword.'%');
                            }
                        });
                });
            });

            return;
        }

        $roleSectors = $this->roleSectorsForCategory($categoryKey);
        if ($roleSectors === []) {
            return;
        }

        $keywords = $this->keywordsForCategory($categoryKey);

        $query->where(function (Builder $outer) use ($roleSectors, $categoryKey, $keywords) {
            $outer->whereHas('jobRole', fn (Builder $rq) => $rq->whereIn('sector', $roleSectors));

            if ($this->profilesAvailable()) {
                $outer->orWhereHas('candidate.candidateProfile', fn (Builder $pq) => $this->profileMatchesCategory($pq, $categoryKey));
            }

            if ($this->resumesAvailable()) {
                $outer->orWhereHas('candidate.resumes', fn (Builder $rq) => $this->resumeMatchesCategory($rq, $categoryKey));
            }

            if ($keywords !== []) {
                $outer->orWhere(function (Builder $textQ) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $textQ->orWhere('lead_summary', 'like', '%'.$keyword.'%');
                    }
                });
            }

            $outer->orWhereHas('jobRole', function (Builder $rq) use ($keywords) {
                $rq->where(function (Builder $titleQ) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $titleQ->orWhere('title', 'like', '%'.$keyword.'%');
                    }
                });
            });
        });
    }

    /**
     * @param  Builder<HirevoUser>  $baseQuery
     * @return array<string, int>
     */
    public function candidateCategoryCounts(Builder $baseQuery): array
    {
        if (! $this->sectorFeaturesAvailable()) {
            return [];
        }

        $counts = [];
        foreach (array_keys($this->catalog()) as $key) {
            $counts[$key] = (clone $baseQuery)->tap(fn (Builder $q) => $this->applyCandidateFilter($q, $key))->count();
        }
        $counts['uncategorized'] = (clone $baseQuery)->tap(fn (Builder $q) => $this->applyCandidateFilter($q, 'uncategorized'))->count();

        return $counts;
    }

    /**
     * @param  Builder<HirevoLead>  $baseQuery
     * @return array<string, int>
     */
    public function leadCategoryCounts(Builder $baseQuery): array
    {
        if (! $this->sectorFeaturesAvailable()) {
            return [];
        }

        $counts = [];
        foreach (array_keys($this->catalog()) as $key) {
            $counts[$key] = (clone $baseQuery)->tap(fn (Builder $q) => $this->applyLeadFilter($q, $key))->count();
        }
        $counts['uncategorized'] = (clone $baseQuery)->tap(fn (Builder $q) => $this->applyLeadFilter($q, 'uncategorized'))->count();

        return $counts;
    }

    public function resolveForCandidate(HirevoUser $candidate): ?string
    {
        if (! $this->sectorFeaturesAvailable()) {
            return null;
        }

        $candidate->loadMissing([
            'leads' => fn ($q) => $q->with('jobRole')->orderByDesc('created_at')->limit(1),
            'jobApplications' => fn ($q) => $q->with('jobRole')->orderByDesc('created_at')->limit(1),
        ]);

        if ($this->profilesAvailable()) {
            $candidate->loadMissing('candidateProfile');
        }

        if ($this->resumesAvailable()) {
            $candidate->loadMissing([
                'resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(3),
            ]);
        }

        $leadSector = $candidate->leads->first()?->jobRole?->sector;
        if ($leadSector) {
            return $this->categoryForRoleSector($leadSector);
        }

        $applicationSector = $candidate->jobApplications->first()?->jobRole?->sector;
        if ($applicationSector) {
            return $this->categoryForRoleSector($applicationSector);
        }

        $leadRoleTitle = $candidate->leads->first()?->jobRole?->title;
        if ($leadRoleTitle) {
            $fromTitle = $this->resolveCategoryFromText($leadRoleTitle);
            if ($fromTitle !== null) {
                return $fromTitle;
            }
        }

        $profile = $candidate->candidateProfile;
        if ($profile instanceof HirevoCandidateProfile && $this->profilesAvailable()) {
            $fromProfile = $this->resolveCategoryFromProfile($profile);
            if ($fromProfile !== null) {
                return $fromProfile;
            }
        }

        foreach ($candidate->resumes ?? [] as $resume) {
            $fromResume = $this->resolveCategoryFromText((string) ($resume->ai_summary ?? ''));
            if ($fromResume !== null) {
                return $fromResume;
            }
        }

        return null;
    }

    public function resolveForLead(HirevoLead $lead): ?string
    {
        if (! $this->jobRolesAvailable()) {
            return null;
        }

        $lead->loadMissing(['jobRole', 'candidate']);

        if ($lead->jobRole?->sector) {
            return $this->categoryForRoleSector($lead->jobRole->sector);
        }

        if ($lead->jobRole?->title) {
            $fromTitle = $this->resolveCategoryFromText($lead->jobRole->title);
            if ($fromTitle !== null) {
                return $fromTitle;
            }
        }

        if (filled($lead->lead_summary)) {
            $fromSummary = $this->resolveCategoryFromText((string) $lead->lead_summary);
            if ($fromSummary !== null) {
                return $fromSummary;
            }
        }

        if ($this->profilesAvailable()) {
            $lead->loadMissing('candidate.candidateProfile');
            $profile = $lead->candidate?->candidateProfile;
            if ($profile instanceof HirevoCandidateProfile) {
                $fromProfile = $this->resolveCategoryFromProfile($profile);
                if ($fromProfile !== null) {
                    return $fromProfile;
                }
            }
        }

        if ($this->resumesAvailable()) {
            $lead->loadMissing([
                'candidate.resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(3),
            ]);

            foreach ($lead->candidate?->resumes ?? [] as $resume) {
                $fromResume = $this->resolveCategoryFromText((string) ($resume->ai_summary ?? ''));
                if ($fromResume !== null) {
                    return $fromResume;
                }
            }
        }

        return null;
    }

    private function resolveCategoryFromProfile(HirevoCandidateProfile $profile): ?string
    {
        if ($profile->preferred_job_role) {
            $sector = $this->lookupSectorForRoleTitle((string) $profile->preferred_job_role);
            if ($sector) {
                return $this->categoryForRoleSector($sector);
            }
        }

        return $this->resolveCategoryFromText($this->profileTextBlob($profile));
    }

    private function resolveCategoryFromText(string $text): ?string
    {
        $haystack = mb_strtolower(trim($text));
        if ($haystack === '') {
            return null;
        }

        $bestKey = null;
        $bestScore = 0;

        foreach ($this->catalog() as $key => $category) {
            $score = 0;

            foreach ($category['role_sectors'] ?? [] as $roleSector) {
                if (str_contains($haystack, str_replace('_', ' ', $roleSector))) {
                    $score += 2;
                }
            }

            foreach ($this->keywordsForCategory($key) as $keyword) {
                $needle = mb_strtolower(trim($keyword));
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $key;
            }
        }

        return $bestScore > 0 ? $bestKey : null;
    }

    private function profileTextBlob(HirevoCandidateProfile $profile): string
    {
        $parts = [];
        foreach ($this->profileTextColumns() as $column) {
            $value = $profile->{$column} ?? null;
            if (is_string($value) && trim($value) !== '') {
                $parts[] = $value;
            }
        }

        return implode(' ', $parts);
    }

    /** @return list<string> */
    private function profileTextColumns(): array
    {
        if ($this->profileTextColumns !== null) {
            return $this->profileTextColumns;
        }

        $candidates = [
            'preferred_job_role',
            'headline',
            'skills',
            'bio_summary',
            'career_objective',
            'tools',
            'education',
            'current_role',
            'current_company',
        ];

        $this->profileTextColumns = array_values(array_filter(
            $candidates,
            fn (string $column) => Schema::hasColumn('candidate_profiles', $column),
        ));

        return $this->profileTextColumns;
    }

    /** @param  Builder<HirevoCandidateProfile>  $query */
    private function profileMatchesCategory(Builder $query, string $categoryKey): void
    {
        $roleSectors = $this->roleSectorsForCategory($categoryKey);
        $keywords = $this->keywordsForCategory($categoryKey);

        $query->where(function (Builder $pq) use ($roleSectors, $keywords) {
            if ($roleSectors !== []) {
                $pq->whereExists(function ($sub) use ($roleSectors) {
                    $sub->select(DB::raw(1))
                        ->from('job_roles')
                        ->whereIn('job_roles.sector', $roleSectors)
                        ->where(function ($jq) {
                            $jq->whereColumn('job_roles.title', 'candidate_profiles.preferred_job_role')
                                ->orWhereRaw('candidate_profiles.preferred_job_role LIKE CONCAT(\'%\', job_roles.title, \'%\')')
                                ->orWhereRaw('job_roles.title LIKE CONCAT(\'%\', candidate_profiles.preferred_job_role, \'%\')');
                        });
                });
            }

            $this->applyKeywordLikes($pq, $keywords);
        });
    }

    /** @param  Builder<HirevoCandidateProfile>  $query */
    private function profileMatchesKnownSector(Builder $query): void
    {
        $query->where(function (Builder $pq) {
            $pq->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('job_roles')
                    ->whereNotNull('job_roles.sector')
                    ->where(function ($jq) {
                        $jq->whereColumn('job_roles.title', 'candidate_profiles.preferred_job_role')
                            ->orWhereRaw('candidate_profiles.preferred_job_role LIKE CONCAT(\'%\', job_roles.title, \'%\')')
                            ->orWhereRaw('job_roles.title LIKE CONCAT(\'%\', candidate_profiles.preferred_job_role, \'%\')');
                    });
            });

            $this->applyKeywordLikes($pq, $this->allKeywords());
        });
    }

    /** @param  Builder<\Illuminate\Database\Eloquent\Model>  $query */
    private function resumeMatchesCategory(Builder $query, string $categoryKey): void
    {
        $keywords = $this->keywordsForCategory($categoryKey);

        $query->where(function (Builder $rq) use ($keywords) {
            foreach ($keywords as $keyword) {
                $rq->orWhere('ai_summary', 'like', '%'.$keyword.'%');
            }
        });
    }

    /** @param  Builder<\Illuminate\Database\Eloquent\Model>  $query */
    private function resumeMatchesKnownSector(Builder $query): void
    {
        $query->where(function (Builder $rq) {
            foreach ($this->allKeywords() as $keyword) {
                $rq->orWhere('ai_summary', 'like', '%'.$keyword.'%');
            }
        });
    }

    /** @param  Builder<HirevoCandidateProfile>  $query */
    private function applyKeywordLikes(Builder $query, array $keywords): void
    {
        foreach ($keywords as $keyword) {
            foreach ($this->profileTextColumns() as $column) {
                $query->orWhere($column, 'like', '%'.$keyword.'%');
            }
        }
    }

    /** @return list<string> */
    private function allKeywords(): array
    {
        $keywords = [];
        foreach (array_keys($this->catalog()) as $key) {
            $keywords = array_merge($keywords, $this->keywordsForCategory($key));
        }

        return array_values(array_unique($keywords));
    }

    private function lookupSectorForRoleTitle(string $roleTitle): ?string
    {
        $roleTitle = trim($roleTitle);
        if ($roleTitle === '' || ! $this->jobRolesAvailable()) {
            return null;
        }

        $exact = DB::table('job_roles')
            ->where('title', $roleTitle)
            ->whereNotNull('sector')
            ->value('sector');

        if ($exact) {
            return (string) $exact;
        }

        return DB::table('job_roles')
            ->whereNotNull('sector')
            ->where(function ($q) use ($roleTitle) {
                $q->where('title', 'like', '%'.$roleTitle.'%')
                    ->orWhereRaw('? LIKE CONCAT("%", title, "%")', [$roleTitle]);
            })
            ->value('sector');
    }

    private function profilesAvailable(): bool
    {
        return Schema::hasTable('candidate_profiles');
    }

    private function resumesAvailable(): bool
    {
        return Schema::hasTable('resumes') && Schema::hasColumn('resumes', 'ai_summary');
    }

    public function sectorFeaturesAvailable(): bool
    {
        return $this->jobRolesAvailable();
    }

    private function jobRolesAvailable(): bool
    {
        return Schema::hasTable('job_roles') && Schema::hasColumn('job_roles', 'sector');
    }
}
