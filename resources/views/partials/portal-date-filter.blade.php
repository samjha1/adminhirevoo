@php
    $periodParam = $periodParam ?? 'period';
    $dateFilter = $dateFilter ?? \App\Support\PortalDateFilter::fromRequest(request(), $periodParam);
    $action = $action ?? request()->url();
    $hasActive = $dateFilter->isActive();
@endphp
<div class="portal-filters-card">
    <div class="portal-filters-head">
        <h2><i class="bi bi-calendar3 text-primary"></i> Date filter</h2>
        @if($hasActive)
            <a href="{{ $action }}" class="btn btn-sm btn-link text-decoration-none">Clear dates</a>
        @endif
    </div>
    <form method="GET" action="{{ $action }}" class="portal-filters-body">
        @foreach(request()->except([$periodParam, 'period', 'app_period', 'date_from', 'date_to', 'page', 'jobs_page', 'apps_page', 'leads_page']) as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label">Time period</label>
                <select name="{{ $periodParam }}" class="form-select portal-period-select">
                    <option value="">All time</option>
                    <option value="today" @selected($dateFilter->key === 'today')>Today</option>
                    <option value="last_7_days" @selected($dateFilter->key === 'last_7_days')>Last 7 days</option>
                    <option value="last_30_days" @selected($dateFilter->key === 'last_30_days')>Last 30 days</option>
                    <option value="this_week" @selected($dateFilter->key === 'this_week')>This week</option>
                    <option value="this_month" @selected($dateFilter->key === 'this_month')>This month</option>
                    <option value="custom" @selected($dateFilter->key === 'custom')>Custom range</option>
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
                <button type="submit" class="btn btn-primary px-4" style="border-radius:10px;">
                    <i class="bi bi-check2 me-1"></i>Apply
                </button>
            </div>
        </div>
        @if($hasActive)
            <div class="mt-2 small text-muted">
                <i class="bi bi-funnel me-1"></i>Showing: <strong>{{ $dateFilter->label() }}</strong>
            </div>
        @endif
    </form>
</div>
@once
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
@endonce
