<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
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
        'employer_prospect_id',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(HirevoLead::class, 'lead_id');
    }

    public function employerProspect(): BelongsTo
    {
        return $this->belongsTo(CrmEmployerProspect::class, 'employer_prospect_id');
    }

    public function isForCompany(): bool
    {
        return $this->employer_prospect_id !== null;
    }
}
