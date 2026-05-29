<?php

namespace App\Modules\Rbac\Services;

use App\Models\Admin;
use App\Modules\Rbac\Models\CrmPermission;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Support\PermissionCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PermissionResolver
{
    public const CACHE_PREFIX = 'crm_admin_permissions:';

    public function can(Admin $admin, string $slug): bool
    {
        $permissions = $this->permissionsFor($admin);

        return in_array($slug, $permissions, true);
    }

    /** @return list<string> */
    public function permissionsFor(Admin $admin): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.$admin->id,
            now()->addHours(6),
            fn () => $this->resolvePermissions($admin),
        );
    }

    public function forget(Admin|int $admin): void
    {
        $id = $admin instanceof Admin ? $admin->id : $admin;
        Cache::forget(self::CACHE_PREFIX.$id);
    }

    public function forgetAll(): void
    {
        // Best-effort: flush cache store when using array/file in tests.
        if (method_exists(Cache::getStore(), 'flush')) {
            Cache::flush();
        }
    }

    /** @return list<string> */
    private function resolvePermissions(Admin $admin): array
    {
        if ($admin->role?->isSuperAdmin()) {
            return array_column(PermissionCatalog::all(), 'slug');
        }

        $this->ensureCrmRoleLinked($admin);

        $role = $admin->crmRole;
        $fromRole = $role
            ? $role->permissions()->pluck('slug')->all()
            : $this->legacyPermissionsFor($admin);

        $overrides = $admin->permissionOverrides()
            ->with('permission')
            ->get();

        $granted = [];
        $denied = [];

        foreach ($overrides as $override) {
            $slug = $override->permission?->slug;
            if (! $slug) {
                continue;
            }
            if ($override->effect === 'grant') {
                $granted[] = $slug;
            } else {
                $denied[] = $slug;
            }
        }

        $merged = array_values(array_unique(array_merge($fromRole, $granted)));
        $merged = array_values(array_diff($merged, $denied));

        sort($merged);

        return $merged;
    }

    /** @return list<string> */
    private function legacyPermissionsFor(Admin $admin): array
    {
        if (! $admin->role) {
            return [];
        }

        $slug = $admin->role->crmRoleSlug();

        return PermissionCatalog::rolePermissionMap()[$slug] ?? [];
    }

    private function ensureCrmRoleLinked(Admin $admin): void
    {
        if ($admin->crm_role_id || ! $admin->role || ! Schema::hasTable('crm_roles')) {
            return;
        }

        $crmRole = CrmRole::query()->where('slug', $admin->role->crmRoleSlug())->first();

        if (! $crmRole) {
            return;
        }

        $admin->forceFill(['crm_role_id' => $crmRole->id])->saveQuietly();
        $admin->setRelation('crmRole', $crmRole);
        $this->forget($admin);
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $role = \App\Modules\Rbac\Models\CrmRole::query()->findOrFail($roleId);
        $role->permissions()->sync($permissionIds);

        foreach ($role->admins as $admin) {
            $this->forget($admin);
        }
    }
}
