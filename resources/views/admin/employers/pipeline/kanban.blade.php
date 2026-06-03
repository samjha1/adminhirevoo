@extends('layouts.app')

@section('title', 'Company pipeline — Kanban')

@section('content')
    @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0 small">Drag-free: change stage from each card to move companies through your B2B funnel.</p>
        <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-sm btn-outline-secondary">Table view</a>
    </div>

    <div class="d-flex gap-2 overflow-auto pb-3" style="min-height: 65vh;">
        @foreach($columns as $stage => $column)
            <div class="card shadow-soft flex-shrink-0 b2b-kanban-col">
                <div class="card-header py-2">
                    <div class="fw-semibold small">{{ $column['label'] }}</div>
                    <div class="text-muted" style="font-size:.7rem">{{ $column['probability'] }}% · {{ $column['prospects']->count() }}</div>
                </div>
                <div class="card-body p-2 d-flex flex-column gap-2">
                    @foreach($column['prospects'] as $prospect)
                        <div class="b2b-kanban-card">
                            <a href="{{ route('admin.employers.pipeline.show', $prospect) }}" class="fw-semibold text-decoration-none text-dark">
                                {{ $prospect->company_name }}
                            </a>
                            <div class="text-muted small">{{ $prospect->assignedTo?->name ?? 'Unassigned' }}</div>
                            @if($prospect->expected_revenue)
                                <div class="small text-success">₹{{ number_format($prospect->expected_revenue, 0) }} exp.</div>
                            @endif
                            <form method="POST" action="{{ route('admin.employers.pipeline.stage', $prospect) }}" class="mt-2"
                                  data-kanban-stage-form
                                  data-detail-url="{{ route('admin.employers.pipeline.show', $prospect) }}">
                                @csrf
                                <select name="pipeline_stage" class="form-select form-select-sm"
                                        data-kanban-stage-select
                                        data-follow-up-value="follow_up"
                                        data-meeting-value="meeting_scheduled">
                                    @foreach($stageLabels as $val => $label)
                                        <option value="{{ $val }}" @selected($prospect->pipeline_stage === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @include('partials.crm-kanban-follow-up-guard')
@endsection
