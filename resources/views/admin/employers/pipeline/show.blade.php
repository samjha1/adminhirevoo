@extends('layouts.app')

@section('title', $prospect->company_name)

@section('content')
    @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-soft">
                <div class="card-header bg-white fw-semibold">Company profile</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="text-muted small">Company</label><div class="fw-semibold">{{ $prospect->company_name }}</div></div>
                    <div class="col-md-6"><label class="text-muted small">Industry</label><div>{{ $prospect->industry ?? '—' }}</div></div>
                    <div class="col-md-6"><label class="text-muted small">Contact</label><div>{{ $prospect->contact_name ?? '—' }} · {{ $prospect->contact_designation ?? '' }}</div></div>
                    <div class="col-md-6"><label class="text-muted small">Email / Phone</label><div>{{ $prospect->email ?? '—' }} · {{ $prospect->phone ?? '—' }}</div></div>
                    <div class="col-md-6"><label class="text-muted small">Website</label><div>{{ $prospect->website ?? '—' }}</div></div>
                    <div class="col-md-6"><label class="text-muted small">Location</label><div>{{ $prospect->location ?? '—' }} · {{ $prospect->company_size ?? '' }}</div></div>
                    <div class="col-12"><label class="text-muted small">Notes</label><div>{{ $prospect->notes ?? '—' }}</div></div>
                </div>
            </div>

            @if($planPayments->isNotEmpty())
            <div class="card shadow-soft mt-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Plan payments</span>
                    <a href="{{ route('admin.employer-plan-payments.index', ['status' => 'pending']) }}" class="small">View all</a>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach($planPayments as $planPayment)
                        @php
                            $meta = $planPayment->meta ?? [];
                            $planLabel = $meta['plan_name'] ?? ucfirst((string) ($meta['plan_key'] ?? 'Plan'));
                            $isPending = $planPayment->status === \App\Models\Hirevo\HirevoPayment::STATUS_PENDING;
                        @endphp
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $planLabel }} · ₹{{ number_format((float) $planPayment->amount, 2) }}</div>
                                    <div class="small text-muted">
                                        {{ $planPayment->payment_gateway === \App\Models\Hirevo\HirevoPayment::GATEWAY_NETBANKING ? 'UTR' : 'Cheque' }}
                                        #{{ $planPayment->payment_reference ?? '—' }}
                                        · {{ $planPayment->created_at?->format('d M Y') }}
                                    </div>
                                </div>
                                @if($isPending)
                                    <span class="badge text-bg-warning text-dark">Pending</span>
                                @else
                                    <span class="badge text-bg-success">Completed</span>
                                @endif
                            </div>
                            @if($isPending && $canCompletePlanPayments)
                                <form method="POST"
                                      action="{{ route('admin.employer-plan-payments.complete', $planPayment) }}"
                                      class="mt-2"
                                      onsubmit="return confirm('Verify this payment and activate the subscription?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Verify &amp; activate</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="card shadow-soft mt-3">
                <div class="card-header bg-white fw-semibold">Activity</div>
                <ul class="list-group list-group-flush">
                    @forelse($prospect->activities as $act)
                        <li class="list-group-item">
                            <div class="fw-semibold">{{ $act->title }}</div>
                            <div class="small text-muted">{{ $act->created_at?->format('M j, H:i') }} · {{ $act->admin?->name }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No activity yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-soft">
                <div class="card-header bg-white fw-semibold">Deal &amp; stage</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.employers.pipeline.stage', $prospect) }}" data-crm-stage-form>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pipeline stage</label>
                            <select name="pipeline_stage" class="form-select"
                                    data-crm-stage-select
                                    data-follow-up-value="follow_up"
                                    data-meeting-value="meeting_scheduled">
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->value }}" @selected($prospect->pipeline_stage === $stage->value)>{{ $stage->label() }} ({{ $stage->winProbability() }}%)</option>
                                @endforeach
                                <option value="lost" @selected($prospect->pipeline_stage === 'lost')>Lost</option>
                            </select>
                        </div>
                        @include('partials.crm-follow-up-stage-fields')
                        @include('partials.crm-meeting-stage-fields')
                        <div class="mb-3">
                            <label class="form-label">Deal value (₹)</label>
                            <input type="number" name="deal_value" class="form-control" value="{{ $prospect->deal_value }}" step="0.01">
                        </div>
                        <div class="mb-3 small text-muted">
                            Expected revenue: <strong>₹{{ number_format($prospect->expected_revenue ?? 0, 0) }}</strong>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update</button>
                    </form>
                    @include('partials.crm-stage-schedule-script')
                </div>
            </div>
            @if(auth('admin')->user()?->canPermission('leads.manage_followups'))
            <div class="card shadow-soft mt-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Schedule</span>
                    <div class="dropdown">
                        <button type="button" class="btn btn-link btn-sm p-0 dropdown-toggle" data-bs-toggle="dropdown">+ Add</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#scheduleCompanyFollowUpModal">Follow-up</button></li>
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#scheduleCompanyMeetingModal">Meeting</button></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body pt-2 pb-2">
                    @php
                        $hasSchedule = ($upcomingFollowUps ?? collect())->isNotEmpty() || ($upcomingMeetings ?? collect())->isNotEmpty();
                    @endphp
                    @forelse($upcomingMeetings ?? [] as $meeting)
                        <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                            <div class="small">
                                <span class="badge text-bg-warning mb-1">Meeting</span>
                                <div class="fw-semibold">{{ $meeting->meeting_at?->format('M j, g:i A') }}</div>
                                @if($meeting->notes)
                                    <div class="text-muted">{{ Str::limit($meeting->notes, 80) }}</div>
                                @endif
                            </div>
                            @if(!$meeting->outcome)
                                <form method="POST" action="{{ route('admin.companies.meetings.complete', $meeting) }}" class="m-0">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Done</button>
                                </form>
                            @endif
                        </div>
                    @empty
                    @endforelse
                    @forelse($upcomingFollowUps ?? [] as $fu)
                        <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                            <div class="small">
                                <span class="badge text-bg-info mb-1">Follow-up</span>
                                <div class="fw-semibold">{{ $fu->scheduled_at?->format('M j, g:i A') }}</div>
                                <div class="text-muted">{{ $fu->status->label() }}</div>
                                @if($fu->notes)
                                    <div class="text-muted">{{ Str::limit($fu->notes, 80) }}</div>
                                @endif
                            </div>
                            @if(in_array($fu->status->value, ['pending', 'overdue'], true))
                                <form method="POST" action="{{ route('admin.follow-ups.complete', $fu) }}" class="m-0">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Done</button>
                                </form>
                            @endif
                        </div>
                    @empty
                    @endforelse
                    @if(! $hasSchedule)
                        <p class="text-muted small mb-0">No follow-ups or meetings scheduled.</p>
                    @endif
                </div>
            </div>
            <div class="modal fade" id="scheduleCompanyMeetingModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('admin.employers.meetings.store', $prospect) }}" class="modal-content border-0 shadow">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Schedule meeting</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-3 pt-2">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">When</label>
                                <input type="datetime-local" name="meeting_at" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal fade" id="scheduleCompanyFollowUpModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('admin.employers.follow-ups.store', $prospect) }}" class="modal-content border-0 shadow">
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
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            <div class="card shadow-soft mt-3">
                <div class="card-body small">
                    <div class="text-muted">Owner</div>
                    <div>{{ $prospect->assignedTo?->name ?? 'Unassigned' }}</div>
                    <div class="text-muted mt-2">Manager</div>
                    <div>{{ $prospect->salesManager?->name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
