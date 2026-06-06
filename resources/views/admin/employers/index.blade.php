@extends('layouts.app')

@section('title', 'Companies')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Companies</h1>
            <div class="page-subtitle">Employer accounts &amp; job portal companies</div>
        </div>
        <form class="d-flex gap-2 flex-wrap" method="GET" action="{{ route('admin.employers.index') }}">
            <input class="form-control" name="q" placeholder="Search company, email…" value="{{ request('q') }}" style="width: 220px">
            <select name="status" class="form-select" style="width: 160px">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending approval</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
        </form>
    </div>

    @include('partials.portal-date-filter', ['dateFilter' => $dateFilter ?? null])

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Contact person</th>
                    <th>Jobs posted</th>
                    <th>Applications</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($employers as $employer)
                    @php
                        $profile = $employer->referrerProfile;
                        $approved = $profile && $profile->is_approved;
                        $active = ($employer->status ?? 'active') === 'active' && $approved;
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $profile?->company_name ?? $employer->name }}</td>
                        <td class="text-muted">{{ $profile?->company_email ?? $employer->email }}</td>
                        <td>{{ $employer->name }}</td>
                        <td>{{ $employer->employer_jobs_count ?? 0 }}</td>
                        <td>{{ $employer->applications_received_count ?? 0 }}</td>
                        <td class="text-muted">{{ $employer->created_at?->format('M j, Y') }}</td>
                        <td>
                            @if($active)
                                <span class="badge text-bg-success">Active</span>
                            @elseif($approved)
                                <span class="badge text-bg-secondary">Inactive</span>
                            @else
                                <span class="badge text-bg-warning">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.employers.show', $employer->id) }}">
                                View <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No companies found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $employers->links() }}</div>
@endsection
