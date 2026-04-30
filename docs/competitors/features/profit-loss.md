---
name: Profit & Loss
slug: profit-loss
purpose: Answer "Am I profitable, and which lines move the needle?" by stitching revenue, COGS, ad spend, fees, shipping, refunds, and operating expenses into a single statement that shows the journey from gross sales to net profit.
nexstage_pages: profit, dashboard
researched_on: 2026-04-28
competitors_covered: lifetimely, trueprofit, storehero, bloom-analytics, conjura, beprofit, triple-whale, polar-analytics, putler, shopify-native, klaviyo, profit-calc, adbeacon, glew
sources:
  - ../competitors/lifetimely.md
  - ../competitors/trueprofit.md
  - ../competitors/storehero.md
  - ../competitors/bloom-analytics.md
  - ../competitors/conjura.md
  - ../competitors/beprofit.md
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/putler.md
  - ../competitors/shopify-native.md
  - ../competitors/klaviyo.md
  - ../competitors/profit-calc.md
  - ../competitors/adbeacon.md
  - ../competitors/glew.md
  - https://useamp.com/products/analytics/profit-loss
  - https://help.useamp.com/article/643-the-profit-dashboard
  - https://www.bloomanalytics.io/shopify-profit-and-loss-dashboard
  - https://docs.bloomanalytics.io/profit-table.md
  - https://docs.bloomanalytics.io/order-profits.md
  - https://trueprofit.io/solutions/profit-dashboard
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/profit-reports
---

## What is this feature

The P&L surface answers the merchant's most basic CFO question: "I had X in sales — where did it actually go, and what's left?" For an SMB Shopify/Woo owner this is the screen that replaces the spreadsheet they used to maintain at 11pm on Sundays — pulling Shopify gross revenue, refunds, COGS (auto from `cost per item` or manual), Stripe/Shopify Payments transaction fees, ShipBob/ShipStation shipping invoices, Meta + Google + TikTok ad spend, and recurring opex (rent, software, salaries) into a single statement. The job-to-be-done is *interpretation*, not aggregation: the merchant wants to read the page top-to-bottom, see which line item moved the most vs last period, and know whether to push or pull a lever next week.

The data to compute a P&L exists in source platforms (Shopify Finance reports, ad-platform billing, 3PL invoices) — what merchants pay for is the *synthesis*. Every competitor's framing differs on one axis: is the P&L a **table** (line-item rows × time-period columns), an **income statement** (vertical waterfall of subtractions: Revenue → ... → Net Profit, accountant-style), a **waterfall chart** (visual cost-bridge from gross revenue down to net), or a **tree-graph / Profit Map** (Bloom's spatial decomposition of where each dollar flows)? Sub-question: per-period drill (P&L for one day vs one month) vs per-order P&L (per-order net profit on every transaction in the orders table). Most competitors do both.

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `orders.total_price`, `orders.subtotal_price`, `orders.total_discounts`, `orders.total_tax`, `orders.total_shipping_price_set`, `orders.refunds`, `orders.line_items.price`, `orders.line_items.quantity`, `orders.line_items.cost` (Shopify "cost per item" field), `orders.transactions.fee` (Shopify Payments)
- **Source: WooCommerce** — equivalent order/line-item/refund/fee fields via REST API
- **Source: Meta Ads / Google Ads / TikTok Ads / Pinterest / Snapchat / Bing** — `campaigns.spend` per period (the marketing-cost line of the P&L)
- **Source: Klaviyo / Sendlane** — campaign-attributed revenue (input to revenue-by-channel breakdowns; not a cost line)
- **Source: ShipStation / ShipBob / Shippo / ShippingEasy / FedEx** — actual `shipping_cost_per_order` (replaces formula-based shipping)
- **Source: Stripe / PayPal / Shopify Payments** — gateway fee per transaction
- **Source: QuickBooks Online** (Lifetimely-style) — accounting expense lines for opex
- **Source: User-input / Cost config** — per-SKU COGS overrides, geographic COGS Zones (TrueProfit), shipping rules by country/product/carrier (Bloom 4-layer fallback), recurring opex (rent, salaries, SaaS), one-time costs, default-COGS-margin fallback %, transaction fee formulas (when not pulled), tax/VAT rules, COD handling rules, tariff cost (Bloom 2025-2026 addition), handling cost, channel fee
- **Source: Computed** — `gross_profit = net_revenue - cogs`, `contribution_margin_1 = gross_profit - fulfillment`, `contribution_margin_2 = cm1 - marketing`, `contribution_margin_3 = cm2 - opex`, `net_profit = revenue - all_costs`, `net_profit_per_order`, `net_profit_per_product`

## Data outputs (what's typically displayed)

- **KPI: Net Revenue** — `gross_sales - discounts - returns`, currency, vs prior period delta
- **KPI: Gross Profit** — `net_revenue - cogs`, currency + %, vs prior
- **KPI: Contribution Margin (CM1/CM2/CM3)** — staged subtractions; explicitly named in Bloom and Lifetimely
- **KPI: Net Profit** — bottom-line dollar + margin %, vs prior
- **KPI: Marketing Spend / MER / POAS / ROAS** — feeds into the marketing-cost row
- **Line items (rows of the statement):** Gross Sales, Discounts, Returns/Refunds, Net Sales, COGS, Fulfillment Costs (shipping + handling + packaging), Gateway/Transaction Fees, Channel Fees, Tariff Cost, Marketing/Ad Spend, Custom OPEX (recurring + one-time), Taxes, Net Profit
- **Dimension: Time period** — day / week / month / quarter / year (column axis on the table; or the time-comparison toggle)
- **Dimension: Per-order** — order_id with each cost component as a column (per-order P&L)
- **Dimension: Per-product** — SKU/variant with allocated cost components
- **Dimension: Channel / store** — multi-store rollup or per-channel breakdown
- **Comparison:** Period-over-period (last 30d vs prior 30d), YoY, vs goal/forecast (StoreHero)
- **Export:** PDF, CSV, Excel; scheduled email/Slack delivery (daily/weekly/monthly)

## How competitors implement this

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Default landing screen — top-level sidebar item "Profit Dashboard" / branded "Income Statement"
- **Visualization:** Income-statement vertical stacked layout (line per cost category, descending from revenue → contribution → net), accountant-style — explicitly NOT a 4-up KPI grid
- **Layout (prose):** "Top: revenue, product costs, marketing costs, and net profit as the four anchor figures, structured as an income-statement-style stacked vertical layout. Below the headline figures, costs are factored in line-by-line (Shopify COGS auto-pulled, transaction gateway fees, shipping from ShipStation/ShipBob, custom recurring costs)." (1800DTC hands-on breakdown; useamp.com product page)
- **Specific UI:** "Income-statement table format (line per cost category, descending from revenue → contribution → net). Top-of-page date-range picker. Color usage from screenshots described as 'professional color scheme emphasizing readability' — neutral palette with restrained green/red for deltas." (UI details beyond this not directly observable from public sources without paid trial.)
- **Filters:** Daily / weekly / monthly toggles; date-range picker
- **Data shown:** Total sales, COGS, marketing spend, gross margin, contribution margin, net profit, refunds, fees, custom expenses
- **Interactions:** Date-range and granularity toggles; data refreshes "every few hours" (reviewers) despite "real-time" marketing copy; email/Slack export delivered at 7am daily / Monday 8am
- **Why it works (from reviews):** "simplified, impactful dashboards that help make decision making easier" — Raycon, Shopify App Store review, March 18, 2026; "tool we rely on every day to make decisions" — Constantly Varied Gear, March 23, 2026
- **Source:** [profile](../competitors/lifetimely.md); https://useamp.com/products/analytics/profit-loss; https://help.useamp.com/article/643-the-profit-dashboard

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Two surfaces. (1) **Profit Dashboard** — default landing, KPI-led; (2) **P&L Report** — sidebar nav, gated to Advanced tier ($60/mo+)
- **Visualization:** Profit Dashboard = large primary KPI number ("$495,345" example) with supporting KPI tiles + a metric-picker line graph + a categorical cost-breakdown chart (likely stacked bar or donut — not confirmed). P&L Report = accountant-style P&L statement, line items grouped Revenue → Discounts/Refunds → Net Revenue → COGS → Gross Profit → Operating Costs → Net Profit
- **Layout (prose):** "Top of page shows live net-profit number prominently with gross revenue, profit margin, and AOV displayed alongside. Below the KPIs sits a dynamic line graph for performance over time (user picks which metric — revenue, orders, ROAS, net profit — and toggles day/week/month). Below that, a cost breakdown chart (categorical breakdown across packaging, fulfillment, marketing fees, transaction fees, custom costs)."
- **Specific UI:** Schedulable email delivery (daily/weekly/monthly) on Advanced tier via "Customizable Email Reports"; CSV export; date-range picker; multi-store switcher
- **Filters:** Date range, store-switcher (rollup vs single store), metric-picker on the line graph
- **Data shown:** Net profit, gross revenue, profit margin, AOV, ROAS, orders, "average order profit", total costs; on P&L Report — full statement lines
- **Interactions:** Metric picker on line graph; date selection; "every 15 minutes" refresh per mobile-app marketing
- **Why it works (from reviews):** "tells you exactly where you are loosing money and how to fix it" — Frome, Shopify App Store, February 4, 2026; "not having a profit calculator is biggest mistake a shop can do" — Carholics, March 11, 2026
- **Source:** [profile](../competitors/trueprofit.md); https://trueprofit.io/solutions/profit-dashboard

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** "Finance" tab in main top-level nav (alongside Dashboard, Ads, Creatives, LTV, Products, Orders); Academy module "P&L" 7:42 lesson
- **Visualization:** P&L statement table — rows are line items (Net Sales, COGS, Marketing Spend, Contribution Margin, then operating costs); columns are time periods (day / week / month). Time-granularity toggle is the headline interaction
- **Layout (prose):** "Expert view of your full P&L split by day, week, or month on a single screen" (homepage). Dashboard-level KPI tiles for net sales, ad spend, contribution margin
- **Specific UI:** Day/week/month granularity toggle. Specific column count, frozen-row behavior, export options NOT observable from public sources. Unique: paired with **Spend Advisor** "next-$100 → profit" simulator and **Goals & Forecasting** module that adds a "green & red traffic-light system" (binary, no amber) for goal drift
- **Filters:** Time granularity (day/week/month); store-switcher for agency multi-store view
- **Data shown:** Net Sales, COGS, Marketing Spend, Contribution Margin (their North-Star metric), MER, ROAS, breakeven ROAS, new customer sales, repeat customer sales, AOV — every screen anchored to contribution margin
- **Interactions:** Time-granularity toggle; daily/weekly/monthly Slack + email digest delivery
- **Why it works (from reviews):** "clarity around contribution margin. It gives a true understanding of what is actually driving profit" — Origin Coffee, Shopify App Store, March 2 2026; "we caught a huge profit issue and turned it around within a day — cut 3K of wasted spend almost immediately!" — Jordan West, homepage testimonial
- **Source:** [profile](../competitors/storehero.md); https://storehero.ai/; https://academy.storehero.ai/p/platform-deep-dive

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Three named surfaces: (1) **Profit Map** (sidebar > Profit Analytics) — visual interactive tree-graph; (2) **Profit Table / Profit Report** (sidebar > Profit Analytics) — pivot table; (3) **Order Profits** (sidebar) — per-order table
- **Visualization:** Three distinct viz types in the same product. **Profit Map** = interactive tree-graph with nodes/branches showing how metrics flow into net profit ("Visualize Profit Drivers at a Glance" homepage; "consolidates revenue alongside every expense — ad spend, product costs, shipping, and operating overhead so you instantly see how net profit is calculated"). **Profit Table** = pivot table where columns are time periods plus a final "Total" column, rows are metrics, many parent rows expandable. **Order Profits** = wide per-order table with column-selector
- **Layout (prose):** Profit Table organizes metrics in 5 vertical tiers: (1) Revenue (7 categories), (2) Cost (product cost, COGS, fulfillment with 6 subcategories), (3) Contribution Margins (CM1, CM2, CM3 plus % variants), (4) Marketing (6 indicators including MER, CAC, MPR), (5) Net Profit (5 final profitability metrics). Order Profits row contains: "Created At, Items, Gross Sales, Discounts, Refunds, Net Sales, Tax, Total Sales, Shopify Gross Profit, Shopify Gross Margin %, Contribution Margin 1 (+%), Contribution Margin 2 (+%), Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost"
- **Specific UI:** Profit Table — "expandable parent rows, negative numbers rendered with minus signs, all amounts in account-level Reporting Currency"; group-by toggle (day/week/month). Profit Map — tree-graph node/branch layout (specific node count, orientation, hover state NOT documented publicly; no `profit-map.md` page exists in docs sitemap, 404 confirmed). Order Profits — column selector top-right, filter icon, export to Excel/CSV/PDF
- **Filters:** Date range picker; group-by (day/week/month); order-name search dropdown; column visibility
- **Data shown:** All P&L lines including the unique 6-column order cost decomposition (Gateway / Shipping / Variant COGS / Handling / Channel Fee / Tariff Cost — last is 2025-2026 addition). CM1/CM2/CM3 explicit naming
- **Interactions:** Real-time API sync; row expansion on Profit Table; click-to-filter on Profit Map (interactivity claimed but specifics not documented)
- **Why it works (from reviews):** "We now know exactly what we make from every sale" — kicksshop.nl, January 19, 2026; "No more digging through spreadsheets — just instant, actionable data." — Baron Barclay Bridge Supply, March 11, 2025
- **Source:** [profile](../competitors/bloom-analytics.md); https://docs.bloomanalytics.io/profit-table.md; https://docs.bloomanalytics.io/order-profits.md; https://www.bloomanalytics.io/shopify-profit-and-loss-dashboard

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** P&L is implicit rather than a named tab — Performance Overview is the daily snapshot, with **Order Table Dashboard** providing per-order P&L drill-down
- **Visualization:** No standalone P&L statement screen named publicly. Order Table = per-order table with rows enriched by customer/product/profit data. Pre-built saved views: "New customer orders, unprofitable orders and most profitable" and example custom views like "Orders where profit margin is >70%, orders with refunds, high shipping costs and more"
- **Layout (prose):** Per-order columns include profit margin, ad budget allocated to the order's SKU (via SKU-level ad-spend attribution by URL), refund flag, promo code, shipping cost, customer flag (new/existing). Promocode sub-report attached
- **Specific UI:** Custom filter views; saved segments; "individual customer profiles linked from each row." Owly AI overlay can answer NL queries against the P&L data ("Where am I overspending on ads?")
- **Filters:** Date range, store, channel, territory, segment
- **Data shown:** Contribution Profit (their headline KPI on Performance Overview, not Revenue/ROAS), Order-level profit margin, attributed ad spend per SKU, refunds, shipping cost
- **Interactions:** Daily refresh ("refreshed nightly" per Capterra review and "updated daily" on Performance Overview); daily email digest "Daily performance round-up"; save custom views; CRM-export from Customer Table
- **Why it works (from reviews):** "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, Shopify App Store, March 2024; "It gives you real visibility into profitability—way beyond Shopify's standard reporting." — The Herbtender, Shopify App Store, August 2025
- **Source:** [profile](../competitors/conjura.md); https://www.conjura.com/order-table-dashboard

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Two: (1) **Profit Dashboard** — main landing screen; (2) **P&L report** — gated to Pro tier ($99/mo+) as a "formal profit-and-loss statement"
- **Visualization:** Profit Dashboard = top filter strip with date-range picker (Daily/Weekly/Monthly presets), KPI row (Lifetime Profit, Retention, ROAS, POAS as headline numbers), expense-tracking section underneath with category breakdown. P&L Report = formal statement (UI not pictured in public sources beyond pricing-page mention)
- **Layout (prose):** "View your store's lifetime profit, retention, ROAS and POAS" (Shopify App Store screenshot caption). UI uses "intuitive charts and graphs for trend spotting" (WooCommerce listing). Per-order drill: "Identify your unprofitable orders and most profitable orders" implies sortable orders table with profit-color coding or top/bottom toggle
- **Specific UI:** Multi-dimension grouping (product / type / vendor / collection / variant); columns include revenue, profit, COGS, sales. Period switcher (daily/weekly/monthly), real-time refresh, export
- **Filters:** Date range; group-by; period preset
- **Data shown:** Net profit, gross profit, contribution profit, ROAS, POAS, retention, lifetime profit, expenses by category; per-order P&L
- **Interactions:** Period switching, real-time refresh, export, sort by profit ascending (unprofitable) / descending (top); per-order expansion
- **Why it works (from reviews):** "+20% Contribution Profit Within 3 Months" homepage outcome claim; positions explicitly against "spreadsheet-based P&L tracking"
- **Source:** [profile](../competitors/beprofit.md); Shopify App Store screenshots 1-5

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** No dedicated P&L tab. Net profit and POAS surface as **tiles inside the Summary Dashboard** alongside Revenue, MER, ncROAS, CAC, AOV, LTV; dashboard organized as "collapsible sections by data integration" (Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics, Custom Expenses)
- **Visualization:** Tile grid (KPI cards) with optional "table view" toggle that pivots the same tile grid into a dense single-table layout. P&L is decomposed into individual tiles, not presented as a vertical statement
- **Layout (prose):** "Body is organized as collapsible sections by data integration… each section is a grid of draggable metric tiles." Custom Expenses section is the OPEX line; net profit tile sits in a finance-oriented pinned section
- **Specific UI:** "KPI tile shows headline value + period-vs-period delta. Hovering a tile reveals a 📌 pin icon (the help-center copy literally references the pushpin emoji). 'Edit Dashboard' button enters drag-edit mode." On-demand refresh button (April 2026) cycles "Refreshing Meta…" status per integration
- **Filters:** Date range + comparison toggle; store-switcher; per-section filters
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV (60/90), Total Ad Spend
- **Interactions:** Drag-and-drop tile reordering; pin to "Pinned" section; pivot to table view; click metric to drill; Moby Chat sidebar on every dashboard
- **Why it works (from reviews):** "Best app i've used to track profit/loss great for beginners!" — Elyso, Shopify App Store, February 2, 2026
- **Source:** [profile](../competitors/triple-whale.md)

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Dedicated **Profitability page** (pre-built) — "Contribution margin at business / product / campaign level; net profit"
- **Visualization:** Custom Dashboard / Canvas Builder pattern — vertical canvas with stacked Metric Cards or Sparkline Cards in a horizontal row across the top, charts and tables below. Profitability page is a pre-built canvas instance of this pattern
- **Layout (prose):** "Recommended pattern (per docs): Metric Cards or Sparkline Cards in a horizontal row across the top, with charts and tables below. Date range selector lives in the top-right of the dashboard." Three block types: (1) Key Indicator Section — grid of metric cards with optional targets; (2) Tables/Charts — Custom Reports composed of metrics × dimensions × granularities × filters; (3) Sparkline Card — metric card with a mini trend line embedded
- **Specific UI:** **No-code formula builder for Custom Metrics** (e.g., "net profit = revenue - cogs - ad_spend - shipping"). Users "tap into all of our data without any SQL" — semantic layer abstracts joins. Comparison indicators (improvement/decline arrows) render automatically. Schedule a block to auto-deliver as Slack message or email
- **Filters:** Dashboard-level date range controls every block at once; comparison toggle (vs prior period or YoY); Views (saved-filter system) for store/region/channel slicing
- **Data shown:** Profit, MER, CAC, ROAS, LTV, AOV, repeat rate, cohort retention, contribution margin, net profit — all configurable via formula
- **Interactions:** Drag/drop block reordering; schedule for delivery; Ask Polar AI emits a Custom Report you can edit
- **Why it works (from reviews):** Custom Metrics no-code formula builder "heavily praised" — semantic layer + formula composer for "net profit = revenue - cogs - ad_spend - shipping"
- **Source:** [profile](../competitors/polar-analytics.md)

### Profit Calc ([profile](../competitors/profit-calc.md))
- **Surface:** Single **Main Profit Dashboard** is the default landing; a sibling **Detailed Reporting / Analytics view** holds the explicit "P&L report"
- **Visualization:** Tile-based dashboard layout (per App Store screenshot) with "P&L style cost-waterfall sense" — though the specific viz type is not pixel-confirmable from compressed App Store thumbnails. App Store screenshot caption: "Deep profit analytics: ROAS, AOV, refunds, VAT, fees, P&L report"
- **Layout (prose):** "One-click true profit dashboard: orders, COGS, fees, ads, taxes" — single canvas combining net profit headline plus the cost stack feeding into it
- **Specific UI:** UI details beyond screenshot captions not available. "One-click" framing implies no required configuration beyond Shopify + ad accounts + COGS connections. VAT and COD handling baked in (relevant for EU dropship segment). Multi-currency with both real-time and historical FX rates
- **Filters:** Date range; multi-store toggle (per-store subscription required)
- **Data shown:** net profit, orders, COGS, fees, ad spend, taxes, ROAS, AOV, refunds, chargebacks, VAT, fees, P&L statement
- **Interactions:** Period selection; export
- **Why it works (from reviews):** "helps me keep track of my profit without ever having to be confused" — divaree, March 11, 2026; "Saves so much time and gives me a true picture of my actual profit" — leighanndobbsbooks, May 11, 2025
- **Source:** [profile](../competitors/profit-calc.md)

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Two reports surfaces. (1) **Finance reports** under Reports library — answers "What does the P&L look like? Refunds? Taxes? Tips?" Includes Finance summary, Sales report, Payments report, Liabilities, Tips, Gift cards, Taxes, US sales tax. (2) **Profit reports** (Advanced+) — Gross profit by product, Gross profit by variant, Gross profit breakdown card
- **Visualization:** Standard Shopify report pattern: chart at the top, metric+dimension chips on a side panel, sortable table below. Finance summary "Gross profit breakdown card" shows net sales, costs, and profit; surfaces both "Net sales without cost recorded" and "Net sales with cost recorded"
- **Layout (prose):** "Configuration panel allows users to add or remove metrics and dimensions, change the visualization type, and specify a date range… the various selections populate in the report dynamically." Visualization-type switcher (line / bar / table). Plus tier ($2,300/mo, 3-yr term) adds Financial Reports with "P&L-style summaries, payment reconciliation, tax totals"
- **Specific UI:** Inline COGS field on each product variant; reports "clearly call out which line items lack a recorded COGS (so they aren't counted toward gross profit)" — explicit exclusion handling
- **Filters:** Date range; metric/dimension chips; visualization-type switcher
- **Data shown:** Net sales, gross sales, total tax, total tip, total shipping, total refund; gross profit by product/variant; net sales with/without cost recorded
- **Interactions:** Drill-down from row to filtered exploration; save as custom report; pin to Overview as custom card; CSV export (with 1MB / 50-record / email-on-large-export caps)
- **Why it works (from reviews):** Sidekick can author the underlying ShopifyQL — natural-language query of profit/finance data
- **Source:** [profile](../competitors/shopify-native.md)

### Putler ([profile](../competitors/putler.md))
- **Surface:** No P&L surface observed in public sources — `grep` returned zero hits for "p&l", "profit/loss", "income statement", "finance" in the Putler profile. Putler exposes Sales, Customers, Products, RFM dashboards but no dedicated profit-and-loss statement
- **Visualization:** Not observed in public sources for P&L. (Putler is included in scan list but does not implement a recognizable P&L feature.)
- **Layout (prose):** Not observed in public sources.
- **Source:** [profile](../competitors/putler.md)

### Klaviyo (revenue side) ([profile](../competitors/klaviyo.md))
- **Surface:** No P&L surface observed — `grep` returned zero hits for profit/P&L/income-statement in Klaviyo's profile. Klaviyo provides email-attributed revenue (the revenue line) but does not own the P&L surface; competitors like Lifetimely, Bloom, StoreHero, Polar pull Klaviyo as a *data input* to their own P&L
- **Visualization:** No P&L visualization. Klaviyo surfaces campaign-attributed revenue at flow/campaign level inside its own marketing analytics screens
- **Layout (prose):** Not applicable as P&L surface.
- **Source:** [profile](../competitors/klaviyo.md)

### AdBeacon ([profile](../competitors/adbeacon.md))
- **Surface:** Named **Profit & Loss Reporting** in product surface inventory ("P&L surface")
- **Visualization:** Not directly observable from public marketing pages. AdBeacon's primary visual identity is its Optimization Dashboard (Meta-modeled UX) and Customer Journey ("380+ actionable data points"). The P&L surface is listed alongside "Profitability Dashboard" (Total Sales, Net Sales, AOV, ROAS, MER, customizable KPIs) and "Orders / Returns / Coupon Codes"
- **Layout (prose):** UI details for the dedicated P&L surface not directly observed.
- **Specific UI:** UI details not available — only feature description listed in surface inventory
- **Data shown:** AOV, MER, profit & loss, organic vs paid attribution, RFM scores (380+ data points), customer LTV, new vs returning splits, 30/60/90-day cohorts
- **Source:** [profile](../competitors/adbeacon.md)

### Glew ([profile](../competitors/glew.md))
- **Surface:** **Net Profit by Channel** screenshot title referenced; LTV / Net Profit / Top Customers / Top Products bundle on Glew Pro tier ($249/mo, $1M-$5M revenue band); 250+ KPIs claimed
- **Visualization:** Marketing screenshot titles include "KPI Highlights", "Performance Channels", "Net Profit by Channel". UI details not directly observed; no public hover/tooltip behavior documented
- **Layout (prose):** "Net Profit by Channel — Channel-level profit breakdown (referenced screenshot title)"
- **Specific UI:** UI details not available — only marketing screenshot titles seen
- **Data shown:** Revenue, Profit, Orders, AOV, Visits, Conversion rate, CAC, LTV, ROAS, channel-specific performance, "LTV Profitability by Channel", "Net Profit by Channel", inventory aging
- **Source:** [profile](../competitors/glew.md)

## Visualization patterns observed (cross-cut)

Counting only competitors with a recognizable P&L surface (10 of 14 scanned — Putler and Klaviyo do not implement; AdBeacon and Glew name-only without observable UI):

- **Income-statement vertical layout (line items descending Revenue → ... → Net Profit):** 3 competitors — Lifetimely (their canonical "Profit Dashboard / Income Statement"), TrueProfit (P&L Report), StoreHero (Finance tab "expert view of your full P&L split by day, week, or month")
- **Pivot-table P&L (rows = metrics, columns = time periods):** 2 competitors — Bloom Analytics (Profit Table with 5 vertical tiers and expandable parent rows) and StoreHero's Finance time-granularity toggle. Highly accountant-compatible; supports period comparison directly in-cell
- **Tree-graph / Profit Map (spatial decomposition):** 1 competitor — **Bloom Analytics' Profit Map** is the only example. "Visual interactive tree graph that lays out exactly how various metrics … impact your net profit." Marketing-page-only — no `profit-map.md` page exists in the docs sitemap. UI details not documented publicly. Novel and uncertain reception
- **KPI-tile grid (P&L decomposed into individual tiles, no statement layout):** 4 competitors — Triple Whale (Summary Dashboard with collapsible-section tile grid), Polar Analytics (Custom Dashboard / Canvas Builder), Profit Calc (Main Profit Dashboard), BeProfit (Profit Dashboard) — sometimes paired with a separate formal P&L Report screen for the statement view
- **Per-order P&L table (every order is a row, cost components are columns):** 5 competitors — Bloom Analytics (Order Profits — most detailed: 6 cost columns including Tariff Cost), BeProfit (per-order drill on Profit Dashboard with profit-color coding), Conjura (Order Table Dashboard with saved views like "unprofitable orders"), TrueProfit (Order Breakdown via Product Analytics), Profit Calc (Order Breakdown)
- **Waterfall chart (visual cost-bridge from gross revenue down to net profit):** **0 dedicated competitors observed.** Profit Calc's App Store screenshot was described as having a "P&L style cost-waterfall sense" but it's a tile layout, not a true waterfall. The closest is Lifetimely's Cohort Waterfall — but that's an LTV/CAC payback waterfall (cumulative bar), not a P&L waterfall (cost-bridge). Looker Studio offers Waterfall as a chart primitive but is a generic BI tool, not a vertical SaaS competitor. **This is a noticeable gap in the category**
- **Finance-report library pattern (multiple separate reports for different cuts of the P&L):** 1 competitor — Shopify Native (Finance summary, Sales report, Payments report, Liabilities, Tips, Gift cards, Taxes, US sales tax; plus separate Profit reports on Advanced+)

**Recurring visual conventions:**
- **Color use:** Lifetimely uses "restrained green/red for deltas" (neutral palette); StoreHero uses **binary green/red traffic-light** (no amber middle) for goal drift in Goals & Forecasting paired with the Finance tab; Bloom renders "negative numbers with minus signs" (no color descriptor confirmed); Triple Whale uses period-vs-period delta on every tile
- **Iconography:** Pencil icons for inline edit (Lifetimely cost editor); 📌 pin emoji for Triple Whale's Pinned section; ⠿ drag-handle (Shopify); "Filter, slice and dice" filter chips on Conjura
- **Period comparison:** Universal — every implementation shows vs prior period delta. YoY toggle on Polar; comparison vs goal/forecast on StoreHero
- **Drill interaction:** Click row → filtered exploration (Shopify Native, Polar, Conjura); column expansion (Bloom Profit Table)
- **Multi-currency reporting:** Bloom (account-level "Reporting Currency"), Profit Calc (real-time + historical FX rates) — relevant for cross-border SMB
- **Email/Slack delivery:** Universal at the top tier — Lifetimely (7am daily / Monday 8am), Bloom (Slack updates Grow tier+), TrueProfit (Customizable Email Reports Advanced+), Polar (block-level scheduling), StoreHero (daily/weekly/monthly digest)

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Replaces the spreadsheet**
- "No more digging through spreadsheets — just instant, actionable data." — Baron Barclay Bridge Supply, [Bloom Analytics profile](../competitors/bloom-analytics.md), Shopify App Store, March 11, 2025
- "Saves so much time and gives me a true picture of my actual profit" — leighanndobbsbooks, [Profit Calc profile](../competitors/profit-calc.md), Shopify App Store, May 11, 2025
- "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." — Island Living, [Conjura profile](../competitors/conjura.md), Shopify App Store, November 2024
- "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, [Lifetimely profile](../competitors/lifetimely.md), Shopify App Store, January 27, 2026
- "Pretty complete for tracking profits in a simple way" — Tempus Mods, [Bloom Analytics profile](../competitors/bloom-analytics.md), Shopify App Store, May 30, 2025

**Theme: Single anchor metric makes decisions easier**
- "clarity around contribution margin. It gives a true understanding of what is actually driving profit" — Origin Coffee, [StoreHero profile](../competitors/storehero.md), Shopify App Store, March 2 2026
- "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, [Conjura profile](../competitors/conjura.md), Shopify App Store, March 2024
- "It gives you real visibility into profitability—way beyond Shopify's standard reporting." — The Herbtender, [Conjura profile](../competitors/conjura.md), Shopify App Store, August 2025

**Theme: Catches expensive mistakes**
- "we caught a huge profit issue and turned it around within a day — cut 3K of wasted spend almost immediately!" — Jordan West, [StoreHero profile](../competitors/storehero.md), homepage testimonial
- "tells you exactly where you are loosing money and how to fix it" — Frome, [TrueProfit profile](../competitors/trueprofit.md), Shopify App Store, February 4, 2026
- "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." — ChefSupplies.ca, [Conjura profile](../competitors/conjura.md), Shopify App Store, January 2024

**Theme: Decision-grade clarity for the founder/CFO persona**
- "simplified, impactful dashboards that help make decision making easier" — Raycon, [Lifetimely profile](../competitors/lifetimely.md), Shopify App Store, March 18, 2026
- "tool we rely on every day to make decisions. Great customer support!" — Constantly Varied Gear, [Lifetimely profile](../competitors/lifetimely.md), Shopify App Store, March 23, 2026
- "Best app i've used to track profit/loss great for beginners!" — Elyso, [Triple Whale profile](../competitors/triple-whale.md), Shopify App Store, February 2, 2026
- "Conjura provides us with a joined-up view of all customer and marketing data. This removes the data silos." — Andy B., [Conjura profile](../competitors/conjura.md), Capterra, January 2019

**Theme: Real-time / set-and-forget**
- "just what I needed to track my costs in real time" — Obnoxious Golf, [TrueProfit profile](../competitors/trueprofit.md), Shopify App Store, April 15, 2026
- "Set-up is easy, love that the data sync in real time, and support is extremely responsive" — Good Bacteria, [Profit Calc profile](../competitors/profit-calc.md), Shopify App Store, March 5, 2026
- "Bloom has been a great tool for real time analytics of our shopify [store]." — World Rugby Shop, [Bloom Analytics profile](../competitors/bloom-analytics.md), Shopify App Store, January 22, 2025

## What users hate about this feature

**Theme: Real-time claim mismatched with actual freshness**
- Reviewers report Lifetimely dashboard updates "every few hours" rather than instantaneous, despite "real-time" in marketing — [Lifetimely profile](../competitors/lifetimely.md) data_freshness paragraph
- Conjura runs daily ("refreshed nightly per Capterra review and 'updated daily' on Performance Overview page") despite Owly AI marketing implying real-time — [Conjura profile](../competitors/conjura.md)
- StoreHero "data-refresh cadence is not technically specified — 'real-time' is marketing language, no exact interval published" — [StoreHero profile](../competitors/storehero.md) weaknesses

**Theme: Refund/return double-counting in P&L**
- "Shopify's financial summary counts all return requests as issued refunds, which can be misleading. Not all return requests are accepted, and not all approved returns end up refunded… I've discussed this concern with the TrueProfit team, but they believe making changes now isn't practical." — Apollo Moda, [TrueProfit profile](../competitors/trueprofit.md), Shopify App Store, May 3, 2024 (2-star)

**Theme: Transaction fees calculated by formula, not pulled from source**
- "transaction fees are calculated by a formula although this can be pulled directly from Shopify [...] Beprofit handle this correctly" — 1-star Shopify App Store reviewer, [TrueProfit profile](../competitors/trueprofit.md) (cited via Reputon)
- "transaction fees are calculated by a formula although this can be pulled directly from Shopify (resulting in incorrect fee calculations; reviewer compared unfavorably to BeProfit on this point)" — WASABI Knives, [Profit Calc profile](../competitors/profit-calc.md), May 2021 (cited via Reputon)

**Theme: Dashboard customization is limited**
- "In future releases, it would be great to be able to customize the main dashboard more" — Insane Army (4-star portion), [Profit Calc profile](../competitors/profit-calc.md), Shopify App Store, January 20, 2026
- BeProfit's competitive page characterizes StoreHero as missing "customizable dashboards, advanced analytics on products, cohorts, LTV, discounts, shipping, and returns" (competitor-authored) — [StoreHero profile](../competitors/storehero.md) weaknesses

**Theme: Pricing is the friction**
- "Support is very slow, the app does not load the prices and the price is far too expensive… If I pay 150 euros a month, I expect direct live support." — Sellsbydanchic, [Lifetimely profile](../competitors/lifetimely.md), Shopify App Store, May 23, 2025 (1-star)
- "At $149/month for the first paid tier, Lifetimely is a real line item, with stores doing under $30K/month potentially struggling to justify it." — ATTN Agency review, [Lifetimely profile](../competitors/lifetimely.md), 2026
- "the pricing plans, it's expensive and they take a % from each [order]" — 1-star Shopify App Store reviewer, [TrueProfit profile](../competitors/trueprofit.md) (cited via Reputon)

**Theme: P&L gated to higher tiers**
- BeProfit's P&L Report is gated to Pro tier ($99/mo+) — [BeProfit profile](../competitors/beprofit.md) pricing table
- TrueProfit's P&L Report is gated to Advanced tier ($60/mo+) — [TrueProfit profile](../competitors/trueprofit.md) pricing table
- Shopify Native's "Financial Reports (P&L-style summaries, payment reconciliation, tax totals)" gated to Plus tier (from $2,300/mo, 3-yr term) — [Shopify Native profile](../competitors/shopify-native.md) pricing table

**Theme: Item-pick / handling charges not captured separately**
- ShipStation/ShipBob "syncs shipping costs effectively" but flagged a limitation on "item pick charges not being captured separately" — Interior Delights, [TrueProfit profile](../competitors/trueprofit.md), March 2026

## Anti-patterns observed

- **Real-time claim without engineering substantiation:** Lifetimely, StoreHero, Conjura all market "real-time" but actual refresh is hourly to daily. Multiple reviewers call this out as a credibility gap — see Lifetimely's "every few hours" reviewer reports. Cited explicitly in [Lifetimely profile](../competitors/lifetimely.md) Notes for Nexstage: "Real-time claim is undefended. 'Every few hours' actual freshness creates a credibility wedge"

- **Aggregating without composition (KPI-tile-grid disguised as a P&L):** Triple Whale shows Net Profit as one tile among many, with cost categories spread across "collapsible sections by data integration." A merchant cannot read top-to-bottom from gross sales to net — they have to assemble the statement mentally from scattered tiles. Reviewers like P&L for "beginners" because the income-statement format is structurally legible (Lifetimely, TrueProfit P&L Report); when decomposed into a tile grid the legibility is lost — [Triple Whale profile](../competitors/triple-whale.md) Summary Dashboard description

- **Refund/return double-counting (taking Shopify's financial summary at face value):** TrueProfit explicitly chose not to fix the "all return requests = refunds" pass-through. The 2-star review from Apollo Moda is a documented anti-pattern: "Stay vigilant to ensure more precise results" — [TrueProfit profile](../competitors/trueprofit.md). Shopify Native handles this better by surfacing both "Net sales without cost recorded" and "Net sales with cost recorded" — explicit exclusion handling

- **Transaction fees computed by formula instead of pulled from gateway feed:** Profit Calc and TrueProfit both criticized for this; BeProfit cited as "handles this correctly" by reviewers comparing the two. Drift is worst on edge cases (Shop Pay Installments, currency conversion on Stripe, partial refunds) — [Profit Calc profile](../competitors/profit-calc.md), [TrueProfit profile](../competitors/trueprofit.md)

- **Hidden P&L gating (Pro/Plus paywall):** Both BeProfit and TrueProfit lock the formal P&L statement behind a tier upgrade. Users on entry tiers see a "Profit Dashboard" but not the P&L Report; the difference is real (statement layout vs tile layout) but the upgrade path is opaque. [BeProfit profile](../competitors/beprofit.md), [TrueProfit profile](../competitors/trueprofit.md)

- **No ad-platform-level cost decomposition in the P&L:** Marketing cost is a single rolled-up bucket in nearly every P&L surface observed. None of the income-statement implementations break "Marketing Spend" into "Meta / Google / TikTok / Pinterest / Snap" sub-rows by default. This collapses the source-attribution information that merchants paid for elsewhere in the product

- **Two parallel pricing surfaces that don't reconcile (StoreHero, Conjura):** Both companies publish revenue-bracket pricing on their main page that doesn't match Shopify App Store pricing. Buyers comparing P&L tools cannot know what they'll actually pay. — [StoreHero profile](../competitors/storehero.md), [Conjura profile](../competitors/conjura.md)

- **No public screenshots / paywalled UI:** TrueProfit's marketing pages use lazy-loaded image carousels that don't render via WebFetch. Bloom's "Open demo app" CTA links to a Shopify install gate. Conjura references "Owly AI Short.gif" but doesn't embed it. The category as a whole is poorly documented for prospective buyers — every implementation requires installing the app to see the actual P&L UI

## Open questions / data gaps

- **Bloom's Profit Map tree-graph visual is not documented in their docs sitemap.** No `profit-map.md` page exists (404 confirmed). The feature is positioned as a marketing centerpiece but the actual node count, orientation, hover state, and click-to-filter interaction are not observable from public sources. Would require a paid eval account to capture the layout
- **Lifetimely's exact line ordering of the income statement** (full row sequence from Revenue down to Net Profit) is not explicit in public sources beyond "Total sales, COGS, marketing spend, gross margin, contribution margin, net profit, refunds, fees, custom expenses." A logged-in screen-cap would resolve
- **No confirmed waterfall-chart P&L implementation.** Looker Studio supports the chart primitive; no vertical SaaS in this batch uses it. Open whether anyone has shipped one in 2026 — possible the closest is Lifetimely's cohort-waterfall (which is LTV-based, not P&L-based)
- **StoreHero's Spend Advisor "next-$100 → profit" simulator.** Specific visual treatment (slider vs numeric input, recommendation pill rendering, three-state pause/pivot/scale UI) NOT observable from public screenshots — academy lesson "Using the Spend Advisor" is 6:10 but the video transcript is not extractable
- **Triple Whale's "Custom Expenses" section** — how OPEX line items are entered, edited, recurring vs one-time treatment, and how they roll up into the Net Profit tile is not documented in public sources
- **Klaviyo / Putler implementation:** Both were listed in the scan list. Putler does not implement a recognizable P&L surface (no hits on grep). Klaviyo provides revenue-side data only and does not own a P&L surface. Confirmed null findings, not data gaps
- **Mobile P&L:** TrueProfit's iOS app surfaces revenue/net profit/cost breakdowns but is "read-only" with "no SKU/attribution drill-downs in mobile per the review blog." StoreHero's iOS app is "read-only summary." Whether any competitor exposes a fully-interactive P&L on mobile is not surfaced
- **AdBeacon's named "Profit & Loss Reporting"** is in the surface inventory but no UI screenshot or layout description is observable in public pages — surface is named but not detailed

## Notes for Nexstage (observations only — NOT recommendations)

- **Income-statement layout (Lifetimely, TrueProfit P&L Report, StoreHero Finance tab) is structurally different from KPI-tile-grid (Triple Whale, Polar, Profit Calc).** 3 implementations choose accountant-statement format; 4 choose tile-grid. Bloom does both (Profit Table = pivot, Profit Map = tree). The format choice maps to persona — CFO/founder reads top-down, performance marketer reads tile-by-tile. Multiple competitors offer **both** as separate screens
- **CM1/CM2/CM3 explicit naming is the de-facto vocabulary** (Bloom, Lifetimely-adjacent, Drivepoint per Bloom's notes). MER, aMER, MPR, aMPR, CAC, BEROAS are the secondary metric vocabulary. Leads will arrive fluent in these acronyms — [Bloom profile](../competitors/bloom-analytics.md) Notes for Nexstage
- **Bloom's Order Profits cost-column set is the most exhaustive observed** (Gateway / Shipping / Variant COGS / Handling / Channel Fee / Tariff Cost — 6 columns). Tariff Cost was a 2025-2026 addition. This is a concrete reference for the per-order cost decomposition schema
- **Bloom's Profit Map is the only spatial/tree-graph P&L** in the category. Marketing-page material only — uncertain reception, no public docs page. If Nexstage builds a "where does the money go" decomposition, this is the only competitive precedent and it's thinly documented
- **No competitor breaks the Marketing Spend line into per-platform sub-rows in the P&L statement** — every P&L rolls Meta/Google/TikTok/Pinterest into one "Marketing" bucket. This collapses the source-attribution information the merchant paid for elsewhere. Direct gap relevant to Nexstage's 6-source-badge thesis
- **No competitor breaks revenue by source attribution lens in the P&L** (Real / Store / Facebook / Google / GSC / GA4). Shopify Native shows "Net sales without cost recorded" vs "Net sales with cost recorded" — that's the closest to a source-disagreement primitive in the P&L. Polar exposes attribution side-by-side on its Attribution screen but not on Profitability
- **Real-time vs actual freshness is a credibility wedge.** 3 competitors (Lifetimely, StoreHero, Conjura) make "real-time" claims with weaker actual freshness ("every few hours" / "nightly" / unspecified). Nexstage has hourly_snapshots + daily_snapshots + an explicit `MetricSourceResolver` — opportunity for explicit data-freshness badges
- **Refund/return double-count is a known industry problem TrueProfit chose not to solve.** Apollo Moda's 2-star review is canon. Shopify Native handles it via "Net sales with cost recorded" / "without cost recorded" surfacing — explicit exclusion is the pattern that earns trust
- **Transaction-fee accuracy is a litmus test.** WASABI Knives + Apollo Moda + the 1-star reviewer corpus all surface this. Pulling from Shopify Payments / Stripe / PayPal feeds (not formula) is the pattern that earns the comparison-site checkmark. Nexstage's `UpsertShopifyOrderAction` already pulls actual fees
- **P&L gating to Pro/Plus tiers is universal.** BeProfit (Pro $99/mo+), TrueProfit (Advanced $60/mo+), Shopify Native (Plus $2,300/mo). Most entry tiers show a "Profit Dashboard" tile-grid; the formal statement is the upgrade trigger. Different from Lifetimely's "all features included" model
- **Per-order P&L is the most consistently-implemented sub-feature** (5 competitors: Bloom, BeProfit, Conjura, TrueProfit, Profit Calc). Click-to-sort by profit ascending = "find unprofitable orders" is the single most quoted use-case. This belongs on the orders surface, not just the profit surface
- **Saved views / pre-built segments on the order P&L are competitive** — Conjura has "unprofitable orders, high shipping costs, profit margin >70%"; Bloom Analytics offers column-selector + filter. Polar's "Views" system unifies filter state across blocks with documented OR-logic gotcha
- **Email/Slack delivery of the P&L is universal at top tiers** — Lifetimely 7am daily / Monday 8am Slack; Bloom Slack updates Grow+; TrueProfit Customizable Email Reports Advanced+; StoreHero daily/weekly/monthly digest; Polar block-level scheduling. Table-stakes for the top tier
- **Multi-currency reporting** is uneven. Bloom (account-level "Reporting Currency"), Profit Calc (real-time + historical FX) handle it cleanly; Triple Whale is per-store. Relevant for cross-border SMB
- **The 6 source badges thesis (Real / Store / Facebook / Google / GSC / GA4) is unique.** Nearest competitor patterns: Polar's 3-column "platform / GA4 / Polar Pixel" attribution side-by-side; Conjura's "Last Click / Platform Attributed" 2-column. None apply to the P&L surface itself — they apply to the Attribution surface. Direct opening for source-tagged P&L lines (e.g., "Revenue: $X according to Store / $Y according to Facebook / $Z according to GA4")
- **No GSC integration in any P&L implementation observed.** Bloom, Lifetimely, TrueProfit, BeProfit, StoreHero, Conjura, Triple Whale all lack GSC. Organic search profitability is invisible. Nexstage's GSC ingestion can layer organic-source profit attribution onto the P&L — uncontested
- **Income-statement layout favors the CFO/founder persona; tile-grid favors the performance-marketer persona.** Lifetimely's "Boardroom KPIs" template, StoreHero's "Finance" tab naming, and Bloom's CM1/CM2/CM3 explicit margin tiering are all CFO-leaning. Triple Whale and Polar lean performance-marketer. Nexstage's persona positioning will dictate format choice
- **Putler and Klaviyo do not own the P&L surface** — confirmed null. Klaviyo is treated as an *input* (email-attributed revenue line) by Lifetimely, Bloom Grow tier+, StoreHero, Polar. Putler's RFM/Sales/Customer surfaces don't include a P&L primitive
