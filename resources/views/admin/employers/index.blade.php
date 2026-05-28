@extends('layouts.app')

@section('title', 'Employers')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Employers</h1>
            <div class="page-subtitle">Approve or reject employer accounts</div>
        </div>

        <form class="d-flex gap-2" method="GET" action="{{ route('admin.employers.index') }}">
            <select name="status" class="form-select" style="width: 200px">
                <option value="">All statuses</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            </select>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
        </form>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($employers as $employer)
                    @php
                        $profile = $employer->referrerProfile;
                        $approved = $profile && $profile->is_approved;
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $employer->name }}</td>
                        <td class="text-muted">{{ $employer->email }}</td>
                        <td>{{ $profile?->company_name ?? '—' }}</td>
                        <td>
                            @if($approved)
                                <span class="badge text-bg-success">Approved</span>
                            @else
                                <span class="badge text-bg-warning">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary"
                               href="{{ route('admin.employers.show', $employer->id) }}">
                                View <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No employers found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $employers->links() }}
    </div>
@endsection

