---
name: Cost overview
slug: cost-overview
purpose: Answer "what does it actually cost me to run the business?" by decomposing every dollar spent — COGS, shipping, fees, ad spend, refunds, OPEX — into a single comparable view, ideally beside revenue and net profit.
nexstage_pages: profit, dashboard, cost-config (read surface)
researched_on: 2026-04-28
competitors_covered: trueprofit, beprofit, lifetimely, conjura, profit-calc, triple-whale, polar-analytics, storehero, bloom-analytics, putler, shopify-native, hyros
sources:
  - ../competitors/trueprofit.md
  - ../competitors/beprofit.md
  - ../competitors/lifetimely.md
  - ../competitors/conjura.md
  - ../competitors/profit-calc.md
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/storehero.md
  - ../competitors/bloom-analytics.md
  - ../competitors/putler.md
  - ../competitors/shopify-native.md
  - ../competitors/hyros.md
  - https://trueprofit.io/solutions/profit-dashboard
  - https://trueprofit.io/blog/what-is-trueprofit
  - https://beprofit.co/expenses-revenue/
  - https://useamp.com/products/analytics/profit-loss
  - https://1800dtc.com/breakdowns/lifetimely
  - https://help.useamp.com/article/643-the-profit-dashboard
  - https://www.conjura.com/performance-overview-dashboard
  - https://docs.bloomanalytics.io/overview-dashboard.md
  - https://docs.bloomanalytics.io/profit-table.md
  - https://docs.bloomanalytics.io/order-profits.md
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/profit-reports
  - https://storehero.ai/contribution-margin-formula/
---

## What is this feature

Cost overview is the single canvas that answers a SMB merchant's most repeated question: **"where does my money actually go?"** Every Shopify and WooCommerce store already has every cost component scattered — COGS in product records, shipping in 3PL invoices, transaction fees in payout reports, ad spend across 5+ ad platforms, refunds in financial summaries, custom OPEX nowhere — and the *feature* is the synthesis that brings them into one decomposition: revenue at the top, costs subtracted line-by-line, net profit at the bottom, with the ability to see which category moved this week.

The reason this is a feature (not just "data") is the level-of-effort gap between (a) having a Shopify Payments fee feed and (b) seeing **"of $100K revenue this month, $42K is COGS, $11K is fees, $28K is ad spend, $4K refunds, $5K OPEX → $10K net"** in one glance with prior-period deltas. SMB merchants do this in spreadsheets when they don't have a tool, and TrueProfit, BeProfit, Lifetimely, Conjura, Profit Calc, Bloom, and StoreHero all built their entry-tier wedge on replacing that spreadsheet. The merchant question being answered is rarely "what is my net profit" in isolation — it's "**which cost is eating my margin this period vs. last**", which requires composition + comparison, not just totals.

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `orders.line_items.cost`, `products.variants.cost` (Shopify "cost per item"), `orders.transactions.fees`, `orders.refunds`, `orders.discounts`, `orders.total_tax`, `orders.shipping_lines.price`, `orders.financial_status`, `orders.cancelled_at`
- **Source: WooCommerce** — `wc_orders.line_items.cost_of_goods_sold` (WC 9.0+ COGS field) or product meta `_cost_of_goods`, `wc_orders.fee_lines`, `wc_orders.shipping_lines`, `wc_orders.refunds`, `wc_orders.tax_lines`
- **Source: Shopify Payments / Stripe / PayPal / Mollie / Klarna / Shop Pay Installments** — actual `transaction.fee_amount` per gateway (must be pulled from the actual feed, not formula-estimated — see "anti-patterns")
- **Source: Meta Ads API** — `campaigns.spend`, `adsets.spend`, `ads.spend` aggregated to total ad spend per period (filter to single `level` per `CLAUDE.md` rule)
- **Source: Google Ads API** — `customer.spend`, `campaign.spend`
- **Source: TikTok Ads API** — `campaign.spend`
- **Source: Pinterest / Snapchat / Bing / Microsoft / Amazon Ads / X** — `campaign.spend`
- **Source: Klaviyo** — `email_campaign.cost` (rare; mostly the platform-side fee, not displayed as cost line by competitors)
- **Source: 3PL feeds (ShipStation, ShipBob, Shippo, ShipHero, ShippingEasy, Shipwire, Easyship, FedEx)** — `shipment.actual_cost`, `shipment.handling_charge` per order/shipment (replaces formula-based shipping estimation; called out as a "litmus test" in TrueProfit reviews)
- **Source: Dropship / POD suppliers (CJ Dropshipping, AliExpress via Chrome extension, Printful, Printify, Gelato)** — per-product cost auto-sync
- **Source: User-input (Cost Config)** — `custom_expenses` (recurring OPEX: agency fees, software subscriptions, salaries, rent), `one_time_expenses`, `cogs_per_product` (when missing from store data), `cogs_per_variant`, `cogs_zone_overrides` (per delivery destination — TrueProfit COGS Zones pattern), `shipping_rule_profile` (by weight/destination/items/quantity multiplier — BeProfit shipping profiles), `transaction_fee_override`, `tariff_cost` (Bloom 2025-2026 addition), `handling_cost`, `channel_fee`
- **Source: Computed** — `gross_profit = revenue - COGS`; `contribution_margin_1 = revenue - COGS - shipping - fees`; `contribution_margin_2 = CM1 - marketing_spend`; `contribution_margin_3 = CM2 - operating_expenses`; `net_profit = revenue - all_costs - taxes`; `cost_share_pct = cost_category / revenue` for each category (the building block of waterfall and stacked-bar viz)
- **Source: Computed (period delta)** — `Δcost = current_period_cost - prior_period_cost`; `Δcost_pct = Δcost / prior_period_cost`; required for the "what moved" comparison frame
- **Source: Computed (per-source attribution of marketing cost)** — total ad spend split across platforms; for Conjura, additionally `sku_level_ad_spend` parsed from ad URL; "Ad Spend - No Product" bucket when ad lands on homepage

## Data outputs (what's typically displayed)

- **KPI: Total cost** — `SUM(all_cost_categories)`, USD/EUR/GBP, vs prior-period delta as % and absolute
- **KPI: Cost-to-revenue ratio** — `total_cost / revenue` (computed, never stored — per CLAUDE.md), %
- **KPI: Net profit** — `revenue - total_cost - taxes`, USD, vs prior-period delta
- **KPI: Net margin %** — `net_profit / revenue` (computed)
- **KPI per category** — COGS, Shipping/Fulfillment, Transaction/Gateway fees, Marketing/Ad spend, Refunds/Returns, Custom OPEX, Taxes, Handling, Channel fees, Tariff
- **Dimension: Cost category** — string, ~6-10 distinct values (varies by competitor; Bloom exposes 6 order-cost columns, BeProfit groups under 4 settings folders)
- **Dimension: Time period** — daily / weekly / monthly / yearly buckets; vs prior-period or vs same-period-prior-year
- **Dimension: Source platform** (for marketing cost) — Meta, Google, TikTok, Pinterest, Snapchat, Bing, Amazon, Klaviyo
- **Dimension: Cost line subtype** (BeProfit/Bloom only) — within each category, drill into sub-rows (e.g., COGS → product variant cost vs. inventory cost; Fees → gateway cost vs. processing fee)
- **Breakdown: Cost × category × time** — table or chart (this is the canonical "cost overview" view)
- **Breakdown: Revenue → cost categories → net profit** — waterfall composition
- **Slice: Per-order cost breakdown** — Bloom, BeProfit, Conjura, Putler all expose a per-order cost drill-down with columns for Gateway / Shipping / COGS / Handling / Channel Fee / Tariff
- **Slice: Per-product allocated cost** — SKU-level, with COGS + allocated ad spend (Conjura's URL-parsed model) + allocated shipping
- **Slice: Per-store / per-platform** (multi-store) — costs aggregated across all stores or compared side-by-side

## How competitors implement this

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Sidebar > Profit Dashboard (default landing); cost categories also editable under Settings > Expense Tracking.
- **Visualization:** Categorical cost-breakdown chart (type unspecified in public sources — described as "stacked bar or donut, not confirmed") sitting **below** the headline net-profit number and KPI tiles. A separate "Orders vs. Ad Spend per Order" chart was added in a 2025 update per the changelog.
- **Layout (prose):** "Top of page shows live net-profit number prominently (the '$495,345' example used in their walkthrough blog) with gross revenue, profit margin, and AOV displayed alongside. Below the KPIs sits a dynamic line graph for performance over time. Below that, a cost breakdown chart (categorical breakdown across packaging, fulfillment, marketing fees, transaction fees, custom costs)."
- **Specific UI:** Large primary KPI number; supporting KPI tiles; line chart with metric-picker; categorical cost-breakdown chart. Specific colors, sparkline presence, and stoplight indicators are not confirmed. Multi-store users see store-switcher (top-bar) toggling between rollup and per-store cost views.
- **Filters:** Date-range, store switcher (rollup vs single store), metric picker on the line graph.
- **Data shown:** Net profit, gross revenue, profit margin, AOV, ROAS, orders, "average order profit", total costs, and the stack of: packaging, fulfillment, marketing fees, transaction fees, custom costs.
- **Interactions:** Metric picker, date-range, store switcher. Drill-down to per-product or per-order from the cost breakdown is not confirmed.
- **Why it works (from reviews):** "tells you exactly where you are loosing money and how to fix it" — Frome, Shopify App Store, February 2026; "just what I needed to track my costs in real time" — Obnoxious Golf, April 2026.
- **Source:** [trueprofit.md](../competitors/trueprofit.md), https://trueprofit.io/solutions/profit-dashboard, https://trueprofit.io/blog/what-is-trueprofit

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Top-level Profit Dashboard (default landing), plus dedicated "Settings > Costs" tree (Fulfillment / Processing Fees / Calculation Preferences / Marketing Platforms) plus "Settings > Custom Operational Expenses" plus "Settings > Products Costs". An "Expense tracking" section sits underneath the KPI row on the dashboard.
- **Visualization:** KPI row + expense-tracking section with category breakdown ("intuitive charts and graphs for trend spotting" — WooCommerce listing). Per Shopify App Store screenshot captions: "Track all expenses in a powerful e-commerce analytics platform" and "Get daily, weekly, and monthly reports on your store performance."
- **Layout (prose):** "Top filter strip with date-range picker (presets: Daily / Weekly / Monthly per screenshot caption), KPI row featuring Lifetime Profit, Retention, ROAS, and POAS as headline numbers, expense-tracking section underneath with category breakdown."
- **Specific UI:** POAS (Profit on Ad Spend) is elevated to the dashboard hero row alongside ROAS — unusual aggressive profit-positioning. The Expenses-Revenue marketing page describes a conditional/variable expense engine: "Add variable costs to your orders as a percentage of order revenue or a fixed amount for each order" with "Conditions: Order Status? Sales Channel? Items Amount? Any Other Condition?". Order-row drill-down has explicit "unprofitable" / "most profitable" segmentation per screenshot #5 caption ("Identify your unprofitable orders and most profitable orders").
- **Filters:** Date range (daily/weekly/monthly), real-time refresh, export.
- **Data shown:** Net profit, gross profit, contribution profit, ROAS, POAS, retention, lifetime profit, expenses by category. Per-order columns: revenue, COGS, shipping, fees, taxes, marketing cost (allocated), net profit.
- **Interactions:** Period switching, real-time refresh, sort by profit ascending (unprofitable) / descending. Multi-dimension grouping: product / type / vendor / collection / variant.
- **Why it works (from reviews):** "BeProfit is by far the most accurate and analytical one we've used." — Braneit Beauty, February 2026; "It's a really solid profit analytics platform that finally gives us a clear picture." — Ecolino.ro, December 2025.
- **Source:** [beprofit.md](../competitors/beprofit.md), https://beprofit.co/expenses-revenue/, https://apps.shopify.com/beprofit-profit-tracker

### Lifetimely (by AMP) ([profile](../competitors/lifetimely.md))
- **Surface:** Profit Dashboard / "Income Statement" — default sidebar landing. Cost configuration lives in Sidebar > Costs / Settings.
- **Visualization:** **Income-statement table** (line per cost category, descending from revenue → contribution → net) — explicitly not a 4-up KPI grid. 1800DTC describes it as "clean, easy to digest" — a deliberate spreadsheet-replacement aesthetic. **Distinct from this**, the LTV report contains a **cohort-waterfall chart** with cumulative LTV bars per month + a user-configurable horizontal **green CAC-payback marker bar**.
- **Layout (prose):** "The landing canvas leads with revenue, product costs, marketing costs, and net profit as the four anchor figures, structured as an income-statement-style stacked vertical layout rather than a 4-up KPI grid. Below the headline figures, costs are factored in line-by-line (Shopify COGS auto-pulled, transaction gateway fees, shipping from ShipStation/ShipBob, custom recurring costs)."
- **Specific UI:** Income-statement format. Top-of-page date-range picker. Color usage "professional color scheme emphasizing readability" — neutral palette with restrained green/red for deltas. Daily P&L delivered to email inbox at 7am and Slack channel Monday 8am — same view, exported. Cost & Expenses tab: per-product row with **pencil icon** to edit cost individually; CSV bulk-import accepts `SKU` + `product_cost` + optional `shipping_cost`; default COGS margin % fallback when no explicit cost. Priority hierarchy: Lifetimely manual cost > Shopify cost-per-item > default COGS margin. **Transaction fees and handling costs are explicitly excluded from this scope** per help docs.
- **Filters:** Date range (daily/weekly/monthly), comparison.
- **Data shown:** Total sales, COGS, marketing spend, gross margin, contribution margin, net profit, refunds, fees, custom expenses.
- **Interactions:** Email/Slack export, period toggle, drill from line item presumably (not confirmed).
- **Why it works (from reviews):** "simplified, impactful dashboards that help make decision making easier" — Raycon, March 2026; "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, January 2026.
- **Source:** [lifetimely.md](../competitors/lifetimely.md), https://useamp.com/products/analytics/profit-loss, https://help.useamp.com/article/643-the-profit-dashboard, https://1800dtc.com/breakdowns/lifetimely

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Performance Overview Dashboard (the "beating heart of the analytics suite") + Order Table Dashboard (per-order cost drill-down) + Product Table (per-SKU cost allocation).
- **Visualization:** "Performance snapshot" hero KPI cards labeled "Conjura Dashboard" with **product imagery integrated alongside metric cards** (recurring visual motif). For per-order costs: sortable/filterable order table with cost columns. For per-SKU cost: SKU-level table with ad-spend-per-product computed via **URL parsing** of the ad's destination link (works for Google Shopping, Performance Max, Facebook ads pointing to product page). Ad spend that lands on a generic homepage falls into a "**Ad Spend - No Product**" bucket — published methodology.
- **Layout (prose):** Six primary KPIs: Contribution Profit, ROAS, CAC, Order Volume, Revenue (gross + AOV), Channel Performance breakdown. "Filter, slice and dice by store, channel or territory."
- **Specific UI:** Pre-built order-table saved views: "New customer orders, unprofitable orders and most profitable" and example custom views "Orders where profit margin is >70%, orders with refunds, high shipping costs and more." Per-order columns include profit margin, ad budget allocated to the order's SKU, refund status, shipping cost.
- **Filters:** Store, channel, territory, date range, custom segments.
- **Data shown:** Contribution Profit (verbatim — every Conjura dashboard headline is "Contribution Profit" not "Revenue" or "ROAS"), ROAS, CAC, Order Volume, Revenue, Channel Performance. Per-order: profit margin, allocated ad spend, refund flag, promo code used, shipping cost.
- **Interactions:** Daily refresh; daily email digest "Daily performance round-up keeps you and your team in the loop"; saved-view filters.
- **Why it works (from reviews):** "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, March 2024; "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." — ChefSupplies.ca, January 2024.
- **Source:** [conjura.md](../competitors/conjura.md), https://www.conjura.com/performance-overview-dashboard, https://www.conjura.com/order-table-dashboard, https://help.conjura.com/en/articles/9127883-understanding-sku-level-ad-spend

### Profit Calc ([profile](../competitors/profit-calc.md))
- **Surface:** Default landing "Dashboard" — "real-time central dashboard to understand your store's financials." Single canvas combining net profit headline plus cost stack feeding into it.
- **Visualization:** Tile-based dashboard layout, white background, "P&L style cost-waterfall sense" per the App Store screenshot; explicit chart types not pictured publicly. App Store screenshot 1 caption: "One-click true profit dashboard: orders, COGS, fees, ads, taxes."
- **Layout (prose):** "Single canvas combining net profit headline plus the cost stack feeding into it. Concrete card counts, sparklines, or grid structure are not visible in public-page renderings."
- **Specific UI:** Marketing-page emphasis on simplicity over depth — tagline "Know your real Shopify profit." UI details not available beyond captions; the screenshot 5 caption "Custom COGS rules by country & quantity, date for exact margins" implies a rules-table cost-config UI.
- **Filters:** Date range implied ("over any period of time" copy). Multi-store: each connected Shopify store requires a separate subscription (no unified rollup at single-sub price).
- **Data shown:** Net profit, orders, COGS, fees, ad spend, taxes, ROAS, AOV, refunds, chargebacks, VAT, fees, P&L.
- **Interactions:** "One-click" framing — no required configuration to land on this page beyond connecting Shopify + ad accounts + COGS.
- **Why it works (from reviews):** "Saves so much time and gives me a true picture of my actual profit" — leighanndobbsbooks, May 2025; "Spectacularly easy way to see exactly what's happening in your store." — Cindy Nichols Store, May 2025; "bang for the buck, Profit Calc comes up on top, by a long shot" — Navy Humor, October 2025.
- **Source:** [profit-calc.md](../competitors/profit-calc.md), https://apps.shopify.com/profit-calc

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Summary Dashboard (default landing) — Triple Whale frames cost as one of the **collapsible sections by data integration** ("Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics (Triple Pixel), and Custom Expenses").
- **Visualization:** **Draggable metric tile grid** with a "table view" toggle that pivots the same tile grid into a dense single-table layout. Tiles show headline value + period-vs-period delta. **"Custom Expenses" is one of the named sections** — i.e., cost is treated as a peer of revenue/marketing rather than a dedicated waterfall.
- **Layout (prose):** "Top date-range and store-switcher controls (period-comparison toggle implied by 'vs prior period' delta language). Body is organized as collapsible sections by data integration. Each section is a grid of draggable metric tiles."
- **Specific UI:** Hovering a tile reveals a 📌 pin icon. "Edit Dashboard" button enters drag-edit mode and surfaces "Create Custom Metric". Sections can be hidden. New (April 2026) **on-demand refresh button** pulls the latest data with a real-time status cycling display ("Refreshing Meta…").
- **Filters:** Date range, store switcher, vs prior period, segment by Shopify and Ad data.
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV (60/90), Total Ad Spend, Sessions, Conversion Rate, Refund Rate, plus per-platform spend/ROAS sub-tiles.
- **Interactions:** Drag-and-drop reordering, pin to "Pinned", pivot to table view, click metric to drill into detail, Moby Chat sidebar on every dashboard.
- **Why it works (from reviews):** Triple Whale's wedge is creative + attribution, not the cost-overview surface itself. The Custom Expenses section is treated peer-to-other-sources — cost is not the headline frame for them.
- **Source:** [triple-whale.md](../competitors/triple-whale.md)

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Cost / attribution config lives under "Data Settings / Currency / Cost configs" workspace-level surface; cost is composed via the Custom Metrics no-code formula builder ("net profit = revenue - cogs - ad_spend - shipping").
- **Visualization:** No dedicated cost-overview viz visible from public sources; cost flows into the Custom Metrics builder which is heavily praised. Polar's interface "lacks the visual polish seen in some competitors like Triple Whale" per Conjura comparison article 2025.
- **Layout (prose):** Not observed in public sources for the cost surface specifically — only mentioned that workspace-level cost config feeds the semantic layer.
- **Specific UI:** Custom Metrics no-code formula builder (semantic layer). Cost retroactive recalc behavior implied but no public "Recomputing…" UI banner observed.
- **Filters:** Workspace, currency, tax handling, time period.
- **Data shown:** User-defined via formula builder; competitor reviews don't surface a canonical "cost overview" KPI set.
- **Interactions:** Build formula → render in Custom Metrics card on dashboard.
- **Why it works (from reviews):** "Custom Metrics no-code formula builder is heavily praised" — semantic layer flexibility is the strength.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md)

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Unified Dashboard (top-level), Spend Advisor module, Finance tab, Cost Settings (configuration).
- **Visualization:** Unified Dashboard described as "one clean command center" with KPI tiles for net sales, ad spend, contribution margin and channel-performance side-by-side. Specific chart types (bar/line/donut) and exact tile layout NOT observable from public screenshots. Finance tab: "expert view of your full P&L split by day, week, or month on a single screen" — implied row-by-row P&L with day/week/month column toggle.
- **Layout (prose):** "Net Sales, Marketing Spend, Ad Spend, Contribution Margin, MER, ROAS, breakeven ROAS, new customer sales, repeat customer sales, AOV." Spend Advisor adds a simulator: "Watch how every $100 you invest into ads changes profit in real time."
- **Specific UI:** Spend Advisor surfaces three discrete recommendation states ("pause, pivot, or scale") as labels or pills. Goals & Forecasting uses a **green & red traffic-light system** for performance drift (binary, not three-state — yellow/amber NOT mentioned in public copy).
- **Filters:** Date-range filter, store-switcher (agency multi-store view), channel-blended-vs-channel-by-channel toggle.
- **Data shown:** Net Sales, Marketing Spend, Ad Spend, Contribution Margin, MER, ROAS, breakeven ROAS, new customer sales, repeat customer sales, AOV.
- **Interactions:** Day/week/month granularity toggle, ad-spend slider input → live profit recalculation in Spend Advisor.
- **Why it works (from reviews):** Contribution-margin focus is structural — every dashboard headline is contribution margin not revenue.
- **Source:** [storehero.md](../competitors/storehero.md), https://storehero.ai/contribution-margin-formula/

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Overview Dashboard (default landing), Profit Map (sidebar > Profit Analytics, the headline visual), Profit Table / Profit Report (pivot table), Order Profits (per-order drill-down).
- **Visualization:** **Multiple distinct viz types layered together.** (1) Overview Dashboard uses **stacked-section card layout** with four primary blocks: Revenue-to-Profit summary cards, Margin Overview cards with percentages, Marketing Performance cards, Customer & Revenue cards. (2) Below cards: a **Revenue-to-Profit % chart** (revenue distribution across cost categories as percentages — i.e., a stacked-composition chart), a **Profit Margins Trend** (CM1, CM2, CM3 over time — multi-line trend), a **Marketing Performance Trend** (mixed line/bar combo: MER/aMER/MPR/aMPR as lines + CAC as bars), a **Customer Type Trend** (new vs repeat). Marketing copy elsewhere mentions **"Spline Area charts"** as the visualization style. (3) **Profit Map: an "interactive tree graph that lays out exactly how various metrics … impact your net profit"** — a spatial decomposition tree (specific node count, orientation, hover state not documented). (4) **Profit Table** is a pivot where COLUMNS are time periods (group-by day/week/month) plus a final "Total" column, and ROWS are metrics organized in 5 vertical tiers: Revenue (7 categories), Cost (product cost, COGS, fulfillment with 6 subcategories), Contribution Margins (CM1/CM2/CM3 + percentages), Marketing (6 indicators), Net Profit (5 final metrics). Many rows are **expandable to reveal sub-metrics**.
- **Layout (prose):** Stacked-section dashboard. Profit Map is positioned as a "visual, interactive tree graph that lays out exactly how various metrics … impact your net profit." Order Profits exposes per-order cost columns: Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost.
- **Specific UI:** KPI cards each show "the metric value for the selected period" plus "the percentage change compared to the previous period." Marketing site shows example cards rendering "$68.28K, 10.3% From Last Month" for Net Profit and "$420K, 5.2% compare to last week" for Ad Spend. Profit Table: expandable parent rows, **negative numbers rendered with minus signs**, all amounts in account-level "Reporting Currency". 4-layer shipping-cost engine: rules → 3PL integration → Shopify shipping auto-sync → manual edit, with rules dimensioned by country / product / fulfillment center / shipping method.
- **Filters:** Date range picker (daily/weekly/monthly/yearly), period comparison vs prior year or same historical period, cumulative analysis option, group-by toggle.
- **Data shown:** Net Revenue, COGS, Cost to Fulfill Orders, Marketing Costs, Operating Expenses, Net Profit, Total Revenue, CM1/CM2/CM3 (with %), MER, aMER, MPR, aMPR, CAC, New Customers, New Customers %, BEROAS, AOV. Per-order cost columns: Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost.
- **Interactions:** Date range, period comparison, group-by, row expansion (Profit Table), drill into Order Profits with "edit operational costs and reimport updated files for recalculation" workflow.
- **Why it works (from reviews):** "We now know exactly what we make from every sale. Thanks John and [team]." — kicksshop.nl, January 2026; "It gives us much more clarity on our numbers, helps us make better decisions." — CAPS, April 2026.
- **Source:** [bloom-analytics.md](../competitors/bloom-analytics.md), https://docs.bloomanalytics.io/overview-dashboard.md, https://docs.bloomanalytics.io/profit-table.md, https://docs.bloomanalytics.io/order-profits.md

### Putler ([profile](../competitors/putler.md))
- **Surface:** Sales Dashboard, Transactions screen (sidebar). Cost detail surfaces inside per-transaction breakdown.
- **Visualization:** Per-transaction breakdown of net/refund/shipping/tax/fee/discount/commission — table-row drill-down; not a dedicated cost-overview waterfall. Subscriptions Dashboard surfaces an MRR breakdown formula visibly ("Net MRR = current + New − Churned + Expansion") — **transparent formula displayed inline** as a UX pattern.
- **Layout (prose):** Vertical stack of metric cards on Subscriptions Dashboard. Transactions screen: "Find the transaction, click refund, confirm. Done." workflow.
- **Specific UI:** Marketing copy describes "the formula transparently in tooltips," suggesting visible formula explanations on hover or expand. Star icons inline next to top-revenue products. Export CSV with currency conversion + timezone normalization + dedup pre-applied.
- **Filters:** Date range, time-granularity (days/months/years).
- **Data shown:** Total amount, count, fees, taxes, per-transaction breakdown of net/refund/shipping/tax/fee/discount/commission.
- **Interactions:** Click row → transaction detail. Click "Refund" → modal for full or partial refund. Time-granularity toggle.
- **Why it works (from reviews):** Long-tenured users dominate the review pool — "since 2017", "for years" appear repeatedly; high stickiness from data-history asset.
- **Source:** [putler.md](../competitors/putler.md)

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Analytics > Reports > Profit (Advanced+ tier only, $299/mo and up). A "Gross profit breakdown card" sits inside the Finance summary report.
- **Visualization:** Card-based report. "**Gross profit breakdown card**" inside the Finance summary report shows net sales, costs, and profit; surfaces both **"Net sales without cost recorded" and "Net sales with cost recorded"** — explicit COGS-coverage visibility.
- **Layout (prose):** "Gross profit by product report displays gross profit by product for the selected date range, considering only variants that have product cost information at the time of sale."
- **Specific UI:** Inline COGS field on each product variant; reports clearly call out which line items lack a recorded COGS (so they aren't counted toward gross profit). Configuration-panel pattern: chart at top, metric+dimension chips on a side panel, sortable table below. Visualization-type switcher (line / bar / table).
- **Filters:** Date range, metric chips, dimension chips.
- **Data shown:** Gross profit by product, gross profit by variant, gross profit breakdown card with net sales, costs, profit; "Net sales without cost recorded" / "Net sales with cost recorded" split.
- **Interactions:** Drill-down from a row to a filtered exploration; save a configured report; pin to Overview as a custom card; export to CSV (1 MB / 50-record / email-on-large-export caps).
- **Why it works:** Native — no install friction. But profit reports are **paywalled to $299/mo Advanced** — most SMBs don't see this surface.
- **Source:** [shopify-native.md](../competitors/shopify-native.md), https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/profit-reports

### Hyros ([profile](../competitors/hyros.md))
- **Surface:** No dedicated cost-overview surface observed — Hyros is attribution-first, not cost-first. Cost surfaces only via the **"Reporting Gap" widget** (delta between Hyros-attributed sales and ad-platform-reported sales) and per-channel `clicks/cost in, conversions out` flow back to ad platforms via CAPI.
- **Visualization:** No cost-overview viz observed; per-source ad-cost flows into attribution comparison.
- **Layout (prose):** Not observed for cost specifically.
- **Specific UI:** "Reporting Gap" widget surfaces ad-cost vs. attributed-revenue mismatch as a delta — **single-pair version** of multi-source disagreement. AIR remarketing add-on charges 5%-of-attributed-revenue fee deducted on the same screen.
- **Filters:** Date range; Hyros-attributed vs platform-reported.
- **Data shown:** Ad spend per platform; deduped CAPI conversions sent back; Reporting Gap delta.
- **Interactions:** Tag a call → re-feeds Meta/Google CAPI with outcome.
- **Why it works:** Attribution-revenue lens, not cost lens — Hyros isn't a cost-overview tool by design.
- **Source:** [hyros.md](../competitors/hyros.md)

## Visualization patterns observed (cross-cut)

Synthesizing the per-competitor sections into a count by viz type:

- **KPI tiles + categorical cost-breakdown chart below (type unspecified — stacked bar or donut):** 4 competitors (TrueProfit, BeProfit, Profit Calc, Triple Whale via "Custom Expenses" section) — the dominant SMB pattern. None of the four exposes a true revenue→cost-categories→net-profit waterfall.
- **Income statement / line-by-line P&L table:** 3 competitors (Lifetimely, StoreHero Finance tab, Bloom Profit Table) — the "spreadsheet replacement" aesthetic. Lifetimely is most explicit; 1800DTC describes Lifetimely's layout as deliberately accountant-friendly.
- **Pivot table (rows = metrics, columns = time periods, expandable rows):** 1 competitor (Bloom Analytics Profit Table) — "Many rows are expandable to reveal sub-metrics." Most differentiated tabular cost view.
- **Tree / hierarchy view (revenue at top, cost branches, net at bottom):** 1 competitor (Bloom Analytics Profit Map — "interactive tree graph that lays out exactly how various metrics … impact your net profit"). Novel; specific UI undocumented in public sources.
- **Stacked-composition chart (Revenue-to-Profit % chart — distribution across cost categories as percentages):** 1 competitor (Bloom Analytics Overview Dashboard).
- **Multi-line trend (CM1, CM2, CM3 over time):** 1 competitor (Bloom Analytics Profit Margins Trend).
- **Mixed line/bar combo (efficiency ratios as lines + CAC as bars):** 1 competitor (Bloom Analytics Marketing Performance Trend).
- **Cohort-waterfall (cumulative LTV bars + horizontal CAC-payback marker):** 1 competitor (Lifetimely) — note this is a *cohort* waterfall, not a P&L waterfall (revenue→net cost-bridge). No competitor in this batch implements a true revenue→cost-categories→net-profit waterfall.
- **Per-order cost-column drill-down table:** 4 competitors (Bloom — Gateway/Shipping/COGS/Handling/Channel/Tariff; BeProfit — revenue/COGS/shipping/fees/taxes/marketing; Conjura — profit margin / allocated ad spend / refund / shipping; Putler — net/refund/shipping/tax/fee/discount/commission). Universal pattern at the row level.
- **Native report cards with explicit "no-cost-recorded" caveat:** 1 competitor (Shopify Native — "Net sales without cost recorded" / "Net sales with cost recorded"). The most honest about COGS coverage gaps.
- **Spend-Advisor simulator (slider input → live profit recalc):** 1 competitor (StoreHero) — "Watch how every $100 you invest into ads changes profit in real time."
- **Reporting Gap delta widget (cost vs. attributed-revenue mismatch):** 1 competitor (Hyros).

**Visual conventions that recur:**
- **Period-vs-period delta inline with KPI** (every competitor that surfaces costs as KPIs — Bloom example "$68.28K, 10.3% From Last Month"; Triple Whale, BeProfit, TrueProfit all carry this).
- **Daily/weekly/monthly time-granularity toggle** as standard top-of-page control (BeProfit, StoreHero, Bloom, Lifetimely, Shopify Native).
- **Color palette restraint** — Lifetimely is described as "professional color scheme emphasizing readability" with restrained green/red for deltas. No competitor uses cost-category-specific colors that we observed in public sources.
- **Negative numbers rendered with minus signs** (Bloom Profit Table — explicit), implying SQL-style accounting display rather than red-only.
- **Reporting currency selector** at workspace level (Bloom, Polar, multi-currency Profit Calc) — multi-currency cost is a baseline expectation.
- **Drill-from-card-to-detail** as the universal interaction (click KPI → category breakdown → per-order/per-product). Universal.
- **Saved views / pre-built filter chips for "unprofitable orders" / "most profitable orders"** (Conjura, BeProfit) — common segmentation primitive.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Clarity at a glance — "I finally see where the money goes"**
- "tells you exactly where you are loosing money and how to fix it" — Frome (Canada), TrueProfit, Shopify App Store, February 4, 2026 ([trueprofit.md](../competitors/trueprofit.md))
- "We now know exactly what we make from every sale. Thanks John and [team]." — kicksshop.nl (Netherlands), Bloom Analytics, Shopify App Store, January 19, 2026 ([bloom-analytics.md](../competitors/bloom-analytics.md))
- "Saves so much time and gives me a true picture of my actual profit" — leighanndobbsbooks, Profit Calc, Shopify App Store, May 11, 2025 ([profit-calc.md](../competitors/profit-calc.md))
- "Spectacularly easy way to see exactly what's happening in your store." — Cindy Nichols Store, Profit Calc, Shopify App Store, May 14, 2025 ([profit-calc.md](../competitors/profit-calc.md))
- "It's a really solid profit analytics platform that finally gives us a clear picture." — Ecolino.ro, BeProfit, Shopify App Store, December 1, 2025 ([beprofit.md](../competitors/beprofit.md))

**Theme: Replaces the spreadsheet**
- "not having a profit calculator is biggest mistake a shop can do" — Carholics (Finland), TrueProfit, Shopify App Store, March 11, 2026 ([trueprofit.md](../competitors/trueprofit.md))
- "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, Lifetimely, Shopify App Store, January 27, 2026 ([lifetimely.md](../competitors/lifetimely.md))
- "BeProfit made it easy for me, you don´t need to be a master in accounting." — Capterra reviewer, BeProfit, January 2022 ([beprofit.md](../competitors/beprofit.md))
- "No more digging through spreadsheets — just instant, actionable data." — Baron Barclay Bridge Supply, Bloom Analytics, Shopify App Store, March 11, 2025 ([bloom-analytics.md](../competitors/bloom-analytics.md))

**Theme: Drill-down without losing context**
- "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, Conjura, Shopify App Store, March 2024 ([conjura.md](../competitors/conjura.md))
- "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." — ChefSupplies.ca, Conjura, Shopify App Store, January 2024 ([conjura.md](../competitors/conjura.md))
- "It gives us much more clarity on our numbers, helps us make better decisions." — CAPS (Netherlands), Bloom Analytics, Shopify App Store, April 13, 2026 ([bloom-analytics.md](../competitors/bloom-analytics.md))

**Theme: Real-time / set-and-forget cost tracking**
- "just what I needed to track my costs in real time" — Obnoxious Golf (USA), TrueProfit, Shopify App Store, April 15, 2026 ([trueprofit.md](../competitors/trueprofit.md))
- "Set-up is easy, love that the data sync in real time, and support is extremely responsive" — Good Bacteria, Profit Calc, Shopify App Store, March 5, 2026 ([profit-calc.md](../competitors/profit-calc.md))
- "Bloom has been a great tool for real time analytics of our shopify [store]." — World Rugby Shop, Bloom Analytics, January 22, 2025 ([bloom-analytics.md](../competitors/bloom-analytics.md))

**Theme: Decision velocity**
- "tool we rely on every day to make decisions. Great customer support!" — Constantly Varied Gear, Lifetimely, March 23, 2026 ([lifetimely.md](../competitors/lifetimely.md))
- "simplified, impactful dashboards that help make decision making easier" — Raycon, Lifetimely, March 18, 2026 ([lifetimely.md](../competitors/lifetimely.md))
- "Simple to use, seriously rich insights, all action-orientated." — Rapanui Clothing, Conjura, October 2024 ([conjura.md](../competitors/conjura.md))

## What users hate about this feature

**Theme: Transaction fees calculated by formula instead of pulled from feed**
- "The transaction fees are calculated by a formula although this can be pulled directly from Shopify [...] Beprofit handle this correctly" — Shopify App Store 1-star reviewer, TrueProfit (cited via Reputon aggregation) ([trueprofit.md](../competitors/trueprofit.md))
- "transaction fees are calculated by a formula although this can be pulled directly from Shopify" (resulting in incorrect fee calculations; reviewer compared unfavorably to BeProfit on this point) — WASABI Knives, Profit Calc, Shopify App Store, May 2021 ([profit-calc.md](../competitors/profit-calc.md))

**Theme: Refund / return double-count inherits from upstream**
- "Attention all business owners! It's essential to double-check the accuracy of your refund versus returns data. Shopify's financial summary counts all return requests as issued refunds, which can be misleading. Not all return requests are accepted, and not all approved returns end up refunded. Stay vigilant to ensure more precise results. I've discussed this concern with the TrueProfit team, but they believe making changes now isn't practical. So, choose wisely. Accurate net profit reporting is crucial for all of us." — Apollo Moda (USA), TrueProfit, Shopify App Store, May 3, 2024 (2-star) ([trueprofit.md](../competitors/trueprofit.md))

**Theme: Manual COGS / cost entry burden**
- "Initial data entry [is] too much." — Sayed S., BeProfit, Capterra, January 2022 ([beprofit.md](../competitors/beprofit.md))
- "Learning curve in setting up advanced cost rules" — TrueProfit's own review blog (citing weaknesses) ([trueprofit.md](../competitors/trueprofit.md))
- "Some of our more unique data sources didn't have a pre-built Conjura data connector. Custom-built connectors took a little longer." — Andy B., Conjura, Capterra, January 2019 ([conjura.md](../competitors/conjura.md))

**Theme: Calculation Preferences fragility / cost engine bugs**
- "Not calculating the profit correctly. Calculation Preferences section not working properly." — Celluweg, BeProfit, Shopify App Store, January 17, 2026 ([beprofit.md](../competitors/beprofit.md))
- "Staff super slow to respond…issues not appearing, all my data is skewed and inaccurate." — PuppyPad (US), BeProfit, Shopify App Store, April 2, 2024 ([beprofit.md](../competitors/beprofit.md))
- "After scaling, [Lifetimely] started breaking in ways that actually cost them money. The interface gave the illusion of accuracy, but under the hood, it just wasn't reliable at the SKU level." — paraphrased Reddit/community sentiment surfaced in third-party reviews ([lifetimely.md](../competitors/lifetimely.md))

**Theme: Marketing-cost under-counting (UTM-only attribution)**
- "BeProfit only 'counts' ad spend that can be attributed via UTMs / converted traffic." — A Farley Country Attire, BeProfit, Shopify App Store, January 7, 2026 (this is the recurring "Google Ads under-counting" complaint; users report only ~15% of actual Google Ads spend imported because BeProfit "attributes based on UTM data only to avoid attributing SEO-based orders to the Google Ads platform" — help-center verbatim) ([beprofit.md](../competitors/beprofit.md))

**Theme: Shipping-cost edge-cases not captured**
- ShipStation/ShipBob "syncs shipping costs effectively" but flagged a limitation on "item pick charges" not being captured separately. — Interior Delights (USA), TrueProfit, March 2026 ([trueprofit.md](../competitors/trueprofit.md))

**Theme: Cost overview gated behind premium tier**
- "the additional add on cause the product to be a bit limiting…but overall a useful tool for high level view" — Sur Nutrition, Lifetimely, March 19, 2026 ([lifetimely.md](../competitors/lifetimely.md))
- "At $149/month for the first paid tier, Lifetimely is a real line item, with stores doing under $30K/month potentially struggling to justify it." — ATTN Agency review, 2026 ([lifetimely.md](../competitors/lifetimely.md))
- (Shopify Native: profit reports paywalled to **$299/mo Advanced tier** — "Includes [...] profit reports with COGS" only at Advanced+. Most SMBs never see this surface natively.) ([shopify-native.md](../competitors/shopify-native.md))

**Theme: Customization limits**
- "In future releases, it would be great to be able to customize the main dashboard more" — Insane Army (4-star portion), Profit Calc, Shopify App Store, January 20, 2026 ([profit-calc.md](../competitors/profit-calc.md))

## Anti-patterns observed

- **Formula-estimated transaction fees instead of pulled-from-Shopify-Payments-feed.** Both TrueProfit and Profit Calc inherited 1-star reviews specifically citing this — "BeProfit handles this correctly" is the canonical 1-star line. The class of edge-case that breaks: Shop Pay Installments, currency conversion on Stripe, partial refunds, gateway-specific surcharges. ([trueprofit.md](../competitors/trueprofit.md), [profit-calc.md](../competitors/profit-calc.md))
- **UTM-only marketing-cost attribution silently dropping un-mapped spend.** BeProfit's documented rule "for Google specifically: we attribute based on UTM data only to avoid attributing SEO-based orders to the Google Ads platform" causes user reports of ~15% of actual Google Ads spend imported, "massively inflating profit figures." Hides the disagreement that *is* the information. ([beprofit.md](../competitors/beprofit.md))
- **Aggregating without composition.** Generic line chart of "total cost" — doesn't show which cost categories moved. Users can't act. Pure flat tables (BeProfit Order reports without sparklines or color, the older Profit Calc dashboard) get neutral-to-negative reviews when displayed without trend indicators or color.
- **Refund double-count inheritance.** TrueProfit chose not to fix Shopify's "all return requests = refunds" treatment, citing "isn't practical." Documented limitation surfaces in 2-star review as accuracy concern. ([trueprofit.md](../competitors/trueprofit.md))
- **Cost overview behind paywall.** Shopify Native gates profit reports to $299/mo Advanced tier; Lifetimely's $149/mo entry "is a real line item" for sub-$30K/mo stores; Marketing Attribution paywalled at TrueProfit's $200/mo Enterprise tier means lower tiers never see channel-level cost-vs-revenue alignment. SMBs at $29-50/mo expect cost overview to be table-stakes, not premium. ([shopify-native.md](../competitors/shopify-native.md), [lifetimely.md](../competitors/lifetimely.md), [trueprofit.md](../competitors/trueprofit.md))
- **No cost-by-source-platform breakout in the cost overview itself.** Every competitor lumps marketing into a single "Marketing" or "Ad Spend" cost bucket on the cost overview canvas; per-platform ad-spend shows up separately on the Marketing/Channel report, not as a sub-row of the cost overview. Direct gap for source-disagreement thesis.
- **Cohort-waterfall labelled as P&L-waterfall.** Lifetimely's "cohort waterfall" with the green CAC-payback bar is great UX for *cohort* analysis but is sometimes referenced in marketing copy in a way that conflates it with a revenue→net P&L waterfall. They are different visualizations answering different questions. ([lifetimely.md](../competitors/lifetimely.md))
- **Spreadsheet-style settings tree as the cost-config surface.** BeProfit's `Settings > Costs > {Fulfillment, Processing Fees, Calculation Preferences, Marketing Platforms}` plus `Custom Operational Expenses` plus `Products Costs` is a 5-section settings tree. Multiple reviewers cite setup burden ("Initial data entry too much"). ([beprofit.md](../competitors/beprofit.md))
- **Shopify Cost-of-Item gap silently zeroed.** Variants without a recorded COGS get excluded from gross-profit calculation. Shopify Native handles this with explicit "Net sales without cost recorded" line — most third-party tools just drop the row, hiding coverage gaps. ([shopify-native.md](../competitors/shopify-native.md))

## Open questions / data gaps

- **No public revenue→cost-categories→net-profit waterfall observed in ANY competitor.** Profit Calc App Store screenshot has a "P&L style cost-waterfall sense" per visual inspection, but no competitor's marketing or help docs specifically document a true P&L waterfall (e.g., revenue bar, COGS down-step, fees down-step, ad-spend down-step, net-profit bar). The closest is Bloom's Profit Map tree. Worth a paid-trial probe to confirm whether a waterfall exists in any product's actual UI.
- **TrueProfit's "categorical cost-breakdown chart"** type is not pictured publicly. Could be stacked bar, donut, treemap, or stacked column — reviewers don't specify.
- **Bloom Profit Map tree-graph orientation** undocumented (left-to-right vs top-down vs radial). The docs sitemap does NOT have a `profit-map.md` page (404 confirmed) — the feature lives on marketing pages without screenshots.
- **Polar Analytics cost-overview surface** is essentially absent from public docs; cost is composed via Custom Metrics formula builder. A paid-trial would clarify whether Polar has a dedicated cost canvas or only the formula builder.
- **Triple Whale "Custom Expenses" section** layout details (column count, default metric ordering) not pictured publicly — only the existence of the section is documented.
- **Per-order cost columns vary in scope.** Bloom (Gateway/Shipping/COGS/Handling/Channel/Tariff — 6 cols), BeProfit (revenue/COGS/shipping/fees/taxes/marketing — 6 cols), Conjura (profit margin / allocated ad spend / refund / shipping — 4 cols), Putler (net/refund/shipping/tax/fee/discount/commission — 7 cols). The "right" column set depends on merchant geography and channel mix — universal not yet established.
- **No competitor exposes the "no COGS recorded" coverage explicitly the way Shopify Native does.** Worth probing whether third-party tools silently exclude or warn — Shopify Native is the only one we observed flagging the gap inline in the report.
- **Cohort-payback green-bar overlay** (Lifetimely) is well-documented in 1800DTC's review but UI specifics not directly observable from public sources without paid trial.

## Notes for Nexstage (observations only — NOT recommendations)

- **No competitor exposes a true revenue→cost-categories→net-profit waterfall as the headline visual.** Bloom's Profit Map tree-graph is the closest spatial decomposition; Lifetimely's cohort-waterfall is a different visualization (cumulative LTV, not P&L bridge). The dominant pattern is KPI tiles + "categorical cost-breakdown chart" (TrueProfit/BeProfit/Profit Calc/Triple Whale) with the chart type unspecified or generic. Direct gap for the "waterfall vs table comparison" hypothesis.
- **Per-order cost-column tables are universal at the row level (4/12 competitors).** Column scope differs but the pattern (Gateway/Shipping/COGS/Handling/Channel/Tariff or similar) is converged. Bloom's 2025-2026 addition of "Tariff Cost" is notable given current trade policy.
- **Source-platform breakdown of marketing cost is NEVER inside the cost-overview canvas itself.** Every competitor relegates per-platform ad spend to a separate Marketing/Channel report and lumps marketing into a single bucket on the cost overview. Direct gap for source-disagreement thesis — a Nexstage cost overview that breaks "Marketing" into Real / Facebook / Google / TikTok / etc. sub-rows would be structurally novel.
- **5 of 12 competitors gate cost overview or its sub-features behind premium tiers.** TrueProfit Marketing Attribution at $200/mo, Lifetimely entry at $149/mo, Shopify Native profit reports at $299/mo, BeProfit P&L at $99/mo Pro, Conjura ERP cost integrations at Scale tier. SMBs explicitly complain about the gating ("real line item," "potentially struggling to justify"). Nexstage's tier strategy could lean here.
- **Bloom's Profit Table 5-tier metric pivot (Revenue → Cost → Contribution Margins → Marketing → Net Profit, with expandable rows) is the most differentiated pure-table cost view observed.** Direct precedent if Nexstage builds an "expert mode" tabular cost overview with row expansion.
- **Lifetimely's income-statement-as-default pattern is structurally different from the KPI-card-grid framing.** "Top-down line items in P&L order" appeals to CFO/founder personas more than performance marketers. Worth observing as a layout primitive distinct from card grids.
- **Transaction-fee accuracy is a litmus test in this category.** Both TrueProfit and Profit Calc collected 1-star reviews specifically about formula-estimated fees. Pulling from Shopify Payments / Stripe / PayPal feeds directly is the user expectation.
- **Cost-config retroactive recalc is implied but only Polar's docs hint at the pattern.** No competitor publishes a "Recomputing…" UI banner the way Nexstage's `RecomputeAttributionJob` does.
- **Refund vs. return distinction (return-request vs. issued-refund) is a known industry problem.** TrueProfit explicitly chose not to solve it. Solving it correctly would be a quotable differentiator.
- **No competitor exposes a "Net sales without cost recorded" breakdown the way Shopify Native does.** This is the most honest UI primitive for COGS coverage gaps observed in the entire batch.
- **GSC / GA4 do not appear in any competitor's cost overview.** That makes sense (they're traffic sources, not cost sources), but the absence is notable when paired with marketing-cost attribution — the merchant complaint that "BeProfit only counts UTM-mapped Google Ads spend" is exactly the problem a GSC-aware cost overview could illuminate (organic vs. paid Google split).
- **Multi-currency reporting at workspace level is a baseline expectation** — Bloom, Polar, Profit Calc all have it. Multi-store cost rollup at single-subscription price (TrueProfit, Bloom) is a competitive differentiator vs Profit Calc's per-store-billing model.
- **StoreHero's Spend Advisor (slider input → live profit recalc with pause/pivot/scale recommendation pills) is a unique cost-overview-adjacent simulation pattern.** Cost is the input; recommendation is the output. No other competitor exposes anything similar.
- **Conjura's URL-parsed SKU-level ad-spend attribution** (works for Google Shopping, Performance Max, Facebook ads pointing to product page; "Ad Spend - No Product" bucket for homepage-targeted ads) is a pragmatic cost-allocation methodology that requires no pixel. Worth investigating whether Nexstage can do the same when product feed + ad URL are present.
- **Reporting Gap (Hyros) is a single-pair version of cross-source disagreement** — they surface ad-platform-reported sales vs. Hyros-attributed sales as a delta widget. Validates the "show the disagreement" thesis at multi-source scale.
