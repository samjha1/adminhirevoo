<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Services\AuditLogService;
use App\Support\PortalDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
