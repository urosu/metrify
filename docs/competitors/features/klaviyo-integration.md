---
name: Klaviyo integration depth
slug: klaviyo-integration
purpose: Surfaces Klaviyo flow/campaign revenue alongside ad spend so a merchant can read total marketing impact (paid + owned) on one screen, with attribution windows that match Klaviyo's defaults.
nexstage_pages: dashboard, ads, performance, attribution-comparison
researched_on: 2026-04-28
competitors_covered: triple-whale, polar-analytics, lifetimely, storehero, lebesgue, klaviyo, conjura, cometly, thoughtmetric, peel-insights, glew, daasity, bloom-analytics, beprofit, trueprofit, fospha, varos, elevar, northbeam, wicked-reports, looker-studio
sources:
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/lifetimely.md
  - ../competitors/storehero.md
  - ../competitors/lebesgue.md
  - ../competitors/klaviyo.md
  - ../competitors/conjura.md
  - ../competitors/cometly.md
  - ../competitors/thoughtmetric.md
  - ../competitors/peel-insights.md
  - ../competitors/glew.md
  - ../competitors/daasity.md
  - ../competitors/bloom-analytics.md
  - ../competitors/beprofit.md
  - ../competitors/trueprofit.md
  - ../competitors/fospha.md
  - ../competitors/varos.md
  - ../competitors/elevar.md
  - ../competitors/northbeam.md
  - ../competitors/wicked-reports.md
  - ../competitors/looker-studio.md
  - https://www.triplewhale.com/blog/triple-whale-product-updates-april-2026
  - https://www.triplewhale.com/sonar
  - https://lebesgue.io/product-features/shopify-reporting-app
  - https://lebesgue.io/le-pixel
  - https://help.klaviyo.com/hc/en-us/articles/4708299478427
---

## What is this feature

A merchant running paid Meta + Google + TikTok ads typically also runs Klaviyo flows (welcome series, abandoned cart, post-purchase) and Klaviyo campaigns (newsletters, promo blasts). The buying question is no longer "how is Meta doing" but "how is *marketing* doing — paid plus owned, on one screen, with one attribution window I trust." Klaviyo's own dashboard answers half the question (email/SMS-attributed revenue with peer percentiles) but cannot show paid spend or blended ROAS — its analytics is owned-channel-only ([klaviyo.md](../competitors/klaviyo.md)). Conversely, ad-attribution tools like Northbeam, Cometly, and Hyros pull spend but do not always treat email as a co-equal channel.

The "Klaviyo integration depth" feature is the synthesis that closes that gap: pulling Klaviyo flow + campaign revenue from the Klaviyo API (or the storefront pixel), normalizing it onto the same date range, attribution model, and conversion window as the ad-platform data, and rendering it inside the same channel breakdown as paid Meta / Google / TikTok. The interesting variants are (a) whether Klaviyo gets a *dedicated section* or sits as one row among 12 channels; (b) whether the attribution window is matched to Klaviyo's defaults (5-day click for email, 1-day click for SMS) or forced into the tool's universal model; and (c) whether the tool *enriches* Klaviyo (server-side events back to Klaviyo to lift flow performance) or just *reads* from it.

## Data inputs (what's required to compute or display)

- **Source: Klaviyo API** — `flows.id`, `flows.name`, `flows.attributed_revenue`, `flows.placed_order_value`, `flows.conversions`, `flows.opens`, `flows.clicks`, `flows.unique_recipients`
- **Source: Klaviyo API** — `campaigns.id`, `campaigns.name`, `campaigns.send_time`, `campaigns.attributed_revenue`, `campaigns.conversions`, `campaigns.open_rate`, `campaigns.click_rate`
- **Source: Klaviyo Track API events** — `Placed Order`, `Active on Site`, `Viewed Product`, `Started Checkout`, `Ordered Product` (used by Klaviyo's own attribution model)
- **Source: Klaviyo configuration** — `account.conversion_window` (default 5d email click / 1d SMS click), `account.attribution_model` (last-click default)
- **Source: Storefront pixel** — `klaviyo` UTM source/medium parameters appended to flow links, used by some tools (Lifetimely, Cometly, ThoughtMetric) to *re-attribute* email-driven orders rather than read Klaviyo's own attribution
- **Source: Shopify / Woo orders** — `order.tags`, `order.note_attributes`, `order.landing_site` (UTM parameters), `order.referring_site` for first-party pixel reconciliation
- **Source: Computed** — `email_attributed_revenue = SUM(flows.placed_order_value) + SUM(campaigns.attributed_revenue)` (when reading Klaviyo) OR `SUM(orders.total_price WHERE utm_source IN ('klaviyo','email'))` (when re-attributing via UTM)
- **Source: Computed** — `blended_marketing_efficiency = total_revenue / (paid_ad_spend + email_send_cost)` (rare — most tools treat email cost as $0 in MER)
- **Source: User-input** — `email_send_cost_per_recipient` (when computing true email ROI; Klaviyo's own pricing per profile-tier informs this)
- **Source: User-input** — channel mapping rule: which UTM source/medium combinations roll up to "Email" in the channel taxonomy
- **Source: First-party pixel (some competitors)** — Triple Pixel, Polar Pixel, Le Pixel, AdBeacon pixel — to enrich Klaviyo events with cross-device identity and push back via server-side events
- **Source: Push-back / activation (optional)** — Klaviyo Audiences API to push enriched RFM segments out to Klaviyo

## Data outputs (what's typically displayed)

- **KPI: Email-attributed revenue** — `SUM(klaviyo_attributed_revenue)`, USD, per period
- **KPI: Flow revenue vs Campaign revenue split** — two stacked numbers, USD
- **KPI: Email % of total revenue** — ratio, "N/A" if total is zero
- **KPI: Blended ROAS / MER (with email)** — ratio, computed, never stored
- **Dimension: Channel** — string, ~10–12 values: Direct, Paid Social (Meta), Paid Search (Google), Paid TikTok, Email (Klaviyo), SMS (Klaviyo/Postscript/Attentive), Organic Search, Affiliate, Referral, Subscription
- **Dimension: Klaviyo flow / campaign name** — string, drill-down dimension
- **Breakdown: Revenue × channel × time** — table or stacked bar
- **Breakdown: Top flows by revenue** — ranked list, descending; usually 5–10 rows with status pill (Live / Manual / Draft)
- **Breakdown: Recent campaigns** — table, sortable by send date or revenue
- **Slice: First-time vs repeat purchasers from email** — Klaviyo, Lebesgue, Lifetimely, Polar all expose this
- **Slice: Email correlation with repeat purchase** — Lebesgue's Henri sample prompt is verbatim "Analyze the correlation between email-campaign spikes and repeat-purchase revenue"
- **Comparison: Klaviyo-reported revenue vs tool-attributed revenue** — side-by-side columns (Triple Whale, Polar, Lifetimely all do this)
- **Lift output (where push-back is integrated):** "X% increase in Klaviyo flow revenue" — Triple Whale Sonar Send claims 22%, Elevar Session Enrichment claims "2-3x performance boost for Klaviyo flows"

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Summary (default landing) has a **collapsible Klaviyo section** alongside Pinned, Store Metrics, Meta, Google, Web Analytics, Custom Expenses. Separately, Sidebar > Email & SMS Attribution Dashboard (April 2026 beta) is a dedicated cross-tool surface unifying Klaviyo / Attentive / Omnisend / Postscript ([profile](../competitors/triple-whale.md)).
- **Visualization:** Draggable KPI tile grid (Summary section); dedicated dashboard table for the Email & SMS Attribution view. The Summary view's Klaviyo section is a sub-grid of metric tiles inside the broader collapsible sections layout.
- **Layout (prose):** "Top date-range and store-switcher controls. Body is organized as collapsible sections by data integration — by default sections include Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics (Triple Pixel), and Custom Expenses. Each section is a grid of draggable metric tiles." ([profile](../competitors/triple-whale.md))
- **Specific UI:** KPI tile shows headline value + period-vs-period delta; hovering reveals 📌 pin icon to pin Klaviyo metrics into the Pinned section so they sit alongside Meta/Google ROAS at the top of Summary. On-demand refresh button (April 2026) shows "Refreshing Klaviyo…" status mid-pull.
- **Filters:** Date range, store switcher, period-vs-prior-period toggle.
- **Data shown:** Klaviyo flow revenue, campaign revenue, opens, clicks, conversions; alongside per-platform paid spend/ROAS sub-tiles.
- **Interactions:** Drag/drop tile reordering; pin Klaviyo tiles to Pinned; pivot to table view; click metric to drill into detail; Moby Chat sidebar accepts NL queries that span Klaviyo + paid (e.g. cross-source). Sonar Send dashboard (sub-surface) shows enrichment-attributable lift. Sonar Send marketing claim: **"22% Increase in Klaviyo Flow Revenue"** ([profile](../competitors/triple-whale.md)).
- **Why it works (from reviews/observations):** "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra ([profile](../competitors/triple-whale.md)). Reviewer attributes the side-by-side framing to legitimate disagreement-finding.
- **Source:** [triple-whale.md](../competitors/triple-whale.md); https://www.triplewhale.com/blog/triple-whale-product-updates-april-2026; https://www.triplewhale.com/sonar.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Sidebar > Engagement page (pre-built) is the dedicated Klaviyo email/SMS performance dashboard. Klaviyo also appears as a row in the Acquisition page's channel breakdown alongside Meta, Google, TikTok.
- **Visualization:** Pre-built dashboard with metric cards, sparkline cards, and tables (per their canvas-builder pattern: Key Indicator Section + Tables/Charts + Sparkline Cards).
- **Layout (prose):** "A dashboard is a vertical canvas the user composes by stacking blocks. Recommended pattern: Metric Cards or Sparkline Cards in a horizontal row across the top, with charts and tables below." ([profile](../competitors/polar-analytics.md))
- **Specific UI:** Sparkline Card — "a metric card with a mini trend line embedded inside the card itself" — used for email-attributed revenue and revenue-per-recipient with comparison-period delta arrows rendered automatically off the dashboard date range. Klaviyo Audiences activation surface (separately) lets users push enriched segments back into Klaviyo lists.
- **Filters:** Dashboard-level date range; Views (saved filter bundles) for store/region/channel; comparison toggle (vs prior period or YoY).
- **Data shown:** Email/SMS-attributed revenue, abandonment recovery (via Audiences activation), engagement metrics; broken out by campaign vs flow ([profile](../competitors/polar-analytics.md)).
- **Interactions:** Block reordering between dashboards; schedule a block to auto-deliver as Slack message or email; drill from channel → campaign → ad → order → customer journey shows the full multi-touchpoint sequence including Klaviyo touchpoints.
- **Why it works:** "The level of support you get from the polar team is outstanding, really willing to help" — Gardenesque, Shopify App Store ([profile](../competitors/polar-analytics.md)). Klaviyo is one of "Polar's deeply integrated" connectors per the integrations list.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md); https://intercom.help/polar-app/en/articles/10430437-understanding-dashboards.

### Lifetimely (by AMP) ([profile](../competitors/lifetimely.md))
- **Surface:** Sidebar > Attribution Report. Klaviyo also appears as a marketing-bucket cost in the Profit Dashboard income statement.
- **Visualization:** Tabular layout — channel rows × metric columns, with side-by-side platform-reported vs Lifetimely-pixel-attributed columns.
- **Layout (prose):** "Centralized marketing command center. Channel rows (Facebook, Instagram, Google, TikTok, Snapchat, Pinterest, Microsoft) with columns: reported revenue, spend, CPC, CAC, ROAS. Lifetimely's own pixel data shown alongside platform-reported numbers — explicit side-by-side comparison." ([profile](../competitors/lifetimely.md))
- **Specific UI:** Klaviyo enters the channel row list when email is treated as a channel; the income-statement-style P&L renders Klaviyo as a marketing line item descending from revenue. Anomaly-detection alerts surface email performance spikes/drops.
- **Filters:** Date range, attribution-model toggle (first-click vs last-click).
- **Data shown:** Email/SMS revenue attribution; segment-level performance feeds into channel and cohort reports ([profile](../competitors/lifetimely.md)).
- **Interactions:** Filter by date range; attribution-model toggle; cohort filter (slice cohort by source/medium captures email cohort retention). Daily P&L Email/Slack delivery at 7am bundles Klaviyo into the same digest as paid spend.
- **Why it works:** "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health ([profile](../competitors/lifetimely.md)). The income-statement framing puts Klaviyo cost and revenue on the same page as paid.
- **Source:** [lifetimely.md](../competitors/lifetimely.md); https://useamp.com/products/analytics.

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Sidebar > Ads tab (channel-by-channel paid performance) treats Klaviyo as a marketing-impact channel folded into Marketing Reports; not a dedicated standalone tab.
- **Visualization:** KPI tile grid + channel-comparison side-by-side blocks within the Unified Dashboard.
- **Layout (prose):** "One clean command center combining sales, ad spend, and profit on a single screen; the homepage copy frames it as a full funnel from reach to profit. KPI tiles for net sales, ad spend, contribution margin; a side-by-side grouping of channel performance." ([profile](../competitors/storehero.md))
- **Specific UI:** Contribution margin is the anchor metric — Klaviyo email revenue feeds the contribution-margin calculation alongside paid ad spend; Klaviyo doesn't get a separate visualization, it gets folded into the same "Marketing Spend / Net Sales / Contribution Margin" math. UI details for Klaviyo-specific drill-down not observable from public sources ([profile](../competitors/storehero.md)).
- **Filters:** Date range, store-switcher (for agency multi-store view), channel-blended-vs-channel-by-channel toggle.
- **Data shown:** Email-attributed revenue and engagement, integrated into the Marketing Reports module.
- **Interactions:** Channel-blended toggle; drill from channel into per-source.
- **Why it works:** "clarity around contribution margin. It gives a true understanding of what is actually driving profit" — Origin Coffee ([profile](../competitors/storehero.md)). Klaviyo is treated as a margin-positive channel rather than a separate revenue silo.
- **Source:** [storehero.md](../competitors/storehero.md).

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Business Report (primary dashboard) lists Klaviyo as one of the ad-spend/marketing-cost rows alongside Meta/Google/TikTok/Amazon. Henri (AI agent) accepts cross-source prompts.
- **Visualization:** Auto-generated custom report — line/bar chart canvases with metric-selection dropdowns, plus a Business Overview Table that lists "all KPIs side-by-side, exportable."
- **Layout (prose):** "User picks 'the metrics and time period you'd like to analyze,' then the system auto-generates a custom report. Layout includes metric-selection dropdowns, a date-range picker, and line/bar charts. Grouping options for day/week/month sit alongside the period selector." ([profile](../competitors/lebesgue.md))
- **Specific UI:** "Color-coded performance indicators (blue for improvements, red for declines)" — note **blue (not green) for positive deltas**, an unusual choice ([profile](../competitors/lebesgue.md)). Metrics shown verbatim include "Revenue, First-time Revenue, Ad Spend (Meta/Google/TikTok/Amazon/**Klaviyo**), COGS, Profit, ROAS" — Klaviyo is *literally* listed in the same parenthetical as paid platforms, treated as another ad-spend bucket.
- **Filters:** Metric selector, date range, day/week/month aggregation toggle.
- **Data shown:** Klaviyo campaign performance and email tactics; computed: email-spike-to-repeat-purchase correlation analysis (Henri sample prompt: *"Analyze the correlation between email-campaign spikes and repeat-purchase revenue."*) ([profile](../competitors/lebesgue.md)).
- **Interactions:** Pick metric → pick range → auto-generate; download custom report. Henri chat returns inline charts plus Key Takeaways and Recommendations sub-blocks.
- **Why it works:** "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." — Fringe Sport, Shopify App Store ([profile](../competitors/lebesgue.md)). Verbatim user praise that explicitly names Klaviyo alongside paid as the value prop.
- **Source:** [lebesgue.md](../competitors/lebesgue.md); https://lebesgue.io/product-features/shopify-reporting-app.

### Klaviyo (own profile — what they expose via API and own UI) ([profile](../competitors/klaviyo.md))
- **Surface:** Klaviyo's own Home dashboard and Analytics > Overview dashboard are the canonical "Klaviyo revenue" surfaces; merchants who don't use a third-party tool live here. Klaviyo *does not* ingest Meta/Google/TikTok ad spend, so a merchant cannot see blended ROAS or MER inside Klaviyo itself.
- **Visualization:** Stacked-bar (Conversion Summary card splits flows vs campaigns); multi-line chart (Campaign Performance card with three colored lines: open rate blue, click rate teal, conversion metric yellow); KPI cards with peer-benchmark badges rated **"Excellent / Fair / Poor"**; top-flows ranked list with status pills (Live / Manual / Draft); per-message embedded analytics sidebar inside the Flow Builder canvas.
- **Layout (prose):** "Top: alerts strip + conversion-metric selector + time-period selector (up to 180 days). Main canvas (vertical scroll): 'Business Performance Summary' card showing total revenue with an inline channel breakdown (email/SMS/push) and a flows-vs-campaigns split. Below: 'Top-Performing Flows' — up to six flows ranked descending by conversion or revenue with status pill (Live / Manual), message-type icon, delivery count, conversion count, and percent-change vs prior period." ([profile](../competitors/klaviyo.md))
- **Specific UI:** Per-message embedded analytics sidebar — "Click any message card and a left-hand sidebar slides in showing 30-day analytics for that specific node — opens, clicks, revenue per recipient" ([profile](../competitors/klaviyo.md)). Default Klaviyo conversion window: customizable per-metric on Email/SMS plans; Marketing Analytics add-on unlocks "flexible attribution settings that apply retroactively." Default email conversion window is 5-day click; SMS is 1-day click (Klaviyo platform default).
- **Filters:** Date range (up to 180 days), conversion-metric selector that re-pivots all cards globally, comparison period.
- **Data shown:** Total revenue, attributed revenue, conversions, opens, clicks, sends, percent change vs prior period; revenue-per-recipient.
- **Interactions:** Selecting a different conversion metric recalculates all cards. Clicking a flow name opens flow detail. Click message node in Flow Builder to load 30-day analytics.
- **Why it works:** "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group ([profile](../competitors/klaviyo.md)). The per-node embedded analytics is "a strong embedded analytics pattern that exists nowhere on a marketing-only canvas" ([profile](../competitors/klaviyo.md)).
- **Source:** [klaviyo.md](../competitors/klaviyo.md); https://help.klaviyo.com/hc/en-us/articles/4708299478427.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** No native Klaviyo. Customer Table allows segment export to "CRM or email platform for personalized retargeting and loyalty campaigns" but Klaviyo is not first-class as a revenue source.
- **Visualization:** No Klaviyo visualization observed.
- **Layout (prose):** Not applicable — Klaviyo is explicitly absent.
- **Specific UI:** "**No Klaviyo.** Email/SMS attribution not natively supported (export-to-CRM only)." — verbatim from profile ([profile](../competitors/conjura.md)).
- **Filters:** N/A.
- **Data shown:** None for Klaviyo.
- **Interactions:** Export customer list to email tool; no read-back.
- **Why it works:** Does not — flagged as a gap.
- **Source:** [conjura.md](../competitors/conjura.md). Verbatim: "**No GSC / no Klaviyo.** Two major sources missing for an SMB ecommerce analytics tool — SEO/search data and email/SMS attribution."

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** Klaviyo is listed only under "Forms" (form submissions, not email/SMS event ingest) — i.e., Cometly captures Klaviyo opt-in form submissions as lead events, not email-attributed orders.
- **Visualization:** Per-lead Customer Journey timeline (touchpoints across "ads, emails, forms, and more") ([profile](../competitors/cometly.md)).
- **Layout (prose):** "Per-lead detail view 'showing detailed lead information including journey and source.' Tracks 'every interaction across ads, emails, forms, and more — from first touch to closed deal.'"
- **Specific UI:** Lead identity panel (name, email, phone, IP); chronological touchpoint timeline; source attribution per touchpoint. Klaviyo email opens/clicks NOT pulled.
- **Filters:** Attribution-model dropdown (First Touch / Last Touch / Linear / U-Shaped / Time Decay); conversion-window selector with **30/60/90-day options** ([profile](../competitors/cometly.md)).
- **Data shown:** Klaviyo form submissions only.
- **Interactions:** Drill into lead journey.
- **Why it works:** Does not for Klaviyo revenue — verbatim: "**No Klaviyo as an email/SMS source** — Klaviyo is listed only under 'Forms' (likely form submissions, not email-event ingest)" ([profile](../competitors/cometly.md)).
- **Source:** [cometly.md](../competitors/cometly.md).

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Marketing Attribution dashboard (Product > Analytics > Attribution) — channel rows include "email/SMS" alongside Meta/TikTok/Pinterest/Google/Bing/organic-social/podcasts/influencers/affiliates.
- **Visualization:** Channel-level performance table with selectable attribution-model dropdown and attribution-window selector. Inline widget thumbnails show square spend cards labelled "Spend"/"ROAS"/"MER" plus platform-tagged sub-widgets ([profile](../competitors/thoughtmetric.md)).
- **Layout (prose):** "The surface is centred on a channel-level performance view with a selectable attribution-model dropdown and an attribution-window selector (7 / 14 / 30 / 60 / 90 days). Channel rows enumerated on the page include 'Meta Ads, TikTok, Pinterest, Google Ads, Bing, organic social, email/SMS, podcasts, influencers, affiliates, and UTM-based custom channels.'" ([profile](../competitors/thoughtmetric.md))
- **Specific UI:** Email/SMS gets a single channel row with no separate visualization treatment. Five attribution models (Multi-Touch default / First Touch / Last Touch / Position Based / Linear Paid) recompute the email row's attributed revenue. Conversion window selectable at 7/14/30/60/90 days — wider than Klaviyo's default 5-day, so values diverge from Klaviyo's own report.
- **Filters:** Attribution-model dropdown, lookback window (7/14/30/60/90), date range.
- **Data shown:** Email/SMS sends, opens, clicks; attributed revenue under whichever attribution model is selected.
- **Interactions:** Switch attribution model and lookback window — both re-render the email row's attributed revenue value.
- **Why it works (from reviews/observations):** Limited verbatim Klaviyo-specific user feedback. The "Email and SMS performance is enumerated as a tracked channel" is the headline differentiator vs Conjura ([profile](../competitors/thoughtmetric.md)).
- **Source:** [thoughtmetric.md](../competitors/thoughtmetric.md); https://thoughtmetric.io/marketing_attribution.

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Audiences module pushes built segments out to Klaviyo / Attentive / Postscript / Meta. Reporting side ingests Klaviyo as a connector across the Cohort, RFM, and journey reports.
- **Visualization:** Audience-builder filter UI; cohort grids that include channel filters; Audience Overlap Venn-style report.
- **Layout (prose):** "Audiences — customer-segment builder (filters: products, SKUs, tags, channels, locations, campaigns, LTV, discount codes, purchase count) → push to Klaviyo / Attentive / Postscript / Meta or download CSV." ([profile](../competitors/peel-insights.md))
- **Specific UI:** Bidirectional Klaviyo — Peel reads engagement and pushes audience exports back. Daily Insights Report digest can include Klaviyo metrics.
- **Filters:** Multi-filter audience builder (channels, campaigns); 36+ cohort metrics.
- **Data shown:** Klaviyo as a channel filter and as a destination; specifics of metrics shown in dashboards not enumerated for Klaviyo specifically.
- **Interactions:** Export segment to Klaviyo audience; cohort filter on Klaviyo as an acquisition channel.
- **Source:** [peel-insights.md](../competitors/peel-insights.md).

### Glew ([profile](../competitors/glew.md))
- **Surface:** Customer Segments 2.0 module — bidirectional sync to Klaviyo (Glew → Klaviyo audiences). Klaviyo as data source for campaign-performance reads on Pro tier+.
- **Visualization:** Segment table with 55+ filterable metrics, RFM scoring; not a dedicated Klaviyo dashboard.
- **Layout (prose):** "55+ filterable metrics and 15 product-specific metrics… Over 40 unique KPIs and more than 30 unique charts and data visualizations per segment view. RFM scoring built in. Percentile-based filtering for high-value customer tiers." ([profile](../competitors/glew.md))
- **Specific UI:** Cross-platform filtering across ecommerce + loyalty + support + Klaviyo; segment sync to Klaviyo as audiences. **"Glew is bidirectional with Klaviyo: Glew customer segments can be pushed to Klaviyo as audiences."** ([profile](../competitors/glew.md))
- **Filters:** RFM filters, percentile filters, cross-source filters (Loyalty Lion + Yotpo + Zendesk join).
- **Data shown:** Subscriber lists, campaign performance from Klaviyo; segment-specific KPIs.
- **Interactions:** Push segment to Klaviyo; export all segments a customer belongs to.
- **Source:** [glew.md](../competitors/glew.md).

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Report Library includes a dedicated "Klaviyo Campaign & Flow Performance" template ([profile](../competitors/daasity.md)). Audiences module is reverse-ETL destination to Klaviyo.
- **Visualization:** Pre-built dashboard (specific viz type not described in public sources beyond "report"); sits in the team-organized Report Library spine.
- **Layout (prose):** Report Library is "organized by department/team, not by data source." Klaviyo Campaign & Flow Performance is one entry alongside Gorgias, Loop Returns, Okendo Reviews, ShipBob templates ([profile](../competitors/daasity.md)).
- **Specific UI:** UI details not available — only template name observable. Reverse-ETL Audiences activation pushes segments back to Klaviyo / Attentive / Meta / Google Ads.
- **Filters:** Department-organized report navigation.
- **Data shown:** Klaviyo campaign + flow performance.
- **Interactions:** Reverse-ETL push to Klaviyo.
- **Source:** [daasity.md](../competitors/daasity.md).

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Klaviyo email campaign profits feature — gated to Grow tier ($40/mo) and above.
- **Visualization:** UI details not observable from public sources beyond the feature name.
- **Layout (prose):** Not directly observable.
- **Specific UI:** "Klaviyo email campaign profits — net profit attributed to specific Klaviyo campaigns (Grow tier+)." ([profile](../competitors/bloom-analytics.md)) Notable: framed as **profit per campaign**, not just attributed revenue — pulls Klaviyo into the same profit-first lens as paid spend.
- **Filters:** Date range; campaign-level drill-down.
- **Data shown:** Per-campaign net profit attributed to Klaviyo.
- **Interactions:** Slack updates can include Klaviyo campaign profit alerts.
- **Why it works:** Free tier upgrade-trigger is verbatim "Custom COGS / multi-store / Klaviyo" — Bloom uses Klaviyo as a paid-tier wedge ([profile](../competitors/bloom-analytics.md)).
- **Source:** [bloom-analytics.md](../competitors/bloom-analytics.md).

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Klaviyo listed as integration; role is unclear from public docs.
- **Visualization:** No Klaviyo-specific viz observed.
- **Layout (prose):** Not observable.
- **Specific UI:** "**No native Klaviyo revenue attribution** (Klaviyo listed as integration but role is unclear from public docs)." ([profile](../competitors/beprofit.md)) Verbatim from gap analysis.
- **Filters:** N/A.
- **Data shown:** Specifics not documented in public pages.
- **Interactions:** N/A.
- **Source:** [beprofit.md](../competitors/beprofit.md).

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Klaviyo listed in blog feature listings but never described as a primary data source.
- **Visualization:** No standalone Klaviyo surface observed.
- **Layout (prose):** Not observable.
- **Specific UI:** "Listed as integrations on blog comparison pages but never described as data sources for attribution. Treated as auxiliary connectors rather than first-class lenses (no 'Klaviyo lens' or 'GA4 lens' UI hinted at)." ([profile](../competitors/trueprofit.md))
- **Filters:** N/A.
- **Data shown:** Auxiliary at best.
- **Interactions:** N/A.
- **Source:** [trueprofit.md](../competitors/trueprofit.md).

### Fospha ([profile](../competitors/fospha.md))
- **Surface:** Klaviyo email-driven conversions appear inside the full-funnel MMM model.
- **Visualization:** Horizontal bar charts where email sits as a channel alongside PMax, Direct, Referral.
- **Layout (prose):** "Email channel ROI within full-funnel model (shown as a channel in horizontal bar charts alongside PMax, Direct, Referral)." ([profile](../competitors/fospha.md))
- **Specific UI:** Email is one bar in a multi-channel horizontal stacked bar; not a dedicated Klaviyo surface but explicitly co-equal to paid channels in the visualization.
- **Filters:** Market filter (Lite=1, Pro=3, Enterprise=5 markets); date range.
- **Data shown:** Email-driven conversions, channel ROI under MMM attribution.
- **Interactions:** Compare channel ROI across the full funnel.
- **Source:** [fospha.md](../competitors/fospha.md).

### Varos ([profile](../competitors/varos.md))
- **Surface:** Klaviyo cited in third-party reviews as integration; benchmarks include email metrics on a peer-cohort basis.
- **Visualization:** Not directly observed for Klaviyo.
- **Layout (prose):** "Klaviyo (cited in trymesha.com review as integration); Stripe (cited in trymesha.com review). Computed metrics not documented publicly." ([profile](../competitors/varos.md))
- **Specific UI:** UI details not available.
- **Filters:** Vertical, AOV band, spend tier (the "three-axis peer group" framework — applies to email metrics if exposed).
- **Data shown:** Peer percentile benchmarks for email — specifics not documented.
- **Source:** [varos.md](../competitors/varos.md).

### Elevar ([profile](../competitors/elevar.md))
- **Surface:** Klaviyo is a destination (push-back), not a read source. Server-side events are pushed to Klaviyo via Session Enrichment.
- **Visualization:** Server Events Log + Channel Accuracy Report — not a Klaviyo revenue dashboard, but a transparency view of "Klaviyo flows fired correctly."
- **Layout (prose):** "Event-level log; one row per server-side event sent. Per-event status (success/failure/ignored), API response from destination, error code if applicable." Cross-references an "Error Code Directory" with a dedicated **Klaviyo error-code doc** ([profile](../competitors/elevar.md)).
- **Specific UI:** Klaviyo gets its own error-code section — operational lens. Marketing claim: **"2-3x performance boost for Klaviyo flows via Session Enrichment"** and **"50% or greater increase in product view and add-to-cart events"** sent server-side to Klaviyo ([profile](../competitors/elevar.md)).
- **Filters:** Filter by destination (e.g., Klaviyo only), by status, by error code.
- **Data shown:** Event-level success/failure to Klaviyo; not revenue.
- **Interactions:** Used for ad-hoc verification of Klaviyo event flow.
- **Why it works (and breaks):** Notable cautionary verbatim: "When Shopify launched its own server-side Klaviyo integration, some Elevar customers were not proactively notified that it could conflict with their existing Elevar tracking. **This caused Klaviyo flows to stop for some merchants.**" — ATTN Agency, Elevar Review 2026 ([profile](../competitors/elevar.md)). Hidden source-disagreement risk.
- **Source:** [elevar.md](../competitors/elevar.md).

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Klaviyo flows treated as touchpoints in the multi-touch attribution model.
- **Visualization:** UI details not directly observable for Klaviyo specifically.
- **Layout (prose):** Klaviyo enters the MTA channel breakdown alongside paid; specific layout not documented.
- **Specific UI:** "Pulled: email/SMS engagement events, sends, opens. Computed: Klaviyo flows treated as touchpoints in MTA model." ([profile](../competitors/northbeam.md))
- **Filters:** Standard Northbeam attribution-model controls.
- **Data shown:** Klaviyo as a touchpoint within MTA; not as a standalone surface.
- **Interactions:** Switch attribution model.
- **Source:** [northbeam.md](../competitors/northbeam.md).

### Wicked Reports ([profile](../competitors/wicked-reports.md))
- **Surface:** Klaviyo is named first-class — explicit "New Lead vs. ReEngaged Lead" split for Klaviyo opt-ins.
- **Visualization:** UI details not directly described.
- **Layout (prose):** Not observable.
- **Specific UI:** "New Lead vs. ReEngaged Lead split; cold-traffic optin attribution that 'captures Klaviyo email optins that have delayed Shopify sales conversions'; opt-in to first-purchase delay measurement." ([profile](../competitors/wicked-reports.md)) Notable: Wicked emphasises the *long-window* pattern — Klaviyo opt-in to first purchase can lag weeks; Wicked claims to capture that delay where short-window tools miss it.
- **Filters:** Conversion-window settings (Wicked is known for very long lookbacks).
- **Data shown:** Optin → first purchase delay; new vs reengaged split.
- **Interactions:** Standard Wicked attribution drill-downs.
- **Source:** [wicked-reports.md](../competitors/wicked-reports.md).

### Looker Studio (via Partner connector) ([profile](../competitors/looker-studio.md))
- **Surface:** Klaviyo data only available via paid Partner connector (Supermetrics, Porter Metrics, Catchr, Windsor.ai, etc.).
- **Visualization:** Whatever the merchant builds — table, time-series, scorecard.
- **Layout (prose):** No native Klaviyo connector. Each Partner connector is its own data source bound by Looker Studio's 5-source-blend cap and 12-hour refresh ceiling ([profile](../competitors/looker-studio.md)).
- **Specific UI:** **"5-source blend cap. Looker Studio limits data blending to five sources per blend… For a merchant blending Shopify + Meta + Google + GA4 + GSC + TikTok + Klaviyo + COGS sheet, the cap is reached fast."** Verbatim ([profile](../competitors/looker-studio.md)). Klaviyo is one of the connectors that competes for the 5-slot budget.
- **Filters:** Whatever the merchant configures.
- **Data shown:** Whatever the Partner connector exposes — varies.
- **Interactions:** Custom report-building.
- **Source:** [looker-studio.md](../competitors/looker-studio.md).

## Visualization patterns observed (cross-cut)

Synthesized count of how Klaviyo revenue sits visually alongside paid spend:

- **Channel row in a unified attribution table:** 6 competitors (Triple Whale's Email & SMS Attribution Dashboard, Polar Acquisition page, Lifetimely Attribution Report, ThoughtMetric Marketing Attribution, Northbeam, Fospha) — Klaviyo is one row among ~10–12 channels, no special treatment.
- **Collapsible/dedicated Klaviyo section in a multi-source dashboard:** 2 competitors (Triple Whale Summary's Klaviyo collapsible block; Polar's Engagement page). Both let the merchant drag/pin Klaviyo metrics into the top KPI strip.
- **KPI tile alongside Meta/Google tiles:** 3 competitors (Triple Whale Summary, Polar Custom Dashboard, Lebesgue Business Report). Lebesgue is the most explicit — it lists Klaviyo *literally inside the parenthesis* with paid platforms: "Ad Spend (Meta/Google/TikTok/Amazon/Klaviyo)".
- **Income-statement / P&L line item:** 1 competitor (Lifetimely Profit Dashboard) — Klaviyo cost descends as a marketing line item alongside Meta/Google ad spend.
- **Contribution-margin folded:** 1 competitor (StoreHero) — Klaviyo email revenue feeds contribution margin alongside paid; no separate viz.
- **Per-campaign profit table:** 1 competitor (Bloom Analytics, Grow tier+) — "Klaviyo email campaign profits" framed as profit-per-campaign.
- **MMM horizontal stacked-bar:** 1 competitor (Fospha) — email is one bar alongside PMax, Direct, Referral.
- **Side-by-side: Klaviyo-reported vs tool-attributed revenue (two columns):** 3 competitors (Triple Whale, Polar, Lifetimely) — explicit disagreement-finding pattern.
- **Top-flows ranked list with status pills:** 1 competitor (Klaviyo's own Home dashboard) — Live / Manual / Draft pills + percent-change vs prior period.
- **Sankey for customer migration into/out of email-engaged segments:** 1 competitor (Klaviyo's RFM analysis card).
- **Per-message embedded analytics in flow canvas:** 1 competitor (Klaviyo only — no third-party tool replicates this).
- **Push-back enrichment dashboard (server events log):** 2 competitors (Triple Whale Sonar Send, Elevar Server Events Log) — operational lens, not revenue.
- **No Klaviyo viz at all:** 5 competitors (Conjura, BeProfit, TrueProfit, Cometly for revenue purposes, Atria, Motion).

Color conventions:
- **Lebesgue: blue for positive, red for negative deltas** — unusual; most use green for positive ([lebesgue.md](../competitors/lebesgue.md)).
- **Klaviyo benchmark badges: "Excellent / Fair / Poor"** — three-state, not green/yellow/red dots ([klaviyo.md](../competitors/klaviyo.md)).
- **Triple Whale: pin emoji 📌** literal in help-center copy for pinning Klaviyo tiles to top ([triple-whale.md](../competitors/triple-whale.md)).
- **Klaviyo's own CLV bar: blue (historic) + green (predicted)** stacked horizontally — relevant analog for any "what email did already" + "what it's projected to do" treatment ([klaviyo.md](../competitors/klaviyo.md)).

Recurring interactions: **attribution-model dropdown** (5 competitors expose this for Klaviyo: Polar, Lifetimely, ThoughtMetric, Northbeam, Lebesgue's Le Pixel); **conversion-window selector** (3 competitors — ThoughtMetric 7/14/30/60/90, Cometly 30/60/90, Klaviyo per-metric custom); **drill-down from channel → campaign/flow → individual message** (3 competitors: Polar, Klaviyo native, Triple Whale).

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Email + paid in one view ("clarity across")**
- "The level of clarity it gives us across Google Ads, Meta, GA4, and Klaviyo is incredible." — Fringe Sport, Shopify App Store, October 28, 2025 ([lebesgue.md](../competitors/lebesgue.md))
- "Brings everything from Shopify to Meta ads into one place...Would recommend for small marketing teams." — Susanne Kaufmann, Shopify App Store, June 2025 ([polar-analytics.md](../competitors/polar-analytics.md))
- "the software is a real bonus to our buisiness…brings eveything together." — Svens Island Australia, Shopify App Store, January 21, 2026 ([triple-whale.md](../competitors/triple-whale.md))

**Theme: Source-disagreement visibility (Klaviyo says X, my pixel says Y)**
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra, July 12, 2024 ([triple-whale.md](../competitors/triple-whale.md))
- "The ability to see (and trust!) our data at a high level gives us peace of mind." — Optimal Health Systems, Shopify App Store, July 2024 ([polar-analytics.md](../competitors/polar-analytics.md))

**Theme: Per-segment/per-message granularity (when you stay inside Klaviyo)**
- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group ([klaviyo.md](../competitors/klaviyo.md))
- "Easy ability to see how much money generated for each email sent, good visibility of who opened emails." — Lee W., Capterra, January 2026 ([klaviyo.md](../competitors/klaviyo.md))

**Theme: Cross-source AI prompts that span email + paid**
- "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports." — Steve R., Capterra ([triple-whale.md](../competitors/triple-whale.md))
- Verbatim Henri sample prompt: *"Analyze the correlation between email-campaign spikes and repeat-purchase revenue."* ([lebesgue.md](../competitors/lebesgue.md))

**Theme: Flow lift via push-back (server-side enrichment to Klaviyo)**
- Marketing claim: **"22% Increase in Klaviyo Flow Revenue"** with Sonar Send ([triple-whale.md](../competitors/triple-whale.md))
- Marketing claim: **"2-3x performance boost for Klaviyo flows via Session Enrichment"** and **"50% or greater increase in product view and add-to-cart events"** ([elevar.md](../competitors/elevar.md))

## What users hate about this feature

**Theme: Klaviyo gets paywalled or partially-implemented**
- "Klaviyo email campaign profits" gated to Grow tier ($40/mo) and above — Bloom Analytics free-tier upgrade trigger is verbatim "Custom COGS / multi-store / **Klaviyo**" ([bloom-analytics.md](../competitors/bloom-analytics.md))
- "All the valuable features come in paid plans" — Digismoothie editorial review summarizing Lebesgue ([lebesgue.md](../competitors/lebesgue.md))
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra (Klaviyo's own paywalled Marketing Analytics tier at +$100/mo) ([klaviyo.md](../competitors/klaviyo.md))

**Theme: Email gets ingested but not surfaced as a first-class lens**
- "Listed as integrations on blog comparison pages but never described as data sources for attribution. Treated as auxiliary connectors rather than first-class lenses (no 'Klaviyo lens' or 'GA4 lens' UI hinted at)." ([trueprofit.md](../competitors/trueprofit.md))
- "**No native Klaviyo revenue attribution** (Klaviyo listed as integration but role is unclear from public docs)." ([beprofit.md](../competitors/beprofit.md))
- "**No Klaviyo as an email/SMS source** — Klaviyo is listed only under 'Forms' (likely form submissions, not email-event ingest)." ([cometly.md](../competitors/cometly.md))

**Theme: Klaviyo absent entirely**
- "**No GSC / no Klaviyo.** Two major sources missing for an SMB ecommerce analytics tool — SEO/search data and email/SMS attribution." ([conjura.md](../competitors/conjura.md))
- Atria, Motion: "No Klaviyo, no email/SMS attribution." ([motion.md](../competitors/motion.md))

**Theme: Tracking conflicts with Klaviyo's own integration**
- "When Shopify launched its own server-side Klaviyo integration, some Elevar customers were not proactively notified that it could conflict with their existing Elevar tracking. **This caused Klaviyo flows to stop for some merchants.**" — ATTN Agency, Elevar Review 2026 ([elevar.md](../competitors/elevar.md))

**Theme: Klaviyo's reporting UX itself is criticised when relied on alone**
- "Reporting is clunky and the UI buries things that should be front and center." — Darren Y., Capterra, April 2026 ([klaviyo.md](../competitors/klaviyo.md))

## Anti-patterns observed

- **Klaviyo as a destination only, not a source.** Cometly lists Klaviyo only under "Forms" — captures form submissions but does not pull email-attributed orders. Merchant gets no email revenue read at all and may falsely conclude email is a small channel ([cometly.md](../competitors/cometly.md)).
- **Klaviyo paywalled at the entry tier.** Bloom Analytics' free tier doesn't include Klaviyo email campaign profits — the upgrade trigger language *names Klaviyo by name*, suggesting demand is high enough to gate it. Pattern of monetising email-revenue visibility creates an empty-state problem for free users ([bloom-analytics.md](../competitors/bloom-analytics.md)).
- **Conversion windows that don't match Klaviyo's defaults.** ThoughtMetric (7/14/30/60/90) and Cometly (30/60/90) don't expose Klaviyo's own 5-day click default — so the tool's "email-attributed revenue" disagrees with what the merchant sees in Klaviyo's own dashboard. None of the profiles surfaced a tool that explicitly *matches* Klaviyo's default conversion window for parity ([thoughtmetric.md](../competitors/thoughtmetric.md), [cometly.md](../competitors/cometly.md), [klaviyo.md](../competitors/klaviyo.md)).
- **Hidden source disagreement.** When Triple Whale showed "3x the amount of users on site vs Klaviyo's metrics" the reviewer praised the transparency. The anti-pattern is the inverse: tools that silently pick *one* number for "email revenue" without showing the disagreement (BeProfit, TrueProfit, StoreHero — Klaviyo numbers folded into a single contribution-margin or "marketing impact" without column-level transparency).
- **Server-side push-back colliding with native Klaviyo integrations.** Elevar's flow-stoppage incident is the cautionary tale ([elevar.md](../competitors/elevar.md)).
- **Aggregating without composition.** Fospha and StoreHero render email as a single "Email" channel bar without exposing flow vs campaign vs SMS — merchant can't tell whether the revenue came from welcome series (always-on) or last week's blast (one-shot), so "email is X% of revenue" is unactionable.
- **Looker Studio's 5-source blend cap.** Adding Klaviyo to a Shopify+Meta+Google+GA4+GSC report exhausts the cap — Klaviyo competes with GSC for a slot. Tool-level constraint that forces merchants to drop a source ([looker-studio.md](../competitors/looker-studio.md)).

## Open questions / data gaps

- **Klaviyo default conversion window matching.** None of the competitor profiles I read explicitly state "we match Klaviyo's 5-day click email default." This may exist in product docs not indexed, or it may not exist at all. Direct verification would require logged-in product tours of Polar / Lifetimely / Triple Whale to inspect their Klaviyo attribution-window controls.
- **Triple Whale Email & SMS Attribution Dashboard (April 2026 beta) UI structure.** The profile references the dashboard exists but layout details are not directly observable — KB pages 403'd to WebFetch ([triple-whale.md](../competitors/triple-whale.md)). Specific column list, drill-down paths, and how it differs from the Sonar Send dashboard not verified.
- **Polar Engagement page UI.** Profile lists it as "Klaviyo email/SMS performance" but does not describe individual visualization types ([polar-analytics.md](../competitors/polar-analytics.md)).
- **Per-flow vs per-campaign drill in Lifetimely's Attribution Report.** Confirmed Klaviyo appears as a channel row but flow-level drill-down depth not described in public sources ([lifetimely.md](../competitors/lifetimely.md)).
- **What StoreHero's "Marketing Reports" actually shows for Klaviyo.** Profile says email is folded into Marketing Reports but no Klaviyo-specific view documented publicly ([storehero.md](../competitors/storehero.md)).
- **Klaviyo SMS attribution windows.** Klaviyo's 1-day SMS default is documented in their own help center but not surfaced in any third-party tool profile — unclear if competitors honor this distinction.
- **Bidirectional sync error handling.** Glew, Polar, Peel all push audiences back to Klaviyo but no profile describes what happens when push fails (silent? alert? retry?). Operational gap.
- **Klaviyo Marketing Analytics ($100/mo) vs Advanced KDP ($500/mo) — overlap with third-party tools.** Whether merchants who already have Triple Whale / Polar are still paying for Klaviyo's analytics add-on isn't surfaced in any profile.

## Notes for Nexstage (observations only — NOT recommendations)

- **Klaviyo is treated as a channel row in 6/21 competitors examined and as a dedicated section in only 2.** The dedicated-section pattern (Triple Whale's collapsible Klaviyo block on Summary, Polar's Engagement page) is associated with positive reviews about "clarity"; the channel-row pattern is more common but less remembered in user feedback.
- **Lebesgue literally bundles Klaviyo with paid platforms in their metric label** — "Ad Spend (Meta/Google/TikTok/Amazon/Klaviyo)" — even though Klaviyo is owned-channel. Suggests SMB merchants think of Klaviyo as another marketing-cost-and-revenue node, not as a distinct category. Worth comparing against Nexstage's channel-mapping taxonomy in `ChannelMappingsSeeder.php`.
- **No competitor profile observed publishes "we match Klaviyo's 5-day click conversion window."** This is the cleanest user-facing answer to the original question ("attribution-window matching to Klaviyo's defaults") and there's no documented precedent — a defensible Nexstage transparency angle if the source-badge pattern surfaces "Email: 5d-click (Klaviyo default)" as legible metadata.
- **Side-by-side Klaviyo-reported vs tool-attributed revenue exists in 3 competitors (Triple Whale, Polar, Lifetimely)** and the Triple Whale reviewer quote ("3x the amount of users on site vs Klaviyo's metrics") is the strongest verbatim endorsement of source-disagreement-as-feature in the entire dataset. Direct analog to Nexstage's Real / Store / Facebook / Google / GSC / GA4 lens — Klaviyo could be a 7th badge or fold into a separate "Email" lens.
- **Push-back enrichment (Triple Whale Sonar Send, Elevar Session Enrichment) is the closed-loop pattern.** Both publish numeric lift claims (22% Klaviyo flow revenue increase; 2-3x performance boost). Nexstage's current scope appears to be read-only — push-back is a category-defining capability where competitors are racing.
- **Klaviyo's own per-message embedded analytics sidebar (in Flow Builder)** is the strongest "analytics-where-you-edit" pattern observed and exists nowhere else. Nexstage doesn't ship a flow builder, so this is informational — but the principle (analytics scoped to the artifact) translates to ad-creative review and campaign editors.
- **Conversion-window mismatch is a quiet anti-pattern.** Several tools force Klaviyo into 7-day or 30-day windows that diverge from Klaviyo's 5-day click default. Merchants comparing Nexstage's "Email" channel revenue to their Klaviyo dashboard will see disagreement; surfacing the window explicitly (per CLAUDE.md "metricSources" pattern) is a transparency lever.
- **Klaviyo paywalls its own analytics ($100/mo Marketing Analytics, $500/mo Advanced KDP).** Even Klaviyo, which owns the data, doesn't bundle advanced cohort/RFM/CLV in the base plan. Suggests willingness-to-pay for email-revenue analytics depth is real — and that Nexstage including Klaviyo first-class undercuts a substantial Klaviyo-paywall arbitrage.
- **Bidirectional Klaviyo (push audiences back) is shipped by Polar, Peel, Glew, Daasity, Triple Whale.** Read-only Klaviyo is a feature gap that 5+ competitors close. Worth tracking against the `WorkspaceContext` job pattern — pushing a Nexstage-built segment to Klaviyo would need workspace-scoped Klaviyo auth and audience-CRUD permissions.
- **Cometly's "Klaviyo as Forms only" trap is instructive.** It's possible to "list Klaviyo as integration" without actually pulling email-attributed revenue — Nexstage marketing should not list Klaviyo as a logo unless the integration is genuinely first-class (revenue read, not just form submissions or webhook).
- **The 22% / 2-3x flow-lift numbers from Triple Whale and Elevar are the marketing benchmark to beat.** If Nexstage ever pushes server-side events to Klaviyo, those are the published targets ([triple-whale.md](../competitors/triple-whale.md), [elevar.md](../competitors/elevar.md)).
- **Klaviyo's six-bucket RFM (Champions / Loyal / Recent / Needs Attention / At Risk / Inactive) and the Sankey migration diagram are category conventions.** When email-revenue analysis crosses into customer segmentation, Klaviyo's vocabulary is the default merchants already know ([klaviyo.md](../competitors/klaviyo.md)).
- **The Lifetimely income-statement framing puts Klaviyo cost on the same vertical line-item ladder as Meta/Google ad spend.** This is structurally different from a KPI tile grid and may be a stronger CFO-facing answer to "is email cheaper than paid?" ([lifetimely.md](../competitors/lifetimely.md)).
- **Bloom Analytics' "Klaviyo email campaign profits" framing (profit, not revenue, per Klaviyo campaign)** is the most cleanly profit-first treatment of Klaviyo observed. Treats sends as a cost and attributed revenue as an offset — directly compatible with Nexstage's `cost-config` thesis if email-send cost ever gets a config bucket ([bloom-analytics.md](../competitors/bloom-analytics.md)).
