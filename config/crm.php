<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employer prospect sync (Hirevo referrers → CRM)
    |--------------------------------------------------------------------------
    |
    | Full sync runs at most once per TTL window when loading the company
    | pipeline. Use `php artisan crm:sync-employer-prospects` for immediate sync.
    |
    */
    'employer_prospect_sync_ttl_minutes' => (int) env('CRM_EMPLOYER_SYNC_TTL', 5),

];
