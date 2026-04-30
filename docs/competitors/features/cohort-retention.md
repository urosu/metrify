---
name: Cohort retention
slug: cohort-retention
purpose: Help merchants answer "How do customers acquired in month X retain (and pay back) over time?" by visualising lifetime value, repeat-purchase rate, and CAC payback by acquisition cohort.
nexstage_pages: customers, cohorts, profit
researched_on: 2026-04-28
competitors_covered: lifetimely, peel-insights, everhort, polar-analytics, metorik, shopify-native, klaviyo, beprofit, triple-whale
sources:
  - ../competitors/lifetimely.md
  - ../competitors/peel-insights.md
  - ../competitors/everhort.md
  - ../competitors/polar-analytics.md
  - ../competitors/metorik.md
  - ../competitors/shopify-native.md
  - ../competitors/klaviyo.md
  - ../competitors/beprofit.md
  - ../competitors/triple-whale.md
  - https://useamp.com/products/analytics/lifetime-value
  - https://help.useamp.com/article/682-cohort-analysis-use-cases
  - https://help.peelinsights.com/docs/rfm-analysis
  - https://www.peelinsights.com/post/your-guide-to-cohort-analysis
  - https://help.everhort.com/article/8-ltv-by-cohort-chart
  - https://help.everhort.com/article/9-stacked-cohort-area-chart
  - https://help.everhort.com/article/13-cohort-retention
  - https://help.everhort.com/article/19-table-views
  - https://metorik.com/features/cohorts
  - https://metorik.com/blog/customer-cohort-reports-track-retention-over-time-by-join-date-first-product-and-more
  - https://help.metorik.com/article/139-subscription-cohorts-report
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/customers-reports
  - https://www.triplewhale.com/blog/cohort-analysis
---

## What is this feature

Cohort retention is the merchant question "of the customers who first bought in month X, what fraction (and what cumulative spend per customer) have come back since?" It is the canonical way to detect whether retention is improving over time, whether a particular acquisition channel produces sticky customers, and when CAC is paid back. For SMB Shopify/Woo owners, it answers the second-most-important diagnostic after profitability: "Am I building a base, or churning through one-time buyers?"

Every competitor in this space ingests the same underlying primitive (orders × customers × first-order date) — the *data* is always present. The *feature* is the synthesis: rendering it as a heatmap or curve, letting the user re-pivot by acquisition source / first product / discount code / country, and overlaying a CAC payback marker so the "when did this cohort break even" question can be answered without arithmetic. Tools that ship raw cohort numbers without these affordances ("cohort table") get neutral reviews; tools that ship a CAC-payback overlay or a rich filter grid (Lifetimely's green bar, BeProfit's green triangle, Peel's drill-into-RFM) get cited verbatim by users as the reason they bought.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.created_at`, `orders.customer_id`, `orders.total_price`, `orders.financial_status`, `orders.refunds`, `line_items.product_id`, `line_items.cost`, `customers.created_at`, `customers.tags`, `orders.discount_codes`, `orders.shipping_address.country`, `orders.source_name` / first-touch UTM.
- **Source: Computed (cohort assignment)** — each customer assigned to a cohort key = `MIN(orders.created_at)` (first-order month / week / quarter / year per user toggle). Some tools (Metorik) expose `customers.created_at` (signup) as an alternative cohort-key axis.
- **Source: Computed (cell metric)** — for each (cohort, period) pair: `cumulative_revenue_per_customer = SUM(net_revenue WHERE order.created_at <= cohort_start + period) / COHORT_SIZE`, `retention_rate = COUNT(DISTINCT customers_with_order_in_period) / COHORT_SIZE`, `repurchase_rate`, `cumulative_orders_per_customer`, `cohort_AOV`, `cumulative_gross_margin_per_customer`, `cumulative_contribution_margin_per_customer`.
- **Source: User-input (COGS)** — `cogs_per_product` or `default_cogs_margin` for contribution-margin / gross-profit cohorts. Shopify's per-variant `Cost per item` is the auto-fallback (Lifetimely, Everhort, Shopify Native).
- **Source: User-input (CAC)** — single CAC value the user enters manually to anchor the payback overlay (Lifetimely, Everhort). Where ad spend is ingested (Triple Whale, Polar, BeProfit, Lifetimely Attribution, Peel), CAC can be computed as `ad_spend / new_customers_in_cohort`.
- **Source: Meta / Google / TikTok / Pinterest / Klaviyo (optional, per tool)** — `first_touch_channel`, `last_touch_channel`, `campaign`, `discount_code` for cohort segmentation (Lifetimely, Peel, Triple Whale, Polar).
- **Source: Subscription platforms (Recharge / Skio / Smartrr / WooCommerce Subscriptions)** — `subscription_status`, `subscription_mrr`, `cancellation_date` for subscription-cohort variants (Metorik, Peel).

## Data outputs (what's typically displayed)

- **KPI: Cohort size** — `COUNT(DISTINCT customer_id) WHERE first_order_in_period = X`, used as the row-header denominator.
- **KPI: Cumulative LTV per customer at month N** — currency, used as the dominant cell metric (Lifetimely, Everhort, Peel, Shopify Native, Metorik).
- **KPI: Retention rate at month N** — percentage, primary cell metric on Everhort's Cohort Retention chart, Shopify Native's cohort grid, Metorik's "Returning Customers" variant.
- **KPI: Repurchase rate** — percentage, complementary cell metric (Lifetimely, Peel).
- **KPI: CAC payback period** — months until cumulative LTV crosses CAC; shown as overlay (Lifetimely green bar, BeProfit green triangle) or computed column (Everhort LTV Summary, Peel Payback Period).
- **Dimension: Cohort start period** — week / month / quarter / year (user toggle on every implementation).
- **Dimension: Period-since-acquisition** — months 0, 1, 2, … N (column axis).
- **Breakdown: Cohort × period** — heatmap or table grid (the canonical viz).
- **Breakdown: Cohort × calendar time** — stacked area chart (Everhort's Stacked Cohort Activity).
- **Breakdown: Cohort × period × cohort line** — multi-line "spaghetti" chart, one line per cohort (Everhort Average LTV by Cohort).
- **Slice: First-product / first-channel / first-discount / country / customer-tag / order-tag / first-coupon / first-touch** — sliceable filter axis on Lifetimely (8+ slices), Peel (40+ filter dimensions), Metorik (6 cohort variants), BeProfit, Triple Whale.
- **Slice: Subscription cohort (MRR retained / subscriber retained)** — separate heatmap (Metorik, Peel).

## How competitors implement this

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Sidebar > Lifetime Value Report (cohort heatmap is the default LTV view); separate Cohort Waterfall sub-view inside the LTV Report.
- **Visualization:** **Color-gradient heatmap matrix** (rows = cohort start week/month/year, columns = months-since-first-order, cells shaded light → saturated by cumulative value). Paired **cohort waterfall chart** (vertical accumulating bar segments per month, one bar per cohort) with a **user-configurable horizontal green CAC-payback line** drawn across it.
- **Layout (prose):** "Top: filter strip slicing the entire grid (first-order product / category, source/medium, marketing channel first- or last-touch, country, customer tags, order tags, discount codes). Left rail: cohort labels (week/month/year). Main canvas: heatmap matrix with selectable cell metric. Bottom: tabular companion (implied)." For the waterfall: "Main canvas: cumulative-revenue-per-customer bars built up month-over-month for the selected cohort, with a horizontal green threshold line at the user's CAC value."
- **Specific UI:** "Color-gradient cells (light→saturated saturation by accumulated value); 13+ selectable cell metrics (cumulative sales / gross margin / contribution margin / repurchasing customers / orders per customer / predicted LTV at 3/6/9/12mo / repurchase rate / cohort AOV)." "User-configurable horizontal green line on the cohort waterfall — user enters their CAC manually; the line renders at that y-value so the payback month is read off where bars cross it."
- **Filters:** Date range, cohort timeframe (weekly / monthly / yearly), first-order product, first-order category, source/medium, marketing channel (first-touch OR last-touch), country, customer tags, order tags, discount codes.
- **Data shown:** Cumulative sales per customer, accumulated gross margin per customer, accumulated contribution margin per customer, total repurchasing customers, accumulated orders per customer, predicted LTV at 3/6/9/12 months, repurchase rate, AOV by cohort. 13+ cell-metric options.
- **Interactions:** Filter chips re-pivot grid; cohort timeframe toggle; metric dropdown swaps cell content; predictive overlay toggles forward LTV projection; user enters CAC value to position the green payback bar on the waterfall.
- **Why it works (from reviews/observations):** "helped us understand our Lifetime Value better than anything else" — Blessed Be Magick (Lifetimely profile). "invaluable for understanding of your MER, customer cohorts and LTV" — Radiance Plan. The CAC-payback green bar is repeatedly singled out as a signature primitive: 1800DTC describes it as "a small (but highly useful) feature where you can add a green bar directly on your cohort waterfall chart to show exactly when your CAC payback hits."
- **Source:** [lifetimely.md](../competitors/lifetimely.md), https://useamp.com/products/analytics/lifetime-value, https://1800dtc.com/breakdowns/lifetimely.

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Sidebar > Cohort Analysis (also reachable from the Essentials tab and Templates Library).
- **Visualization:** **Heatmap-style cohort table** (rows = acquisition month/week/quarter, columns = period 0…N) plus separate **cohort curve** chart and **pacing graph** views of the same data. The brief described Peel's heatmap as "monochromatic blue" — Peel's own profile flags that "public docs do not quote exact color tokens"; the brief's blue assertion is **not directly verified from public Peel sources** (only described as "color-gradient" generically).
- **Layout (prose):** "Top: time-grouping toggle (month/week/quarter), cohort metric tree on the left (Cohorts Retention 8 metrics, Cohorts Revenue 8 metrics, Subscription Cohorts 17 metrics — 36 total). Main canvas: heatmap table. Right rail / drill-down: Single Cohort View when a row is clicked. Companion charts (cohort curve, pacing graph, Tickers) sit on the same screen or as add-to-dashboard widgets."
- **Specific UI:** "Cells shaded by gradient (exact palette unverified); 36 cohort-specific metrics organised in a left-hand tree; companion line-chart 'cohort curves' and 'pacing graphs' available as separate widget types when added to a dashboard."
- **Filters:** Time grouping (month/week/quarter), 40+ filter dimensions (products, SKUs, customer tags, locations, channels, campaigns, LTV bucket, discount codes, purchase count). Audiences integration — any filtered cohort can be saved as an Audience and pushed to Klaviyo/Attentive/Meta.
- **Data shown:** Customer Retention by Cohort, MoM Retention, Repeat Orders Rate per Cohort, LTV by Cohort, Lifetime Revenue by Cohort, Cohort AOV per Month, Discounts/Refunds by Cohort, Customers Returning Rate, Days Since First Order, Repurchase Rate.
- **Interactions:** Switch grouping; drill into Single Cohort View; save report → add to dashboard; schedule via email/Slack; one-click export of cohort to Audience and push to Klaviyo/Attentive/Meta.
- **Why it works (from reviews/observations):** "Great app for all things retention and cohort analysis! Easy to use but also excellent service" — Koh, Australia (Peel profile). "I have loved working in Peel for our customer retention and insights projects... My favorite features are the custom dashboards and audience building." — Saalt.
- **Source:** [peel-insights.md](../competitors/peel-insights.md), https://www.peelinsights.com/post/your-guide-to-cohort-analysis.

### Everhort ([profile](../competitors/everhort.md))
- **Surface:** Sidebar > Reports (the entire product is six cohort report screens).
- **Visualization:** Five distinct chart types across the report set — **multi-line "cohort spaghetti" chart** (Average LTV by Cohort), **stacked area chart** (Stacked Cohort Activity), **bar chart with side-by-side comparison bars** (Forecasted Average LTV), unnamed chart for **Cohort Retention** (chart type not stated in KB), **multi-line averages chart** (AOV / IPB / AIV by cohort age). Heatmap coloring is reserved for the **tabular companion view** that sits beneath every chart.
- **Layout (prose):** "Top: filter strip (Customer / Order / Channel; AND across groups, OR within). Main canvas: primary chart (line, stacked area, or bar). Bottom: tabular companion with green/red heatmap-shaded cells; CSV download link beneath the table."
- **Specific UI:** "Average LTV chart — darker blue lines = older cohorts, lighter blue = newer cohorts, light-red blended-average line, green baseline line when filters are active (filtered cohort overlaid on unfiltered baseline)." "Stacked Cohort Activity — each monthly cohort gets its own band color in the stack; oldest cohort is bottom band; toggle to bundle pre-period cohorts into a single gray band; click any band to isolate that cohort's trajectory in isolation; hover reveals layer-by-layer per-cohort contribution at the hovered timestamp." "Forecasted LTV — light-green baseline bars + gray filtered bars, side-by-side dual bars when a filter is active." "Tabular cells — green if value ≥ blended average, red if below; intensity bucketed at 1, 2, or 3+ absolute deviations of the mean; **dashed border on cells representing ongoing/incomplete periods** so users don't misread partial-month data."
- **Filters:** Customer (tagged), Order (Product Collections / Product Name / Product Type / Product Properties / Discounts), Channel; per-filter "first purchase / subsequent purchase / any purchase" qualifier.
- **Data shown:** Cumulative LTV per customer per cohort age; revenue or returning-customer count decomposed by cohort over calendar months; AOV / Items-Per-Basket / Average-Item-Value by cohort age; retention % by cohort age (or absolute returning count toggle); forecasted LTV at 1Y/2Y/3Y; CAC ceiling at LTV/CAC=3 with payback period.
- **Interactions:** %/absolute toggle on retention chart; metric tab switcher on Averages chart; click stacked-area band to isolate one cohort; CSV export beneath every table.
- **Why it works (from reviews/observations):** "We've used this tool for several months to track cohort retention rates" / "an excellent visualiser of retention" — LUXE Fitness, NZ (Everhort profile). "the best LTV cohort analysis app I have found on Shopify" — The Beard Club. The filter-vs-baseline overlay is repeatedly highlighted: every chart renders the unfiltered baseline alongside the filtered cohort when a filter is active.
- **Source:** [everhort.md](../competitors/everhort.md), https://help.everhort.com/article/8-ltv-by-cohort-chart, https://help.everhort.com/article/13-cohort-retention.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Pre-built **Retention & LTV page** (sidebar) plus user-built dashboards composed of cohort-table or cohort-curve blocks via the Custom Report builder.
- **Visualization:** **Cohort table heatmap** rendered as a Custom Report block (rows × period grid). Companion line-chart blocks for cohort curves are configurable through the same metrics × dimensions × granularity composer. UI details for the specific cell-color palette not directly observable from public sources — Polar's profile notes the UI is described by reviewers as "functional" but "lacks the visual polish seen in some competitors like Triple Whale" (Conjura comparison, 2025).
- **Layout (prose):** "Top: dashboard-level date range, comparison toggle, and View dropdown (saved filter bundle). Main canvas: stacked vertical blocks — Key Indicator Section at top, Custom Report cohort grid below. Right rail (when editing): metric × dimension × granularity composer. Block-level scheduling and Slack/email export buttons inline."
- **Specific UI:** "Sparkline Cards available as KPI tiles atop a cohort dashboard (mini trend line embedded inside the metric card itself); custom-metric formula builder lets users compose 'profit on ad spend', 'net contribution per customer' as cohort cell metrics with no SQL." "Comparison indicators (improvement / decline arrows) render automatically off the dashboard date range."
- **Filters:** Global Filters across all sources; Individual Filters per source with operators "is / is not / is in list / is not in list"; saved Views (collections of filters by store / country / region / product / channel). **Documented gotcha: multiple Views combine with OR, not AND.**
- **Data shown:** Cohort retention, repeat rate, customer LTV-by-cohort, contribution margin per cohort, CAC, ROAS, MER, AOV — composable via the formula builder.
- **Interactions:** Switch View → entire dashboard re-filters; drill from cohort row to underlying customers; schedule block as Slack/email; ask Ask Polar for a cohort report in natural language and the result drops into the BI builder as an editable Custom Report.
- **Why it works (from reviews/observations):** "Their multi-touch attribution and incrementality testing have been especially valuable for us." — Chicory (Polar profile). General cohort viz is described as part of the broader BI builder rather than a marquee surface — reviewers praise the *flexibility* (no-code formulas, 40+ Shopify dimensions) more than the cohort screen specifically.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md), https://www.polaranalytics.com/features/ecommerce-dashboards.

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Sidebar > Reports > Cohorts (Customer Cohorts); separate Reports > Subscription Cohorts for WooCommerce Subscriptions.
- **Visualization:** **Classic cohort heatmap table** — cohort groups as rows (default = join month), time periods as columns (Month 1, Month 2, Month 3, …), each cell shows the metric for that cohort at that lifetime stage. **Summary row at the bottom** shows the average across all cohorts at each lifetime point. Subscription Cohorts uses the same heatmap structure with **MRR-retained-% as cell metric** and a **triple toggle** (MRR ↔ subscribers, % from start ↔ % from prior month ↔ raw value, week ↔ month ↔ year grouping).
- **Layout (prose):** "Top: cohort variant tabs/dropdown (six variants); group-by toggle (default join month). Main canvas: rows × period heatmap. Bottom: summary row with weighted-average retention per lifetime month. Hover-on-cell tooltip reveals raw underlying values."
- **Specific UI:** "Heatmap cells (palette unconfirmed from public sources; Metorik's overall site theme is monochromatic blue but the cohort cell color spec is not verbatim documented in public marketing copy). For Customer Cohorts the variant chosen determines the unit (LTV variant shows $, Returning Customers shows %, Order Count uses # of orders as the lifetime axis instead of months). For Subscription Cohorts: explicit toggles MRR↔Subscriber count and %↔raw value↔% from prior period."
- **Filters:** Saved Segments apply across the cohort report (segments are first-class and reusable across every report); group-by axis toggle (join month / first product / billing country / first coupon / order count).
- **Data shown:** Six Customer Cohort variants — Customer Lifetime Profit, Returning Customers (retention rate), Order Count (lifetime axis = number of orders), Billing Country, First Product Purchased, First Coupon Used. Subscription Cohorts: MRR retained, subscribers retained, churned $, weighted-average retention.
- **Interactions:** Switch cohort variant via tabs/dropdown; toggle MRR↔Subscriber on Subscription Cohorts; toggle %-from-start↔%-from-prior↔raw value; export to CSV; apply saved segment to the cohort report.
- **Why it works (from reviews/observations):** "I want to attack churn, increase LTV, improve order frequency and maximise transaction volume. With Metorik, I can do all that before I've had my morning coffee!" — John Lamerton, BIGIdea (Metorik profile). "Metorik brings WooCommerce reports to a whole new level." — Caspar Eberhard, Appenzeller Gurt.
- **Source:** [metorik.md](../competitors/metorik.md), https://metorik.com/features/cohorts, https://help.metorik.com/article/139-subscription-cohorts-report.

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Analytics > Reports > Customer cohort analysis.
- **Visualization:** **Three views of the same dataset on one screen** — heatmap-style cohort grid + retention curve (line chart) + detailed cohort table. Per the Shopify help center the report "includes a heatmap-style cohort grid, a retention curve, and a detailed cohort table."
- **Layout (prose):** "Top: cohort-granularity toggle (week/month/quarter/year), metric menu, acquisition-channel filter. Main canvas (vertically stacked): heatmap grid → retention curve → detailed table — all filtered/grouped consistently." UI details on the specific palette and cell shading are not directly observable (Help Center pages return 403 to public scrape).
- **Specific UI:** "Three coordinated views: heatmap grid (rows = first-purchase cohort, columns = subsequent periods), retention curve (line chart, cohort age on x-axis, metric on y-axis), detailed cohort table (raw numbers). Metric menu switches all three views to show Number of customers / Customer retention rate / Gross sales / Net sales / AOV."
- **Filters:** Cohort granularity (week/month/quarter/year); acquisition channel.
- **Data shown:** Number of customers, Customer retention rate, Gross sales, Net sales, AOV per cohort × period.
- **Interactions:** Switch granularity → all three views re-render; switch metric → all three views update; filter by channel; export. Sidekick can author the underlying ShopifyQL via natural-language prompt ("How many repeat versus new customers for each traffic source?").
- **Why it works (from reviews/observations):** Native = zero setup; "the cohort report ships heatmap, retention curve, AND detailed table of the same cohort data — three views of one dataset, all built-in" (Shopify Native profile). Verbatim love quotes for the cohort report specifically are scarce — most Shopify reviews praise the platform breadth, not the cohort surface in isolation.
- **Source:** [shopify-native.md](../competitors/shopify-native.md), https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/customers-reports.

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Marketing Analytics > Cohort analysis (gated behind the $100/mo Marketing Analytics add-on or $500/mo Advanced KDP).
- **Visualization:** Standard **cohort retention/repurchase heatmap** as part of the Marketing Analytics module. UI details for the specific cohort heatmap layout are not richly documented in public Klaviyo help content — the surrounding RFM and CLV surfaces are documented in detail (Sankey for RFM migration, blue/green stacked bar for CLV) but the cohort grid itself is referenced as a standard heatmap without verbatim color/palette tokens.
- **Layout (prose):** "Surface lives inside Marketing Analytics > Customer insights alongside the RFM analysis and CLV dashboard. Layout primitives consistent across the module: top filter strip (date range, conversion metric), card-stacked canvas, status pills, drill-into-segment affordances." Specific cohort-grid layout NOT directly observable from public sources.
- **Specific UI:** No verbatim cohort-cell UI details in public docs. Adjacent CLV visualization on customer profiles uses a **horizontal stacked bar** where the **blue segment is historic CLV (already spent) and the green segment is predicted CLV (next 365 days)**, with **diamond-shaped tick marks on the order timeline for the predicted next-order date** — this is the closest documented visual primitive to a cohort-payback overlay in Klaviyo's product.
- **Filters:** Date range; conversion metric; segment built from RFM group / predictive CLV / historic CLV / churn-risk; channel.
- **Data shown:** Retention rate, repurchase rate, predicted CLV, historic CLV, average days between orders, expected next-order date, churn-risk score.
- **Interactions:** Recalculate via date pickers; drill into segments built from "Current RFM group" / "Previous RFM group" / CLV bucket; push segment to Klaviyo email/SMS flows.
- **Why it works (from reviews/observations):** "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group (Klaviyo profile). Cohort-specific verbatim love quotes are sparse — Klaviyo's strongest cohort signal is the per-customer predictive CLV bar (blue historic + green predicted), not the cohort grid itself.
- **Source:** [klaviyo.md](../competitors/klaviyo.md).

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Reports > LTV & Cohort Analysis (Ultimate tier and up; $149/mo+).
- **Visualization:** **Cohort table** with rows = customer cohort (categorized by week of first purchase) and columns = New Customers / CAC / LTV plus a progression of period buckets. Distinctive overlay: **green triangle on each row** marking the estimated point in time at which LTV crosses CAC for that cohort.
- **Layout (prose):** "Top-left: date picker for time-window selection. Top: period-granularity toggle (weekly / monthly / yearly), base-metric dropdown (ROAS / gross profit / repurchase rate), data-progression toggle (accumulated vs marginal), calculation-scheme toggle (Average Per Customer vs Total Cohort). Main canvas: cohort grid with green-triangle CAC payback marker per row."
- **Specific UI:** "Single-glance LTV/CAC payback marker — verbatim from BeProfit's docs: 'The green triangle on each row represents the estimated point in time at which the LTV matches the CAC for that respective cohort.' Period granularity toggle redraws column count; base-metric dropdown re-pivots the entire grid; calculation-scheme toggle changes between Average Per Customer and Total Cohort denominators."
- **Filters:** Date range; period granularity (weekly/monthly/yearly); base metric (ROAS / gross profit / repurchase rate); data progression (accumulated/marginal); calculation scheme (Average Per Customer / Total Cohort).
- **Data shown:** Customer Cohort, New Customers, CAC, LTV, ROAS (toggle), gross profit (toggle), repurchase rate (toggle).
- **Interactions:** Toggle base metric / scheme → entire grid re-renders; period-granularity toggle changes column count; CAC value comes from ingested ad spend rather than user-entered (since BeProfit ingests Meta/Google/etc. spend natively).
- **Why it works (from reviews/observations):** "BeProfit is by far the most accurate and analytical one we've used." — Braneit Beauty (BeProfit profile). "Been using BeProfit for years—absolutely essential. Clear, accurate, and powerful." — Satéur. The green-triangle CAC marker is identified as a distinctive viz "not seen on Lifetimely/TrueProfit" (BeProfit profile, Unique strengths).
- **Source:** [beprofit.md](../competitors/beprofit.md).

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Cohorts (Advanced tier and up).
- **Visualization:** Cohort retention grid; specific structure (heatmap vs table vs curve) **not directly verified from public sources** — Triple Whale's KB is 403'd to public WebFetch and the cohort blog post does not describe the grid layout in detail. The blog states cohorts are "easily accessible from your main dashboard" and segmentable by first-product-purchased, discount code, demographics, channel, time bucket (daily/weekly/monthly/quarterly/annual).
- **Layout (prose):** "Top: time-bucket selector (daily/weekly/monthly/quarterly/annual), segmentation builder (Advanced+). Main canvas: cohort grid (structure not directly observed). Adjacent: Moby Chat sidebar for natural-language cohort queries." UI specifics beyond this not available without a paid trial.
- **Specific UI:** "Time-bucket selector + segmentation builder. Cohort cell structure not directly observable from public marketing pages. Mobile-app surface includes 'real-time MER, ncROAS & POAS' plus 60/90 LTVs as KPI tiles — these read as cohort-derived (cumulative LTV at 60 / 90 days)."
- **Filters:** First product purchased, discount code, demographics, channel, time bucket.
- **Data shown:** CLTV by cohort, repeat rate by cohort, ncROAS / nCAC (new-customer ROAS / CAC) at cohort granularity. 60/90 LTVs surfaced as headline mobile-app metrics.
- **Interactions:** Time-bucket toggle; segmentation builder; Moby Chat ("which campaign drove the most new customers last week?").
- **Why it works (from reviews/observations):** "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports." — Steve R., Capterra (Triple Whale profile). Cohort-specific verbatim love quotes are sparse — most Triple Whale praise is for Summary / attribution / Moby, not cohorts in isolation.
- **Source:** [triple-whale.md](../competitors/triple-whale.md), https://www.triplewhale.com/blog/cohort-analysis. UI structure not fully observed.

## Visualization patterns observed (cross-cut)

By viz type, count across the 9 competitors above:

- **Heatmap grid (rows = cohort, columns = period N, cells shaded by metric):** **7 competitors** — Lifetimely (gradient light→saturated), Peel (gradient — palette unverified; brief asserted blue but Peel profile flags this is not confirmed in public docs), Metorik (palette unconfirmed; site is monochromatic blue), Shopify Native (one of three coordinated views), Klaviyo (standard heatmap, palette undocumented), BeProfit (cohort table with green-triangle overlay), Triple Whale (structure not directly observed). **Heatmap is the dominant primary visualization** for this feature category.
- **Multi-line cohort curves ("spaghetti chart"):** **3 competitors** — Everhort (Average LTV by Cohort, dark-blue→light-blue lines for old→new + light-red blended-average + green baseline-when-filtered), Shopify Native (retention curve as one of three coordinated views), Peel (cohort curves as a separate widget type alongside the heatmap).
- **Stacked area chart (cohort × calendar time):** **1 competitor** — Everhort (Stacked Cohort Activity, click-band-to-isolate interaction).
- **Cumulative cohort waterfall with CAC payback line:** **1 competitor** — Lifetimely (vertical accumulating bars + user-configurable horizontal green CAC line).
- **Cohort grid with green-triangle CAC-payback marker per row:** **1 competitor** — BeProfit (auto-computed crossover point per cohort, no manual CAC entry needed).
- **Bar chart with side-by-side dual bars (filtered cohort vs unfiltered baseline):** **1 competitor** — Everhort (Forecasted LTV chart, light-green baseline + gray filtered).
- **Three coordinated views on one screen (heatmap + curve + table):** **1 competitor** — Shopify Native (the only tool that ships all three views of the same data side-by-side as the default cohort surface).
- **Triple-toggle subscription cohort heatmap (MRR↔subscribers × %↔raw × week↔month↔year):** **1 competitor** — Metorik (subscription-specific variant; closest analog at Peel for MRR cohorts but without the same explicit triple toggle in public docs).

Recurring visual conventions:

- **Cohort row labels on the left, period columns across the top** — universal across all 7 heatmap implementations.
- **Summary/average row at the bottom of the grid** — Metorik explicit ("weighted-average retention rate for each lifetime month"), Everhort (blended baseline line on charts is the analogue), Shopify Native (implied in coordinated table).
- **Color-gradient cells (light→saturated by value)** is the dominant cell encoding; **green/red ±deviation** is Everhort's specific tabular convention; **monochromatic blue** is asserted in the brief for Peel/Metorik but **not verbatim verified from public competitor docs** in either profile.
- **CAC-payback overlay** is a high-leverage primitive but only **2 of 9 competitors** ship it (Lifetimely's user-configurable green bar on the waterfall; BeProfit's auto-computed green triangle per row). Everhort exposes payback period as a column in the LTV Summary table, not as an overlay on the cohort grid.
- **First-touch / first-product / first-discount / country / tag slicing** is universal at the heatmap-filter-strip level (Lifetimely 8+, Peel 40+, Metorik 6 variants, BeProfit, Triple Whale).

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Single-glance CAC payback**
- "helped us understand our Lifetime Value better than anything else" — Blessed Be Magick, Shopify App Store, February 8, 2026 (Lifetimely profile).
- "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, Shopify App Store, January 27, 2026 (Lifetimely profile).
- "BeProfit is by far the most accurate and analytical one we've used." — Braneit Beauty, Shopify App Store, February 12, 2026 (BeProfit profile).
- "Been using BeProfit for years—absolutely essential. Clear, accurate, and powerful." — Satéur (Hong Kong), Shopify App Store, June 6, 2025 (BeProfit profile).

**Theme: Cohort depth + slicing flexibility**
- "best-in-class for a Shopify app, allowing you to segment customers by first purchase date, first product purchased, acquisition channel, geography, and more, with each cohort showing cumulative revenue, repeat purchase rate, and CAC payback period over time." — synthesised Lifetimely review observation (Lifetimely profile, Unique strengths).
- "Great app for all things retention and cohort analysis! Easy to use but also excellent service (hi, Jordan!) which has enabled me to have custom reports built out to explore problems unique to the business." — Koh, Shopify App Store, March 26, 2026 (Peel profile).
- "I have loved working in Peel for our customer retention and insights projects... My favorite features are the custom dashboards and audience building." — Saalt, Shopify App Store, September 17, 2024 (Peel profile).
- "Great app! This is by far the most advanced analytics tool for Shopify and Amazon." — Will Nitze, IQBAR, Shopify App Store, July 12, 2023 (Peel profile).

**Theme: Filtered-cohort-vs-baseline overlay**
- "We've used this tool for several months to track cohort retention rates" / "an excellent visualiser of retention." — LUXE Fitness (NZ), Shopify App Store, June 15, 2020 (Everhort profile).
- "the best LTV cohort analysis app I have found on Shopify" — D. Morse, Director of Performance Marketing, The Beard Club, September 8, 2020 (Everhort profile).
- "really good LTV analytics capabilities combined with best-in-class, highly responsive support" — Mighty Petz, Shopify App Store, December 15, 2023 (Everhort profile).

**Theme: Cohort-driven decisions on retention tactics**
- "I want to attack churn, increase LTV, improve order frequency and maximise transaction volume. With Metorik, I can do all that before I've had my morning coffee!" — John Lamerton, BIGIdea (Metorik profile).
- "Metorik brings WooCommerce reports to a whole new level." — Caspar Eberhard, Appenzeller Gurt (Metorik profile).
- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group (Klaviyo profile, adjacent retention-segmentation surface).

## What users hate about this feature

**Theme: Paywalled behind upgrade tiers**
- "At $149/month for the first paid tier, Lifetimely is a real line item, with stores doing under $30K/month potentially struggling to justify it." — ATTN Agency review, 2026 (Lifetimely profile).
- "the additional add on cause the product to be a bit limiting…but overall a useful tool for high level view" — Sur Nutrition, Shopify App Store, March 19, 2026 (Lifetimely profile, on Amazon-add-on paywall friction generally).
- "Advanced filtering capability has been made available to Pro & Plus customers" — Lifetimely's own docs after the AMP acquisition gated cohort filtering depth (Lifetimely profile).
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, December 2025 (Klaviyo profile, on the $100/mo Marketing Analytics add-on that gates cohort).
- "A bit more expensive than other analytics applications available, which can be a limitation for start-ups with limited budgets." — smbguide.com (Peel profile).

**Theme: Cohort surface trustworthiness at scale**
- "After scaling, [Lifetimely] started breaking in ways that actually cost them money. The interface gave the illusion of accuracy, but under the hood, it just wasn't reliable at the SKU level." — paraphrased Reddit/community sentiment (Lifetimely profile).
- "Not calculating the profit correctly. Calculation Preferences section not working properly." — Celluweg, Shopify App Store, January 17, 2026 (BeProfit profile, on profit-calc bugs that propagate into cohort LTV).
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson, Brightleaf Organics, workflowautomation.net, March 16, 2026 (Triple Whale profile).

**Theme: UI / discoverability friction**
- "Reporting is clunky and the UI buries things that should be front and center." — Darren Y., Capterra, April 2026 (Klaviyo profile).
- "Switching between views and reports can be slow sometimes" — bloggle.app review, 2024 (Polar profile).
- "Modifying reports or navigating menus is a cluster." — BioPower Pet, Shopify App Store, April 2, 2026 (Triple Whale profile).
- "the UI could improve" — Auriga (Spain), Shopify App Store, July 20, 2022 (Everhort profile, the only mildly critical review of an otherwise 100% 5-star product).

**Theme: Manual CAC entry + COGS gaps poison cohort math**
- "Transaction fees and handling costs are explicitly excluded from this scope." — Lifetimely cost-config docs (Lifetimely profile, Unique weaknesses).
- "COGS field is static" and doesn't update with supplier price changes — Putler summary of Shopify Native (Shopify Native profile).
- "Initial data entry [is] too much." — Sayed S., Capterra, January 2022 (BeProfit profile).
- Everhort: "If costs aren't available, it default[s] to 100% margin (net revenue)" — silent fallback that flatters cohort contribution-margin numbers (Everhort profile).

## Anti-patterns observed

- **Heatmap with no payback overlay.** Peel, Metorik, Shopify Native, Klaviyo, Triple Whale all ship the cohort heatmap without a CAC-payback marker on the cells. Users have to manually trace down a row and read off when cumulative LTV first crosses CAC — the exact arithmetic the heatmap was supposed to eliminate. Lifetimely's green-bar waterfall and BeProfit's green-triangle row marker are the only two competitors that close this gap in the visualization itself.
- **Cohort feature paywalled above the COGS feature.** Lifetimely (advanced filtering Pro/Plus only post-AMP), Klaviyo ($100/mo Marketing Analytics add-on), BeProfit ($149/mo Ultimate), Triple Whale ($259+/mo Advanced), Shopify Native (Customer cohort report exists on Grow but profit-by-cohort and custom report builder require $299/mo Advanced). For SMB owners on cheaper plans the cohort surface either doesn't exist or is reduced to retention-rate-only without LTV. Direct anti-pattern to "having data without having the feature" — the data is there, the feature is locked.
- **Silent COGS fallback to 100% margin.** Everhort's docs explicitly state "If costs aren't available, it default[s] to 100% margin (net revenue)." Cohort cells display as if the merchant is fully profitable. There is no badge/warning on the cell. This is a credibility-of-cohort-numbers risk that propagates through every downstream chart.
- **"Returning customer" defined too loosely upstream of cohort.** Shopify Native defines "returning customer" as anyone who placed >1 order ever — which is fine for the dashboard's repeat-rate card but creates a mismatch with the cohort report's stricter retention definition. Merchants who don't read the help center get inconsistent numbers between the Overview KPI and the cohort grid.
- **Cohort heatmap palette undocumented.** Peel, Metorik, Klaviyo, Triple Whale all ship a "color-gradient" heatmap with no public documentation of the palette, color-blind affordance, or thresholds. The brief asserts "monochromatic blue" for Peel and Metorik but neither competitor profile verifies this from public sources. Users can't tell whether two adjacent shades represent a 5% or 50% retention difference without hovering — not an anti-pattern in itself, but documentation lag is a recurring complaint.
- **Cohort grid as the only viz, no companion curve or table.** Klaviyo and BeProfit ship the cohort heatmap as a single view. Shopify Native is the only competitor that ships heatmap + curve + table in coordinated views — making the cohort easy to read at multiple zoom levels. Single-viz implementations force users into either "scan the heatmap" or "stare at numbers" without the middle-ground of a smooth retention curve.
- **CAC-payback marker requires manual CAC entry.** Lifetimely's green bar requires the user to type their CAC in. If the user has multiple acquisition channels with different CACs, the bar is wrong for any individual cohort. BeProfit auto-computes from ingested ad spend per cohort, which is structurally better but relies on the BeProfit "Google Ads only-counts-UTM-attributed-spend" attribution rule (which under-counts spend, per the BeProfit profile's "What users hate" section).
- **Cohort grid does not surface ongoing-period uncertainty.** Only Everhort flags incomplete-period cells with a dashed border in its tabular companion. Other competitors render partial-period cells the same as completed-period cells, leading users to misread current-month / current-week cohorts as if they were finished.

## Open questions / data gaps

- **Peel cohort heatmap palette.** The brief asserts "monochromatic blue Metorik vs Lifetimely's gradient." Lifetimely's profile confirms "color-gradient" cells. Peel's profile explicitly says "**Do not assert blue heatmap from this profile alone — it was instructed in the brief but not verified verbatim from public Peel sources.**" Metorik's profile similarly says "Couldn't confirm the cohort heatmap color palette from public sources… monochromatic blue is suggested by overall site theme but not confirmed by a verbatim screenshot caption." Verifying the brief's palette assertions requires paid-trial screenshots.
- **Triple Whale Cohort grid structure.** Triple Whale's KB is 403'd to public WebFetch. The cohort blog post describes accessibility and segmentation axes but does not describe whether the grid is a heatmap, a table, or a curve view. Brief mentioned Triple Whale only as "scan" — but the actual cohort UI specifics could not be observed from public sources without a paid trial.
- **Shopify Native cell coloring.** Shopify Help Center returns 403 on WebFetch. The cohort heatmap is documented as existing alongside the curve and table, but exact cell colors, hover behavior, and per-cell drill-down behavior are not verifiable from public sources.
- **Klaviyo cohort grid layout.** Klaviyo's RFM Sankey and CLV stacked bar are heavily documented, but the cohort heatmap inside Marketing Analytics has no public documentation of layout, palette, or interaction patterns. Likely requires a paid Marketing Analytics seat to verify.
- **Polar Analytics cohort cell palette.** Polar profile only documents the broader BI builder; the cohort-block specific palette is not in public docs. Reviewers describe Polar's overall UI as "functional" but "lacks the visual polish seen in some competitors like Triple Whale."
- **CAC-payback marker behavior on multi-channel cohorts.** Lifetimely's green bar takes a single user-entered CAC; unclear how this is supposed to apply when a single cohort grid shows mixed-channel cohorts. BeProfit's green triangle is documented as "estimated point in time at which the LTV matches the CAC" but the CAC attribution rule (UTM-only on Google) is documented elsewhere in the BeProfit profile as a recurring complaint vector.
- **Saras Pulse** appears in the feature index's "Top competitors known to do this well" row but is **not in the competitors directory** (`/home/uros/projects/nexstage/docs/competitors/competitors/`). No profile, no quotes, no UI details — the competitor was not researched in the previous batch and is therefore omitted from this profile. Worth flagging upstream.
- **Fospha** — present in the competitors directory but not read in this profile pass. May or may not implement cohort retention; has not been confirmed to or excluded from this list.

## Notes for Nexstage (observations only — NOT recommendations)

- **Heatmap dominates: 7 of 9 competitors ship a heatmap-grid as the primary cohort visualization.** Lifetimely and BeProfit add an overlay (green bar / green triangle) on top of the heatmap or its waterfall companion; Everhort is the outlier that uses multi-line + stacked-area instead of a heatmap as the primary chart.
- **Only 2 of 9 competitors ship a CAC-payback overlay.** Lifetimely (user-configurable horizontal green line on cohort waterfall) and BeProfit (auto-computed green triangle per cohort row). 7 of 9 leave the user to read off payback from the heatmap themselves. This is a high-leverage gap — both implementations are repeatedly cited as distinctive in their respective competitor profiles.
- **Shopify Native is the only competitor that ships heatmap + curve + table as three coordinated views of the same cohort dataset.** Most third-party tools pick one. The "three views, one dataset" pattern would be a direct match to Shopify SMB owners' existing mental model.
- **Filter slicing depth is a hard differentiator.** Lifetimely (8+ slices), Peel (40+), Metorik (6 cohort variants + saved-segment overlay), BeProfit, Triple Whale all expose first-product / first-discount / first-channel / country / tag slicing. Everhort has only one customer-tag filter and no marketing-attribution layer. Slicing depth correlates with positive review density.
- **Subscription cohorts are a separate surface in the two tools that target subscription brands** — Metorik (Subscription Cohorts Report with MRR↔subscribers triple toggle) and Peel (17 subscription-cohort metrics). Other competitors fold subscription metrics into the standard cohort grid or skip them. For Recharge/Skio-heavy Nexstage workspaces this is a documented pattern.
- **The brief's "Lifetimely vs Metorik palette gradient" framing is not fully verified.** Lifetimely's profile says "color-gradient cells (light→saturated)" verbatim; Metorik's profile says monochromatic blue is suggested by overall site theme but **not confirmed**; Peel's profile explicitly warns against asserting blue from public docs alone. The downstream UX synthesis should treat any palette claims as paid-trial-required.
- **Everhort's "filtered cohort vs unfiltered baseline overlay on every chart" is a strong UX motif** that no other competitor matches. When a user applies a filter, the chart shows both the filtered cohort and the original baseline as separate series in distinct colors (light-green baseline / gray filtered for forecasted LTV bars; green baseline / cohort-colored series for the LTV line chart). This is a "compare segment to baseline" pattern with a single-glance differential.
- **Everhort is also the only tool that flags ongoing/incomplete periods with a dashed border in its tabular cohort views.** Eliminates a class of misreading where current-period cohorts look completed.
- **Cohort metric depth ranges from 5 (Shopify Native) to 36 (Peel) to "13+" (Lifetimely).** No clear correlation with price tier — Shopify Native at $299/mo has 5; Peel at $199/mo has 36. Metric depth seems to track product-research investment rather than pricing.
- **Cell-color palette is universally undocumented in public sources.** Across all 9 competitors, only Everhort publishes its palette rule explicitly (green if ≥ blended average, red if below; bucketed by ±1/2/3 standard deviations). This is a publicly-checkable signal that Everhort takes statistical literacy seriously; all others rely on visual gradients without documenting the threshold math.
- **Lifetimely's CAC-payback green bar is user-entered, not computed.** The user types in their CAC manually, the bar renders at that y-value. BeProfit's green-triangle is auto-computed from ingested ad spend. Auto-computed has the obvious advantage but requires that CAC attribution rules are correct — and BeProfit's attribution rule (UTM-only Google) is one of its biggest user complaints.
- **Cohort retention is universally read-only.** No competitor ships any "click cohort row → take action" affordance other than Peel's "save cohort as Audience and push to Klaviyo" and Triple Whale's "Audience Sync to Meta from RFM/cohort segment." For SMB Shopify owners, the dominant interaction is "look, then act elsewhere" — no in-app remediation flow.
- **Klaviyo's CLV stacked-bar primitive (blue historic + green predicted, with diamond next-order tick on the timeline)** is per-customer rather than per-cohort, but it is the single most visually distinctive retention-prediction primitive in the category. Different surface, but adjacent design language.
