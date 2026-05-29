@extends('layouts.app')

@section('title', 'Company sales — Home')

@section('content')
    @include('partials.crm-pipeline-chrome', ['pipeline' => \App\Enums\SalesTeam::Employer])

    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card shadow-soft h-100 crm-stat-card">
                <div class="card-body">
                    <div class="crm-stat-label">Contacted today</div>
                    <div class="crm-stat-value">{{ number_format($companiesContactedToday ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card shadow-soft h-100 crm-stat-card">
                <div class="card-body">
                    <div class="crm-stat-label">Calls today</div>
                    <div class="crm-stat-value">{{ number_format($callsToday ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card shadow-soft h-100 crm-stat-card">
                <div class="card-body">
                    <div class="crm-stat-label">Meetings today</div>
                    <div class="crm-stat-value">{{ number_format($meetingsToday ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card shadow-soft h-100 crm-stat-card">
                <div class="card-body">
                    <div class="crm-stat-label">Proposals sent</div>
                    <div class="crm-stat-value">{{ number_format($proposalsSent ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card shadow-soft h-100 crm-stat-card">
                <div class="card-body">
                    <div class="crm-stat-label">In negotiation</div>
                    <div class="crm-stat-value">{{ number_format($inNegotiation ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card shadow-soft h-100 crm-stat-card accent">
                <div class="card-body">
                    <div class="crm-stat-label">Expected revenue</div>
                    <div class="crm-stat-value">₹{{ number_format($expectedRevenue ?? 0, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-soft">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <span>Pipeline by stage</span>
                    <a href="{{ route('admin.employers.pipeline.kanban') }}" class="btn btn-sm btn-outline-primary">Open Kanban</a>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach(\App\Enums\CompanyB2bPipelineStage::ordered() as $stage)
                            @php $count = $stageCounts[$stage->value] ?? 0; @endphp
                            <div class="col-6 col-md-4">
                                <a href="{{ route('admin.employers.pipeline.index', ['pipeline_stage' => $stage->value]) }}" class="text-decoration-none">
                                    <div class="border rounded-3 p-2 h-100 hover-lift">
                                        <div class="small text-muted">{{ $stage->label() }}</div>
                                        <div class="fw-bold fs-5 text-dark">{{ $count }}</div>
                                        <div class="small text-muted">{{ $stage->winProbability() }}% prob.</div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-soft mb-3">
                <div class="card-body">
                    <div class="crm-stat-label">Pipeline value (open)</div>
                    <div class="crm-stat-value">₹{{ number_format($pipelineValue ?? 0, 0) }}</div>
                    <div class="crm-stat-label mt-3">Won revenue</div>
                    <div class="crm-stat-value text-success">₹{{ number_format($wonRevenue ?? 0, 0) }}</div>
                    <div class="crm-stat-label mt-3">Active clients</div>
                    <div class="crm-stat-value">{{ number_format($activeClients ?? 0) }}</div>
                </div>
            </div>
            <div class="card shadow-soft">
                <div class="card-body">
                    <h6 class="fw-semibold">Quick actions</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-primary btn-sm">Company list</a>
                        <a href="{{ route('admin.employers.pipeline.kanban') }}" class="btn btn-outline-primary btn-sm">Kanban board</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
