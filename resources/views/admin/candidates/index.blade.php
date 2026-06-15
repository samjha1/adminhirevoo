@extends('layouts.app')

@section('title', 'Candidates')

@push('styles')
<style>
    .leads-stage-strip {
        display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .35rem;
        scrollbar-width: thin;
    }
    .leads-stage-pill {
        flex-shrink: 0; display: inline-flex; align-items: center; gap: .45rem;
        padding: .4rem .75rem; border-radius: 999px; text-decoration: none;
        font-size: .78rem; font-weight: 600; color: #475569;
        background: #fff; border: 1px solid #e2e8f0;
        transition: border-color .15s, background .15s, color .15s;
    }
    .leads-stage-pill:hover { border-color: #93c5fd; color: #1d4ed8; background: #f8fafc; }
    .leads-stage-pill.active {
        background: linear-gradient(135deg, #2563eb, #3b82f6); border-color: transparent;
        color: #fff;
    }
    .leads-stage-pill .count {
        font-size: .7rem; font-weight: 700; padding: .1rem .45rem; border-radius: 999px;
        background: rgba(15, 23, 42, .08);
    }
    .leads-stage-pill.active .count { background: rgba(255,255,255,.22); }
    .leads-filter-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .75rem; font-weight: 600; color: #1e40af;
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 999px;
        padding: .25rem .65rem; text-decoration: none;
    }
    .sector-badge { font-size: .72rem; font-weight: 600; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Candidates</h1>
            <div class="page-subtitle">Browse job seekers by role category for marketing and outreach</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form class="d-flex gap-2 flex-wrap" method="GET" action="{{ route('admin.candidates.index') }}">
                @if(request('sector'))
                    <input type="hidden" name="sector" value="{{ request('sector') }}">
                @endif
                <input class="form-control" name="q" placeholder="Search name, email, skills…" value="{{ request('q') }}" style="width: 240px">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button>
            </form>
            @if(auth('admin')->user()?->canPermission('leads.export'))
                <a href="{{ route('admin.candidates.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            @endif
        </div>
    </div>

    @include('partials.portal-date-filter', ['dateFilter' => $dateFilter ?? null])

    @if(($sectorCounts ?? []) !== [])
        @include('partials.candidate-sector-filter', [
            'sectorRoute' => route('admin.candidates.index'),
        ])
    @endif

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th><a href="?sort=name&dir={{ $sort === 'name' && $direction === 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except(['sort', 'dir', 'page'])) }}">Name</a></th>
                    <th>Sector</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Preferred role</th>
                    <th>Experience</th>
                    <th><a href="?sort=created_at&dir={{ $sort === 'created_at' && $direction === 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except(['sort', 'dir', 'page'])) }}">Registered</a></th>
                    <th>Applications</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($candidates as $candidate)
                    @php
                        $profile = $candidate->candidateProfile;
                        $sectorKey = $resolvedSectors[$candidate->id] ?? null;
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $candidate->name }}</td>
                        <td>
                            @if($sectorKey)
                                <span class="badge text-bg-primary sector-badge">{{ $sectorCatalog[$sectorKey]['short'] ?? $sectorCatalog[$sectorKey]['label'] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $candidate->email }}</td>
                        <td>{{ $candidate->phone ?? '—' }}</td>
                        <td class="text-truncate" style="max-width: 160px">{{ $profile?->preferred_job_role ?? '—' }}</td>
                        <td>{{ $profile?->experience_years ? $profile->experience_years.' yrs' : '—' }}</td>
                        <td class="text-muted">{{ $candidate->created_at?->format('M j, Y') }}</td>
                        <td><span class="badge text-bg-light">{{ $candidate->employer_applications_count }}</span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.candidates.show', $candidate->id) }}">
                                Profile <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No candidates found for this sector.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $candidates->links() }}</div>
@endsection
