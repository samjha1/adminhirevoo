@extends('layouts.app')

@section('title', 'CRM dashboard')

@section('content')
    @php
        $period = $period ?? \App\Support\DashboardPeriod::forPreset('this_month');
        $summary = $summary ?? [];
        $funnel = $funnel ?? [];
        $trends = $trends ?? [];
        $teamTables = $teamTables ?? [];
        $platform = $platform ?? [];
        $activityFeeds = $activityFeeds ?? ['talent' => [], 'company' => []];
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">CRM dashboard</h1>
            <div class="page-subtitle">
                {{ $period->label() }} · Talent + Company pipelines · {{ auth('admin')->user()->role->label() }}
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.leads.index') }}" class="btn btn-sm btn-outline-primary">Talent leads</a>
            <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-sm btn-outline-success">Companies</a>
            @if(auth('admin')->user()->canPermission('analytics.export'))
                <a href="{{ route('admin.dashboard.export', ['format' => 'csv'] + $period->queryParams()) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            @endif
        </div>
    </div>

    @include('partials.dashboard-period-filter', ['period' => $period])

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-soft h-100 border-primary border-opacity-25">
                <div class="card-header bg-white fw-semibold text-primary">
                    <i class="bi bi-person-workspace me-1"></i>Talent (candidates) · {{ $period->label() }}
                </div>
                <div class="card-body pt-0">
                    @include('partials.dashboard-summary-cards', ['summary' => $summary['talent'] ?? [], 'showPeriodLabels' => true])
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-soft h-100 border-success border-opacity-25">
                <div class="card-header bg-white fw-semibold text-success">
                    <i class="bi bi-buildings me-1"></i>Company B2B · {{ $period->label() }}
                </div>
                <div class="card-body pt-0">
                    @include('partials.dashboard-summary-cards', ['summary' => $summary['company'] ?? [], 'showPeriodLabels' => true])
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            @include('partials.dashboard-activity-feed', [
                'title' => 'What happened · Talent · '.$period->label(),
                'items' => $activityFeeds['talent'] ?? [],
                'pipelineUrl' => route('admin.leads.index'),
            ])
        </div>
        <div class="col-lg-6">
            @include('partials.dashboard-activity-feed', [
                'title' => 'What happened · Company · '.$period->label(),
                'items' => $activityFeeds['company'] ?? [],
                'pipelineUrl' => route('admin.employers.pipeline.index'),
            ])
        </div>
    </div>

    <div class="card shadow-soft mb-4">
        <div class="card-header bg-white fw-semibold">Combined overview · {{ $period->label() }}</div>
        <div class="card-body pt-0">
            @include('partials.dashboard-summary-cards', ['summary' => $summary['combined'] ?? [], 'showPeriodLabels' => true])
        </div>
    </div>

    <ul class="nav nav-pills mb-3 gap-2" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button">Overview</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-talent" type="button">Talent detail</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-company" type="button">Company detail</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-teams" type="button">Team performance</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-overview">
            @include('partials.dashboard-charts', compact('trends'))
        </div>

        <div class="tab-pane fade" id="tab-talent">
            <h2 class="h6 fw-bold text-primary mb-2">Active in period (by status)</h2>
            <div class="card shadow-soft mb-3">
                <div class="card-body d-flex flex-wrap gap-2">
                    @forelse($funnel['talent'] ?? [] as $stage => $count)
                        <span class="badge bg-light text-dark border">{{ $stage }}: <strong>{{ $count }}</strong></span>
                    @empty
                        <span class="text-muted small">No talent activity in {{ $period->label() }}</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-company">
            <h2 class="h6 fw-bold text-success mb-2">Active in period (by stage)</h2>
            <div class="card shadow-soft mb-3">
                <div class="card-body d-flex flex-wrap gap-2">
                    @forelse($funnel['company'] ?? [] as $key => $item)
                        @php $label = is_array($item) ? ($item['label'] ?? $key) : $key; $count = is_array($item) ? ($item['count'] ?? 0) : $item; @endphp
                        <span class="badge bg-light text-dark border">{{ $label }}: <strong>{{ $count }}</strong></span>
                    @empty
                        <span class="text-muted small">No company activity in {{ $period->label() }}</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-teams">
            @include('partials.dashboard-performance-tables', compact('teamTables'))
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="card shadow-soft">
                <div class="card-body">
                    <div class="crm-stat-label">All-time talent leads</div>
                    <div class="crm-stat-value">{{ number_format($platform['totalTalentLeads'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-soft">
                <div class="card-body">
                    <div class="crm-stat-label">All-time companies</div>
                    <div class="crm-stat-value">{{ number_format($platform['totalCompanies'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-soft">
                <div class="card-header bg-white fw-semibold small">System audit (latest)</div>
                <ul class="list-group list-group-flush small">
                    @forelse($recentActivities ?? [] as $log)
                        <li class="list-group-item px-0">
                            <span class="text-muted">{{ $log->created_at?->diffForHumans() }}</span>
                            {{ $log->admin?->name ?? 'System' }} · <code>{{ $log->action }}</code>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No audit events yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
