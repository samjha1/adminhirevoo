@php
    use App\Enums\SalesTeam;
    use App\Support\AdminHomeResolver;
    $admin = auth('admin')->user();
    $homeUrl = AdminHomeResolver::urlFor($admin);
    $can = fn (string $slug) => $admin->canPermission($slug);
    $team = $admin->sales_team;
    $isMarketing = $admin->role?->hasUnrestrictedLeadVisibility() || $admin->role === \App\Enums\AdminRole::Marketing;
    $isCompanyTeam = $team === SalesTeam::Employer;
    $isTalentTeam = ! $isCompanyTeam && ($isMarketing || $team === SalesTeam::Candidate || $team === null);
    $showCompany = $can('leads.view') && ($isMarketing || $isCompanyTeam);
    $showTalent = $can('leads.view') && ($isMarketing || ! $isCompanyTeam);
@endphp
<aside class="sidebar d-none d-lg-flex flex-column">
    <div class="p-3 p-xl-4 flex-grow-1">
        <div class="text-white-50 small mb-3 text-uppercase" style="letter-spacing:.08em;font-size:.65rem">Hirevoo CRM</div>
        <nav class="nav nav-pills flex-column gap-1">
            @if($can('analytics.view'))
            <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif"
               href="{{ $homeUrl }}">
                <i class="bi bi-grid-1x2 me-2"></i>Home
            </a>
            @endif

            @if($showCompany)
            <div class="nav-section-label mt-3 mb-1">Company sales (B2B)</div>
            <a class="nav-link @if(request()->routeIs('admin.employers.pipeline.index', 'admin.employers.pipeline.show')) active @endif"
               href="{{ route('admin.employers.pipeline.index') }}">
                <i class="bi bi-buildings me-2"></i>Companies
                <span class="d-block nav-sub">Employer pipeline</span>
            </a>
            @if($can('kanban.view'))
            <a class="nav-link ps-4 py-1 small @if(request()->routeIs('admin.employers.pipeline.kanban')) active @endif"
               href="{{ route('admin.employers.pipeline.kanban') }}">
                <i class="bi bi-kanban me-2"></i>Kanban
            </a>
            @endif
            @endif

            @if($showTalent)
            <div class="nav-section-label mt-3 mb-1">Talent sales</div>
            <a class="nav-link @if(request()->routeIs('admin.leads.index', 'admin.leads.show') && !request()->routeIs('admin.employers.*')) active @endif"
               href="{{ route('admin.leads.index') }}">
                <i class="bi bi-person-workspace me-2"></i>Candidates
                <span class="d-block nav-sub">Job seeker pipeline</span>
            </a>
            @if($can('kanban.view') && ! $isCompanyTeam)
            <a class="nav-link ps-4 py-1 small @if(request()->routeIs('admin.leads.kanban')) active @endif"
               href="{{ route('admin.leads.kanban') }}">
                <i class="bi bi-kanban me-2"></i>Kanban
            </a>
            @endif
            @endif

            @if($can('leads.manage_followups') && ! $isCompanyTeam)
            <a class="nav-link @if(request()->routeIs('admin.follow-ups.*')) active @endif"
               href="{{ route('admin.follow-ups.today') }}">
                <i class="bi bi-calendar-check me-2"></i>My follow-ups
            </a>
            @endif

            @if($isMarketing)
            <div class="nav-section-label mt-3 mb-1">Marketing</div>
            @if($can('consultations.view'))
            <a class="nav-link @if(request()->routeIs('admin.consultations.*')) active @endif"
               href="{{ route('admin.consultations.index') }}">
                <i class="bi bi-person-lines-fill me-2"></i>Consultations
            </a>
            @endif
            @endif

            @if($can('staff.view') || $can('staff.manage'))
            <div class="nav-section-label mt-3 mb-1">Team</div>
            <a class="nav-link @if(request()->routeIs('admin.staff.*')) active @endif"
               href="{{ route('admin.staff.index') }}">
                <i class="bi bi-people me-2"></i>Staff logins
            </a>
            @endif

            @if($can('rbac.manage_permissions'))
            <a class="nav-link @if(request()->routeIs('admin.settings.rbac')) active @endif"
               href="{{ route('admin.settings.rbac') }}">
                <i class="bi bi-shield-check me-2"></i>Permissions
            </a>
            @endif
        </nav>

        @if($team)
        <div class="mt-4 p-3 rounded-3 sidebar-team-badge">
            <div class="small text-white-50">Your team</div>
            <div class="fw-semibold text-white">{{ $team->shortLabel() }}</div>
            @if($isCompanyTeam)
            <div class="small text-white-50 mt-1">Sell hiring solutions to companies</div>
            @else
            <div class="small text-white-50 mt-1">Convert candidates &amp; job seekers</div>
            @endif
        </div>
        @endif
    </div>
</aside>
