@extends('layouts.app')

@section('title', 'Jobs')

@section('content')
    @include('partials.portal-ui')

    @php
        $stats = $stats ?? [];
        $dateFilter = $dateFilter ?? \App\Support\PortalDateFilter::fromRequest(request());
        $hasFilters = request()->filled('q') || request()->filled('status') || $dateFilter->isActive();
    @endphp

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'jobs'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">Job listings</h1>
                <p class="portal-hero-sub">Review, search, and moderate all employer job posts on the platform.</p>
            </div>
            <div class="portal-hero-actions">
                <span class="badge rounded-pill bg-light text-dark px-3 py-2 fw-semibold">
                    <i class="bi bi-briefcase me-1"></i>{{ number_format($jobs->total()) }} total
                </span>
            </div>
        </div>

        @include('partials.portal-mini-stats', ['items' => [
            ['label' => 'All jobs', 'value' => $stats['total'] ?? 0, 'icon' => 'bi-collection', 'tone' => 'indigo'],
            ['label' => 'Active', 'value' => $stats['active'] ?? 0, 'icon' => 'bi-check-circle', 'tone' => 'emerald', 'hint' => 'Live on portal'],
            ['label' => 'Expired / closed', 'value' => $stats['expired'] ?? 0, 'icon' => 'bi-x-circle', 'tone' => 'rose'],
            ['label' => 'Posted today', 'value' => $stats['today'] ?? 0, 'icon' => 'bi-lightning', 'tone' => 'amber'],
        ]])

        <div class="portal-filters-card">
            <div class="portal-filters-head">
                <h2><i class="bi bi-funnel text-primary"></i> Search &amp; filters</h2>
                @if($hasFilters)
                    <a href="{{ route('admin.jobs.index') }}" class="btn btn-sm btn-link text-decoration-none">Reset all</a>
                @endif
            </div>
            <form method="GET" action="{{ route('admin.jobs.index') }}" class="portal-filters-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input class="form-control" name="q" placeholder="Job title, company, or category…" value="{{ request('q') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status')==='active')>Active</option>
                            <option value="expired" @selected(request('status')==='expired')>Expired / closed</option>
                            <option value="draft" @selected(request('status')==='draft')>Draft</option>
                        </select>
                    </div>
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
                        <th>Job</th>
                        <th>Company</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Apps</th>
                        <th>Posted</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $job->title }}</div>
                                @if($job->job_type)
                                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $job->job_type)) }}</div>
                                @endif
                            </td>
                            <td>{{ $job->employer?->referrerProfile?->company_name ?? $job->company_name ?? '—' }}</td>
                            <td><span class="text-muted">{{ $job->job_department ?? '—' }}</span></td>
                            <td><span class="text-muted">{{ $job->location_city }}</span></td>
                            <td><span class="portal-badge status-{{ $job->status }}">{{ $job->status }}</span></td>
                            <td><strong>{{ $job->applications_count }}</strong></td>
                            <td class="text-muted small">{{ $job->created_at?->format('M j, Y') }}</td>
                            <td class="text-end">
                                @if(auth('admin')->user()->canPermission('portal.jobs.edit') || auth('admin')->user()->canPermission('platform.jobs'))
                                    <form method="POST" action="{{ route('admin.jobs.status', $job->id) }}" class="d-inline-flex gap-1 align-items-center">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" style="width:108px;border-radius:8px;">
                                            <option value="draft" @selected($job->status==='draft')>Draft</option>
                                            <option value="active" @selected($job->status==='active')>Active</option>
                                            <option value="closed" @selected($job->status==='closed')>Closed</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" style="border-radius:8px;">Save</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="portal-empty">
                                    <i class="bi bi-briefcase"></i>
                                    No jobs match your filters.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.crm-pagination-footer', ['paginator' => $jobs])
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
