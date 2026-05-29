<?php

namespace App\Modules\Leads\Services;

use App\Enums\CompanyB2bPipelineStage;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmCompanyActivity;
use App\Modules\Leads\Models\CrmCompanyClient;
use App\Modules\Leads\Models\CrmEmployerProspect;
use Illuminate\Support\Facades\DB;

class CompanyB2bPipelineService
{
    public function stages(): array
    {
        return CompanyB2bPipelineStage::ordered();
    }

    public function stageLabels(): array
    {
        $labels = [];
        foreach (CompanyB2bPipelineStage::ordered() as $stage) {
            $labels[$stage->value] = $stage->label();
        }
        $labels[CompanyB2bPipelineStage::Lost->value] = CompanyB2bPipelineStage::Lost->label();

        return $labels;
    }

    public function moveToStage(CrmEmployerProspect $prospect, string $stageValue, Admin $actor): CrmEmployerProspect
    {
        $stage = CompanyB2bPipelineStage::from($stageValue);

        return DB::transaction(function () use ($prospect, $stage, $actor) {
            $prospect->pipeline_stage = $stage->value;
            $prospect->win_probability = $stage->winProbability();
            if ($prospect->deal_value) {
                $prospect->expected_revenue = round(
                    (float) $prospect->deal_value * ($stage->winProbability() / 100),
                    2,
                );
            }
            $prospect->last_activity_at = now();
            $prospect->save();

            if ($stage === CompanyB2bPipelineStage::Won) {
                CrmCompanyClient::query()->firstOrCreate(
                    ['employer_prospect_id' => $prospect->id],
                    [
                        'account_manager_id' => $prospect->assigned_to,
                        'package_purchased' => $prospect->proposal_status,
                        'start_date' => now()->toDateString(),
                    ],
                );
            }

            CrmCompanyActivity::query()->create([
                'employer_prospect_id' => $prospect->id,
                'admin_id' => $actor->id,
                'type' => 'stage_change',
                'title' => 'Moved to '.$stage->label(),
                'payload' => ['stage' => $stage->value],
            ]);

            return $prospect->fresh();
        });
    }

    public function recalculateForecast(CrmEmployerProspect $prospect): void
    {
        $stage = CompanyB2bPipelineStage::tryFrom($prospect->pipeline_stage ?? '')
            ?? CompanyB2bPipelineStage::LeadGenerated;

        $prospect->win_probability = $stage->winProbability();
        if ($prospect->deal_value) {
            $prospect->expected_revenue = round(
                (float) $prospect->deal_value * ($stage->winProbability() / 100),
                2,
            );
        }
        $prospect->save();
    }
}
