@extends('layouts.app')

@section('title', 'Assign companies — '.$recruiter->name)

@section('content')
    @include('partials.portal-ui')

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'recruiter-assignments'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Recruiter assignments</div>
                <h1 class="portal-hero-title">{{ $recruiter->name }}</h1>
                <p class="portal-hero-sub">{{ $recruiter->email }} · {{ count($assignedIds) }} companies assigned</p>
            </div>
            <a href="{{ route('admin.portal.recruiter-assignments.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                <i class="bi bi-arrow-left me-1"></i> All recruiters
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success shadow-soft">{{ session('success') }}</div>
        @endif

        <div class="portal-filters-card mb-3">
            <div class="portal-filters-head">
                <h2><i class="bi bi-search text-primary"></i> Search companies</h2>
            </div>
            <form method="GET" action="{{ route('admin.portal.recruiter-assignments.edit', $recruiter->id) }}" class="portal-filters-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Company name or email</label>
                        <input type="search" class="form-control" name="q" value="{{ request('q') }}"
                               placeholder="Search Hirevo employers…">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary px-4" type="submit" style="border-radius:10px;">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.portal.recruiter-assignments.update', $recruiter->id) }}">
            @csrf
            <div class="portal-table-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th style="width:48px">
                                <input type="checkbox" class="form-check-input" id="select-all-employers">
                            </th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th class="text-end">Quick remove</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($employers as $employer)
                            @php
                                $profile = $employer->referrerProfile;
                                $companyName = $profile?->company_name ?? $employer->name;
                                $isAssigned = in_array((int) $employer->id, $assignedIds, true);
                            @endphp
                            <tr @class(['table-primary' => $isAssigned])>
                                <td>
                                    <input type="checkbox" class="form-check-input employer-checkbox"
                                           name="employer_ids[]" value="{{ $employer->id }}"
                                           @checked($isAssigned)>
                                </td>
                                <td class="fw-semibold">{{ $companyName }}</td>
                                <td>{{ $employer->name }}</td>
                                <td class="text-muted">{{ $profile?->company_email ?? $employer->email }}</td>
                                <td class="text-end">
                                    @if($isAssigned)
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                style="border-radius:8px;"
                                                formaction="{{ route('admin.portal.recruiter-assignments.destroy', [$recruiter->id, $employer->id]) }}"
                                                formmethod="POST"
                                                onclick="if(confirm('Remove this company from {{ $recruiter->name }}?')) { document.getElementById('remove-form-{{ $employer->id }}').submit(); }">
                                            Remove
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="portal-empty">
                                        <i class="bi bi-building"></i>
                                        No companies match your search.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.crm-pagination-footer', ['paginator' => $employers])
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                <p class="text-muted small mb-0">Check companies to assign. Saving replaces the full assignment set for this recruiter.</p>
                <button type="submit" class="btn btn-primary px-4" style="border-radius:10px;">
                    <i class="bi bi-check2 me-1"></i> Save assignments
                </button>
            </div>
        </form>

        @foreach($employers as $employer)
            @if(in_array((int) $employer->id, $assignedIds, true))
                <form id="remove-form-{{ $employer->id }}" method="POST"
                      action="{{ route('admin.portal.recruiter-assignments.destroy', [$recruiter->id, $employer->id]) }}"
                      class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('select-all-employers')?.addEventListener('change', function () {
        document.querySelectorAll('.employer-checkbox').forEach(function (cb) {
            cb.checked = this.checked;
        }, this);
    });
</script>
@endpush
