@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    @php
        $me = auth('admin')->user();
        $canSeeConsultations = $me->hasAnyRole([\App\Enums\AdminRole::Admin, \App\Enums\AdminRole::Marketing]);
        $activeTab = ($activeTab ?? 'leads') === 'consultations' ? 'consultations' : 'leads';
        $listRoute = $activeTab === 'consultations' ? route('admin.consultations.index') : route('admin.leads.index');
        $leadDot = ($leadAttentionCount ?? 0) > 0;
        $consultDot = ($pendingConsultationCount ?? 0) > 0;
        $canBulkManagers = $me->hasAnyRole([\App\Enums\AdminRole::Admin, \App\Enums\AdminRole::Marketing]);
        $canBulkEmployees = $me->role === \App\Enums\AdminRole::SalesManager;
        $isMarketing = $me->role === \App\Enums\AdminRole::Marketing;
    @endphp

    <style>
        .leads-shell { max-width: 1200px; margin: 0 auto; }
        .leads-top-filters .form-label { font-size: .72rem; font-weight: 600; color: #64748b; margin-bottom: .35rem; }
        .leads-top-filters .form-select,
        .leads-top-filters .form-control { min-height: 38px; }
        .leads-tab-btn { position: relative; border-radius: 999px !important; padding: 0.5rem 1.1rem !important; font-weight: 600; }
        .leads-tab-btn .notify-dot {
            position: absolute;
            top: 6px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px rgba(239,68,68,.35);
        }
        .leads-table th { font-size: 0.72rem; letter-spacing: 0.04em; }
        .leads-meta { font-size: 0.8rem; color: #6b7280; }
        .leads-bulk-bar {
            position: static;
            width: 100%;
            margin-bottom: 1rem;
            border-radius: 14px !important;
            border: 1px solid rgba(15, 23, 42, .08) !important;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, .06), 0 2px 4px -2px rgba(15, 23, 42, .06) !important;
            transition: box-shadow .22s ease, border-color .22s ease;
        }
        .leads-bulk-bar:not(.is-idle) {
            border-color: rgba(37, 99, 235, .22) !important;
            box-shadow: 0 12px 24px -4px rgba(37, 99, 235, .12), 0 20px 40px -8px rgba(15, 23, 42, .14) !important;
        }
        .leads-bulk-bar.is-idle { opacity: .88; }
        .leads-bulk-bar .bulk-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37,99,235,.12), rgba(59,130,246,.08));
            color: #1d4ed8;
            flex-shrink: 0;
        }
        .leads-bulk-bar .bulk-kicker { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
        .leads-bulk-bar .bulk-title { font-size: .95rem; font-weight: 700; color: #0f172a; letter-spacing: -.01em; }
        .leads-bulk-bar .bulk-countline { font-size: .875rem; color: #475569; }
        .leads-bulk-bar .bulk-countline strong { color: #0f172a; font-weight: 700; }
        .leads-bulk-bar .bulk-hint { font-size: .75rem; color: #94a3b8; margin-top: 2px; }
        .leads-bulk-bar .form-label { font-size: .72rem; font-weight: 600; letter-spacing: .02em; color: #64748b; }
        .leads-bulk-bar .form-select { border-radius: 10px; border-color: #e2e8f0; min-width: 240px; }
        .leads-bulk-bar .btn-apply { font-weight: 600; letter-spacing: .02em; padding: .5rem 1.25rem; }
    </style>

    <form id="form-bulk-managers" method="POST" action="{{ route('admin.leads.bulk-assign-manager') }}" class="d-none">@csrf<input type="hidden" name="manager_id" value=""></form>
    <form id="form-bulk-employees" method="POST" action="{{ route('admin.leads.bulk-assign-employee') }}" class="d-none">@csrf<input type="hidden" name="employee_id" value=""></form>

    <div class="leads-shell">
        <div class="page-header flex-wrap">
            <div>
                <h1 class="page-title">Leads</h1>
                <p class="page-subtitle mb-0">Review product leads @if($canSeeConsultations) and consultation requests @endif in one place.</p>
            </div>
        </div>

        @if(session('bulk_errors') && count(session('bulk_errors')))
            <div class="alert alert-warning border-0 shadow-sm mb-3">
                <div class="fw-semibold mb-1">Some rows could not be updated</div>
                <ul class="mb-0 small">
                    @foreach(session('bulk_errors') as $leadId => $err)
                        <li>Lead #{{ $leadId }}: {{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($activeTab === 'leads')
            <form method="GET" action="{{ route('admin.leads.index') }}" class="card shadow-soft border-0 mb-3 leads-top-filters">
                <div class="card-body py-3">
                    <div class="row g-2 g-lg-3 align-items-end">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="search" class="form-control border-start-0" name="q" value="{{ request('q') }}" placeholder="Search by name, email, or phone">
                            </div>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label">Assignment workflow</label>
                            <select class="form-select" name="assignment_status">
                                <option value="">All workflows</option>
                                <option value="new" @selected(request('assignment_status') === 'new')>New</option>
                                <option value="assigned" @selected(request('assignment_status') === 'assigned')>Assigned</option>
                                <option value="in_progress" @selected(request('assignment_status') === 'in_progress')>In progress</option>
                                <option value="closed" @selected(request('assignment_status') === 'closed')>Closed</option>
                            </select>
                        </div>
                        @if($showAssigneeFilter)
                            <div class="col-6 col-lg-2">
                                <label class="form-label">Assigned to</label>
                                <select class="form-select" name="assignee_id">
                                    <option value="">Anyone</option>
                                    @if($me->hasAnyRole([\App\Enums\AdminRole::Admin, \App\Enums\AdminRole::Marketing]))
                                        <option value="unassigned" @selected(request('assignee_id') === 'unassigned')>Unassigned</option>
                                        <optgroup label="Sales managers">
                                            @foreach($assignableManagers as $m)
                                                <option value="{{ $m->id }}" @selected(request('assignee_id') == (string) $m->id)>{{ $m->name }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Sales employees">
                                            @foreach($assigneeFilterEmployees as $e)
                                                <option value="{{ $e->id }}" @selected(request('assignee_id') == (string) $e->id)>{{ $e->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @elseif($me->role === \App\Enums\AdminRole::SalesManager)
                                        <option value="{{ $me->id }}" @selected(request('assignee_id') == (string) $me->id)>Me</option>
                                        @foreach($assigneeFilterEmployees as $e)
                                            <option value="{{ $e->id }}" @selected(request('assignee_id') == (string) $e->id)>{{ $e->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        @endif
                        <div class="col-6 col-lg-2">
                            <label class="form-label">CRM stage</label>
                            <select class="form-select" name="mgmt_stage">
                                <option value="">All stages</option>
                                @foreach($managementStages as $stage)
                                    <option value="{{ $stage }}" @selected(request('mgmt_stage') === $stage)>{{ ($crmStageLabels[$stage] ?? $stage) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label">Product status</label>
                            <select class="form-select" name="status">
                                <option value="">All statuses</option>
                                @foreach(['available','bidding','sold','contact_unlocked'] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">Apply filters</button>
                            @if(request()->filled('q') || request()->filled('status') || request()->filled('assignment_status') || request()->filled('mgmt_stage') || request()->filled('assignee_id'))
                                <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">Reset</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        @else
            <form method="GET" action="{{ $listRoute }}" class="card shadow-soft border-0 mb-3">
                <div class="card-body py-3 d-flex flex-wrap gap-2 align-items-center">
                    @foreach(['consultation_status'] as $k)
                        @if(request()->filled($k))
                            <input type="hidden" name="{{ $k }}" value="{{ request($k) }}">
                        @endif
                    @endforeach
                    <div class="flex-grow-1" style="min-width: 220px;">
                        <label class="visually-hidden">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="search" class="form-control border-start-0" name="q" value="{{ request('q') }}" placeholder="Search by name, email, or phone">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Search</button>
                    @if(request()->filled('q') || request()->filled('consultation_status'))
                        <a href="{{ $listRoute }}" class="btn btn-outline-secondary">Reset filters</a>
                    @endif
                </div>
            </form>
        @endif

        {{-- Lead funnel tab --}}
        @if(!$canSeeConsultations || $activeTab === 'leads')
            <div class="tab-pane fade show active">
                @if($canBulkManagers)
                    <div id="bulk-bar-managers" class="leads-bulk-bar card is-idle" role="region" aria-label="Assign leads to a sales manager">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-column flex-lg-row gap-3 gap-lg-4 align-items-stretch align-items-lg-center justify-content-between">
                                <div class="d-flex gap-3 align-items-start min-w-0">
                                    <div class="bulk-icon" aria-hidden="true"><i class="bi bi-diagram-3 fs-5"></i></div>
                                    <div class="min-w-0">
                                        <div class="bulk-kicker mb-1">@if($isMarketing) Marketing @else Admin @endif</div>
                                        <div class="bulk-title">
                                            @if($isMarketing)
                                                Forward leads to a sales manager
                                            @else
                                                Assign leads to a sales manager
                                            @endif
                                        </div>
                                        <p class="bulk-countline mb-0 mt-1 text-muted" id="bulk-m-countline">No leads selected.</p>
                                        <p class="bulk-hint mb-0">
                                            @if($isMarketing)
                                                Hand leads to the sales desk: choose one manager and confirm. New routing and re-routing use the same control.
                                            @else
                                                Send or move leads to any sales manager. Applies to the visible page only.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 align-items-stretch align-items-sm-end flex-shrink-0">
                                    <div>
                                        <label class="form-label mb-1" for="bulk-manager-select">Sales manager</label>
                                        <select class="form-select form-select-sm" id="bulk-manager-select">
                                            <option value="">Select sales manager…</option>
                                            @foreach($assignableManagers as $m)
                                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-apply rounded-3 shadow-sm" id="bulk-m-submit">Confirm assignment</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($canBulkEmployees)
                    <div id="bulk-bar-employees" class="leads-bulk-bar card is-idle" role="region" aria-label="Assign leads to sales employees">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-column flex-lg-row gap-3 gap-lg-4 align-items-stretch align-items-lg-center justify-content-between">
                                <div class="d-flex gap-3 align-items-start min-w-0">
                                    <div class="bulk-icon" aria-hidden="true"><i class="bi bi-person-lines-fill fs-5"></i></div>
                                    <div class="min-w-0">
                                        <div class="bulk-kicker mb-1">Sales manager</div>
                                        <div class="bulk-title">Assign to your sales employees</div>
                                        <p class="bulk-countline mb-0 mt-1 text-muted" id="bulk-e-countline">No leads selected.</p>
                                        <p class="bulk-hint mb-0">Only your direct reports appear here. Each confirmation sets or moves ownership for the selected leads (this page).</p>
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 align-items-stretch align-items-sm-end flex-shrink-0">
                                    <div>
                                        <label class="form-label mb-1" for="bulk-employee-select">Sales employee</label>
                                        <select class="form-select form-select-sm" id="bulk-employee-select">
                                            <option value="">Select sales employee…</option>
                                            @foreach($assignableEmployees as $e)
                                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-apply rounded-3 shadow-sm" id="bulk-e-submit">Confirm assignment</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card shadow-soft border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle leads-table mb-0" id="leads-table">
                            <thead class="table-light">
                            <tr>
                                @if($canBulkManagers || $canBulkEmployees)
                                    <th class="border-end-0" style="width: 2.5rem;">
                                        <input type="checkbox" class="form-check-input" id="lead-select-all" title="Select all on this page" aria-label="Select all on this page">
                                    </th>
                                @endif
                                <th>Candidate</th>
                                <th>Product</th>
                                <th>Assignment</th>
                                <th>CRM</th>
                                <th class="text-end">Score</th>
                                <th>Updated</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($leads as $lead)
                                @php
                                    $leadScore = (int) ($lead->match_percentage ?? 0) + (int) ($lead->intent_score ?? 0);
                                @endphp
                                <tr>
                                    @if($canBulkManagers || $canBulkEmployees)
                                        <td class="border-end-0 align-middle">
                                            <input type="checkbox" class="form-check-input lead-bulk-cb" value="{{ $lead->id }}" aria-label="Select lead {{ $lead->id }}">
                                        </td>
                                    @endif
                                    <td>
                                        <div class="fw-semibold">{{ $lead->candidate?->name ?? '—' }}</div>
                                        <div class="leads-meta text-truncate" style="max-width: 220px;">{{ $lead->candidate?->email ?? '—' }}</div>
                                    </td>
                                    <td><span class="badge rounded-pill text-bg-light text-dark border">{{ str_replace('_', ' ', $lead->status) }}</span></td>
                                    <td>
                                        <div class="small"><span class="text-muted">Flow:</span> {{ str_replace('_', ' ', $lead->assignment_status?->value ?? '—') }}</div>
                                        <div class="small"><span class="text-muted">Owner:</span> {{ $lead->assignedTo?->name ?? 'Unassigned' }}</div>
                                    </td>
                                    <td><span class="badge text-bg-primary">{{ $crmStageLabels[$lead->adminStage?->stage ?? 'new'] ?? 'New' }}</span></td>
                                    <td class="text-end fw-semibold">{{ $leadScore }}</td>
                                    <td class="small text-muted text-nowrap">{{ $lead->updated_at?->format('M j, H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.leads.show', $lead->id) }}" class="btn btn-sm btn-primary rounded-pill px-3">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ ($canBulkManagers || $canBulkEmployees) ? 8 : 7 }}" class="text-center text-muted py-5">No leads match your filters.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-center">{{ $leads->withQueryString()->links() }}</div>

                @if($canBulkManagers || $canBulkEmployees)
                    <script>
                        (function () {
                            const cbs = document.querySelectorAll('.lead-bulk-cb');
                            const all = document.getElementById('lead-select-all');
                            function count() { return document.querySelectorAll('.lead-bulk-cb:checked').length; }
                            function formatCountLine(n) {
                                if (n === 0) {
                                    return '<span class="text-muted">No leads selected.</span>';
                                }
                                const noun = n === 1 ? 'lead' : 'leads';
                                return '<strong>' + n + '</strong> ' + noun + ' selected';
                            }
                            function refresh() {
                                const n = count();
                                const mBar = document.getElementById('bulk-bar-managers');
                                const eBar = document.getElementById('bulk-bar-employees');
                                const mLine = document.getElementById('bulk-m-countline');
                                const eLine = document.getElementById('bulk-e-countline');
                                const mSel = document.getElementById('bulk-manager-select');
                                const eSel = document.getElementById('bulk-employee-select');
                                const mBtn = document.getElementById('bulk-m-submit');
                                const eBtn = document.getElementById('bulk-e-submit');
                                if (mLine) {
                                    mLine.innerHTML = formatCountLine(n);
                                    mLine.classList.toggle('text-muted', n === 0);
                                }
                                if (eLine) {
                                    eLine.innerHTML = formatCountLine(n);
                                    eLine.classList.toggle('text-muted', n === 0);
                                }
                                if (mBar) mBar.classList.toggle('is-idle', n === 0);
                                if (eBar) eBar.classList.toggle('is-idle', n === 0);
                                if (mBtn) mBtn.disabled = n === 0 || !(mSel && mSel.value);
                                if (eBtn) eBtn.disabled = n === 0 || !(eSel && eSel.value);
                            }
                            cbs.forEach(cb => cb.addEventListener('change', refresh));
                            document.getElementById('bulk-manager-select')?.addEventListener('change', refresh);
                            document.getElementById('bulk-employee-select')?.addEventListener('change', refresh);
                            if (all) {
                                all.addEventListener('change', function () {
                                    cbs.forEach(cb => { cb.checked = all.checked; });
                                    refresh();
                                });
                            }
                            function fillAndSubmit(formId, ids, field, val) {
                                const form = document.getElementById(formId);
                                form.querySelectorAll('input[name="lead_ids[]"]').forEach(el => el.remove());
                                ids.forEach(id => {
                                    const i = document.createElement('input');
                                    i.type = 'hidden';
                                    i.name = 'lead_ids[]';
                                    i.value = id;
                                    form.appendChild(i);
                                });
                                form.querySelector('input[name="' + field + '"]').value = val;
                                form.submit();
                            }
                            document.getElementById('bulk-m-submit')?.addEventListener('click', function () {
                                const sel = document.getElementById('bulk-manager-select');
                                const ids = [...document.querySelectorAll('.lead-bulk-cb:checked')].map(c => c.value);
                                if (!ids.length) { alert('Select one or more leads in the table first.'); return; }
                                if (!sel.value) { alert('Choose which sales manager should receive these leads.'); return; }
                                fillAndSubmit('form-bulk-managers', ids, 'manager_id', sel.value);
                            });
                            document.getElementById('bulk-e-submit')?.addEventListener('click', function () {
                                const sel = document.getElementById('bulk-employee-select');
                                const ids = [...document.querySelectorAll('.lead-bulk-cb:checked')].map(c => c.value);
                                if (!ids.length) { alert('Select one or more leads in the table first.'); return; }
                                if (!sel.value) { alert('Choose which team member should own these leads.'); return; }
                                fillAndSubmit('form-bulk-employees', ids, 'employee_id', sel.value);
                            });
                            refresh();
                        })();
                    </script>
                @endif
            </div>
        @endif

        @if($canSeeConsultations && $consultations !== null && $activeTab === 'consultations')
            <div class="tab-pane fade show active">
                <div class="alert alert-info border-0 shadow-sm mb-3 d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle mt-1"></i>
                    <div>
                        <strong>Assignments</strong> apply to product leads only. Use the <a href="{{ route('admin.leads.index') }}" class="alert-link">Lead funnel</a> page, select rows, then use the assignment panel below the filters to assign in bulk.
                    </div>
                </div>
                <form method="GET" action="{{ route('admin.consultations.index') }}" class="card shadow-soft border-0 mb-3">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small text-muted mb-1">Consultation status</label>
                                <select class="form-select form-select-sm" name="consultation_status" onchange="this.form.submit()">
                                    <option value="">All statuses</option>
                                    @foreach(['pending','in_progress','completed','cancelled'] as $status)
                                        <option value="{{ $status }}" @selected(request('consultation_status') === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-3 ms-md-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="card shadow-soft border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th class="text-end">Match</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($consultations as $consultation)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $consultation->user?->name ?? '—' }}</div>
                                        <div class="leads-meta">{{ $consultation->user?->email ?? '' }}</div>
                                    </td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $consultation->source ?? '—') }}</td>
                                    <td><span class="badge rounded-pill text-bg-light text-dark border">{{ str_replace('_', ' ', $consultation->status) }}</span></td>
                                    <td class="text-end">{{ (int) ($consultation->match_percentage ?? 0) }}%</td>
                                    <td class="small text-muted text-nowrap">{{ $consultation->created_at?->format('M j, H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.consultations.show', $consultation->id) }}" class="btn btn-sm btn-primary rounded-pill px-3">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">No consultation requests found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-center">{{ $consultations->withQueryString()->links() }}</div>
            </div>
        @endif
    </div>
@endsection
