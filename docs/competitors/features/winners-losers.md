---
name: Winners & losers
slug: winners-losers
purpose: Tell the merchant which products, campaigns, creatives or channels are gaining traction vs. fading this week or month — so they know what to scale, what to fix, and what to kill.
nexstage_pages: dashboard, performance, ads, products
researched_on: 2026-04-28
competitors_covered: triple-whale, lebesgue, conjura, storehero, putler, atria, motion, northbeam, cometly, trueprofit
sources:
  - ../competitors/triple-whale.md
  - ../competitors/lebesgue.md
  - ../competitors/conjura.md
  - ../competitors/storehero.md
  - ../competitors/putler.md
  - ../competitors/atria.md
  - ../competitors/motion.md
  - ../competitors/northbeam.md
  - ../competitors/cometly.md
  - ../competitors/trueprofit.md
  - https://www.conjura.com/campaign-deepdive-dashboard
  - https://www.conjura.com/product-table-dashboard
  - https://www.conjura.com/order-table-dashboard
  - https://www.conjura.com/performance-trends-dashboard
  - https://www.putler.com/product-analysis
  - https://www.putler.com/blog/rfm-analysis/
  - https://intercom.help/atria-e5456f8f6b7b/en/articles/13862195-meet-raya-your-ai-creative-strategist
  - https://motionapp.com/solutions/creative-testing-tool
  - https://lebesgue.io/product-features/shopify-reporting-app
  - https://lebesgue.io/ai-agents
  - https://storehero.ai/features/
---

## What is this feature

Winners & losers is the surface that answers "of all the things in my store, which are accelerating and which are decelerating right now, and how should I rank them so I know where to spend the next hour?" It compresses ten or twelve dashboards' worth of campaign, ad-set, ad, creative, product, SKU, channel, country, customer-segment and email-flow data into a single triaged view — usually a ranked-delta table or a labelled grid — that tells the merchant the *change* in a metric, not just the metric, over a recent window (yesterday / 7d / 28d / MTD vs. prior period).

For SMB Shopify and Woo merchants the difference between "having data" (Meta Ads Manager, Shopify Analytics, GA4 Events) and "having this feature" is one of cognitive load. Source platforms surface absolute values per row; they do not rank rows by *delta*, attach triage labels (Winner / Iteration Candidate / Loser), or surface a "biggest mover" leaderboard scoped to a 7-day window. Without that synthesis the merchant has to mentally diff two date ranges, scan dozens of rows and hold "is that good or bad?" in their head — a workflow that does not survive a Monday morning. Competitors who do this well — Atria's Radar, Motion's Ad Leaderboard / Launch Analysis, Conjura's Product Table saved views ("unprofitable products", "slow movers", "items selling out"), Triple Whale's Lighthouse / Anomaly Detection Agent, Lebesgue's Revenue Drop Investigator — collapse the loop into "open this tab Monday, act, close it."

The feature is sometimes presented as a standalone surface ("Winners & Losers", "Leaderboard", "Radar") and sometimes as a saved-view / filter chip on a generic table. Both patterns appear across the competitor set; pure-play standalone surfaces are concentrated in creative-analytics tools (Atria, Motion), while ecommerce profit-first tools (Conjura, StoreHero, Lebesgue) ship the pattern as saved-views on Product / Order / Campaign tables.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders.id`, `orders.line_items.product_id`, `orders.line_items.variant_id`, `orders.line_items.quantity`, `orders.line_items.price`, `orders.refunds`, `orders.created_at`, `products.title`, `products.image`, `products.cost` (where present), `customer.first_order_at` (for new vs. existing split — Conjura, StoreHero).
- **Source: Meta Ads / Google Ads / TikTok Ads / Pinterest / Bing** — `campaigns.id`, `adsets.id`, `ads.id`, `ads.creative_id`, `spend`, `impressions`, `clicks`, `platform_conversions`, `platform_revenue`. Creative thumbnail / video asset URL alongside the metric row (Motion's "creative-and-metric-side-by-side" mandate).
- **Source: Klaviyo / email** — `flow_id`, `campaign_id`, `attributed_revenue`, `opens`, `clicks` (Triple Whale Email & SMS dashboard, Lebesgue email-spike correlation prompt).
- **Source: GA4 / web analytics** — `sessions`, `landing_pages`, `traffic_source` (Conjura's Performance Trends "spikes in customer acquisition costs" detection).
- **Source: Computed (period-over-period delta)** — for every (entity, metric, period_now, period_prior) tuple: `delta_abs = metric_now − metric_prior`, `delta_pct = (metric_now − metric_prior) / NULLIF(metric_prior, 0)`. Period_prior is the immediately preceding window of the same length unless the user picks YoY explicitly (Putler Pulse zone surfaces both 3-day trend and YoY in one widget).
- **Source: Computed (rank within entity-class)** — `rank_by_delta_abs` and `rank_by_delta_pct` per entity class (campaign, ad, ad-set, product, channel) within the selected window.
- **Source: Computed (triage label)** — letter grade or category badge derived from a rule matrix on (delta_pct, absolute_metric_floor, statistical-significance-floor). Atria's Radar is the canonical example: a per-creative letter grade A–D across Hook / Retention / CTR plus one of Conversion or ROAS, then a triage label Winner / High Iteration Potential / Iteration Candidate. Motion's Launch Analysis: "scaling / declining / early-winner" categorical labels.
- **Source: Computed (anomaly z-score)** — for "biggest mover by surprise" framing: residual against trend or seasonality (Triple Whale's Anomaly Detection Agent, Conjura Performance Trends "early warnings when unexpected performance changes occur", Lebesgue's Revenue Drop Investigator agent).
- **Source: User-input (cost config)** — `products.cost`, shipping rules, transaction fees, ad-spend allocation rules — required if the metric being ranked is contribution profit not revenue (StoreHero, Conjura, TrueProfit, Lebesgue).

## Data outputs (what's typically displayed)

Output column set converges across the competitor pool — almost every implementation surfaces some subset of:

- **Identity column** — entity name, thumbnail (creative tools), product image (Conjura "incorporates product imagery alongside performance metrics"), or campaign name.
- **Headline metric (current period)** — revenue / spend / contribution profit / ROAS / orders.
- **Comparison metric (prior period)** — same metric, prior window.
- **Delta absolute** — current − prior, signed, color-coded (red negative / green positive — except Lebesgue which uses **blue for positive, red for negative**).
- **Delta percent** — same delta as % of prior, often rendered as the loud number with the absolute as caption.
- **Trend sparkline** — small inline line/bar chart over the window (referenced across Motion, Triple Whale Summary, Putler Home Dashboard's "daily-sales mini-chart").
- **Triage badge** — Atria: Winner / High Iteration Potential / Iteration Candidate. Motion: scaling / declining / early-winner. StoreHero: green/red traffic light. Conjura saved views: "unprofitable products", "slow movers", "items selling out".
- **Letter grade** — Atria-only: A–D rubric per metric axis (third-party-attested grade scale).
- **Action CTA per row** — "Iterate" (Atria), "Pause / Scale" (Cometly Ads Manager — read-write into Meta), "Push Audience" (Triple Whale Lighthouse), "Refund" / "Drill" (Putler).
- **Anomaly tag** — text annotation on outliers ("Spend Anomalie", "Orders Anomalie" — Triple Whale Lighthouse copy).

Slices: per-product, per-campaign / ad-set / ad, per-creative, per-channel, per-country, per-customer-segment, per-email-flow. Window selectors: 7d, 28d, MTD, YoY, custom range (date-picker recomputes the entire ranked-delta table).

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Lighthouse (in 2025–2026 absorbed into Moby Anomaly Detection Agent / Order & Revenue Pacing Agent / Revenue Anomaly Agent); also surfaced on Summary Dashboard via period-vs-period deltas on KPI tiles, on Pixel Events Manager (April 2026) for event-level anomalies, and on the new Email & SMS Attribution Dashboard.
- **Visualization:** Anomaly inbox / alert-card list. Per-card layout, not a ranked table. Each card carries severity, the metric that moved, and a suggested action.
- **Layout (prose):** "Anomaly inbox / alert list — entries fire on suspicious variances in ad spend, inventory, and order data" (`../competitors/triple-whale.md`). Includes AI Audiences sub-section with "6 already built segments" of RFM tiles. Pre-built RFM audience tiles function as an evergreen "winner segments" leaderboard. Summary Dashboard tiles show absolute KPI plus "period-vs-period delta language" inline on each card.
- **Specific UI:** "KPI tile shows headline value + period-vs-period delta. Hovering a tile reveals a 📌 pin icon" (verbatim from profile). Lighthouse-era copy used "Orders Anomalie" / "Spend Anomalie" terminology on alert cards. Alert cards expose severity / metric / suggested-action with a click-through to anomaly cause and a "push audience to Meta" affordance for RFM-segment cards.
- **Filters:** Date range, store-switcher, attribution-model selector. Filters can segment Shopify and Ad data on Summary.
- **Data shown:** Spend anomalies, order anomalies, inventory anomalies; AI-generated RFM audience tiles ("6 already built segments"); KPI deltas vs. prior period across blended metrics (Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV 60/90, sessions).
- **Interactions:** Acknowledge alert; drill into anomaly cause; push audience to Meta; click KPI tile → drill into detail. Mobile push notifications fire on revenue milestones "within minutes of the triggering event."
- **Why it works (from reviews/observations):** "Real-time data, clean dashboards, mobile app, and automation consistently save operators 4–8 hours per week." (AI Systems Commerce, 2026 review, cited in `../competitors/triple-whale.md`). Lighthouse-era copy framed it as "what anomalies should I act on?" — the same job winners-losers solves.
- **Source:** `../competitors/triple-whale.md`; https://www.triplewhale.com/lighthouse (redirects to Moby AI marketing page in 2026); https://www.triplewhale.com/blog/triple-whale-product-updates-april-2026

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** AI Agents hub > Revenue Drop Investigator (named agent), plus Compare Metrics tool in Reporting section, plus Business Report which "user picks the metrics and time period."
- **Visualization:** Two distinct surfaces — (1) Compare Metrics is a single-metric trend chart with day/week/month aggregation toggle showing correlation patterns; (2) Revenue Drop Investigator is an agent-card workflow in the AI Agents hub. No standalone ranked-delta table observed.
- **Layout (prose):** Business Report layout: metric-selection dropdowns, date-range picker, line/bar chart canvases. Compare Metrics output "shows correlation patterns between metrics (their example: 'Facebook purchases and first-time orders')." AI Agents hub renders each agent as a tile/card; user selects to launch focused workflow.
- **Specific UI:** "Color-coded performance indicators (blue for improvements, red for declines)" — verbatim from feature page. Note: **blue, not green**, for positive deltas — unusual against the competitor set. Henri chat surfaces "Key Takeaways sections, and recommendations formatted as actionable next steps beneath performance analysis charts."
- **Filters:** Metric selector, date range, aggregation toggle (day/week/month). Channel filter on LTV view. No country-level breakdown within a single account (cited as a limitation in reviews).
- **Data shown:** Revenue, First-time Revenue, Ad Spend by platform, COGS, Profit, ROAS — selected by the user at report-build time. Henri sample prompts illustrate intent: "Analyze how our store performed over the last 30 days compared to the same period last year" (verbatim from profile).
- **Interactions:** Pick metric → pick range → auto-generate report. Custom-report download. Henri natural-language queries with inline charts in responses.
- **Why it works:** "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." (Fringe Sport, Shopify App Store, Oct 2025, cited in `../competitors/lebesgue.md`). Reviewers consistently praise the daily/weekly email-digest cadence which functions as an asynchronous winners-losers report.
- **Source:** `../competitors/lebesgue.md`; https://lebesgue.io/product-features/shopify-reporting-app; https://lebesgue.io/ai-agents

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Product Table, Order Table, Customer Table, Performance Trends Dashboard, Campaign Deepdive (KPI Scatter Chart). The pattern is shipped as **saved views / pre-built filter chips on tables**, not as a standalone "Winners" surface.
- **Visualization:** Sortable / filterable tables with pre-built saved views (verbal labels function as triage badges); Performance Trends uses "clean trend lines"; Campaign Deepdive's centerpiece is the **"KPI Scatter Chart"** — a 2D scatter where two ratio metrics are plotted and outliers identified ("highlights which campaigns are dragging your metrics down and which are outperforming").
- **Layout (prose):** Product Table is "decision-making engine for product, marketing, and merchandising teams" with pre-built filter chips for "unprofitable products, slow movers, items selling out" and the ability to "save your views to revisit at any time" (verbatim from `../competitors/conjura.md`). Order Table pre-built saved views: "New customer orders, unprofitable orders and most profitable" plus user-defined examples like "Orders where profit margin is >70%, orders with refunds, high shipping costs and more." Performance Trends "early warnings when unexpected performance changes occur, such as drops in conversion rates or spikes in customer acquisition costs."
- **Specific UI:** Pre-built saved-view chips above the table, each chip is effectively a triage label ("slow movers" = losers, "items selling out" = winners). Per-row product imagery integrated alongside metric cells — recurring visual motif "incorporates product imagery alongside performance metrics, creating a visually-oriented analytics experience" (blog excerpt). Campaign Deepdive presents Last Click columns side-by-side with Platform Attributed columns simultaneously.
- **Filters:** Date range, store, channel, territory, profitability, inventory, performance, custom metric. Drill from chart point to specific campaign on Campaign Deepdive.
- **Data shown:** Per-product: sales, conversion rate, product views, discount %, returns/refund rate, contribution profit, ad spend by product, stock levels, sell-through rate. Per-order: revenue, profit margin, attributed ad spend per SKU, refund flag, promo code, shipping cost, customer flag (new/existing). Campaign Deepdive: Ad Spend, Impressions, CPM, Clicks, CTR, CPC, CAC, customers acquired, Last Click ROAS, Platform ROAS (verbatim list from KPI definitions help-doc).
- **Interactions:** Filter chips combine; click product/order → detail card; export segment to CRM; save view; drill from KPI Scatter point. Owly AI agent answers natural-language queries like "Where am I overspending on ads?" with explicit recommendations: "stopping ads on underperformers, increasing prices on high-converting products, or shifting budget to your top acquisition drivers."
- **Why it works:** "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." (ChefSupplies.ca, Shopify App Store, Jan 2024). "Simple to use, seriously rich insights, all action-orientated." (Rapanui Clothing, Oct 2024) — both cited in `../competitors/conjura.md`. Reviewer praise centers on action-orientation, which is the winners-losers job.
- **Source:** `../competitors/conjura.md`; https://www.conjura.com/product-table-dashboard; https://www.conjura.com/order-table-dashboard; https://www.conjura.com/campaign-deepdive-dashboard; https://www.conjura.com/performance-trends-dashboard

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Goals & Forecasting module + Spend Advisor + Products / Orders tables. Triage logic is layered onto goal tracking via traffic lights, and onto ad spend via Spend Advisor's "pause / pivot / scale" recommendation states.
- **Visualization:** Two-state traffic-light system on goal benchmarks (green / red, **no amber** — explicit per StoreHero copy: "a green & red traffic-light system"); Spend Advisor uses pill-style recommendation states (pause / pivot / scale); SKU table sorts by breakeven ROAS and contribution margin per product.
- **Layout (prose):** Goals & Forecasting renders an "annual goal entry → auto-generated month-by-month seasonally-adjusted benchmark grid" with traffic-light cells per benchmark. Spend Advisor is "simulator-style screen — homepage text reads 'Watch how every $100 you invest into ads changes profit in real time' suggesting an interactive slider or live-updating numeric input with a profit-impact panel beside it." (verbatim from `../competitors/storehero.md`).
- **Specific UI:** Green/red traffic-light dots/cells attached to each monthly benchmark or KPI tile. Three discrete recommendation states surfaced as labels or pills: pause / pivot / scale. SKU table column for "fully-loaded COGS, gross profit per SKU, breakeven ROAS" on every row.
- **Filters:** Date-range filter; store-switcher (agency multi-store); channel-blended-vs-channel-by-channel toggle.
- **Data shown:** Net Sales, Marketing Spend, Ad Spend, Contribution Margin, MER, ROAS, breakeven ROAS, new customer sales, repeat customer sales, AOV per benchmark and per row.
- **Interactions:** Annual goal input → automatic month seeding; drift triggers visible alert. Ad-spend input → live profit recalculation in Spend Advisor; recommendation state surfaces.
- **Why it works:** "I'm so happy with the platform — we caught a huge profit issue and turned it around within a day — cut 3K of wasted spend almost immediately!" (Jordan West, StoreHero homepage testimonial, cited in `../competitors/storehero.md`). The traffic-light + scale/pivot/pause framing is the wedge.
- **Source:** `../competitors/storehero.md`; https://storehero.ai/features/

### Putler ([profile](../competitors/putler.md))
- **Surface:** Products Dashboard / Leaderboard, Customers Dashboard / RFM, Sales Heatmap, Time Machine "Performance Comparison Report", Home Dashboard "Three Months Comparison" widget. Triage logic is via star icons on top revenue products, RFM 11-segment names, and AI Growth Tips.
- **Visualization:** Three coexisting patterns — (1) sortable leaderboard table with **star icons inline next to top-revenue products**; (2) **80/20 Breakdown Chart** — "trend line showing how revenue concentration shifts over time across the product catalog"; (3) **6×6 RFM 2D matrix** with 11 named segments overlaid as colored regions; (4) "Three Months Comparison" widget showing visitor count, conversion rate, ARPU, revenue for last 90d vs preceding 90d side-by-side.
- **Layout (prose):** Products Dashboard: "sortable list/table of every product, with top revenue generators 'marked with stars'" — stars are an explicit visual triage badge for winners. Adjacent 80/20 trend line shows concentration drift. RFM matrix: Recency (0–5) on X-axis, combined Frequency+Monetary (0–5) on Y-axis, 36-cell grid with 11 named segments — Champions (top-right), Loyal Customers, Potential Loyalist, Recent Customers, Promising, Customers Needing Attention, About To Sleep, At Risk, Can't Lose Them, Hibernating, Lost (bottom-left). "Each segment is rendered as a distinct colored region — 'Giving a distinct color to each segment will allow easier recall.'" (verbatim from `../competitors/putler.md`).
- **Specific UI:** Star icons inline next to top-revenue products. RFM segment regions colored to encode urgency/value. "Did You Know" tile rotates daily growth tips. Activity Log shows colored dots (event type indicators) and timestamps streaming new sales / refunds / disputes / failures with a dropdown filter to scope by event type.
- **Filters:** Date-range picker scopes all widgets simultaneously. Activity Log dropdown filters event types. Products: 5 filter chips (Customer count, Quantity sold, Refund percentage, Average price tier, Attributes).
- **Data shown:** Revenue, units sold, refund rate, refund timing, AOV, predicted future sales, variation-level revenue, co-purchase pairs per product. RFM: customer count per segment, segment-level revenue, segment recommendations ("retain", "win back").
- **Interactions:** Click product row → Individual Product card with predicted-monthly-sales, average-time-between-sales, sales-history timeline, "frequently bought together." Click any RFM segment → view customers in segment, 3-click export to Mailchimp or CSV.
- **Why it works:** Long-tenured users dominate the review pool — "Putler has been my trusted data companion for a decade." (Ekaterina S., Capterra, Oct 2025) — the "what changed and where" surfaces are the daily-use job. "It's a game-changing dashboard for viewing sales-related data." (Matt B., Capterra, Feb 2025).
- **Source:** `../competitors/putler.md`; https://www.putler.com/product-analysis; https://www.putler.com/blog/rfm-analysis/

### Atria ([profile](../competitors/atria.md))
- **Surface:** Sidebar > Analytics > select ad account > **Radar**. The defining winners-losers surface in the entire competitor pool — purpose-built for the triage job.
- **Visualization:** Portfolio table of every creative with per-axis **letter-grade columns (A–D)** plus per-row **triage badges** (Winner / High Iteration Potential / Iteration Candidate). Two primary tabs at top: **Winners** and **High Iteration Potential**. Iteration Candidate appears on individual cards but is not enumerated as its own tab in Atria's help center.
- **Layout (prose):** Top of page exposes "Radar Settings" controls where the operator picks which key metric Raya grades on. Below that, two primary tabs: **Winners** and **High Iteration Potential** (both confirmed in Atria's own help center). Main canvas is a "portfolio table of every creative with letter-grade columns and 'full metrics… ROAS, CTR, spend, and AOV visible at a glance'" (verbatim from `../competitors/atria.md`). Each row has an **"Iterate" CTA** wired to an AI iteration workflow.
- **Specific UI:** Letter grades **A through D** (third-party-attested, not vendor-attested in the help center). Grades shown across multiple axes — third-party sources disagree on the exact axis set: max-productive.ai cites "Conversion, Hook, Retention, and CTR"; gethookd.ai cites "ROAS, CTR, hook rate, and retention." Hook + Retention + CTR confirmed across sources; the fourth axis is ambiguous (ROAS vs. Conversion). Triage badges on each ad card: "Winner," "High Iteration Potential," "Iteration Candidate." Column headers expose grade rationale on hover ("Hover over column headers to understand grade rationale" — official help doc).
- **Filters:** Tab switch between Winners / High Iteration Potential; ad-account selector; key-metric selector via Radar Settings.
- **Data shown:** Letter grades per axis; ROAS, CTR, spend, AOV per ad; auto-identified personas (example: "Eco-conscious Consumers", "Coffee Enthusiasts"); specific improvement flags ("weak CTAs or unclear value propositions").
- **Interactions:** Tab between Winners / High Iteration Potential. Click "Iterate" → AI iteration workflow generates an improved variant tuned to the flagged weakness. Click into an ad → recommendation detail view with target personas + prioritized improvement actions.
- **Why it works:** Help doc literally prescribes the cadence: "Check Radar weekly. It's the fastest way to know what to scale, what to kill, and what to iterate on." (verbatim, intercom.help). G2 reviewer: "The Radar feature and creative breakdowns make it easy to see why certain ads perform and what needs to be adjusted to improve other ones." (search excerpt, 2026, cited in `../competitors/atria.md`).
- **Source:** `../competitors/atria.md`; https://intercom.help/atria-e5456f8f6b7b/en/articles/13862195-meet-raya-your-ai-creative-strategist; https://max-productive.ai/ai-tools/atria/; https://www.gethookd.ai/blog/atria-ai-reviews-pricing-alternatives-is-this-facebook-ad-tool-legit

### Motion ([profile](../competitors/motion.md))
- **Surface:** Sidebar > Creative Analytics > **Ad Leaderboard**, **Launch Analysis**, **Top Performing Reports**, **Comparative Analysis**, **Winning Combinations**.
- **Visualization:** Visual-first creative leaderboard — creative thumbnails alongside performance metrics in a stack/list (not a metrics-only table). Launch Analysis is a time-bounded list/table grouped by launch cohort with **status labels** ("scaling / declining / early-winner") attached per ad. "Color-coded reports and intuitive charts" (per ad-analysis-tool page).
- **Layout (prose):** Public-facing copy describes Ad Leaderboard as "Weekly top ads leaderboard" surfacing "top-performing and declining ads." Visual-first: "actual creative assets alongside performance data." Click a thumbnail → **Creative Insights modal** with a video-CTR/retention chart at top and demographic breakdown below. Launch Analysis: "Track newly launched creative to identify scaling, declining, and early-winner ads" (verbatim).
- **Specific UI:** Creative thumbnails — videos play in-line per Product Hunt comments. Sort/filter chips for date range, performance metric, naming-convention values, and tags. Snapshot button publishes leaderboard as shareable URL (frozen or live). Status labels on Launch Analysis ads ("scaling", "declining", "early-winner") function as triage badges.
- **Filters:** Date range, performance metric, naming-convention values, tags; cohort filter on Launch Analysis. Reports are single-account-scoped — cannot blend cross-account data into one report.
- **Data shown:** Spend, impressions, clicks, conversions, ROAS, CPA, CTR, hook rate (3-second video plays), watch time, conversion rate, plus Motion's hook/watch/click/convert composite scores. TikTok specifically: second-by-second video CTR.
- **Interactions:** Click thumbnail → Creative Insights modal (video-retention chart on top, gender × age demographic breakdown on bottom). Snapshot to share. AI Tasks one-click workflows e.g. "creative diversity review" output to Inbox; Agent Chat for follow-up.
- **Why it works:** "Motion solves for analysis paralysis by providing digestible insights which makes it easy to work with creative teams and streamline the creative iteration process." (Josh Yelle, Wpromote, Motion media-buyers page, cited in `../competitors/motion.md`). The visual-first leaderboard pattern is repeatedly cited as the category benchmark.
- **Source:** `../competitors/motion.md`; https://motionapp.com/solutions/creative-testing-tool; https://motionapp.com/library/talk/go-deeper-with-creative-insights/

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Profit Benchmarks (unlocks at Day 90); Performance Targets; cross-platform creative views. No standalone Winners/Losers leaderboard in their public-facing IA — instead, the differentiator is *attribution-model deltas* surfaced as a 7-model comparison table.
- **Visualization:** Tabular per-attribution-model comparison — for any campaign / channel / ad, columns show attributed revenue and transactions across the 7 attribution models side-by-side; "deltas between models highlighted as the primary insight" (verbatim from `../competitors/northbeam.md`). Profit Benchmarks shows live performance against benchmark targets.
- **Layout (prose):** Marketing page describes three feature blocks: Performance Targets, Cross-Platform Functionality, Growth Strategy. UI specifics are limited in public sources. Computes target ROAS / MER / CAC against actual contribution margins.
- **Specific UI:** "Compare any two of the 7 models; export to CSV; overlay platform-reported numbers as a third column for reconciliation against Meta/Google self-reporting" (verbatim from `../competitors/northbeam.md`). UI details beyond this are not available in public docs.
- **Filters:** Attribution model picker (7 options); date range; channel.
- **Data shown:** Per model: Attributed Revenue, Transactions; deltas between models highlighted. Profit Benchmark: target ROAS / MER / CAC vs. actual.
- **Interactions:** Compare any two models; export to CSV; overlay platform-reported numbers as third column.
- **Why it works:** Reviewer signal centered on the attribution-delta surface as a winners-losers proxy — campaigns that look good on platform-reported but bad on Northbeam's MTA are losers; campaigns underrated by platform-reported but strong on MTA are winners. Onboarding pain is the universal complaint.
- **Source:** `../competitors/northbeam.md`

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** AI Ads Manager — a unified table that's also a write-back surface to ad platforms.
- **Visualization:** Multi-platform unified table with creative thumbnails, customizable column picker, attribution-model dropdown, conversion-window selector, custom-metric builder, AI chat panel embedded in the same screen.
- **Layout (prose):** Unified rows blending Meta + Google + TikTok + LinkedIn in one table. Drill-down hierarchy: campaign → ad set → individual ad → creative-level performance. AI chat alongside surfaces "AI recommendations to optimize spend, creatives, and targeting."
- **Specific UI:** Bulk actions: "Manage budgets, pause under performers, and scale winners directly from Cometly without switching ad platforms" (verbatim from `../competitors/cometly.md`) — i.e. the table is **read-write** and mutates the upstream ad platform via API. AI chat surfaces explicit dollar-level recommendations ("Scale Adset 1 from $20/day to $50/day").
- **Filters:** Attribution-model dropdown, conversion-window selector, custom-metric builder using "existing data formulas," date range.
- **Data shown:** Spend, impressions, clicks, conversions, revenue (Cometly-attributed), ROAS, CPA, custom metrics built from formulas. "Continuous LTV tracking per customer."
- **Interactions:** Bulk pause / scale from inside the table; AI Chat with write-back actions.
- **Why it works:** "AI Chat that mutates ad accounts" is positioned as the differentiator vs. read-only analytics — the winners-losers loop closes inside Cometly without switching to Meta Ads Manager.
- **Source:** `../competitors/cometly.md`; https://www.cometly.com/features/ads-manager

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Product Analytics (Advanced tier+) — tabular SKU/variant view explicitly framed as "winner/loser product identification."
- **Visualization:** Sortable SKU/variant table with **per-product net profit margin** column displayed as percentage. Per-row breakdown: ad spend allocated to product, page views, add-to-cart rate, conversion rate, COGS, shipping, fees.
- **Layout (prose):** "Tabular SKU/variant view with per-product net profit margin displayed as a percentage (the walkthrough blog cites '58.95% and 45.23%' as live examples)… Designed to enable 'winner/loser product identification' — the framing implies sortable columns and probably a top/bottom split, but column-level UI is not pictured." (verbatim from `../competitors/trueprofit.md`).
- **Specific UI:** SKU/variant rows; margin % column; cost-breakdown columns (COGS, shipping, ad spend, fees); funnel-metric columns (views, ATC, CVR). UI details limited — no published triage badges or letter grades.
- **Filters:** Date range and store assumed but not confirmed from public sources.
- **Data shown:** Net profit per product, profit margin %, ad spend per product, COGS, shipping, page views, ATC rate, conversion rate.
- **Interactions:** Sortable columns implied. Drill-down behavior not confirmed from public sources.
- **Why it works:** Reviewer signal points to per-SKU margin clarity as the wedge — the "is this product a winner or loser?" question gets answered at the row level once cost config is in.
- **Source:** `../competitors/trueprofit.md`

## Visualization patterns observed (cross-cut)

Synthesizing the per-competitor sections into a count by viz type:

- **Sortable table with saved-view filter chips:** 4 competitors (Conjura Product/Order/Customer Tables, TrueProfit Product Analytics, Cometly AI Ads Manager, Putler Products Dashboard) — the dominant pattern in profit-first ecommerce tools. Reviews positive on action-orientation; chip labels function as triage names ("slow movers", "unprofitable products", "items selling out").
- **Letter-grade + triage-badge per row:** 1 competitor (Atria Radar — A–D grades across Hook/Retention/CTR + ROAS/Conversion, badges Winner / High Iteration Potential / Iteration Candidate). Strongly differentiated, prescriptive cadence ("Check Radar weekly").
- **Categorical status labels per row (no letter):** 1 competitor (Motion Launch Analysis "scaling / declining / early-winner"). Looser than Atria's badges; same triage intent.
- **Anomaly-card / alert-inbox feed:** 2 competitors (Triple Whale Lighthouse → Moby Anomaly Detection Agent; Lebesgue Revenue Drop Investigator agent). Card-per-anomaly, severity + metric + suggested-action.
- **Two-state traffic light (green/red, no amber):** 1 competitor (StoreHero Goals & Forecasting). Explicitly two-state per their own marketing copy.
- **Recommendation pills (pause / pivot / scale):** 1 competitor (StoreHero Spend Advisor) — three discrete states tied to a live what-if input.
- **2D scatter for outlier identification:** 1 competitor (Conjura Campaign Deepdive "KPI Scatter Chart" — two ratio metrics plotted, outliers identified).
- **Visual-first creative leaderboard with thumbnails:** 2 competitors (Motion Ad Leaderboard, Atria Radar). Creative asset adjacency to metrics is the category benchmark on the ads side.
- **80/20 concentration trend line:** 1 competitor (Putler Products Dashboard) — drift of revenue concentration over time.
- **6×6 RFM matrix with 11 named segments:** 1 competitor (Putler RFM 2D Chart) — also functions as a customer-class winners-losers grid (Champions vs. Lost).
- **Star-icon inline-flag for top performers:** 1 competitor (Putler Products Dashboard).
- **KPI-tile period-vs-period delta on every dashboard tile:** 3+ competitors (Triple Whale Summary Dashboard, Putler Home Dashboard "Three Months Comparison" widget, Lebesgue Business Report). Universal pattern at the dashboard-overview level — every tile carries an inline delta.

Recurring color conventions:
- **Red = decline / loss / refund**, green = improvement / sale: 6+ competitors (Putler Transactions colors sales green and refunds red, Triple Whale, Conjura, Motion, StoreHero, Atria implied via badge color).
- **Blue = improvement (NOT green)**: 1 competitor (Lebesgue Business Report — explicit "blue for improvements, red for declines"). Outlier in the set; conflicts with the dominant convention.
- **Single-hue gradient (heatmaps)**: Putler Sales Heatmap uses single-hue darker-= -more, not diverging. Conjura LTV heatmap same convention ("darker = higher").

Recurring interaction patterns:
- Click row / card → drill-down detail (universal).
- Saved views as filter chips (Conjura, Putler, Cometly).
- Click triage badge or status → action workflow (Atria "Iterate", Cometly "Scale / Pause", Triple Whale "Push Audience").
- Snapshot / share-as-URL (Motion, Triple Whale dashboards). Removes seat-cost friction.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Triage at a glance — knowing what to scale / fix / kill in seconds**
- "The Radar feature and creative breakdowns make it easy to see why certain ads perform and what needs to be adjusted to improve other ones." — G2 reviewer, cited in `../competitors/atria.md`
- "Motion solves for analysis paralysis by providing digestible insights which makes it easy to work with creative teams and streamline the creative iteration process." — Josh Yelle, Wpromote, cited in `../competitors/motion.md`
- "Simple to use, seriously rich insights, all action-orientated." — Rapanui Clothing, Shopify App Store, October 2024, cited in `../competitors/conjura.md`
- "These have saved me so much time with ideation and strategy so that I can focus on ad creation." — G2 reviewer praising Inspo + AI Recommendations + Radar + Clone Ads, cited in `../competitors/atria.md`

**Theme: Catching problems early via deltas / anomalies**
- "I'm so happy with the platform — we caught a huge profit issue and turned it around within a day — cut 3K of wasted spend almost immediately!" — Jordan West, StoreHero homepage testimonial, cited in `../competitors/storehero.md`
- "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." — ChefSupplies.ca, Shopify App Store, January 2024, cited in `../competitors/conjura.md`
- "Real-time data, clean dashboards, mobile app, and automation consistently save operators 4–8 hours per week." — AI Systems Commerce 2026 review, cited in `../competitors/triple-whale.md`

**Theme: Action coupled to the row, not buried in a separate flow**
- "Atria gives me everything I need in one place." — Boris M., Creative Strategist, Atria homepage, cited in `../competitors/atria.md`
- "The product deep dive down to SKU level is phenomenal, as well as the insights around LTV." — Amelia P., G2, December 2023, cited in `../competitors/conjura.md`
- "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — Bell Hutley, Shopify App Store, March 2024, cited in `../competitors/conjura.md`

**Theme: Visual + metric adjacency (creative thumbnails next to KPIs)**
- "Motion has helped ATTN unearth a new way of looking for patterns within the data that wouldn't have been feasible due to time and labor constraints. Having the actual creative displayed next to our metrics has also opened up a whole new world of what we can do in terms of strategy and driving insights." — David Adesman, ATTN Agency, cited in `../competitors/motion.md`
- "Motion links performance data with the actual creatives in a clean dashboard, making it much easier to answer questions like 'Which hook style is working?' or 'Which UGC format is driving the best ROAS?' without exporting a ton of data." — G2 reviewer aggregation, cited in `../competitors/motion.md`

**Theme: Scheduled cadence — winners/losers as a weekly ritual**
- "Check Radar weekly. It's the fastest way to know what to scale, what to kill, and what to iterate on." — Atria official help doc, cited in `../competitors/atria.md`
- "The metrics and pacing data delivered via email save time." — Marco P., Owner, Capterra, January 2025, cited in `../competitors/lebesgue.md`
- "Easy to set KPIs and watch over business reports including your marketing costs, shipping costs, revenue, forecast for sales." — Sasha Z., Founder, Capterra, September 2025, cited in `../competitors/lebesgue.md`

## What users hate about this feature

**Theme: AI-flagged "insights" that are too shallow to act on**
- Capterra summary criticism: "insights" can be a little basic, such as simply noting that CAC increased and conversion rate dropped off — would prefer "more actionable guidance." — paraphrased Capterra synthesis, cited in `../competitors/lebesgue.md`
- "The software did not provide meaningful or actionable data to identify or scale top-performing creatives, and AI-generated ads were below usable quality standards." — Trustpilot reviewer (search-summary excerpt, 2025–2026), cited in `../competitors/atria.md`

**Theme: Single-account / single-store scoping breaks the agency / multi-brand workflow**
- "Each Motion report is scoped to a single ad account" — Superads, cited in `../competitors/motion.md`
- "Users find it frustrating that you can only use one account per platform, as on Meta, they would like to be able to use two accounts at once." — G2 reviewer aggregation, cited in `../competitors/motion.md`
- "Could not break down or analyze performance by country within the same account." — Tomás Manuel J., Performance Manager, Capterra, February 2026, cited in `../competitors/lebesgue.md`

**Theme: Overwhelming dashboards / data-dense rows that hide the signal**
- "The dashboard can sometimes feel overwhelming with so many parameters." — Ekaterina S., Capterra, October 2025, cited in `../competitors/putler.md`
- "Modifying reports or navigating menus is a cluster." — BioPower Pet, Shopify App Store, April 2026, cited in `../competitors/triple-whale.md`
- "for a small operation it's just way overload." — BioPower Pet, Shopify App Store, April 2026, cited in `../competitors/triple-whale.md`

**Theme: Triage labels that don't reconcile with platform self-reporting**
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson, Brightleaf Organics, workflowautomation.net, March 2026, cited in `../competitors/triple-whale.md`
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — AI Systems Commerce 2026 review, cited in `../competitors/triple-whale.md`

**Theme: Action wired to the row but the action mutates the wrong thing**
- "Some users reported that the Clone Ad tool's AI significantly altered the look of their products, with results that do not even closely resemble the original products." — G2 review summary, cited in `../competitors/atria.md`

## Anti-patterns observed

- **Anomaly inbox without ranked context.** Triple Whale's Lighthouse fires "Spend Anomalie" / "Orders Anomalie" cards individually but does not (per public sources) present them as a single ranked-delta leaderboard. Reviewers in `../competitors/triple-whale.md` describe modifying reports as "a cluster" — possibly because anomalies arrive scattered, not pre-ranked.
- **Letter grade without published rubric.** Atria surfaces A–D letter grades but Atria's own help center does not enumerate the grade scale on the public page; the A–D range is third-party-attested only, and even the *axis set* is contested between sources (Hook + Retention + CTR confirmed; fourth axis is ROAS-vs-Conversion depending on reviewer). Users can't verify what threshold made an ad a "B" vs. a "C". `../competitors/atria.md`.
- **Two-state traffic light (no amber).** StoreHero's green/red Goals system explicitly omits an amber middle state. The "you're slipping but not failing" range collapses into one of the two — forces a binary verdict on borderline performance. `../competitors/storehero.md`.
- **Single-color heatmap intensity without numeric labels.** Putler Sales Heatmap "Darker spots mean more sales. Lighter spots mean quieter periods" with "no numeric values printed in cells; activity inferred from shade only." Hides the *magnitude* of differences between cells. `../competitors/putler.md`.
- **Saved-view chips without underlying threshold transparency.** Conjura's "unprofitable products", "slow movers", "items selling out" chips above the Product Table — the threshold for each (e.g. what makes a product "slow") is not surfaced inline; users have to inspect or trust the default. `../competitors/conjura.md`.
- **Hidden source disagreement on the row.** Conjura shows Last Click columns alongside Platform Attributed columns, exposing the disagreement; Triple Whale Summary collapses Triple-Pixel-attributed and platform-reported revenue into single tiles by default — the disagreement that *is* the information is hidden behind an attribution-model selector switch.
- **Single-account scoping on the leaderboard.** Motion explicitly: "Each Motion report is scoped to a single ad account." Multi-store / multi-account brands cannot see a unified winners-losers ranking — defeats the central point of the surface for agencies. `../competitors/motion.md`.
- **Color convention drift.** Lebesgue uses **blue for improvements, red for declines** while the rest of the competitor pool uses green-positive / red-negative. A merchant who switches tools loses the visual instinct that "green = good." `../competitors/lebesgue.md`.
- **Pricing-creep via spend-bracketed leaderboards.** Motion's spend-based pricing puts the leaderboard surface itself behind escalating cost as the merchant scales — "I almost had a heart attack. Motion would eat into our margins way more than we're comfortable with" (Josh Graham, cited in `../competitors/motion.md`). The leaderboard punishes its own use case.

## Open questions / data gaps

- **Atria's exact letter-grade thresholds.** A vs. B vs. C vs. D rubric is not enumerated on the public page; what numeric threshold defines each band is invisible to a non-customer. Resolving requires a paid trial.
- **Triple Whale's Lighthouse → Moby migration UI.** Lighthouse marketing pages now redirect to the Moby AI page; the in-app surface for "Anomaly Detection Agent" / "Order & Revenue Pacing Agent" / "Revenue Anomaly Agent" is not directly observable from public pages. KB.triplewhale.com 403's WebFetch.
- **Whether ranked-delta tables exist as standalone surfaces in any competitor.** Most "winners-losers" framing is *implicit* in saved views (Conjura), tab labels (Atria), or status pills (Motion Launch Analysis). A dedicated "Biggest Movers" or "Top Gainers / Top Losers" table — common in financial dashboards — was not directly observable as a top-level nav item in any competitor profile read.
- **How thresholds for triage labels are configured (or whether they're tunable).** Atria exposes a "key metric" picker but not the band cutoffs. StoreHero's traffic-light cutoffs are not published. Conjura saved-view chips ("slow movers") have no exposed configuration.
- **Whether competitors expose statistical-significance gates** (e.g. "this delta is not significant — too few sessions / orders to claim a winner"). None of the profiles read describe a confidence threshold; small-sample false-positive winners may be a category-wide blind spot.
- **Mobile presentation of winners-losers.** Triple Whale ships push notifications on revenue milestones "within minutes of the triggering event" (`../competitors/triple-whale.md`), but the mobile equivalent of the desktop ranked-delta table or Radar grid was not directly screenshotted. StoreHero's iOS app is "read-only summary"; mobile triage-screen UI is largely unverified.
- **Whether anomaly-card / alert-inbox volume becomes noise.** Triple Whale Lighthouse predates the Moby Agents rebrand; reviewer signal on alert volume / acknowledge-rate / dismissal patterns is not directly captured in `../competitors/triple-whale.md`.
- **No competitor profile read documents a "fading" or "momentum" metric** explicitly named as such. The pattern is implicit in "declining" labels (Motion) and "drift" alerts (StoreHero) but a calendar-aware "fading this week" label is not surfaced explicitly anywhere read.

## Notes for Nexstage (observations only — NOT recommendations)

- **Atria's Radar is the only competitor in the pool with a purpose-built winners-losers surface as a top-level navigation item.** Every other competitor ships the pattern as saved-views on tables (Conjura, Cometly, Putler, TrueProfit), as anomaly inboxes (Triple Whale), or as period-vs-period deltas on KPI tiles (universal). The absence of a dedicated "Biggest Movers" leaderboard as a standalone nav target across 9 of 10 competitors is notable for IA decisions.
- **Triage badges + letter grades are the rarest viz pattern (1 competitor — Atria) but reviewers single it out as the wedge.** The "school-grade rubric translated into one of three weekly actions (scale / iterate / kill)" pattern is differentiated. The cost is opacity — Atria's grade thresholds are not published, and reviewers cannot verify what made an ad a "B" vs. a "C." Nexstage's "compute on the fly" rule plus source-badge transparency thesis intersects with this directly: a triage label *should* expose its source and threshold inline, not hide them.
- **Color convention is mostly green-positive / red-negative (5+ of 10 competitors); Lebesgue is the lone outlier with blue-positive.** Nexstage's `--color-source-{real,store,facebook,google,gsc,ga4}` token set already includes a Google blue and a Facebook blue — using blue for "improvement" would clash semantically with the source-badge palette. Worth flagging in the design tokens conversation.
- **Two-state traffic lights (StoreHero green/red, no amber) vs. three-state pills (StoreHero Spend Advisor pause/pivot/scale).** Same vendor uses two different cardinalities for different decision contexts. Three-state is more common across the pool when the decision is action-typed (scale / iterate / kill); two-state shows up for binary on-pace / off-pace goal tracking.
- **Saved-view filter chips on a generic table is the dominant low-cost implementation.** Conjura, Cometly, Putler, TrueProfit all ship the pattern. The chip text is the triage label ("slow movers", "unprofitable products", "items selling out"). This is cheap to ship on top of any existing table — same UX vocabulary across multiple Nexstage pages (orders, ads, products) without building a new surface.
- **No competitor profile documents a statistical-significance gate.** Small-sample false-positive winners (e.g. "this campaign 'gained' 4× because it ran for 2 days last week and 8 days this week") appear to be a category-wide blind spot. Worth investigating whether `daily_snapshots` / `hourly_snapshots` carries enough signal to derive a confidence band.
- **The ranked-delta numbers on every leaderboard depend on cost config being right.** Conjura, StoreHero, TrueProfit, Lebesgue all anchor their winners-losers surfaces on contribution profit / margin — meaning a misconfigured COGS turns the leaderboard into noise. CLAUDE.md's "Cost/attribution config changes trigger retroactive recalc" note via `RecomputeAttributionJob` directly maps to this surface.
- **Click-row → action is the universal interaction pattern at the winner-loser level.** Atria "Iterate" → AI variant generation; Cometly "Scale / Pause" → write-back to ad platform; Triple Whale "Push Audience" → Meta CAPI; Putler "Refund" → write-back to gateway. Read-only winners-losers is the SMB-tool convention; read-write is the wedge. Nexstage's read-only stance is consistent with most peers but is the explicit *non-feature* vs. Cometly / Triple Whale.
- **Anomaly inboxes (Triple Whale Lighthouse → Moby agents, Lebesgue Revenue Drop Investigator) coexist with leaderboards rather than replacing them.** They solve a different sub-question: "what *unexpectedly* changed?" vs. "what changed?" Worth holding separate in IA — alerts inbox is a different feature than winners-losers (see `alerts-inbox.md`).
- **Putler's RFM 2D matrix is a customer-class winners-losers surface in disguise.** 6×6 grid with 11 named segments (Champions → Lost) functions exactly like a ranked-delta table where the "delta" is segment membership change over time. Worth noting against any future customer-segment winners-losers cut.
- **Conjura's "incorporates product imagery alongside performance metrics" is repeatedly cited as a positive review theme** and Motion's "creative thumbnail next to metric" is the category benchmark on the ads side. Visual-asset adjacency to metric appears table-stakes for product- and ad-level winners-losers, but rare for campaign- and channel-level (no thumbnails to show).
- **Calendar-aware "this week vs. last week" and "MTD" are the dominant windows.** Atria help doc explicitly prescribes "weekly cadence." Putler Pulse zone is "current month MTD." Daily / weekly email digests (Lebesgue, Putler, StoreHero, Conjura) deliver the leaderboard asynchronously. The window choice itself is the surface's product decision — nobody read defaults to "rolling 90d."
- **No competitor read uses "Winners" and "Losers" as paired top-level surface names.** Atria uses "Winners" + "High Iteration Potential" (no surface called "Losers"). Motion uses "scaling / declining / early-winner" inline labels. The semantic asymmetry — naming the positive case explicitly but euphemising the negative ("High Iteration Potential" rather than "Losers") — is consistent across the set.
