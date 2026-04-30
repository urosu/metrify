---
name: Inventory signals
slug: inventory-signals
purpose: Tell the merchant which SKUs are about to stock out and which are sitting on shelves too long, before either becomes a revenue or cash problem.
nexstage_pages: products, dashboard, alerts
researched_on: 2026-04-28
competitors_covered: glew, putler, metorik, daasity, conjura, triple-whale, shopify-native, beprofit, lebesgue, polar-analytics, bloom-analytics, woocommerce-native
sources:
  - ../competitors/glew.md
  - ../competitors/putler.md
  - ../competitors/metorik.md
  - ../competitors/daasity.md
  - ../competitors/conjura.md
  - ../competitors/triple-whale.md
  - ../competitors/shopify-native.md
  - ../competitors/beprofit.md
  - ../competitors/lebesgue.md
  - ../competitors/polar-analytics.md
  - ../competitors/bloom-analytics.md
  - ../competitors/woocommerce-native.md
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types
  - https://www.glew.io/features
  - https://www.conjura.com/product-table-dashboard
---

## What is this feature

Inventory signals answers two operational questions a SMB merchant has every Monday morning: "what's about to run out (and where do I lose revenue if I don't reorder right now)?" and "what's been sitting too long (and how much working capital is tied up in stock I should discount or kill)?". Every store platform exposes a current quantity-on-hand number — that is "having data." This feature is the synthesis: pairing live stock with sales velocity to compute days-of-cover, flagging stockout risk on best-sellers, and surfacing slow-movers/overstock against a sell-through baseline so a merchant knows whether to reorder, promote, or markdown.

For SMB Shopify/Woo owners specifically, this matters because they typically don't run a separate IMS (Inventory Management System) like Cin7 / Linnworks / NetSuite, so the analytics tool is the only place sales velocity and stock level meet. Running out of a hero SKU silently — while paid social keeps spending — is one of the costliest mistakes the persona makes; conversely, a Black Friday over-order hangs around for months as a cash drag. The category as a whole under-invests here: most "Shopify-first profit" tools (Triple Whale, BeProfit, StoreHero, Lifetimely, Conjura) treat inventory as a column on the product table, not a first-class signal — and most retain none of the forward-looking logic (reorder point, days-of-cover, demand forecast) that Shopify Native, Glew, and the IMS-bundled tier (Daasity / Polar) ship.

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `products.variants[].inventory_item_id`, `inventory_levels.available`, `products.vendor`, `orders.line_items.quantity`, `orders.created_at`, `orders.refunds.line_items[].quantity` (returns reduce true sell-through).
- **Source: WooCommerce** — `wc_products.stock_quantity`, `wc_products.manage_stock`, `wc_products.stock_status` (in stock / low / out of stock), `wc_order_product_lookup.product_qty`, `wc_orders.date_created_gmt`.
- **Source: ERP / IMS (optional)** — Linnworks, Brightpearl, Cin7, NetSuite, ShipBob, ShipStation: multi-warehouse stock, in-transit / on-PO quantities, fulfilment costs (per Conjura, Daasity, Glew profiles).
- **Source: Computed** — `daily_units_sold = SUM(line_item.quantity) WHERE date BETWEEN now-N AND now` (rolling 7d/14d/28d window per merchant convention).
- **Source: Computed** — `sales_velocity = daily_units_sold / N` (units per day).
- **Source: Computed** — `days_of_cover = stock_on_hand / sales_velocity` (NULL when velocity = 0; "∞" when stock > 0 and velocity = 0 → flagged as overstock candidate).
- **Source: Computed** — `sell_through_rate = units_sold_in_period / (units_sold_in_period + ending_stock)` (per Glew's documented metric).
- **Source: Computed** — `stockout_eta = today + days_of_cover` (date the SKU goes to 0 at current pace).
- **Source: Computed** — `inventory_aging = today - last_sale_date` for each SKU (Glew labels this "inventory aging").
- **Source: Computed** — `inventory_value = stock_on_hand × cost_per_unit` (requires COGS — see `cost-config.md`).
- **Source: Computed** — `lost_revenue_estimate = days_out_of_stock × sales_velocity × avg_unit_price` (no competitor publishes this formula, but it is the obvious extension).
- **Source: User-input** — `reorder_lead_time_days` per SKU/vendor (when not in an IMS).
- **Source: User-input** — `safety_stock_days` (a buffer beyond lead time).
- **Source: Ad platforms (optional join)** — `ads.spend WHERE landing_page = product_url` from Meta/Google so the surface can answer "we're paying ads on a SKU about to run out" (Lebesgue's "stock-vs-ad-perf coupling" pattern).

## Data outputs (what's typically displayed)

- **KPI: Days of cover** — `stock_on_hand / sales_velocity`, days, color-coded by threshold (red <7d, amber 7–30d, green >30d). Per Shopify Native's "Days of inventory remaining" Advanced+ report.
- **KPI: Stock-on-hand units** — `SUM(inventory_levels.available)`, integer, per variant or rolled to product.
- **KPI: Inventory value at cost** — `SUM(stock × cost)`, USD, total working capital tied up.
- **KPI: Sell-through rate** — `units_sold / (units_sold + ending_stock)`, %, per period.
- **KPI: Sales velocity** — units/day over rolling 7/14/28d window.
- **Dimension: SKU / variant** — string, primary row.
- **Dimension: Vendor / supplier** — string, used to batch reorder POs.
- **Dimension: Stock status** — categorical (in stock / low / out of stock / archived), per Woo + Shopify native.
- **Dimension: Inventory aging bucket** — 0–30d / 31–60d / 61–90d / 91–180d / 180d+ since last sale (Glew "inventory aging").
- **Dimension: ABC class** — A / B / C tier by revenue contribution (Shopify Native Advanced+ "ABC analysis").
- **Breakdown: SKU × days-of-cover × stock value** — sortable table; the canonical layout.
- **Breakdown: SKU × ad-spend × stock-on-hand** — Lebesgue's "are we burning ad spend on a SKU about to run out?" cross-cut.
- **Slice: Per-SKU detail page** — sales history timeline, predicted monthly sales, average time between sales (Putler's Individual Product card pattern).
- **Alert: Low-stock anomaly** — fired when days-of-cover crosses threshold or sales spike alters ETA (Triple Whale Lighthouse "inventory anomaly").
- **Alert: Sidekick automation** — Shopify Native Sidekick example: "When inventory drops below 10 units, send a Slack alert and tag the product."

## How competitors implement this

### Glew ([profile](../competitors/glew.md))
- **Surface:** Sidebar > Inventory Analytics (a top-level module in their IA, alongside Customer / Product / Marketing / Subscription Analytics).
- **Visualization:** No visualization observed in public sources (live UI is paywalled behind sales-led demo). Marketing copy lists tabular + KPI presentation but specific viz type is **not directly observable**.
- **Layout (prose):** Inventory Analytics module is one of ~20 named screens in the Glew app. Refresh cadence is "customer + inventory **nightly**" (slower than the hourly default for orders). Marketing copy describes what the module computes, not how it renders. Per the Pre-built dashboards list: "Inventory management" is one of seven dashboard categories.
- **Specific UI:** UI details for the inventory module are not directly observable from public sources. Glew's marketing screenshot library exposes "KPI Highlights", "Performance Channels", "Net Profit by Channel" — no Inventory Analytics screenshot is published.
- **Filters:** Per Pro-tier copy, "300+ unique filtering options" platform-wide — assumed to apply here (vendor, category, status) but not confirmed for this surface.
- **Data shown:** "Stock levels, sell-through, sales velocity, demand prediction, inventory aging" — verbatim from the Glew Pro feature description. **Demand prediction** is the strongest forward-looking claim of any competitor in this profile. Plus on-tier integrations to **Cin7, Fishbowl, Linnworks, DEAR Systems, Channel Advisor** push true multi-warehouse + WMS stock back into the same module.
- **Interactions:** Custom segments + custom reports via bundled Looker (Glew Plus). Per-product detail page exists (Customer Profile pattern extends to Product Profile per FAQ search index).
- **Why it works:** No specific reviews praise the inventory module — reviewer enthusiasm clusters on the Daily Snapshot and Customer Segments. **Inventory Analytics is a listed module without surfacing as a love-or-hate theme**, suggesting it's adequate but not standout.
- **Source:** ../competitors/glew.md (lines 104, 75 for WMS integrations).

### Putler ([profile](../competitors/putler.md))
- **Surface:** Sidebar > Products > Individual Product card (no dedicated "Inventory" dashboard — inventory signals live inside the per-product profile).
- **Visualization:** Per-product detail card with line-chart sales history + predicted-sales numeric + co-purchase pairings list. **No grid view, no days-of-cover KPI, no portfolio-level inventory dashboard.**
- **Layout (prose):** Sidebar > Products opens a sortable list/table of every product, with top revenue generators "marked with stars". Click a product row to open the Individual Product card. The card includes: customer purchase list, revenue contribution, refund rate, average refund timing, **predicted monthly sales**, average time between sales, sales history timeline, product variation breakdown (size/color), and frequently-bought-together pairings.
- **Specific UI:** Star icons inline next to top-revenue products in the leaderboard. The 80/20 Breakdown Chart (a separate widget) shows revenue concentration over time — not inventory-specific but used to reason about which SKUs deserve restock attention.
- **Filters:** Customer count, Quantity sold, Refund percentage, Average price tier, Attributes (size/color/category). No "stock-level" filter observed.
- **Data shown:** Predicted monthly sales (forward), average time between sales, revenue contribution, refund rate. **Stock-on-hand is not exposed in public marketing of the Products surface** — Putler's positioning is financial/operational reconciliation, not inventory.
- **Interactions:** Click product → open card. Export customer list as CSV from within product card.
- **Why it works:** Reviews celebrate the Products dashboard for "easy statistics for products and total orders" (yair P., Capterra, May 2019) but no inventory-signal complaint or praise emerged. The surface is descriptive, not prescriptive.
- **Source:** ../competitors/putler.md (Products Dashboard / Leaderboard section).

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Reports > Products + Product Profile drill-down.
- **Visualization:** Sortable table at index, sales-trend line chart on the per-product profile. "Inventory forecasts" is named as a Products Report capability but no concrete viz type is published.
- **Layout (prose):** The Products Report shows "sales by product, profit margins, **inventory forecasts**" — the only mention of forward-looking inventory in the profile. The Product Profile page shows first sale date, last sale date, refund count, **average daily sales**, monthly sales trend.
- **Specific UI:** No specific UI observed for inventory-signal alerts. Average daily sales is the building block for days-of-cover, but the days-of-cover metric itself is not named.
- **Filters:** 500+ filters via the global Segment Builder; unclear how many apply to inventory dimensions specifically.
- **Data shown:** Average daily sales, sales trend, "inventory forecasts" (forecast type unspecified — could be sales forecast, not stockout forecast).
- **Interactions:** Saved segments cascade into Products Report; export CSV; schedule recurring exports to Slack/email.
- **Why it works:** No verbatim user quotes praise the inventory side specifically; one G2 snippet notes "perfect insights on what is happening on our store(s)" but doesn't single out inventory.
- **Source:** ../competitors/metorik.md (Products Report bullet, Product Profile section).

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Templates Library > **Ecommerce Performance > Inventory** (a dedicated dashboard) plus IMS-fed roll-ups via Extensiv / NetSuite / ShipBob / ShipStation / Order Desk / Fulfil / BackinStock connectors.
- **Visualization:** Embedded Looker tiles (specific viz type not surfaced publicly in help docs). Daasity exposes raw Looker explores, so users likely render their own (table / bar / scatter) on top of an "Inventory" semantic model.
- **Layout (prose):** "Inventory" appears as a named dashboard in the Ecommerce Performance category alongside Orders and Revenue, Fulfilment Status Options, Operations, Product, Product Repurchase Rate. It's a separate surface from "Product" — implying inventory signals are intentionally siloed from product-performance analytics.
- **Specific UI:** UI details not available. Help-doc breadcrumbs confirm the dashboard exists; no screenshots, no enumerated KPI list, no interaction flow published. Filter changes require explicit "Refresh Button" click (verbatim from the Flash dashboards convention applied across the Report Library).
- **Filters:** Standard Daasity filter strip — Store Type, Store Integration Name (defaults to "ALL Store Integrations combined").
- **Data shown:** "Order & inventory" connectors (BackinStock, Extensiv, Fulfil, NetSuite, Order Desk, ShipBob, ShipStation) push fulfilment + inventory-health data; Amazon Vendor Central pulls "inventory health" verbatim. Specific metrics on the Inventory dashboard are **not enumerated** in public docs.
- **Interactions:** Looker drill-down into Explores; reverse-ETL via Audiences not used for inventory signals (Audiences pushes RFM segments to Klaviyo/Meta — not stock alerts).
- **Why it works:** No reviewer mentions the Inventory dashboard specifically — Daasity reviews praise the warehouse + custom-report depth ("Lots of great integrations & dashboards" — tentree CA, Shopify App Store, June 2022). The Inventory surface is one of ~40 prebuilt dashboards in the library.
- **Source:** ../competitors/daasity.md (Report Library lines 99, 55 for ingest connectors).

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Product Intelligence > **Product Table** (inventory is a column set, not a separate dashboard). ERP/OMS integrations (Linnworks, Brightpearl, Cin7, ShipStation, NetSuite) are **gated behind the Scale tier** ($129.99/mo on website pricing).
- **Visualization:** Sortable / filterable SKU table with "pre-built segments listed: unprofitable products, slow movers, items selling out." Custom saved views.
- **Layout (prose):** "Decision-making engine for product, marketing, and merchandising teams." Each row = SKU. Per-row metrics include sales, conversion rate, product views, discount %, returns/refund rate, contribution profit, ad spend by product, **stock levels, sell-through rate**.
- **Specific UI:** Pre-built filter chips for "items selling out" (the closest Conjura comes to a low-stock alert). Click row → Product Deepdive ("visual command centre for tracking individual product performance"). Save-view affordance.
- **Filters:** "Filter by profitability, inventory, performance, or any custom metric." Saved views.
- **Data shown:** Stock levels, sell-through rate, contribution profit, ad spend per SKU (attributed via ad URL). Multi-warehouse stock + restock forecasts arrive via the Scale-tier ERP/OMS connectors.
- **Interactions:** Click row → SKU detail (Product Deepdive). Save filter views. Export to CSV implied. Owly AI ($199+/mo add-on) takes natural-language queries like "Which products are driving traffic but not sales?" — the closest a Conjura user gets to a conversational inventory query.
- **Why it works:** Reviewers cite "I can see my contribution margin down to an SKU level" (Bell Hutley, Shopify App Store, March 2024) and "The product deep dive down to SKU level is phenomenal" (Amelia P., G2, December 2023) — both general-purpose product praise that implicitly covers inventory but doesn't single out the stockout signal.
- **Source:** ../competitors/conjura.md (Product Table section, Scale tier integrations).

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > **Lighthouse** (anomaly inbox). Inventory is one of three anomaly classes alongside ad-spend and order anomalies.
- **Visualization:** Alert card list / anomaly inbox — each entry is a row with severity, metric, suggested action. **No dedicated inventory dashboard observed.**
- **Layout (prose):** Lighthouse is an anomaly inbox where "entries fire on suspicious variances in ad spend, **inventory**, and order data" (verbatim "Orders Anomalie", "Spend Anomalie" terminology in their own copy). Triple Whale appears to have folded Lighthouse messaging into Moby AI + Anomaly Detection Agent in 2025–2026, but the inventory anomaly class persists.
- **Specific UI:** Alert cards with severity / metric / suggested action. UI details "not directly verified beyond marketing copy" per the profile.
- **Filters:** Acknowledge / dismiss; presumably filter by class.
- **Data shown:** Anomaly class, metric value at anomaly, threshold. No days-of-cover KPI, no SKU table, no aging bucket.
- **Interactions:** Acknowledge alert, drill into anomaly cause. Push notifications on mobile.
- **Why it works:** Reviewers don't single out the inventory anomaly specifically — Lighthouse praise (when it appears) is about spend anomalies. Inventory is a co-traveller, not the headline.
- **Source:** ../competitors/triple-whale.md (Lighthouse section, lines 98, 215).

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Analytics > Reports > **Inventory category** (one of 10 categories: Acquisition, Behaviour, Customers, Finance, Inventory, Marketing, Profit, Retail, Sales, Custom). Three named reports: **Inventory snapshot**, **ABC analysis**, **Days of inventory remaining**. **Gated behind Advanced+ plan ($299/mo).**
- **Visualization:** Per Reports chassis: chart-at-top + sortable table below; visualization-type switcher (line / bar / table). The Days of inventory remaining report is most likely a sortable table with a per-SKU days numeric.
- **Layout (prose):** Left-hand category list ("Acquisition, Behaviour, Customers, Finance, **Inventory**, Marketing, Profit, Retail, Sales, Custom") with the report list in the main canvas. Each report opens into a configuration panel pattern: chart at the top, metric+dimension chips on a side panel, sortable table below. Top-of-page button: "Create custom report."
- **Specific UI:** **Status pills for stock state (in stock / low / out of stock)** — this is the canonical Shopify pattern, also surfaced in the Products report's per-row "Status" column. Sidekick (free across all tiers) accepts conversational queries; published example: "**When inventory drops below 10 units, send a Slack alert and tag the product**" (builds a Shopify Flow automation).
- **Filters:** Date range, vendor, category, status pill.
- **Data shown:** Stock-on-hand snapshot, **days of inventory remaining** (the days-of-cover metric, named as such), **ABC analysis** (revenue concentration class).
- **Interactions:** Drill from a row to filtered exploration; save a configured report as a new custom report; pin to Overview as a custom card. Sidekick → Shopify Flow handoff for low-stock alerts.
- **Why it works:** Days-of-inventory + ABC analysis is the most specific named-metric set across all competitors profiled here; Sidekick's natural-language inventory automation is the only published agent example that emits an actual Flow rather than a textual answer.
- **Source:** ../competitors/shopify-native.md (Reports library, line 89, 140, 162).

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** **Product reports** — "revenue, profit, COGS, sales by product/vendor/type/inventory." A column-cut on the Products surface, not a dedicated dashboard.
- **Visualization:** Per-product table; specific viz not surfaced publicly.
- **Layout (prose):** Product reports listed as one of the standard report types ("revenue, profit, COGS, sales by product/vendor/type/**inventory**"). Inventory is named as a slicing dimension, not as a forward-looking signal.
- **Specific UI:** UI details not available — only feature description seen on the marketing page.
- **Filters:** Standard date range; product / vendor / type / inventory grouping.
- **Data shown:** Revenue, profit, COGS, units sold per SKU. Stock-on-hand is implied by the "inventory" grouping but not explicitly enumerated as a column.
- **Interactions:** Sortable; export.
- **Why it works:** No reviewer cites BeProfit's inventory surface — review themes cluster on profit/COGS clarity. **This is a pure column-set treatment, not a feature.**
- **Source:** ../competitors/beprofit.md (Product reports bullet, line 76).

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** **Stock Inventory Management** — a named dashboard, with the distinctive twist that it joins inventory to ad performance ("**inventory-tied-to-ad-perf views**"). Available on Advanced ($59/mo) tier and up.
- **Visualization:** UI details not surfaced publicly. The pitch is the join — stock vs. ad-spend per SKU — not the chart.
- **Layout (prose):** Dashboard cross-references stock levels with the ads driving traffic to those products, so a merchant can see "we are still spending Meta on a product about to run out" (or conversely "we have warehoused inventory but no ads pushing it"). The "stock-vs-ad-perf coupling" term appears verbatim in the Lebesgue computed-metrics list.
- **Specific UI:** No screenshot/UI detail available on the Lebesgue marketing pages for this dashboard. The Henri AI agent (chat) is positioned as a layer over all dashboards including this one.
- **Filters:** Standard Lebesgue date range + per-SKU; assumed inheritance from product surface.
- **Data shown:** Stock-on-hand × ad-spend × ad-attributed conversions per SKU. Forecasts (60-day, via Prophet/ARIMA/exponential smoothing) cover revenue but **stock forecast is not explicitly named**.
- **Interactions:** Chat with Henri for natural-language queries; subscription required at Advanced+ ($59/mo).
- **Why it works:** The ad-spend-vs-stock join is the only competitor pattern in this profile that explicitly addresses "we're paying for traffic to a SKU we can't ship." No reviewer surfaces this dashboard specifically by name in the public review pool.
- **Source:** ../competitors/lebesgue.md (Stock Inventory Management bullet, line 72; stock-vs-ad-perf coupling line 85).

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** **Product / Merchandising page (pre-built)** with "**stock depletion**" as a named subject; also the **Inventory Planner AI agent** (in the AI Agents hub).
- **Visualization:** UI for the Merchandising page not directly observed; named subjects include "Product performance, variants, inventory, bundling, stock depletion." The Inventory Planner agent is part of a four-agent hub (Email Marketer, Media Buyer, Inventory Planner, Data Analyst) — agent UX is conversational, not dashboard-driven.
- **Layout (prose):** The pre-built Merchandising page is one of ~15 dashboards in Polar's library. Stock depletion is named as a subject, suggesting a forward-looking days-to-zero metric. Polar's pricing tier where this lives is opaque — likely AI-Analytics ($810/mo) or higher.
- **Specific UI:** UI details not available — only positioning language.
- **Filters:** Polar's standard Views (saved filter/data-source mappings: multi-store, region, channel) presumed to apply.
- **Data shown:** Product performance, variants, inventory, bundling, stock depletion. AI Agents hub claims agentic workflows on top.
- **Interactions:** Ask Polar (AI Analyst) emits a Custom Report you can edit — natural-language path to inventory questions. MCP server exposes the data layer to Claude/ChatGPT.
- **Why it works:** Polar's review pool praises data ownership / Snowflake access more than specific dashboards. Inventory Planner is part of the agentic-workflows narrative (April 2026) but no shipped review verbatim about its inventory accuracy.
- **Source:** ../competitors/polar-analytics.md (lines 108, 121).

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Sidebar > **Product Profits** (per-product/variant table) and **Product Analysis** (separate product-attribute table) — both expose inventory as columns.
- **Visualization:** Sortable column-rich table; export to Excel/CSV/PDF; row-size and rows-per-page controls.
- **Layout (prose):** Product Profits exposes Units Sold, Net Sales, Product COGS, **Inventory Cost, Inventory Quantity, Inventory Value** alongside profitability. Product Analysis is a sibling table focused on attributes (Product Type, Tags, **Stock Quantity**, Net Sales, COGS).
- **Specific UI:** Column visibility toggle, column filter, manual campaign-link entry for Product ROAS attribution. Marketing/ads spend can be joined per SKU.
- **Filters:** Product Name, Variant Name, Product Type, SKU, Tags, Status, ACTIVE flag.
- **Data shown:** Inventory Cost, Inventory Quantity, Inventory Value (forward-looking inventory **value at cost** is unique to Bloom in this set), plus the standard sales/profit columns.
- **Interactions:** Date range, column filter, column visibility toggle, export.
- **Why it works:** No public reviewer quote singles out Bloom's inventory columns; Bloom's positioning emphasizes per-order/per-product profit clarity.
- **Source:** ../competitors/bloom-analytics.md (Product Profits / Product Analysis sections, lines 81, 177, 184).

### WooCommerce Native ([profile](../competitors/woocommerce-native.md))
- **Surface:** Analytics > **Stock report** (a top-level Analytics report) plus inline status pills on the Products report.
- **Visualization:** **Single sortable table. No chart, no summary cards** — purely a tabular snapshot of current inventory.
- **Layout (prose):** Per `Analytics > Stock` doc: "Single sortable table. No chart, no summary cards — purely a tabular snapshot of current inventory." Status pills indicate stock state (in stock / low / out of stock). The Products report adds a Status column with the same pill set inline alongside Items Sold / Net Sales / Orders.
- **Specific UI:** Status pills with three states. No date range — it's a point-in-time snapshot. **No CSV export — confirmed limitation** (per Putler's gap analysis: "Stock report cannot be exported at all. If you need inventory data in a spreadsheet, you have to use the legacy Products screen, which has its own limitations").
- **Filters:** Sort by column. No filter strip.
- **Data shown:** Product, SKU, Status, Stock. **No velocity, no days-of-cover, no aging, no value at cost.**
- **Interactions:** Sort columns. Click product → product detail in WP admin (not in Analytics).
- **Why it works:** Universally panned as inadequate. The non-exportable Stock report is a notable structural complaint that Putler and Metorik both pick up as a wedge.
- **Source:** ../competitors/woocommerce-native.md (Stock report section, lines 176–182).

## Visualization patterns observed (cross-cut)

- **Sortable column-rich table:** 7 competitors (Conjura, Bloom Analytics, BeProfit, WooCommerce Native, Shopify Native, Putler products list, Metorik) — the dominant pattern. Inventory shows up as columns on the Product Table, not as its own visual.
- **Status pill (in stock / low / out of stock):** 2 competitors (Shopify Native, WooCommerce Native) — the only categorical-color cue observed.
- **Anomaly inbox / alert list:** 1 competitor (Triple Whale Lighthouse) — inventory anomalies treated like spend or order anomalies, surfaced in a notifications stream.
- **Per-SKU detail card with sales-history line + predicted sales:** 2 competitors (Putler Individual Product card, Metorik Product Profile) — drill-down reveals forward-looking signal but at one-SKU-at-a-time granularity.
- **Cross-cut "stock × ad-spend" join:** 1 competitor (Lebesgue Stock Inventory Management) — the only pattern that surfaces the operational risk of paying for traffic to depleting SKUs.
- **Named days-of-cover KPI:** 1 competitor (Shopify Native "Days of inventory remaining" report, Advanced+) — the only competitor that explicitly names the days-of-cover metric in a published report title.
- **ABC class analysis:** 1 competitor (Shopify Native, Advanced+) — the only inventory-aging-by-revenue-rank cut found.
- **AI agent for inventory:** 2 competitors (Polar Analytics "Inventory Planner" agent, Shopify Sidekick conversational rule that emits a Shopify Flow automation) — both 2025–26 announcements; UI details thin.
- **Demand prediction / inventory forecast:** 2 competitors (Glew "demand prediction" in Inventory Analytics, Metorik "inventory forecasts") — neither publishes the forecast methodology or UI.
- **No dedicated dashboard at all:** 4 competitors (Putler, BeProfit, Bloom, Conjura) — inventory is a column on the product table.

Color conventions observed: red/amber/green tied to stock-status pills (Shopify, Woo). No competitor publishes a heatmap, scatter, or progress visualization specifically for inventory — this surface is overwhelmingly tabular.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

This is one of the thinnest review surfaces in the entire feature index — almost no competitor's review pool surfaces inventory as a love theme. The closest praise lives in adjacent product-detail surfaces.

**Theme: SKU-level visibility implicitly covers stock**
- "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, Shopify App Store, March 2024 (../competitors/conjura.md)
- "The product deep dive down to SKU level is phenomenal, as well as the insights around LTV." — Amelia P., G2, December 2023 (../competitors/conjura.md)
- "easy statistics for products and total orders... UI is really great and comfortable to work with" — yair P., Production Manager, Capterra, May 14, 2019 (../competitors/putler.md)

**Theme: One place to see everything (inventory implicit)**
- "Now I have a single source of truth that saves me hours weekly." — Waqas Q., Capterra, May 29, 2025 (../competitors/putler.md)
- "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." — Island Living (Singapore), Shopify App Store, November 2024 (../competitors/conjura.md)

No verbatim user quote in the corpus praises a stockout forecast, a days-of-cover metric, an overstock detection screen, or a low-stock alert by name.

## What users hate about this feature

**Theme: Native stock reports are inadequate**
- "Stock report cannot be exported at all. If you need inventory data in a spreadsheet, you have to use the legacy Products screen, which has its own limitations." — Putler's gap analysis quoted in (../competitors/woocommerce-native.md, lines 180–182).

**Theme: Refresh latency on inventory specifically**
- The Glew profile flags that inventory refreshes **nightly** while orders refresh hourly (../competitors/glew.md, line 9: "data_freshness: hourly (most data); customer + inventory nightly"). No verbatim user quote, but a structural complaint a merchant chasing live stock would feel.

**Theme: Inventory features are paywalled out of reach**
- Shopify Native's Days-of-inventory-remaining + ABC analysis reports are gated behind **Advanced+ plan ($299/mo)** (../competitors/shopify-native.md, line 89). Lebesgue's Stock Inventory Management requires Advanced ($59/mo) (../competitors/lebesgue.md, line 42). Conjura's ERP/OMS connectors gate behind Scale tier ($129.99/mo) (../competitors/conjura.md). Glew's Inventory Analytics is on Pro ($249/mo at the $1M revenue band) (../competitors/glew.md). The merchants who care about inventory most — sub-$1M brands with tight cash — find these features above their plan.

**Theme: ad spend × stock disconnect**
- No verbatim quote, but Lebesgue's "stock-vs-ad-perf coupling" (../competitors/lebesgue.md, line 85) implicitly admits the gap exists everywhere else. The other ten competitors profiled do not surface "we are still spending ads on a depleting SKU" inside the inventory surface.

The thinness of explicit user complaints likely reflects reviewer composition (analytics buyers tend to be marketers/founders who complain about ROAS first) rather than absence of pain — merchants probably solve inventory in spreadsheets when the analytics tool fails them, and never come back to write a review about it.

## Anti-patterns observed

- **Inventory as a column, not a signal:** BeProfit, Conjura, Bloom, Putler all surface stock-on-hand as a column on the Products table without a forward-looking days-of-cover KPI or a low-stock alert mechanism. Merchants must mentally combine units-sold + units-on-hand to derive runway. This is the dominant anti-pattern.
- **Static snapshot with no comparison:** WooCommerce Native's Stock report is a single sortable table — no date range, no chart, no summary cards, no CSV export. It tells you the current state but not whether it's getting worse or whether velocity has changed. The non-exportable behavior is structurally hostile to operational workflows.
- **Slow refresh on the most operational surface:** Glew refreshes inventory nightly while everything else is hourly. By the time you see "low stock," the SKU has been on Meta's auction at full bid for 12 more hours.
- **Anomaly inbox without the metric:** Triple Whale Lighthouse fires "inventory anomaly" alerts but the profile suggests no underlying days-of-cover dashboard backs it up — alerts without a navigable surface to investigate the why.
- **Forecasts you can't audit:** Glew lists "demand prediction"; Metorik lists "inventory forecasts"; neither publishes the methodology, the lookback, or whether the forecast accounts for seasonality, trend, or one-off spikes. A merchant can't tell whether to trust a number when 'sales velocity' was distorted by last week's promo.
- **Forward-looking signal hidden in chat:** Shopify Sidekick's "When inventory drops below 10 units, send a Slack alert" example is excellent — but the user has to know to ask. There's no proactive "your top-10 SKUs are within 14 days of stockout" surface in the dashboard.
- **AI agent without UI surface:** Polar's Inventory Planner agent is announced (April 2026) without a visible UI. Conjura's Owly AI takes inventory questions but charges $199+/mo on top of the base tier. The most-marketed solution to inventory questions is the most expensive and least visible.

## Open questions / data gaps

- **Glew Inventory Analytics screen has no public screenshots.** The module is named in marketing and KPIs are listed ("Stock levels, sell-through, sales velocity, demand prediction, inventory aging") but the actual UI sits behind a sales-led demo. A trial or demo walkthrough would resolve whether this is a strong dashboard or a thin one.
- **Daasity Inventory dashboard is named in the Report Library but its KPIs and viz are not enumerated in public docs.** Only the breadcrumb is confirmed.
- **Polar Inventory Planner AI agent UX is undisclosed.** The agent is part of an April 2026 narrative but no UI details, no example queries, no screenshot.
- **Lebesgue Stock Inventory Management lacks a screenshot.** The "stock-vs-ad-perf coupling" pitch is unique but the actual dashboard layout is invisible to public research.
- **Days-of-cover formula details vary.** Shopify Native names "Days of inventory remaining" but doesn't publish the rolling-window length (7d? 28d?) used as the velocity baseline. None of the competitors publishes the exact formula.
- **Lost-revenue-from-stockout estimates.** No competitor profiled here surfaces a "you lost $X to stockouts last week" dollar value. This would be the obvious next-mile metric — its absence is conspicuous.
- **Multi-warehouse / in-transit / on-PO logic.** Daasity, Conjura, Glew expose ERP/OMS connectors that bring multi-location stock + on-PO into the picture, but none of the public docs shows how the analytics surface visualizes "150 in NYC warehouse, 0 in LA warehouse, 200 on PO landing in 21 days."
- **No reviewer corpus on inventory specifically.** Across all 12 profiles, zero verbatim user quotes name an inventory dashboard, a days-of-cover metric, or a stockout alert as a love-or-hate driver. Either the surfaces aren't sticky, or merchants who care about inventory aren't writing reviews about it.

## Notes for Nexstage (observations only — NOT recommendations)

- **This is a thinly-covered surface.** Of 12 competitors profiled with inventory data flowing through the product, only 3 (Glew, Shopify Native, Lebesgue) have a dedicated inventory dashboard with named forward-looking metrics. 4 competitors treat inventory as a column on the product table. 1 competitor (WooCommerce Native) ships a stock table that can't even export to CSV.
- **Days-of-cover as a named, published KPI is uncontested by name.** Only Shopify Native uses the exact term "Days of inventory remaining" — and it sits behind the $299/mo Advanced plan. The metric is universally derivable but rarely surfaced.
- **The ad-spend × stock-on-hand cross-cut is unique to Lebesgue.** This is the strongest single pattern in the corpus — paying for traffic to a depleting SKU is a textbook SMB pain that 11 of 12 competitors don't visualize. It's a natural fit for any tool that joins ad data and store data (Nexstage's MetricSourceResolver setup already touches both).
- **Sell-through rate, sales velocity, and inventory aging are all named but inconsistently surfaced.** Glew names all three; Conjura exposes sell-through; Bloom exposes inventory value. No single competitor exposes all four (sell-through, velocity, aging, value at cost) in one surface.
- **ABC analysis is a published pattern but only Shopify Native has it.** Pareto-style "your A-class SKUs are 80% of revenue" is a well-understood concept; only Shopify Native has it as a named report. Glew does revenue concentration; Putler does an 80/20 chart but for revenue concentration over time, not for inventory class.
- **Refresh cadence matters for inventory more than for other surfaces.** Glew explicitly refreshes inventory nightly (separate from hourly orders) — a known structural compromise. Daasity is daily/nightly except for an hourly Shopify Flash dashboard that doesn't include inventory. Real-time stock is uniquely valuable here because the action (pause ads, reorder) is operational, not analytical.
- **AI agents are the announced answer to inventory questions across 2025–26.** Polar's Inventory Planner, Triple Whale's Anomaly Detection Agent (replacing Lighthouse), Lebesgue's Henri, Conjura's Owly, Shopify's Sidekick all claim conversational entry into inventory questions. None publish a screenshot of the inventory-specific output. The pattern is consistently "ask the bot, don't navigate the dashboard."
- **The most pragmatic "alert" surface is Sidekick's.** Shopify Sidekick's published example — "When inventory drops below 10 units, send a Slack alert and tag the product" — is the only alert example that emits an executable rule (a Shopify Flow automation), not just a notification. This bridges analytics → action without leaving the conversational surface.
- **IMS / ERP integration is the high-end gate.** Linnworks, Brightpearl, Cin7, NetSuite, ShipBob, ShipStation, BackinStock, Extensiv, Fulfil, Order Desk all show up across Glew, Conjura, Daasity profiles — always at the upper-tier price ($129.99/mo Conjura Scale, ~$1,899/mo Daasity, Glew Plus). For SMB merchants without an IMS, the analytics tool has to be the IMS-lite, which raises the bar for native inventory features beyond a Stock column.
- **No competitor surfaces "lost revenue from stockouts" as a dollar value.** The formula (`days_out × velocity × avg_unit_price`) is trivial; the absence is conspicuous. This is a candidate for a differentiating KPI if Nexstage wanted one.
- **Inventory signals is paired naturally with `winners-losers.md`.** A SKU that's a "winner" (gaining traction) running low on stock is a different urgency than a "loser" running low. The cross-cut is implicit in Conjura's pre-built segments ("items selling out" vs "slow movers") and in the ABC × velocity logic.
