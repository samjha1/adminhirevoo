@extends('layouts.app')

@section('title', 'Recruiter assignments')

@section('content')
    @include('partials.portal-ui')

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'recruiter-assignments'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">Recruiter company assignments</h1>
                <p class="portal-hero-sub">Assign Hirevo employer companies to recruiters. Recruiters only see jobs and applications for their assigned companies.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success shadow-soft">{{ session('success') }}</div>
        @endif

        <div class="portal-table-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Recruiter</th>
                        <th>Email</th>
                        <th>Assigned companies</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recruiters as $recruiter)
                        <tr>
                            <td class="fw-semibold">{{ $recruiter->name }}</td>
                            <td class="text-muted">{{ $recruiter->email }}</td>
                            <td>
                                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2">
                                    {{ $recruiter->recruiter_company_assignments_count ?? 0 }} companies
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.portal.recruiter-assignments.edit', $recruiter->id) }}"
                                   class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                                    <i class="bi bi-building-gear me-1"></i> Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="portal-empty">
                                    <i class="bi bi-people"></i>
                                    No recruiter staff accounts found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
