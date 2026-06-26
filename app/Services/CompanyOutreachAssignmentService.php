<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\AssignmentRoleLevel;
use App\Enums\LeadAssignmentStatus;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmCompanyOutreachLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CompanyOutreachAssignmentService
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly CompanyOutreachVisibilityService $visibility,
        private readonly CompanySalesAssignmentSupport $assignmentSupport,
    ) {
    }

    public function assignToTeamLead(CrmCompanyOutreachLead $lead, Admin $target, Admin $actor): CrmCompanyOutreachLead
    {
        $this->assignmentSupport->assertActorMayAssignOutreachTeamLead($actor, $target);

        return DB::transaction(function () use ($lead, $target, $actor) {
            $lead->assigned_to = $target->id;
            $lead->assigned_by = $actor->id;
            $lead->sales_manager_id = $target->id;
            $lead->assignment_role_level = AssignmentRoleLevel::Manager;
            $lead->assignment_status = LeadAssignmentStatus::Assigned;
            $lead->save();

            $this->audit->log('outreach.assign_team_lead', $actor, $lead, [
                'target_id' => $target->id,
                'target_role' => $target->role?->value,
            ]);

            return $lead->fresh();
        });
    }

    public function assignToEmployee(CrmCompanyOutreachLead $lead, Admin $employee, Admin $actor): CrmCompanyOutreachLead
    {
        $this->assignmentSupport->assertActorMayAssignEmployee($actor, $employee);

        if (! $this->assignmentSupport->actorOwnsRecordForEmployeeAssignment(
            $actor,
            $lead->sales_manager_id,
            $lead->assigned_to,
        )) {
            throw new InvalidArgumentException('Lead is not owned by your team.');
        }

        if ($actor->role === AdminRole::SalesManager
            && $lead->sales_manager_id !== $actor->id
            && $lead->assigned_to !== $actor->id) {
            throw new InvalidArgumentException('Lead is not owned by this manager.');
        }

        $salesManagerId = $this->assignmentSupport->salesManagerIdForEmployeeAssignment($actor, $employee);

        return DB::transaction(function () use ($lead, $employee, $actor, $salesManagerId) {
            $lead->assigned_to = $employee->id;
            $lead->assigned_by = $actor->id;
            $lead->sales_manager_id = $salesManagerId;
            $lead->assignment_role_level = AssignmentRoleLevel::Employee;
            $lead->assignment_status = LeadAssignmentStatus::InProgress;
            $lead->save();

            $this->audit->log('outreach.assign_employee', $actor, $lead, ['employee_id' => $employee->id]);

            return $lead->fresh();
        });
    }

    /**
     * @param  list<int>  $leadIds
     * @return array{success: int, skipped: int, errors: array<int, string>}
     */
    public function bulkAssignToTeamLeads(array $leadIds, Admin $target, Admin $actor): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($leadIds as $id) {
            $id = (int) $id;
            try {
                $lead = CrmCompanyOutreachLead::query()->find($id);
                if (! $lead) {
                    $result['errors'][$id] = 'Lead not found.';

                    continue;
                }
                if (! $this->visibility->canView($actor, $lead)) {
                    $result['errors'][$id] = 'You cannot assign this lead.';

                    continue;
                }
                if ((int) $lead->sales_manager_id === (int) $target->id && (int) $lead->assigned_to === (int) $target->id) {
                    $result['skipped']++;

                    continue;
                }
                $this->assignToTeamLead($lead, $target, $actor);
                $result['success']++;
            } catch (\Throwable $e) {
                $result['errors'][$id] = Str::limit($e->getMessage(), 140);
            }
        }

        return $result;
    }

    /**
     * @param  list<int>  $leadIds
     * @return array{success: int, skipped: int, errors: array<int, string>}
     */
    public function bulkAssignToEmployees(array $leadIds, Admin $employee, Admin $actor): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($leadIds as $id) {
            $id = (int) $id;
            try {
                $lead = CrmCompanyOutreachLead::query()->find($id);
                if (! $lead) {
                    $result['errors'][$id] = 'Lead not found.';

                    continue;
                }
                if (! $this->visibility->canView($actor, $lead)) {
                    $result['errors'][$id] = 'You cannot assign this lead.';

                    continue;
                }
                if ((int) $lead->assigned_to === (int) $employee->id) {
                    $result['skipped']++;

                    continue;
                }
                $this->assignToEmployee($lead, $employee, $actor);
                $result['success']++;
            } catch (\Throwable $e) {
                $result['errors'][$id] = Str::limit($e->getMessage(), 140);
            }
        }

        return $result;
    }
}
