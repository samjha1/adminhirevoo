<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use App\Modules\Leads\Enums\FollowUpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmFollowUp extends Model
{
    use SoftDeletes;

    protected $table = 'crm_follow_ups';

    protected $fillable = [
        'lead_id',
        'admin_id',
        'scheduled_at',
        'status',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => FollowUpStatus::class,
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
