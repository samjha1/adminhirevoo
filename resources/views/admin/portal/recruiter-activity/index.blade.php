@extends('layouts.app')

@section('title', 'Recruiter activity')

@section('content')
    @include('partials.portal-ui')

    @php
        $dateFilter = $dateFilter ?? \App\Support\PortalDateFilter::fromRequest(request());
        $hasFilters = request()->filled('recruiter_id') || request()->filled('company_id')
            || request()->filled('job_id') || $dateFilter->isActive();
    @endphp

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'recruiter-activity'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">Recruiter activity</h1>
                <p class="portal-hero-sub">Applications submitted on behalf of candidates by recruiter staff.</p>
            </div>
            <span class="badge rounded-pill bg-light text-dark px-3 py-2 fw-semibold">
                <i class="bi bi-person-check me-1"></i>{{ number_format($applications->total()) }} applies
            </span>
        </div>

        <div class="portal-filters-card">
            <div class="portal-filters-head">
                <h2><i class="bi bi-funnel text-primary"></i> Filters</h2>
                @if($hasFilters)
                    <a href="{{ route('admin.portal.recruiter-activity.index') }}" class="btn btn-sm btn-link text-decoration-none">Reset all</a>
                @endif
            </div>
            <form method="GET" action="{{ route('admin.portal.recruiter-activity.index') }}" class="portal-filters-body">
                <div class="row g-3 align-items-end">
                    @if($filterRecruiters->isNotEmpty())
                        <div class="col-6 col-md-4 col-lg-3">
                            <label class="form-label">Recruiter</label>
                            <select name="recruiter_id" class="form-select">
                                <option value="">All recruiters</option>
                                @foreach($filterRecruiters as $r)
                                    <option value="{{ $r->id }}" @selected((int) request('recruiter_id') === $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($filterCompanies->isNotEmpty())
                        <div class="col-6 col-md-4 col-lg-3">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select">
                                <option value="">All companies</option>
                                @foreach($filterCompanies as $co)
                                    <option value="{{ $co->id }}" @selected((int) request('company_id') === $co->id)>
                                        {{ \Illuminate\Support\Str::limit($co->referrerProfile?->company_name ?? $co->name, 32) }}
                                    </option>
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
                    <div class="col-auto ms-lg-auto">
                        <button class="btn btn-primary px-4" type="submit" style="border-radius:10px;">
                            <i class="bi bi-check2 me-1"></i>Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @include('admin.portal.partials.activity-table', [
            'applications' => $applications,
            'appShowRoute' => $appShowRoute,
            'showRecruiterColumn' => true,
        ])
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
