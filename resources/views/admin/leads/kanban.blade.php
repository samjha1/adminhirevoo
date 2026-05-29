@extends('layouts.app')

@section('title', 'Lead Kanban')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Pipeline Kanban</h1>
            <p class="page-subtitle mb-0">Drag-free stage board — use the form on each card to move stages.</p>
        </div>
        <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>

    <div class="d-flex gap-3 overflow-auto pb-3" style="min-height: 70vh;">
        @foreach($columns as $stage => $column)
            <div class="card shadow-soft flex-shrink-0" style="width: 280px;">
                <div class="card-header fw-semibold d-flex justify-content-between">
                    <span>{{ $column['label'] }}</span>
                    <span class="badge text-bg-light">{{ $column['leads']->count() }}</span>
                </div>
                <div class="card-body p-2 d-flex flex-column gap-2">
                    @foreach($column['leads'] as $lead)
                        <div class="border rounded-3 p-2 bg-light">
                            <a href="{{ route('admin.leads.show', $lead) }}" class="fw-semibold text-decoration-none">
                                #{{ $lead->id }} {{ $lead->candidate?->name ?? 'Lead' }}
                            </a>
                            <div class="text-muted small">{{ $lead->assignedTo?->name ?? 'Unassigned' }}</div>
                            @can('updateCrmStage', $lead)
                            <form method="POST" action="{{ route('admin.leads.kanban-stage', $lead) }}" class="mt-2">
                                @csrf
                                <select name="stage" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach($stages as $s)
                                        <option value="{{ $s }}" @selected(($lead->adminStage?->stage ?? 'new') === $s)>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
