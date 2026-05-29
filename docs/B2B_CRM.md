# Hirevoo B2B Company Sales CRM

## Two sales motions (do not mix)

| Motion | Sidebar | Users | Data |
|--------|---------|-------|------|
| **Talent sales** | Candidates / Talent pipeline | `talent.manager@`, `talent.executive@` | Hirevo `leads` (job seekers) |
| **Company sales (B2B)** | Companies / Company pipeline | `company.manager@`, `company.executive@` | `crm_employer_prospects` (employers) |

**Marketing** (`marketing@`) sees both pipelines and assigns to the correct team only.

## Company team login flow

1. Log in → lands on **Home** (`/dashboard`) with B2B KPIs (same URL for all teams; content switches by `sales_team`).
2. Use sidebar **Companies** for list + bulk assign.
3. Use **Kanban** for stage board (HubSpot-style).
4. Open a company → full profile + stage + deal value + forecast.

If a company user opens a talent URL, they are redirected **home** with an info message (not an error).

## B2B pipeline stages

`lead_generated` → `contacted` → `interested` → `meeting_scheduled` → `demo_completed` → `proposal_sent` → `negotiation` → `won` → `onboarding` → `hiring_active` → `renewed` (+ `lost`)

**Forecast:** `expected_revenue = deal_value × win_probability`  
Probabilities: Demo 30%, Proposal 50%, Negotiation 75%, Won 100%.

## Database (adminpanal)

- `crm_employer_prospects` — company master + deal fields
- `crm_company_meetings` — meetings module (schema ready)
- `crm_company_proposals` — proposals (schema ready)
- `crm_company_clients` — auto-created on **Won**
- `crm_company_activities` — timeline

## Roles roadmap

| Role | Status |
|------|--------|
| Super Admin, Admin, Marketing | Done |
| Sales Manager / Executive (per team) | Done |
| Account Manager | Phase 2 — `crm_company_clients.account_manager_id` |
| Hiring Operations Manager | Phase 3 — delivery dashboard |

## API roadmap (`/api/v1/companies/...`)

- `GET/POST companies`, `PATCH stage`, `POST calls`, `POST meetings`, `POST proposals`
- CSO / Founder aggregated dashboards

## Demo data (local testing)

Load realistic sample data across talent leads, company pipeline, marketing leads, calls, meetings, and proposals:

```bash
php artisan crm:seed-demo
# or
php artisan db:seed --class=CrmDemoDataSeeder
```

Re-running removes previous demo rows (emails `crm-demo.*@hirevoo.test`) and inserts fresh data. Requires `AdminRbacSeeder` first.

## Development phases

1. **Done:** Team split, company pipeline, stages, forecast, dashboard, Kanban, profiles.
2. **Next:** Meetings + proposals UI, call logging on companies, reports, automations.
3. **Later:** Hiring delivery per client, renewals, full role set, mobile API.
