<?php

namespace App\Modules\Leads\Services;

use App\Models\Admin;
use App\Modules\Leads\Models\CrmCompanyActivity;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmEmployerProspect;
use App\Services\AuditLogService;
use Carbon\Carbon;

class CompanyMeetingService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    /**
     * @param  array{meeting_at: string, notes?: string|null, meeting_type?: string|null, admin_id?: int|null}  $data
     */
    public function schedule(CrmEmployerProspect $prospect, Admin $actor, array $data): CrmCompanyMeeting
    {
        $assigneeId = $data['admin_id'] ?? $actor->id;
        $meetingAt = Carbon::parse($data['meeting_at']);

        $meeting = CrmCompanyMeeting::query()->create([
            'employer_prospect_id' => $prospect->id,
            'admin_id' => $assigneeId,
            'meeting_at' => $meetingAt,
            'meeting_type' => $data['meeting_type'] ?? 'scheduled',
            'notes' => $data['notes'] ?? null,
        ]);

        $prospect->last_activity_at = now();
        $prospect->save();

        CrmCompanyActivity::query()->create([
            'employer_prospect_id' => $prospect->id,
            'admin_id' => $actor->id,
            'type' => 'meeting_scheduled',
            'title' => 'Meeting scheduled',
            'payload' => ['meeting_at' => $meeting->meeting_at->toIso8601String()],
        ]);

        $this->auditLog->log('employer.meeting_scheduled', $actor, $prospect, [
            'meeting_id' => $meeting->id,
        ]);

        return $meeting;
    }

    public function complete(CrmCompanyMeeting $meeting, Admin $actor): CrmCompanyMeeting
    {
        $meeting->outcome = 'completed';
        $meeting->save();

        CrmCompanyActivity::query()->create([
            'employer_prospect_id' => $meeting->employer_prospect_id,
            'admin_id' => $actor->id,
            'type' => 'meeting_completed',
            'title' => 'Meeting marked complete',
            'payload' => ['meeting_id' => $meeting->id],
        ]);

        return $meeting;
    }

    public function isOpen(CrmCompanyMeeting $meeting): bool
    {
        return $meeting->outcome === null || $meeting->outcome === '';
    }
}
