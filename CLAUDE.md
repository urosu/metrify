# Nexstage — Coding Agent Instructions

Ecommerce analytics SaaS. Laravel 13 + Inertia v3 + Vue 3.5 + Tailwind 4 + PostgreSQL 18 + Redis. Breeze auth scaffolded, integration contracts exist. No application code yet.

## Start here

Read `docs/plan/README.md` for the full reading order (12 docs). The most critical:
- **`coding-spec.md`** — 46 sections (1-45 + 10b) with every formula, query, service, route, component, job
- **`non-obvious-issues.md`** — build order, runtime traps, security requirements

## Dev environment

```bash
# Docker containers (PHP 8.5, PostgreSQL 18, Redis 8, Horizon, Scheduler, Mailpit)
docker compose up -d --build

# PHP dependencies (can run on host — PHP 8.5 available locally)
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed

# Frontend (Vite + Tailwind 4 via @tailwindcss/vite plugin)
npm install && npm run dev

# After any PHP changes: restart OPcache + Horizon
docker compose restart php horizon
```

## Build order

WorkspaceAwareJob → WorkspaceScope → workspace() → CurrencyConverter → DateRange → Webhook HMAC → ChannelClassifier → Migrations (16-step order in database-schema.md) → Seeders → Sync jobs → Snapshot builder → Controllers → Vue pages

## Hard rules

- NEVER store ratios (ROAS, MER, CAC, AOV, CVR) — compute at query time
- Every model with `workspace_id` needs `WorkspaceScope` (except ChannelMapping — nullable workspace_id, manual query)
- Every queue job extends `WorkspaceAwareJob`
- Timezone conversion in every date query — `AT TIME ZONE`
- Verify webhook HMAC — reject 401. Webhook routes bypass CSRF.
- All workspace routes require `verified` middleware (email verification)
- `INSERT ... ON CONFLICT DO UPDATE` for all sync upserts
- `Inertia::defer()` for heavy data. KPIs load immediately.
- `WHERE quantity > 0` in velocity/COGS/units queries
- Filter `test = true` orders during Shopify sync
- All snapshot amounts in workspace reporting currency
- Every model needs explicit `$fillable` — never use `$request->all()` or `$guarded = []`
- After any PHP changes: `docker compose restart php horizon` — OPcache is enabled, Horizon has its own process cache

## Do NOT build (v2)

TikTok Ads, Uptime monitoring, Slack notifications, Custom pixel, Benchmarks, AI assistant, Post-purchase survey, Multi-store UI chrome, Attribution comparison + Position-Based/Time Decay models

## Do NOT

- Read or reference `OLD-DONT-OPEN/`
- Add gold/amber accents (Shopify Polaris neutral style)
- Build demo/sample data or sortable dashboards or Cmd+K

## Frontend stack (already configured)

- **Tailwind 4** — config in `resources/css/app.css` via `@theme` block (no tailwind.config.js)
- **PrimeVue 4** — unstyled mode, styled with Tailwind
- **ECharts 5** + vue-echarts 7 — canvas renderer, tree-shakeable imports via `use()`
- **Inertia 3** + Vue 3.5 — SPA with server-side routing
- Design tokens defined in `resources/css/app.css`, component contracts in `frontend-spec.md`

## Reference files

- `docs/plan/enums.md` — 30 PHP 8.5 backed enums with values, state transitions, platform mappings
- `docs/plan/service-contracts.md` — method signatures for all services, value objects, middleware
- `docs/plan/api-payloads.md` — example JSON for every external API response and webhook
- `docs/plan/frontend-spec.md` — design tokens, TypeScript types, Vue component contracts, ECharts configs
- `docs/plan/pages-outline.md` — 11 sidebar pages, layouts, UI elements, cross-page data flows
- `docs/research/channel-mapping.md` — 277 channel rules, PHP seeder array
- `app/Integrations/Contracts/` — 3-layer interfaces: Base (Connectable/Syncable) → Category (StoreConnector/AdPlatformConnector/etc.) → Platform (ShopifyConnector/etc.)
- Tech stack, architecture patterns, packages → `tech-stack.md`
- Model map, JSONB casts, traits → `database-schema.md` "Eloquent Model Map"
- Seed data (25 events, 8 global settings) → `coding-spec.md` section 36
