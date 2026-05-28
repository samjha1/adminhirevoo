<?php

namespace App\Models\Leadsmanager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadsmanagerCampaign extends Model
{
    protected $table = 'leadsmanager_campaigns';

    protected $guarded = [];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(LeadsmanagerAdvertiser::class, 'user_id');
    }
}
