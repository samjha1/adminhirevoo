<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoEmployerJobApplication;
use App\Models\Hirevo\HirevoResume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $query = HirevoEmployerJobApplication::query()
            ->with(['candidate.resumes', 'job.employer.referrerProfile'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'qualified') {
                $query->where('status', 'qualified');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
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

        $applications = $query->paginate(10)->withQueryString();
        $applications->getCollection()->transform(function (HirevoEmployerJobApplication $application) {
            $resume = $this->primaryResumeFor($application);
            $application->setAttribute('ai_resume_summary', (string) ($resume?->ai_summary ?? ''));
            $application->setAttribute('profile_match_percent', $this->resolveMatchPercent($application, $resume));

            return $application;
        });

        return view('admin.applications.index', ['applications' => $applications]);
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
        $application->setAttribute('profile_match_percent', $this->resolveMatchPercent($application, $resume));

        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    public function updateStatus(Request $request, HirevoEmployerJobApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['qualified'])],
        ]);

        $application->status = $validated['status'];
        $application->save();

        return back()->with('success', 'Application status updated.');
    }

    private function primaryResumeFor(HirevoEmployerJobApplication $application): ?HirevoResume
    {
        return $application->candidate?->resumes
            ?->sortByDesc(fn ($r) => (int) (($r->is_primary ?? false) ? 1 : 0))
            ?->sortByDesc('created_at')
            ?->first();
    }

    private function resolveMatchPercent(HirevoEmployerJobApplication $application, ?HirevoResume $resume): int
    {
        $stored = $application->match_percentage ?? null;
        if (is_numeric($stored)) {
            return max(0, min(100, (int) round((float) $stored)));
        }

        $resumeSkills = $this->toSkillList($resume?->extracted_skills ?? []);
        if ($resumeSkills === []) {
            return 0;
        }

        $job = $application->job;
        $required = [];
        foreach (['required_skills', 'skills_required', 'must_have_skills', 'key_skills'] as $key) {
            $required = array_merge($required, $this->toSkillList($job?->{$key}));
        }

        if ($required === []) {
            $required = $this->extractWords((string) ($job?->title ?? '').' '.(string) ($job?->description ?? '').' '.(string) ($job?->requirements ?? ''));
        }

        if ($required === []) {
            return 0;
        }

        $resumeSet = array_unique(array_map(fn ($s) => Str::lower(trim($s)), $resumeSkills));
        $requiredSet = array_unique(array_map(fn ($s) => Str::lower(trim($s)), $required));
        $hits = count(array_intersect($requiredSet, $resumeSet));
        $percent = (int) round(($hits / max(1, count($requiredSet))) * 100);

        return max(0, min(100, $percent));
    }

    /**
     * @param  mixed  $raw
     * @return array<int, string>
     */
    private function toSkillList($raw): array
    {
        if (is_array($raw)) {
            $items = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $items = is_array($decoded) ? $decoded : (preg_split('/[,|\/]/', $raw) ?: []);
        } else {
            $items = [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (! is_scalar($item)) {
                return null;
            }

            $skill = trim((string) $item);

            return $skill !== '' ? $skill : null;
        }, $items)));
    }

    /**
     * @return array<int, string>
     */
    private function extractWords(string $text): array
    {
        $parts = preg_split('/[^a-zA-Z0-9\+\#\.]+/', Str::lower($text)) ?: [];

        return array_values(array_filter($parts, fn ($w) => strlen($w) >= 3));
    }
}

