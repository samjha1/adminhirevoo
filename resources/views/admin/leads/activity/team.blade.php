@extends('layouts.app')

@section('title', 'Team activity')

@push('styles')
<style>
    .leads-activity-table-wrap {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .04);
    }
    .leads-activity-table thead th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .leads-activity-summary {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }
    .leads-activity-summary-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: .85rem 1rem;
        text-decoration: none;
        color: inherit;
        transition: border-color .15s, box-shadow .15s;
    }
    .leads-activity-summary-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 14px rgba(37, 99, 235, .12);
        color: inherit;
    }
    .leads-activity-summary-card .count {
        font-size: 1.35rem;
        font-weight: 800;
        color: #1d4ed8;
        line-height: 1.1;
    }
    .leads-activity-summary-card .name {
        font-weight: 600;
        font-size: .85rem;
        color: #0f172a;
    }
    .leads-activity-summary-card .role {
        font-size: .72rem;
        color: #64748b;
    }
</style>
@endpush

@section('content')
    <div class="leads-page">
        @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
        @include('partials.talent-section-tabs', ['activeTab' => 'team-activity'])

        <div class="leads-toolbar">
            <span class="leads-total-badge">
                <i class="bi bi-people text-primary"></i>
                <strong>{{ number_format($activities->total()) }}</strong> activities
                <span class="text-muted">· {{ $dateFilter->label() }}</span>
            </span>
            <div class="leads-toolbar-actions">
                <a href="{{ route('admin.leads.activity.my') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person me-1"></i>My activity
                </a>
                <a href="{{ route('admin.leads.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person-workspace me-1"></i>Pipeline
                </a>
            </div>
        </div>

        <p class="text-muted mb-3">
            Monitor your team's talent pipeline activity. Managers see their employees; ASM sees managers and employees; admins see everyone.
        </p>

        @if(! empty($staffSummaryToday))
            <div class="mb-2">
                <h2 class="h6 fw-bold text-secondary mb-2">Today's activity by team member</h2>
                <div class="leads-activity-summary">
                    @foreach($staffSummaryToday as $row)
                        <a href="{{ route('admin.leads.activity.team', ['staff_id' => $row['admin_id'], 'period' => 'today']) }}"
                           class="leads-activity-summary-card">
                            <div class="count">{{ number_format($row['count']) }}</div>
                            <div class="name">{{ $row['admin_name'] }}</div>
                            <div class="role">{{ $row['role_label'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @include('admin.leads.activity.partials.filters', ['teamView' => true])

        @include('admin.leads.activity.partials.activity-table', [
            'activities' => $activities,
            'showStaffColumn' => true,
        ])
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.talent-activity-period-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const form = sel.closest('form');
            if (!form) return;
            form.querySelectorAll('.talent-activity-custom-dates').forEach(function (el) {
                el.classList.toggle('d-none', sel.value !== 'custom');
            });
        });
    });
</script>
@endpush
