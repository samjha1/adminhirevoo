@php
    $items = $items ?? [];
@endphp
<div class="row g-3 mb-3">
    @foreach($items as $item)
        <div class="col-6 col-md-3">
            <div class="portal-stat-card card border-0 shadow-none">
                <div class="card-body py-3">
                    <div class="portal-stat-icon {{ $item['tone'] ?? 'indigo' }}" style="width:38px;height:38px;font-size:1rem;">
                        <i class="bi {{ $item['icon'] ?? 'bi-graph-up' }}"></i>
                    </div>
                    <div>
                        <div class="portal-stat-label">{{ $item['label'] }}</div>
                        <div class="portal-stat-value" style="font-size:1.35rem;">{{ number_format($item['value'] ?? 0) }}</div>
                        @if(!empty($item['hint']))
                            <div class="portal-stat-delta neutral">{{ $item['hint'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
