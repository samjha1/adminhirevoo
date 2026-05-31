# Talent pipeline — Hirevo → CRM flow

## How candidates reach `/leads`

| Hirevo action | CRM trigger | `lead_summary` / source |
|---------------|-------------|-------------------------|
| Upload resume (first time) | `CandidateLeadService::ensureCandidateCrmLead` | `profile_active` / `resume_upload` |
| Apply to employer job | `recordEmployerJobLead` | `job_application` |
| “Get learning help” on results | `recordSkillGapLead` | `skill_gap` |
| Referral / Premium click | `recordSkillGapLead` or `recordEmployerJobLead` | `referral_source` query param |
| Upskill contact form | `recordUpskillLead` | `upskill_contact` |
| Guest referral (not logged in) | `recordGuestReferral` | `candidate_id` null — visible in CRM, search by source |

All writes go to the shared **`leads`** table. Admin CRM reads the same database (configure `DB_DATABASE=hirevo` in adminpanal `.env`).

## Sales process (CRM)

1. Marketing assigns rows to **Talent manager** (bulk bar on `/leads`).
2. Manager assigns to **Talent executive**.
3. Executive works lead: calls, follow-ups, CRM stage (list or `/leads/kanban`).

## Verify locally

```bash
# Hirevo: candidate uploads resume or applies to a job
# Admin:
php artisan crm:seed-demo   # optional demo rows
# Log in as talent.executive@themesdesign.test / password
```

## Code entry point

`hirevo/app/Services/CandidateLeadService.php` — single service for creating/updating leads.
