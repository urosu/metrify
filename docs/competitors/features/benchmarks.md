---
name: Benchmarks
slug: benchmarks
purpose: Tells a merchant whether a metric movement is them or the market, by comparing their KPIs against an anonymized peer cohort.
nexstage_pages: dashboard, performance, profit
researched_on: 2026-04-28
competitors_covered: varos, lebesgue, klaviyo, conjura, lifetimely, northbeam, triple-whale, storehero, rockerbox, repeat-customer-insights, glew
sources:
  - ../competitors/varos.md
  - ../competitors/lebesgue.md
  - ../competitors/klaviyo.md
  - ../competitors/conjura.md
  - ../competitors/lifetimely.md
  - ../competitors/northbeam.md
  - ../competitors/triple-whale.md
  - ../competitors/storehero.md
  - ../competitors/rockerbox.md
  - ../competitors/repeat-customer-insights.md
  - ../competitors/glew.md
  - https://help.useamp.com/category (Lifetimely Benchmarks help)
  - https://help.klaviyo.com/hc/en-us/articles/360050110072
  - https://www.varos.com/blog/new-feature-set-high-low-performers-based-off-north-star-kpi
  - https://www.northbeam.io/features/profit-benchmarks
  - https://www.triplewhale.com/blog/trends-benchmarking
---

## What is this feature

Benchmarks answer the merchant's question "is this number good?" — and behind that, the more anxious version: "is my CPM swing me, or is it the market?" Every store owner can already see their own ROAS, CAC, repeat rate, and AOV in their store and ad platforms; what they cannot see is whether those numbers are top-quartile, median, or alarming relative to similar brands. Benchmarks turn raw KPIs into a relative position, usually expressed as a percentile against an anonymized peer cohort defined by industry vertical, AOV band, GMV/spend tier, and (sometimes) geography.

For SMB Shopify/Woo owners specifically, the friction is two-sided. They lack the data to build their own benchmark (n=1 store) and they lack the operating context to know if a 0.8 first-time AOV repeat-rate is "panic" or "normal for skincare under $50 AOV." Public industry reports (Klaviyo, Shopify, agency PDFs) are quarterly and stale; the value of an in-product benchmark is that it updates as peers report. The hard part is not the chart — it is (a) recruiting enough peers to make the cohort statistically meaningful, (b) defining the cohort correctly so the comparison is apples-to-apples, and (c) showing the user's percentile without leaking the identities of the brands in the cohort.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.total_price`, `orders.line_items`, `orders.customer_id`, `orders.created_at`, `refunds`, `discounts`, `abandoned_checkouts` (used by Varos for AOV, repeat-rate, abandoned-checkout, refund-rate benchmarks per `competitors/varos.md`)
- **Source: Meta Ads API** — `campaigns.spend`, `campaigns.impressions`, `campaigns.clicks`, `campaigns.purchases` (`omni_purchase` / `offsite_conversion.fb_pixel_purchase`), `campaigns.purchase_revenue`; normalized to USD; Varos hardcodes Meta to **7-day click + 1-day view** (`competitors/varos.md`)
- **Source: Google Ads API** — `campaigns.spend`, `impressions`, `clicks`, `conversions`, `conversion_value`; Varos uses **30-day click + 1-day view** for Google (`competitors/varos.md`)
- **Source: TikTok Ads / LinkedIn Ads / Microsoft Ads / Pinterest** — spend, impressions, clicks, video-view metrics (TikTok adds P50 video-view rate per `competitors/varos.md`)
- **Source: Klaviyo** — sends, opens, clicks, conversions per email; deliverability metrics (used for Klaviyo's per-channel benchmarks per `competitors/klaviyo.md`)
- **Source: User-input survey** — Lifetimely uses a **4-question survey** (business model, product type, category, B2B vs B2C) to define the peer group (`competitors/lifetimely.md`); Klaviyo uses Organization industry + AOV + total revenue + YoY growth (`competitors/klaviyo.md`); Varos uses vertical, sub-vertical, AOV, spend tier, geography (`competitors/varos.md`)
- **Source: Computed (per-tenant)** — derived KPIs (CPM, CPC, CTR, CPA/CPP, ROAS, AOV, repeat-purchase rate, refund rate, etc.) computed at the tenant level then aggregated into peer-cohort distributions
- **Source: Computed (peer cohort)** — P25 / P50 / P75 / mean per metric per cohort × time bucket (Klaviyo + Lifetimely both expose 25th/median/75th); minimum-N guardrail to suppress thin samples (Varos called out for "benchmarks are strongest in popular verticals," `competitors/varos.md`)
- **Source: User-input goal/CAC** — Lifetimely's CAC-payback green bar requires user-entered CAC value (`competitors/lifetimely.md`); Northbeam Profit Benchmarks computes target ROAS/MER/CAC against actual contribution margins (`competitors/northbeam.md`)

## Data outputs (what's typically displayed)

- **KPI: User value vs peer percentile** — e.g., "Your blended ROAS = 2.4 (75th percentile)" — used by Klaviyo Performance Highlights, Varos default view, Lifetimely Benchmarks (`competitors/klaviyo.md`, `competitors/varos.md`, `competitors/lifetimely.md`)
- **Distribution: P25 / P50 / P75 per metric** — Klaviyo drills into 25/50/75 (`competitors/klaviyo.md`); Lifetimely shows bell-curve distribution with 25th, median, 75th points (`competitors/lifetimely.md`)
- **Dimension: Industry vertical** — string, ~20+ values (DTC apparel, supplements, skincare, etc.). Triple Whale exposes vertical + AOV band + revenue band as filter rail (`competitors/triple-whale.md`); Klaviyo edits via Organization settings (`competitors/klaviyo.md`)
- **Dimension: AOV band** — explicit Varos axis ("separates low-end, medium-end, high-end products"); also in Triple Whale Benchmarks (`competitors/varos.md`, `competitors/triple-whale.md`)
- **Dimension: Spend / GMV tier** — Varos defends explicitly ("results can significantly change as budgets increase"); Triple Whale uses GMV band (`competitors/varos.md`, `competitors/triple-whale.md`)
- **Dimension: Geography / market** — Varos paid tier exposes geographic-location filter (`competitors/varos.md`)
- **Breakdown: Metric × peer cohort × time** — Triple Whale offers last 7 / 30 day toggle (`competitors/triple-whale.md`)
- **Breakdown: North Star KPI re-ranking** — Varos lets user pick MER / CPP / ROAS as the lens, then re-ranks peers as "high" vs "low" performers and surfaces secondary KPIs of high performers (`competitors/varos.md`)
- **Slice: Top-5 / bottom-5 metrics by user percentile** — Klaviyo Performance Highlights and Benchmarks Overview both use this format (`competitors/klaviyo.md`)
- **Slice: Per-platform** — Triple Whale Benchmarks tabs Meta / TikTok / Google / Bing / X / Amazon / Pinterest / Snap / Blended (`competitors/triple-whale.md`); Varos has per-platform dashboards
- **Cohort size disclosure** — Klaviyo says "roughly one hundred companies similar to yours" (`competitors/klaviyo.md`); Varos "4,500+ brands" (`competitors/varos.md`); Triple Whale "11,000–20,000+" (`competitors/triple-whale.md`); Lebesgue "20,000–25,000" (`competitors/lebesgue.md`)
- **Delta vs goal / target** — Northbeam Profit Benchmarks computes target ROAS/MER/CAC and shows live performance vs target (`competitors/northbeam.md`); StoreHero Goals & Forecasting auto-generates seasonally-adjusted monthly benchmarks with green/red traffic light (`competitors/storehero.md`)

## How competitors implement this

### Varos ([profile](../competitors/varos.md))
- **Surface:** Default Peer Group view (login landing) → per-platform tabs (Meta / Google / TikTok / LinkedIn / Shopify / GA4); Marketing Mix Distribution dashboard; weekly Monday Morning Benchmark email.
- **Visualization:** Per-metric **table rows** (your value vs market value with traffic-light indicator); line charts beneath for time-series; **stacked / side-by-side spend allocation comparison** for the Marketing Mix dashboard. The specific P25/P50/P75 percentile-bar referenced in the brief was **not directly verifiable in public sources** — Varos talks about averages, ranges, and high/low performers but the explicit shaded P25-P75 band rendering could not be confirmed (per `competitors/varos.md`).
- **Layout (prose):** Top: filter strip exposing the four canonical peer-group dimensions (vertical, AOV, spend tier, channel) plus geo and sub-vertical on paid plans. Left: sidebar of platform tabs. Main canvas: per-platform table with rows = metrics (or peer-group buckets) and columns = your value vs market value(s). Bottom: line charts for time-series.
- **Specific UI:** **Traffic-light indicators next to North Star KPI — "Green: Strong performance, minimize optimization; Yellow/Red: Inefficient, investigate secondary metrics."** Date-range picker with custom ranges including "historical comparison across previous years." Filters are sticky and inherited across platform tabs. On line charts, "select one line at a time" to prevent visual flatness.
- **Filters:** vertical, sub-vertical, AOV, spend tier, channel, geographic location, campaign objective, targeting type. Default Peer Group is automatic; deeper cuts require "request changes" workflow.
- **Data shown:** Platform-specific North Star (CPP / CPA / MER / ROAS), then secondary KPIs (CPM, CTR, CPC, ROAS, Conversion Rate, Spend, P50 Video Views for TikTok). Shopify dashboard exposes Repeating Orders Ratio, Repeat Purchase Conversion, Cohort Purchase Frequency, AOV, Discount Rate, Abandoned Checkout Rate, Refunds % of Orders, Revenue Growth.
- **Interactions:** Click filter chip to recompute peer group; toggle North Star to re-rank peers as high/low performers; line-chart legend toggle. Email alerts trigger on statistically significant CPP changes for user or peers.
- **Why it works (from reviews):** "It's helpful to figure out if only your ad performance sucks or if it's the same for everybody." (Reddit, cited in `competitors/varos.md`); "Been using this product for a few weeks now and am a huge fan. As someone who spends lots of money on Facebook Ads, one of the biggest questions when performance starts falling off is 'is it just me or is it everyone'. Varos answers that question quickly." (Zachary Shakked, Product Hunt, `competitors/varos.md`).
- **Source:** `competitors/varos.md`; https://win.varos.com/en/articles/9352014-varos-best-practices

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Competitor Tracking module + Google Ads Performance + Benchmarks page (CTR/CPC/CVR by campaign type) + Henri AI prompts that pull benchmark context.
- **Visualization:** **No specific chart type observed in public sources** — feature pages describe metric comparisons against "20,000+" / "25,000+" benchmark cohort but the visualization is not detailed publicly.
- **Layout (prose):** UI details not available — only feature description seen on marketing pages. Benchmarks are bundled into Advertising Audit (Meta / Google / TikTok / GA4 scanned against ~50 rule tests) and into per-platform analytics.
- **Specific UI:** Color-coded performance indicators on Business Report use **blue for improvements, red for declines** (unusual; not green) per `competitors/lebesgue.md`.
- **Filters:** Not publicly exposed beyond date range and channel.
- **Data shown:** CTR, CPC, CVR per campaign type; competitor ad creatives, publishing history, estimated ad spend, email tactics, trending keywords (Competitor Tracking add-on).
- **Interactions:** Bundled into Henri natural-language Q&A; no standalone benchmark drill-down surface documented.
- **Why it works:** "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." (Fringe Sport, Shopify App Store, `competitors/lebesgue.md`).
- **Source:** `competitors/lebesgue.md`

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Analytics > Benchmarks (Overview / Business performance / Email campaigns / Flows / Sign-up forms tabs); Performance Highlights card embedded on Home + Overview Dashboard.
- **Visualization:** **Two side-by-side ranked tables** (Top-5 / Bottom-5 by percentile); **drill-down view per metric reveals 25th / 50th / 75th peer percentile values**; **status badges "Excellent / Fair / Poor"** on Campaign Performance card (this badge pattern lives on campaign benchmarks, NOT RFM cells per `competitors/klaviyo.md`).
- **Layout (prose):** Top: tab nav (Overview / Business performance / Email campaigns / Flows / Sign-up forms). Main canvas: two side-by-side tables — "Top Performing Metrics" lists user's strongest five metrics ordered by descending percentile vs peer group; "Bottom Performing Metrics" lists weakest five ordered by ascending percentile. Each row shows metric name, user's raw value, user's percentile. Click a row to expand the percentile distribution.
- **Specific UI:** Peer group is "roughly one hundred companies that are similar to your own in size and scope (e.g., industry, average item value, total revenue, year over year growth rate)." Industry editable in Organization settings to change peer composition. **Performance Highlights card "updates on the 10th of every month"** (monthly cadence, not real-time).
- **Filters:** Industry vertical (Organization-level), implicit AOV / revenue / YoY growth match.
- **Data shown:** All major email/SMS/flow/form/business KPIs with user value + user percentile + 25/50/75th peer percentiles.
- **Interactions:** Click metric row to expand percentile distribution. Edit industry in Organization settings to change peer group.
- **Why it works:** "Easy ability to see how much money generated for each email sent, good visibility of who opened emails." (Lee W., Capterra, `competitors/klaviyo.md`).
- **Source:** `competitors/klaviyo.md`; https://help.klaviyo.com/hc/en-us/articles/360050110072

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Not implemented as a standalone benchmark surface. Conjura's "Profit Benchmarks" framing is internal — KPI comparison against the user's own historical baseline rather than peer-cohort comparison.
- **Visualization:** No peer-benchmark visualization observed; Conjura's product pages describe contribution-margin benchmarks tied to user-defined targets, not an anonymized peer cohort.
- **Layout (prose):** Not observed in public sources.
- **Specific UI:** Not observed. Conjura's KPI Scatter Chart on the Campaign Deepdive plots their own campaigns on a 2D ratio plane, not against peers.
- **Filters:** N/A.
- **Data shown:** N/A — the marketing copy does not advertise peer benchmarks in the Klaviyo / Lifetimely / Varos sense.
- **Why it works:** "It gives you real visibility into profitability—way beyond Shopify's standard reporting." (The Herbtender, Shopify App Store, `competitors/conjura.md`) — note this is about depth, not benchmarking.
- **Source:** `competitors/conjura.md`. Conjura was on the "top competitors" list for benchmarks in `_feature_index.md` but the public surface does not actually expose anonymized peer benchmarks; the index entry appears to conflate "profit benchmarks" with "peer benchmarks."

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Sidebar > Benchmarks (opt-in required; anonymized data sharing toggle in Settings > Privacy).
- **Visualization:** **Bell-curve distribution chart** — user's value plotted against industry median, 25th, and 75th percentiles. **Metric tiles shaded green (top 25% or 25–50%) or yellow (50–75% or bottom 25%)** based on user's percentile position (verbatim from help docs per `competitors/lifetimely.md`). Binary green/yellow shading rule, no red — three-state nuance hinted by "top 25% or 25-50%" range.
- **Layout (prose):** Top: pencil-icon entry to edit the **4-question survey** (business model, product type, category, B2B vs B2C). Main canvas: 11 metric tiles laid out in a grid, each tile = one metric with bell-curve + user pin + green/yellow shading. Bottom: overall performance score = average position across all 11 metrics.
- **Specific UI:** **11 metrics** organized in 4 categories: P&L (net sales, contribution margin, gross margin), Order (new + repeat AOV — split as separate metrics), Retention (90d + 180d repurchase rates for new + repeat customers), Acquisition (blended ROAS, blended CAC, marketing spend %). Pencil icon on the survey entry. Anonymized opt-in required — Settings > Privacy.
- **Filters:** Implicit by 4-question survey only — no in-page filter controls described.
- **Data shown:** 11 metrics × 4 data points (user value, median, 25th, 75th).
- **Interactions:** Click pencil to recategorize via 4-question survey; opt-in/out of anonymized data sharing.
- **Why it works:** "removes the hassle of calculating a customer's CAC and LTV" (ELMNT Health, `competitors/lifetimely.md`) — benchmark surface gives the comparative meaning to those numbers without manual lookup.
- **Source:** `competitors/lifetimely.md`; https://help.useamp.com/category — Benchmarks help article (verbatim color rule confirmed).

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Profit Benchmarks — **unlocks at Day 90** (right-rail panel on Attribution Home + standalone surface). Not a peer-cohort benchmark; this is a target-driven benchmark computed against the user's own contribution margin.
- **Visualization:** UI specifics not visible in public sources — only feature description on marketing page. Three feature blocks named: Performance Targets, Cross-Platform Functionality, Growth Strategy.
- **Layout (prose):** Right-rail Profitability widget is **gated and stays empty/locked until the 90-day learning period passes** (`competitors/northbeam.md`). Once unlocked: target ROAS / MER / CAC computed against actual contribution margins, with live performance vs benchmarks across platforms.
- **Specific UI:** **Day 30 / 60 / 90 progressive feature unlock** is the explicit Northbeam pattern — Apex (30), Clicks + Modeled Views (60), Profit Benchmarks (90). The empty/locked right-rail until Day 90 is concrete UI precedent for gating a panel by data-readiness instead of hiding it.
- **Filters:** Not described in public sources.
- **Data shown:** Target ROAS, target MER, target CAC, and live performance vs each.
- **Interactions:** "See your performance against benchmarks in real-time" (marketing copy).
- **Why it works:** No verbatim user quote on Profit Benchmarks specifically — this is a Day-90 paywalled feature with limited public UX coverage.
- **Source:** `competitors/northbeam.md`; https://www.northbeam.io/features/profit-benchmarks — UI details not available beyond marketing copy.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Benchmarks (formerly "Trends"); also surfaced on Founders Dash free tier.
- **Visualization:** Per `competitors/triple-whale.md`: "graph comparing the benchmark data to your data — plausibly a line + benchmark band, but the text doesn't describe chart type concretely." UI details for the benchmark chart type not directly verified from public sources (KB returned 403).
- **Layout (prose):** Top: platform tab strip (Meta / TikTok / Google / Bing / X / Amazon / Pinterest / Snap / Blended). Right: filter rail with industry vertical, annual revenue range, AOV band, time period (last 7 / 30 days). Main canvas: comparison chart for primary KPIs.
- **Specific UI:** Cohort size positioned as moat — homepage cites "20,000+ Triple Whale customers" (Trends launch blog cited 11,000+); peer dataset functions as a network-effect differentiator.
- **Filters:** Industry vertical, annual revenue range, AOV band, time period (7d / 30d), platform.
- **Data shown:** CPA, CPC, CPM, ROAS as four primary KPIs per platform.
- **Interactions:** Filter to peer cohort → visualize delta between your metric and peer benchmark.
- **Why it works:** "Its Founders dash is something that all Shopify brands should be using. It's free and puts all the metrics you need to know about your high level business performance in a single place." (Head West Guide, `competitors/triple-whale.md`) — benchmarks are part of the free-tier wedge.
- **Source:** `competitors/triple-whale.md`; https://www.triplewhale.com/blog/trends-benchmarking

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Goals & Forecasting module — not a peer benchmark; an internal goal-vs-actual benchmark with auto-generated seasonal targets.
- **Visualization:** **Traffic-light dots/cells (green = on-pace, red = drifted)** attached to each monthly benchmark or KPI tile. **Yellow/amber state is NOT used — they specifically say "green & red," binary not three-state** (per `competitors/storehero.md`).
- **Layout (prose):** Annual goal entry → auto-generated month-by-month seasonally-adjusted benchmark grid. Drift triggers visible alert.
- **Specific UI:** Binary green/red traffic-light system (no yellow). BeProfit's competitive comparison page criticizes StoreHero for missing "industry benchmarks" specifically, suggesting StoreHero's benchmark surface is internally-defined targets, not peer benchmarks (`competitors/storehero.md`).
- **Filters:** Not described.
- **Data shown:** Monthly benchmarks vs actuals for revenue, contribution margin, channel-level ad spend.
- **Interactions:** Annual goal input → automatic month seeding.
- **Why it works:** "Built-in seasonal forecasting from a single annual goal input — auto-generates month-by-month benchmarks with a green/red traffic-light deviation flag. Most 'profit dashboards' require manual monthly target entry." (`competitors/storehero.md`).
- **Source:** `competitors/storehero.md`

### Rockerbox ([profile](../competitors/rockerbox.md))
- **Surface:** Top-level "Spend Benchmarks" tab — sits outside per-account dashboards as a peer/industry cohort view.
- **Visualization:** UI not described in public sources beyond the feature framing.
- **Layout (prose):** Industry/peer-benchmarking surface showing "how companies across multiple sizes and industries are varying their channel spend over time." Distinct from per-account dashboards — own dedicated tab.
- **Specific UI:** **IA decision: benchmarks live in their own top-level tab, not as inline overlays on the dashboard** (per `competitors/rockerbox.md` Notes for Nexstage).
- **Filters:** Not directly observed.
- **Data shown:** Channel spend mix and trends across company sizes and industries.
- **Interactions:** Not observed.
- **Why it works:** Listed as a distinct strength in `competitors/rockerbox.md` — "Spend Benchmarks peer/industry cohort view sits outside per-account data; rare among SMB attribution tools."
- **Source:** `competitors/rockerbox.md`; https://www.rockerbox.com/blog/rockerbox-reimagined-a-deep-dive-on-the-new-and-updated-functionality

### Repeat Customer Insights ([profile](../competitors/repeat-customer-insights.md))
- **Surface:** Store Analysis (home/landing dashboard) — store-wide metrics with industry benchmarks plus the store's own historical baseline.
- **Visualization:** No visualization details directly observed in public sources beyond "metrics… plus benchmarking against industry and against the store's own historical baseline." Tile/card-based dashboard.
- **Layout (prose):** Top-level Store Analysis dashboard with date drill-down selector (all-time / current year / previous year on Entrepreneur tier; quarterly + 4-year history on Growth; per-quarter + annualized on Peak).
- **Specific UI:** UI details not available — only feature description seen on marketing page.
- **Filters:** Tier-based date drill-down; no peer-cohort selector publicly described.
- **Data shown:** AOV, LTV, Repeat Purchase Rate plus industry benchmark + own historical baseline.
- **Interactions:** Email-digest subscription toggle.
- **Why it works:** Not specifically called out in user reviews for this surface — RCI's strength is the 5×5 RFM grid, with benchmarking secondary.
- **Source:** `competitors/repeat-customer-insights.md`

### Glew ([profile](../competitors/glew.md))
- **Surface:** Daily Snapshot email — KPIs surfaced "with built-in benchmarks and period-over-period comparisons" (no dedicated in-app peer-benchmark dashboard observed in public sources).
- **Visualization:** Tile-style KPI blocks in email format ("Ecom Daily Flash Dashboard"). No peer-percentile chart observed.
- **Layout (prose):** Daily email with "15+ KPIs across financial and operational categories." Glew Plus tier customizes tiles, comparison periods, targets, currency.
- **Specific UI:** Period-over-period comparison embedded inline; benchmarks framed as targets the user sets, not peer percentiles.
- **Filters:** Customizable on Plus tier (tiles, comparison periods, targets, currency).
- **Data shown:** Revenue, orders, AOV, gross profit, gross margin, website visits, conversion rate, refunds, new customers, repeat customers, ad spend, top marketing channel, top-selling product, largest order.
- **Interactions:** Click-through to web app for drill-down.
- **Why it works:** Daily-email cadence as primary surface ("They lean hard on email (Daily Snapshot) as a primary surface rather than a mobile app." — `competitors/glew.md`).
- **Source:** `competitors/glew.md`

## Visualization patterns observed (cross-cut)

Synthesis across the 11 competitors covered:

- **Top-5 / Bottom-5 ranked tables by percentile:** 1 competitor (Klaviyo) — explicit, scannable, but assumes user understands percentile vocabulary.
- **Bell-curve distribution with user pin + green/yellow shading:** 1 competitor (Lifetimely) — only competitor with documented green/yellow rule, binary three-state with no red.
- **Per-metric table rows (your value vs market value) with traffic-light indicator:** 1 competitor (Varos) — green / yellow / red on North Star KPI; specific P25/P50/P75 percentile-bar UI mentioned in the brief was **not directly verifiable** in public Varos sources.
- **Filter-rail + comparison chart (line + benchmark band):** 1 competitor (Triple Whale Benchmarks) — chart type "plausibly a line + benchmark band" but not concretely confirmed from public sources.
- **Auto-generated seasonal target grid with green/red traffic light:** 1 competitor (StoreHero) — binary green/red, NO yellow.
- **Day-90 gated right-rail panel:** 1 competitor (Northbeam) — empty/locked panel until ML model trains; not peer-benchmark, target-benchmark.
- **Standalone "Spend Benchmarks" tab (peer-cohort view, separate from per-account dashboards):** 1 competitor (Rockerbox) — IA decision: own top-level tab.
- **Industry-vs-historical baseline tile cards:** 1 competitor (Repeat Customer Insights) — UI details not available beyond category description.
- **Daily email with built-in benchmarks + period-over-period:** 1 competitor (Glew) — email-first surface, not in-app dashboard.
- **No peer benchmark / internal goal benchmark only:** Conjura (despite being on the curated list, the public surface does not expose peer-cohort benchmarks).
- **Bundled into AI assistant prompts / Audit module:** 1 competitor (Lebesgue) — benchmarks are surfaced via Henri natural-language Q&A and the Advertising Audit; no standalone benchmark dashboard documented.

**Recurring color/iconography conventions:**
- **Traffic-light convention is universal but the palette varies:** Varos (green / yellow / red, three-state); Lifetimely (green / yellow only, binary three-state with no red); StoreHero (green / red only, binary two-state with no yellow); Klaviyo ("Excellent / Fair / Poor" labels rather than dot icons, on Campaign Performance card not RFM).
- **Cohort size disclosure as a moat signal:** Klaviyo "~100 companies"; Varos "4,500+"; Lebesgue "20,000–25,000"; Triple Whale "11,000–20,000+." Larger numbers are explicitly used as marketing copy on homepages.
- **Anonymized opt-in is universal but explicit only in Lifetimely** — Settings > Privacy toggle. Varos requires data contribution as price of admission ("data co-op tax"). Klaviyo / Triple Whale do not surface an explicit opt-out (data contribution is implicit in TOS).

**Common interaction patterns:**
- Click metric row to expand percentile distribution (Klaviyo).
- Edit peer-group definition via short survey or settings (Lifetimely 4-question, Klaviyo Organization settings, Varos request-changes).
- Pivot North Star KPI to re-rank (Varos).
- Time-period toggle (Triple Whale 7d / 30d).

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: "Is it me or the market?"** — the existential reassurance value
- "Been using this product for a few weeks now and am a huge fan. As someone who spends lots of money on Facebook Ads, one of the biggest questions when performance starts falling off is 'is it just me or is it everyone'. Varos answers that question quickly by informing me of CPM / CPA and other metric fluctuations in real time." — Zachary Shakked, Product Hunt comment, `competitors/varos.md`
- "It's helpful to figure out if only your ad performance sucks or if it's the same for everybody." — Reddit user, cited in `competitors/varos.md`
- "Varos has given their team a much stronger market pulse." — Connor MacDonald, CMO at The Ridge, paraphrased in `competitors/varos.md`

**Theme: Free-tier benchmarks as a wedge**
- "It's FREE (for now) and it's already added a ton of value for myself and my clients." — DTC community recommendation, `competitors/varos.md`
- "Its Founders dash is something that all Shopify brands should be using. It's free and puts all the metrics you need to know about your high level business performance in a single place." — Head West Guide review, `competitors/triple-whale.md`

**Theme: Benchmarks make raw KPIs interpretable**
- "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, Shopify App Store, `competitors/lifetimely.md`
- "Easy ability to see how much money generated for each email sent, good visibility of who opened emails." — Lee W., Capterra, `competitors/klaviyo.md`

**Theme: Real-time / weekly cadence beats quarterly PDFs**
- "Incredible real time CMO data and analysis." — 4Throws.com, Shopify App Store, `competitors/lebesgue.md`
- The Monday Morning Benchmark email is described as a major engagement / retention loop in `competitors/varos.md` (cited in trymesha.com review).

## What users hate about this feature

**Theme: Thin samples in niche verticals**
- "Benchmarks are strongest in popular verticals." — search-result summary of Varos limitations, `competitors/varos.md`
- "Limited historical data initially impacts analysis depth." — tripleareview.com con-list, `competitors/varos.md`

**Theme: Cold-start lag**
- "Initial data gathering period required for trend visibility" — tripleareview.com, `competitors/varos.md`
- "Your dashboard will be generated within 48 hours" — Varos help-center "Connecting Your Account," cited in `competitors/varos.md`. The 48-hour wait is product policy, not bug.
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed, workflowautomation.net, `competitors/triple-whale.md` (not benchmark-specific but applies to peer-cohort calibration).

**Theme: Data co-op tax / opt-in friction**
- "You have to share anonymized performance data, and Varos shows insights but doesn't execute optimizations" — search-result summary, `competitors/varos.md`. Non-starter for some VC-backed brands or competitive-paranoid teams.

**Theme: Benchmarks paywalled or buried**
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, `competitors/klaviyo.md` (Klaviyo benchmarks sit in Marketing Analytics add-on at $100/mo).
- "Reporting is clunky and the UI buries things that should be front and center." — Darren Y., Capterra, `competitors/klaviyo.md`.

**Theme: "Where do I stand" without "what should I do"**
- "Insights" can be "simply noting that CAC increased and conversion rate dropped off" — Capterra synthesis paraphrased in `competitors/lebesgue.md`. Users want benchmarks tied to next-step recommendations.
- "Varos shows insights but doesn't execute optimizations" — cited in `competitors/varos.md`.

## Anti-patterns observed

- **"Industry average" without disclosed cohort math.** Triple Whale's earlier marketing said "industry vertical / revenue band" but the actual chart type and N-disclosure for individual benchmark views was not reproducible from public sources (KB blocked). Klaviyo is the counter-example — they name the cohort math ("roughly one hundred companies similar to you in industry, AOV, total revenue, YoY growth"). Per `competitors/klaviyo.md` Notes for Nexstage: "Benchmark percentile distribution (25/50/75) with named peer-cohort criteria is more transparent than 'industry average' claims competitors make."
- **Conflating internal goal-benchmarks with peer-benchmarks.** Northbeam Profit Benchmarks computes against the user's own contribution margins (not peers); StoreHero Goals & Forecasting auto-seeds monthly targets from a single annual goal input (also not peers); Conjura's "Profit Benchmarks" framing is internal. Marketing the feature as "Benchmarks" without distinguishing peer vs target invites confusion. Per `competitors/conjura.md`: the public surface does not actually expose anonymized peer benchmarks despite being on the curated benchmarks list.
- **48-hour onboarding gate before benchmarks render** (Varos) — manual review for data quality. Friction for a freemium "30-second signup" pitch (`competitors/varos.md`).
- **Benchmark drift from monthly cadence:** Klaviyo Performance Highlights "updates on the 10th of every month" — for users in a fast-moving channel-shock window (algo change, holiday surge), monthly cadence is too slow. Varos counters this with real-time + Monday Morning email.
- **"Where am I" without a configurable lens.** Most competitors expose a single benchmark view (your value vs P50). Varos's North Star KPI selector — pick MER / CPP / ROAS as the lens, then re-rank peers as high/low performers and surface secondary KPIs of high performers — is the only documented lens-switcher pattern.
- **Binary or three-state shading inconsistency.** Lifetimely uses green/yellow only (no red), StoreHero uses green/red only (no yellow), Varos uses all three. No consistent industry convention; users coming from one tool to another encounter palette dissonance. Per `competitors/lifetimely.md` and `competitors/storehero.md`.
- **No GSC benchmarks anywhere.** None of the 11 competitors covered expose Google Search Console / organic-search peer benchmarks. Varos explicitly excludes GSC; Lebesgue does not have GSC; Lifetimely / Conjura / StoreHero do not have GSC.

## Open questions / data gaps

- **Varos P25/P50/P75 percentile-bar UI not verifiable.** The assignment brief specifically referenced a P25/P50/P75 + shaded P25-P75 band rendering. Varos's public help-center copy talks about "averages," "ranges," and high vs low performers, but the specific percentile-bar visualization with a shaded P25-P75 band could not be confirmed from public sources (`competitors/varos.md` flags this directly: "UI details not visible in public sources beyond this; dashboards are paywalled/login-gated"). Would require a logged-in trial account.
- **Triple Whale benchmark chart type.** Public-page text describes "graph comparing the benchmark data to your data" without naming the chart type. KB returned 403 in research. Free-tier signup needed to capture screenshots.
- **Lebesgue benchmark UI.** Lebesgue advertises "20,000–25,000 brand" benchmarks but does not describe a standalone benchmark dashboard UI in any public marketing page; the feature surfaces only via Henri AI prompts and Advertising Audit. Surface boundary is unclear.
- **Northbeam Profit Benchmarks UI.** Day-90 gated; only marketing copy describes three feature blocks (Performance Targets / Cross-Platform Functionality / Growth Strategy) without showing the actual chart or table.
- **Conversific** — listed in `_feature_index.md` row 25 as a top competitor but no profile exists in `docs/competitors/competitors/`. Excluded from this profile; gap to fill if desired.
- **Minimum-N disclosure.** None of the competitors covered publicly disclose a minimum cohort size (N) below which a benchmark is suppressed. Varos hints at it ("strongest in popular verticals") but does not name a threshold.
- **Bell-curve specifics in Lifetimely.** Help docs confirm green/yellow shading rule but do not show the exact tile dimensions, axis labels, or whether the bell-curve is histogram-style or kernel-density. Would require trial signup.
- **Anonymization mechanism details.** No competitor publishes their k-anonymity / differential-privacy approach. Klaviyo says "roughly one hundred companies"; Varos says "4,500+ brands" but neither names the math.

## Notes for Nexstage (observations only — NOT recommendations)

- **3 of 11 competitors covered (Varos, Klaviyo, Lifetimely) implement true peer-cohort benchmarks with named cohort criteria; the rest mix peer benchmarks with target/goal benchmarks under the same "Benchmarks" label.** Naming discipline (peer vs target) is observable as a clarity wedge — Klaviyo names cohort math explicitly ("~100 companies similar in industry, AOV, total revenue, YoY growth"); Triple Whale and Lebesgue do not.
- **Cohort-axis count is the explicit moat for Varos.** Three-axis peer model — vertical + AOV + spend tier — is published prior art (`win.varos.com/.../the-science-behind-varos-competitive-sets`). Klaviyo uses four (industry + AOV + total revenue + YoY growth). Lifetimely uses a 4-question survey (business model + product type + category + B2B/B2C). The number of axes maps directly to perceived peer relevance.
- **North Star KPI configurable lens (Varos) is conceptually parallel to Nexstage's `MetricSourceResolver` pattern.** Both surface a primary lens picker (MER / CPP / ROAS for Varos vs Real / Store / Facebook / Google / GSC / GA4 for Nexstage) and re-rank/re-filter the rest of the UI around it. Worth a side-by-side review when designing the source-badge UX.
- **0 of 11 competitors expose Google Search Console / organic-search peer benchmarks.** Direct gap relative to Nexstage's 6-source thesis. If GSC benchmarks ever ship, no incumbent has the surface.
- **Lifetimely's green/yellow shading rule (top 25% or 25–50% = green; 50–75% or bottom 25% = yellow) is the only documented binary three-state rule** — verbatim from help docs. StoreHero is binary green/red (no yellow). Varos is three-state green/yellow/red. Color tokens in `resources/css/app.css` would need a deliberate decision if Nexstage ships any benchmark shading.
- **Cohort-size disclosure as a moat signal is universal.** Klaviyo "~100 companies similar to you"; Varos "4,500+"; Lebesgue "20,000–25,000"; Triple Whale "11,000–20,000+." Nexstage at launch will have N=0 — empty-state design needed before benchmarks have statistical power.
- **CLAUDE.md "ratios are never stored" rule has implications for benchmarks.** Triple Whale Benchmarks surfaces CPA / CPC / CPM / ROAS as headline benchmark KPIs; these are exactly the ratios CLAUDE.md says Nexstage should never store. Triple Whale clearly persists them at aggregate level (otherwise peer benchmarks would not be possible at their scale). For Nexstage's compute-on-the-fly rule, peer-benchmark precomputation would presumably need to be a server-side aggregation from `daily_snapshots` separate from request-time computation.
- **Lifetimely's CAC-payback green bar (LTV cohort waterfall)** is a benchmark-adjacent UI primitive — user enters their CAC manually, a horizontal green line marks the threshold, and the bar visibly crosses the line at the payback month. Single-purpose annotation, very high recognition value.
- **Day-90 progressive unlock (Northbeam)** is a viable UI pattern for any cost-recompute or attribution-default flow that takes time to converge — sells the wait as a feature rather than an empty state. The right-rail Profitability panel literally stays empty/locked until Day 90 (`competitors/northbeam.md`).
- **Anonymization opt-in surface is explicit only in Lifetimely** (Settings > Privacy). Varos requires data contribution as price of admission. If Nexstage ever ships peer benchmarks, the opt-in copy and toggle location are decisions worth pre-empting.
- **Standalone tab vs inline overlay is an IA fork.** Rockerbox keeps Spend Benchmarks in its own tab; Klaviyo embeds Performance Highlights on Home + Overview Dashboard; Varos makes the entire dashboard a benchmark view. No dominant convention.
- **Email cadence (Monday Morning Benchmark — Varos; Daily Snapshot — Glew; Klaviyo Performance Highlights monthly on the 10th)** is a recurring out-of-app delivery surface for benchmarks. In-app and email are not either/or.
- **"Where do I stand" without "what to do" is the dominant complaint pattern.** Lebesgue's Capterra synthesis ("simply noting CAC increased") and Varos's "shows insights but doesn't execute optimizations" both point to the same gap: percentile rank without action prompts. Recommendation/next-step tie-in is unsolved across competitors.
