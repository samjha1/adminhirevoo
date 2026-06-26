@extends('layouts.app')

@section('title', $lead->company_name . ' — Outreach lead')

@push('styles')
<style>
    .outreach-detail { max-width: 960px; margin: 0 auto; }
    .outreach-card {
        border: 1px solid #e2e8f0; border-radius: 16px; background: #fff;
        padding: 1.25rem; margin-bottom: 1rem;
    }
    .outreach-stage-form select { min-height: 42px; }
    .badge-outreach-stage {
        font-size: .75rem; font-weight: 700; padding: .35rem .75rem; border-radius: 999px;
    }
    .badge-outreach-stage.s-new { background: #f1f5f9; color: #475569; }
    .badge-outreach-stage.s-called { background: #eff6ff; color: #1d4ed8; }
    .badge-outreach-stage.s-follow_up { background: #fffbeb; color: #b45309; }
    .badge-outreach-stage.s-interested { background: #f5f3ff; color: #6d28d9; }
    .badge-outreach-stage.s-signup_link_sent { background: #ecfeff; color: #0e7490; }
    .badge-outreach-stage.s-signed_up { background: #ecfdf5; color: #047857; }
    .badge-outreach-stage.s-not_interested { background: #fef2f2; color: #991b1b; }
    .outreach-meta dt { font-size: .7rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; }
    .outreach-meta dd { margin-bottom: .75rem; }
</style>
@endpush

@section('content')
    <div class="outreach-detail">
        @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
        @include('partials.company-section-tabs', ['activeTab' => 'outreach'])

        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div>
                <a href="{{ route('admin.employers.outreach.index') }}" class="text-muted small text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Back to outreach leads
                </a>
                <h1 class="h4 fw-bold mt-2 mb-1">{{ $lead->company_name }}</h1>
                <span class="badge-outreach-stage s-{{ $lead->outreach_stage }}">
                    {{ $stageLabels[$lead->outreach_stage] ?? $lead->outreach_stage }}
                </span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="outreach-card">
                    <h2 class="h6 fw-bold mb-3">Company details</h2>
                    <dl class="row outreach-meta mb-0">
                        <div class="col-sm-6">
                            <dt>Contact</dt>
                            <dd>{{ $lead->contact_name ?? '—' }}</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt>Phone</dt>
                            <dd>
                                @if($lead->phone)
                                    <a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="col-sm-6">
                            <dt>Email</dt>
                            <dd>
                                @if($lead->email)
                                    <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="col-sm-6">
                            <dt>Industry</dt>
                            <dd>{{ $lead->industry ?? '—' }}</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt>Location</dt>
                            <dd>{{ $lead->location ?? '—' }}</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt>Website</dt>
                            <dd>
                                @if($lead->website)
                                    <a href="{{ $lead->website }}" target="_blank" rel="noopener">{{ $lead->website }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="col-12">
                            <dt>Source</dt>
                            <dd>{{ $lead->source ?? '—' }}</dd>
                        </div>
                        @if($lead->notes)
                            <div class="col-12">
                                <dt>Notes</dt>
                                <dd class="small" style="white-space: pre-wrap;">{{ $lead->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="col-lg-5">
                @can('leads.update_stage')
                <div class="outreach-card outreach-stage-form">
                    <h2 class="h6 fw-bold mb-3">Update call stage</h2>
                    <p class="small text-muted mb-3">
                        Track your outreach calls until the company signs up on the website.
                    </p>
                    <form method="POST" action="{{ route('admin.employers.outreach.stage', $lead) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Stage</label>
                            <select name="outreach_stage" class="form-select" required>
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->value }}" @selected($lead->outreach_stage === $stage->value)>
                                        {{ $stage->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Follow-up date</label>
                            <input type="datetime-local" name="follow_up_at" class="form-control"
                                   value="{{ $lead->follow_up_at?->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Call notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="What happened on the call?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Save stage</button>
                    </form>
                </div>
                @endcan

                @if(($canAssignTeamLeads ?? false) || ($canAssignEmployees ?? false))
                <div class="outreach-card">
                    <h2 class="h6 fw-bold mb-3">Assignment</h2>
                    @if($lead->assignedTo)
                        <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded" style="background:#f8fafc">
                            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                                <i class="bi bi-person-check"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small">{{ $lead->assignedTo->name }}</div>
                                <div class="text-muted" style="font-size:.7rem">
                                    {{ $lead->assignedTo->role?->label() ?? 'Assigned' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="small text-muted mb-3">
                            @if($isAsmActor ?? false)
                                Not assigned yet — pick a manager or executive from your team.
                            @else
                                Not assigned yet — pick an ASM or manager from the company sales team.
                            @endif
                        </p>
                    @endif

                    @if(($canAssignTeamLeads ?? false) && ($assignableTeamLeads ?? collect())->isNotEmpty())
                        <form method="POST" action="{{ route('admin.employers.outreach.assign-team-lead', $lead) }}" class="mb-3">
                            @csrf
                            <label class="form-label small fw-semibold">
                                {{ ($isAsmActor ?? false) ? 'Assign to manager' : 'Assign to ASM / Manager' }}
                            </label>
                            <div class="input-group input-group-sm">
                                <select name="team_lead_id" class="form-select" required>
                                    <option value="">Select…</option>
                                    @foreach($assignableTeamLeads as $tl)
                                        <option value="{{ $tl->id }}" @selected($lead->assigned_to === $tl->id)>
                                            {{ $tl->name }}
                                            @if(! ($isAsmActor ?? false) && $tl->role === \App\Enums\AdminRole::Asm) (ASM) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-success">Assign</button>
                            </div>
                        </form>
                    @endif

                    @if(($canAssignEmployees ?? false) && ($assignableEmployees ?? collect())->isNotEmpty())
                        <form method="POST" action="{{ route('admin.employers.outreach.assign-employee', $lead) }}">
                            @csrf
                            <label class="form-label small fw-semibold">Assign to executive</label>
                            <div class="input-group input-group-sm">
                                <select name="employee_id" class="form-select" required>
                                    <option value="">Select…</option>
                                    @foreach($assignableEmployees as $e)
                                        <option value="{{ $e->id }}" @selected($lead->assigned_to === $e->id)>{{ $e->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-success">Assign</button>
                            </div>
                        </form>
                    @endif
                </div>
                @endif

                <div class="outreach-card">
                    <h2 class="h6 fw-bold mb-3">Signup link</h2>
                    <p class="small text-muted mb-2">Share this link when moving to "Signup link sent":</p>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" readonly
                               value="{{ config('app.hirevo_url', 'https://hirevo.themesdesign.in') }}/sign-up?role=referrer"
                               id="signup-link">
                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('signup-link').value)">
                            Copy
                        </button>
                    </div>
                    @if($lead->hirevoUser)
                        <div class="alert alert-success small mt-3 mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            Linked to Hirevo account: {{ $lead->hirevoUser->email }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
