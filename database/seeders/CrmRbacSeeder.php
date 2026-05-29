<?php

namespace Database\Seeders;

use App\Modules\Rbac\Models\CrmPermission;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class CrmRbacSeeder extends Seeder
{
    public function run(): void
    {
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
