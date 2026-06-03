@extends('layouts.app')

@section('title', $lead->candidate?->name ?? 'Lead')

@push('styles')
<style>
    .crm-record { max-width: 1280px; margin: 0 auto; }
    .crm-record-top {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.1rem 1.35rem;
        margin-bottom: 1rem;
        box-shadow: 0 4px 24px rgba(15, 23, 42, .05);
    }
    .crm-avatar {
        width: 52px; height: 52px; border-radius: 14px;
        background: linear-gradient(135deg, #2563eb, #7c3aed);
        color: #fff; font-weight: 800; font-size: 1.1rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .crm-record-title { font-size: 1.35rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; margin: 0; line-height: 1.2; }
    .crm-record-sub { font-size: .85rem; color: #64748b; margin-top: .2rem; }
    .crm-stage-path {
        display: flex; gap: 0; overflow-x: auto; padding: .15rem 0 1rem;
        margin-bottom: 1rem; scrollbar-width: thin;
    }
    .crm-stage-step {
        flex: 1 0 auto; min-width: 88px; position: relative; text-align: center;
        padding: .55rem .5rem .45rem; font-size: .68rem; font-weight: 600;
        color: #94a3b8; border-bottom: 3px solid #e2e8f0;
        transition: color .2s, border-color .2s;
    }
    .crm-stage-step.done { color: #475569; border-color: #93c5fd; }
    .crm-stage-step.active {
        color: #1d4ed8; border-color: #2563eb; font-weight: 700;
        background: linear-gradient(180deg, rgba(37,99,235,.06), transparent);
    }
    .crm-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(15, 23, 42, .04);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .crm-card-hd {
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700; font-size: .82rem;
        letter-spacing: .03em;
        text-transform: uppercase;
        color: #475569;
        background: #fafbfc;
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    }
    .crm-card-bd { padding: 1.1rem 1.15rem; }
    .crm-field { margin-bottom: .85rem; }
    .crm-field:last-child { margin-bottom: 0; }
    .crm-field .lbl {
        font-size: .68rem; font-weight: 700; letter-spacing: .05em;
        text-transform: uppercase; color: #94a3b8; margin-bottom: .2rem;
    }
    .crm-field .val { font-size: .9rem; color: #0f172a; font-weight: 500; }
    .crm-action-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .45rem .85rem; border-radius: 10px;
        font-size: .8rem; font-weight: 600;
        border: 1px solid #e2e8f0; background: #fff; color: #334155;
        text-decoration: none; transition: all .15s ease;
    }
    .crm-action-btn:hover { border-color: #93c5fd; color: #1d4ed8; background: #f8fafc; }
    .crm-action-btn.primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    .crm-action-btn.primary:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
    .crm-sidebar { position: sticky; top: .75rem; }
    .crm-timeline { position: relative; padding-left: 1.75rem; }
    .crm-timeline::before {
        content: ''; position: absolute; left: .55rem; top: .35rem; bottom: .35rem;
        width: 2px; background: linear-gradient(180deg, #e2e8f0, #f1f5f9);
    }
    .crm-tl-item { position: relative; padding-bottom: 1.1rem; }
    .crm-tl-item:last-child { padding-bottom: 0; }
    .crm-tl-dot {
        position: absolute; left: -1.75rem; top: .15rem;
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
    }
    .crm-tl-dot.call { background: #dbeafe; color: #1d4ed8; }
    .crm-tl-dot.follow_up { background: #fef3c7; color: #b45309; }
    .crm-tl-dot.assignment { background: #d1fae5; color: #047857; }
    .crm-tl-dot.stage { background: #ede9fe; color: #6d28d9; }
    .crm-tl-dot.default { background: #f1f5f9; color: #64748b; }
    .crm-tl-title { font-weight: 600; font-size: .875rem; color: #0f172a; }
    .crm-tl-meta { font-size: .75rem; color: #94a3b8; margin-top: .15rem; }
    .crm-insight-box {
        background: linear-gradient(180deg, #faf5ff 0%, #fff 100%);
        border: 1px solid #e9d5ff; border-radius: 12px;
        padding: .9rem 1rem; font-size: .875rem; line-height: 1.55; color: #334155;
    }
    .crm-metric {
        text-align: center; padding: .75rem; border-radius: 12px;
        background: #f8fafc; border: 1px solid #e2e8f0;
    }
    .crm-metric .n { font-size: 1.25rem; font-weight: 800; color: #0f172a; }
    .crm-metric .l { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-top: .15rem; }
    .crm-owner-line { display: flex; align-items: center; gap: .5rem; font-size: .875rem; }
    .crm-owner-line .mini-av {
        width: 28px; height: 28px; border-radius: 8px; background: #e2e8f0;
        display: flex; align-items: center; justify-content: center;
        font-size: .7rem; font-weight: 700; color: #475569;
    }
    @media (max-width: 991px) {
        .crm-sidebar { position: static; }
    }
</style>
@endpush

@section('content')
    @php
        $leadScore = (int) ($lead->match_percentage ?? 0) + (int) ($lead->intent_score ?? 0);
        $candidate = $lead->candidate;
        $profile = $candidate?->candidateProfile;
        $currentStage = $lead->adminStage?->stage ?? 'new';
        $me = auth('admin')->user();
        $interest = $insight['interest_level'] ?? 'low';
        $interestBadge = $interest === 'high' ? 'success' : ($interest === 'medium' ? 'warning' : 'secondary');
        $initials = collect(explode(' ', (string) ($candidate?->name ?? 'L')))
            ->filter()->take(2)->map(fn ($w) => strtoupper(substr($w, 0, 1)))->implode('') ?: 'L';
        $stageIndex = array_search($currentStage, $stages, true);
        if ($stageIndex === false) {
            $stageIndex = 0;
        }
        $atEmployee = $lead->assignment_role_level === \App\Enums\AssignmentRoleLevel::Employee;
        $hasManager = $lead->sales_manager_id !== null;
        $inPool = $lead->assigned_to === null && $lead->sales_manager_id === null;
    @endphp

    <div class="crm-record">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <a href="{{ route('admin.leads.index') }}" class="crm-action-btn">
                <i class="bi bi-arrow-left"></i> Leads
            </a>
            <div class="d-flex flex-wrap gap-2">
                @if($candidate?->phone)
                    <a href="tel:{{ $candidate->phone }}" class="crm-action-btn"><i class="bi bi-telephone"></i> Call</a>
                @endif
                @if($candidate?->email)
                    <a href="mailto:{{ $candidate->email }}" class="crm-action-btn"><i class="bi bi-envelope"></i> Email</a>
                @endif
                @can('logCall', $lead)
                    <button type="button" class="crm-action-btn primary" data-bs-toggle="modal" data-bs-target="#logCallModal">
                        <i class="bi bi-plus-lg"></i> Log activity
                    </button>
                @endcan
                @can('manageFollowups', $lead)
                    <button type="button" class="crm-action-btn" data-bs-toggle="modal" data-bs-target="#scheduleFollowUpModal">
                        <i class="bi bi-calendar-plus"></i> Follow-up
                    </button>
                @endcan
            </div>
        </div>

        <div class="crm-record-top">
            <div class="d-flex flex-wrap align-items-start gap-3">
                <div class="crm-avatar">{{ $initials }}</div>
                <div class="flex-grow-1 min-w-0">
                    <h1 class="crm-record-title">{{ $candidate?->name ?? 'Unknown candidate' }}</h1>
                    <div class="crm-record-sub">
                        {{ $profile?->preferred_job_role ?? 'Candidate lead' }}
                        @if($profile?->preferred_job_location)
                            · {{ $profile->preferred_job_location }}
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge rounded-pill text-bg-primary">{{ $crmStageLabels[$currentStage] ?? ucfirst(str_replace('_', ' ', $currentStage)) }}</span>
                        <span class="badge rounded-pill text-bg-light text-dark border text-capitalize">{{ str_replace('_', ' ', $lead->assignment_status?->value ?? 'new') }}</span>
                        <span class="badge rounded-pill text-bg-{{ $interestBadge }} text-capitalize">{{ $interest }} interest</span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="crm-metric d-inline-block px-4">
                        <div class="n">{{ $leadScore }}</div>
                        <div class="l">Lead score</div>
                    </div>
                </div>
            </div>
        </div>

        @can('updateCrmStage', $lead)
            <div class="crm-stage-path" role="list" aria-label="Pipeline stage">
                @foreach($stages as $i => $stage)
                    @php
                        $isActive = $currentStage === $stage;
                        $isDone = $i < $stageIndex;
                    @endphp
                    <div class="crm-stage-step @if($isActive) active @elseif($isDone) done @endif" role="listitem">
                        {{ $crmStageLabels[$stage] ?? $stage }}
                    </div>
                @endforeach
            </div>
        @endcan

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="crm-card">
                    <div class="crm-card-hd"><span><i class="bi bi-person-lines-fill me-1"></i> Contact</span></div>
                    <div class="crm-card-bd">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="crm-field">
                                    <div class="lbl">Email</div>
                                    <div class="val">
                                        @if($candidate?->email)
                                            <a href="mailto:{{ $candidate->email }}" class="text-decoration-none">{{ $candidate->email }}</a>
                                        @else — @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="crm-field">
                                    <div class="lbl">Phone</div>
                                    <div class="val">
                                        @if($candidate?->phone)
                                            <a href="tel:{{ $candidate->phone }}" class="text-decoration-none">{{ $candidate->phone }}</a>
                                        @else — @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="crm-field">
                                    <div class="lbl">Experience</div>
                                    <div class="val">{{ $profile?->experience_years !== null ? $profile->experience_years.' yrs' : '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="crm-field">
                                    <div class="lbl">Match</div>
                                    <div class="val">{{ (int) ($lead->match_percentage ?? 0) }}%</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="crm-field">
                                    <div class="lbl">Intent</div>
                                    <div class="val">{{ (int) ($lead->intent_score ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="crm-card">
                    <div class="crm-card-hd"><span><i class="bi bi-file-earmark-text me-1"></i> Resume summary</span></div>
                    <div class="crm-card-bd">
                        <p class="mb-0 text-secondary" style="font-size:.9rem;line-height:1.6">
                            {{ $primaryResume?->ai_summary ?? 'No AI summary available yet.' }}
                        </p>
                    </div>
                </div>

                <div class="crm-card">
                    <div class="crm-card-hd"><span><i class="bi bi-stars me-1"></i> AI insights</span></div>
                    <div class="crm-card-bd">
                        <div class="crm-insight-box mb-3">{{ $insight['executive_summary'] ?? 'No summary available.' }}</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="lbl text-uppercase small fw-bold text-muted mb-2">Upskill</div>
                                @if(count($insight['upskill_recommendations'] ?? []) > 0)
                                    <ul class="small mb-0 ps-3 text-secondary">
                                        @foreach($insight['upskill_recommendations'] as $item)
                                            <li class="mb-1">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">None listed.</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="lbl text-uppercase small fw-bold text-muted mb-2">Next actions</div>
                                @if(count($insight['next_best_actions'] ?? []) > 0)
                                    <ul class="small mb-0 ps-3 text-secondary">
                                        @foreach($insight['next_best_actions'] as $item)
                                            <li class="mb-1">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">None listed.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="crm-card">
                    <div class="crm-card-hd">
                        <span><i class="bi bi-clock-history me-1"></i> Activity</span>
                        <span class="badge text-bg-light text-dark border fw-normal">{{ $timeline->count() }}</span>
                    </div>
                    <div class="crm-card-bd">
                        @if($timeline->isEmpty())
                            <p class="text-muted small mb-0">No activity yet. Log a call or update the stage to get started.</p>
                        @else
                            <div class="crm-timeline">
                                @foreach($timeline->take(30) as $item)
                                    @php
                                        $tlType = $item['type'] ?? 'default';
                                        $dotClass = in_array($tlType, ['call', 'follow_up', 'assignment', 'stage'], true) ? $tlType : 'default';
                                        $icon = match ($tlType) {
                                            'call' => 'bi-telephone',
                                            'follow_up' => 'bi-calendar-event',
                                            'assignment' => 'bi-people',
                                            'stage' => 'bi-flag',
                                            default => 'bi-dot',
                                        };
                                    @endphp
                                    <div class="crm-tl-item">
                                        <div class="crm-tl-dot {{ $dotClass }}"><i class="bi {{ $icon }}"></i></div>
                                        <div class="crm-tl-title">{{ $item['title'] }}</div>
                                        <div class="crm-tl-meta">
                                            {{ $item['at']->format('M j, Y · g:i A') }}
                                            @if($item['actor']) · {{ $item['actor'] }} @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if($me->canPermission('consultations.view') && $relatedConsultations->isNotEmpty())
                    <div class="crm-card mb-0">
                        <div class="crm-card-hd"><span><i class="bi bi-calendar2-check me-1"></i> Consultations</span></div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th class="ps-3">ID</th><th>Status</th><th>Match</th><th>Created</th><th></th></tr>
                                </thead>
                                <tbody>
                                @foreach($relatedConsultations as $consultation)
                                    <tr>
                                        <td class="ps-3">#{{ $consultation->id }}</td>
                                        <td>{{ $consultation->status }}</td>
                                        <td>{{ (int) ($consultation->match_percentage ?? 0) }}%</td>
                                        <td class="text-muted small">{{ $consultation->created_at?->format('M j, Y') }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('admin.consultations.show', $consultation->id) }}" class="btn btn-sm btn-link">Open</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="crm-sidebar">
                    @can('updateCrmStage', $lead)
                        <div class="crm-card">
                            <div class="crm-card-hd">Pipeline stage</div>
                            <div class="crm-card-bd">
                                <form method="POST" action="{{ route('admin.leads.stage', $lead->id) }}" data-crm-stage-form>
                                    @csrf
                                    <div class="mb-2">
                                        <select class="form-select form-select-sm" name="stage"
                                                data-crm-stage-select
                                                data-follow-up-value="follow_up"
                                                data-interview-value="interview">
                                            @foreach($stages as $stage)
                                                <option value="{{ $stage }}" @selected($currentStage === $stage)>{{ $crmStageLabels[$stage] ?? $stage }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @include('partials.crm-follow-up-stage-fields')
                                    <div class="mb-2">
                                        <input type="text" class="form-control form-control-sm" name="notes"
                                               value="{{ $lead->adminStage?->notes }}"
                                               placeholder="Stage notes…">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Update stage</button>
                                </form>
                                @include('partials.crm-stage-schedule-script')
                            </div>
                        </div>
                    @endcan

                    <div class="crm-card">
                        <div class="crm-card-hd">Ownership</div>
                        <div class="crm-card-bd">
                            <div class="crm-field">
                                <div class="lbl">Owner</div>
                                <div class="crm-owner-line">
                                    <span class="mini-av">{{ substr($lead->assignedTo?->name ?? '?', 0, 1) }}</span>
                                    <span>{{ $lead->assignedTo?->name ?? 'Unassigned' }}</span>
                                </div>
                            </div>
                            <div class="crm-field">
                                <div class="lbl">Sales manager</div>
                                <div class="val">{{ $lead->salesManager?->name ?? '—' }}</div>
                            </div>
                            <div class="crm-field">
                                <div class="lbl">Sales status</div>
                                <div class="val text-capitalize">{{ $lead->sales_status?->value ?? 'pending' }}</div>
                            </div>
                        </div>
                    </div>

                    @can('updateSalesStatus', $lead)
                        <div class="crm-card">
                            <div class="crm-card-hd">Sales outcome</div>
                            <div class="crm-card-bd">
                                <form method="POST" action="{{ route('admin.leads.sales-status', $lead) }}">
                                    @csrf
                                    <select name="sales_status" class="form-select form-select-sm mb-2">
                                        @foreach($salesStatuses as $ss)
                                            <option value="{{ $ss->value }}" @selected($lead->sales_status === $ss)>{{ ucfirst($ss->value) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-outline-success btn-sm w-100">Save</button>
                                </form>
                            </div>
                        </div>
                    @endcan

                    @can('manageFollowups', $lead)
                        <div class="crm-card">
                            <div class="crm-card-hd">
                                <span>Follow-ups</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#scheduleFollowUpModal">+ Add</button>
                            </div>
                            <div class="crm-card-bd pt-2 pb-2">
                                @forelse($upcomingFollowUps as $fu)
                                    <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                                        <div class="small">
                                            <div class="fw-semibold">{{ $fu->scheduled_at?->format('M j, g:i A') }}</div>
                                            <div class="text-muted">{{ $fu->status->label() }}</div>
                                        </div>
                                        @if(in_array($fu->status->value, ['pending', 'overdue'], true))
                                            <form method="POST" action="{{ route('admin.follow-ups.complete', $fu) }}" class="m-0">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success">Done</button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0 py-1">No follow-ups scheduled.</p>
                                @endforelse
                            </div>
                        </div>
                    @endcan

                    @if($me->can('assignAsMarketing', $lead) || $me->can('assignAsManager', $lead) || $me->can('takeBack', $lead))
                        <div class="crm-card">
                            <div class="crm-card-hd">Assign &amp; route</div>
                            <div class="crm-card-bd">
                                @can('assignAsMarketing', $lead)
                                    @if($inPool || ! $hasManager)
                                        <form method="POST" action="{{ route('admin.leads.assign-manager', $lead) }}" class="mb-3">
                                            @csrf
                                            <label class="form-label small fw-semibold mb-1">Assign manager</label>
                                            <select name="manager_id" class="form-select form-select-sm mb-2" required>
                                                <option value="">Select…</option>
                                                @foreach($assignableManagers as $m)
                                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm w-100">Assign</button>
                                        </form>
                                    @endif
                                    @if($hasManager && ! $atEmployee)
                                        <form method="POST" action="{{ route('admin.leads.reassign-manager', $lead) }}" class="mb-3">
                                            @csrf
                                            <label class="form-label small fw-semibold mb-1">Reassign manager</label>
                                            <select name="manager_id" class="form-select form-select-sm mb-2" required>
                                                @foreach($assignableManagers as $m)
                                                    <option value="{{ $m->id }}" @selected((int) $lead->sales_manager_id === (int) $m->id)>{{ $m->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">Reassign</button>
                                        </form>
                                    @endif
                                    @if(! $inPool)
                                        <form method="POST" action="{{ route('admin.leads.release', $lead) }}" onsubmit="return confirm('Return to unassigned pool?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Release to pool</button>
                                        </form>
                                    @endif
                                @endcan

                                @can('assignAsManager', $lead)
                                    @if(! $atEmployee)
                                        <form method="POST" action="{{ route('admin.leads.assign-employee', $lead) }}" class="mb-0 mt-2">
                                            @csrf
                                            <label class="form-label small fw-semibold mb-1">Assign to employee</label>
                                            <select name="employee_id" class="form-select form-select-sm mb-2" required>
                                                <option value="">Select…</option>
                                                @foreach($assignableEmployees as $e)
                                                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm w-100">Assign</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.leads.reassign-employee', $lead) }}" class="mb-0 mt-2">
                                            @csrf
                                            <label class="form-label small fw-semibold mb-1">Reassign employee</label>
                                            <select name="employee_id" class="form-select form-select-sm mb-2" required>
                                                @foreach($assignableEmployees as $e)
                                                    <option value="{{ $e->id }}" @selected((int) $lead->assigned_to === (int) $e->id)>{{ $e->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">Reassign</button>
                                        </form>
                                    @endif
                                @endcan

                                @can('takeBack', $lead)
                                    <form method="POST" action="{{ route('admin.leads.take-back', $lead) }}" class="mt-2" onsubmit="return confirm('Take this lead back from the employee?');">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm w-100">Take back from employee</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @can('logCall', $lead)
    <div class="modal fade" id="logCallModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.leads.calls.store', $lead) }}" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Log activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3 pt-2">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Outcome</label>
                        <select name="outcome" class="form-select" required>
                            @foreach(\App\Modules\Leads\Enums\CallOutcome::cases() as $outcome)
                                <option value="{{ $outcome->value }}">{{ $outcome->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Duration (sec)</label>
                        <input type="number" name="duration_seconds" class="form-control" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">When</label>
                        <input type="datetime-local" name="called_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="What was discussed…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    @can('manageFollowups', $lead)
    <div class="modal fade" id="scheduleFollowUpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.leads.follow-ups.store', $lead) }}" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Schedule follow-up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3 pt-2">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">When</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
@endsection
