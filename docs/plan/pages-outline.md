# Nexstage Pages Outline

Layout specs, UI elements, and page connections. Formulas and queries are in coding-spec.md.

---

## Navigation

```
Sidebar (persistent, collapsible)
├── Home (Dashboard)
├── Profit                    tabs: P&L | Shipping & Countries
├── Marketing                 tabs: Campaigns | Creatives | Funnel
├── Products                  tabs: Performance | Inventory
├── Orders
├── Customers                 tabs: LTV & Cohorts | Segments | List
├── SEO
├── Site Health               tab: Page Speed (Uptime: v2)
├── ─── (divider)
├── Alerts (badge count)
├── Tools                     tabs: Holidays | UTM | Naming | Calculator
├── Settings                  tabs: Integrations | Costs | Channels | Workspace | Notifications
└── ─── (divider)
    Pinned Views
```

**Top chrome:** Workspace switcher (searchable) | Date range picker + comparison | Data freshness | Notification bell | User avatar

---

## Detail Pattern (all pages)

**Drawer:** Right-side overlay, `w-[min(480px,90vw)]`. 200ms ease-out. Dim background (scrollable). Close: Esc/scrim/X. URL updates via pushState. Focus management.

**Hierarchies:** Inline accordion (Product → SKU, Campaign → Ad Set → Ad). Same columns, 2-level indent. Never modals for data viewing.

---

## Page 1: Dashboard (`/dashboard`)

1. **Alert strip** — actionable, dismissable inline alerts (max 3)
2. **Today-so-far** — running revenue + yesterday-at-this-hour comparison (top-right of KPIs)
3. **KPI row** (8 cards) — Revenue, Net Profit, Ad Spend, MER, Orders, AOV, Sessions, CVR. Each: 28-32px number + delta + sparkline. Click card → hero chart renders that metric.
4. **Hero chart** — time-series with comparison dashed line, annotations, granularity toggle
5. **Source detail** — on drill-in/hover, not always visible
6. **Channel breakdown** — mini table: Channel × Spend × Revenue × ROAS × Delta. Click → `/marketing?channel=X`
7. **Quick panels** — Left: top 5 products. Right: 3 rising + 3 falling (momentum arrows)
8. **Monthly overview** — Date × Ad Spend × Revenue × Orders × Items × AOV × ROAS × Notes (inline editable annotations)
9. **Sales heatmap** — 7×24 day-of-week × hour-of-day grid. Orders/Revenue toggle. White-to-indigo scale. Default: last 30 days.

---

## Page 2: Profit (`/profit`)

### Tab: P&L
1. **KPI strip** (6) — Orders, Revenue inc. VAT, Revenue ex. VAT, Total Cost, Net Profit ex. VAT, Net Profit Margin
2. **P&L table** — rows per coding-spec section 1. Columns: time periods (day/week/month). FX rates on hover. Estimated COGS: amber badge row. Waterfall chart toggle.
3. **ROAS section** — Gross ROAS + Net ROAS
4. **Additional cards** — AOV, Units sold, COGS total, Shipping, Ad spend, Taxes

### Tab: Shipping & Countries
1. **Country table** — Country, Orders, Revenue, AOV, CVR, Avg Shipping Charged, Carrier Cost, Delta (highlighted when negative), Return %, COD %, Margin, Status chip
2. **AOV + CVR analysis per country**
3. **What-if simulator** — 4 sliders: free shipping threshold, free returns, COD surcharge, carrier cost %. Live margin recomputation.

Both tabs: granularity toggle, export (CSV/PDF/Excel), digest scheduling.

---

## Page 3: Marketing (`/marketing`)

### Tab: Campaigns
1. **Filter strip** — Attribution model (Last/First/Linear), window (7d/14d/30d), platform (All/Meta/Google), naming dimensions as chips
2. **KPI row** (6) — Spend, Blended ROAS, MER, Conversions, Attributed Revenue, CPA
3. **Campaign table** — expandable hierarchy (Campaign → Ad Set → Ad). Columns: Spend, Impressions, Clicks, CTR, CPC, CPM, CPA, Conversions, Revenue, Net Profit. Multi-source ROAS (Store/Platform/Real). Fatigue flag. Naming tags. Sortable.
4. **Best campaigns** — top 5 by ROAS with trend

### Tab: Creatives
1. **Thumbnail grid** — creative image primary, metrics below. ROAS, CPA, CTR, hook rate, hold rate, spend. Triage badge (Winner/Iterate/Kill green/amber/rose). Momentum arrows.
2. **Leaderboard toggle** — ranked list with position change
3. **Klaviyo section** — email/flow performance alongside ad creatives

### Tab: Funnel
- Horizontal bar funnel (5 steps) with drop-off %. Source filter chips. Mobile/desktop toggle. Payment method distribution.
- Click step → drawer breakdown (landing pages, products viewed, cart contents, AOV)
- Flag: high cart + low purchase = "possible price resistance"

---

## Page 4: Products (`/products`)

### Tab: Performance
1. **Toolbar** — filter chips, group-by (type/vendor/collection), sort, column picker, date range
2. **Products table** — Thumbnail, Name, Units, Revenue, COGS, Contribution Profit, Margin %, Ad Spend, ROAS, Refund Rate, Repeat Rate, Stock (dot). Expandable to variants. Inline COGS edit. Margin gradient. Low-stock badge.
3. **Quadrant scatter** — X=Margin Index, Y=ROAS Index, size=Revenue. Four quadrants.
4. **Products sold summary** — ranked by units/revenue with delta

### Tab: Inventory
1. **Alert banner** — critically low + overstocked counts
2. **Inventory table** — Product, SKU, Stock, Velocity, Days Remaining, Stock-Out Date, Reorder Qty, Status. Color-coded days. Expandable variants. Inline notes.
3. **Stock forecast chart** (in drawer) — declining line with threshold markers
4. **Sales prediction link** — "Product X predicted to sell 340 but only 180 in stock"

---

## Page 5: Orders (`/orders`)

1. **Filter bar** + saved views (All, Unprofitable, High Shipping, Refunded, Unattributed)
2. **Orders table** — Order #, Date, Customer, Items, Revenue, COGS, Shipping, Fees, Ad Spend, Net Profit, Margin %, Channel, Attribution, New/Returning, Country. Profit color-coding.
3. **Order drawer** — summary, per-item cost breakdown, touchpoints timeline, customer link

---

## Page 6: Customers (`/customers`)

### Tab: LTV
- KPI row: Avg LTV, LTV:CAC, Payback Period, Repeat Rate
- Cohort heatmap with metric picker. Three-view toggle: Heatmap / Curves / Pacing. CAC payback line.
- Filters: channel, first product, discount, country. Filter-vs-baseline overlay.

### Tab: Segments
- Segment tiles (count + LTV + AOV). 5×5 RFM grid. Click cell → customer list. Push to Klaviyo/Meta. Segment builder.

### Tab: Customer List
- Filterable table: Customer, Orders, Spent, LTV, Last Order, RFM Segment, Country. Click → drawer with profile.

---

## Page 7: SEO (`/seo`)

- KPI row: Organic Clicks, Impressions, CTR, Position, Organic Revenue
- Paired tables: Top Queries / Top Pages. Brand/non-brand toggle.
- CTR vs Position scatter. Revenue-per-query table.
- Keyword cannibalization table (queries ranking for 2+ pages, severity badges).
- Cross-filter: click query → page filters, vice versa.

---

## Page 8: Site Health (`/health`)

### Tab: Speed
- KPI row: LCP, INP, CLS (p75, Good/Needs Improvement/Poor). Mobile/desktop toggle.
- URL table: URL, Score (colored ring ≥90 emerald, 50-89 amber, <50 rose), LCP, INP, CLS, Source, Strategy. Poor first. Rose highlight on <50.
- Drawer: Lighthouse dials, opportunities sorted by impact, PSI deep-link.

### Tab: Uptime (v2)
- v2 stub — show "Coming soon" placeholder. Implementation: uptime %, incident log, response time sparkline, TTFB trend.

---

## Page 9: Alerts (`/alerts`)

Inbox list with severity badges. Alert types: metric anomalies, speed drops, source disagreement, integration failures, low stock, RFM migrations. Acknowledge (permanent) / Snooze (temporary). Digest config.

---

## Page 10: Tools

- **Holidays** (`/tools/holidays`) — list/calendar views, country picker, watch toggle, chart annotations
- **UTM** (`/tools/utm`) — form + platform presets + macro templates + saved templates
- **Naming** (`/tools/naming`) — delimiter → dimension slots → live preview → compliance % → export
- **Calculator** (`/tools/calculator`) — margin/breakeven/price calculator

---

## Page 11: Settings (`/settings`)

One route, tabs via `?tab=`. Tabs: Integrations (connection cards + sync health), Costs (COGS/CSV/shipping/fees/opex), Channels (UTM rules + brand keywords), Workspace (name/members/billing), Notifications (alert rules + digest schedule).

---

## Cross-Page Data Flows

1. Dashboard → Marketing: click channel KPI → campaigns filtered
2. Dashboard → Products: click top product → products filtered
3. Marketing → Orders: click campaign → orders filtered by attribution
4. Products/Performance → Inventory: stock badge switches tab
5. Products → Customers: repeat rate → cohorts by first-product
6. Orders → Customers: click customer → profile drawer
7. SEO → Products: organic revenue per product
8. Site Health → Alerts: speed drops + downtime push alerts
9. Tools/Holidays → Dashboard: events as chart annotations
10. Tools/Naming → Marketing: parsed dimensions as filter options
11. Settings/Costs → Profit + Products + Orders: COGS feeds profit

---

## Design System

Design tokens, component contracts, and ECharts configs → `frontend-spec.md`

- **Layout:** Persistent left sidebar, collapsible
- **Typography:** KPI 28-32px, body 15-16px, table 14px min. Tabular/monospace numbers.
- **Font floor:** 14px absolute minimum. Never 11/12/13px.
- **Loading:** Skeleton shimmer for content, small spinner for actions.
- **Errors:** Optimistic UI + undo toast (6-10s).
- **Empty states:** Single sentence + single action. Progress screen during import.
- **Mobile:** Responsive. KPI cards stack. Tables scroll horizontally.
- **Accessibility:** WCAG AA contrast (4.5:1 text, 3:1 large). Focus management. Keyboard nav. aria-labels.
