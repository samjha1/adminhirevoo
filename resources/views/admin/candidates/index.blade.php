@extends('layouts.app')

@section('title', 'Candidates')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Candidates</h1>
            <div class="page-subtitle">Manage registered job seekers</div>
        </div>
        <form class="d-flex gap-2 flex-wrap" method="GET" action="{{ route('admin.candidates.index') }}">
            <input class="form-control" name="q" placeholder="Search name, email, skills…" value="{{ request('q') }}" style="width: 240px">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button>
        </form>
    </div>

    @include('partials.portal-date-filter', ['dateFilter' => $dateFilter ?? null])

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th><a href="?sort=name&dir={{ $sort === 'name' && $direction === 'asc' ? 'desc' : 'asc' }}">Name</a></th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Experience</th>
                    <th>Skills</th>
                    <th><a href="?sort=created_at&dir={{ $sort === 'created_at' && $direction === 'asc' ? 'desc' : 'asc' }}">Registered</a></th>
                    <th>Applications</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($candidates as $candidate)
                    @php $profile = $candidate->candidateProfile; @endphp
                    <tr>
                        <td class="fw-semibold">{{ $candidate->name }}</td>
                        <td class="text-muted">{{ $candidate->email }}</td>
                        <td>{{ $candidate->phone ?? '—' }}</td>
                        <td>{{ $profile?->experience_years ? $profile->experience_years.' yrs' : '—' }}</td>
                        <td class="text-truncate" style="max-width: 200px">{{ \Illuminate\Support\Str::limit($profile?->skills ?? '—', 60) }}</td>
                        <td class="text-muted">{{ $candidate->created_at?->format('M j, Y') }}</td>
                        <td><span class="badge text-bg-light">{{ $candidate->employer_applications_count }}</span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.candidates.show', $candidate->id) }}">
                                Profile <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No candidates found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $candidates->links() }}</div>
@endsection
