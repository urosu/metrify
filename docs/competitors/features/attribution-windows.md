---
name: Attribution windows
slug: attribution-windows
purpose: Lets the merchant see and control the lookback / view-through window applied to ad-driven revenue, so they understand which sales each "ROAS" or "CAC" number is actually counting.
nexstage_pages: ads, performance, attribution-comparison, dashboard
researched_on: 2026-04-28
competitors_covered: triple-whale, polar-analytics, hyros, cometly, wicked-reports, northbeam, adbeacon, klaviyo, rockerbox, ga4, thoughtmetric, segmentstream, lifetimely, conjura, elevar, fospha
sources:
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/hyros.md
  - ../competitors/cometly.md
  - ../competitors/wicked-reports.md
  - ../competitors/northbeam.md
  - ../competitors/adbeacon.md
  - ../competitors/klaviyo.md
  - ../competitors/rockerbox.md
  - ../competitors/ga4.md
  - ../competitors/thoughtmetric.md
  - ../competitors/segmentstream.md
  - ../competitors/lifetimely.md
  - ../competitors/conjura.md
  - ../competitors/elevar.md
  - ../competitors/fospha.md
  - https://docs.northbeam.io/docs/attribution-windows
  - https://www.wickedreports.com/the-attribution-time-machine
  - https://www.wickedreports.com/funnel-vision
  - https://thoughtmetric.io/marketing_attribution
---

## What is this feature

The attribution-window feature is the small but load-bearing setting that says "when an ad is given credit for a sale, how long before that sale could the click (or view) have happened, and does the *view* count at all?". For an SMB merchant this is the single biggest reason that the ROAS Meta shows them differs from the ROAS their store report shows them: Meta defaults to 7-day-click + 1-day-view, Shopify reports last-touch in-session, GA4 attributes across a 90-day data-driven model. The number the merchant trusts depends entirely on the window they don't know they picked.

"Having data" here is universal — every ad platform and every analytics tool has *some* window — but "having this feature" means three concrete things: (1) the window is *visible* (a label like "[7d]" on the metric, or a global selector at the top of the page), (2) the window is *changeable* (the merchant can switch from 7d-click to 30d-click and watch the number move), and (3) the click-only vs view-through choice is *exposed* rather than buried in a model definition. The competitors that get this right turn an opaque platform default into a knob the merchant can defend in front of their CFO.

## Data inputs (what's required to compute or display)

For each input, name the source + the specific field/event:

- **Source: Meta Ads API** — `attribution_setting` per campaign (`1d_view`, `7d_click`, `7d_click_1d_view`, `7d_click_7d_view`), `actions[].action_type=purchase`, `action_values[].value`, `action_attribution_windows[]` array
- **Source: Google Ads API** — `conversion_action.attribution_model` enum (LAST_CLICK, DATA_DRIVEN, etc.), `conversion_action.click_through_lookback_window_days`, `conversion_action.view_through_lookback_window_days`
- **Source: TikTok Ads API** — campaign `attribution_event_count` config (1d/7d/14d/28d click; 1d/7d view); `conversions` keyed to those windows
- **Source: First-party pixel / store events** — `click_id` (fbclid / gclid / ttclid), `landing_timestamp`, `purchase_timestamp`, `customer_id`, full `touchpoint[]` array per visitor (used to apply *any* lookback retroactively)
- **Source: Shopify / Woo orders** — `orders.id`, `orders.created_at`, `orders.customer_id`, `orders.landing_site_ref`, `orders.referring_site` (used to anchor the conversion side of a click→sale pairing)
- **Source: GA4** — `Admin > Attribution settings`: `acquisition_lookback_window` (7d / 30d), `key_event_lookback_window` (30d / 60d / 90d)
- **Source: User-input** — `attribution_default_window_days` (workspace setting), `attribution_default_model` (last-click / first-click / linear / position / time-decay), `view_through_enabled` (boolean), per-channel overrides on each
- **Source: Computed** — `attributed_revenue[window][model] = SUM(orders WHERE touchpoint_within(window) AND model_credit > 0) × model_credit`
- **Source: Computed** — `cohort_lookforward` for LTV-aware attribution (Wicked-style "rebill re-attribution"): a campaign's effective ROAS at acquisition_month + N is recomputed as cohort revenue accumulates

## Data outputs (what's typically displayed)

- **KPI: Attributed revenue [window]** — `SUM(attributed_revenue WHERE window=<picked>)`, USD, vs prior-period delta. Suffix the window onto the metric label (e.g. "Revenue [7d-click]")
- **KPI: ROAS [window]** — `attributed_revenue / spend`, ratio, computed on the fly. Suffix: "ROAS [7d]", "ROAS [28d]", "ROAS [LTV]"
- **KPI: CAC [window]** — `spend / new_customers_within(window)`, USD per customer
- **Dimension: Attribution model** — string, ~5-9 distinct values (last-click, first-click, linear, position-based, time-decay, data-driven, full-impact)
- **Dimension: Window** — string, typically {1d, 3d, 7d, 14d, 28d, 30d, 60d, 90d, infinite/LTV}
- **Dimension: View-through on/off** — boolean toggle; or an explicit "click only / click+view" radio
- **Breakdown: Spend × revenue × window × channel** — table with columns suffixed by window (Northbeam pattern)
- **Breakdown: Side-by-side platform vs Real (or Pixel)** — two columns per metric, one keyed to platform's window, one to merchant-picked window
- **Slice: Cohort lookforward** — for LTV/Wicked-style: the ROAS of a campaign as the cohort it acquired matures over 30/60/90 days

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Attribution / Pixel Dashboard (top-level nav). Also a per-metric tile setting on the Summary Dashboard.
- **Visualization:** Side-by-side table, one row per channel, columns paired (Triple-Pixel-attributed vs Meta-reported vs first-click vs last-click). An attribution-model selector dropdown sits above the table.
- **Layout (prose):** Top: date-range + store-switcher + on-demand refresh button. Body: channel breakdown table with paired columns for Triple Pixel attribution, platform-reported, first/last-click, and "Total Impact" (Advanced+). The attribution-model selector reflows the table in place.
- **Specific UI:** Attribution-model dropdown reflows channel revenue numbers; per-tile "edit" reveals the lookback config; older blog reference states "look back up to 60 days, with 180 days planned" while Triple Pixel on paid plans markets "unlimited lookback."
- **Filters:** Date range, channel grouping, attribution model, store.
- **Data shown:** Spend, attributed revenue, ROAS, CPA per channel for each model column.
- **Interactions:** Switching the attribution lens reflows the channel revenue numbers; click into a channel to drill to campaign/adset/ad with the window applied.
- **Why it works (from reviews/observations):** "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra (triple-whale profile). Reviewers consistently call out that the side-by-side platform-vs-pixel column is what makes the window choice legible.
- **Source:** ../competitors/triple-whale.md (Attribution / Pixel Dashboard section); KB articles 403'd to WebFetch so column-count exact verification not available.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Acquisition / Attribution surface, top-level. Lookback selectable per attribution model.
- **Visualization:** Side-by-side columnar comparison — three columns (platform-reported, GA4, Polar Pixel) for the same KPI on the same window — with a 9-10-option attribution-model dropdown above.
- **Layout (prose):** Top: attribution-model picker. Main: three-column compare per channel row. Drill-through: click an order → multi-touchpoint customer journey timeline that led to it.
- **Specific UI:** Attribution-model dropdown exposes 9-10 named models (First Click, Last Click, Linear, U-Shaped, Time Decay, Paid Linear, Full Paid Overlap, **Full Paid Overlap + Facebook Views**, Full Impact). The "+ Facebook Views" variant is the explicit view-through switch — surfaced as a model name rather than a separate toggle. Order-level drill-down shows full touchpoint sequence; lookback is "selectable per attribution model" but specific values not publicly enumerated.
- **Filters:** Date range, View (saved-filter bundle including currency + region), attribution model.
- **Data shown:** Spend, attributed revenue, ROAS, CAC, conversions per model — with platform/GA4/Polar columns.
- **Interactions:** Switch attribution model from a dropdown; the same KPI block re-renders. Drill from channel → campaign → ad → order → customer journey.
- **Why it works (from reviews/observations):** "Their multi-touch attribution and incrementality testing have been especially valuable for us." — Chicory, Shopify App Store, Sept 2025 (polar-analytics profile). The order-level drill-down is the trust-builder: users can see the actual touchpoint sequence behind a model's number.
- **Source:** ../competitors/polar-analytics.md; https://www.polaranalytics.com/post/attribution-models-shopify-brands

### Hyros ([profile](../competitors/hyros.md))
- **Surface:** Reports page (sidebar > Reports), with attribution-model dropdown inline.
- **Visualization:** Multi-pivot table report; attribution-model dropdown changes the credit allocation in place. A separate "Reporting Gap" widget on the Dashboard shows the delta between Hyros and the platform.
- **Layout (prose):** Sidebar nav. Reports page is a campaign/adset/ad/landing page/source pivot table styled after Facebook Ads Manager (per the 2025-2026 redesign). Attribution-model dropdown sits inline with date filter; saved-report tabs at top.
- **Specific UI:** Attribution-model dropdown options: Last click / First click / Linear / Time decay / Scientific Mode / Custom (user-weighted). Long-window support is a core selling point — "from first ad click to the 1000th touch", multi-week and multi-month windows in coaching funnels. Tracking explicitly "starts from install day" (no historical backfill — a structural caveat to any window choice).
- **Filters:** Campaign, adset, ad, landing page, source, date range, attribution model, customer segment.
- **Data shown:** Spend, impressions, clicks, attributed revenue, ROAS, leads, calls booked/closed, AOV, LTV (cohort-bound), refund-adjusted ROAS — all keyed to the picked model + window.
- **Interactions:** Drag-and-drop column configuration; switch model via dropdown; row → drill into single-customer Lead Journey ("Deep Mode") showing every click, page visit, email open, call across the lookback window.
- **Why it works (from reviews/observations):** "We receive pitches from competitors monthly, but no tool matches HYROS data quality." — Pummys, Trustpilot via smbguide.com (hyros profile). The Reporting Gap widget is the simplified expression of the window-truth disagreement that Wicked also builds around.
- **Source:** ../competitors/hyros.md (Dashboard, Reports, Lead Journey sections). UI screenshots not directly observable — Hyros has no free trial.

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** AI Ads Manager (primary surface, sidebar). Attribution model and conversion-window selectors are inline, in-table.
- **Visualization:** Tabular ad-account view with rows for campaigns / adsets / ads. The model and window selectors are dropdown controls *inside* the table chrome, not on a separate page.
- **Layout (prose):** Multi-platform unified rows (Meta + Google + TikTok + LinkedIn in one table). Customizable column picker, attribution-model dropdown, conversion-window selector, custom-metric builder, AI chat panel embedded in the same screen.
- **Specific UI:** Attribution-model dropdown: First Touch / Last Touch / Linear / U-Shaped / Time Decay. Conversion-window selector with explicit options "30, 60, and 90-day performance periods" (per the Attribution feature page). Switching the model redistributes credit live in the same table.
- **Filters:** Date range, attribution model, conversion window, ad account, custom-metric formula filters.
- **Data shown:** Spend, impressions, clicks, conversions, Cometly-attributed revenue, ROAS, CPA, custom metrics — for the picked window and model.
- **Interactions:** In-place model/window swap; drill from campaign → adset → ad → creative; bulk write-back to platform (pause, scale, change budget).
- **Why it works (from reviews/observations):** "I am very impressed with the tracking accuracy. I would say it tracks about 90% of my orders, sometimes more, whereas Facebook tracks maybe 50%." — Leo Roux, Petsmont (cometly profile). The in-table swap turns a settings-page concept into a live exploration tool.
- **Source:** ../competitors/cometly.md; https://www.cometly.com/features/attribution

### Wicked Reports ([profile](../competitors/wicked-reports.md))
- **Surface:** Top-level "Attribution Time Machine" feature underlying every report; FunnelVision feature for two-source compare.
- **Visualization:** Per-customer click-history timeline (Time Machine) tied to OrderID and email — plus a sidebar/toggle "Customized Meta View-Through Conversion Impact" slider on FunnelVision.
- **Layout (prose):** FunnelVision: TOF / MOF / BOF segmentation columns with side-by-side **Wicked-attributed ROAS vs Facebook-reported ROAS** per campaign. A "Cold Traffic" tag fires for conversions occurring more than 7 days before sale. Lookback and view-through-impact are on-the-fly slider/toggle controls.
- **Specific UI:** **Infinite lookback / lookforward** explicitly contrasted against Facebook's 7-day default. View-through impact is a user-tunable slider — "Customized Meta View-Through Conversion Impact" — that *inflates or deflates* ROAS/CAC numbers based on how much credit the merchant chooses to give views. Six attribution models exposed (First Click, Last Click, Linear, Full Impact, New Lead, ReEngaged Lead). One-click model swap: "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time." (marketingtoolpro.com).
- **Filters:** Date range, lookback window, view-through impact, attribution model, source/campaign/ad/email/targeting.
- **Data shown:** ROAS (Wicked-attributed), ROAS (Facebook-reported), spend, conversions, CAC at each funnel stage, cold-traffic ROAS — all responsive to the lookback + view-through settings.
- **Interactions:** Adjust lookback slider; toggle view-through impact; switch model; click customer record to see full Time Machine timeline with click timestamps, OrderID match, email-profile match.
- **Why it works (from reviews/observations):** "When we use the iOS tracking, we often miss data and it leads us to turning off marketing campaigns. However, with Wicked Reports, the multiple attribution data solves the missing data and we're able to see there were sales made." — Michelle P, Agency Owner, smbguide.com (wicked-reports profile).
- **Source:** ../competitors/wicked-reports.md (Attribution Time Machine + FunnelVision sections); https://www.wickedreports.com/the-attribution-time-machine

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Sidebar > Attribution. Global filter strip at top of every Attribution surface for window + model + accounting mode.
- **Visualization:** Table with **windowed metric columns** ("Attributed Rev (1d)", "ROAS (7d)", "LTV CAC") — the window is appended into the column header itself, not held in a separate selector. A dedicated Model Comparison Tool puts seven models side-by-side.
- **Layout (prose):** Top: global filter strip (date, attribution model, attribution window, accounting mode). Body: channel/campaign/adset/ad rows with metric columns whose headers carry the window suffix. The same metric (e.g. "Attributed Rev") can appear multiple times in the same table at different windows.
- **Specific UI:** Window picker (Accrual mode only) exposes **1d, 3d, 7d, 14d, 30d, 90d Click**, plus combined click/view variants like **"7-Day Click / 1-Day View"**, plus **LTV (infinite)**. Cash Snapshot mode does not support windowed attribution. Suffix-on-metric-name is the dominant UI convention here — column headers literally read "Attributed Rev (1d)", "ROAS (7d)", "LTV CAC".
- **Filters:** Single attribution model + window + accounting mode via global filter strip; date; channel; new vs returning lens.
- **Data shown:** Spend, Attributed Rev (windowed), Transactions, New Customer %, ROAS, CAC, MER, ECR, Visits, % New Visits, CPM, CTR, eCPC, eCPNV — each as Blended / New / Returning variant.
- **Interactions:** Change global window → entire page recomputes. Drill from channel → campaign → adset → ad. Open Model Comparison Tool to see seven models stacked.
- **Why it works (from reviews/observations):** "Seven attribution models exposed simultaneously, plus a dedicated Model Comparison Tool that puts them side-by-side with platform self-reporting" — direct support for "don't pick one truth" thinking (northbeam profile, unique strengths).
- **Source:** ../competitors/northbeam.md; https://docs.northbeam.io/docs/attribution-windows

### AdBeacon ([profile](../competitors/adbeacon.md))
- **Surface:** Main attribution dashboard + Chrome extension overlay inside Meta Ads Manager.
- **Visualization:** Side-by-side AdBeacon vs Meta-reported numbers in a comparison table; Chrome extension injects AdBeacon-attributed columns directly into Meta's native ad-set/ad table.
- **Layout (prose):** Hero positioning is "click-only, server-side validated" — view-through attribution is *deliberately omitted* and framed as "inflated metrics." Attribution-model toggle is inline in the Chrome extension at Ad Set + Ad level.
- **Specific UI:** Four attribution models (First-Click, Last-Click, Linear, Full Impact) — **no view-based variant offered**. This is the single sharpest "click-only vs view-through" stance in the dataset. Custom model creation referenced. Lookback windows not explicitly published, but "session continuity beyond 30 minutes" claim implies they extend beyond Meta's default; Tether docs reference "modified last-click attribution model".
- **Filters:** Date, model, channel.
- **Data shown:** Spend, AdBeacon-attributed revenue, Meta-reported revenue, ROAS gap, conversions.
- **Interactions:** In-platform model toggle inside the Chrome extension; in-app side-by-side compare; Tether (server-side click ID enrichment) feeds Meta CAPI.
- **Why it works (from reviews/observations):** AdBeacon profile notes the click-only stance is a "transparency play — they explicitly reject view-based attribution as 'inflated metrics' and frame click-only as the more honest model." (adbeacon profile, unique strengths).
- **Source:** ../competitors/adbeacon.md.

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Per-metric conversion-window setting on Email/SMS plans; broader "flexible attribution settings" gated to the Marketing Analytics add-on.
- **Visualization:** Settings field in the metric configuration panel — not a dashboard surface in its own right. Attributed revenue then flows through into campaign / flow / segment reports.
- **Layout (prose):** Per-metric configuration: each conversion metric (e.g. "Placed Order") has a `conversion_window` field. Marketing Analytics add-on unlocks "flexible attribution settings that apply retroactively" — meaning a window change recomputes historical reporting.
- **Specific UI:** "Customizable conversion window per metric" (klaviyo profile). The retroactive-recalc-on-change behavior is explicitly documented in the Marketing Analytics add-on; without that add-on the window is fixed per metric.
- **Filters:** Per-metric only — not a global page-level toggle.
- **Data shown:** Email/SMS-attributed revenue per campaign / flow / segment, keyed to the window per metric.
- **Interactions:** Edit metric → set conversion window → reports recompute.
- **Why it works (from reviews/observations):** No verbatim user quotes specifically about windows in the klaviyo profile; the feature is more "exposed plumbing" than headline UX.
- **Source:** ../competitors/klaviyo.md (Attribution windows line in Source: Shopify section).

### Rockerbox ([profile](../competitors/rockerbox.md))
- **Surface:** Cross-Channel Attribution Report (Attribution tab, primary cross-channel surface).
- **Visualization:** Attribution-model toggle row above a comparison table with three named sub-views: Platform-Reported Performance, Rockerbox De-duplicated View, Conversion Comparison.
- **Layout (prose):** "Customize what you see by toggling between attribution models and filtering by customer attributes." Time Period Comparison feature pairs the same view across two date ranges.
- **Specific UI:** Five-option attribution-model toggle (modeled multi-touch / even weight / last touch / first touch / full credit). Lookback windows configurable but specific values **not published**. Doc-level note: new customers see "artificially shortened conversion timeframes initially, as historical touchpoints may not be captured before implementation."
- **Filters:** Attribution model toggle, customer-attribute filter (new vs repeat), date.
- **Data shown:** Spend, impressions, clicks, conversions (platform-reported), conversions (Rockerbox-modeled), CPA, ROAS — with model-keyed columns.
- **Interactions:** Switch model in-place; compare time periods; column customization; drill from channel down to ad.
- **Why it works (from reviews/observations):** No verbatim attributable quotes — Rockerbox profile notes the page structure is the trust-builder rather than any specific copy.
- **Source:** ../competitors/rockerbox.md.

### GA4 ([profile](../competitors/ga4.md))
- **Surface:** Admin > Attribution settings (configuration); Advertising > Model Comparison report (visualization).
- **Visualization:** Configuration-page form fields for the windows; Model Comparison report shows conversions + revenue + delta % per model side-by-side.
- **Layout (prose):** Admin > Attribution settings is a settings panel, not a dashboard. The Model Comparison report renders a table with one row per channel, columns for each selected model, plus a delta-percentage column.
- **Specific UI:** Configurable acquisition lookback (**7d / 30d**) and key-event lookback (**30d / 60d / 90d**, depending on event type). Attribution-model picker reduced from 7 (Universal Analytics) to 3 in 2023: **Data-Driven (DDA) / Paid+organic last click / Google paid channels last click**. DDA requires 400+ key-event conversions and 20,000+ total in the lookback to actually run — most SMBs silently fall back. First-click, linear, time-decay, position-based were removed from the picker.
- **Filters:** Date range; model selector at the report level (one toggle re-renders the entire page, not per-KPI).
- **Data shown:** Conversions count + Revenue per model + delta %.
- **Interactions:** Switch model at report level (page-scoped); change windows in Admin (workspace-scoped).
- **Why it works (from reviews/observations):** Mostly negative — "Attribution model picker reduced from 7 (UA) to 3 (DDA / Paid-organic last click / Google last click) — first-touch, linear, time-decay, and position-based were removed in 2023." (ga4 profile, unique weaknesses). The strict requirement gate for DDA is a recurring user complaint.
- **Source:** ../competitors/ga4.md (Source: Shopify section + Attribution models section).

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Top-level Analytics > Attribution.
- **Visualization:** Channel-level performance table with two adjacent dropdowns above it — attribution-model picker + attribution-window picker.
- **Layout (prose):** Channel rows include "Meta Ads, TikTok, Pinterest, Google Ads, Bing, organic social, email/SMS, podcasts, influencers, affiliates, and UTM-based custom channels." Attribution model and window are *both* exposed as inline selectors.
- **Specific UI:** Five attribution models with explicit definitions: **Multi-Touch (default, proprietary data-driven), First Touch, Last Touch, Position Based (40/40/20), Linear Paid** (excludes organic — uncommon). Window picker explicitly enumerated: **7 / 14 / 30 / 60 / 90 days**. Post-purchase survey signal feeds the default Multi-Touch model directly.
- **Filters:** Attribution model, lookback window (7/14/30/60/90), date, channel.
- **Data shown:** Spend, ROAS, MER, attributed Sales, attributed Orders, channel breakdown.
- **Interactions:** Switch attribution model, change lookback window, drill from channel into campaign/adset/ad (drill UI not directly observable).
- **Why it works (from reviews/observations):** "The Founder himself responded very quickly to my questions" — Poseidon Animal Health (thoughtmetric profile). Explicit definitions next to each model is itself the differentiator vs GA4's terse picker.
- **Source:** ../competitors/thoughtmetric.md; https://thoughtmetric.io/marketing_attribution

### SegmentStream ([profile](../competitors/segmentstream.md))
- **Surface:** Top nav > Attribution. Configurable table of metrics × dimensions.
- **Visualization:** Configurable table; users select an attribution model and dimensions; date range is global at the top.
- **Layout (prose):** Top: global date range. Body: configurable columns per metric. Doc-level concept of "Metric approximation" surfaced in the Attribution doc — implies the UI shows approximate/predicted vs observed values for late-maturing conversions. A separate "Cross-channel attribution + maturation curve" view shows Observed vs Projected cumulative conversions over 42 days with confidence band, plus a maturation timeline ("Last 7d: 53%, +1 week: 78%, +2 weeks: 95%, +3 weeks: 99%").
- **Specific UI:** Attribution models: First Click / Last Click / Multi-Touch / **Visit Scoring** (their ML model). Windows configurable per model. Supports "click-time revenue attribution" (revenue assigned at click time, not just conversion time) — an unusual attribution-time definition.
- **Filters:** Date, model, dimensions (channel, campaign, source, medium, custom dimensions), include/exclude data filters.
- **Data shown:** Spend, clicks, impressions, conversions, attributed revenue, ROAS — broken down by selected attribution model.
- **Interactions:** Custom dimensions definable; export to CSV; external BI tools can read the same model.
- **Why it works (from reviews/observations):** No verbatim quotes available (G2 paywalled). The maturation-curve view is the distinctive piece — it teaches the merchant that today's "last 7d" number is only 53% of what the campaign will eventually be credited.
- **Source:** ../competitors/segmentstream.md.

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Marketing Reports surface (channel-level attribution).
- **Visualization:** Tabular layout with channel rows; per-row pixel-attributed revenue rendered as a comparison column alongside platform-reported revenue.
- **Layout (prose):** Channel rows. Anomaly-detection alerts surface performance spikes/drops. Per the 1800DTC breakdown, the screen offers a "first-click vs last-click" toggle.
- **Specific UI:** Attribution-model toggle: **first-click vs last-click** only — narrow vs Polar/Northbeam. Cohort-side windows are richer: cohorts can be weekly / monthly / yearly; LTV horizons standardised at **3, 6, 9, and 12 months**; repurchase rates exposed at **90d and 180d** in benchmarks.
- **Filters:** Date range, attribution-model toggle (first/last), channel.
- **Data shown:** Reported revenue per channel, spend, CPC, CAC, ROAS, plus pixel-attributed revenue as a comparison column.
- **Interactions:** Toggle first/last; date range; click channel for drill (UI details not available).
- **Why it works (from reviews/observations):** Lifetimely's strength is cohort/LTV; the attribution surface is comparatively spartan and not the headline differentiator.
- **Source:** ../competitors/lifetimely.md.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Campaign Deepdive Dashboard (multi-platform comparison view).
- **Visualization:** Side-by-side display of two attribution columns per metric — **Last Click** (Conjura's session-based) vs **Platform Attributed** (passes through what Meta/Google/TikTok report).
- **Layout (prose):** Multi-platform comparison spanning Google, Meta, TikTok, Bing, Pinterest. Hierarchical drill: Campaign → Ad Group → Ad. Centerpiece is the "KPI Scatter Chart" plotting two ratio metrics in a 2D scatter to surface outliers.
- **Specific UI:** Two-column compare per KPI: "Last Click Conversions / Last Click Revenue / Last Click Conversion Rate / Last Click ROAS" alongside "Platform Attributed Conversions / Platform Attributed Revenue / Platform Conversion Rate / Platform ROAS." Lookback window not published. SKU-level ad-spend attribution is done via the URL of the ad — falls into "Ad Spend - No Product" bucket when the ad lands on a generic homepage.
- **Filters:** Campaign, region, product category.
- **Data shown:** Ad Spend, Impressions, CPM, Clicks, CTR, CPC, Customers Acquired, Last Click Conversions/Revenue/Conversion-Rate/ROAS, Platform Attributed Conversions/Revenue/Conversion-Rate/ROAS.
- **Interactions:** Filter, drill from chart point to specific campaign.
- **Why it works (from reviews/observations):** Two-column compare is the trust-builder; reviewers do not specifically quote the window-feature but the side-by-side is a recurring praised pattern (conjura profile).
- **Source:** ../competitors/conjura.md.

### Elevar ([profile](../competitors/elevar.md))
- **Surface:** Attribution Feed (data-layer surface, not a BI dashboard).
- **Visualization:** Single data-layer feed exposing First Touch + Last Touch UTMs per order. No dashboard model-switcher.
- **Layout (prose):** Per-order feed with First Touch UTMs (source/medium/campaign), Last Touch UTMs, Last Touch Organic Referrer (when no UTMs), revenue, order count.
- **Specific UI:** **Single-touch only** — Elevar explicitly excludes Data-Driven and other multi-touch models, deferring those to GA4. First Touch comes from Shopify's first-touch server-side cookie; Last Touch from Elevar's data layer ("always resets to the latest set of UTM params used on an inbound link"). Server-set cookie has a **1-year window**, explicitly contrasted with browser cookies' 7-day expiry.
- **Filters:** Date, source, medium.
- **Data shown:** First/Last Touch UTMs, revenue, order count.
- **Interactions:** Read-only feed; consumed by downstream analytics tools.
- **Why it works (from reviews/observations):** Elevar's value is the *plumbing* (1-year cookie, accurate UTMs) feeding *other* tools' attribution windows. Doc disclaimer captured verbatim: "not meant to be a replacement for Google Analytics" and "excludes first-touch organic referrals and alternative attribution models like Data-Driven attribution." (elevar profile).
- **Source:** ../competitors/elevar.md.

### Fospha ([profile](../competitors/fospha.md))
- **Surface:** Core dashboard (MMM-style, Bayesian).
- **Visualization:** Saturation curves and ad-stock decay charts; no explicit attribution-window selector.
- **Layout (prose):** Bayesian saturation curves and ad-stock decay handle "lookback" implicitly — there is no user-facing "pick 7d vs 30d" knob.
- **Specific UI:** Attribution windows **not surfaced in marketing pages** — Bayesian saturation curves and ad-stock decay handle "lookback" implicitly. Last-click vs Fospha credit comparison shown in marketing imagery.
- **Filters:** Date range, channel.
- **Data shown:** Impression-led attribution credit, ad-level MER vs paid-ROAS targets, blended ROAS, halo contribution to marketplace.
- **Interactions:** No window toggle; the model itself absorbs lookback decisions.
- **Why it works (from reviews/observations):** Fospha's pitch is "you don't need to pick a window; the Bayesian model decays credit naturally." Window-as-a-knob is intentionally absent.
- **Source:** ../competitors/fospha.md.

## Visualization patterns observed (cross-cut)

Synthesized count by viz type for the surface that *exposes* the window/model:

- **Side-by-side two-source compare table** (platform-reported vs tool-attributed columns): 6 competitors — Triple Whale, Polar Analytics (3-column), Wicked Reports (FunnelVision), Conjura (Last Click vs Platform Attributed), Lifetimely, AdBeacon. **Most common pattern.** Reviews praise it as the trust-builder ("3 times the amount of users on site vs Klaviyo's metrics").
- **Suffix-on-metric-name (e.g. "ROAS [7d]")**: 1 competitor — Northbeam ("Attributed Rev (1d)", "ROAS (7d)", "LTV CAC" literally in column headers). Most others hold the window in a global selector and label the metric generically.
- **Inline dropdown in pivot table** (model + window selectors right inside the metrics table): 4 competitors — Cometly (in the AI Ads Manager rows), Hyros (Reports page), Northbeam (global filter strip), ThoughtMetric (above the channel table).
- **Settings-page-only window** (window changeable via Admin/config, not page-level): 2 competitors — GA4 (Admin > Attribution settings), Klaviyo (per-metric conversion window).
- **Slider for view-through impact**: 1 competitor — Wicked Reports ("Customized Meta View-Through Conversion Impact" — a continuous knob, not a binary toggle). Wicked is the only one that lets the merchant *dial how much* views inflate the number rather than just on/off.
- **Per-customer click timeline** (the audit trail behind whatever window is picked): 4 competitors — Wicked Reports (Time Machine), Hyros (Lead Journey "Deep Mode"), Polar Analytics (order-level drill-down), Cometly (Customer Journeys / Conversion Profiles). Strong correlation with positive trust-related reviews.
- **No window selector at all** (model decides lookback implicitly): 1 competitor — Fospha (Bayesian saturation curves + ad-stock decay).
- **Click-only stance, no view-through option**: 1 competitor — AdBeacon (deliberate omission, "inflated metrics" framing).
- **Dropdown of named models that bake in view-through as a model variant**: 1 competitor — Polar Analytics ("Full Paid Overlap + Facebook Views" is a separate model rather than a toggle on another model).

Recurring conventions: window values cluster on **1d / 3d / 7d / 14d / 28d / 30d / 60d / 90d**, plus an "infinite/LTV" option that 3 competitors expose (Northbeam LTV-CAC, Triple Pixel "unlimited lookback", Wicked Time Machine "infinite lookback / lookforward"). View-through, when offered, is most often a *named model variant* (Polar) or a *combined window* like "7-Day Click / 1-Day View" (Northbeam) — not a separate boolean control.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Side-by-side disagreement is the trust-builder, not the unified number**
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra (triple-whale profile)
- "I am very impressed with the tracking accuracy. I would say it tracks about 90% of my orders, sometimes more, whereas Facebook tracks maybe 50% (but mostly it attributes sales to the wrong ad sets!)." — Leo Roux, Founder, Petsmont (cometly profile)
- "When we use the iOS tracking, we often miss data and it leads us to turning off marketing campaigns. However, with Wicked Reports, the multiple attribution data solves the missing data and we're able to see there were sales made." — Michelle P, Agency Owner (wicked-reports profile)

**Theme: One-click model swap inside the table**
- "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time." — reviewer, marketingtoolpro.com (wicked-reports profile)
- "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports." — Steve R., Marketing Manager, Capterra, July 12, 2024 (triple-whale profile)

**Theme: Long-window LTV-aware attribution**
- "Wicked Reports allows us to optimize Facebook ads to those with the highest ROI and just not the cheapest lead. Nothing else on the market remotely compares. It's a total game changer!" — Ralph Burns, Tier 11 CEO (wicked-reports profile)
- "We found it hard to rely on FB or Google ad platforms to accurately measure ROI since we had longer sales cycles. Wicked Reports offered us more accurate ROI on our ad spend, and now we see the impact through the attribution models." — Mark D, Director of Paid Advertising (wicked-reports profile)
- "Their multi-touch attribution and incrementality testing have been especially valuable for us." — Chicory, Shopify App Store, September 2025 (polar-analytics profile)

**Theme: Click-only as a clarity stance**
- "Call tracking: I used dynamic numbers and Twilio. Hyros tied Google search clicks to booked calls and then to closed deals." — Scout Analytics hands-on review, 2026 (hyros profile) — illustrates that click-only is *more* defensible when the click resolves to a known event downstream
- (AdBeacon profile, unique strengths) "Click-only attribution as a transparency play — They explicitly reject view-based attribution as 'inflated metrics' and frame click-only as the more honest model."

## What users hate about this feature

**Theme: Attribution accuracy is contested — windows that move the number feel unsafe**
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson (Brightleaf Organics), workflowautomation.net, March 16, 2026 (triple-whale profile)
- "Hyros data sometimes does not match exactly with Facebook Ads Manager or other ad platforms, leading to confusion or distrust in the data." — Reddit r/FacebookAds user via checkthat.ai (hyros profile)
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — AI Systems Commerce, 2026 review (triple-whale profile)

**Theme: Pixel needs a learning period — windows below the maturation horizon are unreliable**
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed (Atlas Engineering), workflowautomation.net, November 20, 2025 (triple-whale profile)
- (rockerbox profile) Doc-level note: new customers see "artificially shortened conversion timeframes initially, as historical touchpoints may not be captured before implementation."

**Theme: GA4's reduced model picker hides the levers users used to have**
- (ga4 profile, weaknesses) "Attribution model picker reduced from 7 (UA) to 3 (DDA / Paid-organic last click / Google last click) — first-touch, linear, time-decay, and position-based were removed in 2023."
- (ga4 profile) "Data-driven attribution requires 400 conversions/key event + 20K total — most SMBs don't qualify and silently fall back to last-click-equivalent behavior."

**Theme: UTM tagging is functionally required and that friction propagates into windows**
- "Wicked Reports is limited by the requirement of adding UTM codes to all advertising materials." — Cuspera aggregated review (wicked-reports profile)
- "The limitations of their API make automation challenging for some use cases, particularly with specific data columns like first and last click date." — review aggregator (wicked-reports profile)

**Theme: No historical backfill ⇒ window math is broken at install time**
- (hyros profile, unique weaknesses) "No historical import. Tracking starts from install day; no retroactive backfill of orders/clicks."

## Anti-patterns observed

- **Window hidden inside model name.** Polar Analytics buries view-through inside the model picker as "Full Paid Overlap + Facebook Views" — a 6-word model variant that's the *only* surface where the merchant sees that views are now counted. Discoverable only by reading every option in the dropdown.
- **Fixed-at-install window with no retroactive recalc.** Hyros tracks "from install day" — historical orders are not re-attributed when a window is changed. Users who switch from 7d to 30d months later still get a partial answer for the early period and don't know it.
- **Page-level model toggle, not per-metric.** GA4's Model Comparison report applies one toggle to the whole page. Users can't see "Sessions on Last Click" alongside "Revenue on DDA" on the same surface — they have to switch contexts and remember the previous number.
- **Suppressing the model picker for data-thresholding.** GA4 silently falls back to a non-DDA model when the merchant has fewer than 400 conversions/key event, but the picker still shows DDA selected. The displayed setting and the actual attribution behavior diverge with no warning.
- **Click-only with no transparency about what views would have added.** AdBeacon drops view-through entirely as "inflated" — defensible philosophically, but a merchant running upper-funnel video has no way to see what they're missing.
- **Tracked-revenue billing tied to window choice.** Hyros's tier is set by attributed revenue, so improving attribution (e.g. extending the lookback) auto-bumps the customer's bill. The window choice is no longer a pure measurement decision; it's a pricing decision (hyros profile, unique weaknesses).
- **Lookback values not published.** Polar Analytics, AdBeacon, Conjura, Rockerbox, StoreHero, Fospha all expose "selectable" or "configurable" windows in marketing copy but do not publish the actual values anywhere public. Buyers have to book a demo to learn whether 7d or 28d is supported.
- **Black-box Bayesian "lookback handled implicitly".** Fospha removes the window selector entirely, on the argument that ad-stock decay handles it. Defensible at MMM tier; opaque for an SMB that wants to defend a CFO question with "I picked 7d".
- **Two windows for the same campaign that disagree.** Meta's default is 7d-click + 1d-view; Triple Whale defaults to "unlimited lookback"; the same Meta campaign reports different ROAS depending on which surface the merchant is on. The disagreement is the information, but several tools collapse it into one number per campaign per page rather than showing both.

## Open questions / data gaps

- **Polar's view-through-model implementation details** — the "Full Paid Overlap + Facebook Views" model is named but the lookback values, click vs view weighting, and decay function are not published. Would require a paid eval account or a sales call.
- **Triple Whale's actual default window** — older blog says "look back up to 60 days, with 180 days planned"; Triple Pixel marketing now claims "unlimited lookback" on paid plans; KB pages 403'd to WebFetch so the Summary-tile default could not be verified.
- **AdBeacon's actual lookback values** — "session continuity beyond 30 minutes" implies extension beyond Meta's default but the explicit window list is not published.
- **Cometly Shopify-specific default vs funnel-builder default** — the 28-day default is referenced in third-party reviews but the in-app picker contents (and whether different defaults apply for ClickFunnels vs Shopify) is not directly observable.
- **Klaviyo's per-metric vs Marketing Analytics retroactive-recalc behavior** — the docs distinguish them but the UI delineation (which window is editable on the base plan vs the add-on) is not directly observable.
- **Hyros's "Scientific Mode" attribution model** — referenced in third-party reviews as a proprietary hybrid; the actual window logic is undocumented publicly.
- **How any tool handles the cross-platform window disagreement** — Meta defaults to 7d-click+1d-view, TikTok to 7d-click+1d-view, Google to data-driven, GA4 to data-driven over 90d. Whether any tool reconciles these into a single "consistent across platforms" window when computing blended numbers is not directly verified from public docs.
- **No public sandbox for Wicked Reports / Hyros / Triple Whale's Attribution dashboard** — most UI screenshots in this dataset are reviewer-prose secondhand. A paid eval account would be needed for pixel-accurate teardown of the column-suffix vs in-table-dropdown choices.

## Notes for Nexstage (observations only — NOT recommendations)

- **Suffix-on-metric-name ("ROAS [7d]") is a 1-of-12 pattern, dominated by Northbeam.** Every other competitor either holds the window in a global selector or buries it in a model name. If Nexstage wants the window to be legible at the metric level (a natural fit for the 6-source-badge thesis), the visual precedent is Northbeam's literal column-header suffix — sparsely used in the category.
- **6/12 implementations use a side-by-side two-source compare** (platform-reported vs tool-attributed columns). This is the closest existing UX to Nexstage's 6-source thesis — Polar goes furthest with 3 columns (platform/GA4/Polar), but no one does 6.
- **Click-only vs view-through is exposed as a binary toggle by zero competitors observed.** AdBeacon makes it a *philosophical stance* (no view-through at all). Polar makes it a *named model variant* ("…+ Facebook Views"). Northbeam makes it a *combined window* ("7-Day Click / 1-Day View"). Wicked makes it a *continuous slider* ("View-Through Conversion Impact"). The "click-only / click+view" radio is whitespace.
- **Default vs override pattern.** Most tools ship a single default (Triple Whale: pixel-unlimited, GA4: DDA over 90d, Cometly: 28d, Northbeam: 7d-click). Polar exposes 9-10 model variants and lets the merchant pick. ThoughtMetric explicitly names 5 models with definitions. Nexstage's `UpdateAttributionDefaultsAction` already supports retroactive-recalc-on-change — the dispatched `RecomputeAttributionJob` + "Recomputing…" banner is the right pattern (Klaviyo does this only on the paid Marketing Analytics add-on).
- **Window changes triggering retroactive recalc is documented as a paid-tier feature in 2 of the 12 (Klaviyo Marketing Analytics, Wicked Reports Time Machine).** Nexstage's CLAUDE.md gotcha that "Cost/attribution config changes trigger retroactive recalc" via `RecomputeAttributionJob` is more aggressive than the category baseline; worth marketing as transparency.
- **Per-customer touchpoint drill-down is the strongest trust-builder when paired with windows.** 4 competitors do this: Wicked Time Machine, Hyros Lead Journey, Polar order-level drill-down, Cometly Conversion Profiles. The pattern is: pick a window, see the resulting number, click the number, see the actual click-history that earned it.
- **Window values cluster on 7/14/28/30/60/90.** ThoughtMetric explicitly enumerates 7/14/30/60/90. Cometly: 30/60/90. GA4: 7/30 (acquisition), 30/60/90 (key-event). Northbeam: 1/3/7/14/30/90 (click). The merchant-facing default vocabulary is converging on this set.
- **"Infinite / LTV" lookback is offered by 3 competitors** (Northbeam LTV-CAC, Triple Whale Triple Pixel "unlimited lookback", Wicked Time Machine). Wicked goes further with retroactive re-pricing as cohorts mature — a structural innovation that crosses into cohort-retention territory (see `cohort-retention.md` profile).
- **GA4's silent fall-back when DDA fails (sub-400 conversions) is exactly the kind of "invisible source picking" that `MetricSourceResolver` exists to surface.** GA4 shows "DDA selected" while behaving like last-click — the merchant can't see the source-disagreement that IS the information. Direct precedent for Nexstage's "show the source badge with the actual lens applied, not the requested one" pattern.
- **5 of 12 publish their lookback values; 7 don't.** Tools that hide the window value publicly (Polar, AdBeacon, Conjura, Rockerbox, StoreHero, Fospha) tend to also hide their pricing publicly. Window transparency correlates with pricing transparency in this dataset.
- **The merchant question "which window am I on?" maps to a UX that none of the 12 fully answer at-a-glance.** The window is either suffixed (Northbeam, 1 of 12), held in a model name (Polar, 1 of 12), buried in settings (GA4, Klaviyo), or implicit in the model (Fospha). A globally visible source/window badge — analogous to Nexstage's 6-source-badge but for the window — does not exist in the surveyed competitors.
