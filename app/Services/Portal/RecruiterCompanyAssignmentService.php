<?php

namespace App\Services\Portal;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Hirevo\HirevoUser;
use App\Models\PortalRecruiterCompanyAssignment;
use App\Services\AuditLogService;
use Illuminate\Support\Collection;

class RecruiterCompanyAssignmentService
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly PortalRecruiterScopeService $scope,
    ) {
    }

    /** @return Collection<int, Admin> */
    public function recruitersWithCounts(): Collection
    {
        return Admin::query()
            ->where('role', AdminRole::Recruiter)
            ->withCount('recruiterCompanyAssignments')
            ->orderBy('name')
            ->get();
    }

    /** @return list<int> */
    public function assignedEmployerIdsFor(Admin $recruiter): array
    {
        abort_unless($recruiter->role === AdminRole::Recruiter, 404);

        return PortalRecruiterCompanyAssignment::query()
            ->where('admin_id', $recruiter->id)
            ->pluck('employer_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $employerUserIds
     * @return array{added: int, removed: int}
     */
    public function syncAssignments(Admin $recruiter, array $employerUserIds, Admin $manager): array
    {
        abort_unless($recruiter->role === AdminRole::Recruiter, 404);

        $employerUserIds = array_values(array_unique(array_filter(
            array_map('intval', $employerUserIds),
            fn (int $id) => $id > 0,
        )));

        $validIds = HirevoUser::query()
            ->where('role', 'referrer')
            ->whereIn('id', $employerUserIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $current = $this->assignedEmployerIdsFor($recruiter);
        $toAdd = array_values(array_diff($validIds, $current));
        $toRemove = array_values(array_diff($current, $validIds));

        if ($toRemove !== []) {
            PortalRecruiterCompanyAssignment::query()
                ->where('admin_id', $recruiter->id)
                ->whereIn('employer_user_id', $toRemove)
                ->delete();
        }

        foreach ($toAdd as $employerId) {
            PortalRecruiterCompanyAssignment::query()->create([
                'admin_id' => $recruiter->id,
                'employer_user_id' => $employerId,
                'assigned_by' => $manager->id,
                'assigned_at' => now(),
            ]);
        }

        $this->audit->log('portal.recruiter_assignments.update', $manager, $recruiter, [
            'recruiter_id' => $recruiter->id,
            'employer_ids' => $validIds,
            'added' => $toAdd,
            'removed' => $toRemove,
        ]);

        return ['added' => count($toAdd), 'removed' => count($toRemove)];
    }

    public function removeAssignment(Admin $recruiter, int $employerUserId, Admin $manager): void
    {
        abort_unless($recruiter->role === AdminRole::Recruiter, 404);

        PortalRecruiterCompanyAssignment::query()
            ->where('admin_id', $recruiter->id)
            ->where('employer_user_id', $employerUserId)
            ->delete();

        $this->audit->log('portal.recruiter_assignments.remove', $manager, $recruiter, [
            'recruiter_id' => $recruiter->id,
            'employer_user_id' => $employerUserId,
        ]);
    }
}
