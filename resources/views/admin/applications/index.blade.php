@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    @include('partials.portal-ui')

    @php
        $stats = $stats ?? [];
        $dateFilter = $dateFilter ?? \App\Support\PortalDateFilter::fromRequest(request());
        $appRoute = Route::has('admin.portal.applications.index') && !auth('admin')->user()->canPermission('leads.view')
            ? 'admin.portal.applications.index'
            : 'admin.applications.index';
        $showRoute = $appRoute === 'admin.portal.applications.index' ? 'admin.portal.applications.show' : 'admin.applications.show';
        $statusRoute = $appRoute === 'admin.portal.applications.index' ? 'admin.portal.applications.status' : 'admin.applications.status';
        $hasFilters = request()->filled('q') || request()->filled('status') || request()->filled('company_id')
            || request()->filled('job_id') || $dateFilter->isActive();
    @endphp

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'applications'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">Applications</h1>
                <p class="portal-hero-sub">Track who applied, for which role, and update hiring status in one place.</p>
            </div>
            <div class="portal-hero-actions">
                <span class="badge rounded-pill bg-light text-dark px-3 py-2 fw-semibold">
                    <i class="bi bi-send-check me-1"></i>{{ number_format($applications->total()) }} applications
                </span>
            </div>
        </div>

        @include('partials.portal-mini-stats', ['items' => [
            ['label' => 'Total', 'value' => $stats['total'] ?? 0, 'icon' => 'bi-inboxes', 'tone' => 'indigo'],
            ['label' => 'Today', 'value' => $stats['today'] ?? 0, 'icon' => 'bi-sun', 'tone' => 'amber'],
            ['label' => 'This week', 'value' => $stats['weekly'] ?? 0, 'icon' => 'bi-calendar-week', 'tone' => 'emerald'],
            ['label' => 'This month', 'value' => $stats['monthly'] ?? 0, 'icon' => 'bi-calendar-month', 'tone' => 'violet'],
        ]])

        <div class="portal-filters-card">
            <div class="portal-filters-head">
                <h2><i class="bi bi-funnel text-primary"></i> Search &amp; filters</h2>
                @if($hasFilters)
                    <a href="{{ route($appRoute) }}" class="btn btn-sm btn-link text-decoration-none">Reset all</a>
                @endif
            </div>
            <form method="GET" action="{{ route($appRoute) }}" class="portal-filters-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="search" class="form-control" name="q" value="{{ request('q') }}"
                                   placeholder="Candidate, company, or job title…">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            @foreach(['applied','shortlisted','interviewed','offered','hired','rejected','qualified'] as $st)
                                <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(isset($filterCompanies))
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select">
                                <option value="">All companies</option>
                                @foreach($filterCompanies as $co)
                                    <option value="{{ $co->id }}" @selected((int)request('company_id') === $co->id)>{{ \Illuminate\Support\Str::limit($co->name, 28) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(isset($filterJobs))
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label">Job</label>
                            <select name="job_id" class="form-select">
                                <option value="">All jobs</option>
                                @foreach($filterJobs as $jo)
                                    <option value="{{ $jo->id }}" @selected((int)request('job_id') === $jo->id)>{{ \Illuminate\Support\Str::limit($jo->title, 28) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Time period</label>
                        <select name="period" class="form-select portal-period-select">
                            <option value="">All time</option>
                            <option value="today" @selected($dateFilter->key === 'today')>Today</option>
                            <option value="last_7_days" @selected($dateFilter->key === 'last_7_days')>Last 7 days</option>
                            <option value="last_30_days" @selected($dateFilter->key === 'last_30_days')>Last 30 days</option>
                            <option value="this_week" @selected($dateFilter->key === 'this_week')>This week</option>
                            <option value="this_month" @selected($dateFilter->key === 'this_month')>This month</option>
                            <option value="custom" @selected($dateFilter->key === 'custom')>Custom</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 portal-custom-dates @if($dateFilter->key !== 'custom') d-none @endif">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 portal-custom-dates @if($dateFilter->key !== 'custom') d-none @endif">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 col-lg-auto ms-lg-auto">
                        <button class="btn btn-primary px-4" type="submit" style="border-radius:10px;">
                            <i class="bi bi-check2 me-1"></i>Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="portal-table-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Company</th>
                        <th>Job role</th>
                        <th>Contact</th>
                        <th>Match</th>
                        <th>Status</th>
                        <th>Applied</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($applications as $application)
                        @php
                            $candidate = $application->candidate;
                            $job = $application->job;
                            $employer = $job?->employer;
                            $companyName = $employer?->referrerProfile?->company_name ?: $employer?->name ?: '—';
                            $status = $application->status ?? 'applied';
                        @endphp
                        <tr>
                            <td>
                                @if($candidate?->name)
                                    <a href="{{ route($showRoute, $application->id) }}" class="fw-semibold text-decoration-none">
                                        {{ $candidate->name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $companyName }}</td>
                            <td>
                                <div class="fw-medium">{{ $job?->title ?? '—' }}</div>
                                <div class="small text-muted text-truncate" style="max-width:200px">
                                    {{ \Illuminate\Support\Str::limit($application->ai_resume_summary ?: 'No AI summary', 60) }}
                                </div>
                            </td>
                            <td class="small">
                                <div>{{ $candidate?->email ?? '—' }}</div>
                                @if($candidate?->phone)<div class="text-muted">{{ $candidate->phone }}</div>@endif
                            </td>
                            <td><span class="portal-match-badge">{{ (int) ($application->profile_match_percent ?? 0) }}%</span></td>
                            <td>
                                <form method="POST" action="{{ route($statusRoute, $application->id) }}" class="d-flex gap-1 align-items-center">
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="portal-empty">
                                    <i class="bi bi-inbox"></i>
                                    No applications match your filters.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.crm-pagination-footer', ['paginator' => $applications])
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.portal-period-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const form = sel.closest('form');
            if (!form) return;
            form.querySelectorAll('.portal-custom-dates').forEach(function (el) {
                el.classList.toggle('d-none', sel.value !== 'custom');
            });
        });
    });
</script>
@endpush
