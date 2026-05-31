@extends('layouts.app')

@section('title', 'Leads')

@push('styles')
<style>
    .leads-page { max-width: 1440px; margin: 0 auto; }
    .leads-toolbar {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .75rem; margin-bottom: 1rem;
    }
    .leads-toolbar-actions { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .leads-total-badge {
        font-size: .8rem; font-weight: 600; color: #475569;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
        padding: .35rem .85rem; display: inline-flex; align-items: center; gap: .35rem;
    }
    .leads-total-badge strong { color: #0f172a; font-weight: 800; }
    .leads-stage-strip {
        display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .35rem;
        margin-bottom: 1rem; scrollbar-width: thin;
    }
    .leads-stage-pill {
        flex-shrink: 0; display: inline-flex; align-items: center; gap: .45rem;
        padding: .4rem .75rem; border-radius: 999px; text-decoration: none;
        font-size: .78rem; font-weight: 600; color: #475569;
        background: #fff; border: 1px solid #e2e8f0;
        transition: border-color .15s, background .15s, color .15s, box-shadow .15s;
    }
    .leads-stage-pill:hover { border-color: #93c5fd; color: #1d4ed8; background: #f8fafc; }
    .leads-stage-pill.active {
        background: linear-gradient(135deg, #2563eb, #3b82f6); border-color: transparent;
        color: #fff; box-shadow: 0 4px 14px rgba(37, 99, 235, .35);
    }
    .leads-stage-pill .count {
        font-size: .7rem; font-weight: 700; padding: .1rem .45rem; border-radius: 999px;
        background: rgba(15, 23, 42, .08); color: inherit;
    }
    .leads-stage-pill.active .count { background: rgba(255,255,255,.22); }
    .leads-filters-card {
        border: 1px solid rgba(15, 23, 42, .08); border-radius: 16px;
        background: #fff; margin-bottom: 1rem; overflow: hidden;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .04);
    }
    .leads-filters-head {
        padding: .75rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        background: linear-gradient(180deg, #fafbfc, #fff);
    }
    .leads-filters-head h2 { font-size: .85rem; font-weight: 700; margin: 0; color: #0f172a; }
    .leads-filters-body { padding: 1rem 1.25rem 1.25rem; }
    .leads-filters-body .form-label {
        font-size: .7rem; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; color: #64748b; margin-bottom: .35rem;
    }
    .leads-filters-body .form-control,
    .leads-filters-body .form-select { min-height: 40px; border-color: #e2e8f0; font-size: .875rem; }
    .leads-filters-body .input-group-text { border-color: #e2e8f0; background: #f8fafc; }
    .leads-active-filters { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; }
    .leads-filter-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .75rem; font-weight: 600; color: #1e40af;
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 999px;
        padding: .25rem .65rem; text-decoration: none;
    }
    .leads-filter-chip:hover { background: #dbeafe; color: #1d4ed8; }
    .leads-filter-chip i { font-size: .85rem; opacity: .7; }
    .leads-bulk-bar {
        border-radius: 14px !important; border: 1px solid rgba(15, 23, 42, .08) !important;
        background: #fff !important; margin-bottom: 1rem;
        box-shadow: 0 4px 16px rgba(15, 23, 42, .05) !important;
        transition: border-color .2s, box-shadow .2s;
    }
    .leads-bulk-bar:not(.is-idle) {
        border-color: rgba(37, 99, 235, .25) !important;
        box-shadow: 0 8px 28px rgba(37, 99, 235, .1) !important;
    }
    .leads-bulk-bar.is-idle { opacity: .92; }
    .leads-bulk-bar .bulk-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8;
    }
    .leads-bulk-bar .bulk-kicker { font-size: .65rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
    .leads-bulk-bar .bulk-title { font-size: .9rem; font-weight: 700; color: #0f172a; }
    .leads-table-card {
        border: 1px solid rgba(15, 23, 42, .08); border-radius: 16px;
        background: #fff; overflow: hidden;
        box-shadow: 0 8px 30px rgba(15, 23, 42, .06);
    }
    .leads-table-head {
        padding: .85rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem;
        background: linear-gradient(180deg, #fafbfc, #fff);
    }
    .leads-table-head h2 { font-size: .95rem; font-weight: 700; margin: 0; color: #0f172a; }
    .leads-table { margin: 0; }
    .leads-table thead th {
        font-size: .68rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: #64748b;
        background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        padding: .75rem 1rem; white-space: nowrap;
    }
    .leads-table tbody td { padding: .85rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .leads-table tbody tr { transition: background .12s; }
    .leads-table tbody tr:hover { background: #f8fafc; }
    .leads-table tbody tr:last-child td { border-bottom: 0; }
    .lead-avatar {
        width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; font-weight: 800; color: #fff; letter-spacing: -.02em;
    }
    .lead-avatar.c0 { background: linear-gradient(135deg, #3b82f6, #6366f1); }
    .lead-avatar.c1 { background: linear-gradient(135deg, #8b5cf6, #a855f7); }
    .lead-avatar.c2 { background: linear-gradient(135deg, #06b6d4, #0ea5e9); }
    .lead-avatar.c3 { background: linear-gradient(135deg, #10b981, #14b8a6); }
    .lead-avatar.c4 { background: linear-gradient(135deg, #f59e0b, #f97316); }
    .lead-avatar.c5 { background: linear-gradient(135deg, #ec4899, #f43f5e); }
    .lead-name { font-weight: 600; color: #0f172a; font-size: .9rem; line-height: 1.3; }
    .lead-email { font-size: .78rem; color: #64748b; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lead-phone { font-size: .72rem; color: #94a3b8; }
    .badge-stage {
        font-size: .7rem; font-weight: 700; padding: .3rem .65rem; border-radius: 999px;
        border: 1px solid transparent; text-transform: capitalize;
    }
    .badge-stage.stage-new { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .badge-stage.stage-called { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .badge-stage.stage-follow_up { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .badge-stage.stage-dnp { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .badge-stage.stage-interested { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .badge-stage.stage-upskill_needed { background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe; }
    .badge-stage.stage-applied { background: #ecfeff; color: #0e7490; border-color: #a5f3fc; }
    .badge-stage.stage-referred { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
    .badge-stage.stage-interview { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
    .badge-stage.stage-hired { background: #ecfdf5; color: #065f46; border-color: #6ee7b7; }
    .badge-stage.stage-lost { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .badge-product {
        font-size: .68rem; font-weight: 600; padding: .25rem .55rem; border-radius: 6px;
        background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;
        text-transform: capitalize;
    }
    .badge-assign {
        font-size: .68rem; font-weight: 600; padding: .2rem .5rem; border-radius: 6px;
    }
    .badge-assign.assigned { background: #ecfdf5; color: #047857; }
    .badge-assign.new { background: #f1f5f9; color: #64748b; }
    .badge-assign.in_progress { background: #eff6ff; color: #1d4ed8; }
    .lead-score-wrap { min-width: 72px; text-align: right; }
    .lead-score-num { font-size: 1rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em; line-height: 1; }
    .lead-score-bar {
        height: 4px; border-radius: 999px; background: #e2e8f0; margin-top: 4px; overflow: hidden;
    }
    .lead-score-bar span {
        display: block; height: 100%; border-radius: 999px;
        background: linear-gradient(90deg, #3b82f6, #6366f1);
    }
    .lead-owner { font-size: .82rem; font-weight: 500; color: #334155; }
    .lead-owner.unassigned { color: #94a3b8; font-style: italic; font-weight: 400; }
    .btn-lead-open {
        font-size: .78rem; font-weight: 600; padding: .35rem .85rem;
        border-radius: 999px; white-space: nowrap;
    }
    .leads-empty {
        text-align: center; padding: 3.5rem 1.5rem; color: #64748b;
    }
    .leads-empty-icon {
        width: 56px; height: 56px; margin: 0 auto 1rem; border-radius: 16px;
        background: #f1f5f9; color: #94a3b8;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    @media (max-width: 768px) {
        .leads-table thead { display: none; }
        .leads-table tbody tr {
            display: block; padding: 1rem; border-bottom: 1px solid #f1f5f9;
        }
        .leads-table tbody td {
            display: block; padding: .25rem 0; border: 0; text-align: left !important;
        }
        .leads-table tbody td::before {
            content: attr(data-label);
            display: block; font-size: .65rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; color: #94a3b8; margin-bottom: .15rem;
        }
        .leads-table tbody td.lead-actions { margin-top: .5rem; }
        .leads-table tbody td.lead-actions::before { display: none; }
    }
</style>
@endpush

@section('content')
    @php
        $me = auth('admin')->user();
        $canSeeConsultations = $me->hasAnyRole([\App\Enums\AdminRole::Admin, \App\Enums\AdminRole::Marketing]);
        $activeTab = ($activeTab ?? 'leads') === 'consultations' ? 'consultations' : 'leads';
        $listRoute = $activeTab === 'consultations' ? route('admin.consultations.index') : route('admin.leads.index');
        $canBulkManagers = $me->hasAnyRole([\App\Enums\AdminRole::Admin, \App\Enums\AdminRole::Marketing]);
        $canBulkEmployees = $me->role === \App\Enums\AdminRole::SalesManager;
        $isMarketing = $me->role === \App\Enums\AdminRole::Marketing;
        $hasFilters = request()->filled('q') || request()->filled('status') || request()->filled('assignment_status')
            || request()->filled('mgmt_stage') || request()->filled('assignee_id');
        $filterBase = request()->except(['leads_page', 'mgmt_stage']);
        $totalLeads = $leads->total();
        $stageTotal = array_sum($crmStageCounts ?? []);
    @endphp

    <form id="form-bulk-managers" method="POST" action="{{ route('admin.leads.bulk-assign-manager') }}" class="d-none">@csrf<input type="hidden" name="manager_id" value=""></form>
    <form id="form-bulk-employees" method="POST" action="{{ route('admin.leads.bulk-assign-employee') }}" class="d-none">@csrf<input type="hidden" name="employee_id" value=""></form>

    <div class="leads-page">
        @isset($pipeline)
            @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
        @else
            <div class="page-header flex-wrap mb-3">
                <div>
                    <h1 class="page-title">Talent pipeline</h1>
                    <p class="page-subtitle mb-0">Candidate leads @if($canSeeConsultations) and consultations @endif</p>
                </div>
            </div>
        @endisset

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
            <div class="leads-toolbar">
                <span class="leads-total-badge">
                    <i class="bi bi-people text-primary"></i>
                    <strong>{{ number_format($totalLeads) }}</strong> candidates
                    @if($leads->hasPages())
                        <span class="text-muted">· page {{ $leads->currentPage() }}</span>
                    @endif
                </span>
                <div class="leads-toolbar-actions">
                    @if($me->canPermission('kanban.view'))
                        <a href="{{ route('admin.leads.kanban') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-kanban me-1"></i>Kanban
                        </a>
                    @endif
                    @if($me->canPermission('leads.export'))
                        <a href="{{ route('admin.leads.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i>Export
                        </a>
                    @endif
                </div>
            </div>

            <div class="leads-stage-strip" role="navigation" aria-label="Filter by CRM stage">
                <a href="{{ route('admin.leads.index', $filterBase) }}"
                   class="leads-stage-pill @if(!request('mgmt_stage')) active @endif">
                    All stages <span class="count">{{ $stageTotal }}</span>
                </a>
                @foreach($managementStages as $stage)
                    @php $count = (int) ($crmStageCounts[$stage] ?? 0); @endphp
                    <a href="{{ route('admin.leads.index', array_merge($filterBase, ['mgmt_stage' => $stage])) }}"
                       class="leads-stage-pill @if(request('mgmt_stage') === $stage) active @endif">
                        {{ $crmStageLabels[$stage] ?? $stage }}
                        <span class="count">{{ $count }}</span>
                    </a>
                @endforeach
            </div>

            @if($hasFilters)
                <div class="leads-active-filters">
                    @if(request('q'))
                        <a class="leads-filter-chip" href="{{ route('admin.leads.index', request()->except(['q', 'leads_page'])) }}">
                            Search: {{ \Illuminate\Support\Str::limit(request('q'), 24) }} <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                    @if(request('mgmt_stage'))
                        <a class="leads-filter-chip" href="{{ route('admin.leads.index', request()->except(['mgmt_stage', 'leads_page'])) }}">
                            Stage: {{ $crmStageLabels[request('mgmt_stage')] ?? request('mgmt_stage') }} <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                    @if(request('assignment_status'))
                        <a class="leads-filter-chip" href="{{ route('admin.leads.index', request()->except(['assignment_status', 'leads_page'])) }}">
                            Workflow: {{ str_replace('_', ' ', request('assignment_status')) }} <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                    @if(request('status'))
                        <a class="leads-filter-chip" href="{{ route('admin.leads.index', request()->except(['status', 'leads_page'])) }}">
                            Product: {{ str_replace('_', ' ', request('status')) }} <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                    <a href="{{ route('admin.leads.index') }}" class="leads-filter-chip text-secondary border-secondary-subtle bg-white">
                        Clear all
                    </a>
                </div>
            @endif

            <div class="leads-filters-card">
                <div class="leads-filters-head">
                    <h2><i class="bi bi-funnel me-2 text-primary"></i>Filters</h2>
                    @if($hasFilters)
                        <a href="{{ route('admin.leads.index') }}" class="btn btn-sm btn-link text-decoration-none">Reset</a>
                    @endif
                </div>
                <form method="GET" action="{{ route('admin.leads.index') }}" class="leads-filters-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                                <input type="search" class="form-control" name="q" value="{{ request('q') }}"
                                       placeholder="Name, email, or phone">
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label">Workflow</label>
                            <select class="form-select" name="assignment_status">
                                <option value="">All</option>
                                @foreach(['new','assigned','in_progress','closed'] as $st)
                                    <option value="{{ $st }}" @selected(request('assignment_status') === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($showAssigneeFilter)
                            <div class="col-6 col-md-4 col-lg-2">
                                <label class="form-label">Owner</label>
                                <select class="form-select" name="assignee_id">
                                    <option value="">Anyone</option>
                                    @if($me->hasAnyRole([\App\Enums\AdminRole::Admin, \App\Enums\AdminRole::Marketing]))
                                        <option value="unassigned" @selected(request('assignee_id') === 'unassigned')>Unassigned</option>
                                        <optgroup label="Managers">
                                            @foreach($assignableManagers as $m)
                                                <option value="{{ $m->id }}" @selected(request('assignee_id') == (string) $m->id)>{{ $m->name }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Executives">
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
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label">CRM stage</label>
                            <select class="form-select" name="mgmt_stage">
                                <option value="">All</option>
                                @foreach($managementStages as $stage)
                                    <option value="{{ $stage }}" @selected(request('mgmt_stage') === $stage)>{{ $crmStageLabels[$stage] ?? $stage }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label">Product status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                @foreach(['available','bidding','sold','contact_unlocked'] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-auto ms-lg-auto">
                            <button type="submit" class="btn btn-primary w-100 w-lg-auto px-4">
                                <i class="bi bi-check2 me-1"></i>Apply
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if($canBulkManagers)
                <div id="bulk-bar-managers" class="leads-bulk-bar card is-idle">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="bulk-icon"><i class="bi bi-diagram-3"></i></div>
                                <div>
                                    <div class="bulk-kicker">@if($isMarketing) Marketing @else Admin @endif</div>
                                    <div class="bulk-title">Assign to sales manager</div>
                                    <p class="small text-muted mb-0" id="bulk-m-countline">No leads selected.</p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-end">
                                <div>
                                    <label class="form-label small mb-1" for="bulk-manager-select">Manager</label>
                                    <select class="form-select form-select-sm" id="bulk-manager-select" style="min-width:200px">
                                        <option value="">Select…</option>
                                        @foreach($assignableManagers as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="bulk-m-submit">Assign</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($canBulkEmployees)
                <div id="bulk-bar-employees" class="leads-bulk-bar card is-idle">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="bulk-icon"><i class="bi bi-person-lines-fill"></i></div>
                                <div>
                                    <div class="bulk-kicker">Sales manager</div>
                                    <div class="bulk-title">Assign to your team</div>
                                    <p class="small text-muted mb-0" id="bulk-e-countline">No leads selected.</p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-end">
                                <div>
                                    <label class="form-label small mb-1" for="bulk-employee-select">Executive</label>
                                    <select class="form-select form-select-sm" id="bulk-employee-select" style="min-width:200px">
                                        <option value="">Select…</option>
                                        @foreach($assignableEmployees as $e)
                                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="bulk-e-submit">Assign</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="leads-table-card">
                <div class="leads-table-head">
                    <h2><i class="bi bi-list-ul me-2 text-primary"></i>Candidate leads</h2>
                    @if($leads->count())
                        <span class="text-muted small">Updated recently first</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table leads-table mb-0" id="leads-table">
                        <thead>
                        <tr>
                            @if($canBulkManagers || $canBulkEmployees)
                                <th style="width:2.5rem">
                                    <input type="checkbox" class="form-check-input" id="lead-select-all" title="Select all on this page">
                                </th>
                            @endif
                            <th>Candidate</th>
                            <th>Product</th>
                            <th>Assignment</th>
                            <th>CRM stage</th>
                            <th class="text-end">Score</th>
                            <th>Owner</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($leads as $lead)
                            @php
                                $name = $lead->candidate?->name ?? 'Unknown';
                                $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                                $avatarClass = 'c'.(crc32($name) % 6);
                                $mgmtStage = $lead->adminStage?->stage ?? 'new';
                                $leadScore = min(100, (int) ($lead->match_percentage ?? 0) + (int) ($lead->intent_score ?? 0));
                                $assignSt = $lead->assignment_status?->value ?? 'new';
                            @endphp
                            <tr>
                                @if($canBulkManagers || $canBulkEmployees)
                                    <td data-label="">
                                        <input type="checkbox" class="form-check-input lead-bulk-cb" value="{{ $lead->id }}">
                                    </td>
                                @endif
                                <td data-label="Candidate">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="lead-avatar {{ $avatarClass }}">{{ $initials ?: '?' }}</div>
                                        <div class="min-w-0">
                                            <div class="lead-name">{{ $name }}</div>
                                            <div class="lead-email" title="{{ $lead->candidate?->email }}">{{ $lead->candidate?->email ?? '—' }}</div>
                                            @if($lead->candidate?->phone)
                                                <div class="lead-phone">{{ $lead->candidate->phone }}</div>
                                            @endif
                                            @if($lead->referral_source || $lead->lead_summary)
                                                <div class="lead-phone mt-1">
                                                    <i class="bi bi-signpost-split me-1"></i>{{ str_replace('_', ' ', $lead->referral_source ?? $lead->lead_summary) }}
                                                </div>
                                            @elseif(! $lead->candidate)
                                                <div class="lead-phone mt-1 text-warning">Guest / no profile linked</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Product">
                                    <span class="badge-product">{{ str_replace('_', ' ', $lead->status) }}</span>
                                </td>
                                <td data-label="Assignment">
                                    <span class="badge-assign {{ $assignSt }}">{{ str_replace('_', ' ', $assignSt) }}</span>
                                </td>
                                <td data-label="CRM stage">
                                    <span class="badge-stage stage-{{ $mgmtStage }}">{{ $crmStageLabels[$mgmtStage] ?? 'New' }}</span>
                                </td>
                                <td data-label="Score" class="lead-score-wrap">
                                    <div class="lead-score-num">{{ $leadScore }}</div>
                                    <div class="lead-score-bar"><span style="width:{{ $leadScore }}%"></span></div>
                                </td>
                                <td data-label="Owner">
                                    @if($lead->assignedTo)
                                        <span class="lead-owner">{{ $lead->assignedTo->name }}</span>
                                    @else
                                        <span class="lead-owner unassigned">Unassigned</span>
                                    @endif
                                </td>
                                <td data-label="Updated" class="small text-muted text-nowrap">
                                    {{ $lead->updated_at?->diffForHumans(short: true) }}
                                </td>
                                <td data-label="" class="text-end lead-actions">
                                    <a href="{{ route('admin.leads.show', $lead->id) }}" class="btn btn-primary btn-lead-open">
                                        Open <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($canBulkManagers || $canBulkEmployees) ? 9 : 8 }}">
                                    <div class="leads-empty">
                                        <div class="leads-empty-icon"><i class="bi bi-inbox"></i></div>
                                        <div class="fw-semibold text-dark mb-1">No leads match your filters</div>
                                        <p class="small mb-3">Try clearing filters or browse all stages above.</p>
                                        <a href="{{ route('admin.leads.index') }}" class="btn btn-sm btn-outline-primary">View all leads</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.crm-pagination-footer', ['paginator' => $leads])
            </div>

            @if($canBulkManagers || $canBulkEmployees)
                <script>
                    (function () {
                        const cbs = document.querySelectorAll('.lead-bulk-cb');
                        const all = document.getElementById('lead-select-all');
                        function count() { return document.querySelectorAll('.lead-bulk-cb:checked').length; }
                        function formatCountLine(n) {
                            if (n === 0) return 'No leads selected.';
                            return '<strong>' + n + '</strong> ' + (n === 1 ? 'lead' : 'leads') + ' selected';
                        }
                        function refresh() {
                            const n = count();
                            ['bulk-m-countline', 'bulk-e-countline'].forEach(id => {
                                const el = document.getElementById(id);
                                if (el) el.innerHTML = formatCountLine(n);
                            });
                            ['bulk-bar-managers', 'bulk-bar-employees'].forEach(id => {
                                document.getElementById(id)?.classList.toggle('is-idle', n === 0);
                            });
                            const mSel = document.getElementById('bulk-manager-select');
                            const eSel = document.getElementById('bulk-employee-select');
                            document.getElementById('bulk-m-submit')?.toggleAttribute('disabled', n === 0 || !mSel?.value);
                            document.getElementById('bulk-e-submit')?.toggleAttribute('disabled', n === 0 || !eSel?.value);
                        }
                        cbs.forEach(cb => cb.addEventListener('change', refresh));
                        document.getElementById('bulk-manager-select')?.addEventListener('change', refresh);
                        document.getElementById('bulk-employee-select')?.addEventListener('change', refresh);
                        all?.addEventListener('change', () => { cbs.forEach(cb => { cb.checked = all.checked; }); refresh(); });
                        function fillAndSubmit(formId, ids, field, val) {
                            const form = document.getElementById(formId);
                            form.querySelectorAll('input[name="lead_ids[]"]').forEach(el => el.remove());
                            ids.forEach(id => {
                                const i = document.createElement('input');
                                i.type = 'hidden'; i.name = 'lead_ids[]'; i.value = id;
                                form.appendChild(i);
                            });
                            form.querySelector('input[name="' + field + '"]').value = val;
                            form.submit();
                        }
                        document.getElementById('bulk-m-submit')?.addEventListener('click', function () {
                            const sel = document.getElementById('bulk-manager-select');
                            const ids = [...document.querySelectorAll('.lead-bulk-cb:checked')].map(c => c.value);
                            if (!ids.length || !sel?.value) return;
                            fillAndSubmit('form-bulk-managers', ids, 'manager_id', sel.value);
                        });
                        document.getElementById('bulk-e-submit')?.addEventListener('click', function () {
                            const sel = document.getElementById('bulk-employee-select');
                            const ids = [...document.querySelectorAll('.lead-bulk-cb:checked')].map(c => c.value);
                            if (!ids.length || !sel?.value) return;
                            fillAndSubmit('form-bulk-employees', ids, 'employee_id', sel.value);
                        });
                        refresh();
                    })();
                </script>
            @endif
        @endif

        @if($canSeeConsultations && $consultations !== null && $activeTab === 'consultations')
            <div class="leads-toolbar mb-3">
                <span class="leads-total-badge">
                    <i class="bi bi-chat-left-text text-primary"></i>
                    <strong>{{ number_format($consultations->total()) }}</strong> consultations
                </span>
            </div>
            <div class="alert alert-info border-0 shadow-sm mb-3 d-flex gap-2 align-items-start">
                <i class="bi bi-info-circle mt-1"></i>
                <div>
                    <strong>Assignments</strong> apply to product leads only. Use the
                    <a href="{{ route('admin.leads.index') }}" class="alert-link">Lead funnel</a> to bulk-assign.
                </div>
            </div>
            <form method="GET" action="{{ route('admin.consultations.index') }}" class="leads-filters-card mb-3">
                <div class="leads-filters-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="consultation_status" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach(['pending','in_progress','completed','cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(request('consultation_status') === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
            <div class="leads-table-card">
                <div class="table-responsive">
                    <table class="table leads-table mb-0">
                        <thead>
                        <tr>
                            <th>User</th><th>Source</th><th>Status</th>
                            <th class="text-end">Match</th><th>Created</th><th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($consultations as $consultation)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $consultation->user?->name ?? '—' }}</div>
                                    <div class="lead-email">{{ $consultation->user?->email ?? '' }}</div>
                                </td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $consultation->source ?? '—') }}</td>
                                <td><span class="badge-stage stage-new">{{ str_replace('_', ' ', $consultation->status) }}</span></td>
                                <td class="text-end fw-semibold">{{ (int) ($consultation->match_percentage ?? 0) }}%</td>
                                <td class="small text-muted">{{ $consultation->created_at?->format('M j, H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.consultations.show', $consultation->id) }}" class="btn btn-primary btn-lead-open">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="leads-empty">No consultation requests.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.crm-pagination-footer', ['paginator' => $consultations])
            </div>
        @endif
    </div>
@endsection
