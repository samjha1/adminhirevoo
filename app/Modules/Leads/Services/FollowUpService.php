<?php

namespace App\Modules\Leads\Services;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Services\AuditLogService;
use Carbon\Carbon;

class FollowUpService
{
    public function __construct(
        private readonly LeadActivityWriter $activityWriter,
        private readonly AuditLogService $auditLog,
    ) {
    }

    /**
     * @param  array{scheduled_at: string, notes?: string|null, admin_id?: int|null}  $data
     */
    public function schedule(HirevoLead $lead, Admin $actor, array $data): CrmFollowUp
    {
        $assigneeId = $data['admin_id'] ?? $actor->id;

        $followUp = CrmFollowUp::query()->create([
            'lead_id' => $lead->id,
            'admin_id' => $assigneeId,
            'scheduled_at' => Carbon::parse($data['scheduled_at']),
            'status' => FollowUpStatus::Pending,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->activityWriter->record(
            $lead->id,
            'follow_up',
            'Follow-up scheduled',
            $actor,
            ['scheduled_at' => $followUp->scheduled_at->toIso8601String()],
            $followUp,
        );

        $this->auditLog->log('lead.follow_up_scheduled', $actor, $lead, [
            'follow_up_id' => $followUp->id,
        ]);

        return $followUp;
    }

    public function complete(CrmFollowUp $followUp, Admin $actor): CrmFollowUp
    {
        $followUp->status = FollowUpStatus::Completed;
        $followUp->completed_at = now();
        $followUp->save();

        $this->activityWriter->record(
            (int) $followUp->lead_id,
            'follow_up_completed',
            'Follow-up completed',
            $actor,
            [],
            $followUp,
        );

        return $followUp;
    }

    public function markOverdueStatuses(): int
    {
        return CrmFollowUp::query()
            ->where('status', FollowUpStatus::Pending)
            ->where('scheduled_at', '<', now())
            ->update(['status' => FollowUpStatus::Overdue]);
    }
}
