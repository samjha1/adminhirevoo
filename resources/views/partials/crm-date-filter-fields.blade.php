@php
    $dateFilter = $dateFilter ?? \App\Support\PortalDateFilter::fromRequest(request());
    $periodParam = $periodParam ?? 'period';
    $colClass = $colClass ?? 'col-6 col-md-4 col-lg-2';
@endphp
<div class="{{ $colClass }}">
    <label class="form-label">Date range</label>
    <select class="form-select crm-period-select" name="{{ $periodParam }}">
        <option value="">All time</option>
        <option value="today" @selected($dateFilter->key === 'today')>Today</option>
        <option value="last_7_days" @selected($dateFilter->key === 'last_7_days')>Last 7 days</option>
        <option value="last_30_days" @selected($dateFilter->key === 'last_30_days')>Last 30 days</option>
        <option value="this_week" @selected($dateFilter->key === 'this_week')>This week</option>
        <option value="this_month" @selected($dateFilter->key === 'this_month')>This month</option>
        <option value="custom" @selected($dateFilter->key === 'custom')>Custom range</option>
    </select>
</div>
<div class="{{ $colClass }} crm-custom-dates @if($dateFilter->key !== 'custom') d-none @endif">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
</div>
<div class="{{ $colClass }} crm-custom-dates @if($dateFilter->key !== 'custom') d-none @endif">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
</div>
@once
    @push('scripts')
    <script>
        document.querySelectorAll('.crm-period-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                const form = sel.closest('form');
                if (!form) return;
                form.querySelectorAll('.crm-custom-dates').forEach(function (el) {
                    el.classList.toggle('d-none', sel.value !== 'custom');
                });
            });
        });
    </script>
    @endpush
@endonce
