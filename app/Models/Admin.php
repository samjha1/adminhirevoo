<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
use App\Enums\SalesTeam;
use App\Services\OrgHierarchyService;
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
        'referral_code',
        'password',
        'role',
        'crm_role_id',
        'sales_team',
        'sales_region',
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
            'sales_region' => SalesRegion::class,
        ];
    }

    public function resolvedRegion(): ?SalesRegion
    {
        if ($this->sales_region instanceof SalesRegion) {
            return $this->sales_region;
        }

        return app(OrgHierarchyService::class)->inheritRegion($this->manager_id);
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    public function descendantIds(): \Illuminate\Support\Collection
    {
        return app(OrgHierarchyService::class)->descendantIds($this);
    }

    public function onCandidateTeam(): bool
    {
        return $this->sales_team === SalesTeam::Candidate
            || ($this->sales_team === null && $this->hasAnyRole([AdminRole::Asm, AdminRole::SalesManager, AdminRole::SalesEmployee]));
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

    public function recruiterCompanyAssignments(): HasMany
    {
        return $this->hasMany(PortalRecruiterCompanyAssignment::class, 'admin_id');
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
