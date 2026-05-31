# Enterprise CRM Dashboard

## Routes

| URL | Role |
|-----|------|
| `GET /dashboard` | All with `analytics.view` |
| `GET /dashboard/export?format=csv` | `analytics.export` |
| `GET /settings/audit-logs` | `audit.view` (Super Admin) |

## API (`auth:sanctum`, `analytics.view`)

- `GET /api/v1/dashboard/summary`
- `GET /api/v1/dashboard/revenue`
- `GET /api/v1/dashboard/leads`
- `GET /api/v1/dashboard/funnel`
- `GET /api/v1/dashboard/team-performance`
- `GET /api/v1/dashboard/manager-performance`
- `GET /api/v1/dashboard/employee-performance`
- `GET /api/v1/dashboard/recent-activities`

Query: `period=this_month` (and `from`/`to` for custom).

## Role visibility

| Role | Dashboard |
|------|-----------|
| Super Admin / Admin | Executive: Talent + Company + team tables |
| Marketing | Talent pool KPIs + dual pipeline summary strip |
| Sales Manager / Employee | Scoped to `sales_team` (one pipeline) |

## Metrics

- **Talent revenue:** `payments` completed in period.
- **Company revenue:** `deal_value` on won/onboarding/hiring_active/renewed stages.
- **Talent meetings:** scheduled `crm_follow_ups` in period.
- **Company meetings:** `crm_company_meetings` in period.

## Services

- `DashboardScopeService` — row-level scoping
- `DashboardPipelineMetrics` — shared KPI math
- `ExecutiveDashboardService` — admin/super admin
- `ScopedDashboardService` — manager/employee
