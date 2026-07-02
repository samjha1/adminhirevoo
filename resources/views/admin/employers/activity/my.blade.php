@extends('layouts.app')

@section('title', 'My activity')

@push('styles')
@include('admin.employers.activity.partials.styles')
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
        @include('partials.company-section-tabs', ['activeTab' => 'my-activity'])

        <div class="company-toolbar">
            <span class="company-total-badge">
                <i class="bi bi-activity text-success"></i>
                <strong>{{ number_format($activities->total()) }}</strong> activities
                <span class="text-muted">· {{ $dateFilter->label() }}</span>
            </span>
            <div class="company-toolbar-actions">
                @if($canViewTeam ?? false)
                    <a href="{{ route('admin.employers.activity.team') }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-people me-1"></i>Team activity
                    </a>
                @endif
                <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-buildings me-1"></i>Pipeline
                </a>
            </div>
        </div>

        <p class="text-muted mb-3">
            Track your company pipeline work — stage updates, follow-ups, meetings, calls, and assignments.
        </p>

        @include('admin.employers.activity.partials.filters')

        @include('admin.employers.activity.partials.activity-table', [
            'activities' => $activities,
            'showStaffColumn' => false,
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
