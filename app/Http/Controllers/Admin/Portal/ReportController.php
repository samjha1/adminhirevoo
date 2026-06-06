<?php

namespace App\Http\Controllers\Admin\Portal;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\Portal\PortalReportService;
use App\Support\PortalDateFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly PortalReportService $reports,
        private readonly AuditLogService $audit,
    ) {
    }

    public function index(): View
    {
        return view('admin.reports.index', [
            'reports' => $this->reports->allReports(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $format = $request->query('format', 'csv');
        $dateFilter = PortalDateFilter::fromRequest($request);
        $reports = $this->reports->allReports();
        $admin = auth('admin')->user();

        $this->audit->log('portal.reports.export', $admin, null, [
            'format' => $format,
            'period' => $dateFilter->key,
        ]);

        $filename = 'portal-reports-'.now()->format('Y-m-d');

        if ($format === 'pdf') {
            $html = view('admin.reports.export-print', compact('reports'))->render();

            return response()->streamDownload(
                fn () => print($html),
                $filename.'.html',
                ['Content-Type' => 'text/html'],
            );
        }

        $extension = $format === 'excel' ? 'csv' : 'csv';
        $filename .= '.'.$extension;

        return response()->streamDownload(function () use ($reports, $format) {
            $out = fopen('php://output', 'w');
            if ($format === 'excel') {
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            }

            fputcsv($out, ['Job Portal Reports', now()->format('Y-m-d H:i')]);
            fputcsv($out, []);

            foreach ($reports as $section => $metrics) {
                if ($section === 'generatedAt') {
                    continue;
                }
                fputcsv($out, [strtoupper($section)]);
                foreach ($metrics as $metric => $value) {
                    fputcsv($out, [str_replace('_', ' ', ucfirst($metric)), $value]);
                }
                fputcsv($out, []);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
