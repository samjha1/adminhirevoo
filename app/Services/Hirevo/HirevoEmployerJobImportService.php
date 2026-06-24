<?php

namespace App\Services\Hirevo;

use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoReferrerProfile;
use App\Models\Hirevo\HirevoUser;
use App\Support\HirevoEmployerJobPayload;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HirevoEmployerJobImportService
{
    public const CSV_HEADERS = [
        'company_name',
        'title',
        'job_department',
        'job_type',
        'work_location_type',
        'pay_type',
        'location_city',
        'location_state',
        'location_country',
        'salary_min',
        'salary_max',
        'experience_years',
        'description',
        'required_skills',
        'perks',
        'apply_link',
        'joining_fee_required',
        'is_night_shift',
        'status',
        'display_applications_count',
        'posted_days_ago',
    ];

    /**
     * @return array{imported: int, skipped: int, failed: list<array{line: int, message: string}>}
     */
    public function importFromCsvFile(string $path, ?string $employerEmail = null, bool $skipDuplicates = true): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException("CSV file not readable: {$path}");
        }

        $employer = $this->ensureCatalogEmployer($employerEmail);
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Could not open CSV file: {$path}");
        }

        $summary = ['imported' => 0, 'skipped' => 0, 'failed' => []];
        $lineNumber = 0;
        $headers = null;
        $maxRows = (int) config('hirevo_portal.csv_max_rows', 2000);

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($lineNumber === 1) {
                    $headers = $this->normalizeHeaders($row);
                    continue;
                }

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                if ($summary['imported'] + $summary['skipped'] >= $maxRows) {
                    $summary['failed'][] = [
                        'line' => $lineNumber,
                        'message' => "Row limit ({$maxRows}) reached. Import stopped.",
                    ];
                    break;
                }

                if ($headers === null) {
                    $summary['failed'][] = ['line' => $lineNumber, 'message' => 'Missing header row.'];
                    continue;
                }

                $assoc = $this->assocRow($headers, $row);
                $result = $this->importRow($assoc, $employer, $skipDuplicates);

                if ($result === 'imported') {
                    $summary['imported']++;
                } elseif ($result === 'skipped') {
                    $summary['skipped']++;
                } else {
                    $summary['failed'][] = ['line' => $lineNumber, 'message' => $result];
                }
            }
        } finally {
            fclose($handle);
        }

        return $summary;
    }

    public function ensureCatalogEmployer(?string $email = null): HirevoUser
    {
        $email = strtolower(trim($email ?: (string) config('hirevo_portal.catalog_employer_email', 'catalog-employer@hirevo.com')));

        $employer = HirevoUser::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Hirevo Catalog Employer',
                'password' => Hash::make('ChangeMeCatalog!'),
                'role' => 'referrer',
                'status' => 'active',
            ]
        );

        HirevoReferrerProfile::query()->firstOrCreate(
            ['user_id' => $employer->id],
            [
                'company_name' => 'Hirevo Catalog',
                'company_email' => $email,
                'is_approved' => true,
                'credits' => 100,
            ]
        );

        return $employer->fresh(['referrerProfile']);
    }

    /**
     * @return 'imported'|'skipped'|string
     */
    protected function importRow(array $row, HirevoUser $employer, bool $skipDuplicates): string
    {
        try {
            $validated = $this->validateRow($row);
        } catch (ValidationException $e) {
            return implode(' ', $e->validator->errors()->all());
        }

        $companyName = trim((string) ($validated['company_name'] ?? ''));
        $title = trim((string) $validated['title']);

        if ($skipDuplicates) {
            $exists = HirevoEmployerJob::query()
                ->where('user_id', $employer->id)
                ->where('title', $title)
                ->where('company_name', $companyName)
                ->exists();
            if ($exists) {
                return 'skipped';
            }
        }

        $attributes = HirevoEmployerJobPayload::buildAttributesFromValidated($validated, $companyName);
        $attributes['user_id'] = $employer->id;
        $attributes['slug'] = Str::slug($title).'-'.substr(uniqid(), -5);

        $postedDaysAgo = isset($validated['posted_days_ago']) && $validated['posted_days_ago'] !== ''
            ? max(0, min(365, (int) $validated['posted_days_ago']))
            : null;

        $timestamps = [];
        if ($postedDaysAgo !== null) {
            $postedAt = now()->subDays($postedDaysAgo);
            $timestamps['created_at'] = $postedAt;
            $timestamps['updated_at'] = $postedAt;
        }

        HirevoEmployerJob::query()->insert(array_merge($attributes, $timestamps ?: [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return 'imported';
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function validateRow(array $row): array
    {
        $salaryMinFloor = (int) config('hirevo_portal.employer_salary_min_floor_inr', 150_000);
        $floorLabel = number_format($salaryMinFloor);

        $data = [
            'company_name' => $row['company_name'] ?? null,
            'title' => $row['title'] ?? null,
            'job_department' => $row['job_department'] ?? null,
            'job_type' => $row['job_type'] ?? null,
            'work_location_type' => $row['work_location_type'] ?? null,
            'pay_type' => $row['pay_type'] ?? null,
            'location_city' => $row['location_city'] ?? null,
            'location_state' => $row['location_state'] ?? null,
            'location_country' => $row['location_country'] ?? 'India',
            'location_area' => $row['location_area'] ?? null,
            'location_pincode' => $row['location_pincode'] ?? null,
            'location_radius' => $row['location_radius'] ?? null,
            'salary_min' => $row['salary_min'] ?? null,
            'salary_max' => $row['salary_max'] ?? null,
            'experience_years' => $row['experience_years'] ?? null,
            'description' => $row['description'] ?? null,
            'required_skills' => $row['required_skills'] ?? null,
            'perks' => $row['perks'] ?? null,
            'apply_link' => $row['apply_link'] ?? null,
            'joining_fee_required' => $row['joining_fee_required'] ?? '0',
            'is_night_shift' => $row['is_night_shift'] ?? '0',
            'status' => $row['status'] ?? 'active',
            'display_applications_count' => $row['display_applications_count'] ?? null,
            'posted_days_ago' => $row['posted_days_ago'] ?? null,
        ];

        return Validator::make($data, [
            'company_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'job_department' => ['required', 'string', 'max:100'],
            'job_type' => ['required', 'in:full_time,part_time,contract,internship,temporary,volunteer,other'],
            'work_location_type' => ['required', 'in:office,remote,hybrid'],
            'pay_type' => ['required', 'in:fixed,hourly,negotiable,not_disclosed,other'],
            'location_city' => ['nullable', 'string', 'max:120'],
            'location_state' => ['nullable', 'string', 'max:120'],
            'location_country' => ['nullable', 'string', 'max:120'],
            'location_area' => ['nullable', 'string', 'max:120'],
            'location_pincode' => ['nullable', 'string', 'max:20'],
            'location_radius' => ['nullable', 'integer', 'min:1', 'max:500'],
            'salary_min' => [
                'nullable',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($data, $salaryMinFloor, $floorLabel): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $payType = (string) ($data['pay_type'] ?? '');
                    if (! in_array($payType, ['fixed', 'negotiable'], true)) {
                        return;
                    }
                    if ((int) $value > 0 && (int) $value < $salaryMinFloor) {
                        $fail("Minimum salary must be at least ₹{$floorLabel} per annum for fixed or negotiable pay.");
                    }
                },
            ],
            'salary_max' => ['nullable', 'integer', 'min:0'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'description' => ['nullable', 'string', 'max:10000'],
            'required_skills' => ['nullable', 'string', 'max:2000'],
            'perks' => ['nullable', 'string', 'max:2000'],
            'apply_link' => ['nullable', 'url', 'max:2048'],
            'joining_fee_required' => ['required', 'in:0,1'],
            'is_night_shift' => ['nullable', 'in:0,1'],
            'status' => ['nullable', 'in:draft,active,closed'],
            'display_applications_count' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'posted_days_ago' => ['nullable', 'integer', 'min:0', 'max:365'],
        ])->validate();
    }

    /**
     * @param  list<string|null>  $headers
     * @return list<string>
     */
    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function (?string $header): string {
            $header = (string) $header;
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

            return strtolower(trim($header));
        }, $headers);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string|null>  $row
     * @return array<string, string>
     */
    protected function assocRow(array $headers, array $row): array
    {
        $assoc = [];
        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }
            $assoc[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $assoc;
    }

    /**
     * @param  list<string|null>  $row
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    public static function hirevoCsvPath(string $filename): ?string
    {
        $candidates = [
            dirname(base_path()).DIRECTORY_SEPARATOR.'hirevo'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'csv'.DIRECTORY_SEPARATOR.$filename,
            base_path('storage'.DIRECTORY_SEPARATOR.'hirevo-csv'.DIRECTORY_SEPARATOR.$filename),
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
