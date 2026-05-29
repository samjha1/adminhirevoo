@extends('layouts.app')

@section('title', 'My Follow-ups')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">@if($filter === 'today') Today's follow-ups @else All follow-ups @endif</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.follow-ups.today') }}" class="btn btn-sm @if($filter === 'today') btn-primary @else btn-outline-primary @endif">Today</a>
            <a href="{{ route('admin.follow-ups.index') }}" class="btn btn-sm @if($filter === 'all') btn-primary @else btn-outline-primary @endif">All</a>
        </div>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Scheduled</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($followUps as $fu)
                    <tr>
                        <td><a href="{{ route('admin.leads.show', $fu->lead_id) }}">Lead #{{ $fu->lead_id }}</a></td>
                        <td>{{ $fu->scheduled_at?->format('Y-m-d H:i') }}</td>
                        <td><span class="badge text-bg-secondary">{{ $fu->status->label() }}</span></td>
                        <td class="text-muted small">{{ Str::limit($fu->notes, 60) }}</td>
                        <td class="text-end">
                            @if(in_array($fu->status->value, ['pending', 'overdue'], true))
                                <form method="POST" action="{{ route('admin.follow-ups.complete', $fu) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Complete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No follow-ups found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($followUps->hasPages())
            <div class="card-footer">{{ $followUps->links() }}</div>
        @endif
    </div>
@endsection
