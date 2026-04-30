---
name: Dashboard overview
slug: dashboard-overview
purpose: Answers the merchant's first-glance question — "How is the business doing right now?" — on a single canvas blending revenue, ad spend, profit, and traffic from every connected source.
nexstage_pages: dashboard
researched_on: 2026-04-28
competitors_covered: triple-whale, polar-analytics, storehero, lifetimely, shopify-native, conjura, beprofit, trueprofit, bloom-analytics, putler, daasity, glew, klaviyo, lebesgue
sources:
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/storehero.md
  - ../competitors/lifetimely.md
  - ../competitors/shopify-native.md
  - ../competitors/conjura.md
  - ../competitors/beprofit.md
  - ../competitors/trueprofit.md
  - ../competitors/bloom-analytics.md
  - ../competitors/putler.md
  - ../competitors/daasity.md
  - ../competitors/glew.md
  - ../competitors/klaviyo.md
  - ../competitors/lebesgue.md
---

## What is this feature

The Dashboard Overview is the default landing page every ecommerce analytics tool ships, and it is the only page most merchants look at on a normal day. Its job is to compress the answer to "how is the business doing right now?" into one screen — period-vs-period revenue, ad spend across all platforms, profit (when COGS is configured), and a small set of supporting KPIs (orders, sessions, conversion rate, AOV) — without making the merchant pick a date filter, navigate a tree, or click into a sub-report. For SMB Shopify/Woo owners specifically, it replaces a morning ritual of opening four tabs (Shopify admin, Meta Ads Manager, Google Ads, a profit spreadsheet) and reconciling numbers by hand.

The data is always present in the source platforms — every Shopify merchant has a sales total, every Meta advertiser has a spend number. What this feature provides is the *synthesis*: blended ROAS / MER / contribution margin computed across sources, period comparisons rendered inline, and trend context (sparkline, delta arrow, prior-period absolute) so the merchant can read a single tile and know whether to act. Competitors differ on which metrics are headline-elevated, whether the canvas is customizable, whether the period scope is global or per-tile, and how aggressively the AI assistant injects itself into the same surface.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.total_price`, `orders.created_at`, `orders.financial_status`, `orders.refunds`, `line_items.quantity`, `customers.id`, `checkouts.started_at`, `sessions` (where exposed), `inventory_levels`, `discounts`, `shipping_lines`, `taxes`.
- **Source: Meta Ads API** — `campaigns.spend`, `campaigns.impressions`, `campaigns.clicks`, `ads.platform_conversions`, `ads.platform_attributed_revenue`.
- **Source: Google Ads API** — `campaigns.spend`, `campaigns.clicks`, `campaigns.impressions`, `campaigns.conversions`, `campaigns.conversion_value`.
- **Source: TikTok / Pinterest / Snapchat / Microsoft Ads / Reddit** — spend, impressions, clicks, platform conversions per channel.
- **Source: GA4** — `sessions`, `users`, `engaged_sessions`, `conversions`, traffic source/medium.
- **Source: Google Search Console** — `queries.clicks`, `queries.impressions`, `queries.position` (only Putler and Glew ingest this among the surveyed competitors).
- **Source: Klaviyo** — `campaigns.attributed_revenue`, `flows.attributed_revenue`, `email.opens`, `email.clicks`.
- **Source: First-party pixel** (Triple Pixel / Polar Pixel / Lifetimely pixel / Bloom Pixel / Le Pixel) — `events.page_view`, `events.add_to_cart`, `events.purchase`, server-side deduplicated conversions, identity-graph stitching.
- **Source: User-input / Cost-config** — `cogs_per_product`, `shipping_cost_rules`, `transaction_fee_rate`, `custom_recurring_costs`.
- **Source: Computed** — Net profit = `revenue − COGS − ad_spend − shipping − fees − custom_costs`; MER = `revenue / total_ad_spend`; ROAS = `attributed_revenue / spend`; CAC = `spend / new_customers`; AOV = `revenue / orders`; CVR = `orders / sessions`. All ratios computed on the fly per CLAUDE.md rule.

## Data outputs (what's typically displayed)

- **KPI: Total revenue / Net sales / Gross sales** — `SUM(orders.total_price)`, currency, vs prior-period delta.
- **KPI: Net profit / Contribution margin** — revenue minus all costs, currency, delta.
- **KPI: Total ad spend** — sum across connected ad platforms, currency, delta.
- **KPI: MER (Marketing Efficiency Ratio)** — revenue / ad spend, ratio.
- **KPI: Blended ROAS / ncROAS** — attributed revenue / spend, ratio.
- **KPI: CAC / nCAC** — spend / new customers, currency.
- **KPI: AOV** — revenue / orders, currency.
- **KPI: Sessions / Conversion rate / Orders / Refund rate** — counts and ratios.
- **Dimension: Channel** — Direct, Email, Paid Social, Paid Search, Organic Search, Affiliate, etc.
- **Dimension: Source platform** — Shopify, Meta, Google, TikTok, Klaviyo, GA4, GSC.
- **Breakdown: Per-platform spend × ROAS** — sub-grid below the headline tiles.
- **Trend: Inline sparkline / area chart** under each KPI showing the period's daily progression.
- **Comparison: Period-over-period delta** rendered inline as `+10.3% vs last month` or `$420K, 5.2% compare to last week`.

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Default landing page, sidebar > Summary Dashboard.
- **Visualization:** Drag-and-drop KPI-tile grid, with a "table view" toggle that pivots the same tiles into a dense single-table layout.
- **Layout (prose):** "Top date-range and store-switcher controls (period-comparison toggle implied by 'vs prior period' delta language). Body is organized as **collapsible sections by data integration** — by default sections include Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics (Triple Pixel), and Custom Expenses. Each section is a grid of **draggable metric tiles**." The hero is a row of KPI tiles across the top of the canvas labelled "Summary Dashboard Blended Metrics."
- **Specific UI:** "KPI tile shows headline value + period-vs-period delta. Hovering a tile reveals a 📌 pin icon (the help-center copy literally references the pushpin emoji). 'Edit Dashboard' button enters drag-edit mode and surfaces 'Create Custom Metric'." On-demand refresh button (April 2026) triggers a real-time status display ("Refreshing Meta…", etc.). Moby Chat sidebar persists on every dashboard.
- **Filters:** Date range, store switcher, period-vs-period toggle; per-section filters on Shopify and Ad data.
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV (60/90), Total Ad Spend, Sessions, Conversion Rate, Refund Rate, plus per-platform spend/ROAS sub-tiles.
- **Interactions:** Drag-and-drop reordering, pin to "Pinned" section, pivot to table view, click metric to drill into detail, on-demand refresh, Moby NL query.
- **Why it works (from reviews/observations):** "Its Founders dash is something that all Shopify brands should be using. It's free and puts all the metrics you need to know about your high level business performance in a single place." (Head West Guide, 2026, [profile](../competitors/triple-whale.md)). "We've seen a lot of dashboard products (paid and unpaid), and we think Triple Whale's is among the best." (Head West Guide review, 2026).
- **Source:** [profile](../competitors/triple-whale.md), `triplewhale.com/blog/build-the-perfect-data-analytics-dashboard`, `kb.triplewhale.com/en/articles/5725275-track-kpis-with-the-summary-dashboard` (KB blocked WebFetch).

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Sidebar > Folders > Dashboard pages; canvas built from a block library.
- **Visualization:** Vertically-stacked block canvas — Key Indicator Section (metric-card grid) on top, Tables/Charts and Sparkline Cards below.
- **Layout (prose):** "Recommended pattern (per docs): Metric Cards or Sparkline Cards in a horizontal row across the top, with charts and tables below. Date range selector lives in the top-right of the dashboard. Left sidebar holds folder tree of dashboards."
- **Specific UI:** Three block types — Key Indicator Section (grid of metric cards with optional targets), Tables/Charts (Custom Reports), and Sparkline Card (a metric card with a mini trend line embedded inside the card itself). "Comparison indicators (improvement / decline arrows) render automatically off the dashboard date range."
- **Filters:** Dashboard-wide date range, period comparison toggle (vs prior period or YoY), Views (saved-filter system that re-filters the entire dashboard from a dropdown).
- **Data shown:** All metrics from the semantic layer ("hundreds of pre-built metrics and dimensions") plus user-defined Custom Metrics; default seeds include MER, blended CAC, ROAS, AOV, contribution margin.
- **Interactions:** Drag/drop block reordering, schedule any block to auto-deliver as Slack/Email, switch View to re-filter all blocks at once.
- **Why it works (from reviews/observations):** "Polar is easy to setup and offers tons of value, KPI's and metrics out of the box" (anonymous Denmark reviewer cited in Polar's vs-Triple-Whale page, [profile](../competitors/polar-analytics.md)). "The ability to see (and trust!) our data at a high level gives us peace of mind." (Optimal Health Systems, Shopify App Store, July 2024).
- **Source:** [profile](../competitors/polar-analytics.md), `intercom.help/polar-app/en/articles/10430437-understanding-dashboards`.

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Primary top-level nav tab "Dashboard" — the "Unified Dashboard."
- **Visualization:** KPI-tile row with side-by-side channel-performance grouping; framed by their copy as a "full funnel from reach to profit."
- **Layout (prose):** "Described as 'one clean command center' combining sales, ad spend, and profit on a single screen." Side-by-side grouping of channel performance sits below the top-level KPI tiles.
- **Specific UI:** KPI tiles for Net Sales, Marketing Spend, Ad Spend, Contribution Margin. The Spend Advisor sub-module (separate from but adjacent to the Dashboard) renders a "live next-$100 simulator" with three discrete pause/pivot/scale recommendation pills. Goals & Forecasting module attaches **green/red two-state traffic-light dots** (no amber) to each monthly KPI benchmark.
- **Filters:** Date range, store-switcher (for agency multi-store view), channel-blended-vs-channel-by-channel toggle.
- **Data shown:** Net Sales, Marketing Spend, Ad Spend, Contribution Margin, MER, ROAS, breakeven ROAS, new customer sales, repeat customer sales, AOV.
- **Interactions:** Date range, store switcher, channel-blended toggle. UI specifics for hover/keyboard not observable from public sources.
- **Why it works (from reviews/observations):** "clarity around contribution margin. It gives a true understanding of what is actually driving profit" (Origin Coffee, Shopify App Store, March 2 2026, [profile](../competitors/storehero.md)). "dashboard is intuitive and gives us a much clearer picture of profitability" (Kinvara Skincare, Shopify App Store, January 7 2026).
- **Source:** [profile](../competitors/storehero.md), `apps.shopify.com/storehero-profit-analytics`, `storehero.ai/`.

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Top-level sidebar item; default page on login. Branded "Profit Dashboard" / "Income Statement."
- **Visualization:** **Income-statement-style stacked vertical layout** — line items descending from revenue to net profit — *not* a 4-up KPI grid.
- **Layout (prose):** "The landing canvas leads with revenue, product costs, marketing costs, and net profit as the four anchor figures, structured as an income-statement-style **stacked vertical layout** rather than a 4-up KPI grid. Below the headline figures, costs are factored in line-by-line (Shopify COGS auto-pulled, transaction gateway fees, shipping from ShipStation/ShipBob, custom recurring costs)."
- **Specific UI:** "Income-statement table format (line per cost category, descending from revenue → contribution → net). Top-of-page date-range picker. Color usage from screenshots described as 'professional color scheme emphasizing readability' — neutral palette with restrained green/red for deltas." Daily/weekly/monthly toggle. Email/Slack export of the same view delivered at 7am daily.
- **Filters:** Date-range picker, daily/weekly/monthly toggle.
- **Data shown:** Total sales, COGS, marketing spend, gross margin, contribution margin, net profit, refunds, fees, custom expenses.
- **Interactions:** Daily/weekly/monthly toggles. Reviewers note multi-hour refresh ("every few hours") despite "real-time" marketing claim.
- **Why it works (from reviews/observations):** "simplified, impactful dashboards that help make decision making easier" (Raycon, Shopify App Store, March 18, 2026, [profile](../competitors/lifetimely.md)). "removes the hassle of calculating a customer's CAC and LTV" (ELMNT Health, Shopify App Store, January 27, 2026).
- **Source:** [profile](../competitors/lifetimely.md), `useamp.com/products/analytics/profit-loss`, `1800dtc.com/breakdowns/lifetimely`.

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Shopify admin > Analytics (left nav) > Overview / Home.
- **Visualization:** Customizable metric-card grid (drag, drop, resize) with inline trend chart per card, plus top-products and top-traffic-sources tables below.
- **Layout (prose):** "Date-range picker + comparison toggle pinned to the top of the canvas; primary canvas is a **grid of metric cards** that the merchant can drag-and-drop, resize, add, or remove… 'choose from a library of metric cards to add or remove metrics' and 'reorder metric cards to build a personalized dashboard layout.'" Below the card grid surfaces top-products and top-traffic-sources tables.
- **Specific UI:** "⠿ drag-handle icon on each card; lower-right resize handle on each card; sidebar/library panel of available metric cards in edit mode; card content shows current value, comparison delta against the prior period, and a small inline trend chart for time-series cards. Custom cards can be created from saved Reports and pinned to the dashboard." Per-card date-range overrides are NOT documented; the date picker is dashboard-wide.
- **Filters:** Dashboard-wide date range with presets ("Last 30 days") and custom range; comparison mode toggles prior-period vs YoY.
- **Data shown:** Total sales, Gross sales, Net sales, Orders, Sessions, Online store conversion rate, AOV, Returning customer rate, Top products by units sold, Top channels, Top referrers, Sales attributed to marketing, Sessions attributed to marketing, Customers, plus any custom card pinned from the Reports library.
- **Interactions:** Click card to drill into the underlying report; cards refresh in real-time after the 2024 infrastructure rebuild; layout saved per user. Sidekick AI sits in the top-right ("purple glasses icon") and writes ShopifyQL for analytics queries.
- **Why it works (from reviews/observations):** "The design, flexibility, payment integrations, custom sections options, integrations with Shopify Apps, analytics dashboard everything is perfect and easy to use." (Capterra reviewer, 2026, [profile](../competitors/shopify-native.md)). "[Sidekick] feels like real-time support without having to search through help docs or wait for a reply" (paraphrase via `pagefly.io/blogs/shopify/shopify-sidekick`).
- **Source:** [profile](../competitors/shopify-native.md), `help.shopify.com/en/manual/reports-and-analytics/shopify-reports/overview-dashboard`, `changelog.shopify.com/posts/customize-your-analytics-dashboard-to-focus-on-key-business-metrics`.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Performance Overview Dashboard — "the beating heart of Conjura's analytics suite."
- **Visualization:** KPI cards mixed with **product-imagery thumbnails** ("incorporates product imagery alongside performance metrics, creating a visually-oriented analytics experience").
- **Layout (prose):** "Marketing copy describes a 'performance snapshot' hero image labeled 'Conjura Dashboard' with daily-refreshed KPIs aggregated from storefront, ad platforms, and GA4… 'Filter, slice and dice by store, channel or territory' — implies top-of-page filter/segmentation controls."
- **Specific UI:** Customizable KPI views can be saved. Daily refresh; daily email digest "Daily performance round-up keeps you and your team in the loop." Specific layout grid not disclosed in marketing pages.
- **Filters:** Store, channel, territory; date range.
- **Data shown:** Six primary KPIs — Contribution Profit, ROAS, CAC, Order Volume, Revenue (gross + AOV), Channel Performance breakdown.
- **Interactions:** Daily refresh, save custom KPI views, daily email digest.
- **Why it works (from reviews/observations):** "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." (Island Living, Shopify App Store, November 2024, [profile](../competitors/conjura.md)). "It gives you real visibility into profitability—way beyond Shopify's standard reporting." (The Herbtender, Shopify App Store, August 2025).
- **Source:** [profile](../competitors/conjura.md), `conjura.com/performance-overview-dashboard`.

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Top-level after sidebar nav; default landing page.
- **Visualization:** Top filter strip + KPI row + expense-tracking section with category breakdown (charts/graphs).
- **Layout (prose):** "Top filter strip with date-range picker (presets: Daily / Weekly / Monthly per screenshot caption), KPI row featuring Lifetime Profit, Retention, ROAS, and POAS as headline numbers, expense-tracking section underneath with category breakdown."
- **Specific UI:** Per screenshot captions: "Track all expenses in a powerful e-commerce analytics platform", "Get daily, weekly, and monthly reports on your store performance", "View your store's lifetime profit, retention, ROAS and POAS." UI uses charts and graphs ("intuitive charts and graphs for trend spotting").
- **Filters:** Date-range picker (Daily/Weekly/Monthly preset chips), real-time refresh, export.
- **Data shown:** Net profit, gross profit, contribution profit, ROAS, POAS, retention, lifetime profit, expenses by category.
- **Interactions:** Period switching (daily/weekly/monthly), real-time refresh, export. Customizable dashboard available on Pro+ tier.
- **Why it works (from reviews/observations):** Verbatim positive quotes specific to the dashboard surface not surfaced in the BeProfit profile.
- **Source:** [profile](../competitors/beprofit.md), Shopify App Store listing screenshots.

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Default landing screen after login; primary nav item — "Profit Dashboard."
- **Visualization:** **Large primary KPI number** (live net profit) + supporting KPI tiles + dynamic line graph + categorical cost-breakdown chart.
- **Layout (prose):** "Top of page shows live net-profit number prominently (the '$495,345' example used in their walkthrough blog) with gross revenue, profit margin, and AOV displayed alongside. Below the KPIs sits a **dynamic line graph for performance over time** (user picks which metric — revenue, orders, ROAS, net profit — and toggles day/week/month). Below that, a **cost breakdown chart** (categorical breakdown across packaging, fulfillment, marketing fees, transaction fees, custom costs)."
- **Specific UI:** Large primary KPI number; supporting KPI tiles; line chart with metric-picker; categorical cost-breakdown chart (type unspecified — likely stacked bar or donut, not confirmed); date-range filter; store switcher. A separate "Orders vs. Ad Spend per Order" chart was added in a 2025 update. Mobile iOS app mirrors with iOS widget integration for at-a-glance profit.
- **Filters:** Date range, store-switcher (multi-store rollup vs single-store), metric picker on the line graph.
- **Data shown:** Net profit, gross revenue, profit margin, AOV, ROAS, orders, "average order profit", total costs, ad spend per platform.
- **Interactions:** Metric picker on line graph; date-range; store switcher. Real-time (mobile app: "every 15 minutes").
- **Why it works (from reviews/observations):** "Great app for keeping an eye on your main metrics" (GetUp Alarm UK, Shopify App Store, March 9, 2026, [profile](../competitors/trueprofit.md)). "just what I needed to track my costs in real time" (Obnoxious Golf USA, Shopify App Store, April 15, 2026).
- **Source:** [profile](../competitors/trueprofit.md), `trueprofit.io/solutions/profit-dashboard`.

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Default landing page after onboarding; "Overview Dashboard" — described as "360 snapshot of revenue, costs & margin."
- **Visualization:** **Stacked-section card layout** — four blocks of KPI cards above a stack of trend charts and a Top 5 products list.
- **Layout (prose):** "Stacked-section layout with four primary blocks: (1) Revenue-to-Profit summary cards, (2) Margin Overview cards with percentages, (3) Marketing Performance cards, (4) Customer & Revenue cards. Below the cards sits a stack of trend charts and a Top 5 products list."
- **Specific UI:** "KPI cards each show 'the metric value for the selected period' plus 'the percentage change compared to the previous period.' Marketing site shows example cards rendering '$68.28K, 10.3% From Last Month' for Net Profit and '$420K, 5.2% compare to last week' for Ad Spend — implying period-comparison delta is rendered inline as percentage with directional copy (no color descriptor confirmed)." Four trend charts: Revenue-to-Profit %; Profit Margins (CM1/CM2/CM3 over time); Marketing Performance (MER, aMER, MPR, aMPR as lines + CAC as bars — **mixed line/bar combo chart**); Customer Type Trend. Marketing copy elsewhere mentions "Spline Area charts."
- **Filters:** Date range picker (daily/weekly/monthly/yearly); period comparison vs prior year or same historical period; cumulative analysis option.
- **Data shown:** Net Revenue, COGS, Cost to Fulfill Orders, Marketing Costs, Operating Expenses, Net Profit, Total Revenue, CM1 / CM2 / CM3 (with %), MER, aMER, MPR, aMPR, CAC, New Customers, New Customers %, BEROAS, AOV.
- **Interactions:** Period selection, comparison toggle. The separate "My Metrics" surface lets the user "Drag the ones that impact profit into your custom dashboard" from a 150+ metric library, with a per-tile **toggle between number widget and chart**. The "Profit Map" tree-graph (homepage describes "Visualize Profit Drivers at a Glance") visualizes how each metric flows into net profit — UI details not in public docs.
- **Why it works (from reviews/observations):** Specific dashboard-surface quotes not isolated in the Bloom profile (15 App Store reviews; review depth shallow).
- **Source:** [profile](../competitors/bloom-analytics.md), `docs.bloomanalytics.io/overview-dashboard.md`.

### Putler ([profile](../competitors/putler.md))
- **Surface:** First screen post-login, sidebar entry "Home."
- **Visualization:** Two-zone layout — a "Pulse" zone (current-month combined widget + Activity Log + 3-month comparison) on top and an Overview area (date-filterable widget grid) below.
- **Layout (prose):** "Top of screen is the 'Pulse' zone for the current month: a primary Sales Metrics widget showing this-month-to-date sales, a daily-sales mini-chart, a 3-day trend, current-month target setting, year-over-year comparison vs same month previous year, and a forecasted month-end sales number — all stacked together as one widget. Adjacent is an Activity Log streaming new sales, refunds, disputes, transfers, and failures with a dropdown filter to scope by event type. A 'Three Months Comparison' widget shows visitor count, conversion rate, ARPU, and revenue for the last 90 days vs the preceding 90 days side-by-side. A 'Did You Know' tile rotates daily with growth tips. Below the Pulse zone is an Overview area with a date-picker filter and stacked KPI widgets."
- **Specific UI:** "Widget-card layout (rectangular tiles with rounded corners, light gray borders based on demo screenshots). Year-over-year comparison rendered as inline percentage delta beside the absolute number. Daily-sales mini-chart appears as a small bar/line within the widget body, no axis labels. Activity Log shows a vertical scrolling list with **colored dots (event type indicators) and timestamps**. 'Did You Know' tile rotates daily content, single tip per day."
- **Filters:** Date-picker filter on the Overview region scopes all widgets simultaneously; Activity Log dropdown filters event types.
- **Data shown:** Month-to-date sales, daily sales, 3-day trend, target, YoY, forecast, ARPU, ARPPU, conversion rate, MRR, churn, active subs, orders, disputes, failed orders, top 20% customers, top 20% products, visitors, repeat-customer split.
- **Interactions:** Click into any KPI widget drills to its native dashboard; Putler Copilot floating chat invokes a NL overlay; near real-time refresh (5-minute on running session).
- **Why it works (from reviews/observations):** Specific dashboard-surface verbatim love quotes not isolated in the Putler profile.
- **Source:** [profile](../competitors/putler.md), `putler.com/docs/category/putler-dashboards/home/`, `putler.com/putler-features`.

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Top-level / "Home" landing screen plus a dedicated "Company Overview" dashboard in the Omnichannel template library.
- **Visualization:** Embedded Looker tiles with three sub-tabs (Ecommerce / Marketing / Retail); the Company Overview adds **stacked weekly-sales-by-channel bars with a YoY overlay line**.
- **Layout (prose):** Home Dashboard: "Single page containing top-line KPIs segmented by channel, with three sub-tabs labelled **Ecommerce**, **Marketing**, **Retail** — each containing the key metrics for the respective department. Click-through links from each KPI tile drill into the corresponding specialized dashboard." Company Overview: "Three vertically-stacked sections. (1) **Top KPIs (Current Period)**: Total Dollar (Net) Sales currency-adjusted, YoY or Period-over-Period % change, **Channel Mix %** showing top 5 channels by contribution plus a rolled-up 'all others.' (2) **Weekly Sales by Channel** stacked chart… (3) **Weekly Detail Table** with columns Week, Total Sales, YoY %, Change vs Prior Week, Channel Sales (both $ and % mix), exportable."
- **Specific UI:** Embedded Looker tiles; KPI cards segmented by channel; period comparison; "Stacked bars with multi-period band coloring; YoY overlay line; exportable table with mixed $-and-% columns."
- **Filters:** Date range, channel; on Flash Dashboards filter changes do not auto-apply ("the Data on the Dashboards will update after you click the Refresh Button"). 4-5-4 retail calendar comparison logic baked in.
- **Data shown:** Channel-segmented KPIs (e-commerce, marketing, retail); Net sales (currency-adjusted), YoY %, PoP %, channel mix %, weekly sales by channel.
- **Interactions:** Click-through drill-down to operational dashboards; date-range filtering; channel filtering; explicit refresh button on Flash Dashboards.
- **Why it works (from reviews/observations):** Dashboard-specific verbatim quotes not isolated in the Daasity profile.
- **Source:** [profile](../competitors/daasity.md), `help.daasity.com/core-concepts/dashboards`, `help.daasity.com/core-concepts/dashboards/report-library/omnichannel/company-overview`.

### Glew ([profile](../competitors/glew.md))
- **Surface:** Default app landing screen — "Dashboard / KPI Highlights" — plus an asynchronous "Daily Snapshot" email surface.
- **Visualization:** KPI tiles + charts grouped by department (Sales, Marketing, Customers, Products); Daily Snapshot email is a tile-style KPI block.
- **Layout (prose):** "Per marketing copy, 'an instant, unified view of sales, marketing, customers and products'. Pre-built dashboards organized around 'Sales and revenue tracking, Marketing channel performance, Customer analytics, Product performance, Orders and transactions, Inventory management, Subscription metrics.'"
- **Specific UI:** "UI details not directly observed — marketing screenshot titles include 'KPI Highlights', 'Performance Channels', 'Net Profit by Channel'. No public hover/tooltip behavior documented." Daily Snapshot email features "15+ KPIs across financial and operational categories" with built-in benchmarks and period-over-period comparisons; marketing imagery shows tile-style KPI blocks labeled "Ecom Daily Flash Dashboard."
- **Filters:** "Advanced data filtering capabilities" + "300+ unique filtering options" per Glew Pro page.
- **Data shown:** Revenue, profit, margin, orders, AOV, conversion rate, visits, CAC, LTV, ROAS, channel-specific performance, ad spend across Facebook/Instagram/Google/email; Daily Snapshot adds top marketing channel, top-selling product, largest order.
- **Interactions:** Click-through to the Glew web app for drill-down from the email; Glew Plus customers can customize tiles, comparison periods, targets, and currency conversion via "Creating Custom Daily Snapshots" video flow.
- **Why it works (from reviews/observations):** Dashboard-specific verbatim quotes not isolated in the Glew profile (Shopify App Store listing currently unavailable).
- **Source:** [profile](../competitors/glew.md), `glew.io/features/ecommerce-dashboard`, `glew.io/features/daily-snapshot`.

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Sidebar > Home (default landing); plus Analytics > Overview Dashboard for the deeper card library.
- **Visualization:** Vertical scroll of summary cards (Business Performance Summary on top, then Top-Performing Flows, then Recent Campaigns); Analytics Overview adds card-library composition with multi-line trend charts.
- **Layout (prose):** Home: "Top: alerts strip + conversion-metric selector + time-period selector (up to 180 days). Main canvas (vertical scroll): 'Business Performance Summary' card showing total revenue with an inline channel breakdown (email/SMS/push) and a flows-vs-campaigns split. Below: 'Top-Performing Flows' — up to six flows ranked descending by conversion or revenue with status pill (Live / Manual), message-type icon, delivery count, conversion count, and percent-change vs prior period. Below that: 'Recent Campaigns' list, most recent first, with name, message type, open rate, click rate, and conversion data per row."
- **Specific UI:** "Conversion-metric selector that re-pivots all cards globally; status pills (Live / Manual / Draft); percent-delta cells (no documented color coding observed publicly)." Analytics Overview: "Per-card channel tabs (Email / SMS / Mobile push); peer-benchmark badges directly on Campaign Performance card rated **'Excellent,' 'Fair,' or 'Poor'**; line-chart with three colored lines (blue / teal / yellow) per the help docs."
- **Filters:** Time-period selector up to 180 days; conversion-metric selector; comparison period; per-card channel tab.
- **Data shown:** Total revenue, attributed revenue, conversions, opens, clicks, sends, percent change vs prior period; channel split (email/SMS/push); flows vs campaigns split.
- **Interactions:** "Selecting a different conversion metric recalculates all cards. Clicking a flow name opens flow detail." Card library lets the user compose custom Overview Dashboards.
- **Why it works (from reviews/observations):** Dashboard-overview-specific verbatim quotes not isolated in the Klaviyo profile (its analytics layer is positioned as a CRM extension, not a standalone dashboard).
- **Source:** [profile](../competitors/klaviyo.md), `help.klaviyo.com/hc/en-us/articles/9974064152347`.

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Primary dashboard surface — "Business Report"; user picks metrics + period and the system auto-generates a custom report.
- **Visualization:** Auto-generated reports with line/bar charts; **blue for improvements, red for declines** color encoding.
- **Layout (prose):** "Per the Shopify-reporting-app feature page, user picks 'the metrics and time period you'd like to analyze,' then the system auto-generates a custom report. Layout includes metric-selection dropdowns, a date-range picker, and line/bar charts. Grouping options for day/week/month sit alongside the period selector."
- **Specific UI:** "'Color-coded performance indicators (blue for improvements, red for declines)' per the feature page — note this is **blue** for positive, not green, which is unusual. Metric-selection dropdowns. Date range pickers. Line/bar chart canvases." Henri AI chat sidebar embeds inline charts within chat responses with "Key Takeaways sections, and recommendations formatted as actionable next steps beneath performance analysis charts."
- **Filters:** Metric selection, date range, group toggle (day/week/month).
- **Data shown:** Revenue, First-time Revenue, Ad Spend (Meta/Google/TikTok/Amazon/Klaviyo), COGS, Profit, ROAS.
- **Interactions:** Pick metric → pick range → auto-generate; custom-report download; group toggle. Henri NL chat available alongside.
- **Why it works (from reviews/observations):** Dashboard-overview-specific quotes not isolated in the Lebesgue profile (review surface concentrates on AI/Henri).
- **Source:** [profile](../competitors/lebesgue.md), `lebesgue.io/product-features/shopify-reporting-app`.

## Visualization patterns observed (cross-cut)

Tally across the 14 dashboards profiled above:

- **Drag-and-drop KPI-tile grid:** 5 competitors (Triple Whale, Polar Analytics, Shopify Native, Bloom Analytics "My Metrics", Klaviyo Analytics Overview) — most-praised single pattern; reviews uniformly positive when paired with a metric-card library.
- **Fixed/opinionated KPI-tile grid (no user customization):** 4 competitors (StoreHero, Conjura, BeProfit Basic tier, TrueProfit) — neutral reviews; reduces decision fatigue but ceiling for power users.
- **Income-statement-style stacked vertical layout:** 1 competitor (Lifetimely) — a deliberate spreadsheet-replacement aesthetic that appeals to CFO/founder personas.
- **Department-tabbed Home (Ecommerce / Marketing / Retail):** 1 competitor (Daasity) — specific to omnichannel where retail/wholesale data lives alongside DTC.
- **Two-zone "Pulse + Overview" layout:** 1 competitor (Putler) — pairs a fixed current-month combined widget with a date-filterable widget grid below.
- **Vertical scroll of summary cards (single canvas, no grid):** 1 competitor (Klaviyo Home) — single-axis stack rather than tiled grid.
- **Auto-generated report from metric + date pick:** 1 competitor (Lebesgue Business Report) — config-then-render rather than pre-rendered tiles.
- **Asynchronous "Daily Snapshot" email as primary surface:** 2 competitors (Glew, Lifetimely's daily 7am P&L email; Conjura ships a daily round-up email too) — recurring pattern alongside the in-app dashboard, not a substitute.
- **Inline trend / sparkline under each KPI:** 5+ competitors (Shopify Native, Polar Analytics Sparkline Card, Bloom Analytics, Putler, TrueProfit) — increasingly table-stakes; absent only in BeProfit/Conjura screenshots.
- **Period-vs-period delta inline on every tile:** universal across all 14 — rendered as `+10.3% From Last Month`-style text. Color/arrow conventions vary (Lebesgue uses blue/red, others rely on green/red, Bloom's color "no color descriptor confirmed").
- **Dashboard-wide date range scope:** 13 of 14 (Shopify Native explicitly does NOT support per-card date overrides; Polar's date picker is dashboard-scoped; only the comparison toggle differs).
- **AI chat persistent on or beside the dashboard:** 6 competitors (Triple Whale Moby, Polar Ask Polar, Shopify Sidekick, Conjura Owly, Putler Copilot, Lebesgue Henri, Lifetimely Ask AMP, StoreHero MCP-for-Claude) — AI sidebar is the 2026 default rather than a differentiator.

Color conventions: green/red for positive/negative deltas is the majority convention, but Lebesgue inverts this with **blue for positive, red for negative**; Lifetimely deliberately uses a "professional… restrained" palette (no saturated greens/reds); Putler's Activity Log uses event-type **colored dots** rather than red/green.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Replaces the morning-spreadsheet ritual**
- "Its Founders dash is something that all Shopify brands should be using. It's free and puts all the metrics you need to know about your high level business performance in a single place." — Head West Guide review, 2026 ([profile](../competitors/triple-whale.md))
- "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." — Island Living, Shopify App Store, November 2024 ([profile](../competitors/conjura.md))
- "tool we rely on every day to make decisions. Great customer support!" — Constantly Varied Gear, Shopify App Store, March 23, 2026 ([profile](../competitors/lifetimely.md))
- "it's made things so much easier… real-time analytics alone have saved me HOURS every week" — WowWee.ie, Shopify App Store, March 24 2026 ([profile](../competitors/storehero.md))

**Theme: Clarity at a glance**
- "Polar is easy to setup and offers tons of value, KPI's and metrics out of the box" — anonymous Denmark reviewer, cited in Polar's vs-Triple-Whale page ([profile](../competitors/polar-analytics.md))
- "We've seen a lot of dashboard products (paid and unpaid), and we think Triple Whale's is among the best." — Head West Guide review, 2026 ([profile](../competitors/triple-whale.md))
- "Clean dashboards and access across teams with ease… Customer service and responsiveness always timely" — The Skin Nerd, Shopify App Store, April 16 2026 ([profile](../competitors/storehero.md))
- "dashboard is intuitive and gives us a much clearer picture of profitability" — Kinvara Skincare, Shopify App Store, January 7 2026 ([profile](../competitors/storehero.md))
- "simplified, impactful dashboards that help make decision making easier" — Raycon, Shopify App Store, March 18, 2026 ([profile](../competitors/lifetimely.md))

**Theme: Profit/contribution-margin as the headline metric**
- "clarity around contribution margin. It gives a true understanding of what is actually driving profit" — Origin Coffee, Shopify App Store, March 2 2026 ([profile](../competitors/storehero.md))
- "It gives you real visibility into profitability—way beyond Shopify's standard reporting." — The Herbtender, Shopify App Store, August 2025 ([profile](../competitors/conjura.md))
- "Best app i've used to track profit/loss great for beginners!" — Elyso, Shopify App Store, February 2, 2026 ([profile](../competitors/triple-whale.md))
- "tells you exactly where you are loosing money and how to fix it" — Frome, Shopify App Store, February 4, 2026 ([profile](../competitors/trueprofit.md))

**Theme: Real-time / mobile**
- "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design." — paraphrased consensus across 2026 reviews (workflowautomation.net, headwestguide.com, [profile](../competitors/triple-whale.md))
- "just what I needed to track my costs in real time" — Obnoxious Golf, Shopify App Store, April 15, 2026 ([profile](../competitors/trueprofit.md))
- "Great app for keeping an eye on your main metrics" — GetUp Alarm, Shopify App Store, March 9, 2026 ([profile](../competitors/trueprofit.md))

**Theme: Trust in the data**
- "The ability to see (and trust!) our data at a high level gives us peace of mind." — Optimal Health Systems, Shopify App Store, July 2024 ([profile](../competitors/polar-analytics.md))
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra, July 12, 2024 ([profile](../competitors/triple-whale.md))

## What users hate about this feature

**Theme: Dashboard overload / IA volatility**
- "for a small operation it's just way overload." — BioPower Pet, Shopify App Store, April 2, 2026 ([profile](../competitors/triple-whale.md))
- "Modifying reports or navigating menus is a cluster." — BioPower Pet, Shopify App Store, April 2, 2026 ([profile](../competitors/triple-whale.md))
- "The feature set is expanding rapidly, which means the UI changes frequently and documentation sometimes lags behind." — Derek Robinson / Noah Reed, workflowautomation.net, 2025–2026 ([profile](../competitors/triple-whale.md))
- "Switching between views and reports can be slow sometimes" — bloggle.app review, 2024 ([profile](../competitors/polar-analytics.md))

**Theme: Reports paywalled / customization gated**
- "basic plan customers get access to dashboard, live view, and basic acquisition reports, but sales reports, full customer reports, order reports, and custom report creation are unavailable." — Putler summary of Shopify Native ([profile](../competitors/shopify-native.md))
- "Pretty poor app overall. Expensive and slow. Buggy." — Plushy, Shopify App Store review, March 29, 2022 ([profile](../competitors/lifetimely.md))
- "Overpriced for what it is. Very basic and slow." — TheCustomGoodsCo, Shopify App Store review, May 16, 2022 ([profile](../competitors/lifetimely.md))

**Theme: Stale or untrusted data**
- "Real-time claim vs. actual freshness." Marketing says "real-time"; reviewers say "every few hours." — Reputational mismatch documented across Lifetimely reviews ([profile](../competitors/lifetimely.md))
- "After scaling, [Lifetimely] started breaking in ways that actually cost them money. The interface gave the illusion of accuracy, but under the hood, it just wasn't reliable at the SKU level." — paraphrased Reddit/community sentiment ([profile](../competitors/lifetimely.md))
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — AI Systems Commerce, 2026 review ([profile](../competitors/triple-whale.md))
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson, workflowautomation.net, March 16, 2026 ([profile](../competitors/triple-whale.md))

**Theme: AI on the dashboard hallucinates**
- "The AI has proven to be not only unreliable but actively detrimental to my brand's management." — Dawsonx on Sidekick, Shopify Community, February 24, 2026 ([profile](../competitors/shopify-native.md))
- "Support confirmed there is 'no setting' to prevent the AI from hallucinating data or ignoring negative SEO constraints." — Dawsonx, Shopify Community, February 24, 2026 ([profile](../competitors/shopify-native.md))
- "Building with the AI tool Moby is very buggy and crashes more than half the time." — Trustpilot reviewer cited via search aggregator ([profile](../competitors/triple-whale.md))

**Theme: Bot / Direct traffic noise**
- "The frequent occurrence of bot traffic makes it impossible for me to rely on the Visitor stats… I can tell when I have bot traffic because I'll see the same four dots in the exact same locations." — PrimRenditions, Shopify Community, January 19, 2023 ([profile](../competitors/shopify-native.md))
- Last-click default + Direct-traffic inflation up to 40% per Putler's Shopify analysis ([profile](../competitors/shopify-native.md)).

**Theme: Pricing escalates as the business grows**
- "Pricing is based on revenue tiers, which means costs increase as your store grows — can get expensive at scale." — Rachel Lopez, workflowautomation.net, January 31, 2026 ([profile](../competitors/triple-whale.md))
- "At $149/month for the first paid tier, Lifetimely is a real line item, with stores doing under $30K/month potentially struggling to justify it." — ATTN Agency review, 2026 ([profile](../competitors/lifetimely.md))
- "Once your brand crosses the $5M GMV mark, costs can climb steeply, particularly if you want advanced features like pixel attribution or Snowflake access." — Conjura comparison article, 2025 ([profile](../competitors/polar-analytics.md))

## Anti-patterns observed

- **Hidden source disagreement collapsed into one "Blended" number:** Triple Whale's Summary defaults to a "Blended Metrics" header that hides whether Meta-reported, Triple-Pixel, or first-click revenue is the source of each tile. Reviews complain about attribution feeling "closer to platform self-reporting than fully independent models" ([profile](../competitors/triple-whale.md)). Conjura's UI exposes the disagreement (Last Click vs Platform Attributed columns side-by-side, [profile](../competitors/conjura.md)) — the *disagreement* IS the information, but only one competitor leans on it on the overview surface.

- **Real-time claim without disclosed refresh interval:** Lifetimely markets "real-time" while reviewers report "every few hours" ([profile](../competitors/lifetimely.md)); StoreHero's "real-time" is marketing language with no published refresh SLA ([profile](../competitors/storehero.md)); Daasity is honest about it ("LAST COMPLETE DAY DATA" labelled on dashboards, [profile](../competitors/daasity.md)) and is praised for transparency rather than penalized for slowness.

- **Customization paywalled at the highest tier:** Bloom locks marketing attribution out of the Overview until the $80 Flourish tier ([profile](../competitors/bloom-analytics.md)); BeProfit gates Customizable Dashboard to Pro+ ($99/mo) and Custom Reports/P&L to Pro+ ([profile](../competitors/beprofit.md)); Shopify Native gates Custom Report Builder + ShopifyQL behind Advanced ($299/mo) ([profile](../competitors/shopify-native.md)). Result: the Overview screen the merchant first sees is fixed and minimal — a leading reason for upgrade churn.

- **AI sidebar that hallucinates on the dashboard surface:** Sidekick reportedly fabricates SEO/technical data and "ignor[es] negative constraints" with "'no setting' to prevent the AI from hallucinating" ([profile](../competitors/shopify-native.md)); Moby reviewers report it "crashes more than half the time" ([profile](../competitors/triple-whale.md)). The AI-on-dashboard pattern carries trust-collapse risk when outputs aren't constrained to verifiable computed metrics.

- **Filter changes that don't auto-apply:** Daasity's Flash Dashboards require an explicit click on a Refresh Button after toggling filters ("the Data on the Dashboards will update after you click the Refresh Button", [profile](../competitors/daasity.md)) — reads as broken to merchants used to instant filter feedback elsewhere.

- **Bot/Direct traffic inflating Sessions tiles:** Shopify Native's session counts include unfiltered bot traffic; PrimRenditions reports "the same four dots in the exact same locations: CA, KS, IA, and Ireland!" ([profile](../competitors/shopify-native.md)). Visitor count on the Overview becomes unreliable.

- **"Order deletion" wipes data with no recovery path:** Shopify Native — once an order is deleted in admin, its reporting history on the Overview is permanently erased ([profile](../competitors/shopify-native.md)).

- **Departmental tabs hide the cross-functional view:** Daasity's Home segments KPIs by Ecommerce / Marketing / Retail tabs ([profile](../competitors/daasity.md)) — fine for omnichannel teams, but founders looking for a single "state of the business" number must mentally re-aggregate across tabs.

- **Income-statement layout assumes the merchant reads top-down:** Lifetimely's stacked income-statement view ([profile](../competitors/lifetimely.md)) is praised by CFO/founder personas but reviewers note it "isn't reliable at the SKU level" — the layout's strength (line-by-line cost composition) is also its weakness when one of the lines is wrong, because the whole stack reads broken.

## Open questions / data gaps

- **Per-card date-range overrides** are NOT documented for Shopify Native, Polar, Triple Whale, or any other surveyed competitor. The dashboard date picker is dashboard-wide in every observed case. Whether any competitor ships per-tile date scope is unverified.
- **Exact tile color tokens** (hex values, dark-mode variants) are not extractable from public sources for any competitor except Lebesgue (blue=positive, red=negative) and Klaviyo's Analytics Overview line chart (blue/teal/yellow). Lifetimely is described as "neutral palette with restrained green/red" but precise tokens are gated behind authenticated dashboards.
- **Drag-handle iconography:** only Shopify Native's ⠿ glyph is publicly documented; Triple Whale references a 📌 pin icon for tile-pinning; Bloom and Polar describe drag-and-drop without naming a glyph.
- **AI sidebar latency / multi-turn behavior** on the dashboard surface — Polar's "Ask Polar produces editable Custom Reports rather than chat answers" is the only well-documented pattern; Moby/Sidekick/Henri/Owly UI screenshots are limited in public sources.
- **Triple Whale, Polar, Shopify Native admin UI screenshots** are paywalled behind authentication; Help Center pages return HTTP 403 to WebFetch on all three (kb.triplewhale.com, help.shopify.com, intercom.help/polar-app). UI specifics on hover state, tooltip behavior, and per-card actions could not be verified end-to-end.
- **Putler Copilot / Lebesgue Henri / StoreHero MCP-for-Claude** UI surfaces have minimal public screenshots — chat-bubble vs full-panel vs slide-over patterns are inferred from marketing, not observed.
- **Whether competitors store ratios** (CPM, CPC, ROAS, CPA, MER) at aggregate level for the Overview tile, or compute them on read, is mostly unobservable from public sources. Triple Whale's Benchmarks dataset ([profile](../competitors/triple-whale.md)) is large enough that storage is implied; Polar's semantic layer treats ratios as derived. Nexstage's "compute on the fly" rule is not contradicted by any public competitor disclosure but isn't directly compared either.

## Notes for Nexstage (observations only — NOT recommendations)

- **Customizable metric-card grid is the dominant pattern (5/14 competitors with full drag/drop/resize, plus 4 more with partial configurability).** Shopify Native's ⠿ + corner-resize + library-panel-on-edit ([profile](../competitors/shopify-native.md)) is what Shopify merchants are already trained on; any Nexstage Overview that doesn't at least match this idiom carries a re-learning cost. Source-of-truth observation, not a build directive.

- **Period-vs-period delta is universal across all 14 competitors.** Inline `+10.3% vs last month` text on every tile is table-stakes; a tile without it reads broken.

- **Six-source-badge hypothesis lands in a partial-precedent space.** Conjura exposes 2 attribution columns (Last Click + Platform Attributed) on the Campaign Deepdive ([profile](../competitors/conjura.md)). Polar exposes 3 columns (Platform / GA4 / Polar Pixel) on the Attribution screen ([profile](../competitors/polar-analytics.md)). Lifetimely shows pixel + GA4 + platform-reported as 3 columns on the Attribution Report ([profile](../competitors/lifetimely.md)). No surveyed competitor reaches 6 simultaneous source lenses on the *Overview* screen — every multi-source view is on a sub-page (Attribution / Campaign Deepdive). The Overview surface itself defaults to a single "blended" number across all competitors.

- **GSC is absent from 12 of 14 competitor Overviews.** Only Putler ([profile](../competitors/putler.md)) and Glew ([profile](../competitors/glew.md)) ingest GSC at all, and neither surfaces it as a first-class Overview tile. Triple Whale, Polar, StoreHero, Lifetimely, Shopify Native, Conjura, BeProfit, TrueProfit, Bloom, Daasity, Klaviyo, Lebesgue all skip GSC. Direct gap for the 6-source thesis.

- **GA4 is conspicuously absent from Triple Whale, BeProfit, TrueProfit, Klaviyo.** Polar, Lifetimely, Conjura, Daasity, Lebesgue, Shopify Native (no), Bloom, Glew do ingest it but typically as supporting data rather than a primary Overview lens.

- **AI sidebar is now table-stakes (8/14 competitors ship a chat surface beside the dashboard)** — Moby, Ask Polar, Sidekick, Owly, Henri, Putler Copilot, Ask AMP, StoreHero MCP-for-Claude. Polar's "chat output is an editable BI report, not a frozen answer" ([profile](../competitors/polar-analytics.md)) is the smartest pattern observed; Sidekick's hallucination problem ([profile](../competitors/shopify-native.md)) is the cautionary tale.

- **Daily email digest is a recurring dashboard cousin (4/14: Glew Daily Snapshot, Lifetimely 7am P&L, Conjura daily round-up, StoreHero email reports).** Even competitors with strong in-app dashboards ship an asynchronous email of the same data.

- **Mobile parity is unevenly distributed.** Triple Whale ships native iOS+Android with widgets and push ([profile](../competitors/triple-whale.md)); StoreHero and TrueProfit ship iOS only; Polar, Lifetimely, Conjura, Bloom, BeProfit, Daasity, Glew, Lebesgue have NO mobile app at all — Polar's Trustpilot reviews and bloggle.app explicitly call mobile a "weak point."

- **Income-statement layout is the contrarian pattern.** Lifetimely is alone among the 14 in NOT using a tile/grid at all — line-items descend top-down P&L style. Praised by CFO/founder personas; criticized for "reliability at the SKU level" being hard to spot in a stacked layout.

- **Color conventions are not universal.** Lebesgue inverts to **blue=positive, red=negative** ([profile](../competitors/lebesgue.md)). Putler uses event-type colored dots in the Activity Log instead of green/red. Lifetimely deliberately uses a "professional… restrained" palette. CLAUDE.md's source-color tokens (`--color-source-{real,store,facebook,google,gsc,ga4}`) are an independent design choice not contradicted by any precedent.

- **"Real-time" is a marketing claim everyone makes and few defend.** Triple Whale (claim, push notifications "within minutes"), Polar (hourly standard), Lifetimely (claim vs "every few hours" reality), StoreHero (no published SLA), TrueProfit ("every 15 minutes" mobile), Shopify Native (real-time post-2024 rebuild), BeProfit (claim), Bloom (claim), Conjura (daily, honest), Daasity (nightly except Hourly Flash, honest), Glew (hourly, honest), Klaviyo (real-time events), Lebesgue (unstated). The honest competitors (Daasity, Glew) are praised for transparency; the dishonest ones absorb negative reviews.

- **Customization gated to higher tiers is a recurring upgrade lever AND a recurring complaint.** Drag/drop, custom cards, custom reports = Shopify Advanced ($299), BeProfit Pro ($99), Bloom Flourish ($80), Triple Whale Advanced ($259-389). The "free Overview must be customizable" frontier is uncrowded — only Shopify Native and Triple Whale's Founders Dash offer drag/drop on the free tier among the 14 surveyed.
