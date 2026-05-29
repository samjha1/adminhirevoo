<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLeadNote extends Model
{
    use SoftDeletes;

    protected $table = 'crm_lead_notes';

    protected $fillable = [
        'lead_id',
        'admin_id',
        'body',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
