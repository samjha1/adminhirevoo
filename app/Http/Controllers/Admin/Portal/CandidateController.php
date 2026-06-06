<?php

namespace App\Http\Controllers\Admin\Portal;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoUser;
use App\Support\PortalDateFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function index(Request $request): View
    {
        $dateFilter = PortalDateFilter::fromRequest($request);
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'email', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = HirevoUser::query()
            ->where('role', 'candidate')
            ->with('candidateProfile')
            ->withCount('employerApplications')
            ->orderBy($sort, $direction);

        $dateFilter->apply($query);

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

        return view('admin.candidates.index', [
            'candidates' => $query->paginate(20)->withQueryString(),
            'dateFilter' => $dateFilter,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(HirevoUser $candidate): View
    {
        abort_unless($candidate->role === 'candidate', 404);

        $candidate->load([
            'candidateProfile',
            'resumes',
            'employerApplications.job.employer.referrerProfile',
        ]);

        return view('admin.candidates.show', [
            'candidate' => $candidate,
        ]);
    }
}
