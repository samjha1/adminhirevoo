@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    @include('partials.portal-ui')

    @php
        $r = $reports ?? [];
        $sections = [
            'companies' => ['icon' => 'bi-buildings', 'tone' => 'indigo', 'label' => 'Companies'],
            'jobs' => ['icon' => 'bi-briefcase', 'tone' => 'emerald', 'label' => 'Jobs'],
            'candidates' => ['icon' => 'bi-people', 'tone' => 'violet', 'label' => 'Candidates'],
            'applications' => ['icon' => 'bi-send-check', 'tone' => 'amber', 'label' => 'Applications'],
        ];
    @endphp

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'reports'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">Reports &amp; analytics</h1>
                <p class="portal-hero-sub">Export-ready metrics for companies, jobs, candidates, and applications.</p>
            </div>
            @if(auth('admin')->user()->canPermission('portal.reports.export'))
                <div class="portal-hero-actions d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.reports.export', ['format' => 'csv']) }}" class="btn btn-light btn-sm">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a href="{{ route('admin.reports.export', ['format' => 'excel']) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
                    </a>
                    <a href="{{ route('admin.reports.export', ['format' => 'pdf']) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-filetype-pdf me-1"></i>PDF
                    </a>
                </div>
            @endif
        </div>

        <div class="portal-section-label"><i class="bi bi-pie-chart"></i> Summary by area</div>
        <div class="row g-3 portal-report-grid mb-4">
            @foreach($sections as $key => $meta)
                <div class="col-md-6 col-xl-3">
                    <div class="portal-stat-card card border-0 shadow-none h-100">
                        <div class="card-body">
                            <div class="portal-stat-icon {{ $meta['tone'] }}">
                                <i class="bi {{ $meta['icon'] }}"></i>
                            </div>
                            <div>
                                <div class="portal-stat-label">{{ $meta['label'] }}</div>
                                <div class="portal-stat-value">{{ number_format($r[$key]['total'] ?? 0) }}</div>
                                <div class="portal-stat-delta neutral small">
                                    @if($key === 'applications')
                                        Week: {{ number_format($r[$key]['weekly'] ?? 0) }} · Month: {{ number_format($r[$key]['monthly'] ?? 0) }}
                                    @elseif($key === 'jobs')
                                        Active: {{ number_format($r[$key]['active'] ?? 0) }} · Today: {{ number_format($r[$key]['today'] ?? 0) }}
                                    @else
                                        Today: {{ number_format($r[$key]['today'] ?? 0) }} · Month: {{ number_format($r[$key]['monthly'] ?? 0) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="portal-panel">
            <div class="portal-panel-head">
                <h2 class="portal-panel-title"><i class="bi bi-table text-primary"></i> Detailed breakdown</h2>
                <span class="small text-muted">All metrics at a glance</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Area</th>
                        <th>Metric</th>
                        <th class="text-end">Count</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sections as $sectionKey => $meta)
                        @foreach($r[$sectionKey] ?? [] as $metric => $value)
                            <tr>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <i class="bi {{ $meta['icon'] }} text-muted"></i>
                                        <span class="fw-medium">{{ $meta['label'] }}</span>
                                    </span>
                                </td>
                                <td class="text-muted">{{ str_replace('_', ' ', ucfirst($metric)) }}</td>
                                <td class="text-end fw-bold">{{ number_format($value) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
