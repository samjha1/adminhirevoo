@php
    $activeTab = $activeTab ?? (request()->routeIs('admin.employers.outreach.*') ? 'outreach' : 'pipeline');
    $admin = auth('admin')->user();
    $canViewTeamActivity = app(\App\Services\SalesTeamActivityScopeService::class)->canViewTeamActivity($admin);
@endphp
@once
@push('styles')
<style>
    .company-section-tabs {
        display: flex; flex-wrap: wrap; gap: .5rem;
        padding: .35rem; background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .company-section-tab {
        display: inline-flex; align-items: center; padding: .5rem 1rem;
        border-radius: 8px; text-decoration: none; font-size: .85rem; font-weight: 600;
        color: #475569; transition: background .15s, color .15s;
    }
    .company-section-tab:hover { background: #fff; color: #047857; }
    .company-section-tab.active {
        background: #fff; color: #047857;
        box-shadow: 0 2px 8px rgba(5, 150, 105, .15);
    }
</style>
@endpush
@endonce
<div class="company-section-tabs mb-3" role="tablist" aria-label="Company sections">
    <a href="{{ route('admin.employers.pipeline.index') }}"
       class="company-section-tab @if($activeTab === 'pipeline') active @endif"
       role="tab"
       @if($activeTab === 'pipeline') aria-selected="true" @endif>
        <i class="bi bi-buildings me-1"></i>Signed-up companies
    </a>
    <a href="{{ route('admin.employers.outreach.index') }}"
       class="company-section-tab @if($activeTab === 'outreach') active @endif"
       role="tab"
       @if($activeTab === 'outreach') aria-selected="true" @endif>
        <i class="bi bi-person-plus me-1"></i>Outreach leads
        <span class="text-muted small ms-1">(not signed up)</span>
    </a>
    <a href="{{ route('admin.employers.activity.my') }}"
       class="company-section-tab @if($activeTab === 'my-activity') active @endif"
       role="tab"
       @if($activeTab === 'my-activity') aria-selected="true" @endif>
        <i class="bi bi-person-check me-1"></i>My activity
    </a>
    @if($canViewTeamActivity)
        <a href="{{ route('admin.employers.activity.team') }}"
           class="company-section-tab @if($activeTab === 'team-activity') active @endif"
           role="tab"
           @if($activeTab === 'team-activity') aria-selected="true" @endif>
            <i class="bi bi-people me-1"></i>Team activity
        </a>
    @endif
</div>
