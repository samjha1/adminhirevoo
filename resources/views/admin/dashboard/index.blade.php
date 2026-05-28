@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $role = $role ?? auth('admin')->user()->role;
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <div class="page-subtitle">
                Signed in as <strong>{{ auth('admin')->user()->role->label() }}</strong>
                · {{ auth('admin')->user()->email }}
            </div>
        </div>
    </div>

    @switch($role)
        @case(\App\Enums\AdminRole::Admin)
            <div class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi">
                                <div class="kpi-icon primary"><i class="bi bi-buildings"></i></div>
                                <div>
                                    <div class="kpi-label">Total users (candidates)</div>
                                    <div class="kpi-value">{{ number_format($totalUsers) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi">
                                <div class="kpi-icon success"><i class="bi bi-patch-check"></i></div>
                                <div>
                                    <div class="kpi-label">Total jobs</div>
                                    <div class="kpi-value">{{ number_format($totalJobs) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi">
                                <div class="kpi-icon primary"><i class="bi bi-file-earmark-text"></i></div>
                                <div>
                                    <div class="kpi-label">Applications</div>
                                    <div class="kpi-value">{{ number_format($totalApplications) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi">
                                <div class="kpi-icon success"><i class="bi bi-diagram-3"></i></div>
                                <div>
                                    <div class="kpi-label">Referrals</div>
                                    <div class="kpi-value">{{ number_format($totalReferrals) }}</div>
                                    <div class="small text-muted">Accepted: {{ number_format($acceptedReferrals) }} ({{ $conversionRate }}%)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi">
                                <div class="kpi-icon success"><i class="bi bi-cash-coin"></i></div>
                                <div>
                                    <div class="kpi-label">Revenue</div>
                                    <div class="kpi-value">INR {{ number_format($revenue, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi">
                                <div class="kpi-icon primary"><i class="bi bi-funnel"></i></div>
                                <div>
                                    <div class="kpi-label">Total leads</div>
                                    <div class="kpi-value">{{ number_format($totalLeads ?? $totalLeadsTracked) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-soft mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="mb-0">CRM pipeline (admin)</h5>
                        <div class="text-muted small">Pending consultations: <strong>{{ number_format($pendingConsultations) }}</strong></div>
                    </div>
                    <div class="row g-2 mb-4">
                        @foreach($crmPipeline as $stage => $count)
                            <div class="col-6 col-md-4 col-xl-2">
                                <a href="{{ route('admin.leads.index', ['mgmt_stage' => $stage]) }}" class="text-decoration-none">
                                    <div class="border rounded-3 p-2 h-100 text-dark hover-shadow" style="transition: box-shadow .15s;">
                                        <div class="text-muted small text-capitalize">{{ str_replace('_', ' ', $stage) }}</div>
                                        <div class="fw-bold fs-5">{{ number_format($count) }}</div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <h5 class="mb-2">Assignment workflow</h5>
                    <div class="row g-3 mb-4">
                        @foreach($assignmentByStatus ?? [] as $st => $count)
                            <div class="col-sm-6 col-xl-3">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="text-muted text-capitalize">{{ str_replace('_', ' ', $st) }}</div>
                                    <div class="fw-bold fs-4">{{ number_format($count) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h5 class="mb-3">Lead funnel (product status)</h5>
                    <div class="row g-3">
                        @forelse($leadStages as $stage => $count)
                            <div class="col-sm-6 col-xl-3">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="text-muted text-capitalize">{{ str_replace('_', ' ', $stage) }}</div>
                                    <div class="fw-bold fs-4">{{ number_format($count) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted">No lead data yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            @break

        @case(\App\Enums\AdminRole::Marketing)
            <div class="row g-3 g-lg-4">
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">Total leads</div>
                            <div class="kpi-value">{{ number_format($totalLeads) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">Consultation requests</div>
                            <div class="kpi-value">{{ number_format($totalConsultationRequests) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">Unassigned leads</div>
                            <div class="kpi-value">{{ number_format($unassignedLeads) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">Assigned leads</div>
                            <div class="kpi-value">{{ number_format($assignedLeads) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @break

        @case(\App\Enums\AdminRole::SalesManager)
            <div class="row g-3 g-lg-4">
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">Leads assigned to you</div>
                            <div class="kpi-value">{{ number_format($leadsAssignedToMe) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">With your team</div>
                            <div class="kpi-value">{{ number_format($leadsWithEmployees) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">In progress</div>
                            <div class="kpi-value">{{ number_format($inProgress) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">Closed (workflow)</div>
                            <div class="kpi-value">{{ number_format($closed) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-muted small mt-2">Total in your scope: <strong>{{ number_format($totalInScope) }}</strong></div>
            @break

        @case(\App\Enums\AdminRole::SalesEmployee)
            <div class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="card shadow-soft">
                        <div class="card-body">
                            <div class="kpi-label">Your assigned leads</div>
                            <div class="kpi-value">{{ number_format($totalAssigned) }}</div>
                        </div>
                    </div>
                </div>
                @foreach($salesStatusBreakdown as $st => $count)
                    <div class="col-md-4">
                        <div class="card shadow-soft">
                            <div class="card-body">
                                <div class="kpi-label text-capitalize">{{ str_replace('_', ' ', $st) }}</div>
                                <div class="kpi-value">{{ number_format($count) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card shadow-soft mt-4">
                <div class="card-header bg-white fw-semibold">Recent activity</div>
                <ul class="list-group list-group-flush">
                    @forelse($recentLeads as $rl)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $rl->candidate?->name ?? 'Lead #'.$rl->id }}</span>
                            <span class="text-muted small">{{ $rl->updated_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No recent updates.</li>
                    @endforelse
                </ul>
            </div>
            @break
    @endswitch
@endsection
