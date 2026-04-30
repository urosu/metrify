---
name: Product performance
slug: product-performance
purpose: Answers "Which SKUs drive revenue, margin, and repeat purchases?" by surfacing per-product profitability, ad-spend attribution, and cohort behavior.
nexstage_pages: products, performance, dashboard
researched_on: 2026-04-28
competitors_covered: conjura, triple-whale, lifetimely, glew, daasity, northbeam, beprofit, trueprofit, putler, bloom-analytics, polar-analytics, storehero, shopify-native, peel-insights
sources:
  - ../competitors/conjura.md
  - ../competitors/triple-whale.md
  - ../competitors/lifetimely.md
  - ../competitors/glew.md
  - ../competitors/daasity.md
  - ../competitors/northbeam.md
  - ../competitors/beprofit.md
  - ../competitors/trueprofit.md
  - ../competitors/putler.md
  - ../competitors/bloom-analytics.md
  - ../competitors/polar-analytics.md
  - ../competitors/storehero.md
  - ../competitors/shopify-native.md
  - ../competitors/peel-insights.md
  - https://www.conjura.com/product-table-dashboard
  - https://help.conjura.com/en/articles/9127883-understanding-sku-level-ad-spend
  - https://docs.northbeam.io/docs/product-analytics
  - https://docs.bloomanalytics.io/product-profits.md
  - https://docs.bloomanalytics.io/product-analysis.md
  - https://www.putler.com/product-analysis
  - https://trueprofit.io/solutions/product-analytics
  - https://useamp.com/products/analytics
---

## What is this feature

Product performance is the surface that answers "Which SKUs drive revenue, margin, and repeat purchases?" — translating Shopify/Woo line-item data into a per-SKU view that mixes revenue, gross/contribution profit, ad-spend allocation, refund rate, inventory state, and downstream customer behavior (repeat-purchase rate, cross-sell, LTV by first product). Native commerce platforms (Shopify, WooCommerce) already expose units sold and revenue by product; the *feature* is the synthesis that brings COGS-aware margin, attributed ad spend, conversion-rate funnel metrics, and SKU-level retention into a single sortable surface.

For SMB Shopify/Woo merchants the question is operational, not analytical: should they reorder this SKU, kill this listing, raise the price, or push more ad spend behind it. Spreadsheets and the native admin can show units sold, but they cannot answer "is this SKU's ad spend payback positive once shipping, COGS, and gateway fees are netted?" That synthesis (spend allocated by ad-URL parsing, COGS pulled from variant cost field, contribution margin computed live) is what every competitor in the category claims as their wedge.

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `orders.line_items.product_id`, `orders.line_items.variant_id`, `orders.line_items.quantity`, `orders.line_items.price`, `orders.line_items.discount_allocations`, `orders.refunds.refund_line_items`, `products.variants.cost`, `products.title`, `products.product_type`, `products.tags`, `products.vendor`, `inventory_levels.available`
- **Source: WooCommerce** — `wc_orders.items.product_id`, `wc_orders.items.variation_id`, `wc_orders.items.quantity`, `wc_orders.items.subtotal`, `wc_products.regular_price`, `wc_products._wc_cog_cost` (cost of goods plugin), `wc_orders.refunds`, `wc_products.stock_quantity`, `wc_product_attributes`
- **Source: Meta Ads API** — `ads.creative.object_story_spec.link_data.link` (destination URL for SKU-level attribution via URL parsing), `ads.spend`, `ads.impressions`, `ads.clicks`, `ads.creative_id`, `ads.adset_id`, `ads.campaign_id`
- **Source: Google Ads API** — `ads.final_url`, `shopping_performance_view.product_item_id`, `shopping_performance_view.merchant_id`, `ads.metrics.cost_micros`, `ads.metrics.conversions`, `ads.metrics.conversions_value`
- **Source: TikTok / Pinterest / Snapchat / Bing Ads** — `ads.landing_page_url`, `ads.spend`, `ads.impressions`, `ads.clicks`, `ads.platform_reported_revenue`
- **Source: GA4** — `events.page_view.page_location` (product detail page URL), `events.add_to_cart.items.item_id`, `events.purchase.items.item_id`, `events.purchase.items.item_revenue`, `sessions` per landing page
- **Source: GSC** — `query`, `page` (product URL), `clicks`, `impressions`, `position` (for organic-attribution cross-reference, not standard in competitors)
- **Source: User-input** — `cogs_per_variant` (when missing from store), `shipping_cost_per_product` or shipping rule (by country/weight/quantity), `handling_cost`, `tariff_cost`, `channel_fees_per_gateway`
- **Source: Computed** — `attributed_ad_spend_per_sku = SUM(ad_spend WHERE landing_url MATCHES product_url)`, `contribution_profit = revenue − cogs − refunds − fees − shipping − attributed_ad_spend`, `breakeven_roas = 1 / contribution_margin`, `cac_index_per_sku`, `roas_index_per_sku` (1–100 normalized), `repeat_rate_first_product`, `time_to_second_order_per_first_product`
- **Source: Klaviyo / Attentive (optional)** — `attributed_revenue_per_product` for cross-channel SKU profit attribution
- **Source: ReCharge / Skio (subscription brands)** — `subscription_orders.product_id`, `mrr_per_sku`, `churn_per_sku`

## Data outputs (what's typically displayed)

- **KPI: SKU contribution profit** — `revenue − cogs − refunds − fees − shipping − attributed_ad_spend`, USD, vs prior-period delta and as % of category
- **KPI: Gross margin %** — `(revenue − cogs) / revenue`, %, store-level vs SKU-level comparison
- **KPI: Net profit margin %** — `contribution_profit / revenue`, % per SKU
- **KPI: Breakeven ROAS** — `1 / contribution_margin`, ratio, rendered as "needs to clear X.Xx" target
- **KPI: ROAS Index** — `1–100 normalized score across catalog` (Northbeam pattern)
- **KPI: CAC Index** — `1–100 normalized score across catalog` (Northbeam pattern)
- **Dimension: SKU / variant** — string, fans out to `~10–10,000` rows depending on catalog
- **Dimension: Product type / vendor / tag / collection** — group-by selectors
- **Dimension: First-product-purchased** — used to slice cohort/LTV and repeat-rate
- **Breakdown: Ad spend × SKU × platform** — table column or stacked bar (Conjura: ad-URL parsing assigns spend bucket; falls into "Ad Spend - No Product" if URL is generic)
- **Breakdown: Funnel × SKU** — page views, ATC rate, conversion rate (TrueProfit pattern)
- **Slice: Per-cohort repeat-rate by first SKU** — what was 2nd / 3rd / 4th order for buyers whose 1st order included this SKU (Conjura Purchase Patterns, Lifetimely noodle)
- **Slice: Inventory state × SKU** — stock units, sell-through, days-of-cover
- **Saved view / segment: "Unprofitable products"**, "Slow movers", "Items selling out", "High-margin winners", "Stop ads on these"

## How competitors implement this

### Conjura ([profile](../competitors/conjura.md))

- **Surface:** Sidebar > Product Intelligence > Product Table (and sibling Product Deepdive, Purchase Patterns).
- **Visualization:** Sortable/filterable wide table per SKU with **pre-built filter chips/saved views** ("unprofitable products, slow movers, items selling out"); plus a separate 5-column card-grid funnel ("Purchase Patterns") for what-buyers-bought-next.
- **Layout (prose):** "Top: filter chips + saved-view selector. Left rail: standard sidebar. Main canvas: dense per-SKU row table with custom-metric filtering and column sort. Bottom: pagination. Adjacent Product Deepdive opens a 'visual command centre for tracking individual product performance' with full 360° SKU view (UI not disclosed in marketing pages)."
- **Specific UI:** "**SKU-level ad-spend attribution via ad URL parsing** — works for Google Shopping, Performance Max, deep-linked Google ads, and Facebook ads pointing to a product page. Ads landing on a generic homepage fall into a literal `Ad Spend - No Product` bucket as a column value." Also: "Pre-built saved views are first-class — 'unprofitable products', 'slow movers', 'items selling out' are surfaced as filter chips above the table, not buried in a custom-segments menu."
- **Filters:** date, store, channel, territory, product profitability bracket, inventory state, custom metric.
- **Data shown:** sales, conversion rate, product views, discount %, returns/refund rate, contribution profit, ad spend by product, stock levels, sell-through rate.
- **Interactions:** Click row → drill into Product Deepdive 360° view. Click a card in Purchase Patterns → filters the entire dashboard to customers who purchased that item at that order rank, revealing what they bought subsequently.
- **Why it works (from reviews/observations):** "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, Shopify App Store, March 2024. "The product deep dive down to SKU level is phenomenal." — Amelia P., G2, December 2023.
- **Source:** [conjura.md](../competitors/conjura.md), https://www.conjura.com/product-table-dashboard, https://help.conjura.com/en/articles/9127883-understanding-sku-level-ad-spend.

### Triple Whale ([profile](../competitors/triple-whale.md))

- **Surface:** Sidebar > Analytics > Product Analytics (Advanced+ tier).
- **Visualization:** Sortable per-SKU table; UI details for the Product Analytics surface specifically were not directly observable from public marketing/blog pages (KB blocked anti-bot in research).
- **Layout (prose):** "Top: date range + attribution-model selector. Main canvas: SKU rows with cross-platform spend + Triple-Pixel-attributed revenue side-by-side with platform-reported revenue. Likely sortable; no public screenshot."
- **Specific UI:** "Product Analytics is gated to Advanced+ — explicitly listed in pricing tier copy as 'SKU-level performance + journey mapping'. UI details not directly verified."
- **Filters:** date, store, attribution model, channel, segment.
- **Data shown:** SKU-level performance + customer journey mapping (per pricing-tier feature copy); specific column list not published.
- **Interactions:** Drill via Moby Chat ("which products are driving traffic but not sales?" is a published example query).
- **Why it works (from reviews/observations):** "Triple Whale is the gold standard and leading platform for multi-channel analytics." — Brixton, Shopify App Store, January 21, 2026. Note: SKU-level reliability has been flagged as inconsistent in some reviews — limited observation.
- **Source:** [triple-whale.md](../competitors/triple-whale.md), https://www.triplewhale.com/analytics. UI details not available — only feature description seen on marketing page.

### Lifetimely ([profile](../competitors/lifetimely.md))

- **Surface:** Distributed: Profit Dashboard surfaces top-product cards; LTV Drivers Report ranks by product; Customer Product Journey ("noodle" diagram) shows 1st→2nd→3rd→4th product flow.
- **Visualization:** **Sankey-style "noodle" flow diagram** for product journey (curved/flowing bands; volume of customers transitioning between specific products). Plus auto-ranked correlation table for LTV Drivers, plus standard top-N tables on the Profit Dashboard.
- **Layout (prose):** "No single 'product' tab — product analytics fans out across multiple surfaces. Profit Dashboard income-statement view shows top SKUs as a side card. LTV Drivers Report is a ranked list. Customer Product Journey is a full-page Sankey/noodle. No dedicated SKU-table-with-margin-column surface observed."
- **Specific UI:** "Noodle diagram: curved bands rather than straight Sankey lines, color-coded by product or category, filterable by cohort / discount / channel / post-purchase survey response. Each band's thickness = customer count transitioning between purchase positions."
- **Filters:** cohort timeframe, first-product, discount code, channel, country, customer/order tags.
- **Data shown:** Customer count flowing between purchase positions (1st→2nd, 2nd→3rd…); conversion rate from purchase N to purchase N+1; LTV-driver correlation rank for products / discount codes / countries / tags.
- **Interactions:** Filter cohort upstream of the noodle; ranked list click-through to underlying cohort.
- **Why it works (from reviews/observations):** "helped us understand our Lifetime Value better than anything else" — Blessed Be Magick, Shopify App Store, February 8, 2026. Cohort UX is repeatedly called "best-in-class for a Shopify app."
- **Source:** [lifetimely.md](../competitors/lifetimely.md), https://useamp.com/products/analytics, https://1800dtc.com/breakdowns/lifetimely.

### Glew ([profile](../competitors/glew.md))

- **Surface:** Sidebar > Product Analytics (and sibling Amazon Products marketplace-specific table, Net Profit by Channel).
- **Visualization:** Sortable per-product table + "Individual Product KPIs" detail page; "Products Purchased" sub-table on Customer Profile filterable by revenue, margin, COGS, refunds.
- **Layout (prose):** "Top: KPI Highlights tiles. Left rail: standard sidebar. Main canvas: product table with 250+ KPIs available across the platform; per-product drill page. Multi-source enrichment via warehouse joins (Looker)."
- **Specific UI:** "Customer Profile > Products Purchased sub-table — filterable by revenue, margin, COGS, refunds. Per-product detail page surfaces volume, margin, profitability, refunds by channel."
- **Filters:** Revenue band (pricing tier driven), 300+ unique filtering options per Glew Pro page, channel, store (multi-brand toggle).
- **Data shown:** Volume, margin, profitability, refunds by channel; per-channel net profit; LTV Profitability by Channel sub-page exists (UI not directly observed).
- **Interactions:** Click row → individual product KPIs page; cross-source filter joins on Plus tier via Looker.
- **Why it works (from reviews/observations):** "easy to comprehend dashboards at your fingertips with actionable insights" — Jonathan J S., Capterra Oct 2019. "exceptional reporting capabilities, transforming data visualization." — G2 review summary, 2025.
- **Source:** [glew.md](../competitors/glew.md), https://www.glew.io/features/ecommerce-dashboard. UI details for the Product Analytics screen specifically not directly observed in public sources beyond marketing-page copy.

### Daasity ([profile](../competitors/daasity.md))

- **Surface:** Templates Library > Ecommerce Performance > Product (and sibling Product Repurchase Rate dashboard).
- **Visualization:** Embedded Looker tile dashboard — sortable tables, line/bar charts; specific tile structure not surfaced in public docs.
- **Layout (prose):** "Top: filter strip with Store Type / Store Integration / date controls (with explicit refresh button — filter changes do not auto-apply). Main canvas: Looker-embedded tiles. Sibling 'Product Repurchase Rate' dashboard surfaces SKU-level repurchase metrics."
- **Specific UI:** "Filter changes require manual refresh-button click ('When you Toggle the Dashboard Filters the Data on the Dashboards will update after you click the Refresh Button'). Hourly Flash dashboard (Shopify-only) is the only sub-daily refresh."
- **Filters:** Linked Store Type (Amazon vs ecommerce), Store Integration Name, date range.
- **Data shown:** Per-SKU sales, AOV, UPT (Units Per Transaction), repurchase rate; multi-store consolidation across Shopify integrations into unified UOS schema.
- **Interactions:** Drill from Home Dashboard's Ecommerce tab into Product dashboard; Looker drag-and-drop for custom views.
- **Why it works (from reviews/observations):** "Lots of great integrations & dashboards" with praise for support team's helpfulness in creating custom reports. — tentree CA, Shopify App Store, June 9, 2022.
- **Source:** [daasity.md](../competitors/daasity.md), https://help.daasity.com/core-concepts/dashboards/report-library. UI details not available for the Product dashboard specifically — only template-library structure and naming.

### Northbeam ([profile](../competitors/northbeam.md))

- **Surface:** Sidebar > Attribution > Product Analytics tab.
- **Visualization:** **4-quadrant scatter plot** with bubble size = ad spend; X axis = CAC Index (1–100), Y axis = ROAS Index (1–100); plus a row-level data table below for the same selection.
- **Layout (prose):** "Top: four 'stackable' filter buttons (Product / Platform / Campaign / Ad) checkbox-combinable. Main canvas: large scatterplot divided into four colored quadrants. Below: row-level data table for the same selection."
- **Specific UI:** "**Four-quadrant color scheme** — Yellow (top-left) = High ROAS, high CAC; **Green (top-right) = High ROAS, low CAC ('your best performers')**; Red (bottom-left) = Low ROAS, high CAC ('underperformers'); Blue (bottom-right) = Low ROAS, low CAC. **Quick-analysis chips auto-filter both scatterplot and table to a single quadrant.** **Index-based 1–100 scoring (not raw ROAS/CAC)** — explicit normalization for cross-product comparison so distributions are plottable."
- **Filters:** Date range, attribution model, attribution window, accounting mode (Cash/Accrual), product/platform/campaign/ad toggle (stackable).
- **Data shown:** ROAS Index (1–100), CAC Index (1–100), Spend (encoded as bubble size), plus underlying raw metrics in the table.
- **Interactions:** Click quadrant or quick-analysis chip to focus; toggle bubble dimension Product/Platform/Campaign/Ad; row-level filter on the table; hover bubble for details.
- **Why it works (from reviews/observations):** "Index normalization escape-hatch for the universal 'ROAS distributions are too wide to plot raw' problem." — Northbeam profile observation. Note: Northbeam itself is "behind a sales-led demo gate" — public adoption commentary on this specific surface is thin.
- **Source:** [northbeam.md](../competitors/northbeam.md), https://docs.northbeam.io/docs/product-analytics.

### BeProfit ([profile](../competitors/beprofit.md))

- **Surface:** Sidebar > Reports > Product reports.
- **Visualization:** Multi-dimension grouped table (group-by dropdown for product / type / vendor / collection / variant).
- **Layout (prose):** "Top: group-by dropdown + date range + sort controls. Main canvas: per-product / per-vendor / per-collection / per-variant rows with revenue, profit, COGS, sales columns. Drill into single product from row click."
- **Specific UI:** "Multi-dimension grouping selector at top — same table pivots between Product / Type / Vendor / Collection / Variant levels via the dropdown. Shopify App Store screenshot #4 caption: 'Profit breakdown by product, type, vendor, collection, variant'."
- **Filters:** date range, group-by dimension.
- **Data shown:** revenue, profit, COGS, sales per product / type / vendor / collection / variant.
- **Interactions:** Group-by dropdown, sort columns, drill into single product.
- **Why it works (from reviews/observations):** Limited specific praise for the product report surface; broader complaint pattern is initial setup data-entry burden.
- **Source:** [beprofit.md](../competitors/beprofit.md). UI details beyond the Shopify App Store carousel caption not directly observable.

### TrueProfit ([profile](../competitors/trueprofit.md))

- **Surface:** Sidebar > Product Analytics (gated to Advanced tier $60/mo+).
- **Visualization:** Tabular SKU/variant view with **per-product net profit margin as a percentage column** (live examples cited: "58.95% and 45.23%").
- **Layout (prose):** "Top: date range + tier-gated lock state. Main canvas: SKU/variant rows with margin %, cost-breakdown columns, and funnel-metric columns. Designed to enable 'winner/loser product identification' (verbatim from marketing copy)."
- **Specific UI:** "**Variant-level granularity confirmed** (most SMB peers cap at SKU/product level). Per-row: margin %, ad spend allocated to that product, page views, ATC rate, conversion rate."
- **Filters:** Date range; sortable columns implied. Drill-down behavior not confirmed.
- **Data shown:** Net profit per product, profit margin %, ad spend per product, COGS, shipping, page views, ATC rate, conversion rate.
- **Interactions:** Sortable columns; mobile app explicitly does not include SKU drill-downs ("Deeper feature dashboards like SKU reports aren't accessible via mobile app yet" — TrueProfit's own review blog).
- **Why it works (from reviews/observations):** Headline marketing claim is "Identify most and least profitable products" — review verbatims tend to emphasize net-profit framing generally rather than the product surface specifically.
- **Source:** [trueprofit.md](../competitors/trueprofit.md), https://trueprofit.io/solutions/product-analytics. UI details not available — only feature description seen on marketing page.

### Putler ([profile](../competitors/putler.md))

- **Surface:** Sidebar > Products (Products Dashboard / Leaderboard).
- **Visualization:** Sortable list/table with **inline star icons next to top revenue generators**; adjacent **80/20 Breakdown Chart** (a trend line showing how revenue concentration shifts over time across the catalog); plus per-product card view with frequently-bought-together pairings and predicted-monthly-sales.
- **Layout (prose):** "Top: five filter chips — Customer count, Quantity sold, Refund percentage, Average price tier, Attributes (size/color/category). Main canvas: sortable product table with star icons on top-revenue rows. Adjacent: 80/20 Breakdown Chart (concentration line trend). Click row → Individual Product card with customer purchase list (exportable), revenue contribution, refund rate, average refund timing, predicted monthly sales, average time between sales, sales history timeline, product variation breakdown (size/color), and **'frequently bought together' pairings**."
- **Specific UI:** "**Star icons inline next to top-revenue products in the row list** (8/16px range — exact size not published). **80/20 Breakdown Chart**: line chart of concentration ratio over time, signaling Pareto distribution shifts. Custom Segments combine filter chips via AND/OR logic."
- **Filters:** Customer count, Quantity sold, Refund percentage, Price tier, Attributes (size/color/category).
- **Data shown:** Revenue per product, customer count, quantity sold, refund %, refund timing, predicted monthly sales, average time between sales, variation breakdown, frequently-bought-together pairs.
- **Interactions:** Filter chips combine via AND/OR; click product → open detail card; export customer list as CSV from card.
- **Why it works (from reviews/observations):** "easy statistics for products and total orders... UI is really great and comfortable to work with" — yair P., Capterra, May 14, 2019.
- **Source:** [putler.md](../competitors/putler.md), https://www.putler.com/product-analysis.

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))

- **Surface:** Sidebar > Product Metrics > Product Profits (and sibling Product Analysis).
- **Visualization:** Wide table with **column-selector menu in top-right + 20+ metric columns**; sibling **Profit Map** is a "visual interactive tree-graph showing how each metric flows into net profit" at the dashboard level (not strictly product, but the map decomposition routinely surfaces product cost as a branch).
- **Layout (prose):** "Top: column-selector menu + filter icon. Main canvas: wide table with toggle 'View by product variants' that expands rows to variant level. 20+ metric columns. Adjacent Profit Map (homepage-marketed) is a tree-graph visualization that consolidates 'revenue alongside every expense — ad spend, product costs, shipping, and operating overhead'."
- **Specific UI:** "**Toggle 'View by product variants'** expands rows to variant level — explicit variant drill in-place rather than separate route. **Manual campaign-link entry for Product ROAS attribution** (user pastes the ad's destination URL to claim ad spend for that SKU). Profit Map: tree-graph nodes/branches (specific node count, orientation, hover state not documented publicly)."
- **Filters:** Product Name, Variant Name, Product Type, SKU, Tags, Status, ACTIVE flag.
- **Data shown:** Units Sold, Units Refunded, Gross Sales, Discounts, Refunds, Net Sales, Product COGS, Inventory Cost, Total Sales, Gross Profit, Gross Margin %, Net Profit, Ad Spend, Campaign Link, Product ROAS, Inventory Quantity, Inventory Value.
- **Interactions:** Filter, sort, expand variants in-place, export to Excel/CSV/PDF, manual campaign-link entry.
- **Why it works (from reviews/observations):** "Great app! Very interactive UI. Gives you full insight in product data…" — BRUNS (Sweden), Shopify App Store, January 28, 2026.
- **Source:** [bloom-analytics.md](../competitors/bloom-analytics.md), https://docs.bloomanalytics.io/product-profits.md, https://docs.bloomanalytics.io/product-analysis.md.

### Polar Analytics ([profile](../competitors/polar-analytics.md))

- **Surface:** Pre-built Product / Merchandising page.
- **Visualization:** Pre-built dashboard combining product performance, variants, inventory, bundling, stock depletion in one canvas. Specific tile/chart structure not surfaced in public marketing.
- **Layout (prose):** "Pre-built Product/Merchandising page among ~10 named pages. Combines product performance + variants + inventory + bundling + stock depletion."
- **Specific UI:** "Stock depletion is called out as a first-class metric on this page — distinct from a generic inventory snapshot. Bundling analysis surface listed but UI details not public."
- **Filters:** Saved 'Views' (named filter bundles), date range, store/channel/region/product, sales channel.
- **Data shown:** Product performance, variants, inventory, bundling, stock depletion (per marketing page).
- **Interactions:** View dropdown switches saved filter bundles; AI Co-Pilot generates Custom Reports answering product questions ("What were my top selling products in NYC last week?").
- **Why it works (from reviews/observations):** Product-merchandising page is a named pre-built — emphasizes the consolidation pitch. Specific user verbatims focused on this surface are limited in the profile.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md). UI details not available — only feature description seen on marketing page.

### StoreHero ([profile](../competitors/storehero.md))

- **Surface:** Top tab strip > Products tab.
- **Visualization:** SKU-level table with fully-loaded COGS, gross profit per product, and **breakeven ROAS each SKU needs to clear from paid ads**.
- **Layout (prose):** "Tab strip across the top: Dashboard, Ads, Creatives, Finance, LTV, **Products**, Orders. Main canvas: SKU table with fully-loaded COGS, gross profit per SKU, breakeven ROAS per SKU as named columns."
- **Specific UI:** "**Breakeven ROAS column per SKU** is the single most distinctive primitive — converts contribution margin into the ad-spend efficiency target a buyer can act on (`1/contribution_margin`)."
- **Filters:** date range, store.
- **Data shown:** SKU, fully-loaded COGS, gross profit per SKU, breakeven ROAS.
- **Interactions:** Sortable columns implied; UI specifics beyond column list not observable.
- **Why it works (from reviews/observations):** All-5-star review base with limited critical commentary on this specific surface; BeProfit's competitive-attack page acknowledges StoreHero is "Made for marketers."
- **Source:** [storehero.md](../competitors/storehero.md). UI details beyond column list not directly observable — small public review surface.

### Shopify Native ([profile](../competitors/shopify-native.md))

- **Surface:** Admin > Analytics > Reports > **Sales by product** / **Sales by product variant SKU** / **Gross profit by product** (Advanced tier+).
- **Visualization:** Standard Shopify report tables — `FROM products SHOW ... WHERE ... GROUP BY ...` ShopifyQL pattern; plus **top-products card** on the customizable Overview dashboard.
- **Layout (prose):** "Date-range picker + comparison toggle pinned to top. Top-products table sits below the metric-card grid. Profit reports (Advanced+): 'Gross profit by product' and 'Gross profit by variant' with explicit COGS-aware columns."
- **Specific UI:** "Reports clearly call out which line items lack a recorded COGS so they aren't counted toward gross profit ('Net sales without cost recorded' vs 'Net sales with cost recorded' — both are explicit columns). Sidekick translates plain-English ('What were my best-selling products this month?') into ShopifyQL and saves the result as an Exploration."
- **Filters:** Date range + comparison; ShopifyQL `WHERE` clause for power users.
- **Data shown:** Total sales by product / variant SKU / vendor; gross profit by product (Advanced+); gross profit by variant (Advanced+); top products by units sold (Overview card).
- **Interactions:** Drag-and-drop metric cards on Overview; click a top-products card to drill into the Sales-by-product report; Sidekick `/weekly-summary` slash command auto-generates a product summary.
- **Why it works (from reviews/observations):** Native and free-with-store; the "always-on default lens" baseline against which every paid product is measured.
- **Source:** [shopify-native.md](../competitors/shopify-native.md), https://help.shopify.com/en/manual/reports-and-analytics.

### Peel Insights ([profile](../competitors/peel-insights.md))

- **Surface:** Sidebar > Product Analytics (multiple sub-templates).
- **Visualization:** Set of pre-built templates — Market Basket Analysis, Customer Purchasing Journey, Product Sales by Vendor, Product Ranking, Product Popularity by Order Number, Products by Source/Campaign & Channel.
- **Layout (prose):** "Sidebar lists Product Analytics templates by name. Each template is its own dashboard — Market Basket is a co-occurrence/affinity matrix, Product Ranking is a sortable leaderboard, Product Popularity by Order Number breaks SKU performance down by 1st/2nd/3rd order rank."
- **Specific UI:** "**Product Popularity by Order Number** — explicit dimension that lets users see which SKUs dominate the 1st order vs the 5th order (parallel to Conjura's Purchase Patterns). **Magic Dash AI** can answer 'What are the top 3 products that new customers purchase?' but explicitly cannot answer 'Product Journey' or predictive queries."
- **Filters:** First-product, channel, cohort, segment, date range.
- **Data shown:** Per-SKU revenue/units, basket affinity scores, order-rank popularity, source/campaign/channel breakdown per SKU.
- **Interactions:** Templates are starting points for custom dashboards; audiences can be built from product filters and pushed to Klaviyo / Attentive / Postscript / Meta.
- **Why it works (from reviews/observations):** "purpose-built reports unavailable elsewhere" framing; positioned against general BI for retention-specific product questions.
- **Source:** [peel-insights.md](../competitors/peel-insights.md), https://www.peelinsights.com/post/product-update-new-analysis-templates.

## Visualization patterns observed (cross-cut)

- **Sortable wide table with margin column:** 9+ competitors (Conjura, Glew, Daasity, BeProfit, TrueProfit, Bloom, StoreHero, Shopify Native, Polar) — the universal default. Variants: column-selector menu (Bloom 20+ metrics), group-by selector (BeProfit), variant-toggle in-place (Bloom, TrueProfit), pre-built saved-views as filter chips (Conjura).
- **Quadrant scatter (ROAS Index vs CAC Index, bubble = spend):** 1 competitor (Northbeam) — only direct implementation observed in the SMB/mid-market product-performance space. Index normalization (1–100) is the unique trick; quick-analysis chips auto-filter quadrant to focus.
- **Tree / hierarchy view (Profit Map):** 1 competitor (Bloom Analytics) — only competitor positioning a tree-graph as a profit-decomposition visualization. UI details largely undocumented in public docs (no `profit-map.md` page in their docs sitemap).
- **5-column funnel of product purchases by order rank:** 1 competitor (Conjura's Purchase Patterns) plus 1 partial (Peel's Product Popularity by Order Number). Conjura's click-to-filter funnel is unique.
- **Sankey/noodle flow (1st→2nd→3rd→4th product transitions):** 1 competitor (Lifetimely) — curved bands rather than straight Sankey lines.
- **80/20 concentration trend chart:** 1 competitor (Putler) — line chart of concentration ratio shifting over time across the catalog.
- **Frequently-bought-together pairings card:** 1 competitor explicitly (Putler); 1 implicit via Market Basket template (Peel).
- **Per-SKU breakeven-ROAS column:** 1 competitor (StoreHero) — direct, named primitive.
- **Top-products card on dashboard overview:** Universal across overview-style dashboards (Shopify Native, Triple Whale, Polar, Lifetimely, Glew, Putler) — table-stakes baseline.
- **Color conventions:** Green = best performers / high margin (Northbeam quadrant green, Lifetimely benchmark green, Bloom green margin). Red = underperformers / negative profit (Northbeam red quadrant, Putler refunds). Stars = top revenue (Putler). No competitor was observed using a divergent palette specifically for SKU-level views — single-hue gradients dominate when used at all.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: SKU-level contribution-margin visibility (vs platform vanity revenue)**
- "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, Shopify App Store, March 2024 ([conjura.md](../competitors/conjura.md))
- "The product deep dive down to SKU level is phenomenal, as well as the insights around LTV." — Amelia P., G2, December 2023 ([conjura.md](../competitors/conjura.md))
- "It gives you real visibility into profitability—way beyond Shopify's standard reporting." — The Herbtender, Shopify App Store, August 2025 ([conjura.md](../competitors/conjura.md))

**Theme: Saved views / pre-built segments**
- "Simple to use, seriously rich insights, all action-orientated." — Rapanui Clothing, Shopify App Store, October 2024 ([conjura.md](../competitors/conjura.md))
- "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." — Island Living (Singapore), Shopify App Store, November 2024 ([conjura.md](../competitors/conjura.md))

**Theme: Polished interaction + ease of comprehension**
- "Great app! Very interactive UI. Gives you full insight in product data…" — BRUNS (Sweden), Shopify App Store, January 28, 2026 ([bloom-analytics.md](../competitors/bloom-analytics.md))
- "easy statistics for products and total orders... UI is really great and comfortable to work with" — yair P., Capterra, May 14, 2019 ([putler.md](../competitors/putler.md))
- "easy to comprehend dashboards at your fingertips with actionable insights" — Jonathan J S., Capterra, October 2019 ([glew.md](../competitors/glew.md))

**Theme: Cross-source product enrichment via warehouse joins**
- "Lots of great integrations & dashboards" with praise for support team's helpfulness in creating custom reports. — tentree CA, Shopify App Store, June 9, 2022 ([daasity.md](../competitors/daasity.md))
- "exceptional reporting capabilities, transforming data visualization." — G2 review summary, 2025 ([glew.md](../competitors/glew.md))

## What users hate about this feature

**Theme: SKU-level reliability at scale**
- "After scaling, [Lifetimely] started breaking in ways that actually cost them money. The interface gave the illusion of accuracy, but under the hood, it just wasn't reliable at the SKU level." — paraphrased Reddit/community sentiment surfaced in third-party reviews ([lifetimely.md](../competitors/lifetimely.md))

**Theme: Mobile gaps for SKU drill-down**
- "Deeper feature dashboards like SKU reports aren't accessible via mobile app yet" — TrueProfit's own review blog, trueprofit.io/blog/trueprofit-review ([trueprofit.md](../competitors/trueprofit.md))

**Theme: COGS/cost-config setup burden upstream of the product surface**
- "Initial setup data-entry burden — multiple reviewers note product-cost / expense entry is heavy upfront; not a '5-minute install' tool." — BeProfit profile observation ([beprofit.md](../competitors/beprofit.md))
- "Some of our more unique data sources didn't have a pre-built Conjura data connector. Custom-built connectors took a little longer." — Andy B., Capterra, January 2019 ([conjura.md](../competitors/conjura.md))

**Theme: Feature gating / pricing friction**
- "the additional add on cause the product to be a bit limiting…but overall a useful tool for high level view" — Sur Nutrition, Shopify App Store, March 19, 2026 ([lifetimely.md](../competitors/lifetimely.md))
- "custom reports may seem restrictive, with an additional cost of $150 per hour for each one" — Shopify-store reviewer cited in search index ([glew.md](../competitors/glew.md))
- TrueProfit's Product Analytics is gated to Advanced tier ($60/mo+) ([trueprofit.md](../competitors/trueprofit.md)).

**Theme: Refresh latency on SKU surfaces**
- "Not all marketing platforms are yet automated, requiring manual entry, and some reports don't refresh in real time." — paraphrased complaint from G2/SourceForge aggregations ([daasity.md](../competitors/daasity.md))
- "It's slow! It takes forever to load... Support is slow and useless" — Paul B., Capterra, May 2024 ([glew.md](../competitors/glew.md))

## Anti-patterns observed

- **Per-SKU ad spend allocated by URL parsing only, with no fallback for non-URL-deep-linked traffic:** Conjura attributes ad spend to SKU by parsing the ad's destination URL — works only when the URL points to a product page. Anything pointing to a generic homepage falls into a literal `Ad Spend - No Product` bucket. For brands running primarily brand-awareness Meta campaigns landing on a homepage, this collapses most of their ad spend into an unallocated bucket — making the surface useful only for catalog ads / Performance Max / Shopping. Cited in [conjura.md](../competitors/conjura.md) and Conjura's own help doc.
- **Variant cost not modeled (SKU-keyed only):** Lifetimely costs are SKU/product-keyed; help docs do not describe variant cost differentiation. For apparel/size-variant brands, this means a small/large variant of the same SKU averages the same cost in the surface — incorrect for many real-world catalogs. Cited in [lifetimely.md](../competitors/lifetimely.md). TrueProfit and Bloom do support variant-level cost — direct gap.
- **SKU table without sparkline / color encoding / saved views:** "Table-only" implementations fall short — generic flat tables of revenue + margin are universally rated lower than tables with pre-built filter chips ("unprofitable products" — Conjura), star icons (Putler), or quick-analysis quadrant chips (Northbeam). The flat-table-only pattern is alluded to in BeProfit's review pattern around setup burden (the table is dense but action-less).
- **Aggregating ad spend without SKU disaggregation:** Generic "ad spend" totals on a product table without explaining which SKUs the spend is allocated against (or how) hides the per-SKU economics that the surface promises. Bloom's manual campaign-link entry (paste the ad URL on the SKU row) is a workaround, but it's user-burden masquerading as a feature. Cited in [bloom-analytics.md](../competitors/bloom-analytics.md).
- **Hidden tier gating mid-flow:** TrueProfit's Product Analytics is paywalled to the Advanced tier ($60/mo+) — Basic-tier users see the surface in nav but cannot access it. BeProfit and Bloom gate variant view / product profits to mid-tiers. Friction exposed by review patterns.
- **No SKU-level mobile parity:** TrueProfit's mobile app explicitly excludes SKU/attribution drill-downs ("aren't accessible via mobile app yet" — their own blog). Triple Whale's mobile app surfaces real-time MER/ncROAS/POAS but Product Analytics screen specifically not surfaced in mobile feature lists.
- **Refresh-button-required for filter changes:** Daasity's Flash dashboards explicitly do not auto-apply filter changes — "When you Toggle the Dashboard Filters the Data on the Dashboards will update after you click the Refresh Button." For a per-SKU filter exploration use case, this is high-friction.

## Open questions / data gaps

- **Conjura Product Deepdive 360° UI is not disclosed in marketing pages.** Only the marketing-page description ("visual command centre for tracking individual product performance") is public — actual layout, tile structure, and interaction model would need a paid/trial signup to capture.
- **Triple Whale Product Analytics screen-level UI is not directly observable** — KB pages 403 to WebFetch (anti-bot). Column list, chart types, and drill behavior all need a free-tier or trial signup.
- **Northbeam Product Analytics scatter is documented in their docs** (https://docs.northbeam.io/docs/product-analytics) but no public screenshot was captured — quadrant color hex tokens, bubble hover state, and exact label rendering are inferred from prose.
- **Bloom Analytics Profit Map orientation, node-count, and hover state are undocumented in public docs** — no `profit-map.md` page exists in their docs sitemap (404 confirmed).
- **Lifetimely's noodle/Sankey UI** color tokens, bin width, and band-thickness scaling are not directly observable from public sources.
- **Glew Product Analytics screen-level UI** is gated behind sales-led demo; FAQ confirms the navigation path but tile/column structure is not visible publicly.
- **Whether any competitor surfaces GSC organic-search performance per SKU/product page** is not visible from public sources — none of the profiles describe a "queries per product page" cross-reference. This may be a real whitespace for Nexstage's GSC source badge thesis.
- **Repeat-rate-by-first-product as a column in the main SKU table** is not surfaced as a concrete column in any of the profiles read; it appears only in cohort-side surfaces (Conjura LTV Analysis, Lifetimely Cohort Analysis filtered by first-product, Peel Product Popularity by Order Number). The merchant-facing question "which SKUs drive repeat?" is partially served, never natively a column on the main product surface.

## Notes for Nexstage (observations only — NOT recommendations)

- **The sortable wide-table-with-margin-column pattern is the universal baseline** (9/14 competitors observed). Differentiation lives in the affordances on top — saved-views as filter chips (Conjura), quadrant scatter (Northbeam), variant-expand-in-place (Bloom), 80/20 chart adjacent (Putler), star icons on top revenue (Putler), breakeven-ROAS column (StoreHero), inline campaign-link paste (Bloom). The table itself is table-stakes; the interaction surface around it is the wedge.
- **SKU-level ad-spend attribution by URL is implemented by 2 competitors out of 14** (Conjura natively, Bloom via manual campaign-link entry). Conjura's gap (homepage-landing ads collapse to "No Product") is real and bounded — this methodology only works for Performance Max / Google Shopping / deep-linked Meta. Nexstage's MetricSourceResolver has the inputs to do the same parsing; whether to expose the unallocated bucket as a literal column is a design call.
- **Northbeam's 4-quadrant scatter with index-normalized 1–100 axes is the only quadrant viz observed** in this category. Index normalization solves the universal "ROAS distributions are too wide to plot raw" problem. No SMB-tier competitor has copied it. Worth noting for any product-performance scatter Nexstage might explore.
- **Bloom's Profit Map (tree-graph)** is the only tree-style decomposition viz across the 14 profiles, and even Bloom's own docs don't document its layout — pure marketing differentiation. If Nexstage builds a profit-decomposition view, the market expects "a tree" but no one has actually shipped a documented one.
- **Variant-level COGS support is split:** TrueProfit and Bloom explicitly support variant-level; Lifetimely is SKU-keyed only. Apparel/size brands hit this. Direct opening if Nexstage's `cogs_per_variant` schema is in place.
- **Per-SKU breakeven-ROAS column is StoreHero's distinctive primitive** (`1 / contribution_margin`). It converts a margin number into an ad-buyer's actionable target. CLAUDE.md's "ratios are never stored" rule means Nexstage would compute this on the fly — fine, since the table render is the view layer.
- **Repeat-purchase-rate as a SKU-level column is whitespace.** Every profile treats first-product cohort behavior as a separate cohort surface, not a column on the SKU table. The user question "which SKUs drive repeat?" is structurally underserved — the data exists in `daily_snapshot_products` × first-product cohort joins.
- **Conjura's Purchase Patterns (5-column funnel of 1st–5th order with click-to-filter)** is the most novel IA pattern in the category. The closest peer is Peel's "Product Popularity by Order Number" template. Lifetimely's noodle is a different (flow) take on the same question. Three competitors, three different visualizations — no clear convention.
- **Putler's 80/20 Breakdown Chart** (concentration ratio over time) is unique and arguably the most direct visual answer to "is my catalog getting more or less concentrated?" — relevant for SMBs evaluating SKU-rationalization decisions.
- **Filter-chip-as-saved-view (Conjura's "unprofitable products / slow movers / items selling out") is high-leverage UX** — turns a power-user segment builder into a one-click answer for the merchant questions actually being asked. Cheap to implement on top of any existing segment infra.
- **Mobile parity for SKU surfaces is universally weak.** TrueProfit explicitly excludes it; Triple Whale's mobile feature list does not call out Product Analytics. If Nexstage ships mobile, SKU drill-down is a clear differentiator.
- **GSC × product-page cross-reference is not implemented by any competitor observed** — Nexstage's 6-source thesis (Real, Store, Facebook, Google, GSC, GA4) puts GSC alongside ad sources; surfacing GSC organic clicks per product URL on the SKU row would be a direct extension of the source-badge model into the product surface, and no peer has shipped it.
- **"Frequently bought together" / market-basket data** is surfaced by 2 competitors (Putler card, Peel template). Underserved relative to its merchandising utility.
- **Most competitors gate their richest product surface behind a mid-tier paywall** (TrueProfit Advanced, Conjura Grow $299, Glew Plus, Triple Whale Advanced+). Free-tier product surfaces tend to be a "top products" card on overview, not a dedicated table.
