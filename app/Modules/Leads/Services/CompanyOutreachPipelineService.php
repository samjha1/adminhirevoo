<?php

namespace App\Modules\Leads\Services;

use App\Enums\CompanyOutreachStage;
use App\Modules\Leads\Models\CrmCompanyOutreachLead;
use Illuminate\Database\Eloquent\Builder;

class CompanyOutreachPipelineService
{
    /** @return list<CompanyOutreachStage> */
    public function stages(): array
    {
        return CompanyOutreachStage::ordered();
    }

    /** @return array<string, string> */
    public function stageLabels(): array
    {
        $labels = [];
        foreach (CompanyOutreachStage::cases() as $stage) {
            $labels[$stage->value] = $stage->label();
        }

        return $labels;
    }

    /** @return array<string, int> */
    public function stageCounts(?Builder $scopedQuery = null): array
    {
        $query = $scopedQuery ?? CrmCompanyOutreachLead::query();

        return $query
            ->selectRaw('outreach_stage, COUNT(*) as aggregate')
            ->groupBy('outreach_stage')
            ->pluck('aggregate', 'outreach_stage')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public function moveToStage(CrmCompanyOutreachLead $lead, CompanyOutreachStage|string $stage): void
    {
        $enum = $stage instanceof CompanyOutreachStage
            ? $stage
            : (CompanyOutreachStage::tryFrom((string) $stage) ?? CompanyOutreachStage::New);

        $updates = ['outreach_stage' => $enum->value];

        if ($enum === CompanyOutreachStage::Called) {
            $updates['last_call_at'] = now();
        }

        if ($enum === CompanyOutreachStage::SignedUp) {
            $updates['sales_status'] = 'converted';
        }

        $lead->update($updates);
    }
}
