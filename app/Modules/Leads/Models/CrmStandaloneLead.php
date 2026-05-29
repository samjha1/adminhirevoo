<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmStandaloneLead extends Model
{
    protected $table = 'crm_standalone_leads';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'source',
        'notes',
        'created_by',
        'assigned_to',
        'sales_manager_id',
        'assignment_status',
        'sales_status',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sales_manager_id');
    }
}
