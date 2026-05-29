<?php

namespace App\Modules\Leads\Services;

use App\Models\Admin;
use App\Modules\Leads\Models\CrmLeadActivity;
use Illuminate\Database\Eloquent\Model;

class LeadActivityWriter
{
    public function record(
        int $leadId,
        string $type,
        string $title,
        ?Admin $admin = null,
        array $payload = [],
        ?Model $source = null,
    ): CrmLeadActivity {
        return CrmLeadActivity::query()->create([
            'lead_id' => $leadId,
            'admin_id' => $admin?->id,
            'type' => $type,
            'title' => $title,
            'payload' => $payload ?: null,
            'source_type' => $source ? $source::class : null,
            'source_id' => $source?->getKey(),
        ]);
    }
}
