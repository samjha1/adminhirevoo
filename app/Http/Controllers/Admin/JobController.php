<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoEmployerJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $query = HirevoEmployerJob::query()
            ->with('employer')
            ->withCount('applications')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return view('admin.jobs.index', [
            'jobs' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function updateStatus(Request $request, HirevoEmployerJob $job): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,active,closed'],
        ]);

        $job->status = $validated['status'];
        $job->save();

        return back()->with('success', "Job {$job->title} status updated.");
    }
}

