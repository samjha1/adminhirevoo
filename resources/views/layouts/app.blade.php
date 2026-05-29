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
        .nav-section-label { font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.45); padding-left: .5rem; }
        .sidebar .nav-link .nav-sub { font-size: .68rem; opacity: .65; margin-top: 2px; line-height: 1.2; }
        .sidebar-team-badge { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); }
        .crm-pipeline-chrome { background: #fff; border-radius: 16px; padding: 1.25rem 1.5rem; border: 1px solid rgba(15,23,42,.08); box-shadow: 0 8px 24px rgba(15,23,42,.06); }
        .crm-pipeline-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .crm-pipeline-icon.talent { background: linear-gradient(135deg, #dbeafe, #eff6ff); color: #1d4ed8; }
        .crm-pipeline-icon.company { background: linear-gradient(135deg, #d1fae5, #ecfdf5); color: #047857; }
        .crm-pipeline-kicker { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
        .crm-pipeline-title { font-size: 1.35rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; }
        .crm-pipeline-sub { font-size: .875rem; color: #64748b; margin-top: .25rem; }
        .crm-pipeline-switch .btn { border-radius: 999px !important; font-weight: 600; }
        .crm-bulk-card { border-radius: 14px; border: 1px solid rgba(37,99,235,.15); }
        .crm-table thead th { background: #f8fafc; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
        .crm-stat-card { border-radius: 14px; border: 1px solid rgba(15,23,42,.06); }
        .crm-stat-card.accent { border-color: rgba(5,150,105,.25); background: linear-gradient(180deg,#ecfdf5,#fff); }
        .crm-stat-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        .crm-stat-value { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; }
        .b2b-kanban-col { width: 280px; }
        .b2b-kanban-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: .75rem; background: #fafbfc; }
        .hover-lift { transition: box-shadow .15s; }
        .hover-lift:hover { box-shadow: 0 8px 20px rgba(15,23,42,.08); }
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
    @stack('styles')
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
            @include('partials.admin-sidebar')
        @endauth

        <main class="app-main flex-grow-1">
            <div class="content-wrap">
                @if (session('info'))
                    <div class="alert alert-info d-flex align-items-center gap-2 shadow-soft" role="alert">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>{{ session('info') }}</div>
                    </div>
                @endif
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

