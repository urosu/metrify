# Triple Whale & Northbeam — UI/UX Detail Research

> Live web research conducted April 2026. Sources: official docs, help centers, blog posts.

---

## Triple Whale

### Navigation
Left sidebar. Sections: Workspaces (folder containers), Pods View, Metrics, Product Analytics, Product Journeys, Cart Analysis, Pixel (attribution + Creative Cockpit), Ops, Moby AI (chat). Gear icon at bottom for settings/store switching.

### Summary Dashboard
- Source-grouped horizontal bands: "Store Metrics" | "Meta" | "Google" | "Klaviyo" | "Web Analytics" | "Custom Expenses"
- Each band = row of draggable, resizable metric tiles: large bold number + label + small delta (↑/↓ arrow + %)
- Pin icon on hover promotes any tile to the top "Pinned" section
- Any section can pivot to flat table view
- No sparklines in tiles by default — number + delta only
- Custom Metrics Builder: drag-and-drop formula builder for derived metrics

### Attribution / Pixel Dashboard
- **Sortable table** at campaign/adset/ad level. Key columns:
  - Spend
  - **Platform ROAS** (with platform logo: Facebook "f", Google "G")
  - **GA ROAS** (Google Analytics icon)
  - **Pixel ROAS** (Triple Whale floppy disc logo)
  - Pixel Orders, Pixel CV, Pixel CPA, NC ROAS, NC CPA, Orders Overlap
- **Three ROAS columns side-by-side** = the core visual for source disagreement. Users see the gap instantly.
- Click Pixel ROAS value → drill to list of attributed orders → click customer → customer journey modal (full touchpoint sequence with timestamps)
- **Attribution model switcher**: toggle above table (First Click / Last Click / Linear / Total Impact), table updates without reload
- **Channel Overlap visualization**: Venn diagram with overlapping circles per channel (Meta, Google, TikTok). Circle size = customer volume. Overlap zones = multi-touch customer counts.

### Creative Cockpit
1. **Hero: dual-axis line chart** at top. Top 3 creatives auto-selected. Left Y = one metric (solid line per creative), Right Y = second metric (dashed line). 20+ metrics selectable from dropdowns.
2. **Ads Comparison** (below, 3 view modes via icon toggle):
   - **Card view**: thumbnail grid. Below each thumbnail: Spend / ROAS Platform+Pixel / CPA + **color scale bars** (red→green gradient behind each number, calibrated to min/max of visible set)
   - **Bar chart view**: comparative horizontal/vertical bars for both metrics side-by-side across creatives
   - **Line view**: trend lines per creative over time
3. **Creative Highlights**: one-click callout surfacing "best by metric X"

### Cohort Analysis
- **Heatmap table**: rows = acquisition month, cols = Month 1 / Month 2 / Month 3...
- Cell value: retention %, repeat purchase rate, or cumulative LTV (toggle via checkboxes)
- Color: green family gradient — darker = higher retention. Diagonal patterns = seasonal trends.
- Additional: 60/90/120-day LTV bar or line chart, Product Journey Sankey diagram, time-between-orders histogram

### Benchmarks Dashboard
- Peer data from 20,000+ Triple Whale customers
- Visualization: **gauge/percentile bars** or **grouped bar charts** (your value vs. P25/P50/P75)
- Filterable by: industry vertical, AOV segment, GMV bracket
- Metrics: CPA, CPC, CPM, CVR, CTR, MER, ROAS, AOV across Meta/Google/TikTok/Blended

### Product Analytics (Scatterplot)
- **4-quadrant bubble scatterplot**
  - X: CAC Index (1–100, best=100)
  - Y: ROAS Index (1–100, best=100)
  - Bubble size: ad spend
  - Quadrant colors: Green (top-right=scale) / Yellow (top-left) / Red (bottom-left=cut) / Blue (bottom-right)
- Filter toggle: Product / Platform / Campaign / Ad
- Below: multi-select checkbox table for stacked filters

### Live Orders / Activity Feed
- Real-time scrolling feed: one order per row with attributed channel icon
- Activity Feed: chronological timeline of campaign changes/budget adjustments with timestamps

---

## Northbeam

### Navigation
Left sidebar with icon-based navigation. Telescope icon = Metrics Explorer. Hamburger top-right = Model Comparison overlay.
Day-1 features: Overview Home, Attribution Home (tabs: Sales / Product Analytics / Orders / Creative Analytics), Metrics Explorer.
Day-30-90 unlocks: Northbeam Apex, Profit Benchmarks, Clicks+Deterministic Views.

### Overview Home Page
- **Customizable KPI tile grid**: ROAS, CAC/CPO, Revenue, Spend, AOV, Transactions — add/remove/rearrange
- Each tile: primary number + period-over-period delta (no sparklines)
- **Global controls at top**: date range + comparison period + granularity (Daily/Weekly/Monthly) + **Attribution Model** + **Attribution Window** + **Accounting Mode** (Cash Snapshot vs Accrual)
- **Conversion Lag charts**: line chart showing revenue accruing over 7–90 days after spend. Answers "am I looking at complete data?"
- No sparklines in tiles

### Attribution Home Page (Sales View)
- **Hierarchical left-panel table**: Platform → Campaign → Ad Set → Ad (expandable rows)
- Columns: Spend / Revenue (Northbeam-attributed) / ROAS / New Customer Revenue / Returning Customer Revenue / CAC/CPO / Visitors / Revenue per Visitor / Touchpoints / Orders
- **Stoplights**: Green/Yellow/Red colored circles inline on every row at all hierarchy levels simultaneously
  - Green = exceeds your historical benchmark → scale
  - Yellow = within monitoring range → hold
  - Red = below benchmark → cut
  - Derived from YOUR historical best days (personalized, not industry averages)
- **No platform-reported ROAS column** next to Northbeam ROAS by default. Northbeam philosophy: replace platform reporting entirely, don't compare.
- Model Comparison (hamburger): side-by-side of Clicks-Only / First Touch / Last Touch etc. — separate overlay, not persistent column
- **Right panel**: Profitability sidebar (unlocks after 90 days)
- **Above table**: aggregated line chart with multiple metrics overlaid as colored lines, dual-axis available

### Creative Analytics
- **Creative cards grid**: thumbnail (upper) + heatmap metric cells (lower)
- Metric cells: each cell has a **sliding scale Red→Green** calibrated to visible set. Shows CTR, CPM, ECR, CAC, ROAS
- Cards sorted by spend (descending) by default
- **Expandable chart panel** at bottom: pull up to reveal line or bar chart comparing up to 6 selected ads (checkbox per card = "Show in charts")
- Search/filter/hide-inactive controls in left rail

### Product Analytics
Same quadrant as Triple Whale: CAC Index × ROAS Index, bubble size = spend, 4-color quadrants. Relative indexing (your assets vs each other, not external benchmarks).

### Metrics Explorer
- Entry: pre-built template tiles ("Revenue (1st time) & Spend by Platform" etc.)
- Config panel: attribution model + window + accounting mode + granularity + time period
- **Correlation tiles grid**: each tile = two metrics + Pearson coefficient score. Click tile → reorient analysis around that pair.
- **Multi-line time series**: each metric = one colored line. Dual-axis for scale differences.

### MMM+
- **3D multi-channel interaction chart**: shows how channels interact to influence new customer acquisition
- **Cost curves**: S-curve per channel (spend on X, incremental revenue on Y = diminishing returns)
- **Budget scenario builder**: adjust allocations, see projected revenue impact
- **Forecast accuracy view**: model predictions vs actuals over time

### Key Contrast: How Source Disagreement is Shown
- **Triple Whale**: three ROAS columns side-by-side (Platform / GA / Pixel) — literal number comparison
- **Northbeam**: Stoplights replace comparison — Green/Yellow/Red tells you what to do without needing to compare platform vs Northbeam
