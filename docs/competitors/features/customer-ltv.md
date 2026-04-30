---
name: Customer LTV
slug: customer-ltv
purpose: Answer "what is a customer worth to me, by source / cohort?" — separating historic value already realized from predicted future value, sliced by acquisition channel, first product, discount, geography, or segment.
nexstage_pages: profit, dashboard, customers
researched_on: 2026-04-28
competitors_covered: lifetimely, peel-insights, polar-analytics, klaviyo, lebesgue, wicked-reports, everhort, repeat-customer-insights, glew, daasity, triple-whale
sources:
  - ../competitors/lifetimely.md
  - ../competitors/peel-insights.md
  - ../competitors/polar-analytics.md
  - ../competitors/klaviyo.md
  - ../competitors/lebesgue.md
  - ../competitors/wicked-reports.md
  - ../competitors/everhort.md
  - ../competitors/repeat-customer-insights.md
  - ../competitors/glew.md
  - ../competitors/daasity.md
  - ../competitors/triple-whale.md
  - https://useamp.com/products/analytics/lifetime-value
  - https://help.useamp.com/article/682-cohort-analysis-use-cases
  - https://1800dtc.com/breakdowns/lifetimely
  - https://help.peelinsights.com/docs/rfm-analysis
  - https://www.peelinsights.com/post/your-guide-to-cohort-analysis
  - https://help.klaviyo.com/hc/en-us/articles/17797865070235
  - https://help.klaviyo.com/hc/en-us/articles/360020919731
  - https://lebesgue.io/customer-lifetime-value
  - https://help.wickedreports.com/guide-to-cohort-and-customer-lifetime-value-reporting
  - https://www.wickedreports.com/wicked-recharge
  - https://help.everhort.com/article/8-ltv-by-cohort-chart
  - https://help.everhort.com/article/10-ltv-summary
  - https://www.littlestreamsoftware.com/articles/using-the-cohort-revenue-report-to-see-how-your-customers-are-buying-over-time/
  - https://help.daasity.com/core-concepts/dashboards/report-library/acquisition-marketing/ltv-and-rfm
  - https://www.triplewhale.com/blog/cohort-analysis
---

## What is this feature

Customer LTV is the answer to "given everything I know about how this customer behaves so far — and what people who started like them did next — how much revenue (or contribution margin) will they generate over their relationship with my store?" SMB Shopify and Woo merchants ask this in two distinct moments. First, when setting paid-acquisition budgets: "what's the maximum CAC I can pay before this channel becomes unprofitable across the customer's lifetime, not just first order?" Second, when prioritizing retention investment: "which cohorts (by first product, discount, channel, country) drive the highest LTV, and where should I lean in or pull back?"

The difference between "having data" and "having this feature" is sharp here. Every storefront has order and customer history; every ad platform reports ROAS on first order. The feature is the synthesis: cumulative revenue per customer plotted against months-since-acquisition, sliced by the dimension that acquired them, ideally with predicted forward LTV stacked on top of historic LTV, and a CAC-payback marker that turns the chart into a binary "this channel pays back in month X" answer. Without that synthesis, merchants scale on first-order ROAS and either overpay (consumables that rebuy) or underpay (one-shot products with no repeat).

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.id`, `orders.customer_id`, `orders.processed_at`, `orders.total_price`, `orders.total_discounts`, `orders.refunds`, `orders.line_items.cost` (Shopify "Cost per Item"), `orders.source_name` (channel attribution proxy), `orders.discount_codes`, `customers.tags`, `customers.country`, `products.id`, `products.tags`, `line_items.product_id`
- **Source: Computed (cohort assignment)** — `cohort_period = date_trunc('month'|'week'|'quarter', first_order.processed_at)` per customer; `months_since_acquisition = date_diff(order.processed_at, customer.first_order_at, 'month')`
- **Source: Computed (LTV variants)** — `cumulative_revenue_per_customer = SUM(orders.total_price - orders.refunds) GROUP BY customer_id`; `cumulative_contribution_margin_per_customer = SUM((orders.total_price - orders.refunds) - SUM(line_items.cost * quantity)) GROUP BY customer_id`; `accumulated_LTV_at_month_N = SUM(cumulative_revenue) WHERE months_since_acquisition <= N`
- **Source: Meta Ads / Google Ads / TikTok / Klaviyo / etc.** — `campaigns.spend`, `campaigns.attributed_conversions`, UTM parameters (`utm_source`, `utm_medium`, `utm_campaign`) — used to slice cohorts by acquisition channel and to compute CAC for the payback overlay
- **Source: User-input (CAC)** — `target_CAC` per cohort or blended (used to position the green CAC-payback bar overlay); `cogs_per_product` when not supplied by Shopify cost field
- **Source: Recharge / Skio / subscription platforms** — `subscriptions.charges`, `subscriptions.churned_at`, `subscriptions.next_charge_at` — for subscription LTV and rebill-aware re-attribution (Wicked Reports patents-pending pattern)
- **Source: Fairing / KnoCommerce (post-purchase survey)** — `survey_response.channel` — used as a cohort-slice dimension where pixel and UTM tracking fail (Daasity "Survey-Based Channel" dimension)
- **Source: Predictive model (where applicable)** — historical order trajectories per cohort feed Prophet / linear-regression / proprietary churn-and-frequency models to produce `predicted_LTV_at_horizon_{3,6,9,12,24,36}_months`
- **Source: Computed (CAC ratio)** — `target_CAC_ceiling = forecasted_LTV / 3` (Everhort's transparent rule; industry default fallback)

## Data outputs (what's typically displayed)

- **KPI: Average LTV per customer** — `SUM(orders.total_price - orders.refunds) / COUNT(DISTINCT customer_id)`, USD, vs prior-period delta, segmentable by cohort dimension
- **KPI: Predicted LTV (horizon: 365d / 12mo / 24mo / 36mo)** — modeled forward projection, USD, often shown as a stacked extension on top of historic LTV
- **KPI: LTV : CAC ratio** — computed live (CLAUDE.md rule: never stored), unitless ratio, healthy at >=3
- **KPI: Payback period (months)** — first month where cumulative LTV crosses CAC threshold
- **Dimension: Cohort period** — month / week / quarter, ~12-60 distinct values
- **Dimension: Acquisition channel** — Direct, Email, Paid Social, Paid Search, Organic, etc., ~10-15 values
- **Dimension: First product** — SKU or product_id, hundreds-thousands of values (paginated/top-N)
- **Dimension: Discount code on first order** — string, dozens of values
- **Dimension: Country / region** — ISO codes, ~30-100 values
- **Breakdown: Cumulative LTV × cohort × months-since-acquisition** — heatmap matrix or multi-line chart
- **Breakdown: LTV × first-product** — ranked table
- **Breakdown: LTV × channel × time** — line chart or stacked area
- **Slice: Per-customer profile** — predicted CLV, expected next order date, churn risk, full order timeline
- **Slice: Stacked composition (historic + predicted)** — single bar showing what's earned vs what's modeled

This becomes the column list for cohort tables, the axes for cohort heatmaps and cumulative-LTV line charts, and the per-customer drill-down panel.

## How competitors implement this

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Sidebar > Lifetime Value Report (cohort heatmap is the headline screen); LTV Drivers Report as a sub-tab; predictive overlay toggle inside the same view. CAC-payback green bar lives inside the cohort waterfall variant of the LTV report.
- **Visualization:** Color-gradient heatmap matrix (rows = cohort start period, columns = months-since-first-order, cells = chosen accumulated-metric, light-to-saturated gradient). Cohort waterfall is a vertical accumulating-bar chart with a user-positioned horizontal green CAC threshold line.
- **Layout (prose):** "Top: filter strip for first-product / channel / country / discount / tags. Left rail: cohort-time-period toggle (weekly / monthly / yearly). Main canvas: heatmap matrix with 13+ selectable metrics in cells. Bottom: predictive-LTV overlay toggle. Cohort waterfall view replaces the matrix when selected — vertical bars per month with the user's CAC entered as an annotation line."
- **Specific UI:** "User-configurable horizontal green line/bar denoting CAC threshold; bar overlays a vertical accumulating-revenue-per-customer chart so the payback month is read off where bar height crosses the green line. Cells in the heatmap render in a color gradient (light → saturated) where higher accumulated values render darker. Pencil-icon edit affordance on the underlying cohort filter chips."
- **Filters:** Cohort time period (weekly/monthly/yearly), first-touch source/medium, last-touch source/medium, marketing channel, country, customer tags, order tags, discount codes, first-order product, first-order product category.
- **Data shown:** Accumulated sales per customer, accumulated gross margin per customer, accumulated contribution margin per customer, total repurchasing customers, accumulated orders per customer, AOV by cohort, repurchase rate, predicted LTV at 3/6/9/12 months — 13+ total metric options.
- **Interactions:** Filter chip add/remove; metric dropdown swap; weekly/monthly/yearly toggle; predictive-overlay toggle; user enters CAC value to position green bar; click-cohort drill-down implied but not directly verified in public sources.
- **Why it works (from reviews/observations):** "helped us understand our Lifetime Value better than anything else" (Blessed Be Magick, Shopify App Store, Feb 8, 2026); "removes the hassle of calculating a customer's CAC and LTV" (ELMNT Health, Jan 27, 2026); "invaluable for understanding of your MER, customer cohorts and LTV" (Radiance Plan, Apr 2, 2026). The 13+ cohort metric options + green CAC bar are repeatedly cited as best-in-class for a Shopify app.
- **Source:** [lifetimely.md](../competitors/lifetimely.md); https://useamp.com/products/analytics/lifetime-value; https://1800dtc.com/breakdowns/lifetimely.

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Sidebar > Cohort Analysis (or via Essentials tab). RFM landing page exposes per-cohort LTV per customer in the North Star strip; Audiences view scopes LTV to a built segment.
- **Visualization:** Cohort heatmap table with cumulative-LTV cells; paired cohort-curve chart and pacing graph; "Tickers" (single-number KPI cards) and "Legends" (graphs) for dashboard composition.
- **Layout (prose):** "Top: date-range and cohort grouping toggle (month/week/quarter). Left rail: tree of 36 cohort metrics organized into Cohorts Retention (8), Cohorts Revenue (8), Subscription Cohorts (17), Customer (3). Main canvas: cohort table with rows = acquisition month, columns = months 0, 1, 2... Bottom: associated cohort-curve and pacing graph."
- **Specific UI:** "Cohort table with rows stacked vertically per cohort, periods on horizontal axis. Drilldown into 'Single Cohort View' surface on row click. Magic Insights AI headline plus description rendered above each cohort widget — refreshes every 7 days for dashboards viewed in the past 7 days. Newspaper-style narrative format ('look like newspaper headlines with revenue opportunities being delivered to you everyday')."
- **Filters:** Cohort time period, channel, product, discount code, customer tag, audience membership.
- **Data shown:** LTV by Cohort, Lifetime Revenue by Cohort, Cohort AOV per Month, Discounts/Refunds by Cohort, Customers Returning Rate, Days Since First Order, Repurchase Rate by Cohort, Repeat Orders Rate per Cohort, plus 17 subscription-cohort variants.
- **Interactions:** Switch grouping (month/week/quarter); drill into cohort row; save report to dashboard; schedule via email/Slack; Magic Dash conversational input that auto-builds a cohort dashboard from a natural-language question; export audience built from cohort to Klaviyo/Attentive/Meta.
- **Why it works (from reviews/observations):** "Great app for all things retention and cohort analysis!" (Koh, Shopify App Store, March 26, 2026); "Their reporting capabilities are so robust that the ability to customize your search seems unlimited" (Bridget Laye, Saalt). The depth (36 metrics) and the Magic-Dash narrative wrapper are the cited differentiators.
- **Source:** [peel-insights.md](../competitors/peel-insights.md); https://www.peelinsights.com/post/your-guide-to-cohort-analysis.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Pre-built Retention & LTV page; Personas (identity-based segmentation, launched April 2025); Custom Dashboard blocks composed from semantic-layer metrics.
- **Visualization:** Cohort retention table (rows = cohort, columns = period N), cumulative-LTV line chart, customer-LTV-by-cohort metric card with sparkline. Persona cards on the Personas surface.
- **Layout (prose):** "Top: date-range selector (top-right). Left rail: folder tree of dashboards. Main canvas: vertical stack of blocks — Key Indicator Section (grid of LTV/CAC/repeat-rate metric cards with optional targets), then cohort tables and curves below. Sparkline cards embed a mini trend line inside the metric card itself."
- **Specific UI:** "Three block primitives: Key Indicator Section (KPI grid with targets), Tables/Charts (custom report block built from metric × dimension × granularity × filter composer), Sparkline Card (metric card with embedded mini trend line). Comparison indicators (improvement / decline arrows) render automatically off the dashboard date range. No-code formula builder lets users define LTV variants (e.g., 'net LTV = LTV - cogs - shipping')."
- **Filters:** Date range; Views (saved-filter bundles spanning multiple data sources, grouped into Collections by store/region/channel); attribution-model picker (9-10 models: First Click, Last Click, Linear, U-Shaped, Time Decay, Paid Linear, Full Paid Overlap, Full Paid Overlap + Facebook Views, Full Impact).
- **Data shown:** LTV per customer, LTV by cohort, repeat rate, cohort retention, contribution margin, CAC, LTV:CAC. Side-by-side attribution columns (Platform / GA4 / Polar Pixel) for channel-LTV slicing.
- **Interactions:** Switch attribution model from dropdown — same KPI block re-renders; drill from channel → campaign → ad → order → customer journey; Ask Polar AI chat that produces an editable Custom Report (not a frozen chat answer).
- **Why it works (from reviews/observations):** "Polar solved all of our analytic issues" (Vitaly, Shopify App Store, March 2025); "Their multi-touch attribution and incrementality testing have been especially valuable for us" (Chicory, Sept 2025). The semantic-layer + custom-metric-builder pattern is repeatedly praised; per-cohort LTV is one of many composable views rather than a single screen.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md); https://intercom.help/polar-app/en/articles/10430437-understanding-dashboards.

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Marketing Analytics > Customer insights > CLV dashboard (paywalled behind $100/mo Marketing Analytics add-on or $500/mo Advanced KDP). Per-customer predictive panel on Profiles > [profile] > Metrics and insights tab.
- **Visualization:** **Horizontal stacked bar (blue historic + green predicted)** at the per-profile level; five vertically stacked dashboard cards (Current Model, Segments Using CLV, Upcoming Campaigns, Flows Using CLV, Forms Using CLV). Diamond-shaped tick mark on the order timeline for predicted next-order date. Card grid of predictive metrics on profile pages.
- **Layout (prose):** "Top: Current Model card pinned showing historic date range, predicted date range, last model retrain date, and five example customer profiles demonstrating the calculation. Below: stacked rows of usage tables (Segments Using CLV, Campaigns Using CLV, Flows Using CLV, Forms Using CLV). On the per-profile page: card grid with each predictive metric as its own card."
- **Specific UI:** "Horizontal stacked bar where the blue segment is historic CLV (already spent) and the green segment is predicted CLV (next 365 days); the full bar represents Total CLV. On the profile's order timeline, diamond-shaped tick marks mark past orders and the predicted next-order date. Status pills (Live / Manual / Draft / Scheduled / Sending) on usage tables. Churn-risk uses traffic-light color coding — green for low risk, yellow for medium, red for high."
- **Filters:** CLV-window selector (Marketing Analytics tier customizes prediction window beyond 365d default); date range; segment membership.
- **Data shown:** Predicted CLV, Historic CLV, Total CLV, Predicted Number of Orders, Historic Number of Orders, AOV, Average Days Between Orders, expected next-order date, churn risk score, predicted gender.
- **Interactions:** Click any segment row to view profiles; click campaigns/flows/forms to navigate into builders; customize prediction window in Marketing Analytics tier; predictive features hide until thresholds are met (500+ customers with orders, 180+ days history, 3+ repeat purchasers).
- **Why it works (from reviews/observations):** "I love being able to go in and see how each message performed with each RFM segment" (Christopher Peek, The Moret Group, Klaviyo features page). The stacked-bar visualization (historic blue + predicted green) is the primitive that bridges "what happened" and "what's modeled" without needing a chart — quoted in user reviews and is a direct analog to source-of-truth-vs-prediction lensing.
- **Source:** [klaviyo.md](../competitors/klaviyo.md); https://help.klaviyo.com/hc/en-us/articles/17797865070235; https://help.klaviyo.com/hc/en-us/articles/360020919731.

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Sidebar > Customer/LTV section. LTV & Cohort dashboard surfaces predicted LTV plus retention rate plus cohort grids; CAC vs LTV comparison block lives on the same page.
- **Visualization:** Cohort grid grouped by week/month/year with channel filter; predicted LTV value cards; CAC-vs-LTV comparison block (specific chart type not enumerated in public sources beyond "value cards" / "comparison block").
- **Layout (prose):** "Top: channel filter (Google/Meta/TikTok), product filter, geography filter (state/country). Main canvas: cohort grid + predicted LTV cards + CAC vs LTV side-by-side block + revenue/cost/profitability breakdowns + high-LTV products list."
- **Specific UI:** "Color-coded performance indicators use blue for improvements and red for declines (note: blue, not green, for positive deltas — explicit in their feature page). Henri AI chat embedded as a sidebar / dedicated surface; responses include inline charts, time-based breakdowns, Key Takeaways block, Recommendations block."
- **Filters:** Marketing channel (Google/Meta/TikTok), product, geography (state/country), cohort time period (week/month/year).
- **Data shown:** Average LTV, predicted LTV, retention rate by cohort, CAC vs LTV, high-LTV products. Five attribution models exposed via Le Pixel: Shapley Value, Markov Chain, First-Click, Linear, Custom.
- **Interactions:** Filter by channel/product/geography; switch attribution model (per Le Pixel page); ask Henri natural-language questions about LTV; weekly PDF MMM report includes LTV-aware budget redistribution.
- **Why it works (from reviews/observations):** "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible" (Fringe Sport, Shopify App Store, Oct 28, 2025); "This is far and away the best [compared to Lifetimely, Databox]" (Kiwi Nutrition, Apr 9, 2026). The bundle of attribution + LTV + MMM + competitor benchmarks under one AI agent is the cited differentiator.
- **Source:** [lebesgue.md](../competitors/lebesgue.md); https://lebesgue.io/customer-lifetime-value.

### Wicked Reports ([profile](../competitors/wicked-reports.md))
- **Surface:** Reports section — four cohort-family reports: Customer Cohort, New Lead Cohort, Product Cohort, Customer LTV (legacy). Per-customer journey drilldown reachable from any cohort row.
- **Visualization:** Cohort matrix (rows = acquisition months, columns = subsequent months of accumulated revenue per cohort, cells = lifetime value built up). FunnelVision adds **side-by-side comparison columns of Wicked-attributed ROAS vs. Facebook-reported ROAS** per campaign — a two-source compare on the cohort lens.
- **Layout (prose):** "Top: filter strip allowing breakdown by 'attributed source, campaign, ad, email, and targeting that generated the new lead or customer'. Main canvas: cohort matrix. Side panel / drilldown: vertical timeline-style customer-journey view from per-customer click."
- **Specific UI:** "Cohort matrix with rows = acquisition month and columns = subsequent accumulated months. Top-bar filter chip re-slices the cohort by acquired-source/campaign/ad/email/targeting. Per-customer drilldown shows full tracked journey including marketing-attribution contributions to LTV. Color-coded dashboards described by reviewers as 'green for growth and red for issues' (marketingtoolpro.com)."
- **Filters:** Attributed source, campaign, ad, email, targeting (UTM-derived); attribution model (First Click / Last Click / Linear / Full Impact / New Lead / ReEngaged Lead — six total); lookback / lookforward window (infinite).
- **Data shown:** Cumulative customer LTV per cohort over time; attributed marketing credit sources per cohort; subscription rebill revenue (when ReCharge integrated); patents-pending continuous re-pricing as subscriptions rebill.
- **Interactions:** Filter cohort by acquired source; click a customer record to open full journey detail; one-click attribution-model swap ("Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time" — marketingtoolpro.com); 5 Forces AI weekly verdict (Scale / Chill / Kill) per campaign.
- **Why it works (from reviews/observations):** "Wicked Reports allows us to optimize Facebook ads to those with the highest ROI and just not the cheapest lead. Nothing else on the market remotely compares." (Ralph Burns, Tier 11 CEO); "[Got] 500% ROI from some of the traffic sources" when analyzing "lifetime value of customers over time." (Henry Reith). The breakthrough is that LTV reads as a 2D matrix while attribution acts as a slicer.
- **Source:** [wicked-reports.md](../competitors/wicked-reports.md); https://help.wickedreports.com/guide-to-cohort-and-customer-lifetime-value-reporting; https://www.wickedreports.com/wicked-recharge.

### Everhort ([profile](../competitors/everhort.md))
- **Surface:** Reports section. Average LTV by Cohort Chart (likely default), Stacked Cohort Activity Chart, Forecasted Average LTV (LTV Summary), Cohort Retention Chart, Averages Chart. Filters panel applies globally.
- **Visualization:** **Multi-line chart** for Average LTV by Cohort (one line per acquisition month, blue gradient by recency) — NOT a heatmap. Stacked area chart for Cohort Activity. **Two-column layout** for Forecasted LTV: bar chart (left) with projected LTV at 1Y/2Y/3Y horizons + metrics table (right) listing CAC targets and payback periods. Heatmap-style coloring exists only in tabular companion views (green/red ±1/2/3 stdev shading).
- **Layout (prose):** "Top: filter strip (Customer / Order / Channel) applying globally. Main canvas: line chart with cohort-age on X (months since acquisition) and cumulative LTV on Y, one line per cohort. Below: tabular view companion with green/red heatmap-shaded cells. CSV export link beneath the table."
- **Specific UI:** "Darker blue lines indicate older cohorts; lighter blue lines represent newer cohorts. A light red line displays a blended average of recent monthly cohorts as a baseline. When filters are active, **a green line represents the blended unfiltered (baseline) average LTV** so users see filtered vs unfiltered side-by-side on the same chart. Forecast view: light green bars show baseline performance, gray bars represent filtered cohort performance — direct visual A/B between filtered cohort and unfiltered baseline. Cells representing ongoing/incomplete periods display a dashed border so users don't misread partial-month data."
- **Filters:** Customer (tagged), Order (Product Collections / Product Name / Product Type / Product Properties / Discounts), Channel. Each order filter has a first/subsequent/any-purchase qualifier.
- **Data shown:** Cumulative LTV per customer at each month-of-age (e.g., "$1,103 per customer after 7 months" cited example); blended-average LTV; baseline LTV when filtered; forecasted LTV at 1Y/2Y/3Y; recommended CAC ceiling at LTV/CAC=3 ratio; payback period.
- **Interactions:** Global filter chip add/remove; %/absolute toggle on retention chart; click-band-to-isolate on stacked area chart; CSV export under every report.
- **Why it works (from reviews/observations):** "the best LTV cohort analysis app I have found on Shopify" (The Beard Club, Sept 8, 2020); "really good LTV analytics capabilities combined with best-in-class, highly responsive support" (Mighty Petz, Dec 15, 2023); "an excellent visualiser of retention" (LUXE Fitness, June 15, 2020). Filter-vs-baseline overlay is the core analytical motif and is repeatedly cited as the value driver.
- **Source:** [everhort.md](../competitors/everhort.md); https://help.everhort.com/article/8-ltv-by-cohort-chart; https://help.everhort.com/article/10-ltv-summary.

### Repeat Customer Insights ([profile](../competitors/repeat-customer-insights.md))
- **Surface:** Sidebar > Cohorts (Cohort Revenue Report); also First Product Analysis (Nth Product Analysis) ranks LTV by first-product. Customer Grid (5x5 RFM) provides cohort-style segmentation per customer.
- **Visualization:** Classic cohort triangle — rows = cohort acquisition month, columns = elapsed months since first order, cells = revenue that cohort generated in that elapsed month, final column = lifetime cohort revenue. No documented heatmap intensity in public sources (raw numeric cells in a triangle). First Product Analysis is a tabular ranked list.
- **Layout (prose):** "Cohort triangle. Each cell = revenue per cohort × elapsed month. Lookback gated by tier (12 cohorts on Entrepreneur, full history on Growth/Peak). Doc example: '2014-12 cohort shows $1,098.80 in orders in Month 0, then $169.92 in Month 1... $4,392.09 across all months.'"
- **Specific UI:** "A month will be blank if there were no orders for that cohort in that month or if the date is in the future. Customer Grid is a 5x5 spatial matrix with two RFM dimensions on axes; clicking a cell drills into the named segment with marketing advice."
- **Filters:** Date drill-down (all-time + current/previous year on Entrepreneur; quarterly + 4-year on Growth; per-quarter on Peak); 3/11/41 Shopify acquisition sources by tier.
- **Data shown:** Revenue per cohort × elapsed month; lifetime cohort total; per-first-product Repeat Purchase Rate and Total LTV; A-F letter grade per customer (recency-weighted RFM).
- **Interactions:** Tier-gated lookback; CSV export; daily auto-recompute of grades; push segment membership to Shopify customer tags / Klaviyo (Growth tier+).
- **Why it works (from reviews/observations):** "COHORT ANALYSIS automatically created from your store is unbelievable and a must-have for any startup focused on growth" (pantys, June 14, 2019); "Great app to keep track of your customer cohorts and stay on top of LTV" (Pacas, May 15, 2023). The First Product Analysis (LTV ranked by first-product purchased) is a distinctive cohort lens.
- **Source:** [repeat-customer-insights.md](../competitors/repeat-customer-insights.md); https://www.littlestreamsoftware.com/articles/using-the-cohort-revenue-report-to-see-how-your-customers-are-buying-over-time/; https://www.littlestreamsoftware.com/articles/measuring-how-the-products-in-the-first-order-influence-customer-repurchases/.

### Glew ([profile](../competitors/glew.md))
- **Surface:** Customers > Lifetime Value > LTV Profitability by Channel (sub-page under Customers, confirmed via Glew search index). Customer Segments surface exposes per-segment LTV with RFM scoring. Custom Reports (Looker-powered, Glew Plus tier) support arbitrary cohort joins.
- **Visualization:** UI details not directly observed beyond navigation path; marketing screenshot titles include "KPI Highlights", "Performance Channels", "Net Profit by Channel" — implying tile/card layout consistent with the rest of Glew. Custom-report builder is Looker drag-and-drop with prebuilt LookML.
- **Layout (prose):** UI details not available — only the navigation path "Customers > Lifetime Value > LTV Profitability by Channel" was confirmed via the search index. Customer Profile pages show "indicators like the current status, orders & returns, and the total spend".
- **Specific UI:** UI details not available — Glew's app sits behind a paywall and sales-led demo gate. Public sources describe segments with "55+ filterable metrics and 15 product-specific metrics", percentile-based filtering, and bidirectional Klaviyo sync, but no concrete LTV-screen UI specifics.
- **Filters:** 300+ filter options on Glew Pro; cross-platform filtering (Loyalty Lion + Yotpo + Zendesk joins); RFM scoring; percentile-based filters.
- **Data shown:** Revenue, Profit, Orders, AOV, Visits, Conversion rate, CAC, LTV, ROAS, channel-specific performance, "LTV Profitability by Channel", "Net Profit by Channel", inventory aging.
- **Interactions:** Push segment to Klaviyo as audience; CSV export of any table; schedule reports via email/Slack; SQL access via BI Tunnel on Glew Plus.
- **Why it works (from reviews/observations):** "easy to comprehend dashboards at your fingertips with actionable insights" (Jonathan J S., Capterra Oct 2019); "far more accurate than Google Analytics" (Alex C., Capterra Nov 2018). LTV Profitability by Channel as a named sub-page is the differentiator — most competitors show LTV by channel as a column inside another table, not as its own surface.
- **Source:** [glew.md](../competitors/glew.md).

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Templates Library > Acquisition Marketing > LTV & RFM. Department-organized IA puts LTV inside Acquisition Marketing rather than Customers.
- **Visualization:** **"Layer Cake Graph"** — explicitly named in Daasity docs. Breaks down customers by the quarter they were acquired and stacks each cohort's revenue contribution over time. Other sections exist on the dashboard but aren't enumerated in public docs.
- **Layout (prose):** "Templates Library > Acquisition Marketing > LTV & RFM. Layer Cake Graph stacks quarterly cohort revenue over calendar time so newer quarters layer on top of earlier ones. Embedded Looker tiles below. Department-organized IA — LTV lives under Acquisition Marketing department, not under a Customers tab."
- **Specific UI:** "Layer Cake Graph — stacked area chart where each layer is one acquisition quarter's revenue contribution stacked over calendar time. UI specifics beyond this not surfaced in public docs (login required for full layout)."
- **Filters:** Department lens (Ecommerce / Marketing / Retail tabs on Home); Dynamic Attribution Method filter exposes 8 models (First-Click, Last-Click, Assisted, Last-Click + Assisted, Last Ad Click, Last Marketing Click, Survey-Based, Vendor-Reported) plus Custom Attribution waterfall; Discount Code Attribution as a parallel dimension.
- **Data shown:** Quarterly cohort revenue contribution; RFM segment tags; HVC (high-value-customer) classification; churn cohorts; channel-mix %.
- **Interactions:** Switch attribution model via Dynamic Attribution Method filter without rebuilding the report; rank attribution sources for Custom Attribution waterfall (e.g., survey → discount-code → GA last-click priority).
- **Why it works (from reviews/observations):** "I've used Glew, TripleWhale, Lifetimely and more and this is by far the best tool I've used. The customer support is unparalleled and they can actually get me answers to questions I've been trying to get at for months." (Béis, March 3, 2022). The Layer Cake Graph + 8-model attribution toggle on cohort LTV is the depth differentiator.
- **Source:** [daasity.md](../competitors/daasity.md); https://help.daasity.com/core-concepts/dashboards/report-library/acquisition-marketing/ltv-and-rfm.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Cohorts (Advanced+ tier). Cohort Analysis / CLTV is a named module. Mobile app surfaces 60/90 LTVs as headline numbers.
- **Visualization:** Cohort segmentation builder. Public sources reference a single dashboard screenshot in the cohort blog post but don't describe the specific grid/heatmap structure. Likely standard cohort retention grid (rows = cohort, columns = period N), but **not directly verified from public sources**.
- **Layout (prose):** "Sidebar > Cohorts. Time-bucket selector (daily/weekly/monthly/quarterly/annual). Segmentation builder lets users segment by first-product-purchased, discount code, demographics, channel. Mobile-app version surfaces 60/90 LTVs as KPI tiles."
- **Specific UI:** "UI structure not fully observed from public sources. Triple Whale's KB articles 403'd to WebFetch in research; cohort-grid layout is referenced as 'easily accessible from your main dashboard' but specific column/row structure, color encoding, and hover behavior were not directly observable. Real-time pixel events feed cohort assignment."
- **Filters:** First-product-purchased, discount code, demographics, channel, time bucket (daily/weekly/monthly/quarterly/annual), cohort lookback (12 months free / unlimited paid).
- **Data shown:** CLTV by cohort; 60-day LTV; 90-day LTV; nc-ROAS, ncCAC, repeat rate, blended MER, POAS — all sliceable by cohort dimension.
- **Interactions:** Time bucket selector; segmentation builder; Moby Chat for natural-language cohort queries; mobile push notifications on revenue milestones; Audience Sync pushes RFM/behavioural cohort audiences back to Meta.
- **Why it works (from reviews/observations):** "Triple Whale's Summary page on mobile is addictive with real-time profit data" (paraphrased consensus across 2026 reviews); "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports" (Steve R., Capterra, July 12, 2024). The sheer breadth of cohort segmentation and the pixel-driven freshness are the cited strengths; UI specifics for cohort grid not publicly verifiable.
- **Source:** [triple-whale.md](../competitors/triple-whale.md); https://www.triplewhale.com/blog/cohort-analysis.

## Visualization patterns observed (cross-cut)

Synthesis by viz type across the 11 competitor profiles documenting LTV:

- **Color-gradient cohort heatmap matrix:** 3 competitors (Lifetimely, Peel Insights, Triple Whale [inferred — UI not directly verified]). Lifetimely is the most explicit ("clean grid structure with color gradients to represent performance variations across customer cohorts"). Heatmap is the dominant pattern when LTV is the canonical screen.
- **Multi-line cumulative-LTV chart (one line per cohort):** 1 competitor (Everhort) — explicitly NOT a heatmap; uses blue gradient by recency for cohort-line color and a light-red blended-average baseline. Plus Peel exposes "cohort curves" alongside its tables.
- **Stacked area / "layer cake" chart:** 2 competitors (Everhort's Stacked Cohort Activity Chart with click-to-isolate band; Daasity's "Layer Cake Graph" stacking quarterly cohort revenue over calendar time).
- **Cohort-waterfall (vertical accumulating-bar chart):** 1 competitor (Lifetimely) — distinct from a P&L waterfall. Vertical accumulating bars per month with **user-configurable horizontal green CAC-payback line overlay** that converts the chart into a "when do I break even" answer.
- **Cohort triangle (raw numeric cells, no heatmap):** 1 competitor (Repeat Customer Insights) — minimum-viable cohort table.
- **Stacked-bar (historic + predicted, single bar):** 1 competitor (Klaviyo) — horizontal stacked bar with **blue historic CLV + green predicted CLV** at the per-profile level. The smallest possible primitive for "earned vs modeled" composition. No other competitor exposes a per-profile stacked bar at this resolution.
- **Cohort matrix with attribution-source slicer (filter chip on top):** 1 competitor (Wicked Reports) — rows = cohort month, columns = LTV accumulation, top-bar filter re-slices by acquired-source/campaign/ad/email. Treats attribution as a dimension on the LTV matrix rather than as a separate report.
- **Two-column forecast view (bar chart + metrics table):** 1 competitor (Everhort) — left bar chart shows projected LTV at 1Y/2Y/3Y horizons; right table lists CAC targets and payback periods at the LTV/CAC=3 ratio.
- **Filter-vs-baseline overlay on every chart:** 1 competitor (Everhort) — when a filter is applied, an additional baseline series renders alongside the filtered series in a distinct color. Direct A/B comparison without leaving the report.
- **Per-customer profile predictive panel:** 2 competitors (Klaviyo's Metrics and insights tab; Lifetimely's Customer Product Journey "noodle"). Klaviyo's is the more developed (predicted CLV, expected next order, churn risk traffic-light, predicted gender, all on cards).
- **Layer Cake Graph (department-organized IA):** 1 competitor (Daasity) — quarterly cohorts stacked over calendar time, located under Acquisition Marketing rather than under Customers.
- **Sankey customer-migration (between cohort/segment groups over time):** 1 competitor (Klaviyo, on RFM not directly LTV — but the same underlying data) — shows group-to-group migration between two date-bounded snapshots. Translates "what happened to my Champions cohort?" into a flow diagram.

Recurring color conventions:
- **Blue for historic / observed** and **green for predicted / target** is the Klaviyo + Everhort convention (Klaviyo's stacked bar; Everhort's green CAC baseline line).
- **Green for "good / high LTV / payback hit"** is universal where present (Lifetimely CAC bar, Wicked Reports' "green for growth and red for issues").
- **Lebesgue inverts the convention** — uses **blue for improvements, red for declines**, explicitly avoiding green to dodge R/G colorblindness issues.
- **Red for incomplete / partial data** is rare; **dashed-border for incomplete periods** (Everhort) is the cleaner alternative.
- **Light-to-saturated color gradient** is the universal heatmap intensity encoding (Lifetimely, Peel; Triple Whale and RCI not verified).

Recurring interaction patterns:
- **Click cohort row → drill into single-cohort detail** is implied/confirmed in Lifetimely, Peel, Wicked Reports, Triple Whale.
- **Filter chip re-slices the entire cohort grid** is universal where filters exist; only Everhort and Wicked Reports treat the slicer as a first-class layout element on the cohort screen.
- **CAC threshold as a user-input that draws a line/bar on the chart** is unique to Lifetimely.
- **One-click attribution-model swap that re-renders LTV-by-channel** — Polar (9-10 models), Daasity (8 models), Wicked Reports (6 models), Lebesgue (5 models). Not on the LTV screen specifically for most, but on adjacent cohort/attribution surfaces.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: LTV finally connects acquisition spend to lifetime value**
- "helped us understand our Lifetime Value better than anything else" — Blessed Be Magick, Shopify App Store, Feb 8, 2026 (Lifetimely)
- "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, Shopify App Store, Jan 27, 2026 (Lifetimely)
- "invaluable for understanding of your MER, customer cohorts and LTV" — Radiance Plan, Shopify App Store, Apr 2, 2026 (Lifetimely)
- "Great app to keep track of your customer cohorts and stay on top of LTV" — Pacas, Shopify App Store, May 15, 2023 (Repeat Customer Insights)
- "[Got] 500% ROI from some of the traffic sources" when analyzing "lifetime value of customers over time." — Henry Reith, Marketing Consultant (Wicked Reports testimonials)

**Theme: Cohort analysis in one click that would take days in spreadsheets**
- "Great app for all things retention and cohort analysis! Easy to use but also excellent service" — Koh, Shopify App Store, March 26, 2026 (Peel Insights)
- "COHORT ANALYSIS automatically created from your store is unbelievable and a must-have for any startup focused on growth." — pantys, Shopify App Store, June 14, 2019 (Repeat Customer Insights)
- "We've used this tool for several months to track cohort retention rates" — LUXE Fitness, Shopify App Store, June 15, 2020 (Everhort)
- "Repeat customer insights is a great tool that we use to better understand cohort data and segmentation." — Package Free, Shopify App Store, May 22, 2020 (Repeat Customer Insights)
- "Great app and really hands-on support from their team for any reporting needs." — Biocol Labs, Shopify App Store, April 28, 2025 (Peel Insights)

**Theme: Filter cohorts by acquisition source / first product / discount**
- "the best LTV cohort analysis app I have found on Shopify" — The Beard Club, Sept 8, 2020 (Everhort)
- "really good LTV analytics capabilities combined with best-in-class, highly responsive support" — Mighty Petz, Dec 15, 2023 (Everhort)
- "Wicked allows us to accurately eliminate the advertising which is not working or refine the conversion on another piece." — Karen C, Owner / Marketing Strategist (Wicked Reports)
- "Wicked Reports allows us to optimize Facebook ads to those with the highest ROI and just not the cheapest lead. Nothing else on the market remotely compares." — Ralph Burns, Tier 11 CEO (Wicked Reports)

**Theme: Predictive LTV — knowing what customers will be worth, not just what they earned so far**
- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group (Klaviyo)
- "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." — Fringe Sport, Shopify App Store, Oct 28, 2025 (Lebesgue)
- "Their multi-touch attribution and incrementality testing have been especially valuable for us." — Chicory, Shopify App Store, September 2025 (Polar Analytics)

**Theme: First-product-purchased as a cohort lens reveals which products grow LTV**
- "an excellent visualiser of retention" — LUXE Fitness, Shopify App Store, June 15, 2020 (Everhort)
- "This app is a game-changer when it comes to diving into customer data." — The Sock Drawer, Shopify App Store, Feb 16, 2021 (Repeat Customer Insights)

## What users hate about this feature

**Theme: Paywalled or locked behind premium tiers**
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, December 2025 (Klaviyo)
- "All the valuable features come in paid plans" — Digismoothie editorial review (Lebesgue)
- "Free version doesn't offer too much, so definitely thinking more so about a paid version." — Sasha Z., Founder (Retail), Capterra, Sept 30, 2025 (Lebesgue)
- "A bit more expensive than other analytics applications available, which can be a limitation for start-ups with limited budgets." — smbguide.com (Peel Insights)
- "At $149/month for the first paid tier, Lifetimely is a real line item, with stores doing under $30K/month potentially struggling to justify it." — ATTN Agency review, 2026 (Lifetimely)

**Theme: Predictive features hidden until data thresholds are met**
- "Predictive analytics gated by data thresholds" — Klaviyo profile observation (500+ customers with orders, 180+ days history, 3+ repeat purchasers required) (Klaviyo)
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed, workflowautomation.net, Nov 20, 2025 (Triple Whale)

**Theme: Manual COGS entry blocks accurate LTV (margin-based, not revenue-based)**
- "Manual COGS entry" — Peel profile observation (Peel Insights)
- "Transaction fees and handling costs are explicitly excluded from this scope" — Lifetimely help-doc verbatim limitation (Lifetimely)
- "No variant-level COGS" — Lifetimely profile observation (Lifetimely)
- Glew lacks "1st-party pixel and channel-specific attribution data" — Polar comparison page (biased source) (Glew)

**Theme: UI is clunky / overwhelming / outdated**
- "Reporting is clunky and the UI buries things that should be front and center." — Darren Y., Capterra, April 2026 (Klaviyo)
- "The interface can feel overwhelming for newcomers." — marketingtoolpro.com, 2025 (Wicked Reports)
- "Outdated user interface design" — smbguide.com review, 2025 (Wicked Reports)
- "The user interface, while functional, lacks the visual polish seen in some competitors like Triple Whale." — Conjura comparison article, 2025 (Polar Analytics)
- "for a small operation it's just way overload." — BioPower Pet, Shopify App Store, Apr 2, 2026 (Triple Whale)

**Theme: Source / attribution disagreement is hidden, not exposed**
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson, workflowautomation.net, Mar 16, 2026 (Triple Whale)
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — AI Systems Commerce, 2026 review (Triple Whale)

**Theme: Cohort lookback gated by tier**
- "Lookback depth as a paid axis — Entrepreneur capped at 12 monthly cohorts" — RCI profile observation (Repeat Customer Insights)
- "Cohort and LTV reports paywalled for some advanced filtering — 'Advanced filtering capability has been made available to Pro & Plus customers'" — Lifetimely post-AMP-acquisition packaging (Lifetimely)

## Anti-patterns observed

- **Cohort triangle with no color encoding** (Repeat Customer Insights): raw numeric cells in a triangle. The information density is high but the operator can't scan-read the chart for hot spots; the eye has nowhere to go. Reviewers don't complain explicitly but the product's tiny review surface (14 reviews in ~10 years) suggests low pull-through. Direct contrast with Lifetimely's gradient heatmap, which 1800DTC explicitly praises.
- **Hiding source disagreement on LTV-by-channel** (Triple Whale, Lifetimely): LTV by channel collapses pixel-attributed and platform-reported figures into a single number. Reviewers note "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding" (Triple Whale). The disagreement *is* the information; collapsing it loses the lens.
- **Predictive LTV as a black box without methodology** (Lifetimely's "12% average LTV increase" claim, Triple Whale's 60/90 LTV with no published model): users see a number, can't interrogate the formula. Contrast Everhort, which explicitly states "linear regression of the blended average LTV of recent cohort performance" with a fixed LTV/CAC=3 multiplier — same number, transparent method.
- **Predictive features hidden when data is too sparse** (Klaviyo: 500 customers / 180 days / 3 repeats required) without an explicit empty-state explanation: users on new stores see blank cards and don't know why. Klaviyo gates honestly but the empty state is weak.
- **Cohort filtering paywalled** (Lifetimely post-AMP, Repeat Customer Insights tier-gating to 12 cohorts on Entrepreneur): users hit a gate mid-analysis. The cohort report becomes a teaser for the upsell rather than a working tool.
- **No baseline overlay on filtered cohort views** (most competitors): when a filter is applied, the chart redraws to show only filtered series — operators lose the comparison to "the rest of the store." Everhort is the exception (filter-vs-baseline overlay built in).
- **Aggregating LTV without composition** (any tool that shows a single LTV number without splitting historic from predicted, or without splitting per-channel): the number tells you a lot less than the breakdown. Klaviyo's stacked bar is the cleanest counter-example; most other tools just show a number.
- **Manual CAC entry as the only way to position the payback marker** (Lifetimely): the green CAC-payback bar is a fantastic primitive but requires the user to type their CAC. If CAC is already known to the system (via ad-platform integration + new-customer-orders), the bar should auto-render. Lifetimely doesn't auto-compute the bar position.
- **Cohort-by-channel buried inside attribution rather than featured as the LTV question** (Triple Whale, Polar): the channel breakdown of LTV is the SMB merchant's #1 use case ("which channel pays back?"), but it's typically a column in an attribution table rather than the headline screen. Wicked Reports and Glew are the exceptions — Wicked treats attribution as a slicer on the cohort matrix; Glew gives "LTV Profitability by Channel" its own sub-page.

## Open questions / data gaps

- **Exact color tokens** for cohort heatmaps in Lifetimely, Peel, and Triple Whale are not extractable from public marketing pages (JS-rendered images, paywalled dashboards). Heatmap intensity gradients are described but not specified in hex.
- **Triple Whale's cohort grid structure** (rows × columns, color encoding, hover tooltips) is referenced in their cohort blog post but not verifiably described — KB articles 403'd to WebFetch.
- **Polar's per-cohort LTV layout** is composed dynamically from Custom Reports / Sparkline Cards / Key Indicator Sections; there is no canonical "Cohort screen" with a fixed layout to capture. Public pages confirm the metric set but not a single-screen reference layout.
- **Glew's LTV Profitability by Channel UI** is confirmed as a navigation path in the search index but no public screenshot or layout description is available — Glew's app is paywalled and behind a sales-led demo.
- **Daasity's full LTV & RFM dashboard layout** is only partially documented — the "Layer Cake Graph" section is named in docs, but other sections on the same dashboard are visible in screenshots without text descriptions. Login required for full enumeration.
- **Klaviyo's exact "stacked bar" rendering at the per-profile level** is described in help docs ("blue historic + green predicted, full bar = Total CLV") but exact pixel widths, segment border treatment, and tooltip behavior are not in public docs.
- **Triple Whale's 60/90 LTV methodology** — what model produces these forecasts is not published. They surface as KPIs without a methodology page.
- **Lifetimely's Predictive LTV at 3/6/9/12-month horizons** marketed with "12% average LTV increase" claim — methodology not published.
- **Wicked Reports' "Continuous update of revenue and ROI when subscriptions rebill"** patents-pending mechanism is described but the data freshness cadence (real-time? daily? per-rebill?) is not specified publicly.
- **Cohort-waterfall green CAC bar interaction** in Lifetimely — whether the bar can be set per-cohort (variable CAC by acquisition channel) or only as a single global CAC value across all cohorts is not directly verified.

## Notes for Nexstage (observations only — NOT recommendations)

- **Heatmap is the dominant primary visualization for LTV cohorts (3 of 11 competitors confirmed, with Triple Whale likely making 4).** Multi-line charts (Everhort) and stacked area / Layer Cake (Everhort, Daasity) are the secondary patterns. Cohort triangles without color encoding (RCI) are minimum-viable.
- **Klaviyo's stacked-bar (blue historic + green predicted) is the cleanest "earned vs modeled" primitive observed.** It's a single bar at the per-customer level. No competitor at the *cohort* level uses this composition pattern — they use heatmaps or separate predicted-LTV cards. Direct gap: "stack historic on predicted" is missing as a cohort-level visualization, even though it works at the customer level.
- **Lifetimely's user-configurable green CAC-payback line on a cohort waterfall is the only "when do I break even?" primitive observed.** It is a tiny feature with a high recognition value; users repeatedly cite it. The auto-render-from-known-CAC variant (where the system knows CAC from ad-platform integration and draws the line without user input) does not exist in any competitor.
- **Daasity's "Layer Cake Graph" (quarterly cohorts stacked over calendar time) and Everhort's Stacked Cohort Activity Chart are functionally similar.** Both stack cohort revenue contribution over calendar time. Everhort adds click-to-isolate band; Daasity adds the department-organized IA wrapper.
- **Wicked Reports' "cohort matrix with attribution-source slicer at the top" is the only LTV view that treats acquisition source as a *slicer dimension on the LTV matrix* rather than as a separate attribution report.** Cohort lens × source dimension. This collapses two questions ("how do cohorts retain?" + "which source acquired them?") into one screen.
- **Predictive LTV is gated by data thresholds in Klaviyo (500 customers / 180 days / 3 repeats).** This is the only competitor with explicit, named thresholds. Worth deciding empty-state behavior before predictive features ship.
- **Filter-vs-baseline overlay (Everhort) is rare.** When a filter is applied, most competitors redraw the chart with only the filtered series. Everhort is alone in keeping the unfiltered baseline visible alongside.
- **3 sources for LTV-by-channel is the current Polar bar (Platform / GA4 / Polar Pixel side-by-side); 2 sources is the Wicked bar (Wicked-attributed vs Facebook-reported in FunnelVision); 1 source is the universal default.** Nexstage's 6-source thesis (Real / Store / Facebook / Google / GSC / GA4) maps onto LTV-by-channel as a direct extension of these patterns.
- **Cohort grouping is universally month/week/quarter/year as a toggle.** No competitor exposes "fiscal week" or "retail 4-5-4 calendar" on the LTV screen specifically (Daasity has it on Flash dashboards but not on LTV). Universal flat-calendar assumption.
- **First-product-purchased as a cohort dimension is universal** across Lifetimely, Peel, Polar, Wicked Reports, Triple Whale, Repeat Customer Insights, Daasity. Discount-code-on-first-order is similarly universal. Country/geography is in 6/11. First-touch source is in 8/11.
- **GSC absence is universal in this feature.** No competitor uses GSC data for LTV cohorting. SEO-acquired customers are typically lumped into "Organic Search" with no further slicing. Direct gap for Nexstage's 6-source thesis at the LTV screen specifically.
- **Subscription-aware LTV (rebill-driven re-attribution)** is patented territory for Wicked Reports + Recharge. Most competitors compute LTV against revenue-as-recorded; Wicked re-prices cohort campaign credit *as the cohort rebills over time*, so a campaign's ROAS number changes after the fact. Nexstage's "ratios are never stored, computed on the fly" rule is conceptually adjacent — if cohort revenue recomputes nightly, downstream ratios refresh automatically.
- **CAC-payback computation uses LTV/CAC=3 as the industry default ratio in Everhort and is unstated elsewhere.** Lifetimely lets users enter their own CAC; most others don't display payback period at all. Nexstage already exposes CAC via ad-platform integration; the payback-period overlay would be a derived computation, not a new data source.
- **Klaviyo's "Sankey customer-migration between cohort/segment groups over time"** is on RFM, not LTV, but the same data underlies both. The migration story ("how did my Champions cohort become At Risk?") is missing from the LTV side of every competitor. LTV is canonically a cohort-by-time matrix; Klaviyo's Sankey is the only flow visualization in the customer-analytics neighborhood.
- **Pricing pattern: cohort/LTV depth is consistently a higher-tier feature.** Lifetimely all-tiers (but post-AMP advanced filtering paywalled), Peel Core+ ($199/mo), Klaviyo $100/mo Marketing Analytics add-on, Polar Audiences+ ($470/mo Shopify-listing entry), Lebesgue $59/mo Advanced+, Wicked $499/mo, Glew Pro $249/mo, Daasity $1,899/mo, Triple Whale Advanced+ ($259+/mo entry GMV). Free-tier cohort/LTV is rare (Everhort claims free; Lifetimely claims free 50-orders/mo).
- **AI/conversational layer over LTV is emerging.** Peel's Magic Dash auto-builds cohort dashboards from natural-language questions; Polar's Ask Polar produces editable Custom Reports; Lebesgue's Henri returns inline charts + Key Takeaways + Recommendations; Triple Whale's Moby Chat answers cohort queries. None of these *generate predictive LTV models from prompts* — they just query the existing model.
