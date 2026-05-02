# Database Schema Review

Comprehensive cross-check of database-schema.md against feature-list.md, integrations.md, pages-outline.md, and workspace-architecture.md. Issues found and fixes applied.

---

## Issues Found & Fixes

### 1. MISSING: Funnel event data in ga4_daily

**Problem:** The Funnel page (F13) needs step-by-step conversion data: landing → product view → add to cart → checkout → purchase. Our `ga4_daily` table only stores `sessions` and `purchases`, missing the intermediate steps.

**Fix:** Add columns to `ga4_daily`:
- `add_to_carts` (int) — GA4 `addToCarts` metric
- `checkouts_started` (int) — GA4 `checkouts` metric  
- `item_views` (int) — GA4 `itemViews` metric
- `cart_to_view_rate` removed (compute at query time)

These are available from the same GA4 Data API `runReport` call — just add them to the metrics list.

### 2. MISSING: Inventory velocity computation path

**Problem:** F6 Inventory needs sales velocity (units/day) and days-of-stock-remaining. We store current `inventory_quantity` on `product_variants` but have no history.

**Resolution:** No new table needed. Velocity = units sold per day, computed from `order_line_items` grouped by variant_id over the last 28 days. Current stock / velocity = days remaining. This is a query, not stored data.

### 3. MISSING: Order-to-campaign attribution link

**Problem:** We have UTM params on orders and campaign names in ad_campaigns, but no explicit join. How does the Ads page show "orders attributed to this campaign"?

**Resolution:** Channel mapping resolves `utm_source + utm_medium → channel`. For campaign-level: match `orders.utm_campaign` to `ad_campaigns.name` or `parsed_dimensions`. This is a soft-match at query time. Adding explicit columns:
- `orders.matched_campaign_id` (bigint FK, nullable) — populated by a background job that matches UTM → campaign after sync

### 4. MISSING: Revenue in workspace currency on orders

**Problem:** Orders store amounts in `currency` (original). Dashboard needs everything in workspace `reporting_currency`. Currently only `ad_insights` has `spend_workspace_currency`.

**Fix:** Add to `orders`:
- `total_price_workspace_currency` (decimal 12,2)
- `net_revenue_workspace_currency` (decimal 12,2)

Computed during sync using `fx_rates` table at transaction-day rate.

### 5. MISSING: `updated_at` on several tables

**Problem:** `order_line_items`, `refunds`, `product_variants`, `ad_sets`, `ads`, `ad_insights` lack `updated_at`. Needed for change detection.

**Fix:** Add `updated_at timestamp` to all of these.

### 6. CONCERN: orders.contribution_margin is a stored computed value

**Problem:** `contribution_margin` on orders depends on COGS which can change. We said "never store ratios" but contribution margin is an absolute value, not a ratio.

**Resolution:** Keep it. It's useful for speed (sort by profitability). Rebuild when COGS changes via the "Recalculate past orders" job. Document that this column is derived and may need rebuilding.

### 7. MISSING: `daily_snapshots` needs tax columns

**Problem:** P&L (F2) needs taxes collected as a separate line item. Customer specifically requested "Taxes collected" as a data point.

**Fix:** Add to `daily_snapshots`:
- `taxes_collected` (decimal 14,2) — sum of order taxes

### 8. MISSING: `daily_snapshots` needs refund cost columns

**Problem:** P&L separates refund amounts from return shipping/restocking costs. Snapshots only have `total_refunds` but not the cost of processing returns.

**Fix:** Add to `daily_snapshots`:
- `return_shipping_costs` (decimal 14,2) — from refunds.return_shipping_cost
- `restocking_fees_collected` (decimal 14,2) — from refunds.restocking_fee

### 9. MISSING: `daily_snapshots` needs revenue in workspace currency

**Problem:** Snapshots store revenue/costs but with multi-store + multi-currency, we need to clarify: are snapshot amounts in workspace currency or original?

**Resolution:** All snapshot amounts are in **workspace reporting currency**. Document this explicitly. The snapshot builder converts during aggregation.

### 10. CONCERN: ga4_daily unique constraint cardinality

**Problem:** The unique constraint `(workspace_id, analytics_property_id, date, source, medium, landing_page, country, device_category)` has very high cardinality. A store with 500 landing pages × 10 sources × 5 countries × 3 devices = 75,000 rows PER DAY.

**Resolution:** This is fine for PostgreSQL with proper indexing. But consider: do we need all combinations? For MVP, we might want two levels:
- `ga4_daily_summary` — aggregated by date only (for dashboard KPIs)
- `ga4_daily` — full dimension breakdown (for drill-downs)

For now, keep single table but add `ga4_daily_summary` fields to `daily_snapshots` (already done — sessions, etc.).

### 11. MISSING: `customers.total_contribution_margin`

**Problem:** Customer page needs "customer-level contribution margin" (F8). We have `total_spent` and `total_refunded` but not margin.

**Fix:** Add to `customers`:
- `total_contribution_margin` (decimal 12,2) — denormalized, updated on order sync

### 12. MISSING: `ad_insights` level indicator

**Problem:** `ad_insights` stores campaign-level, adset-level, and ad-level rows but there's no explicit indicator of which level a row represents. The nullable `ad_id`/`ad_set_id` implies it, but a level column is clearer.

**Fix:** Add to `ad_insights`:
- `level` (varchar 10) — campaign, adset, ad

### 13. MISSING: Historical data limits documentation

**Problem:** Not in schema, but should be documented for the sync logic.

**Fix:** Document in integrations.md (done separately).

### 14. MISSING: `stores.api_version`

**Problem:** Shopify API requires pinning to a version (`2026-04`). WooCommerce uses v3. We should track which version each store connection uses.

**Fix:** Add to `stores`:
- `api_version` (varchar 20) — e.g., "2026-04" for Shopify, "v3" for WooCommerce

### 15. MISSING: `ad_accounts.refresh_token`

**Problem:** Meta and Google Ads OAuth needs refresh tokens for long-lived access. We have `access_token` but not `refresh_token`.

**Fix:** Add to `ad_accounts`:
- `refresh_token` (text, encrypted, nullable)
- `token_expires_at` (timestamp, nullable) — for proactive refresh

### 16. MISSING: Discount code analysis table

**Problem:** F7 Orders wants "discount code performance analysis." We store discount codes as JSONB on orders, which works for querying but a dedicated summary could be useful.

**Resolution:** No separate table needed for MVP. Query directly from `orders.discount_codes` JSONB with PostgreSQL JSON functions: `jsonb_array_elements(discount_codes)->>'code'`. Aggregate revenue, orders, margin per code. If performance becomes an issue, add a materialized view.

### 17. SCHEMA OK: All ad platforms covered

Verified: `ad_accounts.platform` supports meta, google, tiktok, pinterest, snapchat. `ad_insights` is platform-agnostic. ✓

### 18. SCHEMA OK: Multi-store support

Verified: `stores` supports 1..N per workspace. Orders, products, customers all have `store_id` FK. `daily_snapshots` has `store_id` nullable (null = aggregated). ✓

### 19. SCHEMA OK: COGS with date history

Verified: `cogs_entries` has `effective_from`, `effective_to`, source tracking. `order_line_items.unit_cogs` snapshots cost at order time. `cogs_source` column tracks provenance. ✓

### 20. SCHEMA OK: Multi-currency

Verified: `fx_rates` table with `(base_currency, target_currency, date)` PK. Orders store original currency. Workspace has `reporting_currency`. ✓

---

## Historical Data Limits (for sync logic)

| Platform | History Available | Sync Strategy |
|----------|-----------------|---------------|
| **Shopify** | Unlimited (all orders since store creation) | Bulk import everything on connect |
| **WooCommerce** | Unlimited (data in customer's own DB) | Paginate all history on connect |
| **Google Ads** | Unlimited (life of account) | Import all on connect |
| **Meta Ads** | **37 months** (daily breakdowns) | Import 37mo on connect; store locally to build beyond |
| **TikTok Ads** | Unlimited (no documented cap) | Import all on connect |
| **Klaviyo** | Unlimited (all events since creation) | Import all on connect |
| **GA4** | **~14 months** (standard; configurable 2 or 14mo) | Import max window on connect; **store locally to build beyond** |
| **GSC** | **16 months** rolling | Import 16mo on connect; **store locally to preserve beyond window** |
| **Pinterest Ads** | ~24 months | Import 24mo on connect |
| **Snapchat Ads** | ~24 months | Import 24mo on connect |

**Critical:** For GA4 and GSC, data disappears from their APIs over time. We must sync regularly and store locally to preserve history beyond their windows. This is the most important data engineering decision — once GSC data is >16 months old, it's gone forever if we didn't already pull it.

---

## Summary of Schema Changes Needed

| # | Table | Change | Type |
|---|-------|--------|------|
| 1 | `ga4_daily` | Add `add_to_carts`, `checkouts_started`, `item_views` | New columns |
| 2 | `orders` | Add `matched_campaign_id`, `total_price_workspace_currency`, `net_revenue_workspace_currency` | New columns |
| 3 | Multiple | Add `updated_at` to: `order_line_items`, `refunds`, `product_variants`, `ad_sets`, `ads`, `ad_insights` | New columns |
| 4 | `daily_snapshots` | Add `taxes_collected`, `return_shipping_costs`, `restocking_fees_collected` | New columns |
| 5 | `customers` | Add `total_contribution_margin` | New column |
| 6 | `ad_insights` | Add `level` (campaign/adset/ad) | New column |
| 7 | `stores` | Add `api_version` | New column |
| 8 | `ad_accounts` | Add `refresh_token`, `token_expires_at` | New columns |
| 9 | All snapshot amounts | Document: all in workspace reporting currency | Documentation |
