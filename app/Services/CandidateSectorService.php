<?php

namespace App\Services;

use App\Models\Hirevo\HirevoCandidateProfile;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidateSectorService
{
    private const SECTOR_INDEX_CACHE_KEY = 'portal.candidate_sector_index_v1';

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

        return array_values(array_unique(array_filter($category['keywords'] ?? [])));
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

        $ids = $this->resolvedCandidateIds($query, $categoryKey);
        $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids ?: [-1]);
    }

    public function applyLeadFilter(Builder $query, string $categoryKey): void
    {
        if (! $this->sectorFeaturesAvailable() || $categoryKey === '' || $categoryKey === 'all') {
            return;
        }

        $ids = $this->resolvedLeadIds($query, $categoryKey);
        $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids ?: [-1]);
    }

    /**
     * Each candidate is counted in exactly one bucket (no double-counting).
     *
     * @param  Builder<HirevoUser>  $baseQuery
     * @return array<string, int>
     */
    public function candidateCategoryCounts(Builder $baseQuery): array
    {
        if (! $this->sectorFeaturesAvailable()) {
            return [];
        }

        return $this->bucketExclusive(
            array_merge(array_keys($this->catalog()), ['uncategorized']),
            $this->loadCandidatesForResolution($baseQuery),
            fn (HirevoUser $candidate) => $this->resolveForCandidate($candidate),
        );
    }

    /**
     * Each lead is counted in exactly one bucket (no double-counting).
     *
     * @param  Builder<HirevoLead>  $baseQuery
     * @return array<string, int>
     */
    public function leadCategoryCounts(Builder $baseQuery): array
    {
        if (! $this->sectorFeaturesAvailable()) {
            return [];
        }

        return $this->bucketExclusive(
            array_merge(array_keys($this->catalog()), ['uncategorized']),
            $this->loadLeadsForResolution($baseQuery),
            fn (HirevoLead $lead) => $this->resolveForLead($lead),
        );
    }

    public function resolveForCandidate(HirevoUser $candidate): ?string
    {
        if (! $this->sectorFeaturesAvailable()) {
            return null;
        }

        $this->ensureCandidateRelations($candidate);

        return $this->resolveFromSignals(
            $this->candidateTextBlob($candidate),
            $candidate->leads->first()?->jobRole?->sector,
            $candidate->leads->first()?->jobRole?->title,
            $candidate->jobApplications->first()?->jobRole?->sector,
            $candidate->jobApplications->first()?->jobRole?->title,
        );
    }

    public function resolveForLead(HirevoLead $lead): ?string
    {
        if (! $this->jobRolesAvailable()) {
            return null;
        }

        $this->ensureLeadRelations($lead);

        return $this->resolveFromSignals(
            $this->leadTextBlob($lead),
            $lead->jobRole?->sector,
            $lead->jobRole?->title,
        );
    }

    public function resolveForJob(HirevoEmployerJob $job): ?string
    {
        if (! $this->sectorFeaturesAvailable()) {
            return null;
        }

        $department = trim((string) ($job->job_department ?? ''));
        if ($department !== '') {
            $fromDepartment = $this->resolveCategoryFromDepartment($department);
            if ($fromDepartment !== null) {
                return $fromDepartment;
            }
        }

        $textBlob = $this->jobTextBlob($job);
        $title = trim((string) ($job->title ?? ''));

        return $this->resolveFromSignals(
            $textBlob,
            null,
            $title !== '' ? $title : null,
        );
    }

    private function resolveCategoryFromDepartment(string $department): ?string
    {
        $dept = mb_strtolower(trim($department));
        if ($dept === '') {
            return null;
        }

        $aliases = [
            'engineering' => 'technology',
            'technology' => 'technology',
            'information technology' => 'technology',
            'it' => 'technology',
            'software' => 'technology',
            'tech' => 'technology',
            'marketing' => 'sales_marketing',
            'sales' => 'sales_marketing',
            'business development' => 'sales_marketing',
            'finance' => 'finance',
            'banking' => 'finance',
            'accounting' => 'finance',
            'human resources' => 'hr_admin',
            'hr' => 'hr_admin',
            'recruitment' => 'hr_admin',
            'operations' => 'operations',
            'supply chain' => 'operations',
            'logistics' => 'operations',
            'healthcare' => 'healthcare',
            'medical' => 'healthcare',
            'education' => 'education',
            'retail' => 'retail',
            'e-commerce' => 'retail',
            'ecommerce' => 'retail',
            'manufacturing' => 'manufacturing',
            'production' => 'manufacturing',
            'customer support' => 'operations',
            'support' => 'operations',
        ];

        foreach ($aliases as $needle => $category) {
            if ($dept === $needle || str_contains($dept, $needle)) {
                return $category;
            }
        }

        return $this->resolveCategoryFromText($department);
    }

    /**
     * Candidate user IDs for a sector category, excluding already-applied users.
     *
     * @param  list<int>  $excludeUserIds
     * @return list<int>
     */
    public function candidateIdsForCategory(?string $categoryKey, array $excludeUserIds = []): array
    {
        return $this->candidateIdsForCategoryCached($categoryKey, $excludeUserIds);
    }

    /**
     * @param  list<int>  $excludeUserIds
     * @return list<int>
     */
    public function candidateIdsForCategoryCached(?string $categoryKey, array $excludeUserIds = []): array
    {
        if (! $this->sectorFeaturesAvailable()) {
            return $this->allCandidateIdsDirect($excludeUserIds);
        }

        $key = ($categoryKey === null || $categoryKey === '' || $categoryKey === 'all')
            ? 'uncategorized'
            : $categoryKey;

        $ids = $this->sectorIndex()[$key] ?? [];

        return $this->excludeIds($ids, $excludeUserIds);
    }

    /**
     * All candidate IDs (any sector), excluding given user IDs.
     *
     * @param  list<int>  $excludeUserIds
     * @return list<int>
     */
    public function allCandidateIds(array $excludeUserIds = []): array
    {
        return $this->allCandidateIdsCached($excludeUserIds);
    }

    /**
     * @param  list<int>  $excludeUserIds
     * @return list<int>
     */
    public function allCandidateIdsCached(array $excludeUserIds = []): array
    {
        if (! $this->sectorFeaturesAvailable()) {
            return $this->allCandidateIdsDirect($excludeUserIds);
        }

        $merged = [];
        foreach ($this->sectorIndex() as $ids) {
            foreach ($ids as $id) {
                $merged[$id] = $id;
            }
        }

        $all = array_values($merged);
        rsort($all);

        return $this->excludeIds($all, $excludeUserIds);
    }

    public function forgetSectorIndexCache(): void
    {
        Cache::forget(self::SECTOR_INDEX_CACHE_KEY);
    }

    /** @return array<string, list<int>> */
    public function sectorIndex(): array
    {
        return Cache::remember(
            self::SECTOR_INDEX_CACHE_KEY,
            (int) config('hirevo_portal.candidate_sector_index_ttl', 1800),
            fn () => $this->buildSectorIndex(),
        );
    }

    /** @return array<string, list<int>> */
    private function buildSectorIndex(): array
    {
        $keys = array_merge(array_keys($this->catalog()), ['uncategorized', 'other']);
        $index = array_fill_keys($keys, []);

        $baseQuery = HirevoUser::query()->where('role', 'candidate');

        foreach ($this->loadCandidatesForResolution($baseQuery) as $candidate) {
            $resolved = $this->resolveForCandidate($candidate) ?? 'uncategorized';
            if (! array_key_exists($resolved, $index)) {
                $resolved = 'other';
            }
            $index[$resolved][] = (int) $candidate->id;
        }

        foreach ($index as &$ids) {
            rsort($ids);
        }
        unset($ids);

        return $index;
    }

    /**
     * @param  list<int>  $ids
     * @param  list<int>  $excludeUserIds
     * @return list<int>
     */
    private function excludeIds(array $ids, array $excludeUserIds): array
    {
        if ($excludeUserIds === []) {
            return $ids;
        }

        $exclude = array_flip($excludeUserIds);

        return array_values(array_filter($ids, fn (int $id) => ! isset($exclude[$id])));
    }

    /**
     * @param  list<int>  $excludeUserIds
     * @return list<int>
     */
    private function allCandidateIdsDirect(array $excludeUserIds = []): array
    {
        $query = HirevoUser::query()
            ->where('role', 'candidate');

        if ($excludeUserIds !== []) {
            $query->whereNotIn('id', $excludeUserIds);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function jobTextBlob(HirevoEmployerJob $job): string
    {
        $parts = array_filter([
            $job->title,
            $job->job_department,
            $job->description,
            $job->requirements,
            is_array($job->required_skills)
                ? implode(' ', $job->required_skills)
                : (is_string($job->required_skills ?? null) ? $job->required_skills : null),
        ], fn ($v) => is_string($v) && trim($v) !== '');

        return implode(' ', $parts);
    }

    /**
     * Pick one primary sector from combined text + optional job-role hints.
     */
    private function resolveFromSignals(
        string $textBlob,
        ?string $jobSector = null,
        ?string $jobTitle = null,
        ?string $fallbackJobSector = null,
        ?string $fallbackJobTitle = null,
    ): ?string {
        $fromText = $this->resolveCategoryFromText($textBlob);
        $textScore = $fromText !== null ? $this->scoreTextForCategory($textBlob, $fromText) : 0;

        $jobCategory = $jobSector ? $this->categoryForRoleSector($jobSector) : null;
        $titleCategory = $jobTitle ? $this->resolveCategoryFromText($jobTitle) : null;

        if ($jobCategory && $titleCategory && $jobCategory === $titleCategory) {
            return $jobCategory;
        }

        if ($fromText !== null && $textScore >= 2) {
            return $fromText;
        }

        if ($titleCategory !== null) {
            return $titleCategory;
        }

        if ($jobCategory !== null) {
            return $jobCategory;
        }

        if ($fromText !== null) {
            return $fromText;
        }

        if ($fallbackJobSector || $fallbackJobTitle) {
            return $this->resolveFromSignals('', $fallbackJobSector, $fallbackJobTitle);
        }

        return null;
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
            $score = $this->scoreTextForCategory($haystack, $key);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $key;
            }
        }

        return $bestScore > 0 ? $bestKey : null;
    }

    private function scoreTextForCategory(string $text, string $categoryKey): int
    {
        $haystack = mb_strtolower(trim($text));
        if ($haystack === '') {
            return 0;
        }

        $category = $this->catalog()[$categoryKey] ?? [];
        $score = 0;

        foreach ($category['role_sectors'] ?? [] as $roleSector) {
            if ($this->textContainsKeyword($haystack, str_replace('_', ' ', $roleSector))) {
                $score += 2;
            }
        }

        foreach ($this->keywordsForCategory($categoryKey) as $keyword) {
            if ($this->textContainsKeyword($haystack, $keyword)) {
                $score++;
            }
        }

        return $score;
    }

    private function textContainsKeyword(string $haystack, string $keyword): bool
    {
        $needle = mb_strtolower(trim($keyword));
        if ($needle === '') {
            return false;
        }

        if (mb_strlen($needle) < 5) {
            return (bool) preg_match('/\b'.preg_quote($needle, '/').'\b/u', $haystack);
        }

        return str_contains($haystack, $needle);
    }

    private function leadTextBlob(HirevoLead $lead): string
    {
        $parts = array_filter([
            $lead->jobRole?->title,
            $lead->lead_summary,
            $lead->referral_source ? str_replace('_', ' ', (string) $lead->referral_source) : null,
            $lead->candidate?->candidateProfile instanceof HirevoCandidateProfile
                ? $this->profileTextBlob($lead->candidate->candidateProfile)
                : null,
        ]);

        foreach ($lead->candidate?->resumes ?? [] as $resume) {
            if (filled($resume->ai_summary)) {
                $parts[] = (string) $resume->ai_summary;
            }
        }

        return implode(' ', $parts);
    }

    private function candidateTextBlob(HirevoUser $candidate): string
    {
        $parts = [];

        if ($candidate->candidateProfile instanceof HirevoCandidateProfile) {
            $parts[] = $this->profileTextBlob($candidate->candidateProfile);
        }

        foreach ($candidate->resumes ?? [] as $resume) {
            if (filled($resume->ai_summary)) {
                $parts[] = (string) $resume->ai_summary;
            }
        }

        $parts[] = $candidate->leads->first()?->jobRole?->title;
        $parts[] = $candidate->jobApplications->first()?->jobRole?->title;

        return implode(' ', array_filter($parts));
    }

    private function profileTextBlob(HirevoCandidateProfile $profile): string
    {
        if ($profile->preferred_job_role) {
            $sector = $this->lookupSectorForRoleTitle((string) $profile->preferred_job_role);
            if ($sector) {
                return implode(' ', array_filter([
                    $profile->preferred_job_role,
                    str_replace('_', ' ', $sector),
                    $this->profileRawTextBlob($profile),
                ]));
            }
        }

        return $this->profileRawTextBlob($profile);
    }

    private function profileRawTextBlob(HirevoCandidateProfile $profile): string
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

    /**
     * @template T
     * @param  list<string>  $bucketKeys
     * @param  Collection<int, T>  $records
     * @param  callable(T): (?string)  $resolver
     * @return array<string, int>
     */
    private function bucketExclusive(array $bucketKeys, Collection $records, callable $resolver): array
    {
        $counts = array_fill_keys($bucketKeys, 0);

        foreach ($records as $record) {
            $key = $resolver($record) ?? 'uncategorized';
            if (! array_key_exists($key, $counts)) {
                $key = 'other';
            }
            $counts[$key]++;
        }

        return $counts;
    }

    /** @return Collection<int, HirevoLead> */
    private function loadLeadsForResolution(Builder $baseQuery): Collection
    {
        $leads = collect();

        (clone $baseQuery)
            ->select('leads.*')
            ->with($this->leadEagerRelations())
            ->orderBy('leads.id')
            ->chunkById(100, function ($chunk) use ($leads): void {
                $leads->push(...$chunk);
            }, 'leads.id', 'id');

        return $leads;
    }

    /** @return Collection<int, HirevoUser> */
    private function loadCandidatesForResolution(Builder $baseQuery): Collection
    {
        $candidates = collect();

        (clone $baseQuery)
            ->select('users.*')
            ->with($this->candidateEagerRelations())
            ->orderBy('users.id')
            ->chunkById(100, function ($chunk) use ($candidates): void {
                $candidates->push(...$chunk);
            }, 'users.id', 'id');

        return $candidates;
    }

    /**
     * @param  Builder<HirevoLead>  $baseQuery
     * @return list<int>
     */
    private function resolvedLeadIds(Builder $baseQuery, string $categoryKey): array
    {
        $want = $categoryKey === 'uncategorized' ? null : $categoryKey;
        $ids = [];

        foreach ($this->loadLeadsForResolution($baseQuery) as $lead) {
            $resolved = $this->resolveForLead($lead);
            if ($want === null && $resolved === null) {
                $ids[] = (int) $lead->id;
            } elseif ($resolved === $want) {
                $ids[] = (int) $lead->id;
            }
        }

        return $ids;
    }

    /**
     * @param  Builder<HirevoUser>  $baseQuery
     * @return list<int>
     */
    private function resolvedCandidateIds(Builder $baseQuery, string $categoryKey): array
    {
        $want = $categoryKey === 'uncategorized' ? null : $categoryKey;
        $ids = [];

        foreach ($this->loadCandidatesForResolution($baseQuery) as $candidate) {
            $resolved = $this->resolveForCandidate($candidate);
            if ($want === null && $resolved === null) {
                $ids[] = (int) $candidate->id;
            } elseif ($resolved === $want) {
                $ids[] = (int) $candidate->id;
            }
        }

        return $ids;
    }

    /** @return list<string> */
    private function leadEagerRelations(): array
    {
        $relations = ['jobRole', 'candidate'];

        if ($this->profilesAvailable()) {
            $relations[] = 'candidate.candidateProfile';
        }

        if ($this->resumesAvailable()) {
            $relations['candidate.resumes'] = fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(3);
        }

        return $relations;
    }

    /** @return list<string|array<string, mixed>> */
    private function candidateEagerRelations(): array
    {
        $relations = [
            'leads' => fn ($q) => $q->with('jobRole')->orderByDesc('created_at')->limit(1),
            'jobApplications' => fn ($q) => $q->with('jobRole')->orderByDesc('created_at')->limit(1),
        ];

        if ($this->profilesAvailable()) {
            $relations[] = 'candidateProfile';
        }

        if ($this->resumesAvailable()) {
            $relations['resumes'] = fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(3);
        }

        return $relations;
    }

    private function ensureLeadRelations(HirevoLead $lead): void
    {
        $lead->loadMissing($this->leadEagerRelations());
    }

    private function ensureCandidateRelations(HirevoUser $candidate): void
    {
        $candidate->loadMissing($this->candidateEagerRelations());
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
