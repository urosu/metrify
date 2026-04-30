---
name: Ad performance
slug: ad-performance
purpose: Answers "which campaigns / ad sets / ads are working today?" by showing a hierarchical paid-media table with side-by-side ROAS lenses, drill-down from campaign to creative, and stoplight/grade signals on what to scale, hold, or kill.
nexstage_pages: ads, performance, dashboard
researched_on: 2026-04-28
competitors_covered: triple-whale, northbeam, polar-analytics, thoughtmetric, adbeacon, hyros, cometly, wicked-reports, lebesgue, conjura, motion, trueprofit, fospha, atria
sources:
  - ../competitors/triple-whale.md
  - ../competitors/northbeam.md
  - ../competitors/polar-analytics.md
  - ../competitors/thoughtmetric.md
  - ../competitors/adbeacon.md
  - ../competitors/hyros.md
  - ../competitors/cometly.md
  - ../competitors/wicked-reports.md
  - ../competitors/lebesgue.md
  - ../competitors/conjura.md
  - ../competitors/motion.md
  - ../competitors/trueprofit.md
  - ../competitors/fospha.md
  - ../competitors/atria.md
  - https://docs.northbeam.io/docs/attribution-page
  - https://docs.northbeam.io/docs/creative-analytics
  - https://www.cometly.com/features/ads-manager
  - https://thoughtmetric.io/campaign_performance
  - https://www.conjura.com/campaign-deepdive-dashboard
  - https://www.adbeacon.com/the-adbeacon-chrome-extension-independent-attribution-inside-meta-ads-manager/
---

## What is this feature

Ad performance is the surface a media buyer or operator opens at 9am to answer the same recurring question: "where did yesterday's spend go, and what should I scale, hold, or kill before noon?" It assumes the merchant already has paid-media data inside Meta / Google / TikTok / etc. and that their Shopify or Woo store knows what actually got purchased — the feature's job is to **stitch those two truths into one table** with a hierarchy (campaign → ad set → ad), enough metrics to act on (spend, ROAS, CPA, CTR, ATC, conversions, attributed revenue), and at least one trust signal that explains why platform-reported numbers and store-side numbers disagree.

For SMB Shopify/Woo owners specifically, the difference between "having data" and "having this feature" is the consolidation. Native Meta Ads Manager gives Meta-self-reported revenue. Native Google Ads gives Google-self-reported. Shopify gives orders that get attributed to "Direct" 60% of the time. The merchant manually reconciles in a spreadsheet — or buys this feature, where Triple Whale, Northbeam, Polar, and ThoughtMetric all promise to render multiple platforms in one row, multiple attribution lenses in adjacent columns, and (in the better implementations) a side-by-side delta between platform-reported vs. independent-pixel-reported revenue so the merchant can see the disagreement that *is* the information.

## Data inputs (what's required to compute or display)

- **Source: Meta Ads API** — `campaigns.spend`, `campaigns.impressions`, `campaigns.clicks`, `adsets.spend`, `adsets.conversions`, `ads.id`, `ads.creative_id`, `ads.spend`, `ads.impressions`, `ads.clicks`, `ads.purchases`, `ads.purchase_value`, `ads.cpm`, `ads.cpc`, `ads.ctr`, `ads.add_to_cart`, view-through and click-through conversion windows
- **Source: Google Ads API** — `campaigns.spend`, `ad_groups.spend`, `ads.spend`, `ads.impressions`, `ads.clicks`, `ads.conversions`, `ads.conversion_value`, search-term and Performance-Max-specific breakdowns
- **Source: TikTok Ads API** — campaign / ad-group / ad spend, impressions, clicks, conversions, conversion_value, video metrics (3-sec views, hold rate)
- **Source: Pinterest / Snapchat / Bing / LinkedIn / Reddit Ads APIs** — same campaign-level shape; required for any cross-platform "blended" lens
- **Source: Shopify / WooCommerce** — `orders.total_price`, `orders.line_items`, `orders.created_at`, `orders.customer_id`, `orders.referring_site`, `orders.landing_site`, UTM params on the landing URL, `orders.financial_status`, `orders.refunds`. Used as the store-truth ROAS denominator's numerator.
- **Source: First-party pixel / server-side tracking** — `click_id` capture (fbclid, gclid, ttclid), session-stitch to order, server-side de-duplicated event payloads sent back via Meta CAPI / Google Enhanced Conversions / TikTok Events API
- **Source: GA4** (where exposed) — `sessions`, `conversions`, `revenue`, channel grouping; used for the GA4 column in side-by-side comparisons
- **Source: Computed** — `attributed_revenue = revenue × attribution_weight` (per chosen model: first-click, last-click, linear, position-based, time-decay, paid-linear, multi-touch, full-impact)
- **Source: Computed** — `ROAS = attributed_revenue / spend`, `CPA = spend / conversions`, `CTR = clicks / impressions`, `CPC = spend / clicks`, `CPM = spend / impressions × 1000`, `CVR = conversions / clicks`, all `NULLIF`-guarded for zero divisors
- **Source: User-input / channel-mapping** — UTM-to-channel mapping; ad-account-to-store mapping for multi-store; cost-config (COGS, fees) when ROAS extends to POAS / contribution profit
- **Source: Computed (cohort axis)** — `nc_ROAS = new_customer_revenue / spend`, `n_CAC = spend / new_customers`, requiring new-vs-returning split from store-side customer history

## Data outputs (what's typically displayed)

- **KPI row (page header):** Total Spend, Blended ROAS, Blended MER, Total Conversions, Total Attributed Revenue, CPA, vs prior-period delta — USD/% per workspace currency
- **Hierarchy table (primary surface):** rows expand Campaign → Ad Set → Ad. Each row repeats the same metric columns at its aggregation level
- **Per-row metric columns:** Spend, Impressions, Clicks, CTR, CPC, CPM, ATC, CPA, Purchases, Purchase Value, ROAS (×), CVR, Hook Rate / 3-sec view rate (Meta/TikTok), Watch Time, Net Customers, nc-ROAS — all sortable
- **Side-by-side ROAS lenses (multi-column same row):** Platform-reported ROAS, Pixel-attributed ROAS, GA4-reported ROAS, Store-attributed (last-click / multi-touch) ROAS — adjacent columns with the same denominator (spend) but different numerators (attributed revenue per source)
- **Attribution-model selector (top-of-page):** dropdown reflowing every revenue/ROAS column under the chosen model (First / Last / Linear / Position / Time-decay / U-Shaped / Multi-Touch / Full-Impact / Paid-Linear)
- **Lookback-window selector (top-of-page):** 7 / 14 / 30 / 60 / 90 days (and "infinite" / "LTV" on enterprise tools)
- **Stoplight / verdict column (optional):** green/yellow/red dot, A–F letter grade, or Scale / Chill / Kill three-state badge per row
- **Drill-down:** click an ad row → Creative Insights modal with creative thumbnail, video CTR over time, demographic breakdown, hold rate
- **Side-rail (optional):** Profitability / contribution-margin panel; AI verdict for selected rows; touchpoint-journey for individual conversions
- **Slice toggles:** New vs Returning customer split; Top-of-Funnel / Mid-Funnel / Bottom-of-Funnel funnel bucket; Platform tabs (Meta / Google / TikTok / Pinterest / Snapchat / Bing / Blended)

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Attribution / Pixel Dashboard (top-level nav). Summary Dashboard also surfaces blended ROAS/MER tiles per platform.
- **Visualization:** hierarchical table + attribution-model dropdown + on-demand refresh button; side-by-side columns for Triple-Pixel-attributed revenue vs Meta-reported revenue vs first/last-click splits.
- **Layout (prose):** "Top: date-range picker, store switcher, attribution-model selector, on-demand refresh button. Left rail: standard sidebar nav with Summary, Attribution, Pixel Events Manager, Creative Cockpit, Cohorts, Lighthouse. Main canvas: channel breakdown table; per-row columns split into Triple-Pixel attribution columns vs platform-reported columns vs first/last-click columns. Bottom: Moby Chat right-rail floating button on every dashboard."
- **Specific UI:** "Attribution-model selector (dropdown) reflows the channel revenue numbers when toggled. Per-row drill from channel → campaign → ad set → ad. Side-by-side Triple-Pixel-attributed revenue alongside Meta-reported revenue — the explicit two-source-of-truth column pattern. April 2026 'On-Demand Data Refresh' button cycles status text per integration ('Refreshing Meta…')." Sonar Optimize is a separate config surface that pushes deduped server-side conversions back to Meta CAPI / Google / TikTok / Reddit / X.
- **Filters:** date range, store, attribution model, attribution window, platform tabs.
- **Data shown:** spend, impressions, clicks, CPM, CPC, CTR, ROAS, ncROAS, MER, POAS, ATC, conversions, attributed revenue (Triple Pixel + platform-reported as parallel columns).
- **Interactions:** drill-down on row click; switch attribution model live; pin tile / pivot to table view; Moby Chat sidebar for natural-language query.
- **Why it works (from reviews/observations):** "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports." (Steve R., Capterra, 2024). "Best app i've used to track profit/loss great for beginners!" (Elyso, Shopify App Store, 2026). KB pages 403'd to WebFetch — exact column count and tooltip behaviour not directly observable.
- **Source:** [`triple-whale.md`](../competitors/triple-whale.md); `triplewhale.com/analytics`; `triplewhale.com/blog/triple-whale-product-updates-april-2026`

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Sidebar > Attribution (the "Attribution Home Page"). Default view is "Attribution: Revenue."
- **Visualization:** funnel-stage-sectioned table with global-filter strip on top (attribution model + window + accounting mode + granularity + time period + comparison mode); right-rail Profitability panel that gates empty until Day 90; Sales / Product Analytics / Orders / Creative Analytics tabs across the top.
- **Layout (prose):** "Top: global filters apply across all dashboards — Attribution Model (Clicks-Only / First Touch / Last Touch / Last Non-Direct / Clicks + Modeled Views / Clicks + Deterministic Views / Linear), Window selector (1d-Click through 90d-Click + LTV), Accounting Mode (Cash Snapshot vs Accrual), Granularity (Monthly / Weekly / Daily), Time Period, Previous Period / YoY toggle. Left rail: Sidebar with Overview, Attribution, Metrics Explorer, Settings/gear at bottom-left. Main canvas: vertical sectioning down the page — Sales → New Customers → Returning Customers → Top of Funnel (Demand Capture) → Bottom of Funnel (Demand Generation) → Organic and Owned Media. Right rail: Profitability panel that stays empty until Day 90."
- **Specific UI:** "Funnel-stage section dividers (Top vs Bottom of Funnel as named blocks rather than just channel rollup). New vs Returning customer split shown consistently across every section as a Blended / New / Returning column triplet. Inline tooltips on table headers (Touchpoints, Revenue, ROAS, CAC, Visitors, Customers — added in Northbeam 3.0). Full-screen toggle on tables and graphs." A separate **Model Comparison Tool** (top-right hamburger) puts two of seven attribution models side-by-side with platform self-reporting as a third column — purpose-built for "see how revenue and transactions shift across models without toggling back and forth."
- **Filters:** attribution model, window, accounting mode, granularity, time period, comparison mode, breakdown taxonomy (Platform / Category / Targeting / Revenue Source via the Breakdowns Manager).
- **Data shown:** Spend, Attributed Rev (windowed: "Attributed Rev (1d)", "ROAS (7d)", "LTV CAC"), Transactions, New Customer %, ROAS, CAC, MER, ECR, Visits, % New Visits, CPM, CTR, eCPC, eCPNV — each available as Blended / New / Returning variant.
- **Interactions:** drill from channel → campaign → ad set → ad; redefine row groupings via Breakdowns Manager; export CSV; Saved Views with rename/share.
- **Why it works (from reviews/observations):** "Northbeam's data is by far the most accurate and consistent." (Victor M., Capterra). "I check in every day. Our CFO checks in. Our CEO checks in. It's the first look of the day for all of us." (Claire Yi, Grüns case study). Critical: "complex to use, particularly for new users, and some of the visual design is still being refined" (Capterra aggregated, 2026).
- **Source:** [`northbeam.md`](../competitors/northbeam.md); `docs.northbeam.io/docs/attribution-page`

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Acquisition / Attribution surface (pre-built dashboard) + Custom Dashboards built on the same semantic layer.
- **Visualization:** side-by-side columnar table comparing Platform-reported revenue vs GA4 vs Polar Pixel for the same window — a literal three-source comparison column triplet per channel.
- **Layout (prose):** "Top: dashboard-level date range picker (top-right); attribution-model dropdown exposing 9-10 models (First Click, Last Click, Linear, U-Shaped, Time Decay, Paid Linear, Full Paid Overlap, Full Paid Overlap + Facebook Views, Full Impact). Left rail: folder tree of dashboards; '+' button creates new dashboards. Main canvas: vertical stack of blocks — KPI row (Sparkline Cards or Metric Cards across the top) → channel breakdown table → drill-down. Bottom: per-customer / per-order touchpoint-journey drill-down accessible from any conversion."
- **Specific UI:** "Attribution-model picker (dropdown) re-renders the same KPI block under a new model. Drill from channel → campaign → ad → order → customer journey: clicking an order opens the multi-touchpoint sequence that led to it. Sparkline card pattern: a metric card with mini trend line embedded inside the card itself."
- **Filters:** date range, store, channel, segment ("Views" — saved bundles of filters). Important quirk: "Multiple Views combine with OR logic, not AND" — non-obvious gotcha called out in their own help docs.
- **Data shown:** Spend, attributed revenue, ROAS, CAC, conversions per model — with platform/GA4/Polar columns visible at once.
- **Interactions:** switch attribution model → KPI block re-renders; drill from channel to campaign to ad to single order; schedule any block to Slack / Gmail; Ask Polar AI emits an editable Custom Report rather than a frozen chat answer.
- **Why it works (from reviews/observations):** "Their multi-touch attribution and incrementality testing have been especially valuable for us." (Chicory, Shopify App Store, Sept 2025). "The level of support you get from the polar team is outstanding, really willing to help." (Gardenesque, Shopify App Store, June 2024). Negative: "The user interface, while functional, lacks the visual polish seen in some competitors like Triple Whale." (Conjura comparison article, 2025).
- **Source:** [`polar-analytics.md`](../competitors/polar-analytics.md); `swankyagency.com/polar-analytics-shopify-data-analysis/`

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Product > Analytics > Campaign ("Ad Central") and Product > Analytics > Attribution.
- **Visualization:** hierarchical table (Campaign → Ad Set → Ad) inside a unified ad-account workspace ("Ad Central"); "Compare Lines" dual-metric view that overlays two metrics on one graph.
- **Layout (prose):** "Top: attribution-model selector (Multi-Touch / First Touch / Last Touch / Position Based / Linear Paid), attribution-window selector (7 / 14 / 30 / 60 / 90 days). Left rail: Analytics submenu (Attribution, Campaign, Creative, Customer, Product). Main canvas: hierarchical table — Campaign level above Ad Set level above Ad level — with a quick toggle to switch between ad platforms (Meta / Google Ads / TikTok / Pinterest) without changing tabs. Bottom: 'Compare Lines' dual-metric overlay graph."
- **Specific UI:** "Ad Central" unified workspace housing all ad accounts — "no additional logins required." Platform toggle visibly switches the table data without a new page load. Dual-metric view "displays two metrics simultaneously on one graph" with "automatic calculations (no manual formulas needed)."
- **Filters:** attribution model, attribution window (7/14/30/60/90), platform toggle, date range.
- **Data shown:** Total spend, ROAS, Conversions, CPA per Campaign / Ad Set / Ad row. Channel rows enumerated: Meta Ads, TikTok, Pinterest, Google Ads, Bing, organic social, email/SMS, podcasts, influencers, affiliates, UTM-based custom channels.
- **Interactions:** platform toggle, hierarchy drill, attribution-model swap, dual-metric overlay.
- **Why it works (from reviews/observations):** "Really great app! it has all the data you need in order to manage multi channel marketing spend, and effort. We can see the attribution when orders come in instantly." (Olive Odyssey, Shopify App Store, Jan 2025). "TM has really helped us understand what's working and what's not; trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time." (WIDI CARE, Shopify App Store, Dec 2024).
- **Source:** [`thoughtmetric.md`](../competitors/thoughtmetric.md); `thoughtmetric.io/campaign_performance`

### AdBeacon ([profile](../competitors/adbeacon.md))
- **Surface:** Optimization Dashboard (primary post-login surface) + a Chrome Extension overlay that injects directly into Meta Ads Manager.
- **Visualization:** Meta-modeled tabular hierarchy + Chrome-extension side-by-side columns comparing Meta-reported metrics to AdBeacon-tracked metrics inside the native Ads Manager UI.
- **Layout (prose):** "Top: tabbed nav, attribution-model toggle (First Click / Last Click / Linear / Full Impact). Left rail: navigation 'modern and simple with a navy blue and highlights of green color' (smbguide.com). Main canvas: 'modeled off Meta's user interface, allowing you to make real-time campaign optimization changes right from within the platform' (smbguide.com). Bottom: in-platform edit controls for ad campaigns (push to Meta/Google directly)."
- **Specific UI:** "Chrome Extension overlay: side-by-side columns inside Meta Ads Manager comparing Meta-reported metrics to AdBeacon-tracked metrics at the Ad Set + Ad level (not Campaign level). In-overlay attribution-model toggle (First Click / Last Click / Linear / Full Impact). Account switcher dropdown for agencies managing multiple clients. Customizable metric chooser." Click-only attribution philosophically — view-based attribution explicitly rejected as "inflated metrics."
- **Filters:** ad set, date, campaign, attribution model.
- **Data shown:** CTR, CPC, impressions, ROAS, conversions, custom KPIs; tracked purchases, attribution-based revenue, orders.
- **Interactions:** in-platform optimization actions push to Meta/Google directly (read-write table); switch attribution model in real-time inside Ads Manager via the extension; toggle between connected client accounts.
- **Why it works (from reviews/observations):** "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey… they (customers) love that." (agency testimonial, AdBeacon marketing). "Real-time clarity turned ad spend into impact" (Tanners Fish case study). Public reviews are sparse — 0 Capterra reviews as of April 2026.
- **Source:** [`adbeacon.md`](../competitors/adbeacon.md); `adbeacon.com/the-adbeacon-chrome-extension-independent-attribution-inside-meta-ads-manager/`

### Hyros ([profile](../competitors/hyros.md))
- **Surface:** Sidebar > Reports (full-power report builder) and Sidebar > Dashboard (Quick Reports with widget grid).
- **Visualization:** multi-pivot report builder; Dashboard has a widget grid including a unique **"Reporting Gap"** widget that surfaces the delta between Hyros-attributed sales and ad-platform-reported sales.
- **Layout (prose):** "Top: date range filter; saved-report tabs above the canvas. Left rail: nav including Dashboard, Reports, Leads, Call Tracking, Tags, Blacklist, Settings. Main canvas: pivot table with filter/group toggles for campaign, ad set, individual ad, landing page, traffic source, attribution model, customer segment. Bottom: per-row drill-into-lead-journey link opens 'Deep Mode' showing every page view, ad click, email open, click, form submit, call, and purchase event for the lead."
- **Specific UI:** "Reporting Gap widget — top-level dashboard tile showing the delta between Hyros's attributed conversions and what Meta/Google self-report. This is a single-pair version of multi-source-of-truth visualization. Two view modes shipped 2025–2026: Basic View ('clean, simple stats at a glance') and Pro View ('full power-user mode, custom widgets, advanced dashboards'). Default load is the last 7 days."
- **Filters:** campaign, ad set, individual ad, landing page, traffic source, date range, attribution model (Last click / First click / Linear / Time decay / Scientific Mode / Custom), customer segment.
- **Data shown:** Spend, impressions, clicks, CTR, CPC, attributed revenue, ROAS, leads, calls booked, calls closed, AOV, LTV (cohort-bound), refund-adjusted ROAS.
- **Interactions:** drill row → Lead Journey vertical timeline; pivot grouping; saved-view tabs; export.
- **Why it works (from reviews/observations):** "We receive pitches from competitors monthly, but no tool matches HYROS data quality." (Pummys, Trustpilot). Negative: "UI feels dense. Lots of power, but it took me a week to feel smooth." (Scout Analytics, 2026).
- **Source:** [`hyros.md`](../competitors/hyros.md)

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** Sidebar > AI Ads Manager (primary surface; replaces "switching between ad platforms").
- **Visualization:** unified tabular ad-account view with rows for campaigns / ad sets / ads across Meta + Google + TikTok + LinkedIn in one table; AI Chat panel embedded alongside the same screen.
- **Layout (prose):** "Top: customizable column picker, attribution-model dropdown (First Touch / Last Touch / Linear / U-Shaped / Time Decay), conversion-window selector (30 / 60 / 90), 'Easily Switch Ad Accounts' single-click navigation. Left rail: sidebar nav. Main canvas: multi-platform unified table with rows for campaigns/ad sets/ads. Right rail (or panel): AI Chat embedded for natural-language query."
- **Specific UI:** "Multi-platform unified rows (Meta + Google + TikTok + LinkedIn in one table). Customize Columns lets users select which metrics to display. Daily Breakdown lets users view performance day-by-day. Custom-metric builder using existing data formulas. The table is **read-write** — bulk actions: 'Manage budgets, pause under performers, and scale winners directly from Cometly without switching ad platforms' (mutates the upstream ad platform via API)."
- **Filters:** attribution model, conversion window, date range, platform, ad account.
- **Data shown:** Spend, impressions, clicks, conversions, revenue (Cometly-attributed), ROAS, CPA, custom metrics built from formulas; Continuous LTV tracking per customer.
- **Interactions:** drill-down from campaign → ad set → individual ad → creative-level performance; AI Chat surfaces explicit dollar-level recommendations like "Scale Adset 1 from $20/day to $50/day" with backing metrics.
- **Why it works (from reviews/observations):** "Cometly AI is like hiring a world-class media buyer who never sleeps. I just ask it what's working, what's not, and what to scale and I get answers instantly." (Rob Andolina, Keywordme). "I am very impressed with the tracking accuracy. I would say it tracks about 90% of my orders." (Leo Roux, Petsmont). Negative: "The user interface was clunky and difficult to navigate." (gethookd.ai aggregator).
- **Source:** [`cometly.md`](../competitors/cometly.md); `cometly.com/features/ads-manager`

### Wicked Reports ([profile](../competitors/wicked-reports.md))
- **Surface:** **FunnelVision** + multiple ROI Attribution Reports (First Click ROI, Last Click ROI, Linear ROI, Full Impact ROI, New Lead ROI, ReEngaged Lead ROI).
- **Visualization:** TOF / MOF / BOF segmented funnel with **side-by-side comparison columns of Wicked-attributed ROAS vs Facebook-reported ROAS per campaign**; "drag-and-drop dashboard tiles," "color-coded dashboards" with "green for growth and red for issues" (per marketingtoolpro.com reviewer).
- **Layout (prose):** "Top: 'Netflix Style Easy Button' curated entry point + attribution-model menu. Main canvas: TOF / MOF / BOF segmentation with two adjacent ROAS columns per campaign — one Wicked-attributed, one Facebook-reported. Toggleable lookback windows; toggleable view-through impact slider. 5 Forces AI panel surfaces weekly per-campaign Scale / Chill / Kill verdicts with justification text 'you can defend.'"
- **Specific UI:** "TOF / MOF / BOF labels per click; 'Cold Traffic' tag for conversions occurring more than 7 days before sale; 'Customized Meta View-Through Conversion Impact' is a **user-adjustable on-the-fly slider** controlling how much view-through inflates ROAS/CAC. Three-state Scale / Chill / Kill pill per campaign in the 5 Forces AI surface, with nCAC threshold settings as user-defined inputs that drive the verdict."
- **Filters:** attribution model (one-click swap — "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time" per marketingtoolpro.com), lookback / lookforward window, view-through impact toggle.
- **Data shown:** ROAS (Wicked-attributed), ROAS (Facebook-reported), spend, conversions, CAC at each funnel stage, cold-traffic ROAS, nCAC vs threshold, recommended action with justification.
- **Interactions:** one-click model swap; slider to tune view-through impact; click customer record → vertical-timeline journey drilldown showing "the inbound marketing link clicks and timestamps behind every identified visitor."
- **Why it works (from reviews/observations):** "Wicked Reports allows us to optimize Facebook ads to those with the highest ROI and just not the cheapest lead." (Ralph Burns, Tier 11 CEO). "Color-coded dashboards make reviewing my performance simple and fast. Each section uses visual cues — like green for growth and red for issues." (marketingtoolpro.com reviewer).
- **Source:** [`wicked-reports.md`](../competitors/wicked-reports.md); `wickedreports.com/funnel-vision`

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Meta Ads Analytics + Google Ads Performance + Benchmarks; AI agent "Henri" overlays the entire surface.
- **Visualization:** campaign/ad-level table with benchmarked CTR/CPC/CVR vs Lebesgue's brand network; per-order touchpoint timeline view in the Le Pixel customer-journey screen.
- **Layout (prose):** "Top: metric-selection dropdowns, date-range picker. Left rail: nav including Business Report, Compare Metrics, Business Overview Table, LTV & Cohort, Advertising Audit, Meta Ads Analytics, Google Ads, Meta Creative Strategy, Meta Ads Forecast, Le Pixel attribution. Main canvas: line/bar charts; campaign-level performance views with benchmark overlay. Right rail (or floating): Henri AI chat panel."
- **Specific UI:** "Color-coded performance indicators (blue for improvements, red for declines)" — per Lebesgue's feature page. **Notable: blue, not green, for positive deltas** — unusual choice. Five attribution models exposed via Le Pixel: Shapley Value, Markov Chain, First-Click, Linear, Custom. Advertising Audit runs ~50 rule-based tests against connected Meta/Google/TikTok/GA4 accounts and flags mistakes.
- **Filters:** date range, channel filter (Google / Meta / TikTok), product, geography.
- **Data shown:** Spend, impressions, clicks, conversions, ROAS, attributed revenue per Le Pixel model, benchmarked CTR/CPC/CVR vs network of 20-25K brands.
- **Interactions:** Henri chat returns inline charts + Key Takeaways block + Recommendations block; switch attribution model on Le Pixel; export reports.
- **Why it works (from reviews/observations):** "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." (Fringe Sport, Shopify App Store, Oct 2025). Negative: "AI insights described as shallow — 'simply noting that CAC increased and conversion rate dropped off.'" (Capterra synthesis).
- **Source:** [`lebesgue.md`](../competitors/lebesgue.md)

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Campaign Deepdive Dashboard.
- **Visualization:** multi-platform comparison table spanning Google / Meta / TikTok / Bing / Pinterest with **side-by-side display of dual attribution models** (Last Click columns vs Platform Attributed columns), plus an exclusive **"KPI Scatter Chart"** — 2D scatter where two ratio metrics (CAC, CTR, ROAS) are plotted with outlier campaigns highlighted.
- **Layout (prose):** "Top: filters by campaign, region, product category. Left rail: nav with Performance Overview, Performance Trends, Campaign Deepdive, Product Table, Order Table, Customer Table, New vs Existing, LTV Analysis, Purchase Patterns. Main canvas: hierarchical Campaign → Ad Group → Ad table with dual-model columns — Last Click revenue/conversions next to Platform Attributed revenue/conversions for the same campaign. Bottom (or alongside): KPI Scatter Chart highlights which campaigns are dragging metrics down vs outperforming."
- **Specific UI:** "Side-by-side dual attribution: each campaign row shows Conjura's session-based last-click columns AND the platforms' own attributed columns simultaneously — Ad Spend, Impressions, CPM, Clicks, CTR, CPC, Customers Acquired, Last Click Conversions, Last Click Revenue, Last Click Conversion Rate, Last Click ROAS, Platform Attributed Conversions, Platform Attributed Revenue, Platform Conversion Rate, Platform ROAS. KPI Scatter Chart 2D plot with outlier identification: 'highlights which campaigns are dragging your metrics down and which are outperforming.'"
- **Filters:** date range, campaign, ad group, region, product category, attribution lens (last-click or platform).
- **Data shown:** all 15 metrics named verbatim above; SKU-level ad-spend attribution via the URL of the ad (works for Google Shopping, Performance Max, deep-linked ads; falls into "Ad Spend - No Product" bucket on generic homepages).
- **Interactions:** drill from chart point to specific campaign; hierarchy drill Campaign → Ad Group → Ad.
- **Why it works (from reviews/observations):** "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." (Island Living, Shopify App Store, Nov 2024). "Using Conjura we were able to discover 'holes' in our marketing strategies that were costing thousands." (ChefSupplies.ca, Shopify App Store, Jan 2024).
- **Source:** [`conjura.md`](../competitors/conjura.md); `conjura.com/campaign-deepdive-dashboard`; `help.conjura.com/en/articles/8867310-kpi-definitions-campaign-deepdive`

### Motion ([profile](../competitors/motion.md))
- **Surface:** Sidebar > Creative Analytics > {Top Performing Reports / Comparative Analysis / Launch Analysis / Ad Leaderboard / Winning Combinations}.
- **Visualization:** **sparkline-grid + thumbnail-leaderboard** — every metric view is anchored to the actual creative thumbnail (image/video) rather than a row of text; per-ad drill-down opens a Creative Insights modal with a video-CTR/retention line chart and a demographic breakdown bar chart.
- **Layout (prose):** "Top: sort/filter chips for date range, performance metric, naming-convention values, AI tags. Left rail: Creative Analytics + Inspo nav. Main canvas: visual-first stack/list of creative thumbnails with performance metrics rendered alongside; videos play in-line. Bottom: Snapshot button publishes the report as a public URL — frozen or live mode."
- **Specific UI:** "Click a thumbnail → Creative Insights modal opens with two stacked sections. Top section: video performance chart — TikTok shows second-by-second CTR; Meta shows watch-ratio decline. Bottom section: demographic breakdown — grouped/stacked bars by age cohort (13-17, 18-24, 25-34, etc.) split male/female/unknown. Color-coded reports and intuitive charts. AI Tags applied to each ad across 8 categories (Asset type, Visual format, Persona, Messaging angle, Seasonality, Offer type, Hook/Headline tactic, +1)."
- **Filters:** date range, performance metric, naming-convention variables, AI tags, comparison-dimension picker (funnel stages / products / messaging / creators / formats).
- **Data shown:** Spend, ROAS, CPA, CTR, hook rate, watch time, click rate, conversion rate, plus Motion's hook/watch/click/convert scores. GA4 + Northbeam attribution lenses available side-by-side at the Pro+ tier.
- **Interactions:** click thumbnail → Creative Insights modal; copy/paste export for slide decks; download GIF; publish Snapshot URL with unlimited view-only guests.
- **Why it works (from reviews/observations):** "Motion was the missing link in helping our media buyers and creatives see eye-to-eye on ad performance." (Cody Plofker, Jones Road Beauty). "Having the actual creative displayed next to our metrics has also opened up a whole new world of what we can do in terms of strategy and driving insights." (David Adesman, ATTN Agency).
- **Source:** [`motion.md`](../competitors/motion.md); `motionapp.com/solutions/ad-performance-dashboard`

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Marketing Attribution (paywalled to the $200/mo Enterprise tier — every lower tier sees ad spend and ROAS in the Profit Dashboard but not the attribution screen).
- **Visualization:** per-channel/per-ad table with a two-lens toggle — **Last-clicked Purchases** vs **Assisted Purchases** — and a profit-first column set ending in "Net profit" and "net profit margin" per row.
- **Layout (prose):** "Top: lens toggle (Last-clicked / Assisted) + date-range. Left rail: nav with Profit Dashboard, Product Analytics, Marketing Attribution, P&L Report, Customer Lifetime Value, Expense Tracking. Main canvas: per-channel/per-ad table with full funnel metric set. Bottom: drill from channel → campaign → ad implied (not screenshot-confirmed)."
- **Specific UI:** "Two attribution lens toggle: 'Last-clicked Purchases' and 'Assisted Purchases' (verbatim). Per-row columns: impressions, spending, clicks, click-through rate, add-to-cart events, cost per ATC, purchase count, purchase value, cost per purchase, conversion rate, revenue, total cost, **Net profit, net profit margin** — uniquely terminates at profit per ad, not just ROAS."
- **Filters:** date range, lens toggle (last-click / assisted).
- **Data shown:** every metric named verbatim above; net profit per ad/adset/campaign as the differentiator.
- **Interactions:** lens toggle; drill-down implied; not screenshot-confirmed.
- **Why it works (from reviews/observations):** "tells you exactly where you are loosing money and how to fix it" (Frome, Shopify App Store, Feb 2026). Negative: paywalling Marketing Attribution at $200/mo Enterprise is a recurring complaint structurally — every lower tier sees the gap.
- **Source:** [`trueprofit.md`](../competitors/trueprofit.md)

### Fospha ([profile](../competitors/fospha.md))
- **Surface:** Core dashboard (always-on Daily MMM with ad-level granularity on the Pro tier).
- **Visualization:** **horizontal bar chart of channel-attributed value changes with tooltips** (positive deltas in one color, negative in another, range -2.8k to +52.2k per their marketing illustration); **side-by-side "Last-click vs Fospha attribution" bar group** across paid social, Amazon, brand PPC, organic search; daily-granularity line charts; ad-level MER vs paid-ROAS target comparison specifically for Meta and TikTok.
- **Layout (prose):** "Top: KPI / time controls. Main canvas: four chart types stacked. Top: horizontal bar of channel-attributed value changes with tooltips. Middle: comparative channel-performance bars across Email, Referral, PMAX, Direct. Below: side-by-side attribution comparison — Last-click vs Fospha attribution — as a 4-bar group per channel showing the attribution gap. Bottom: daily-granularity line charts tracking revenue and spend trends, plus ad-level performance comparing MER vs paid-ROAS targets for Meta and TikTok."
- **Specific UI:** "'Last-click vs Fospha' framing as an explicit comparison chart — the load-bearing visual story. Tooltip on hover shows specific value; color-coded positive/negative deltas. Glass-box transparency: RMSE / R² model accuracy metrics surfaced inline." Lite tier ceiling is channel + campaign-type only; ad-level granularity requires Pro ($2,000/mo + variable %).
- **Filters:** KPI selector, market (1 / 3 / 5 by tier), date range; drill to ad-level on Pro+.
- **Data shown:** ROAS, MER, spend, impressions, views, clicks, attributed revenue/conversions per channel, last-click attribution as a parallel reference column.
- **Interactions:** drill-down channel → ad-level on Pro+; filter by KPI.
- **Why it works (from reviews/observations):** Negative: "Fospha only allows viewing at the ad set level, hindering effective campaign analysis." (G2 reviewer paraphrased). "When a model weights impressions heavily, channels that generate massive impression volumes receive disproportionate credit." (SegmentStream Fospha alternatives).
- **Source:** [`fospha.md`](../competitors/fospha.md); `fospha.com/platform/core`

### Atria ([profile](../competitors/atria.md))
- **Surface:** Radar (creative performance + analytics surface; ad-level data ingested from Meta/TikTok).
- **Visualization:** **letter-grade rubric (A/B/C/D/F)** per creative across multiple axes (ROAS, Hook / 3-sec view rate, Retention / hold rate, CTR, Conversion — sources disagree on exact axis set), plus triage classification badges (Winner / High Iteration Potential / Iteration Candidate).
- **Layout (prose):** "Top: ad-account connection + Radar settings. Main canvas: per-creative cards or rows with letter grade badges + triage classification + auto-tagged hooks/personas/themes/USPs. Right (or chat panel): Raya AI strategist with Quick Action chips ('Analyze my ad performance,' 'Clone competitor's top performing ads')."
- **Specific UI:** "Letter-grade rubric translates raw metrics into action much faster than numbers do. Triage classification: Winner / High Iteration Potential / Iteration Candidate as named badges. Auto-tags hooks, personas, themes, USPs as filter chips. Raya Quick-action chips for one-click strategic analyses."
- **Filters:** ad account, date range, tag filters, performance axis.
- **Data shown:** per-creative ROAS, CPA, CTR, AOV, Hook rate, Retention rate (numbers taken as Meta reports them — Atria has zero store-side data and no proprietary attribution model).
- **Interactions:** Quick Actions to clone competitor ads, iterate underperformers, mine reviews; outputs flow directly to next step (brief auto-fill → batch generation → 1-click Meta upload).
- **Why it works (from reviews/observations):** "The school-grade rubric (A/B/C/D) translates raw metrics into action much faster than numbers do." (per profile observation). Negative: "Atria has zero store-side data. ROAS, AOV, CPA all come from Meta as reported."
- **Source:** [`atria.md`](../competitors/atria.md)

## Visualization patterns observed (cross-cut)

Synthesizing the per-competitor sections by viz type for the ad-performance surface:

- **Hierarchical table (Campaign → Ad Set → Ad) with attribution-model dropdown:** 9 competitors (Triple Whale, Northbeam, Polar, ThoughtMetric, Cometly, Hyros, Wicked Reports, Conjura, TrueProfit) — overwhelmingly the dominant pattern; the differentiation is in the columns, not the table shape.
- **Side-by-side multi-source ROAS columns (the key differentiator):** Polar (Platform / GA4 / Polar Pixel — three-way), Conjura (Last Click / Platform Attributed — two-way), Northbeam Model Comparison Tool (any-two-of-seven + platform — three-way max), Wicked Reports FunnelVision (Wicked / Facebook-reported — two-way), Fospha (Last-click / Fospha — two-way as a 4-bar group), Triple Whale (Triple Pixel / platform-reported / first-click / last-click — up to four), AdBeacon Chrome Extension (AdBeacon-tracked / Meta-reported — two-way inside Ads Manager UI). **No competitor exposes 5 or 6 source columns simultaneously.** Polar's three-column is the maximum observed in a single live table.
- **Attribution-model selector exposed to user (in-table or top-of-page dropdown):** 11 of 14 — Triple Whale, Northbeam, Polar, ThoughtMetric, AdBeacon, Hyros, Cometly, Wicked Reports, Lebesgue (5 models via Le Pixel), Conjura (Last Click vs Platform), TrueProfit (Last-click vs Assisted). Northbeam exposes the most (7); Polar second-most (9-10).
- **Stoplight / verdict signals on rows:** Wicked Reports (Scale / Chill / Kill three-state pill with justification text), Atria (A/B/C/D letter grade + Winner/High Iteration Potential/Iteration Candidate triage badges), Northbeam (4-quadrant Product Analytics scatter — Yellow/Green/Red/Blue quadrant colors but at product/campaign/ad scale not ad-level grade), Wicked Reports / marketingtoolpro reviewer ("green for growth and red for issues" color coding), Lebesgue ("blue for improvements, red for declines" — unusual blue-positive choice). Triple Whale, Polar, ThoughtMetric, AdBeacon, Cometly, Hyros, Conjura do **not** ship a stoplight column on the ad-performance table — they leave the merchant to read raw numbers.
- **Thumbnail-grid (creative-level visualization):** Motion (visual-first leaderboard, every metric anchored to creative), Northbeam Creative Analytics (creative cards on red→green color gradient + multi-select up-to-6-ads comparison chart), Atria Radar, Triple Whale Creative Cockpit, ThoughtMetric Creative Performance, Polar Creative Studio (Meta-only), AdBeacon Creative Dashboard, Cometly creative-level drill, Hyros (no dedicated creative grid). Motion + Northbeam are the standouts on visual-first execution.
- **Drill-down: row click → modal / detail view:** universal — all 14 competitors implement some version (creative insights modal, lead journey timeline, customer journey, order-level touchpoint sequence). Motion's Creative Insights modal (video CTR over time + demographic breakdown) and Hyros's Lead Journey vertical timeline are the most concrete public descriptions.
- **Right-rail companion panel:** Northbeam (Profitability panel — gates empty until Day 90), Triple Whale (Moby Chat persistent floating button), Polar (Ask Polar AI chat surface), Cometly (AI Chat panel embedded), Atria (Raya chat). The pattern: the table is in the middle, an AI / contextual assistant is on the right.
- **Chrome-extension / in-Meta-Ads-Manager overlay:** AdBeacon (side-by-side AdBeacon-tracked vs Meta-reported columns injected at Ad Set + Ad level inside Meta's native UI), Hyros (overlay that adds Hyros-attributed revenue/ROAS columns into Meta Ads Manager). 2/14 — niche but distinctive.
- **Two-mode views (Basic + Pro):** Hyros explicitly ships Basic View / Pro View as a 2025-2026 redesign response to UI density complaints. Pattern is converging across competitors.

Color conventions recur unevenly. **Green-positive / red-negative is the majority** (Wicked Reports, AdBeacon's "navy + green" UI, Northbeam's red-to-green creative cards). Lebesgue uses **blue-positive / red-negative** — explicitly out of step. No competitor uses a fixed 6-color source palette akin to Nexstage's Real / Store / Facebook / Google / GSC / GA4 source-badge tokens.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Replacing the spreadsheet — one place for cross-platform paid spend**
- "Cometly has streamlined our ad reporting and eliminated numerous internal processes, saving my team valuable time. Beyond the efficiency gains, we've seen a significant boost in performance by leveraging Cometly's direct data feedback to ad platforms, bypassing the need for complex server-side tracking setups." — [`cometly.md`](../competitors/cometly.md), Aleric Heck, AdOutreach
- "Our team relies on Cometly to track and attribute various KPIs, including revenue, to the correct marketing sources. Cometly has enabled us to view our paid media spend in a single, comprehensive view." — [`cometly.md`](../competitors/cometly.md), Rexell Espinosa, Design Pickle
- "Brings everything from Shopify to Meta ads into one place...Would recommend for small marketing teams." — [`polar-analytics.md`](../competitors/polar-analytics.md), Susanne Kaufmann (Austria), Shopify App Store, June 2025
- "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps." — [`conjura.md`](../competitors/conjura.md), Island Living (Singapore), Shopify App Store, November 2024

**Theme: Don't trust the platforms — show me the disagreement**
- "TM has really helped us understand what's working and what's not; trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time." — [`thoughtmetric.md`](../competitors/thoughtmetric.md), WIDI CARE, Shopify App Store, December 2024
- "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey… they (customers) love that." — [`adbeacon.md`](../competitors/adbeacon.md), Agency testimonial
- "I am very impressed with the tracking accuracy. I would say it tracks about 90% of my orders, sometimes more, whereas Facebook tracks maybe 50% (but mostly it attributes sales to the wrong ad sets!)." — [`cometly.md`](../competitors/cometly.md), Leo Roux, Petsmont
- "When we use the iOS tracking, we often miss data and it leads us to turning off marketing campaigns. However, with Wicked Reports, the multiple attribution data solves the missing data and we're able to see there were sales made." — [`wicked-reports.md`](../competitors/wicked-reports.md), Michelle P, Agency Owner
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — [`triple-whale.md`](../competitors/triple-whale.md), Steve R., Capterra

**Theme: Side-by-side attribution lenses are load-bearing**
- "Northbeam's depth of attribution modeling is genuinely best-in-class" — [`northbeam.md`](../competitors/northbeam.md), Head West Guide review, 2026
- "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time." — [`wicked-reports.md`](../competitors/wicked-reports.md), marketingtoolpro.com reviewer
- "Their multi-touch attribution and incrementality testing have been especially valuable for us." — [`polar-analytics.md`](../competitors/polar-analytics.md), Chicory, Shopify App Store, September 2025
- "We found it hard to rely on FB or Google ad platforms to accurately measure ROI since we had longer sales cycles. Wicked Reports offered us more accurate ROI on our ad spend, and now we see the impact through the attribution models." — [`wicked-reports.md`](../competitors/wicked-reports.md), Mark D, smbguide.com

**Theme: Clarity at a glance through visual cues**
- "Color-coded dashboards make reviewing my performance simple and fast. Each section uses visual cues — like green for growth and red for issues." — [`wicked-reports.md`](../competitors/wicked-reports.md), marketingtoolpro.com reviewer
- "Motion was the missing link in helping our media buyers and creatives see eye-to-eye on ad performance." — [`motion.md`](../competitors/motion.md), Cody Plofker, Jones Road Beauty
- "Having the actual creative displayed next to our metrics has also opened up a whole new world of what we can do in terms of strategy and driving insights." — [`motion.md`](../competitors/motion.md), David Adesman, ATTN Agency

**Theme: Daily ritual / morning check-in**
- "I check in every day. Our CFO checks in. Our CEO checks in. It's the first look of the day for all of us." — [`northbeam.md`](../competitors/northbeam.md), Claire Yi, Grüns case study
- "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design." — [`triple-whale.md`](../competitors/triple-whale.md), paraphrased consensus across 2026 reviews

**Theme: Profit-first framing wins over revenue/ROAS-only**
- "I can see my contribution margin down to an SKU level, so I know where I should be paying attention." — [`conjura.md`](../competitors/conjura.md), Bell Hutley, Shopify App Store, March 2024
- "tells you exactly where you are loosing money and how to fix it" — [`trueprofit.md`](../competitors/trueprofit.md), Frome (Canada), Shopify App Store, February 2026

## What users hate about this feature

**Theme: Dense / clunky / overwhelming UI**
- "The app is okay, but it's full of bugs and the UI is terrible." — [`triple-whale.md`](../competitors/triple-whale.md), BioPower Pet, Shopify App Store, April 2026
- "Modifying reports or navigating menus is a cluster." — [`triple-whale.md`](../competitors/triple-whale.md), BioPower Pet, Shopify App Store, April 2026
- "complex to use, particularly for new users, and some of the visual design is still being refined" — [`northbeam.md`](../competitors/northbeam.md), Capterra aggregated, 2026
- "UI feels dense. Lots of power, but it took me a week to feel smooth." — [`hyros.md`](../competitors/hyros.md), Scout Analytics hands-on review, 2026
- "The user interface, while functional, lacks the visual polish seen in some competitors like Triple Whale." — [`polar-analytics.md`](../competitors/polar-analytics.md), Conjura comparison article, 2025
- "The user interface was clunky and difficult to navigate." — [`cometly.md`](../competitors/cometly.md), gethookd.ai aggregator
- "Outdated user interface design" / "interface can feel overwhelming for newcomers" — [`wicked-reports.md`](../competitors/wicked-reports.md), smbguide.com / marketingtoolpro.com, 2025

**Theme: Attribution discrepancy without explanation = distrust**
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — [`triple-whale.md`](../competitors/triple-whale.md), Derek Robinson (Brightleaf Organics), workflowautomation.net, March 2026
- "Hyros data sometimes does not match exactly with Facebook Ads Manager or other ad platforms, leading to confusion or distrust in the data." — [`hyros.md`](../competitors/hyros.md), Reddit r/FacebookAds user
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — [`triple-whale.md`](../competitors/triple-whale.md), AI Systems Commerce, 2026 review
- "Tracked only 50% of sales; worst tracking app performance tested" — [`thoughtmetric.md`](../competitors/thoughtmetric.md), Denis N., Capterra, December 2023
- "Doesn't work at all with funnels outside of Shopify like ClickFunnels or Funnelish. Tracking on funnels is reported to be around 50%." — [`cometly.md`](../competitors/cometly.md), Trustpilot reviewer summary
- "i have a discrepancy on the data, but after 10 days, there's no effort of giving me any clarity!" — [`thoughtmetric.md`](../competitors/thoughtmetric.md), hugbel, Shopify App Store, March 2026

**Theme: Granularity gating / paywalls on the actual answer**
- "Fospha only allows viewing at the ad set level, hindering effective campaign analysis." — [`fospha.md`](../competitors/fospha.md), G2 reviewer
- TrueProfit Marketing Attribution gated to the $200/mo Enterprise tier exclusively — "every lower tier sees ad spend and ROAS but not the attribution screen." — [`trueprofit.md`](../competitors/trueprofit.md), structural observation
- "Pricing is based on revenue tiers, which means costs increase as your store grows — can get expensive at scale." — [`triple-whale.md`](../competitors/triple-whale.md), Rachel Lopez, workflowautomation.net, January 2026

**Theme: UTM tagging / setup friction blocks the data getting there**
- "Wicked Reports is limited by the requirement of adding UTM codes to all advertising materials." — [`wicked-reports.md`](../competitors/wicked-reports.md), Cuspera aggregated review
- "Tracking codes aren't intuitive to locate initially" — [`thoughtmetric.md`](../competitors/thoughtmetric.md), Jen W., Capterra, December 2022
- "Setup nightmare – spent 6 months and 5+ setup calls, still no working tracking. Dropped $7,000 upfront for the year – they flat-out denied my refund." — [`hyros.md`](../competitors/hyros.md), Reddit user, 2025

**Theme: Single-account / no cross-blend = lying to multi-store / multi-account merchants**
- "Each Motion report is scoped to a single ad account" — [`motion.md`](../competitors/motion.md), Superads comparison
- "Users find it frustrating that you can only use one account per platform, as on Meta, they would like to be able to use two accounts at once." — [`motion.md`](../competitors/motion.md), G2 reviewer aggregation
- Polar Views OR-vs-AND gotcha: "Multiple Views combine with OR, not AND" — [`polar-analytics.md`](../competitors/polar-analytics.md), per their own help docs

**Theme: Slow data lag undermines the "what's working today" promise**
- "24-hour delay checking today's data" — [`thoughtmetric.md`](../competitors/thoughtmetric.md), Agustin G., Capterra, October 2021
- "Some data updates lagged by an hour or two" during peak periods. — [`wicked-reports.md`](../competitors/wicked-reports.md), marketingtoolpro.com, 2025
- "Reporting latency. Hands-on testing shows reports settling 10–20 min behind, vs. Cometly's near-minute updates." — [`hyros.md`](../competitors/hyros.md), profile observation
- "Switching between views and reports can be slow sometimes" — [`polar-analytics.md`](../competitors/polar-analytics.md), bloggle.app review, 2024

## Anti-patterns observed

- **Hidden source disagreement (a single "blended" number).** When the table shows one ROAS column with no indication that Meta-reported and store-attributed disagree, merchants chase ghosts. Triple Whale's older marketing positioned "Triple Pixel ROAS" as the single truth column; reviewers explicitly flag the resulting distrust ("Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding"). The fix shipped: a side-by-side Triple-Pixel vs Meta-reported column.
- **Aggregating spend across `level` (campaign + ad set + ad in one SUM).** None of the public profiles describe a competitor making this error explicitly, but the universal hierarchical-table pattern (always rolling **up** from ad → ad set → campaign, never SUMming all three together) is itself the implicit guardrail. Nexstage's CLAUDE.md rule "Never `SUM` across `ad_insights` levels" tracks the industry consensus.
- **Storing precomputed ROAS / CPA / CTR cells.** Triple Whale's Benchmarks dashboard implicitly stores these aggregates ("otherwise peer benchmarks are impossible at their scale" — per profile observation). For an SMB dashboard, storing ratios at the cell level breaks every time cost-config changes — the right move is the on-the-fly compute Nexstage already enforces.
- **Paywalling attribution behind the top tier.** TrueProfit gates Marketing Attribution at $200/mo Enterprise — every lower-tier user gets a Profit Dashboard that hides the very attribution comparison they need. Reviewers note this structurally; the lower-tier user doesn't get the answer to "which campaigns are working today."
- **One ad account per report.** Motion's single-account-scoped reports break for agencies and multi-store merchants — flagged across G2, Superads, Foreplay comparisons. The fix is workspace-scoped multi-account roll-ups (Northbeam Breakdowns Manager + Polar Views handle this; Motion does not).
- **Stoplight without a visible threshold.** Wicked Reports' Scale / Chill / Kill verdict is praised because it ships with **justification text "you can defend"** and user-tunable nCAC thresholds. Atria's letter grades are praised for similar reasons. A stoplight without "why this color and what's the threshold" creates more confusion than no signal at all — Lebesgue's terse "CAC increased and conversion rate dropped off" insights are flagged as too shallow.
- **View-based attribution counted without a toggle.** AdBeacon's deliberately click-only stance is sold as anti-pattern correction: they explicitly reject view-based attribution as "inflated metrics." Wicked Reports addresses the same gap with a user-adjustable view-through-impact slider — the user controls how much views inflate ROAS. Either rejecting views entirely or surfacing a slider is acceptable; silently mixing click + view credit into one ROAS column is the trap.
- **Frequent UI churn during product evolution.** Triple Whale's "UI changes frequently and documentation sometimes lags behind" (Derek Robinson / Noah Reed, workflowautomation.net) — the cost of moving fast is the merchant losing recall of where they clicked yesterday. Stable IA on the ad-performance surface specifically is a credible differentiator.
- **Sub-$50K stores don't have enough data for MTA / pixel models.** Northbeam ("won't really benefit" sub-$10M / sub-$50k/mo media spend) and Triple Whale Pixel ("won't really be useful" for sub-$10M brands) both fail at the SMB floor — the model needs traffic to converge. For SMB ads-performance UIs, last-click + platform-reported as the headline columns is honest; pixel-attributed as a third column is bonus, not the primary signal.

## Open questions / data gaps

- **Direct UI screenshots are sparse across the entire batch.** G2, Trustpilot, Capterra all 403 / anti-bot the WebFetch tool. KB.triplewhale.com 403's. Northbeam's product is sales-gated. Most "UI patterns" above are reconstructed from marketing-page prose, third-party reviewer descriptions, and customer testimonial quotes — not from inspected screens. For pixel-accurate teardown of any of the side-by-side column layouts, a paid evaluation account is required.
- **Exact column counts and tooltip behaviour are unverified** for Triple Whale's attribution dashboard, ThoughtMetric's Campaign Performance table, AdBeacon's Optimization Dashboard, Cometly's AI Ads Manager, and TrueProfit's Marketing Attribution screen — all five are paywalled and not in any public screenshot gallery.
- **Stoplight thresholds and color encoding** vary across competitors (green-positive standard, Lebesgue blue-positive, Northbeam four-quadrant Yellow/Green/Red/Blue) — but the actual hex values, dot sizes, and hover behaviour are not in any public source.
- **Multi-platform creative consolidation** is contested: Superads claims Motion does **not** ship cross-account / cross-platform consolidated reports (each report is single-ad-account-scoped); Motion's marketing implies the opposite. Without a hands-on, it's unclear whether the typical SMB merchant can see Meta + TikTok + Pinterest in one ad table at Motion.
- **Cohort × attribution overlay** (LTV-weighted ROAS at the ad-row level): Wicked Reports is the only competitor that explicitly re-prices ROAS as cohorts mature. Whether this shows up at the campaign-row level or only in a separate Cohort report is not screenshot-confirmed.
- **AI verdict integration** (Cometly's "Scale Adset 1 from $20/day to $50/day", Wicked's Scale/Chill/Kill, Atria's letter grades) — whether these are inline columns in the table or separate panels alongside is inconsistently described across sources.
- **Mobile parity for ad performance specifically.** Triple Whale ships a native iOS app but third-party reviews describe the ad-performance / attribution surface as desktop-first. Polar's mobile experience is acknowledged weak. ThoughtMetric, Northbeam, Wicked Reports, AdBeacon, Conjura, Hyros all have no mobile app or web-responsive only. The mobile ad-performance experience is universally a gap.

## Notes for Nexstage (observations only — NOT recommendations)

- **Side-by-side multi-source ROAS columns are the dominant differentiation pattern at the high end (Polar 3-way, Conjura 2-way, Wicked 2-way, Fospha 2-way, Triple Whale up-to-4-way, AdBeacon 2-way via Chrome extension, Northbeam Model Comparison 3-way).** No competitor exposes 5 or 6 sources simultaneously in one table. Nexstage's Real / Store / Facebook / Google / GSC / GA4 thesis would be the most-source-columns implementation observed if shipped at full breadth on the ad-performance table.
- **GSC is not on the ad-performance surface for any competitor.** Triple Whale, Polar, Northbeam, ThoughtMetric, AdBeacon, Hyros, Cometly, Wicked, Lebesgue, Conjura, Motion, TrueProfit, Fospha, Atria — none surface organic-search-query data in the ad-performance hierarchy. The "GSC as a source-column on the ad table" position is structurally novel.
- **Attribution-model dropdown is table-stakes.** 11 of 14 competitors expose it. Northbeam exposes 7 models; Polar 9-10; ThoughtMetric 5; Lebesgue 5 (via Le Pixel); Wicked 6; Conjura 2; TrueProfit 2; Cometly 5; Hyros 6+. The number is variable; the existence of the dropdown is universal.
- **Window selector is also table-stakes.** 7 / 14 / 30 / 60 / 90 days is the consensus default set (ThoughtMetric, Wicked, Cometly, Hyros, Polar). Northbeam adds 1d / 3d Click windows. Triple Whale advertises "unlimited lookback" on paid tiers.
- **Stoplight / verdict columns are NOT universal — they are a differentiator opportunity.** Only Wicked Reports (Scale / Chill / Kill three-state) and Atria (letter grade A/B/C/D) ship explicit per-row verdict signals. Triple Whale, Polar, ThoughtMetric, Northbeam, AdBeacon, Hyros, Cometly, Conjura, Lebesgue, Motion, TrueProfit all leave the merchant to read raw numbers. Both Wicked and Atria ship justification text alongside the verdict — the verdict alone without "why" is flagged as a Lebesgue-style anti-pattern.
- **The hierarchical Campaign → Ad Set → Ad table is universal.** Don't reinvent the row shape; differentiate the columns.
- **Profit-first framing (TrueProfit, Conjura) wins over revenue/ROAS-only at the SMB end.** TrueProfit's Marketing Attribution table terminates each row in "Net profit" + "net profit margin" — uniquely. Conjura's headline is Contribution Profit not ROAS. For an SMB-targeted product, profit-per-ad is harder to compute than ROAS-per-ad but reviewers clearly prefer it.
- **In-table read-write actions (Cometly) are the agentic frontier.** Cometly's table mutates the upstream ad platform via API — pause underperformers, scale winners, change budgets without leaving the dashboard. AdBeacon also offers in-platform optimization actions. Triple Whale shipped direct ad-platform controls in April 2026. Nexstage does not have this on the roadmap; it's where the high end is going.
- **Chrome extension / in-Meta-Ads-Manager overlay (AdBeacon, Hyros) is a niche-but-effective pattern** for media buyers who live in Meta's UI all day. Not on Nexstage roadmap; worth flagging as a possible workflow primitive.
- **Northbeam's right-rail Profitability panel that gates empty until Day 90** is a precedent for the "Recomputing…" banner state — gating-by-data-readiness rather than hiding. Could inform how Nexstage handles cost-config retroactive recalc UX.
- **Northbeam's Breakdowns Manager (user-defined channel groupings: Paid Prospecting / Performance Max / Branded Search / Retargeting / Online Store vs Amazon)** maps directly onto Nexstage's `ChannelMappingsSeeder.php` taxonomy. Their default groupings are validated default seeds.
- **Inline tooltips on table headers (Northbeam 3.0)** are explicitly called out by reviewers — direct analog to a Nexstage metric-definition affordance.
- **Sub-$50k/mo ad-spend merchants likely don't get reliable MTA from any pixel-based competitor.** The honest column for SMB is last-click + platform-reported; pixel-attributed is bonus. Triple Whale Pixel admits sub-$10M brands "won't really be useful." Worth holding the line that Nexstage's primary ad-performance lens for SMB is store-truth + platform-reported, not pixel-attribution-as-source-of-truth.
- **Color tokens are inconsistent across competitors.** Lebesgue uses blue-positive; AdBeacon "navy + green"; Wicked / Northbeam use red→green creative grids. There's no industry-standard 6-source palette — Nexstage's `--color-source-*` tokens are creating a new convention rather than fighting an existing one.
- **The Wicked-style "Customized Meta View-Through Conversion Impact slider"** (user controls how much views inflate ROAS) is structurally distinct and respects user judgement instead of imposing a click-only or click-plus-view default. Worth noting if Nexstage ever models view-through credit.
- **UTM / channel-mapping admin is consistently friction-heavy.** Wicked, ThoughtMetric, Hyros all flag UTM tagging as a setup blocker. Nexstage's `TagGenerator.tsx` + `ChannelMappingsSeeder.php` sync rule (per CLAUDE.md gotchas) is addressing the right problem.
- **Pricing model: tracked-revenue or ad-spend bands trigger backlash universally** (Triple Whale GMV bands, Hyros tracked-revenue bands, Cometly ad-spend bands, Motion ad-spend bands). AdBeacon's "Annual Scaling fixed pricing forever" and ThoughtMetric's "every feature in every tier" pageview-based soft-cap are the alternative messages. Relevant to pricing-strategy research, not specific to ad-performance UI.
