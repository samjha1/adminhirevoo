<?php

namespace App\Services;

use App\Models\AdminLeadStage;
use App\Models\Hirevo\HirevoCareerConsultationRequest;
use App\Models\Hirevo\HirevoLead;
use Illuminate\Database\Eloquent\Builder;

class LeadPipelineService
{
    /** CRM pipeline stages (order matches product UI). @return list<string> */
    public function managementStages(): array
    {
        return [
            'new',
            'called',
            'follow_up',
            'dnp',
            'interested',
            'upskill_needed',
            'applied',
            'referred',
            'interview',
            'hired',
            'lost',
        ];
    }

    /** Human-readable labels for selects and filters. @return array<string, string> */
    public function managementStageLabels(): array
    {
        return [
            'new' => 'New',
            'called' => 'Called',
            'follow_up' => 'Follow up',
            'dnp' => 'Dnp',
            'interested' => 'Interested',
            'upskill_needed' => 'Upskill needed',
            'applied' => 'Applied',
            'referred' => 'Referred',
            'interview' => 'Interview',
            'hired' => 'Hired',
            'lost' => 'Lost',
        ];
    }

    /**
     * Global counts per admin CRM stage (executive / marketing dashboards).
     *
     * @return array<string, int>
     */
    public function managementStageCounts(): array
    {
        return $this->managementStageCountsFor(HirevoLead::query());
    }

    /**
     * Counts per CRM stage for a visibility-scoped lead query.
     * "new" includes leads with no admin_lead_stages row plus rows explicitly set to new.
     *
     * @param  Builder<HirevoLead>  $visibleLeads
     * @return array<string, int>
     */
    public function managementStageCountsFor(Builder $visibleLeads): array
    {
        $stages = $this->managementStages();
        $leadIds = (clone $visibleLeads)->select('leads.id');

        $fromDb = AdminLeadStage::query()
            ->whereIn('lead_id', $leadIds)
            ->selectRaw('stage, COUNT(*) as aggregate')
            ->groupBy('stage')
            ->pluck('aggregate', 'stage')
            ->map(fn ($v) => (int) $v)
            ->all();

        $counts = [];
        foreach ($stages as $stage) {
            $counts[$stage] = (int) ($fromDb[$stage] ?? 0);
        }

        $counts['new'] += (clone $visibleLeads)->whereDoesntHave('adminStage')->count();

        return $counts;
    }

    public function totalLeads(): int
    {
        return HirevoLead::query()->count();
    }

    public function pendingConsultations(): int
    {
        return HirevoCareerConsultationRequest::query()->where('status', 'pending')->count();
    }
}
