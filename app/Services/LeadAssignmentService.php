<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Enums\AssignmentRoleLevel;
use App\Enums\LeadAssignmentActionType;
use App\Enums\LeadAssignmentStatus;
use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Models\LeadAssignmentHistory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LeadAssignmentService
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly SalesTeamService $teams,
    ) {}

    public function assignToSalesManager(HirevoLead $lead, Admin $manager, Admin $actor): HirevoLead
    {
        if (! $actor->hasAnyRole([AdminRole::Admin, AdminRole::Marketing])) {
            throw new InvalidArgumentException('Only admin or marketing can assign to a sales manager.');
        }
        $this->assertCandidateManager($manager);

        return DB::transaction(function () use ($lead, $manager, $actor) {
            $from = $lead->assigned_to;
            $lead->assigned_to = $manager->id;
            $lead->assigned_by = $actor->id;
            $lead->sales_manager_id = $manager->id;
            $lead->assignment_role_level = AssignmentRoleLevel::Manager;
            $lead->assignment_status = LeadAssignmentStatus::Assigned;
            $lead->save();

            $this->history($lead, $from, $manager->id, $actor->id, LeadAssignmentActionType::Assign);
            $this->audit->log('lead.assign_manager', $actor, $lead, ['manager_id' => $manager->id]);

            return $lead->fresh();
        });
    }

    public function reassignSalesManager(HirevoLead $lead, Admin $newManager, Admin $actor): HirevoLead
    {
        if (! $actor->hasAnyRole([AdminRole::Admin, AdminRole::Marketing])) {
            throw new InvalidArgumentException('Only admin or marketing can reassign managers.');
        }
        $this->assertCandidateManager($newManager);

        return DB::transaction(function () use ($lead, $newManager, $actor) {
            $from = $lead->assigned_to;
            $lead->assigned_to = $newManager->id;
            $lead->assigned_by = $actor->id;
            $lead->sales_manager_id = $newManager->id;
            $lead->assignment_role_level = AssignmentRoleLevel::Manager;
            $lead->assignment_status = LeadAssignmentStatus::Assigned;
            $lead->save();

            $this->history($lead, $from, $newManager->id, $actor->id, LeadAssignmentActionType::Reassign);
            $this->audit->log('lead.reassign_manager', $actor, $lead, ['manager_id' => $newManager->id]);

            return $lead->fresh();
        });
    }

    public function assignToEmployee(HirevoLead $lead, Admin $employee, Admin $manager): HirevoLead
    {
        if ($manager->role !== AdminRole::SalesManager) {
            throw new InvalidArgumentException('Only a sales manager can assign to employees.');
        }
        $this->assertCandidateEmployee($employee, $manager);
        if ($lead->sales_manager_id !== $manager->id && $lead->assigned_to !== $manager->id) {
            throw new InvalidArgumentException('Lead is not owned by this manager.');
        }

        return DB::transaction(function () use ($lead, $employee, $manager) {
            $from = $lead->assigned_to;
            $lead->assigned_to = $employee->id;
            $lead->assigned_by = $manager->id;
            $lead->sales_manager_id = $manager->id;
            $lead->assignment_role_level = AssignmentRoleLevel::Employee;
            $lead->assignment_status = LeadAssignmentStatus::InProgress;
            $lead->save();

            $this->history($lead, $from, $employee->id, $manager->id, LeadAssignmentActionType::Assign);
            $this->audit->log('lead.assign_employee', $manager, $lead, ['employee_id' => $employee->id]);

            return $lead->fresh();
        });
    }

    public function reassignEmployee(HirevoLead $lead, Admin $newEmployee, Admin $manager): HirevoLead
    {
        $this->assertCandidateEmployee($newEmployee, $manager);
        if ($lead->sales_manager_id !== $manager->id) {
            throw new InvalidArgumentException('Lead is not owned by this manager.');
        }

        return DB::transaction(function () use ($lead, $newEmployee, $manager) {
            $from = $lead->assigned_to;
            $lead->assigned_to = $newEmployee->id;
            $lead->assigned_by = $manager->id;
            $lead->assignment_role_level = AssignmentRoleLevel::Employee;
            $lead->assignment_status = LeadAssignmentStatus::InProgress;
            $lead->save();

            $this->history($lead, $from, $newEmployee->id, $manager->id, LeadAssignmentActionType::Reassign);
            $this->audit->log('lead.reassign_employee', $manager, $lead, ['employee_id' => $newEmployee->id]);

            return $lead->fresh();
        });
    }

    public function takeBackFromEmployee(HirevoLead $lead, Admin $manager): HirevoLead
    {
        if ($manager->role !== AdminRole::SalesManager) {
            throw new InvalidArgumentException('Only a sales manager can take back a lead.');
        }
        if ($lead->sales_manager_id !== $manager->id) {
            throw new InvalidArgumentException('Lead is not under this manager.');
        }
        if ($lead->assignment_role_level !== AssignmentRoleLevel::Employee) {
            throw new InvalidArgumentException('Lead is not assigned to an employee.');
        }

        return DB::transaction(function () use ($lead, $manager) {
            $from = $lead->assigned_to;
            $lead->assigned_to = $manager->id;
            $lead->assigned_by = $manager->id;
            $lead->assignment_role_level = AssignmentRoleLevel::Manager;
            $lead->assignment_status = LeadAssignmentStatus::Assigned;
            $lead->save();

            $this->history($lead, $from, $manager->id, $manager->id, LeadAssignmentActionType::TakeBack);
            $this->audit->log('lead.take_back', $manager, $lead, []);

            return $lead->fresh();
        });
    }

    /**
     * @param  list<int>  $leadIds
     * @return array{success: int, skipped: int, errors: array<int, string>}
     */
    public function bulkAssignToSalesManagers(array $leadIds, Admin $manager, Admin $actor): array
    {
        $success = 0;
        $skipped = 0;
        $errors = [];

        foreach (array_unique(array_map('intval', $leadIds)) as $id) {
            if ($id < 1) {
                continue;
            }

            $lead = HirevoLead::query()->find($id);
            if (! $lead) {
                $errors[$id] = 'Lead not found.';

                continue;
            }

            try {
                Gate::forUser($actor)->authorize('assignAsMarketing', $lead);
            } catch (AuthorizationException) {
                $errors[$id] = 'You cannot assign this lead.';

                continue;
            }

            if ($manager->role !== AdminRole::SalesManager) {
                $errors[$id] = 'Target must be a sales manager.';

                continue;
            }

            try {
                if (
                    (int) $lead->sales_manager_id === (int) $manager->id
                    && (int) $lead->assigned_to === (int) $manager->id
                    && $lead->assignment_role_level === AssignmentRoleLevel::Manager
                ) {
                    $skipped++;

                    continue;
                }

                if ($lead->sales_manager_id === null && $lead->assigned_to === null) {
                    $this->assignToSalesManager($lead, $manager, $actor);
                } else {
                    $this->reassignSalesManager($lead, $manager, $actor);
                }
                $success++;
            } catch (\Throwable $e) {
                $errors[$id] = Str::limit($e->getMessage(), 140);
            }
        }

        return compact('success', 'skipped', 'errors');
    }

    /**
     * @param  list<int>  $leadIds
     * @return array{success: int, skipped: int, errors: array<int, string>}
     */
    public function bulkAssignToEmployees(array $leadIds, Admin $employee, Admin $manager): array
    {
        $success = 0;
        $skipped = 0;
        $errors = [];

        foreach (array_unique(array_map('intval', $leadIds)) as $id) {
            if ($id < 1) {
                continue;
            }

            $lead = HirevoLead::query()->find($id);
            if (! $lead) {
                $errors[$id] = 'Lead not found.';

                continue;
            }

            try {
                Gate::forUser($manager)->authorize('assignAsManager', $lead);
            } catch (AuthorizationException) {
                $errors[$id] = 'You cannot assign this lead.';

                continue;
            }

            if ($employee->role !== AdminRole::SalesEmployee || (int) $employee->manager_id !== (int) $manager->id) {
                $errors[$id] = 'Pick a sales employee on your team.';

                continue;
            }

            try {
                if (
                    (int) $lead->assigned_to === (int) $employee->id
                    && $lead->assignment_role_level === AssignmentRoleLevel::Employee
                ) {
                    $skipped++;

                    continue;
                }

                if (
                    $lead->assignment_role_level === AssignmentRoleLevel::Employee
                    && $lead->assigned_to
                    && (int) $lead->assigned_to !== (int) $employee->id
                ) {
                    $this->reassignEmployee($lead, $employee, $manager);
                } else {
                    $this->assignToEmployee($lead, $employee, $manager);
                }
                $success++;
            } catch (\Throwable $e) {
                $errors[$id] = Str::limit($e->getMessage(), 140);
            }
        }

        return compact('success', 'skipped', 'errors');
    }

    /** Release lead to global pool (marketing / admin). */
    public function releaseToPool(HirevoLead $lead, Admin $actor): HirevoLead
    {
        if (! $actor->hasAnyRole([AdminRole::Admin, AdminRole::Marketing])) {
            throw new InvalidArgumentException('Only admin or marketing can release leads to the pool.');
        }

        return DB::transaction(function () use ($lead, $actor) {
            $from = $lead->assigned_to;
            $lead->assigned_to = null;
            $lead->assigned_by = $actor->id;
            $lead->sales_manager_id = null;
            $lead->assignment_role_level = null;
            $lead->assignment_status = LeadAssignmentStatus::New;
            $lead->save();

            $this->history($lead, $from, null, $actor->id, LeadAssignmentActionType::Unassign);
            $this->audit->log('lead.unassign_pool', $actor, $lead, []);

            return $lead->fresh();
        });
    }

    private function assertCandidateManager(Admin $manager): void
    {
        if ($manager->role !== AdminRole::SalesManager) {
            throw new InvalidArgumentException('Target must be a talent team sales manager.');
        }
        $team = $this->teams->teamFor($manager);
        if ($team === SalesTeam::Employer) {
            throw new InvalidArgumentException('Use the company pipeline to assign employer team managers.');
        }
    }

    private function assertCandidateEmployee(Admin $employee, Admin $manager): void
    {
        if ($employee->role !== AdminRole::SalesEmployee) {
            throw new InvalidArgumentException('Target must be a talent team sales executive.');
        }
        if ($this->teams->teamFor($employee) === SalesTeam::Employer) {
            throw new InvalidArgumentException('Use the company pipeline for employer team executives.');
        }
        if ((int) $employee->manager_id !== (int) $manager->id) {
            throw new InvalidArgumentException('Employee must report to this manager.');
        }
    }

    private function history(
        HirevoLead $lead,
        ?int $from,
        ?int $to,
        int $by,
        LeadAssignmentActionType $type,
    ): void {
        LeadAssignmentHistory::query()->create([
            'lead_id' => $lead->id,
            'assigned_from' => $from,
            'assigned_to' => $to,
            'assigned_by' => $by,
            'action_type' => $type->value,
            'created_at' => now(),
        ]);
    }
}
