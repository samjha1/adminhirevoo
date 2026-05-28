@extends('layouts.app')

@section('title', 'Applied Job Details')

@section('content')
    @php
        $candidate = $application->candidate;
        $profile = $candidate?->candidateProfile;
        $job = $application->job;
        $employer = $job?->employer;
        $companyName = $employer?->referrerProfile?->company_name ?: $employer?->name ?: '—';
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">Application #{{ $application->id }}</h1>
            <div class="page-subtitle">Candidate, company, job role, and qualification status.</div>
        </div>
        <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-soft mb-3">
        <div class="card-header bg-white fw-semibold">Application Status</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.applications.status', $application->id) }}" class="d-flex flex-wrap gap-2 align-items-end">
                @csrf
                <div>
                    <label class="form-label small text-muted mb-1">Status</label>
                    <input type="text" class="form-control" value="{{ ucfirst($application->status ?? 'pending') }}" readonly>
                    <input type="hidden" name="status" value="qualified">
                </div>
                @if($application->status === 'qualified')
                    <span class="badge text-bg-success align-self-center">Already Qualified</span>
                @else
                    <button type="submit" class="btn btn-primary">Mark as Qualified</button>
                @endif
            </form>
        </div>
    </div>

    <div class="card shadow-soft mb-3">
        <div class="card-header bg-white fw-semibold">AI Fit Insights</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="small text-muted">Profile match to job role</div>
                    <div class="fs-4 fw-bold text-primary">{{ (int) ($application->profile_match_percent ?? 0) }}%</div>
                </div>
                <div class="col-md-9">
                    <div class="small text-muted mb-1">AI resume summary</div>
                    <div>{{ $application->ai_resume_summary ?: 'No AI summary available for this candidate resume.' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-soft mb-3">
        <div class="card-header bg-white fw-semibold">Candidate Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Name:</strong><br>{{ $candidate?->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Email:</strong><br>{{ $candidate?->email ?? '—' }}</div>
                <div class="col-md-4"><strong>Phone:</strong><br>{{ $candidate?->phone ?? '—' }}</div>
                <div class="col-md-4"><strong>Preferred Role:</strong><br>{{ $profile?->preferred_job_role ?? '—' }}</div>
                <div class="col-md-4"><strong>Location:</strong><br>{{ $profile?->preferred_job_location ?? ($profile?->location ?? '—') }}</div>
                <div class="col-md-4"><strong>Experience:</strong><br>{{ $profile?->experience_years !== null ? $profile->experience_years.' years' : '—' }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-soft">
        <div class="card-header bg-white fw-semibold">Job & Company Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Company:</strong><br>{{ $companyName }}</div>
                <div class="col-md-6"><strong>Company Email:</strong><br>{{ $employer?->email ?? '—' }}</div>
                <div class="col-md-6"><strong>Role:</strong><br>{{ $job?->title ?? '—' }}</div>
                <div class="col-md-6"><strong>Location:</strong><br>{{ $job?->location_display ?? '—' }}</div>
                <div class="col-md-6"><strong>Application Status:</strong><br><span class="badge text-bg-light text-capitalize">{{ $application->status }}</span></div>
                <div class="col-md-6"><strong>Applied On:</strong><br>{{ $application->created_at?->format('Y-m-d H:i:s') ?? '—' }}</div>
            </div>
        </div>
    </div>
@endsection
