@php
    /** @var \App\Support\DashboardPeriod $period */
    $presets = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This week',
        'last_week' => 'Last week',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_quarter' => 'This quarter',
        'this_year' => 'This year',
    ];
    $baseParams = request()->except(['period', 'from', 'to', 'page']);
@endphp
<div class="card shadow-soft mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2">
            @foreach($baseParams as $key => $val)
                @if(is_array($val))
                    @foreach($val as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            <span class="small fw-semibold text-muted me-1"><i class="bi bi-calendar3 me-1"></i>Period</span>
            @foreach($presets as $key => $label)
                <a href="{{ route('admin.dashboard', array_merge($baseParams, ['period' => $key])) }}"
                   class="btn btn-sm {{ $period->key === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
            <span class="text-muted small ms-2">{{ $period->label() }}</span>
        </form>
    </div>
</div>
