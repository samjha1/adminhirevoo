<?php

namespace App\Modules\Leads\Services;

use App\Enums\CompanyOutreachStage;
use App\Models\Admin;
use App\Modules\Leads\Models\CrmCompanyOutreachLead;
use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyOutreachImportService
{
  /** @return array{imported: int, skipped: int} */
    public function import(UploadedFile $file, Admin $admin): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = $extension === 'xlsx'
            ? $this->readXlsx($file->getRealPath())
            : $this->readCsv($file->getRealPath());

        if ($rows === []) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $header = array_shift($rows);
        $map = $this->headerMap($header);
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $companyName = trim($row[$map['company_name']] ?? '');
            if ($companyName === '') {
                $skipped++;

                continue;
            }

            $email = $this->nullableCell($row, $map, 'email');
            $phone = $this->nullableCell($row, $map, 'phone');

            if ($this->isDuplicate($companyName, $email, $phone)) {
                $skipped++;

                continue;
            }

            CrmCompanyOutreachLead::query()->create([
                'company_name' => $companyName,
                'contact_name' => $this->nullableCell($row, $map, 'contact_name'),
                'phone' => $phone,
                'email' => $email,
                'industry' => $this->nullableCell($row, $map, 'industry'),
                'website' => $this->nullableCell($row, $map, 'website'),
                'location' => $this->nullableCell($row, $map, 'location'),
                'source' => $this->nullableCell($row, $map, 'source') ?? 'excel_import',
                'notes' => $this->nullableCell($row, $map, 'notes'),
                'outreach_stage' => CompanyOutreachStage::New->value,
                'created_by' => $admin->id,
            ]);
            $imported++;
        }

        return compact('imported', 'skipped');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'company_name',
                'contact_name',
                'phone',
                'email',
                'industry',
                'website',
                'location',
                'source',
                'notes',
            ]);
            fputcsv($out, [
                'Acme Corp',
                'John Smith',
                '+911234567890',
                'john@acme.com',
                'IT Services',
                'https://acme.com',
                'Mumbai',
                'linkedin',
                'Met at conference',
            ]);
            fclose($out);
        }, 'company-outreach-leads-template.csv', ['Content-Type' => 'text/csv']);
    }

    /** @return list<list<string>> */
    private function readCsv(string $path): array
    {
        $reader = new CsvReader;
        $reader->open($path);

        return $this->collectRows($reader);
    }

    /** @return list<list<string>> */
    private function readXlsx(string $path): array
    {
        $reader = new XlsxReader;
        $reader->open($path);

        return $this->collectRows($reader);
    }

    /** @return list<list<string>> */
    private function collectRows(CsvReader|XlsxReader $reader): array
    {
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cells[] = trim((string) $cell->getValue());
                }
                if ($cells !== [] && implode('', $cells) !== '') {
                    $rows[] = $cells;
                }
            }
            break;
        }

        $reader->close();

        return $rows;
    }

    /** @param  list<string|null>  $header */
    private function headerMap(array $header): array
    {
        $normalized = [];
        foreach ($header as $i => $col) {
            $key = strtolower(trim((string) $col));
            $key = str_replace([' ', '-'], '_', $key);
            $normalized[$key] = $i;
        }

        $aliases = [
            'company' => 'company_name',
            'companyname' => 'company_name',
            'contact' => 'contact_name',
            'contactname' => 'contact_name',
            'mobile' => 'phone',
            'phone_number' => 'phone',
            'email_address' => 'email',
            'city' => 'location',
        ];

        foreach ($aliases as $from => $to) {
            if (isset($normalized[$from]) && ! isset($normalized[$to])) {
                $normalized[$to] = $normalized[$from];
            }
        }

        return [
            'company_name' => $normalized['company_name'] ?? 0,
            'contact_name' => $normalized['contact_name'] ?? 1,
            'phone' => $normalized['phone'] ?? 2,
            'email' => $normalized['email'] ?? 3,
            'industry' => $normalized['industry'] ?? 4,
            'website' => $normalized['website'] ?? 5,
            'location' => $normalized['location'] ?? 6,
            'source' => $normalized['source'] ?? 7,
            'notes' => $normalized['notes'] ?? 8,
        ];
    }

    /** @param  list<string>  $row */
    private function nullableCell(array $row, array $map, string $key): ?string
    {
        $value = trim($row[$map[$key]] ?? '');

        return $value !== '' ? $value : null;
    }

    private function isDuplicate(string $companyName, ?string $email, ?string $phone): bool
    {
        return CrmCompanyOutreachLead::query()
            ->where(function ($q) use ($companyName, $email, $phone) {
                $q->where('company_name', $companyName);
                if ($email) {
                    $q->orWhere('email', $email);
                }
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->exists();
    }
}
