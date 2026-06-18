<?php

namespace App\Models\Leadsmanager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadsmanagerLead extends Model
{
    protected $table = 'leadsmanager_leads';

    protected $guarded = [];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'exported_at' => 'datetime',
        'is_exported' => 'boolean',
        'meta' => 'array',
    ];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(LeadsmanagerAdvertiser::class, 'user_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LeadsmanagerCampaign::class, 'campaign_id');
    }
}
