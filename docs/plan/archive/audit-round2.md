# Audit Round 2 — Deep Data Flow Analysis

Going beyond surface-level checks. For each claimed feature, asking: "Can we actually compute this from the data we store?"

---

## Issues Found

### Issue 10: CM1/CM2/CM3 definition inconsistent between feature-list and pages-outline

**feature-list.md F2** says: `CM1 → marketing spend → CM2 → custom OPEX → Net Profit`
**pages-outline.md Page 2** says: `Gross Profit (CM1) → Fulfillment → Gateway Fees → Channel Fees → CM2 → Marketing Spend → CM3 → Custom OPEX → Taxes → Net Profit`

These are different! Pages outline is correct (matches Bloom/Klar industry standard: CM1 = gross profit, CM2 = after fulfillment+fees, CM3 = after ad spend). Feature list has a simplified/wrong version.

**Fix:** Align feature-list.md F2 to match the pages-outline P&L row structure.

### Issue 11: P&L "Net Profit" from snapshots can't include operational costs

**daily_snapshots** stores revenue, COGS, shipping, fees, ad spend — but NOT operational costs (rent, salaries, SaaS). Operational costs are in the `operational_costs` table as recurring/one-time entries.

P&L shows: CM3 → Custom OPEX → Net Profit. But snapshots don't have an `operational_costs_total` column.

**Fix:** Either add `operational_costs_total` to daily_snapshots (computed from operational_costs table during snapshot build), OR compute CM3→Net Profit by joining snapshots + operational_costs at query time. The join is simpler since operational costs are few rows. Document this.

### Issue 12: COD data — where does it come from?

Shipping & Countries page shows "COD %" per country. But neither Shopify nor WooCommerce APIs natively expose whether an order was Cash on Delivery.

**Shopify:** `payment_gateway_names` array on the order — if it includes `cash_on_delivery` or a COD-specific gateway.
**WooCommerce:** `payment_method` field — value `cod` for the built-in COD gateway.

So COD IS detectable from the `payment_gateway` column on orders. We just need to map gateway names to a `is_cod` boolean during sync.

**Fix:** Add `is_cod boolean default false` to orders table. Set during sync based on gateway name matching.

### Issue 13: CVR in Shipping & Countries — where does per-country CVR come from?

Page shows AOV + CVR per country. AOV = revenue / orders per country (from orders table, fine). But CVR = orders / sessions. Sessions per country would need GA4 data sliced by country. Our `ga4_daily` table HAS a `country` column, so we CAN compute sessions per country. But we'd need to join `orders` (by shipping_country) with `ga4_daily` (by country) which is a cross-source join — the country codes might not match perfectly (GA4 uses full names vs ISO codes).

**Fix:** Ensure GA4 country dimension is stored as ISO 2-letter code (convert during sync). Document this join path.

### Issue 14: Product schema / rich results audit — no data source

F10 SEO mentions "Product schema / rich results eligibility audit" but we have NO mechanism to crawl product pages and check their schema markup. This would require a web crawler, which is out of scope for MVP.

**Fix:** Remove from MVP. This is a v2 feature requiring crawl infrastructure.

### Issue 15: Return reasons — where does structured data come from?

F7 Orders lists "return reason (if returned)" and the refunds table has a `reason` column. But:
- **Shopify refunds API:** Has no structured reason field. Just a free-text `note` on the refund.
- **WooCommerce refunds API:** Has a `reason` string field — also free text.

Neither provides structured categories (sizing, damaged, not_as_described). We'd need to either: parse free text, ask users to categorize manually, or accept free-text.

**Fix:** Change refunds.reason to accept free text from the platform, not structured categories. Structured categorization is v2 (manual tagging UI or NLP).

### Issue 16: "Source badges" — not all 6 sources apply to all metrics

Feature list says "6 source badges on every metric" but this doesn't make sense for many metrics:
- Orders count: only Store source is relevant (orders come from the store)
- Sessions: only GA4 source is relevant
- Organic clicks: only GSC source is relevant
- LTV: only computed from Store data

Source badges make sense for: Revenue (store-reported vs ad-platform-claimed), ROAS (different per attribution source), Conversions (platform-reported vs store-counted).

**Fix:** Clarify: source badges appear on metrics where multiple sources disagree (revenue, conversions, ROAS), not literally on every metric.

### Issue 17: "Real" source — algorithm undefined

We keep referencing the "Real" reconciled value (gold badge) but nowhere do we define HOW it's computed. Is it: the store value? A weighted average? The lowest? The highest?

**Fix:** Define "Real" = Store-side value (Shopify/WooCommerce order total) as the ground truth for revenue. For conversions, "Real" = store order count. Document that "Real" is not a computed blend — it's the store as the single source of truth. The other source badges show what each platform claims; "Real" shows what actually happened in the store.

### Issue 18: Inventory inline notes — no column in schema

F6 says "Inline notes field per product/SKU" and pages outline says "Inline notes on products." But neither `products` nor `product_variants` tables have a `notes` column.

**Fix:** Add `notes text nullable` to both `products` and `product_variants` tables.

### Issue 19: daily_snapshots missing `total_customers` for new vs returning split verification

Snapshots have `new_customers` and `returning_customers` but not a total. The sum should equal `orders_count` (assuming one customer per order) but doesn't account for multi-item orders from the same customer on the same day.

Actually: `new_customers + returning_customers` should equal unique customers who ordered that day, not orders_count. An order is new/returning based on whether the customer had prior orders.

**Fix:** This is fine as-is. `new_customers` = count of distinct customers whose first_order_at = this date. `returning_customers` = distinct customers who ordered this day AND first_order_at < this date. No schema change needed, just document the computation.

### Issue 20: FX rates source — not documented

`fx_rates` table exists but we never documented WHERE to get exchange rates from. Need an API source.

**Fix:** Document: use European Central Bank (ECB) rates (free, daily) or Open Exchange Rates API ($12/mo, hourly). Sync daily. Add to integrations.md.

### Issue 21: Payment method for funnel page — where does it come from?

Funnel tab shows "Payment method conversion analysis (which payment methods convert best)." This requires knowing which payment method each order used. We store `payment_gateway` on orders (e.g., "shopify_payments", "paypal", "stripe"). This works — we can group orders by payment_gateway and compute conversion rates.

But CVR by payment method needs sessions-to-order, and sessions don't have a payment method (payment happens at checkout). So "conversion rate by payment method" is really "share of orders by payment method" + "abandonment rate per payment method" (if we had checkout-start data). GA4's `checkouts_started` gives us total checkouts but not per payment method.

**Fix:** Clarify: this is "order distribution by payment method" not "conversion rate by payment method." Show as a bar chart of order share per gateway. True per-method CVR would require checkout-level payment selection data which we don't have.

---

## Summary of Round 2 Fixes

| # | Issue | Fix | Type |
|---|-------|-----|------|
| 10 | CM1/CM2/CM3 inconsistent | Align feature-list to pages-outline definition | Doc fix |
| 11 | Operational costs not in snapshots | Compute by joining snapshots + operational_costs at query time | Document |
| 12 | COD detection | Add `is_cod` boolean to orders, set from payment_gateway | Schema add |
| 13 | Per-country CVR join | Ensure GA4 country stored as ISO code | Sync logic note |
| 14 | Product schema audit impossible | Move to v2 (needs crawler) | Scope change |
| 15 | Return reasons unstructured | Accept free text, structured categorization v2 | Clarification |
| 16 | Source badges not on every metric | Clarify: only on metrics with multi-source disagreement | Doc fix |
| 17 | "Real" source undefined | Define: Real = store-side value (ground truth) | Doc fix |
| 18 | Inventory notes no column | Add `notes` to products and product_variants | Schema add |
| 19 | Snapshot customer counts | Fine as-is, document computation | No change |
| 20 | FX rates source missing | Add ECB/Open Exchange Rates to integrations | Doc fix |
| 21 | Payment method CVR impossible | Clarify: order distribution by gateway, not CVR | Doc fix |
