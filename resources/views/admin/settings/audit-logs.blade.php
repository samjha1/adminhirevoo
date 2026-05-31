@extends('layouts.app')

@section('title', 'Audit logs')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Audit logs</h1>
            <div class="page-subtitle">System activity (Super Admin)</div>
        </div>
    </div>

    <div class="card shadow-soft mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Action contains</label>
                    <input type="text" name="action" class="form-control form-control-sm" value="{{ request('action') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>IP</th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap small">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->admin?->email ?? '—' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td class="small text-muted">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td>
                        <td class="small">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No audit entries</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
