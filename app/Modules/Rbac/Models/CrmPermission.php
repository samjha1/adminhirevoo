<?php

namespace App\Modules\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CrmPermission extends Model
{
    protected $table = 'crm_permissions';

    protected $fillable = [
        'slug',
        'group',
        'name',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmRole::class,
            'crm_role_permissions',
            'crm_permission_id',
            'crm_role_id',
        );
    }
}
