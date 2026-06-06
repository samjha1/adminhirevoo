@extends('layouts.app')

@section('title', 'Employer Details')

@section('content')
    @php
        $profile = $employer->referrerProfile;
        $approved = $profile && $profile->is_approved;
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $employer->name }}</h1>
            <div class="page-subtitle">{{ $employer->email }}</div>
            <div class="mt-2">
                @if($approved)
                    <span class="badge text-bg-success"><i class="bi bi-patch-check me-1"></i>Approved</span>
                @else
                    <span class="badge text-bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.employers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>

            <form method="POST" action="{{ route('admin.employers.approve', $employer->id) }}" class="m-0">
                @csrf
                <button class="btn btn-success" type="submit" @disabled(! $profile || $approved)>
                    <i class="bi bi-check2-circle me-1"></i>Approve
                </button>
            </form>

            <form method="POST" action="{{ route('admin.employers.reject', $employer->id) }}" class="m-0">
                @csrf
                <button class="btn btn-danger" type="submit" @disabled(! $profile || ! $approved)>
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 g-lg-4">
        <div class="col-lg-8">
            <div class="card shadow-soft">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0">Company profile</h5>
                        @if($profile && $profile->approved_at)
                            <span class="small text-muted">Updated: {{ $profile->updated_at?->format('Y-m-d H:i') }}</span>
                        @endif
                    </div>
                    @if(! $profile)
                        <div class="text-muted">No referrer profile found yet.</div>
                    @else
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Company name</dt>
                            <dd class="col-sm-8">{{ $profile->company_name ?? '—' }}</dd>

                            <dt class="col-sm-4">Company email</dt>
                            <dd class="col-sm-8">{{ $profile->company_email ?? '—' }}</dd>

                            <dt class="col-sm-4">Designation</dt>
                            <dd class="col-sm-8">{{ $profile->designation ?? '—' }}</dd>

                            <dt class="col-sm-4">Department</dt>
                            <dd class="col-sm-8">{{ $profile->department ?? '—' }}</dd>

                            <dt class="col-sm-4">Phone</dt>
                            <dd class="col-sm-8">{{ $profile->phone ?? '—' }}</dd>

                            <dt class="col-sm-4">Company website</dt>
                            <dd class="col-sm-8">{{ $profile->company_website ?? '—' }}</dd>

                            <dt class="col-sm-4">Company GST</dt>
                            <dd class="col-sm-8">{{ $profile->gst_number ?? '—' }}</dd>

                            <dt class="col-sm-4">Company email verified</dt>
                            <dd class="col-sm-8">{{ $profile->company_email_verified ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">GST verified</dt>
                            <dd class="col-sm-8">{{ $profile->gst_verified ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">Invoice consent</dt>
                            <dd class="col-sm-8">{{ $profile->invoice_consent ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">Approved at</dt>
                            <dd class="col-sm-8">{{ optional($profile->approved_at)->format('Y-m-d H:i') ?? '—' }}</dd>
                        </dl>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-soft">
                <div class="card-body">
                    <h5 class="card-title mb-3">Employer account</h5>
                    <dl class="row mb-0">
                        <dt class="col-6">User ID</dt>
                        <dd class="col-6 text-end">{{ $employer->id }}</dd>

                        <dt class="col-6">Role</dt>
                        <dd class="col-6 text-end">{{ ucfirst($employer->role) }}</dd>

                        <dt class="col-6">Status</dt>
                        <dd class="col-6 text-end">{{ ucfirst($employer->status ?? 'active') }}</dd>

                        <dt class="col-6">Registered</dt>
                        <dd class="col-6 text-end">{{ $employer->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>

                        <dt class="col-6">Last updated</dt>
                        <dd class="col-6 text-end">{{ $employer->updated_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-soft mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Job posting summary</h5>
                    <dl class="row mb-0">
                        <dt class="col-8">Total jobs</dt>
                        <dd class="col-4 text-end fw-semibold">{{ $jobStats['total'] }}</dd>

                        <dt class="col-8">Active jobs</dt>
                        <dd class="col-4 text-end">{{ $jobStats['active'] }}</dd>

                        <dt class="col-8">Draft jobs</dt>
                        <dd class="col-4 text-end">{{ $jobStats['draft'] }}</dd>

                        <dt class="col-8">Closed jobs</dt>
                        <dd class="col-4 text-end">{{ $jobStats['closed'] }}</dd>

                        <dt class="col-8">Total applications</dt>
                        <dd class="col-4 text-end fw-semibold">{{ $jobStats['applications'] }}</dd>

                        <dt class="col-8">Applications today</dt>
                        <dd class="col-4 text-end">{{ $jobStats['applications_today'] ?? 0 }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-soft mt-3 mt-lg-4">
        <div class="card-body pb-0">
            <h5 class="card-title mb-1">Posted jobs</h5>
            <p class="text-muted mb-3">All jobs posted by this employer.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Applications</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td class="fw-semibold">{{ $job->title ?? '—' }}</td>
                        <td>{{ $job->job_department ?? '—' }}</td>
                        <td>{{ $job->location_display ?? '—' }}</td>
                        <td>
                            <div>{{ $job->job_type ? ucwords(str_replace('_', ' ', $job->job_type)) : '—' }}</div>
                            <div class="small text-muted">{{ $job->work_location_type ? ucwords(str_replace('_', ' ', $job->work_location_type)) : '—' }}</div>
                        </td>
                        <td>
                            @php
                                $status = $job->status ?? 'draft';
                                $statusClass = $status === 'active' ? 'success' : ($status === 'closed' ? 'danger' : 'warning');
                            @endphp
                            <span class="badge text-bg-{{ $statusClass }}">{{ ucfirst($status) }}</span>
                        </td>
                        <td>{{ $job->applications_count }}</td>
                        <td class="text-muted">{{ $job->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No jobs posted by this employer yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
            <div class="card-body pt-3">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

    <div class="card shadow-soft mt-3 mt-lg-4">
        <div class="card-body pb-0">
            <h5 class="card-title mb-1">Applications</h5>
            <p class="text-muted mb-3">Candidates who applied to this company's jobs.</p>
            @include('partials.portal-date-filter', [
                'dateFilter' => $appDateFilter ?? null,
                'action' => route('admin.employers.show', $employer->id),
                'periodParam' => 'app_period',
            ])
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Job</th>
                    <th>Applied</th>
                    <th>Resume</th>
                </tr>
                </thead>
                <tbody>
                @forelse($applications ?? [] as $application)
                    @php $resume = $application->candidate?->resumes?->first(); @endphp
                    <tr>
                        <td class="fw-semibold">{{ $application->candidate?->name ?? '—' }}</td>
                        <td>{{ $application->candidate?->email ?? '—' }}</td>
                        <td>{{ $application->candidate?->phone ?? '—' }}</td>
                        <td>{{ $application->job?->title ?? '—' }}</td>
                        <td class="text-muted">{{ $application->created_at?->format('M j, Y') }}</td>
                        <td>
                            @if($resume?->file_path)
                                <a href="{{ $resume->file_path }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No applications found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($applications) && $applications->hasPages())
            <div class="card-body pt-3">{{ $applications->links() }}</div>
        @endif
    </div>
@endsection

