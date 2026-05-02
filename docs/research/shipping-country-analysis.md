# Shipping Cost Analysis by Country — Competitive Research

> Researched 2026-04-30 for customer feature #3: shipping cost by country with returns, COD, and what-if simulator.

---

## 1. KPI Hierarchy

### Primary KPIs (show in summary cards above table)
| KPI | Formula | Notes |
|-----|---------|-------|
| Avg shipping cost (customer) | `SUM(shipping_charged) / orders` | What the customer paid at checkout |
| Avg shipping cost (you) | `SUM(carrier_cost) / orders` | Carrier invoice; difference = passed-through surcharge |
| Blended return rate | `refunds / orders` | Gross returns, not net revenue impact |
| COD penetration % | `cod_orders / orders` | High in IT/GR/RO/PL/TR; drives working capital risk |
| Contribution margin per country | `Revenue − COGS − carrier_cost − transaction_fees − return_cost` | Excludes OpEx allocation; country-level only |

### Secondary KPIs (table columns)
- COD cost per order (bank fee + failed delivery handling, typically €1.50–4.00)
- Avg shipping speed (days) — from carrier API or order → delivered timestamp
- Return reasons distribution (top 3, drawer only)
- Carrier mix (% by carrier, drawer only)

---

## 2. Competitor Patterns

### Lifetimely — Country P&L breakdown
- **Pattern:** Classic income-statement rows × country columns; click any cell → orders contributing to that intersection.
- **Pattern catalog ref:** `_patterns_catalog.md §Pattern: Income statement (classic P&L)`
- **Nexstage delta:** Lifetimely shows static rows; we add live what-if knobs that recompute contribution margin column in real time.
- **Source:** `docs/competitors/lifetimely.md` — P&L drill-down on any row.

### ROAS Monster — Four-level hierarchy (Total → Country → Shop → Product)
- **Pattern:** Country as a first-class navigation level, not just a filter dimension.
- **Pattern catalog ref:** `_patterns_catalog.md §Pattern: Four-level hierarchy (Total → Country → Shop → Product)`
- **Nexstage delta:** We don't need four levels — one dedicated shipping-analysis page with per-country rows is sufficient.
- **Source:** `docs/competitors/roasmonster.md`.

### Shopify Native — Shipping zone reporting
- **Pattern:** Shopify's Finance → Shipping report shows shipping charged, shipping cost (if carrier-calculated), and net shipping. No contribution margin, no what-if.
- **Nexstage delta:** We go further: carrier cost vs charged delta, COD penetration, return rate per country, and a what-if simulator. Shopify paywalls this behind Advanced tier — our base plan includes it (anti-Shopify paywall pattern per `docs/competitors/shopify-native.md`).

### Polar Analytics — Profitability dashboard
- **Pattern:** Waterfall (Revenue → Refunds → COGS → Shipping → Fees → Ad Spend → Net Profit) with color-coded status.
- **Pattern catalog ref:** `_patterns_catalog.md §Polar Profitability Dashboard`
- **Nexstage delta:** Polar's waterfall is workspace-level; we surface it at country granularity and add a live scenario slider.

### Glew — Geographic profit by country
- **Pattern:** Table of countries with revenue, orders, AOV, and gross profit. Sortable. No shipping cost breakdown, no COD, no returns.
- **Nexstage delta:** Full shipping cost decomposition (charged vs carrier cost), returns, COD, and contribution margin. Glew is read-only; we add what-if knobs.

### Metorik (WooCommerce) — Country breakdown
- **Pattern:** Orders by country table, revenue, avg order value. No shipping profitability angle.
- **Nexstage delta:** Same plus shipping + COD + return modelling.

### Triple Whale — Geo-level ROAS
- **Pattern:** Country filter on the main dashboard; no dedicated country profitability table.
- **Nexstage delta:** Dedicated surface with contribution margin as the primary metric.

### Peel Insights — Geographic cohort retention
- **Pattern:** Retention curves by country — which countries have higher LTV. Useful companion.
- **Nexstage delta:** We focus on shipping economics, not LTV; these are complementary.

---

## 3. What-If Simulator UX Research

### Klaviyo — Retroactive recompute banner
- Knob changes in settings trigger a `"Recomputing metrics…"` banner and re-render the affected metrics.
- **Nexstage pattern:** Same; knob changes recompute the contribution margin column client-side (no server round-trip since we're on mock data; future: debounced API call).
- **Source:** `docs/UX.md §6 interaction conventions`.

### Lifetimely — Scenario forecasting (v2 reference)
- Lifetimely has a scenario builder where you can change CAC, margin, etc. and see how LTV:CAC moves.
- **Nexstage:** We implement a simplified 4-knob version scoped to shipping economics.

### Shopify — Free shipping threshold A/B testing (no analytics equivalent found)
- Shopify lets merchants set free shipping thresholds in shipping rules but does not show the P&L impact of doing so.
- **Nexstage:** The what-if simulator directly fills this gap — merchants can model the margin impact of a free-shipping-at-$X campaign per country.

---

## 4. Table Columns (canonical for this page)

| Column | Type | Notes |
|--------|------|-------|
| Country | Code chip | ISO-2, no flag emoji per token rules |
| Orders | Integer | Period count |
| Revenue | Currency | Period sum |
| Avg shipping charged | Currency | What customer paid |
| Avg carrier cost | Currency | What you paid; red when > charged |
| Returns | Integer | Count |
| Return % | Percentage | Returns / orders |
| COD % | Percentage | COD orders / total orders |
| COD cost / order | Currency | Only shown when COD % > 0 |
| Avg speed (days) | Decimal | From carrier data |
| Contribution margin | Currency | Revenue − COGS − carrier − fees − return_cost |
| Status | Chip | Profitable / Marginal / Loss |

---

## 5. Placement Decision

**Chosen placement: `/tools/shipping-analysis` (new standalone route)**

### Reasoning

1. **Settings/costs/shipping** is the *rule definition* surface. This page is *analysis* of what actually happened — fundamentally different intent.
2. **/profit** already has a "Breakdown by Country" BarChart dimension and a "Shipping" waterfall bar. Merging in a 12-column data table + 4 what-if knobs + drawer would overload the profit page's focus on P&L structure.
3. **Dedicated tool route** matches the `/manage/*` and `/holidays` patterns — bounded analytical tools live outside the main data pages.
4. **Competitor precedent:** Shopify's shipping reporting is a separate Finance → Shipping sub-page. Metorik similarly separates the shipping report from the P&L. Neither embeds it in the main profitability page.
5. **COD + what-if simulator** are operationally-focused interactions (the merchant is planning campaigns or evaluating carrier contracts) — not the same cognitive context as reviewing overall P&L structure. Separate page = cleaner mental model.
6. The `/profit` page already owns contribution margin as a KPI; the shipping analysis page drills into the *inputs* that drive it, at country granularity with modelling.

### URL: `/{workspace}/tools/shipping-analysis`
### Controller: `app/Http/Controllers/Tools/ShippingAnalysisController.php`
### Page: `resources/js/Pages/Tools/ShippingAnalysis/Index.tsx`

---

## 6. Free Shipping vs Free Returns Campaign Analysis

### Research findings
- **Returnly / Loop Returns** dashboards show return rate by country + return reason mix. Neither offers a contribution margin impact simulator alongside it.
- **ShipBob analytics** shows cost-per-shipment by zone but not a what-if for free shipping thresholds.
- **Narvar** (enterprise) has return rate by country + reason distribution in their insights dashboard — closest analogue. No public UI.
- **Nexstage opportunity:** The 4-knob what-if (free shipping threshold + free returns toggle + COD surcharge + carrier cost %) is genuinely novel among SMB analytics SaaS. The closest is Lifetimely's scenario forecasting (LTV:CAC inputs) but it doesn't touch shipping economics.

### COD-specific patterns
- COD is high in IT (40–55% of orders), GR (50–65%), RO (60–70%), PL (30–40%), TR (55–70%), UA (75%+).
- COD failure rate (undelivered/refused) averages 15–25% in high-COD markets — a hidden cost rarely surfaced in analytics tools.
- **Nexstage delta:** Surface COD failure rate as a column; model the cost of adding a COD surcharge (passed to customer) in the what-if knob.

---

## 7. Geographic Profitability Matrix

### Pattern: Red-to-green status system
- `Profitable`: contribution margin > 15%
- `Marginal`: contribution margin 5–15%
- `Loss`: contribution margin < 5% (or negative)
- Per `_patterns_catalog.md §Red-to-green gradient` — "semantic color that tells the merchant what to do next."
- Status chip color: CSS vars `--color-status-success`, `--color-status-warning`, `--color-status-danger` (no hardcoded colors).

### Pattern: Sortable table with searchable country picker
- Country picker = single-select with search, consistent with Holidays page's country picker pattern (referenced in the brief).
- "Drill into one country" → opens DrawerSidePanel with: daily orders sparkline, top 5 products, top 3 channels, return reason pie, carrier mix bar chart.
