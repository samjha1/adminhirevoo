<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerController extends Controller
{
    public function index(Request $request): View
    {
        $query = HirevoUser::query()
            ->where('role', 'referrer')
            ->with('referrerProfile')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            if ($request->string('status')->toString() === 'pending') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('referrerProfile')
                        ->orWhereHas('referrerProfile', fn ($q2) => $q2->where('is_approved', false));
                });
            }

            if ($request->string('status')->toString() === 'approved') {
                $query->whereHas('referrerProfile', fn ($q) => $q->where('is_approved', true));
            }
        }

        $employers = $query->paginate(20)->withQueryString();

        return view('admin.employers.index', [
            'employers' => $employers,
        ]);
    }

    public function show(HirevoUser $employer): View
    {
        abort_unless($employer->role === 'referrer', 404);

        $employer->load([
            'referrerProfile',
        ]);

        $jobsQuery = $employer->employerJobs()
            ->withCount('applications')
            ->orderByDesc('created_at');

        $jobStats = [
            'total' => (clone $jobsQuery)->count(),
            'active' => (clone $jobsQuery)->where('status', 'active')->count(),
            'draft' => (clone $jobsQuery)->where('status', 'draft')->count(),
            'closed' => (clone $jobsQuery)->where('status', 'closed')->count(),
            'applications' => (clone $jobsQuery)->sum('applications_count'),
        ];

        $jobs = $jobsQuery->paginate(10)->withQueryString();

        return view('admin.employers.show', [
            'employer' => $employer,
            'jobs' => $jobs,
            'jobStats' => $jobStats,
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

        return back()->with('success', "Employer {$employer->name} rejected.");
    }
}

