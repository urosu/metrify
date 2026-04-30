# Inventory

Route: `/inventory`

## Purpose

Answer "what's my stock vs predicted demand, and where am I going to stock out?" — combining platform inventory data with sales velocity and a simple demand forecast based on last month + same-period-last-year trend.

## User questions this page answers

- Which products are at risk of stocking out in the next 30 days, given current sales velocity?
- Where does predicted demand exceed current stock?
- Which SKUs are overstocked relative to predicted demand?
- How does my stock health compare across product categories and countries?
- What should I reorder now, and how many units?

## Data sources

| Source | Required? | Provenance | Freshness |
|---|---|---|---|
| Store inventory | Yes | `product_variants.stock_quantity` — synced from Shopify Inventory API / WooCommerce stock fields via platform webhooks | Shopify: webhook on stock change (~1 min); WC: poll every 15 min |
| Sales velocity | Yes | `daily_snapshot_products` — units sold per SKU per day, aggregated from `order_items` | Hourly rollup |
| Demand forecast | Yes (computed) | `SalesPredictionService` — projects next 30d demand per SKU using: units sold last 30d + units sold same-period-last-year (if available), averaged. Simple linear trend. Flagged with `SignalTypeBadge` "Modeled" (§5.28). | Nightly recompute |

Stock data requires that inventory tracking is enabled per variant in the connected platform. SKUs with inventory tracking disabled render as "Not tracked" in the stock column.

## Above the fold (1440×900)

- `AlertBanner` (warning, conditional) — "N products have fewer than 7 days of stock at current velocity." Includes count, rendered when any SKU has `days_of_stock < 7`. Click alert → filters table to those SKUs.
- `KpiGrid` (4 cols):
  - `MetricCard` "At-Risk SKUs" — count of SKUs where predicted demand > current stock within 30 days. Delta vs prior 30d snapshot.
  - `MetricCard` "Overstocked SKUs" — count of SKUs where stock > 3× predicted 30d demand.
  - `MetricCard` "Avg Days of Stock" — median days of stock across all tracked active SKUs.
  - `MetricCard` "Stockout Events (30d)" — count of SKUs that hit zero stock in the past 30 days (missed revenue signal).
- **Inventory Table** (`DataTable` §5.5) — one row per SKU. Default sort: `days_of_stock` ASC (most critical first). Columns:
  - Product (thumbnail 24×24 + title + SKU in JetBrains Mono · `MiddleTruncate` §5.18)
  - Current Stock (units; "Not tracked" if inventory tracking off)
  - Units Sold (30d) — from `daily_snapshot_products`
  - Predicted Demand (30d) — `SalesPredictionService` output; `SignalTypeBadge` "Modeled" on each cell
  - Days of Stock — `current_stock ÷ (units_sold_30d ÷ 30)`; NULLIF on zero velocity → "∞" (no recent sales)
  - Stock Status chip — `Critical` (rose, <7d) · `Low` (amber, 7–30d) · `Healthy` (emerald, >30d) · `Overstocked` (sky, stock > 3× demand) · `No Data` (zinc, tracking off)
  - Suggested Reorder Qty — `(predicted_demand_30d × 1.2) − current_stock`, floored at 0. Shown only for Critical/Low rows. Not editable; informational only.
  - Last synced (relative timestamp)

## Below the fold

- `BarChart` "Stock health by category" — horizontal bars per product category, colored by stock status composition (stacked: Critical / Low / Healthy / Overstocked). Click a bar → filters the Inventory Table to that category.
- `LineChart` "Units sold over time" — appears when a table row is selected. Shows the selected SKU's daily units sold (past 90d) + predicted demand (next 30d, dotted). Rightmost solid segment = last real data point; dotted = forecast. Pair: same-period-last-year ghost line (desaturated, thinner) for seasonal context.
- **Low-data warning** — when a workspace has fewer than 30 days of sales history for a SKU, the demand forecast is suppressed and the row shows `ConfidenceChip` (§5.27) "Based on N days — low confidence. Forecast available after 30 days."

## Interactions specific to this page

- **Table row click** — selects the SKU and renders the LineChart below with that SKU's data. Does not open a drawer; the chart replaces a generic placeholder below the fold.
- **FilterChipSentence** filters: Stock Status · Product category · Days of stock ≤ N · Has demand forecast (yes/no).
- `SavedView` (§5.19): canonical presets seeded — "Critical (< 7 days)", "Overstocked", "All Active SKUs".
- `ExportMenu` (§5.30): CSV includes current stock, velocity, predicted demand, days of stock, suggested reorder qty. Intended for buyer handoffs.
- **Inline reorder note** — right-click a row → ContextMenu "Add reorder note" → saves a workspace annotation (§5.6.1) with the SKU and a note; shows in the LineChart as a vertical marker on the annotation date.

## Competitor references

- [Lifetimely](../competitors/lifetimely.md) — mentions stock awareness as a common merchant pain; no dedicated inventory page. We fill the gap.
- [Putler](../competitors/putler.md) — RFM-style stock health signal: "which products are in decline and at risk?" — we translate this to actual unit stock vs forecast.
- [Shopify native inventory](../competitors/shopify-native.md) — shows stock levels but no demand forecast or "days of stock" metric. We extend on top of their data.

## Mobile tier

**Mobile-usable** (768×1024+). KpiGrid collapses to 2 columns. Inventory Table reduces to card-stack on mobile: each card shows Product + Stock Status chip + Days of Stock + Predicted Demand. The LineChart collapses to a sparkline embedded in the card. BarChart deferred to desktop with "View category breakdown on desktop" link.

## Out of scope v1

- **Purchase order (PO) creation** — read-only analytics; no write-back to suppliers or ERP.
- **Multi-warehouse / location inventory** — v1 treats stock as a single pool per workspace. Shopify locations API is additive; deferred to v2.
- **Supplier lead time tracking** — reorder suggestion is qty only, not date-based. v2 can incorporate configured lead times.
- **Replenishment alerts via email/Slack** — the `TriageInbox` (§5.22) fires a "Critical: [SKU] has < 7 days stock" item; a dedicated digest channel ships in v2.
- **Backorder / pre-order stock accounting** — stock quantity reflects available units only; backorder tracking is a platform-native concern, not replicated here in v1.
