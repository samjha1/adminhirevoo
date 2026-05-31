@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
    @php
        $leadScore = (int) ($lead->match_percentage ?? 0) + (int) ($lead->intent_score ?? 0);
        $candidate = $lead->candidate;
        $profile = $candidate?->candidateProfile;
        $currentStage = $lead->adminStage?->stage ?? 'new';
        $me = auth('admin')->user();
        $interest = $insight['interest_level'] ?? 'low';
        $interestBadge = $interest === 'high' ? 'success' : ($interest === 'medium' ? 'warning' : 'secondary');
    @endphp

    <style>
        .lead-detail { max-width: 980px; margin: 0 auto; }
        .lead-hero {
            border-radius: 16px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #1e40af 100%);
            color: #fff;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: 0 12px 40px rgba(15, 23, 42, .18);
        }
        .lead-hero .lead-id { font-size: .75rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; opacity: .85; }
        .lead-hero h1 { font-size: 1.35rem; font-weight: 800; letter-spacing: -.02em; margin: .25rem 0 0; }
        .lead-hero-meta { font-size: .875rem; opacity: .88; margin-top: .35rem; }
        .lead-section {
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, .08);
            background: #fff;
            box-shadow: 0 10px 30px rgba(17, 24, 39, .06);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .lead-section-hd {
            display: flex;
            align-items: flex-start;
            gap: .875rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
            border-bottom: 1px solid rgba(15, 23, 42, .06);
        }
        .lead-section-hd .hd-icon {
            width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 1.15rem;
        }
        .lead-section-hd .hd-icon.contact { background: rgba(59,130,246,.12); color: #1d4ed8; }
        .lead-section-hd .hd-icon.ai { background: rgba(139,92,246,.12); color: #7c3aed; }
        .lead-section-hd .hd-icon.assign { background: rgba(16,185,129,.12); color: #047857; }
        .lead-section-hd .hd-icon.crm { background: rgba(245,158,11,.14); color: #b45309; }
        .lead-section-hd .hd-icon.history { background: rgba(100,116,139,.12); color: #475569; }
        .lead-section-hd .hd-title { font-weight: 700; font-size: 1rem; letter-spacing: -.01em; margin: 0; color: #0f172a; }
        .lead-section-hd .hd-desc { font-size: .8125rem; color: #64748b; margin: .2rem 0 0; line-height: 1.4; }
        .lead-section-bd { padding: 1.25rem; }
        .contact-chip {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .5rem .75rem; border-radius: 10px;
            background: #f8fafc; border: 1px solid #e2e8f0;
            font-size: .875rem; color: #334155;
        }
        .contact-chip i { color: #64748b; }
        .resume-snippet {
            border-radius: 12px;
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 1rem 1.1rem;
            font-size: .9rem;
            line-height: 1.55;
            color: #334155;
        }
        .stat-tile {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: .9rem 1rem;
            background: #fafbfc;
            height: 100%;
        }
        .stat-tile .lbl { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b; }
        .stat-tile .val { font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em; margin-top: .2rem; }
        .ai-prose {
            border-radius: 12px;
            background: linear-gradient(180deg, #faf5ff 0%, #fff 100%);
            border: 1px solid #e9d5ff;
            padding: 1rem 1.15rem;
            font-size: .9rem;
            line-height: 1.6;
            color: #334155;
        }
        .ai-list li { margin-bottom: .35rem; padding-left: .15rem; }
        .assign-grid { display: grid; gap: .75rem; }
        @media (min-width: 768px) { .assign-grid.cols-4 { grid-template-columns: repeat(4, 1fr); } }
        .assign-kv {
            padding: .65rem .85rem;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .assign-kv .k { font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #64748b; }
        .assign-kv .v { font-size: .9rem; font-weight: 600; color: #0f172a; margin-top: .15rem; }
        .assign-actions { border-top: 1px dashed #e2e8f0; margin-top: 1rem; padding-top: 1rem; }
        .assign-actions .form-label { font-weight: 600; color: #475569; font-size: .8rem; }
        .history-table thead th { border-bottom-width: 1px; }
        .history-table tbody tr:hover { background: #f8fafc; }
    </style>

    <div class="lead-detail">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-2">
            <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to leads
            </a>
        </div>

        <div class="lead-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="lead-id">Product lead</div>
                    <h1>Lead #{{ $lead->id }}</h1>
                    <div class="lead-hero-meta">
                        {{ $candidate?->name ?? 'Unknown candidate' }}
                        @if($candidate?->email)
                            <span class="opacity-50 mx-1">·</span> {{ $candidate->email }}
                        @endif
                    </div>
                </div>
                <div class="text-end">
                    <div class="small opacity-75">Combined score</div>
                    <div class="fs-3 fw-bold">{{ $leadScore }}</div>
                </div>
            </div>
        </div>

        {{-- 1. Contact + Resume Snapshot --}}
        <section class="lead-section" aria-labelledby="sec-contact">
            <div class="lead-section-hd">
                <div class="hd-icon contact"><i class="bi bi-person-vcard"></i></div>
                <div>
                    <h2 class="hd-title" id="sec-contact">Contact + Resume Snapshot</h2>
                    <p class="hd-desc mb-0">Reach-out details and resume context before you call or email.</p>
                </div>
            </div>
            <div class="lead-section-bd">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="fw-semibold text-secondary small text-uppercase mb-2">Identity</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="contact-chip"><i class="bi bi-person"></i> {{ $candidate?->name ?? '—' }}</span>
                            @if($candidate?->email)
                                <a href="mailto:{{ $candidate->email }}" class="contact-chip text-decoration-none text-dark"><i class="bi bi-envelope"></i> {{ $candidate->email }}</a>
                            @else
                                <span class="contact-chip text-muted"><i class="bi bi-envelope"></i> —</span>
                            @endif
                            @if($candidate?->phone)
                                <a href="tel:{{ $candidate->phone }}" class="contact-chip text-decoration-none text-dark"><i class="bi bi-telephone"></i> {{ $candidate->phone }}</a>
                            @else
                                <span class="contact-chip text-muted"><i class="bi bi-telephone"></i> —</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fw-semibold text-secondary small text-uppercase mb-1">Preferred role</div>
                        <div class="text-dark">{{ $profile?->preferred_job_role ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fw-semibold text-secondary small text-uppercase mb-1">Location</div>
                        <div class="text-dark">{{ $profile?->preferred_job_location ?? ($profile?->location ?? '—') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fw-semibold text-secondary small text-uppercase mb-1">Experience</div>
                        <div class="text-dark">{{ $profile?->experience_years !== null ? $profile->experience_years.' years' : '—' }}</div>
                    </div>
                </div>
                <div>
                    <div class="fw-semibold text-secondary small text-uppercase mb-2">Resume AI summary</div>
                    <div class="resume-snippet">
                        {{ $primaryResume?->ai_summary ?? 'No AI summary available for this resume yet.' }}
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. AI Candidate Summary --}}
        <section class="lead-section" aria-labelledby="sec-ai">
            <div class="lead-section-hd">
                <div class="hd-icon ai"><i class="bi bi-stars"></i></div>
                <div>
                    <h2 class="hd-title" id="sec-ai">AI Candidate Summary</h2>
                    <p class="hd-desc mb-0">Signals and narrative to prioritize your next touchpoint.</p>
                </div>
            </div>
            <div class="lead-section-bd">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="stat-tile">
                            <div class="lbl">Interest</div>
                            <div class="val"><span class="badge text-bg-{{ $interestBadge }} rounded-pill px-3 py-2 text-capitalize">{{ $interest }}</span></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-tile">
                            <div class="lbl">Lead score</div>
                            <div class="val">{{ $leadScore }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-tile">
                            <div class="lbl">Match %</div>
                            <div class="val">{{ (int) ($lead->match_percentage ?? 0) }}%</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-tile">
                            <div class="lbl">Intent score</div>
                            <div class="val">{{ (int) ($lead->intent_score ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="fw-semibold text-dark mb-2"><i class="bi bi-chat-quote text-primary me-1"></i> Executive summary</div>
                    <div class="ai-prose">{{ $insight['executive_summary'] ?? 'No summary available.' }}</div>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="fw-semibold text-dark mb-2"><i class="bi bi-mortarboard text-primary me-1"></i> Upskill recommendations</div>
                        @if(count($insight['upskill_recommendations'] ?? []) > 0)
                            <ul class="ai-list mb-0 ps-3 small">
                                @foreach(($insight['upskill_recommendations'] ?? []) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small mb-0">None listed.</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="fw-semibold text-dark mb-2"><i class="bi bi-lightning-charge text-primary me-1"></i> Next best actions</div>
                        @if(count($insight['next_best_actions'] ?? []) > 0)
                            <ul class="ai-list mb-0 ps-3 small">
                                @foreach(($insight['next_best_actions'] ?? []) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small mb-0">None listed.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- 3. Assignment --}}
        <section class="lead-section" aria-labelledby="sec-assign">
            <div class="lead-section-hd">
                <div class="hd-icon assign"><i class="bi bi-diagram-3"></i></div>
                <div>
                    <h2 class="hd-title" id="sec-assign">Assignment</h2>
                    <p class="hd-desc mb-0">Routing, ownership, and sales tracking for this lead.</p>
                </div>
            </div>
            <div class="lead-section-bd">
                <div class="assign-grid cols-4 mb-1">
                    <div class="assign-kv">
                        <div class="k">Workflow</div>
                        <div class="v"><span class="badge text-bg-secondary text-capitalize">{{ str_replace('_', ' ', $lead->assignment_status?->value ?? '—') }}</span></div>
                    </div>
                    <div class="assign-kv">
                        <div class="k">Assigned to</div>
                        <div class="v">{{ $lead->assignedTo?->name ?? '—' }}</div>
                    </div>
                    <div class="assign-kv">
                        <div class="k">Sales manager</div>
                        <div class="v">{{ $lead->salesManager?->name ?? '—' }}</div>
                    </div>
                    <div class="assign-kv">
                        <div class="k">Sales status</div>
                        <div class="v"><span class="badge text-bg-light text-dark border text-capitalize">{{ $lead->sales_status?->value ?? 'pending' }}</span></div>
                    </div>
                </div>

                @php
                    $atEmployee = $lead->assignment_role_level === \App\Enums\AssignmentRoleLevel::Employee;
                    $hasManager = $lead->sales_manager_id !== null;
                    $inPool = $lead->assigned_to === null && $lead->sales_manager_id === null;
                @endphp

                @can('assignAsMarketing', $lead)
                    <div class="assign-actions">
                        <div class="fw-semibold text-dark mb-2 small text-uppercase">Marketing / admin routing</div>
                        @if($inPool || ! $hasManager)
                            <form method="POST" action="{{ route('admin.leads.assign-manager', $lead) }}" class="row g-2 align-items-end mb-3">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label mb-1">Assign to sales manager</label>
                                    <select name="manager_id" class="form-select form-select-sm" required>
                                        <option value="">Select manager…</option>
                                        @foreach($assignableManagers as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Assign</button>
                                </div>
                            </form>
                        @endif
                        @if($hasManager && ! $atEmployee)
                            <form method="POST" action="{{ route('admin.leads.reassign-manager', $lead) }}" class="row g-2 align-items-end mb-3">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label mb-1">Reassign to another manager</label>
                                    <select name="manager_id" class="form-select form-select-sm" required>
                                        <option value="">Select manager…</option>
                                        @foreach($assignableManagers as $m)
                                            <option value="{{ $m->id }}" @selected((int) $lead->sales_manager_id === (int) $m->id)>{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Reassign</button>
                                </div>
                            </form>
                        @endif
                        @if(! $inPool)
                            <form method="POST" action="{{ route('admin.leads.release', $lead) }}" onsubmit="return confirm('Return this lead to the unassigned pool?');" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Unassign (pool)</button>
                            </form>
                        @endif
                    </div>
                @endcan

                @can('assignAsManager', $lead)
                    <div class="assign-actions">
                        <div class="fw-semibold text-dark mb-2 small text-uppercase">Sales manager — team ownership</div>
                        @if(! $atEmployee)
                            <form method="POST" action="{{ route('admin.leads.assign-employee', $lead) }}" class="row g-2 align-items-end mb-3">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label mb-1">Assign to employee</label>
                                    <select name="employee_id" class="form-select form-select-sm" required>
                                        <option value="">Select employee…</option>
                                        @foreach($assignableEmployees as $e)
                                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Assign</button>
                                </div>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.leads.reassign-employee', $lead) }}" class="row g-2 align-items-end mb-2">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label mb-1">Reassign to another employee</label>
                                    <select name="employee_id" class="form-select form-select-sm" required>
                                        @foreach($assignableEmployees as $e)
                                            <option value="{{ $e->id }}" @selected((int) $lead->assigned_to === (int) $e->id)>{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Reassign</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endcan

                @can('takeBack', $lead)
                    <div class="assign-actions pt-3">
                        <form method="POST" action="{{ route('admin.leads.take-back', $lead) }}" onsubmit="return confirm('Take this lead back from the executive to you?');" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">Take back from employee</button>
                        </form>
                    </div>
                @endcan

                @can('updateSalesStatus', $lead)
                    <div class="assign-actions">
                        <div class="fw-semibold text-dark mb-2 small text-uppercase">Sales tracking</div>
                        <form method="POST" action="{{ route('admin.leads.sales-status', $lead) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label mb-1">Status</label>
                                <select name="sales_status" class="form-select form-select-sm">
                                    @foreach($salesStatuses as $ss)
                                        <option value="{{ $ss->value }}" @selected($lead->sales_status === $ss)>{{ ucfirst($ss->value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-success w-100">Update</button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </section>

        @can('updateCrmStage', $lead)
            <section class="lead-section" aria-labelledby="sec-crm">
                <div class="lead-section-hd">
                    <div class="hd-icon crm"><i class="bi bi-kanban"></i></div>
                    <div>
                        <h2 class="hd-title" id="sec-crm">CRM pipeline stage</h2>
                        <p class="hd-desc mb-0">Called, DNP, follow-up — keep the funnel accurate.</p>
                    </div>
                </div>
                <div class="lead-section-bd">
                    <form method="POST" action="{{ route('admin.leads.stage', $lead->id) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Current stage</label>
                                <select class="form-select" name="stage">
                                    @foreach($stages as $stage)
                                        <option value="{{ $stage }}" @selected($currentStage === $stage)>{{ $crmStageLabels[$stage] ?? $stage }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small">Notes</label>
                                <input type="text" class="form-control" name="notes" value="{{ $lead->adminStage?->notes }}" placeholder="e.g. called, no response — follow up tomorrow">
                            </div>
                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <span class="text-muted small">Pick the stage that matches where this lead is in your pipeline (New through Lost).</span>
                                <button type="submit" class="btn btn-primary">Update stage</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        @endcan

        @can('logCall', $lead)
            <section class="lead-section" aria-labelledby="sec-calls">
                <div class="lead-section-hd">
                    <div class="hd-icon crm"><i class="bi bi-telephone"></i></div>
                    <div class="flex-grow-1">
                        <h2 class="hd-title" id="sec-calls">Call activity</h2>
                        <p class="hd-desc mb-0">Log outcomes and review recent calls.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#logCallModal">Log call</button>
                </div>
                <div class="lead-section-bd">
                    <ul class="list-group list-group-flush">
                        @forelse($recentCalls as $call)
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>{{ $call->outcome->label() }} · {{ $call->admin?->name }}</span>
                                <span class="text-muted small">{{ $call->called_at?->format('M j, H:i') }}</span>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-muted">No calls logged yet.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        @endcan

        @can('manageFollowups', $lead)
            <section class="lead-section" aria-labelledby="sec-followups">
                <div class="lead-section-hd">
                    <div class="hd-icon crm"><i class="bi bi-calendar-plus"></i></div>
                    <div class="flex-grow-1">
                        <h2 class="hd-title" id="sec-followups">Follow-ups</h2>
                        <p class="hd-desc mb-0">Schedule the next touchpoint.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#scheduleFollowUpModal">Schedule</button>
                </div>
                <div class="lead-section-bd">
                    <ul class="list-group list-group-flush">
                        @forelse($upcomingFollowUps as $fu)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>{{ $fu->scheduled_at?->format('M j, Y H:i') }} · {{ $fu->status->label() }}</span>
                                @if($fu->status->value === 'pending' || $fu->status->value === 'overdue')
                                    <form method="POST" action="{{ route('admin.follow-ups.complete', $fu) }}" class="m-0">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Done</button>
                                    </form>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-muted">No follow-ups scheduled.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        @endcan

        <section class="lead-section" aria-labelledby="sec-timeline">
            <div class="lead-section-hd">
                <div class="hd-icon history"><i class="bi bi-activity"></i></div>
                <div>
                    <h2 class="hd-title" id="sec-timeline">Activity timeline</h2>
                    <p class="hd-desc mb-0">Calls, assignments, stages, and audit events.</p>
                </div>
            </div>
            <div class="lead-section-bd">
                <ul class="list-unstyled mb-0">
                    @foreach($timeline->take(25) as $item)
                        <li class="d-flex gap-3 mb-3 pb-3 border-bottom">
                            <div class="text-muted small text-nowrap" style="min-width:120px">{{ $item['at']->format('M j, H:i') }}</div>
                            <div>
                                <div class="fw-semibold">{{ $item['title'] }}</div>
                                <div class="text-muted small">{{ $item['type'] }}@if($item['actor']) · {{ $item['actor'] }}@endif</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        @if($me->canPermission('consultations.view'))
            <section class="lead-section" aria-labelledby="sec-consult">
                <div class="lead-section-hd">
                    <div class="hd-icon contact"><i class="bi bi-calendar2-check"></i></div>
                    <div>
                        <h2 class="hd-title" id="sec-consult">Related career consultations</h2>
                        <p class="hd-desc mb-0">Other requests from this user, if any.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>ID</th><th>Status</th><th>Source</th><th>Match %</th><th>Created</th><th></th></tr></thead>
                        <tbody>
                        @forelse($relatedConsultations as $consultation)
                            <tr>
                                <td>#{{ $consultation->id }}</td>
                                <td>{{ $consultation->status }}</td>
                                <td>{{ $consultation->source }}</td>
                                <td>{{ (int) ($consultation->match_percentage ?? 0) }}</td>
                                <td class="text-muted small">{{ $consultation->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end"><a href="{{ route('admin.consultations.show', $consultation->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill">Details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No related consultation requests.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Assignment history (last) --}}
        @if($lead->assignmentHistory->isNotEmpty())
            <section class="lead-section mb-0" aria-labelledby="sec-history">
                <div class="lead-section-hd">
                    <div class="hd-icon history"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <h2 class="hd-title" id="sec-history">Assignment history</h2>
                        <p class="hd-desc mb-0">Audit trail of who moved this lead and when.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 history-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">When</th>
                                <th>Action</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="pe-3">By</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($lead->assignmentHistory as $h)
                            <tr>
                                <td class="text-muted small ps-3 text-nowrap">{{ $h->created_at?->format('M j, Y · H:i') }}</td>
                                <td><span class="badge text-bg-light text-dark border text-capitalize">{{ str_replace('_', ' ', $h->action_type) }}</span></td>
                                <td>{{ $h->fromAdmin?->name ?? '—' }}</td>
                                <td>{{ $h->toAdmin?->name ?? '—' }}</td>
                                <td class="pe-3">{{ $h->byAdmin?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    @can('logCall', $lead)
    <div class="modal fade" id="logCallModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.leads.calls.store', $lead) }}" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Log call</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Outcome</label>
                        <select name="outcome" class="form-select" required>
                            @foreach(\App\Modules\Leads\Enums\CallOutcome::cases() as $outcome)
                                <option value="{{ $outcome->value }}">{{ $outcome->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Duration (seconds)</label>
                        <input type="number" name="duration_seconds" class="form-control" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Called at</label>
                        <input type="datetime-local" name="called_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save call</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    @can('manageFollowups', $lead)
    <div class="modal fade" id="scheduleFollowUpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.leads.follow-ups.store', $lead) }}" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Schedule follow-up</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label">When</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endsection
