<?php

namespace App\Services;

use App\Models\AdminLeadStage;
use App\Models\Hirevo\HirevoCareerConsultationRequest;
use App\Models\Hirevo\HirevoLead;

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
     * Counts per admin CRM stage. "new" includes leads with no admin_lead_stages row plus rows explicitly set to new.
     *
     * @return array<string, int>
     */
    public function managementStageCounts(): array
    {
        $stages = $this->managementStages();
        $fromDb = AdminLeadStage::query()
            ->selectRaw('stage, COUNT(*) as aggregate')
            ->groupBy('stage')
            ->pluck('aggregate', 'stage')
            ->map(fn ($v) => (int) $v)
            ->all();

        $counts = [];
        foreach ($stages as $stage) {
            $counts[$stage] = (int) ($fromDb[$stage] ?? 0);
        }

        $counts['new'] += HirevoLead::query()->whereDoesntHave('adminStage')->count();

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
