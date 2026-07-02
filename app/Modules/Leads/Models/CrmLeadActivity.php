<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmLeadActivity extends Model
{
    protected $table = 'crm_lead_activities';

    protected $fillable = [
        'lead_id',
        'admin_id',
        'type',
        'title',
        'payload',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hirevo\HirevoLead::class, 'lead_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
