<?php

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Modules\Rbac\Models\CrmRole;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('crm_roles')) {
            return;
        }

        $rolesBySlug = CrmRole::query()->pluck('id', 'slug');

        Admin::query()->whereNull('crm_role_id')->each(function (Admin $admin) use ($rolesBySlug): void {
            $slug = $admin->role instanceof AdminRole
                ? $admin->role->crmRoleSlug()
                : (string) $admin->role;

            $crmRoleId = $rolesBySlug[$slug] ?? null;

            if ($crmRoleId) {
                $admin->forceFill(['crm_role_id' => $crmRoleId])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive data migration.
    }
};
