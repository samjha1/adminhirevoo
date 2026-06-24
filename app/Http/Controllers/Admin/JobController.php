<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Services\AuditLogService;
use App\Services\Hirevo\HirevoEmployerJobImportService;
use App\Support\PortalDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JobController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $dateFilter = PortalDateFilter::fromRequest($request);
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = HirevoEmployerJob::query()
            ->with(['employer.referrerProfile'])
            ->withCount('applications')
            ->orderBy($sort, $direction);

        $dateFilter->apply($query);

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'expired') {
                $query->where('status', 'closed');
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('job_department', 'like', "%{$search}%")
                    ->orWhereHas('employer', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%")
                            ->orWhereHas('referrerProfile', fn ($pq) => $pq
                                ->where('company_name', 'like', "%{$search}%"));
                    });
            });
        }

        $today = now()->startOfDay();
        $stats = [
            'total' => HirevoEmployerJob::query()->count(),
            'active' => HirevoEmployerJob::query()->where('status', 'active')->count(),
            'expired' => HirevoEmployerJob::query()->where('status', 'closed')->count(),
            'draft' => HirevoEmployerJob::query()->where('status', 'draft')->count(),
            'today' => HirevoEmployerJob::query()->where('created_at', '>=', $today)->count(),
        ];

        return view('admin.jobs.index', [
            'jobs' => $query->paginate(20)->withQueryString(),
            'dateFilter' => $dateFilter,
            'stats' => $stats,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function updateStatus(Request $request, HirevoEmployerJob $job): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,active,closed'],
        ]);

        $old = $job->status;
        $job->status = $validated['status'];
        $job->save();

        $this->audit->log('portal.jobs.status_update', auth('admin')->user(), $job, [
            'from' => $old,
            'to' => $validated['status'],
        ]);

        return back()->with('success', "Job {$job->title} status updated.");
    }

    public function importForm(): View
    {
        $catalogEmail = (string) config('hirevo_portal.catalog_employer_email', 'catalog-employer@hirevo.com');
        $samplePath = HirevoEmployerJobImportService::hirevoCsvPath('employer_jobs_catalog_500.csv');

        return view('admin.jobs.import', [
            'templateHeaders' => HirevoEmployerJobImportService::CSV_HEADERS,
            'catalogEmail' => $catalogEmail,
            'hasSampleCsv' => $samplePath !== null,
            'maxKb' => (int) config('hirevo_portal.csv_max_kb', 5120),
            'maxRows' => (int) config('hirevo_portal.csv_max_rows', 2000),
        ]);
    }

    public function importStore(Request $request, HirevoEmployerJobImportService $importService): RedirectResponse
    {
        $maxKb = (int) config('hirevo_portal.csv_max_kb', 5120);

        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'max:'.$maxKb,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('Please choose a CSV file to upload.');
                        return;
                    }
                    $ext = strtolower($value->getClientOriginalExtension() ?: '');
                    if (! in_array($ext, ['csv', 'txt'], true)) {
                        $fail('The file must be a .csv file.');
                    }
                },
            ],
            'skip_duplicates' => ['nullable', 'boolean'],
            'catalog_email' => ['nullable', 'email', 'max:255'],
        ]);

        $file = $request->file('csv_file');
        $path = $file?->getRealPath();
        if (! is_string($path) || $path === '') {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        try {
            $summary = $importService->importFromCsvFile(
                $path,
                $request->input('catalog_email'),
                $request->boolean('skip_duplicates', true),
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: '.$e->getMessage());
        }

        $this->audit->log('portal.jobs.csv_import', auth('admin')->user(), null, $summary);

        $failedCount = count($summary['failed']);
        $message = "Import complete: {$summary['imported']} imported, {$summary['skipped']} skipped, {$failedCount} failed.";

        if ($failedCount > 0) {
            $details = collect($summary['failed'])
                ->take(8)
                ->map(fn (array $f) => "Line {$f['line']}: {$f['message']}")
                ->implode(' | ');

            return redirect()
                ->route('admin.jobs.import')
                ->with('warning', $message.' '.$details);
        }

        return redirect()
            ->route('admin.jobs.index')
            ->with('success', $message);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, HirevoEmployerJobImportService::CSV_HEADERS);
            fclose($out);
        }, 'employer_jobs_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadSample(): BinaryFileResponse|RedirectResponse
    {
        $path = HirevoEmployerJobImportService::hirevoCsvPath('employer_jobs_catalog_500.csv');
        if ($path === null) {
            return redirect()
                ->route('admin.jobs.import')
                ->with('error', 'Sample catalog CSV not found on server.');
        }

        return response()->download($path, 'employer_jobs_catalog_500.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
