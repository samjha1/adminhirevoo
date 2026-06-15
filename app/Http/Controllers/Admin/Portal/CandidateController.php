<?php

namespace App\Http\Controllers\Admin\Portal;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoUser;
use App\Services\CandidateExportService;
use App\Services\CandidateSectorService;
use App\Support\PortalDateFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    public function __construct(
        private readonly CandidateSectorService $sectors,
    ) {
    }

    public function index(Request $request): View
    {
        $dateFilter = PortalDateFilter::fromRequest($request);
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'email', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $baseQuery = HirevoUser::query()
            ->where('role', 'candidate');

        $dateFilter->apply($baseQuery);

        $sectorCounts = $this->sectors->candidateCategoryCounts(clone $baseQuery);

        $query = (clone $baseQuery)
            ->with([
                'candidateProfile',
                'leads.jobRole',
                'jobApplications.jobRole',
                'resumes' => fn ($q) => $q->orderByDesc('is_primary')->orderByDesc('created_at')->limit(1),
            ])
            ->withCount('employerApplications')
            ->orderBy($sort, $direction);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('candidateProfile', function ($pq) use ($search) {
                        $pq->where('skills', 'like', "%{$search}%")
                            ->orWhere('headline', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('sector')) {
            $this->sectors->applyCandidateFilter($query, $request->string('sector')->toString());
        }

        $resolvedSectors = [];
        $candidates = $query->paginate(20)->withQueryString();
        foreach ($candidates as $candidate) {
            $resolvedSectors[$candidate->id] = $this->sectors->resolveForCandidate($candidate);
        }

        return view('admin.candidates.index', [
            'candidates' => $candidates,
            'dateFilter' => $dateFilter,
            'sort' => $sort,
            'direction' => $direction,
            'sectorCatalog' => $this->sectors->catalog(),
            'sectorCounts' => $sectorCounts,
            'resolvedSectors' => $resolvedSectors,
        ]);
    }

    public function export(Request $request, CandidateExportService $export): StreamedResponse
    {
        return $export->export($request);
    }

    public function show(HirevoUser $candidate): View
    {
        abort_unless($candidate->role === 'candidate', 404);

        $candidate->load([
            'candidateProfile',
            'resumes',
            'employerApplications.job.employer.referrerProfile',
            'leads.jobRole',
            'jobApplications.jobRole',
        ]);

        return view('admin.candidates.show', [
            'candidate' => $candidate,
            'sectorLabel' => $this->sectors->labelForCategory($this->sectors->resolveForCandidate($candidate)),
        ]);
    }
}
