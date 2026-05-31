@php
    $summary = $summary ?? [];
    $showPeriodLabels = $showPeriodLabels ?? false;
    $newLabel = $showPeriodLabels ? 'New in period' : 'Total leads';
@endphp
<div class="row g-3 g-lg-4 mb-0">
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-soft h-100 crm-stat-card">
            <div class="card-body">
                <div class="crm-stat-label">{{ $newLabel }}</div>
                <div class="crm-stat-value">{{ number_format($summary['newLeads'] ?? $summary['totalLeads'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    @if($showPeriodLabels && isset($summary['activeInPeriod']))
        <div class="col-6 col-md-4 col-xl">
            <div class="card shadow-soft h-100 crm-stat-card">
                <div class="card-body">
                    <div class="crm-stat-label">Active in period</div>
                    <div class="crm-stat-value">{{ number_format($summary['activeInPeriod']) }}</div>
                </div>
            </div>
        </div>
    @endif
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-soft h-100 crm-stat-card">
            <div class="card-body">
                <div class="crm-stat-label">Calls</div>
                <div class="crm-stat-value">{{ number_format($summary['calls'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-soft h-100 crm-stat-card">
            <div class="card-body">
                <div class="crm-stat-label">Meetings / follow-ups</div>
                <div class="crm-stat-value">{{ number_format($summary['meetings'] ?? 0) }}</div>
                @if($showPeriodLabels)
                    <div class="small text-muted">Talent = scheduled follow-ups</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-soft h-100 crm-stat-card">
            <div class="card-body">
                <div class="crm-stat-label">Closed</div>
                <div class="crm-stat-value">{{ number_format($summary['closed'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-soft h-100 crm-stat-card accent">
            <div class="card-body">
                <div class="crm-stat-label">Revenue (period)</div>
                <div class="crm-stat-value">₹{{ number_format($summary['revenue'] ?? 0, 0) }}</div>
                @if(isset($summary['revenueGrowth']))
                    <div class="small {{ ($summary['revenueGrowth'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ ($summary['revenueGrowth'] ?? 0) >= 0 ? '+' : '' }}{{ $summary['revenueGrowth'] }}% vs prev period
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-soft h-100 crm-stat-card">
            <div class="card-body">
                <div class="crm-stat-label">Today's revenue</div>
                <div class="crm-stat-value">₹{{ number_format($summary['revenueToday'] ?? 0, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-soft h-100 crm-stat-card">
            <div class="card-body">
                <div class="crm-stat-label">Conversion</div>
                <div class="crm-stat-value">{{ $summary['conversionRate'] ?? 0 }}%</div>
            </div>
        </div>
    </div>
</div>
