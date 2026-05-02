# Nexstage Tech Stack

---

## Stack Summary

| Layer | Choice | Version |
|-------|--------|---------|
| Backend | **Laravel** | 13.x |
| PHP | **PHP** | 8.5 |
| Frontend | **Inertia + Vue** | Inertia 3.x + Vue 3.5.x |
| Database | **PostgreSQL** | 18.x |
| Cache/Queue | **Redis** | Latest (separate dbs: 0=cache, 1=queue, 2=sessions) |
| Queue | **Laravel Horizon** | v5.45+ |
| CSS | **Tailwind CSS** | 4.x |
| Build | **Vite** | 8.x (use Node 22+) |
| Charts | **ECharts 6** via vue-echarts v8 | Canvas renderer, 100K+ points |
| Sparklines | **vue-sparklines** or inline SVG | ~2KB — don't load ECharts for 48px charts |
| Tables | **PrimeVue DataTable** | Unstyled mode + Tailwind presets |
| Admin | **Filament** | 5.x |
| File storage | **S3-compatible** | Exports, thumbnails |
| Node.js | **Node** | 22.x or 24.x LTS |

---

## Key Inertia Patterns

- **Deferred props:** KPIs load immediately, heavy tables/charts via `Inertia::defer()`
- **Partial reloads:** `router.reload({ only: ['kpis'] })` — no full page reload
- **WhenVisible:** Lazy-load chart sections on scroll
- **Polling:** Partial reload on 60s interval for freshness
- **Server-side paginate** all tables (50 rows max per page)
- **Date range** in Inertia shared data (every page)
- **Drawer state** via URL query params for deep-linking
- **Code splitting** via Vite dynamic imports
- No SSR needed (authenticated dashboard)

---

## Charts: ECharts 6 (via vue-echarts)

| Chart type | Support |
|---|---|
| Time-series + comparison dashed overlay | Native |
| Horizontal funnel | Native (funnel series) |
| Cohort heatmap | Native (heatmap + visualMap) |
| Scatter + quadrant zones | Native (scatter + markArea) |
| Annotations (event lines) | Native (markLine) |
| Stacked area | Native |
| Waterfall (P&L) | Stacked bar technique (documented pattern, ~30 lines config) |

Canvas renderer handles 100K+ points. ~200-300KB tree-shaken (import only needed components).

**Sparklines:** Use `vue-sparklines` (~2KB) or inline SVG `<polyline>` for KPI cards.

---

## Tables: PrimeVue DataTable

**Built-in features we use:**
- Server-side pagination, sorting, filtering
- Expandable/accordion rows (Product → Variants, Campaign → Ad Sets → Ads)
- Inline cell editing (COGS cost field)
- Column visibility toggle + resize + reorder
- Row selection
- CSV export
- Responsive/mobile

**Styling:** Aura preset via `@primeuix/themes` + `tailwindcss-primeui` CSS plugin for Tailwind 4 compatibility. `cssLayer` config ensures Tailwind utilities override PrimeVue defaults.

**Bundle:** ~40-60KB tree-shakeable. Setup code → coding-spec.md section 42.

---

## Database Scaling Path

| Phase | Architecture | When |
|-------|-------------|------|
| MVP | Postgres + daily_snapshots + Redis cache | Now |
| Growth | Hash partitioning (64-128), BRIN indexes | ~10M orders |
| Scale | ClickHouse for analytical queries | ~50M+ orders |

Snapshot architecture, queue architecture, file structure, and multi-tenant isolation → see `coding-spec.md` sections 21-25 and `workspace-architecture.md`.

---

## Dependencies

| Package | Purpose |
|---------|---------|
| `laravel/horizon` | Queue management |
| `inertiajs/inertia-laravel` | Inertia adapter |
| `tightenco/ziggy` | Laravel routes in JS |
| `laravel/socialite` | Google OAuth login |
| `laravel/pennant` | Feature flags per workspace (add when ready for gradual rollouts) |
| `laravel/cashier` | Stripe billing & subscription management |
| `filament/filament` | Superadmin panel |
| `sentry/sentry-laravel` | Error tracking (essential for production) |
| `barryvdh/laravel-dompdf` | PDF generation (no extra Docker service needed) |
| `spatie/simple-excel` | CSV/Excel export (OpenSpout, streams rows, ~3MB memory) |
| `spatie/laravel-activitylog` | Audit logging (requires PHP 8.5+) |
| `evo-mark/laravel-impersonate` | Superadmin impersonation |
| `league/iso3166` | GA4 country name → ISO code conversion during sync |
| `vinkla/hashids` | Encode bigint IDs in URLs/API responses (prevents enumeration) |
| `phpredis` extension | Redis client (Laravel 13 default) |

**Monitoring:** Laravel Pulse — free, self-hosted (slow queries, queue depth, cache stats).

**Auth:** Laravel Breeze scaffolding (Vue + Inertia). 3 roles (owner/admin/member) via `role` enum on `workspace_users` pivot + Laravel Gates. No spatie/permission needed.

**Search:** PostgreSQL full-text search via Scout `database` driver for MVP.

---

## Architecture Patterns

**Enums (PHP 8.5 backed):** 30 enums for all status/type varchar columns. Prevents typo bugs, enables IDE autocomplete. Cast via `$casts` on models. Key enums: FinancialStatus, SyncStatus, StorePlatform, AdPlatform, Channel, WorkspaceRole, AlertSeverity, CogsSource, AttributionModel, Plan, RfmSegment.

**Value Objects:** `Money` (amount + currency, banker's rounding, prevents mixing EUR/USD), `DateRange` (start/end/comparison/granularity, session persistence), `Touchpoint` (typed JSONB array entries), `FilterSet` (saved view rules, validates operators).

**Events & Listeners:** `OrderSynced` → UpdateCustomerStats + ClassifyChannel + MatchCampaign + ComputeOrderCogs. `InitialImportCompleted` → FixIsNewCustomer + FixFirstOrderAt + BuildSnapshots + ProcessHeldWebhooks. `CogsUpdated` → BackfillCogsOnOrders + RebuildSnapshots. `AlertFired` → SendInAppNotification + SendEmailNotification.

**Query Scopes:** `ExcludeCancelled` (8+ queries), `DateRangeScope` (timezone-aware), `WithPositiveQuantity`, `Unread`, `ActiveOnly`, `Enabled`.

**Traits:** `HasWorkspace` (29 models — auto-applies WorkspaceScope, auto-sets workspace_id), `HasSyncStatus` (5 integration models), `HasPlatformId` (8 models — `findByPlatformId()` for upserts), `HasDateHistory` (effective_from/to date overlap logic).

**Pipeline for SnapshotBuilder:** Each data source = one pipe class (Orders → Refunds → AdSpend → GA4 → GSC → Email → Upsert). Adding new source = add one pipe. Each pipe independently testable.

**Factory pattern:** `StoreConnectorFactory::for($store)` and `AdPlatformClientFactory::for($account)` with exhaustive enum match — compile error when adding new platform without implementation.
