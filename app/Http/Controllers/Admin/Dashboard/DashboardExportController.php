<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;
use App\Support\DashboardPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardExportController extends Controller
{
    public function __construct(
        private readonly ExecutiveDashboardService $executive,
    ) {
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->canPermission('analytics.export'), 403);

        $period = DashboardPeriod::fromRequest($request);
        $data = $this->executive->metricsFor($admin, $period);
        $format = $request->query('format', 'csv');

        $filename = 'dashboard-'.$period->key.'-'.now()->format('Y-m-d').'.'.($format === 'pdf' ? 'html' : 'csv');

        if ($format === 'pdf') {
            $html = view('admin.dashboard.export-print', $data)->render();

            return response()->streamDownload(
                fn () => print($html),
                $filename,
                ['Content-Type' => 'text/html'],
            );
        }

        return response()->streamDownload(function () use ($data, $period) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Dashboard export', $period->label()]);
            fputcsv($out, []);
            fputcsv($out, ['Section', 'Talent', 'Company', 'Combined']);
            foreach (['totalLeads', 'meetings', 'closed', 'revenue', 'conversionRate'] as $key) {
                fputcsv($out, [
                    $key,
                    $data['summary']['talent'][$key] ?? '',
                    $data['summary']['company'][$key] ?? '',
                    $data['summary']['combined'][$key] ?? '',
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Manager', 'Team', 'Leads', 'Meetings', 'Closures', 'Revenue']);
            foreach ($data['teamTables']['byManager'] ?? [] as $row) {
                fputcsv($out, [
                    $row['name'],
                    $row['team'],
                    $row['leads'],
                    $row['meetings'],
                    $row['closures'],
                    $row['revenue'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
