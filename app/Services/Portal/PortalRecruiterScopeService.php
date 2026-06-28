<?php

namespace App\Services\Portal;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\PortalRecruiterCompanyAssignment;
use Illuminate\Database\Eloquent\Builder;

class PortalRecruiterScopeService
{
    /** @var array<int, list<int>> */
    private array $employerIdsCache = [];

    public function isRecruiter(Admin $admin): bool
    {
        return $admin->role === AdminRole::Recruiter;
    }

    public function isUnrestricted(Admin $admin): bool
    {
        if ($admin->isSuperAdmin() || $admin->isAdmin()) {
            return true;
        }

        return $admin->canPermission('portal.recruiter_assignments.manage');
    }

    /** @return list<int> */
    public function assignedEmployerIds(Admin $admin): array
    {
        if (! $this->isRecruiter($admin)) {
            return [];
        }

        if (array_key_exists($admin->id, $this->employerIdsCache)) {
            return $this->employerIdsCache[$admin->id];
        }

        $ids = PortalRecruiterCompanyAssignment::query()
            ->where('admin_id', $admin->id)
            ->pluck('employer_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->employerIdsCache[$admin->id] = $ids;

        return $ids;
    }

    public function hasAssignments(Admin $admin): bool
    {
        return $this->assignedEmployerIds($admin) !== [];
    }

    public function canAccessEmployer(Admin $admin, int $employerUserId): bool
    {
        if ($this->isUnrestricted($admin)) {
            return true;
        }

        if (! $this->isRecruiter($admin)) {
            return true;
        }

        return in_array($employerUserId, $this->assignedEmployerIds($admin), true);
    }

    public function canAccessJob(Admin $admin, HirevoEmployerJob $job): bool
    {
        return $this->canAccessEmployer($admin, (int) $job->user_id);
    }

    /**
     * @param  Builder<\App\Models\Hirevo\HirevoEmployerJob>  $query
     * @return Builder<\App\Models\Hirevo\HirevoEmployerJob>
     */
    public function scopeJobsQuery(Builder $query, Admin $admin): Builder
    {
        if ($this->isUnrestricted($admin) || ! $this->isRecruiter($admin)) {
            return $query;
        }

        $ids = $this->assignedEmployerIds($admin);

        return $query->whereIn('user_id', $ids !== [] ? $ids : [-1]);
    }

    /**
     * @param  Builder<\App\Models\Hirevo\HirevoUser>  $query
     * @return Builder<\App\Models\Hirevo\HirevoUser>
     */
    public function scopeEmployersQuery(Builder $query, Admin $admin): Builder
    {
        if ($this->isUnrestricted($admin) || ! $this->isRecruiter($admin)) {
            return $query;
        }

        $ids = $this->assignedEmployerIds($admin);

        return $query->whereIn('id', $ids !== [] ? $ids : [-1]);
    }

    /**
     * @param  Builder<\App\Models\Hirevo\HirevoEmployerJobApplication>  $query
     * @return Builder<\App\Models\Hirevo\HirevoEmployerJobApplication>
     */
    public function scopeApplicationsQuery(Builder $query, Admin $admin): Builder
    {
        if ($this->isUnrestricted($admin) || ! $this->isRecruiter($admin)) {
            return $query;
        }

        $ids = $this->assignedEmployerIds($admin);
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('job', fn (Builder $jq) => $jq->whereIn('user_id', $ids));
    }

    public function assertCanAccessEmployer(Admin $admin, int $employerUserId): void
    {
        if (! $this->canAccessEmployer($admin, $employerUserId)) {
            abort(403, 'You do not have access to this company.');
        }
    }

    public function assertCanAccessJob(Admin $admin, HirevoEmployerJob $job): void
    {
        if (! $this->canAccessJob($admin, $job)) {
            abort(403, 'You do not have access to this job.');
        }
    }
}
