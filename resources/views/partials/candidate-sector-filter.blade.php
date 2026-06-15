@php
    $sectorCatalog = $sectorCatalog ?? [];
    $sectorCounts = $sectorCounts ?? [];
    $activeSector = request('sector', '');
    $sectorFilterBase = request()->except(['sector', 'leads_page', 'page']);
    $sectorRoute = $sectorRoute ?? route('admin.candidates.index');
    $sectorTotal = (int) ($sectorGrandTotal ?? array_sum($sectorCounts));
@endphp

<div class="leads-stage-strip mb-3" role="navigation" aria-label="Filter by job sector">
    <a href="{{ $sectorRoute }}?{{ http_build_query($sectorFilterBase) }}"
       class="leads-stage-pill @if($activeSector === '') active @endif">
        All sectors <span class="count">{{ number_format($sectorTotal) }}</span>
    </a>
    @foreach($sectorCatalog as $key => $sector)
        @php $count = (int) ($sectorCounts[$key] ?? 0); @endphp
        @if($count > 0 || $activeSector === $key)
            <a href="{{ $sectorRoute }}?{{ http_build_query(array_merge($sectorFilterBase, ['sector' => $key])) }}"
               class="leads-stage-pill @if($activeSector === $key) active @endif">
                {{ $sector['short'] ?? $sector['label'] }}
                <span class="count">{{ number_format($count) }}</span>
            </a>
        @endif
    @endforeach
    @php $uncategorized = (int) ($sectorCounts['uncategorized'] ?? 0); @endphp
    @if($uncategorized > 0 || $activeSector === 'uncategorized')
        <a href="{{ $sectorRoute }}?{{ http_build_query(array_merge($sectorFilterBase, ['sector' => 'uncategorized'])) }}"
           class="leads-stage-pill @if($activeSector === 'uncategorized') active @endif">
            Uncategorized <span class="count">{{ number_format($uncategorized) }}</span>
        </a>
    @endif
</div>

@if($activeSector !== '')
    <div class="leads-active-filters mb-3">
        <a class="leads-filter-chip" href="{{ $sectorRoute }}?{{ http_build_query($sectorFilterBase) }}">
            Sector: {{ $sectorCatalog[$activeSector]['label'] ?? 'Uncategorized' }} <i class="bi bi-x-lg"></i>
        </a>
    </div>
@endif
