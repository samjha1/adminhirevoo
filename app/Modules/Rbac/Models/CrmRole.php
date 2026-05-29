<?php

namespace App\Modules\Rbac\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmRole extends Model
{
    protected $table = 'crm_roles';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmPermission::class,
            'crm_role_permissions',
            'crm_role_id',
            'crm_permission_id',
        );
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class, 'crm_role_id');
    }
}
