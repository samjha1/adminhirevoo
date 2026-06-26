<?php

namespace App\Services\Hirevo;

use App\Models\Admin;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoResume;
use App\Models\Hirevo\HirevoUser;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Schema;

class HirevoEmployerJobApplicationService
{
    /** @var list<string> */
    private const NOTICE_PERIODS = ['immediate', '15_days', '30_days', '60_days', '90_days'];

    public function __construct(
        private readonly JobMatchScoreService $matchScore,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * @param  list<int>  $candidateIds
     * @return array{applied: list<int>, skipped: list<array{id: int, reason: string}>, failed: list<array{id: int, reason: string}>}
     */
    public function applyManyOnBehalf(HirevoEmployerJob $job, array $candidateIds, Admin $admin): array
    {
        $result = ['applied' => [], 'skipped' => [], 'failed' => []];

        foreach (array_unique(array_map('intval', $candidateIds)) as $candidateId) {
            if ($candidateId <= 0) {
                continue;
            }

            try {
                $outcome = $this->applyOnBehalf($job, $candidateId, $admin);
                if ($outcome['status'] === 'applied') {
                    $result['applied'][] = $candidateId;
                } else {
                    $result['skipped'][] = ['id' => $candidateId, 'reason' => $outcome['reason']];
                }
            } catch (\Throwable $e) {
                $result['failed'][] = ['id' => $candidateId, 'reason' => $e->getMessage()];
            }
        }

        return $result;
    }

    /**
     * @return array{status: string, reason: string, application?: HirevoEmployerJobApplication}
     */
    public function applyOnBehalf(HirevoEmployerJob $job, int $candidateId, Admin $admin): array
    {
        if ($job->status !== 'active') {
            return ['status' => 'skipped', 'reason' => 'Job is not active.'];
        }

        $candidate = HirevoUser::query()
            ->where('role', 'candidate')
            ->with(['candidateProfile', 'resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')])
            ->find($candidateId);

        if (! $candidate) {
            return ['status' => 'skipped', 'reason' => 'Candidate not found.'];
        }

        if (HirevoEmployerJobApplication::query()
            ->where('employer_job_id', $job->id)
            ->where('user_id', $candidate->id)
            ->exists()) {
            return ['status' => 'skipped', 'reason' => 'Already applied.'];
        }

        $resume = $this->primaryResumeFor($candidate);
        if (! $resume) {
            return ['status' => 'skipped', 'reason' => 'No resume on file.'];
        }

        $noticePeriod = $this->resolveNoticePeriod($candidate);
        $matchPercent = $this->matchScore->scoreResumeAgainstJob($resume, $job);

        $payload = [
            'employer_job_id' => $job->id,
            'user_id' => $candidate->id,
            'resume_id' => $resume->id,
            'cover_message' => null,
            'notice_period' => $noticePeriod,
            'info_accurate_confirmed_at' => now(),
            'status' => 'applied',
            'ats_score' => is_numeric($resume->ai_score ?? null) ? (int) $resume->ai_score : null,
            'job_match_score' => $matchPercent,
            'job_match_explanation' => 'Matched via admin apply-on-behalf (rule-based).',
        ];

        if (Schema::hasColumn('employer_job_applications', 'applied_by_admin_id')) {
            $payload['applied_by_admin_id'] = $admin->id;
        }

        $application = HirevoEmployerJobApplication::query()->create($payload);

        $this->audit->log('portal.applications.apply_on_behalf', $admin, $application, [
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'resume_id' => $resume->id,
        ]);

        return ['status' => 'applied', 'reason' => '', 'application' => $application];
    }

    private function primaryResumeFor(HirevoUser $candidate): ?HirevoResume
    {
        return $candidate->resumes
            ?->sortByDesc(fn ($r) => (int) (($r->is_primary ?? false) ? 1 : 0))
            ?->sortByDesc('created_at')
            ?->first();
    }

    private function resolveNoticePeriod(HirevoUser $candidate): string
    {
        $fromProfile = trim((string) ($candidate->candidateProfile?->notice_period ?? ''));
        if ($fromProfile !== '' && in_array($fromProfile, self::NOTICE_PERIODS, true)) {
            return $fromProfile;
        }

        return 'immediate';
    }
}
