# Workspace & Data Architecture

How workspaces, integrations, settings, and costs tie together.

---

## Core Model: 1 Workspace = 1 Brand

A workspace = one brand/business. Multiple stores (even mixed CMS), ad accounts, analytics properties per workspace. Aggregated by default, per-store drill-down.

```
Organization (optional, for agencies)
└── Workspace (1 per brand)
    ├── Stores[] (1..N — Shopify + WooCommerce, mixed)
    ├── Ad Accounts[] (1..N per platform — Meta, Google, TikTok)
    ├── Analytics Properties[] (1..N — one GA4 per domain)
    ├── Search Properties[] (1..N — one GSC per domain)
    ├── Email Accounts[] (1..N — Klaviyo, etc.)
    ├── Settings (reporting currency, timezone, COGS defaults, attribution, channels, targets, opex)
    ├── Users (owner, admin, member — with capability flags per member)
    └── Data (orders, products, customers, ad insights, snapshots, alerts — all workspace-scoped)
```

**Integration rules:**
- Never shared across workspaces
- Multiple same-type integrations within workspace: standard
- Customer dedup by email is automatic — schema unique constraint `(workspace_id, email)` merges cross-store. `store_id` on customers = first store seen. `orders_count`/`total_spent` aggregate across all stores.

**Multi-currency:** Use Shopify `shop_money` (not `presentment_money`). Convert to workspace `reporting_currency` at transaction-day mid-market rate (ECB).

---

## Settings Hierarchy (highest wins)

1. Per-variant COGS with date range
2. Per-product COGS with date range
3. Auto-pulled from store (Shopify/WooCommerce native)
4. Per-store cost settings
5. Workspace default COGS margin %
6. Global defaults (superadmin-managed)

---

## Cost Tracking

P&L formula, COGS tracking, transaction fees → `coding-spec.md` sections 1 and 26; `integrations.md` sections 1-2.

---

## User Roles

3 roles: Owner, Admin, Member. Gate definitions → `coding-spec.md` section 43.

**Capability flags (MVP):** 4 booleans on `workspace_users` pivot — `can_access_financials`, `can_access_pii`, `can_access_settings`, `can_manage_members`. Owner/admin always have full access (flags ignored). Admin configures per member via Settings > Team checkboxes. Nav items for restricted sections are hidden, not locked. Follows Triple Whale / GA4 pattern (capability flags, not per-page checkboxes). Research → `docs/competitors/page-permissions.md`.

**v2:** Guest/Client role, Organization Admin, custom role builder for agencies.

---

## Superadmin Panel

Filament v5 on `/admin` with custom `superadmin` guard checking `users.is_superadmin = true`.

### Guard Setup
```php
// config/auth.php
'guards' => ['superadmin' => ['driver' => 'session', 'provider' => 'users']],
// AdminPanelProvider: ->authGuard('superadmin')->authMiddleware([...])
// Gate: Gate::define('viewFilament', fn(User $u) => $u->is_superadmin);
```

### Resources (MVP — keep minimal, read-heavy)

| Resource | Table Columns | Filters | Actions |
|----------|--------------|---------|---------|
| `WorkspaceResource` | name, slug, plan, stores_count, users_count, created_at | plan, has_stores | View (read-only). No create/edit — workspaces are user-created. |
| `UserResource` | name, email, workspaces_count, is_superadmin, created_at | is_superadmin | View, Impersonate, Force password reset |
| `SyncLogResource` | integration_type, integration_name, status, records_synced, started_at, duration | status, integration_type, date range | View details. Retry failed. |
| `GlobalSettingResource` | key, value, description | group | Edit value (inline). Used for RFM thresholds, channel defaults, etc. |

### Custom Pages

| Page | Content |
|------|---------|
| `SystemHealthPage` | Link to Horizon dashboard, queue depth per queue, Redis memory, cache hit rate, last snapshot build time. Read-only. Uses Laravel Pulse widgets. |

### Impersonation
Package: `evo-mark/laravel-impersonate`. Flow:
1. Click "Impersonate" on UserResource → sets session flag, redirects to user's default workspace
2. Yellow banner at top: "Impersonating {name} — [Leave]"
3. Auto-exit after 30 minutes (middleware checks `session('impersonation_started_at')`)
4. Log start/end to `activity_log` via spatie/laravel-activitylog
5. `impersonate.protect` middleware blocks during impersonation: billing changes, workspace/account deletion, password/2FA changes, team management, integration disconnects, PII data export

### Access
- `/admin` route, `is_superadmin = true` only
- No self-registration of superadmins — set via database/tinker
- Require MFA on superadmin guard for production (TOTP via `laragear/two-factor`)

---

## Data Isolation

1. All queries scoped to workspace via global scope
2. No cross-workspace data mutation
3. Agency portfolio: read-only aggregation with currency normalization (v2)
4. Workspace deletion: 30-day grace period

---

Billing model → see `onboarding.md`.
