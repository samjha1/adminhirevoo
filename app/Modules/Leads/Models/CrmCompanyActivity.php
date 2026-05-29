<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCompanyActivity extends Model
{
    protected $table = 'crm_company_activities';

    protected $fillable = [
        'employer_prospect_id', 'admin_id', 'type', 'title', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
