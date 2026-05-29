<?php

namespace App\Console\Commands;

use App\Modules\Rbac\Services\PermissionResolver;
use Illuminate\Console\Command;

class ClearPermissionCacheCommand extends Command
{
    protected $signature = 'permission:cache-clear {admin_id? : Optional admin id}';

    protected $description = 'Clear cached CRM permission sets';

    public function handle(PermissionResolver $resolver): int
    {
        $adminId = $this->argument('admin_id');

        if ($adminId) {
            $resolver->forget((int) $adminId);
            $this->info("Cleared permission cache for admin #{$adminId}.");

            return self::SUCCESS;
        }

        $resolver->forgetAll();
        $this->info('Cleared all permission caches.');

        return self::SUCCESS;
    }
}
