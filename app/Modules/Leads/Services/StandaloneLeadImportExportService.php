<?php

namespace App\Modules\Leads\Services;

use App\Models\Admin;
use App\Modules\Leads\Models\CrmStandaloneLead;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StandaloneLeadImportExportService
{
    /** @return array{imported: int, skipped: int} */
    public function importCsv(UploadedFile $file, Admin $admin): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $imported = 0;
        $skipped = 0;

        if (! $header) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0];
        }

        $map = $this->headerMap($header);

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim($row[$map['name']] ?? '');
            if ($name === '') {
                $skipped++;

                continue;
            }

            CrmStandaloneLead::query()->create([
                'name' => $name,
                'phone' => trim($row[$map['phone']] ?? '') ?: null,
                'email' => trim($row[$map['email']] ?? '') ?: null,
                'source' => trim($row[$map['source']] ?? '') ?: null,
                'notes' => trim($row[$map['notes']] ?? '') ?: null,
                'created_by' => $admin->id,
            ]);
            $imported++;
        }

        fclose($handle);

        return compact('imported', 'skipped');
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'marketing-leads-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'phone', 'email', 'source', 'notes', 'assignment_status', 'sales_status', 'created_at']);

            CrmStandaloneLead::query()->orderBy('id')->chunk(200, function ($rows) use ($out): void {
                foreach ($rows as $lead) {
                    fputcsv($out, [
                        $lead->name,
                        $lead->phone,
                        $lead->email,
                        $lead->source,
                        $lead->notes,
                        $lead->assignment_status,
                        $lead->sales_status,
                        $lead->created_at?->toDateTimeString(),
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'phone', 'email', 'source', 'notes']);
            fputcsv($out, ['Jane Doe', '+911234567890', 'jane@example.com', 'facebook', 'Interested in upskilling']);
            fclose($out);
        }, 'marketing-leads-template.csv', ['Content-Type' => 'text/csv']);
    }

    /** @param  list<string|null>  $header */
    private function headerMap(array $header): array
    {
        $normalized = [];
        foreach ($header as $i => $col) {
            $normalized[strtolower(trim((string) $col))] = $i;
        }

        return [
            'name' => $normalized['name'] ?? 0,
            'phone' => $normalized['phone'] ?? 1,
            'email' => $normalized['email'] ?? 2,
            'source' => $normalized['source'] ?? 3,
            'notes' => $normalized['notes'] ?? 4,
        ];
    }
}
