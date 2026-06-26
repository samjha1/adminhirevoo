<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\AssignmentRoleLevel;
use App\Enums\LeadAssignmentStatus;
use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmEmployerProspect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmployerProspectAssignmentService
{
    public function __construct(
        private readonly SalesTeamService $teams,
        private readonly AuditLogService $audit,
        private readonly EmployerProspectVisibilityService $visibility,
        private readonly CompanySalesAssignmentSupport $assignmentSupport,
    ) {
    }

    public function assignToSalesManager(CrmEmployerProspect $prospect, Admin $manager, Admin $actor): CrmEmployerProspect
    {
        $this->assignmentSupport->assertActorMayAssignManager($actor, $manager);

        return DB::transaction(function () use ($prospect, $manager, $actor) {
            $prospect->assigned_to = $manager->id;
            $prospect->assigned_by = $actor->id;
            $prospect->sales_manager_id = $manager->id;
            $prospect->assignment_role_level = AssignmentRoleLevel::Manager;
            $prospect->assignment_status = LeadAssignmentStatus::Assigned;
            $prospect->save();

            $this->audit->log('employer.assign_manager', $actor, $prospect, ['manager_id' => $manager->id]);

            return $prospect->fresh();
        });
    }

    public function assignToEmployee(CrmEmployerProspect $prospect, Admin $employee, Admin $actor): CrmEmployerProspect
    {
        $this->assignmentSupport->assertActorMayAssignEmployee($actor, $employee);

        if (! $this->assignmentSupport->actorOwnsRecordForEmployeeAssignment(
            $actor,
            $prospect->sales_manager_id,
            $prospect->assigned_to,
        )) {
            throw new InvalidArgumentException('Company is not owned by your team.');
        }

        if ($actor->role === AdminRole::SalesManager
            && $prospect->sales_manager_id !== $actor->id
            && $prospect->assigned_to !== $actor->id) {
            throw new InvalidArgumentException('Company is not owned by this manager.');
        }

        $salesManagerId = $this->assignmentSupport->salesManagerIdForEmployeeAssignment($actor, $employee);

        return DB::transaction(function () use ($prospect, $employee, $actor, $salesManagerId) {
            $prospect->assigned_to = $employee->id;
            $prospect->assigned_by = $actor->id;
            $prospect->sales_manager_id = $salesManagerId;
            $prospect->assignment_role_level = AssignmentRoleLevel::Employee;
            $prospect->assignment_status = LeadAssignmentStatus::InProgress;
            $prospect->save();

            $this->audit->log('employer.assign_employee', $actor, $prospect, ['employee_id' => $employee->id]);

            return $prospect->fresh();
        });
    }

    /**
     * @param  list<int>  $prospectIds
     * @return array{success: int, skipped: int, errors: array<int, string>}
     */
    public function bulkAssignToManagers(array $prospectIds, Admin $manager, Admin $actor): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($prospectIds as $id) {
            $id = (int) $id;
            try {
                $prospect = CrmEmployerProspect::query()->find($id);
                if (! $prospect) {
                    $result['errors'][$id] = 'Company not found.';

                    continue;
                }
                if (! $this->visibility->canView($actor, $prospect)) {
                    $result['errors'][$id] = 'You cannot assign this company.';

                    continue;
                }
                if ((int) $prospect->sales_manager_id === (int) $manager->id
                    && (int) $prospect->assigned_to === (int) $manager->id) {
                    $result['skipped']++;

                    continue;
                }
                $this->assignToSalesManager($prospect, $manager, $actor);
                $result['success']++;
            } catch (\Throwable $e) {
                $result['errors'][$id] = Str::limit($e->getMessage(), 140);
            }
        }

        return $result;
    }

    /**
     * @param  list<int>  $prospectIds
     * @return array{success: int, skipped: int, errors: array<int, string>}
     */
    public function bulkAssignToEmployees(array $prospectIds, Admin $employee, Admin $actor): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($prospectIds as $id) {
            $id = (int) $id;
            try {
                $prospect = CrmEmployerProspect::query()->find($id);
                if (! $prospect) {
                    $result['errors'][$id] = 'Company not found.';

                    continue;
                }
                if (! $this->visibility->canView($actor, $prospect)) {
                    $result['errors'][$id] = 'You cannot assign this company.';

                    continue;
                }
                if ((int) $prospect->assigned_to === (int) $employee->id) {
                    $result['skipped']++;

                    continue;
                }
                $this->assignToEmployee($prospect, $employee, $actor);
                $result['success']++;
            } catch (\Throwable $e) {
                $result['errors'][$id] = Str::limit($e->getMessage(), 140);
            }
        }

        return $result;
    }

    public function assignFromReferralCode(CrmEmployerProspect $prospect, Admin $admin): CrmEmployerProspect
    {
        if ($prospect->assigned_to) {
            return $prospect;
        }

        if ($this->teams->teamFor($admin) !== SalesTeam::Employer) {
            return $prospect;
        }

        if (! in_array($admin->role, [AdminRole::SalesManager, AdminRole::SalesEmployee], true)) {
            return $prospect;
        }

        return DB::transaction(function () use ($prospect, $admin) {
            $locked = CrmEmployerProspect::query()->lockForUpdate()->find($prospect->id);
            if (! $locked || $locked->assigned_to) {
                return $locked ?? $prospect;
            }

            if ($admin->role === AdminRole::SalesManager) {
                $locked->assigned_to = $admin->id;
                $locked->sales_manager_id = $admin->id;
                $locked->assignment_role_level = AssignmentRoleLevel::Manager;
                $locked->assignment_status = LeadAssignmentStatus::Assigned;
            } else {
                $locked->assigned_to = $admin->id;
                $locked->sales_manager_id = $admin->manager_id;
                $locked->assignment_role_level = AssignmentRoleLevel::Employee;
                $locked->assignment_status = LeadAssignmentStatus::InProgress;
            }

            $locked->assigned_by = null;
            $locked->source = 'hirevo_referral';
            $locked->save();

            $this->audit->log('employer.referral_auto_assign', $admin, $locked, [
                'referral_code' => $admin->referral_code,
            ]);

            return $locked->fresh();
        });
    }
}
