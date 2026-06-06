@php
    $stats = $stats ?? [];
    $cards = [
        ['key' => 'totalCompanies', 'today' => 'companiesToday', 'label' => 'Companies', 'icon' => 'bi-buildings', 'tone' => 'indigo'],
        ['key' => 'totalJobs', 'today' => 'jobsToday', 'label' => 'Jobs posted', 'icon' => 'bi-briefcase', 'tone' => 'emerald'],
        ['key' => 'totalCandidates', 'today' => 'candidatesToday', 'label' => 'Candidates', 'icon' => 'bi-people', 'tone' => 'violet'],
        ['key' => 'totalApplications', 'today' => 'applicationsToday', 'label' => 'Applications', 'icon' => 'bi-send-check', 'tone' => 'amber'],
    ];
@endphp
<div class="row g-3">
    @foreach($cards as $card)
        <div class="col-sm-6 col-xl-3">
            <div class="portal-stat-card card border-0 shadow-none">
                <div class="card-body">
                    <div class="portal-stat-icon {{ $card['tone'] }}">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="portal-stat-label">{{ $card['label'] }}</div>
                        <div class="portal-stat-value">{{ number_format($stats[$card['key']] ?? 0) }}</div>
                        <div class="portal-stat-delta up">
                            <i class="bi bi-arrow-up-short"></i>+{{ number_format($stats[$card['today']] ?? 0) }} today
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
