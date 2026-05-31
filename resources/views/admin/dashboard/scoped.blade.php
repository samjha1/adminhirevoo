@extends('layouts.app')

@section('title', 'My dashboard')

@section('content')
    @php
        $period = $period ?? \App\Support\DashboardPeriod::forPreset('this_month');
        $pipeline = $pipeline ?? \App\Enums\SalesTeam::Candidate;
        $summary = $summary ?? [];
        $role = $role ?? auth('admin')->user()->role;
        $isManager = $role === \App\Enums\AdminRole::SalesManager;
        $isEmployee = $role === \App\Enums\AdminRole::SalesEmployee;
        $isCompany = $pipeline === \App\Enums\SalesTeam::Employer;
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $isManager ? 'Manager dashboard' : 'My dashboard' }}</h1>
            <div class="page-subtitle">{{ $pipeline->pipelineTitle() }} · {{ $period->label() }}</div>
        </div>
        <a href="{{ $isCompany ? route('admin.employers.pipeline.index') : route('admin.leads.index') }}" class="btn btn-sm btn-outline-secondary">
            Open pipeline
        </a>
    </div>

    @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
    @include('partials.dashboard-period-filter', ['period' => $period])

    <h2 class="h6 fw-bold mb-2">My performance · {{ $period->label() }}</h2>
    @include('partials.dashboard-summary-cards', ['summary' => $summary, 'showPeriodLabels' => true])

    @if($isManager && !empty($teamSummary))
        <h2 class="h6 fw-bold mt-4 mb-2">My team · {{ $period->label() }}</h2>
        @include('partials.dashboard-summary-cards', ['summary' => $teamSummary, 'showPeriodLabels' => true])
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-{{ $isManager ? '6' : '12' }}">
            @include('partials.dashboard-activity-feed', [
                'title' => 'Activity in my domain · '.$period->label(),
                'items' => $activityFeed ?? [],
                'pipelineUrl' => $isCompany ? route('admin.employers.pipeline.index') : route('admin.leads.index'),
            ])
        </div>
        @if($isManager && !empty($teamMembers))
            <div class="col-lg-6">
                <div class="card shadow-soft h-100">
                    <div class="card-header bg-white fw-semibold">Team members · {{ $period->label() }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Employee</th>
                                <th class="text-end">Leads</th>
                                <th class="text-end">Meetings</th>
                                <th class="text-end">Closed</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($teamMembers as $row)
                                <tr>
                                    <td>{{ $row['employee'] }}</td>
                                    <td class="text-end">{{ number_format($row['leads']) }}</td>
                                    <td class="text-end">{{ number_format($row['meetings']) }}</td>
                                    <td class="text-end">{{ number_format($row['closed']) }}</td>
                                    <td class="text-end">₹{{ number_format($row['revenue'], 0) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if(!empty($myRecordsInPeriod))
        <div class="card shadow-soft mb-4">
            <div class="card-header bg-white fw-semibold">
                {{ $isEmployee ? 'My leads in this period' : 'My assigned records · '.$period->label() }}
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Stage</th>
                        <th>Last activity</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($myRecordsInPeriod as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="small text-muted">{{ $row['stage'] }}</td>
                            <td class="small">{{ $row['activity'] }}</td>
                            <td class="text-end">
                                <a href="{{ $row['url'] }}" class="btn btn-sm btn-outline-secondary">Open</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(!empty($trends))
        @include('partials.dashboard-charts', ['trends' => $trends])
    @endif

    @if(!empty($funnel))
        <div class="card shadow-soft">
            <div class="card-header bg-white fw-semibold">Active in period (by stage)</div>
            <div class="card-body d-flex flex-wrap gap-2">
                @if($isCompany)
                    @foreach(\App\Enums\CompanyB2bPipelineStage::ordered() as $stage)
                        @php $count = is_array($funnel[$stage->value] ?? null) ? ($funnel[$stage->value]['count'] ?? 0) : ($funnel[$stage->value] ?? 0); @endphp
                        @if($count > 0)
                            <span class="badge bg-light text-dark border">{{ $stage->label() }}: {{ $count }}</span>
                        @endif
                    @endforeach
                @else
                    @foreach($funnel as $stage => $count)
                        <span class="badge bg-light text-dark border">{{ $stage }}: {{ $count }}</span>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
@endsection
