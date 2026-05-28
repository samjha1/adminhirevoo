<?php

namespace App\Models;

use App\Models\Hirevo\HirevoLead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAssignmentHistory extends Model
{
    public $timestamps = false;

    protected $table = 'lead_assignments_history';

    protected $fillable = [
        'lead_id',
        'assigned_from',
        'assigned_to',
        'assigned_by',
        'action_type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(HirevoLead::class, 'lead_id');
    }

    public function fromAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_from');
    }

    public function toAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function byAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }
}
