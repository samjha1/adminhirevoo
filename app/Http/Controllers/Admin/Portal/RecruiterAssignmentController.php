<?php

namespace App\Http\Controllers\Admin\Portal;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Hirevo\HirevoUser;
use App\Services\Portal\RecruiterCompanyAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruiterAssignmentController extends Controller
{
    public function __construct(
        private readonly RecruiterCompanyAssignmentService $assignments,
    ) {
    }

    public function index(): View
    {
        return view('admin.portal.recruiter-assignments.index', [
            'recruiters' => $this->assignments->recruitersWithCounts(),
        ]);
    }

    public function edit(Request $request, Admin $admin): View
    {
        abort_unless($admin->role === AdminRole::Recruiter, 404);

        $assignedIds = $this->assignments->assignedEmployerIdsFor($admin);

        $employersQuery = HirevoUser::query()
            ->where('role', 'referrer')
            ->with('referrerProfile')
            ->orderBy('name');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $employersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('referrerProfile', fn ($pq) => $pq
                        ->where('company_name', 'like', "%{$search}%"));
            });
        }

        return view('admin.portal.recruiter-assignments.edit', [
            'recruiter' => $admin,
            'assignedIds' => $assignedIds,
            'employers' => $employersQuery->paginate(25)->withQueryString(),
        ]);
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        abort_unless($admin->role === AdminRole::Recruiter, 404);

        $validated = $request->validate([
            'employer_ids' => ['nullable', 'array'],
            'employer_ids.*' => ['integer', 'min:1'],
        ]);

        $summary = $this->assignments->syncAssignments(
            $admin,
            $validated['employer_ids'] ?? [],
            auth('admin')->user(),
        );

        $message = "Assignments updated: {$summary['added']} added, {$summary['removed']} removed.";

        return redirect()
            ->route('admin.portal.recruiter-assignments.edit', $admin->id)
            ->with('success', $message);
    }

    public function destroy(Admin $admin, int $employer): RedirectResponse
    {
        abort_unless($admin->role === AdminRole::Recruiter, 404);

        $this->assignments->removeAssignment($admin, $employer, auth('admin')->user());

        return back()->with('success', 'Company removed from recruiter.');
    }
}
