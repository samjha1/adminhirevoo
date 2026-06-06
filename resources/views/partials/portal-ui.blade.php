@once
@push('styles')
<style>
    .portal-page {
        --portal-accent: #4f46e5;
        --portal-accent-soft: #eef2ff;
        --portal-accent-mid: #818cf8;
        --portal-surface: #ffffff;
        --portal-border: rgba(15, 23, 42, .08);
        --portal-muted: #64748b;
        --portal-text: #0f172a;
        max-width: 1440px;
        margin: 0 auto;
        width: 100%;
    }
    .portal-hero {
        background: linear-gradient(135deg, #312e81 0%, #4f46e5 48%, #6366f1 100%);
        border-radius: 18px;
        padding: 1.35rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 12px 40px rgba(79, 70, 229, .22);
        position: relative;
        overflow: hidden;
    }
    .portal-hero::after {
        content: '';
        position: absolute;
        right: -40px;
        top: -40px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,.08);
        pointer-events: none;
    }
    .portal-hero-kicker {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        opacity: .75;
        margin-bottom: .25rem;
    }
    .portal-hero-title {
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -.03em;
        margin: 0 0 .35rem;
        line-height: 1.2;
    }
    .portal-hero-sub {
        font-size: .9rem;
        opacity: .88;
        margin: 0;
        max-width: 560px;
    }
    .portal-hero-actions { position: relative; z-index: 1; }
    .portal-hero-actions .btn { border-radius: 10px; font-weight: 600; }
    .portal-hero-actions .btn-light {
        background: rgba(255,255,255,.95);
        color: #312e81;
        border: none;
    }
    .portal-hero-actions .btn-outline-light {
        border-color: rgba(255,255,255,.35);
        color: #fff;
    }
    .portal-hero-actions .btn-outline-light:hover {
        background: rgba(255,255,255,.12);
        color: #fff;
    }
    .portal-nav-pills {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1.25rem;
    }
    .portal-nav-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .85rem;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        color: #475569;
        background: #fff;
        border: 1px solid #e2e8f0;
        transition: all .2s ease;
    }
    .portal-nav-pill:hover {
        color: var(--portal-accent);
        border-color: #c7d2fe;
        background: var(--portal-accent-soft);
        transform: translateY(-1px);
    }
    .portal-nav-pill.is-active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 4px 14px rgba(79, 70, 229, .35);
    }
    .portal-section-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--portal-muted);
        margin-bottom: .75rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .portal-stat-card {
        border: 1px solid var(--portal-border);
        border-radius: 16px;
        background: var(--portal-surface);
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease;
        overflow: hidden;
    }
    .portal-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
    }
    .portal-stat-card .card-body {
        padding: 1.1rem 1.15rem;
        display: flex;
        gap: .85rem;
        align-items: flex-start;
    }
    .portal-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .portal-stat-icon.indigo { background: #eef2ff; color: #4f46e5; }
    .portal-stat-icon.emerald { background: #ecfdf5; color: #059669; }
    .portal-stat-icon.violet { background: #f5f3ff; color: #7c3aed; }
    .portal-stat-icon.amber { background: #fffbeb; color: #d97706; }
    .portal-stat-icon.rose { background: #fff1f2; color: #e11d48; }
    .portal-stat-label {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--portal-muted);
        margin-bottom: .15rem;
    }
    .portal-stat-value {
        font-size: 1.65rem;
        font-weight: 800;
        letter-spacing: -.03em;
        color: var(--portal-text);
        line-height: 1.1;
    }
    .portal-stat-delta {
        font-size: .78rem;
        font-weight: 600;
        margin-top: .25rem;
    }
    .portal-stat-delta.up { color: #059669; }
    .portal-stat-delta.neutral { color: var(--portal-muted); }
    .portal-panel {
        border: 1px solid var(--portal-border);
        border-radius: 16px;
        background: var(--portal-surface);
        box-shadow: 0 4px 20px rgba(15, 23, 42, .04);
        overflow: hidden;
        height: 100%;
        transition: box-shadow .2s ease;
    }
    .portal-panel:hover { box-shadow: 0 8px 28px rgba(15, 23, 42, .07); }
    .portal-panel-head {
        padding: .9rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        background: linear-gradient(180deg, #fafbff, #fff);
    }
    .portal-panel-title {
        font-size: .92rem;
        font-weight: 700;
        color: var(--portal-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .portal-panel-link {
        font-size: .78rem;
        font-weight: 600;
        color: var(--portal-accent);
        text-decoration: none;
    }
    .portal-panel-link:hover { color: #3730a3; text-decoration: underline; }
    .portal-activity-item {
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #f8fafc;
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        transition: background .15s ease;
    }
    .portal-activity-item:last-child { border-bottom: 0; }
    .portal-activity-item:hover { background: #fafbff; }
    .portal-activity-dot {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .portal-activity-dot.type-company_registered { background: #eef2ff; color: #4f46e5; }
    .portal-activity-dot.type-candidate_registered { background: #f5f3ff; color: #7c3aed; }
    .portal-activity-dot.type-job_posted { background: #ecfdf5; color: #059669; }
    .portal-activity-dot.type-application_submitted { background: #fff7ed; color: #ea580c; }
    .portal-activity-kicker {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--portal-muted);
    }
    .portal-activity-title { font-weight: 600; color: var(--portal-text); font-size: .9rem; }
    .portal-activity-sub { font-size: .8rem; color: var(--portal-muted); }
    .portal-activity-time { font-size: .72rem; color: #94a3b8; margin-top: .15rem; }
    .portal-today-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .7rem 0;
        border-bottom: 1px solid #f1f5f9;
        gap: .5rem;
    }
    .portal-today-row:last-child { border-bottom: 0; }
    .portal-today-label {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .88rem;
        color: #334155;
    }
    .portal-today-label i { color: var(--portal-accent-mid); }
    .portal-today-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--portal-text);
        min-width: 2rem;
        text-align: right;
    }
    .portal-filters-card {
        border: 1px solid rgba(79, 70, 229, .12);
        border-radius: 16px;
        background: #fff;
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(79, 70, 229, .06);
    }
    .portal-filters-head {
        padding: .7rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(180deg, #eef2ff, #fff);
    }
    .portal-filters-head h2 {
        font-size: .82rem;
        font-weight: 700;
        margin: 0;
        color: var(--portal-text);
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .portal-filters-body { padding: 1rem 1.15rem 1.15rem; }
    .portal-filters-body .form-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--portal-muted);
        margin-bottom: .35rem;
    }
    .portal-filters-body .form-control,
    .portal-filters-body .form-select {
        min-height: 40px;
        border-color: #e2e8f0;
        border-radius: 10px;
        font-size: .875rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .portal-filters-body .form-control:focus,
    .portal-filters-body .form-select:focus {
        border-color: #a5b4fc;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .15);
    }
    .portal-table-card {
        border: 1px solid var(--portal-border);
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .04);
    }
    .portal-table-card .table thead th {
        background: #f8fafc;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--portal-muted);
        border-bottom: 1px solid #e2e8f0;
        padding: .75rem 1rem;
        white-space: nowrap;
    }
    .portal-table-card .table tbody td {
        padding: .85rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f8fafc;
    }
    .portal-table-card .table tbody tr {
        transition: background .15s ease;
    }
    .portal-table-card .table tbody tr:hover {
        background: #fafbff;
    }
    .portal-table-card .table tbody tr:last-child td { border-bottom: 0; }
    .portal-badge {
        display: inline-flex;
        align-items: center;
        padding: .28rem .6rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        text-transform: capitalize;
    }
    .portal-badge.status-active { background: #ecfdf5; color: #047857; }
    .portal-badge.status-draft { background: #f1f5f9; color: #475569; }
    .portal-badge.status-closed { background: #fef2f2; color: #b91c1c; }
    .portal-badge.status-applied { background: #eff6ff; color: #1d4ed8; }
    .portal-badge.status-shortlisted { background: #eef2ff; color: #4338ca; }
    .portal-badge.status-interviewed { background: #f5f3ff; color: #6d28d9; }
    .portal-badge.status-offered { background: #ecfdf5; color: #047857; }
    .portal-badge.status-hired { background: #dcfce7; color: #15803d; }
    .portal-badge.status-rejected { background: #fef2f2; color: #dc2626; }
    .portal-badge.status-qualified { background: #fff7ed; color: #c2410c; }
    .portal-match-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        padding: .25rem .5rem;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 800;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
    }
    .portal-empty {
        text-align: center;
        padding: 2.5rem 1.5rem;
        color: var(--portal-muted);
    }
    .portal-empty i {
        font-size: 2rem;
        opacity: .35;
        display: block;
        margin-bottom: .5rem;
    }
    .portal-page .crm-table-footer {
        border-top: 1px solid #f1f5f9;
        background: #fafbff;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
        padding: .85rem 1.15rem;
    }
    .portal-page .crm-pagination a:hover {
        background: #eef2ff;
        border-color: #a5b4fc;
        color: #4338ca;
    }
    .portal-page .crm-pagination .is-active span {
        background: #4f46e5;
        border-color: #4f46e5;
    }
    .portal-report-grid .portal-stat-card { border-left: 3px solid transparent; }
    .portal-report-grid .portal-stat-card:nth-child(1) { border-left-color: #4f46e5; }
    .portal-report-grid .portal-stat-card:nth-child(2) { border-left-color: #059669; }
    .portal-report-grid .portal-stat-card:nth-child(3) { border-left-color: #7c3aed; }
    .portal-report-grid .portal-stat-card:nth-child(4) { border-left-color: #ea580c; }
    .sidebar-group--portal .sidebar-group-label { color: rgba(165, 180, 252, .85); }
    .sidebar-group--portal .sidebar-link.is-active {
        background: linear-gradient(90deg, rgba(99, 102, 241, .25), rgba(99, 102, 241, .08));
        border-color: rgba(129, 140, 248, .35);
    }
    [data-theme="dark"] .portal-page {
        --portal-surface: #111827;
        --portal-border: rgba(255,255,255,.08);
        --portal-text: #f1f5f9;
        --portal-muted: #94a3b8;
    }
    [data-theme="dark"] .portal-panel,
    [data-theme="dark"] .portal-stat-card,
    [data-theme="dark"] .portal-filters-card,
    [data-theme="dark"] .portal-table-card {
        background: #111827;
    }
    [data-theme="dark"] .portal-panel-head,
    [data-theme="dark"] .portal-filters-head {
        background: linear-gradient(180deg, #1e1b4b, #111827);
    }
    @media (max-width: 767px) {
        .portal-hero { padding: 1.1rem; }
        .portal-hero-title { font-size: 1.2rem; }
    }
</style>
@endpush
@endonce
