<?php

namespace App\Modules\Rbac\Services;

use App\Modules\Rbac\Models\CrmPermission;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Support\PermissionCatalog;
use Illuminate\Support\Facades\Schema;

class RbacCatalogSyncService
{
    public function tablesExist(): bool
    {
        try {
            return Schema::hasTable('crm_roles')
                && Schema::hasTable('crm_permissions')
                && Schema::hasTable('crm_role_permissions');
        } catch (\Throwable) {
            return false;
        }
    }

    public function isEmpty(): bool
    {
        if (! $this->tablesExist()) {
            return true;
        }

        return CrmRole::query()->count() === 0
            || CrmPermission::query()->count() === 0;
    }

    /** Sync roles and permissions from PermissionCatalog (idempotent). */
    public function sync(): void
    {
        if (! $this->tablesExist()) {
            return;
        }

        foreach (PermissionCatalog::all() as $row) {
            CrmPermission::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'group' => $row['group'],
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                ],
            );
        }

        $permissionIdsBySlug = CrmPermission::query()->pluck('id', 'slug');

        foreach (PermissionCatalog::rolePermissionMap() as $roleSlug => $slugs) {
            $role = CrmRole::query()->updateOrCreate(
                ['slug' => $roleSlug],
                [
                    'name' => str_replace('_', ' ', ucwords($roleSlug, '_')),
                    'is_system' => true,
                ],
            );

            $ids = collect($slugs)
                ->map(fn (string $slug) => $permissionIdsBySlug[$slug] ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($ids);
        }
    }
}
