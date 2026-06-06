@extends('layouts.app')

@section('title', 'Application Details')

@push('styles')
<style>
    .app-detail-hero {
        background: linear-gradient(135deg, #312e81 0%, #4f46e5 52%, #6366f1 100%);
        border-radius: 18px;
        padding: 1.35rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 12px 40px rgba(79, 70, 229, .22);
        position: relative;
        overflow: hidden;
    }
    .app-detail-hero::before {
        content: '';
        position: absolute;
        right: -30px;
        top: -50px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,.07);
        pointer-events: none;
    }
    .app-detail-hero::after {
        content: '';
        position: absolute;
        right: 80px;
        bottom: -60px;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: rgba(255,255,255,.05);
        pointer-events: none;
    }
    .app-detail-avatar {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: rgba(255,255,255,.18);
        border: 2px solid rgba(255,255,255,.25);
        color: #fff;
        font-weight: 800;
        font-size: 1.15rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        backdrop-filter: blur(4px);
    }
    .app-detail-title {
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: -.03em;
        margin: 0;
        line-height: 1.2;
    }
    .app-detail-sub {
        font-size: .88rem;
        opacity: .88;
        margin-top: .25rem;
    }
    .app-detail-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .75rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.2);
        color: #fff;
        text-decoration: none;
        transition: background .15s ease, transform .15s ease;
    }
    .app-detail-chip:hover {
        background: rgba(255,255,255,.22);
        color: #fff;
        transform: translateY(-1px);
    }
    .app-detail-back {
        border-radius: 10px;
        font-weight: 600;
        font-size: .82rem;
        padding: .45rem .9rem;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.28);
        color: #fff;
        text-decoration: none;
        transition: background .15s ease;
        position: relative;
        z-index: 1;
    }
    .app-detail-back:hover {
        background: rgba(255,255,255,.2);
        color: #fff;
    }
    .app-detail-status-path {
        display: flex;
        gap: 0;
        overflow-x: auto;
        padding: .15rem 0 1rem;
        margin-bottom: 1rem;
        scrollbar-width: thin;
    }
    .app-detail-status-step {
        flex: 1 0 auto;
        min-width: 90px;
        text-align: center;
        padding: .6rem .5rem .5rem;
        font-size: .68rem;
        font-weight: 600;
        color: #94a3b8;
        border-bottom: 3px solid #e2e8f0;
        transition: color .2s, border-color .2s;
        text-transform: capitalize;
    }
    .app-detail-status-step.done { color: #475569; border-color: #a5b4fc; }
    .app-detail-status-step.active {
        color: #4f46e5;
        border-color: #4f46e5;
        font-weight: 700;
        background: linear-gradient(180deg, rgba(79,70,229,.06), transparent);
    }
    .app-detail-status-step.terminal {
        color: #dc2626;
        border-color: #fca5a5;
        background: linear-gradient(180deg, rgba(220,38,38,.04), transparent);
    }
    .app-detail-sidebar { position: sticky; top: .75rem; }
    .app-detail-match-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1.25rem 1rem;
    }
    .app-detail-match-ring {
        --pct: 0;
        width: 108px;
        height: 108px;
        border-radius: 50%;
        background: conic-gradient(#4f46e5 calc(var(--pct) * 1%), #e2e8f0 0);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-bottom: .65rem;
        transition: background .4s ease;
    }
    .app-detail-match-ring::before {
        content: '';
        position: absolute;
        inset: 8px;
        border-radius: 50%;
        background: #fff;
    }
    .app-detail-match-value {
        position: relative;
        z-index: 1;
        font-size: 1.5rem;
        font-weight: 800;
        color: #312e81;
        letter-spacing: -.03em;
    }
    .app-detail-match-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
    }
    .app-detail-status-form .form-select {
        border-radius: 10px;
        border-color: #e2e8f0;
        font-size: .875rem;
        min-height: 42px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .app-detail-status-form .form-select:focus {
        border-color: #a5b4fc;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .15);
    }
    .app-detail-status-form .btn-primary {
        border-radius: 10px;
        font-weight: 600;
        min-height: 42px;
    }
    .app-detail-insight {
        background: linear-gradient(180deg, #eef2ff 0%, #fff 55%);
        border: 1px solid #c7d2fe;
        border-radius: 14px;
        padding: 1.1rem 1.2rem;
        font-size: .9rem;
        line-height: 1.65;
        color: #334155;
    }
    .app-detail-info-grid {
        display: grid;
        gap: .75rem;
    }
    .app-detail-info-row {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .65rem .75rem;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: background .15s ease, border-color .15s ease;
    }
    .app-detail-info-row:hover {
        background: #fafbff;
        border-color: #e0e7ff;
    }
    .app-detail-info-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
        background: #eef2ff;
        color: #4f46e5;
    }
    .app-detail-info-icon.emerald { background: #ecfdf5; color: #059669; }
    .app-detail-info-lbl {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: .1rem;
    }
    .app-detail-info-val {
        font-size: .9rem;
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
    }
    .app-detail-info-val a {
        color: #4f46e5;
        text-decoration: none;
    }
    .app-detail-info-val a:hover { text-decoration: underline; }
    .app-detail-panel .card-body { padding: 1.1rem 1.15rem; }
    @media (max-width: 991px) {
        .app-detail-sidebar { position: static; }
        .app-detail-title { font-size: 1.2rem; }
    }
</style>
@endpush

@section('content')
    @include('partials.portal-ui')

    @php
        $candidate = $application->candidate;
        $profile = $candidate?->candidateProfile;
        $job = $application->job;
        $employer = $job?->employer;
        $companyName = $employer?->referrerProfile?->company_name ?: $job?->company_name ?: $employer?->name ?: '—';
        $backRoute = auth('admin')->user()->canPermission('leads.view')
            ? 'admin.applications.index'
            : 'admin.portal.applications.index';
        $statusRoute = auth('admin')->user()->canPermission('leads.view')
            ? 'admin.applications.status'
            : 'admin.portal.applications.status';
        $status = $application->status ?? 'applied';
        $matchPercent = (int) ($application->profile_match_percent ?? 0);
        $initials = collect(explode(' ', (string) ($candidate?->name ?? 'C')))
            ->filter()->take(2)->map(fn ($w) => strtoupper(substr($w, 0, 1)))->implode('') ?: 'C';
        $statusFlow = ['applied', 'shortlisted', 'interviewed', 'offered', 'hired'];
        $terminalStatuses = ['rejected', 'qualified'];
        $currentStepIndex = array_search($status, $statusFlow, true);
        $isTerminal = in_array($status, $terminalStatuses, true);
    @endphp

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'applications'])

        <div class="app-detail-hero">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 position-relative" style="z-index:1">
                <div class="d-flex align-items-start gap-3 flex-grow-1 min-w-0">
                    <div class="app-detail-avatar">{{ $initials }}</div>
                    <div class="min-w-0">
                        <div class="portal-hero-kicker mb-1"><i class="bi bi-file-earmark-person"></i> Application #{{ $application->id }}</div>
                        <h1 class="app-detail-title">{{ $candidate?->name ?? 'Candidate' }}</h1>
                        <div class="app-detail-sub">
                            Applied for <strong>{{ $job?->title ?? '—' }}</strong> at <strong>{{ $companyName }}</strong>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="app-detail-chip">
                                <i class="bi bi-calendar3"></i>
                                {{ $application->created_at?->format('M j, Y · g:i A') }}
                            </span>
                            <span class="app-detail-chip text-capitalize">
                                <i class="bi bi-flag"></i> {{ $status }}
                            </span>
                            <span class="app-detail-chip">
                                <i class="bi bi-stars"></i> {{ $matchPercent }}% match
                            </span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if($candidate?->phone)
                        <a href="tel:{{ $candidate->phone }}" class="app-detail-chip"><i class="bi bi-telephone"></i> Call</a>
                    @endif
                    @if($candidate?->email)
                        <a href="mailto:{{ $candidate->email }}" class="app-detail-chip"><i class="bi bi-envelope"></i> Email</a>
                    @endif
                    <a href="{{ route($backRoute) }}" class="app-detail-back">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:12px;">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            </div>
        @endif

        <div class="app-detail-status-path" role="list" aria-label="Application status">
            @foreach($statusFlow as $i => $step)
                @php
                    $isActive = $status === $step;
                    $isDone = $currentStepIndex !== false && $i < $currentStepIndex;
                @endphp
                <div class="app-detail-status-step @if($isActive) active @elseif($isDone) done @endif" role="listitem">
                    {{ $step }}
                </div>
            @endforeach
            @if($isTerminal)
                <div class="app-detail-status-step terminal active" role="listitem">{{ $status }}</div>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="app-detail-sidebar">
                    <div class="portal-panel app-detail-panel mb-3">
                        <div class="portal-panel-head">
                            <h2 class="portal-panel-title"><i class="bi bi-sliders"></i> Update status</h2>
                            <span class="portal-badge status-{{ $status }}">{{ $status }}</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route($statusRoute, $application->id) }}" class="app-detail-status-form">
                                @csrf
                                <label class="form-label small text-muted fw-semibold mb-2">Hiring stage</label>
                                <select name="status" class="form-select mb-3">
                                    @foreach(['applied','shortlisted','interviewed','offered','hired','rejected','qualified'] as $st)
                                        <option value="{{ $st }}" @selected($status === $st)>{{ ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check2 me-1"></i> Save status
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="portal-panel app-detail-panel">
                        <div class="portal-panel-head">
                            <h2 class="portal-panel-title"><i class="bi bi-bullseye"></i> AI match</h2>
                        </div>
                        <div class="app-detail-match-wrap">
                            <div class="app-detail-match-ring" style="--pct: {{ $matchPercent }}">
                                <span class="app-detail-match-value">{{ $matchPercent }}%</span>
                            </div>
                            <div class="app-detail-match-label">Profile fit for this role</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="portal-panel app-detail-panel mb-3">
                    <div class="portal-panel-head">
                        <h2 class="portal-panel-title"><i class="bi bi-robot text-primary"></i> AI resume summary</h2>
                    </div>
                    <div class="card-body">
                        <div class="app-detail-insight">
                            {{ $application->ai_resume_summary ?: 'No AI summary available for this candidate resume.' }}
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="portal-panel app-detail-panel h-100">
                            <div class="portal-panel-head">
                                <h2 class="portal-panel-title"><i class="bi bi-person"></i> Candidate</h2>
                            </div>
                            <div class="card-body">
                                <div class="app-detail-info-grid">
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon"><i class="bi bi-person-badge"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Name</div>
                                            <div class="app-detail-info-val">{{ $candidate?->name ?? '—' }}</div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon"><i class="bi bi-envelope"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Email</div>
                                            <div class="app-detail-info-val">
                                                @if($candidate?->email)
                                                    <a href="mailto:{{ $candidate->email }}">{{ $candidate->email }}</a>
                                                @else — @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon"><i class="bi bi-telephone"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Phone</div>
                                            <div class="app-detail-info-val">
                                                @if($candidate?->phone)
                                                    <a href="tel:{{ $candidate->phone }}">{{ $candidate->phone }}</a>
                                                @else — @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon emerald"><i class="bi bi-briefcase"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Experience</div>
                                            <div class="app-detail-info-val">{{ $profile?->experience_years !== null ? $profile->experience_years.' yrs' : '—' }}</div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon emerald"><i class="bi bi-geo-alt"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Location</div>
                                            <div class="app-detail-info-val">{{ $profile?->preferred_job_location ?? ($profile?->location ?? '—') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="portal-panel app-detail-panel h-100">
                            <div class="portal-panel-head">
                                <h2 class="portal-panel-title"><i class="bi bi-building"></i> Job &amp; company</h2>
                            </div>
                            <div class="card-body">
                                <div class="app-detail-info-grid">
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon emerald"><i class="bi bi-building"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Company</div>
                                            <div class="app-detail-info-val">{{ $companyName }}</div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon"><i class="bi bi-envelope-at"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Company email</div>
                                            <div class="app-detail-info-val">
                                                @if($employer?->email)
                                                    <a href="mailto:{{ $employer->email }}">{{ $employer->email }}</a>
                                                @else — @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon"><i class="bi bi-briefcase"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Role</div>
                                            <div class="app-detail-info-val">{{ $job?->title ?? '—' }}</div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon emerald"><i class="bi bi-geo-alt"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Job location</div>
                                            <div class="app-detail-info-val">{{ $job?->location_display ?? '—' }}</div>
                                        </div>
                                    </div>
                                    <div class="app-detail-info-row">
                                        <div class="app-detail-info-icon"><i class="bi bi-circle-half"></i></div>
                                        <div>
                                            <div class="app-detail-info-lbl">Job status</div>
                                            <div class="app-detail-info-val">
                                                <span class="portal-badge status-{{ $job?->status ?? 'draft' }}">{{ $job?->status ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
