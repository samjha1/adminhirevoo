<style>
    .company-page { max-width: 1440px; margin: 0 auto; width: 100%; }
    .company-toolbar {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .75rem; margin-bottom: 1rem;
    }
    .company-toolbar-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .company-total-badge {
        font-size: .8rem; font-weight: 600; color: #475569;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
        padding: .35rem .85rem; display: inline-flex; align-items: center; gap: .35rem;
    }
    .company-total-badge strong { color: #0f172a; font-weight: 800; }
    .company-filters-card {
        border: 1px solid rgba(15, 23, 42, .08); border-radius: 16px;
        background: #fff; margin-bottom: 1rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .04); overflow: hidden;
    }
    .company-filters-head {
        padding: .75rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(180deg, #f0fdf4, #fff);
    }
    .company-filters-head h2 { font-size: .85rem; font-weight: 700; margin: 0; color: #0f172a; }
    .company-filters-body { padding: 1rem 1.25rem 1.25rem; }
    .company-filters-body .form-label {
        font-size: .7rem; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; color: #64748b; margin-bottom: .35rem;
    }
    .company-filters-body .form-control,
    .company-filters-body .form-select { min-height: 40px; border-color: #e2e8f0; font-size: .875rem; }
</style>
