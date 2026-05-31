@php
    $items = $items ?? [];
    $title = $title ?? 'Activity in period';
    $pipelineUrl = $pipelineUrl ?? null;
    $emptyText = $emptyText ?? 'No activity in this period.';
@endphp
<div class="card shadow-soft h-100">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small">{{ $title }}</span>
        @if($pipelineUrl)
            <a href="{{ $pipelineUrl }}" class="small text-decoration-none">View all</a>
        @endif
    </div>
    <ul class="list-group list-group-flush small">
        @forelse($items as $item)
            <li class="list-group-item px-3 py-2">
                @if(!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="text-decoration-none text-dark">
                @endif
                <span class="badge bg-light text-secondary border me-1">{{ $item['label'] ?? $item['type'] }}</span>
                {{ $item['detail'] ?? $item['name'] ?? '' }}
                @if(!empty($item['at']))
                    <span class="text-muted d-block mt-1">{{ \Illuminate\Support\Carbon::parse($item['at'])->diffForHumans() }}</span>
                @endif
                @if(!empty($item['url']))
                    </a>
                @endif
            </li>
        @empty
            <li class="list-group-item text-muted px-3 py-3">{{ $emptyText }}</li>
        @endforelse
    </ul>
</div>
