<?php

namespace App\Services\Hirevo;

use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoUser;
use App\Services\CandidateSectorService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class JobRelevantCandidatesService
{
    public function __construct(
        private readonly CandidateSectorService $sectors,
        private readonly JobMatchScoreService $matchScore,
    ) {
    }

    public function invalidateJobCache(int $jobId): void
    {
        Cache::forget($this->appliedIdsCacheKey($jobId));
    }

    /** @return list<int> */
    public function appliedUserIds(HirevoEmployerJob $job): array
    {
        return Cache::remember(
            $this->appliedIdsCacheKey($job->id),
            (int) config('hirevo_portal.job_applied_ids_cache_ttl', 120),
            fn () => $job->applications()
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        );
    }

    /**
     * @param  list<int>  $excludeUserIds
     * @return list<int>
     */
    public function relevantCandidateIds(HirevoEmployerJob $job, ?string $categoryKey, array $excludeUserIds, bool $showAll): array
    {
        if ($showAll) {
            $ids = $this->sectors->allCandidateIdsCached($excludeUserIds);
        } elseif ($categoryKey === null || $categoryKey === '') {
            return [];
        } else {
            $ids = $this->sectors->candidateIdsForCategoryCached($categoryKey, $excludeUserIds);
        }

        return $this->sortCandidateIdsByJobMatch($job, $ids);
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function sortCandidateIdsByJobMatch(HirevoEmployerJob $job, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $stamp = $job->updated_at?->getTimestamp() ?? 0;
        $cacheKey = 'portal.job.'.$job->id.'.sorted_ids.'.$stamp.'.'.md5(implode(',', $ids));

        return Cache::remember(
            $cacheKey,
            (int) config('hirevo_portal.job_relevant_sort_cache_ttl', 300),
            function () use ($job, $ids) {
                $scored = [];

                foreach (array_chunk($ids, 100) as $chunk) {
                    $users = HirevoUser::query()
                        ->whereIn('id', $chunk)
                        ->with([
                            'candidateProfile',
                            'resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(1),
                        ])
                        ->get();

                    foreach ($users as $user) {
                        $resume = $user->resumes->first();
                        $scored[] = [
                            'id' => (int) $user->id,
                            'score' => $this->matchScore->scoreResumeAgainstJob(
                                $resume,
                                $job,
                                is_string($user->candidateProfile?->skills) ? $user->candidateProfile->skills : null,
                            ),
                        ];
                    }
                }

                usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score'] ?: $b['id'] <=> $a['id']);

                return array_column($scored, 'id');
            },
        );
    }

    public function resolveJobCategory(HirevoEmployerJob $job): ?string
    {
        $stamp = $job->updated_at?->getTimestamp() ?? 0;

        return Cache::remember(
            "portal.job.{$job->id}.sector.v2.{$stamp}",
            (int) config('hirevo_portal.job_sector_cache_ttl', 3600),
            fn () => $this->sectors->resolveForJob($job),
        );
    }

    /**
     * @param  list<int>  $candidateIds
     */
    public function paginateRelevant(
        Request $request,
        HirevoEmployerJob $job,
        array $candidateIds,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $page = max(1, (int) $request->query('candidates_page', 1));
        $search = $request->filled('q') ? trim($request->string('q')->toString()) : '';

        if ($search !== '') {
            return $this->paginateRelevantWithSearch($request, $job, $candidateIds, $search, $perPage, $page);
        }

        $total = count($candidateIds);
        $offset = ($page - 1) * $perPage;
        $pageIds = array_slice($candidateIds, $offset, $perPage);

        if ($pageIds === []) {
            return new LengthAwarePaginator(
                collect(),
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'pageName' => 'candidates_page',
                    'query' => $request->query(),
                ],
            );
        }

        $order = array_flip($pageIds);
        $candidates = HirevoUser::query()
            ->whereIn('id', $pageIds)
            ->with([
                'candidateProfile',
                'resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(1),
            ])
            ->get()
            ->sortBy(fn (HirevoUser $c) => $order[$c->id] ?? PHP_INT_MAX)
            ->values();

        $this->attachCandidateMetrics($candidates, $job);

        return new LengthAwarePaginator(
            $candidates,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'candidates_page',
                'query' => $request->query(),
            ],
        );
    }

    public function paginateApplicants(Request $request, HirevoEmployerJob $job, int $perPage = 15): LengthAwarePaginator
    {
        $applications = HirevoEmployerJobApplication::query()
            ->where('employer_job_id', $job->id)
            ->with([
                'candidate.candidateProfile',
                'resume',
                'candidate.resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(1),
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'applicants_page')
            ->withQueryString();

        $applications->getCollection()->transform(function (HirevoEmployerJobApplication $application) {
            $resume = $application->resume ?? $application->candidate?->resumes?->first();
            $application->setAttribute('profile_match_percent', $this->matchScore->resolveMatchPercent($application, $resume));

            return $application;
        });

        return $applications;
    }

    private function paginateRelevantWithSearch(
        Request $request,
        HirevoEmployerJob $job,
        array $candidateIds,
        string $search,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $query = HirevoUser::query()
            ->where('role', 'candidate')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('candidateProfile', function ($pq) use ($search) {
                        $pq->where('skills', 'like', "%{$search}%")
                            ->orWhere('headline', 'like', "%{$search}%");
                    });
            });

        if ($candidateIds !== []) {
            $query->whereIn('id', $candidateIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        $paginator = $query
            ->with([
                'candidateProfile',
                'resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(1),
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'candidates_page', $page)
            ->withQueryString();

        $this->attachCandidateMetrics($paginator->getCollection(), $job);

        return $paginator;
    }

  /**
     * @param  \Illuminate\Support\Collection<int, HirevoUser>  $candidates
     */
    private function attachCandidateMetrics($candidates, HirevoEmployerJob $job): void
    {
        $candidates->transform(function (HirevoUser $candidate) use ($job) {
            $resume = $candidate->resumes?->first();
            $candidate->setAttribute('has_resume', $resume !== null);
            $candidate->setAttribute('profile_match_percent', $this->matchScore->scoreResumeAgainstJob(
                $resume,
                $job,
                is_string($candidate->candidateProfile?->skills) ? $candidate->candidateProfile->skills : null,
            ));

            return $candidate;
        });
    }

    private function appliedIdsCacheKey(int $jobId): string
    {
        return "portal.job.{$jobId}.applied_user_ids";
    }
}
