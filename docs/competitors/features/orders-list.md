---
name: Orders list
slug: orders-list
purpose: Give merchants a single filterable, exportable grid of every order with revenue, cost, and profit columns so they can find specific orders, audit attribution/refunds, and pull lists for accountants or ops.
nexstage_pages: orders, performance, profit
researched_on: 2026-04-28
competitors_covered: metorik, putler, shopify-native, glew, trueprofit, beprofit, bloom-analytics, woocommerce-native
sources:
  - ../competitors/metorik.md
  - ../competitors/putler.md
  - ../competitors/shopify-native.md
  - ../competitors/glew.md
  - ../competitors/trueprofit.md
  - ../competitors/beprofit.md
  - ../competitors/bloom-analytics.md
  - ../competitors/woocommerce-native.md
  - https://help.metorik.com/article/177-customer-reports
  - https://metorik.com/features/segmenting
  - https://www.putler.com/ecommerce-transaction-management
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types
  - https://docs.bloomanalytics.io/order-profits.md
  - https://woocommerce.com/document/woocommerce-analytics/orders-report/
---

## What is this feature

Every ecommerce platform records orders. The "orders list" feature is the searchable, sortable, filterable grid through which a merchant goes from "I think there was something weird about Tuesday's $480 order from Texas" or "the accountant wants every refunded order from Q1" to actually pulling that record in seconds. It is also the canvas where third-party tools layer the columns native platforms don't have — true per-order COGS, allocated ad spend, blended profit, attributed channel, gateway fees pulled from the actual feed (not formula-estimated), and country/zone-resolved shipping cost. The native Shopify and WooCommerce orders tables answer "what did this order contain?"; the third-party orders tables answer "did this order make us money, and through which channel?"

For SMB Shopify/Woo owners specifically, this surface matters because: (1) it is the most-used operational screen after the order edit page itself — for refunds, customer-service lookup, audit, and CSV-for-accountant workflows; (2) it is where attribution and cost configuration are observable per-row, exposing whether the cost engine is working at all; (3) it is the only surface where merchants reliably catch "unprofitable orders" — the $35 sale that cost $42 to fulfill once shipping zones, packaging, and ad spend are loaded onto it. Every paid analytics tool in the SMB stack has at least a thin version of it; the differentiation is in column depth, segment-saving, filter expressiveness, and export behavior.

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `orders.id`, `orders.name` (#1234), `orders.created_at`, `orders.updated_at`, `orders.financial_status`, `orders.fulfillment_status`, `orders.total_price`, `orders.subtotal_price`, `orders.total_tax`, `orders.total_discounts`, `orders.total_shipping`, `orders.currency`, `orders.customer.id`, `orders.customer.first_order_date` (for new-vs-returning), `orders.line_items[].product_id`, `orders.line_items[].variant_id`, `orders.line_items[].quantity`, `orders.line_items[].price`, `orders.line_items[].sku`, `orders.refunds[].amount`, `orders.refunds[].created_at`, `orders.shipping_address.country`, `orders.shipping_address.city`, `orders.shipping_address.zip`, `orders.payment_gateway_names`, `orders.transactions[].fee` (Shopify Payments only), `orders.discount_applications[].code`, `orders.note_attributes` / `orders.landing_site` / `orders.referring_site` / order-attribution UTM bag.
- **Source: WooCommerce** — `wp_wc_orders.id`, `wp_wc_orders.status`, `wp_wc_orders.date_created_gmt`, `wp_wc_orders.date_paid_gmt`, `wp_wc_order_items` line items, `wp_wc_order_itemmeta` (variation, COGS where set), `wp_woocommerce_order_items` refunds, `wc_order_attribution_*` UTM/source/channel/device meta (added by Order Attribution extension), `_payment_method`, `_payment_method_title`, `_billing_country`, `_shipping_country`, `_order_currency`, `_order_total`, `_order_tax`.
- **Source: Meta Ads / Google Ads / TikTok Ads / Bing Ads / Pinterest / Snapchat** — `campaigns.spend`, `adsets.spend`, `ads.spend` for the day of the order; click/view events when a server-side pixel exists; UTM `source/medium/campaign/content/term`. Required to compute per-order allocated ad spend.
- **Source: Klaviyo** — campaign-level revenue/clicks for email-attributed-order ribboning (Bloom precedent).
- **Source: Shipping carriers (ShipStation, ShipBob, Shippo, ShippingEasy, Shipwire, ShipHero, FedEx)** — actual per-order shipping cost (replacing formula estimates).
- **Source: Print-on-demand / dropship (Printful, Printify, Gelato, CJ Dropshipping)** — auto-synced per-variant COGS.
- **Source: Payment gateways (Stripe, PayPal, Braintree, Razorpay, Authorize.Net, 2Checkout, SagePay)** — actual transaction fees per charge (rather than formula).
- **Source: User-input** — per-product / per-variant COGS (CSV, Google Sheets, manual), COGS Zones (geographic per-destination COGS), custom recurring/one-time operating expenses, handling cost rules, channel-fee rules, tariff cost (Bloom 2025-2026 addition).
- **Source: Computed** — `net_profit_per_order = total_price − refunds − cogs − shipping_cost − handling − channel_fee − tariff − transaction_fee − allocated_ad_spend − allocated_opex` (Bloom, BeProfit, TrueProfit formula); `customer_type ∈ {new, returning}` based on first-order date; `attributed_channel` via UTM-mapping rules.

## Data outputs (what's typically displayed)

- **KPI strip above grid:** Total amount (sum of selected rows / range), Order count, Total fees, Total taxes, Total refunds, Total profit (when COGS configured). Putler shows these as four hero tiles; BeProfit shows lifetime profit + ROAS + POAS.
- **Per-row identity columns:** Order # / order ID, Created at (date+time), Status (financial + fulfillment), Customer name + email, Customer type (new vs returning), Country / shipping city.
- **Per-row revenue columns:** Items count, Gross sales, Discounts, Refunds, Tax, Shipping charged, Net sales, Total sales.
- **Per-row cost columns (third-party only):** COGS / Product Variant COGS, Shipping Cost (carrier-real or rule-based), Handling Cost, Gateway Cost / Transaction Fee, Channel Fee, Tariff Cost (Bloom), Allocated Ad Spend, Allocated OPEX.
- **Per-row profit columns:** Shopify Gross Profit, Shopify Gross Margin %, Contribution Margin 1 + %, Contribution Margin 2 + %, Net Profit, Net Profit Margin %.
- **Per-row attribution columns:** Channel, Source, Campaign, Device, UTM medium, Coupon code applied, Referring site / landing page.
- **Filters:** Date range + comparison toggle, Order Status, Payment Method / Gateway, Fulfillment Status, Country / Region, Channel / Source, Customer Type (new vs returning), Product / Variant / SKU, Coupon, Refund flag, Tax Rate, Profit-positive vs unprofitable, Amount range, Tag.
- **Segment / saved-filter primitives:** Reusable named segments that apply to the grid AND other reports (Metorik); per-user persisted column-visibility (Woo Native).
- **Export options:** CSV with column picker, Excel, PDF (Bloom), Scheduled CSV via email or Slack (Metorik, Glew), direct download vs async-emailed-link (Woo native is async-only on big ranges — universally hated).

## How competitors implement this

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Reports > Orders, plus the same grid surfaces as the result canvas of every Segment built in the Segment Builder.
- **Visualization:** Table grid with summary cards on top; segment-builder side panel as the dominant filter UX rather than per-column inline filters.
- **Layout (prose):** "Top: summary metric cards (orders, revenue, AOV). Left rail / popover: Segment Builder filter list (rows that combine into AND/OR groups). Main canvas: live-updating order table reflecting the segment as filters are added. Bottom: pagination + export entry point."
- **Specific UI:** "Filter-builder UI 'more like a conversation than a database query' (their copy). Add filter row, group rows, AND/OR at row + group level. Live result count updates 'in seconds.' Saved segments named, shared via URL, and reusable across every report — not siloed inside the orders screen." Drag-and-drop column picker on Exports — "user reorders/toggles columns to include in CSV. Schedule recurring exports (daily/weekly/etc.) delivered via email or Slack. WooCommerce custom fields can be added as columns."
- **Filters:** 500+ filter criteria across orders, customers, subscriptions, products, variations, categories, coupons, carts. Includes WooCommerce custom fields, custom meta, fulfillment status, payment method, shipping method, frequency/recency/monetary, custom date math.
- **Data shown:** Orders table with status, payment method, shipping method, customer type (new vs returning), location; per-segment subtotals.
- **Interactions:** Add / group filters; save segment with name; share via URL; apply saved segment to any other report (cohorts, customer, product, retention); auto-recurring scheduled CSV export of the segment results.
- **Why it works (from reviews/observations):** "Exporting through Metorik has been an absolute game-changer. We can rely on going into Metorik, hitting export, and all correct data being included." — Brian Zarlenga, General Manager, Output. Segments-as-first-class-primitive is repeatedly cited as the unlock vs siloed per-report filters.
- **Source:** [metorik.md](../competitors/metorik.md), https://metorik.com/features/segmenting, https://help.metorik.com/article/177-customer-reports

### Putler ([profile](../competitors/putler.md))
- **Surface:** Sidebar > Transactions Dashboard.
- **Visualization:** Unified searchable transaction list with KPI tile strip on top; color-coded rows (sales green, refunds red).
- **Layout (prose):** "Top: KPI bar with four tiles — Total amount (across selected range), Transaction count, Fees consolidated across all gateways, Taxes. Below: filter strip with Location ('continent down to street level'), Product, Status, Type. Search bar accepts customer name, email, or transaction ID. Main canvas: unified list of transactions across every connected source — sales rendered green, refunds rendered red. Right side of each row: inline Refund button. Bottom: standard pagination."
- **Specific UI:** "Color-coded rows: sales green, refunds red. Inline refund button per row. Filter chips combine freely. Detail view shows net revenue, refunds, shipping, taxes, fees, discounts, and commissions broken out as separate line items." Search: "results in seconds, not after a loading screen" (their copy).
- **Filters:** Date range, Location ("continent → country → state → city → street"), Product, Status (completed / refunded / pending / failed), Type (sale / refund), Amount range. Cross-platform — same grid spans Shopify + Woo + Stripe + PayPal + Amazon + eBay etc.
- **Data shown:** Total amount, count, fees, taxes per range. Per-row: net amount, refund/shipping/tax/fee/discount/commission line items, currency-normalized to base currency.
- **Interactions:** Click row → transaction detail. Click "Refund" → modal for full or partial refund (works for PayPal, Stripe, Shopify — write access). "Find the transaction, click refund, confirm. Done." Export CSV with currency conversion + timezone normalization + dedup pre-applied.
- **Why it works (from reviews/observations):** Multi-gateway dedup is the differentiator: "Putler is great for combining sales stats, finding customer data, getting things sorted if you use multiple payment platforms especially. Paypal and Stripe, good dashboard." — Jake (@hnsight_wor), wordpress.org, July 25, 2025. In-app refund processing is repeatedly cited; "5 minutes via gateway dashboards becomes 5 seconds in Putler."
- **Source:** [putler.md](../competitors/putler.md), https://www.putler.com/ecommerce-transaction-management

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Admin > Orders (operational grid) and Analytics > Reports > Sales / Orders (analytical grid). The two are intentionally separate — operational vs. analytical.
- **Visualization:** Sortable table with chip filters; Analytics version adds a chart-on-top + metric/dimension chip configurator.
- **Layout (prose):** "Top: date-range picker + comparison toggle. Below: configuration panel pattern with metric chips and dimension chips on a side panel. Main canvas: chart on top (line/bar/table switcher), sortable table below with one row per order. Top-of-page button: 'Create custom report.'"
- **Specific UI:** "Visualization-type switcher (line / bar / table); metric chips and dimension chips with add/remove; saved-report and scheduled-export buttons (Advanced+); export to CSV with the documented 1 MB / 50-record / email-on-large-export caps." Reports library spans named sub-reports: Total sales over time, Sales by product, Sales by product variant SKU, Sales by product vendor, Sales by discount code, Sales by traffic referrer, Sales by channel, Sales by billing location, Sales by POS staff.
- **Filters:** Date, Order status, Channel, Country / billing location, Discount code, Vendor, Product / Variant / SKU, POS staff. ShopifyQL (Advanced+) lets users `FROM orders SHOW … WHERE … GROUP BY …` directly.
- **Data shown:** Total sales, Gross sales, Net sales, Orders, AOV, Tax, Tip, Shipping, Refund, Sessions; Profit reports (Advanced+) add Gross profit by product / variant / Net sales without cost recorded vs. Net sales with cost recorded.
- **Interactions:** Drill-down from a row to a filtered exploration; save a configured report as a new custom report; pin to Overview as a custom card; schedule export (Advanced+); Sidekick chat translates plain-English to ShopifyQL and saves the result as an Exploration.
- **Why it works (from reviews/observations):** Bundled-in convenience is the praise vector — merchants praise that it's "always already there" rather than depth. Negative reviews focus on the CSV caps and the Advanced-tier paywall ($299/mo) for custom reports + ShopifyQL.
- **Source:** [shopify-native.md](../competitors/shopify-native.md), https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types

### Glew ([profile](../competitors/glew.md))
- **Surface:** Order Analytics / Orders tab; Custom Report Builder (Looker) for power users on Glew Plus.
- **Visualization:** Standard tabular grid; "Compare" pivot views via Looker dashboards; aggregated multi-brand consolidation as the headline IA.
- **Layout (prose):** "Top: KPI Highlights / Performance Channels rollup tiles. Main canvas: order list with status, shipping costs, COGS, profit margin columns. Right rail (Plus tier): Looker drag-and-drop builder with metric/dimension catalog and pre-built joins. Multi-brand mode shows aggregated rollup across all connected stores via top-menu store-name dropdown."
- **Specific UI:** UI details for the order grid itself were NOT directly observable from public sources — the application sits behind a sales-led demo gate. Confirmed via FAQ and feature pages: "an instant, unified view of sales, marketing, customers and products" with "Advanced data filtering capabilities," "Customizable report builder," and "300+ unique filtering options" on Glew Pro. CSV export of "only viewed metrics" is confirmed.
- **Filters:** "300+ unique filtering options" claim on Pro tier; "55+ filterable metrics and 15 product-specific metrics" on Customer Segments. Cross-source filtering (orders + Klaviyo + Yotpo + Loyalty Lion + Zendesk + ReCharge) inside a single segment.
- **Data shown:** Order status, shipping costs, COGS, profit margins; aggregated rollup across Shopify + BigCommerce + Woo + Magento + Amazon + eBay + Walmart in one grid for multi-brand operators.
- **Interactions:** Custom report builder via bundled Looker license (Plus); BI Tunnel for SQL passthrough to dedicated AWS Redshift warehouse (Plus / Enterprise); scheduled email/Slack report distribution; segment sync to Klaviyo as audiences. Custom one-off report builds carry an additional "$150/hour" charge per multiple Capterra/Trustpilot complaints.
- **Why it works (from reviews/observations):** "exceptional reporting capabilities, transforming data visualization and streamlining business analytics effortlessly" — G2 review summary, 2025. The bundled Looker license is the agency-bait — most competitors either ship a proprietary builder or make customers BYO Looker/Tableau seat. Negative pattern: "custom reports may seem restrictive, with an additional cost of $150 per hour for each one" — Shopify-store reviewer cited in search index.
- **Source:** [glew.md](../competitors/glew.md), https://www.glew.io/articles/glew-looker-partnership

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Inferred under Profit Dashboard / Product Analytics; per-order drill-down referenced in walkthroughs but no dedicated public screenshot.
- **Visualization:** SKU/variant-level table with per-row margin %; the order-level grid itself is not publicly screenshotted.
- **Layout (prose):** "UI details from `trueprofit.io/solutions/product-analytics` and the blog. Tabular SKU/variant view with per-product net profit margin displayed as a percentage (the walkthrough blog cites '58.95% and 45.23%' as live examples). Per-row breakdown includes ad spend allocated to that product, page views, add-to-cart rate, conversion rate."
- **Specific UI:** Per-row columns include cost-breakdown (COGS, shipping, ad spend, fees) and funnel-metric columns (views, ATC, CVR). Variant-level granularity confirmed. Sortable columns implied. Drill-down behavior (click row → detail) not confirmed from public sources. UI details not available — only feature description seen on marketing page.
- **Filters:** Date range, store (multi-store rollup vs. single store) — the rest unverified from public sources.
- **Data shown:** Net profit, profit margin %, ad spend per product, COGS, shipping, page views, ATC rate, conversion rate. COGS Zones (geographic per-destination COGS) layer in differentiated unit cost by destination.
- **Interactions:** Sortable columns (implied). Two-mode attribution toggle ("Last-clicked Purchases" vs "Assisted Purchases") sits at the Marketing Attribution screen, gated to $200/mo Enterprise.
- **Why it works (from reviews/observations):** "tells you exactly where you are loosing money and how to fix it" — Frome (Canada), Shopify App Store, February 4, 2026. The per-order overage complaint and the "transaction fees calculated by formula" complaint are both raised against the cost rows: "The transaction fees are calculated by a formula although this can be pulled directly from Shopify [...] Beprofit handle this correctly" — 1-star Shopify reviewer cited via Reputon aggregation.
- **Source:** [trueprofit.md](../competitors/trueprofit.md), https://trueprofit.io/solutions/product-analytics

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Sidebar > Orders (raw data page per FAQ) and Reports > Orders profit drill-down.
- **Visualization:** Sortable order table with profit-color coding or top/bottom toggle; per-order P&L expansion.
- **Layout (prose):** "Top: filter strip with date-range picker (presets Daily / Weekly / Monthly per screenshot caption). Main canvas: sortable orders table with profit-color coding implied. Top of page: KPI row featuring Lifetime Profit, Retention, ROAS, POAS as headline numbers. Per-row: revenue, COGS, shipping, fees, taxes, marketing cost (allocated), net profit."
- **Specific UI:** Per Shopify App Store screenshot #5 caption: "Identify your unprofitable orders and most profitable orders" — implies sortable orders table with profit-color coding or 'top/bottom' toggle. Screenshot #6: "Compare your shop's performance across countries, sales channels, and shops" (multi-store comparison). Screenshot #8: "Analyze profit by order, country, shop, and platform" — multi-dimensional pivot.
- **Filters:** Order Status, Sales Channel, Items Amount, Country (per Conditional Expense Engine fields). Advanced filters gated to Pro tier+. UTM Attribution screen exposes a separate scrollable list with per-UTM-group source-mapping dropdown.
- **Data shown:** Per-order revenue, COGS, shipping, fees, taxes, allocated marketing cost, net profit. POAS (Profit on Ad Spend) elevated to dashboard hero alongside ROAS. CM1/CM2/CM3 contribution margins implied.
- **Interactions:** Sort by profit ascending (unprofitable) / descending (top); per-order expansion. Export and customizable dashboard (Pro+).
- **Why it works (from reviews/observations):** "BeProfit is by far the most accurate and analytical one we've used." — Braneit Beauty, Shopify App Store, February 12, 2026. Negative pattern on the orders surface: "Not calculating the profit correctly. Calculation Preferences section not working properly." — Celluweg, Shopify App Store, January 17, 2026. Documented Google Ads under-counting because "BeProfit uses last-click attribution" with UTM-only matching for Google specifically — orders with no UTM get zero ad spend allocated, inflating profit on the row.
- **Source:** [beprofit.md](../competitors/beprofit.md), Shopify App Store screenshots #5–#9

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Sidebar > Order Profits.
- **Visualization:** Wide per-order table with date-range filter and order-name search dropdown; column-selector top-right; export to Excel / CSV / PDF.
- **Layout (prose):** "Top: date-range picker + order-name search dropdown. Right side: column selector (top-right corner) and filter icon nearby. Main canvas: wide per-order table with one row per order. Per-row: a long horizontal column set covering identity, revenue, refund, cost, contribution-margin, and explicit cost-component columns. Bottom: pagination + 'edit operational costs and reimport updated files for recalculation' workflow."
- **Specific UI:** Column selector (top-right corner); filter icon; date range picker; export menu (Excel / CSV / PDF). Toggle "View by product variants" expands rows to variant level on Product Profits — same pattern likely applied per-order. "Edit operational costs and reimport updated files for recalculation" workflow confirmed in docs.
- **Filters:** Order-name search dropdown; date range; column-visibility filter; (full filter dimension list for Order Profits not exhaustively documented publicly — Product Profits filters by Product Name, Variant Name, Product Type, SKU, Tags, Status, ACTIVE flag).
- **Data shown (verbatim from docs):** Created At, Items, Gross Sales, Discounts, Refunds, Net Sales, Tax, Total Sales, **Shopify Gross Profit, Shopify Gross Margin %**, **Contribution Margin 1 (+%)**, **Contribution Margin 2 (+%)**, **Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost**.
- **Interactions:** Column selector to add/remove the cost-component columns; export to Excel / CSV / PDF; manual campaign-link entry for product-level ROAS attribution; multi-currency reporting via account-level Reporting Currency.
- **Why it works (from reviews/observations):** "Great app! Very interactive UI. Gives you full insight in product data…" — BRUNS (Sweden), Shopify App Store, January 28, 2026. The 6-cost-column breakdown (Gateway / Shipping / COGS / Handling / Channel Fee / Tariff Cost) is the most explicit cost decomposition in the Shopify SMB profit category as of 2026 — Tariff Cost is a distinguishing 2025-2026 addition. Review depth shallow (only 15 App Store reviews), so the praise signal is thin.
- **Source:** [bloom-analytics.md](../competitors/bloom-analytics.md), https://docs.bloomanalytics.io/order-profits.md

### WooCommerce Native ([profile](../competitors/woocommerce-native.md))
- **Surface:** `wp-admin > Analytics > Orders`.
- **Visualization:** Standard chassis used by every native Analytics report — date-range bar, summary cards, single-axis line chart, sortable table at the bottom.
- **Layout (prose):** "Top: date-range bar + comparison toggle. Above the table: horizontal row of summary cards (Orders, Net Sales, AOV, etc.) — click a card to highlight it as the chart series. Middle: line chart spanning full content width. Between chart and table: 'Advanced Filters' affordance. Bottom: sortable table with one row per order, sorted by order date descending; refunded orders appear twice — once on the original date and once on the refund date with a return-arrow icon. Pagination + CSV 'Download' button (async-emailed for large ranges)."
- **Specific UI:** "Filters open as a stacked panel with chip-style filter rows. Each filter row has a left-side dimension dropdown (Order Status / Products / Coupon Codes / Customer Type / Refunds / Tax Rates / Product Attribute) and a right-side value selector. Match-mode radio at the top of the panel: 'Match all' vs 'Match any'. 'Add filter' button at the bottom. Refunded-order rows have a return-arrow icon so you can spot duplicates." Per-user column-visibility persistence (small but appreciated detail).
- **Filters:** Order Status, Products, Coupon Codes, Customer Type (new vs returning), Refunds, Tax Rates, Product Attribute. Match-mode toggle (Match all vs Match any).
- **Data shown:** Date, Order #, Status, Customer, Customer Type (new vs returning), Items Sold, Coupon, Net Sales. Sort axes restricted to Date / Items Sold / Net Sales (per docs).
- **Interactions:** Click order ID → routes to standard WooCommerce order edit screen; CSV "Download" button — async-emailed link rather than direct download for large ranges (universally hated). The Order Attribution extension adds five sub-views (Channel / Source / Campaign / Device / Channel+Source) with last-touch UTM data per order.
- **Why it works (from reviews/observations):** "Great tool!" — WooCommerce Marketplace listing, April 2026. Pain dominates: "A total chore because the csv or excel sheets are EMAILED TO ME NOW instead of just being downloadable the way they were before." — @jeffsbesthemp, WordPress.org support, November 2024. "Forced previous-period comparison" with no opt-out is a consistent regression complaint vs the legacy Reports screen. Stock report cannot be exported at all. Single-currency-only on the Order Attribution extension, with WooCommerce explicitly warning of "inaccurate analytics" for multi-currency stores.
- **Source:** [woocommerce-native.md](../competitors/woocommerce-native.md), https://woocommerce.com/document/woocommerce-analytics/orders-report/

## Visualization patterns observed (cross-cut)

- **Standard table grid (sortable, paginated, with summary KPIs above):** 8/8 competitors (Metorik, Putler, Shopify Native, Glew, TrueProfit, BeProfit, Bloom, Woo Native) — universal default. There is no debate about the primary pattern.
- **Color-coded rows for sales vs refunds:** 1 competitor (Putler — green sale / red refund). Most others use icons (Woo Native: return-arrow on refund duplicate row) or status pills.
- **Profit-color coding / unprofitable-row flagging:** 1 competitor (BeProfit — implied by screenshot #5 caption "Identify your unprofitable orders and most profitable orders").
- **Side-panel filter-builder with AND/OR groups (rather than chip filters):** 1 competitor (Metorik — segment builder is the dominant filter UX). Most others use chip filters (Putler, Bloom, Woo Native, Shopify Native).
- **Saved segments as cross-report primitives:** 1 competitor (Metorik — segments apply to orders, customers, products, cohort, retention reports identically). Others silo filters per report.
- **Inline action button per row (e.g., Refund):** 1 competitor (Putler — refund button writes back to gateway).
- **Column selector / column-visibility toggle:** 5/8 (Bloom, Shopify Native, Woo Native — persisted per user, BeProfit Pro+, Glew "viewed metrics only" export).
- **Column / dimension chip configurator (analytics-style, not just visibility):** 2 competitors (Shopify Native Reports library, Glew via bundled Looker).
- **Group-by toggle inside the orders grid (not dimension chips):** Several competitors (Putler — Location continent→street, BeProfit — by product / type / vendor / collection / variant on the Products grid which neighbours Orders).
- **Cost-component column breakdown (per-row Gateway / Shipping / COGS / Handling / Channel Fee / Tariff):** Only Bloom exposes the full 6-cost decomposition explicitly. BeProfit and TrueProfit show subsets (COGS + shipping + fees + ad spend); Shopify Native shows COGS-aware Gross Profit on Advanced+ but not per-cost-component breakdown; Putler shows fees consolidated across gateways. Bloom's Tariff Cost column is unique in the dataset (2025-2026 addition).
- **Per-row attribution column (channel / source / campaign / device):** Woo Native (via Order Attribution extension, last-touch only); Shopify Native (via Marketing > Analyze marketing module). Most third-party tools surface attribution in a separate Marketing report rather than as columns on the orders grid.
- **Direct CSV download vs async-emailed-link:** Direct download is the desired pattern. Async-emailed-link (Woo Native, Shopify Native on >50 records) is universally complained-about. Putler claims "currency conversion + timezone normalization + dedup pre-applied" on its CSV — meaningfully more than the others.
- **Scheduled exports to email or Slack:** Metorik, Glew, Bloom (Slack on Grow tier+). Shopify Native scheduled-exports gated to Advanced+ ($299/mo).
- **Inline refund/write-back action:** Only Putler — write access to PayPal, Stripe, Shopify gateways. All others are read-only.

Recurring conventions: status as colored pills (in stock / refunded / pending), refunded-order rows duplicated with a return-arrow icon (Woo Native canonical pattern), top-right column selector, KPI strip pinned above the grid, date-range picker with named presets ("Today / Yesterday / Week to date / Last week / Month to date / Last month / Quarter to date / Year to date / Last year") plus custom-range calendar (Woo Native). Pagination at bottom is universal.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Reliable export of "all the right columns"**
- "Exporting through Metorik has been an absolute game-changer. We can rely on going into Metorik, hitting export, and all correct data being included." — Brian Zarlenga, General Manager, Output, via [metorik.md](../competitors/metorik.md).
- "easy statistics for products and total orders... UI is really great and comfortable to work with" — yair P., Production Manager, Capterra, May 14, 2019, via [putler.md](../competitors/putler.md).
- "Easy to use…with graphs and tables, and export options." — Capterra reviewer, 2021, via [beprofit.md](../competitors/beprofit.md).

**Theme: One grid spans every gateway / store / platform**
- "Putler is great for combining sales stats, finding customer data, getting things sorted if you use multiple payment platforms especially. Paypal and Stripe, good dashboard." — Jake (@hnsight_wor), wordpress.org plugin review, July 25, 2025, via [putler.md](../competitors/putler.md).
- "I spend a lot of time getting an overview with Excel, but when I got Putler, I have an overview for Amazon, eBay, Etsy, Shopify. I don't need to do Excel anymore." — G2 reviewer cited via Putler aggregation, [putler.md](../competitors/putler.md).
- "Glew.io is solving the challenge of consolidating data from multiple platforms into a single source of truth by automating data integration and ensuring accuracy" — G2 review summary, 2025, via [glew.md](../competitors/glew.md).

**Theme: True profit visible per row, not just headline**
- "We now know exactly what we make from every sale. Thanks John and [team]." — kicksshop.nl (Netherlands), Shopify App Store, January 19, 2026, via [bloom-analytics.md](../competitors/bloom-analytics.md).
- "tells you exactly where you are loosing money and how to fix it" — Frome (Canada), Shopify App Store, February 4, 2026, via [trueprofit.md](../competitors/trueprofit.md).
- "BeProfit is by far the most accurate and analytical one we've used." — Braneit Beauty, Shopify App Store, February 12, 2026, via [beprofit.md](../competitors/beprofit.md).
- "It's a really solid profit analytics platform that finally gives us a clear picture." — Ecolino.ro, Shopify App Store, December 1, 2025, via [beprofit.md](../competitors/beprofit.md).

**Theme: Filter expressiveness without leaving the screen**
- "Not only is it fast and a huge time saver, we used Metorik to create several new customer segments and send targeted email promotions. It paid for itself several times over in the first week." — Robby McCullough, Co-Founder, Beaver Builder, via [metorik.md](../competitors/metorik.md).
- "easy to comprehend dashboards at your fingertips with actionable insights" — Jonathan J S., eCommerce Manager, Sporting Goods, Capterra Oct 2019, via [glew.md](../competitors/glew.md).

**Theme: In-grid action (refund) saves context-switch**
- (No verbatim quote isolated to refund processing in the competitor profiles, but Putler's strength callout — "5 minutes via gateway dashboards becomes 5 seconds in Putler" — and broad praise of Putler's transactions surface implicitly endorses the inline-action pattern. Source: [putler.md](../competitors/putler.md).)

## What users hate about this feature

**Theme: Forced comparison that won't turn off**
- "Every single report I try to do forces it as a comparison and I don't want a f***ing comparison report, I just want the date range I select, and that's it. Period. X to y, and that's it. Not x to y, and last year x to y." — @jeffsbesthemp, WordPress.org support, November 2024, via [woocommerce-native.md](../competitors/woocommerce-native.md).
- "Unfortunately there isn't a way to completely disable the 'previous period' at the moment. At most you can hide the graph which compares the previous period." — @mikkamp (Automattic Happiness Engineer), WordPress.org support, November 2024, via [woocommerce-native.md](../competitors/woocommerce-native.md). (Confirms the limitation rather than refuting it.)

**Theme: CSV emailed instead of downloaded; export caps**
- "A total chore because the csv or excel sheets are EMAILED TO ME NOW instead of just being downloadable the way they were before." — @jeffsbesthemp, WordPress.org support, November 2024, via [woocommerce-native.md](../competitors/woocommerce-native.md).
- "Can't export more than [a limited number of] customer records at once." — Nicolai G., Capterra, June 10, 2019, via [putler.md](../competitors/putler.md).
- "export very large records to CSV is a bit of issue" — yair P., Capterra, May 14, 2019, via [putler.md](../competitors/putler.md).

**Theme: Per-row profit math is wrong because the cost feed is wrong**
- "The transaction fees are calculated by a formula although this can be pulled directly from Shopify [...] Beprofit handle this correctly" — 1-star Shopify reviewer cited via Reputon aggregation, via [trueprofit.md](../competitors/trueprofit.md).
- "Not calculating the profit correctly. Calculation Preferences section not working properly." — Celluweg, Shopify App Store, January 17, 2026, via [beprofit.md](../competitors/beprofit.md).
- "Attention all business owners! It's essential to double-check the accuracy of your refund versus returns data. Shopify's financial summary counts all return requests as issued refunds, which can be misleading. Not all return requests are accepted, and not all approved returns end up refunded. Stay vigilant to ensure more precise results. I've discussed this concern with the TrueProfit team, but they believe making changes now isn't practical. So, choose wisely." — Apollo Moda (USA), Shopify App Store, May 3, 2024 (2-star), via [trueprofit.md](../competitors/trueprofit.md).
- "BeProfit only 'counts' ad spend that can be attributed via UTMs / converted traffic." — A Farley Country Attire, Shopify App Store, January 7, 2026, via [beprofit.md](../competitors/beprofit.md). (Drives the "~15% of Google Ads spend imported" complaint — orders without a UTM get zero allocated ad spend.)

**Theme: Numbers don't match between this grid and the next**
- "My Woocommerce Reports show the correct data (84 orders, 119 products purchased, 14000eur revenue) but when I go to Woocommerce Analytics I see insane numbers (600 orders, 900 products purchased, 98000eur revenue). Shows orders on the dates that had 0 orders." — @krdza93 (Danilo Krdzic), WordPress.org support, July 2023, via [woocommerce-native.md](../competitors/woocommerce-native.md).
- "Net Revenue totals on the Revenue and Orders pages match each other, but do not match the Net Revenue on the Product page or Categories page, even though the Product and Category page Net revenues match each other." — GitHub issue #2855, woocommerce-admin, via [woocommerce-native.md](../competitors/woocommerce-native.md).
- "When searching all of January, the orders and metrics match exactly what the Overview report shows, but if the date range is changed to the last two days of January, the report results basically double." — GitHub issue #6529, woocommerce-admin, via [woocommerce-native.md](../competitors/woocommerce-native.md).

**Theme: Slow load on large catalogs / multi-thousand-order ranges**
- "It's slow! It takes forever to load... Support is slow and useless" — Paul B., Manager, Retail, Capterra May 2024, via [glew.md](../competitors/glew.md).
- "Painfully slow at first run; for under 1k orders with order attribution it takes over 1 hour" — paraphrased complaint pattern surfaced by Putler's analysis of WordPress.org and forum threads, 2026, via [woocommerce-native.md](../competitors/woocommerce-native.md).
- "the data import was a bit slow" — Verified Reviewer, UX Designer, Capterra, October 31, 2021, via [putler.md](../competitors/putler.md).

**Theme: Multi-currency + multi-platform numbers can't be trusted**
- "Multi-currency functionality is not supported, which may result in inaccurate analytics for stores operating in multiple currencies." — Marketplace listing for the Order Attribution extension (verbatim warning from WooCommerce themselves), via [woocommerce-native.md](../competitors/woocommerce-native.md).

**Theme: Custom report builds are paywalled / hourly-charged**
- "custom reports may seem restrictive, with an additional cost of $150 per hour for each one" — Shopify-store reviewer cited in search index, via [glew.md](../competitors/glew.md).

## Anti-patterns observed

- **Async-emailed CSV instead of direct download.** Woo Native (and Shopify Native above documented caps) trigger an email-with-link flow when the CSV crosses an internal threshold. Multiple verbatim "I HATE ANALYTICS SOOOOO MUCH" rants on WordPress.org cite this specifically. The pattern fails because merchants are exporting *to act* — to send to an accountant, paste into a spreadsheet, run a refund — not to receive an email an hour later.
- **Forced comparison row that can't be disabled.** Woo Native renders a "previous period" series on every report with no opt-out — confirmed by an Automattic reply on the support forum. Comparison is good as a default, but undismissable forced comparison is a regression vs. the legacy Reports screen and surfaces in the highest-emotion negative reviews.
- **Per-order ad spend allocation that drops un-UTM'd orders to zero.** BeProfit attributes Google Ads spend only via UTM-matched orders, with the verbatim help-center justification "for Google specifically: we attribute based on UTM data only to avoid attributing SEO-based orders to the Google Ads platform." Net effect: ~15% of real Google Ads spend gets imported, inflating profit on every Google-attributed row. The fix (allocating spend at platform level even when row-level UTM is missing) is structurally available but is a deliberate product choice they made.
- **Transaction fees calculated by formula instead of pulled from the gateway feed.** TrueProfit's documented complaint vs. BeProfit's correct behaviour — both occupy the same niche, but the formulaic-fee approach drifts from reality and gets called out specifically. The grid is the surface where the drift becomes visible (per-row fee column doesn't match the Stripe/Shopify Payments dashboard).
- **Refund-request counted as refund-issued.** Documented limitation in TrueProfit; the team told a reviewer changing it "isn't practical." On the orders grid this manifests as duplicate refund rows for orders where the customer never actually received money back.
- **Stock report cannot be exported at all.** Woo Native ships every other report with CSV export but excludes Stock — confirmed via Putler's gap analysis. The orders surface exports fine; the inventory neighbour does not, breaking the merchant's mental model that "every grid has a download button."
- **Custom-report-builder gated to a top tier with hourly-rate add-ons.** Glew's Pro tier is dashboard-only; Looker + warehouse access is Plus, and even there, one-off custom report builds carry an additional "$150/hour" charge. Reviewers consistently flag this as a bait-and-switch vs. the marketing copy.
- **Each report has its own filter set instead of a shared segment primitive.** Most competitors silo filters per report; only Metorik treats segments as cross-report primitives. The anti-pattern surfaces when a merchant builds a complex segment on the orders grid and discovers they have to recreate it on the customers grid and the cohort grid.
- **Deleted orders persist in the analytics tables.** Woo Native bug — `wc_order_stats` and `wc_order_product_lookup` retain rows for deleted orders until lookup tables are manually emptied. Merchant sees "ghost orders" on the grid that don't exist anywhere else. Source: [woocommerce-native.md](../competitors/woocommerce-native.md), GitHub issues.

## Open questions / data gaps

- **Glew's order grid UI is paywalled behind a sales-led demo gate.** Confirmed feature list and "300+ unique filtering options" claim, but the actual column set, filter UX, and per-row interaction model could not be observed from public sources. A trial / demo would resolve this.
- **TrueProfit's marketing pages use lazy-loaded image carousels that don't render via WebFetch.** Their order-level grid is described in prose only; clean UI captures would require a logged-in trial account or App Store gallery scraping. UI specifics for the order-grid filter set, column reordering, and drill-down behaviour are unverified.
- **Bloom's Profit Map (their headline visual) has no `profit-map.md` page in the docs sitemap (404 confirmed).** Adjacent to the Order Profits grid; unclear whether Profit Map is its own surface or rebranded inside Overview.
- **BeProfit's customizable dashboard, profit simulators, and profit insights screens** are not screenshotted publicly beyond the 9-image App Store carousel. Per-row inline filter UX cannot be verified.
- **Shopify Native's Marketing > Analyze marketing screen** is paywalled inside the admin; UI details for how attribution columns surface on the orders grid (vs. as a separate report) are described only in marketing copy.
- **Inline refund write-back outside Putler.** Putler is the only profile in this batch that documents in-grid refund processing (PayPal / Stripe / Shopify). Whether any other competitor has shipped a similar in-grid action since their last research pass is unverified.
- **Per-order multi-touch attribution columns.** All competitors in this batch surface single-touch attribution on the order grid (last-click for BeProfit / Woo Order Attribution; "Last-clicked + Assisted" toggle on TrueProfit's Marketing Attribution screen but gated to $200/mo). Whether any competitor surfaces a per-order multi-touch journey path *as a column on the grid* (rather than as a separate attribution report) was not observed.

## Notes for Nexstage (observations only — NOT recommendations)

- **Bloom's per-order cost decomposition is the most explicit in the SMB Shopify segment as of 2026.** The exact column set — Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost — is reproducible as a specification target. Tariff Cost in particular is a 2025-2026 addition driven by current trade policy and is unique among the 8 competitors profiled here.
- **Metorik's "segments are first-class, cross-report primitives" pattern is the high-water mark for filter UX** in this category. Every other competitor silos filters per report. Metorik's pitch — "more like a conversation than a database query" — is a reasonable design north star, and "live result count updates in seconds as filters are added" is a measurable UX KPI.
- **Putler's color-coded sale-vs-refund rows + inline refund button** is a unique pattern in the dataset. No other competitor in the eight ships green/red row coding or in-grid write-back to gateways. Whether Nexstage wants write access at all is a separate decision; the row-coding pattern is independent and cheap.
- **Shopify Native's separation of operational orders grid (admin > Orders) from analytical orders grid (Analytics > Reports > Orders)** is a deliberate IA choice. Nexstage's `OrdersController` is currently named like an analytical surface; worth noting that Shopify users are trained on two distinct surfaces with different mental models — one for refunds/fulfillment, one for analysis.
- **Per-row attribution columns are rare on the orders grid** — most competitors surface attribution in a separate Marketing report. Woo Native's Order Attribution extension is the cleanest example of in-grid Channel/Source/Campaign/Device columns, and it's free + native. Last-touch only. Nexstage's `MetricSourceResolver` is structurally well-positioned to expose multiple attribution lenses as togglable columns rather than a separate screen.
- **CSV export is the canonical hated surface.** Async-emailed-link, export caps, missing scheduled exports, and "Stock report can't be exported at all" are all in the verbatim hate quotes. Direct download for any reasonable range, scheduled exports to email + Slack at a low tier, and column-picker-on-export are all low-effort differentiators against Woo Native + Shopify Native.
- **"Numbers don't match between this grid and the next" is a chronic Woo Native bug pattern** with multiple GitHub issues filed. Nexstage's snapshot architecture (`SnapshotBuilderService` as the only writer; `daily_snapshots`, `hourly_snapshots`, `daily_snapshot_products`) sidesteps this by construction — single source of truth for the grid + the dashboard + the cohort.
- **5/8 competitors have a column-visibility toggle persisted per user.** Universal expectation among power users. Woo Native's per-user persistence pattern is the cheapest implementation reference.
- **2/8 competitors (Metorik, Glew) ship scheduled CSV/segment exports to Slack.** Metorik does this at $25/mo Starter; Glew bundles into Pro + Plus. Shopify Native paywalls scheduling to Advanced ($299/mo). Slack-as-export-destination is uncommon at the SMB price point and well-loved by reviewers when present.
- **Multi-currency normalization on CSV export** is documented only by Putler ("currency conversion + timezone normalization + dedup pre-applied"). Woo Native's Order Attribution extension explicitly warns of "inaccurate analytics for stores operating in multiple currencies." A clean multi-currency story on the orders grid + export is differentiated.
- **Refund treatment differs across competitors.** Woo Native duplicates refunded orders on both the original date and the refund date (with a return-arrow icon). TrueProfit inherits Shopify's "all return requests = refunds" treatment. BeProfit allows conditional expense rules per order status. Nexstage's `UpsertShopifyOrderAction` and `UpsertWooCommerceOrderAction` will need an explicit refund-row treatment policy that reviewers can audit on the grid.
- **Forced comparison toggle is a UX lesson, not just a bug.** Period comparison is a useful default but must be dismissible. The Woo Native rant — "I just want the date range I select, and that's it" — is one of the highest-emotion verbatim quotes in the entire batch and applies directly to any Nexstage grid that ships comparison-by-default.
