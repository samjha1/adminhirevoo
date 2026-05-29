@extends('layouts.app')

@section('title', 'Marketing Leads')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Marketing leads</h1>
            <p class="page-subtitle mb-0">Standalone CRM leads (not tied to Hirevo candidates).</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('leads.create')
            <a href="{{ route('admin.standalone-leads.create') }}" class="btn btn-primary">New lead</a>
            @endcan
            @can('leads.import')
            <a href="{{ route('admin.standalone-leads.import') }}" class="btn btn-outline-primary">Import CSV</a>
            <a href="{{ route('admin.standalone-leads.template') }}" class="btn btn-outline-secondary">Template</a>
            @endcan
            @can('leads.export')
            <a href="{{ route('admin.standalone-leads.export') }}" class="btn btn-outline-secondary">Export</a>
            @endcan
        </div>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Source</th>
                        <th>Manager</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td class="fw-semibold">{{ $lead->name }}</td>
                        <td>{{ $lead->phone ?? '—' }}</td>
                        <td>{{ $lead->email ?? '—' }}</td>
                        <td>{{ $lead->source ?? '—' }}</td>
                        <td>{{ $lead->salesManager?->name ?? '—' }}</td>
                        <td class="text-muted small">{{ $lead->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No marketing leads yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $leads->links() }}</div>
    </div>
@endsection
