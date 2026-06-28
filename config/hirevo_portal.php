<?php

return [
    'catalog_employer_email' => env('HIREVO_CATALOG_EMPLOYER_EMAIL', 'catalog-employer@hirevo.com'),
    'employer_salary_min_floor_inr' => (int) env('HIREVO_EMPLOYER_SALARY_MIN_FLOOR_INR', 150_000),
    'csv_max_kb' => max(512, (int) env('HIREVO_JOB_IMPORT_MAX_KB', 5120)),
    'csv_max_rows' => max(50, (int) env('HIREVO_JOB_IMPORT_MAX_ROWS', 2000)),

    /** Seconds to cache candidate sector index (rebuilt on miss). */
    'candidate_sector_index_ttl' => max(60, (int) env('HIREVO_CANDIDATE_SECTOR_INDEX_TTL', 1800)),

    /** Seconds to cache per-job applied candidate IDs. */
    'job_applied_ids_cache_ttl' => max(30, (int) env('HIREVO_JOB_APPLIED_IDS_CACHE_TTL', 120)),

    /** Seconds to cache resolved job sector category. */
    'job_sector_cache_ttl' => max(60, (int) env('HIREVO_JOB_SECTOR_CACHE_TTL', 3600)),

    /** Seconds to cache match-sorted relevant candidate ID lists per job. */
    'job_relevant_sort_cache_ttl' => max(60, (int) env('HIREVO_JOB_RELEVANT_SORT_CACHE_TTL', 300)),

    /**
     * Indian metros for recruiter job ↔ candidate location ranking (aliases roll up to label).
     *
     * @var list<array{label: string, aliases: list<string>}>
     */
    'main_cities' => [
        ['label' => 'Mumbai', 'aliases' => ['mumbai']],
        ['label' => 'Delhi NCR', 'aliases' => ['delhi', 'new delhi', 'delhi ncr', 'ncr', 'gurgaon', 'gurugram', 'noida', 'faridabad', 'ghaziabad', 'greater noida']],
        ['label' => 'Bangalore', 'aliases' => ['bangalore', 'bengaluru']],
        ['label' => 'Hyderabad', 'aliases' => ['hyderabad', 'secunderabad']],
        ['label' => 'Chennai', 'aliases' => ['chennai', 'madras']],
        ['label' => 'Pune', 'aliases' => ['pune', 'pimpri', 'chinchwad']],
        ['label' => 'Kolkata', 'aliases' => ['kolkata', 'calcutta']],
        ['label' => 'Ahmedabad', 'aliases' => ['ahmedabad']],
        ['label' => 'Jaipur', 'aliases' => ['jaipur']],
        ['label' => 'Chandigarh', 'aliases' => ['chandigarh', 'mohali', 'panchkula']],
        ['label' => 'Indore', 'aliases' => ['indore']],
        ['label' => 'Kochi', 'aliases' => ['kochi', 'cochin', 'ernakulam']],
        ['label' => 'Coimbatore', 'aliases' => ['coimbatore']],
        ['label' => 'Lucknow', 'aliases' => ['lucknow']],
        ['label' => 'Nagpur', 'aliases' => ['nagpur']],
        ['label' => 'Surat', 'aliases' => ['surat']],
        ['label' => 'Vadodara', 'aliases' => ['vadodara', 'baroda']],
        ['label' => 'Bhubaneswar', 'aliases' => ['bhubaneswar', 'bhubaneshwar']],
        ['label' => 'Visakhapatnam', 'aliases' => ['visakhapatnam', 'vizag']],
        ['label' => 'Thiruvananthapuram', 'aliases' => ['thiruvananthapuram', 'trivandrum']],
    ],
];
