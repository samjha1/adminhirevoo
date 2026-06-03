<?php

namespace App\Support;

use App\Modules\Leads\Enums\FollowUpStatus;
use App\Modules\Leads\Models\CrmCompanyMeeting;
use App\Modules\Leads\Models\CrmFollowUp;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class CompanyScheduleItem
{
    public function __construct(
        public readonly string $kind,
        public readonly CarbonInterface $at,
        public readonly ?CrmFollowUp $followUp = null,
        public readonly ?CrmCompanyMeeting $meeting = null,
        public readonly bool $forceOverdue = false,
    ) {
    }

    public function employerProspectId(): int
    {
        return (int) ($this->followUp?->employer_prospect_id ?? $this->meeting?->employer_prospect_id);
    }

    public function isDone(): bool
    {
        if ($this->kind === 'follow_up' && $this->followUp) {
            return $this->followUp->status === FollowUpStatus::Completed;
        }

        if ($this->kind === 'meeting' && $this->meeting) {
            return $this->meeting->outcome === 'completed';
        }

        return false;
    }

    public function isOverdue(): bool
    {
        if ($this->isDone()) {
            return false;
        }

        if ($this->forceOverdue) {
            return true;
        }

        if ($this->kind === 'follow_up' && $this->followUp) {
            return $this->followUp->status === FollowUpStatus::Overdue
                || ($this->followUp->status === FollowUpStatus::Pending && $this->at->isPast());
        }

        return $this->at->isPast();
    }

    public function canComplete(): bool
    {
        if ($this->isDone()) {
            return false;
        }

        if ($this->kind === 'follow_up' && $this->followUp) {
            return in_array($this->followUp->status->value, ['pending', 'overdue'], true);
        }

        return $this->meeting && ($this->meeting->outcome === null || $this->meeting->outcome === '');
    }

    public function statusLabel(): string
    {
        if ($this->kind === 'meeting') {
            return $this->isDone() ? 'Completed' : ($this->isOverdue() ? 'Overdue' : 'Scheduled');
        }

        return $this->followUp?->status->label() ?? 'Pending';
    }

    public function statusClass(): string
    {
        if ($this->isDone()) {
            return 'completed';
        }

        if ($this->isOverdue()) {
            return 'overdue';
        }

        return $this->kind === 'meeting' ? 'meeting' : 'pending';
    }

    public function typeLabel(): string
    {
        return $this->kind === 'meeting' ? 'Meeting' : 'Follow-up';
    }

    public function notes(): ?string
    {
        return $this->followUp?->notes ?? $this->meeting?->notes;
    }

    /** @param  Collection<int, CrmFollowUp>  $followUps
     * @param  Collection<int, CrmCompanyMeeting>  $meetings
     * @return Collection<int, self>
     */
    public static function merge(Collection $followUps, Collection $meetings, bool $forceOverdue = false): Collection
    {
        $items = collect();

        foreach ($followUps as $fu) {
            if (! $fu->scheduled_at) {
                continue;
            }
            $items->push(new self('follow_up', $fu->scheduled_at, followUp: $fu, forceOverdue: $forceOverdue));
        }

        foreach ($meetings as $meeting) {
            if (! $meeting->meeting_at) {
                continue;
            }
            $items->push(new self('meeting', $meeting->meeting_at, meeting: $meeting, forceOverdue: $forceOverdue));
        }

        return $items->sortBy(fn (self $item) => $item->at->timestamp)->values();
    }
}
