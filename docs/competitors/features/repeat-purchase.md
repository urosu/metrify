---
name: Repeat purchase
slug: repeat-purchase
purpose: Answers "what's my repeat-purchase rate and how fast does my second order land?" — the core retention KPI for SMB Shopify/Woo merchants whose unit economics depend on customers coming back.
nexstage_pages: customers, dashboard, profit
researched_on: 2026-04-28
competitors_covered: repeat-customer-insights, lifetimely, peel-insights, everhort, klaviyo, conjura, lebesgue, metorik, putler, daasity, polar-analytics, triple-whale, glew, storehero, shopify-native, woocommerce-native
sources:
  - ../competitors/repeat-customer-insights.md
  - ../competitors/lifetimely.md
  - ../competitors/peel-insights.md
  - ../competitors/everhort.md
  - ../competitors/klaviyo.md
  - ../competitors/conjura.md
  - ../competitors/lebesgue.md
  - ../competitors/metorik.md
  - ../competitors/putler.md
  - ../competitors/daasity.md
  - ../competitors/polar-analytics.md
  - ../competitors/triple-whale.md
  - ../competitors/glew.md
  - ../competitors/storehero.md
  - ../competitors/shopify-native.md
  - ../competitors/woocommerce-native.md
  - https://www.littlestreamsoftware.com/articles/measuring-how-the-products-in-the-first-order-influence-customer-repurchases/
  - https://help.useamp.com/article/682-cohort-analysis-use-cases
  - https://help.peelinsights.com/docs/rfm-analysis
  - https://help.everhort.com/article/13-cohort-retention
  - https://help.klaviyo.com/hc/en-us/articles/26685770823451
  - https://www.conjura.com/purchase-patterns-dashboard
  - https://help.metorik.com/article/177-customer-reports
---

## What is this feature

Repeat purchase is the answer to the single most-asked retention question in SMB ecommerce: "of the people who bought once, how many came back, when did they come back, and what did they buy on the second order?" For merchants whose business depends on consumables, replenishment, or subscription-adjacent products, the repeat-purchase rate (RPR) and the time-to-second-order (T2) define whether paid acquisition is rational at all — without a credible RPR/T2 view, every CAC dollar is a guess.

The gap between "having data" and "having this feature" matters here. Shopify and WooCommerce both track every order with a customer ID — the data is always there. The feature is the synthesis: turning those orders into (a) a single rate ("38% of customers ordered again"), (b) a velocity distribution (a histogram of days between purchase 1 and purchase 2), (c) a Nth-order funnel that shows what people buy on the 2nd, 3rd, 4th order, and (d) a slice of any of the above by product, channel, cohort, or discount. Native Shopify exposes "returning customer rate" as one card; native Woo doesn't compute it at all (per `competitors/woocommerce-native.md`). Every competitor in this profile makes its money on the synthesis layer.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.customer_id`, `orders.created_at`, `orders.total_price`, `orders.line_items.product_id`, `orders.line_items.variant_id`, `orders.financial_status`, `orders.refunds`, `orders.discount_codes`, `orders.source_name` / UTM tags
- **Source: Shopify / WooCommerce** — `customers.created_at` (join date), `customers.tags`, `customers.orders_count`
- **Source: Shopify / WooCommerce** — `products.id`, `products.cost` (Shopify "Cost per Item" / Woo COGS field — used by Lifetimely, Everhort, Metorik to make repeat-purchase economics margin-aware)
- **Source: Klaviyo (optional)** — `events.placed_order`, `profile.predicted_clv`, `profile.expected_next_order_date`, `profile.churn_risk_score` (only if Klaviyo connection present; per `competitors/klaviyo.md`)
- **Source: Subscription apps (Recharge, Skio, Smartrr — optional)** — recurring order events; required for separating subscription repeat from one-off repeat (per `competitors/peel-insights.md`)
- **Source: Computed** — `repeat_purchase_rate = customers_with_n_orders >= 2 / total_customers_in_cohort`
- **Source: Computed** — `time_to_second_order_days = orders[2].created_at − orders[1].created_at`, bucketed (e.g., 0-7 / 8-30 / 31-90 / 91+ days)
- **Source: Computed** — `nth_order_share = customers_who_placed_order_n / customers_who_placed_order_n-1` (Conjura's Purchase Patterns funnel)
- **Source: Computed** — `purchase_velocity = cumulative_LTV / months_since_acquisition`, fit by linear regression for forecasted LTV (Everhort)
- **Source: Computed** — first-product attribution: pivot orders by `min(orders.created_at) per customer` then group by that order's `line_items.product_id` to get per-first-product RPR + LTV (Repeat Customer Insights' First Product Analysis)
- **Source: User-input** — manual COGS overrides where store data is missing; default-margin fallback (per `competitors/lifetimely.md`, `competitors/everhort.md`)

## Data outputs (what's typically displayed)

- **KPI: Repeat purchase rate (RPR)** — `repeat_customers / total_customers`, %, vs prior period delta. Some tools split into 90d / 180d / 365d windows (Lifetimely benchmarks).
- **KPI: Time-to-second-order (T2)** — median or mean days from order 1 to order 2; sometimes shown as a single number, sometimes as a distribution.
- **KPI: Average days between orders** — at the customer level (Klaviyo's per-profile predictive panel) or at the cohort level (Daasity's "Time Between Orders" report).
- **KPI: 2nd-order conversion rate** — explicitly the funnel from "placed order 1" → "placed order 2" (Repeat Customer Insights' "1-to-2 Customer Analysis" Focus Page).
- **Distribution: Time-between-repeat-orders histogram** — x-axis = days/weeks/months between consecutive orders, y-axis = customer count (Klaviyo's "Repeat Purchase Timing" card; Metorik's "time-between-repeat-orders chart"; Lifetimely's "Time Lag Between Orders").
- **Funnel: Nth-order share** — column 1 = 100% of customers placing 1st order, column 2 = % returning for 2nd, column 3 = % for 3rd, etc. (Conjura's 5-column Purchase Patterns; Repeat Customer Insights' Order Sequencing Analysis).
- **Slice: By first product** — RPR + LTV per product that appeared in the customer's first order (Repeat Customer Insights' First Product Analysis; Lifetimely's LTV cohort filter on first-product-purchased; Peel's "Product Popularity by Order Number").
- **Slice: By cohort acquisition month** — RPR per cohort row (every cohort tool in this profile).
- **Slice: By acquisition channel / source** — RPR for customers acquired via Direct vs Email vs Paid Social etc. (Repeat Customer Insights' source_name segmentation; Conjura's New vs Existing Customers dashboard).
- **Slice: By discount code** — RPR for customers whose first order used a discount code vs not (Lifetimely cohort filter on discount).
- **Trend: RPR over time** — line chart of RPR per cohort across acquisition months (Everhort's Average LTV by Cohort with multi-line cohort gradient).

## How competitors implement this

### Repeat Customer Insights ([profile](../competitors/repeat-customer-insights.md))
- **Surface:** Sidebar > Focus Pages > "1-to-2 Customer Analysis" (dedicated 1st→2nd-order conversion view); also Sidebar > Customer Purchase Latency; also Sidebar > Order Sequencing Analysis; also Sidebar > Product Analysis > First Product.
- **Visualization:** Combination — (a) a problem-named "Focus Page" dashboard for 1-to-2 conversion, (b) an interval distribution for purchase latency, (c) per-order-position playback report ("playback of past orders" across order #1, #2, #3...), (d) a tabular First Product Analysis with RPR + Total LTV per product row.
- **Layout (prose):** "1-to-2 Customer Analysis" is a problem-scoped dashboard rather than a generic report — UI internals not published. First Product Analysis: each row is a product, two headline metrics per row: Repeat Purchase Rate and Total LTV, e.g., "10 customers ordered a red shirt at $10 each. Then 5 of those customers came back and bought something else for $20 each — 50% Repeat Purchase Rate and $200 Total LTV." Order Sequencing tracks behavior shifts across order positions, gated 2 yrs (Entrepreneur) / 5 yrs (Growth) / full history (Peak).
- **Specific UI:** "If you then click on that segment name, you'll see details about that segment as well as advice on how to market to them" — segment cells are click-through with prescriptive marketing advice attached. UI details for the 1-to-2 page itself are not available from public sources.
- **Filters:** Date range (all-time / current year / previous year on Entrepreneur; quarterly + 4-year history on Growth; per-quarter + annualized on Peak); acquisition source (3/11/41 channels by tier); product/variant scope (Growth+).
- **Data shown:** Repeat Purchase Rate, Total LTV per first-product, customer purchase latency interval distribution, behavior shifts by order position.
- **Interactions:** Click cells/rows; tier-gated lookback depth; daily auto-recompute ("automatically adjust as needed as new customer behavior comes in every day"); email-digest subscription on Order Sequencing.
- **Why it works (from reviews/observations):** "A must-have app for generating business insights and understanding customer loyalty / repeat shopping behavior beyond basic Shopify analytics." — 8020nl, Shopify App Store, Apr 12, 2018. The "1-to-2" framing names the merchant's actual job-to-be-done.
- **Source:** [`competitors/repeat-customer-insights.md`](../competitors/repeat-customer-insights.md); https://www.littlestreamsoftware.com/articles/measuring-how-the-products-in-the-first-order-influence-customer-repurchases/

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Visualization:** Three discrete views — (1) Repurchase Rate Report (table of % across windows), (2) Time Lag Between Orders distribution chart, (3) Customer Product Journey "noodle" diagram (Sankey-style flow).
- **Surface:** Sidebar > Customer Behavior Reports > Repurchase Rate / Time Lag / Customer Product Journey.
- **Layout (prose):** Repurchase Rate Report shows percentages across multiple time windows. Time Lag chart bucketed at 7 / 30 / 90 days per consecutive order pair (1st→2nd, 2nd→3rd). Customer Product Journey is "intuitive 'noodle' diagrams" showing customer flow from 1st → 2nd → 3rd → 4th product purchases, with each band representing the volume of customers transitioning between specific products.
- **Specific UI:** Sankey-style flow diagram — "noodle" terminology suggests curved/flowing bands. Color-coded by product or category. Distribution buckets exposed as 7/30/90 days; benchmarks card uses 90d + 180d windows specifically.
- **Filters:** Cohort, discount, channel, post-purchase survey responses.
- **Data shown:** Repurchase % at 30d / 60d / 90d / 180d / 365d (90d + 180d confirmed via benchmarks article); customer count per time-lag bucket per order pair; customer count flowing between purchase positions; conversion rate from purchase N to purchase N+1.
- **Interactions:** Filter by cohort dimensions; toggle between cohort weekly/monthly/yearly grouping upstream; export.
- **Why it works (from reviews/observations):** "invaluable for understanding of your MER, customer cohorts and LTV" — Radiance Plan, Shopify App Store, Apr 2, 2026. The noodle diagram makes the "what do they buy next" question visual rather than tabular.
- **Source:** [`competitors/lifetimely.md`](../competitors/lifetimely.md); https://help.useamp.com/article/682-cohort-analysis-use-cases

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Sidebar > Essentials > Repurchase Rate by Cohort; Sidebar > Marketing Analytics > Catalog Insights > Product Analysis > "Repeat Purchase Timing"; Sidebar > Cohort Analysis (36+ cohort metrics).
- **Visualization:** Histogram (Repeat Purchase Timing card) plus cohort heatmap table (Repurchase Rate by Cohort) plus ranked tables ("Products Bought in Same Cart", "Products Bought in Next Order").
- **Layout (prose):** Catalog Insights left rail = sortable product list (Revenue High-Low / Customers High-Low). Main canvas = customizable analysis cards. Repeat Purchase Timing card is a histogram with **x-axis "days between purchases" capped at 90-day view and y-axis "customer count"** — shows distribution of when buyers come back. Adjacent cards: Products Bought in Same Cart (ranked table with co-purchase rate %), Products Bought in Next Order (ranked table with post-purchase rate % and median days between purchases).
- **Specific UI:** 90-day-capped histogram x-axis (deliberate framing — anything beyond 90 days reads as churn, not repeat). Per-product selection refreshes all three cards together.
- **Filters:** Date range defaults to last two years; refunded/cancelled orders excluded by default; product selection drives all cards.
- **Data shown:** Repeat purchase timing distribution, co-purchase rate, post-purchase rate, median days between purchases, top 500 product recommendations per category.
- **Interactions:** Select a product to refresh all cards. Sort/filter product list. Drill into cohort table from any cell.
- **Why it works (from reviews/observations):** "Great app for all things retention and cohort analysis! Easy to use but also excellent service" — Koh, Shopify App Store, Mar 26, 2026. The 90-day x-axis cap is an editorial decision — they refuse to show "repeat" purchases beyond 90 days as repeat at all, framing them as separate cohorts.
- **Source:** [`competitors/peel-insights.md`](../competitors/peel-insights.md); https://help.klaviyo.com/hc/en-us/articles/26685770823451 (analogous histogram pattern documented in Klaviyo)

### Everhort ([profile](../competitors/everhort.md))
- **Surface:** Reports > Cohort Retention Chart; Reports > Average LTV by Cohort; Reports > Stacked Cohort Activity; Reports > Forecasted Average LTV.
- **Visualization:** Multi-line chart (Average LTV by Cohort, x = months-since-acquisition starting at month 2, y = % returning customers); stacked area chart (Stacked Cohort Activity); two-column bar+table (Forecasted LTV); table-only for Cohort Retention Chart with %/absolute toggle.
- **Layout (prose):** Cohort Retention Chart x-axis = "the age of each cohort in months since their first purchase" (excluding the acquisition month — i.e., **starts at month 2**). Y-axis defaults to "percentage of returning customers" out of 100%. Toggle "in the upper right" switches Y-axis between percentage and absolute customer count. Average LTV by Cohort: darker blue lines = older cohorts, lighter blue = newer; "a light red line displays a blended average of recent monthly cohorts"; when filters active, "a green line represents the blended unfiltered (baseline) average LTV" alongside the filtered series.
- **Specific UI:** Tabular companion below every chart with green/red heatmap cell shading bucketed by ±1, ±2, ±3 absolute deviations of the mean. Cells representing "ongoing, incomplete periods display a dashed border" — explicit visual flag against partial-period misreading. **Per-filter "first purchase" / "subsequent purchase" / "any purchase" qualifier** — every order filter accepts a scope qualifier that lets the user separate first-time from repeat behavior on the same chart.
- **Filters:** Customer (single filter — tagged customers only); Order (Product Collections, Product Name, Product Type, Product Properties, Discounts) × first/subsequent/any purchase qualifier; Channel.
- **Data shown:** % returning per cohort per month-of-age, absolute returning-customer count, cumulative LTV per customer per month, blended baseline LTV, forecasted LTV at 1Y/2Y/3Y, recommended CAC ceiling (LTV/CAC = 3 fixed), payback period.
- **Interactions:** %/absolute toggle on retention chart; filtered-vs-baseline overlay; click-band-to-isolate on stacked cohort activity; CSV export beneath every table.
- **Why it works (from reviews/observations):** "the best LTV cohort analysis app I have found on Shopify" — The Beard Club, Shopify App Store, Sep 8, 2020. The first/subsequent/any purchase qualifier on every filter is the closest UI pivot to "show me only repeat behavior" in any tool reviewed.
- **Source:** [`competitors/everhort.md`](../competitors/everhort.md); https://help.everhort.com/article/13-cohort-retention

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Marketing Analytics > Catalog Insights > Product Analysis > "Repeat Purchase Timing"; Profile page > Metrics and insights tab (per-customer "average time between orders" + "expected date of next order").
- **Visualization:** Histogram (Repeat Purchase Timing) with x-axis days-between-purchases capped at 90 days, y-axis customer count; per-customer KPI card grid on profile page; diamond glyph on customer order timeline marking predicted next-order date.
- **Layout (prose):** Catalog Insights left rail = sortable product list. Main canvas = customizable analysis cards including Repeat Purchase Timing (histogram), Products Bought in Same Cart (ranked table), Products Bought in Next Order (ranked table). On individual customer profiles, the Metrics and insights tab is a card grid: predicted CLV, expected date of next order, churn risk (low/medium/high), **average time between orders**, predicted gender. Order timeline at the bottom shows past orders as ticks plus a **diamond tick at the predicted next order date**.
- **Specific UI:** Diamond-shaped tick for predicted-next-order on customer timelines (distinct glyph vs round ticks for past orders). Churn-risk uses **traffic-light color coding — green for low risk, yellow for medium, red for high**. Activation gate: "at least 500 customers have placed an order, 180+ days of order history, orders in the last 30 days, three or more repeat purchasers" — predictive panels hide until thresholds met.
- **Filters:** Product selection drives all Catalog Insights cards; date range (default last two years).
- **Data shown:** Days-between-purchases histogram, co-purchase rate %, post-purchase rate %, median days between purchases, per-customer expected next-order date, average time between orders, predicted CLV (predicted = green stacked-bar segment, historic = blue), churn risk score.
- **Interactions:** Sort product list; select product to refresh all cards; click a customer's diamond tick to see prediction methodology; segment-builder accepts "average time between orders" as a filter dimension.
- **Why it works (from reviews/observations):** "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group. The per-customer diamond glyph + churn traffic-light is the most concrete "is THIS person about to churn?" surface in the category.
- **Source:** [`competitors/klaviyo.md`](../competitors/klaviyo.md); https://help.klaviyo.com/hc/en-us/articles/26685770823451

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Customer Analytics > Purchase Patterns Dashboard; Customer Analytics > LTV Analysis Dashboard; Customer Analytics > New vs Existing Customers Dashboard; Customer Table Dashboard.
- **Visualization:** **5-column Nth-order funnel table** — unique IA pattern not seen elsewhere. Each column = customer's Nth order (1st, 2nd, 3rd, 4th, 5th). Each column contains stacked cards representing products, categories, or brands.
- **Layout (prose):** Top of column 1 always shows "100% of Unique Customers" since all customers must place a 1st order. Each card shows what % of customers at that stage purchased that item (verbatim example: "62.5% of new customers"). Cards display unique customer count, gross revenue, and order contribution %. Adjacent New vs Existing dashboard is two-panel layout for behavioral metrics with channel performance breakdown ("which channels are best for acquiring new customers vs repeat purchases") and product-level split ("products that attract new customers vs the ones your loyal base can't resist").
- **Specific UI:** Card-based grid inside columns. Granularity selector (product / category / brand). Sort metric controls (unique customers / gross revenue / order contribution %). **Click any card to filter the entire dashboard** to only customers who purchased that item at that stage — reveals (a) what was bought in the same order, (b) what those customers bought in subsequent orders, (c) progressive funnel narrowing across columns.
- **Filters:** Granularity (product/category/brand); sort metric; click-to-filter on any card propagates to all dashboards.
- **Data shown:** % of customers purchasing each item per order-rank, unique customers, gross revenue, order contribution %; LTV Analysis dashboard adds Repeat Rate (`repeat purchasers / cohort size`), Order Frequency (`orders within 12mo / cohort size`), LTV:CAC.
- **Interactions:** Click-card-to-filter is the primary interaction. Daily refresh.
- **Why it works (from reviews/observations):** "Simple to use, seriously rich insights, all action-orientated." — Rapanui Clothing, Shopify App Store, Oct 2024. The 5-column funnel turns "what do customers buy next?" from a table query into a click-through narrative. Help docs explicitly teach how to read the columns.
- **Source:** [`competitors/conjura.md`](../competitors/conjura.md); https://www.conjura.com/purchase-patterns-dashboard

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** LTV & Cohort dashboard; AI prompt examples (Henri assistant) including "Analyze the correlation between email-campaign spikes and repeat-purchase revenue."
- **Visualization:** Cohort grid grouped by week/month/year with channel filter (chart type not explicitly named in public docs); first-time-vs-repeat split appears as a tag/flag on attribution columns rather than a dedicated visualization.
- **Layout (prose):** Cohort dashboard exposes average LTV, retention, cohort grids by week/month/year. Le Pixel attribution layer carries a first-time-vs-repeat flag per customer journey, which propagates into channel/campaign/ad-level reports as a separate "first-time vs repeat" split column. Henri AI accepts natural-language prompts including the email-spike-to-repeat correlation analysis.
- **Specific UI:** First-time-vs-repeat is a flag, not a dashboard. UI details for how the split renders inside reports not published.
- **Filters:** Cohort grouping (week/month/year); channel; first-time vs repeat; subscription flag.
- **Data shown:** First-time vs repeat revenue, predicted LTV, channel/campaign/ad attribution split by first-time vs repeat.
- **Interactions:** AI prompt for correlation analysis; cohort grouping toggle.
- **Why it works (from reviews/observations):** Reviewer feedback on Lebesgue's repeat-purchase surface specifically not isolated in public sources; first-time-vs-repeat as a flag-rather-than-page is a packaging choice that keeps the split available everywhere instead of in one report.
- **Source:** [`competitors/lebesgue.md`](../competitors/lebesgue.md)

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Reports > Customer Report > "Time-between-repeat-orders" chart; Reports > Cohorts ("Returning Customers" cohort variant); Reports > Retention Report ("orders made over lifetime", "items purchased over lifetime").
- **Visualization:** Bar chart for "Orders made over the lifetime of new customers" with average order gross. Line/bar chart for time-between-repeat-orders. Cohort heatmap with "Returning Customers" variant.
- **Layout (prose):** Customer Report top section = summary cards (new customers, total orders, total revenue). Below = averages section (avg LTV, AOV, avg lifetime orders, items per customer). Below that = "Customers grouped by" tables. Geographic heatmap section. Then bar chart "Orders made over the lifetime of new customers" with corresponding average order gross. Then **time-between-repeat-orders chart — toggleable between weeks / days / months / years**. Then items-purchased-over-lifetime chart.
- **Specific UI:** **X-axis unit toggle (weeks / days / months / years) on the time-between-repeat-orders chart** — explicit user control over time grain. **Blue-colored numbers in customer-count columns are clickable**, drilling into the underlying customer list for that segment. Cohort variants tabbed: "Returning Customers" (retention rate), "Order Count" (uses # of orders as the lifetime axis instead of months).
- **Filters:** Saved segments apply across all reports; cohort variant tabs (Customer Lifetime Profit / Returning Customers / Order Count / Billing Country / First Product Purchased / First Coupon Used); attribution date toggle (first-order vs join date in Store Settings).
- **Data shown:** Time-between-repeat-orders distribution, avg LTV, AOV, avg lifetime orders, items per customer, retention % by cohort, repeat rate.
- **Interactions:** Click blue numbers → drill into customer list. Toggle x-axis unit on time-between-orders chart. Switch cohort variant. Apply saved segment.
- **Why it works (from reviews/observations):** Reviewer praise centers on Metorik's segment builder + filter depth (500+ filters); the time-between-orders chart with x-axis toggle is the only one in this profile that lets a user re-grain the same data on the fly.
- **Source:** [`competitors/metorik.md`](../competitors/metorik.md); https://help.metorik.com/article/177-customer-reports

### Putler ([profile](../competitors/putler.md))
- **Surface:** Home Dashboard > Website Metrics widget (one-time vs repeat customer split); Customers Dashboard > Top 20% block; Sales Dashboard > Three Months Comparison.
- **Visualization:** KPI card with split number (one-time vs repeat customer); 80/20 trend line chart (concentration ratio over time); per-product card with "average time between sales" metric.
- **Layout (prose):** Home Dashboard "Pulse" zone for current month is one stacked widget. Below the Pulse zone, a Website Metrics block shows conversion rate plus **one-time vs repeat customer split**. Three Months Comparison widget shows visitor count, conversion rate, ARPU, and revenue for last 90 days vs preceding 90 days. Per-product card includes customer purchase list, refund rate, average refund timing, predicted monthly sales, **average time between sales**, sales history timeline.
- **Specific UI:** **Star icons inline next to top-revenue products.** 80/20 trend visualization (line chart of concentration ratio over time). Time-between-sales is a single number on each product card, not a distribution chart.
- **Filters:** Date-picker; event-type dropdown on Activity Log.
- **Data shown:** Repeat-rate, one-time vs repeat customer split, average time between sales (per product), top 20% customers, ARPU/ARPPU.
- **Interactions:** Drill into product card; switch date range; YoY comparison auto-renders.
- **Why it works (from reviews/observations):** "Long-tenured users dominate the review pool — 10-year customers, 'since 2017', 'for years' appear repeatedly." Putler's strength is normalised cross-platform aggregation; the repeat-purchase split lives as one number in a Website Metrics card rather than a dedicated dashboard.
- **Source:** [`competitors/putler.md`](../competitors/putler.md)

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Retention Marketing > Retention Dashboard; Ecommerce Performance > Product Repurchase Rate; Acquisition Marketing > LTV & RFM.
- **Visualization:** Three-section dashboard with "Performance by Customer Segment" (two side-by-side comparative charts current vs prior month), "Time Between Orders" repurchase interval visualizations, and "Customer Movement & Historical Performance" cohort segment-transition tracking.
- **Layout (prose):** Retention Dashboard has three section blocks. (1) Performance by Customer Segment — two comparative charts side-by-side showing current and prior month for gross sales, orders, AOV, units per order, average unit revenue. Customers tagged into RFM segments at month start and remain static through that month. (2) Time Between Orders — repurchase interval visualizations driving campaign cadence decisions. (3) Customer Movement & Historical Performance — cohort segment-transition tracking (single-buyer → multi-buyer → HVC) and churn/lapsed-customer monitoring.
- **Specific UI:** RFM tags assigned at month start and frozen through the month — deliberate snapshot stability for cadence planning. Segment-transition tracking explicitly named (single-buyer → multi-buyer → HVC).
- **Filters:** Channel mix, RFM segment, product category.
- **Data shown:** Gross sales, orders, AOV, units per order, average unit revenue, time between orders, segment transition rates, repurchase rate.
- **Interactions:** Cohort drill-down; segment transition view.
- **Why it works (from reviews/observations):** Daasity is warehouse-first; its repeat-purchase view is more analytical (frozen RFM tags, transition tracking) than operational. UI screenshots not publicly accessible without paid demo.
- **Source:** [`competitors/daasity.md`](../competitors/daasity.md)

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Retention & LTV page (pre-built); Custom Reports composer (metrics × dimensions × filters).
- **Visualization:** Pre-built page with cohort analysis, repeat rate, customer LTV cards. Custom-report composer renders table or chart depending on metric/dimension selection.
- **Layout (prose):** Pre-built Retention & LTV page bundles cohort analysis, repeat rate, and customer LTV. The Custom Reports composer lets a user pick metrics (including repeat rate), dimensions, date granularity, and filters; output renders as table or chart.
- **Specific UI:** No-code formula builder over Shopify dimensions for custom metrics ("net profit", "profit on ad spend"). Repeat rate is one of many configurable cells, not a flagship surface.
- **Filters:** Configurable per dimension and date.
- **Data shown:** Repeat rate, cohort retention, customer LTV, AOV, MER, CAC, contribution margin.
- **Interactions:** Compose custom reports; apply target lines on charts.
- **Why it works (from reviews/observations):** Polar's strength is configurability; repeat rate is exposed as a metric token rather than a dedicated dashboard.
- **Source:** [`competitors/polar-analytics.md`](../competitors/polar-analytics.md)

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Cohort dashboard (segmentable by first-product-purchased, discount code, demographics, channel, time bucket).
- **Visualization:** Cohort grid (specific structure not described in fetched content); CLTV by cohort cited at 60/90 windows.
- **Layout (prose):** "Easily accessible from your main dashboard"; segmentable by first-product-purchased, discount code, demographics, channel, time bucket (daily/weekly/monthly/quarterly/annual). Repeat rate is one of the metrics computed alongside MER/ncROAS/POAS/nCAC.
- **Specific UI:** Single dashboard screenshot in their cohort blog post; specific grid/heatmap structure not described publicly. UI details not available — only feature description seen on marketing page.
- **Filters:** First-product-purchased, discount code, demographics, channel, time bucket.
- **Data shown:** Repeat rate, CLTV at 60/90, ncROAS, AOV, MER.
- **Interactions:** Time-bucket grouping (daily through annual); segmentation by 5 dimensions.
- **Why it works (from reviews/observations):** Triple Whale's cohort dashboard is documented as accessible from the main dashboard but its UI internals are largely opaque in public sources.
- **Source:** [`competitors/triple-whale.md`](../competitors/triple-whale.md)

### Glew ([profile](../competitors/glew.md))
- **Visualization:** KPI strip including "new customers" and "repeat customers" alongside revenue, AOV, conversion rate.
- **Surface:** Default dashboard.
- **Layout (prose):** KPI strip; repeat-purchase exposed as one of many KPI tokens rather than a dedicated dashboard. UI details not deeply documented in public sources.
- **Specific UI:** Repeat customers as a count, not a rate or distribution. UI details not available — only feature description seen on marketing page.
- **Filters:** Date range; segment.
- **Data shown:** Revenue, orders, AOV, gross profit, gross margin, website visits, conversion rate, refunds, new customers, repeat customers, ad spend, top marketing channel, top-selling product, largest order.
- **Interactions:** Date drill-down.
- **Why it works (from reviews/observations):** Glew bundles repeat-customer count into a unified KPI strip; it's a baseline implementation rather than a feature.
- **Source:** [`competitors/glew.md`](../competitors/glew.md)

### StoreHero ([profile](../competitors/storehero.md))
- **Visualization:** KPI cards including "new customer sales" and "repeat customer sales" as separate revenue lines.
- **Surface:** Default dashboard.
- **Layout (prose):** Repeat-purchase exposed as a revenue-split (new vs repeat customer sales) on the headline metrics row rather than a rate or distribution.
- **Specific UI:** UI details not available — only feature description seen on marketing page. Two separate revenue numbers (new / repeat) sit alongside Net Sales, Marketing Spend, MER, ROAS, breakeven ROAS, AOV.
- **Filters:** Date range.
- **Data shown:** New customer sales $, repeat customer sales $, contribution margin, MER, AOV.
- **Interactions:** Slack/email/iOS app digest delivery.
- **Why it works (from reviews/observations):** Splitting revenue into new-vs-repeat dollars (rather than a rate) lets operators see absolute repeat-customer revenue contribution.
- **Source:** [`competitors/storehero.md`](../competitors/storehero.md)

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Visualization:** "Returning customer rate" KPI card + cohort report (separate surface).
- **Surface:** Analytics > Reports > Customers + dashboard cards.
- **Layout (prose):** Returning customer rate is one card on the analytics dashboard. The cohort report is a separate surface. Definition of "returning customer" is loose — anyone who placed >1 order ever — which the profile flags as a known issue: "useful for repeat rate at the brand level, less useful for cohort/retention diagnosis (which is why the cohort report exists, but the dashboard cards still use the looser definition)."
- **Specific UI:** Single KPI tile; cohort report on separate page.
- **Filters:** Date range.
- **Data shown:** Returning customer rate (% all-time), cohort retention.
- **Interactions:** Drill into customer list.
- **Why it works (from reviews/observations):** Free, present in every Shopify install; the loose "returning customer" definition is the most-cited weakness vs purpose-built tools.
- **Source:** [`competitors/shopify-native.md`](../competitors/shopify-native.md)

### WooCommerce Native ([profile](../competitors/woocommerce-native.md))
- **Visualization:** Not observed.
- **Surface:** Not implemented.
- **Layout (prose):** "NOT computed: Profit, margin, COGS-aware metrics, LTV, RFM, cohort retention, repeat-purchase rate, churn, MER, contribution margin, forecast." — `competitors/woocommerce-native.md`. Native Woo does not compute repeat-purchase rate at all.
- **Specific UI:** N/A.
- **Filters:** N/A.
- **Data shown:** N/A.
- **Interactions:** N/A.
- **Why it works (from reviews/observations):** It doesn't — this is a structural gap that every Woo-targeting analytics tool fills.
- **Source:** [`competitors/woocommerce-native.md`](../competitors/woocommerce-native.md)

## Visualization patterns observed (cross-cut)

Counted across the 16 competitors above (excluding Woo native, which does not implement):

- **Histogram of time-between-orders (days x customer-count):** 4 competitors (Peel Insights, Klaviyo, Lifetimely "Time Lag", Metorik with x-axis toggle). The 90-day x-axis cap recurs as an editorial framing in Peel and Klaviyo.
- **Cohort heatmap with retention %:** 6 competitors (Lifetimely, Peel, Everhort companion table, Metorik, Conjura, Daasity). Cell coloring is heatmap-style intensity in 4/6.
- **Multi-line cohort chart (one line per acquisition month):** 1 competitor (Everhort) — explicit cohort-age x-axis with cohort-recency-color gradient (older = darker blue).
- **Nth-order funnel / Sankey:** 3 competitors (Conjura's 5-column Purchase Patterns table — unique; Lifetimely's "noodle" Sankey; Repeat Customer Insights' Order Sequencing Analysis).
- **Per-customer predictive panel (next-order date + churn risk):** 1 competitor (Klaviyo) — diamond-glyph timeline marker is unique.
- **Single-letter / single-glyph customer health:** 1 competitor (Repeat Customer Insights' A-F grade).
- **KPI-strip-only (one or two repeat tokens, no distribution):** 4 competitors (Glew, StoreHero, Putler, Shopify Native). Reviews of the KPI-only tools rarely cite the repeat metric specifically — it reads as "data present, not feature".
- **5×5 RFM grid as the home of segmentation (which carries repeat behavior implicitly):** 3 competitors (Repeat Customer Insights' Customer Grid, Peel's home page, Klaviyo's six-bucket variant). Not strictly a repeat-purchase visualization but the merchant-side activation surface for it.

Color and iconography conventions: the few public-source color tokens recur — Everhort uses **darker blue = older cohort, lighter blue = newer**, with **light red = blended baseline** and **green = filtered baseline**; Klaviyo uses **traffic-light green/yellow/red for churn risk** and **blue (historic) + green (predicted) stacked bars**; Everhort uses **dashed cell borders for incomplete periods** as an explicit anti-misread pattern.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: The "what comes next" question rendered as a visual narrative**
- "Simple to use, seriously rich insights, all action-orientated." — Rapanui Clothing, Shopify App Store, Oct 2024 (Conjura's Purchase Patterns 5-column funnel) — `competitors/conjura.md`
- "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." — Island Living, Shopify App Store, Nov 2024 — `competitors/conjura.md`

**Theme: Diagnosis at the first-product / 1-to-2 conversion level**
- "A must-have app for generating business insights and understanding customer loyalty / repeat shopping behavior beyond basic Shopify analytics." — 8020nl, Shopify App Store, Apr 12, 2018 — `competitors/repeat-customer-insights.md`
- "Great app to keep track of your customer cohorts and stay on top of LTV." — Pacas, Shopify App Store, May 15, 2023 — `competitors/repeat-customer-insights.md`
- "COHORT ANALYSIS automatically created from your store is unbelievable and a must-have for any startup focused on growth." — pantys, Shopify App Store, Jun 14, 2019 — `competitors/repeat-customer-insights.md`

**Theme: Cohort-comparison-against-baseline reveals which retention tactics worked**
- "the best LTV cohort analysis app I have found on Shopify" — D. Morse, The Beard Club, Shopify App Store, Sep 8, 2020 — `competitors/everhort.md`
- "really good LTV analytics capabilities combined with best-in-class, highly responsive support" — Mighty Petz, Shopify App Store, Dec 15, 2023 — `competitors/everhort.md`
- "We've used this tool for several months to track cohort retention rates" and to identify retention drivers; described as "an excellent visualiser of retention." — LUXE Fitness, Shopify App Store, Jun 15, 2020 — `competitors/everhort.md`

**Theme: Retention scoped per RFM segment so messaging maps to behavior**
- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group, quoted on klaviyo.com — `competitors/klaviyo.md`

**Theme: Cohort + LTV unification as the basis for paid-acquisition decisions**
- "invaluable for understanding of your MER, customer cohorts and LTV" — Radiance Plan, Shopify App Store, Apr 2, 2026 — `competitors/lifetimely.md`
- "helped us understand our Lifetime Value better than anything else" — Blessed Be Magick, Shopify App Store, Feb 8, 2026 — `competitors/lifetimely.md`

## What users hate about this feature

**Theme: Repeat-purchase computation gated behind paid analytics tier**
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, Dec 2025 — `competitors/klaviyo.md`
- "There's no free plan with segmentation capabilities." — digismoothie.com — `competitors/peel-insights.md`

**Theme: Loose / inconsistent definitions of "repeat" or "returning"**
- "Returning-customer definition is too loose. Shopify defines 'returning customer' as anyone who placed >1 order ever — useful for repeat rate at the brand level, less useful for cohort/retention diagnosis." — `competitors/shopify-native.md`

**Theme: Predictive / forecasted repeat metrics gated by data thresholds**
- "Predictive analytics gated by data thresholds (500+ customers with orders, 180+ days history, 3+ repeat purchasers) — small/new stores see empty CLV / churn cards." — `competitors/klaviyo.md`

**Theme: Real-time claim vs actual freshness on retention cards**
- "Real-time claim vs. actual freshness. Marketing says 'real-time'; reviewers say 'every few hours.'" — `competitors/lifetimely.md`
- "Pretty poor app overall. Expensive and slow. Buggy." — Plushy, Shopify App Store, Mar 29, 2022 — `competitors/lifetimely.md`

**Theme: Limited slicing dimensions on repeat surfaces**
- "One customer filter only ('Customers who are tagged'). No filter on customer geography, signup channel, lifetime spend bucket, etc. — limits cohort segmentation depth." — `competitors/everhort.md`
- "the additional add on cause the product to be a bit limiting…but overall a useful tool for high level view" — Sur Nutrition, Shopify App Store, Mar 19, 2026 — `competitors/lifetimely.md`

## Anti-patterns observed

- **"Returning customer" defined as anyone who placed >1 order ever (lifetime).** Shopify Native's dashboard cards use this loose definition. The cohort report on the same product uses a stricter cohort-windowed definition. Two definitions, same word, same product. — `competitors/shopify-native.md`
- **Repeat rate as a single number with no distribution context.** Glew, StoreHero, Putler each surface "repeat customer count/sales/rate" as a KPI token without a paired histogram or cohort. Operators can read the rate but cannot diagnose whether it moved because cohort velocity changed or because the cohort population changed.
- **Time-between-orders without an x-axis cap.** A long-tail of customers who reorder after 12+ months drags the median rightward. Peel's and Klaviyo's 90-day caps are an explicit editorial decision; tools without a cap risk implying that "repeat" stretches arbitrarily.
- **Cohort tables without dashed-border treatment for incomplete periods.** Everhort flags this as a deliberate UX pattern: cells representing partial months render with a dashed border so users don't misread them as complete. Profiles for Lifetimely, Peel, Conjura, and Triple Whale do not document this treatment in public sources.
- **First-time-vs-repeat as a column flag rather than a visualization.** Lebesgue carries the flag through every report but never builds a dedicated repeat-purchase surface — the merchant has to reconstruct the picture mentally across reports.
- **Predictive next-order date with no empty state for stores below the data threshold.** Klaviyo gates the panel behind 500+ customers / 180+ days / 3+ repeat purchasers, but the published help center does not describe what the surface looks like below those thresholds beyond "predictive features hide" — risk of a silent-empty experience.

## Open questions / data gaps

- **Time-to-second-order as a single headline KPI is not a flagship in any tool reviewed.** Klaviyo exposes "average time between orders" per-customer; Lifetimely shows it as a distribution; Daasity has a "Time Between Orders" section. None of them surface a single store-wide "median T2 = X days" headline KPI on a default dashboard. Per `competitors/everhort.md`: "the brief specified time-to-second-order as a flagship metric, but Everhort's public sources do not name or show such a metric." This is a category-wide gap, not a competitor-specific one.
- **Cohort heatmap color tokens** are not published verbatim by Peel, Lifetimely, Triple Whale, or Conjura — most marketing screenshots show a gradient but exact hex values require paid trials.
- **Per-customer predictive next-order date** appears only in Klaviyo's profile pages publicly; whether other tools (Lifetimely, Peel, Triple Whale) compute this without exposing it is unclear.
- **Repeat-purchase rate definition (window, denominator)** varies. Lifetimely benchmarks at 90d + 180d. Conjura uses 12-month fixed window for `repeat purchasers / cohort size`. Shopify uses lifetime. Peel separates "Repeat Orders Rate per Cohort" from "Repurchase Rate" without publishing the formula difference.
- **Subscription-vs-one-off split** in the repeat-purchase histogram is described but not visually documented for any tool except Lebesgue (where it's a flag, not a viz). Required for SMBs running both subscription and one-time SKUs.
- **Conjura's 5-column Purchase Patterns dashboard** is the only Nth-order funnel visual found; whether it extends past 5 columns is undocumented.

## Notes for Nexstage (observations only — NOT recommendations)

- **No competitor in this profile surfaces "median time-to-second-order" as a headline KPI.** It exists as a distribution (Lifetimely Time Lag, Peel/Klaviyo Repeat Purchase Timing histograms, Metorik time-between-repeat-orders chart), as a per-customer attribute (Klaviyo profile page), or as a section title (Daasity), but not as a single number on the customer overview. The user-question framing of this feature ("time-to-second-order") is a category gap.
- **The 5-column Nth-order funnel (Conjura Purchase Patterns) is the only novel IA in this profile.** 1st through 5th order columns, click-any-card-to-filter-the-dashboard. No other tool implements it. Direct copyable pattern.
- **First-product → repeat behavior is the recurring slice.** Repeat Customer Insights' First Product Analysis (per-product RPR + Total LTV), Lifetimely's cohort filter on first-product-purchased, Peel's Product Popularity by Order Number, Klaviyo's "Products Bought in Next Order". Five tools converge on "what the first order contained predicts whether the second order happens." Worth noting against Nexstage's product-performance surface.
- **90-day x-axis cap on repeat-timing histograms recurs across Peel and Klaviyo.** Editorial framing — anything past 90 days reads as a separate cohort, not a repeat. Two of the strongest retention products in the category share this convention.
- **Klaviyo's diamond-glyph for predicted-next-order on the customer timeline is the only per-customer "is this person about to churn / re-buy?" surface in public sources.** Single visual primitive, low cost, high recognition.
- **Repeat Customer Insights' "1-to-2 Customer Analysis" Focus Page** is named after the merchant's job-to-be-done, not after the dimension or metric. Different IA philosophy from Nexstage's source-/lens-based navigation; flagged for IA decisions.
- **Multi-source-attribution for repeat behavior is absent everywhere.** No tool in this profile splits "repeat-customer revenue" by 6 sources (Real / Store / Facebook / Google / GSC / GA4). Lebesgue's first-time-vs-repeat flag inside Le Pixel attribution is the closest, and it's a single-source pixel view. Nexstage's 6-source thesis applies to the *acquisition* leg of repeat purchase (which channel produced the customer who came back).
- **Cohort lookback depth is a paid-tier axis** in Repeat Customer Insights (12 months → all history) and gated by data thresholds in Klaviyo (500+ customers / 180+ days). The minimum data-volume floor for predictive next-order is something Nexstage will hit on every new install.
- **WooCommerce native does not compute repeat-purchase rate at all.** Direct opening for Nexstage's Woo-first positioning — this is a structural feature gap, not a competitor advantage to overcome.
- **The "first/subsequent/any purchase" qualifier on every Everhort filter** is the cleanest UI pivot for "show me only repeat behavior" found in any tool. It's a global control, not a per-report toggle. Worth noting as a filter-system pattern.
- **Daasity's "RFM segments are tagged at month start and frozen through the month"** is an opinionated stability choice for cadence planning. Klaviyo's Sankey of segment migration is the opposite philosophy (show every move). Both are valid; the choice is editorial.
- **Subscription-vs-one-off separation** is documented only in Peel and Lebesgue. For SMBs running Recharge/Skio alongside one-time SKUs, this is a real distinction that most tools collapse.
