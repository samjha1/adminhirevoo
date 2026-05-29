<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use App\Modules\Leads\Enums\CallOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCallLog extends Model
{
    protected $table = 'crm_call_logs';

    protected $fillable = [
        'lead_id',
        'employer_prospect_id',
        'admin_id',
        'outcome',
        'duration_seconds',
        'notes',
        'called_at',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => CallOutcome::class,
            'called_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
