@extends('layouts.app')

@section('title', 'My activity')

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
</style>
@endpush

@section('content')
    <div class="leads-page">
        @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
        @include('partials.talent-section-tabs', ['activeTab' => 'my-activity'])

        <div class="leads-toolbar">
            <span class="leads-total-badge">
                <i class="bi bi-activity text-primary"></i>
                <strong>{{ number_format($activities->total()) }}</strong> activities
                <span class="text-muted">· {{ $dateFilter->label() }}</span>
            </span>
            <div class="leads-toolbar-actions">
                @if($canViewTeam ?? false)
                    <a href="{{ route('admin.leads.activity.team') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-people me-1"></i>Team activity
                    </a>
                @endif
                <a href="{{ route('admin.leads.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person-workspace me-1"></i>Pipeline
                </a>
            </div>
        </div>

        <p class="text-muted mb-3">
            Track your talent pipeline work — calls, follow-ups, stage updates, notes, and assignments.
        </p>

        @include('admin.leads.activity.partials.filters')

        @include('admin.leads.activity.partials.activity-table', [
            'activities' => $activities,
            'showStaffColumn' => false,
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
