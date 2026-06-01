<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\AssignmentRoleLevel;
use App\Enums\LeadAssignmentActionType;
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
    ) {
    }

    public function assignToSalesManager(CrmEmployerProspect $prospect, Admin $manager, Admin $actor): CrmEmployerProspect
    {
        $this->assertMarketingActor($actor);
        $this->assertEmployerManager($manager);

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

    public function assignToEmployee(CrmEmployerProspect $prospect, Admin $employee, Admin $manager): CrmEmployerProspect
    {
        if (! $manager->canPermission('leads.assign_employee')) {
            throw new InvalidArgumentException('You do not have permission to assign companies to executives.');
        }
        $this->assertEmployerManager($manager);
        $this->assertEmployerEmployee($employee, $manager);

        if ($prospect->sales_manager_id !== $manager->id && $prospect->assigned_to !== $manager->id) {
            throw new InvalidArgumentException('Prospect is not owned by this manager.');
        }

        return DB::transaction(function () use ($prospect, $employee, $manager) {
            $prospect->assigned_to = $employee->id;
            $prospect->assigned_by = $manager->id;
            $prospect->sales_manager_id = $manager->id;
            $prospect->assignment_role_level = AssignmentRoleLevel::Employee;
            $prospect->assignment_status = LeadAssignmentStatus::InProgress;
            $prospect->save();

            $this->audit->log('employer.assign_employee', $manager, $prospect, ['employee_id' => $employee->id]);

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
                if ((int) $prospect->sales_manager_id === (int) $manager->id) {
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
    public function bulkAssignToEmployees(array $prospectIds, Admin $employee, Admin $manager): array
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
                if (! $this->visibility->canView($manager, $prospect)) {
                    $result['errors'][$id] = 'You cannot assign this company.';

                    continue;
                }
                if ((int) $prospect->assigned_to === (int) $employee->id) {
                    $result['skipped']++;

                    continue;
                }
                $this->assignToEmployee($prospect, $employee, $manager);
                $result['success']++;
            } catch (\Throwable $e) {
                $result['errors'][$id] = Str::limit($e->getMessage(), 140);
            }
        }

        return $result;
    }

    private function assertMarketingActor(Admin $actor): void
    {
        if (! $actor->canPermission('leads.assign_manager')) {
            throw new InvalidArgumentException('You do not have permission to assign companies to managers.');
        }
    }

    private function assertEmployerManager(Admin $manager): void
    {
        if ($manager->role !== AdminRole::SalesManager) {
            throw new InvalidArgumentException('Target must be a company team sales manager.');
        }
        if ($this->teams->teamFor($manager) !== SalesTeam::Employer) {
            throw new InvalidArgumentException('Manager must belong to the company (employer) sales team.');
        }
    }

    private function assertEmployerEmployee(Admin $employee, Admin $manager): void
    {
        if ($employee->role !== AdminRole::SalesEmployee) {
            throw new InvalidArgumentException('Target must be a company team sales executive.');
        }
        if ($this->teams->teamFor($employee) !== SalesTeam::Employer) {
            throw new InvalidArgumentException('Employee must belong to the company (employer) sales team.');
        }
        if ((int) $employee->manager_id !== (int) $manager->id) {
            throw new InvalidArgumentException('Employee must report to this manager.');
        }
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
