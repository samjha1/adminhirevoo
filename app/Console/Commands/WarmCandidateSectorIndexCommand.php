<?php

namespace App\Console\Commands;

use App\Services\CandidateSectorService;
use Illuminate\Console\Command;

class WarmCandidateSectorIndexCommand extends Command
{
    protected $signature = 'portal:warm-candidate-sectors {--fresh : Rebuild the index even if cached}';

    protected $description = 'Warm the cached candidate sector index used by job relevant-candidates pages';

    public function handle(CandidateSectorService $sectors): int
    {
        if ($this->option('fresh')) {
            $sectors->forgetSectorIndexCache();
            $this->info('Cleared existing sector index cache.');
        }

        $index = $sectors->sectorIndex();
        $total = array_sum(array_map('count', $index));

        $this->info("Sector index ready: {$total} candidates across ".count($index).' buckets.');

        return self::SUCCESS;
    }
}
