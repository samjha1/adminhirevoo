<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\CrmAdminPermissionOverride;
use App\Modules\Rbac\Models\CrmPermission;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Services\PermissionResolver;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permissions_are_resolved(): void
    {
        $this->seed(CrmRbacSeeder::class);

        $role = CrmRole::query()->where('slug', 'marketing')->firstOrFail();
        $admin = Admin::factory()->create([
            'role' => \App\Enums\AdminRole::Marketing,
            'crm_role_id' => $role->id,
        ]);

        $resolver = app(PermissionResolver::class);

        $this->assertTrue($resolver->can($admin, 'leads.import'));
        $this->assertFalse($resolver->can($admin, 'rbac.manage_permissions'));
    }

    public function test_deny_override_blocks_permission(): void
    {
        $this->seed(CrmRbacSeeder::class);

        $role = CrmRole::query()->where('slug', 'marketing')->firstOrFail();
        $admin = Admin::factory()->create([
            'role' => \App\Enums\AdminRole::Marketing,
            'crm_role_id' => $role->id,
        ]);

        $permission = CrmPermission::query()->where('slug', 'leads.import')->firstOrFail();

        CrmAdminPermissionOverride::query()->create([
            'admin_id' => $admin->id,
            'crm_permission_id' => $permission->id,
            'effect' => 'deny',
        ]);

        $resolver = app(PermissionResolver::class);
        $resolver->forget($admin);

        $this->assertFalse($resolver->can($admin, 'leads.import'));
    }

    public function test_grant_override_adds_permission(): void
    {
        $this->seed(CrmRbacSeeder::class);

        $role = CrmRole::query()->where('slug', 'sales_employee')->firstOrFail();
        $admin = Admin::factory()->create([
            'role' => \App\Enums\AdminRole::SalesEmployee,
            'crm_role_id' => $role->id,
        ]);

        $permission = CrmPermission::query()->where('slug', 'leads.import')->firstOrFail();

        CrmAdminPermissionOverride::query()->create([
            'admin_id' => $admin->id,
            'crm_permission_id' => $permission->id,
            'effect' => 'grant',
        ]);

        $resolver = app(PermissionResolver::class);
        $resolver->forget($admin);

        $this->assertTrue($resolver->can($admin, 'leads.import'));
    }

    public function test_legacy_enum_fallback_when_crm_role_id_missing(): void
    {
        $this->seed(CrmRbacSeeder::class);

        $admin = Admin::factory()->create([
            'role' => \App\Enums\AdminRole::Marketing,
            'crm_role_id' => null,
        ]);

        $resolver = app(PermissionResolver::class);
        $resolver->forget($admin);

        $this->assertTrue($resolver->can($admin, 'analytics.view'));
        $this->assertTrue($resolver->can($admin, 'leads.import'));
    }

    public function test_cache_invalidation(): void
    {
        $this->seed(CrmRbacSeeder::class);

        $role = CrmRole::query()->where('slug', 'sales_employee')->firstOrFail();
        $admin = Admin::factory()->create([
            'crm_role_id' => $role->id,
            'role' => \App\Enums\AdminRole::SalesEmployee,
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertFalse($resolver->can($admin, 'leads.import'));

        $role->permissions()->attach(
            CrmPermission::query()->where('slug', 'leads.import')->value('id')
        );
        $resolver->forget($admin);

        $this->assertTrue($resolver->can($admin, 'leads.import'));
    }
}
