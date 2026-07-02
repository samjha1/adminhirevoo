@php
    $activeTab = $activeTab ?? 'pipeline';
    $admin = auth('admin')->user();
    $canViewTeamActivity = app(\App\Services\SalesTeamActivityScopeService::class)->canViewTeamActivity($admin);
@endphp
@once
@push('styles')
<style>
    .talent-section-tabs {
        display: flex; flex-wrap: wrap; gap: .5rem;
        padding: .35rem; background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .talent-section-tab {
        display: inline-flex; align-items: center; padding: .5rem 1rem;
        border-radius: 8px; text-decoration: none; font-size: .85rem; font-weight: 600;
        color: #475569; transition: background .15s, color .15s;
    }
    .talent-section-tab:hover { background: #fff; color: #1d4ed8; }
    .talent-section-tab.active {
        background: #fff; color: #1d4ed8;
        box-shadow: 0 2px 8px rgba(37, 99, 235, .15);
    }
</style>
@endpush
@endonce
<div class="talent-section-tabs mb-3" role="tablist" aria-label="Talent sections">
    <a href="{{ route('admin.leads.index') }}"
       class="talent-section-tab @if($activeTab === 'pipeline') active @endif"
       role="tab"
       @if($activeTab === 'pipeline') aria-selected="true" @endif>
        <i class="bi bi-person-workspace me-1"></i>Candidates
    </a>
    <a href="{{ route('admin.leads.activity.my') }}"
       class="talent-section-tab @if($activeTab === 'my-activity') active @endif"
       role="tab"
       @if($activeTab === 'my-activity') aria-selected="true" @endif>
        <i class="bi bi-person-check me-1"></i>My activity
    </a>
    @if($canViewTeamActivity)
        <a href="{{ route('admin.leads.activity.team') }}"
           class="talent-section-tab @if($activeTab === 'team-activity') active @endif"
           role="tab"
           @if($activeTab === 'team-activity') aria-selected="true" @endif>
            <i class="bi bi-people me-1"></i>Team activity
        </a>
    @endif
</div>
