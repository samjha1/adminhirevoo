<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoEmployerJob;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoResume;
use App\Models\Hirevo\HirevoUser;
use App\Services\AuditLogService;
use App\Services\Hirevo\JobMatchScoreService;
use App\Support\PortalDateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly JobMatchScoreService $matchScore,
    ) {
    }

    public function index(Request $request): View
    {
        $dateFilter = PortalDateFilter::fromRequest($request);
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = HirevoEmployerJobApplication::query()
            ->with(['candidate.resumes', 'job.employer.referrerProfile'])
            ->orderBy($sort, $direction);

        $dateFilter->apply($query);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('company_id')) {
            $companyId = (int) $request->query('company_id');
            $query->whereHas('job', fn ($q) => $q->where('user_id', $companyId));
        }

        if ($request->filled('job_id')) {
            $query->where('employer_job_id', (int) $request->query('job_id'));
        }

        if ($request->filled('candidate_id')) {
            $query->where('user_id', (int) $request->query('candidate_id'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->whereHas('candidate', function ($candidateQ) use ($search) {
                    $candidateQ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('job', function ($jobQ) use ($search) {
                    $jobQ->where('title', 'like', "%{$search}%")
                        ->orWhereHas('employer', function ($employerQ) use ($search) {
                            $employerQ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhereHas('referrerProfile', function ($profileQ) use ($search) {
                                    $profileQ->where('company_name', 'like', "%{$search}%");
                                });
                        });
                });
            });
        }

        $now = now();
        $stats = [
            'total' => HirevoEmployerJobApplication::query()->count(),
            'today' => HirevoEmployerJobApplication::query()->where('created_at', '>=', $now->copy()->startOfDay())->count(),
            'weekly' => HirevoEmployerJobApplication::query()->where('created_at', '>=', $now->copy()->startOfWeek())->count(),
            'monthly' => HirevoEmployerJobApplication::query()->where('created_at', '>=', $now->copy()->startOfMonth())->count(),
        ];

        $applications = $query->paginate(15)->withQueryString();
        $applications->getCollection()->transform(function (HirevoEmployerJobApplication $application) {
            $resume = $this->primaryResumeFor($application);
            $application->setAttribute('ai_resume_summary', (string) ($resume?->ai_summary ?? ''));
            $application->setAttribute('profile_match_percent', $this->matchScore->resolveMatchPercent($application, $resume));

            return $application;
        });

        $filterCompanies = HirevoUser::query()
            ->where('role', 'referrer')
            ->whereHas('referrerProfile')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name']);

        $filterJobs = HirevoEmployerJob::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'title']);

        $filterCandidates = HirevoUser::query()
            ->where('role', 'candidate')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'name']);

        return view('admin.applications.index', [
            'applications' => $applications,
            'dateFilter' => $dateFilter,
            'stats' => $stats,
            'filterCompanies' => $filterCompanies,
            'filterJobs' => $filterJobs,
            'filterCandidates' => $filterCandidates,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(HirevoEmployerJobApplication $application): View
    {
        $application->load([
            'candidate.candidateProfile',
            'candidate.resumes',
            'job.employer.referrerProfile',
        ]);

        $resume = $this->primaryResumeFor($application);
        $application->setAttribute('ai_resume_summary', (string) ($resume?->ai_summary ?? ''));
        $application->setAttribute('profile_match_percent', $this->matchScore->resolveMatchPercent($application, $resume));

        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    public function updateStatus(Request $request, HirevoEmployerJobApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                'applied', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected', 'qualified',
            ])],
        ]);

        $old = $application->status;
        $application->status = $validated['status'];
        $application->save();

        $this->audit->log('portal.applications.status_update', auth('admin')->user(), $application, [
            'from' => $old,
            'to' => $validated['status'],
        ]);

        return back()->with('success', 'Application status updated.');
    }

    private function primaryResumeFor(HirevoEmployerJobApplication $application): ?HirevoResume
    {
        return $application->candidate?->resumes
            ?->sortByDesc(fn ($r) => (int) (($r->is_primary ?? false) ? 1 : 0))
            ?->sortByDesc('created_at')
            ?->first();
    }
}
