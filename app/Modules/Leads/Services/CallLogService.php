<?php

namespace App\Modules\Leads\Services;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Enums\CallOutcome;
use App\Modules\Leads\Models\CrmCallLog;
use App\Services\AuditLogService;

class CallLogService
{
    public function __construct(
        private readonly LeadActivityWriter $activityWriter,
        private readonly AuditLogService $auditLog,
    ) {
    }

    /**
     * @param  array{outcome: string, duration_seconds?: int|null, notes?: string|null, called_at?: string|null}  $data
     */
    public function log(HirevoLead $lead, Admin $admin, array $data): CrmCallLog
    {
        $call = CrmCallLog::query()->create([
            'lead_id' => $lead->id,
            'admin_id' => $admin->id,
            'outcome' => CallOutcome::from($data['outcome']),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'notes' => $data['notes'] ?? null,
            'called_at' => isset($data['called_at']) ? $data['called_at'] : now(),
        ]);

        $this->activityWriter->record(
            $lead->id,
            'call',
            'Call logged: '.$call->outcome->label(),
            $admin,
            [
                'outcome' => $call->outcome->value,
                'duration_seconds' => $call->duration_seconds,
            ],
            $call,
        );

        $this->auditLog->log('lead.call_logged', $admin, $lead, [
            'call_id' => $call->id,
            'outcome' => $call->outcome->value,
        ]);

        return $call;
    }
}
