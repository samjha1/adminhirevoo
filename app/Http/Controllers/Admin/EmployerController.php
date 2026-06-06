<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoUser;
use App\Services\AuditLogService;
use App\Support\PortalDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerController extends Controller
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

        $query = HirevoUser::query()
            ->where('role', 'referrer')
            ->with('referrerProfile')
            ->withCount([
                'employerJobs',
                'employerJobs as active_jobs_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->orderBy($sort, $direction);

        $query->addSelect([
            'applications_received_count' => HirevoEmployerJobApplication::query()
                ->selectRaw('count(*)')
                ->whereIn('employer_job_id', function ($sub) {
                    $sub->select('id')
                        ->from('employer_jobs')
                        ->whereColumn('employer_jobs.user_id', 'users.id');
                }),
        ]);

        $dateFilter->apply($query);

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'pending') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('referrerProfile')
                        ->orWhereHas('referrerProfile', fn ($q2) => $q2->where('is_approved', false));
                });
            } elseif ($status === 'approved' || $status === 'active') {
                $query->where('status', 'active')
                    ->whereHas('referrerProfile', fn ($q) => $q->where('is_approved', true));
            } elseif ($status === 'inactive') {
                $query->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhereHas('referrerProfile', fn ($q2) => $q2->where('is_approved', false));
                });
            }
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('referrerProfile', fn ($pq) => $pq
                        ->where('company_name', 'like', "%{$search}%")
                        ->orWhere('company_email', 'like', "%{$search}%"));
            });
        }

        return view('admin.employers.index', [
            'employers' => $query->paginate(20)->withQueryString(),
            'dateFilter' => $dateFilter,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(Request $request, HirevoUser $employer): View
    {
        abort_unless($employer->role === 'referrer', 404);

        $employer->load(['referrerProfile']);

        $jobsQuery = $employer->employerJobs()
            ->withCount('applications')
            ->orderByDesc('created_at');

        $jobStats = [
            'total' => (clone $employer->employerJobs())->count(),
            'active' => (clone $employer->employerJobs())->where('status', 'active')->count(),
            'draft' => (clone $employer->employerJobs())->where('status', 'draft')->count(),
            'closed' => (clone $employer->employerJobs())->where('status', 'closed')->count(),
            'applications' => HirevoEmployerJobApplication::query()
                ->whereIn('employer_job_id', $employer->employerJobs()->pluck('id'))
                ->count(),
            'applications_today' => HirevoEmployerJobApplication::query()
                ->whereIn('employer_job_id', $employer->employerJobs()->pluck('id'))
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
        ];

        $jobs = $jobsQuery->paginate(10, ['*'], 'jobs_page')->withQueryString();

        $appDateFilter = PortalDateFilter::fromRequest($request, 'app_period');
        $applicationsQuery = HirevoEmployerJobApplication::query()
            ->with(['candidate.resumes', 'job'])
            ->whereIn('employer_job_id', $employer->employerJobs()->pluck('id'))
            ->orderByDesc('created_at');

        $appDateFilter->apply($applicationsQuery);

        $applications = $applicationsQuery->paginate(15, ['*'], 'apps_page')->withQueryString();

        return view('admin.employers.show', [
            'employer' => $employer,
            'jobs' => $jobs,
            'jobStats' => $jobStats,
            'applications' => $applications,
            'appDateFilter' => $appDateFilter,
        ]);
    }

    public function approve(HirevoUser $employer): RedirectResponse
    {
        abort_unless($employer->role === 'referrer', 404);

        $profile = $employer->referrerProfile;
        if (! $profile) {
            return back()->with('error', 'Employer has no profile. They must complete profile first.');
        }

        $profile->is_approved = true;
        $profile->approved_at = now();
        $profile->save();

        $this->audit->log('portal.companies.approve', auth('admin')->user(), $employer, [
            'company' => $profile->company_name,
        ]);

        return back()->with('success', "Employer {$employer->name} approved.");
    }

    public function reject(HirevoUser $employer): RedirectResponse
    {
        abort_unless($employer->role === 'referrer', 404);

        $profile = $employer->referrerProfile;
        if ($profile) {
            $profile->is_approved = false;
            $profile->approved_at = null;
            $profile->save();
        }

        $this->audit->log('portal.companies.reject', auth('admin')->user(), $employer);

        return back()->with('success', "Employer {$employer->name} rejected.");
    }
}
