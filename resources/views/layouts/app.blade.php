<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --app-bg: #0b1220; --app-surface: rgba(255,255,255,.06); --app-border: rgba(255,255,255,.10); }
        html { height: 100%; }
        body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f6f7fb; height: 100%; margin: 0; overflow: hidden; }
        .app-shell {
            height: 100vh;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .app-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            overflow: hidden;
        }
        .app-main {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        .app-topbar { flex-shrink: 0; background: linear-gradient(135deg, #0b1220, #121b33); }
        .brand-pill { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); }
        .sidebar {
            flex-shrink: 0;
            width: 280px;
            align-self: stretch;
            overflow-y: auto;
            overflow-x: hidden;
            background: linear-gradient(180deg, #0b1220, #0b1220 55%, #0f1730);
            border-right: 1px solid rgba(15, 23, 42, .15);
        }
        .sidebar .nav-link { color: rgba(255,255,255,.78); border-radius: 10px; }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); }
        .sidebar .nav-link.active { color: #fff; background: rgba(59,130,246,.18); border: 1px solid rgba(59,130,246,.25); }
        .content-wrap { padding: 24px; }
        .page-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 18px; }
        .page-title { font-size: 1.25rem; font-weight: 700; margin:0; letter-spacing: -.01em; }
        .page-subtitle { color: #6b7280; margin-top: 4px; }
        .card { border: 1px solid rgba(15, 23, 42, .08); border-radius: 14px; }
        .card.shadow-soft { box-shadow: 0 10px 30px rgba(17, 24, 39, .08); }
        .table thead th { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
        .badge { font-weight: 600; }
        .btn { border-radius: 10px; }
        .form-control, .form-select { border-radius: 12px; }
        .alert { border-radius: 14px; }
        .kpi { display:flex; gap: 12px; align-items: center; }
        .kpi-icon { width: 44px; height: 44px; border-radius: 12px; display:flex; align-items:center; justify-content:center; }
        .kpi-icon.primary { background: rgba(59,130,246,.12); color: #2563eb; }
        .kpi-icon.success { background: rgba(16,185,129,.12); color: #059669; }
        .kpi-value { font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em; }
        .kpi-label { color: #6b7280; font-size: .9rem; }
        @media (max-width: 991px) {
            .content-wrap { padding: 16px; }
            .app-body { flex-direction: column; }
            .app-main { overflow-y: auto; }
        }
    </style>
</head>
<body class="bg-light">
<div class="app-shell d-flex flex-column">
    <header class="app-topbar text-white">
        <div class="container-fluid px-3 px-lg-4">
            <div class="d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="brand-pill d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3">
                        <i class="bi bi-shield-lock"></i>
                        <span class="fw-semibold">{{ config('app.name') }}</span>
                    </span>
                    <span class="d-none d-md-inline small text-white-50">Admin dashboard</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @auth('admin')
                        <span class="d-none d-sm-inline small text-white-50">
                            Signed in as <span class="text-white">{{ auth('admin')->user()->email }}</span>
                        </span>
                        <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
                            @csrf
                            <button class="btn btn-sm btn-outline-light" type="submit">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <div class="app-body flex-grow-1 d-flex">
        @auth('admin')
            @php
                $navRole = auth('admin')->user()->role;
                $isFullAdmin = $navRole === \App\Enums\AdminRole::Admin;
                $isSalesManager = $navRole === \App\Enums\AdminRole::SalesManager;
                $isMarketing = $navRole === \App\Enums\AdminRole::Marketing;
            @endphp
            <aside class="sidebar d-none d-lg-flex flex-column">
                <div class="p-3 p-xl-4">
                    <div class="text-white-50 small mb-2">Navigation</div>
                    <nav class="nav nav-pills flex-column gap-2">
                        <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif"
                           href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-grid-1x2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link @if(request()->routeIs('admin.leads.*')) active @endif"
                           href="{{ route('admin.leads.index') }}">
                            <i class="bi bi-funnel me-2"></i>oppochunity
                        </a>
                        @if($isFullAdmin || $isMarketing)
                        <a class="nav-link @if(request()->routeIs('admin.consultations.*')) active @endif"
                           href="{{ route('admin.consultations.index') }}">
                            <i class="bi bi-person-lines-fill me-2"></i>Consultations leads
                        </a>
                        @endif
                        <a class="nav-link @if(request()->routeIs('admin.applications.*')) active @endif"
                           href="{{ route('admin.applications.index') }}">
                            <i class="bi bi-file-earmark-text me-2"></i>Applied Jobs
                        </a>
                        @if($isFullAdmin || $isSalesManager)
                        <a class="nav-link @if(request()->routeIs('admin.staff.*')) active @endif"
                           href="{{ route('admin.staff.index') }}">
                            <i class="bi bi-person-badge me-2"></i>@if($isSalesManager) Sales team @else Staff &amp; roles @endif
                        </a>
                        @endif
                        @if($isFullAdmin)
                        <a class="nav-link @if(request()->routeIs('admin.users.*')) active @endif"
                           href="{{ route('admin.users.index') }}">
                            <i class="bi bi-people me-2"></i>Candidate users
                        </a>
                        <a class="nav-link @if(request()->routeIs('admin.employers.*')) active @endif"
                           href="{{ route('admin.employers.index') }}">
                            <i class="bi bi-building-check me-2"></i>Employers
                        </a>
                        <a class="nav-link @if(request()->routeIs('admin.jobs.*')) active @endif"
                           href="{{ route('admin.jobs.index') }}">
                            <i class="bi bi-briefcase me-2"></i>Jobs
                        </a>
                        <a class="nav-link @if(request()->routeIs('admin.sponsored-ads.*')) active @endif"
                           href="{{ route('admin.sponsored-ads.index') }}">
                            <i class="bi bi-megaphone me-2"></i>Sponsored ads
                            @php $pendingSponsored = \Illuminate\Support\Facades\Schema::hasTable('leadsmanager_ads') ? \App\Models\Leadsmanager\LeadsmanagerAd::where('status', 'pending_review')->count() : 0; @endphp
                            @if($pendingSponsored > 0)
                                <span class="badge rounded-pill text-bg-warning ms-1">{{ $pendingSponsored }}</span>
                            @endif
                        </a>
                        <a class="nav-link @if(request()->routeIs('admin.referrals.*')) active @endif"
                           href="{{ route('admin.referrals.index') }}">
                            <i class="bi bi-diagram-3 me-2"></i>Referrals
                        </a>
                        <a class="nav-link @if(request()->routeIs('admin.referral-submissions.*')) active @endif"
                           href="{{ route('admin.referral-submissions.index') }}">
                            <i class="bi bi-envelope-paper me-2"></i>Referral Requests
                        </a>
                        <a class="nav-link @if(request()->routeIs('admin.payments.*')) active @endif"
                           href="{{ route('admin.payments.index') }}">
                            <i class="bi bi-cash-coin me-2"></i>Payments
                        </a>
                        @endif
                    </nav>

                    <div class="mt-4 p-3 rounded-3" style="background: var(--app-surface); border: 1px solid var(--app-border); color: rgba(255,255,255,.85);">
                        <div class="fw-semibold mb-1">Quick tips</div>
                        <div class="small text-white-50">
                            Approving an employer updates <code class="text-white">referrer_profiles.is_approved</code> in the shared DB.
                        </div>
                    </div>
                </div>
            </aside>
        @endauth

        <main class="app-main flex-grow-1">
            <div class="content-wrap">
                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center gap-2 shadow-soft" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger d-flex align-items-center gap-2 shadow-soft" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>
</body>
</html>

