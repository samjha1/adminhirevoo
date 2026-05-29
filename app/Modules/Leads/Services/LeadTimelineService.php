<?php

namespace App\Modules\Leads\Services;

use App\Models\AuditLog;
use App\Models\Hirevo\HirevoLead;
use App\Modules\Leads\Models\CrmCallLog;
use App\Modules\Leads\Models\CrmFollowUp;
use App\Modules\Leads\Models\CrmLeadActivity;
use Illuminate\Support\Collection;

class LeadTimelineService
{
    /**
     * @return Collection<int, array{at: \Carbon\Carbon, type: string, title: string, meta: array, actor: ?string}>
     */
    public function forLead(HirevoLead $lead): Collection
    {
        $items = collect();

        foreach (CrmLeadActivity::query()->where('lead_id', $lead->id)->with('admin')->orderByDesc('created_at')->get() as $row) {
            $items->push([
                'at' => $row->created_at,
                'type' => $row->type,
                'title' => $row->title,
                'meta' => $row->payload ?? [],
                'actor' => $row->admin?->name,
            ]);
        }

        foreach (CrmCallLog::query()->where('lead_id', $lead->id)->with('admin')->orderByDesc('called_at')->get() as $call) {
            if ($items->contains(fn ($i) => ($i['meta']['call_id'] ?? null) === $call->id)) {
                continue;
            }
            $items->push([
                'at' => $call->called_at,
                'type' => 'call',
                'title' => 'Call: '.$call->outcome->label(),
                'meta' => ['duration_seconds' => $call->duration_seconds, 'notes' => $call->notes],
                'actor' => $call->admin?->name,
            ]);
        }

        foreach (CrmFollowUp::query()->where('lead_id', $lead->id)->with('admin')->orderByDesc('scheduled_at')->get() as $fu) {
            $items->push([
                'at' => $fu->scheduled_at,
                'type' => 'follow_up',
                'title' => 'Follow-up ('.$fu->status->label().')',
                'meta' => ['notes' => $fu->notes],
                'actor' => $fu->admin?->name,
            ]);
        }

        $lead->loadMissing(['assignmentHistory.byAdmin', 'adminStage']);

        foreach ($lead->assignmentHistory as $history) {
            $items->push([
                'at' => $history->created_at,
                'type' => 'assignment',
                'title' => 'Assignment: '.$history->action,
                'meta' => [
                    'from' => $history->fromAdmin?->name,
                    'to' => $history->toAdmin?->name,
                ],
                'actor' => $history->byAdmin?->name,
            ]);
        }

        if ($lead->adminStage) {
            $items->push([
                'at' => $lead->adminStage->updated_at,
                'type' => 'stage',
                'title' => 'CRM stage: '.$lead->adminStage->stage,
                'meta' => ['notes' => $lead->adminStage->notes],
                'actor' => null,
            ]);
        }

        AuditLog::query()
            ->where('auditable_type', HirevoLead::class)
            ->where('auditable_id', $lead->id)
            ->with('admin')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->each(function (AuditLog $log) use ($items): void {
                $items->push([
                    'at' => $log->created_at,
                    'type' => 'audit',
                    'title' => $log->action,
                    'meta' => $log->metadata ?? [],
                    'actor' => $log->admin?->name,
                ]);
            });

        return $items->sortByDesc(fn ($i) => $i['at']->timestamp)->values();
    }
}
