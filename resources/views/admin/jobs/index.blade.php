@extends('layouts.app')

@section('title', 'Jobs')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Jobs</h1>
            <div class="page-subtitle">Moderate employer job posts</div>
        </div>
        <form class="d-flex gap-2" method="GET" action="{{ route('admin.jobs.index') }}">
            <input class="form-control" name="q" placeholder="Search title/location" value="{{ request('q') }}" style="width: 240px;">
            <select class="form-select" name="status" style="width: 150px;">
                <option value="">All status</option>
                <option value="draft" @selected(request('status')==='draft')>Draft</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="closed" @selected(request('status')==='closed')>Closed</option>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </form>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Title</th><th>Employer</th><th>Location</th><th>Applications</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td class="fw-semibold">{{ $job->title }}</td>
                        <td>{{ $job->employer?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $job->location_city }}</td>
                        <td>{{ $job->applications_count }}</td>
                        <td><span class="badge text-bg-light text-capitalize">{{ $job->status }}</span></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.jobs.status', $job->id) }}" class="d-inline-flex gap-2">
                                @csrf
                                <select name="status" class="form-select form-select-sm" style="width:120px">
                                    <option value="draft" @selected($job->status==='draft')>Draft</option>
                                    <option value="active" @selected($job->status==='active')>Active</option>
                                    <option value="closed" @selected($job->status==='closed')>Closed</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No jobs found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $jobs->links() }}</div>
@endsection

