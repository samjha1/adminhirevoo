@extends('layouts.app')

@section('title', $candidate->name)

@section('content')
    @php
        $profile = $candidate->candidateProfile;
        $resume = $candidate->resumes?->sortByDesc('created_at')->first();
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $candidate->name }}</h1>
            <div class="page-subtitle">{{ $candidate->email }} · Registered {{ $candidate->created_at?->format('M j, Y') }}
                @if(!empty($sectorLabel))
                    · <span class="badge text-bg-primary">{{ $sectorLabel }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('admin.candidates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-soft mb-3">
                <div class="card-header bg-white fw-semibold">Personal information</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $candidate->name }}</dd>
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $candidate->email }}</dd>
                        <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $candidate->phone ?? '—' }}</dd>
                        <dt class="col-sm-4">Job sector</dt><dd class="col-sm-8">{{ $sectorLabel ?? '—' }}</dd>
                        <dt class="col-sm-4">Preferred role</dt><dd class="col-sm-8">{{ $profile?->preferred_job_role ?? '—' }}</dd>
                        <dt class="col-sm-4">Headline</dt><dd class="col-sm-8">{{ $profile?->headline ?? '—' }}</dd>
                        <dt class="col-sm-4">Location</dt><dd class="col-sm-8">{{ $profile?->location ?? '—' }}</dd>
                        <dt class="col-sm-4">Experience</dt><dd class="col-sm-8">{{ $profile?->experience_years ? $profile->experience_years.' years' : '—' }}</dd>
                        <dt class="col-sm-4">Expected salary</dt><dd class="col-sm-8">{{ $profile?->expected_salary ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-soft mb-3">
                <div class="card-header bg-white fw-semibold">Skills</div>
                <div class="card-body">{{ $profile?->skills ?? '—' }}</div>
            </div>

            <div class="card shadow-soft mb-3">
                <div class="card-header bg-white fw-semibold">Experience</div>
                <div class="card-body">
                    @if(is_array($profile?->work_experience) && count($profile->work_experience))
                        @foreach($profile->work_experience as $exp)
                            <div class="mb-2 pb-2 border-bottom">
                                <strong>{{ $exp['title'] ?? $exp['role'] ?? 'Role' }}</strong>
                                @if(!empty($exp['company'])) — {{ $exp['company'] }} @endif
                                @if(!empty($exp['duration']))<div class="small text-muted">{{ $exp['duration'] }}</div>@endif
                            </div>
                        @endforeach
                    @else
                        <span class="text-muted">No structured experience data.</span>
                    @endif
                </div>
            </div>

            <div class="card shadow-soft">
                <div class="card-header bg-white fw-semibold">Education</div>
                <div class="card-body">
                    @if(is_array($profile?->education_history) && count($profile->education_history))
                        @foreach($profile->education_history as $edu)
                            <div class="mb-2">{{ $edu['degree'] ?? $edu['institution'] ?? json_encode($edu) }}</div>
                        @endforeach
                    @else
                        {{ $profile?->education ?? '—' }}
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-soft mb-3">
                <div class="card-header bg-white fw-semibold">Resume</div>
                <div class="card-body">
                    @if($resume?->file_path)
                        <a href="{{ $resume->file_path }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-pdf me-1"></i>View resume
                        </a>
                    @else
                        <span class="text-muted">No resume uploaded.</span>
                    @endif
                </div>
            </div>

            <div class="card shadow-soft">
                <div class="card-header bg-white fw-semibold">Applied jobs history</div>
                <div class="list-group list-group-flush">
                    @forelse($candidate->employerApplications as $app)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ $app->job?->title ?? 'Job' }}</div>
                            <div class="small text-muted">{{ $app->job?->employer?->referrerProfile?->company_name ?? '—' }}</div>
                            <div class="small">
                                <span class="badge text-bg-light text-capitalize">{{ $app->status }}</span>
                                · {{ $app->created_at?->format('M j, Y') }}
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">No applications yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
