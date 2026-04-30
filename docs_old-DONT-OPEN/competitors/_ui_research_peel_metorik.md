# Peel Insights & Metorik — UI/UX Detail Research

> Live web research conducted April 2026. Sources: official help centers, product pages.

---

## Peel Insights

### Navigation
Left sidebar: Magic Dash (AI dashboard generator) / Essentials / **RFM Analysis (app homepage)** / Audiences / Templates Library / Dashboards / Reports / Metrics.
Billing/Account at bottom via mascot "Pal" icon.
Dark navigation panel with white text. Clean, modern, enterprise aesthetic.

### Main Dashboard
- Free-layout grid of draggable widgets
- Widget types: line charts / cohort tables / cohort curves / pacing graphs / number trackers (single-number "tickers")
- Each widget can have title + description + notes for storytelling
- Purple "Share" button → read-only sharing link
- Pre-built dashboards: Customer / Subscriptions / Marketing / Multitouch Attribution

### RFM Analysis (App Homepage = Default Landing)
- **5×5 fixed-size grid**: squares are **uniform size** regardless of customer count (not bubble-sized)
- X-axis: Recency (days since last order, 5 buckets — most recent on RIGHT)
- Y-axis: Combined Frequency + Monetary Value (5 buckets — high F+M at TOP)
- 10 named segments as labeled squares across the grid:
  Champions / Loyal Customers / Potential Loyalist / New Customers / Promising / Needs Attention / About to Sleep / Can't Lose Them / At Risk / Hibernating
- **Directional metaphor**: "Customers start bottom-right, goal is top-right" — Champions are top-right
- **Interaction**: click any segment square → modal opens → name the audience + choose how many customers → creates static audience for export (Klaviyo or Facebook)

### Cohort Analysis (3 visualization types)

**1. Cohort Heatmap Table:**
- Rows = acquisition cohorts (labeled by month)
- Cols = months after acquisition (Month 1, Month 2, Month 3...)
- 40+ metric options: Repurchase Rate / Returning Rate / Days Since First Order / AOV / Refunds / Discounts / LTV / Churn / MRR / Gross Margin Rate / etc.
- Color shading for pattern recognition — each metric gets its own table
- Cohort customer count visible alongside metric values (redesigned feature)
- Three reading dimensions designed in:
  - Horizontal: individual cohort's lifetime
  - Vertical: same time period across cohort vintages
  - Diagonal: seasonal patterns

Single Cohort Segmentation: drill into one cohort month → compare up to 12 segment values (by SKU/vendor/campaign/discount/location) side-by-side in sortable table

**2. Cohort Curves:**
- Line chart version: each cohort = one retention/LTV curve over time
- Available as a widget type in dashboards
- Visual comparison of which cohorts ramp fastest

**3. Pacing Charts:**
- Unique chart: current-month metric performance vs. projected goal/pace trajectory
- For in-flight campaign monitoring (not retrospective)
- Shows "outperforming / underperforming vs. projection"

### LTV
- Cohort table (same heatmap, LTV metric selected) — cumulative: Month 3 = sum through Month 3
- LTV Growth Rate: MoM growth rate of LTV as separate metric
- Cohort LTV curve: per-cohort lines diverging over time (each cohort = one line)

### Audiences (Segment Builder)
- Multi-filter interface: products / discount codes / location / customer tags / acquisition channel/campaign / subscription status
- Filters from popup menu within report view
- Live count not confirmed from docs — flow appears to be: build filters → create audience → track audience over time
- Activation: direct "Send to Klaviyo" or "Send to Facebook" button
- Explore builder: select metric + segment combinations for custom cross-tabs

### Magic Dash (AI)
- Natural-language input field top-left
- AI selects appropriate viz (line/bar/stacked/pie) and generates dashboard
- "Magic Insights" = AI-written headline summaries styled like newspaper headlines, refreshing weekly
- Three-dot menu: create/share/delete; per-widget: CSV export / filter / grouping / date-range change

---

## Metorik

### Navigation
- **Top navigation bar** (not left sidebar) with Cmd+K command center (Spotlight-style search)
- Main sections: Dashboard / Orders / Customers / Products / Reports (Revenue/Refunds/Sources/Devices/Subscriptions/Discounts/Taxes/Carts) / Cohorts / Engage (email)
- **Color scheme**: Blue-dominant. White backgrounds. Blue hyperlinks. Brand blue in nav elements.

### Dashboard
- **Drag-drop card grid** — default login page. Cards: Segment Totals (aggregate KPI) + Segment Lists (top-N tables: top products, top coupons)
- Standard cards: Net Sales / New Customers / Items Sold / Average Order Size / Visitors / Recent Activity Feed
- Recent Activity Feed (right sidebar): chronological customer signups + sales events
- "Customize" enters edit mode. Multiple named dashboard screens can be saved and switched.
- **TV Mode**: fullscreen display for office monitors

### Cohort Analysis (Metorik's strongest feature)
- **Heatmap matrix**: rows = cohort label (join month, country, first product, first coupon), col 1 = Customers count, cols = Month 1/Month 2/Month 3...
- **Color**: monochromatic BLUE scale — darker blue = higher activity. Not red/amber/green.
- **Toggle at top**: Number ↔ Percentage. Cumulative checkbox on LTV/Profit reports.
- **Summary row above table**: average per-column values across all cohorts ("On average 4.4% of customers order in month 3")
- **Line chart companion**: shows the cross-cohort average curve over time periods
- **10 cohort types**:
  1. Return customer rate
  2. Order count
  3. Average LTV (cumulative per customer)
  4. Lifetime profit (cumulative average profit)
  5. Revenue
  6. Orders
  7. Profit
  8. Average order value
  9. Subscription: Retained MRR by cohort
  10. Subscriber count retention
- **Subscription cohort hover tooltip**: % retained + absolute retained MRR + original cohort MRR + MRR churned in period
- **Grouping options**: Join month (default) / Billing country / First product / First coupon used

### Segment Builder (Key Differentiator)
- Filter builder panel: dropdown to choose field → condition → value
- Numeric fields: between/greater than/less than; Date fields: dynamic periods ("in the past 30 days"); Categorical fields: multi-select
- Combine with AND/OR toggle; nested multi-group support
- **500+ filter types**: order status / payment method / shipping method / billing country/city/state/zip / specific SKU / coupon / UTM source/medium/campaign / customer tags / subscription status / WooCommerce custom fields
- **CONFIRMED LIVE COUNT**: "2,171 customers" decrements in real time as filters added: 2,171 → 251 → 36 → 8
- **CONFIRMED LIVE STATS BAR**: Average LTV / AOV / total count / total spend — all recompute with every filter change
- Results table below filters: sortable, clickable rows to record detail. Totals footer shows key aggregates.
- After building: Save / Export CSV / Add to Dashboard / Create automated email (Engage) / Subscribe to digest

### Saved Segments Page
- Lists all saved segments + Metorik's "suggested segments" (pre-built: "customers with 3+ orders", "at risk of churning")
- Each row: segment name + current matching count + key KPI (Avg LTV for customer segments, Avg AOV for order segments)

### LTV in Metorik
- **Customer report**: 4 aggregate KPI cards at top: Average LTV / Average Order Value / Average Lifetime Orders / Average Lifetime Items
- **LTV by Cohort**: same blue heatmap table, "Average LTV" metric, cumulative
- **Segment LTV footer**: Total Customers in Segment + Total LTV + Average LTV — dynamic as filters change
- No standalone LTV curve page
