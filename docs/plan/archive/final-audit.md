# Final Pre-Coding Audit

Comprehensive cross-check of all 7 plan docs against each other. Every feature checked against its page, its data tables, its integration source, and its onboarding path.

---

## Issues Found & Resolutions

### Issue 1: F6 Inventory says "separate page" but pages outline has it as tab
**Where:** feature-list.md F6 says "Separate `/inventory` page... because data depth warrants it"
**But:** pages-outline.md has it as Products → Inventory tab (user agreed to tab consolidation)
**Fix:** Update feature-list.md F6 to say "Products → Inventory tab" instead of separate page.
**Status:** FIXED

### Issue 2: F14 Forecasting has no page/tab home
**Where:** feature-list.md includes F14 Forecasting as MVP
**But:** pages-outline.md has NO Forecasting section, tab, or widget anywhere
**Resolution:** Forecasting doesn't need its own page. It manifests as:
- **Dashboard:** "Today-so-far" widget already shows intra-day projection
- **Products/Inventory tab:** Sales velocity forecast + stock-out prediction already planned
- **Profit/P&L:** Goal progress bar (target vs actual pacing)
- No separate Forecasting page needed — the data appears where it's actionable
**Fix:** Add note to feature-list.md F14 clarifying where forecasting surfaces. Not a separate page.
**Status:** FIXED

### Issue 3: F15 Winners/Losers only partially represented
**Where:** feature-list.md F15 describes a full leaderboard with custom date comparison
**But:** pages-outline.md only has a small "Quick-glance panel" on Dashboard (top 3 rising/falling)
**Resolution:** The full leaderboard is already covered by:
- **Products → Performance tab:** sortable by any metric with date comparison (customer request for "best sellers in date range")
- **Marketing → Campaigns tab:** sortable by any metric with deltas
- **Dashboard:** condensed top 3/bottom 3 as a teaser
- A dedicated Winners/Losers tab isn't needed — sorting + delta columns on existing tables IS the feature
**Fix:** Update feature-list.md F15 to note it surfaces via sorting on existing tables, not a separate view.
**Status:** FIXED

### Issue 4: Uptime/TTFB in both MVP (F11) and v2 (V2/V3)
**Where:** feature-list.md F11 Site Health lists "Uptime monitoring" and "TTFB trend line" in MVP core data
**But:** feature-list.md V2 and V3 also list these as v2 features
**Resolution:** Uptime monitoring (simple ping check) is MVP. TTFB trending is v2. Clarify.
**Fix:** Remove V2/V3 from v2 features list (already in F11). Add "(v2)" label to TTFB in F11.
**Status:** FIXED

### Issue 5: Orphaned page content in pages-outline.md
**Where:** Lines 399-448 have "Page removed" markers but then still contain the full old Shipping and Funnel content that duplicates content already in Page 2 and Page 3 tabs.
**Fix:** Remove orphaned sections entirely — the content lives in its parent tab sections.
**Status:** FIXED

### Issue 6: Empty states mention "sample data" — user rejected this
**Where:** pages-outline.md Design System Rules says "Sample data during backfill with visible tag"
**But:** User explicitly said "I don't like that" and "just give a progress screen"
**Fix:** Change to "Progress screen during import, redirect to dashboard when done. No sample/demo data."
**Status:** FIXED

### Issue 7: Dashboard URL references `/ads` instead of `/marketing`
**Where:** pages-outline.md Page 1, section 6: "Click row → `/ads?channel=X`"
**But:** The page is now `/marketing` (renamed during tab consolidation)
**Fix:** Change to `/marketing?channel=X`
**Status:** FIXED

### Issue 8: Attribution journey touchpoints need storage
**Where:** pages-outline.md Page 5 Orders says "Attribution journey timeline: scrollable touchpoints (campaign → pageviews → cart → purchase) with time deltas"
**But:** database-schema.md has NO table for per-order touchpoints. We store UTM data on orders but not a sequence of events.
**Source:** Shopify's `customerJourneySummary.moments` provides this data — a timeline of touchpoints per order.
**Fix:** Add `touchpoints` JSONB column on orders table to store the moments array from Shopify's customerJourneySummary. For WooCommerce, we only have the final UTM — store as a single-entry array. Don't create a separate table — JSONB on the order is sufficient for display.
**Status:** FIXED

### Issue 9: Onboarding checklist state not tracked in schema
**Where:** onboarding.md describes a persistent checklist (e.g., "3 of 7 complete")
**But:** database-schema.md has no column/table to track checklist completion state
**Resolution:** Minor. Add a `onboarding_checklist` JSONB column to workspaces table.
**Status:** FIXED

---

## Verification Matrix: Every Feature → Page → Table

| Feature | Page/Tab | Primary Tables | ✓ |
|---------|----------|---------------|---|
| F1 Dashboard | Page 1: Home | daily_snapshots, annotations | ✓ |
| F2 P&L | Page 2: Profit → P&L | daily_snapshots, operational_costs | ✓ |
| F3 Ads | Page 3: Marketing → Campaigns | ad_campaigns, ad_sets, ads, ad_insights | ✓ |
| F4 Creatives | Page 3: Marketing → Creatives | ads (creative fields), ad_insights, email_campaigns | ✓ |
| F5 Products | Page 4: Products → Performance | products, product_variants, order_line_items, cogs_entries | ✓ |
| F6 Inventory | Page 4: Products → Inventory | product_variants.inventory_quantity, order_line_items (velocity) | ✓ |
| F7 Orders | Page 5: Orders | orders, order_line_items, refunds | ✓ |
| F8 LTV/Cohorts | Page 6: Customers → LTV | customers, orders (cohort computation) | ✓ |
| F9 RFM | Page 6: Customers → Segments | customers.rfm_*, customer_segments | ✓ |
| F10 SEO | Page 7: SEO | gsc_daily, orders (revenue join) | ✓ |
| F11 Site Health | Page 8: Site Health → Speed + Uptime | page_speeds, uptime_checks | ✓ |
| F12 Shipping | Page 2: Profit → Shipping | orders (by shipping_country), shipping_rules | ✓ |
| F13 Funnel | Page 3: Marketing → Funnel | ga4_daily (item_views, add_to_carts, checkouts_started, purchases) | ✓ |
| F14 Forecasting | Dashboard + Products/Inventory + Profit | daily_snapshots (trend), order_line_items (velocity) | ✓ |
| F15 Winners/Losers | Dashboard panel + sorting on Products/Marketing | daily_snapshots (deltas), products, ad_insights | ✓ |
| F16 Alerts | Page 9: Alerts | alerts, alert_rules | ✓ |
| F17 Settings | Page 11: Settings | stores, ad_accounts, analytics_properties, search_properties, etc. | ✓ |
| F18 Export | Cross-cutting | All tables (read) | ✓ |
| T1 Holidays | Page 10: Tools → Holidays | holidays, workspace_holidays | ✓ |
| T2 UTM | Page 10: Tools → UTM | (no storage — generates URLs client-side) | ✓ |
| T3 Calculator | Page 10: Tools → Calculator | (no storage — computes client-side) | ✓ |
| T4 Naming | Page 10: Tools → Naming | workspaces.naming_*, ad_campaigns.parsed_dimensions | ✓ |

## Integration → Schema Field Coverage

| Integration | All API fields mapped to schema? | Gaps |
|-------------|--------------------------------|------|
| Shopify orders | ✓ | touchpoints now stored as JSONB |
| Shopify products/inventory | ✓ | |
| Shopify customers | ✓ | |
| Shopify fees (Payouts API) | ✓ | |
| WooCommerce orders | ✓ | |
| WooCommerce COGS (native) | ✓ | |
| WooCommerce customers (guest dedup) | ✓ | |
| Meta Ads insights | ✓ | |
| Google Ads (SearchStream) | ✓ | |
| TikTok Ads | ✓ (uses same ad_insights table) | |
| GA4 Data API | ✓ | funnel columns added |
| GSC API | ✓ | |
| Klaviyo campaigns/flows | ✓ | |
| PageSpeed/CrUX | ✓ | |

## Cross-Doc Consistency

| Check | Status |
|-------|--------|
| Feature list ↔ Pages outline: every feature has a page home | ✓ (after fixes) |
| Pages outline ↔ Schema: every page has its data tables | ✓ (after fixes) |
| Integrations ↔ Schema: every API field has a column | ✓ (after fixes) |
| Workspace architecture ↔ Schema: settings hierarchy matches | ✓ |
| Onboarding ↔ Integrations: OAuth flows documented | ✓ |
| Tech stack ↔ Schema: Postgres features used correctly | ✓ |
| Feature list ↔ Onboarding: no features require onboarding steps not documented | ✓ |
| No contradictions between any docs | ✓ (after fixes) |
