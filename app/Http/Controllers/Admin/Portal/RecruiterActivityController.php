<?php

namespace App\Http\Controllers\Admin\Portal;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoUser;
use App\Services\Portal\PortalRecruiterScopeService;
use App\Support\PortalDateFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RecruiterActivityController extends Controller
{
    public function __construct(
        private readonly PortalRecruiterScopeService $scope,
    ) {
    }

    public function managerIndex(Request $request): View
    {
        return $this->renderActivity($request, managerView: true);
    }

    public function myActivity(Request $request): View
    {
        return $this->renderActivity($request, managerView: false);
    }

    private function renderActivity(Request $request, bool $managerView): View
    {
        $admin = auth('admin')->user();
        $dateFilter = PortalDateFilter::fromRequest($request);

        $query = HirevoEmployerJobApplication::query()
            ->with([
                'candidate.candidateProfile',
                'job.employer.referrerProfile',
                'appliedByAdmin',
            ])
            ->whereNotNull('applied_by_admin_id')
            ->orderByDesc('created_at');

        if (Schema::hasColumn('employer_job_applications', 'applied_by_admin_id')) {
            if (! $managerView) {
                $query->where('applied_by_admin_id', $admin->id);
            } elseif ($request->filled('recruiter_id')) {
                $query->where('applied_by_admin_id', (int) $request->query('recruiter_id'));
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $this->scope->scopeApplicationsQuery($query, $admin);

        if ($request->filled('company_id')) {
            $companyId = (int) $request->query('company_id');
            $query->whereHas('job', fn ($q) => $q->where('user_id', $companyId));
        }

        if ($request->filled('job_id')) {
            $query->where('employer_job_id', (int) $request->query('job_id'));
        }

        $dateFilter->apply($query);

        $applications = $query->paginate(20)->withQueryString();

        $appShowRoute = Route::has('admin.portal.applications.show') && ! $admin->canPermission('leads.view')
            ? 'admin.portal.applications.show'
            : 'admin.applications.show';

        $view = $managerView
            ? 'admin.portal.recruiter-activity.index'
            : 'admin.portal.my-activity';

        $filterRecruiters = collect();
        $filterCompanies = collect();
        if ($managerView) {
            $filterRecruiters = Admin::query()
                ->where('role', AdminRole::Recruiter)
                ->orderBy('name')
                ->get(['id', 'name']);

            $filterCompanies = HirevoUser::query()
                ->where('role', 'referrer')
                ->with('referrerProfile')
                ->orderBy('name')
                ->limit(100)
                ->get(['id', 'name']);
        } elseif ($this->scope->isRecruiter($admin)) {
            $assignedIds = $this->scope->assignedEmployerIds($admin);
            if ($assignedIds !== []) {
                $filterCompanies = HirevoUser::query()
                    ->whereIn('id', $assignedIds)
                    ->with('referrerProfile')
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        }

        return view($view, [
            'applications' => $applications,
            'dateFilter' => $dateFilter,
            'managerView' => $managerView,
            'appShowRoute' => $appShowRoute,
            'filterRecruiters' => $filterRecruiters,
            'filterCompanies' => $filterCompanies,
        ]);
    }
}
