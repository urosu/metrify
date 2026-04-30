# Polar Analytics & Lifetimely — UI/UX Detail Research

> Live web research conducted April 2026. Sources: official help centers, product pages.

---

## Polar Analytics

### Navigation
Left sidebar with tab sections: Key Indicators / Acquisition / Retention / Products / Subscriptions / Engagement / Custom Reports.
Plus a **folder system** for user-created Dashboards (expandable/collapsible like a file explorer).
Global date range picker top-right — applies to entire page simultaneously.

### Dashboard Canvas
- **Block-based grid**: drag/drop + resize widgets
- Two primary block types:
  1. **Key Indicator card**: metric value (large) + % delta chip (bottom-left corner, context-aware green=good/orange=bad regardless of direction) + "Was $X" prior-period value + optional source icon (Shopify logo) + optional target/goal progress bar
  2. **Sparkline card**: same as above + mini trend line embedded in card body
- **Table block**: sortable columns, color-scale conditional formatting (red→green), export-capable
- **Chart block**: line chart (time series) / bar chart (category comparison) / pie chart (proportion)
- All blocks respond to global date range

### P&L / Profit
- No traditional income statement table. Instead:
  - Metric card grid: Net Sales / Gross Profit / Contribution Margin / MER / COGS
  - Custom tables for breakdown by store/product/country/channel
  - No-code formula builder for custom metrics (e.g. net profit = revenue - COGS - ad spend - shipping)
- A "revenue waterfall bar chart" mentioned in third-party descriptions — appears to be stacked/fall-type bar for daily revenue, not a full P&L waterfall from gross to net
- Closest to P&L: "Profitability" pre-built template = metric card grid + custom-report table by dimension

### LTV Display
- **5 time-bound metric cards**: 30d / 60d / 90d / 180d / 360d LTV — each standard card with delta
- **Cohort Evolution line chart**: X = months since first purchase, Y = cumulative LTV. One cohort at a time, hover for exact values + cohort size.
- Retention tab also shows: Repeat Customer % / New Customer % / Total Customers / Blended AOV / New vs Repeat AOV

### Cohort Analysis (Retention tab)
- **Heatmap matrix table**:
  - Rows = acquisition cohorts by month
  - Cols = 30-day time increments (Month 0, Month 1, Month 2...)
  - Metrics: Total Sales / Customers / Orders / LTV / Gross Margin / Net Sales (user-selectable)
  - Toggle: cumulative vs. non-cumulative; absolute values vs. percentage
  - Color scale: green for above-average, lighter/neutral for below
- **Cohort Evolution Graph** (separate from table): line chart showing how cumulative LTV grows for a selected cohort. Hover shows LTV value + customer count.

### Unique Elements
- Delta chips: **context-aware color** — green = favorable regardless of direction (cost decrease = green, revenue increase = green)
- AI Assistant "Ask Polar": chat panel → returns auto-generated charts or summary text
- Views = named filter presets (visible as chips at top of each tab)
- Incremental testing: lift percentage + confidence interval as dedicated experiment cards

---

## Lifetimely (by AMP / Blend Commerce)

### Navigation (Shopify-embedded)
Left sidebar inside Shopify admin:
- **Profit & Loss** (expandable) → Daily P&L (DEFAULT LOGIN LANDING) / Income Statement / Product P&L
- **Lifetime Value** → LTV Cohorts / Predictive LTV / LTV Drivers / Compare Cohorts
- **Customer Behavior** → Product Journey / Repurchase Rate / Time Between Orders
- **Attribution**
- **Dashboards & Custom Metrics**
- **Benchmarks**

**The login landing page is P&L** — profit-first philosophy.

### Profit Dashboard (Home)
- KPI cards row: Revenue / Product Costs / Marketing Costs / Net Profit — each large number
- Below: time-series line or bar chart for selected metric over date range
- "Command center" / one-glance profit health

### Income Statement (the detailed P&L view)
- **Full accounting-style table** — rows top to bottom:
  - Gross Sales
  - Discounts
  - Returns/Refunds
  - Net Sales
  - Product Costs (COGS)
  - Gross Profit
  - Shipping Costs (customer + outbound)
  - Transaction Fees / Gateway Costs
  - Handling Costs
  - Marketing Costs (per channel: Facebook, Google, TikTok)
  - Other Operating Expenses (custom)
  - Contribution Margin 1 / 2 / 3
  - Net Profit
- **Column structure**: current period value | comparison period value | % change delta
- **Color**: green for positive movements on revenue rows, red/orange for negative — context-aware
- **No chart alongside** — pure table. Daily/weekly/monthly time granularity via date selector.
- Cash-basis accounting: refunds appear on processing date

### LTV Cohort View (the hybrid view)
- **Upper section: staircase/waterfall bar chart**
  - Each bar = one acquisition cohort month
  - Bar height = accumulated LTV at chosen time horizon
  - Older cohorts taller (more data) → staircase shape left-to-right
  - **Green vertical line** = CAC payback marker: user inputs their CAC, green bar appears where average customer revenue crosses that cost
- **Lower section: classic cohort heatmap matrix**
  - Rows = acquisition month, cols = Month 0 / Month 1 / Month 2...
  - 13+ selectable metrics: Accumulated Sales Per Customer / Accumulated Gross Margin Per Customer / Accumulated Contribution Margin / Number of Orders / Repurchase Rate / etc.
  - Color intensity: darker = higher value (gradient per metric range)

### Predictive LTV
- **Line chart**: actual LTV curve (solid) + AI-predicted future LTV (dashed/lighter continuation)
- Controls for CAC target + LTV segment being predicted

### LTV Drivers
- **Ranked table** (not a chart):
  - Attributes: products / customer tags / order tags / discount codes
  - Columns: attribute name | absolute 24-month LTV for customers with that attribute | % lift vs. overall average
  - Color: green shading = positive lift rows, red/orange = negative lift rows
  - Divided into: "Top positive LTV drivers" (top section) + "Negative drivers" (below)

### Compare Cohorts
- Single table with multiple cohort rows (by first product, acquisition channel, discount code, customer tag) all with aligned time columns — compare multiple cohort segments side-by-side in one view

### Product Journey
- **Sankey/noodle diagram**: nodes = products, flows = customer paths between first/second/third/fourth purchase. Flow width ∝ path frequency. Low-volume paths hidden to reduce noise.

### Custom Dashboards
- Drag-drop layout, metric widgets, templates (eCommerce Performance / Acquisition / Retention / Finance)
- Goal/KPI targets per widget
- **Schedulable as daily/weekly email or Slack digest** — the income statement is email-optimized (clean table, no heavy interactivity)
