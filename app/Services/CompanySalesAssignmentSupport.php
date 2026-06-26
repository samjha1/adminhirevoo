<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class CompanySalesAssignmentSupport
{
    public function __construct(
        private readonly SalesTeamService $teams,
        private readonly OrgHierarchyService $org,
    ) {
    }

    public function canAssignManagers(Admin $actor): bool
    {
        if ($actor->role?->hasUnrestrictedLeadVisibility() && $actor->canPermission('leads.assign_manager')) {
            return true;
        }

        if ($this->teams->teamFor($actor) !== SalesTeam::Employer) {
            return false;
        }

        if ($actor->canPermission('leads.assign_manager')) {
            return true;
        }

        return $actor->role === AdminRole::Asm && $actor->canPermission('leads.assign_employee');
    }

    public function canAssignEmployees(Admin $actor): bool
    {
        if (! $actor->canPermission('leads.assign_employee')) {
            return false;
        }

        if ($actor->role?->hasUnrestrictedLeadVisibility()) {
            return false;
        }

        return $this->teams->teamFor($actor) === SalesTeam::Employer
            && in_array($actor->role, [AdminRole::Asm, AdminRole::SalesManager], true);
    }

    /** @return Collection<int, Admin> */
    public function assignableManagers(Admin $actor): Collection
    {
        $query = Admin::query()
            ->where('role', AdminRole::SalesManager)
            ->where('sales_team', SalesTeam::Employer->value);

        if ($actor->role?->hasUnrestrictedLeadVisibility() && $actor->canPermission('leads.assign_manager')) {
            return $query->orderBy('name')->get();
        }

        if ($actor->role === AdminRole::Asm && $this->teams->teamFor($actor) === SalesTeam::Employer) {
            $ids = $this->org->descendantIds($actor);

            return $query->whereIn('id', $ids)->orderBy('name')->get();
        }

        return new Collection;
    }

    /**
     * Marketing can assign outreach leads to ASMs or managers; ASMs only to managers in their team.
     *
     * @return Collection<int, Admin>
     */
    public function assignableOutreachTeamLeads(Admin $actor): Collection
    {
        if (! $this->canAssignManagers($actor)) {
            return new Collection;
        }

        if ($actor->role?->hasUnrestrictedLeadVisibility() && $actor->canPermission('leads.assign_manager')) {
            return Admin::query()
                ->whereIn('role', [AdminRole::Asm, AdminRole::SalesManager])
                ->where('sales_team', SalesTeam::Employer->value)
                ->orderByRaw("CASE role WHEN 'asm' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get();
        }

        return $this->assignableManagers($actor);
    }

    /** @return Collection<int, Admin> */
    public function assignableEmployees(Admin $actor): Collection
    {
        $query = Admin::query()
            ->where('role', AdminRole::SalesEmployee)
            ->where('sales_team', SalesTeam::Employer->value);

        if ($actor->role === AdminRole::SalesManager && $this->teams->teamFor($actor) === SalesTeam::Employer) {
            return $query->where('manager_id', $actor->id)->orderBy('name')->get();
        }

        if ($actor->role === AdminRole::Asm && $this->teams->teamFor($actor) === SalesTeam::Employer) {
            $ids = $this->org->descendantIds($actor);

            return $query->whereIn('id', $ids)->orderBy('name')->get();
        }

        return new Collection;
    }

    public function assertActorMayAssignManager(Admin $actor, Admin $manager): void
    {
        if (! $this->canAssignManagers($actor)) {
            throw new InvalidArgumentException('You do not have permission to assign companies to managers.');
        }

        $this->assertEmployerSalesManager($manager);

        if ($actor->role?->hasUnrestrictedLeadVisibility() && $actor->canPermission('leads.assign_manager')) {
            return;
        }

        if ($actor->role === AdminRole::Asm) {
            $subtree = $this->org->descendantIds($actor);
            if (! $subtree->contains($manager->id)) {
                throw new InvalidArgumentException('Manager must be in your company sales team.');
            }

            return;
        }

        throw new InvalidArgumentException('You cannot assign to this manager.');
    }

    public function assertActorMayAssignOutreachTeamLead(Admin $actor, Admin $target): void
    {
        if (! $this->canAssignManagers($actor)) {
            throw new InvalidArgumentException('You do not have permission to assign outreach leads.');
        }

        if ($actor->role?->hasUnrestrictedLeadVisibility() && $actor->canPermission('leads.assign_manager')) {
            if (! in_array($target->role, [AdminRole::Asm, AdminRole::SalesManager], true)) {
                throw new InvalidArgumentException('Target must be an ASM or company sales manager.');
            }
            if ($this->teams->teamFor($target) !== SalesTeam::Employer) {
                throw new InvalidArgumentException('Target must belong to the company sales team.');
            }

            return;
        }

        if ($actor->role === AdminRole::Asm) {
            $this->assertActorMayAssignManager($actor, $target);

            return;
        }

        throw new InvalidArgumentException('You cannot assign to this team member.');
    }

    public function assertActorMayAssignEmployee(Admin $actor, Admin $employee): void
    {
        if (! $this->canAssignEmployees($actor)) {
            throw new InvalidArgumentException('You do not have permission to assign to executives.');
        }

        $this->assertEmployerSalesEmployee($employee);

        if ($actor->role === AdminRole::SalesManager) {
            if ((int) $employee->manager_id !== (int) $actor->id) {
                throw new InvalidArgumentException('Executive must report to you.');
            }

            return;
        }

        if ($actor->role === AdminRole::Asm) {
            $subtree = $this->org->descendantIds($actor);
            if (! $subtree->contains($employee->id)) {
                throw new InvalidArgumentException('Executive must be in your company sales team.');
            }

            return;
        }

        throw new InvalidArgumentException('You cannot assign to this executive.');
    }

    public function salesManagerIdForEmployeeAssignment(Admin $actor, Admin $employee): int
    {
        if ($actor->role === AdminRole::SalesManager) {
            return $actor->id;
        }

        if ($actor->role === AdminRole::Asm) {
            $managerId = (int) $employee->manager_id;
            if ($managerId === 0) {
                throw new InvalidArgumentException('Executive must have a reporting manager.');
            }

            return $managerId;
        }

        throw new InvalidArgumentException('Cannot resolve sales manager for this assignment.');
    }

    public function actorOwnsRecordForEmployeeAssignment(Admin $actor, ?int $salesManagerId, ?int $assignedTo): bool
    {
        if ($actor->role === AdminRole::Asm) {
            return true;
        }

        if ($actor->role === AdminRole::SalesManager) {
            return $salesManagerId === $actor->id || $assignedTo === $actor->id;
        }

        return false;
    }

    private function assertEmployerSalesManager(Admin $manager): void
    {
        if ($manager->role !== AdminRole::SalesManager) {
            throw new InvalidArgumentException('Target must be a company team sales manager.');
        }
        if ($this->teams->teamFor($manager) !== SalesTeam::Employer) {
            throw new InvalidArgumentException('Manager must belong to the company sales team.');
        }
    }

    private function assertEmployerSalesEmployee(Admin $employee): void
    {
        if ($employee->role !== AdminRole::SalesEmployee) {
            throw new InvalidArgumentException('Target must be a company team sales executive.');
        }
        if ($this->teams->teamFor($employee) !== SalesTeam::Employer) {
            throw new InvalidArgumentException('Executive must belong to the company sales team.');
        }
    }
}
