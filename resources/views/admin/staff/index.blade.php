@extends('layouts.app')

@section('title', 'Staff')

@section('content')
    @include('partials.portal-ui')

    @php
        $mgrOnly = $managerCreatesEmployeesOnly ?? false;
        $fieldActor = $fieldActorOnly ?? $mgrOnly;
        $actor = auth('admin')->user();
        $stats = $stats ?? ['total' => 0, 'sales' => 0, 'managers' => 0, 'platform' => 0];
        $hasFilters = request()->filled('q')
            || (!$fieldActor && (request()->filled('role') || request()->filled('sales_team') || request()->filled('sales_region')));

        $pageTitle = $mgrOnly
            ? 'Sales team'
            : (($asmCreatesManagersOnly ?? false) ? 'Regional sales managers' : 'Staff & roles');
        $pageSubtitle = $mgrOnly
            ? 'Sales employees reporting to you'
            : (($asmCreatesManagersOnly ?? false) ? 'Sales managers in your region' : 'Admin panel users — not Hirevo candidates');
        $addLabel = $mgrOnly
            ? 'Add sales employee'
            : (($asmCreatesManagersOnly ?? false) ? 'Add sales manager' : 'Add staff');

        $roleBadgeClass = fn (\App\Enums\AdminRole $role) => match ($role) {
            \App\Enums\AdminRole::SuperAdmin => 'staff-badge staff-badge--super',
            \App\Enums\AdminRole::Admin => 'staff-badge staff-badge--admin',
            \App\Enums\AdminRole::Marketing => 'staff-badge staff-badge--marketing',
            \App\Enums\AdminRole::Asm => 'staff-badge staff-badge--asm',
            \App\Enums\AdminRole::SalesManager => 'staff-badge staff-badge--manager',
            \App\Enums\AdminRole::SalesEmployee => 'staff-badge staff-badge--employee',
            \App\Enums\AdminRole::Recruiter => 'staff-badge staff-badge--recruiter',
            \App\Enums\AdminRole::RecruiterManager => 'staff-badge staff-badge--recruiter-manager',
        };
    @endphp

    @push('styles')
    <style>
        .staff-page { --staff-accent: #6d28d9; --staff-accent-soft: #f5f3ff; }
        .staff-page .portal-hero {
            background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 48%, #7c3aed 100%);
            box-shadow: 0 12px 40px rgba(109, 40, 217, .22);
        }
        .staff-page .portal-hero-actions .btn-light { color: #4c1d95; }
        .staff-page .portal-filters-card {
            border-color: rgba(109, 40, 217, .12);
            box-shadow: 0 4px 18px rgba(109, 40, 217, .06);
        }
        .staff-page .portal-filters-head {
            background: linear-gradient(180deg, #f5f3ff, #fff);
        }
        .staff-page .portal-filters-body .form-control:focus,
        .staff-page .portal-filters-body .form-select:focus {
            border-color: #c4b5fd;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, .15);
        }
        .staff-page .crm-pagination a:hover {
            background: #f5f3ff;
            border-color: #c4b5fd;
            color: #5b21b6;
        }
        .staff-page .crm-pagination .is-active span {
            background: #6d28d9;
            border-color: #6d28d9;
        }
        .staff-user-cell {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-width: 0;
        }
        .staff-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 800;
            letter-spacing: -.02em;
            flex-shrink: 0;
            color: #fff;
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
            box-shadow: 0 4px 12px rgba(124, 58, 237, .25);
        }
        .staff-user-name {
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }
        .staff-user-email {
            font-size: .8rem;
            color: #64748b;
            margin-top: 1px;
        }
        .staff-badge {
            display: inline-flex;
            align-items: center;
            padding: .28rem .6rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .staff-badge--super { background: #fef2f2; color: #b91c1c; }
        .staff-badge--admin { background: #eff6ff; color: #1d4ed8; }
        .staff-badge--marketing { background: #fff7ed; color: #c2410c; }
        .staff-badge--asm { background: #f5f3ff; color: #6d28d9; }
        .staff-badge--manager { background: #eef2ff; color: #4338ca; }
        .staff-badge--employee { background: #ecfdf5; color: #047857; }
        .staff-badge--recruiter { background: #f0fdf4; color: #15803d; }
        .staff-badge--recruiter-manager { background: #ecfeff; color: #0e7490; }
        .staff-tag {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .22rem .5rem;
            border-radius: 8px;
            font-size: .72rem;
            font-weight: 600;
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .staff-referral {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .78rem;
            font-weight: 600;
            color: #5b21b6;
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 8px;
            padding: .2rem .45rem;
        }
        .staff-referral-copy {
            border: 0;
            background: transparent;
            color: #7c3aed;
            padding: 0;
            line-height: 1;
            cursor: pointer;
            font-size: .85rem;
        }
        .staff-referral-copy:hover { color: #5b21b6; }
        .staff-referral-copy.is-copied { color: #059669; }
        .staff-manager {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .85rem;
            color: #334155;
        }
        .staff-manager i { color: #94a3b8; font-size: .8rem; }
        .staff-actions {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            justify-content: flex-end;
        }
        .staff-actions .btn {
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
        .staff-table-head {
            padding: .85rem 1.15rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            background: linear-gradient(180deg, #fafbff, #fff);
        }
        .staff-table-head h2 {
            font-size: .92rem;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .staff-filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-bottom: 1rem;
        }
        .staff-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .65rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
            background: #f5f3ff;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }
        .staff-filter-chip a {
            color: inherit;
            text-decoration: none;
            opacity: .7;
            line-height: 1;
        }
        .staff-filter-chip a:hover { opacity: 1; }
        [data-theme="dark"] .staff-user-name { color: #f1f5f9; }
        [data-theme="dark"] .staff-table-head { background: linear-gradient(180deg, #1e1b4b, #111827); }
        [data-theme="dark"] .staff-table-head h2 { color: #f1f5f9; }
        [data-theme="dark"] .staff-tag { background: #1e293b; border-color: rgba(255,255,255,.1); color: #cbd5e1; }
    </style>
    @endpush

    <div class="portal-page staff-page">
        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Team management</div>
                <h1 class="portal-hero-title">{{ $pageTitle }}</h1>
                <p class="portal-hero-sub">{{ $pageSubtitle }}</p>
            </div>
            <div class="portal-hero-actions d-flex flex-wrap gap-2">
                <span class="badge rounded-pill bg-light text-dark px-3 py-2 fw-semibold">
                    <i class="bi bi-people-fill me-1"></i>{{ number_format($staff->total()) }} {{ Str::plural('member', $staff->total()) }}
                </span>
                <a href="{{ route('admin.staff.create') }}" class="btn btn-light">
                    <i class="bi bi-person-plus-fill me-1"></i>{{ $addLabel }}
                </a>
            </div>
        </div>

        @if($fieldActor)
            @include('partials.portal-mini-stats', ['items' => [
                ['label' => 'Team size', 'value' => $stats['total'], 'icon' => 'bi-people', 'tone' => 'violet'],
                ['label' => 'On this page', 'value' => $staff->count(), 'icon' => 'bi-list-ul', 'tone' => 'indigo'],
            ]])
        @else
            @include('partials.portal-mini-stats', ['items' => [
                ['label' => 'Total staff', 'value' => $stats['total'], 'icon' => 'bi-people', 'tone' => 'violet'],
                ['label' => 'Sales field', 'value' => $stats['sales'], 'icon' => 'bi-briefcase', 'tone' => 'indigo'],
                ['label' => 'Managers', 'value' => $stats['managers'], 'icon' => 'bi-diagram-3', 'tone' => 'emerald'],
                ['label' => 'Platform', 'value' => $stats['platform'], 'icon' => 'bi-shield-check', 'tone' => 'amber'],
            ]])
        @endif

        <div class="portal-filters-card">
            <div class="portal-filters-head">
                <h2><i class="bi bi-funnel text-primary"></i> Search &amp; filters</h2>
                @if($hasFilters)
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-sm btn-link text-decoration-none">Reset all</a>
                @endif
            </div>
            <form method="GET" action="{{ route('admin.staff.index') }}" class="portal-filters-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 {{ $fieldActor ? 'col-lg-8' : 'col-lg-4' }}">
                        <label class="form-label" for="staff-search">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="search" id="staff-search" name="q" value="{{ request('q') }}"
                                   class="form-control" placeholder="Name or email…">
                        </div>
                    </div>
                    @if(!$fieldActor)
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label" for="staff-role">Role</label>
                            <select id="staff-role" name="role" class="form-select">
                                <option value="">All roles</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label" for="staff-team">Team</label>
                            <select id="staff-team" name="sales_team" class="form-select">
                                <option value="">All teams</option>
                                @foreach($salesTeams as $team)
                                    <option value="{{ $team->value }}" @selected(request('sales_team') === $team->value)>{{ $team->shortLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <label class="form-label" for="staff-region">Region</label>
                            <select id="staff-region" name="sales_region" class="form-select">
                                <option value="">All regions</option>
                                @foreach($salesRegions as $region)
                                    <option value="{{ $region->value }}" @selected(request('sales_region') === $region->value)>{{ $region->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-12 col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i>Apply filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if($hasFilters)
            <div class="staff-filter-chips">
                @if(request('q'))
                    <span class="staff-filter-chip">
                        Search: {{ request('q') }}
                        <a href="{{ request()->fullUrlWithQuery(['q' => null]) }}" title="Remove filter">&times;</a>
                    </span>
                @endif
                @if(!$fieldActor && request('role'))
                    @php $activeRole = collect($roles)->first(fn ($r) => $r->value === request('role')); @endphp
                    <span class="staff-filter-chip">
                        Role: {{ $activeRole?->label() ?? request('role') }}
                        <a href="{{ request()->fullUrlWithQuery(['role' => null]) }}" title="Remove filter">&times;</a>
                    </span>
                @endif
                @if(!$fieldActor && request('sales_team'))
                    @php $activeTeam = collect($salesTeams)->first(fn ($t) => $t->value === request('sales_team')); @endphp
                    <span class="staff-filter-chip">
                        Team: {{ $activeTeam?->shortLabel() ?? request('sales_team') }}
                        <a href="{{ request()->fullUrlWithQuery(['sales_team' => null]) }}" title="Remove filter">&times;</a>
                    </span>
                @endif
                @if(!$fieldActor && request('sales_region'))
                    @php $activeRegion = collect($salesRegions)->first(fn ($r) => $r->value === request('sales_region')); @endphp
                    <span class="staff-filter-chip">
                        Region: {{ $activeRegion?->label() ?? request('sales_region') }}
                        <a href="{{ request()->fullUrlWithQuery(['sales_region' => null]) }}" title="Remove filter">&times;</a>
                    </span>
                @endif
            </div>
        @endif

        <div class="portal-table-card">
            <div class="staff-table-head">
                <h2><i class="bi bi-person-lines-fill text-primary"></i> Team directory</h2>
                @if($staff->total() > 0)
                    <span class="text-muted small">Page {{ $staff->currentPage() }} of {{ $staff->lastPage() }}</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Member</th>
                        @if(!$fieldActor)
                            <th>Role</th>
                            <th>Team</th>
                            <th>Region</th>
                        @endif
                        <th>Reports to</th>
                        @if(!$fieldActor)
                            <th>Referral</th>
                        @endif
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($staff as $user)
                        @php
                            $canEdit = $hierarchy->actorCanEditStaff($actor, $user);
                            $initials = collect(explode(' ', $user->name))
                                ->filter()
                                ->take(2)
                                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                ->implode('');
                        @endphp
                        <tr>
                            <td>
                                <div class="staff-user-cell">
                                    <div class="staff-avatar" aria-hidden="true">{{ $initials ?: '?' }}</div>
                                    <div class="min-width-0">
                                        <div class="staff-user-name text-truncate">{{ $user->name }}</div>
                                        <div class="staff-user-email text-truncate">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            @if(!$fieldActor)
                                <td>
                                    <span class="{{ $roleBadgeClass($user->role) }}">{{ $user->role->label() }}</span>
                                </td>
                                <td>
                                    @if($user->sales_team)
                                        <span class="staff-tag"><i class="bi bi-people"></i>{{ $user->sales_team->shortLabel() }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->resolvedRegion())
                                        <span class="staff-tag"><i class="bi bi-geo-alt"></i>{{ $user->resolvedRegion()->label() }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                @if($user->manager)
                                    <span class="staff-manager">
                                        <i class="bi bi-person-badge"></i>{{ $user->manager->name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @if(!$fieldActor)
                                <td>
                                    @if($user->referral_code)
                                        <span class="staff-referral">
                                            {{ $user->referral_code }}
                                            <button type="button" class="staff-referral-copy" title="Copy code"
                                                    data-copy="{{ $user->referral_code }}">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="text-muted small" title="{{ $user->created_at?->format('M j, Y g:i A') }}">
                                    {{ $user->created_at?->format('M j, Y') }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="staff-actions">
                                    <a href="{{ route('admin.staff.edit', $user) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="{{ $canEdit ? 'Edit' : 'View' }}">
                                        <i class="bi {{ $canEdit ? 'bi-pencil' : 'bi-eye' }}"></i>
                                    </a>
                                    @if($canEdit && $user->id !== $actor->id)
                                        <form method="POST" action="{{ route('admin.staff.destroy', $user) }}"
                                              class="d-inline" onsubmit="return confirm('Delete this staff user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $fieldActor ? 4 : 8 }}">
                                <div class="portal-empty">
                                    <i class="bi bi-people"></i>
                                    <div class="fw-semibold mb-1">No staff members found</div>
                                    <div class="small mb-3">
                                        @if($hasFilters)
                                            Try adjusting your filters or search terms.
                                        @else
                                            Get started by adding your first team member.
                                        @endif
                                    </div>
                                    <a href="{{ route('admin.staff.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-person-plus me-1"></i>{{ $addLabel }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($staff->hasPages() || $staff->total() > 0)
                @include('partials.crm-pagination-footer', ['paginator' => $staff])
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.staff-referral-copy').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var code = btn.getAttribute('data-copy');
                if (!code) return;
                navigator.clipboard.writeText(code).then(function () {
                    btn.classList.add('is-copied');
                    btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                    setTimeout(function () {
                        btn.classList.remove('is-copied');
                        btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                    }, 1600);
                });
            });
        });
    </script>
    @endpush
@endsection
