@php
    $trends = $trends ?? [];
    $leadTrend = $trends['leadTrend'] ?? ['labels' => [], 'talent' => [], 'company' => []];
    $revenueTrend = $trends['revenueTrend'] ?? ['labels' => [], 'talent' => [], 'company' => []];
    $statusDistribution = $trends['statusDistribution'] ?? [];
    $meetingsVsClosures = $trends['meetingsVsClosures'] ?? ['labels' => [], 'meetings' => [], 'closures' => []];
    $talentPie = $statusDistribution['talent'] ?? [];
    $companyPie = $statusDistribution['company'] ?? [];
@endphp
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const leadTrend = @json($leadTrend);
    const revenueTrend = @json($revenueTrend);
    const talentPie = @json(array_values($talentPie));
    const talentPieLabels = @json(array_keys($talentPie));
    const companyPie = @json(array_values($companyPie));
    const companyPieLabels = @json(array_keys($companyPie));
    const mvc = @json($meetingsVsClosures);

    const chartDefaults = { responsive: true, maintainAspectRatio: false };

    const leadEl = document.getElementById('chart-lead-trend');
    if (leadEl) {
        new Chart(leadEl, {
            type: 'line',
            data: {
                labels: leadTrend.labels,
                datasets: [
                    { label: 'Talent leads', data: leadTrend.talent, borderColor: '#2563eb', tension: 0.3 },
                    { label: 'Companies', data: leadTrend.company, borderColor: '#059669', tension: 0.3 }
                ]
            },
            options: chartDefaults
        });
    }

    const revEl = document.getElementById('chart-revenue-trend');
    if (revEl) {
        new Chart(revEl, {
            type: 'bar',
            data: {
                labels: revenueTrend.labels,
                datasets: [
                    { label: 'Talent ₹', data: revenueTrend.talent, backgroundColor: 'rgba(37,99,235,.7)' },
                    { label: 'Company ₹', data: revenueTrend.company, backgroundColor: 'rgba(5,150,105,.7)' }
                ]
            },
            options: { ...chartDefaults, scales: { x: { stacked: false }, y: { beginAtZero: true } } }
        });
    }

    const pieTalent = document.getElementById('chart-talent-pie');
    if (pieTalent && talentPieLabels.length) {
        new Chart(pieTalent, {
            type: 'doughnut',
            data: { labels: talentPieLabels, datasets: [{ data: talentPie }] },
            options: chartDefaults
        });
    }

    const pieCompany = document.getElementById('chart-company-pie');
    if (pieCompany && companyPieLabels.length) {
        new Chart(pieCompany, {
            type: 'doughnut',
            data: { labels: companyPieLabels, datasets: [{ data: companyPie }] },
            options: chartDefaults
        });
    }

    const mvcEl = document.getElementById('chart-meetings-closures');
    if (mvcEl) {
        new Chart(mvcEl, {
            type: 'bar',
            data: {
                labels: mvc.labels,
                datasets: [
                    { label: 'Meetings / follow-ups', data: mvc.meetings, backgroundColor: '#f59e0b' },
                    { label: 'Closures', data: mvc.closures, backgroundColor: '#10b981' }
                ]
            },
            options: chartDefaults
        });
    }
})();
</script>
@endpush
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-soft h-100">
            <div class="card-header bg-white fw-semibold">Lead trend</div>
            <div class="card-body" style="height:260px">
                <canvas id="chart-lead-trend"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-soft h-100">
            <div class="card-header bg-white fw-semibold">Revenue trend</div>
            <div class="card-body" style="height:260px">
                <canvas id="chart-revenue-trend"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-soft h-100">
            <div class="card-header bg-white fw-semibold small">Talent status</div>
            <div class="card-body" style="height:220px">
                <canvas id="chart-talent-pie"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-soft h-100">
            <div class="card-header bg-white fw-semibold small">Company stages</div>
            <div class="card-body" style="height:220px">
                <canvas id="chart-company-pie"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-soft h-100">
            <div class="card-header bg-white fw-semibold small">Meetings vs closures</div>
            <div class="card-body" style="height:220px">
                <canvas id="chart-meetings-closures"></canvas>
            </div>
        </div>
    </div>
</div>
