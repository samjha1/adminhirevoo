<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') — {{ config('app.name', 'Hirevoo CRM') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --auth-navy: #0b1220;
            --auth-navy-mid: #121b33;
            --auth-blue: #2563eb;
            --auth-green: #059669;
            --auth-surface: #ffffff;
            --auth-muted: #64748b;
            --auth-border: #e2e8f0;
            --auth-radius: 20px;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body.auth-body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #eef2f7;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        .auth-page {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        .auth-page::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 20%, rgba(37, 99, 235, .12), transparent 50%),
                radial-gradient(ellipse 70% 50% at 90% 80%, rgba(5, 150, 105, .1), transparent 50%),
                linear-gradient(160deg, #f8fafc 0%, #eef2f7 50%, #e8edf4 100%);
            z-index: 0;
        }
        .auth-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 980px;
            background: var(--auth-surface);
            border-radius: var(--auth-radius);
            border: 1px solid rgba(15, 23, 42, .08);
            box-shadow:
                0 4px 6px rgba(15, 23, 42, .04),
                0 24px 60px rgba(15, 23, 42, .12);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            animation: authEnter .55s cubic-bezier(.22, 1, .36, 1) both;
        }
        @keyframes authEnter {
            from { opacity: 0; transform: translateY(16px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @media (max-width: 767.98px) {
            .auth-shell { grid-template-columns: 1fr; max-width: 440px; }
        }
        .auth-brand-panel {
            position: relative;
            padding: 2.5rem 2rem;
            background: linear-gradient(165deg, var(--auth-navy) 0%, var(--auth-navy-mid) 45%, #0f2847 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 520px;
            overflow: hidden;
        }
        .auth-brand-panel::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -30%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(59, 130, 246, .25), transparent 65%);
            pointer-events: none;
        }
        .auth-brand-panel::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(16, 185, 129, .2), transparent 65%);
            pointer-events: none;
        }
        .auth-brand-inner { position: relative; z-index: 1; }
        .auth-logo-link {
            display: inline-block;
            line-height: 0;
            transition: transform .25s cubic-bezier(.4, 0, .2, 1), opacity .25s ease;
        }
        .auth-logo-link:hover { transform: scale(1.02); opacity: .95; }
        .auth-logo-img {
            height: auto;
            max-height: 40px;
            max-width: 190px;
            width: auto;
            object-fit: contain;
            background: #fff;
            padding: .45rem .7rem;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .2);
        }
        .auth-brand-title {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1.2;
            margin: 2rem 0 .75rem;
        }
        .auth-brand-lead {
            font-size: .92rem;
            line-height: 1.55;
            color: rgba(255, 255, 255, .72);
            max-width: 280px;
        }
        .auth-features {
            list-style: none;
            padding: 0;
            margin: 2rem 0 0;
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .auth-features li {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            font-size: .84rem;
            color: rgba(255, 255, 255, .85);
            line-height: 1.4;
        }
        .auth-features i {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            background: rgba(255, 255, 255, .1);
            color: #93c5fd;
        }
        .auth-features li:nth-child(2) i { color: #6ee7b7; }
        .auth-features li:nth-child(3) i { color: #fcd34d; }
        .auth-brand-footer {
            position: relative;
            z-index: 1;
            font-size: .72rem;
            color: rgba(255, 255, 255, .4);
            margin-top: 2rem;
        }
        .auth-form-panel {
            padding: 2.5rem 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 520px;
        }
        @media (max-width: 767.98px) {
            .auth-brand-panel { min-height: auto; padding: 2rem 1.5rem; }
            .auth-brand-title { font-size: 1.35rem; margin-top: 1.25rem; }
            .auth-features { display: none; }
            .auth-form-panel { min-height: auto; padding: 2rem 1.5rem 2.25rem; }
        }
        .auth-form-kicker {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--auth-blue);
            margin-bottom: .35rem;
        }
        .auth-form-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 0 0 .35rem;
        }
        .auth-form-sub {
            font-size: .88rem;
            color: var(--auth-muted);
            margin-bottom: 1.75rem;
        }
        .auth-field { margin-bottom: 1.1rem; }
        .auth-field label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: .4rem;
        }
        .auth-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .auth-input-wrap > i.field-icon {
            position: absolute;
            left: .85rem;
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: color .2s ease;
        }
        .auth-input-wrap input {
            width: 100%;
            min-height: 48px;
            padding: .65rem .85rem .65rem 2.65rem;
            border: 1px solid var(--auth-border);
            border-radius: 12px;
            font-size: .9rem;
            background: #f8fafc;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .auth-input-wrap input:hover { border-color: #cbd5e1; background: #fff; }
        .auth-input-wrap input:focus {
            outline: none;
            border-color: var(--auth-blue);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }
        .auth-input-wrap:focus-within > i.field-icon { color: var(--auth-blue); }
        .auth-input-wrap input.is-invalid {
            border-color: #f87171;
            box-shadow: 0 0 0 4px rgba(248, 113, 113, .12);
        }
        .auth-input-wrap .btn-toggle-pw {
            position: absolute;
            right: .5rem;
            border: 0;
            background: transparent;
            color: #94a3b8;
            padding: .35rem .5rem;
            border-radius: 8px;
            transition: color .15s ease, background .15s ease;
        }
        .auth-input-wrap .btn-toggle-pw:hover { color: #475569; background: #f1f5f9; }
        .auth-input-wrap.has-toggle input { padding-right: 2.75rem; }
        .auth-remember {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.35rem;
        }
        .auth-remember input {
            width: 1.05rem;
            height: 1.05rem;
            border-radius: 4px;
            accent-color: var(--auth-blue);
        }
        .auth-remember label {
            font-size: .84rem;
            color: #475569;
            margin: 0;
            cursor: pointer;
        }
        .btn-auth-submit {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 12px;
            font-size: .95rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--auth-blue) 0%, #1d4ed8 100%);
            box-shadow: 0 8px 24px rgba(37, 99, 235, .35);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
        }
        .btn-auth-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(37, 99, 235, .4);
            filter: brightness(1.05);
            color: #fff;
        }
        .btn-auth-submit:active { transform: translateY(0); }
        .auth-alert {
            border: 0;
            border-radius: 12px;
            font-size: .85rem;
            padding: .75rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            animation: authAlertIn .35s ease both;
        }
        @keyframes authAlertIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .auth-alert-danger { background: #fef2f2; color: #991b1b; }
        .auth-alert-warning { background: #fffbeb; color: #92400e; }
        .auth-alert-success { background: #ecfdf5; color: #047857; }
        .auth-alert-info { background: #eff6ff; color: #1e40af; }
        .invalid-hint {
            font-size: .78rem;
            color: #dc2626;
            margin-top: .35rem;
        }
    </style>
    @stack('styles')
</head>
<body class="auth-body">
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
