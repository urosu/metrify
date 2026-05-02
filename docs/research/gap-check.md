# Comprehensive Page Gap Check

Research conducted 2026-04-30. Searched broadly across 2025-2026 ecommerce analytics tools to find features we may be missing on each page.

---

## Dashboard / Home — Gaps Found

**Worth adding to MVP:**
- **Return/refund rate** — avg ecommerce return rate is ~20% in 2026. Surface as a KPI card.
- **New vs returning customer revenue split** — standard in Triple Whale, Shopify native, Peel. Already partially planned but should be a first-class KPI card, not just a sub-label.
- **Contribution margin per order** — leading tools show margin-per-order, not just revenue. This is the "revenue dashboard → profitability dashboard" shift.

**Worth adding post-MVP:**
- LTV/CAC payback tracker as dashboard widget
- Peer benchmarking badges (requires user base)
- Push notifications / mobile alerts
- "What-if" scenario widget (MMM-based)

---

## Profit & Loss — Gaps Found

**Worth adding to MVP:**
- **Return/refund costs as separate line item** — return shipping, restocking, return fraud losses. Separate from revenue adjustments.
- **Transaction/payment processing fees** — Stripe/PayPal/Shopify Payments fees as their own P&L row. BeProfit and Lifetimely break these out.
- **CM1/CM2/CM3 explicit naming** — contribution margin tiers: CM1 (revenue - COGS), CM2 (- fulfillment), CM3 (- ad spend). Klar and Finaloop use this framework. Already in our plan but should be labeled explicitly.
- **Waterfall chart visualization** — interactive waterfall (revenue down to net profit) is the standard P&L viz now. Zero competitors have shipped one for ecommerce P&L though. Opportunity.
- **Multi-currency handling** — show P&L in workspace currency with FX conversion rates visible. Critical for international stores.

**Worth adding post-MVP:**
- Per-product/SKU profitability drill from P&L
- Cohort-based P&L (are January customers profitable by month 6?)
- Predictive P&L forecasting
- Custom expense categories (SaaS tools, rent, salaries)

---

## Ads / Campaigns — Gaps Found

**Worth adding to MVP:**
- **Creative fatigue detection** — flag when CTR declines + frequency rises on the same ad. Simple threshold-based, no ML needed.
- **Performance Max asset group awareness** — PMax is a black box; tools like Northbeam attribute PMax conversions back to asset groups. At minimum, surface PMax as a distinct campaign type.
- **TikTok GMV Max** — TikTok's version of PMax (optimizes across organic+paid+affiliate). Treat as distinct campaign type.

**Worth adding post-MVP:**
- Incrementality testing indicators
- Cross-channel journey visualization
- Predicted ROAS / pacing alerts
- Budget optimizer with cost curves

---

## Products — Gaps Found

**Worth adding to MVP:**
- **First-purchase vs repeat-purchase product split** — which products are gateway products vs retention products. High value, simple to compute.
- **Discount code impact per product** — which products are most heavily discounted and what that does to margin.
- **Product-level new vs returning customer split** — does this product attract new customers or just retain existing?

**Worth adding post-MVP:**
- Product journey mapping (what customers buy after this product)
- Market basket / association rules (bundle suggestions)
- Product cannibalization detection

---

## Orders — Gaps Found

**Worth adding to MVP:**
- **Payment processing fee tracking** — per-order actual fees from Shopify Payments/Stripe/PayPal, not formula estimates.
- **Discount code performance** — redemption count, AOV with vs without discount, margin impact.
- **Return/refund reason categorization** — why (sizing, damaged, not as described) to feed product decisions.

**Worth adding post-MVP:**
- Subscription vs one-time order tagging
- Fraud risk signals (lightweight flags, not Sift replacement)
- Fulfillment SLA tracking (order → shipment → delivery time)

---

## Customers — Gaps Found

**Worth adding to MVP:**
- **Time between purchases / purchase latency** — average days between orders, alert when customer exceeds typical gap (churn risk).
- **Customer-level contribution margin** — profit per customer after all variable costs, not just revenue.
- **Cohort-level discount dependency** — what % of each cohort's orders used a discount code?

**Worth adding post-MVP:**
- Channel overlap / multi-touch per customer
- Win-back timing optimization
- One-time to subscriber conversion rate
- Geographic clustering with performance overlay

---

## SEO — Gaps Found

**Worth adding to MVP:**
- **Keyword cannibalization detection** — flag when multiple pages rank for same query. Show which URL Google picks.
- **Product schema / rich results eligibility** — audit which products have valid Product schema, eligible for rich results.

**Worth adding post-MVP:**
- AI visibility / GEO tracking (ChatGPT, Perplexity mentions) — growing 302% in 2025
- Share of Voice metric
- SERP feature tracking (snippets, image packs)
- Content gap analysis

---

## Inventory — Gaps Found

**Worth adding to MVP:**
- **Stockout cost estimation** — calculate lost revenue from out-of-stock periods using historical sell-through. Simple: `days_out_of_stock × avg_daily_units × margin`.
- **Inventory turnover ratio** — how many times stock turns per period.

**Worth adding post-MVP:**
- ABC/XYZ classification (revenue contribution × demand variability)
- Purchase order creation / tracking
- Supplier lead time tracking
- Bundle/kit component-level tracking
- Multi-location inventory
- Aging buckets (60/90/120+ days)

---

## Funnel — Gaps Found

**Worth adding to MVP:**
- **Mobile vs desktop funnel split** — mobile abandonment hits 85% vs ~70% desktop. Show separate funnels.
- **Payment method conversion analysis** — which payment methods convert best. Limited payment options cost 8-15% of revenue.
- **Time between steps** — median time from cart to checkout to purchase. Long gaps signal friction.

**Worth adding post-MVP:**
- Cart recovery rate (effectiveness of recovery efforts)
- Micro-conversion steps (shipping info entered, payment info entered)
- Form field friction analysis
- Exit page analysis

---

## Performance / Site Health — Gaps Found

**Worth adding to MVP:**
- **Uptime monitoring** — simple uptime % + incident log. Ping-based. Store owners lose money per minute of downtime.
- **TTFB trend line** — single number + trend chart. Synthetic check.

**Worth adding post-MVP:**
- Third-party script impact analysis (which apps slow the store)
- Broken link / 404 detection
- SSL certificate expiry warnings

---

## Tools — Gaps Found

**Worth adding to MVP:**
- **Profit/margin calculator** — input COGS, shipping, ad spend, fees → output true per-product margin. High demand.

**Worth adding post-MVP:**
- Ad budget allocator / scenario planner
- Discount/promotion impact simulator
- Tag/pixel health checker
- A/B test duration calculator
- Shipping cost estimator (compare carrier rates)

---

## Sources

- Saras Analytics, Coupler.io, Polar Analytics, Triple Whale, Northbeam, Lifetimely, BeProfit, Varos, Klar, Finaloop, Peel Insights, Glew, LayerFive, Yotpo, BigCommerce, Heatmap.com, FunnelAnalytics.co, Forstock, StoreHero, Otterly.ai, TrendTrack
