# Coding Specification

Every computation, default, edge case, route, component, and scheduled job a coding agent needs. Greenfield project — no application code exists. Build everything from scratch.

---

## 1. P&L Formulas

```
Gross Sales           = SUM(orders.total_price)
- Discounts           = SUM(orders.total_discounts)
- Returns/Refunds     = SUM(refunds.amount)
= Net Sales           = Gross Sales - Discounts - Refunds
  (Memo: Return Shipping Cost = SUM(refunds.return_shipping_cost))
  (Memo: Restocking Fees = SUM(refunds.restocking_fee) — already embedded in reduced refund amount)
- COGS                = SUM(order_line_items.unit_cogs * quantity)
= Gross Profit (CM1)  = Net Sales - COGS
- Fulfillment         = SUM(orders.actual_shipping_cost) + SUM(orders.handling_cost) + SUM(refunds.return_shipping_cost)
- Transaction Fees    = SUM(orders.transaction_fee)
- Channel/Platform Fees = [from platform_fee_rules, prorated daily]
= CM2                 = CM1 - Fulfillment - Transaction Fees - Channel Fees
- Ad Spend Meta       = SUM(ad_insights.spend_workspace_currency WHERE platform='meta')
- Ad Spend Google     = SUM(ad_insights.spend_workspace_currency WHERE platform='google')
- Ad Spend TikTok     = SUM(ad_insights.spend_workspace_currency WHERE platform='tiktok')
- Ad Spend Other      = SUM(ad_insights.spend_workspace_currency WHERE platform NOT IN above)
= CM3                 = CM2 - Total Ad Spend
- Operational Costs   = [from operational_costs table, prorated by frequency]
= Net Profit          = CM3 - Operational Costs

Taxes Collected = SUM(orders.total_tax) — LIABILITY, not revenue. Memo line below Net Profit only.
Revenue ex. VAT = SUM(orders.total_price) - SUM(orders.total_tax)
```

### KPI Formulas (always computed at query time, NEVER stored)

```
AOV        = net_revenue / NULLIF(orders_count, 0)
MER        = net_revenue / NULLIF(ad_spend_total, 0)       [blended across all channels — dashboard-level metric]
ROAS       = revenue / NULLIF(spend, 0)                    [per-campaign or per-platform — marketing-level metric]
CAC        = ad_spend_total / NULLIF(new_customers, 0)
CVR        = orders_count / NULLIF(sessions, 0)
ncROAS     = new_customer_revenue / NULLIF(ad_spend_total, 0)
Net Margin = (net_revenue - all_costs) / NULLIF(net_revenue, 0)
Gross ROAS = revenue / NULLIF(ad_spend_total, 0)
Net ROAS   = net_profit / NULLIF(ad_spend_total, 0)
POAS       = net_profit / NULLIF(ad_spend_total, 0)    [same formula as Net ROAS — single computation, UI label varies by context]
```

Display `—` (em dash) when denominator is zero, not 0 or infinity.

### Operational Costs Proration

Operational costs are NOT in daily_snapshots. Join at query time. Amounts are converted from `oc.currency` to workspace reporting currency via FX rate:
```sql
WITH months AS (
  SELECT generate_series(
    DATE_TRUNC('month', :range_start::date),
    DATE_TRUNC('month', :range_end::date),
    '1 month'::interval
  )::date AS month_start
),
prorated AS (
  SELECT oc.id, m.month_start,
    CASE oc.frequency
      WHEN 'monthly' THEN oc.amount * (
        LEAST(:range_end::date, (m.month_start + '1 month'::interval - '1 day'::interval)::date)
        - GREATEST(:range_start::date, m.month_start) + 1
      )::numeric / ((m.month_start + '1 month'::interval)::date - m.month_start)
      WHEN 'weekly'  THEN oc.amount * (
        LEAST(:range_end::date, (m.month_start + '1 month'::interval - '1 day'::interval)::date)
        - GREATEST(:range_start::date, m.month_start) + 1
      )::numeric / 7.0  -- linear proration, not calendar-week aligned
      WHEN 'daily'   THEN oc.amount * (
        LEAST(:range_end::date, (m.month_start + '1 month'::interval - '1 day'::interval)::date)
        - GREATEST(:range_start::date, m.month_start) + 1
      )
      WHEN 'one_time' THEN CASE WHEN oc.starts_at BETWEEN :range_start AND :range_end THEN oc.amount ELSE 0 END
    END
    * COALESCE(fx.rate, 1) AS prorated_amount  -- convert to workspace reporting currency
  FROM operational_costs oc
  CROSS JOIN months m
  LEFT JOIN LATERAL (
    SELECT rate FROM fx_rates
    WHERE base_currency = oc.currency AND target_currency = :reporting_currency
      AND date <= m.month_start ORDER BY date DESC LIMIT 1
  ) fx ON oc.currency != :reporting_currency
  WHERE oc.workspace_id = :workspace_id
    AND oc.starts_at <= :range_end
    AND (oc.ends_at IS NULL OR oc.ends_at >= :range_start)
)
SELECT SUM(prorated_amount) AS total_opex FROM prorated
```

---

## 2. Dashboard KPI Cards

8 cards (Triple Whale + Shopify defaults):

| Card | Formula | Source |
|------|---------|--------|
| Revenue | `SUM(daily_snapshots.net_revenue)` | Snapshots |
| Net Profit | Section 1 full formula: `net_revenue - cogs_total - shipping_cost - payment_fees - handling_costs - return_shipping_costs - ad_spend_total - platform_fees - opex`. Snapshot provides the first 7 terms; OPEX (section 1 proration CTE) and platform fees (section 41) are joined at query time. Restocking fees NOT deducted — refund amounts are already net of restocking fees. | Snapshots + opex/platform fee join |
| Ad Spend | `SUM(daily_snapshots.ad_spend_total)` | Snapshots |
| MER | `net_revenue / NULLIF(ad_spend_total, 0)` | Computed |
| Orders | `SUM(daily_snapshots.orders_count)` | Snapshots |
| AOV | `net_revenue / NULLIF(orders_count, 0)` | Computed |
| Sessions | `SUM(daily_snapshots.sessions)` | Snapshots (from GA4) |
| CVR | `orders_count / NULLIF(sessions, 0)` | Computed |

Return/refund rate: `refund_count / NULLIF(orders_count, 0)`. Secondary stat under Orders card.

---

## 3. Dashboard Defaults

| Setting | Default |
|---------|---------|
| Date range | Last 30 days (today-29d through today, inclusive) |
| Comparison | Previous 30 days: `[start-30d, end-30d]` — always equal-length to current period |
| Sparkline | Daily granularity, points match date range |
| KPI delta | `((current - previous) / NULLIF(previous, 0)) * 100` |
| Channel breakdown | Group by `orders.channel` |
| Top products | 5 |
| Winners/Losers | 3 rising + 3 falling by revenue % change |
| Today-so-far | Queries orders directly. Shows actuals + yesterday-at-this-hour comparison (no linear projection — matches industry standard). |
| Alert strip | Unread where `acknowledged_at IS NULL`, max 3, by severity then created_at |
| Date presets | Today, Yesterday, 7d (today-6d..today), **30d (default)**, 90d, 365d, Lifetime, MTD (1st of month..today), QTD, YTD, Last month, Last quarter, BFCM (Thanksgiving Thu..Cyber Mon, from holidays table — see section 36), Custom |
| RFM minimum | 100 for 5-quintile. 20-100: simplified 3-tier. <20: "Not enough data." |
| COGS warning | Default 20% (configurable). Alert when >X% revenue from uncosted products. |
| Creative fatigue | See section 10. Thresholds from `global_settings` (section 36). |

---

## 4. Source Detail

KPI cards show ONE number (best source auto-picked). Source breakdown on hover/click only. No always-visible badges.

**Available on drill-in:**
- **Revenue**: Store (orders.total_price), Facebook (ad_insights.purchase_value WHERE platform=meta), Google (same), GA4 (ga4_daily.purchase_revenue)
- **Conversions**: Store (order count), Facebook (ad_insights.purchases), Google (same)
- **"Real"** = Store-side value (ground truth). Neutral zinc, not gold.

Single-source metrics (no comparison): AOV, sessions, organic clicks, LTV, stock levels.

GA4 purchase_revenue feeds dashboard-level source comparison only. NOT per-campaign ROAS (no join path).

---

## 5. Attribution Models (MVP: 3 models)

| Model | Algorithm | Data |
|-------|-----------|------|
| **Last Click** (default) | Full credit to last touchpoint | `orders.utm_source/medium/campaign` |
| **First Click** | Full credit to first touchpoint | `orders.touchpoints[0]` |
| **Linear** | Equal credit split | `order_revenue / COUNT(touchpoints)` per touchpoint |

All use `orders.touchpoints` JSONB. Position-Based and Time Decay: v2.

**UI:** Controller re-queries orders with selected model's credit distribution. For Last Click: group by last touchpoint. First Click: group by first. Linear: explode and distribute equally.

**WooCommerce note:** `touchpoints` typically has 1 entry (final UTM). All models produce identical results. Show note: "Attribution models produce the same results when only one touchpoint is tracked."

---

## 6. Cohort Heatmap

```sql
WITH cohort_raw AS (
  SELECT
    DATE_TRUNC('month', c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date AS cohort_month,
    ((EXTRACT(YEAR FROM o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz) - EXTRACT(YEAR FROM c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)) * 12
      + EXTRACT(MONTH FROM o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz) - EXTRACT(MONTH FROM c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz))::int AS months_since,
    COUNT(DISTINCT c.id) AS customers_active,
    SUM(COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue,
    COUNT(o.id) AS orders_count
  FROM customers c
  JOIN orders o ON o.customer_id = c.id AND o.workspace_id = c.workspace_id
  WHERE c.workspace_id = :workspace_id
    AND o.financial_status NOT IN ('refunded', 'voided', 'cancelled')
    AND o.created_at >= c.first_order_at
  GROUP BY 1, 2
)
SELECT *,
  SUM(customers_active) FILTER (WHERE months_since = 0) OVER (PARTITION BY cohort_month) AS cohort_size
FROM cohort_raw
ORDER BY cohort_month, months_since
```

**Cell values by metric picker:**
- Revenue per customer: `revenue / cohort_size`
- Retention %: `customers_active / cohort_size * 100`
- Repurchase rate: `orders_count / customers_active`

Filtered cohort queries run live against orders (not pre-computed `daily_snapshot_cohorts`). Cache 30s.
```sql
-- Filtered cohort heatmap: by channel, first product, discount, or country
SELECT
  DATE_TRUNC('month', c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date AS cohort_period,
  ((EXTRACT(YEAR FROM o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz) - EXTRACT(YEAR FROM c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)) * 12
    + EXTRACT(MONTH FROM o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz) - EXTRACT(MONTH FROM c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz))::int AS period_offset,
  COUNT(DISTINCT c.id) AS customers_active,
  SUM(COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue,
  COUNT(o.id) AS orders_count
FROM customers c
JOIN orders o ON o.customer_id = c.id AND o.workspace_id = c.workspace_id
-- First-order join for first-product filter
LEFT JOIN order_line_items first_oli ON first_oli.order_id = (
  SELECT id FROM orders WHERE customer_id = c.id AND workspace_id = c.workspace_id
    AND financial_status NOT IN ('refunded','voided','cancelled')
  ORDER BY created_at LIMIT 1
)
WHERE c.workspace_id = :workspace_id
  AND o.financial_status NOT IN ('refunded','voided','cancelled')
  AND o.created_at >= c.first_order_at
  -- Channel filter (optional):
  AND (:channel IS NULL OR o.channel = :channel)
  -- Country filter (optional):
  AND (:country IS NULL OR o.shipping_country = :country)
  -- Discount filter (optional):
  AND (:has_discount IS NULL OR (jsonb_array_length(COALESCE(o.discount_codes, '[]'::jsonb)) > 0) = :has_discount)
  -- First-product filter (optional):
  AND (:first_product_id IS NULL OR first_oli.product_id = :first_product_id)
GROUP BY 1, 2
ORDER BY 1, 2
```
When no filters applied, use pre-computed `daily_snapshot_cohorts` (section 34) for instant loads.

**Curves view:** Same query, post-processed client-side. Each cohort_month becomes a line in a line chart. X-axis = months_since, Y-axis = retention % (customers_active / cohort_size * 100). Max 12 cohort lines shown.

**Pacing view:** Per-cohort cumulative revenue vs CAC. X-axis = months_since, Y-axis = cumulative revenue per customer. Horizontal line at CAC value. Payback = first month where line crosses CAC. Data from same cohort query — client-side cumulative sum of `revenue / cohort_size`.

**CAC payback:** `CAC = SUM(snapshots.ad_spend_total WHERE date IN cohort_month) / SUM(snapshots.new_customers)`. Payback month = first month where cumulative revenue >= CAC. When filtered by channel: use channel-specific spend/customers.

### Customer Page KPIs
```sql
-- Avg LTV
SELECT AVG(total_spent) AS avg_ltv FROM customers 
WHERE workspace_id = :workspace_id AND orders_count > 0

-- Repeat Rate
SELECT COUNT(*) FILTER (WHERE orders_count > 1)::numeric / NULLIF(COUNT(*), 0) AS repeat_rate
FROM customers WHERE workspace_id = :workspace_id AND orders_count > 0

-- LTV:CAC
-- avg_ltv (from above) / (SUM(ds.ad_spend_total) / NULLIF(SUM(ds.new_customers), 0))
-- from daily_snapshots ds WHERE workspace_id AND date BETWEEN :start AND :end

-- Payback Period: from cohort data — first months_since where cumulative revenue/cohort_size >= CAC
```

---

## 7. RFM Scoring

Conditional scoring based on customer count (thresholds from `global_settings`: `rfm.minimum_customers` = 100, `rfm.simplified_tier_min` = 20):
- **≥ 100 customers**: 5-quintile NTILE(5) → 5×5 grid
- **20–99 customers**: simplified 3-tier NTILE(3) → 3×3 grid
- **< 20 customers**: show "Not enough data" message, skip computation

Compute recency from `customers.last_order_at` (stored column, updated by sync):
- R: `(N+1) - NTILE(N) OVER (ORDER BY EXTRACT(EPOCH FROM NOW() - last_order_at) / 86400 ASC)` — lower days = better = highest score
- F: `NTILE(N) OVER (ORDER BY orders_count ASC)`
- M: `NTILE(N) OVER (ORDER BY total_spent ASC)`

Where N = 5 (≥ 100 customers) or N = 3 (20–99 customers).

| Segment | R | F | M |
|---------|---|---|---|
| Champions | 4-5 | 4-5 | 4-5 |
| Loyal | 3-5 | 3-5 | 3-5 (not champion) |
| Potential Loyalists | 4-5 | 1-2 | any |
| At Risk | 1-2 | 3-5 | 3-5 |
| Needs Attention | 2-3 | 2-3 | 2-3 |
| About to Sleep | 2-3 | 1-2 | 1-2 |
| Hibernating | 1 | 1-2 | 1-2 |

Recalculation: nightly at 02:15 UTC. Same job computes purchase gaps as a single set-based query (NOT per-customer — that's an N+1 that timeouts at 100K customers):
```sql
WITH gaps AS (
  SELECT customer_id,
    EXTRACT(EPOCH FROM created_at - LAG(created_at) OVER (
      PARTITION BY customer_id ORDER BY created_at
    )) / 86400 AS days_gap
  FROM orders
  WHERE workspace_id = :workspace_id
    AND financial_status NOT IN ('cancelled','refunded','voided')
)
UPDATE customers SET
  avg_days_between_orders = g.avg_gap,
  predicted_next_order_at = last_order_at + INTERVAL '1 day' * g.avg_gap
FROM (
  SELECT customer_id, AVG(days_gap) AS avg_gap
  FROM gaps WHERE days_gap IS NOT NULL
  GROUP BY customer_id
) g
WHERE customers.id = g.customer_id AND customers.workspace_id = :workspace_id;
```

---

## 8. Revenue-per-Query (SEO → Orders)

```sql
WITH gsc_page_query AS (
  SELECT query, page_path, SUM(clicks) AS clicks, SUM(impressions) AS impressions, AVG(position) AS avg_position
  FROM gsc_daily
  WHERE workspace_id = :workspace_id AND date BETWEEN :start AND :end
  GROUP BY query, page_path
),
page_revenue AS (
  SELECT landing_page_path, SUM(net_revenue) AS revenue
  FROM orders
  WHERE workspace_id = :workspace_id
    AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
    AND landing_page_path IS NOT NULL
  GROUP BY landing_page_path
),
query_with_share AS (
  SELECT gpq.query, gpq.clicks, gpq.impressions, gpq.avg_position, gpq.page_path,
    COALESCE(pr.revenue, 0) AS page_revenue,
    gpq.clicks::numeric / NULLIF(SUM(gpq.clicks) OVER (PARTITION BY gpq.page_path), 0) AS click_share
  FROM gsc_page_query gpq
  LEFT JOIN page_revenue pr ON pr.landing_page_path = gpq.page_path
)
SELECT query,
  SUM(clicks) AS clicks, SUM(impressions) AS impressions, AVG(avg_position) AS avg_position,
  SUM(page_revenue * click_share) AS revenue,
  CASE WHEN SUM(clicks) > 0 THEN SUM(page_revenue * click_share) / SUM(clicks) ELSE 0 END AS revenue_per_click
FROM query_with_share
GROUP BY query
ORDER BY revenue DESC
```

`orders.landing_page_path` computed at sync: strip protocol+host+query params.

**Brand vs Non-Brand:** `workspace.brand_keywords` JSONB array. Filter: `WHERE query NOT ILIKE ANY(ARRAY(SELECT '%' || kw || '%' FROM jsonb_array_elements_text(brand_keywords) kw))`.

**SEO KPI: Organic Revenue** = `SUM(revenue)` from the revenue-per-query result above (total attributed organic revenue). Also available directly: `SELECT SUM(COALESCE(net_revenue_converted, net_revenue)) FROM orders WHERE workspace_id = :workspace_id AND channel = 'organic_search' AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end`.

---

## 9. Ad Spend Attribution to Products

Fractional allocation by order line item revenue share:
```
For each order with matched_campaign_id:
  spend_per_order = campaign_daily_spend / orders_attributed_count
  For each line item:
    product_ad_spend = spend_per_order * (line_item.total_price / NULLIF(order.subtotal_price, 0))

Campaigns with spend but zero orders → "unattributed spend" bucket
```

---

## 10. Creative Analysis

**Fatigue detection:**
- Lookback: 7 days vs prior 7 days
- Flag: CTR decreased >20% AND (frequency increased >30% OR frequency > 3.0) AND impressions >= 1000 in BOTH windows
- From `ad_insights` grouped by `ad_id`

**Triage classification** (percentile-based, relative to account):
```
For each creative with 1000+ impressions:
  composite_score = (PERCENT_RANK(roas) * 0.5) + (PERCENT_RANK(ctr) * 0.25) + (PERCENT_RANK(hook_rate) * 0.25)
  Winner:  >= 0.75    Iterate: 0.25-0.75    Kill: < 0.25
```

**Video metrics:**
- `hook_rate = video_views_p25 / NULLIF(impressions, 0)`
- `hold_rate = video_views_p100 / NULLIF(video_views_p25, 0)`

---

## 10b. Klaviyo Flows (Marketing → Creatives tab)

```sql
SELECT ef.name, ef.status, ef.total_revenue, ef.total_conversions, ef.synced_at
FROM email_flows ef
WHERE ef.workspace_id = :workspace_id
ORDER BY ef.total_revenue DESC
```

Flow stats come from Klaviyo Reporting API (`POST /api/flow-values-reports`). Cumulative totals, not daily granularity at MVP. Show alongside campaign and ad creative performance on the Creatives tab.

---

## 11. Inventory Velocity & Stock Prediction

```sql
SELECT
  SUM(oli.quantity) / LEAST(28, GREATEST(1,
    EXTRACT(EPOCH FROM NOW() - MIN(o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)) / 86400))::numeric AS velocity_28d
FROM order_line_items oli
JOIN orders o ON o.id = oli.order_id
WHERE oli.variant_id = :variant_id AND o.workspace_id = :workspace_id
  AND o.created_at >= NOW() - INTERVAL '28 days'
  AND o.financial_status NOT IN ('refunded', 'voided', 'cancelled')
  AND oli.quantity > 0
```
Derived values (computed in application, not SQL):
- `days_of_stock = inventory_quantity / NULLIF(velocity_28d, 0)`
- `stock_out_date = CURRENT_DATE + days_of_stock::int`
- `reorder_qty = GREATEST(0, (30 * velocity_28d) - inventory_quantity)`

Min 3 distinct sale days in 28 for reliable velocity. Below: "Low confidence" badge. Zero sales: "No recent sales".

**Inventory drawer forecast chart:** Daily projected stock = `current_stock - (velocity_28d * day_offset)` for day_offset 0..90. Horizontal threshold lines at `reorder_qty` level and zero. Declining line chart — client-side computation from velocity + current stock.

---

## 12. Product Analytics Queries

### Repeat Purchase Rate Per Product
```sql
SELECT COUNT(DISTINCT c.id) FILTER (WHERE c.orders_count > 1)::numeric
  / NULLIF(COUNT(DISTINCT c.id), 0)
FROM order_line_items oli
JOIN orders o ON o.id = oli.order_id
JOIN customers c ON c.id = o.customer_id
WHERE oli.product_id = :product_id AND o.workspace_id = :workspace_id
  AND o.financial_status NOT IN ('refunded','voided','cancelled')
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
```

### First Purchase vs Repeat Split
```sql
SELECT
  COUNT(DISTINCT CASE WHEN o.is_new_customer THEN o.id END) AS first_orders,
  COUNT(DISTINCT CASE WHEN NOT o.is_new_customer THEN o.id END) AS repeat_orders,
  SUM(CASE WHEN o.is_new_customer THEN oli.total_price ELSE 0 END) AS first_revenue,
  SUM(CASE WHEN NOT o.is_new_customer THEN oli.total_price ELSE 0 END) AS repeat_revenue
FROM order_line_items oli JOIN orders o ON o.id = oli.order_id
WHERE oli.product_id = :product_id AND o.workspace_id = :workspace_id
  AND o.financial_status NOT IN ('refunded','voided','cancelled')
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
```

### Discount Impact Per Product
```sql
SELECT oli.product_id,
  COUNT(CASE WHEN o.total_discounts > 0 THEN 1 END) AS discounted_orders,
  COUNT(CASE WHEN o.total_discounts = 0 THEN 1 END) AS full_price_orders,
  AVG(CASE WHEN o.total_discounts > 0 THEN o.total_price END) AS avg_aov_discounted,
  AVG(CASE WHEN o.total_discounts = 0 THEN o.total_price END) AS avg_aov_full_price,
  AVG(CASE WHEN o.total_discounts > 0 THEN o.contribution_margin END) AS avg_margin_discounted,
  AVG(CASE WHEN o.total_discounts = 0 THEN o.contribution_margin END) AS avg_margin_full_price
FROM order_line_items oli JOIN orders o ON o.id = oli.order_id
WHERE o.workspace_id = :workspace_id AND o.financial_status NOT IN ('refunded','voided','cancelled')
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
GROUP BY oli.product_id
```

**orders.contribution_margin** — recomputed in order sync webhook handler and BackfillCogsOnOrdersJob:
`contribution_margin = net_revenue - cogs_total - actual_shipping_cost - transaction_fee - handling_cost`

### Quadrant Scatter Normalization
```sql
WITH campaign_spend AS (
  SELECT campaign_id, SUM(spend_workspace_currency) AS spend
  FROM ad_insights
  WHERE workspace_id = :workspace_id AND date BETWEEN :start AND :end AND level = 'campaign'
  GROUP BY campaign_id
),
order_spend AS (
  SELECT o.id AS order_id, COALESCE(cs.spend, 0) AS spend
  FROM orders o
  LEFT JOIN campaign_spend cs ON cs.campaign_id = o.matched_campaign_id
  WHERE o.workspace_id = :workspace_id AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
    AND o.financial_status NOT IN ('refunded','voided','cancelled')
),
product_metrics AS (
  SELECT p.id,
    SUM(oli.total_price) AS revenue,
    SUM(oli.unit_cogs * oli.quantity) AS cogs,
    CASE WHEN SUM(oli.total_price) > 0
      THEN (SUM(oli.total_price) - SUM(oli.unit_cogs * oli.quantity)) / SUM(oli.total_price) * 100
      ELSE 0 END AS contribution_margin_pct,
    SUM(oli.total_price) / NULLIF(SUM(os.spend * oli.total_price / NULLIF(o.subtotal_price, 0)), 0) AS roas
  FROM products p
  JOIN order_line_items oli ON oli.product_id = p.id
  JOIN orders o ON o.id = oli.order_id
  LEFT JOIN order_spend os ON os.order_id = o.id
  WHERE o.workspace_id = :workspace_id AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
    AND o.financial_status NOT IN ('refunded','voided','cancelled') AND oli.quantity > 0
  GROUP BY p.id
)
SELECT id, revenue,
  PERCENT_RANK() OVER (ORDER BY contribution_margin_pct) * 100 AS margin_index,
  PERCENT_RANK() OVER (ORDER BY roas) * 100 AS roas_index
FROM product_metrics
WHERE revenue > 0
```
Quadrant boundaries at 50 (median). Labels: keep pushing / reprice / investigate / kill.

### Winners/Losers
```sql
WITH current AS (
  SELECT oli.product_id,
    SUM(oli.total_price::numeric / NULLIF(o.subtotal_price, 0) * COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue
  FROM order_line_items oli JOIN orders o ON o.id = oli.order_id
  WHERE o.workspace_id = :workspace_id AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :current_start AND :current_end
    AND o.financial_status NOT IN ('refunded','voided','cancelled') AND oli.quantity > 0
  GROUP BY oli.product_id
),
previous AS (
  SELECT oli.product_id,
    SUM(oli.total_price::numeric / NULLIF(o.subtotal_price, 0) * COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue
  FROM order_line_items oli JOIN orders o ON o.id = oli.order_id
  WHERE o.workspace_id = :workspace_id AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :prev_start AND :prev_end
    AND o.financial_status NOT IN ('refunded','voided','cancelled') AND oli.quantity > 0
  GROUP BY product_id
),
changes AS (
  SELECT c.product_id, c.revenue AS current_revenue, p.revenue AS prev_revenue,
    ((c.revenue - COALESCE(p.revenue, 0)) / NULLIF(p.revenue, 0)) * 100 AS pct_change
  FROM current c LEFT JOIN previous p ON c.product_id = p.product_id
)
(SELECT *, 'rising' AS direction FROM changes ORDER BY pct_change DESC LIMIT 3)
UNION ALL
(SELECT *, 'falling' AS direction FROM changes WHERE prev_revenue > 0 ORDER BY pct_change ASC LIMIT 3)
```

---

## 13. Funnel Query

```php
// ga4_daily columns are `source` and `medium` (NOT utm_source/utm_medium)
$sourceFilters = [
    'facebook' => "(source ILIKE '%facebook%' OR source ILIKE '%fb%' OR source ILIKE '%instagram%' OR source ILIKE '%ig%' OR source ILIKE '%meta%')",
    'google'   => "source ILIKE '%google%'",
    'tiktok'   => "(source ILIKE '%tiktok%' OR source ILIKE '%tt%')",
    'email'    => "(medium ILIKE '%email%' OR medium ILIKE '%newsletter%')",
    'organic'  => "medium = 'organic'",
    'direct'   => "(source IS NULL OR source = '' OR source = 'direct')",
];
```

```sql
SELECT COALESCE(SUM(sessions), 0) AS landing, COALESCE(SUM(item_views), 0) AS product_view,
  COALESCE(SUM(add_to_carts), 0) AS atc, COALESCE(SUM(checkouts_started), 0) AS checkout, COALESCE(SUM(purchases), 0) AS purchase
FROM ga4_daily
WHERE workspace_id = :workspace_id AND date BETWEEN :start AND :end
  AND {source_filter_clause} AND ({device_filter})
```

---

## 14. SEO: Keyword Cannibalization

```sql
SELECT query, COUNT(DISTINCT page_path) AS competing_pages,
  ARRAY_AGG(DISTINCT page_path) AS urls,
  SUM(clicks) AS total_clicks, SUM(impressions) AS total_impressions
FROM gsc_daily
WHERE workspace_id = :workspace_id AND date BETWEEN :start AND :end AND impressions >= 10
GROUP BY query
HAVING COUNT(DISTINCT page_path) >= 2
ORDER BY total_impressions DESC
```

Severity: High when position difference < 3 AND impressions split >30% each. Medium otherwise. `LIMIT 100` — paginate if more needed.

---

## 15. SEO: CTR vs Position Scatter

```json
{
  "points": [{"query": "running shoes", "x": 4.2, "y": 0.032, "impressions": 1200, "clicks": 38}],
  "quadrants": {
    "topRight": {"label": "Strong — maintain", "color": "emerald"},
    "topLeft": {"label": "Good position, low CTR — rewrite title/meta", "color": "amber"},
    "bottomRight": {"label": "High CTR, poor position — build links", "color": "sky"},
    "bottomLeft": {"label": "Low priority", "color": "zinc"}
  }
}
```
X = avg position (inverted: 1 right, 20 left). Y = CTR. Boundary: position 10, CTR median.

---

## 16. CVR Per Country

```sql
WITH country_orders AS (
  SELECT shipping_country AS country, COUNT(*) AS order_count,
    SUM(net_revenue) AS revenue, SUM(net_revenue) / NULLIF(COUNT(*), 0) AS aov,
    SUM(actual_shipping_cost) / NULLIF(COUNT(*), 0) AS avg_carrier_cost
  FROM orders WHERE workspace_id = :workspace_id
    AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
    AND financial_status NOT IN ('refunded','voided','cancelled')
  GROUP BY shipping_country
),
country_sessions AS (
  SELECT UPPER(country) AS country, SUM(sessions) AS sessions
  FROM ga4_daily WHERE workspace_id = :workspace_id AND date BETWEEN :start AND :end
  GROUP BY UPPER(country)
)
SELECT co.country, co.order_count, co.revenue, co.aov, co.avg_carrier_cost,
  COALESCE(cs.sessions, 0) AS sessions,
  co.order_count::numeric / NULLIF(cs.sessions, 0) AS cvr
FROM country_orders co
LEFT JOIN country_sessions cs ON cs.country = co.country
ORDER BY co.revenue DESC
```

GA4 country must be stored as ISO 2-letter code during sync. GA4 API returns full names ("United States", "Germany") — convert using a name-to-ISO lookup table (e.g., `League\ISO3166` PHP package or a static array of ~250 entries). Without this mapping, the country join produces NULLs.

---

## 17. What-If Simulator (Shipping)

Client-side. Controller sends per-country data + AOV distribution histogram:
```json
{
  "countries": [{"country": "DE", "orders": 1240, "revenue": 62000, "avg_shipping_charged": 5.99, "avg_carrier_cost": 4.20, "return_pct": 0.28, "cod_pct": 0.05, "cogs_pct": 0.35, "contribution_margin": 18200}],
  "aov_distribution": [{"bucket": "0-25", "order_count": 120}, {"bucket": "25-50", "order_count": 340}, ...]
}
```

```js
const qualifyingOrders = aovDistribution
  .filter(b => b.minValue >= freeShippingThreshold)
  .reduce((sum, b) => sum + b.order_count, 0)
const lostShippingRevenue = qualifyingOrders * avg_shipping_charged
newMargin = revenue - (revenue * cogs_pct) - (orders * adjustedCarrierCost)
  - (orders * cod_pct * adjustedCodFee) - (orders * return_pct * avgOrderValue * returnCostMultiplier)
  - lostShippingRevenue
```

---

## 18. Monthly Overview

```sql
SELECT ds.date, ds.ad_spend_total, ds.net_revenue AS revenue, ds.orders_count,
  ds.units_sold AS items, ds.net_revenue / NULLIF(ds.orders_count, 0) AS aov,
  ds.net_revenue / NULLIF(ds.ad_spend_total, 0) AS mer, a.title AS note
FROM daily_snapshots ds
LEFT JOIN LATERAL (
  SELECT title FROM annotations
  WHERE workspace_id = ds.workspace_id AND date = ds.date
  ORDER BY created_at DESC LIMIT 1
) a ON true
WHERE ds.workspace_id = :workspace_id AND ds.store_id IS NULL
  AND ds.date BETWEEN :month_start AND :month_end
ORDER BY ds.date
```

Annotations: `POST /annotations`, `PUT /annotations/{id}`, `DELETE /annotations/{id}`. Only the most recent annotation per day shown in the table row — full list via CRUD endpoints.

Note: This column is MER (net_revenue / ad_spend), not gross ROAS. UI label should be "MER" to match section 1 definitions.

---

## 19. COD Detection

Set `orders.is_cod = true` during sync:
- Shopify: `payment_gateway_names` contains `'cash_on_delivery'` or `'cod'`
- WooCommerce: `payment_method = 'cod'`

---

## 20. Edge Cases & Zero States

| Scenario | Display |
|----------|---------|
| Division by zero (AOV, ROAS, CVR, CPA, MER) | `—` (em dash) |
| Negative net_revenue (refund > revenue) | Show negative number with red indicator, not `—` |
| Negative MER / AOV (refund-heavy day) | Show negative number + warning tooltip |
| Net Margin when net_revenue < 0 | Show `—` + tooltip "Net revenue is negative — margin % not meaningful" |
| No ads connected | Marketing page: empty state CTA |
| No COGS on any product | Profit columns: `—` + amber banner |
| COGS from default % | Amber "estimated" badge |
| No COGS at all | Rose "unknown" — excluded from profit |
| No GA4 | Sessions/CVR: `—` + CTA |
| No GSC | SEO page: landing-page-only mode (revenue per landing page without query data) |
| Zero spend | ROAS = `—` |
| Partial data | Show available, `—` for missing |
| Multi-store same SKU | Separate rows per store |
| Multi-currency | Convert to workspace currency at transaction-day FX rate |
| New workspace, zero orders | Progress screen until import complete |
| No page_speeds data (Health page) | Empty state: "No speed data yet. First test runs Monday at 04:00 UTC." |
| No alerts (Alerts page) | "No alerts yet. Configure alert rules in Settings. [Set up →]" |
| No segments created (Customers/Segments) | "Create your first customer segment. [Create segment →]" |
| <20 customers (Customers/LTV) | "Not enough customer data for cohort analysis. Need 20+ customers with orders." |
| No holidays watched (Tools/Holidays) | "Watch holidays to get reminders and see them on your charts. [Browse →]" |
| Zero orders in date range (Orders page) | Empty table with "No orders found in this date range." message row |

---

## 21. Services

### WorkspaceContext
```php
class WorkspaceContext {
    private ?Workspace $workspace = null;
    public function set(Workspace $w): void { $this->workspace = $w; }
    public function id(): ?int { return $this->workspace?->id; }
    public function get(): ?Workspace { return $this->workspace; }
}
// Singleton in AppServiceProvider. Helpers:
// function workspace(): ?Workspace
// function workspace_role(User $user): ?string — null-safe, returns null outside workspace context
```

```php
function workspace_role(User $user): ?string {
    $workspaceId = app(WorkspaceContext::class)->id();
    if ($workspaceId === null) return null;
    return DB::table('workspace_users')
        ->where('workspace_id', $workspaceId)
        ->where('user_id', $user->id)
        ->value('role');
}
```

### SetActiveWorkspace Middleware
```php
public function handle(Request $request, Closure $next) {
    $workspace = $request->route('workspace'); // route model binding {workspace:slug}
    if (!$request->user()->workspaces()->where('workspaces.id', $workspace->id)->exists()) {
        abort(404); // always 404, never 403
    }
    app(WorkspaceContext::class)->set($workspace);
    return $next($request);
}
```

### WorkspaceScope
```php
class WorkspaceScope implements Scope {
    public function apply(Builder $builder, Model $model): void {
        $id = app(WorkspaceContext::class)->id();
        if ($id === null) throw new RuntimeException('WorkspaceContext not set');
        $builder->where($model->getTable() . '.workspace_id', $id);
    }
}
```

### CurrencyConverterService
```php
class CurrencyConverterService {
    public function convert(float $amount, string $from, string $to, Carbon $date): float {
        if ($from === $to) return $amount;
        $rate = FxRate::where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('date', '<=', $date)  // weekend fallback: use last available
            ->orderByDesc('date')->first();
        if (!$rate) throw new FxRateNotFoundException("No rate for {$from}/{$to} on/before {$date}");
        return round($amount * $rate->rate, 2, PHP_ROUND_HALF_EVEN); // banker's rounding
    }
}
```

### DateRange Value Object
```php
class DateRange {
    public Carbon $start;
    public Carbon $end;
    public Carbon $comparisonStart;
    public Carbon $comparisonEnd;
    public string $granularity; // day, week, month

    public static function fromRequest(Request $request, string $default = '30d', ?string $tz = null): self {
        $tz = $tz ?? workspace()?->reporting_timezone ?? 'UTC';
        $preset = $request->get('range', session('date_range', $default));
        $today = now()->timezone($tz)->startOfDay();

        [$start, $end] = match($preset) {
            'today'        => [$today, $today],
            'yesterday'    => [$today->copy()->subDay(), $today->copy()->subDay()],
            '7d'           => [$today->copy()->subDays(6), $today],
            '30d'          => [$today->copy()->subDays(29), $today],
            '90d'          => [$today->copy()->subDays(89), $today],
            '365d'         => [$today->copy()->subDays(364), $today],
            'mtd'          => [$today->copy()->startOfMonth(), $today],
            'qtd'          => [$today->copy()->firstOfQuarter(), $today],
            'ytd'          => [$today->copy()->startOfYear(), $today],
            'last_month'   => [$today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth()],
            'last_quarter' => [$today->copy()->subQuarter()->firstOfQuarter(), $today->copy()->subQuarter()->lastOfQuarter()],
            'lifetime'     => [self::earliestSnapshotDate(), $today],
            'bfcm'         => self::bfcmRange($today),  // lookup holidays table for BF–CM dates
            default        => self::parseCustom($request->get('start'), $request->get('end'), $tz),
        };

        $periodLength = $start->diffInDays($end) + 1;
        $comparisonStart = $start->copy()->subDays($periodLength);
        $comparisonEnd = $end->copy()->subDays($periodLength);

        // Granularity auto-select based on period length
        $granularity = match(true) {
            $periodLength <= 14  => 'day',
            $periodLength <= 90  => 'week',
            default              => 'month',
        };

        session(['date_range' => $preset]);
        return new self($start, $end, $comparisonStart, $comparisonEnd, $granularity);
    }
}
```

`'lifetime'` => earliest snapshot date to today. Implementation: `MIN(date) FROM daily_snapshots WHERE workspace_id`. Comparison: same period last year (not previous period). Auto-granularity applies (>90d → monthly).

### ChannelClassifierService
First-match-wins by priority: workspace rules → global defaults → fallback (direct/referral/other).

Default seed: **277 rules** based on GA4 Default Channel Grouping, extended with ecommerce-specific sources. Full mapping at `docs/research/channel-mapping.md`.

**Resolution order:**
1. Click-ID detection: gclid → paid_search (or paid_shopping/paid_video/cross_network by campaign type), fbclid → paid_social, ttclid → paid_social, msclkid → paid_search
2. Walk `channel_mappings` table by priority (first-match-wins). Priority bands and 277 rules → `docs/research/channel-mapping.md`
3. Referrer-only fallback: match referrer domain against in-memory lists (SEARCH_DOMAINS, SOCIAL_DOMAINS, VIDEO_DOMAINS, SHOPPING_DOMAINS)
4. source=(direct) + medium=(none) → direct. Everything else → unassigned.

**Valid channels:** paid_search, paid_social, paid_video, paid_shopping, cross_network, display, email, sms, affiliate, mobile_push, organic_search, organic_social, organic_video, organic_shopping, referral, direct, unassigned

**Klaviyo caveat:** Klaviyo's default `utm_medium=campaign` causes traffic to land in "unassigned" in GA4. Recommend users set `utm_medium=email` in Klaviyo settings. Document this in UTM Builder tool tips.

### AdNameParserService
Extracts dimensions from ad/campaign/adset names using the workspace's delimiter and dimension slots. Called during ad sync (after campaign/adset/ad upsert).

```php
class AdNameParserService {
    public function parse(string $name, string $delimiter, array $dimensionSlots): array {
        $parts = explode($delimiter, $name);
        $parsed = [];
        foreach ($dimensionSlots as $i => $slot) {
            $parsed[$slot] = trim($parts[$i] ?? '');
        }
        return array_filter($parsed); // remove empty slots
    }
}
```

**When to run:** During ad sync, after upserting `ad_campaigns`, `ad_sets`, and `ads`. For each record, call `parse(name, workspace.naming_delimiter, workspace.naming_dimensions)` and store the result in `parsed_dimensions` JSONB. If `naming_delimiter` or `naming_dimensions` is null/empty, skip parsing (leave `parsed_dimensions` as null).

**On naming config change:** When workspace `naming_delimiter` or `naming_dimensions` are updated via Settings, dispatch `ReparseAdNamesJob` to re-parse all campaigns/adsets/ads in the workspace and update their `parsed_dimensions`.

### SnapshotBuilder Order of Operations
```
buildDaily(storeId, date):
  1. Orders for store+date (timezone-converted) → SUM revenue, discounts, refunds, tax, shipping, fees, handling, COGS; COUNT orders, units, new/returning customers; SUM new_customer_revenue
  2. Refunds for store+date → SUM return_shipping_cost, restocking_fees. **Must convert refund amounts** to workspace currency: join refunds→orders to get currency, then apply CurrencyConverterService. Refunds table has no currency column — inherit from parent order.
  3. Ad insights for workspace+date — ad_spend columns populated ONLY on workspace-level snapshot (store_id=NULL). Per-store snapshots leave ad_spend as 0 unless campaign→store mapping exists. This prevents N× multiplication when workspace aggregate sums store rows.
  4. GA4 for store's analytics_property+date → SUM sessions, item_views, add_to_carts, checkouts, purchases
  5. GSC for store's search_property+date → SUM clicks, impressions
  6. Email revenue: SUM revenue from orders WHERE channel IN ('email','sms') for workspace+date (NOT from email_campaigns table — that has cumulative totals, not daily). Campaign-level email metrics (opens, clicks, sends) are display-only on Creatives tab from email_campaigns table. Note: daily_snapshots.email_sends/opens/clicks are left as 0 at MVP — Klaviyo only provides cumulative totals, not daily deltas.
  7. Upsert daily_snapshots (workspace_id, store_id, date)

After all stores:
  8. Workspace-level row (store_id = NULL): SUM store-level numerics for revenue/orders/costs columns. Ad spend columns computed INDEPENDENTLY from ad_insights (not summed from per-store rows, which have 0). RECOMPUTE percentages from weighted components (NOT sum of percentages).
```

---

## 22. Inertia Controller Pattern

```php
class DashboardController extends Controller {
    public function __invoke(Request $request): Response {
        $range = DateRange::fromRequest($request, default: '30d');
        $kpis = DailySnapshot::query()
            ->where('workspace_id', workspace()->id)
            ->whereNull('store_id')
            ->whereBetween('date', [$range->start, $range->end])
            ->selectRaw('SUM(net_revenue) as revenue, SUM(orders_count) as orders, ...')
            ->first();
        $kpis->aov = $kpis->orders > 0 ? $kpis->revenue / $kpis->orders : null;
        $comparisonKpis = DailySnapshot::query()
            ->where('workspace_id', workspace()->id)
            ->whereNull('store_id')
            ->whereBetween('date', [$range->comparisonStart, $range->comparisonEnd])
            ->selectRaw('SUM(net_revenue) as revenue, SUM(orders_count) as orders, ...')
            ->first();
        return Inertia::render('Dashboard/Index', [
            'kpis' => $kpis,
            'comparison' => $comparisonKpis,
            'sparkline' => Inertia::defer(fn () => $this->getSparkline($range)),
            'channels' => Inertia::defer(fn () => $this->getChannelBreakdown($range)),
            'topProducts' => Inertia::defer(fn () => $this->getTopProducts($range)),
            'alerts' => Alert::unread()->limit(3)->get(),
        ]);
    }
}
```

KPIs load immediately. Charts/tables use `Inertia::defer()`. Ratios computed in controller, never stored.

---

## 23. Vue Component Structure

```
resources/js/
├── Pages/
│   ├── Onboarding/Index.vue       (connect store + import progress)
│   ├── Dashboard/Index.vue
│   ├── Profit/Index.vue          (tabs: P&L, Shipping)
│   ├── Marketing/Index.vue       (tabs: Campaigns, Creatives, Funnel)
│   ├── Products/Index.vue        (tabs: Performance, Inventory)
│   ├── Orders/Index.vue
│   ├── Customers/Index.vue       (tabs: LTV, Segments, List)
│   ├── Seo/Index.vue
│   ├── Health/Index.vue          (tab: Speed — Uptime v2)
│   ├── Alerts/Index.vue
│   ├── Tools/Holidays.vue
│   ├── Tools/UtmBuilder.vue
│   ├── Tools/NamingConventions.vue
│   ├── Tools/Calculator.vue
│   └── Settings/Index.vue        (tabs: Integrations, Costs, Channels, Workspace, Notifications)
├── Components/
│   ├── KpiCard.vue               — number + delta + sparkline (vue-sparklines, NOT ECharts)
│   ├── DataTable.vue             — PrimeVue DataTable wrapper (unstyled + Tailwind presets)
│   ├── TimeSeriesChart.vue       — ECharts line/area with comparison + annotations
│   ├── WaterfallChart.vue        — ECharts stacked bar technique
│   ├── CohortHeatmap.vue         — ECharts heatmap + visualMap
│   ├── QuadrantScatter.vue       — ECharts scatter + markArea
│   ├── HorizontalFunnel.vue      — ECharts funnel series
│   ├── SalesHeatmap.vue          — ECharts 7×24 day×hour heatmap
│   ├── RfmGrid.vue               — 5x5 clickable grid (plain HTML/CSS)
│   ├── Drawer.vue                — right-side overlay panel (480-560px)
│   ├── DateRangePicker.vue       — presets + custom + comparison toggle
│   ├── FilterChips.vue           — removable pill filters
│   ├── AlertStrip.vue            — inline alert bar
│   ├── EmptyState.vue            — one sentence + one CTA
│   └── SkeletonLoader.vue        — shimmer placeholder per component type
└── Layouts/
    ├── AppLayout.vue, Sidebar.vue, TopChrome.vue
```

---

## 24. Routes

```php
Route::middleware(['web', 'auth', 'verified', 'workspace'])->prefix('{workspace:slug}')->group(function () {
    Route::get('/onboarding', OnboardingController::class)->name('onboarding');
    Route::get('/onboarding/import', ImportProgressController::class)->name('onboarding.import');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profit', ProfitController::class)->name('profit');
    Route::get('/marketing', MarketingController::class)->name('marketing');
    Route::get('/products', ProductController::class)->name('products');
    Route::get('/orders', OrderController::class)->name('orders');
    Route::get('/customers', CustomerController::class)->name('customers');
    Route::get('/seo', SeoController::class)->name('seo');
    Route::get('/health', HealthController::class)->name('health');
    Route::get('/alerts', AlertController::class)->name('alerts');
    Route::prefix('tools')->group(function () {
        Route::get('/holidays', HolidayController::class)->name('tools.holidays');
        Route::get('/utm', UtmBuilderController::class)->name('tools.utm');
        Route::get('/naming', NamingConventionController::class)->name('tools.naming');
        Route::get('/calculator', CalculatorController::class)->name('tools.calculator');
    });
    Route::get('/settings', SettingsController::class)->name('settings');
    Route::prefix('api')->group(function () {
        Route::get('/import-status', ImportStatusController::class);
        Route::get('/campaigns/{campaign}/adsets', CampaignAdSetsController::class);
        Route::get('/adsets/{adset}/ads', AdSetAdsController::class);
        Route::get('/products/{product}/variants', VariantController::class);
        Route::put('/products/{product}/cogs', UpdateCogsController::class);
        Route::post('/annotations', StoreAnnotationController::class);
        Route::put('/annotations/{annotation}', UpdateAnnotationController::class);
        Route::delete('/annotations/{annotation}', DeleteAnnotationController::class);
        Route::post('/alert-rules', StoreAlertRuleController::class);
        Route::put('/alert-rules/{alertRule}', UpdateAlertRuleController::class);
        Route::delete('/alert-rules/{alertRule}', DeleteAlertRuleController::class);
        Route::put('/alerts/{alert}/acknowledge', AcknowledgeAlertController::class);
        Route::put('/alerts/{alert}/snooze', SnoozeAlertController::class);
        Route::post('/segments', CreateSegmentController::class);
        Route::put('/segments/{segment}', UpdateSegmentController::class);
        Route::delete('/segments/{segment}', DeleteSegmentController::class);
        Route::post('/segments/{segment}/push', PushSegmentController::class);
        Route::post('/cogs/upload', CogsCsvUploadController::class);
        Route::post('/export', ExportController::class);
        Route::get('/export/{export}', ExportStatusController::class);

        // Saved views CRUD
        Route::post('/saved-views', StoreSavedViewController::class);
        Route::put('/saved-views/{savedView}', UpdateSavedViewController::class);
        Route::delete('/saved-views/{savedView}', DeleteSavedViewController::class);

        // Shared links
        Route::post('/shared-links', StoreSharedLinkController::class);

        // Discount code analysis
        Route::get('/discount-codes', DiscountCodeAnalysisController::class);

        // Payment method distribution
        Route::get('/payment-methods', PaymentMethodDistributionController::class);

        // Workspace settings
        Route::put('/workspace', UpdateWorkspaceController::class);
        Route::post('/workspace/invitations', InviteWorkspaceMemberController::class);
        Route::delete('/workspace/invitations/{invitation}', RevokeInvitationController::class);
        Route::delete('/workspace/members/{user}', RemoveWorkspaceMemberController::class);
        Route::put('/workspace/members/{user}', UpdateWorkspaceMemberController::class);  // manage-members gate — update role + capability flags
        Route::put('/workspace/owner', TransferOwnershipController::class);
        Route::delete('/workspace', DeleteWorkspaceController::class);  // manage-workspace gate, password confirmation, 30-day soft delete

        // WooCommerce credential-based connection
        Route::post('/woocommerce/connect', WooCommerceConnectController::class);

        // Cost configuration
        Route::post('/shipping-rules', StoreShippingRuleController::class);
        Route::put('/shipping-rules/{shippingRule}', UpdateShippingRuleController::class);
        Route::delete('/shipping-rules/{shippingRule}', DeleteShippingRuleController::class);
        Route::post('/operational-costs', StoreOperationalCostController::class);
        Route::put('/operational-costs/{operationalCost}', UpdateOperationalCostController::class);
        Route::delete('/operational-costs/{operationalCost}', DeleteOperationalCostController::class);
        Route::post('/platform-fee-rules', StorePlatformFeeRuleController::class);
        Route::put('/platform-fee-rules/{platformFeeRule}', UpdatePlatformFeeRuleController::class);
        Route::delete('/platform-fee-rules/{platformFeeRule}', DeletePlatformFeeRuleController::class);
        Route::post('/channel-mappings', StoreChannelMappingController::class);
        Route::put('/channel-mappings/{channelMapping}', UpdateChannelMappingController::class);
        Route::delete('/channel-mappings/{channelMapping}', DeleteChannelMappingController::class);

        // Digest schedules
        Route::post('/digest-schedules', StoreDigestScheduleController::class);
        Route::put('/digest-schedules/{digestSchedule}', UpdateDigestScheduleController::class);
        Route::delete('/digest-schedules/{digestSchedule}', DeleteDigestScheduleController::class);

        // Holiday watch toggle
        Route::post('/holidays/{holiday}/watch', WatchHolidayController::class);
        Route::delete('/holidays/{holiday}/watch', UnwatchHolidayController::class);

        // UTM templates CRUD
        Route::post('/utm-templates', StoreUtmTemplateController::class);
        Route::put('/utm-templates/{utmTemplate}', UpdateUtmTemplateController::class);
        Route::delete('/utm-templates/{utmTemplate}', DeleteUtmTemplateController::class);

        // Single resource detail (drawer views)
        Route::get('/orders/{order}', OrderDetailController::class);
        Route::get('/customers/{customer}', CustomerDetailController::class);

        // Funnel step drill-through
        Route::get('/funnel/{step}', FunnelStepBreakdownController::class);

        // Integration disconnect (all types)
        Route::delete('/integrations/stores/{store}', DisconnectStoreController::class);
        Route::delete('/integrations/ad-accounts/{adAccount}', DisconnectAdAccountController::class);
        Route::delete('/integrations/analytics/{property}', DisconnectAnalyticsController::class);
        Route::delete('/integrations/search/{property}', DisconnectSearchController::class);
        Route::delete('/integrations/email/{account}', DisconnectEmailController::class);
    });
});

// OAuth redirect routes (require auth + verified — initiated from Settings)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/oauth/facebook', FacebookRedirectController::class);
    Route::get('/oauth/google', GoogleRedirectController::class);
    Route::get('/oauth/klaviyo', KlaviyoRedirectController::class);
});

// OAuth callbacks (no workspace prefix — provider redirects here)
Route::get('/shopify/install', ShopifyInstallController::class);
// ShopifyInstallController flow: verify HMAC params from Shopify → if user not
// authenticated, redirect to login/register with session('shopify_install_shop' => $shop)
// → after auth, redirect back to /shopify/install to resume → check if shop already
// connected (abort 409) → redirect to Shopify OAuth consent screen with state=workspace_id.
Route::get('/shopify/callback', ShopifyCallbackController::class);
// ShopifyCallbackController: verify HMAC → exchange code for access token → create/update
// store record in workspace from state param → subscribe to webhooks → dispatch initial import.
// Resolve store for Shopify webhooks via X-Shopify-Shop-Domain header → stores.domain lookup.
Route::get('/oauth/facebook/callback', FacebookCallbackController::class);
Route::get('/oauth/google/callback', GoogleCallbackController::class);
Route::get('/oauth/klaviyo/callback', KlaviyoCallbackController::class);
// TikTok OAuth: v2
// Route::get('/oauth/tiktok', TikTokRedirectController::class);
// Route::get('/oauth/tiktok/callback', TikTokCallbackController::class);

// Pre-workspace routes (authenticated + verified, no workspace prefix)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/workspaces/create', CreateWorkspaceController::class);
    Route::post('/workspaces', StoreWorkspaceController::class);
});

// Invitation acceptance (no auth — new users register, existing users auto-join)
// Guard: abort_unless($invitation->accepted_at === null && $invitation->expires_at > now(), 404)
Route::get('/invitations/{token}/accept', AcceptInvitationController::class);

// Public sharing (no auth required)
// SharedLinkController MUST: look up shared_link by token, extract workspace_id,
// then set WorkspaceContext manually (NOT bypass WorkspaceScope entirely — one missed
// manual filter leaks cross-workspace data). Skip workspace membership check since
// viewer isn't a member. Ignore any ?start/&end query params from the viewer and use
// ONLY the frozen date_range and filters stored on the shared_link record.
// Strip PII (customer emails, names, addresses, IDs) from all shared link responses.
Route::get('/share/{token}', SharedLinkController::class);

// Shopify order/product webhooks (real-time sync)
// verify-webhook-hmac middleware: extract shop domain from X-Shopify-Shop-Domain header,
// look up store by domain, verify HMAC using store's webhook_signing_secret. If store not
// found or disconnected, return 200 (prevent retries) without processing.
// Redis lock per order: Cache::lock("order-webhook:{$platformOrderId}", 5) to prevent
// concurrent webhook deliveries for the same order from racing.
Route::middleware(['verify-webhook-hmac'])->prefix('webhooks/shopify')->group(function () {
    Route::post('/orders/create', ShopifyOrderCreateController::class);
    Route::post('/orders/updated', ShopifyOrderUpdatedController::class);
    Route::post('/orders/cancelled', ShopifyOrderCancelledController::class);
    Route::post('/orders/delete', ShopifyOrderDeleteController::class); // soft-delete local order
    Route::post('/refunds/create', ShopifyRefundCreateController::class);
    Route::post('/products/update', ShopifyProductUpdateController::class);
    Route::post('/inventory_levels/update', ShopifyInventoryUpdateController::class);
    Route::post('/app/uninstalled', ShopifyAppUninstalledController::class);
});

// WooCommerce webhooks (real-time sync)
Route::middleware(['verify-webhook-hmac'])->prefix('webhooks/woocommerce/{store}')->group(function () {
    Route::post('/order-created', WooCommerceOrderCreatedController::class);
    Route::post('/order-updated', WooCommerceOrderUpdatedController::class);
    Route::post('/product-updated', WooCommerceProductUpdatedController::class);
    Route::post('/order-deleted', WooCommerceOrderDeletedController::class); // soft-delete local order
}); // {store} = Hashid-encoded store_id, registered at WC webhook setup time

// WooCommerce credential-based connection — inside workspace prefix (needs workspace context)
// Route defined above in the workspace API group: POST /{workspace}/api/woocommerce/connect

// Shopify mandatory GDPR webhooks (required for App Store listing — MUST verify HMAC)
Route::middleware(['verify-webhook-hmac'])->prefix('shopify/webhooks')->group(function () {
    Route::post('/customers-data-request', ShopifyCustomerDataRequestController::class);
    Route::post('/customers-redact', ShopifyCustomerRedactController::class);
    Route::post('/shop-redact', ShopifyShopRedactController::class);
});

// Stripe webhooks — single endpoint, Laravel Cashier handles dispatch by event type
Route::post('/stripe/webhook', \Laravel\Cashier\Http\Controllers\WebhookController::class)
    ->middleware(\Laravel\Cashier\Http\Middleware\VerifyWebhookSignature::class);
// Listen for events via: WebhookReceived / WebhookHandled events in EventServiceProvider
// Handle: customer.subscription.created, .updated, .deleted, invoice.paid, invoice.payment_failed, customer.subscription.trial_will_end
```

---

## 25. Scheduled Commands

**Queue topology** (Horizon — separate queues prevent bulk imports from blocking webhooks):
- `default` — webhooks, hourly syncs, lightweight jobs
- `snapshots` — snapshot builds, corrections, cohort snapshots
- `imports` — bulk import jobs (initial sync, historical backfill)
- `exports` — CSV/PDF export generation

```php
// Data sync
Schedule::job(new DispatchStorePollJobs)->hourly();
Schedule::job(new DispatchAdSyncJobs('meta'))->hourly();
Schedule::job(new DispatchAdSyncJobs('google'))->hourly()->at('15');
// DispatchAdSyncJobs('tiktok'): v2
Schedule::job(new DispatchGA4SyncJobs)->dailyAt('06:00');
Schedule::job(new DispatchGSCSyncJobs)->dailyAt('07:00');
Schedule::job(new DispatchKlaviyoSyncJobs)->hourly()->at('45');

// Aggregation
Schedule::job(new DispatchDailySnapshots)->dailyAt('02:00');
Schedule::job(new DispatchSnapshotCorrections)->dailyAt('06:30');  // rebuild last 14 days (after GA4 sync at 06:00; Google Ads data stabilizes in 7-14 days)

// Computation
Schedule::job(new DispatchRfmScoreJobs)->dailyAt('02:15');       // fans out per workspace → ComputeRfmScoresJob (WorkspaceAwareJob)
Schedule::job(new DispatchVelocityJobs)->dailyAt('02:30');       // fans out per workspace → ComputeVelocityJob (WorkspaceAwareJob)
Schedule::job(new DispatchAnomalyDetectionJobs)->hourly();       // fans out per workspace → DetectAnomaliesForWorkspaceJob (WorkspaceAwareJob). At 06:30, chain AFTER DispatchSnapshotCorrections via withoutOverlapping()
Schedule::job(new DispatchCohortSnapshotJobs)->dailyAt('03:00'); // fans out per workspace → BuildCohortSnapshotJob (WorkspaceAwareJob)

// Monitoring
// RunUptimeChecksJob: v2 — do not schedule at MVP (see database-schema.md uptime_checks table)
Schedule::job(new SyncPageSpeedJob)->weeklyOn(1, '04:00');
Schedule::job(new SyncFxRatesJob)->dailyAt('17:00');

// Maintenance — these iterate all workspaces/integrations internally (exceptions to WorkspaceAwareJob rule)
Schedule::job(new DispatchReconciliationJobs)->dailyAt('01:30');  // fans out ReconcileStoreOrdersJob per store
Schedule::job(new DetectStuckImportsJob)->everyTenMinutes();       // global — checks all stores
Schedule::job(new CleanupSyncLogsJob)->weeklyOn(0);                // global — deletes old logs across all workspaces
Schedule::job(new RefreshOAuthTokensJob)->dailyAt('05:00');        // global — queries token_expires_at across all integration tables

// Notifications
Schedule::job(new DispatchDigestJobs)->hourly();                   // fans out SendDigestForWorkspaceJob per workspace with active schedules
Schedule::job(new SendHolidayNotificationsJob)->dailyAt('09:00');  // global — queries watched holidays across workspaces
Schedule::job(new SyncStatutoryHolidaysJob)->weeklyOn(0, '08:00');  // Nager.Date API — free, no key needed
```

Snapshot correction: 14 days for daily corrections (covers Google Ads adjustment window). Meta's 28-day mutation window is handled by the hourly ad sync job (re-fetches last 3 days) plus daily ad re-fetch of last 28 days (see integrations.md §3). The snapshot correction only needs to cover the snapshot rebuild, not the ad data re-fetch. 30 days only for on-demand COGS-change rebuilds (separate trigger).

**Dispatcher job specs:**
- `DispatchStorePollJobs` — queries `stores` WHERE `sync_status = 'active'` AND `syncs_paused_at IS NULL`. Dispatches `SyncStoreOrdersJob` per store on `default` queue. Each job fetches orders since `last_synced_at` (or last 3 days for correction).
- `DispatchAdSyncJobs($platform)` — queries `ad_accounts` WHERE `platform = $platform` AND `sync_status = 'active'`. Dispatches `SyncAdInsightsJob` per account. Each job fetches campaign/adset/ad/insight data for last 3 days (hourly) or last 28 days (Meta daily correction).
- `DispatchGA4SyncJobs` — queries `analytics_properties` WHERE `sync_status = 'active'`. Dispatches `SyncGA4DailyJob` per property. Fetches yesterday + last 3 days correction window.
- `DispatchGSCSyncJobs` — queries `search_properties` WHERE `sync_status = 'active'`. Dispatches `SyncGSCDailyJob` per property. Fetches last 5 days (GSC data lag is 2-3 days).
- `DispatchKlaviyoSyncJobs` — queries `email_accounts` WHERE `sync_status = 'active'`. Dispatches `SyncKlaviyoCampaignsJob` + `SyncKlaviyoFlowsJob` per account.
- `DispatchDailySnapshots` — queries workspaces WHERE `syncs_paused_at IS NULL`. Per workspace: dispatches per-store `BuildDailySnapshotJob` jobs as a `Bus::batch()`, with an `then()` callback that dispatches the workspace-level aggregation job. This ensures the workspace aggregate row is built ONLY after all store snapshots complete. Each job runs SnapshotBuilder pipeline (section 21).
- `SyncPageSpeedJob` — fetches CrUX API (Chrome UX Report) for each store's domain. Stores LCP, INP, CLS, TTFB per URL strategy (mobile/desktop) in `page_speeds` table. Requires `PAGESPEED_API_KEY` env var.
- `CleanupSyncLogsJob` — deletes `sync_logs` older than 30 days. Deletes `exports` with `status = 'completed'` older than 24 hours and their S3 files.

**Job overlap prevention:** All dispatcher jobs and per-workspace jobs implement `ShouldBeUnique` keyed on `job_class:workspace_id`. Prevents concurrent runs for the same workspace (e.g., two snapshot builds racing).

**On-demand jobs (not scheduled):**
- `ReclassifyOrdersJob` — dispatched when channel mapping rules change. Re-runs ChannelClassifierService on all orders in workspace.
- `BackfillCogsOnOrdersJob` — dispatched on COGS change (CSV upload, manual edit, store sync). SQL below.
- `BuildSnapshotsForDateRange` — dispatched after initial import or manual COGS change. Builds snapshots for specified date range.

**BackfillCogsOnOrdersJob SQL** (extends WorkspaceAwareJob, ShouldBeUnique keyed on workspace_id):
```sql
-- Step 1: Re-walk line items and update unit_cogs from cogs_entries (effective_date lookup)
UPDATE order_line_items oli SET
  unit_cogs = ce.cost,
  cogs_source = CASE
    WHEN ce.variant_id IS NOT NULL THEN 'explicit'
    WHEN ce.product_id IS NOT NULL THEN 'explicit'
    ELSE 'workspace_default'
  END
FROM orders o
LEFT JOIN LATERAL (
  SELECT cost, variant_id, product_id FROM cogs_entries
  WHERE workspace_id = :workspace_id
    AND (variant_id = oli.variant_id OR (variant_id IS NULL AND product_id = oli.product_id))
    AND effective_from <= (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date
  ORDER BY (variant_id IS NOT NULL) DESC, effective_from DESC
  LIMIT 1
) ce ON true
WHERE o.workspace_id = :workspace_id AND o.id = oli.order_id
  AND oli.quantity > 0;

-- Step 2: Apply workspace default margin when no cogs_entry found
UPDATE order_line_items oli SET
  unit_cogs = oli.total_price / NULLIF(oli.quantity, 0) * (:default_margin_pct / 100),
  cogs_source = 'workspace_default'
FROM orders o
WHERE o.workspace_id = :workspace_id AND o.id = oli.order_id
  AND oli.unit_cogs IS NULL AND oli.quantity > 0
  AND :default_margin_pct IS NOT NULL;

-- Step 3: Recompute per-order totals
UPDATE orders SET
  cogs_total = (SELECT COALESCE(SUM(unit_cogs * quantity), 0) FROM order_line_items WHERE order_id = orders.id),
  contribution_margin = net_revenue - (SELECT COALESCE(SUM(unit_cogs * quantity), 0) FROM order_line_items WHERE order_id = orders.id)
    - actual_shipping_cost - transaction_fee - handling_cost
WHERE workspace_id = :workspace_id;

-- Step 4: Dispatch BuildSnapshotsForDateRange for affected date range
-- Range: MIN(o.created_at)..MAX(o.created_at) of updated orders, or full history if COGS entry has no effective_to
```

**Snapshot correction rebuild** (`DispatchSnapshotCorrections` at 06:30 daily):
```sql
-- Rebuild last 14 days of snapshots (covers Google Ads 7-14 day adjustment window)
-- For each workspace with syncs_paused_at IS NULL:
--   For each date in (CURRENT_DATE - 14)..CURRENT_DATE:
--     For each store: re-run SnapshotBuilder.buildDaily(workspace, store, date)
--     Then: re-run SnapshotBuilder.buildWorkspaceAggregate(workspace, date)
-- Uses same Bus::batch() pattern as DispatchDailySnapshots (store jobs first, aggregate after)
-- On-demand COGS-change rebuilds use 30-day window instead of 14
```

---

## 26. COGS CSV Upload

```csv
sku,cost,currency,effective_from
SKU-001,12.50,EUR,2026-01-01
```

Match by `sku` → `product_variants.sku` within workspace. Columns: `sku` (required), `cost` (required), `currency` (optional, default workspace), `effective_from` (optional, default today). Convert cost to workspace reporting currency at upload time using day's FX rate — `cogs_entries.cost` is ALWAYS stored in workspace currency, `cogs_entries.currency` preserves the original for audit. Partial import — skip invalid rows, return summary. Strip BOM, auto-detect delimiter (comma/semicolon/tab), enforce UTF-8.

---

## 27. Accordion Data Loading

PrimeVue DataTable handles expandable rows natively. Pre-load first level. Expanding triggers lazy fetch with date range params:
- `GET /{workspace}/api/campaigns/{id}/adsets?start=X&end=Y`
- `GET /{workspace}/api/adsets/{id}/ads?start=X&end=Y`
- `GET /{workspace}/api/products/{id}/variants?start=X&end=Y`

---

## 28. Waterfall Chart Data Shape

ECharts has no native waterfall type. Implement via stacked bar with transparent base series (documented ECharts pattern).

```json
{
  "steps": [
    {"label": "Gross Sales", "value": 50000, "type": "total"},
    {"label": "Discounts", "value": -5000, "type": "decrease"},
    {"label": "Refunds", "value": -3000, "type": "decrease"},
    {"label": "Net Sales", "value": 42000, "type": "subtotal"},
    {"label": "COGS", "value": -12000, "type": "decrease"},
    {"label": "CM1", "value": 30000, "type": "subtotal"},
    {"label": "Fulfillment", "value": -4000, "type": "decrease"},
    {"label": "Fees", "value": -2000, "type": "decrease"},
    {"label": "CM2", "value": 24000, "type": "subtotal"},
    {"label": "Ad Spend", "value": -8000, "type": "decrease"},
    {"label": "CM3", "value": 16000, "type": "subtotal"},
    {"label": "OPEX", "value": -3000, "type": "decrease"},
    {"label": "Net Profit", "value": 13000, "type": "total"}
  ]
}
```

---

## 29. Market Basket Analysis (F5)

```sql
SELECT
  a.product_id AS product_a,
  b.product_id AS product_b,
  COUNT(DISTINCT a.order_id) AS co_purchase_count
FROM order_line_items a
JOIN order_line_items b ON a.order_id = b.order_id AND a.product_id < b.product_id
JOIN orders o ON o.id = a.order_id
WHERE o.workspace_id = :workspace_id
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND o.financial_status NOT IN ('refunded','voided','cancelled')
  AND a.quantity > 0 AND b.quantity > 0
GROUP BY a.product_id, b.product_id
HAVING COUNT(DISTINCT a.order_id) >= 3
ORDER BY co_purchase_count DESC
LIMIT 50
```

Returns top 50 product pairs. Display as ranked table (industry standard — no heatmap/matrix, doesn't scale past ~20 products):

| Product A | Product B | Orders Together | Confidence % | Revenue Share |

- **Confidence %** = co_purchase_count / orders_containing_A. Most actionable metric for cross-sell decisions.
- **Revenue Share** = combined pair revenue / total revenue in period.
- Filter: product search to show pairs containing a specific product.
- Placement: collapsible section below main Products table (deferred).

---

## 30. Creative Launch Analysis (F4)

An ad is "newly launched" if its first impression date is within the last 14 days.

```sql
SELECT ad_id, MIN(date) AS launch_date,
  SUM(spend) AS total_spend, SUM(impressions) AS total_impressions,
  SUM(clicks) AS total_clicks, SUM(purchases) AS total_conversions
FROM ad_insights
WHERE workspace_id = :workspace_id AND date >= CURRENT_DATE - 14
  AND level = 'ad' AND ad_id IS NOT NULL
GROUP BY ad_id
HAVING MIN(date) >= CURRENT_DATE - 14  -- only ads that started in the window
```

**Status labels** (based on day-over-day spend trend in last 3 days):
- **Scaling**: spend increasing AND ROAS above account median → green
- **Early Winner**: <7 days of data AND ROAS above median → blue
- **Declining**: spend decreasing OR ROAS dropping >20% from first 3 days → amber
- **Needs Data**: <1000 impressions total → gray

Show on Creatives tab as a "Recently Launched" section above the main grid.

---

## 31. Sales Heatmap (7×24 day-of-week × hour-of-day)

```sql
SELECT
  EXTRACT(ISODOW FROM created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::int AS dow,  -- 1=Mon..7=Sun
  EXTRACT(HOUR FROM created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::int AS hour,   -- 0..23
  COUNT(*) AS order_count,
  SUM(net_revenue) AS revenue
FROM orders
WHERE workspace_id = :workspace_id
  AND created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz >= :start_date
  AND created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz < :end_date
  AND financial_status NOT IN ('refunded','voided','cancelled')
GROUP BY 1, 2
ORDER BY 1, 2
```

Returns up to 168 rows (7×24). Fill missing cells with zero on frontend. ECharts data shape: `[[dow, hour, value], ...]`. Default: last 30 days, workspace timezone, white-to-indigo sequential color scale. Toggle: Orders / Revenue.

Dashboard card. Actionable for: ad scheduling (pause during dead hours), email send-time optimization, staffing.

---

## 32. Missing Dashboard & Controller Queries

### Channel Breakdown (Dashboard deferred prop)
```sql
WITH channel_revenue AS (
  SELECT o.channel, COUNT(DISTINCT o.id) AS orders, SUM(COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue
  FROM orders o
  WHERE o.workspace_id = :workspace_id
    AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
    AND o.financial_status NOT IN ('refunded','voided','cancelled')
  GROUP BY o.channel
),
channel_spend AS (
  SELECT channel, SUM(spend) AS spend
  FROM (
    SELECT
      CASE ai.platform
        WHEN 'meta' THEN 'paid_social'
        WHEN 'google' THEN COALESCE(
          CASE ac.campaign_type WHEN 'shopping' THEN 'paid_shopping' WHEN 'pmax' THEN 'paid_shopping' WHEN 'video' THEN 'paid_video' ELSE 'paid_search' END,
          'paid_search')
        -- WHEN 'tiktok' THEN 'paid_social'  -- v2
        ELSE 'display'
      END AS channel,
      ai.spend_workspace_currency AS spend
    FROM ad_insights ai
    JOIN ad_campaigns ac ON ac.id = ai.campaign_id
    WHERE ai.workspace_id = :workspace_id AND ai.date BETWEEN :start AND :end AND ai.level = 'campaign'
  ) sub
  GROUP BY channel
)
SELECT cr.channel, cr.orders, cr.revenue,
  COALESCE(cs.spend, 0) AS spend,
  cr.revenue / NULLIF(COALESCE(cs.spend, 0), 0) AS roas
FROM channel_revenue cr
LEFT JOIN channel_spend cs ON cs.channel = cr.channel
ORDER BY cr.revenue DESC
```

### Top 5 Products (Dashboard deferred prop)
Uses `o.net_revenue_converted` proportional allocation per line item (workspace reporting currency):
```sql
SELECT p.id, p.title, p.image_url,
  SUM(oli.total_price::numeric / NULLIF(o.subtotal_price, 0) * COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue,
  SUM(oli.quantity) AS units_sold,
  COALESCE(SUM(oli.unit_cogs * oli.quantity), 0) AS cogs
FROM order_line_items oli
JOIN orders o ON o.id = oli.order_id
JOIN products p ON p.id = oli.product_id
WHERE o.workspace_id = :workspace_id
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND o.financial_status NOT IN ('refunded','voided','cancelled')
  AND oli.quantity > 0
GROUP BY p.id, p.title, p.image_url
ORDER BY revenue DESC
LIMIT 5
```

### Sparkline (Dashboard deferred prop)
Returns daily values for the selected metric (default: `net_revenue`). Comparison period overlaid:
```sql
SELECT date, SUM(net_revenue) AS value
FROM daily_snapshots
WHERE workspace_id = :workspace_id AND store_id IS NULL
  AND date BETWEEN :start AND :end
GROUP BY date
ORDER BY date
```
Same query for comparison period with `date BETWEEN :comp_start AND :comp_end`. Metric column changes based on user selection (`net_revenue`, `orders_count`, `ad_spend_total`, `sessions`, etc.).

### Today-So-Far (Dashboard deferred prop)
Queries `orders` directly (not snapshots — today's snapshot hasn't been built yet):
```sql
SELECT
  SUM(COALESCE(net_revenue_converted, net_revenue)) AS revenue_today,
  COUNT(*) AS orders_today
FROM orders
WHERE workspace_id = :workspace_id
  AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date = (NOW() AT TIME ZONE :tz)::date
  AND financial_status NOT IN ('refunded','voided','cancelled')
```
Yesterday same-hour baseline (for "vs yesterday" comparison):
```sql
SELECT SUM(COALESCE(net_revenue_converted, net_revenue)) AS revenue_yesterday_same_hour
FROM orders
WHERE workspace_id = :workspace_id
  AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date = ((NOW() AT TIME ZONE :tz)::date - 1)
  AND EXTRACT(HOUR FROM created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz) <= EXTRACT(HOUR FROM NOW() AT TIME ZONE :tz)
  AND financial_status NOT IN ('refunded','voided','cancelled')
```
No linear projection (wildly inaccurate in early hours — no competitor does this). Show actuals + yesterday comparison only.

Prop shape: `{revenue_today, orders_today, revenue_yesterday_same_hour, orders_yesterday_same_hour}`.

### Marketing KPI Row (Marketing immediate prop)
All from `daily_snapshots` (fast, pre-aggregated) except conversions/attributed revenue:

| KPI | Source | Formula |
|-----|--------|---------|
| Spend | `daily_snapshots.ad_spend_total` | `SUM(ad_spend_total)` |
| Blended ROAS | `daily_snapshots` | `SUM(revenue) / NULLIF(SUM(ad_spend_total), 0)` (gross revenue) |
| MER | `daily_snapshots` | `SUM(net_revenue) / NULLIF(SUM(ad_spend_total), 0)` (net revenue) |
| Conversions | `ad_insights` | `SUM(purchases) WHERE level = 'campaign'` (platform-reported) |
| Attributed Revenue | `ad_insights` | `SUM(purchase_value) WHERE level = 'campaign'` (platform-reported) |
| CPA (cost per order) | `daily_snapshots` | `SUM(ad_spend_total) / NULLIF(SUM(orders_count), 0)` |

### Creatives Grid (Marketing deferred prop)
```sql
WITH creative_stats AS (
  SELECT a.id, a.name, a.creative_thumbnail_url, a.campaign_id, ac.platform,
    SUM(ai.spend_workspace_currency) AS spend,
    SUM(ai.impressions) AS impressions,
    SUM(ai.clicks) AS clicks,
    SUM(ai.clicks)::numeric / NULLIF(SUM(ai.impressions), 0) AS ctr,
    SUM(ai.purchases) AS conversions,
    SUM(ai.purchase_value) / NULLIF(SUM(ai.spend_workspace_currency), 0) AS roas,
    SUM(ai.spend_workspace_currency) / NULLIF(SUM(ai.purchases), 0) AS cpa,
    SUM(ai.video_views_p25)::numeric / NULLIF(SUM(ai.impressions), 0) AS hook_rate,
    SUM(ai.video_views_p100)::numeric / NULLIF(SUM(ai.video_views_p25), 0) AS hold_rate
  FROM ads a
  JOIN ad_insights ai ON ai.ad_id = a.id AND ai.level = 'ad'
  JOIN ad_campaigns ac ON ac.id = a.campaign_id
  WHERE a.workspace_id = :workspace_id AND ai.date BETWEEN :start AND :end
  GROUP BY a.id, a.name, a.creative_thumbnail_url, a.campaign_id, ac.platform
  HAVING SUM(ai.impressions) >= 1000
),
scored AS (
  SELECT *,
    PERCENT_RANK() OVER (ORDER BY roas) * 0.5
      + PERCENT_RANK() OVER (ORDER BY ctr) * 0.25
      + PERCENT_RANK() OVER (ORDER BY hook_rate) * 0.25 AS composite_score
  FROM creative_stats
)
SELECT *,
  CASE WHEN composite_score >= 0.75 THEN 'winner' WHEN composite_score < 0.25 THEN 'kill' ELSE 'iterate' END AS triage
FROM scored
ORDER BY spend DESC
LIMIT 200
```

### Store-Side Revenue Per Campaign (F3 multi-source ROAS)
```sql
SELECT o.matched_campaign_id AS campaign_id,
  SUM(COALESCE(o.net_revenue_converted, o.net_revenue)) AS store_revenue,
  COUNT(DISTINCT o.id) AS store_conversions
FROM orders o
WHERE o.workspace_id = :workspace_id
  AND o.matched_campaign_id IS NOT NULL
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND o.financial_status NOT IN ('refunded','voided','cancelled')
GROUP BY o.matched_campaign_id
```
Join with campaigns table on `campaign_id` to compute `store_roas = store_revenue / NULLIF(campaign_spend, 0)`.

### Products Table (Products deferred prop)
```sql
SELECT p.id, p.title, p.image_url, p.vendor, p.product_type,
  SUM(oli.quantity) AS units_sold,
  SUM(oli.total_price::numeric / NULLIF(o.subtotal_price, 0) * COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue,
  COALESCE(SUM(oli.unit_cogs * oli.quantity), 0) AS cogs,
  SUM(oli.total_price::numeric / NULLIF(o.subtotal_price, 0) * COALESCE(o.net_revenue_converted, o.net_revenue))
    - COALESCE(SUM(oli.unit_cogs * oli.quantity), 0) AS contribution_profit,
  CASE WHEN SUM(oli.total_price) > 0
    THEN (SUM(oli.total_price) - COALESCE(SUM(oli.unit_cogs * oli.quantity), 0))
      / SUM(oli.total_price) * 100 ELSE 0 END AS margin_pct,
  COUNT(DISTINCT CASE WHEN o.refund_total > 0 OR o.financial_status IN ('refunded','partially_refunded') THEN o.id END)::numeric
    / NULLIF(COUNT(DISTINCT o.id), 0) AS refund_rate,
  (SELECT MIN(pv.inventory_quantity) FROM product_variants pv WHERE pv.product_id = p.id) AS min_stock,
  COALESCE(ad.ad_spend, 0) AS ad_spend,
  CASE WHEN COALESCE(ad.ad_spend, 0) > 0
    THEN SUM(oli.total_price::numeric / NULLIF(o.subtotal_price, 0) * COALESCE(o.net_revenue_converted, o.net_revenue)) / ad.ad_spend
    ELSE NULL END AS roas
FROM products p
JOIN order_line_items oli ON oli.product_id = p.id
JOIN orders o ON o.id = oli.order_id
LEFT JOIN (
  SELECT oli2.product_id,
    SUM(ai.spend_workspace_currency
      / NULLIF(ai.orders_attributed_count, 1)
      * oli2.total_price / NULLIF(o2.subtotal_price, 0)
    ) AS ad_spend
  FROM order_line_items oli2
  JOIN orders o2 ON o2.id = oli2.order_id
  JOIN ad_campaigns ac ON ac.id = o2.matched_campaign_id
  JOIN ad_insights ai ON ai.campaign_id = ac.id AND ai.date = (o2.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date AND ai.level = 'campaign'
  WHERE o2.workspace_id = :workspace_id AND o2.matched_campaign_id IS NOT NULL
    AND (o2.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  GROUP BY oli2.product_id
) ad ON ad.product_id = p.id
WHERE p.workspace_id = :workspace_id
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND o.financial_status NOT IN ('voided','cancelled')  -- deliberately includes refunded for refund_rate denominator
  AND oli.quantity > 0
GROUP BY p.id, p.title, p.image_url, p.vendor, p.product_type, ad.ad_spend
ORDER BY revenue DESC
LIMIT 500
```

Organic search revenue per product (enrichment for Products table):
```sql
SELECT p.id, SUM(COALESCE(o.net_revenue_converted, o.net_revenue)) AS organic_revenue
FROM orders o
JOIN order_line_items li ON li.order_id = o.id
JOIN products p ON p.id = li.product_id
WHERE o.workspace_id = :workspace_id AND o.channel = 'organic_search'
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
GROUP BY p.id
```
GSC traffic per product (optional enrichment — requires `products.handle`):
```sql
SELECT p.id, SUM(g.clicks) AS organic_clicks, SUM(g.impressions) AS organic_impressions
FROM gsc_daily g
JOIN products p ON p.workspace_id = g.workspace_id
  AND substring(g.page_path from '/products/([^/]+)') = p.handle
WHERE g.workspace_id = :workspace_id AND g.date BETWEEN :start AND :end
GROUP BY p.id
```

### Orders List (Orders deferred prop)
Server-side paginated, all amounts in workspace currency:
```sql
SELECT o.id, o.order_number, o.created_at, o.financial_status, o.fulfillment_status,
  c.email AS customer_email, c.first_name, c.last_name,
  COALESCE(o.total_price_converted, o.total_price) AS revenue,
  o.cogs_total, o.actual_shipping_cost, o.transaction_fee,
  o.total_discounts, o.refund_total,
  COALESCE(o.net_revenue_converted, o.net_revenue) AS net_revenue,
  o.contribution_margin, o.channel, o.is_new_customer,
  o.shipping_country, o.payment_gateway,
  o.utm_source, o.utm_medium, o.utm_campaign
FROM orders o
LEFT JOIN customers c ON c.id = o.customer_id
WHERE o.workspace_id = :workspace_id
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  {filter_clauses}
ORDER BY o.created_at DESC
LIMIT :per_page OFFSET :offset
```
Filter clauses built from saved view `filters` JSONB — whitelist allowed field names.

### Order Detail (drawer via GET /api/orders/{order})
```sql
SELECT o.*,
  COALESCE(o.net_revenue_converted, o.net_revenue) AS net_revenue_display,
  c.email, c.first_name, c.last_name, c.orders_count AS customer_orders_count,
  c.total_spent AS customer_total_spent, c.rfm_segment
FROM orders o
LEFT JOIN customers c ON c.id = o.customer_id
WHERE o.id = :order_id AND o.workspace_id = :workspace_id
```

Line items with COGS:
```sql
SELECT oli.id, oli.product_id, oli.variant_id, p.title, pv.title AS variant_title,
  pv.sku, oli.quantity, oli.unit_price, oli.total_price, oli.unit_cogs,
  oli.unit_cogs * oli.quantity AS line_cogs,
  oli.total_price - COALESCE(oli.unit_cogs * oli.quantity, 0) AS line_margin,
  p.image_url
FROM order_line_items oli
JOIN products p ON p.id = oli.product_id
LEFT JOIN product_variants pv ON pv.id = oli.variant_id
WHERE oli.order_id = :order_id
ORDER BY oli.id
```

**Prop shape:** `{order, lineItems, refunds: [...], cogsConfidence: 'full'|'partial'|'none'}`. COGS confidence: `full` = all line items have unit_cogs, `partial` = some have unit_cogs or using default margin %, `none` = no COGS data.

### Customer Detail (drawer via GET /api/customers/{customer})
```sql
SELECT c.*, 
  c.orders_count, c.total_spent, c.total_spent / NULLIF(c.orders_count, 0) AS avg_order_value,
  c.rfm_recency_score, c.rfm_frequency_score, c.rfm_monetary_score, c.rfm_segment,
  c.first_order_at, c.last_order_at, c.avg_days_between_orders, c.predicted_next_order_at
FROM customers c
WHERE c.id = :customer_id AND c.workspace_id = :workspace_id
```

Recent orders:
```sql
SELECT id, order_number, created_at, financial_status,
  COALESCE(net_revenue_converted, net_revenue) AS net_revenue, channel, is_new_customer
FROM orders
WHERE customer_id = :customer_id AND workspace_id = :workspace_id
ORDER BY created_at DESC LIMIT 20
```

Segment membership:
```sql
SELECT cs.id, cs.name FROM customer_segments cs
JOIN customer_segment_members csm ON csm.segment_id = cs.id
WHERE csm.customer_id = :customer_id
```

### Funnel Step Breakdown (drill-through drawer)
```sql
SELECT landing_page, SUM(sessions) AS sessions, SUM({step_metric}) AS step_value
FROM ga4_daily
WHERE workspace_id = :workspace_id AND date BETWEEN :start AND :end
  AND {source_filter_clause}
GROUP BY landing_page
ORDER BY step_value DESC
LIMIT 50
```
`{step_metric}` is one of: `sessions` (landing), `item_views` (product_view), `add_to_carts` (atc), `checkouts_started` (checkout), `purchases` (purchase).
`{source_filter_clause}` built from optional `?source` param — e.g., `AND source = :source`. Empty string when no source filter active. `{device_filter}` = `AND device_category = :device` or empty.

### Campaigns Table (Marketing deferred prop)
```sql
SELECT ac.id, ac.name, ac.status, ac.campaign_type, ac.platform,
  SUM(ai.spend_workspace_currency) AS spend,
  SUM(ai.impressions) AS impressions,
  SUM(ai.clicks) AS clicks,
  SUM(ai.clicks)::numeric / NULLIF(SUM(ai.impressions), 0) AS ctr,
  SUM(ai.spend_workspace_currency) / NULLIF(SUM(ai.clicks), 0) AS cpc,
  SUM(ai.spend_workspace_currency) / NULLIF(SUM(ai.impressions), 0) * 1000 AS cpm,
  SUM(ai.purchases) AS conversions,
  SUM(ai.purchase_value) AS platform_revenue,
  SUM(ai.purchase_value) / NULLIF(SUM(ai.spend_workspace_currency), 0) AS platform_roas,
  SUM(ai.spend_workspace_currency) / NULLIF(SUM(ai.purchases), 0) AS cpa
FROM ad_campaigns ac
LEFT JOIN ad_insights ai ON ai.campaign_id = ac.id AND ai.level = 'campaign' AND ai.date BETWEEN :start AND :end
WHERE ac.workspace_id = :workspace_id AND ac.status != 'archived'
  {parsed_dimensions_filter}
GROUP BY ac.id, ac.name, ac.status, ac.campaign_type, ac.platform
ORDER BY spend DESC NULLS LAST
LIMIT 500
```
`{parsed_dimensions_filter}` = optional JSONB containment filter when naming dimension chips active: `AND ac.parsed_dimensions @> :dimension_filter::jsonb` (e.g., `{"country":"SI","funnel":"TOF"}`).
Use `LEFT JOIN` so campaigns with zero spend in the date range still appear (as paused/draft). Use `ai.level = 'campaign'` to avoid multi-counting spend from ad/adset-level rows. Paginate beyond 500 if needed.

### Health Page KPIs (feature F11)
```sql
SELECT
  PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY lcp_ms) AS lcp_p75,
  PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY inp_ms) AS inp_p75,
  PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY cls) AS cls_p75,
  AVG(performance_score) AS avg_score
FROM page_speeds
WHERE workspace_id = :workspace_id AND strategy = :strategy
  AND measured_at = (SELECT MAX(measured_at) FROM page_speeds WHERE workspace_id = :workspace_id AND strategy = :strategy)
```

URL scores table:
```sql
SELECT url, performance_score, lcp_ms, inp_ms, cls, source, strategy
FROM page_speeds
WHERE workspace_id = :workspace_id AND strategy = :strategy
  AND measured_at = (SELECT MAX(measured_at) FROM page_speeds WHERE workspace_id = :workspace_id AND strategy = :strategy)
ORDER BY performance_score ASC
```

### Shipping Countries (Profit deferred prop)
```sql
SELECT o.shipping_country AS country,
  COUNT(*) AS orders,
  SUM(COALESCE(o.net_revenue_converted, o.net_revenue)) AS revenue,
  SUM(COALESCE(o.net_revenue_converted, o.net_revenue)) / NULLIF(COUNT(*), 0) AS aov,
  SUM(o.total_shipping) / NULLIF(COUNT(*), 0) AS avg_shipping_charged,
  SUM(o.actual_shipping_cost) / NULLIF(COUNT(*), 0) AS avg_carrier_cost,
  COUNT(CASE WHEN o.financial_status IN ('refunded', 'partially_refunded') THEN 1 END)::numeric / NULLIF(COUNT(*), 0) AS return_pct,
  SUM(CASE WHEN o.is_cod THEN 1 ELSE 0 END)::numeric / NULLIF(COUNT(*), 0) AS cod_pct,
  SUM(COALESCE(o.cogs_total, 0))::numeric / NULLIF(SUM(o.total_price), 0) AS cogs_pct,
  SUM(o.contribution_margin) AS contribution_margin
FROM orders o
WHERE o.workspace_id = :workspace_id
  AND (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND o.financial_status NOT IN ('voided','cancelled')
GROUP BY o.shipping_country
ORDER BY revenue DESC
```

### AOV Distribution Histogram (Profit what-if)
Uses `total_price_converted` (workspace reporting currency). `bucket_size` validated as `integer|min:1`, default 25:
```sql
SELECT
  FLOOR(total_price_converted / :bucket_size) * :bucket_size AS bucket_min,
  FLOOR(total_price_converted / :bucket_size) * :bucket_size + :bucket_size AS bucket_max,
  COUNT(*) AS order_count
FROM orders
WHERE workspace_id = :workspace_id
  AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND financial_status NOT IN ('refunded','voided','cancelled')
  AND total_price_converted IS NOT NULL
GROUP BY 1, 2
ORDER BY 1
```

### Payment Method Distribution (Funnel tab)
```sql
SELECT payment_gateway, COUNT(*) AS orders,
  COUNT(*)::numeric / NULLIF(SUM(COUNT(*)) OVER (), 0) AS share
FROM orders
WHERE workspace_id = :workspace_id
  AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND financial_status NOT IN ('refunded','voided','cancelled')
GROUP BY payment_gateway
ORDER BY orders DESC
```

### Naming Convention Compliance (T4)
```sql
SELECT
  COUNT(CASE WHEN parsed_dimensions IS NOT NULL AND parsed_dimensions != '{}' THEN 1 END)::numeric
  / NULLIF(COUNT(*), 0) * 100 AS compliance_pct,
  COUNT(*) AS total_ads,
  COUNT(CASE WHEN parsed_dimensions IS NULL OR parsed_dimensions = '{}' THEN 1 END) AS non_compliant
FROM ads
WHERE workspace_id = :workspace_id AND status = 'active'
```

---

## 33. ComputeVelocityJob (batch strategy)

Runs daily at 02:30. Updates ALL variants in workspace at once (not per-variant N+1):

```sql
WITH velocity AS (
  SELECT oli.variant_id,
    SUM(oli.quantity)::numeric / GREATEST(1, LEAST(28,
      EXTRACT(EPOCH FROM NOW() - MIN(o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)) / 86400
    )) AS velocity_28d,
    COUNT(DISTINCT (o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date) AS sale_days
  FROM order_line_items oli
  JOIN orders o ON o.id = oli.order_id
  WHERE o.workspace_id = :workspace_id
    AND o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz >= NOW() AT TIME ZONE :tz - INTERVAL '28 days'
    AND o.financial_status NOT IN ('refunded','voided','cancelled')
    AND oli.quantity > 0
  GROUP BY oli.variant_id
)
-- No velocity column on product_variants — pre-compute and cache in Redis (see below)
-- This job could pre-compute into a cache or materialized view if needed
SELECT v.variant_id, v.velocity_28d, v.sale_days,
  pv.inventory_quantity / NULLIF(v.velocity_28d, 0) AS days_of_stock,
  CURRENT_DATE + LEAST(pv.inventory_quantity / NULLIF(v.velocity_28d, 0), 3650)::int AS stock_out_date
FROM velocity v
JOIN product_variants pv ON pv.id = v.variant_id
```

Result cached in Redis: `workspace:{id}:velocity:{variant_id}`. TTL: 24h. Inventory page queries this cache.

---

## 34. BuildCohortSnapshotJob (pre-computation)

Runs daily at 03:00. Inserts into `daily_snapshot_cohorts` for fast unfiltered cohort loads:

```sql
INSERT INTO daily_snapshot_cohorts (workspace_id, cohort_period, period_offset, customers_active, revenue, orders_count, built_at)
SELECT
  :workspace_id,
  DATE_TRUNC('month', c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date AS cohort_period,
  ((EXTRACT(YEAR FROM o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz) - EXTRACT(YEAR FROM c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)) * 12
    + EXTRACT(MONTH FROM o.created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz) - EXTRACT(MONTH FROM c.first_order_at AT TIME ZONE 'UTC' AT TIME ZONE :tz))::int AS period_offset,
  COUNT(DISTINCT c.id),
  SUM(COALESCE(o.net_revenue_converted, o.net_revenue)),
  COUNT(o.id),
  NOW()
FROM customers c
JOIN orders o ON o.customer_id = c.id AND o.workspace_id = c.workspace_id
WHERE c.workspace_id = :workspace_id
  AND o.financial_status NOT IN ('refunded','voided','cancelled')
  AND o.created_at >= c.first_order_at
GROUP BY 1, 2, 3
ON CONFLICT (workspace_id, cohort_period, period_offset)
DO UPDATE SET customers_active = EXCLUDED.customers_active, revenue = EXCLUDED.revenue,
  orders_count = EXCLUDED.orders_count, built_at = NOW()
```

Filtered cohort queries (by channel/product/country) run live against orders table, not this pre-computed table.

---

## 35. Discount Code Analysis

```sql
SELECT dc->>'code' AS code, COUNT(*) AS uses,
  AVG(total_price_converted) AS avg_order_value,
  SUM(COALESCE(net_revenue_converted, net_revenue)) AS total_revenue,
  AVG(contribution_margin) AS avg_margin
FROM orders, jsonb_array_elements(COALESCE(discount_codes, '[]'::jsonb)) dc
WHERE workspace_id = :workspace_id AND (created_at AT TIME ZONE 'UTC' AT TIME ZONE :tz)::date BETWEEN :start AND :end
  AND financial_status NOT IN ('refunded','voided','cancelled')
  AND jsonb_typeof(discount_codes) = 'array' AND jsonb_array_length(discount_codes) > 0
  AND total_price_converted IS NOT NULL
GROUP BY dc->>'code'
ORDER BY total_revenue DESC
```

---

## 36. Holiday Calendar & Seed Data

Seed 25 ecommerce events on deployment. `workspace_holidays` pivot tracks watched events. `SendHolidayNotificationsJob` checks 7d + 1d before watched holidays.

**Events to seed:** New Year's Sales (Jan 1), Chinese New Year (dynamic, lunar), Valentine's Day (Feb 14), Super Bowl Sunday (1st Sun Feb, US), Int'l Women's Day (Mar 8), Mother's Day (2nd Sun May), Memorial Day (last Mon May, US), Father's Day (3rd Sun Jun), Summer Sales (late Jun, EU), Amazon Prime Day (mid-Jul), Back to School (Aug-Sep), Labour Day (1st Mon Sep, US), Golden Week (Oct 1-7, CN), Halloween (Oct 31), Singles Day (Nov 11), Diwali (Oct-Nov, IN), Black Friday (4th Fri Nov), Small Business Saturday (Sat after Thanksgiving, US), Cyber Monday (Mon after Thanksgiving), Free Shipping Day (~Dec 14, US), Boxing Day (Dec 26, UK/CA/AU), Christmas (Dec 25), 12.12 Sale (Dec 12, SEA), El Buen Fin (3rd weekend Nov, MX), Click Frenzy (mid-Nov, AU).

Dynamic dates computed per year in seeder. BFCM dates used by `DateRange::fromRequest()`.

**Statutory holidays:** Weekly `SyncStatutoryHolidaysJob` fetches public holidays per workspace country from Nager.Date API (free, 100+ countries, no API key). Store in `holidays` table with `type = 'statutory'`. Ecommerce events use `type = 'ecommerce'`.

**Global settings to seed** (`global_settings` table):
`attribution.default_model` = `last_click`, `cogs.warning_threshold_pct` = `20`, `creative.fatigue_ctr_drop_pct` = `20`, `creative.fatigue_frequency_increase_pct` = `30`, `creative.fatigue_frequency_cap` = `3.0`, `creative.fatigue_impression_min` = `1000`, `rfm.minimum_customers` = `100`, `rfm.simplified_tier_min` = `20`.

**GA4 country mapping:** Use `league/iso3166` package (no seeder needed): `(new ISO3166)->name('United States')['alpha2']` → `US`.

---

## 37. Forecasting (MVP)

Simple linear projection: `projected_month_end = (month_to_date_revenue / NULLIF(days_elapsed, 0)) * days_in_month`. Guard: if `days_elapsed = 0` (midnight on day 1), show "—" instead of projecting. Goal progress = actual / target. Seasonal (YoY factor): v2.

Surfaces on: Dashboard (today-so-far widget), Products/Inventory (velocity forecast), Profit/P&L (target pacing).

---

## 38. Export

`POST /{workspace}/api/export` → queues `ExportJob` → stores file in S3 → returns download URL via polling. Row limit: 100K. Available on: orders, products, campaigns. Uses `spatie/simple-excel` (OpenSpout) for streaming rows with ~3MB memory.

---

## 39. Anomaly Detection

`DispatchAnomalyDetectionJobs` runs hourly (after snapshot corrections when both fire at 06:30). It fans out `DetectAnomaliesForWorkspaceJob` per active workspace. Each per-workspace job then iterates `alert_rules` for that workspace.

**Algorithm:** Compare current value to 7-day trailing average. Alert when deviation exceeds threshold:
```
For each enabled alert_rule:
  current = metric value for today (or latest snapshot)
  baseline = AVG(metric) over prior 7 days
  
  condition=above:     alert if current > threshold
  condition=below:     alert if current < threshold
  condition=change_pct: alert if ABS((current - baseline) / NULLIF(baseline, 0)) * 100 > threshold
```

**Data sources per metric:** `revenue/orders/ad_spend/roas/cac/cvr` → daily_snapshots. `stock_days` → product_variants + velocity computation. `speed_score` → page_speeds. `conversion_rate` → daily_snapshots (orders/sessions).

**Additional alert types** (generated by `DetectAnomaliesForWorkspaceJob` alongside metric anomalies):
- `rfm_migration`: Before RFM recomputation (02:15), snapshot each customer's current segment. After recomputation, compare. Alert when ≥ 5 customers moved from a high-value segment (Champions/Loyal) to a low-value segment (At Risk/Hibernating) in a single day.
- `speed_drop`: After SyncPageSpeedJob (weekly), compare new scores to previous week. Alert when any CWV score drops by > 10 points.
- `low_stock`: After ComputeVelocityJob (02:30), alert when any active variant has `days_of_stock < 7` and `velocity_28d > 0`.
- `source_disagreement`: Compare store-side order count vs platform-reported conversions per ad account for the same day. Alert when discrepancy > 20%: `ABS(store_conversions - platform_conversions) / NULLIF(GREATEST(store_conversions, platform_conversions), 0) > 0.20`.

### Alert State Machine

```
                  ┌─ acknowledge ─→ ACKNOWLEDGED (acknowledged_at = NOW())
UNREAD ──────────┤
(acknowledged_at  └─ snooze(until) → SNOOZED (snoozed_until = :until)
 IS NULL)                                │
                                         │  when NOW() > snoozed_until
                                         └─→ UNREAD (snoozed_until = NULL)
```

- **Unread**: `acknowledged_at IS NULL AND (snoozed_until IS NULL OR snoozed_until < NOW())`
- **Snoozed**: `snoozed_until IS NOT NULL AND snoozed_until >= NOW()`
- **Acknowledged**: `acknowledged_at IS NOT NULL`
- Acknowledged alerts never revert. Snoozed alerts auto-revert when snooze expires.
- `PUT /alerts/{id}/acknowledge`: sets `acknowledged_at = NOW()`, clears `snoozed_until`
- `PUT /alerts/{id}/snooze`: sets `snoozed_until = :until` (max 30 days). Validation: `after:now|before:` + 30 days.
- Alert polling: frontend calls `router.reload({ only: ['unreadAlerts'] })` every 60 seconds.
- Alert page: paginated list sorted by severity DESC, created_at DESC. Filters: type, severity, unread/all.

### Email Alert Delivery

When `alert_rules.channel = 'email'` and alert fires:
```php
// In DetectAnomaliesForWorkspaceJob, after creating alert:
if ($rule->channel === 'email') {
    Mail::to($workspace->owner)->send(new AlertNotification($alert));
}
```
Email template (Blade, plain — no heavy HTML):
```
Subject: [Nexstage] {severity}: {title}
Body:
  {title}
  {body}
  Current value: {metric_value} | Threshold: {metric_threshold}
  [View in Nexstage →]({deep_link})
```

### Digest Format

`SendDigestForWorkspaceJob` runs hourly. For each `digest_schedule` where `time` matches current hour in schedule's `timezone`:
```php
// Match: Carbon::now($schedule->timezone)->format('H:i') === $schedule->time
// Weekly: also check dayOfWeekIso === $schedule->day_of_week
```

**Digest content** — for each metric in `digest_schedules.metrics` JSONB array:
```sql
-- Yesterday's value (daily) or last 7 days sum (weekly)
SELECT
  SUM(net_revenue) AS revenue,
  SUM(orders_count) AS orders,
  SUM(ad_spend_total) AS ad_spend,
  SUM(net_revenue) - SUM(cogs_total) - SUM(shipping_cost) - SUM(payment_fees)
    - SUM(handling_costs) - SUM(return_shipping_costs) - SUM(ad_spend_total) AS net_profit,
  SUM(net_revenue) / NULLIF(SUM(ad_spend_total), 0) AS roas
FROM daily_snapshots
WHERE workspace_id = :workspace_id AND store_id IS NULL
  AND date BETWEEN :period_start AND :period_end
```
Compare against same period prior (daily: day before yesterday; weekly: prior 7 days). Include delta %.

Email template (Blade):
```
Subject: [Nexstage] {frequency} Digest — {workspace_name} ({period})
Body:
  {workspace_name} — {period_label}
  ┌─────────────┬──────────┬─────────┐
  │ Metric      │ Value    │ Δ       │
  ├─────────────┼──────────┼─────────┤
  │ Revenue     │ €12,340  │ +8.2%   │
  │ Orders      │ 156      │ +12.1%  │
  │ Net Profit  │ €3,420   │ -2.3%   │
  │ ...         │          │         │
  └─────────────┴──────────┴─────────┘
  [Open Dashboard →]({workspace_url}/dashboard)
```

### Transactional Emails

All emails use plain Blade templates (no heavy HTML). Laravel Breeze handles password reset and email verification out of the box.

| Mailable | Trigger | Subject | Body |
|----------|---------|---------|------|
| `WorkspaceInvitation` | `POST /workspace/invitations` | `[Nexstage] {inviter} invited you to {workspace}` | Invitation details + `[Accept →]` link + "expires in 7 days" |
| `ImportCompleteNotification` | `InitialImportCompleted` event | `[Nexstage] {store_name} import complete` | Orders/products/customers counts + `[View Dashboard →]` link |
| `TrialExpiringNotification` | 3 days before `trial_ends_at` | `[Nexstage] Your trial ends in 3 days` | Usage summary + `[Subscribe →]` link |
| `SubscriptionCanceledNotification` | Stripe `customer.subscription.deleted` | `[Nexstage] Subscription canceled` | 30-day grace period notice + `[Resubscribe →]` link |

### Events & Listeners (consolidated)

| Event | Payload | Listeners | Queue |
|-------|---------|-----------|-------|
| `OrderSynced` | `Order $order` | `UpdateCustomerStatsListener`, `ClassifyChannelListener`, `MatchCampaignListener`, `ComputeOrderCogsListener` | `default` (sync during webhook, queued during import) |
| `InitialImportCompleted` | `Store $store` | `FixIsNewCustomerListener`, `FixFirstOrderAtListener`, `BuildSnapshotsListener`, `ProcessHeldWebhooksListener`, `SendImportCompleteEmailListener` | `snapshots` |
| `CogsUpdated` | `Workspace $workspace, ?DateRange $range` | `BackfillCogsOnOrdersListener`, `RebuildSnapshotsListener` | `snapshots` |
| `AlertFired` | `Alert $alert, AlertRule $rule` | `SendInAppNotificationListener`, `SendEmailNotificationListener` | `default` |
| `StoreDisconnected` | `Store $store` | `PauseSyncJobsListener`, `DeregisterWebhooksListener` | `default` |
| `SubscriptionChanged` | `Workspace $workspace, string $newStatus` | `UpdateSyncsPausedAtListener`, `SendSubscriptionEmailListener` | `default` |

All listeners implement `ShouldQueue` unless noted. `OrderSynced` listeners run synchronously during webhook processing (single order) and queued during bulk import.

---

## 39b. Segment Push to Klaviyo / Meta

`POST /segments/{id}/push` — throttled at 5/min. Requires `manage-data` gate.

**Klaviyo push** (full-replace strategy):
```
1. Create or update a Klaviyo list: POST /api/lists { name: "Nexstage: {segment_name}" }
2. Fetch all segment member emails from customers table
3. Batch add profiles to list: POST /api/lists/{list_id}/relationships/profiles
   - Batch size: 100 profiles per request (Klaviyo limit)
   - Rate: 75 requests/min (Klaviyo Revision 2025-04-15 limit)
4. Full replace: remove profiles NOT in current segment, add new ones
```

**Meta Custom Audiences push** (requires Marketing API access):
```
1. Create or update Custom Audience: POST /act_{ad_account_id}/customaudiences
   { name: "Nexstage: {segment_name}", subtype: "CUSTOM", customer_file_source: "USER_PROVIDED_ONLY" }
2. Hash customer emails with SHA-256 (Meta requirement)
3. Batch upload: POST /{audience_id}/users
   { schema: ["EMAIL"], data: [["hash1"], ["hash2"], ...] }
   - Batch size: 10,000 per request
4. Full replace: POST /{audience_id}/usersreplace (replaces entire audience)
```

Store Klaviyo list ID or Meta audience ID in `customer_segments.sync_destination_id` for update-in-place on subsequent pushes. Set `last_synced_at` on completion.

---

## 40. Reconciliation & Maintenance Jobs

**ReconcileStoreOrdersJob** (daily 01:30): Re-fetch orders updated in last 7 days from store API. Compare `platform_updated_at` with our `synced_at`. If store version is newer, re-sync. Catches missed webhooks and edits made in store admin.

**RefreshOAuthTokensJob** (daily 05:00): Query all integration tables for tokens expiring within 48 hours (`token_expires_at < NOW() + INTERVAL '48 hours'`). Refresh each. Log failures as `system_alert` with severity `critical`.

**BuildCohortSnapshotJob** (daily 03:00): Pre-computes `daily_snapshot_cohorts` table for fast unfiltered cohort heatmap loads. Filtered queries (by channel/product/etc.) run live against orders table. Pre-computed table is the default; live query triggered when filters active.

**SendDailyDigestJob** (hourly): Checks `digest_schedules` for entries where `time` matches current hour in user's `timezone`. Builds digest from `daily_snapshots` for yesterday (or last week for weekly). Delivers via email. Content: the metrics selected in `digest_schedules.metrics` JSONB array.

---

## 41. Platform Fee Rules Management

`platform_fee_rules` table stores flat monthly fees and percentage-of-revenue fees. Managed in Settings → Costs tab alongside shipping rules and OPEX.

Prorated daily in P&L queries:
- `fee_type=percentage`: `revenue * percentage / 100` per day
- `fee_type=flat_monthly`: `flat_amount / days_in_month` per day

No scheduled job — computed at query time like OPEX. Uses `generate_series` + CROSS JOIN for multi-month ranges. Amounts converted from `pfr.currency` to workspace reporting currency:
```sql
WITH months AS (
  SELECT generate_series(
    DATE_TRUNC('month', :start::date),
    DATE_TRUNC('month', :end::date),
    '1 month'::interval
  )::date AS month_start
),
pct_revenue AS (
  SELECT pfr.id AS rule_id,
    COALESCE(SUM(ds.net_revenue), 0) AS store_revenue
  FROM platform_fee_rules pfr
  LEFT JOIN daily_snapshots ds ON ds.workspace_id = :workspace_id
    AND ds.date BETWEEN :start AND :end
    AND ((pfr.store_id IS NULL AND ds.store_id IS NULL) OR ds.store_id = pfr.store_id)
  WHERE pfr.workspace_id = :workspace_id AND pfr.fee_type = 'percentage'
  GROUP BY pfr.id
)
SELECT COALESCE(pct_total, 0) + COALESCE(flat_total, 0) AS total_platform_fees
FROM (
  -- Percentage fees: one computation per rule (no monthly iteration)
  SELECT SUM(pr.store_revenue * pfr.percentage / 100 * COALESCE(fx.rate, 1)) AS pct_total
  FROM platform_fee_rules pfr
  JOIN pct_revenue pr ON pr.rule_id = pfr.id
  LEFT JOIN LATERAL (
    SELECT rate FROM fx_rates
    WHERE base_currency = pfr.currency AND target_currency = :reporting_currency
      AND date <= :start ORDER BY date DESC LIMIT 1
  ) fx ON pfr.currency != :reporting_currency
  WHERE pfr.workspace_id = :workspace_id AND pfr.fee_type = 'percentage'
) pct
CROSS JOIN (
  -- Flat monthly fees: prorate per month
  SELECT SUM(pfr.flat_amount * (
    LEAST(:end::date, (m.month_start + '1 month'::interval - '1 day'::interval)::date)
    - GREATEST(:start::date, m.month_start) + 1
  )::numeric / ((m.month_start + '1 month'::interval)::date - m.month_start)
  * COALESCE(fx.rate, 1)) AS flat_total
  FROM platform_fee_rules pfr
  CROSS JOIN months m
  LEFT JOIN LATERAL (
    SELECT rate FROM fx_rates
    WHERE base_currency = pfr.currency AND target_currency = :reporting_currency
      AND date <= m.month_start ORDER BY date DESC LIMIT 1
  ) fx ON pfr.currency != :reporting_currency
  WHERE pfr.workspace_id = :workspace_id AND pfr.fee_type = 'flat_monthly'
) flat
```

---

## 42. Infrastructure Setup

**Docker services needed:**
- `app` (php) — PHP 8.5 + Laravel (Vite runs on host with Node 22+)
- `postgres` — PostgreSQL 18
- `redis` — Redis (dbs 0/1/2 for cache/queue/sessions)
- `horizon` — Same PHP image, runs `php artisan horizon`
- `scheduler` — Same PHP image, runs `php artisan schedule:work`
- `mailpit` — Dev-only email testing (catches all outbound mail)

**PrimeVue setup (Tailwind 4 compatible):**
```bash
npm install primevue @primeuix/themes tailwindcss-primeui
```

In `app.js`:
```js
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';

// Inside createInertiaApp setup:
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: { cssLayer: { name: 'primevue', order: 'theme, base, primevue' } },
    },
});
```

In `app.css`:
```css
@import "tailwindcss";
@import "tailwindcss-primeui";
```

The `cssLayer` config ensures PrimeVue sits below Tailwind utilities in specificity. Import components directly (tree-shaken): `import DataTable from 'primevue/datatable'`.

---

## 43. Middleware Groups

```php
// Workspace-scoped web routes (Inertia pages)
Route::middleware(['web', 'auth', 'verified', 'workspace'])->prefix('{workspace:slug}')->group(...);

// Workspace-scoped API routes (JSON, lazy-loaded data)
Route::middleware(['web', 'auth', 'verified', 'workspace', 'throttle:120,1'])->prefix('{workspace:slug}/api')->group(...);

// Pre-workspace routes (workspace creation, invitation acceptance)
Route::middleware(['web', 'auth', 'verified', 'throttle:10,1'])->group(...);

// OAuth callbacks — user-initiated flows need auth; Shopify install does not
Route::middleware(['web', 'auth', 'throttle:10,1'])->group(...);  // Facebook/Google/Klaviyo callbacks
Route::middleware(['web', 'throttle:10,1'])->group(...);          // Shopify install/callback (Shopify-initiated)

// Shopify/WC webhooks (NO web middleware — no CSRF, no session)
Route::middleware(['verify-webhook-hmac'])->prefix('webhooks')->group(...);

// Shopify GDPR webhooks (same: no CSRF)
Route::middleware(['verify-webhook-hmac'])->prefix('shopify/webhooks')->group(...);

// Stripe webhook — single endpoint via Laravel Cashier (see section 24)
Route::post('/stripe/webhook', CashierWebhookController::class)->middleware(VerifyWebhookSignature::class);
```

`workspace` middleware = `SetActiveWorkspace` (resolves slug, checks membership, sets WorkspaceContext).

**Per-endpoint throttle overrides** (override the generic 120/min):
- `POST /api/export`: `throttle:5,1`
- `POST /api/segments/{id}/push`: `throttle:5,1`
- `POST /api/cogs/upload`: `throttle:5,1`
- `POST /api/shared-links`: `throttle:10,1`

### Authorization Gates

Define in `AuthServiceProvider`:
```php
Gate::define('manage-workspace', fn (User $user) =>
    workspace_role($user) === 'owner');

Gate::define('manage-settings', fn (User $user) =>
    in_array(workspace_role($user), ['owner', 'admin']));

Gate::define('manage-data', fn (User $user) =>
    in_array(workspace_role($user), ['owner', 'admin']));

// Capability flags — enforced for members only (owner/admin always pass)
Gate::define('access-financials', fn (User $user) =>
    in_array(workspace_role($user), ['owner', 'admin']) || workspace_pivot($user)->can_access_financials);

Gate::define('access-pii', fn (User $user) =>
    in_array(workspace_role($user), ['owner', 'admin']) || workspace_pivot($user)->can_access_pii);

Gate::define('access-settings', fn (User $user) =>
    in_array(workspace_role($user), ['owner', 'admin']) || workspace_pivot($user)->can_access_settings);

Gate::define('manage-members', fn (User $user) =>
    workspace_role($user) === 'owner' || (workspace_role($user) === 'admin')
    || workspace_pivot($user)->can_manage_members);
```

**Role-based gates (action control):**

| Gate | Allows | Endpoints |
|------|--------|-----------|
| `manage-workspace` | owner only | `PUT /workspace`, `DELETE /workspace`, `PUT /workspace/owner` |
| `manage-settings` | owner + admin | `PUT /products/*/cogs`, `POST /cogs/upload`, `POST|PUT|DELETE /shipping-rules/*`, `POST|PUT|DELETE /operational-costs/*`, `POST|PUT|DELETE /platform-fee-rules/*`, `POST|PUT|DELETE /channel-mappings/*`, `DELETE /integrations/*` |
| `manage-data` | owner + admin | `POST|PUT|DELETE /alert-rules/*`, `POST|PUT|DELETE /segments/*`, `POST /segments/*/push`, `POST|PUT|DELETE /digest-schedules/*`. Members: edit/delete own annotations and saved views only |
| `manage-members` | owner + admin + flagged members | `POST /workspace/invitations`, `DELETE /workspace/invitations/*`, `DELETE /workspace/members/*` |
| _(no gate)_ | all roles | read accessible data, create own annotations/saved views, acknowledge/snooze own alerts, watch/unwatch holidays |

**Capability gates (data visibility — member role only, owner/admin always pass):**

| Gate | Controls | Pages/sections hidden when denied |
|------|----------|----------------------------------|
| `access-financials` | COGS, profit, margins, cost columns | Profit page, Net Profit KPI, margin/cost columns in Products/Orders tables, COGS config in Settings |
| `access-pii` | Customer emails, names, addresses | Customers page, customer columns in Orders table, order detail drawer PII fields |
| `access-settings` | Integrations, workspace config | Settings page (except Team section if `manage-members` is granted) |

**Navigation hiding:** Sidebar items for restricted pages are omitted server-side via Inertia shared data (see `permissions` prop below). Backend middleware enforces the same gates on every route — hiding is UX, not security.

**Admin configures member flags:** Settings > Team > click member row > toggle checkboxes. `PUT /workspace/members/{user}` endpoint (requires `manage-members` gate):
```php
['can_access_financials' => 'boolean', 'can_access_pii' => 'boolean',
 'can_access_settings' => 'boolean', 'can_manage_members' => 'boolean']
```

Every route-model-bound resource MUST verify workspace ownership:
```php
// In controller or policy — never trust route model binding alone
abort_unless($model->workspace_id === workspace()->id, 404);
```

**User-owned resources** (annotations, saved views, exports) also need ownership checks for non-admin users:
```php
// In UpdateAnnotationController, DeleteAnnotationController, Update/DeleteSavedViewController
abort_unless($model->user_id === auth()->id() || Gate::allows('manage-data'), 403);

// In ExportStatusController — always check user ownership (exports contain sensitive data)
abort_unless($export->user_id === auth()->id(), 404);
```

**Destructive operations** require additional guards:
```php
// In RemoveWorkspaceMemberController — prevent ownerless workspace
abort_if($user->id === auth()->id(), 422, 'Cannot remove yourself.');

// In TransferOwnershipController — require password re-entry
$request->validate(['password' => 'required|current_password']);
```

### OAuth State Signing

OAuth `state` parameter MUST be HMAC-signed to prevent workspace ID tampering:
```php
// In redirect controller — store nonce in session for single-use enforcement
$nonce = Str::random(40);
session(['oauth_nonce' => $nonce]);
$payload = json_encode(['workspace_id' => workspace()->id, 'nonce' => $nonce]);
$state = base64_encode($payload) . '.' . hash_hmac('sha256', $payload, config('app.key'));

// In callback controller — verify HMAC, consume nonce, check workspace membership
[$payload, $signature] = explode('.', $state, 2);
abort_unless(hash_equals(hash_hmac('sha256', $payload, config('app.key')), $signature), 403);
$data = json_decode(base64_decode($payload), true);
abort_unless($data['nonce'] === session()->pull('oauth_nonce'), 403);  // consume nonce (single-use)
abort_unless($request->user()->workspaces()->where('workspaces.id', $data['workspace_id'])->exists(), 404);
```

---

## 44. API Endpoint Contracts

All JSON endpoints under `/{workspace}/api/` accept `?start` and `?end` query params for date range.

| Endpoint | Request | Response |
|---|---|---|
| `GET /api/import-status` | — | `{status, orders: {total, imported}, products: {...}, customers: {...}}` |
| `GET /api/campaigns/{id}/adsets` | `?start&end` | `[{id, name, status, spend, impressions, clicks, ctr, cpc, cpm, cpa, conversions, revenue, roas}]` |
| `GET /api/adsets/{id}/ads` | `?start&end` | Same columns as adsets + creative_thumbnail_url |
| `GET /api/products/{id}/variants` | `?start&end` | `[{id, title, sku, price, inventory_quantity, units_sold, revenue, cogs, margin_pct, velocity}]` |
| `PUT /api/products/{id}/cogs` | `{cost, currency?, effective_from?}` | `{success, triggers_backfill: true}` |
| `POST /api/cogs/upload` | multipart `file` (CSV) | `{imported: N, skipped: N, errors: [{row, sku, reason}]}` |
| `POST /api/annotations` | `{date, title, body?, category?}` | `{id, date, title}` |
| `PUT /api/annotations/{id}` | `{title?, body?, category?}` | `{id, date, title}` |
| `DELETE /api/annotations/{id}` | — | 204 |
| `POST /api/alert-rules` | `{metric, scope, condition, threshold, severity, channel}` | `{id}` |
| `PUT /api/alerts/{id}/acknowledge` | — | 204 |
| `PUT /api/alerts/{id}/snooze` | `{until: "2026-05-08T09:00:00Z"}` | 204 |
| `POST /api/segments/{id}/push` | `{destination: "klaviyo"\|"meta"}` | `{job_id, status: "queued"}` |
| `POST /api/export` | `{page: "orders"\|"products"\|"campaigns", columns: [...], filters: {...}}` | `{export_id, status: "queued", poll_url}` |
| `GET /api/export/{id}` | — | `{status: "processing"\|"completed"\|"failed", download_url?, error?}` |

**Validation rules (all write endpoints):**
```php
// Global date range (API endpoints with explicit dates — page controllers use DateRange::fromRequest() with preset fallback)
['start' => 'nullable|date_format:Y-m-d', 'end' => 'nullable|date_format:Y-m-d|after_or_equal:start|required_with:start']

// PUT /products/{id}/cogs
['cost' => 'required|numeric|min:0', 'currency' => 'nullable|string|size:3', 'effective_from' => 'nullable|date']

// POST /cogs/upload
['file' => 'required|file|mimes:csv,txt|max:5120']  // 5MB max

// POST /annotations
['date' => 'required|date', 'title' => 'required|string|max:255', 'body' => 'nullable|string|max:5000', 'category' => 'nullable|in:sale,campaign,price_change,mailing,other']

// POST /alert-rules
['metric' => 'required|in:revenue,orders,ad_spend,roas,cac,cvr,stock_days,speed_score', 'scope' => 'required|in:workspace,store,channel,campaign', 'scope_id' => 'nullable|required_unless:scope,workspace|integer', 'condition' => 'required|in:above,below,change_pct', 'threshold' => 'required|numeric', 'severity' => 'required|in:critical,warning,info', 'channel' => 'required|in:in_app,email']  // slack: v2 — add to validation when Slack integration is built
// When scope != 'workspace': validate scope_id exists in workspace-scoped table (stores, ad_campaigns)

// PUT /alerts/{id}/snooze
['until' => 'required|date|after:now']

// POST /segments
['name' => 'required|string|max:100', 'rules' => 'required|array|min:1',
 'rules.*.field' => 'required|in:orders_count,total_spent,rfm_segment,first_order_at,last_order_at,country,channel',
 'rules.*.operator' => 'required|in:gt,gte,lt,lte,eq,in,not_in,between',
 'rules.*.value' => 'required'] // Per-operator value validation in controller:
// gt/gte/lt/lte → numeric. eq → string|numeric. in/not_in → array|max:100. between → array|size:2.
// Field-operator compat: country/channel/rfm_segment → eq/in/not_in only. Numeric fields → all ops.

// POST /segments/{id}/push
['destination' => 'required|in:klaviyo,meta']

// POST /export
['page' => 'required|in:orders,products,campaigns', 'columns' => 'required|array|min:1',
 'columns.*' => Rule::in(config("export.columns.{$request->page}")),
 'filters' => 'nullable|array', 'filters.*' => 'string|max:255']
// Column whitelists per page (enforce via Rule::in, never interpolate into SQL):
// orders: order_number,created_at,financial_status,fulfillment_status,customer_email,revenue,cogs_total,shipping_cost,transaction_fee,net_revenue,channel,discount_codes,country
// products: title,vendor,product_type,units_sold,revenue,cogs,contribution_profit,margin_pct,refund_rate,min_stock,ad_spend
// campaigns: name,platform,spend,impressions,clicks,ctr,cpc,cpm,conversions,revenue,roas

// POST /saved-views
['page' => 'required|in:orders,products,campaigns,customers', 'name' => 'required|string|max:100',
 'filters' => 'nullable|array', 'columns' => 'nullable|array', 'sort' => 'nullable|array', 'is_pinned' => 'boolean']

// POST /shared-links
['page' => 'required|string|max:50', 'is_live' => 'boolean',
 'expires_at' => 'nullable|required_if:is_live,true|date|after:now|before:' . now()->addDays(30)->toDateString()]

// PUT /workspace
['name' => 'string|max:255', 'reporting_currency' => 'string|size:3|in:EUR,USD,GBP,CHF,SEK,NOK,DKK,PLN,CZK,HUF,RON,BGN,HRK,ISK,TRY,AUD,CAD,NZD,JPY,CNY,HKD,SGD,KRW,THB,INR,BRL,MXN,ZAR,ILS',
 'reporting_timezone' => 'timezone:all',
 'default_cogs_margin_pct' => 'nullable|numeric|min:0|max:100', 'attribution_model' => 'in:last_click,first_click,linear',
 'attribution_window_days' => 'in:7,14,30', 'brand_keywords' => 'nullable|array', 'naming_delimiter' => 'nullable|string|max:3',
 'naming_dimensions' => 'nullable|array', 'target_roas' => 'nullable|numeric|min:0', 'target_cac' => 'nullable|numeric|min:0']

// POST /workspace/invitations
['email' => 'required|email|max:255', 'role' => 'required|in:admin,member']

// PUT /workspace/members/{user} — manage-members gate. Cannot change own role. Cannot promote to owner (use transfer endpoint).
['role' => 'sometimes|in:admin,member', 'can_access_financials' => 'boolean', 'can_access_pii' => 'boolean',
 'can_access_settings' => 'boolean', 'can_manage_members' => 'boolean']
// Capability flags ignored for admin/owner roles (always full access). Only applied to member role.

// POST /shipping-rules — store_id/product_id must be workspace-scoped: Rule::exists('stores','id')->where('workspace_id', workspace()->id)
['store_id' => 'nullable|exists:stores,id', 'country' => 'nullable|string|size:2', 'product_id' => 'nullable|exists:products,id',
 'cost_per_order' => 'nullable|numeric|min:0', 'cost_per_item' => 'nullable|numeric|min:0', 'additional_item_cost' => 'nullable|numeric|min:0']

// POST /operational-costs
['name' => 'required|string|max:255', 'category' => 'required|in:saas,rent,salary,marketing,other',
 'amount' => 'required|numeric|min:0', 'currency' => 'required|string|size:3', 'frequency' => 'required|in:monthly,weekly,daily,one_time',
 'starts_at' => 'required|date', 'ends_at' => 'nullable|date|after:starts_at']

// POST /platform-fee-rules — store_id must be workspace-scoped: Rule::exists('stores','id')->where('workspace_id', workspace()->id)
['store_id' => 'nullable|exists:stores,id', 'name' => 'required|string|max:100',
 'fee_type' => 'required|in:percentage,flat_monthly', 'percentage' => 'required_if:fee_type,percentage|nullable|numeric|min:0|max:100',
 'flat_amount' => 'required_if:fee_type,flat_monthly|nullable|numeric|min:0', 'currency' => 'required|string|size:3']

// POST /channel-mappings
['priority' => 'required|integer|min:1|max:999', 'utm_source' => 'nullable|string|max:100', 'utm_medium' => 'nullable|string|max:100',
 'utm_campaign_pattern' => 'nullable|string|max:255', 'referring_site_pattern' => 'nullable|string|max:255',
 'channel' => 'required|in:paid_search,paid_social,paid_video,paid_shopping,cross_network,display,email,sms,affiliate,mobile_push,organic_search,organic_social,organic_video,organic_shopping,referral,direct,unassigned']

// POST /digest-schedules
['frequency' => 'required|in:daily,weekly', 'day_of_week' => 'nullable|required_if:frequency,weekly|integer|min:1|max:7',
 'time' => 'required|date_format:H:i', 'timezone' => 'required|timezone:all', 'delivery_channel' => 'required|in:email',  // slack: v2
 'slack_webhook_url' => 'nullable|required_if:delivery_channel,slack|url|max:500', 'metrics' => 'required|array|min:1',
 'metrics.*' => 'in:revenue,orders,ad_spend,net_profit,roas,aov,sessions,cvr']

// POST /woocommerce/connect — credential-based (not OAuth). Route under workspace prefix (needs workspace context).
['store_url' => 'required|url|max:255', 'consumer_key' => 'required|string|max:255', 'consumer_secret' => 'required|string|max:255']
// Controller must: (1) validate store_url is not an internal IP — resolve DNS, check against private ranges (SSRF protection),
// (2) test credentials via GET /wc/v3/system_status before persisting,
// (3) on success create stores record with access_token=consumer_key, refresh_token=consumer_secret,
// (4) exclude consumer_key/consumer_secret from request logs

// GET /api/funnel/{step}
['step' => 'required|in:landing,product_view,atc,checkout,purchase']

// POST /utm-templates
['name' => 'required|string|max:100', 'source' => 'required|string|max:100', 'medium' => 'required|string|max:100',
 'campaign_pattern' => 'nullable|string|max:255', 'content_pattern' => 'nullable|string|max:255', 'term_pattern' => 'nullable|string|max:255']

// POST /workspaces
['name' => 'required|string|max:255', 'reporting_currency' => 'required|string|size:3|in:EUR,USD,GBP,CHF,SEK,NOK,DKK,PLN,CZK,HUF,RON,BGN,HRK,ISK,TRY,AUD,CAD,NZD,JPY,CNY,HKD,SGD,KRW,THB,INR,BRL,MXN,ZAR,ILS',
 'reporting_timezone' => 'required|timezone:all']

// DELETE /workspace
['password' => 'required|current_password']  // 30-day soft delete grace period

// PUT /workspace/owner
['user_id' => 'required|integer', 'password' => 'required|current_password']
// Validate user_id is a member of the workspace via Rule::exists('workspace_users','user_id')->where('workspace_id', workspace()->id)
```

**PUT routes** use the same validation rules as their POST counterpart. Use a shared FormRequest per resource (e.g., `SaveAlertRuleRequest` for both POST and PUT).

---

## 45. Controller Props Pattern

Every page controller follows the Dashboard pattern (section 22). Props to pass:

| Page | Immediate Props | Deferred Props (`Inertia::defer()`) |
|---|---|---|
| Dashboard | `kpis`, `comparison`, `alerts` | `sparkline`, `channels`, `topProducts`, `winnersLosers`, `monthlyOverview`, `salesHeatmap`, `todaySoFar` |
| Profit | `kpis` (P&L totals, ROAS, margin) | `plTable` (period columns), `shippingCountries`, `waterfallData`, `whatIfData`, `targetPacing` |
| Marketing | `kpis` (spend, blended ROAS, MER, conversions, attributed revenue, CPA) | `campaigns`, `creatives`, `funnelSteps`, `paymentMethods` |
| Products | `kpis` (units, revenue) | `products`, `quadrantData`, `inventoryTable`, `marketBasket` |
| Orders | — | `orders` (paginated), `savedViews` |
| Customers | `kpis` (LTV, payback) | `cohortData`, `rfmGrid`, `segments`, `customerList` (paginated) |
| SEO | `kpis` (clicks, impressions, organic_revenue, avg_position) | `topQueries`, `topPages`, `revenuePerQuery`, `cannibalization` |
| Health | `kpis` (LCP, INP, CLS) | `urlScores` |
| Alerts | — | `alerts` (paginated) |
| Settings | `workspace`, `integrations` | `costConfig`, `channelMappings`, `digestSchedules` |
| Tools/Holidays | `holidays` (upcoming), `watchedIds` | — |
| Tools/UTM | `templates` | — |
| Tools/Naming | `workspace.naming_delimiter`, `workspace.naming_dimensions` | `complianceData` |
| Tools/Calculator | — (client-side only) | — |

**Calculator formulas (T3 — all computed client-side in Vue):**
```
Inputs: price, cogs, shipping_cost, fee_pct, ad_spend_per_unit, return_rate
total_cost = cogs + shipping_cost + (price * fee_pct / 100) + ad_spend_per_unit + (price * return_rate / 100)
profit_per_unit = price - total_cost
margin_pct = profit_per_unit / price * 100
breakeven_roas = price / (price - cogs - shipping_cost - (price * fee_pct / 100))
suggested_price = total_cost / (1 - target_margin_pct / 100)   [user provides target_margin_pct]
```

**Props without explicit queries (derive from context):**
- `plTable` — pivot section 1 P&L formulas over daily_snapshots grouped by time period columns (day/week/month)
- `targetPacing` — section 37 formula: `(month_to_date_revenue / days_elapsed) * days_in_month` vs `workspace.target_revenue`
- `inventoryTable` — join product_variants with Redis-cached velocity (section 33) + stock levels + days-of-stock formula (section 11)
- `rfmGrid` — `SELECT rfm_recency_score, rfm_frequency_score, COUNT(*) FROM customers WHERE workspace_id = :id GROUP BY 1, 2`
- `customerList` — paginated customers table with standard filters (segment, country, orders_count range)
- `topQueries` / `topPages` — `SELECT query|page_path, SUM(clicks), SUM(impressions), AVG(position) FROM gsc_daily GROUP BY 1 ORDER BY SUM(clicks) DESC`
- `alerts` — `SELECT * FROM alerts WHERE workspace_id = :id ORDER BY severity, created_at DESC` with acknowledge/snooze filters
- Dashboard goal progress — `workspace.target_revenue` vs actual MTD revenue from daily_snapshots

**Helpers all controllers need:**
- `ratioOrNull($numerator, $denominator)` — returns `$denominator > 0 ? $numerator / $denominator : null`. Use for AOV, ROAS, MER, CVR, CPA, Net Margin.

**Prop shape notes (all deferred props are arrays/objects — paginated props use Laravel's built-in `->paginate()` which Inertia handles natively):**
- `costConfig` — `{shippingRules: ShippingRule[], operationalCosts: OperationalCost[], platformFeeRules: PlatformFeeRule[], defaultCogsMarginPct: ?float}`
- `channelMappings` — `ChannelMapping[]` (id, priority, utm_source, utm_medium, utm_campaign_pattern, referring_site_pattern, channel)
- `digestSchedules` — `DigestSchedule[]` (id, name, frequency, delivery_channel, metrics, recipients)
- `segments` — `{id, name, rules, customer_count, avg_ltv, avg_aov}[]`
- `savedViews` — `{id, name, filters, columns, sort, is_pinned}[]`
- `integrations` — `{stores: Store[], adAccounts: AdAccount[], analyticsProperties: [], searchProperties: [], emailAccounts: []}` each with: id, name, platform, sync_status, last_synced_at
- `cohortData` — `{matrix: {cohort_month, period_offset, value}[], metrics: string[], cohort_sizes: {month, size}[]}`
- `funnelSteps` — `{label, value, drop_off_pct}[]` (5 steps: landing → product_view → add_to_cart → checkout → purchase)
- `targetPacing` — `{projected, actual, target, days_elapsed, days_remaining, on_track: bool}`
- `whatIfData` — `{countries: CountryRow[], aov_distribution: {bucket, count}[]}`
- `complianceData` — `{compliance_pct, total_ads, non_compliant_count, samples: {id, name}[]}`

**Inertia shared data** (via `HandleInertiaRequests` middleware — available on ALL pages):
- `auth.user` — id, name, email
- `workspace` — id, name, slug, reporting_currency, reporting_timezone, attribution_model, attribution_window_days, naming_delimiter, naming_dimensions, brand_keywords, target_roas, target_cac, target_revenue, onboarding_checklist, plan
- `permissions` — {role, canAccessFinancials, canAccessPii, canAccessSettings, canManageMembers, canManageWorkspace, canManageSettings, canManageData}. Frontend uses this to hide sidebar items, table columns (margin/cost when !canAccessFinancials), and action buttons. See non-obvious-issues.md #28.
- `dateRange` — {start, end, comparison_start, comparison_end, preset, granularity, comparison_enabled}
- `flash` — success/error messages
