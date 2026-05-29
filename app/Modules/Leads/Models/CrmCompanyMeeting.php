<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCompanyMeeting extends Model
{
    protected $table = 'crm_company_meetings';

    protected $fillable = [
        'employer_prospect_id', 'admin_id', 'meeting_at', 'meeting_type',
        'outcome', 'attendees', 'notes', 'next_action',
    ];

    protected function casts(): array
    {
        return ['meeting_at' => 'datetime'];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(CrmEmployerProspect::class, 'employer_prospect_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
