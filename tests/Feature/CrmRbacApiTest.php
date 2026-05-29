<?php

namespace Tests\Feature;

use App\Models\Admin;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmRbacApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_me_permissions_returns_slugs_for_marketing(): void
    {
        $admin = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/me/permissions');

        $response->assertOk()
            ->assertJsonFragment(['role' => 'marketing'])
            ->assertJsonStructure(['permissions', 'role', 'crm_role']);

        $this->assertContains('leads.import', $response->json('permissions'));
    }

    public function test_sales_employee_cannot_access_staff_manage_users_post(): void
    {
        $admin = Admin::query()->where('email', 'sales.employee@themesdesign.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'role' => 'sales_employee',
        ])->assertForbidden();
    }

    public function test_super_admin_has_rbac_permission(): void
    {
        $admin = Admin::query()->where('email', 'superadmin@themesdesign.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/me/permissions');

        $response->assertOk();
        $this->assertContains('rbac.manage_permissions', $response->json('permissions'));
    }

    public function test_marketing_can_list_leads_api(): void
    {
        $admin = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/leads')->assertOk();
    }
}
