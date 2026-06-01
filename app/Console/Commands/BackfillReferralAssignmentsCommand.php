<?php

namespace App\Console\Commands;

use App\Services\EmployerProspectSyncService;
use Illuminate\Console\Command;

class BackfillReferralAssignmentsCommand extends Command
{
    protected $signature = 'crm:backfill-referral-assignments';

    protected $description = 'Auto-assign unassigned company prospects that have a valid employer referral code';

    public function handle(EmployerProspectSyncService $sync): int
    {
        $count = $sync->backfillReferralAssignments();
        $this->info("Auto-assigned {$count} company prospect(s) from stored referral codes.");

        return self::SUCCESS;
    }
}
