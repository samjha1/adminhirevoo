@extends('layouts.app')

@section('title', 'Applied Jobs')

@section('content')
    <style>
        .applied-jobs-tools { justify-content: flex-end; }
        .applied-jobs-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-top: 1px solid rgba(15, 23, 42, .08);
            background: #fff;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
        }
        .applied-jobs-page-meta { color: #6b7280; font-size: .875rem; }
        .applied-jobs-page-meta strong { color: #334155; }
        .applied-jobs-pagination {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .applied-jobs-page-count {
            font-size: .8rem;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: .3rem .65rem;
        }
        .applied-jobs-pagination-list {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .applied-jobs-pagination-list a,
        .applied-jobs-pagination-list span {
            min-width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            font-size: .85rem;
            color: #334155;
            background: #fff;
            padding: 0 .6rem;
        }
        .applied-jobs-pagination-list a:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1d4ed8;
        }
        .applied-jobs-pagination-list .is-active span {
            color: #fff;
            background: #2563eb;
            border-color: #2563eb;
            font-weight: 600;
        }
        .applied-jobs-pagination-list .is-disabled span {
            opacity: .45;
            cursor: default;
            background: #f8fafc;
        }
        @media (max-width: 991px) {
            .applied-jobs-footer { flex-direction: column; align-items: flex-start; }
            .applied-jobs-pagination { justify-content: flex-start; }
        }
    </style>
    <div class="page-header">
        <div>
            <h1 class="page-title">Applied Jobs</h1>
            <div class="page-subtitle">See who applied to which company and role.</div>
        </div>
        <form class="d-flex flex-wrap gap-2 applied-jobs-tools" method="GET" action="{{ route('admin.applications.index') }}">
            <input type="search"
                   class="form-control"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Search candidate, company, role, email"
                   style="min-width: 280px;">
            <select class="form-select" name="status" style="width: 170px;">
                <option value="">All status</option>
                <option value="qualified" @selected(request('status') === 'qualified')>Qualified</option>
            </select>
            <input type="date"
                   class="form-control"
                   name="date_from"
                   value="{{ request('date_from') }}"
                   style="width: 165px;"
                   title="From date">
            <input type="date"
                   class="form-control"
                   name="date_to"
                   value="{{ request('date_to') }}"
                   style="width: 165px;"
                   title="To date">
            <button class="btn btn-primary">Filter</button>
            @if(request()->filled('q') || request()->filled('status') || request()->filled('date_from') || request()->filled('date_to'))
                <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Company</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>AI Resume Summary</th>
                    <th>Match %</th>
                    <th>Status</th>
                    <th>Applied On</th>
                </tr>
                </thead>
                <tbody>
                @forelse($applications as $application)
                    @php
                        $candidate = $application->candidate;
                        $job = $application->job;
                        $employer = $job?->employer;
                        $companyName = $employer?->referrerProfile?->company_name ?: $employer?->name ?: '—';
                    @endphp
                    <tr>
                        <td>
                            @if($candidate?->name)
                                <a href="{{ route('admin.applications.show', $application->id) }}" class="text-decoration-none fw-semibold">
                                    {{ $candidate->name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $companyName }}</td>
                        <td>{{ $job?->title ?? '—' }}</td>
                        <td>
                            <div>{{ $candidate?->email ?? '—' }}</div>
                            @if($candidate?->phone)
                                <div class="small text-muted">{{ $candidate->phone }}</div>
                            @endif
                        </td>
                        <td style="max-width: 320px;">
                            <div class="small text-muted">
                                {{ \Illuminate\Support\Str::limit($application->ai_resume_summary ?: 'No AI summary available.', 180) }}
                            </div>
                        </td>
                        <td>
                            <span class="badge text-bg-primary">{{ (int) ($application->profile_match_percent ?? 0) }}%</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.applications.status', $application->id) }}" class="d-flex gap-2 align-items-center">
                                @csrf
                                <input type="hidden" name="status" value="qualified">
                                @if($application->status === 'qualified')
                                    <span class="badge text-bg-success">Qualified</span>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-success">Mark Qualified</button>
                                @endif
                            </form>
                        </td>
                        <td class="text-muted">{{ $application->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No applied jobs found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="applied-jobs-footer">
            <div class="applied-jobs-page-meta">
                @if($applications->total() > 0)
                    Showing <strong>{{ $applications->firstItem() }}</strong> to <strong>{{ $applications->lastItem() }}</strong> of <strong>{{ $applications->total() }}</strong> entries
                @else
                    Showing 0 entries
                @endif
            </div>
            <div class="applied-jobs-pagination">
                <span class="applied-jobs-page-count">Page {{ $applications->currentPage() }} of {{ $applications->lastPage() }}</span>
                <ul class="applied-jobs-pagination-list">
                    @if($applications->onFirstPage())
                        <li class="is-disabled"><span>&laquo;</span></li>
                    @else
                        <li><a href="{{ $applications->previousPageUrl() }}" aria-label="Previous page">&laquo;</a></li>
                    @endif

                    @foreach($applications->getUrlRange(max(1, $applications->currentPage() - 1), min($applications->lastPage(), $applications->currentPage() + 1)) as $page => $url)
                        @if($page == $applications->currentPage())
                            <li class="is-active"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach

                    @if($applications->hasMorePages())
                        <li><a href="{{ $applications->nextPageUrl() }}" aria-label="Next page">&raquo;</a></li>
                    @else
                        <li class="is-disabled"><span>&raquo;</span></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endsection

