@php
    use App\Enums\SalesTeam;
    use App\Support\AdminHomeResolver;
    $admin = auth('admin')->user();
    $homeUrl = AdminHomeResolver::urlFor($admin);
    $can = fn (string $slug) => $admin->canPermission($slug);
    $team = $admin->sales_team;
    $isMarketing = $admin->role?->hasUnrestrictedLeadVisibility() || $admin->role === \App\Enums\AdminRole::Marketing;
    $isCompanyTeam = $team === SalesTeam::Employer;
    $showCompany = $can('leads.view') && ($isMarketing || $isCompanyTeam);
    $showTalent = $can('leads.view') && ($isMarketing || ! $isCompanyTeam);
@endphp
<nav class="sidebar-nav" aria-label="Main navigation">
    @if($can('analytics.view'))
        <a class="sidebar-link @if(request()->routeIs('admin.dashboard')) is-active @endif"
           href="{{ $homeUrl }}">
            <span class="sidebar-link-icon"><i class="bi bi-grid-1x2"></i></span>
            <span class="sidebar-link-text">
                <span class="sidebar-link-label">Home</span>
                <span class="sidebar-link-sub">Dashboard</span>
            </span>
        </a>
    @endif

    @if($showCompany)
        <div class="sidebar-group sidebar-group--company">
            <div class="sidebar-group-label">
                <i class="bi bi-buildings"></i>
                <span>Company sales</span>
            </div>
            <div class="sidebar-group-links">
                <a class="sidebar-link @if(request()->routeIs('admin.employers.pipeline.index', 'admin.employers.pipeline.show')) is-active @endif"
                   href="{{ route('admin.employers.pipeline.index') }}">
                    <span class="sidebar-link-icon"><i class="bi bi-buildings"></i></span>
                    <span class="sidebar-link-text">
                        <span class="sidebar-link-label">Companies</span>
                        <span class="sidebar-link-sub">B2B pipeline</span>
                    </span>
                </a>
                @if($can('kanban.view'))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.employers.pipeline.kanban')) is-active @endif"
                       href="{{ route('admin.employers.pipeline.kanban') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-kanban"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Kanban</span>
                        </span>
                    </a>
                @endif
                @if($can('leads.manage_followups'))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.companies.follow-ups.*')) is-active @endif"
                       href="{{ route('admin.companies.follow-ups.today') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-calendar-check"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Schedule</span>
                            <span class="sidebar-link-sub">Follow-ups &amp; meetings</span>
                        </span>
                    </a>
                @endif
                @if($can('employer_payments.view'))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.employer-plan-payments.*')) is-active @endif"
                       href="{{ route('admin.employer-plan-payments.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-credit-card"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Plan payments</span>
                            <span class="sidebar-link-sub">Cheque checkout</span>
                        </span>
                        @if(($employerPlanPaymentsPending ?? 0) > 0)
                            <span class="sidebar-link-badge">{{ $employerPlanPaymentsPending }}</span>
                        @endif
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($showTalent)
        <div class="sidebar-group sidebar-group--talent">
            <div class="sidebar-group-label">
                <i class="bi bi-person-workspace"></i>
                <span>Talent sales</span>
            </div>
            <div class="sidebar-group-links">
                <a class="sidebar-link @if(request()->routeIs('admin.leads.index', 'admin.leads.show') && ! request()->routeIs('admin.employers.*')) is-active @endif"
                   href="{{ route('admin.leads.index') }}">
                    <span class="sidebar-link-icon"><i class="bi bi-person-workspace"></i></span>
                    <span class="sidebar-link-text">
                        <span class="sidebar-link-label">Candidates</span>
                        <span class="sidebar-link-sub">Job seeker pipeline</span>
                    </span>
                </a>
                @if($can('kanban.view') && ! $isCompanyTeam)
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.leads.kanban')) is-active @endif"
                       href="{{ route('admin.leads.kanban') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-kanban"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Kanban</span>
                        </span>
                    </a>
                @endif
                @if($can('leads.manage_followups'))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.follow-ups.*')) is-active @endif"
                       href="{{ route('admin.follow-ups.today') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-calendar-check"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Follow-ups</span>
                            <span class="sidebar-link-sub">Today's tasks</span>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($isMarketing && $can('consultations.view'))
        <div class="sidebar-group">
            <div class="sidebar-group-label">
                <i class="bi bi-megaphone"></i>
                <span>Marketing</span>
            </div>
            <div class="sidebar-group-links">
                <a class="sidebar-link @if(request()->routeIs('admin.consultations.*')) is-active @endif"
                   href="{{ route('admin.consultations.index') }}">
                    <span class="sidebar-link-icon"><i class="bi bi-person-lines-fill"></i></span>
                    <span class="sidebar-link-text">
                        <span class="sidebar-link-label">Consultations</span>
                    </span>
                </a>
            </div>
        </div>
    @endif

    @if($can('staff.view') || $can('staff.manage') || $can('audit.view') || $can('rbac.manage_permissions') || ($can('employer_payments.view') && ! $showCompany))
        <div class="sidebar-group">
            <div class="sidebar-group-label">
                <i class="bi bi-gear"></i>
                <span>Admin</span>
            </div>
            <div class="sidebar-group-links">
                @if($can('employer_payments.view') && ! $showCompany)
                    <a class="sidebar-link @if(request()->routeIs('admin.employer-plan-payments.*')) is-active @endif"
                       href="{{ route('admin.employer-plan-payments.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-credit-card"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Plan payments</span>
                            <span class="sidebar-link-sub">Cheque checkout</span>
                        </span>
                        @if(($employerPlanPaymentsPending ?? 0) > 0)
                            <span class="sidebar-link-badge">{{ $employerPlanPaymentsPending }}</span>
                        @endif
                    </a>
                @endif
                @if($can('staff.view') || $can('staff.manage'))
                    <a class="sidebar-link @if(request()->routeIs('admin.staff.*')) is-active @endif"
                       href="{{ route('admin.staff.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-people"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Staff logins</span>
                        </span>
                    </a>
                @endif
                @if($can('audit.view'))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.settings.audit-logs')) is-active @endif"
                       href="{{ route('admin.settings.audit-logs') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-journal-text"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Audit logs</span>
                        </span>
                    </a>
                @endif
                @if($can('rbac.manage_permissions'))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.settings.rbac')) is-active @endif"
                       href="{{ route('admin.settings.rbac') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-shield-check"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Permissions</span>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    @endif
</nav>

@if($team)
    <div class="sidebar-team-card @if($isCompanyTeam) is-company @else is-talent @endif">
        <div class="sidebar-team-icon" aria-hidden="true">
            <i class="bi {{ $isCompanyTeam ? 'bi-buildings' : 'bi-person-workspace' }}"></i>
        </div>
        <div class="sidebar-team-body">
            <div class="sidebar-team-kicker">Your team</div>
            <div class="sidebar-team-name">{{ $team->shortLabel() }}</div>
            <div class="sidebar-team-desc">
                @if($isCompanyTeam)
                    B2B employer sales
                @else
                    Candidate conversion
                @endif
            </div>
        </div>
    </div>
@endif
