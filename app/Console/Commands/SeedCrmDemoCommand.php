<?php

namespace App\Console\Commands;

use Database\Seeders\CrmDemoDataSeeder;
use Illuminate\Console\Command;

class SeedCrmDemoCommand extends Command
{
    protected $signature = 'crm:seed-demo';

    protected $description = 'Insert ~30 demo records per CRM section for local testing (safe to re-run)';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => CrmDemoDataSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
