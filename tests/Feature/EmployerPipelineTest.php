<?php

namespace Tests\Feature;

use App\Models\Admin;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\CrmRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmRbacSeeder::class);
        $this->seed(AdminRbacSeeder::class);
    }

    public function test_company_pipeline_index_loads_for_marketing(): void
    {
        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->firstOrFail();

        $this->actingAs($marketing, 'admin')
            ->get(route('admin.employers.pipeline.index'))
            ->assertOk();
    }
}
