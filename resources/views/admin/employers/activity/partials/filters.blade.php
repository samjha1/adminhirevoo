@php
    $dateFilter = $dateFilter ?? \App\Support\PortalDateFilter::fromRequest(request());
    $teamView = $teamView ?? false;
    $formAction = $teamView
        ? route('admin.employers.activity.team')
        : route('admin.employers.activity.my');
    $resetUrl = $formAction;
    $hasFilters = request()->filled('staff_id')
        || request()->filled('prospect_id')
        || request()->filled('type')
        || ($dateFilter->isActive() && $dateFilter->key !== 'today');
@endphp

<div class="company-filters-card">
    <div class="company-filters-head">
        <h2><i class="bi bi-funnel me-2 text-success"></i>Filters</h2>
        @if($hasFilters)
            <a href="{{ $resetUrl }}" class="btn btn-sm btn-link text-decoration-none">Reset</a>
        @endif
    </div>
    <form method="GET" action="{{ $formAction }}" class="company-filters-body">
        <div class="row g-3 align-items-end">
            @if($teamView && ($filterStaff ?? collect())->isNotEmpty())
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label">Team member</label>
                    <select name="staff_id" class="form-select">
                        <option value="">All team members</option>
                        @foreach($filterStaff as $member)
                            <option value="{{ $member->id }}" @selected((int) request('staff_id') === $member->id)>
                                {{ $member->name }}
                                @if($member->role)
                                    ({{ $member->role->label() }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(($filterCompanies ?? collect())->isNotEmpty())
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label">Company</label>
                    <select name="prospect_id" class="form-select">
                        <option value="">All companies</option>
                        @foreach($filterCompanies as $company)
                            <option value="{{ $company->id }}" @selected((int) request('prospect_id') === $company->id)>
                                {{ \Illuminate\Support\Str::limit($company->company_name, 36) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label">Activity type</label>
                <select name="type" class="form-select">
                    <option value="">All types</option>
                    @foreach($typeLabels ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label">Time period</label>
                <select name="period" class="form-select company-activity-period-select">
                    <option value="today" @selected($dateFilter->key === 'today')>Today</option>
                    <option value="last_7_days" @selected($dateFilter->key === 'last_7_days')>Last 7 days</option>
                    <option value="last_30_days" @selected($dateFilter->key === 'last_30_days')>Last 30 days</option>
                    <option value="this_week" @selected($dateFilter->key === 'this_week')>This week</option>
                    <option value="this_month" @selected($dateFilter->key === 'this_month')>This month</option>
                    <option value="custom" @selected($dateFilter->key === 'custom')>Custom</option>
                    <option value="" @selected($dateFilter->key === '')>All time</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2 company-activity-custom-dates @if($dateFilter->key !== 'custom') d-none @endif">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-4 col-lg-2 company-activity-custom-dates @if($dateFilter->key !== 'custom') d-none @endif">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-auto ms-lg-auto">
                <button class="btn btn-success px-4" type="submit">
                    <i class="bi bi-check2 me-1"></i>Apply
                </button>
            </div>
        </div>
    </form>
</div>
