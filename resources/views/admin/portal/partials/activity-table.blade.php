<div class="portal-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Date</th>
                @if($showRecruiterColumn ?? false)
                    <th>Recruiter</th>
                @endif
                <th>Company</th>
                <th>Job</th>
                <th>Candidate</th>
                <th>Match</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($applications as $application)
                @php
                    $job = $application->job;
                    $employer = $job?->employer;
                    $companyName = $employer?->referrerProfile?->company_name ?: $employer?->name ?: '—';
                    $candidate = $application->candidate;
                    $match = (int) ($application->job_match_score ?? $application->ats_score ?? 0);
                @endphp
                <tr>
                    <td class="text-muted small">{{ $application->created_at?->format('M j, Y · g:i A') }}</td>
                    @if($showRecruiterColumn ?? false)
                        <td>
                            <span class="fw-semibold">{{ $application->appliedByAdmin?->name ?? '—' }}</span>
                            @if($application->applied_by_admin_id)
                                <span class="badge rounded-pill bg-info-subtle text-info-emphasis ms-1">Recruiter apply</span>
                            @endif
                        </td>
                    @endif
                    <td>{{ $companyName }}</td>
                    <td>{{ $job?->title ?? '—' }}</td>
                    <td>{{ $candidate?->name ?? '—' }}</td>
                    <td><span class="portal-match-badge">{{ $match }}%</span></td>
                    <td><span class="portal-badge status-{{ $application->status ?? 'applied' }}">{{ $application->status ?? 'applied' }}</span></td>
                    <td class="text-end">
                        <a href="{{ route($appShowRoute, $application->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($showRecruiterColumn ?? false) ? 8 : 7 }}">
                        <div class="portal-empty">
                            <i class="bi bi-clipboard-data"></i>
                            No recruiter apply activity found.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.crm-pagination-footer', ['paginator' => $applications])
</div>
