<?php

namespace App\Console\Commands;

use App\Services\AdminReferralCodeService;
use Illuminate\Console\Command;

class EnsureReferralCodesCommand extends Command
{
    protected $signature = 'crm:ensure-referral-codes';

    protected $description = 'Generate referral codes for company-team CRM staff';

    public function handle(AdminReferralCodeService $codes): int
    {
        $count = $codes->backfillEmployerTeamCodes();
        $this->info("Ensured referral codes for {$count} company team staff member(s).");

        return self::SUCCESS;
    }
}
