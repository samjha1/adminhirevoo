@php
    $active = $active ?? '';
    $admin = auth('admin')->user();
    $can = fn (string $slug) => $admin->canPermission($slug);
@endphp
<nav class="portal-nav-pills" aria-label="Job Portal sections">
    @if($can('portal.dashboard.view'))
        <a href="{{ route('admin.portal.dashboard') }}"
           class="portal-nav-pill @if($active === 'dashboard') is-active @endif">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    @endif
    @if($can('portal.jobs.view') || $can('platform.jobs'))
        <a href="{{ route('admin.jobs.index') }}"
           class="portal-nav-pill @if($active === 'jobs') is-active @endif">
            <i class="bi bi-briefcase"></i> Jobs
        </a>
    @endif
    @if($can('portal.applications.view') || $can('applications.view'))
        <a href="{{ $can('leads.view') ? route('admin.applications.index') : route('admin.portal.applications.index') }}"
           class="portal-nav-pill @if($active === 'applications') is-active @endif">
            <i class="bi bi-file-earmark-person"></i> Applications
        </a>
    @endif
    @if($can('portal.reports.view'))
        <a href="{{ route('admin.reports.index') }}"
           class="portal-nav-pill @if($active === 'reports') is-active @endif">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>
    @endif
</nav>
