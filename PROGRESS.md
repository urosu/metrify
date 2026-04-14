# Nexstage — Build Progress

Tracking document for implementation state. Checklist format; PLANNING.md is the spec, this tracks what's been built.

**Status:** Pre-launch. Phases 0 through 1.4 complete. Currently in Phase 1.5.

---

## ✅ Phase 0 — Foundations (complete)

All schema, model layer, connector interface, holiday system, product webhooks, integration flags, ccTLD detection.

## ✅ Phase 1 — MVP launch (complete)

Nav restructure, dashboard cross-channel view, MultiSeriesLineChart, SEO/Campaigns/Products/Performance pages, store URL management, billing, onboarding tiles, workspace events UI, notification preferences, webhook health, daily notes, admin impersonation, trial reactivation backfill.

## ✅ Phase 1.1 — UI Foundation (complete)

Source-tagged MetricCard primitive with 6 badges, target schema columns, view_preferences JSONB, BreakdownView interface spec.

## ✅ Phase 1.2 — Dashboard refactor (complete)

Hero → Real → Channels layout, "Not Tracked" terminology, "Show advanced metrics" toggle, iOS14 negative-Not-Tracked banner, Platform ROAS adjacent to Real ROAS, period comparison delta table.

## ✅ Phase 1.3 — Trust + attribution (complete)

ComputeUtmCoverageJob, unrecognized utm_source surfacing, Tag Generator, Manage nav section.

## ✅ Phase 1.4 — Per-page features + BreakdownView (complete)

BreakdownView full implementation, view_preferences persistence, 14-day binary dot strips, daily average delta widget, latest orders feed, Winners/Losers filter chips, sidebar dropdown.

---

## 🔄 Phase 1.5 — Foundation & Data Layer (current)

**Goal:** Schema pass + attribution parser + channel classifier + COGS reader + queue restructure + sync reliability + operational readiness. No new visible pages. Data foundation every Phase 1.6 page reads from.

### Step 1 — Schema finalisation pass

- [ ] Write all 12 migrations per PLANNING section 5.5
- [ ] `migrate:fresh --seed` runs cleanly
- [ ] Verification checklist (PLANNING section 5.5) all checked
- [ ] `migrate:rollback` reverses cleanly

### Step 2 — Workspace settings + Store country prompt

- [ ] `WorkspaceSettings` value object / Eloquent cast on `workspaces.workspace_settings`
- [ ] `<StoreCountryPrompt />` React component
- [ ] Wired into onboarding step 3 after store connection
- [ ] Wired into "add another store" flow
- [ ] Store settings page shows persistent informational notice when `primary_country_code` is NULL
- [ ] ccTLD detection pre-fills dropdown from `stores.website_url`
- [ ] Skip button writes NULL explicitly

### Step 3 — AttributionParserService + sources

- [ ] `AttributionSource` interface
- [ ] `ParsedAttribution` value object (plain PHP, not Eloquent)
- [ ] `AttributionParserService` with first-hit-wins loop (no blending)
- [ ] `PixelYourSiteSource` — reads `pys_enrich_data`, parses pipe-delimited UTMs, handles "undefined" literal
- [ ] `WooCommerceNativeSource` — maps existing `orders.utm_*` columns into JSONB shape
- [ ] `ReferrerHeuristicSource` — direct/organic/referral rules by domain
- [ ] Unit tests for each source in isolation
- [ ] Feature test: PYS store (Klaviyo email) → parser returns email not organic-google
- [ ] Feature test: WC-native-only store → parser returns utm values from columns
- [ ] Feature test: referrer fallback case → parser returns heuristic result
- [ ] `/admin/attribution-debug/{order_id}` debug route renders full pipeline

### Step 4 — ChannelClassifierService + seed

- [ ] `ChannelClassifierService::classify(utm_source, utm_medium, workspace_id)` method
- [ ] `channel_mappings` seeder with ~40 global rows (PLANNING section 16.4)
- [ ] Workspace row overrides global row — test coverage
- [ ] Returns `{channel_name, channel_type}` tuple
- [ ] Integrated into `AttributionParserService::parse()` via `withChannel()`

### Step 5 — COGS reader

- [ ] `CogsReaderService` class
- [ ] WC core COGS reader (`WC_Order_Item::get_cogs_value()` meta)
- [ ] WPFactory reader (`_alg_wc_cog_*` meta)
- [ ] WooCommerce.com Cost of Goods reader (`_wc_cog_*` meta)
- [ ] Priority-ordered resolution (core → WPFactory → WC.com)
- [ ] Writes to `order_items.unit_cost`
- [ ] Tests with fixture orders for each source

### Step 6 — StoreConnector capability flags

- [ ] Interface extended with `supportsHistoricalCogs()`, `supportedAttributionFeatures()`, `supportsMultiTouch()`
- [ ] `WooCommerceConnector::supportsHistoricalCogs()` returns true when any of three COGS plugins detected
- [ ] `WooCommerceConnector::supportedAttributionFeatures()` returns `['last_touch','referrer_url','landing_page']` (single-touch baseline; PYS upgrades via parser output)
- [ ] `WooCommerceConnector::supportsMultiTouch()` returns false

### Step 7 — Sync job refactor (feature-flagged)

- [ ] `UpsertWooCommerceOrderAction` injects `AttributionParserService` and `CogsReaderService`
- [ ] Parser called once per order, writes to new `attribution_*` columns
- [ ] Existing `utm_*` column writes preserved unchanged (RevenueAttributionService still reads them)
- [ ] COGS reader called for each line item, writes to `order_items.unit_cost`
- [ ] Feature flag `ATTRIBUTION_PARSER_ENABLED` with env config
- [ ] Flag default: true in dev, false in prod until backfill completes

### Step 8 — BackfillAttributionDataJob

- [ ] Job class on `low` queue
- [ ] Per-workspace dispatch
- [ ] Batches orders in chunks to avoid memory issues
- [ ] Re-processes every existing order through parser pipeline
- [ ] Progress tracking (processed / total) surfaced on `/admin/system-health`
- [ ] Admin UI button to dispatch manually for a workspace
- [ ] Idempotent — safe to re-run

### Step 9 — Shared UI primitives + scope filtering + QuadrantChart generalisation

- [ ] `<WhyThisNumber />` modal primitive — formula, sources, raw values, conflicting platform values, view raw data link
- [ ] `<DataFreshness />` indicator primitive — green/amber/red dot with per-integration tooltip
- [ ] Scope filter component — sticky top of analytics pages, store + integration + date selectors
- [ ] Scope persists in URL and `view_preferences`
- [ ] `ScopedQuery` helper trait or scope for models accepting `(workspace_id, store_ids?, integration_ids?, date_range)`
- [ ] QuadrantChart accepts `xField`, `yField`, `sizeField`, `colorField` props
- [ ] Existing campaigns-page QuadrantChart behavior remains the default configuration
- [ ] Scope-aware annotations: daily_notes and workspace_events with scope_type filter render only when scope matches

### Step 10 — Winners/Losers backend + classifier

- [ ] Server-side ranking endpoint `GET /campaigns?filter=winners|losers&classifier=target|peer|period`
- [ ] Same for `/analytics/products`, `/stores`
- [ ] `vs Target` classifier (requires target set)
- [ ] `vs Peer Average` classifier (workspace-average comparison)
- [ ] `vs Previous Period` classifier (noisy, available but never default)
- [ ] Default logic: target when set, peer average otherwise
- [ ] Frontend Winners/Losers chips gain classifier dropdown
- [ ] Tests for each classifier on each endpoint

### Step 11 — BreakdownView adoption on legacy pages

- [ ] `/countries` migrated from manual table to BreakdownView (side-by-side columns per PLANNING 12.5)
- [ ] `/analytics/daily` migrated to BreakdownView with `breakdownBy='date'`
- [ ] Both pages pre-join data server-side and pass flat `BreakdownRow[]`

### Step 12 — Queue restructure

- [ ] `config/horizon.php` rewritten with per-provider supervisors per PLANNING 22.1
- [ ] `SyncAdInsightsJob` declares queue based on `integration.provider`
- [ ] `SyncSearchConsoleJob` → `sync-google-search`
- [ ] `SyncStoreOrdersJob`, `SyncProductsJob`, `SyncRecentRefundsJob`, `PollStoreOrdersJob` → `sync-store`
- [ ] `RunLighthouseCheckJob` → `sync-psi`
- [ ] Historical import jobs → `imports`
- [ ] `ProcessWebhookJob` → `critical-webhooks`
- [ ] Background jobs → `low`
- [ ] Rate-limit release path verified (FB rate limit does not block other queues)
- [ ] Feature test: dispatch FB sync → lands on `sync-facebook`

### Step 13 — Sync reliability

- [ ] `PollStoreOrdersJob` hourly fallback — checks `last_successful_delivery_at`, skips when webhooks healthy
- [ ] `ReconcileStoreOrdersJob` extended with hard-delete detection (orders in DB but not in 7-day store response)
- [ ] Status change detection verified (orders in both with different fields get updated)
- [ ] Webhook health tracking: `store_webhooks.last_successful_delivery_at` updated on successful delivery
- [ ] Store deletion path: `StoreConnector::removeWebhooks()` called **before** store record deletion
- [ ] `WooCommerceConnector::removeWebhooks()` iterates `store_webhooks` rows and calls WC API to delete
- [ ] Webhook cleanup failure logs warning but does not block store deletion
- [ ] Feature test: deleting store removes platform webhooks
- [ ] Feature test: deleted order detected by reconciliation and hard-deleted
- [ ] Feature test: `PollStoreOrdersJob` skips when webhooks fresh, polls when quiet

### Step 14 — Attribution service cutover (feature flag flip)

- [ ] `RevenueAttributionService` switched to read from `orders.attribution_last_touch` JSONB
- [ ] Hardcoded `FACEBOOK_SOURCES` and `GOOGLE_SOURCES` constants deleted
- [ ] Classification now happens via `ChannelClassifierService`
- [ ] Existing `/campaigns` page continues to work unchanged
- [ ] Existing tests pass
- [ ] Feature flag `ATTRIBUTION_PARSER_ENABLED` switched to true in prod

### Step 15 — Operational prerequisites

- [ ] UTM coverage onboarding modal — active nudge when coverage <50% post ad-connect
- [ ] `/admin/silent-alerts` admin review UI with TP/FP/unclear tagging
- [ ] Campaign `previous_names` fallback path in `RevenueAttributionService`
- [ ] IP geolocation on first login (country pre-fill)
- [ ] Stripe billing address country detection on payment method add
- [ ] Automated database backups (PITR enabled)
- [ ] Test-restore procedure documented and executed successfully
- [ ] GDPR data export endpoint — produces JSON bundle of all workspace data
- [ ] `/admin/system-health` dashboard — per-queue depth, per-queue wait time, sync freshness per store, NULL FX counts, backfill progress
- [ ] Secret rotation procedure documentation in repo

### Phase 1.5 tests (batch at end of phase)

Build tests at the end of each step for that step's code. Final phase-end test pass verifies the whole thing works together:

- [ ] Attribution parser full-chain feature test (PYS store)
- [ ] Attribution parser full-chain feature test (WC-native-only store)
- [ ] Attribution parser full-chain feature test (referrer fallback)
- [ ] Channel classifier workspace override precedence
- [ ] COGS reader on each of three WC plugin sources
- [ ] Backfill job completes for a seeded test workspace
- [ ] Store country prompt persists `primary_country_code`
- [ ] Winners/Losers endpoint serves all three classifiers
- [ ] Queue isolation: FB rate limit does not block Google Ads or Store sync
- [ ] Reconciliation hard-deletes disappeared orders
- [ ] Store deletion removes platform webhooks
- [ ] Trial freeze + reactivation backfill
- [ ] UTM parsing coverage calculation
- [ ] Billing tier auto-assignment

### Phase 1.5 sign-off gate

All boxes above checked. PROGRESS.md for Phase 1.5 fully ticked. Phase 1.6 cannot begin until Step 14 cutover is complete and verified.

---

## Phase 1.6 — Pages & UX

**Goal:** Visible product improvements. Every page reads from Phase 1.5 data layer. No schema changes.

### Per-page implementations (PLANNING 12.5)

- [ ] `/campaigns` refinement — classifier dropdown, hero row cleanup
- [ ] `/analytics/products` rewrite — contribution margin, Real profit, scatter view via generalised QuadrantChart, COGS-not-configured empty state
- [ ] `/countries` rewrite — side-by-side integration columns, three-tier country fallback, peer-average classifier, drill-down keeps two-column panel
- [ ] `/seo` refinement — organic revenue hero card, estimated organic revenue columns on queries/pages tables
- [ ] `/analytics/daily` — hero row, weekday-aware peer classifier

### New pages

- [ ] `/acquisition` — flagship page per PLANNING 12.5, channels table + QuadrantChart + line chart, "Other tagged" and "Not Tracked" bottom rows, inline classify sheet
- [ ] `/analytics/discrepancy` — Platform vs Real investigation tool, destination of ROAS "Why this number?" clicks

### Naming convention

- [ ] `CampaignNameParserService` handles all three shapes (country_campaign_target / campaign_target / campaign)
- [ ] Fixed `|` separator
- [ ] Country detection via first-field 2-uppercase-letters check
- [ ] Target matching: product slug → category slug → raw fallback
- [ ] Writes to `campaigns.parsed_convention` on every sync
- [ ] `/manage/naming-convention` read-only explainer page with parse status table and coverage badge

### Channel mappings

- [ ] `/manage/channel-mappings` full CRUD page
- [ ] "Import defaults" button re-seeds global rows
- [ ] Inline classify UI on `/acquisition` (expandable "Other tagged" row with one-click classify)
- [ ] Classify writes workspace-scoped `channel_mappings` row + re-classifies historical orders

### Tag Generator extension

- [ ] Right panel added to `/manage/tag-generator`: campaign/adset/ad name generator
- [ ] Same form drives both URL and name panels
- [ ] Copy buttons on each output
- [ ] Pre-configured templates for FB conversion campaign, Google Ads shopping, etc.

### Order detail page

- [ ] Click any order from orders list or dashboard
- [ ] Shows first-touch, last-touch, click IDs, attribution source badge
- [ ] Reads from `orders.attribution_*` columns

### Frequently-Bought-Together

- [ ] `ComputeProductAffinitiesJob` weekly Sunday
- [ ] Apriori-style query over last 90 days of `order_items`
- [ ] Writes to `product_affinities` with support, confidence, lift, margin_lift
- [ ] Display on product detail pages: "Frequently bought with X"

### Monthly PDF reports

- [ ] `GenerateMonthlyReportJob` + Blade template via `barryvdh/laravel-dompdf`
- [ ] On-demand from Insights page
- [ ] Scheduled 1st of month 08:00 UTC
- [ ] Includes contribution margin when COGS configured

### Dashboard design principles applied

- [ ] `<WhyThisNumber />` on every MetricCard with a defined metric
- [ ] `<DataFreshness />` rendered in every PageHeader
- [ ] Action language on dashboard cards (not metric language)
- [ ] Product images on all product rows

### Phase 1.6 verification

- [ ] Every page in PLANNING 12.5 matches its spec
- [ ] `/acquisition` renders with real parser data
- [ ] `/countries` side-by-side shows ad spend via naming convention + primary_country_code fallback
- [ ] `/analytics/products` shows contribution margin when COGS configured, graceful empty state otherwise
- [ ] Naming convention parser handles all three shapes, product-or-category matching works
- [ ] Tag Generator produces matching URL + campaign names from one form
- [ ] Inline Acquisition classify writes `channel_mappings` and re-classifies historical orders
- [ ] Order detail shows full attribution journey
- [ ] FBT populates `product_affinities` for test store
- [ ] Monthly PDF generates without errors
- [ ] `<WhyThisNumber />` fires on every MetricCard
- [ ] `<DataFreshness />` renders on every page header

### Phase 1.6 tests (batch at end of phase)

- [ ] Feature tests for every new page
- [ ] Naming convention parser unit tests (all three shapes, edge cases)
- [ ] FBT algorithm test with fixture orders
- [ ] PDF generation smoke test
- [ ] Classify UI → channel_mappings → historical re-classification chain

---

## Phase 2 — Shopify

- [ ] `ShopifyConnector` skeleton implementing `StoreConnector`
- [ ] OAuth flow for Shopify app installation
- [ ] Order sync via GraphQL Admin API with field mapping per PLANNING Phase 2 section
- [ ] `ShopifyCustomerJourneySource` registered in `AttributionParserService`
- [ ] `ShopifyLandingPageSource` registered as fallback
- [ ] `supportsHistoricalCogs()` returns false
- [ ] Daily `InventoryItem.unitCost` snapshot job → `daily_snapshot_products.unit_cost`
- [ ] Order sync looks up cost from snapshot → writes `order_items.unit_cost`
- [ ] Pre-snapshot orders badge: "historical estimate"
- [ ] Webhook normalisation layer — platform-specific payloads become uniform internal events
- [ ] Shopify-specific connector test suite
- [ ] Multi-platform feature parity audit (every Phase 1.6 feature works on Shopify)
- [ ] No regressions on WooCommerce

---

## Phase 3 — Intelligence

- [ ] `ComputeMetricBaselinesJob` — historical backfill on first run, then daily
- [ ] `DetectAnomaliesJob` — silent mode default, % threshold, volume floors, skip conditions
- [ ] `correlateSignals()` single-cause investigation chain
- [ ] Composite alerts with prose narratives
- [ ] AI structured anomaly output via `AiSummaryService` second call
- [ ] Alert deduplication (same type + workspace + optional store within 3 days)
- [ ] Coupon auto-promotion detection
- [ ] HTTP interim checkout health check
- [ ] Payment gateway failure detection
- [ ] Refund anomaly detection (distinct from low-order day)
- [ ] `recommendations` table + migration
- [ ] Nightly recommendation jobs (organic-to-paid, GSC product opportunity, site health revenue impact, stock-aware, cohort × channel, basket bundling)
- [ ] Dashboard Recommendations card
- [ ] Named saved segments (extends `view_preferences` with `saved_segments`)
- [ ] CTR opportunities section on `/seo`
- [ ] Theme-campaign entity
- [ ] Stacked area mode on MultiSeriesLineChart
- [ ] Sankey diagram on `/acquisition`
- [ ] Graded 14-day dot strips (replaces binary)
- [ ] `is_silent` default flipped to false after ≥70% TP rate on ≥20 reviewed alerts over ≥4 weeks

---

## Phase 4 — Advanced / Plugins

- [ ] Native uptime monitoring (Hetzner VPS probe scripts, API endpoints, `EvaluateUptimeJob`)
- [ ] CAPI conversion sync to Facebook (uses `pys_fb_cookie.fbc/fbp` from Phase 1.5)
- [ ] Nexstage WooCommerce plugin for stores without PYS
- [ ] Agency white-label (custom domain, logo, colors per workspace)
- [ ] Multi-workspace overview
- [ ] Full Playwright synthetic checkout
- [ ] ML seasonality service (Python FastAPI, STL decomposition)
- [ ] Causal tree visualisation
- [ ] Slack / Discord / Telegram notification webhooks
- [ ] Additional connectors — BigCommerce, Magento, PrestaShop

---

## Future considerations (validate demand first)

- [ ] Abandoned cart recovery (SMS/email win-back, coupon-triggered follow-ups)
- [ ] WooCommerce Subscriptions analytics
- [ ] Investor-ready dashboard export template variant

---

## Phase enforcement rule

Phase N+1 cannot ship to production until Phase N checklist is complete. Parallel dev on feature branches allowed, but merging to main requires prior phase sign-off.

Checklists here are sign-off gates, not aspirations.
