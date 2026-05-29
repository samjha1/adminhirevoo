<?php

namespace App\Models;

use App\Modules\Rbac\Models\CrmPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmAdminPermissionOverride extends Model
{
    protected $table = 'crm_admin_permission_overrides';

    protected $fillable = [
        'admin_id',
        'crm_permission_id',
        'effect',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(CrmPermission::class, 'crm_permission_id');
    }
}
