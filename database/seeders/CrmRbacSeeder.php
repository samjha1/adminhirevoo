<?php

namespace Database\Seeders;

use App\Modules\Rbac\Services\RbacCatalogSyncService;
use Illuminate\Database\Seeder;

class CrmRbacSeeder extends Seeder
{
    public function run(): void
    {
        app(RbacCatalogSyncService::class)->sync();
    }
}
