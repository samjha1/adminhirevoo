@extends('layouts.app')

@section('title', 'Referral Form Submissions')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Referral Form Submissions</h1>
            <div class="page-subtitle">Users asking for referral help ("please contact me")</div>
        </div>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Source</th>
                    <th>Submitted</th>
                </tr>
                </thead>
                <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td class="fw-semibold">{{ $submission->name ?? '—' }}</td>
                        <td>{{ $submission->email ?? '—' }}</td>
                        <td>{{ $submission->phone ?? '—' }}</td>
                        <td style="max-width: 360px; white-space: normal;">{{ $submission->message ?? '—' }}</td>
                        <td><span class="badge text-bg-light">{{ $submission->source ?? '—' }}</span></td>
                        <td class="text-muted">{{ $submission->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No referral submissions found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $submissions->links() }}</div>
@endsection

