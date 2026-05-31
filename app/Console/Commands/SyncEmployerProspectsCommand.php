<?php

namespace App\Console\Commands;

use App\Services\EmployerProspectSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncEmployerProspectsCommand extends Command
{
    protected $signature = 'crm:sync-employer-prospects';

    protected $description = 'Sync Hirevo referrer accounts into the company CRM pipeline';

    public function handle(EmployerProspectSyncService $sync): int
    {
        $count = $sync->syncFromHirevo();
        Cache::forget('crm.employer_prospect_sync');
        $this->info("Synced {$count} company prospect(s) from Hirevo.");

        return self::SUCCESS;
    }
}
