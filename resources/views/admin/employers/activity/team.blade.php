@extends('layouts.app')

@section('title', 'Team activity')

@push('styles')
<style>
    .company-activity-table-wrap {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .04);
    }
    .company-activity-table thead th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .company-activity-summary {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }
    .company-activity-summary-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: .85rem 1rem;
        text-decoration: none;
        color: inherit;
        transition: border-color .15s, box-shadow .15s;
    }
    .company-activity-summary-card:hover {
        border-color: #6ee7b7;
        box-shadow: 0 4px 14px rgba(5, 150, 105, .12);
        color: inherit;
    }
    .company-activity-summary-card .count {
        font-size: 1.35rem;
        font-weight: 800;
        color: #047857;
        line-height: 1.1;
    }
    .company-activity-summary-card .name {
        font-weight: 600;
        font-size: .85rem;
        color: #0f172a;
    }
    .company-activity-summary-card .role {
        font-size: .72rem;
        color: #64748b;
    }
</style>
@endpush

@section('content')
    <div class="company-page">
        @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
        @include('partials.company-section-tabs', ['activeTab' => 'team-activity'])

        <div class="company-toolbar">
            <span class="company-total-badge">
                <i class="bi bi-people text-success"></i>
                <strong>{{ number_format($activities->total()) }}</strong> activities
                <span class="text-muted">· {{ $dateFilter->label() }}</span>
            </span>
            <div class="company-toolbar-actions">
                <a href="{{ route('admin.employers.activity.my') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person me-1"></i>My activity
                </a>
                <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-buildings me-1"></i>Pipeline
                </a>
            </div>
        </div>

        <p class="text-muted mb-3">
            Monitor your team's company pipeline activity. Managers see their employees; ASM sees managers and employees; admins see everyone.
        </p>

        @if(! empty($staffSummaryToday))
            <div class="mb-2">
                <h2 class="h6 fw-bold text-secondary mb-2">Today's activity by team member</h2>
                <div class="company-activity-summary">
                    @foreach($staffSummaryToday as $row)
                        <a href="{{ route('admin.employers.activity.team', ['staff_id' => $row['admin_id'], 'period' => 'today']) }}"
                           class="company-activity-summary-card">
                            <div class="count">{{ number_format($row['count']) }}</div>
                            <div class="name">{{ $row['admin_name'] }}</div>
                            <div class="role">{{ $row['role_label'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @include('admin.employers.activity.partials.filters', ['teamView' => true])

        @include('admin.employers.activity.partials.activity-table', [
            'activities' => $activities,
            'showStaffColumn' => true,
        ])
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.company-activity-period-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const form = sel.closest('form');
            if (!form) return;
            form.querySelectorAll('.company-activity-custom-dates').forEach(function (el) {
                el.classList.toggle('d-none', sel.value !== 'custom');
            });
        });
    });
</script>
@endpush
