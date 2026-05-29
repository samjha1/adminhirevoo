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
                    <form method="POST" action="{{ route('admin.employers.pipeline.stage', $prospect) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pipeline stage</label>
                            <select name="pipeline_stage" class="form-select">
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->value }}" @selected($prospect->pipeline_stage === $stage->value)>{{ $stage->label() }} ({{ $stage->winProbability() }}%)</option>
                                @endforeach
                                <option value="lost" @selected($prospect->pipeline_stage === 'lost')>Lost</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deal value (₹)</label>
                            <input type="number" name="deal_value" class="form-control" value="{{ $prospect->deal_value }}" step="0.01">
                        </div>
                        <div class="mb-3 small text-muted">
                            Expected revenue: <strong>₹{{ number_format($prospect->expected_revenue ?? 0, 0) }}</strong>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update</button>
                    </form>
                </div>
            </div>
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
