@extends('layouts.app')

@section('title', $job->title)

@section('content')
    @include('partials.portal-ui')

    @php
        $profile = $job->employer?->referrerProfile;
        $companyName = $job->displayCompanyName();
    @endphp

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'jobs'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">{{ $job->title }}</h1>
                <p class="portal-hero-sub">
                    {{ $companyName }}
                    @if($job->job_department)
                        · {{ $job->job_department }}
                    @endif
                    @if($job->location_city && $job->location_city !== '—')
                        · {{ $job->location_city }}
                    @endif
                </p>
                <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                    <span class="portal-badge status-{{ $job->status }}">{{ $job->status }}</span>
                    @if($jobCategoryLabel)
                        <span class="badge rounded-pill bg-light text-dark px-3 py-2">
                            <i class="bi bi-diagram-3 me-1"></i>Matched sector: {{ $jobCategoryLabel }}
                        </span>
                    @else
                        <span class="badge rounded-pill bg-warning-subtle text-dark px-3 py-2">
                            <i class="bi bi-question-circle me-1"></i>Sector unmatched — showing broader list
                        </span>
                    @endif
                </div>
            </div>
            <div class="portal-hero-actions d-flex flex-wrap gap-2">
                <a href="{{ route('admin.jobs.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                    <i class="bi bi-arrow-left me-1"></i>Back to jobs
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success shadow-soft">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning shadow-soft">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger shadow-soft">{{ session('error') }}</div>
        @endif

        @include('partials.portal-mini-stats', ['items' => [
            ['label' => 'Applicants', 'value' => $applicantCount, 'icon' => 'bi-people', 'tone' => 'indigo'],
            ['label' => 'Relevant candidates', 'value' => $relevantCount, 'icon' => 'bi-person-check', 'tone' => 'emerald', 'hint' => 'Not yet applied'],
            ['label' => 'Job status', 'value' => ucfirst($job->status), 'icon' => 'bi-briefcase', 'tone' => 'amber'],
            ['label' => 'Posted', 'value' => $job->created_at?->format('M j'), 'icon' => 'bi-calendar3', 'tone' => 'violet'],
        ]])

        <ul class="nav nav-pills portal-tabs mb-3 gap-2">
            <li class="nav-item">
                <a class="nav-link @if($tab === 'relevant') active @endif"
                   href="{{ route('admin.jobs.show', ['job' => $job->id, 'tab' => 'relevant'] + request()->only(['q', 'show_all'])) }}">
                    <i class="bi bi-person-lines-fill me-1"></i>Relevant candidates
                    <span class="badge bg-light text-dark ms-1">{{ number_format($relevantCount) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if($tab === 'applicants') active @endif"
                   href="{{ route('admin.jobs.show', ['job' => $job->id, 'tab' => 'applicants']) }}">
                    <i class="bi bi-inboxes me-1"></i>Applicants
                    <span class="badge bg-light text-dark ms-1">{{ number_format($applicantCount) }}</span>
                </a>
            </li>
        </ul>

        @if($tab === 'applicants')
            <div class="portal-table-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Contact</th>
                            <th>Match</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($applications as $application)
                            @php
                                $candidate = $application->candidate;
                                $status = $application->status ?? 'applied';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $candidate?->name ?? '—' }}</div>
                                    <div class="small text-muted">{{ $candidate?->candidateProfile?->headline ?? '—' }}</div>
                                </td>
                                <td class="small">
                                    <div>{{ $candidate?->email ?? '—' }}</div>
                                    @if($candidate?->phone)<div class="text-muted">{{ $candidate->phone }}</div>@endif
                                </td>
                                <td><span class="portal-match-badge">{{ (int) ($application->profile_match_percent ?? 0) }}%</span></td>
                                <td>
                                    <form method="POST" action="{{ route($appStatusRoute, $application->id) }}" class="d-flex gap-1 align-items-center">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" style="width:118px;border-radius:8px;">
                                            @foreach(['applied','shortlisted','interviewed','offered','hired','rejected','qualified'] as $st)
                                                <option value="{{ $st }}" @selected($status === $st)>{{ ucfirst($st) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">Save</button>
                                    </form>
                                </td>
                                <td class="text-muted small">{{ $application->created_at?->format('M j, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route($appShowRoute, $application->id) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="portal-empty">
                                        <i class="bi bi-inbox"></i>
                                        No applicants yet. Use the Relevant candidates tab to apply on behalf of candidates.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.crm-pagination-footer', ['paginator' => $applications])
            </div>
        @else
            <div class="portal-filters-card mb-3">
                <form method="GET" action="{{ route('admin.jobs.show', $job->id) }}" class="portal-filters-body" id="relevant-search-form">
                    <input type="hidden" name="tab" value="relevant">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Search candidates</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input class="form-control" name="q" id="relevant-search-input"
                                       placeholder="Name, email, skills…" value="{{ request('q') }}"
                                       autocomplete="off">
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="show_all" value="1" id="show_all"
                                       @checked($showAll)>
                                <label class="form-check-label" for="show_all">
                                    Show all candidates (ignore sector match)
                                </label>
                            </div>
                        </div>
                        <div class="col-12 col-lg-auto ms-lg-auto d-flex gap-2">
                            @if(request()->filled('q') || $showAll)
                                <a href="{{ route('admin.jobs.show', ['job' => $job->id, 'tab' => 'relevant']) }}"
                                   class="btn btn-outline-secondary px-3" style="border-radius:10px;">Reset</a>
                            @endif
                            <button class="btn btn-primary px-4" type="submit" style="border-radius:10px;">
                                <i class="bi bi-check2 me-1"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if($job->status !== 'active')
                <div class="alert alert-warning shadow-soft">
                    This job is <strong>{{ $job->status }}</strong>. Applications can only be submitted for active jobs.
                </div>
            @endif

            @if($canApply && $job->status === 'active')
                <form method="POST" action="{{ route('admin.jobs.apply', $job->id) }}" id="bulk-apply-form" class="d-none">
                    @csrf
                </form>
            @endif

            <div class="portal-table-card">
                @if($canApply && $job->status === 'active')
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                        <div class="small text-muted" id="selection-summary">Select candidates to apply</div>
                        <button type="submit" form="bulk-apply-form" class="btn btn-primary btn-sm" id="bulk-apply-btn" disabled style="border-radius:8px;">
                            <i class="bi bi-send-check me-1"></i>Apply selected
                        </button>
                    </div>
                @endif
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                            <tr>
                                @if($canApply && $job->status === 'active')
                                    <th style="width:40px;">
                                        <input type="checkbox" class="form-check-input" id="select-all-candidates" aria-label="Select all">
                                    </th>
                                @endif
                                <th>Candidate</th>
                                <th>Contact</th>
                                <th>Experience</th>
                                <th>Match</th>
                                <th>Resume</th>
                                @if($canApply && $job->status === 'active')
                                    <th class="text-end">Action</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($relevantCandidates as $candidate)
                                @php
                                    $cProfile = $candidate->candidateProfile;
                                    $hasResume = (bool) ($candidate->has_resume ?? false);
                                    $canSelect = $canApply && $job->status === 'active' && $hasResume;
                                @endphp
                                <tr>
                                    @if($canApply && $job->status === 'active')
                                        <td>
                                            @if($canSelect)
                                                <input type="checkbox" class="form-check-input candidate-checkbox"
                                                       form="bulk-apply-form"
                                                       name="candidate_ids[]" value="{{ $candidate->id }}">
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <div class="fw-semibold">
                                            <a href="{{ route('admin.candidates.show', $candidate->id) }}" class="text-decoration-none">
                                                {{ $candidate->name }}
                                            </a>
                                        </div>
                                        <div class="small text-muted">{{ $cProfile?->headline ?? '—' }}</div>
                                    </td>
                                    <td class="small">
                                        <div>{{ $candidate->email }}</div>
                                        @if($candidate->phone)<div class="text-muted">{{ $candidate->phone }}</div>@endif
                                    </td>
                                    <td class="small text-muted">
                                        @if($cProfile?->experience_years !== null)
                                            {{ $cProfile->experience_years }} yrs
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><span class="portal-match-badge">{{ (int) ($candidate->profile_match_percent ?? 0) }}%</span></td>
                                    <td>
                                        @if($hasResume)
                                            <span class="badge text-bg-success-subtle text-success">Yes</span>
                                        @else
                                            <span class="badge text-bg-danger-subtle text-danger" title="Resume required to apply">No</span>
                                        @endif
                                    </td>
                                    @if($canApply && $job->status === 'active')
                                        <td class="text-end">
                                            @if($canSelect)
                                                <form method="POST" action="{{ route('admin.jobs.apply', $job->id) }}" class="d-inline"
                                                      onsubmit="return confirm('Apply {{ $candidate->name }} to this job?');">
                                                    @csrf
                                                    <input type="hidden" name="candidate_ids[]" value="{{ $candidate->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                                                        Apply
                                                    </button>
                                                </form>
                                            @else
                                                <span class="small text-muted">No resume</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($canApply && $job->status === 'active') ? 7 : 5 }}">
                                        <div class="portal-empty">
                                            <i class="bi bi-person-x"></i>
                                            No relevant candidates found.
                                            @if(!$showAll)
                                                Try enabling “Show all candidates” or adjust your search.
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('partials.crm-pagination-footer', ['paginator' => $relevantCandidates])
                </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const selectAll = document.getElementById('select-all-candidates');
        const checkboxes = document.querySelectorAll('.candidate-checkbox');
        const bulkBtn = document.getElementById('bulk-apply-btn');
        const summary = document.getElementById('selection-summary');
        const form = document.getElementById('bulk-apply-form');
        const searchInput = document.getElementById('relevant-search-input');
        const searchForm = document.getElementById('relevant-search-form');
        let searchTimer = null;

        function updateSelection() {
            const checked = document.querySelectorAll('.candidate-checkbox:checked');
            const count = checked.length;
            if (bulkBtn) bulkBtn.disabled = count === 0;
            if (summary) {
                summary.textContent = count === 0
                    ? 'Select candidates to apply'
                    : count + ' candidate' + (count === 1 ? '' : 's') + ' selected';
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
                updateSelection();
            });
        }

        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', updateSelection);
        });

        if (form) {
            form.addEventListener('submit', function (e) {
                const count = document.querySelectorAll('.candidate-checkbox:checked').length;
                if (count === 0) {
                    e.preventDefault();
                    return;
                }
                if (!confirm('Apply ' + count + ' selected candidate(s) to this job?')) {
                    e.preventDefault();
                }
            });
        }

        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    if (searchInput.value.trim().length >= 2 || searchInput.value.trim() === '') {
                        searchForm.requestSubmit();
                    }
                }, 450);
            });
        }
    })();
</script>
@endpush
