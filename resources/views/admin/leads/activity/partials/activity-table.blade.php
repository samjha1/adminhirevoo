@php
    $showStaffColumn = $showStaffColumn ?? false;
@endphp

<div class="leads-activity-table-wrap">
    <div class="table-responsive">
        <table class="table table-hover align-middle leads-activity-table mb-0">
            <thead>
                <tr>
                    <th>When</th>
                    @if($showStaffColumn)
                        <th>Team member</th>
                    @endif
                    <th>Activity</th>
                    <th>Candidate</th>
                    <th class="text-end">Open</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $item)
                    @php
                        $at = $item['at'] instanceof \Carbon\CarbonInterface
                            ? $item['at']
                            : \Carbon\Carbon::parse($item['at']);
                    @endphp
                    <tr>
                        <td class="text-nowrap">
                            <div class="fw-semibold">{{ $at->format('M j, Y') }}</div>
                            <div class="small text-muted">{{ $at->format('g:i A') }}</div>
                        </td>
                        @if($showStaffColumn)
                            <td>
                                <div class="fw-semibold">{{ $item['admin_name'] }}</div>
                            </td>
                        @endif
                        <td>
                            <span class="badge rounded-pill bg-light text-dark border mb-1">
                                {{ $item['type_label'] }}
                            </span>
                            <div class="fw-semibold">{{ $item['title'] }}</div>
                            @if(! empty($item['detail']))
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($item['detail'], 80) }}</div>
                            @endif
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($item['subject_name'], 40) }}</td>
                        <td class="text-end">
                            @if($item['url'])
                                <a href="{{ $item['url'] }}" class="btn btn-sm btn-outline-primary">View</a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showStaffColumn ? 5 : 4 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-activity d-block fs-2 mb-2 opacity-50"></i>
                            No activity found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($activities->hasPages() || $activities->total() > 0)
        @include('partials.crm-pagination-footer', ['paginator' => $activities])
    @endif
</div>
