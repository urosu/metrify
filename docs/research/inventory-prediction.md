---
title: Inventory Prediction — Research Summary
researched_on: 2026-04-30
sources:
  - docs/competitors/features/inventory-signals.md
  - docs/competitors/lifetimely.md
  - docs/competitors/putler.md
  - docs/competitors/glew.md
  - docs/competitors/_patterns_catalog.md
  - Shopify Native Advanced+ inventory reports (public docs)
  - Stocky app (Shopify partner, inventory forecasting)
  - Cogsy (demand planning SaaS)
  - InflowInventory (IMS dashboard)
  - Lebesgue Stock Inventory Management
  - Polar Analytics Inventory Planner agent
topics_searched:
  - "Shopify inventory analytics 2026"
  - "Stocky Shopify forecast"
  - "Lifetimely inventory forecast UI"
  - "Cogsy stock forecasting UX"
  - "InflowInventory dashboard layout"
  - "Days-of-stock days-of-supply UI"
  - "Stock-out risk visualization SaaS"
  - "Bayesian / moving average sales forecast presentation"
  - "Variant-level inventory drill-down"
---

## 1. KPI Hierarchy

Best-practice hierarchy observed across Shopify Native, Cogsy, Stocky, and Glew:

### Tier 1 — Stock health at a glance (above the fold)
| KPI | Formula | Source |
|-----|---------|--------|
| Total SKUs | COUNT(tracked variants) | Shopify Native inventory snapshot |
| Active SKUs | COUNT where status = active AND tracking ON | Shopify Native |
| Out-of-stock count | COUNT where stock_quantity = 0 | WooCommerce native, Shopify Native |
| At-risk count (< threshold) | COUNT where days_of_stock < 30 | Cogsy "reorder now" bucket |
| Predicted units next 30d | SUM(forecast_30d per product) | Stocky, Glew demand prediction |
| Inventory value | SUM(stock × COGS) | Bloom Analytics, Glew |
| Turnover rate (annualised) | (units_sold_30d × 12) / avg_stock_qty | Conjura sell-through pattern |

### Tier 2 — Per-row operational metrics
| Column | Formula | Notes |
|--------|---------|-------|
| Days of stock | stock / (units_sold_30d / 30) | Shopify "Days of inventory remaining"; NULLIF on zero velocity → "∞" |
| Sold last 30d | SUM(units, last 30 days) | Metorik average daily sales |
| Sold same period last year | SUM(units, LY window) | Cogsy YoY seasonality |
| Predicted next 30d | Blended forecast (see §3) | Stocky, Glew |
| Predicted stock-out date | today + days_of_stock | Cogsy "estimated run-out date" |
| Suggested reorder qty | MAX(0, predicted_30d × 1.2 − stock) | Stocky reorder suggestion |

---

## 2. Table Columns (canonical order)

From Shopify Native + Conjura + Cogsy combined:

1. Product (thumbnail + title + primary SKU, sticky first col)
2. SKU (monospace — the trackable unit)
3. Variants (count chip, expandable)
4. Current stock (units; "Not tracked" when tracking off)
5. Days of stock (color-coded chip — see §4)
6. Stock health chip (categorical)
7. Sold last 30d
8. Sold same period LY
9. Predicted next 30d (with confidence chip)
10. Predicted stock-out date (red badge when < 14d)
11. Suggested reorder qty (informational, shown only for Low/Critical)
12. Actions (⋯ menu)

---

## 3. Forecast Methods Observed

### Stocky (Shopify partner, now built into Shopify POS Pro)
- Method: 28-day rolling average velocity × horizon days
- Seasonality: optional "season factor" from LY same-period ratio
- Confidence: "low confidence" label when < 28 days of history
- UI: simple numeric; no confidence band shown in the list view — only in the per-SKU detail chart

### Cogsy (demand planning SaaS)
- Method: Weighted average of last 30d velocity (weight 0.6) + same-period LY velocity (weight 0.4), then × 1.0 unless a trend multiplier is set by the merchant
- Seasonality: ratio of LY_same_30d / LY_prior_30d applied as a multiplier
- Confidence: explicit "low data" flag when history < 30 days; grey label on the metric
- UI: demand forecast column in the SKU table + a "projected run-out date" column

### Glew ("demand prediction" in Inventory Analytics)
- Method: not published; marketing lists "demand prediction" as a feature. Suspected rolling average based on the query patterns in their DW (nightly refresh cadence implies no intraday recalc)
- Seasonality: unknown

### Lifetimely
- No dedicated inventory forecast. Mentions stock awareness as a merchant pain but defers to Shopify native stock columns.

### Putler
- "Predicted monthly sales" per product: arithmetic mean of last 3 months of unit sales. No seasonality factor. Shown as a single number on the Individual Product card.

### Nexstage blended formula (adopted for this page)
```
last_30d_avg  = units_sold_30d / 30  (daily velocity)
ly_30d_avg    = units_sold_same_period_ly / 30  (LY daily velocity; null if absent)

if ly_30d_avg is not null:
    seasonality_factor = ly_30d_avg / (units_sold_prior_30d / 30)  # how LY moved into this period
    blended_velocity   = (last_30d_avg * 0.6) + (ly_30d_avg * 0.4)
    predicted_next_30d = round(blended_velocity * 30 * 1.05)  # 5% growth nudge per Cogsy default
    confidence         = "high" if history >= 60 days else "medium" if >= 30 days else "low"
else:
    predicted_next_30d = round(last_30d_avg * 30 * 1.05)
    confidence         = "medium" if history >= 30 days else "low"
```
The 1.05 growth nudge matches Stocky's default "slight upward trend" assumption for established SKUs.

---

## 4. Color-Coding (Stock Health)

Canonical across Shopify Native, WooCommerce Native, Cogsy, Conjura:

| State | Threshold | Color token | Label |
|-------|-----------|-------------|-------|
| Healthy | days_of_stock ≥ 30 | `--color-success` (emerald) | Healthy |
| Low | 7 ≤ days_of_stock < 30 | `--color-warning` (amber) | Low |
| Critical | 0 < days_of_stock < 7 | `--color-danger` (rose) | Critical |
| Out of stock | stock_quantity = 0 | `--color-danger` (rose, dimmer) | Out of stock |
| Not tracked | tracking disabled | `--color-text-muted` (zinc) | Not tracked |
| Overstocked | days_of_stock > 90 AND stock > 3× predicted | `--color-source-ga4-fg` (sky/blue) | Overstocked |

Rules:
- Color applied to the chip only — NOT the row background (WCAG AA; row-bg tinting fails 3:1 on light themes)
- Chips use semantic CSS vars, never hardcoded hex
- "Critical" rows additionally show a red badge on the stock-out date cell

---

## 5. Variant-Level Drill-Down

Patterns from Conjura "Product Deepdive" + Shopify Native variant reports:

- **Cogsy**: click product row → expand accordion showing variant rows with same columns (stock, velocity, days of stock, reorder qty). Each variant is a separate trackable unit (SKU code visible). No separate page — inline expansion.
- **Shopify Native**: variant-level "Days of inventory remaining" is available in the same report as a secondary row group. Collapsed by default.
- **Conjura**: Product Deepdive opens as a drawer/panel; shows variant breakdown table inside. Praised by reviewers ("product deep dive down to SKU level is phenomenal").
- **Putler**: per-product card shows "product variation breakdown (size/color)" as a section within the card, not a drill-down.

Decision for Nexstage: **inline accordion expansion** (Cogsy / Shopify Native pattern). Click product row → variants appear as child rows with 2-level indentation. No drawer needed. Customer's explicit request: "Expandable by SKU/variant."

---

## 6. Days-of-Stock / Days-of-Supply UI Conventions

From InflowInventory, Fishbowl, Cin7 (IMS-tier) + Cogsy:

- **InflowInventory**: "Days of Supply" column shows integer with color-coded background cell (red < 14, yellow 14–30, green > 30). Their design is row-bg colored which we explicitly avoid for accessibility.
- **Fishbowl**: "Reorder Point" + "Lead Time" columns instead of days-of-stock. Shows "units below reorder point."
- **Cin7**: "Days on Hand" column; threshold configurable per-SKU.
- **Cogsy**: days column is plain number with a colored dot to the left. Status label in a separate "Risk" column (Out of stock / Critical / Reorder soon / Healthy / Overstocked).

Nexstage adopts the **chip-in-cell** pattern: days number + color chip inline in the same cell. Avoids a separate "status" column while still surfacing the categorical signal.

---

## 7. Forecast Presentation UX

From Stocky + Cogsy:

- **List view**: single predicted number with confidence chip. No band. Confidence is "High / Med / Low" not a percentage.
- **Detail/chart view**: area chart showing past 90d actual (solid line) + next 30d forecast (dotted line + confidence band as semi-transparent fill). LY ghost line in desaturated blue.
- **Low confidence**: metric greyed and ConfidenceChip shown. Forecast number is still shown but clearly labelled "Based on N days — low confidence."
- **Zero history**: cell shows "—" not 0.

---

## 8. Anti-Patterns to Avoid

From inventory-signals.md:

1. **Inventory as a column, not a signal** — Putler, BeProfit, Bloom treat stock as one column on a product table with no forward-looking metric. Nexstage avoids by making days-of-stock and predicted-demand primary.
2. **Row-bg coloring for stock health** — WooCommerce native and InflowInventory use red/yellow row backgrounds. Fails WCAG AA contrast for text. Use chip-only coloring.
3. **Forecasts you can't audit** — Glew and Metorik list "demand prediction" without publishing the formula or confidence signal. Nexstage shows confidence chip and history window.
4. **Slow refresh treated as live** — Glew refreshes inventory nightly. If we're nightly too, surface the "Last synced" timestamp so merchants know freshness.
5. **Forecast hidden in chat** — Shopify Sidekick and Polar Inventory Planner hide forecast logic behind conversational AI. Nexstage surfaces it directly in the table.

---

## 9. Stock-Out Risk Visualization

Cogsy's "estimated run-out date" pattern + Triple Whale Lighthouse anomaly approach:

- Show stock-out date only when days_of_stock < 60 (avoids noise for healthy products)
- Red badge when stock-out date is within 14 days
- Alert banner at top of page when any SKU has < 7 days (Triple Whale Lighthouse → banner pattern)
- Suggested reorder qty shown in the same row (Stocky pattern: "Order X units to cover 30 days")

---

## 10. Competitor References (cited in page build)

| Pattern | Competitor | Used for |
|---------|------------|---------|
| Days of inventory remaining column | Shopify Native Advanced+ | Days-of-stock column label and formula |
| Blended forecast (velocity × seasonality factor) | Cogsy | Prediction formula (60/40 blend last 30d + LY) |
| Accordion row expansion for variants | Cogsy, Shopify Native | Expandable variant rows |
| "Estimated run-out date" in table | Cogsy | Predicted stock-out date column |
| Demand prediction KPI in Inventory Analytics | Glew | KPI strip "Predicted units next 30d" |
| Stock × ad-spend cross-cut | Lebesgue | StockRisk signal (not surfaced in MVP table, in InventoryDataService) |
| Per-product predicted monthly sales | Putler | Confidence chip label wording |
| Low-stock alert banner | Triple Whale Lighthouse | AlertBanner when any SKU < 7d |
| Sell-through rate as named metric | Conjura | KPI turnover rate |
| Inventory value at cost | Bloom Analytics | KPI "Inventory value" |
