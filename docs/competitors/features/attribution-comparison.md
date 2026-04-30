---
name: Attribution comparison
slug: attribution-comparison
purpose: Help merchants reconcile conflicting "credit" numbers across attribution lenses (last-click, MTA, MMM, survey, platform-reported, first-party pixel) by showing them side-by-side rather than picking one truth.
nexstage_pages: dashboard, performance, ads, seo
researched_on: 2026-04-28
competitors_covered: northbeam, triple-whale, rockerbox, thoughtmetric, segmentstream, polar-analytics, hyros, conjura, fairing, adbeacon, trueprofit, lebesgue, ga4, daasity, wicked-reports
sources:
  - ../competitors/northbeam.md
  - ../competitors/triple-whale.md
  - ../competitors/rockerbox.md
  - ../competitors/thoughtmetric.md
  - ../competitors/segmentstream.md
  - ../competitors/polar-analytics.md
  - ../competitors/hyros.md
  - ../competitors/conjura.md
  - ../competitors/fairing.md
  - ../competitors/adbeacon.md
  - ../competitors/trueprofit.md
  - ../competitors/lebesgue.md
  - ../competitors/ga4.md
  - ../competitors/daasity.md
  - ../competitors/wicked-reports.md
---

## What is this feature

Attribution comparison answers the merchant's recurring question: "Why does Meta say 3.2 ROAS, my Shopify report say 1.8, GA4 say 2.4, and my post-purchase survey say 'Instagram' is half the funnel — which number do I optimize against?" Merchants don't lack data; every paid channel, every store, every survey tool, and every analytics suite already publishes an opinion. The feature is the synthesis: rendering multiple attribution lenses on the same metric, in the same view, at the same time, so the disagreement itself becomes the information rather than the noise.

For SMB Shopify and Woo merchants this matters because the wrong "single number of truth" picks a winner for them by default — and that default is almost always "platform-reported" (which over-counts) or "GA4 last-click" (which under-counts). A side-by-side view forces the operator to acknowledge the gap, decide which lens applies to the decision they're about to make (cold-traffic prospecting? first-order CAC? subscription rebill?), and stop chasing optimizer-driven growth that isn't real. The feature exists in two shapes across competitors: (1) **lens toggles** that swap one report's numbers between models, and (2) **side-by-side columns/rows/widgets** that show two or more lenses at once.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `orders`, `orders.line_items`, `orders.discount_codes`, `orders.note_attributes` (UTM landing params), `orders.referring_site`, `orders.financial_status`, `orders.subscription_id` for repeat/subscription splits.
- **Source: Meta Ads API** — `campaigns.spend`, `campaigns.impressions`, `campaigns.clicks`, `campaigns.actions[purchase].value` (platform-reported revenue), `ads.creative_id`, `attribution_setting` (1d-click / 7d-click / 1d-view).
- **Source: Google Ads API** — `campaign.metrics.cost_micros`, `campaign.metrics.conversions`, `campaign.metrics.conversions_value` (platform-reported revenue at chosen Google attribution model).
- **Source: GA4 (Data API)** — `sessions`, `conversions`, `purchaseRevenue`, `defaultChannelGroup`, attribution model (DDA / Paid+organic last click / Google paid last click).
- **Source: Google Search Console** — `clicks`, `impressions`, `query`, `landing_page` (used as a comparison column for organic-search credit).
- **Source: First-party pixel / server-side tracking** — `click_id`, `session_id`, `user_id`, `event_timestamp`, `utm_source`, `utm_medium`, `utm_campaign`, full touchpoint sequence per converting customer.
- **Source: Post-purchase survey (Fairing / KnoCommerce / native)** — `response.question_id`, `response.answer_value`, `response.answer_text`, `response.order_id` for self-reported "How did you hear about us?" attribution.
- **Source: Discount codes** — `discount_code.code`, `discount_code.campaign_tag` (used as a parallel attribution dimension for podcast/influencer credit).
- **Source: Computed** — `attributed_revenue = revenue × attribution_weight` per (model, channel, period); `delta_to_real = source_attributed_revenue − store_real_revenue`.
- **Source: User-input** — channel mappings (UTM → channel), attribution-window defaults, lens-priority for waterfall/Custom Attribution.

## Data outputs (what's typically displayed)

- **KPI: Attributed revenue** — `SUM(orders.attributed_revenue) by source × period`, USD; primary axis of comparison.
- **KPI: Attributed conversions** — count of orders credited to a source under a given model.
- **KPI: ROAS (per lens)** — `attributed_revenue / spend`, computed at render-time per (lens, channel, period). Never stored.
- **KPI: CAC / nCAC (per lens)** — `spend / attributed_new_customers`, render-time.
- **KPI: Discrepancy / delta** — `(lens_A − lens_B) / lens_B`, render-time. Often surfaced as a "Reporting Gap" widget (Hyros) or a `% change` column (GA4 Model Comparison).
- **Dimension: Attribution model** — string, ~3-9 distinct values (DDA, First Click, Last Click, Last Non-Direct, Linear, Time Decay, U-Shaped, Position-Based, Survey, Discount-Code, Vendor-Reported, Visit Scoring, Clicks-Only, Clicks + Modeled Views, Clicks + Deterministic Views, etc.).
- **Dimension: Source / lens** — for source-of-truth comparisons (Platform-Reported, Pixel/First-Party, Survey, Store, GA4, GSC).
- **Dimension: Channel** — string, ~6-15 values (Direct, Email, Paid Social, Paid Search, Organic Search, Affiliate, Influencer, Podcast, Display, Retargeting, etc.).
- **Dimension: Funnel stage** — Top / Middle / Bottom (TOF/MOF/BOF) when comparing first-touch vs last-touch interpretations.
- **Breakdown: model × channel × period** — primary table or matrix.
- **Breakdown: model_A vs model_B side-by-side** — paired columns with delta.
- **Slice: per campaign / per ad / per creative / per customer-journey** — drill from rolled-up channel down to single touchpoint timeline.

## How competitors implement this

### Northbeam ([profile](../competitors/northbeam.md))

- **Surface:** Top-right hamburger ("☰") menu next to the maintenance alerts icon → "Model Comparison." Separate from the global Attribution Home, where a single attribution-model dropdown re-renders the whole page.
- **Visualization:** Side-by-side comparison columns (one per attribution model), with per-row Attributed Revenue and Transactions. Plus a global filter strip on every dashboard with: Attribution Model selector (7 values), Window selector (1d Click → 90d Click + LTV), Accounting Mode toggle (Cash Snapshot vs Accrual), Granularity, Time Period, comparison mode.
- **Layout (prose):** "Top: global filter strip applied to all dashboards. Left rail: standard sidebar nav. Main canvas: two model columns rendered side-by-side. Bottom: 'export to CSV and overlay platform data (e.g., Google Ads)' as a third column for reconciliation against Meta/Google self-reporting." The Attribution Home itself uses a single model selector that re-renders the page; Model Comparison is the explicit "see two models at once" surface.
- **Specific UI:** Seven attribution models exposed simultaneously: First Touch, Last Touch, Last Non-Direct Touch, Linear, Clicks-Only, Clicks + Modeled Views, Clicks + Deterministic Views. Two accounting modes (Accrual / Cash Snapshot). Inline tooltips on table headers (Touchpoints, Revenue, ROAS, CAC, Visitors, Customers). Day 30/60/90 progressive feature unlock — Profitability right-rail panel literally stays empty until Day 90.
- **Filters:** Date, comparison mode, attribution model, window, accounting mode, granularity, breakdown (Platform / Category / Targeting / Revenue Source).
- **Data shown:** Per model: Attributed Rev (windowed), Transactions, Spend, ROAS, CAC, MER, ECR, % New Visits — each with Blended / New / Returning variant.
- **Interactions:** Compare any two of 7 models; export to CSV; overlay platform-reported numbers as a third column.
- **Why it works (from reviews/observations):** "the best attribution platform I've ever used" (G2 reviewer aggregate); "Northbeam's depth of attribution modeling is genuinely best-in-class" (Head West Guide, 2026). Day 30/60/90 progressive unlock is honest about ML calibration time.
- **Source:** ../competitors/northbeam.md; https://docs.northbeam.io/docs/what-is-northbeam-model-comparison-tool

### Triple Whale ([profile](../competitors/triple-whale.md))

- **Surface:** Top-level "Pixel / Attribution" sidebar nav; also surfaced inside Compass (Pro tier) for MMM + MTA + Incrementality reconciliation.
- **Visualization:** Table with side-by-side columns — Triple-Pixel-attributed revenue, platform-reported revenue, first-click split, last-click split, "Total Impact" model. Compass adds a Measurement Agent that reconciles MMM vs MTA vs platform-reported.
- **Layout (prose):** "Top: date-range + store-switcher + on-demand refresh button. Left rail: standard sidebar. Main canvas: channel-breakdown table with paired Pixel-vs-Platform columns; attribution-model selector dropdown sits above the table. Bottom: drill-down into campaign/ad creative."
- **Specific UI:** Attribution-model dropdown that swaps the entire channel revenue grid. "Total Impact" model selectable alongside first/last-click as an attribution lens (Advanced+). On-demand refresh button (April 2026) shows real-time refresh status. Pixel Events Manager (April 2026) adds a real-time event log with hourly breakdown + device/browser segmentation, so users can audit the pixel itself.
- **Filters:** Date, attribution model, channel grouping, store, segment.
- **Data shown:** Spend, Pixel revenue, Platform revenue, ROAS (per lens), nc-ROAS, MER, AOV, conversions, attribution-model-specific deltas.
- **Interactions:** Switch attribution lens reflows the channel revenue numbers; drill into ad creative; push attributed conversions back to Meta via Sonar Optimize / Attribution Sync for Meta.
- **Why it works:** "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics" (Steve R., Capterra, 2024). "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports" (Steve R., Capterra).
- **Source:** ../competitors/triple-whale.md

### Rockerbox ([profile](../competitors/rockerbox.md))

- **Surface:** Channels tab → three explicitly-named sub-views: "Platform-Reported Performance" / "Rockerbox De-duplicated View" / "Conversion Comparison." Plus Attribution tab → Cross-Channel Attribution Report with a 5-way model toggle.
- **Visualization:** Three named sub-tabs that act as parallel views of the same data, plus a five-way attribution-model dropdown on a single screen.
- **Layout (prose):** "Top: filter strip with attribution-type selector + new-vs-repeat segmentation + date range. Left rail: top-nav with Channels / Attribution / Funnel / Experiments / MMM. Main canvas: tabbed view between Platform-Reported / Rockerbox / Conversion Comparison; underneath, the attribution-model toggle exposes 5 models (modeled multi-touch / even weight / last touch / first touch / full credit). Bottom: drill from channel → campaign → ad."
- **Specific UI:** Conversion Comparison view explicitly side-by-sides the two methodologies. Funnel Position view has dual normalization modes — "Channel Mix by Funnel Stage" (vertical 100% per stage) and "Channel Role Distribution" (horizontal 100% per channel). MMM Model Comparison is its own surface that picks "active" model for downstream views.
- **Filters:** Date, time-period comparison, customer attribute (new vs repeat), attribution model, channel, funnel stage.
- **Data shown:** Spend, impressions, clicks, conversions (platform-reported), conversions (Rockerbox-modeled), CPA, ROAS, attribution-model breakdowns.
- **Interactions:** Switch view (Platform / De-duplicated / Comparison); toggle attribution model in-place; column customization; drill from channel down to ad level.
- **Why it works:** "Stop tracking duplicate conversions, get a single source of truth for conversion counts across marketing channels" (Rockerbox marketing copy). "Annihilates everything else in the same price range" (G2 reviewer cited via search summaries).
- **Source:** ../competitors/rockerbox.md; https://www.rockerbox.com/blog/rockerbox-reimagined-a-deep-dive-on-the-new-and-updated-functionality

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))

- **Surface:** Marketing Attribution dashboard (Product > Analytics > Attribution).
- **Visualization:** Channel-level table with attribution-model dropdown + attribution-window selector (7 / 14 / 30 / 60 / 90 days).
- **Layout (prose):** "Top: attribution-model dropdown + lookback-window selector. Left rail: standard sidebar. Main canvas: channel rows (Meta Ads, TikTok, Pinterest, Google Ads, Bing, organic social, email/SMS, podcasts, influencers, affiliates, UTM-based custom channels) with spend / ROAS / MER / Sales / Orders columns. Below: drill from channel to campaign/ad-set/ad."
- **Specific UI:** Five attribution models with explicit user-facing definitions: Multi-Touch (default, proprietary data-driven), First Touch, Last Touch, Position Based (40/40/20 split), Linear Paid (paid-only equal credit). Post-purchase survey signal feeds the default Multi-Touch model directly — survey responses are inputs, not a separate report.
- **Filters:** Attribution model, lookback window, channel, date.
- **Data shown:** Spend, ROAS, MER, attributed Sales, attributed Orders per channel per model.
- **Interactions:** Switch attribution model, change lookback window, drill from channel into campaign/ad-set/ad.
- **Why it works:** "trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time. That's why ThoughtMetric is a must!" (WIDI CARE, Shopify App Store, December 2024). "great to have a few different sources of truth in this world" (Woolly Clothing Co, Shopify App Store, August 2025).
- **Source:** ../competitors/thoughtmetric.md

### SegmentStream ([profile](../competitors/segmentstream.md))

- **Surface:** Top nav > Attribution (the analysis surface); plus measurement-engine illustrations showing maturation curves and CRM funnel attribution.
- **Visualization:** Configurable metrics × dimensions table with 4-model dropdown, plus a maturation projection line chart ("Observed vs Projected cumulative conversions over 42 days, with confidence metrics") and a maturation table (Last 7d: 53% → +1 week: 78% → +2 weeks: 95% → +3 weeks: 99%).
- **Layout (prose):** "Top: date filter, attribution-model selector, dimension picker. Left rail: top-nav with Overview / Project Configuration / Dashboards / Attribution / Optimization / Geo Tests / MCP. Main canvas: configurable metric × dimension table with model toggle. Bottom: maturation curve + maturation table communicating how mature today's data is."
- **Specific UI:** Four named transparent models — First-Touch, Last Paid Click, Last Paid Non-Brand Click, ML Visit Scoring. Visit Scoring is documented as session-level behavioral signals (navigation depth, key events, micro-conversions, scroll behavior) with per-session conversion-probability badges (e.g., "(72%)"). Geo-holdout test results render per-row as `<Channel> — <Treatment> | <Significance pill> · [CI low, CI high] | <point estimate>` (e.g., "Google — Brand Search | Significant · [+22%, +49%] | +35%").
- **Filters:** Date, attribution model, dimension, custom dimensions, AND/OR boolean composition.
- **Data shown:** Spend, clicks, impressions, conversions, attributed revenue, ROAS — broken down by selected attribution model. Maturation table shows percent-mature for late-conversion windows.
- **Interactions:** Select model, pick dimensions, apply filters, export. Configure geo tests via MCP-connected AI tools.
- **Why it works:** "A one-of-a-kind attribution, optimisation and budget allocation tool" (G2 reviewer cited in segmentstream.com); "The best attribution platform we've tried so far" (G2 reviewer).
- **Source:** ../competitors/segmentstream.md

### Polar Analytics ([profile](../competitors/polar-analytics.md))

- **Surface:** Acquisition / Attribution surface.
- **Visualization:** **Three-column side-by-side comparison: Platform-reported / GA4 / Polar Pixel** for the same window, with a 9-10 attribution-model dropdown.
- **Layout (prose):** "Top: attribution-model picker (9-10 values) + date range. Left rail: standard Polar sidebar. Main canvas: per-channel rows with three columns — Platform reported, GA4, Polar Pixel — for the same metric. Bottom: drill from channel → campaign → ad → order → customer journey."
- **Specific UI:** 9-10 attribution models in a single dropdown: First Click, Last Click, Linear, U-Shaped, Time Decay, Paid Linear, Full Paid Overlap, Full Paid Overlap + Facebook Views, Full Impact (data-driven). Drill-down to **customer level and order level**: clicking an order shows the multi-touchpoint customer journey that led to it. Marketing claim: "30-40% more accurate attribution data" than Triple Whale's modeled pixel.
- **Filters:** Attribution model, date, channel, store, region (via Views).
- **Data shown:** Spend, attributed revenue, ROAS, CAC, conversions per model — with platform/GA4/Polar columns.
- **Interactions:** Switch attribution model from the dropdown; the same KPI block re-renders. Drill from channel → campaign → ad → order → customer journey.
- **Why it works:** "Their multi-touch attribution and incrementality testing have been especially valuable for us" (Chicory, Shopify App Store, September 2025). "compare and contrast performance being reported by advertising platforms, GA4 and Polar" (swankyagency.com walkthrough).
- **Source:** ../competitors/polar-analytics.md; https://swankyagency.com/polar-analytics-shopify-data-analysis/

### Hyros ([profile](../competitors/hyros.md))

- **Surface:** Dashboard ("Quick Reports") — top-level sidebar entry. The distinctive widget is **Reporting Gap**.
- **Visualization:** Dashboard widget grid with a dedicated "Reporting Gap" widget surfacing the delta between Hyros-attributed sales and ad-platform-reported sales as a top-level KPI; reports themselves use multi-pivot drill with attribution-model dropdown.
- **Layout (prose):** "Top: date-range filter (default last 7 days). Left rail: sidebar with Dashboard / Reports / Leads / Call Tracking / AIR / Settings. Main canvas: drag-and-drop widget grid with **Live Stream**, **Hyros Insights**, **Recent Reports**, and **Reporting Gap** widgets. Bottom: per-row drill into Lead Journey / Deep Mode showing the full touchpoint timeline for a single visitor."
- **Specific UI:** Two view modes — **Basic View** ("clean, simple stats at a glance") and **Pro View** ("full power-user mode, custom widgets"). Reporting Gap widget surfaces a single delta number (Hyros vs ad-platform). Reports support model toggle (Last click / First click / Linear / Time decay / Scientific Mode / Custom-weighted). Chrome extension overlay injects Hyros-attributed revenue/ROAS columns directly into native Meta Ads Manager.
- **Filters:** Attribution model, date, campaign / ad set / ad, source, segment.
- **Data shown:** Hyros-attributed sales vs platform-reported sales (with delta), ROAS, CPA, leads, calls, sales by source/campaign/ad. Marketing claim: "29-33% more sales captured" than native Ads Manager.
- **Interactions:** Drag-and-drop widget arrangement; per-widget custom-metric selection; drill-through from any widget into deep reports; manual revenue assignment per lead.
- **Why it works:** "no tool matches HYROS data quality" (Pummys, Trustpilot via smbguide.com). "tracking and metrics are unbelievable. Its so awesome to have accurate data from all our paid and organic sources in one pane of glass dashboard" (Abd Ghazzawi, Trustpilot).
- **Source:** ../competitors/hyros.md

### Conjura ([profile](../competitors/conjura.md))

- **Surface:** Campaign Deepdive dashboard (marketing analytics section).
- **Visualization:** Side-by-side dual-attribution table with paired columns — **Last Click** (Conjura's session-based attribution) vs **Platform Attributed** (passes through what Meta/Google/TikTok report). Plus a **KPI Scatter Chart** plotting two ratio metrics (CAC, CTR, ROAS) on a 2D scatter to identify outliers.
- **Layout (prose):** "Top: filter strip (campaign, region, product category) + date. Left rail: standard nav with Performance Overview / Trends / Campaign Deepdive / Product Table / etc. Main canvas: hierarchical drill (Campaign → Ad Group → Ad) table with Last Click columns adjacent to Platform Attributed columns; KPI Scatter Chart sits alongside. Bottom: SKU-level ad-spend attribution (via ad URL parsing)."
- **Specific UI:** Table columns enumerated verbatim: Ad Spend, Impressions, CPM, Clicks, CTR, CPC, Customers Acquired, Last Click Conversions, Last Click Revenue, Last Click Conversion Rate, Last Click ROAS, Platform Attributed Conversions, Platform Attributed Revenue, Platform Conversion Rate, Platform ROAS. SKU-level ad-spend attribution is done by parsing the ad's destination URL (works for Google Shopping, Performance Max, deep-linked Google ads, FB ads to product pages). Ads landing on generic homepage fall into "Ad Spend - No Product" bucket.
- **Filters:** Campaign, region, product category, date, channel.
- **Data shown:** All columns above per campaign / ad group / ad row.
- **Interactions:** Filter by campaign, region, product category. Drill from chart point to specific campaign.
- **Why it works:** "I can see my contribution margin down to an SKU level, so I know where I should be paying attention" (Bell Hutley, Shopify App Store, March 2024). "It gives you on-demand insights in a visual format, which would normally take at least 2-3 different source apps" (Island Living, Shopify App Store, November 2024).
- **Source:** ../competitors/conjura.md; https://help.conjura.com/en/articles/8867310-kpi-definitions-campaign-deepdive

### Fairing ([profile](../competitors/fairing.md))

- **Surface:** Analytics tab > Attribution → "Last Click and UTM Report" + "Multi-Tier Attribution."
- **Visualization:** Implied side-by-side table — survey-response source vs UTM-derived source for the same orders. Multi-tier attribution adds parent/child source rollup. The Daasity "Attribution Deep Dive" knowledge-base article confirms the side-by-side pattern when Fairing data is consumed downstream.
- **Layout (prose):** "Top: date range + question selector. Left rail: Question Stream / Responses / Analytics / Integrations. Main canvas: per-order table or rollup with two columns — survey-attributed source and UTM/last-click source. Below: LTV-by-response cohort comparison."
- **Specific UI:** Time Series view shows top-5 responses charted over time with day/week/month aggregation and a last-refresh timestamp ("automatically refreshes every 30 minutes"). Comparison Analytics produces cross-tab tables (Customer Type, or Question + Response). NPS view renders three-bucket count strip + single big-number NPS score. Bulk Recategorization tooling buckets free-text "Other" responses into canonical sources.
- **Filters:** Date range, question, response, customer type, aggregation level.
- **Data shown:** Response count, percent of total, LTV by response cohort, NPS score, multi-tier attribution rollup, survey-vs-UTM delta (per Daasity dashboard implementation).
- **Interactions:** Add comparison; pivot one question against another; export CSV; bulk recategorize "Other"; AI Insights weekly Monday-morning email summary.
- **Why it works:** "while their pixel data showed a positive ROAS of 2.3x for their podcast campaign, survey data revealed an additional 31% increase in conversions that weren't captured by tracking technology, allowing them to increase their podcast budget by 40%" (Fairing case-study marketing copy). "Pixels miss the messy, human journey" (Fairing blog).
- **Source:** ../competitors/fairing.md

### AdBeacon ([profile](../competitors/adbeacon.md))

- **Surface:** **Chrome extension overlay inside Meta Ads Manager** — the primary side-by-side surface — plus an in-app Optimization Dashboard with a four-model toggle.
- **Visualization:** Browser-overlay panel injecting columns into Meta's native Ad Set + Ad views; in-platform attribution-model toggle (First Click / Last Click / Linear / Full Impact).
- **Layout (prose):** "Top of overlay: connected-account dropdown for agencies. Body: side-by-side columns — Meta-reported metrics adjacent to AdBeacon-tracked metrics — at Ad Set and Ad level (no Campaign level). Below: customizable metric chooser; attribution-model toggle inline."
- **Specific UI:** Side-by-side columns rendered inside facebook.com/adsmanager rather than in a separate dashboard tab — closes the "context switch" gap entirely. Account-switcher dropdown for agencies; customizable metric chooser; in-overlay model toggle. Tether (their server-side product) captures click ID + Shopify order context and sends back to Meta CAPI.
- **Filters:** Ad set / ad scope, attribution model, metric chooser, account.
- **Data shown:** Tracked purchases, attribution-based revenue, orders, ROAS, custom KPIs — paired with Meta's native columns.
- **Interactions:** Switch attribution model in real-time inside Ads Manager; toggle between connected client accounts.
- **Why it works:** "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey… they (customers) love that" (Agency testimonial, AdBeacon marketing). "We had problems with META and Google privacy-oriented blocks when tracking our audience…AdBeacon custom pixel tagging removed all the complications."
- **Source:** ../competitors/adbeacon.md; https://www.adbeacon.com/the-adbeacon-chrome-extension-independent-attribution-inside-meta-ads-manager/

### TrueProfit ([profile](../competitors/trueprofit.md))

- **Surface:** Marketing Attribution screen (gated to Enterprise tier $200/mo only).
- **Visualization:** Two attribution lens **toggle** — "Last-clicked Purchases" vs "Assisted Purchases" — over a per-channel/per-ad table.
- **Layout (prose):** "Top: lens toggle (Last-clicked / Assisted). Left rail: standard nav. Main canvas: per-channel/per-ad row with verbatim columns: impressions, spending, clicks, click-through rate, add-to-cart events, cost per ATC, purchase count, purchase value, cost per purchase, conversion rate, revenue, total cost, Net profit, net profit margin."
- **Specific UI:** Two-mode binary toggle (no third option), with Net profit and Net profit margin as the bottom-line columns. Server-side tracking underpins the numbers. Attribution screen is paywalled at the highest ($200/mo) tier.
- **Filters:** Lens toggle, date, channel.
- **Data shown:** Verbatim columns above; new vs returning split implicit via blended ROAS at dashboard level.
- **Interactions:** Toggle between last-click and assisted views. Drill-down (channel → campaign → ad) is the implied IA but not screenshot-confirmed.
- **Why it works:** Public reviews cite the simple toggle as approachable for SMB. Praise vector is auto-sync of cost data, not the attribution UI itself.
- **Source:** ../competitors/trueprofit.md

### Lebesgue ([profile](../competitors/lebesgue.md))

- **Surface:** Le Pixel attribution add-on; per-order customer-journey view + attribution-model selector.
- **Visualization:** Five user-selectable attribution models exposed in a per-order timeline view (page views → add to cart → conversion).
- **Layout (prose):** "Top: attribution-model dropdown (Shapley Value / Markov Chain / First-Click / Linear / Custom). Left rail: standard nav. Main canvas: per-order touchpoint timeline with channel/campaign/ad attribution per touchpoint, first-time-vs-repeat flag, subscription flag. Plus channel/campaign/ad-level rollup tables."
- **Specific UI:** **Five attribution models** — Shapley Value, Markov Chain, First-Click, Linear, Custom — with explicit user-facing model switcher. AI cross-device matching surfaces complete customer journeys. Color convention is unusual: **blue** for positive deltas / improvements, red for declines (most competitors use green for positive).
- **Filters:** Attribution model, date, channel, campaign, customer type.
- **Data shown:** Page views, add-to-cart, conversion value, channel/campaign/ad attribution per touchpoint, first-time vs repeat split, subscription flag.
- **Interactions:** Switch attribution model from dropdown; drill into per-order journey; first-time vs repeat split.
- **Why it works:** "Solid Attribution App for significantly less money than the comp set" (FluidStance, Shopify App Store, November 2025). "Better than TripleWhale already and that's on the free version!" (Robin T., Capterra, September 2025). "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible" (Fringe Sport, Shopify App Store, October 2025).
- **Source:** ../competitors/lebesgue.md

### GA4 ([profile](../competitors/ga4.md))

- **Surface:** Advertising > Attribution > **Model comparison** report.
- **Visualization:** Side-by-side **A vs B model** table — two model dropdowns at top, rows = Default channel group (or Source/Medium/Campaign), three metric columns per side: Conversions (model A), Conversions (model B), `% change` column with color-coded delta cells (green ▲ for >0%, red ▼ for <0%). A 4th column "Revenue" repeats the same trio.
- **Layout (prose):** "Top: two sticky model dropdowns (default Data-driven vs Cross-channel last click) + date range. Left rail: GA4 standard nav (Home / Reports / Explore / Advertising / Admin). Main canvas: side-by-side table with delta column. Conversion paths report sits as a sibling and adds a horizontal **3-segment touchpoint visualization** — Early touchpoints (first 25%), Mid touchpoints (middle 50%), Late touchpoints (last 25%) — each segmented by Default channel group with channel labels and credit-percentages."
- **Specific UI:** Three available models: Data-driven attribution (DDA), Paid and organic last click, Google paid channels last click. Switching either model re-renders both columns. % change column has color-coded delta cells. Conversion paths report's 3-segment Early/Mid/Late bar collapses Mid if path length < 3 and shows only Late if path length = 1.
- **Filters:** Date, model A, model B, dimension picker.
- **Data shown:** Conversions count + Revenue per model + delta %; for paths: Conversions, Purchase revenue, Days to conversion, Touchpoints to conversion.
- **Interactions:** Switching either model re-renders both columns; switching dimension re-aggregates rows. Conversion paths: hover colored segment shows the channel's share of credit at that path stage.
- **Why it works (or doesn't):** "GA4 sucks... Let's make a lot of the important data hard to access" (Dave Davies, Twitter, 2021); "GA4 is a disaster. It is so much harder to use than UA, and completely non-intuitive" (Jason McDonald, Search Engine Land, 2022). The Model Comparison report itself is acknowledged as functional but underused because DDA requires 400+ key-event conversions and 20,000+ total conversions to actually run — most SMBs don't qualify and silently fall back to last-click-equivalent.
- **Source:** ../competitors/ga4.md; https://www.optimizesmart.com/ga4-conversion-paths-report/

### Daasity ([profile](../competitors/daasity.md))

- **Surface:** Templates Library > Acquisition Marketing > **Attribution Deep Dive** (built on the Marketing Attribution explore).
- **Visualization:** Eight attribution models exposed as **filterable dimensions** inside one explore via a "Dynamic Attribution Method" filter-only field — switch model without rebuilding the report. Plus Custom Attribution waterfall (rank multiple sources for canonical attribution).
- **Layout (prose):** "Top: date + 'Dynamic Attribution Method' filter chip + dimension filters. Left rail: department-organized nav (Omnichannel / Retail Analytics / Ecommerce Performance / Acquisition Marketing / Retention Marketing / Utility / Data Source Dashboards). Main canvas: channel × vendor × model rollup table; assisted-lift comparison visualization shows non-last-click contributions; discount-code performance table tracks 'dozens of tracked codes.' Bottom: Custom Attribution waterfall ranks fallback sources (e.g., Survey → Discount-Code → GA last-click)."
- **Specific UI:** **Eight attribution models** in one explore: First-Click, Last-Click, Assisted, Last-Click + Assisted, Last Ad Click, Last Marketing Click, Survey-Based (Fairing-driven), Vendor-Reported. Plus Custom Attribution (waterfall) and Discount Code Attribution as parallel dimensions. Survey-Based view exposes three explicit dimensions: **Survey Response** (verbatim text), **Survey-Based Channel**, **Survey-Based Vendor**. Order-level granularity: orders without survey data show NULL.
- **Filters:** Dynamic Attribution Method, date, channel, vendor, model rank (for Custom), metric (CPA / CPO / gross margin / net sales / ROAS / orders / new-customer orders).
- **Data shown:** CPA, CPO, gross margin, net sales, gross sales, ROAS, orders, new-customer orders — by channel × vendor × attribution model.
- **Interactions:** Toggle between models via the Dynamic Attribution Method filter; rank models for Custom Attribution waterfall; drill UTM dimensions for GA-based models.
- **Why it works:** "I've used Glew, TripleWhale, Lifetimely and more and this is by far the best tool I've used" (Béis, Shopify App Store, March 2022). "We use [Daasity data] for internal decisions, board discussions, and investor presentations" (bioClarity, Shopify App Store, May 2020).
- **Source:** ../competitors/daasity.md; https://help.daasity.com/advanced/marketing-attribution/attribution-overview

### Wicked Reports ([profile](../competitors/wicked-reports.md))

- **Surface:** **FunnelVision** (top-level; entered via "Netflix Style Easy Button" or directly from the attribution-model menu).
- **Visualization:** Side-by-side comparison columns of **Wicked-attributed ROAS vs Facebook-reported ROAS** per campaign, plus TOF/MOF/BOF segmentation labels per click. Six attribution models switch live (First Click / Last Click / Linear / Full Impact / New Lead / ReEngaged Lead).
- **Layout (prose):** "Top: model selector + date + lookback / view-through impact sliders. Left rail: standard nav. Main canvas: full-funnel table with TOF/MOF/BOF rows; paired Wicked-attributed and Facebook-reported ROAS columns; cold-traffic (>7 days before sale) tagged separately. Bottom: per-customer journey drilldown with timestamped click history."
- **Specific UI:** "Customized Meta View-Through Conversion Impact" slider — user-adjustable on the fly (toggle how much view-through inflates ROAS/CAC). Custom Conversions definable mid-funnel. **5 Forces AI** weekly verdict per campaign as a three-state pill: **Scale / Chill / Kill** with justification text "you can defend." Cohort matrix: rows = acquisition month, columns = LTV accumulation over time, with attribution as a top-bar slicer that re-slices the cohort by acquired-source/campaign/ad/email.
- **Filters:** Attribution model, date, lookback / lookforward, view-through impact, channel, funnel stage, cohort source.
- **Data shown:** ROAS (Wicked-attributed), ROAS (Facebook-reported), spend, conversions, CAC at each funnel stage, cold-traffic ROAS, cohort LTV per acquisition source.
- **Interactions:** One-click model swap; numbers refresh live; drag view-through slider; Custom Conversions builder (Maximize tier).
- **Why it works:** "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time" (marketingtoolpro.com, 2025). "Color-coded dashboards make reviewing my performance simple and fast. Each section uses visual cues — like green for growth and red for issues" (marketingtoolpro.com, 2025). "When we use the iOS tracking, we often miss data… with Wicked Reports, the multiple attribution data solves the missing data and we're able to see there were sales made" (Michelle P, Agency Owner, smbguide.com).
- **Source:** ../competitors/wicked-reports.md

## Visualization patterns observed (cross-cut)

Synthesizing the per-competitor sections by visualization type. Each implementation was placed into the closest-fit bucket; some competitors (Northbeam, Polar, GA4) appear in multiple buckets where they ship more than one comparison surface.

- **Side-by-side columns (Platform-vs-Pixel, model-A-vs-model-B, or 3-column source compare):** 9 competitors — Rockerbox (Platform / Rockerbox / Conversion Comparison), Polar Analytics (Platform / GA4 / Polar Pixel — closest analog to a 6-source thesis), Triple Whale (Pixel / Platform), Conjura (Last Click / Platform Attributed), AdBeacon (Meta-reported / AdBeacon-tracked, inside Meta Ads Manager via Chrome extension), Wicked Reports (Wicked / Facebook-reported in FunnelVision), GA4 (Model A / Model B / % change), Northbeam (Model Comparison Tool with optional 3rd platform-data column on CSV export), Fairing (Survey / UTM-derived, with Daasity rendering it inline alongside last-click). Strongest correlation with positive reviews — operators describe it as "I finally know which number to trust."

- **Lens / model toggle on a single screen (one dropdown re-renders the page):** 8 competitors — ThoughtMetric (5 models), Lebesgue (5 models including Shapley Value & Markov Chain), Polar Analytics (9-10 models), Northbeam (7 models on Attribution Home), Wicked Reports (6 models, "took one click. The numbers swapped in real time"), TrueProfit (binary Last-clicked / Assisted toggle), SegmentStream (4 transparent models), Daasity (8 models via "Dynamic Attribution Method" filter chip).

- **Reporting Gap / delta widget (single KPI tile communicating disagreement):** 1 competitor — Hyros (named "Reporting Gap" widget on the dashboard). Direct precursor to a "show the disagreement" KPI tile pattern.

- **Touchpoint timeline / customer-journey drill-down:** 5 competitors — Hyros ("Deep Mode" vertical timeline per visitor), Polar Analytics (order-level click-into multi-touchpoint sequence), Lebesgue (per-order touchpoint timeline), AdBeacon (Customer Journey View, "380+ actionable data points"), ThoughtMetric (Order Profiles with per-order touchpoint timeline — UI not directly verified).

- **Stacked horizontal bar / 3-segment funnel-stage visualization:** 3 competitors — GA4 Conversion paths (Early 25% / Mid 50% / Late 25% horizontal stacked bar by channel), Rockerbox Funnel Position (dual normalization mode: vertical-100% by stage vs horizontal-100% by channel), Wicked Reports FunnelVision (TOF/MOF/BOF segmentation labels per click).

- **Three-state opinionated verdict (pill / badge labeling):** 1 competitor — Wicked Reports 5 Forces AI (Scale / Chill / Kill weekly verdict with justification text).

- **Statistical-result row with significance pill + CI bracket + point estimate:** 1 competitor — SegmentStream Geo Tests (`<Channel> — <Treatment> | Significant · [+22%, +49%] | +35%`). Distinctive way to communicate test outcomes.

- **Maturation curve / projection line:** 1 competitor — SegmentStream ("Observed vs Projected cumulative conversions over 42 days, with confidence metrics" + maturation table: Last 7d 53% → +3 weeks 99%).

- **Custom waterfall / model-priority ranking:** 1 competitor — Daasity Custom Attribution ("uses a waterfall approach to sift through multiple attribution data sources" with user-defined priority ranking, e.g., Survey → Discount-Code → GA last-click).

- **Browser overlay inside ad platform's native UI (in-context comparison):** 1 competitor — AdBeacon Chrome extension injecting AdBeacon columns into Meta Ads Manager's Ad Set + Ad views. Removes the "leave the dashboard to compare" friction entirely.

- **Cohort matrix with attribution slicer (LTV × acquisition month, re-sliced by source):** 1 competitor — Wicked Reports Customer Cohort Report. Inverts the more common "attribution is the report" pattern by making LTV the matrix and attribution a top-bar filter.

Recurring conventions: green for positive delta and red for negative is dominant (GA4, Northbeam Product Analytics, Wicked Reports, AdBeacon). Lebesgue is the outlier — explicitly **blue for positive, red for negative**. Stoplight indicators on rows are uncommon; dropdowns + sticky filter strips are universal. Source / model selectors live at the **report level** (whole page re-renders) more often than at the **per-cell level** (one metric flips lens) — only Northbeam's global filter strip and Daasity's "Dynamic Attribution Method" come close to per-page-wide re-render.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Trust through transparency — the disagreement IS the information**

- "trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time. That's why ThoughtMetric is a must!" — ThoughtMetric (WIDI CARE, Shopify App Store, December 2024)
- "great to have a few different sources of truth in this world and ThoughtMetric is becoming a trusted tool to sift through the chaos." — ThoughtMetric (Woolly Clothing Co, Shopify App Store, August 2025)
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Triple Whale (Steve R., Capterra, July 2024)
- "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey… they (customers) love that." — AdBeacon (agency testimonial, AdBeacon marketing)
- "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." — Lebesgue (Fringe Sport, Shopify App Store, October 2025)
- "tracking and metrics are unbelievable. Its so awesome to have accurate data from all our paid and organic sources in one pane of glass dashboard." — Hyros (Abd Ghazzawi, Trustpilot via checkthat.ai)

**Theme: Speed of model swap — "I can compare lenses in one click"**

- "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time." — Wicked Reports (marketingtoolpro.com, 2025)
- "When we use the iOS tracking, we often miss data and it leads us to turning off marketing campaigns. However, with Wicked Reports, the multiple attribution data solves the missing data and we're able to see there were sales made." — Wicked Reports (Michelle P, Agency Owner, smbguide.com)
- "Color-coded dashboards make reviewing my performance simple and fast. Each section uses visual cues — like green for growth and red for issues." — Wicked Reports (marketingtoolpro.com, 2025)

**Theme: Unique reclaim — "I see what platforms hide"**

- "Northbeam's data is by far the most accurate and consistent." — Northbeam (Victor M., Capterra, February 2023)
- "Northbeam's depth of attribution modeling is genuinely best-in-class" — Northbeam (Head West Guide review, 2026)
- "Northbeam's C+DV showed us exactly how our Meta views were driving purchases. In the future, this will give us more confidence in allocating our spend across the funnel." — Northbeam (Vessi case study)
- "Their multi-touch attribution and incrementality testing have been especially valuable for us." — Polar Analytics (Chicory, Shopify App Store, September 2025)

**Theme: Survey reveals what pixels miss**

- "while their pixel data showed a positive ROAS of 2.3x for their podcast campaign, survey data revealed an additional 31% increase in conversions that weren't captured by tracking technology, allowing them to increase their podcast budget by 40%." — Fairing (case-study marketing copy)
- "Solid Attribution App for significantly less money than the comp set." — Lebesgue (FluidStance, Shopify App Store, November 2025)

**Theme: Drill-down without losing context (timeline + journey)**

- "Hyros tied Google search clicks to booked calls and then to closed deals." — Hyros (Scout Analytics hands-on review, 2026)
- "Landing page on CF, checkout on ThriveCart, upsell in Stripe — Hyros did not freak out." — Hyros (Scout Analytics, 2026)

## What users hate about this feature

**Theme: Models contradict each other and nobody explains why**

- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Triple Whale (Derek Robinson, workflowautomation.net, March 2026)
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — Triple Whale (AI Systems Commerce, 2026 review)
- "Limited visibility into how Rockerbox assigns attribution credit." — Rockerbox (SegmentStream third-party review summary, 2026)
- "Hyros data sometimes does not match exactly with Facebook Ads Manager or other ad platforms, leading to confusion or distrust in the data." — Hyros (Reddit r/FacebookAds user via checkthat.ai)
- "the attribution model was dismal compared to Google Analytics 4 (GA4), and... failed to deliver the necessary depth and accuracy" — Northbeam (Trustpilot reviewer aggregated)

**Theme: Discrepancies that nobody resolves**

- "i have a discrepancy on the data, but after 10 days, there's no effort of giving me any clarity!" — ThoughtMetric (hugbel, Shopify App Store, March 2026)
- "Need side-by-side platform vs. actual data comparison" — ThoughtMetric (Bill C., Capterra, July 2022)
- "Tracked only 50% of sales; worst tracking app performance tested" — ThoughtMetric (Denis N., Capterra, December 2023)
- "It only worked for the first 6 months, after which it started over-reporting leads by 20-30%." — Hyros (2-year customer, Trustpilot via search aggregator)

**Theme: Steep learning curve to read multi-model output**

- "complex to use, particularly for new users, and some of the visual design is still being refined" — Northbeam (Capterra aggregated reviewer summary, 2026)
- "Will not follow the in-platform methodology" requiring users to "adopt Northbeam's attribution approach" — Northbeam (Head West Guide review, 2026)
- "UI feels dense. Lots of power, but it took me a week to feel smooth." — Hyros (Scout Analytics, 2026)
- "There is a learning curve, though there is a lot of documentation and help available, with users still discovering new reports and charts even after years of use." — Wicked Reports (G2 aggregated summary)
- "The interface can feel overwhelming for newcomers." — Wicked Reports (marketingtoolpro.com, 2025)
- "The 'bucket' terminology they use could be confusing to some, but nothing that couldn't be learned." — Rockerbox (Mike W., Capterra, December 2018)

**Theme: GA4-specific frustration with model count reduction**

- "Attribution model picker reduced from 7 (UA) to 3 (DDA / Paid-organic last click / Google last click)" — GA4 (first-touch, linear, time-decay, position-based were removed in 2023). "The platform still has a lot of bugs… spending more time figuring out why attribution is not properly labeled." — GA4 (John McAlpin, Search Engine Land, 2022)

**Theme: Latency between launch and trustable model output**

- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Triple Whale (Hannah Reed, workflowautomation.net, November 2025)
- "AI insights described as shallow" — Lebesgue (Capterra synthesis surfaces complaints like "insights" being "simply noting that CAC increased and conversion rate dropped off")
- "Some complaints revolve around the one-day-lag for the data whenever you launch a campaign." — Wicked Reports (review aggregator)

**Theme: Paywalled attribution screen on the very tier where SMBs need it**

- TrueProfit gates the Marketing Attribution screen entirely to its $200/mo Enterprise tier — every lower tier sees ad spend and ROAS but not the attribution screen. Multiple reviewers flag the upgrade pressure.
- Wicked Reports' entry tier is $499/mo (Measure), well above most SMB Triple Whale / Lifetimely / Glew alternatives.

## Anti-patterns observed

- **Hidden source disagreement / single "blended" number with no breakdown:** When a tool collapses Pixel + Platform + Survey + Store into one ROAS or CAC and surfaces only that, reviewers call it "ghost ROAS." Triple Whale's blended Summary tiles draw the "feels closer to platform self-reporting" critique (AI Systems Commerce, 2026 review). Conjura is praised for the opposite — explicit Last Click vs Platform Attributed columns side-by-side.

- **Model picker buried in settings, not in the report:** GA4's Attribution settings live under Admin > Attribution settings, requiring a navigation context-switch and a property-wide change rather than a per-report toggle. Multiple reviewers cite this as a reason they ignore GA4's Model Comparison report. Contrast with Polar / Northbeam / Wicked / Daasity, where the model dropdown sits at the top of the report and re-renders live.

- **Reducing model count without explanation:** GA4 silently removed first-click, linear, time-decay, and position-based models in 2023, leaving only DDA / Paid+organic last click / Google paid last click. Recurring "Google PTSD" complaints in Search Engine Land / Search Engine Journal cite this as a trust-eroding decision. The lesson: deprecating a lens is a content-removal event users never forgive.

- **Side-by-side without delta column:** Polar's three-column Platform / GA4 / Polar Pixel view shows the three numbers but doesn't compute the explicit delta or % change between them — users have to do the subtraction in their head. GA4 Model Comparison, by contrast, ships a `% change` column with color-coded delta cells. Several Polar reviewers mention "switching between views and reports can be slow sometimes" — the missing delta column compounds this.

- **Pricing axis tied to attribution accuracy:** Hyros's pricing is set by **attributed revenue** (not gross revenue), so improving attribution accuracy automatically pushes accounts up tiers. Multiple Trustpilot reviewers cite "43% post-renewal price hikes" as a structural complaint. Perverse-incentive trap — every accuracy improvement has a cost-side downside for the customer.

- **Survey-only or pixel-only without acknowledging the other exists:** ThoughtMetric folds survey signal into its Multi-Touch model but doesn't expose a "Survey-only" lens for comparison; Fairing exposes Survey-vs-UTM but doesn't ingest ad spend, so users have to leave the tool. Daasity is the only competitor that ships **eight named lenses including Survey-Based and Vendor-Reported as peers** in the same explore.

- **Compute-on-the-fly ratios stored anyway:** Triple Whale persists CPA / CPC / CPM / ROAS at aggregate level in their Benchmarks dashboard (otherwise peer benchmarks would be impossible at their scale). Per Nexstage's CLAUDE.md, ratios should never be stored — Triple Whale demonstrates the cost of doing it: they then have to refresh those aggregates and the UI shows occasional staleness ("UI changes frequently and documentation sometimes lags behind" per Derek Robinson, workflowautomation.net).

- **No GSC lens at all:** Northbeam, Triple Whale, Hyros, Wicked Reports, AdBeacon, Lebesgue, Conjura, ThoughtMetric, TrueProfit — none ship a Google Search Console lens. GA4 surfaces GSC clicks but cannot drill by GA4 user/session dimensions. The entire SMB attribution category structurally ignores organic-search credit; this is the most obvious whitespace observed in this research.

## Open questions / data gaps

- **G2 / Capterra / Trustpilot blocked WebFetch (403/404)** for most competitor research — verbatim quotes for Northbeam, Triple Whale, Hyros, Wicked Reports, AdBeacon are partially aggregated through third-party review summaries (smbguide.com, marketingtoolpro.com, Cuspera, headwestguide.com) rather than direct review-page text. Quote attribution is preserved where the third-party source itself attributes a verbatim line.
- **Northbeam Model Comparison Tool** UI specifics beyond "side-by-side columns" are not deeply documented in public sources — only conceptual description. UI screenshots not available.
- **Daasity Attribution Deep Dive** — the assignment hypothesized explicit Pixel / Survey / Promo / MTA tab labels; public help docs do **not** confirm those tabs. The four lenses appear to coexist as filterable dimensions in one explore (via the "Dynamic Attribution Method" filter chip), not as separate sub-tabs.
- **Fairing in-app side-by-side rendering** — Fairing's own Last Click and UTM Report and Multi-Tier Attribution UI is not directly observable; the side-by-side analog is documented in the Daasity dashboard that ingests Fairing data, not in Fairing's own product.
- **TrueProfit, AdBeacon, Hyros, Wicked Reports** — all paywalled with no free tier or public dashboard tour. UI descriptions reconstructed from third-party reviews + marketing illustrations + iOS App Store listings (Hyros).
- **Triple Whale Compass** (MMM + MTA + Incrementality reconciliation) — gated behind Pro tier; product page redirected/404'd in fetch. Specific UI for the Measurement Agent comparison view not verified.
- **Polar Analytics 9-10 attribution models** — exact model count differs between marketing-page enumerations (9 vs 10); the precise list and side-by-side rendering above the channel rollup table is from third-party walkthrough (swankyagency.com), not direct Polar screenshots.
- **Lebesgue color convention** (blue for positive deltas, red for negative) — observed in marketing copy on `lebesgue.io/product-features/shopify-reporting-app`. Whether this is consistent across all dashboards or only in the reporting view is not verified.
- **AdBeacon Chrome extension overlay** — Chrome Web Store consent gate blocked WebFetch; install count and rating not retrievable. Functional description only.

## Notes for Nexstage (observations only — NOT recommendations)

- **9 of 15 competitors ship a side-by-side comparison view as a first-class IA citizen.** Rockerbox's three named sub-views (Platform / Rockerbox / Conversion Comparison), Polar's three columns (Platform / GA4 / Polar Pixel), and AdBeacon's Chrome-extension overlay are the closest direct precedents for Nexstage's 6-source-badge thesis. None ship 6 sources simultaneously — Polar's 3 columns is the maximum observed.

- **0 of 15 competitors ship a GSC attribution lens** alongside paid + pixel + survey + GA4. GA4 itself surfaces GSC clicks but cannot drill by user/session. This is the most obvious whitespace in the category — Nexstage's `--color-source-gsc` token covers a lens nobody else exposes.

- **Lens toggle vs side-by-side is a real UX decision.** The "lens toggle" pattern (one dropdown re-renders the page) is dominant — 8 of 15 competitors. Side-by-side is operator-preferred when comparison is the explicit task, but heavier to render and harder to read at 6 columns. GA4's Model Comparison ships only 2 columns + delta; Polar caps at 3; Daasity's 8-model "Dynamic Attribution Method" is a filter chip, not a 8-column table.

- **The "Reporting Gap" widget pattern from Hyros is the simplest possible single-pair side-by-side.** A KPI tile that surfaces just the delta (Hyros vs Platform). Single number, big and obvious. Worth comparing against a 6-source row of badges.

- **GA4's `% change` column with color-coded delta cells** is the most lightweight delta-rendering pattern observed — green ▲ for >0%, red ▼ for <0%, applied per model-pair. Most other side-by-sides leave delta computation to the user.

- **Lebesgue uses BLUE for positive delta, not green.** Worth noting against Nexstage's `--color-source-google` and `--color-source-facebook` blue tokens — Lebesgue's choice may reflect colorblind concerns but conflicts semantically with brand-color blues.

- **Daasity's department-organized IA (Ecommerce / Marketing / Retail tabs)** is an alternative axis to source-based comparison. Their "Dynamic Attribution Method" filter chip with 8 models in one explore is the maximalist toggle reference.

- **Wicked Reports' Customer Cohort matrix with attribution as a top-bar slicer** inverts the usual relationship — LTV is the matrix, attribution is the filter. Different mental model from "attribution is the report"; relevant for cohort/LTV surface design downstream.

- **AdBeacon's Chrome extension is the only in-context comparison observed.** Injecting attribution columns into Meta Ads Manager's native UI removes the dashboard context-switch entirely. No equivalent observed for Nexstage's segment.

- **The "Scale / Chill / Kill" three-state verdict from Wicked Reports** is the only opinionated AI output observed in this batch — most competitors ship recommendations as a feed or chat. Worth noting as a pattern reference for any AI verdict surface Nexstage builds.

- **Northbeam's Day 30/60/90 progressive feature unlock** is a precedent for honestly communicating "your model isn't trained yet" — relevant to Nexstage's recompute-banner / cost-config retroactive recalc UX.

- **SegmentStream's maturation curve and table** ("Last 7d 53% → +3 weeks 99%") is the only competitor pattern observed for communicating partial-data uncertainty mid-window without hiding the row. Direct analog to "today's row is incomplete" UX.

- **Pricing pattern: side-by-side / lens-toggle attribution is the upgrade trigger in 4 of 15 competitors.** TrueProfit ($200/mo Enterprise), Triple Whale (Advanced+ for "Total Impact"), Lebesgue (Le Pixel add-on $99-$1,649/mo), Wicked Reports ($499/mo Measure entry). ThoughtMetric is the outlier — every feature at every tier, no gating.

- **No competitor exposes 6 sources simultaneously.** The maximum side-by-side count observed is 3 (Rockerbox, Polar). The maximum lens-toggle count is 9-10 (Polar attribution models). If Nexstage ships 6 source badges as default, that is empirically unprecedented density in the category — worth knowing both for differentiation copy and for usability testing.
