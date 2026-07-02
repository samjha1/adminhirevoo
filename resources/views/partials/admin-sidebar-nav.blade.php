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
    $isRecruiter = $admin->role === \App\Enums\AdminRole::Recruiter;
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
    @elseif($can('portal.recruiter_assignments.manage') || $can('portal.recruiter_activity.view'))
        <a class="sidebar-link @if(request()->routeIs('admin.portal.recruiter-assignments.*', 'admin.portal.recruiter-activity.*')) is-active @endif"
           href="{{ $homeUrl }}">
            <span class="sidebar-link-icon"><i class="bi bi-grid-1x2"></i></span>
            <span class="sidebar-link-text">
                <span class="sidebar-link-label">Home</span>
                <span class="sidebar-link-sub">Recruiter portal</span>
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
                <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.employers.outreach.*')) is-active @endif"
                   href="{{ route('admin.employers.outreach.index') }}">
                    <span class="sidebar-link-icon"><i class="bi bi-person-plus"></i></span>
                    <span class="sidebar-link-text">
                        <span class="sidebar-link-label">Outreach leads</span>
                        <span class="sidebar-link-sub">Not signed up</span>
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
                <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.employers.activity.my')) is-active @endif"
                   href="{{ route('admin.employers.activity.my') }}">
                    <span class="sidebar-link-icon"><i class="bi bi-person-check"></i></span>
                    <span class="sidebar-link-text">
                        <span class="sidebar-link-label">My activity</span>
                        <span class="sidebar-link-sub">Today's work</span>
                    </span>
                </a>
                @if(app(\App\Services\SalesTeamActivityScopeService::class)->canViewTeamActivity($admin))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.employers.activity.team')) is-active @endif"
                       href="{{ route('admin.employers.activity.team') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-people"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Team activity</span>
                            <span class="sidebar-link-sub">Staff reports</span>
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
                <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.leads.activity.my')) is-active @endif"
                   href="{{ route('admin.leads.activity.my') }}">
                    <span class="sidebar-link-icon"><i class="bi bi-person-check"></i></span>
                    <span class="sidebar-link-text">
                        <span class="sidebar-link-label">My activity</span>
                        <span class="sidebar-link-sub">Today's work</span>
                    </span>
                </a>
                @if(app(\App\Services\SalesTeamActivityScopeService::class)->canViewTeamActivity($admin))
                    <a class="sidebar-link sidebar-link--child @if(request()->routeIs('admin.leads.activity.team')) is-active @endif"
                       href="{{ route('admin.leads.activity.team') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-people"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Team activity</span>
                            <span class="sidebar-link-sub">Staff reports</span>
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

    @if($can('platform.sponsored_ads') || $can('platform.ads_manager_leads'))
        <div class="sidebar-group">
            <div class="sidebar-group-label">
                <i class="bi bi-badge-ad"></i>
                <span>Ads Manager</span>
            </div>
            <div class="sidebar-group-links">
                @if($can('platform.sponsored_ads'))
                    <a class="sidebar-link @if(request()->routeIs('admin.sponsored-ads.*')) is-active @endif"
                       href="{{ route('admin.sponsored-ads.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-badge-ad"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Sponsored ads</span>
                            <span class="sidebar-link-sub">Approve for Hirevo</span>
                        </span>
                        @if(($sponsoredAdsPending ?? 0) > 0)
                            <span class="sidebar-link-badge">{{ $sponsoredAdsPending }}</span>
                        @endif
                    </a>
                @endif
                @if($can('platform.ads_manager_leads'))
                    <a class="sidebar-link @if(request()->routeIs('admin.ads-manager.leads.*')) is-active @endif"
                       href="{{ route('admin.ads-manager.leads.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Advertiser leads</span>
                            <span class="sidebar-link-sub">CSV import</span>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($can('portal.dashboard.view') || $can('portal.jobs.view') || $can('portal.applications.view') || $can('portal.reports.view') || $can('portal.companies.view') || $can('portal.recruiter_assignments.manage') || $can('portal.recruiter_activity.view'))
        <div class="sidebar-group sidebar-group--portal">
            <div class="sidebar-group-label">
                <i class="bi bi-briefcase"></i>
                <span>Job Portal</span>
            </div>
            <div class="sidebar-group-links">
                @if($can('portal.dashboard.view'))
                    <a class="sidebar-link @if(request()->routeIs('admin.portal.dashboard')) is-active @endif"
                       href="{{ route('admin.portal.dashboard') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-speedometer2"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Dashboard</span>
                            <span class="sidebar-link-sub">Platform analytics</span>
                        </span>
                    </a>
                @endif
                @if($can('portal.companies.view') || $can('platform.employers'))
                    <a class="sidebar-link @if(request()->routeIs('admin.employers.*')) is-active @endif"
                       href="{{ route('admin.employers.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-building"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">{{ $isRecruiter ? 'My companies' : 'Companies' }}</span>
                        </span>
                    </a>
                @endif
                @if($can('portal.jobs.view') || $can('platform.jobs'))
                    <a class="sidebar-link @if(request()->routeIs('admin.jobs.*')) is-active @endif"
                       href="{{ route('admin.jobs.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-briefcase"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Jobs</span>
                        </span>
                    </a>
                @endif
                @if($can('portal.applications.view') || $can('applications.view'))
                    <a class="sidebar-link @if(request()->routeIs('admin.applications.*', 'admin.portal.applications.*')) is-active @endif"
                       href="{{ $can('leads.view') ? route('admin.applications.index') : route('admin.portal.applications.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-file-earmark-person"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Applications</span>
                        </span>
                    </a>
                @endif
                @if($isRecruiter && $can('portal.applications.view'))
                    <a class="sidebar-link @if(request()->routeIs('admin.portal.my-activity')) is-active @endif"
                       href="{{ route('admin.portal.my-activity') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-clipboard-data"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">My activity</span>
                        </span>
                    </a>
                @endif
                @if($can('portal.recruiter_assignments.manage'))
                    <a class="sidebar-link @if(request()->routeIs('admin.portal.recruiter-assignments.*')) is-active @endif"
                       href="{{ route('admin.portal.recruiter-assignments.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-building-gear"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Recruiter assignments</span>
                        </span>
                    </a>
                @endif
                @if($can('portal.recruiter_activity.view'))
                    <a class="sidebar-link @if(request()->routeIs('admin.portal.recruiter-activity.*')) is-active @endif"
                       href="{{ route('admin.portal.recruiter-activity.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-person-lines-fill"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Recruiter activity</span>
                        </span>
                    </a>
                @endif
                @if($can('portal.reports.view'))
                    <a class="sidebar-link @if(request()->routeIs('admin.reports.*')) is-active @endif"
                       href="{{ route('admin.reports.index') }}">
                        <span class="sidebar-link-icon"><i class="bi bi-bar-chart-line"></i></span>
                        <span class="sidebar-link-text">
                            <span class="sidebar-link-label">Reports</span>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($can('staff.view') || $can('staff.manage') || $can('audit.view') || $can('rbac.manage_permissions') || $can('portal.roles.view') || ($can('employer_payments.view') && ! $showCompany))
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
