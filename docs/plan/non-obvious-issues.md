# Non-Obvious Issues

Runtime traps, silent data bugs, and security risks that won't surface as obvious errors. Address each during coding.

---

## Build Order (what to code first)

**Before any feature code:**
1. Project bootstrap: `laravel new` → Breeze Vue+Inertia → customize
2. `WorkspaceAwareJob` base class — before any queue job
3. `WorkspaceScope` + `SetActiveWorkspace` middleware + `workspace()` helper — before any controller
4. `CurrencyConverterService` with weekend FX fallback — before any money handling
5. `DateRange` value object — before any controller (every page uses it)
6. Webhook HMAC verification middleware — before accepting any webhook
7. `ChannelClassifierService` — before any order sync

**During import/onboarding:**
8. Pause webhook processing until `stores.initial_import_completed = true`
9. Post-import recomputation chain: fix `is_new_customer` → fix `customers.first_order_at` → build snapshots
10. Currency conversion during order sync (set `total_price_converted`) — snapshot builder uses these
11. Timezone conversion in every date query from day 1 — `AT TIME ZONE` in snapshot builder

**During feature development:**
12. Revenue-per-query click-proportional distribution is MVP — without it SEO page is unusable
13. COGS backfill job (`BackfillCogsOnOrdersJob`) — triggered when costs change
14. `DetectAnomaliesJob` runs AFTER snapshot corrections, not before
15. Drop GA4 ROAS from campaign table — no join path. Store / Platform / Real only
16. Normalize `gsc_daily.page` to `page_path` at sync time — asymmetric normalization breaks joins
17. Webhook routes bypass CSRF — external POSTs need separate middleware group

---

## Critical — Wrong Data

### 1. is_new_customer wrong during import
Orders arrive in chunks, not chronologically. Both order #5000 (2024) and order #1 (2023) get `is_new_customer = true`. Corrupts CAC, cohorts, new/returning splits.
**Fix:** Never set during import. After import: `UPDATE orders SET is_new_customer = (created_at = (SELECT MIN(o2.created_at) FROM orders o2 WHERE o2.customer_id = orders.customer_id AND o2.workspace_id = orders.workspace_id)) WHERE workspace_id = :id`

### 2. first_order_at not recomputed post-import
Same root cause as #1. Cohort heatmap groups by `customers.first_order_at` — if wrong, every cohort is wrong.
**Fix:** Post-import job: `UPDATE customers SET first_order_at = (SELECT MIN(created_at) FROM orders WHERE customer_id = customers.id AND workspace_id = customers.workspace_id)`

### 3. Dashboard empty on day 1
Import completes → user sees dashboard → `daily_snapshots` has no rows (nightly build hasn't run). Blank screen.
**Fix:** After import, dispatch immediate `BuildSnapshotsForDateRange` for full imported history.

### 4. Webhook + import overlap causes duplicates
Webhooks register during OAuth, but bulk import is running. Same order arrives via both paths. Concurrent upserts can deadlock.
**Fix:** Queue webhooks in holding list until `initial_import_completed = true`. Process as reconciliation pass after import.

### 6. Snapshot build races with ad sync
If snapshots build before ad sync finishes, dashboard shows revenue without ad spend for hours.
**Fix:** Snapshots at 02:00 UTC. Before building, verify ad accounts synced via `sync_logs`. Corrections job at 06:30 catches misses.

### 8. matched_campaign_id matching undefined
`utm_campaign=summer_tof_broad` doesn't match campaign name `[SI] TOF | Broad | Summer`. No algorithm documented.
**Fix:** Use platform-reported conversions from `ad_insights` for campaign-level attribution. For per-order: match UTM against campaign name with case-insensitive contains, or use `parsed_dimensions`.

### 9. Ad spend can't split across stores
`ad_accounts` belong to workspaces, not stores. Per-store snapshots can't populate `ad_spend_*`.
**Fix:** Optional `store_id` FK on `ad_campaigns`. For shared campaigns, split by attributed order revenue per store. Single-store MVP: not a blocker.

### 11. COGS backfill missing
COGS entered after orders exist → `order_line_items.unit_cogs` stays null → snapshots propagate null.
**Fix:** `BackfillCogsOnOrdersJob` re-walks line items when COGS changes. Trigger after CSV upload, manual edit, or store sync. Then rebuild affected snapshots.

### 12. Currency conversion timing
Both order sync and snapshot build mentioned as conversion points. Ambiguous.
**Fix:** Convert during order sync (set `total_price_converted`). Snapshot builder uses converted values directly. If FX rates corrected, re-sync affected orders, then rebuild snapshots.

---

## Critical — Security

### 15. No webhook signature verification
Shopify: `X-Shopify-Hmac-Sha256`. WooCommerce: signature header. Without verification, anyone can POST fake orders.
**Fix:** Verify HMAC on every webhook. Reject 401 if invalid. Non-negotiable.

### 16. WorkspaceScope bypass in queue jobs
Jobs run outside HTTP — no middleware sets workspace. Forgetting `setWorkspace()` queries ALL workspaces (data leak).
**Fix:** `WorkspaceAwareJob` base class requiring `workspace_id` in constructor, auto-sets scope in `handle()`. All jobs must extend it.

### 17. GDPR right to be forgotten
Customers table has plaintext PII. No deletion cascade documented.
**Fix:** `DeleteCustomerDataAction` that anonymizes customer + scrubs PII from orders and touchpoints JSONB.

### 18. Workspace slug enumeration
404 vs 403 leaks which workspaces exist.
**Fix:** Always return 404 for workspaces user isn't a member of.

### 19. CSV injection in COGS upload
Cells starting with `=`, `+`, `-`, `@` execute formulas if re-exported.
**Fix:** Strip formula-trigger characters. Validate cost as `numeric|min:0`.

### 20. OAuth token security
Laravel `encrypt()` derives from `APP_KEY`. DB + server compromise exposes all tokens.
**Fix:** `APP_KEY` never in source control or alongside DB backups. Production: envelope encryption (AWS KMS).

---

## Medium — Infrastructure

### 21. FX rates missing for weekends
ECB publishes weekdays only. Saturday order lookup returns null → zero revenue in snapshots.
**Fix:** FX lookup: `WHERE date <= :order_date ORDER BY date DESC LIMIT 1`. Hard requirement.

### 22. ECB multi-day outage
No rates for several days.
**Fix:** Retry 3x with backoff. After 48h failure: fire `system_alert`, fall back to last available rate with amber "estimated" badge.

### 23. Single Redis for cache + queue + sessions
LRU eviction can kill queue jobs or log users out.
**Fix:** Separate Redis databases: db 0 cache (`allkeys-lru`), db 1 queue (`noeviction`), db 2 sessions.

### 24. Stale dashboard with no warning
If snapshot build fails, dashboard shows yesterday's data silently.
**Fix:** If `daily_snapshots.built_at` > 26h old, show warning banner + fire alert.

### 26. ga4_daily NULL duplicates in unique constraint
PostgreSQL treats NULLs as distinct. Two rows with NULL country won't conflict.
**Fix:** `COALESCE(country, '__none__')` in unique constraint, or partial unique index.

---

## Implementation Details (missing from other docs)

### 28. Inertia shared data
```php
// HandleInertiaRequests middleware
'auth' => ['user' => $request->user()],
'workspace' => $workspace ? ['id', 'name', 'slug', 'reporting_currency', 'reporting_timezone'] : null,
'unreadAlerts' => $workspace ? Alert::unread()->count() : 0,
'dataFreshness' => $workspace ? DailySnapshot::latestBuiltAt($workspace->id) : null,
'permissions' => $workspace ? [
    'role' => workspace_role($request->user()),
    'canAccessFinancials' => Gate::allows('access-financials'),
    'canAccessPii' => Gate::allows('access-pii'),
    'canAccessSettings' => Gate::allows('access-settings'),
    'canManageMembers' => Gate::allows('manage-members'),
    'canManageWorkspace' => Gate::allows('manage-workspace'),
    'canManageSettings' => Gate::allows('manage-settings'),
    'canManageData' => Gate::allows('manage-data'),
] : null,
```
Frontend uses `permissions` to hide sidebar items, table columns, and action buttons. Never rely on frontend hiding alone — backend gates enforce on every route.

### 30. COGS edit triggers downstream
`UpdateCogsController` → dispatch `BackfillCogsOnOrdersJob` → dispatch `DispatchSnapshotCorrections`. No manual "Recalculate" button.

### 31. Annotation refresh
After POST, frontend calls `router.reload({ only: ['sparkline'] })` to refresh chart with new annotation.

### 32. Saved views filter grammar
`{"field_operator": value}` — e.g. `{"contribution_margin_lt": 0, "shipping_country_in": ["DE","AT"]}`. All AND. Operators: `eq`, `lt`, `gt`, `lte`, `gte`, `in`, `not_in`, `contains`.

### 33. CSV upload: partial import
Validate each row. Skip invalid. Return: `{imported: 45, skipped: 3, errors: [{row: 12, reason: "..."}]}`.

### 34. Docker / environment fixes needed
- PDF generation uses `barryvdh/laravel-dompdf` (no extra Docker service needed)
- **Add `gd` extension** to Dockerfile (image processing)
- **Add Node 22+** to Dockerfile or run Vite on host during dev
- `.env.example` has been rebuilt with correct defaults (pgsql, redis, all OAuth vars)
- No Postgres extensions needed for MVP

### 35. Testing & Dev Environment

**Test database setup:**
Pest with `RefreshDatabase` trait against real PostgreSQL (not SQLite — JSONB, partial indexes, `AT TIME ZONE`, `COALESCE` in unique constraints all break on SQLite). Add to `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="nexstage_test"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="CACHE_STORE" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
```
Create the test database in `docker/postgres/init.sql`: `CREATE DATABASE nexstage_test;`. `RefreshDatabase` runs migrations automatically — no seeders needed for tests (factories build all state).

**Critical-path tests (must exist before dependent code):**
Build order mirrors section "Build Order" above. Each layer needs tests before the next layer ships:
1. `CurrencyConverterService` — weekend FX fallback returns Friday rate; missing pair throws `FxRateNotFoundException`; rounding uses banker's rounding
2. `DateRange` — 30d default; MTD start-of-month; timezone crossing (UTC+2 order at 23:30 local → correct date); comparison period equal-length; DST 23/25-hour days
3. `ChannelClassifierService` — click-ID detection (gclid→paid_search, fbclid→paid_social); priority ordering (first-match-wins); workspace override beats global; fallback to direct/unassigned
4. `SnapshotBuilder` — **most critical service**, test: empty workspace (all zeros, no crash); single store single day; multi-store aggregation (workspace row sums stores, ad spend independent); FX conversion applied; timezone boundary (order at 23:30 local falls on correct snapshot date); COGS change triggers rebuild; refund on refund-date not order-date; percentages recomputed from raw totals (never summed across stores)
5. `WorkspaceScope` — scopes queries to workspace_id; throws RuntimeException when context not set; queue jobs auto-set context via `WorkspaceAwareJob`
6. Webhook HMAC middleware — valid Shopify signature passes; invalid rejects 401; missing header rejects 401

**External API mocking:**
Never hit real APIs. Use `Http::fake()` with fixture files:
```
tests/Fixtures/Shopify/orders_page_1.json
tests/Fixtures/Meta/insights_response.json
tests/Fixtures/Google/ga4_report.json
```
For service classes (`ShopifyClient`, `MetaAdsClient`), mock at the HTTP layer with `Http::fake()` — not with mock service classes. This tests the real parsing/mapping code. For OAuth flows, fake the token exchange response. For webhook tests, compute real HMAC from fixture payload + test secret.

**What NOT to test at MVP:**
Skip browser/Dusk tests, Vue component tests, visual regression, and end-to-end. Skip testing Filament admin panel. Skip testing PrimeVue DataTable rendering. Frontend correctness comes from manual QA until post-launch. Focus 100% on PHP: services, jobs, controllers (via Inertia test helpers).

**CI pipeline (runs on every PR):**
```yaml
# Target: under 3 minutes
- pint --test          # code style (10s)
- larastan analyse     # static analysis level 6 (30s)
- pest --parallel      # tests against Postgres service (2min)
```
GitHub Actions with `services: postgres:18`. Pest `--parallel` uses test databases per process (`nexstage_test_1`, `nexstage_test_2`, etc. — Pest handles this). Fail the PR on any step failure.

**Dev environment first-run:** See CLAUDE.md. After PHP code changes: `docker compose restart php horizon`.

---

## Data Integrity Rules

### 36. Refund date attribution
Refunds debit the snapshot on the **refund's own date** (standard accrual), not backdated to the order date. The order's `refund_total` and `net_revenue` update regardless. Refunds older than 7 days are NOT auto-corrected in snapshots — require on-demand rebuild.

### 37. Webhook idempotency
Beyond unique constraints, use `INSERT ... ON CONFLICT DO UPDATE` (not read-check-write). Add unique constraint on `refunds(order_id, platform_refund_id)`. Use Redis lock keyed on `platform_order_id` with 5s TTL to serialize concurrent webhook processing for the same order.

### 38. Currency rounding
Use banker's rounding: `ROUND(amount * rate, 2)` with `ROUND_HALF_EVEN`. Sum already-rounded converted values in snapshots (don't re-convert aggregates). Expected drift: ~50 currency units per 10K orders — acceptable for analytics, not accounting.

### 39. is_new_customer during ongoing sync
During webhook sync (not import), set via: `is_new_customer = (customer.first_order_at IS NULL OR order.created_at <= customer.first_order_at)`. This handles multi-store race conditions where Store B order arrives before Store A is fully processed.

### 40. Zero-quantity line items
Shopify/WC can send line items with quantity=0 (bundle headers, free gifts). Filter with `WHERE quantity > 0` in velocity, COGS, and units_sold calculations. A quantity-0 line with price > 0 is a display artifact — exclude from financial calculations.

### 41. Test orders must be filtered (PRODUCTION BLOCKER)
Shopify Bogus gateway test orders (`test = true`, `payment_gateway = 'bogus'`) corrupt revenue, AOV, and order count if imported. During sync: skip orders where `test = true`. Add filter to bulk import query and webhook handler.

### 42. Order editing recomputes COGS
When Shopify fires `ORDERS_UPDATED` with changed line items, the upsert handler must re-walk line items and recompute `cogs_total`. Mark the snapshot for that order's date as dirty for rebuild.

### 43. Gift card products
Gift card sales are real revenue but have no COGS or fulfillment cost. When redeemed, they appear as a payment method (no double-counting at order level). Flag `product_type = 'gift_card'` — exclude from COGS completeness warnings and inventory velocity.

### 44. POS orders
POS orders have `source_name = 'pos'` and no UTM data — channel resolves to `direct`. Include in revenue/orders (they're real sales). Add source_name filter chip to Orders and Dashboard pages.

### 45. Free orders ($0)
$0 orders (100% discount) count in `orders_count` and CVR but drag AOV down. Include by default. Suppress COGS warnings for $0 line items. Offer "exclude $0 orders" saved view.

### 46. WooCommerce order status mapping
WC statuses don't match Shopify. Map during sync: `processing` → `paid`, `completed` → `paid`, `on-hold` → `authorized`, `pending` → `pending`, `failed` → `voided`, `cancelled` → `cancelled`, `refunded` → `refunded`, `checkout-draft` → skip (not a real order). Unknown custom statuses → `pending` with log warning.

### 47. WooCommerce guest checkout with empty email
If `billing.email` is empty/null after trim, set `orders.customer_id = null` (skip customer creation). Schema already allows nullable `customer_id`. These orders still count in revenue/AOV but have no customer attribution.

### 48. WooCommerce pagination reconciliation
After initial paginated import completes, run one `modified_after` sweep from import start time to catch orders that changed during import. Page-based pagination in ascending order mitigates most shift issues but doesn't eliminate them.

### 49. COGS overlap tiebreaker
When multiple `cogs_entries` overlap in date range for the same product/variant, use **latest `effective_from` wins**: `ORDER BY effective_from DESC LIMIT 1`. When saving a new COGS entry, auto-close the previous entry's `effective_to` to `new_entry.effective_from - 1 day`. Prevents ambiguous cost lookups.

### 50. RFM 3-tier scoring (20-100 customers)
When workspace has 20-100 customers, use `NTILE(3)` instead of `NTILE(5)`. Three tiers: **Best** (score 3), **Average** (score 2), **At Risk** (score 1). Segment mapping: Best = Champions + Loyal, Average = Potential + Needs Attention, At Risk = About to Sleep + Hibernating. Below 20 customers: show "Not enough data for segmentation."

### 51. Channel mapping priority validation
Workspace custom rules could assign broad rules at lower priority than narrow ones (e.g., `facebook/*` at priority 3 vs `facebook/cpc` at priority 5), causing paid traffic to classify as organic. **Fix:** When saving workspace rules, warn if a broad pattern (null medium) has a lower priority number than a narrow one (specific medium) for the same source.

### 54. Out-of-order refund webhooks
Refund webhook arriving before order webhook fails on `order_id` FK. **Fix:** If refund references unknown order, queue for retry with 60s delay (max 3 retries). After 3 failures, discard with warning log.

### 55. Webhook from disconnected store
Webhook routes still accept POSTs after store disconnection. Handler processes data for disconnected store. **Fix:** Check `stores.sync_status` in webhook handler. If disconnected, return 200 (stop retries) but discard data. Deregister webhooks when disconnecting.

### 56. Store reconnection flow
`(platform, platform_store_id)` unique constraint prevents duplicate stores, but reconnection UX is unspecified. **Fix:** On OAuth callback, check for existing disconnected store with same `platform_store_id`. If found, reactivate (set `sync_status = 'active'`, refresh token). Skip re-import for data that already exists (upserts handle this).

### 57. Negative net_revenue (refund > order total)
Store credit refunds can exceed order total. `net_revenue = total - discounts - refunds` goes negative. Mathematically correct but misleading without context. **Fix:** Flag orders where `refund_total > total_price` with warning badge. Consider capping refund attribution at order total for P&L, excess in "store credit issued" memo.

### 58. CSV upload limits
No row cap on COGS CSV. 1M rows would hammer DB with upserts + trigger full backfill. **Fix:** Hard cap at 50K rows. Process in batches of 500 (transaction boundary).

### 59. Concurrent export limit
`throttle:120,1` allows 100+ concurrent export jobs. Each generates an S3 file. **Fix:** Max 3 pending exports per user. Return 429 if exceeded.

### 60. Quadrant scatter at scale
`PERCENT_RANK()` over 500K products is expensive. **Fix:** `LIMIT 1000` on quadrant query with "showing top 1000 by revenue" note.

### 61. XSS on user text fields
`annotations.body`, `products.notes`, `product_variants.notes` are user input rendered in Vue. Vue auto-escapes but ECharts markLine labels may not. **Fix:** Server-side strip HTML tags on save for annotations and notes.

### 62. All server-side queries must be paginated
Customer list, order list, GSC cannibalization, discount code analysis — every query returning user-facing data needs `LIMIT`/`OFFSET` (or cursor). PrimeVue DataTable uses server-side pagination. No unbounded result sets.

---

## Security & Compliance (launch blockers marked)

### 63. DPA required (LAUNCH BLOCKER)
You process EU customer PII on behalf of merchants. You are the data processor, merchant is the controller. Prepare a standard DPA available at signup.

### 64. Privacy policy (LAUNCH BLOCKER)
Required by GDPR and Shopify partner agreement. Must explain what store customer data you ingest and store.

### 65. Data retention policy (LAUNCH BLOCKER)
Define retention periods per data category. Currently no retention limits on any table.

### 66. Database backup strategy (LAUNCH BLOCKER)
No backup plan documented. Implement automated backups with point-in-time recovery before launch.

### 67. OAuth state parameter
All custom OAuth controllers (Shopify, Meta, Klaviyo) must implement CSRF `state` parameter. (TikTok: v2) Laravel Socialite handles this for Google login automatically, but custom flows need explicit implementation.

### 68. Rate limiting on own API
Add Laravel throttle middleware to `/{workspace}/api/*` routes. Prevent abuse of export/data endpoints.

### 69. Email delivery
Using own SMTP infrastructure. Configure `MAIL_MAILER=smtp` with own server in `.env`. No third-party provider needed.

### 70. Input validation
Document validation rules for all controllers. Whitelist allowed field names in saved_views filter parser to prevent column enumeration. Never use `v-html` on user-generated content.

### 71. Log sanitization
`sync_logs.error_message` may contain PII from API responses. Scrub customer data before storing.

---

## Performance Notes

### 73. Filtered cohort queries
Live joins at 500K orders will be 2-5 seconds. Cache filtered results 30 seconds. At extreme scale, consider nightly materialized views for common filter combinations.

### 74. Revenue-per-query at scale
4 CTEs with window functions. At 100K GSC rows × 50K orders: viable but 3-7 seconds. Add `LIMIT 500` on final output. Consider pre-computing query-to-page mapping during GSC sync.

### 75. PostgreSQL FTS for MVP
Adequate with GIN indexes and debounced input (300ms). Use `LIMIT 5` per entity. Upgrade to Meilisearch when search-as-you-type latency matters.

---

## Error Handling & Retry Patterns

### 76. Queue job defaults
All sync jobs must define: `$tries = 3`, `$backoff = [60, 300, 900]` (1min, 5min, 15min), `$timeout = 300` (5 min). Failed after 3 attempts → log to `failed_jobs`, fire `system_alert`. Monitor Horizon failed jobs dashboard.

### 77. API rate limit retry
- **Shopify 429**: Read `Retry-After` header, sleep and retry. Max 5 retries.
- **Meta**: Check `x-business-use-case-usage` header, back off at 75% utilization. Exponential backoff: 30s, 60s, 120s.
- **WooCommerce**: No rate limit from WC itself but respect hosting. If 5xx, retry with 60s backoff, max 3 retries. If unreachable for 6+ hours, fire integration disconnect alert.

### 78. Shopify bulk operation failure
If bulk op fails or times out: `DetectStuckImportsJob` (every 10min) detects stuck ops. Retry the failed operation once. If second attempt fails, mark import as `partial`, fire alert to user showing which data type failed. Discard partial JSONL output — never import incomplete bulk data.

### 79. Snapshot build failure
Each store snapshot is built independently (one store failure doesn't block others). Wrap each store's build in a transaction — rollback on failure, keep previous snapshot. If nightly build fails, the 06:30 corrections job catches it. If `built_at` > 26h old for any workspace, fire alert (#24).

### 80. Export job failure
On failure: set export status to `failed` in DB. Polling endpoint returns `{"status": "failed", "error": "..."}`. User sees error message with retry button. Auto-cleanup failed export records after 24h.

### 81. Database transaction boundaries
- Order upserts: wrap in transaction (order + line items + refunds together)
- Snapshot builds: one transaction per store per date
- COGS backfill: batch updates of 500 line items per transaction
- If connection drops mid-transaction, PostgreSQL auto-rolls back

### 82. GA4 sampled data
When `samplingMetadatas` is present in response: narrow the date range and re-query in smaller chunks (7-day windows). Log warning. Show amber "sampled" badge on affected metrics if sampling can't be eliminated.

---

## Shopify App Store Requirements

### 83. GDPR webhooks (MANDATORY for App Store)
Three webhooks must be handled — respond with 200, complete within 30 days:
- `customers/data_request` → return all data held for that customer
- `customers/redact` → anonymize/delete customer data
- `shop/redact` → erase ALL store data 48h after app uninstall
Routes added to coding-spec section 24.

### 84. Shopify billing: external billing via Stripe (multi-platform exception). No Shopify Billing API needed.

### 85. Expiring offline access tokens
Since April 2025, new public Shopify apps must use expiring offline access tokens (not permanent). Token rotation must be implemented — refresh before expiry. `RefreshOAuthTokensJob` handles this but the Shopify-specific token exchange flow is not documented.

---

## OAuth & Integration Linking

### 86. OAuth callback → workspace linking
OAuth callbacks (`/shopify/callback`, `/oauth/facebook/callback`, etc.) have no workspace prefix. The callback must know which workspace to attach the integration to. **Fix:** Encode `workspace_id` in the OAuth `state` parameter. On callback, decode state, verify workspace membership, then create the integration record linked to that workspace.

### 87. Shopify app uninstall handling
When merchant uninstalls: Shopify sends `APP_UNINSTALLED` webhook. Must: revoke tokens, pause all sync jobs, mark store as `disconnected`, and handle `shop/redact` within 48h. Not documented.

---

## Billing & Notifications

### 88. Billing + trial implementation
Stripe via Laravel Cashier for all customers (multi-platform billing exception for App Store). Trial: 14 days, import limited to 1 month history. After trial: syncs stop, dashboard stays accessible with stale data banner. Upgrade: full history backfill + syncing resumes. During initial import, enforce 1-month window: `WHERE created_at >= NOW() - INTERVAL '1 month'` in bulk import queries. On upgrade, re-trigger full import without the date limit.

### 89. Alert delivery to browser
New alerts reach the browser via **Inertia partial reload polling** (simplest, no WebSocket needed). `unreadAlerts` count is in Inertia shared data. Poll every 60 seconds via `router.reload({ only: ['unreadAlerts'] })`. Full alert list loads on Alerts page visit.

### 90. Email delivery
Own SMTP infrastructure. From address: `digest@nexstage.io`. Digest subject: "Your [daily/weekly] recap — €X revenue (+Y%)".

### 91. Funnel page bypasses snapshots (by design)
The funnel page queries `ga4_daily` directly for step-by-step data (item_views, add_to_carts, checkouts_started) — these columns are NOT in `daily_snapshots` (only `sessions` is). This is correct: funnel data needs source/device dimensions that snapshots don't have. Document as intentional exception to the "dashboards hit snapshots" rule.

### 92. Klaviyo email revenue attribution
Klaviyo-attributed email revenue (from `email_campaigns.revenue`) may overlap with store order revenue — the same order can be claimed by both Klaviyo (email attribution) and the store (order total). `daily_snapshots.email_revenue` is an ATTRIBUTION metric, not additive revenue. Never add `email_revenue` to `net_revenue` — they overlap. Show as a separate "Email attributed revenue" metric.

---

## Scaling Notes

### 93. GA4 daily row explosion
With `landingPagePlusQueryString` dimension, ga4_daily can reach 75K rows/day per workspace (500 pages × 10 sources × 5 countries × 3 devices). Over 90 days = 6.75M rows. **Fix:** Normalize `landing_page` by stripping query params during GA4 sync (same as we do for orders). Add hash partitioning on ga4_daily by workspace_id at scale. Consider adding BRIN index on `(workspace_id, date)`.

### 94. Post-import snapshot build parallelization
After bulk import, `BuildSnapshotsForDateRange` must build ~1000+ snapshots for stores with multi-year history. **Fix:** Parallelize via job dispatch — each date as its own queued job on `snapshots` queue. Horizon processes them concurrently. Don't block the user on sequential builds.

### 95. SnapshotBuilder memory
Pipeline steps should use raw SQL aggregates (`SUM`, `COUNT`, `AVG`), NOT Eloquent collection hydration. If any pipe loads order models into memory, 10K orders/day × ~1KB per model = 10MB per pipe, which is fine for `memory_limit=256M`. But avoid `Order::where(...)->get()` — always use `->selectRaw()` or `DB::table()`.

### 96. Performance best practices
- [ ] `Model::preventLazyLoading(!app()->isProduction())` in AppServiceProvider
- [ ] Connection pooling: configure `max_connections` or add PgBouncer; document Horizon `maxProcesses`
- [ ] `Redis::pipeline()` for batch writes (SnapshotBuilder, ComputeVelocityJob)
- [ ] php.ini: `opcache.jit=1255`, `opcache.jit_buffer_size=128M`
- [ ] Gzip: `a2enmod deflate` in Dockerfile (html, json, js, css)
- [ ] ECharts: `import { use } from 'echarts/core'` — never `import *`
- [ ] Vite: `import.meta.glob('./Pages/**/*.vue', { eager: false })` for route-level splitting
- [ ] CDN for static assets in production

### 97. Security best practices
- [ ] HTTPS + security headers at Traefik level (HSTS, nosniff, DENY, strict-origin-when-cross-origin); configure `TrustProxies`
- [ ] `APP_DEBUG=false` in production (CI check)
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` in production
- [ ] `$fillable` on every model; explicit column lists in upserts; never `$request->all()`
- [ ] Shared link tokens: `Str::random(64)` + expiry; invitation tokens: `throttle:10,1`
- [ ] `composer audit` + `npm audit` in CI
- [ ] `spatie/laravel-activitylog` for sensitive actions (role changes, disconnects, COGS edits, deletions, token refresh)

### 98. Launch checklist (product gaps)
- [ ] Transactional emails: welcome, import-complete, invitation (plain text OK for MVP)
- [ ] OAuth failure: redirect to Settings > Integrations with flash error (no generic error page)
- [ ] Account deletion: owner-only, 30-day soft delete; delete user record only when removed from ALL workspaces
- [ ] Support link: `support@nexstage.io` in sidebar footer
- [ ] Legal pages: DPA, Privacy Policy, ToS at `/legal/{privacy,terms,dpa}`; link from signup footer + Settings

### 99. URL structure
- **Workspace:** Slug in URL path (`/{slug}/dashboard`). Changeable — store old slugs in `workspace_slug_history` table, 301 redirect from old to current.
- **Resource IDs:** Bigint PK in DB (fast joins). Encode with Hashids in URLs/API responses (`orders/lejRej` not `orders/50000`). Per-model salt. Package: `vinkla/hashids`.
- **Filter state:** Query params: `?from=2026-04-01&to=2026-04-30&channel=paid-social&range=last7d`. ISO 8601 dates.
- **Shareable URLs:** Copy URL, send to teammate — loads identically if they have workspace access. No extra tokens needed.
- **SSR:** Not needed for authenticated dashboard. For shared link previews (v2): use Inertia `Head` component for OG meta tags, no SSR process.

### 100. Subscription state machine
**States:** `trialing` → `active` → `past_due` → `canceled/expired`

**Key rules:**
- Keep syncing during `past_due` (Stripe retries 3x over ~3 weeks — don't punish transient card failures)
- Pause syncs only on `customer.subscription.deleted` (all retries exhausted)
- Add `syncs_paused_at` timestamp on `workspaces` — single flag for sync scheduler

**Stripe webhooks (6):** `subscription.created` (activate), `subscription.updated` (status sync), `subscription.deleted` (pause syncs), `invoice.paid` (clear past_due), `invoice.payment_failed` (log + email warning), `subscription.trial_will_end` (3-day warning email).

**Sync control:** Scheduler filters `WHERE syncs_paused_at IS NULL AND stripe_status IN ('trialing','active','past_due')`. Jobs also check in `WorkspaceAwareJob::handle()` (belt + suspenders).

**Trial → Paid:** On first `invoice.paid`, dispatch `StartHistoricalImportAction` to backfill full history (trial only imported 1 month). Use dedicated `historical-import` queue.

**Data visibility for inactive:** `DateRange` enforces `start >= syncs_paused_at - 30 days`, `end <= syncs_paused_at`.

### 101. Integration disconnect/reconnect lifecycle
Disconnect is undocumented for 4 of 5 integration types. Routes added to coding-spec. On disconnect for ALL types:
1. Set `sync_status = 'disconnected'`
2. Revoke/delete OAuth tokens
3. Stop processing webhooks from this integration (#55)
4. Keep historical data (don't delete orders/insights — just stop syncing)
5. For Shopify: deregister webhooks via API
6. For WooCommerce: delete registered webhook via `DELETE /wc/v3/webhooks/{id}`

On reconnect (re-OAuth same integration): reactivate existing record, refresh tokens, **delete all existing Nexstage webhooks before registering new ones** (#159), resume syncing. Upserts handle data that arrived during disconnected period. Same as store reconnect (#56) but applies to all types.

### 102. Local OAuth with ngrok
OAuth callbacks need a public URL for Shopify/Meta/Google to redirect to. Use ngrok:
```bash
ngrok http https://127.0.0.1:443 --host-header=nexstage.dev.localhost --domain=your-subdomain.ngrok-free.dev
```
Set `NGROK_URL=https://your-subdomain.ngrok-free.dev` in `.env`. OAuth controllers should use `config('app.ngrok_url')` when set, falling back to `config('app.url')` for building redirect URIs. This avoids swapping `APP_URL` back and forth.

### 103. Deployment & CI/CD
- **Horizon restart:** After deploy, run `php artisan horizon:terminate` — workers restart automatically with new code. Don't just restart the container.
- **CI pipeline** (complete): `pint --test` → `larastan analyse` → `composer audit` → `npm audit` → `npm run build` → `pest --parallel` (with PostgreSQL service). Fail PR on any step.
- **Migration safety:** For ALTER TABLE on large tables (orders, order_line_items): use `CREATE INDEX CONCURRENTLY` (not inline). Set `statement_timeout = '30s'` to prevent locking. Test migrations on a copy of production data before applying.
- **Health check:** Add `GET /health` endpoint returning 200 with JSON `{status, db, redis, horizon}`. Use for load balancer + deploy smoke test.
- **Rollback:** Keep database migrations forward-only. If a deploy breaks, fix-forward (new deploy) rather than rolling back migrations. Revert code via git, re-deploy.

### 104. Timezone-aware snapshot scheduling
Snapshot build at 02:00 UTC works for EU merchants (03:00-04:00 local). But for UTC+13 (NZ), 02:00 UTC = 15:00 local — yesterday's snapshot isn't built until mid-afternoon. **MVP acceptable** — corrections job at 06:30 fixes it. If customers complain, add per-workspace scheduled build times in v2.

---

## Subtle Bugs (found in final adversarial review)

### 106. Touchpoints JSONB null/empty guard
Attribution queries access `touchpoints[0]` (first click) but `touchpoints` can be NULL (WC guests, no UTM), empty array `[]`, or malformed. Orders silently vanish from attribution reports. **Fix:** Guard with `jsonb_array_length(touchpoints) > 0` before accessing elements. Default unattributed orders to `channel = 'direct'`.

### 109. Digest job races with snapshot corrections
For a user with timezone Europe/Ljubljana and digest time 07:00 (= 05:00 UTC), the digest fires BEFORE GA4 sync (06:00) and snapshot corrections (06:30). User gets stale session/CVR data daily. **Fix:** Digest job should check `daily_snapshots.built_at` for the requested date — if stale (>26h), delay digest delivery by 2 hours and retry.

### 110. Zero-stores workspace crashes SnapshotBuilder + Dashboard
With 0 stores: no snapshots, DashboardController gets NULL, ratio computation produces NaN. Middleware doesn't redirect to onboarding. **Fix:** SnapshotBuilder skips gracefully when 0 stores. DashboardController coalesces NULLs to 0. Controller guard: redirect to `/onboarding` when `stores()->count() === 0`.

### 112. DateRange::fromRequest() crashes outside HTTP context
`workspace()->reporting_timezone` is null before middleware runs (queue jobs, Artisan commands, tests). **Fix:** Accept timezone as parameter: `DateRange::fromRequest($request, tz: $tz)`, fall back to UTC when workspace is null.

### 113. ChannelClassifierService N+1 during bulk import
Queries `channel_mappings` from DB on every order. 50K orders = 50K identical queries. **Fix:** Load channel mappings once at job start, cache in memory. Pass to classifier as constructor arg.

### 114. No re-classification when channel mapping rules change
`orders.channel` set once during sync. Editing rules doesn't update historical orders. **Fix:** When channel mappings saved, dispatch `ReclassifyOrdersJob` that re-runs classifier on all workspace orders + rebuilds affected snapshots.

### 117. Content Security Policy header missing (SECURITY)
No CSP specified in Traefik security headers (#97). ECharts manipulates DOM, user-provided annotation text is rendered in charts — missing CSP removes defense-in-depth against XSS.
**Fix:** Add `Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self'` to Traefik config. Adjust as needed for CDN assets.

### 118. Export download URL must be signed
`POST /api/export` generates S3 file — spec does not require signed URL with TTL. Permanent public S3 URLs leak sensitive data.
**Fix:** Use `Storage::temporaryUrl($path, now()->addMinutes(15))` for download URLs. Store path in `exports` table, generate signed URL on polling request.

### 119. Superadmin panel needs 2FA
Filament `/admin` with impersonation capability — compromised superadmin exposes ALL workspaces.
**Fix:** Require MFA on superadmin guard (TOTP via `laragear/two-factor` or similar). Consider IP allowlisting for production.

### 120. WooCommerce webhook HMAC verification
`WooCommerceConnector` interface has no `verifyHmac()` method unlike `ShopifyConnector`. WC uses `X-WC-Webhook-Signature` header with HMAC-SHA256 (consumer secret as key).
**Fix:** Add `verifyHmac(Request $request): bool` to `WooCommerceConnector` interface. In middleware: detect platform from URL prefix and dispatch to correct verification.

### 121. Negative Net Margin display
When `net_revenue < 0` (refund-heavy day), `Net Margin = (net_revenue - costs) / NULLIF(net_revenue, 0)` produces nonsensical values (e.g. 600%). **Fix:** When `net_revenue < 0`, display `—` with tooltip "Net revenue is negative — margin % not meaningful." Show the negative dollar amount instead.

### 122. customers.last_order_at not updated on refund
When an order transitions to `financial_status = 'refunded'` and it was the customer's most recent order, `last_order_at` still points to the refunded order. Customer appears more recently active than they are.
**Fix:** In refund webhook handler, if refunded order is the latest, recompute `last_order_at = MAX(created_at) WHERE financial_status NOT IN ('refunded','voided','cancelled')`.

### 123. ComputeRfmScoresJob scalability at 200K+ customers
`NTILE(5)` window function over 200K rows is a full sequential scan with no batching or timeout.
**Fix:** Set `$timeout = 600` (10 min). For workspaces >200K customers, consider batching by first_order_at month ranges.

### 124. Market basket self-join O(n²)
Section 29 self-join on `order_line_items` produces N² pairs per order before HAVING filter. At 1M orders × 3 items = 3M line items, intermediate result set is enormous.
**Fix:** The date range filter already limits scope, but add a hard cap: market basket only queries last 90 days maximum. Show note in UI: "Analysis based on last 90 days."

### 125. ReconcileStoreOrdersJob unbounded at scale
Re-fetches all orders updated in last 7 days from store API with no pagination cap. High-volume store (10K+ daily orders) could timeout.
**Fix:** Use cursor-based batching with page size of 250 orders. Set `$timeout = 600` for this job.

### 126. Horizon maxProcesses unspecified for flash sales
10K orders/hour = ~167 webhook jobs/minute. Without configured `maxProcesses`, Horizon could fall behind.
**Fix:** Configure in `config/horizon.php`: `maxProcesses` = 10 for `default` queue, 5 for `snapshots` queue. Document Redis queue db (`db 1`, `noeviction`) must be sized for at least 50K job payloads (~50MB).

### 128. COGS CSV upload conversion point undefined
When COGS uploaded in AUD but reporting currency is EUR, conversion timing is unspecified. `order_line_items.unit_cogs` has no currency column — must be in workspace currency.
**Fix:** Convert COGS to workspace reporting currency at upload time using the upload date's FX rate. Store converted value in `cogs_entries.cost`. Document that `cogs_entries.currency` is the original currency (audit trail) but `cost` is always in workspace currency.

### 129. DetectAnomaliesJob must be per-workspace
Scheduled as a single job but iterates `alert_rules` — relies on `WorkspaceScope`. Must be a dispatcher that fans out one job per active workspace (like `DispatchStorePollJobs` pattern).
**Fix:** Create `DispatchAnomalyDetectionJobs` that queries all active workspaces, dispatches `DetectAnomaliesForWorkspaceJob(workspace_id)` per workspace. Each extends `WorkspaceAwareJob`.

### 130. No job overlap prevention
If `ReconcileStoreOrdersJob` takes >30 min, the 02:00 snapshot build starts on partially-reconciled data. Hourly `DispatchStorePollJobs` can stack if rate-limited. **Fix:** Use `ShouldBeUnique` or `WithoutOverlapping` middleware on all scheduled jobs. Lock key: `job_class:workspace_id`.

### 131. BackfillCogsOnOrdersJob needs serialization
Two concurrent CSV uploads dispatch two concurrent backfill jobs, producing interleaved `unit_cogs` writes. **Fix:** Add `ShouldBeUnique` keyed on `workspace_id` to `BackfillCogsOnOrdersJob`. Second job waits for first to finish.

### 133. Velocity Redis cache not invalidated during flash sales
`workspace:{id}:velocity:{variant_id}` has 24h TTL. During high-volume selling, inventory levels change rapidly but velocity/stock projections are stale. **Fix:** In webhook order handler, invalidate velocity cache entries for affected variant IDs: `Redis::del("workspace:{id}:velocity:{variant_id}")`. Next page load recomputes from DB.

### 134. Workspace creation rate limit and cap
No rate limit on `POST /workspaces` and no per-user workspace cap. **Fix:** Add `throttle:10,1` to pre-workspace route group. Enforce max 10 workspaces per user in `StoreWorkspaceController`.

### 135. Segment push rate limit too permissive
`POST /segments/{id}/push` sends customer PII to external APIs. Inherits the generic `throttle:120,1`. **Fix:** Add dedicated `throttle:5,1` on segment push route. Add max 3 pending pushes per workspace (similar to concurrent export cap #59).

### 136. RFM: NULL last_order_at customers keep stale segment
Customers with all orders refunded/voided get excluded from NTILE but retain their old `rfm_segment` value. They appear as "Champions" with zero valid orders.
**Fix:** Before NTILE computation, reset all customers: `UPDATE customers SET rfm_segment = NULL, rfm_recency_score = NULL, rfm_frequency_score = NULL, rfm_monetary_score = NULL WHERE workspace_id = :workspace_id`. Then run NTILE only on customers with `last_order_at IS NOT NULL AND orders_count > 0`. Excluded customers correctly show no segment.

### 137. Workspace snapshot aggregation has no job sequencing
`DispatchDailySnapshots` fans out per-store jobs + workspace aggregate job. The aggregate job may run before all per-store jobs complete, producing understated revenue/costs until the next corrections run.
**Fix:** Use `Bus::chain()` to sequence: dispatch all per-store snapshot jobs first, then dispatch the workspace-level aggregation job only after all stores complete. Or: run the workspace aggregate synchronously within the dispatcher after all store jobs finish.

### 141. Workspace ownership on route-model binding must be systematic
With ~40 write endpoints using route model binding, a single missing `abort_unless($model->workspace_id === workspace()->id, 404)` check enables cross-workspace data access.
**Fix:** Create a base `WorkspaceOwnershipPolicy` or `EnsureWorkspaceOwnership` middleware that auto-verifies `workspace_id` on any route-model-bound resource. Apply to all write endpoints. Never rely on per-controller manual checks alone.

### 142. Channel mapping regex patterns — ReDoS risk
`utm_campaign_pattern` and `referring_site_pattern` accept arbitrary strings. If used as regex patterns, malicious input like `(a+)+$` causes catastrophic backtracking.
**Fix:** Restrict to LIKE syntax only (translate `%`/`_` to SQL LIKE), or validate regex patterns with `preg_match()` wrapped in a timeout. Set `pcre.backtrack_limit` as safety net.

### 143. Shared link rate limiting and PII stripping
SharedLinkController is fully public. No rate limiting. Token enumeration via brute force. Shared links may expose PII.
**Fix:** Add `throttle:30,1` middleware to shared link route. Strip PII (customer emails, names) from shared link responses. Token must be `Str::random(64)`.

### 144. Segment rule value size — unbounded IN clauses
Segment rules accept `value` with no size constraints. Operator `in` with 100K items causes enormous SQL IN clauses.
**Fix:** Validate per operator: `in`/`not_in` → `array|max:100`, `between` → `array|size:2`, scalar operators → `string|max:255` or `numeric`.

### 145. reporting_currency must be validated against supported currencies
`PUT /workspace` accepts any 3-char string for `reporting_currency`. Invalid codes break all FX conversions.
**Fix:** Validate against ECB-supported currencies: `in:EUR,USD,GBP,CHF,SEK,NOK,DKK,PLN,CZK,HUF,RON,BGN,ISK,TRY,AUD,BRL,CAD,CNY,HKD,IDR,ILS,INR,JPY,KRW,MXN,MYR,NZD,PHP,SGD,THB,ZAR`. Centralize as a PHP enum or config constant. Also add to section 44 validation for `PUT /workspace`.

### 146. WooCommerce store_url SSRF validation
`POST /woocommerce/connect` must prevent SSRF.
**Fix:** Parse URL → DNS resolve → reject RFC 1918/5735 private ranges (10.x, 172.16-31.x, 192.168.x, 127.x, 169.254.x, ::1). Reject non-HTTPS URLs. Use `gethostbyname()` + `ip2long()`.

### 147. Google Ads "ad_group" maps to "ad_sets" table
Google Ads API uses "ad_group" terminology. Our database normalizes to `ad_sets` table.
**Fix:** Google Ads sync must explicitly map: `ad_group.id` → `platform_adset_id`, `ad_group.name` → `name`. Document this in sync code to prevent accidental creation of a separate "ad_groups" table.

### 148. FxRateNotFoundException must not crash entire sync job
If an order uses a currency not supported by ECB, the sync job crashes, blocking all subsequent orders.
**Fix:** Catch `FxRateNotFoundException` per-order (not per-job). Log warning, set `total_price_converted = NULL`, continue processing. Dashboard already handles NULL converted values with COALESCE.

### 149. Disconnected-all-stores shows stale data with no warning
If all stores are disconnected but records exist, DashboardController doesn't redirect to onboarding (stores count > 0), but snapshots stop building.
**Fix:** Check for active stores (`sync_status = 'active'`), not just any stores. If zero active stores and no recent snapshots, show "No active integrations" warning banner.

### 150. Net Profit formula in section 2 (Dashboard KPIs) is incomplete
Section 2 inline formula omits OPEX and platform fees, showing CM3 labeled as "Net Profit". The comment says "joined at query time" but the formula doesn't show it.
**Fix:** Dashboard Net Profit KPI must include OPEX + platform fee proration from section 1. Reference section 1 formula for the complete computation.

### 151. DateRange 'lifetime' preset crashes on empty workspace
`earliestSnapshotDate()` returns NULL when no snapshots exist. `diffInDays()` on NULL crashes.
**Fix:** Guard: if no snapshots, fall back to `today()->subDays(29)` or show empty state.

### 152. GA4 data assigned to workspace-level snapshot when analytics_property.store_id is NULL
If analytics_property is not linked to a specific store, which snapshot gets the sessions?
**Fix:** Assign to workspace-level snapshot only (store_id = NULL). Never duplicate to per-store snapshots. Document as rule in SnapshotBuilder step 4.

### 153. customers.total_contribution_margin never updated
Described as "denormalized, updated on order sync" but no documented trigger or code updates it.
**Fix:** Update in OrderSynced listener (increment), on refund (decrement), and on COGS backfill (recompute as SUM from orders).

### 154. Segment push to Klaviyo/Meta — no implementation documented
Route `POST /segments/{id}/push` exists but no API calls, sync strategy, or data format documented.
**Fix:** Klaviyo: `POST /api/lists` + `POST /api/lists/{id}/relationships/profiles`. Meta: Custom Audiences API with email hash. Full replace strategy. Document in coding-spec.

### 155. Alert types other than metric anomalies have no generation logic
Only DetectAnomaliesForWorkspaceJob is documented. Speed drops, source disagreement, low stock, RFM migration alerts are listed but have no detection algorithm.
**Fix:** Add detection logic per type: speed_drop = score decreased >10 pts. low_stock = days_of_stock < 7. rfm_migration = customer moved from Champions to At Risk. Run in DetectAnomaliesForWorkspaceJob or dedicated jobs.

### 156. Shopify app install flow from App Store not documented
`GET /shopify/install` exists but controller behavior (HMAC verification, existing store check, unauthenticated user handling) is not specified.
**Fix:** Document ShopifyInstallController: verify HMAC params → check if shop connected → redirect to OAuth. Handle unauthenticated users (login first, resume install).

### 157. RfmGrid.vue must render 3x3 when customer count is 20-100
Section 36 seeds `rfm.simplified_tier_min = 20`, `rfm.minimum_customers = 100`. With NTILE(3), grid should be 3x3 not 5x5. No conditional rendering documented.
**Fix:** RfmGrid.vue: 5x5 when customers >= 100, 3x3 when 20-100, empty state when < 20.

### 158. Deleted orders must be soft-deleted via webhooks
Shopify merchants delete test orders. Deleted orders are invisible to the API afterward — reconciliation can't detect them.
**Fix:** Subscribe to `orders/delete` (Shopify) and `order.deleted` (WooCommerce) webhooks. Set `orders.deleted_at` on receipt. All reporting queries must filter `WHERE deleted_at IS NULL` (Laravel SoftDeletes handles this automatically). Do NOT attempt full-ID reconciliation — it doesn't scale.

### 159. Stale webhooks accumulate on store reconnect or DB reseed
If the local DB is reseeded or a store is reconnected, old webhook subscriptions remain on the platform. Each connect creates new ones, leading to 20+ duplicate webhooks firing for every event.
**Fix:** On every store connect/reconnect, before registering new webhooks: (1) Shopify: `GET /admin/api/2025-04/webhooks.json` → filter by our callback URL prefix → `DELETE` each. Or use GraphQL `webhookSubscriptions` query + `webhookSubscriptionDelete` mutation. (2) WooCommerce: `GET /wc/v3/webhooks?search=nexstage` → `DELETE /wc/v3/webhooks/{id}` for each. Run this cleanup as the FIRST step in `ConnectStoreAction` / `ShopifyCallbackController`, before creating any new subscriptions. Also run on app install callback (`ShopifyInstallController`) since the app may have been previously installed.
