<?php

namespace App\Models\Leadsmanager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadsmanagerLeadFile extends Model
{
    protected $table = 'leadsmanager_lead_files';

    protected $guarded = [];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(LeadsmanagerAdvertiser::class, 'user_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LeadsmanagerCampaign::class, 'campaign_id');
    }
}
