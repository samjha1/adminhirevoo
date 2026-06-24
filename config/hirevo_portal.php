<?php

return [
    'catalog_employer_email' => env('HIREVO_CATALOG_EMPLOYER_EMAIL', 'catalog-employer@hirevo.com'),
    'employer_salary_min_floor_inr' => (int) env('HIREVO_EMPLOYER_SALARY_MIN_FLOOR_INR', 150_000),
    'csv_max_kb' => max(512, (int) env('HIREVO_JOB_IMPORT_MAX_KB', 5120)),
    'csv_max_rows' => max(50, (int) env('HIREVO_JOB_IMPORT_MAX_ROWS', 2000)),
];
