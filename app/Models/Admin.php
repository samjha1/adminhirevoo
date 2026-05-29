<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Services\PermissionResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'crm_role_id',
        'sales_team',
        'manager_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => AdminRole::class,
            'sales_team' => SalesTeam::class,
        ];
    }

    public function onCandidateTeam(): bool
    {
        return $this->sales_team === SalesTeam::Candidate
            || ($this->sales_team === null && $this->hasAnyRole([AdminRole::SalesManager, AdminRole::SalesEmployee]));
    }

    public function onEmployerTeam(): bool
    {
        return $this->sales_team === SalesTeam::Employer;
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function crmRole(): BelongsTo
    {
        return $this->belongsTo(CrmRole::class, 'crm_role_id');
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(CrmAdminPermissionOverride::class);
    }

    public function hasRole(AdminRole|string $role): bool
    {
        $r = $role instanceof AdminRole ? $role->value : $role;

        return $this->role->value === $r;
    }

    /** @param  list<AdminRole|string>  $roles */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $r) {
            if ($this->hasRole($r)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role->isPlatformAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role->isSuperAdmin();
    }

    public function canPermission(string $slug): bool
    {
        return app(PermissionResolver::class)->can($this, $slug);
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        return app(PermissionResolver::class)->permissionsFor($this);
    }
}
