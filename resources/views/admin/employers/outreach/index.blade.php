@extends('layouts.app')

@section('title', 'Company outreach leads')

@push('styles')
<style>
    .company-page { max-width: 1440px; margin: 0 auto; width: 100%; }
    .company-toolbar {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .75rem; margin-bottom: 1rem;
    }
    .company-toolbar-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .company-total-badge {
        font-size: .8rem; font-weight: 600; color: #475569;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
        padding: .35rem .85rem; display: inline-flex; align-items: center; gap: .35rem;
    }
    .company-total-badge strong { color: #0f172a; font-weight: 800; }
    .company-stage-strip {
        display: flex; gap: .45rem; overflow-x: auto; padding-bottom: .35rem;
        margin-bottom: 1rem; scrollbar-width: thin;
    }
    .company-stage-pill {
        flex-shrink: 0; display: inline-flex; align-items: center; gap: .4rem;
        padding: .38rem .7rem; border-radius: 999px; text-decoration: none;
        font-size: .74rem; font-weight: 600; color: #475569;
        background: #fff; border: 1px solid #e2e8f0;
        transition: border-color .15s, background .15s, color .15s, box-shadow .15s;
    }
    .company-stage-pill:hover { border-color: #6ee7b7; color: #047857; background: #f0fdf4; }
    .company-stage-pill.active {
        background: linear-gradient(135deg, #059669, #10b981);
        border-color: transparent; color: #fff;
        box-shadow: 0 4px 14px rgba(5, 150, 105, .35);
    }
    .company-stage-pill .count {
        font-size: .68rem; font-weight: 700; padding: .08rem .4rem; border-radius: 999px;
        background: rgba(15, 23, 42, .08);
    }
    .company-stage-pill.active .count { background: rgba(255,255,255,.22); }
    .company-filters-card {
        border: 1px solid rgba(15, 23, 42, .08); border-radius: 16px;
        background: #fff; margin-bottom: 1rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .04); overflow: hidden;
    }
    .company-filters-head {
        padding: .75rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(180deg, #f0fdf4, #fff);
    }
    .company-filters-head h2 { font-size: .85rem; font-weight: 700; margin: 0; color: #0f172a; }
    .company-filters-body { padding: 1rem 1.25rem 1.25rem; }
    .company-filters-body .form-label {
        font-size: .7rem; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; color: #64748b; margin-bottom: .35rem;
    }
    .company-filters-body .form-control,
    .company-filters-body .form-select { min-height: 40px; border-color: #e2e8f0; font-size: .875rem; }
    .company-active-filters { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; }
    .company-filter-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .75rem; font-weight: 600; color: #047857;
        background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 999px;
        padding: .25rem .65rem; text-decoration: none;
    }
    .company-filter-chip:hover { background: #d1fae5; color: #065f46; }
    .company-bulk-bar {
        border-radius: 14px; border: 1px solid rgba(15, 23, 42, .08);
        background: #fff; margin-bottom: 1rem;
        box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
        transition: border-color .2s, box-shadow .2s;
    }
    .company-bulk-bar:not(.is-idle) {
        border-color: rgba(5, 150, 105, .3);
        box-shadow: 0 8px 28px rgba(5, 150, 105, .12);
    }
    .company-bulk-bar.is-idle { opacity: .92; }
    .company-bulk-bar .bulk-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #d1fae5, #ecfdf5); color: #047857;
    }
    .company-table-card {
        border: 1px solid rgba(15, 23, 42, .08); border-radius: 16px;
        background: #fff; overflow: hidden;
        box-shadow: 0 8px 30px rgba(15, 23, 42, .06);
    }
    .company-table-head {
        padding: .85rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem;
        background: linear-gradient(180deg, #fafbfc, #fff);
    }
    .company-table-head h2 { font-size: .95rem; font-weight: 700; margin: 0; color: #0f172a; }
    .company-table { margin: 0; }
    .company-table thead th {
        font-size: .68rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: #64748b;
        background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        padding: .75rem 1rem; white-space: nowrap;
    }
    .company-table tbody td {
        padding: .85rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9;
    }
    .company-table tbody tr { transition: background .12s; }
    .company-table tbody tr:hover { background: #f8fafc; }
    .company-table tbody tr:last-child td { border-bottom: 0; }
    .co-avatar {
        width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; color: #fff;
        background: linear-gradient(135deg, #0ea5e9, #38bdf8);
    }
    .co-avatar.signed { background: linear-gradient(135deg, #059669, #34d399); }
    .co-avatar.lost { background: linear-gradient(135deg, #94a3b8, #cbd5e1); }
    .co-name { font-weight: 600; color: #0f172a; font-size: .9rem; line-height: 1.3; }
    .co-meta { font-size: .75rem; color: #64748b; }
    .co-industry {
        font-size: .68rem; font-weight: 600; color: #0369a1;
        background: #e0f2fe; border-radius: 6px; padding: .15rem .45rem;
        display: inline-block; margin-top: .2rem;
    }
    .badge-outreach-stage {
        font-size: .68rem; font-weight: 700; padding: .28rem .6rem; border-radius: 999px;
        border: 1px solid transparent; white-space: nowrap;
    }
    .badge-outreach-stage.s-new { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .badge-outreach-stage.s-called { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .badge-outreach-stage.s-follow_up { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .badge-outreach-stage.s-interested { background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe; }
    .badge-outreach-stage.s-signup_link_sent { background: #ecfeff; color: #0e7490; border-color: #a5f3fc; }
    .badge-outreach-stage.s-signed_up { background: #ecfdf5; color: #047857; border-color: #6ee7b7; }
    .badge-outreach-stage.s-not_interested { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .co-owner { font-size: .82rem; font-weight: 500; color: #334155; }
    .co-owner.none { color: #94a3b8; font-style: italic; font-weight: 400; }
    .co-owner-role { font-size: .65rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: .04em; }
    .btn-co-open {
        font-size: .78rem; font-weight: 600; padding: .35rem .85rem;
        border-radius: 999px; white-space: nowrap;
    }
    .company-empty {
        text-align: center; padding: 3.5rem 1.5rem; color: #64748b;
    }
    .company-empty-icon {
        width: 56px; height: 56px; margin: 0 auto 1rem; border-radius: 16px;
        background: #e0f2fe; color: #0284c7;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    @media (max-width: 768px) {
        .company-table thead { display: none; }
        .company-table tbody tr { display: block; padding: 1rem; border-bottom: 1px solid #f1f5f9; }
        .company-table tbody td { display: block; padding: .25rem 0; border: 0; text-align: left !important; }
        .company-table tbody td::before {
            content: attr(data-label);
            display: block; font-size: .65rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; color: #94a3b8; margin-bottom: .15rem;
        }
        .company-table tbody td.co-actions::before { display: none; }
    }
</style>
@endpush

@section('content')
    @php
        $me = auth('admin')->user();
        $filterBase = request()->except(['page', 'outreach_stage']);
        $stageTotal = array_sum($stageCounts ?? []);
        $lostCount = (int) ($stageCounts[\App\Enums\CompanyOutreachStage::NotInterested->value] ?? 0);
        $hasFilters = request()->filled('q') || request()->filled('outreach_stage') || request()->filled('assignment_status');
        $colspan = 7 + (($canBulkTeamLeads ?? false) || ($canBulkEmployees ?? false) ? 1 : 0);
    @endphp

    <form id="form-bulk-team-leads" method="POST" action="{{ route('admin.employers.outreach.bulk-assign-team-lead') }}" class="d-none">
        @csrf
        <input type="hidden" name="team_lead_id" value="">
    </form>
    <form id="form-bulk-employees" method="POST" action="{{ route('admin.employers.outreach.bulk-assign-employee') }}" class="d-none">
        @csrf
        <input type="hidden" name="employee_id" value="">
    </form>

    <div class="company-page">
        @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
        @include('partials.company-section-tabs', ['activeTab' => 'outreach'])

        @if(session('bulk_errors') && count(session('bulk_errors')))
            <div class="alert alert-warning border-0 shadow-sm mb-3">
                <div class="fw-semibold mb-1">Some leads could not be updated</div>
                <ul class="mb-0 small">
                    @foreach(session('bulk_errors') as $id => $err)
                        <li>#{{ $id }}: {{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="company-toolbar">
            <span class="company-total-badge">
                <i class="bi bi-person-plus text-info"></i>
                <strong>{{ number_format($leads->total()) }}</strong> outreach leads
                @if($leads->hasPages())
                    <span class="text-muted">· page {{ $leads->currentPage() }}</span>
                @endif
            </span>
            <div class="company-toolbar-actions">
                @can('leads.import')
                    <a href="{{ route('admin.employers.outreach.import') }}" class="btn btn-sm btn-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel/CSV
                    </a>
                    <a href="{{ route('admin.employers.outreach.template') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Template
                    </a>
                @endcan
                <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-buildings me-1"></i>Signed-up companies
                </a>
            </div>
        </div>

        <div class="company-stage-strip" role="navigation" aria-label="Filter by outreach stage">
            <a href="{{ route('admin.employers.outreach.index', $filterBase) }}"
               class="company-stage-pill @if(!request('outreach_stage')) active @endif">
                All <span class="count">{{ $stageTotal }}</span>
            </a>
            @foreach($outreachStages ?? [] as $stage)
                @php $count = (int) ($stageCounts[$stage->value] ?? 0); @endphp
                <a href="{{ route('admin.employers.outreach.index', array_merge($filterBase, ['outreach_stage' => $stage->value])) }}"
                   class="company-stage-pill @if(request('outreach_stage') === $stage->value) active @endif">
                    {{ $stage->label() }}
                    <span class="count">{{ $count }}</span>
                </a>
            @endforeach
            @if($lostCount > 0 || request('outreach_stage') === 'not_interested')
                <a href="{{ route('admin.employers.outreach.index', array_merge($filterBase, ['outreach_stage' => 'not_interested'])) }}"
                   class="company-stage-pill @if(request('outreach_stage') === 'not_interested') active @endif">
                    Not interested <span class="count">{{ $lostCount }}</span>
                </a>
            @endif
        </div>

        @if($hasFilters)
            <div class="company-active-filters">
                @if(request('q'))
                    <a class="company-filter-chip" href="{{ route('admin.employers.outreach.index', request()->except(['q', 'page'])) }}">
                        Search: {{ \Illuminate\Support\Str::limit(request('q'), 24) }} <i class="bi bi-x-lg"></i>
                    </a>
                @endif
                @if(request('outreach_stage'))
                    <a class="company-filter-chip" href="{{ route('admin.employers.outreach.index', request()->except(['outreach_stage', 'page'])) }}">
                        Stage: {{ $stageLabels[request('outreach_stage')] ?? request('outreach_stage') }} <i class="bi bi-x-lg"></i>
                    </a>
                @endif
                @if(request('assignment_status'))
                    <a class="company-filter-chip" href="{{ route('admin.employers.outreach.index', request()->except(['assignment_status', 'page'])) }}">
                        Assignment: {{ str_replace('_', ' ', request('assignment_status')) }} <i class="bi bi-x-lg"></i>
                    </a>
                @endif
                <a href="{{ route('admin.employers.outreach.index') }}" class="company-filter-chip bg-white text-secondary border-secondary-subtle">
                    Clear all
                </a>
            </div>
        @endif

        <div class="company-filters-card">
            <div class="company-filters-head">
                <h2><i class="bi bi-funnel me-2 text-success"></i>Filters</h2>
                @if($hasFilters)
                    <a href="{{ route('admin.employers.outreach.index') }}" class="btn btn-sm btn-link text-decoration-none">Reset</a>
                @endif
            </div>
            <form method="GET" action="{{ route('admin.employers.outreach.index') }}" class="company-filters-body">
                @if(request('outreach_stage'))
                    <input type="hidden" name="outreach_stage" value="{{ request('outreach_stage') }}">
                @endif
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="search" class="form-control border-start-0" name="q" value="{{ request('q') }}"
                                   placeholder="Company, contact, email, or phone">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Outreach stage</label>
                        <select class="form-select" name="outreach_stage">
                            <option value="">All stages</option>
                            @foreach($stageLabels ?? [] as $val => $label)
                                <option value="{{ $val }}" @selected(request('outreach_stage') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Assignment</label>
                        <select class="form-select" name="assignment_status">
                            <option value="">All</option>
                            @foreach(['new','assigned','in_progress','closed'] as $st)
                                <option value="{{ $st }}" @selected(request('assignment_status') === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-auto ms-lg-auto">
                        <button type="submit" class="btn btn-success w-100 w-lg-auto px-4">
                            <i class="bi bi-check2 me-1"></i>Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if($canBulkTeamLeads || $canBulkEmployees)
            <div id="bulk-bar" class="company-bulk-bar card is-idle">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="bulk-icon"><i class="bi bi-diagram-3"></i></div>
                            <div>
                                <div class="small text-uppercase fw-bold text-muted" style="letter-spacing:.06em">Bulk assign</div>
                                <div class="fw-semibold" id="bulk-countline">No leads selected.</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-end">
                            @if($canBulkTeamLeads)
                                <div>
                                    <label class="form-label small mb-1" for="bulk-team-lead-select">
                                        {{ ($isAsmActor ?? false) ? 'Team manager' : 'ASM / Manager' }}
                                    </label>
                                    <select id="bulk-team-lead-select" class="form-select form-select-sm" style="min-width:220px">
                                        <option value="">Select {{ ($isAsmActor ?? false) ? 'manager…' : 'ASM or manager…' }}</option>
                                        @foreach($assignableTeamLeads as $tl)
                                            <option value="{{ $tl->id }}">
                                                {{ $tl->name }}
                                                @if(! ($isAsmActor ?? false) && $tl->role === \App\Enums\AdminRole::Asm) (ASM) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="btn-bulk-team-lead" disabled>Assign</button>
                            @endif
                            @if($canBulkEmployees)
                                <div>
                                    <label class="form-label small mb-1" for="bulk-employee-select">Executive</label>
                                    <select id="bulk-employee-select" class="form-select form-select-sm" style="min-width:200px">
                                        <option value="">Select executive…</option>
                                        @foreach($assignableEmployees as $e)
                                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="btn-bulk-employee" disabled>Assign</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="company-table-card">
            <div class="company-table-head">
                <h2><i class="bi bi-person-plus me-2 text-info"></i>Outreach leads</h2>
                <span class="text-muted small">Companies not yet on Hirevo</span>
            </div>
            <div class="table-responsive">
                <table class="table company-table mb-0">
                    <thead>
                    <tr>
                        @if($canBulkTeamLeads || $canBulkEmployees)
                            <th style="width:2.5rem">
                                <input type="checkbox" class="form-check-input" id="check-all" title="Select all on this page">
                            </th>
                        @endif
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Stage</th>
                        <th>Owner</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($leads as $lead)
                        @php
                            $isSignedUp = $lead->outreach_stage === 'signed_up';
                            $isLost = $lead->outreach_stage === 'not_interested';
                        @endphp
                        <tr>
                            @if($canBulkTeamLeads || $canBulkEmployees)
                                <td data-label="">
                                    <input type="checkbox" class="form-check-input lead-check" value="{{ $lead->id }}">
                                </td>
                            @endif
                            <td data-label="Company">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="co-avatar @if($isSignedUp) signed @elseif($isLost) lost @endif">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="co-name">{{ $lead->company_name }}</div>
                                        <div class="co-meta">{{ $lead->location ?? '—' }}</div>
                                        @if($lead->industry)
                                            <span class="co-industry">{{ $lead->industry }}</span>
                                        @endif
                                        @if($lead->source)
                                            <div class="co-meta mt-1"><i class="bi bi-signpost-2 me-1"></i>{{ $lead->source }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Contact">
                                <div class="fw-medium" style="font-size:.85rem">{{ $lead->contact_name ?? '—' }}</div>
                                <div class="co-meta text-truncate" style="max-width:180px">
                                    @if($lead->phone)
                                        <i class="bi bi-telephone me-1"></i>{{ $lead->phone }}
                                    @elseif($lead->email)
                                        {{ $lead->email }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </td>
                            <td data-label="Stage">
                                <span class="badge-outreach-stage s-{{ $lead->outreach_stage }}">
                                    {{ $stageLabels[$lead->outreach_stage] ?? $lead->outreach_stage }}
                                </span>
                                @if($lead->follow_up_at && $lead->follow_up_at->isFuture())
                                    <div class="co-meta mt-1"><i class="bi bi-calendar-event me-1"></i>{{ $lead->follow_up_at->format('M j') }}</div>
                                @endif
                            </td>
                            <td data-label="Owner">
                                @if($lead->assignedTo)
                                    <div class="co-owner">{{ $lead->assignedTo->name }}</div>
                                    @if($lead->assignedTo->role === \App\Enums\AdminRole::Asm)
                                        <div class="co-owner-role">ASM</div>
                                    @elseif($lead->assignedTo->role === \App\Enums\AdminRole::SalesManager)
                                        <div class="co-owner-role">Manager</div>
                                    @elseif($lead->assignedTo->role === \App\Enums\AdminRole::SalesEmployee)
                                        <div class="co-owner-role">Executive</div>
                                    @endif
                                @else
                                    <span class="co-owner none">Unassigned</span>
                                @endif
                            </td>
                            <td data-label="Updated" class="small text-muted text-nowrap">
                                {{ $lead->updated_at?->diffForHumans(short: true) }}
                            </td>
                            <td data-label="" class="text-end co-actions">
                                <a href="{{ route('admin.employers.outreach.show', $lead) }}" class="btn btn-success btn-co-open">
                                    Open <i class="bi bi-arrow-right-short"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $colspan }}">
                                <div class="company-empty">
                                    <div class="company-empty-icon"><i class="bi bi-person-plus"></i></div>
                                    <div class="fw-semibold text-dark mb-1">No outreach leads yet</div>
                                    <p class="small mb-3">Import companies from Excel to start calling and driving signups.</p>
                                    @can('leads.import')
                                        <a href="{{ route('admin.employers.outreach.import') }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel/CSV
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.crm-pagination-footer', ['paginator' => $leads])
        </div>
    </div>

    @if($canBulkTeamLeads || $canBulkEmployees)
    <script>
        (function () {
            const checks = document.querySelectorAll('.lead-check');
            const countLine = document.getElementById('bulk-countline');
            const bulkBar = document.getElementById('bulk-bar');
            const checkAll = document.getElementById('check-all');
            function selectedIds() {
                return Array.from(checks).filter(c => c.checked).map(c => c.value);
            }
            function refresh() {
                const n = selectedIds().length;
                if (countLine) {
                    countLine.innerHTML = n === 0
                        ? 'No leads selected.'
                        : '<strong>' + n + '</strong> ' + (n === 1 ? 'lead' : 'leads') + ' selected';
                }
                bulkBar?.classList.toggle('is-idle', n === 0);
                const tlSel = document.getElementById('bulk-team-lead-select');
                const eSel = document.getElementById('bulk-employee-select');
                document.getElementById('btn-bulk-team-lead')?.toggleAttribute('disabled', n === 0 || !tlSel?.value);
                document.getElementById('btn-bulk-employee')?.toggleAttribute('disabled', n === 0 || !eSel?.value);
            }
            checks.forEach(c => c.addEventListener('change', refresh));
            document.getElementById('bulk-team-lead-select')?.addEventListener('change', refresh);
            document.getElementById('bulk-employee-select')?.addEventListener('change', refresh);
            checkAll?.addEventListener('change', () => { checks.forEach(c => { c.checked = checkAll.checked; }); refresh(); });
            function submitBulk(formId, fieldName, selectId, arrayName) {
                const form = document.getElementById(formId);
                const val = document.getElementById(selectId)?.value;
                const ids = selectedIds();
                if (!val || !ids.length) return;
                form.querySelector(`[name="${fieldName}"]`).value = val;
                form.querySelectorAll(`input[name="${arrayName}[]"]`).forEach(el => el.remove());
                ids.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = arrayName + '[]';
                    input.value = id;
                    form.appendChild(input);
                });
                form.submit();
            }
            document.getElementById('btn-bulk-team-lead')?.addEventListener('click', () => submitBulk('form-bulk-team-leads', 'team_lead_id', 'bulk-team-lead-select', 'lead_ids'));
            document.getElementById('btn-bulk-employee')?.addEventListener('click', () => submitBulk('form-bulk-employees', 'employee_id', 'bulk-employee-select', 'lead_ids'));
            refresh();
        })();
    </script>
    @endif
@endsection
