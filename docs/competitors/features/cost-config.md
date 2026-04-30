---
name: Cost configuration
slug: cost-config
purpose: How merchants tell the system about COGS, shipping, payment fees, taxes and recurring opex so net-profit numbers are trustworthy.
nexstage_pages: profit, settings, onboarding (cost-config admin surface; not a chart)
researched_on: 2026-04-28
competitors_covered: lifetimely, beprofit, trueprofit, storehero, bloom-analytics, profit-calc, conjura, putler, metorik, shopify-native
sources:
  - ../competitors/lifetimely.md
  - ../competitors/beprofit.md
  - ../competitors/trueprofit.md
  - ../competitors/storehero.md
  - ../competitors/bloom-analytics.md
  - ../competitors/profit-calc.md
  - ../competitors/conjura.md
  - ../competitors/putler.md
  - ../competitors/metorik.md
  - ../competitors/shopify-native.md
  - https://help.useamp.com/article/652-product-costs-explained
  - https://support.beprofit.co/support/solutions/articles/67000428516-how-do-i-change-the-way-my-shipping-costs-are-calculated-
  - https://beprofit.co/expenses-revenue/
  - https://docs.bloomanalytics.io/shipping-costs-setup.md
  - https://trueprofit.io/solutions/expense-tracking
  - https://profit-calc.helpscoutdocs.com/ (Shopify App Store screenshot 5: "Custom COGS rules by country & quantity, date for exact margins")
---

## What is this feature

Cost configuration is the admin surface where a merchant teaches the analytics tool the costs that the storefront platform doesn't already supply: per-product cost-of-goods-sold (COGS), shipping cost rules, payment-gateway / processing fees, taxes and duties, and recurring operating expenses (staff, software, agency retainers). Without it, every "profit" number a tool shows is fiction — Shopify and WooCommerce surface revenue, discounts, and limited cost data (Shopify exposes a single `cost per item` field; Woo exposes none natively), so the analytics layer must absorb the rest from CSVs, 3PL feeds, supplier integrations, and per-rule editors.

For SMB Shopify/Woo owners the difference between "having data" (orders + ad spend) and "having this feature" is whether the tool can answer "is this order actually profitable?" at the row level. The depth of the cost-config UX directly drives review sentiment in this category — Profit Calc's 49-article COGS knowledge-base section is the largest in their help center, and BeProfit's 2-star reviews quote `"Initial data entry [is] too much"` (`../competitors/beprofit.md`). The category has converged on five admin sub-screens (product costs, shipping, transaction fees, taxes/VAT, custom recurring expenses) plus increasingly expressive rule engines (country/quantity/date, geographic COGS Zones, weight-based shipping).

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `products.cost` (the built-in `cost per item` field, auto-imported by Lifetimely, BeProfit, TrueProfit, Bloom, Profit Calc), `orders.line_items.cost`, `orders.transactions[]` (for actual gateway-fee truth — see TrueProfit transaction-fee complaint), `orders.shipping_lines`, `orders.tax_lines`, `orders.refunds`, `orders.shipping_address.country` (for country-conditional COGS).
- **Source: WooCommerce** — line items, refunds, taxes, shipping; no native cost-per-item field, so user-input COGS is mandatory (`../competitors/metorik.md`, `../competitors/woocommerce-native.md`).
- **Source: Shipping carriers (3PLs)** — actual per-order shipping cost feeds: ShipStation, ShipBob, Shippo, ShippingEasy, Shipwire, ShipHero, FedEx (Lifetimely / TrueProfit / BeProfit / Bloom / Profit Calc all expose the same set).
- **Source: Dropship suppliers** — AliExpress (Profit Calc Chrome extension capture), CJ Dropshipping (BeProfit + TrueProfit + Profit Calc auto-sync), Printful, Printify, Gelato.
- **Source: Payment gateways** — Shopify Payments transactions feed, PayPal, Stripe, Klarna, iDeal, Mollie (Profit Calc lists all six; TrueProfit + BeProfit + Bloom expose Shopify Payments / PayPal / Stripe).
- **Source: Accounting** — QuickBooks Online (Lifetimely) for opex feed.
- **Source: User-input (CSV import)** — `sku, product_cost [, shipping_cost]` minimum schema (Lifetimely), with extensions per-competitor: `country`, `quantity_break`, `effective_date`, `variant_id`, `currency`, `zone`.
- **Source: User-input (manual edit)** — per-row pencil-icon edit on product cost table (verbatim from `https://help.useamp.com/article/652-product-costs-explained`, cited in `../competitors/lifetimely.md`).
- **Source: User-input (rule builder)** — conditional rules keyed on `order_status`, `sales_channel`, `items_amount`, country, weight, fulfillment center, shipping method (BeProfit verbatim: `"Expenses Related to Order Status? Sales Channel? Items Amount? Any Other Condition?"`, `../competitors/beprofit.md`).
- **Source: User-input (default fallback)** — workspace-level default COGS margin % when no SKU-level cost exists (Lifetimely's documented priority hierarchy: manual cost > Shopify cost > default margin).
- **Computed:** `net_profit_per_order = revenue − product_cost − shipping_cost − transaction_fee − tax − allocated_opex − attributed_ad_spend`. Each input is per-line for orders, recurring-amortized for opex.

## Data outputs (what's typically displayed)

This is an admin/configuration feature, so "outputs" are the editable artefacts and the cost-stack lines they materialize downstream.

- **Per-SKU cost row** — `sku, product_name, variant, cost, currency, effective_from, effective_to`, displayed in a sortable table with inline pencil-icon edit.
- **Per-variant cost row** — TrueProfit + Bloom + Lifetimely-help-doc-says-no — variant granularity is competitor-by-competitor.
- **COGS Zone row** — `zone_name, destination_countries[], cost_overrides[]` (TrueProfit; capped 5 / 10 / unlimited by tier).
- **COGS rule row (Profit Calc)** — `sku, country, quantity_min, quantity_max, effective_date, cost`. Verbatim screenshot caption: `"Custom COGS rules by country & quantity, date for exact margins"` (`../competitors/profit-calc.md`).
- **Shipping profile row (BeProfit)** — `profile_name, condition (weight | destination | items), aggregation (sum | highest), quantity_multiplier, cost`. Verbatim options: `"sum all products in an order or use the highest shipping cost in an order"`, `"you can multiply shipping cost by item quantity as an additional rule"` (`../competitors/beprofit.md`).
- **Shipping rule row (Bloom)** — 4-layer fallback: `rules → 3PL integration → Shopify shipping auto-sync → manual edit` with rules dimensioned by `country, products, fulfillment_center, shipping_method` (`../competitors/bloom-analytics.md`).
- **Transaction-fee gateway row** — gateway-name + percentage + fixed-amount per gateway (Shopify Payments, PayPal, Stripe, Klarna, iDeal, Mollie).
- **Custom expense row** — `name, amount, period (one-time | recurring), recurrence (daily | weekly | monthly | annual), variable_basis (% of revenue | fixed per order | flat), conditions[]`.
- **VAT / tax row** — country-keyed (Profit Calc has 7 KB articles in VAT category alone; relevant for EU dropship / COD markets).
- **Order-level cost columns (the downstream display)** — Bloom's verbatim per-order column set: `Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost` (`../competitors/bloom-analytics.md`).

## How competitors implement this

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Sidebar > Costs / Settings > Cost & Expenses tab.
- **Visualization:** table (per-SKU rows with inline edit) + CSV upload widget; no charts.
- **Layout (prose):** "Per-product cost editing UI. Each product row has a **pencil icon** to edit cost individually (per help doc). CSV bulk-import accepts a `SKU` + `product_cost` (+ optional `shipping_cost`) spreadsheet. Default COGS margin % can be set as a fallback when no explicit cost exists." (`../competitors/lifetimely.md`)
- **Specific UI:** "Inline editable rows. Pencil icon per row. CSV upload widget" (`../competitors/lifetimely.md`). Documented priority hierarchy: **Lifetimely manual cost > Shopify cost-per-item > default COGS margin**.
- **Filters:** none observed at the cost-config layer; filtering is on the report side.
- **Data shown:** Product, SKU, cost, shipping cost (optional).
- **Interactions:** Manual edit, CSV upload, default fallback. "Newly added products with zero sales don't appear immediately" (verbatim limitation).
- **Why it works (from reviews/observations):** Reviews praise its "set-and-forget" set-up but penalize it for SKU-level reliability — Reddit-sourced critique: `"After scaling, [Lifetimely] started breaking in ways that actually cost them money. The interface gave the illusion of accuracy, but under the hood, it just wasn't reliable at the SKU level."` (`../competitors/lifetimely.md`)
- **Source:** `https://help.useamp.com/article/652-product-costs-explained` ; `../competitors/lifetimely.md`. **Limitations called out in docs (verbatim):** "Transaction fees and handling costs are explicitly excluded from this scope." "No variant-level cost granularity is described."

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Five separate sub-screens — `Settings > Costs > Fulfillment`, `Settings > Costs > Processing Fees`, `Settings > Costs > Calculation Preferences`, `Settings > Costs > Marketing Platforms`, `Settings > Products Costs`, plus `Settings > Custom Operational Expenses` (six in total — taxonomically the largest cost-config tree in this batch).
- **Visualization:** form-based "shipping profiles" + per-SKU table; no chart.
- **Layout (prose):** Settings tree splits costs into discrete categories. Shipping profiles are form-based; each profile defines parameters (weight/destination/items) and a shipping-cost rule. Custom Operational Expenses page accepts variable expenses (% of revenue OR fixed per order) with conditions on order status / sales channel / items count.
- **Specific UI:** Shipping cost rules — verbatim option labels include `"Shipping costs are either already calculated in with your COGS (production costs) or that your shipping is free"`, `"Your shipping costs are identical to what the client pays"`, aggregation toggle `"sum all products in an order or use the highest shipping cost in an order"`, plus quantity multiplier `"you can multiply shipping cost by item quantity as an additional rule"` (`https://support.beprofit.co/...articles/67000428516`, `../competitors/beprofit.md`). Custom expenses pitch is rhetorical: `"Add variable costs to your orders as a percentage of order revenue or a fixed amount for each order"`, `"Expenses Related to Order Status? Sales Channel? Items Amount? Any Other Condition?"` (`https://beprofit.co/expenses-revenue/`).
- **Filters:** Per-shop, per-platform (multi-store gated to Plus tier); no date filter on the config rules themselves (rules are time-invariant per row).
- **Data shown:** Per-SKU cost (Google Sheets / CSV / manual / API / CJ Dropshipping); shipping profiles; processing-fee rules per gateway; custom OPEX with one-time / recurring toggle.
- **Interactions:** Create multiple shipping profiles per shop; apply per matching order. CSV upload, Google Sheets sync, CJ Dropshipping auto-sync, manual edit, API.
- **Why it works (from reviews/observations):** Most flexible conditional rule engine in the SMB tier. **Why it's hated:** review verbatim — `"Initial data entry [is] too much"` (Sayed S., Capterra, January 2022); `"Not calculating the profit correctly. Calculation Preferences section not working properly"` (Celluweg, January 17, 2026, `../competitors/beprofit.md`).
- **Source:** `../competitors/beprofit.md`; `https://beprofit.co/expenses-revenue/` — "the webpage lacks detailed UI/UX documentation — no screenshots, field names, or step-by-step form descriptions are provided" (verbatim from BeProfit profile).

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Settings > Expense Tracking — multi-tab cost configuration. Listed as `"Quantity-based COGS"`, `"Custom Costs"`, `"COGS Zones"` in the pricing card.
- **Visualization:** multi-tab form; per-product/per-variant cost table; period-history editor; zone-rule builder.
- **Layout (prose):** "Multi-tab cost configuration covering COGS (per-product, per-variant, with unlimited historical periods, CSV import, and auto-sync from Shopify or CJ Dropshipping), **COGS Zones** (geographic — set different COGS by delivery destination), shipping cost rules (`'by location, product, quantity, or weight'`), transaction-fee tracking by gateway (PayPal, Stripe, Shopify Payments), and custom costs (recurring agency fees, one-time payments, labor)." (`../competitors/trueprofit.md`)
- **Specific UI:** Per-product/per-variant cost rows; CSV import flow; period-based historical COGS editor; zone-rule builder; custom-cost row entry with recurring/one-time toggle. **COGS Zone caps:** 5 zones (Basic) / 10 zones (Advanced) / unlimited (Ultimate+) — paywalled scaling axis.
- **Filters:** by zone (geographic), by period (effective-date), by variant.
- **Data shown:** per-SKU + per-variant COGS, per-zone overrides, per-period historical adjustments, gateway fees, custom recurring/one-time costs.
- **Interactions:** CSV upload; zone-rule creation; auto-sync toggles per integration (Shopify, CJ Dropshipping, Printful, Printify, Gelato, ShipStation, ShipBob, Shippo, Shipwire, ShippingEasy); period-history adjustments.
- **Why it works (from reviews/observations):** **Unlimited COGS history periods** is uncontested in the segment for retroactive accuracy. **Why it's hated:** documented bug — `"The transaction fees are calculated by a formula although this can be pulled directly from Shopify [...] Beprofit handle this correctly"` (Reputon-aggregated 1-star reviewer, `../competitors/trueprofit.md`). TrueProfit's own review blog admits `"Learning curve in setting up advanced cost rules"`. Item-pick charges from 3PLs not separately captured (Interior Delights, March 2026).
- **Source:** `../competitors/trueprofit.md`; `https://trueprofit.io/solutions/expense-tracking`.

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** "Cost Settings" — single configuration screen, accessed during onboarding and revisited as needed. Five explicit cost buckets per the Academy "Cost Structure" module: **product costs, shipping, fulfillment/packaging, transaction fees, marketing**.
- **Visualization:** form-based.
- **Layout (prose):** "Form-based configuration for product costs, shipping, fulfillment/packaging, transaction fees, marketing expenses. Shopify App Store screenshot list confirms a 'Cost Settings configuration interface' render." (`../competitors/storehero.md`)
- **Specific UI:** "form fields per cost category; specific input granularity (per-SKU vs. blended, percent-vs-flat) NOT observable from public sources" (`../competitors/storehero.md`).
- **Filters:** UI details not available — only feature description seen on marketing/Academy.
- **Data shown:** Five cost buckets.
- **Interactions:** Setup as part of onboarding; revisited for adjustments.
- **Why it works (from reviews/observations):** Disciplined 5-bucket taxonomy is more legible than BeProfit's 6-screen sprawl; Academy module pairs each bucket with a teaching lesson. No critical reviews surfaced (only 13 App Store reviews, all 5-star).
- **Source:** `../competitors/storehero.md`; Shopify App Store; Academy "Cost Structure" module.

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Settings split — `Settings > Product Costs`, `Settings > Shipping Costs`, `Settings > Custom Operating Expense`, `Settings > Add Custom Revenue`, `Settings > Order Settings`, `Settings > Multiple Shops`, `Settings > Integrations`.
- **Visualization:** per-SKU table (Product Costs); 4-layer fallback rule engine (Shipping); recurring/one-off form (Custom Operating Expense).
- **Layout (prose):** "Multi-tier setup (rules, integrations, Shopify shipping auto-sync, manual edit). Shipping rules support: by country, by products, by fulfillment center, by shipping method" (`../competitors/bloom-analytics.md`). The shipping engine has 4-layer fallback: `rule-based → 3PL integration → Shopify shipping auto-sync → manual edit`.
- **Specific UI:** Per-SKU cost entry / bulk import; shipping rules dimensioned by country / products / fulfillment center / shipping method; custom OPEX with recurring/one-off toggle; "Add Custom Revenue" injection. Order-level cost columns (downstream display): `Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost` (note: **"Tariff Cost" is a 2025-2026 addition** per `../competitors/bloom-analytics.md`).
- **Filters:** per-shop (Multiple Shops model — main store + connected stores via Shop Key paste).
- **Data shown:** SKU costs; shipping cost rules + 3PL costs + Shopify shipping costs; gateway fees; custom opex; custom revenue overrides.
- **Interactions:** CSV import; rule-creation per dimension; 3PL auto-sync (ShipStation / ShipHero / FedEx); manual edit fallback.
- **Why it works (from reviews/observations):** Most granular shipping-cost rule engine in the SMB tier. CM1 / CM2 / CM3 contribution-margin tiering is computed directly from these layers. Reviews praise time-saving: `"No more digging through spreadsheets — just instant, actionable data"` (Baron Barclay Bridge Supply, March 11, 2025, `../competitors/bloom-analytics.md`).
- **Source:** `../competitors/bloom-analytics.md`; `https://docs.bloomanalytics.io/shipping-costs-setup.md`.

### Profit Calc ([profile](../competitors/profit-calc.md))
- **Surface:** Settings — five sub-screens: COGS / Cost per item, Shipping costs, Transaction fees, Monthly expenses, VAT. **49 KB articles in the COGS section alone** — the largest help-center category in any competitor profile in this batch.
- **Visualization:** rules-table for COGS (most expressive in the segment); manual or imported shipping config; gateway-by-gateway transaction-fee form; manual recurring-opex line items; EU VAT configuration (7 KB articles).
- **Layout (prose):** App Store screenshot 5 caption verbatim: `"Custom COGS rules by country & quantity, date for exact margins."` "Implies a rules-table UI where the user defines tiers (qty breakpoints), country overrides, and date-based versions of cost." (`../competitors/profit-calc.md`)
- **Specific UI:** Add/edit cost rules; bulk import from Shopify "cost per item" field; AliExpress Chrome extension auto-pulls cost into a hidden source field (dropshipper-native). Six gateways supported on the transaction-fee screen: Shopify Payments, PayPal, Stripe, Klarna, iDeal, Mollie. **Multi-currency support uses both real-time and historical FX rates** for cost-line accuracy.
- **Filters:** `country`, `quantity_min/max`, `effective_date` — three-axis rules.
- **Data shown:** Configured cost per SKU per dimension; shipping costs; gateway fees; recurring opex; VAT lines.
- **Interactions:** AliExpress Chrome-extension capture; bulk Shopify cost import; rules editor; VAT config workflow.
- **Why it works (from reviews/observations):** Most expressive rule engine in the segment (`country × quantity × date`). Founder-led support cited repeatedly: `"the CEO himself created a new feature upon my request"` (My Charming Fox via Reputon, October 2022, `../competitors/profit-calc.md`). **Why it's hated:** transaction-fee bug — `"transaction fees are calculated by a formula although this can be pulled directly from Shopify"` (WASABI Knives, May 2021, `../competitors/profit-calc.md`).
- **Source:** `../competitors/profit-calc.md`; `https://profit-calc.helpscoutdocs.com/`; App Store screenshot 5.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Implied "Settings / Connectors / Workspace" — "implied but not directly observed in marketing pages" (`../competitors/conjura.md`). Cost-config UI is not publicly documented; the cost layer is computed (`"contribution profit (revenue minus COGS, shipping, fees, refunds, ad spend)"`) but the admin entry surface is not exposed in marketing.
- **Visualization:** Not observed in public sources.
- **Layout (prose):** Not observed in public sources.
- **Specific UI:** UI details not available — only feature description seen on marketing pages.
- **Filters:** Not observed.
- **Data shown:** Downstream the Order Table exposes per-order shipping cost, profit margin, and ad budget allocated per SKU — implying COGS + shipping cost configuration must exist upstream, but the admin form is not described publicly.
- **Interactions:** Not observed.
- **Why it works (from reviews/observations):** No public detail; positioned as a profit-first metric layer rather than a cost-admin tool.
- **Source:** `../competitors/conjura.md`.

### Putler ([profile](../competitors/putler.md))
- **Surface:** Cost-config admin not surfaced as a first-class UI in the profile. Putler relies on "fees (where exposed by Shopify)" pulled from the storefront and breaks them out per-transaction (`"net revenue, refunds, shipping, taxes, fees, discounts, and commissions broken out as separate line items"`, `../competitors/putler.md`).
- **Visualization:** Not observed as a config surface; downstream a transaction-detail panel breaks out each line.
- **Layout (prose):** Not observed.
- **Specific UI:** Color-coded transaction rows (sales green, refunds red); inline refund button per row.
- **Filters:** Filter chips combine freely on the transactions detail page.
- **Data shown:** per-transaction net/refund/shipping/tax/fee/discount/commission.
- **Interactions:** In-Putler refund processing for transactions from PayPal, Stripe, and Shopify (write access).
- **Why it works (from reviews/observations):** Static-COGS critique: `"COGS field is static"` and doesn't update with supplier price changes — manual maintenance required, especially per-variant (Putler critique in `../competitors/shopify-native.md`, line 230).
- **Source:** `../competitors/putler.md`; `../competitors/shopify-native.md`.

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Store Settings — "store connection, force-sync tool, webhook configuration, **COGS/cost configuration**, attribution date toggle (first-order vs. join-date), include/exclude non-paying customers" (`../competitors/metorik.md`).
- **Visualization:** Settings form (specific UI not extracted in public sources). CSV upload via Google Sheets / CSV is the documented input path: `"Manual cost upload (e.g., COGS by SKU, fixed costs)"`.
- **Layout (prose):** Single Store Settings page houses cost configuration alongside other store-level toggles. UI granularity not surfaced.
- **Specific UI:** UI details not available — only feature description seen on marketing/help docs.
- **Filters:** None at cost-config; downstream segment builder applies cost-derived metrics across reports.
- **Data shown:** COGS by SKU (CSV-imported); fixed costs.
- **Interactions:** CSV upload; manual entry; integration-pulled shipping (ShipStation).
- **Why it works (from reviews/observations):** WooCommerce coverage is the structural moat — Metorik is one of the few SMB profit tools with first-class Woo support, so Woo merchants tolerate the thinner cost-config UX.
- **Source:** `../competitors/metorik.md`.

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Product admin — `Products > [product] > Cost per item` field (single value per product or per variant). No standalone "Cost Settings" screen.
- **Visualization:** single-field input per product/variant in the product editor; no rule engine.
- **Layout (prose):** Cost-per-item is one numeric input field on each product/variant edit page. Refunds, taxes, and gateway fees flow through Shopify's transaction system natively. No country / quantity / date conditional rules. Profit is computed when the cost field is set; otherwise margin lines display blank.
- **Specific UI:** Single number input per product or variant; currency tied to store currency. No bulk-import dialog inside the product admin (bulk requires CSV via the products import path).
- **Filters:** No filters at the cost-config layer.
- **Data shown:** `cost per item` only.
- **Interactions:** Manual edit; CSV product import (the same product CSV importer covers cost).
- **Why it works (from reviews/observations):** Native and free, but third-party tools cite the gap: `"Static COGS field. Putler: 'COGS field is static' and doesn't update with supplier price changes — manual maintenance required, especially per-variant"` (`../competitors/shopify-native.md`, line 230). Every Tier-1 third-party in this profile reads this field as the bottom of their priority hierarchy and overlays their own rules on top.
- **Source:** `../competitors/shopify-native.md`.

## Visualization patterns observed (cross-cut)

This is an admin surface, so "visualization" reads as "form pattern." Counts below are by primary form/UI pattern:

- **Per-SKU table with inline edit + CSV import:** 7 competitors (Lifetimely, BeProfit, TrueProfit, Bloom, Profit Calc, Metorik, StoreHero) — universal pattern. Pencil-icon-per-row is the dominant inline-edit affordance (verbatim from Lifetimely's help docs).
- **Multi-tab cost configuration ("buckets"):** 4 competitors (BeProfit's six-screen tree, TrueProfit's tabs, StoreHero's five buckets, Bloom's seven Settings sub-pages). StoreHero's five-bucket taxonomy (product / shipping / fulfillment+packaging / transaction fees / marketing) is the cleanest cut.
- **Conditional rule engine (country / quantity / date / weight / channel):** 4 competitors expose increasingly expressive rules — **Profit Calc** (`country × quantity × date`, the most expressive), **TrueProfit** (`COGS Zones` geographic + period history), **BeProfit** (shipping profiles by weight / destination / items + custom expenses by order status / sales channel / items count), **Bloom** (shipping rules by country / product / fulfillment center / shipping method).
- **Geographic COGS Zones (delivery-destination-keyed costs):** 1 competitor with a dedicated tab (TrueProfit). Profit Calc handles country as a rule-axis rather than a "Zone" object. BeProfit and Bloom expose country at the shipping-rule layer, not COGS.
- **3PL auto-sync layer (actual shipping cost feed):** 6 competitors integrate at least one of {ShipStation, ShipBob, Shippo, ShippingEasy, Shipwire, ShipHero, FedEx}. Bloom's "4-layer fallback" (rules → 3PL → Shopify shipping → manual) is the most explicit articulation.
- **Default fallback margin %:** Lifetimely is the only one in this batch with an explicit doc-described fallback ("manual cost > Shopify cost-per-item > default COGS margin").
- **Recurring vs one-time custom expense toggle:** 3 competitors (BeProfit, TrueProfit, Bloom). Frequency cadence (daily/weekly/monthly/annual) is BeProfit's verbatim option set.
- **Variable expense (% of revenue OR fixed per order):** 1 competitor with a documented toggle (BeProfit's verbatim option). TrueProfit treats this as "custom costs (recurring agency fees, one-time payments, labor)" without explicit % vs flat split.
- **VAT / tax dedicated config:** 1 competitor with a dedicated tab (Profit Calc — 7 KB articles).

Recurring conventions: **pencil icon for per-row edit**; **CSV upload widget** is universal; **integration auto-sync toggles** (Shopify cost-per-item, CJ Dropshipping, AliExpress, 3PL carriers) are bottom-of-priority-stack defaults; **no chart visualization** anywhere — this is a forms-and-tables surface.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Set-and-forget once configured**
- "just what I needed to track my costs in real time" — Obnoxious Golf (USA), Shopify App Store, April 15, 2026 (`../competitors/trueprofit.md`)
- "It really helped us with getting a better hold." — Copper Culture, Shopify App Store, September 8, 2025 (`../competitors/beprofit.md`)
- "Saves so much time and gives me a true picture of my actual profit" — leighanndobbsbooks, Shopify App Store, May 11, 2025 (`../competitors/profit-calc.md`)
- "Pretty complete for tracking profits in a simple way." — Tempus Mods (Portugal), Shopify App Store, May 30, 2025 (`../competitors/bloom-analytics.md`)
- "We now know exactly what we make from every sale." — kicksshop.nl (Netherlands), Shopify App Store, January 19, 2026 (`../competitors/bloom-analytics.md`)

**Theme: Auto-sync of shipping / supplier costs (no spreadsheet drift)**
- "Terrific app! Instantly coalesced a mess of channels" — Alex Fox Books, Shopify App Store, May 9, 2025 (`../competitors/profit-calc.md`)
- (Recurring praise themes from `../competitors/trueprofit.md`): "**shipping-cost auto-sync accuracy** (ShipStation/ShipBob), **set-and-forget cost tracking**, and longevity (multiple reviewers in their 2nd-5th year of use)."
- "Set-up is easy, love that the data sync in real time" — Good Bacteria, Shopify App Store, March 5, 2026 (`../competitors/profit-calc.md`)

**Theme: Founder/personal support during cost-config setup**
- "the CEO himself created a new feature upon my request" — My Charming Fox, Shopify App Store via Reputon, October 2022 (`../competitors/profit-calc.md`)
- "Anthony personally offered one-on-one support…commitment…is rare." — Shopify App Store reviewer (`../competitors/beprofit.md`)
- "Durra has been very helpful and support is always quick to fix any issues" — Houselore (UK), Shopify App Store, April 15, 2026 (`../competitors/trueprofit.md`)
- "Provides insights that are impossible to get anywhere else or without time consuming calculations. (Sam is the best!)" — Topo Designs, Shopify App Store, March 10, 2026 (`../competitors/lifetimely.md`)

**Theme: Spreadsheet replacement is the core promise**
- "No more digging through spreadsheets — just instant, actionable data." — Baron Barclay Bridge Supply (United States), Shopify App Store, March 11, 2025 (`../competitors/bloom-analytics.md`)
- "BeProfit made it easy for me, you don't need to be a master in accounting." — Capterra reviewer, January 2022 (`../competitors/beprofit.md`)
- "Spectacularly easy way to see exactly what's happening in your store." — Cindy Nichols Store, Shopify App Store, May 14, 2025 (`../competitors/profit-calc.md`)

## What users hate about this feature

**Theme: Initial cost-entry is a heavy lift**
- "Initial data entry [is] too much." — Sayed S., Capterra, January 2022 (`../competitors/beprofit.md`)
- "Can feel complex for brand-new stores with very low order volume" — TrueProfit's own review blog (`../competitors/trueprofit.md`)
- "Learning curve in setting up advanced cost rules" — TrueProfit's own review blog (`../competitors/trueprofit.md`)

**Theme: Transaction fees calculated by formula instead of pulled from gateway truth**
- "transaction fees are calculated by a formula although this can be pulled directly from Shopify" (resulting in incorrect fee calculations; reviewer compared unfavorably to BeProfit on this point) — WASABI Knives, Shopify App Store via Reputon, May 2021 (`../competitors/profit-calc.md`)
- "The transaction fees are calculated by a formula although this can be pulled directly from Shopify [...] Beprofit handle this correctly" — Shopify App Store 1-star reviewer via Reputon (`../competitors/trueprofit.md`)

**Theme: SKU-level / variant-level reliability bugs at scale**
- "After scaling, [Lifetimely] started breaking in ways that actually cost them money. The interface gave the illusion of accuracy, but under the hood, it just wasn't reliable at the SKU level." — paraphrased Reddit/community sentiment (`../competitors/lifetimely.md`)
- "Not calculating the profit correctly. Calculation Preferences section not working properly." — Celluweg, Shopify App Store, January 17, 2026 (`../competitors/beprofit.md`)
- "Static COGS field. Putler: 'COGS field is static' and doesn't update with supplier price changes — manual maintenance required, especially per-variant." (`../competitors/shopify-native.md`)

**Theme: Refund-vs-return conflation downstream of the cost-config layer**
- "Shopify's financial summary counts all return requests as issued refunds, which can be misleading. Not all return requests are accepted, and not all approved returns end up refunded. […] I've discussed this concern with the TrueProfit team, but they believe making changes now isn't practical." — Apollo Moda (USA), Shopify App Store, May 3, 2024, 2-star (`../competitors/trueprofit.md`)

**Theme: Excluded scopes (handling fees, item-pick charges, taxes-as-cost)**
- (Lifetimely help-doc verbatim, paraphrased in profile): "Transaction fees and handling costs are explicitly excluded from this scope." (`../competitors/lifetimely.md`)
- "item pick charges" not captured separately — Interior Delights, March 2026 review on TrueProfit's ShipStation/ShipBob integration (`../competitors/trueprofit.md`).

**Theme: Variant-level cost granularity gap**
- (Lifetimely profile observation): "**No variant-level cost granularity is described** — costs are SKU-keyed per-product." (`../competitors/lifetimely.md`)
- (Putler critique surfaced in `../competitors/shopify-native.md`): per-variant manual maintenance burden.

## Anti-patterns observed

- **Formula-based transaction-fee estimation when the gateway feed is available.** Profit Calc and TrueProfit both got 1-star reviews specifically calling out that they estimate Shopify Payments / Stripe / PayPal fees by `(rate × amount + fixed)` instead of pulling `Order.transactions[]`. Edge cases (Shop Pay Installments, Stripe currency conversion, partial refunds) drift from actuals. BeProfit is cited by competitors as the contrast-correct example. (Sources: `../competitors/profit-calc.md`, `../competitors/trueprofit.md`.)
- **Cost-config sprawl across too many sub-screens.** BeProfit splits cost config across **six** Settings sub-screens (`Costs > Fulfillment`, `Costs > Processing Fees`, `Costs > Calculation Preferences`, `Costs > Marketing Platforms`, `Products Costs`, `Custom Operational Expenses`). Multiple reviewers cite setup burden; one names "Calculation Preferences" as broken. (`../competitors/beprofit.md`)
- **Excluding handling fees and item-pick charges silently.** Lifetimely's help doc verbatim excludes transaction fees and handling costs; users are forced to enter them as custom recurring costs, which loses per-order granularity. TrueProfit's 3PL feed misses item-pick charges. (`../competitors/lifetimely.md`, `../competitors/trueprofit.md`)
- **No variant-level COGS.** Lifetimely's help doc keys cost on SKU/product, not variant — fails for size/color SKUs that share product but have different supplier costs. (`../competitors/lifetimely.md`)
- **Static cost field that doesn't version over time.** Putler's "COGS field is static" — supplier price changes require manual rewrite, with no historical period tracking. TrueProfit's "unlimited COGS history periods" is the contrast example. (`../competitors/shopify-native.md`, `../competitors/trueprofit.md`)
- **CSV is the only bulk path.** Lifetimely's help doc describes CSV as the bulk-import path with no Google Sheets sync; BeProfit and Bloom both expose Google Sheets sync as an alternative. CSV-only is friction for merchants who maintain costs in Sheets. (`../competitors/lifetimely.md`)
- **Single-shop scope for cost rules in multi-store accounts.** BeProfit's $249/mo Plus tier is the only path to cross-shop cost-rule reuse; lower tiers force per-shop re-entry. Profit Calc requires a separate subscription per Shopify store. (`../competitors/beprofit.md`, `../competitors/profit-calc.md`)
- **No retroactive recalc UX.** Public profiles do not document a "recomputing…" banner or job-status indicator anywhere in the SMB tier — when a user edits a COGS row, the downstream effect on historical orders is invisible until the next refresh.

## Open questions / data gaps

- **Conjura cost-config admin surface is unobserved publicly.** They compute contribution profit from COGS / shipping / fees but do not expose the configuration form on marketing pages — would require a paid trial to capture. (`../competitors/conjura.md`)
- **TrueProfit "Ad Sync Custom Rules" (Ultimate tier) is named in pricing but not documented in profile UI prose.** Whether this rule engine extends to cost-config or only to ad-spend mapping is not clear. (`../competitors/trueprofit.md`)
- **Bloom's "Add Custom Revenue" surface** is named in the docs sitemap but UI not described — uncertain whether it's a pure cost-config concept (e.g., wholesale revenue not in Shopify) or a different scope. (`../competitors/bloom-analytics.md`)
- **Profit Calc's actual rule editor UI** is described only via the App Store screenshot caption (`"Custom COGS rules by country & quantity, date for exact margins"`). No screenshot at sufficient resolution; KB articles are paywalled-shaped (Help Scout articles return previews only). 49 articles is a strong signal of complexity, but exact field names and operator semantics (range vs. tier, exclusive/inclusive bounds, currency keying) are not extractable.
- **StoreHero's per-cost-bucket form granularity** — Shopify App Store screenshot shows the screen exists but `"specific input granularity (per-SKU vs. blended, percent-vs-flat) NOT observable from public sources"` (`../competitors/storehero.md`).
- **Variant-level cost handling across the segment** is inconsistently documented. TrueProfit explicitly supports per-variant; Lifetimely explicitly does not (per help doc); BeProfit / Bloom / Profit Calc are ambiguous in public sources.
- **Tax-as-cost vs tax-as-pass-through** semantics — Profit Calc has VAT as a dedicated 7-article category (EU dropship-relevant); other competitors fold tax into order-level pulls without configurable handling. The "is VAT a cost or a pass-through?" question depends on B2B vs B2C and country, but no profile documents how the rule is configured.
- **Conditional rule operator semantics** — BeProfit's rhetorical pitch "Order Status? Sales Channel? Items Amount?" doesn't expose UI field names. Whether the operator is `=`, `IN`, `>=`, or expression-builder is unknown.

## Notes for Nexstage (observations only — NOT recommendations)

- **The most expressive rule engine in the SMB tier is Profit Calc's `country × quantity × date` axis.** Profit Calc users coming over to Nexstage will compare directly. Their COGS KB has 49 articles — the largest of any cost-config surface in this batch — suggesting expressiveness is a real product surface, not just marketing claim. (`../competitors/profit-calc.md`)
- **The category has converged on five-to-seven cost buckets.** StoreHero's five (product / shipping / fulfillment+packaging / transaction fees / marketing) is the cleanest taxonomy. BeProfit's six is the most fragmented and reviewers call setup burden out. Bloom's seven sub-screens (incl. Custom Revenue + Multiple Shops) is the most expansive. (`../competitors/storehero.md`, `../competitors/beprofit.md`, `../competitors/bloom-analytics.md`)
- **TrueProfit's `COGS Zones` is a recognised primitive** (`../competitors/trueprofit.md`). Geographic per-destination COGS is paywalled-scaled (5 / 10 / unlimited zones) and Shopify dropshippers with EU+US fulfillment care about it. Profit Calc handles the same job via a `country` rule-axis without a Zone object.
- **Universal anti-pattern: formula-estimated transaction fees.** Two top-tier competitors (TrueProfit, Profit Calc) both have public 1-star reviews on the same root cause. Pulling from `Order.transactions[]` (Shopify) or the gateway feed directly is the user expectation. (`../competitors/trueprofit.md`, `../competitors/profit-calc.md`)
- **Variant-level COGS is a documented gap at Lifetimely.** Lifetimely's help doc keys cost per SKU/product without variant differentiation; community-sourced critique flags SKU-level reliability at scale. (`../competitors/lifetimely.md`)
- **Bloom's order-level cost columns are an exact specification target:** `Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost`. **"Tariff Cost" is a 2025-2026 addition** — relevant given current trade policy. (`../competitors/bloom-analytics.md`)
- **Bloom's 4-layer shipping fallback** (rules → 3PL → Shopify shipping → manual) is the most explicit articulation in the segment. Rule dimensions: country / products / fulfillment center / shipping method. (`../competitors/bloom-analytics.md`)
- **No competitor in this batch documents a retroactive recalc UI.** Nexstage's `RecomputeAttributionJob` + "Recomputing…" banner pattern (per `CLAUDE.md`) is structurally unobserved here — this is direct opening for a transparency-oriented cost-config UX.
- **CSV is universal; Google Sheets sync is in BeProfit + Bloom; AliExpress Chrome extension is unique to Profit Calc** (dropshipper-native cost capture).
- **Lifetimely's documented priority hierarchy** (`manual cost > Shopify cost-per-item > default COGS margin`) is the only fallback chain explicitly named in the segment. (`../competitors/lifetimely.md`)
- **TrueProfit's "unlimited COGS history periods"** maps cleanly to a versioned-cost data model with `effective_from`/`effective_to`. Profit Calc's "date" axis is the same primitive at the rule layer. (`../competitors/trueprofit.md`, `../competitors/profit-calc.md`)
- **Custom recurring expenses with variable basis (% of revenue vs flat per order)** is documented only at BeProfit; conditions on `order_status`, `sales_channel`, `items_amount` are also a BeProfit-only scope. (`../competitors/beprofit.md`)
- **Multi-store cost-rule sharing is a paywall pivot point.** BeProfit gates it to $249/mo Plus tier; Profit Calc charges per-store subscription. Workspace-scoped cost rules in a Nexstage-shaped IA are structurally cleaner. (`../competitors/beprofit.md`, `../competitors/profit-calc.md`)
- **"Initial data entry is too much" is the loudest cost-config user complaint** across all competitors; CSV import + auto-sync from {Shopify cost-per-item, 3PL carrier feeds, dropship suppliers} is the proven mitigation set.
- **Shopify Native's single `cost per item` field** is what every Tier-1 third-party reads as the bottom of their priority hierarchy and overlays rule engines on top. WooCommerce has no native equivalent — Woo merchants must enter COGS through the analytics tool from the start, which Metorik treats as the entry path. (`../competitors/shopify-native.md`, `../competitors/metorik.md`)
- **Tax / VAT as a dedicated config tab is rare** (Profit Calc only, 7 KB articles, EU-driven). Other tools fold tax into the order-level pull without a dedicated rule UI.
- **Refund-vs-return conflation propagates from Shopify's financial-summary feed into the cost-config-derived profit numbers.** TrueProfit told a reviewer the fix `"isn't practical"`. Solving the return-request vs issued-refund split in the cost-config-aware profit pipeline is a quoteable differentiator. (`../competitors/trueprofit.md`)
