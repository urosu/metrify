# Superadmin Panel Research

Research conducted 2026-04-30.

---

## What to include

### Tenant browser
- List all organizations/workspaces with search, filter by plan/status/created date
- Quick stats per workspace: user count, MRR, last active, integration count, data freshness

### User management
- Search users globally, impersonate, force password reset, disable 2FA, merge duplicates

### Integration health dashboard
- Aggregated view of all OAuth connections across ALL tenants
- Surface: broken tokens, failed syncs, stale data (last sync > X hours)
- Alert on error rate spikes

### Job/queue monitor
- Failed jobs table with retry button
- Queue depth graphs, worker status
- Laravel Horizon is the standard here

### Feature flags
- Per-workspace or per-plan toggles
- Global default + workspace-level override

### Global settings (workspace-overridable)
- Channel mapping defaults (UTM → channel name rules)
- Attribution model defaults
- Cost calculation formulas
- Holiday calendar event seeds

**Resolution pattern:** `global_settings` table + `workspace_settings` table. Workspace value wins if set, else fall back to global.

### Billing overview
- Plan distribution, trial expirations, MRR, churn candidates

### System health
- DB size per tenant, API rate limit consumption, cache hit rates, slow query log

### Audit log
- Who did what, when. Critical for debugging customer issues.

---

## Laravel implementation (2025-2026 best practice)

**Filament v5** is the current community standard:
- Free, Livewire-based, rich ecosystem, easy to extend
- Best choice for new projects
- Use on separate `/admin` route with its own guard (`superadmin`)

**Laravel Nova:** Still used but falling behind. Paid, Vue-based, slower ecosystem.

**Recommended approach:** Filament for CRUD-heavy admin (tenant/user browsing) + custom routes for operational dashboards (queue health). Horizon's built-in dashboard for job monitoring.

---

## Impersonation / "Login As Customer"

**Standard pattern:**
1. Superadmin clicks "impersonate" on user row
2. Session stores `original_admin_id` + `impersonating_user_id`
3. Banner shows "Viewing as [customer name]" with "Exit" button
4. All actions logged

**Laravel package:** `evo-mark/laravel-impersonate` — maintained fork of lab404, works with Filament.

**Security rules:**
- Log every impersonation session (who, when, duration)
- Block destructive actions while impersonating (billing changes, account deletion)
- Require re-authentication or 2FA before impersonation
- Time-limit sessions (auto-exit after 30 min)
- Never impersonate other superadmins

---

## Sources
- Filament v5 (filamentphp.com)
- Laravel Nova (nova.laravel.com)
- evo-mark/laravel-impersonate (github.com)
- Laravel Horizon (laravel.com/docs/horizon)
- Stripe internal admin approach (stripe.com/blog)
