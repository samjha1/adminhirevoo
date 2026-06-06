@extends('layouts.app')

@section('title', 'Job Portal Dashboard')

@section('content')
    @include('partials.portal-ui')

    @php
        $stats = $stats ?? [];
        $charts = $charts ?? [];
        $recentActivities = $recentActivities ?? collect();
        $recentCompanies = $recentCompanies ?? collect();
        $recentJobs = $recentJobs ?? collect();
        $activityIcons = [
            'company_registered' => 'bi-buildings',
            'candidate_registered' => 'bi-person-plus',
            'job_posted' => 'bi-briefcase',
            'application_submitted' => 'bi-send-check',
        ];
        $todayRows = [
            ['icon' => 'bi-buildings', 'label' => 'Companies registered', 'value' => $stats['companiesToday'] ?? 0],
            ['icon' => 'bi-briefcase', 'label' => 'Jobs posted', 'value' => $stats['jobsToday'] ?? 0],
            ['icon' => 'bi-people', 'label' => 'Candidates registered', 'value' => $stats['candidatesToday'] ?? 0],
            ['icon' => 'bi-send-check', 'label' => 'Applications submitted', 'value' => $stats['applicationsToday'] ?? 0],
        ];
    @endphp

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'dashboard'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">Platform overview</h1>
                <p class="portal-hero-sub">Live snapshot of jobs, applications, and platform growth across the hirevo marketplace.</p>
            </div>
            <div class="portal-hero-actions d-flex flex-wrap gap-2">
                @if(auth('admin')->user()->canPermission('portal.jobs.view') || auth('admin')->user()->canPermission('platform.jobs'))
                    <a href="{{ route('admin.jobs.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-briefcase me-1"></i>Manage jobs
                    </a>
                @endif
                @if(auth('admin')->user()->canPermission('portal.reports.view'))
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-bar-chart-line me-1"></i>Reports
                    </a>
                @endif
                @if(auth('admin')->user()->canPermission('portal.reports.export'))
                    <a href="{{ route('admin.reports.export', ['format' => 'csv']) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-download me-1"></i>Export
                    </a>
                @endif
            </div>
        </div>

        <div class="portal-section-label"><i class="bi bi-graph-up-arrow"></i> All-time totals &amp; today</div>
        @include('partials.portal-stat-cards', ['stats' => $stats])

        <div class="row g-3 mt-1">
            <div class="col-lg-8">
                <div class="portal-panel">
                    <div class="portal-panel-head">
                        <h2 class="portal-panel-title"><i class="bi bi-activity text-primary"></i> Growth trends</h2>
                        <span class="small text-muted">Last 30 days</span>
                    </div>
                    <div class="card-body p-3">
                        <canvas id="portalTrendChart" height="110"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="portal-panel">
                    <div class="portal-panel-head">
                        <h2 class="portal-panel-title"><i class="bi bi-sun text-warning"></i> Today at a glance</h2>
                    </div>
                    <div class="card-body px-3 pb-2 pt-1">
                        @foreach($todayRows as $row)
                            <div class="portal-today-row">
                                <span class="portal-today-label">
                                    <i class="bi {{ $row['icon'] }}"></i>{{ $row['label'] }}
                                </span>
                                <span class="portal-today-value">{{ number_format($row['value']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-section-label mt-4"><i class="bi bi-clock-history"></i> What's happening</div>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="portal-panel">
                    <div class="portal-panel-head">
                        <h2 class="portal-panel-title">Recent activity</h2>
                        @if(auth('admin')->user()->canPermission('portal.reports.view'))
                            <a href="{{ route('admin.reports.index') }}" class="portal-panel-link">Reports</a>
                        @endif
                    </div>
                    @forelse($recentActivities as $item)
                        <div class="portal-activity-item">
                            <div class="portal-activity-dot type-{{ $item['type'] ?? 'default' }}">
                                <i class="bi {{ $activityIcons[$item['type'] ?? ''] ?? 'bi-circle' }}"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="portal-activity-kicker">{{ $item['label'] }}</div>
                                <div class="portal-activity-title text-truncate">{{ $item['title'] }}</div>
                                <div class="portal-activity-sub text-truncate">{{ $item['subtitle'] }}</div>
                                <div class="portal-activity-time">{{ optional($item['at'])->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="portal-empty">
                            <i class="bi bi-inbox"></i>
                            No recent activity yet.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="portal-panel">
                    <div class="portal-panel-head">
                        <h2 class="portal-panel-title">New companies</h2>
                        @if(auth('admin')->user()->canPermission('leads.view'))
                            <a href="{{ route('admin.employers.pipeline.index') }}" class="portal-panel-link">Pipeline</a>
                        @endif
                    </div>
                    @forelse($recentCompanies as $company)
                        <a href="{{ route('admin.employers.show', $company->id) }}" class="portal-activity-item text-decoration-none text-reset">
                            <div class="portal-activity-dot type-company_registered">
                                <i class="bi bi-buildings"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="portal-activity-title text-truncate">{{ $company->referrerProfile?->company_name ?? $company->name }}</div>
                                <div class="portal-activity-sub text-truncate">{{ $company->email }}</div>
                                <div class="portal-activity-time">{{ $company->employer_jobs_count }} jobs · {{ $company->created_at?->diffForHumans() }}</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    @empty
                        <div class="portal-empty">
                            <i class="bi bi-buildings"></i>
                            No companies registered yet.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="portal-panel">
                    <div class="portal-panel-head">
                        <h2 class="portal-panel-title">Latest job posts</h2>
                        @if(auth('admin')->user()->canPermission('portal.jobs.view'))
                            <a href="{{ route('admin.jobs.index') }}" class="portal-panel-link">All jobs</a>
                        @endif
                    </div>
                    @forelse($recentJobs as $job)
                        <div class="portal-activity-item">
                            <div class="portal-activity-dot type-job_posted">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="portal-activity-title text-truncate">{{ $job->title }}</div>
                                <div class="portal-activity-sub text-truncate">
                                    {{ $job->employer?->referrerProfile?->company_name ?? $job->company_name ?? '—' }}
                                </div>
                                <div class="portal-activity-time">
                                    {{ $job->applications_count }} applications · {{ $job->created_at?->diffForHumans() }}
                                </div>
                            </div>
                            <span class="portal-badge status-{{ $job->status ?? 'draft' }}">{{ $job->status ?? 'draft' }}</span>
                        </div>
                    @empty
                        <div class="portal-empty">
                            <i class="bi bi-briefcase"></i>
                            No jobs posted yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const ctx = document.getElementById('portalTrendChart');
    if (!ctx) return;
    const data = @json($charts);
    const gridColor = 'rgba(148, 163, 184, 0.2)';
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [
                { label: 'Companies', data: data.companies || [], borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.08)', fill: true, tension: 0.35, pointRadius: 2 },
                { label: 'Jobs', data: data.jobs || [], borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.06)', fill: true, tension: 0.35, pointRadius: 2 },
                { label: 'Candidates', data: data.candidates || [], borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.06)', fill: true, tension: 0.35, pointRadius: 2 },
                { label: 'Applications', data: data.applications || [], borderColor: '#ea580c', backgroundColor: 'rgba(234,88,12,.06)', fill: true, tension: 0.35, pointRadius: 2 },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, font: { size: 11, weight: '600' } } },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 8 },
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { maxTicksLimit: 8, font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0, font: { size: 10 } } },
            },
        },
    });
})();
</script>
@endpush
