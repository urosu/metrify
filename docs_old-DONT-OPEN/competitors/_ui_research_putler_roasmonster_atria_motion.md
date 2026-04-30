# Putler, ROAS Monster, Atria & Motion — UI/UX Detail Research

> Live web research conducted April 2026. Sources: official websites, help centers, G2 reviews.

---

## Putler

### Navigation
Left sidebar: Home / Sales / Products / Customers / Transactions / Subscriptions / Audience / Insights / Time Machine

### Home Dashboard Structure: Pulse + Overview Dual Zones

**PULSE ZONE (top, always current-month, no date picker):**
- **Sales Metrics Widget**: big revenue number + daily average + linear trend sparkline + forecast range (two methods: historical-trend + current-pace) + monthly target progress + YoY comparison
- **Activity Log**: live scrolling feed of ALL transactions across ALL stores (sales/refunds/disputes/transfers/failures). Filter buttons per event type. Multi-store merged into single chronological stream.
- **Three Months Comparison**: 4 green/red colored summary boxes — visitors, CVR, ARPU, revenue for last 90d vs. prior 90d
- **Did You Know**: daily-refreshing growth insight card

**OVERVIEW ZONE (below, date-picker-controlled):**
- Net Sales card with daily averages + historical comparison %
- Customer Metrics card (orders, unique customers, ARPPU, dispute/fail counts)
- Website Metrics card (CVR, one-time vs. repeat pie chart)
- Subscription Metrics card (MRR, churn rate, active subscriptions)
- **Top 20% Customers** and **Top 20% Products** (Pareto widgets: list top revenue contributors with % share)

### Sales Dashboard
- Metrics row: Net Sales / Daily Average / Orders / Revenue per Sale
- **Sales Breakdown Chart**: time-series line/bar. Green dots = peaks. Red dots = lows. Overlapping series: orders + gross sales + refunds + net sales.
- **Sales Heatmap (signature Putler element)**: 7 rows (days of week) × 24 cols (hours of day). Each cell color-coded by sales intensity — darker = more sales. Sits in upper corner of Sales Dashboard.

### Customer Dashboard
- **RFM Chart (Interactive 2D colored-zone chart)**:
  - X-axis: Recency (0–5)
  - Y-axis: Frequency + Monetary combined (0–5)
  - 11 named segments as **distinct colored rectangular zones** across the grid
  - Segments: Champions / Loyal / Potential Loyalists / New Customers / Promising / Need Attention / About to Sleep / At Risk / Can't Lose Them / Hibernating / Lost
  - Click a zone → customer list below
- Three line-chart series: New / Lost / Returning customers over date range
- Pie chart: one-time vs. repeat customer split
- Bar chart: daily new customer acquisition

### Products Dashboard
- Product leaderboard (ranked list) with star-marking for top-20% performers, per-product sparklines, refund rates, predicted monthly sales, variation performance breakdown

### Unique Patterns
- **Pulse + Overview dual-timeframe**: Structural UI decision — Pulse = now (always current month), Overview = any date range. "Right now" context always visible before drilling.
- **80/20 Pareto widgets**: "Top 20% Products" and "Top 20% Customers" are always-on widgets, not hidden behind sort
- **Time Machine Dashboard**: replay any historical period as if it were live
- **Multi-source aggregation**: 17+ sources (PayPal/Stripe/Shopify/WooCommerce/Amazon/eBay/Etsy/GA4/GSC/Mailchimp etc.) deduplicated and currency-normalized (36+ currencies)
- **Putler Copilot (beta)**: AI chat overlay on dashboard

---

## ROAS Monster

### Core Philosophy: Product-as-Primary-Axis
Every view starts from a product SKU, then fans out to stores, ad accounts, countries. NOT campaign-first or platform-first.

### Navigation
Core views: Full Overview / Winners & Losers / Real ROAS/CPO / Product Pack / Facebook Dashboard / Google Dashboard / Ad Lab / Advertiser Success

### Full Overview Dashboard
- Above fold: total ROAS, CPO, sales, ad spend, order count, AOV, new ads count — merged across ALL shops + ad accounts
- Comparison: vs. 24 hours prior (not previous period)
- 4 hierarchy levels available: Product / Shop / Country / Total
- Anomaly detection highlighting active

### Winners & Losers Dashboard
- Products listed with **color-coded numbers**:
  - Red = below target ROAS/CPO (loss)
  - Blue = at-target
  - Green = above target (profit)
- Sorted by sales, Timeframe selector, Emojis on campaigns as orientation markers

### Unique Patterns
- **Real ROAS/CPO**: total revenue from product ÷ total ad spend for product (cross-platform, cross-store). Bypasses pixel attribution errors.
- **Target ROAS/CPO line**: user-defined threshold; everything above/below shown in green/red/blue. Not a rolling benchmark.
- **TV Mode**: presentation mode for wall-mount/office screens
- **Chrome Extension**: inline ROAS Monster data overlay inside Meta + Google Ads Manager
- **Advertiser Success**: per-advertiser scorecards — ROAS / CPO / ads created / productivity comparison across time periods
- Platforms: Shopify + WooCommerce + Magento 1&2. Ad platforms: Facebook + Google only.

---

## Atria

### Navigation
Left sidebar: Analytics → **Radar** (default landing) / Inspo (ad library) / Creation (Clone Ad / generation) / Assets.
Raya AI chat button: bottom-left corner.

### Radar Dashboard (Core View)
- **Thumbnail grid** of all creatives from last 90 days
- Each card:
  - Full-bleed creative image/video thumbnail
  - **Letter grade badge** (A–D) top corner — composite performance score
  - Stat overlay: ROAS / CTR / Spend / AOV visible at glance
  - **Triage badge**: Winner / High Iteration Potential / Iteration Candidate
  - **"Iterate" button**: one-click AI improvement trigger
  - Hover: AI recommendation summary + credit cost transparency before generating

### Radar Tabs
Horizontal tab bar: **Winners / High Iteration Potential / Iteration Candidates** — each tab filters the same grid to that tier

### Single Creative Detail View
- Three tabs: Performance / Persona / Recommendations
- Performance: **4 individual dimension grades** — Conversion / Hook / Retention / CTR (each gets its own letter grade A–D)
- Issues listed as itemized recommendations (weak CTA, unclear value prop, missing urgency)

### Inspo (Ad Library)
- Smart Filters sidebar: Format / Platform / Industry / Theme / Status / Language
- 10M+ ads. Each card: brand profile icon + bookmark + "Clone ad" button.
- **Competitor intelligence per brand**: media mix breakdown / top personas / ad angles / USPs / headline patterns

### Raya AI Agent (bottom-left chat)
- Pre-built Quick Action buttons + free-text input
- Outputs: Google Doc reports with pulled ad cards + visual graphs
- **Proactive delivery**: posts weekly concept batches + competitor alerts without being prompted

### Unique Patterns
- **Three-tier Triage + one-click iterate**: see the grade → understand why → generate improvement in 1–2 clicks
- **4-dimension letter grades**: normalizes noisy metrics into actionable letter grades with context
- **Semantic search in Inspo**: search by concept/strategy, not keyword text

---

## Motion (Creative Analytics)

### Default Landing View (Above the Fold)
Two stacked zones:
1. **Performance Shifts** (top): horizontal rows categorized as **Scaling / Declining / Recently Launched / Recently Paused**. Each row = horizontal strip of creative thumbnails with key metric + directional momentum. Week-over-week delta view, not current-snapshot.
2. **Leaderboard** (below): Top 10 ads for current week. Each card: rank number + **rank change indicator vs. prior week** (↑/↓ or delta number) + thumbnail + primary metric.

**Ritual design intent**: open Motion → see shifts → see leaderboard → plan the week.

### Report Types
1. Top Performing Ads — thumbnail grid + table
2. Comparative Analysis — trend lines across multiple accounts/creative sets
3. Leaderboard — weekly ranked list with momentum arrows
4. Launch Analysis — recently launched creatives: scaling early / declining / stalled
5. Winning Combinations — pairings (copy + visual + landing page) that consistently outperform

### Creative Insights Modal (Key Differentiator)
- **Split-screen**:
  - Left pane: **video player** (the actual ad creative)
  - Right pane: two stacked charts:
    1. **Video retention line graph**: CTR (TikTok) or watch ratio (Meta) over video duration in seconds — teal + purple lines on dual Y-axes
    2. **Gender & age breakdown bar chart**: segmented by age groups with Male/Female/Unknown color coding
- Connects creative execution (the video) to audience behavior (where drop-off happens, which demographics respond)

### AI Tags
- Auto-applied across 8 key categories: format / hook type / visual style / etc.
- Visible as tag chips on creative cards, filterable in table view

### Unique Patterns
- **Performance Shifts as hero**: momentum first, not raw rank. Replaces "what's my ROAS?" with "what's changing?"
- **Rank change indicators**: transforms leaderboard from static snapshot to trend-aware instrument
- **Retention curve in split-screen modal**: connects creative to viewer behavior at frame level
- **Winning Combinations**: treats creative strategy as a system (format × hook × landing page), not isolated ads
- **Presentation-first**: "one-click snapshots" and "downloadable GIFs" — designed around weekly agency/brand reporting ritual
