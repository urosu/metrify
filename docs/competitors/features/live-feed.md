---
name: Live feed / live view
slug: live-feed
purpose: Answer "what is happening in my store right now?" with a real-time visual of orders, sessions, and visitor geography refreshed in seconds-to-minutes.
nexstage_pages: dashboard, live
researched_on: 2026-04-28
competitors_covered: shopify-native, triple-whale, putler, polar-analytics, ga4, woocommerce-native, fairing
sources:
  - ../competitors/shopify-native.md
  - ../competitors/triple-whale.md
  - ../competitors/putler.md
  - ../competitors/polar-analytics.md
  - ../competitors/ga4.md
  - ../competitors/woocommerce-native.md
  - ../competitors/fairing.md
  - ../competitors/storehero.md
  - ../competitors/klaviyo.md
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/live-view
  - https://shopify.engineering/2025-bfcm-live-globe
  - https://www.putler.com/docs/category/putler-dashboards/home/
---

## What is this feature

The live-feed is the surface a merchant opens during a flash sale, a Black Friday push, an ad launch, an influencer drop, or just a "did the morning email send work?" moment. It collapses the lag between event-in-store and number-on-screen to seconds or minutes, and pairs that with a visual idiom — globe, 2D map, ticker tape, activity log — that makes one-time spikes legible without forcing the merchant to interpret a chart. Source platforms (Shopify, Woo, Stripe, GA4) all expose the underlying events, but a "live feed feature" is specifically the synthesis: stitching orders + sessions + geography + alerting onto one always-refreshing canvas instead of three separate admin tabs.

For SMB Shopify/Woo owners the appeal is emotional as much as analytical. Reviewers describe leaving Live View open all day "like a TV channel" (PrimRenditions, Shopify Community), and Triple Whale's mobile push is described as "addictive." It is also the surface that breaks first under bot traffic, and the surface most often paywalled or stripped of history — closing the tab usually deletes everything seen.

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `orders.created_at`, `orders.total_price`, `orders.shipping_address.country/city/lat/lng`, `orders.line_items`, `checkouts.started_at`, `checkouts.completed_at`, `cart.abandonment` events, `refunds.created_at`. Live View consumes the same Shopify event stream that powers admin order notifications.
- **Source: WooCommerce** — `wc-order` post-creation hook, `woocommerce_new_order`, `woocommerce_payment_complete`, billing/shipping country, line_items. WooCommerce mobile "My Store" treats the order list as real-time but the analytics aggregation lags 12 hours by default.
- **Source: Storefront sessions** — first-party pixel events (Shopify visitor pixel; Triple Pixel; Polar Pixel; Putler's cookie-less script). Required fields: `session_id`, `started_at`, `country/region/city`, `device`, `referrer`, `utm_source/medium/campaign`, `landing_page`, `current_step` (browsing / cart / checkout). Triple Pixel and Polar Pixel both work server-side; Shopify Live View uses Shopify's own session ping.
- **Source: Geo lookup** — IP → lat/lng/country (typically MaxMind or platform-internal). Required to plot dots on a 3D globe or 2D map.
- **Source: Ad platforms (Meta / Google / TikTok)** — spend pacing for "real-time MER" tiles on Triple Whale's Summary; refreshed on demand via the April 2026 on-demand refresh button. Not used for visitor-dot rendering.
- **Source: Klaviyo / Attentive / Postscript** — flow-send events surfaced in Triple Whale's Sonar Send dashboard for "X campaign just sent, Y conversions so far" framings.
- **Source: Computed** — `visitors_now = COUNT(DISTINCT session_id) WHERE last_event_at >= now() - 5m`. `sales_today = SUM(orders.total) WHERE created_at >= local_midnight`. `pacing_vs_yesterday = (sales_today / sales_at_same_clock_time_yesterday) - 1`.
- **Source: User-input** — local timezone (sets "today" boundary for since-midnight metrics); per-day-of-year target (Putler "Pulse zone" current-month target).

## Data outputs (what's typically displayed)

- **KPI: Visitors right now** — `COUNT DISTINCT session_id` over rolling 5-min window. Integer count.
- **KPI: Sessions today** — `COUNT(sessions)` since local midnight. Integer.
- **KPI: Total sales (today)** — `SUM(orders.total - discounts - refunds + shipping + tax)` since midnight. USD.
- **KPI: Total orders (today)** — count since midnight.
- **KPI: Sessions in checkout / Completed purchases / Carts created** — funnel-stage counts.
- **KPI: MER / ROAS / POAS (live)** — Triple Whale's mobile-app "real-time MER, ncROAS & POAS" tiles refreshed as ad-platform spend rolls in.
- **Dimension: Country / City / Latitude-longitude** — for globe / map rendering.
- **Dimension: Event type** — visitor session vs order vs refund vs dispute (Putler activity log) vs flow send (Klaviyo, via Triple Whale Sonar).
- **Breakdown: Visitors × geography × time** — dot density on map / globe.
- **Slice: Order value / product / channel per ticker entry** — Putler activity log shows "$148 / Beauty Cream / PayPal" rows.
- **Trend: Mini bar chart, 1 bar per minute, last 30 minutes** — GA4 Realtime hero strip.
- **Trend: Hourly spark / 3-day rolling delta** — Putler Pulse zone.
- **Alert: Anomaly tile** — "Sales pacing 32% behind yesterday" (Triple Whale Anomaly Detection Agent / Polar Smart Alerts firing into a live surface).

## How competitors implement this

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Shopify admin > Analytics (left nav) > Live View.
- **Visualization:** 3D rotating globe (three.js Points) with toggle to 2D map; surrounded by a strip of real-time numeric KPI cards. Dots are the primary visual: blue dots = visitor sessions, purple dots = orders.
- **Layout (prose):** Hero is the 3D globe occupying most of the canvas. A surrounding strip of cards displays "Visitors Right Now" (active in past 5 minutes), "Total sales" (today), "Total sessions" (since midnight), "Sessions in checkout," "Completed purchases," "Total orders" since midnight. 2D map alternative renders dashed state lines and state labels.
- **Specific UI:** Blue dots (sessions) and purple dots (orders) plotted at IP-derived lat/lng; "blue dots indicate recent visitor sessions, and purple dots indicate orders" (Help Center wording). Globe rendered using three.js Points "to draw using dots instead" of triangles. Click/drag to rotate. Visitor counter is a 5-minute rolling window; some surrounding metrics refresh every 10 minutes per the Shopify blog. Toggle button in canvas chrome to switch between globe and 2D map. BFCM "public globe" version uses arcs from shop → buyer with bloom-effect dot-matrix readouts on a 128×32-pixel display, but inside the merchant Live View only the dots are shown year-round.
- **Filters:** None observed beyond globe/2D toggle; the surface is intentionally unfiltered (shows all stores, all channels, all geographies).
- **Data shown:** Visitors Right Now, Total sales (today), Total sessions, Sessions in checkout, Completed purchases, Total orders.
- **Interactions:** Drag globe to rotate; hover/tap a dot for visitor or order detail; toggle globe ↔ 2D map; works on mobile. No drill-down to specific session/order pages observed publicly.
- **Why it works (from reviews/observations):** "I usually have it open all the time when I am at my computer working on other tasks." (PrimRenditions, Shopify Community, Jan 19 2023). Visual language is part of Shopify brand identity (BFCM public globe).
- **Source:** [shopify-native profile](../competitors/shopify-native.md); https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/live-view; https://shopify.engineering/2025-bfcm-live-globe.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Summary Dashboard (default landing) + native iOS / Android app + push notifications. There is no dedicated "Live View" or globe surface in the Triple Whale profile; the live-feed function is fulfilled by the Summary Dashboard's real-time tiles plus mobile push.
- **Visualization:** KPI-tile grid (collapsible sections by integration: Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics, Custom Expenses) with each tile showing headline value + period-vs-period delta. April 2026 added an on-demand refresh button with a real-time status cycler ("Refreshing Meta…"). Mobile widgets render the same KPIs on iOS/Android home screens.
- **Layout (prose):** Top: date-range and store-switcher with comparison toggle. Body: vertically stacked, collapsible sections each holding a draggable tile grid. Mobile-app variant: scrollable single-column tile stack, Apple/Android home-screen widgets mirror the most-pinned tiles.
- **Specific UI:** KPI tile = headline number + period delta + 📌 pin-on-hover. Edit-mode reveals "Create Custom Metric." Table-view toggle pivots the same tiles into a dense single-table layout. April 2026 refresh button cycles through integrations live ("Refreshing Meta…", "Refreshing Klaviyo…") so the merchant sees provenance of the new pull. Mobile push notifications fire "within minutes of the triggering event" on revenue milestones.
- **Filters:** Date range, store switcher, comparison period; per-section show/hide; section reorder via drag.
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV (60/90), Total Ad Spend, Sessions, Conversion Rate, Refund Rate plus per-platform spend/ROAS sub-tiles. Mobile widgets surface "real-time MER, ncROAS & POAS."
- **Interactions:** Drag/drop tile reorder, pin to "Pinned" section, on-demand refresh, click tile → drill to detail report; mobile push tap → open Summary in app; Moby Chat sidebar present on every dashboard for "what just happened?" queries.
- **Why it works:** "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design." (paraphrased consensus across 2026 reviews — workflowautomation.net, headwestguide.com).
- **Source:** [triple-whale profile](../competitors/triple-whale.md); https://triplewhale.com/blog/triple-whale-product-updates-april-2026.

### Putler ([profile](../competitors/putler.md))
- **Surface:** Home Dashboard ("Pulse zone") at top of sidebar.
- **Visualization:** Composite widget card stack: numeric KPI block + small embedded daily-sales mini-chart (no axis labels) + inline YoY % delta + activity log (vertical scrolling list). No globe, no map.
- **Layout (prose):** Top of screen is the "Pulse" zone for the current month. Primary widget shows month-to-date sales, daily mini-chart, 3-day trend, current-month target setting, year-over-year vs same month last year, and a forecasted month-end number — all stacked into one card. Adjacent: an Activity Log streaming new sales / refunds / disputes / transfers / failures. Adjacent again: "Three Months Comparison" widget (visitors, conversion rate, ARPU, revenue for 90d vs preceding 90d). A "Did You Know" tile rotates daily growth tips. Below the Pulse zone: standard Overview area with date-picker.
- **Specific UI:** Rectangular widget cards with rounded corners, light-gray borders. YoY rendered as inline percentage delta beside absolute number. Daily-sales mini-chart is a small bar/line inside the widget body without axes. Activity Log is a vertical scrolling list with **colored dots as event-type indicators** and timestamps; dropdown filter scopes the log by event type. "Did You Know" tile rotates one tip per day.
- **Filters:** Date-picker scopes the surrounding Overview region; Activity Log dropdown filters by event type (sale / refund / dispute / transfer / failure).
- **Data shown:** Month-to-date sales, daily sales, 3-day trend, target, YoY delta, forecast, plus the streaming activity events.
- **Interactions:** Click any KPI widget → drill to its native dashboard (Net Sales → Sales Dashboard, Subscription Metrics → Subscriptions Dashboard). Activity Log dropdown filter. Refresh cadence advertised as 5 minutes; reviewers note 15-30 min lag on PayPal.
- **Why it works:** "Putler has been my trusted data companion for a decade" (Ekaterina S., Capterra Oct 7 2025); "To check sales and metrics, I rarely visit my WordPress site. Instead, I directly open Putler, which has become my new home" (mrbinayadhikari, wordpress.org plugin review, Dec 22 2025).
- **Source:** [putler profile](../competitors/putler.md); https://www.putler.com/docs/category/putler-dashboards/home/.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Smart Alerts (Slack / email), the Custom Dashboard canvas (with hourly refresh standard, intraday as paid add-on), and the Polar Pixel order-level view.
- **Visualization:** No dedicated globe / map / ticker. KPI-card + sparkline-card layout on dashboards; alerts are notification entries delivered out-of-app. Order-level drill-down to the multi-channel touchpoint sequence is offered but framed as attribution, not live monitoring.
- **Layout (prose):** Custom Dashboard top row hosts Metric Cards or Sparkline Cards (a metric card with a mini trend embedded); below sit charts and tables. Date range selector top-right. No timestamp ticker. Smart Alerts surface arrives via Slack / email and is the closest Polar gets to "what's happening right now."
- **Specific UI:** Sparkline Card embeds a mini line inside the card. Comparison indicators (improvement/decline arrows) auto-render. Smart Alerts route Slack notifications such as "a sudden surge in your sales." Default refresh cadence is hourly; intraday refresh is paid. "Switching between views and reports can be slow sometimes" (bloggle.app review).
- **Filters:** Dashboard date range, Views (saved filter bundles spanning multiple data sources), Global Filters, Individual Filters, currency.
- **Data shown:** Configurable from semantic layer ("hundreds of pre-built metrics and dimensions"). For live use: blended ROAS, MER, sales pacing, conversions.
- **Interactions:** Smart Alert ping → click through to Slack message → into dashboard. Hourly refresh; on-demand refresh not surfaced as a button. Order-level drill into customer journey from Attribution surface.
- **Why it works:** "The feature worked like a charm; it's almost like having another team member keeping an eye on things" (bloggle.app reviewer about Smart Alerts, 2024). The pattern is push-notification-as-live-feed rather than always-on canvas.
- **Source:** [polar-analytics profile](../competitors/polar-analytics.md); https://intercom.help/polar-app/en/articles/10430437-understanding-dashboards.

### GA4 ([profile](../competitors/ga4.md))
- **Surface:** Reports > Realtime.
- **Visualization:** Full-bleed top-of-page 3D-style globe (geo bubble visualization showing concurrent users by country) on the left + "Users in last 30 min" big-number card with mini per-minute bar chart underneath (one bar per minute, ~30 bars, leftmost = 30 min ago, rightmost = now). Below the hero row: a 6-card insight grid.
- **Layout (prose):** Top: globe (left) + 30-min big-number + per-minute bar timeline (right). Below: 6 insight cards — Users by First user source / medium / campaign (each with dropdown), Users by Audience, Views by Page title and screen name, Event count by Event name, Conversions by Event name, User property values. Top-right of page: "View user snapshot" button.
- **Specific UI:** Per-minute bar chart with ~30 bars (one per minute) visualizes the 30-min rolling window. Each card has a dimension dropdown ("first user medium" vs "source/medium" etc). Hovering a row reveals percentage of total. **"View user snapshot"** opens a side panel with one randomly-selected active user's session timeline of events; arrow buttons step through next/previous user; clicking an event in the timeline expands its parameters. Up-to-4-way comparison view available; map disappears when comparisons are active. No drill-on-click into a row from realtime.
- **Filters:** Comparisons (up to 4), but no date range — surface is hardcoded to last 30 minutes.
- **Data shown:** Active users last 30 min, users by source/medium/campaign/audience, page views, events, conversions.
- **Interactions:** Hover row → percentage; "View user snapshot" → step through individual user event timelines; "Edit comparisons" pill adds cohort filters.
- **Why it works:** Cited as the canonical real-time UX for web analytics; the per-minute bar timeline is widely copied. No verbatim user love quote in profile.
- **Source:** [ga4 profile](../competitors/ga4.md); https://support.google.com/analytics/answer/9271392.

### WooCommerce Native ([profile](../competitors/woocommerce-native.md))
- **Surface:** Mobile app "My Store" tab (iOS/Android).
- **Visualization:** No globe, no map. A single summary screen showing sales total + top-performing products for a chosen period (Day / Week / Month / Year). Order list is real-time; everything else lags 12 h.
- **Layout (prose):** Period toggle (Day/Week/Month/Year) at top; sales total card; top-products list. Order list is a separate tab and is real-time. Push notifications for new orders/reviews require Jetpack.
- **Specific UI:** UI details not available beyond marketing copy ("process orders and watch your sales climb in real time"). Push notifications via Jetpack are the live-event channel.
- **Filters:** Period toggle (Day / Week / Month / Year).
- **Data shown:** Sales total + top products for the selected period; real-time order list.
- **Interactions:** Push notification → open order; period toggle.
- **Why it works:** Implicit — no dedicated review quotes in profile. "Mobile app is real-time for orders but thin on analytics" (profile's own observation).
- **Source:** [woocommerce-native profile](../competitors/woocommerce-native.md); https://woocommerce.com/mobile/.

### Fairing ([profile](../competitors/fairing.md))
- **Surface:** Question Stream > Live Feed sub-tab.
- **Visualization:** Real-time response monitoring stream. UI details not directly observable from public sources beyond the docs labeling it "Live Feed — real-time response monitoring stream."
- **Layout (prose):** UI details not available — the surface is named in the docs IA list but not described in screenshots reviewed.
- **Specific UI:** "Real-time response monitoring stream" — phrasing implies a vertical scrolling list of incoming survey responses, but the exact element list is not documented publicly.
- **Filters:** Not observed.
- **Data shown:** Incoming post-purchase survey responses (HDYHAU answers, NPS, etc.).
- **Interactions:** Not observed.
- **Why it works:** Not observed in profile.
- **Source:** [fairing profile](../competitors/fairing.md).

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** No dedicated live feed observed. Marketing language uses "real-time" (e.g., "Watch how every $100 you invest into ads changes profit in real time") but this refers to the Spend Advisor what-if simulator rather than a live event canvas. Mobile iOS app delivers daily/weekly/monthly digest reports, not real-time stream.
- **Visualization:** Not observed (no live-feed surface).
- **Layout (prose):** Not observed.
- **Specific UI:** Not observed.
- **Filters:** Not observed.
- **Data shown:** Not observed for live-feed purpose.
- **Interactions:** Not observed.
- **Why it works:** Not applicable.
- **Source:** [storehero profile](../competitors/storehero.md).

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** No dedicated live feed observed. "Real-time" appears in profile only in the context of message triggers ("real-time triggers" — Marc G., Capterra, March 2026); analytics dashboards refresh per data range and are not framed as live monitoring. Status pills (Live / Manual / Draft) on flows describe configuration state, not real-time delivery.
- **Visualization:** Not observed (no live-feed surface).
- **Layout (prose):** Not observed.
- **Specific UI:** Not observed.
- **Filters:** Not observed.
- **Data shown:** Not observed for live-feed purpose.
- **Interactions:** Not observed.
- **Why it works:** Not applicable.
- **Source:** [klaviyo profile](../competitors/klaviyo.md).

## Visualization patterns observed (cross-cut)

Counted across the 7 competitors with any live-feed surface:

- **3D globe + dot rendering:** 2 competitors (Shopify Native — three.js Points with blue/purple session/order dots; GA4 — geo-bubble globe with country-level concurrent-user dots). Shopify is the only one to encode event type by dot color.
- **2D map alternative:** 1 competitor (Shopify Native, toggleable from the globe; "dashed state lines and state labels" was added and disliked by reviewers).
- **KPI tile grid (no globe / no map):** 2 competitors (Triple Whale Summary + mobile widgets; Polar Sparkline Cards on hourly-refresh dashboards). This is the dominant pattern for "live numbers" without geographic visualization.
- **Per-minute mini bar timeline (last 30 min):** 1 competitor (GA4 Realtime — ~30 bars, leftmost = 30 min ago, rightmost = now).
- **Activity log / ticker (vertical scrolling event stream with colored dot indicators):** 1 competitor (Putler Pulse zone activity log streaming sales / refunds / disputes / transfers / failures).
- **Composite "Pulse zone" (single widget combining MTD total + mini-chart + 3-day trend + target + YoY + forecast):** 1 competitor (Putler — distinctive in compressing 6 sub-metrics into one card).
- **Push-notification-as-live-feed:** 3 competitors (Triple Whale mobile push within minutes; Polar Smart Alerts to Slack/email; WooCommerce mobile via Jetpack push).
- **User-session snapshot / step-through individual visitor:** 1 competitor (GA4 "View user snapshot" side panel with arrow-button paging through active users).
- **No live-feed surface at all:** 2 competitors (StoreHero — "real-time" is marketing language for the Spend Advisor simulator; Klaviyo — "real-time triggers" refers to email send rules, not analytics canvas).

Color/iconography conventions: Shopify's blue=session / purple=order is the only event-type dot-color encoding observed. Putler uses colored dots in the activity log to encode event type but does not publish the palette. Refresh-status indicators (Triple Whale's "Refreshing Meta…" cycler) are a recent April 2026 invention and not yet widespread.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Always-open background channel**
- "I usually have it open all the time when I am at my computer working on other tasks." — PrimRenditions on Live View, Shopify Community, January 19, 2023 (shopify-native profile)
- "To check sales and metrics, I rarely visit my WordPress site. Instead, I directly open Putler, which has become my new home." — mrbinayadhikari, wordpress.org plugin review, December 22, 2025 (putler profile)

**Theme: Mobile-first, real-time on the go**
- "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design." — paraphrased consensus across 2026 reviews (workflowautomation.net, headwestguide.com) (triple-whale profile)
- "Real-time data, clean dashboards, mobile app, and automation consistently save operators 4–8 hours per week." — AI Systems Commerce, 2026 review (triple-whale profile)

**Theme: Anomaly push beats always-watching**
- "The feature worked like a charm; it's almost like having another team member keeping an eye on things." — bloggle.app reviewer about Polar Smart Alerts, 2024 (polar-analytics profile)

**Theme: Pulse-zone storytelling (one widget, full picture)**
- "It's a game-changing dashboard for viewing sales-related data." — Matt B., Capterra, February 24, 2025 (putler profile)
- "I spend a lot of time getting an overview with Excel, but when I got Putler, I have an overview for Amazon, eBay, Etsy, Shopify. I don't need to do Excel anymore." — G2 reviewer cited via Putler's own G2 aggregation (putler profile)

## What users hate about this feature

**Theme: Bot-traffic noise destroys signal**
- "The frequent occurrence of bot traffic makes it impossible for me to rely on the Visitor stats… I can tell when I have bot traffic because I'll see the same four dots in the exact same locations: CA, KS, IA, and Ireland! So if I see 20 visitors during a period of bot activity, I know I only really have 4 or 5 actual customers." — PrimRenditions, Shopify Community, January 19, 2023 (shopify-native profile)
- "the issues with live view prevent me from using it for anything useful." — PrimRenditions, Shopify Community, January 31, 2023 (shopify-native profile)

**Theme: Visual regressions from product updates**
- "The new map with all the dashed state lines and state labels make it really difficult to see the blue visitor dots." — PrimRenditions on Live View, Shopify Community, January 17, 2023 (shopify-native profile)
- "Why would they then make changes to the MAP view, which was fine, and leave the globe view untouched?" — PrimRenditions, Shopify Community, January 17, 2023 (shopify-native profile)

**Theme: Refresh lag vs marketing claims**
- "Data import could be faster and pricing lacks transparency." — Patrick C., Capterra, October 4, 2023 (putler profile)
- "Switching between views and reports can be slow sometimes" — bloggle.app review, 2024 (polar-analytics profile)
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed, workflowautomation.net, November 20, 2025 (triple-whale profile)

**Theme: Closed-when-closed (data not preserved)**
- Putler observation about Shopify Live View captured in shopify-native profile: "Moment you close it, that data is gone." (shopify-native profile, Notes for Nexstage section, citing Putler analysis)

**Theme: Mobile feed stripped vs desktop**
- "you can view only a limited number of reports on mobile." — bloggle.app review, 2024 (polar-analytics profile)
- Profile observation: "Mobile app is real-time for orders but thin on analytics" — woocommerce-native profile.

## Anti-patterns observed

- **Bot-noise without filtering (Shopify Live View).** Visitor dots are plotted from raw IP data with no bot exclusion in the surface itself. PrimRenditions documents the exact symptom: four identical dots in CA / KS / IA / Ireland during bot waves, making the "20 visitors" headline number unreliable.
- **Map redesign that hurt legibility (Shopify Live View 2D map).** Adding dashed state lines + state labels obscured the blue visitor dots; reviewers explicitly asked why the change was made when "the globe view [was] untouched." Visual chrome can outweigh signal.
- **Closed-when-closed historical loss (Shopify Live View).** Per Putler's analysis surfaced in the shopify-native profile, real-time data isn't preserved after the tab closes. Merchants can't recall a Friday 4pm spike on Monday.
- **"Real-time" as marketing copy without a published cadence (StoreHero).** The storehero profile flags "data-refresh cadence is not technically specified — 'real-time' is marketing language, no exact interval published." Setting expectations the product doesn't meet.
- **PayPal sync lag contradicting the marketing claim (Putler).** 5-minute refresh advertised, 15-30-min lag observed for PayPal. When a "live feed" includes a slow source, the slowest source defines perceived freshness.
- **No cross-source disagreement surfaced.** None of the live-feed implementations show two sources next to each other (Shopify-confirmed orders vs Meta-pixel-fired purchases) — they pick one truth and render it. Direct gap for a multi-source-badge thesis applied to live data.

## Open questions / data gaps

- **Putler activity log dot-color palette** — colored event-type dots are documented but the palette itself isn't published; would need a paid eval account to capture.
- **Triple Whale Summary on-demand refresh button visual** — described in the April 2026 product-update blog ("real-time status cycling display 'Refreshing Meta…'") but no screenshot fetched.
- **Polar Smart Alerts visual** — only verbal description ("a sudden surge in your sales"); the in-app metric-alert configuration UI is unscreen-shotted in profile.
- **Fairing Live Feed UI** — listed as a sub-surface of Question Stream but the profile has no element-level description (`UI details not available`).
- **Shopify Live View per-dot tooltip details** — Help Center and engineering blog describe blue/purple dot semantics, but the exact tooltip payload (does hovering show order value? customer email? landing page?) isn't documented in public sources surfaced.
- **GA4 globe rendering technique** — GA4 profile calls it a "3D-style 'globe' map" but doesn't confirm three.js / WebGL implementation specifics; only Shopify's three.js Points usage is engineering-blog-confirmed.
- **WooCommerce mobile real-time order list** — marketing-page only; no element-level description.
- **Whether anyone surfaces a "bot vs human" toggle** on a live map — none observed; would close the loop on PrimRenditions' bot-noise complaint.
- **Refresh-cadence transparency** — only Polar (hourly) and Putler (5-min advertised) publish a cadence number. Shopify says "real-time" + "every 10 minutes" inconsistently. Triple Whale and StoreHero use "real-time" without a number.

## Notes for Nexstage (observations only — NOT recommendations)

- **2 of 7 competitors with a live-feed surface render a globe (Shopify Native, GA4); the rest fall into KPI-tile / activity-log / push-notification patterns.** The globe is iconic but not universal. Shopify owns the dot-color semantics blue=session / purple=order; any merchant arriving from Shopify will read those colors as defaults.
- **0 of 7 implementations show source disagreement on the live surface.** Every competitor picks one truth (Shopify orders or pixel orders or platform-reported) and renders it. The 6-source-badge thesis (Real / Store / Facebook / Google / GSC / GA4) has no live-feed precedent — this is whitespace, with the caveat that bot-noise, ad-platform reporting lag, and pixel firing delays make multi-source agreement at sub-minute cadence harder than at daily cadence.
- **Putler's "Pulse zone" composite widget (MTD total + mini-chart + 3-day trend + target + YoY + forecast in one card) is the most information-dense single live element observed.** It is also the only one to combine a target line + forecast on a real-time surface, which makes "are we on pace?" answerable without a second click.
- **Push-notification-as-live-feed (Triple Whale, Polar, WooCommerce mobile) is the only pattern that survives the merchant closing the laptop.** Shopify Live View and Putler Pulse zone both require the surface to be open. Closed-when-closed is documented as a Shopify pain.
- **Bot-traffic filtering is universally unsolved on the live surface.** PrimRenditions' "four dots in CA / KS / IA / Ireland" complaint has been live since 2023 and the shopify-native profile has no resolution noted in 2026.
- **Refresh cadence is published for only 2 of 7 (Polar hourly + intraday paid; Putler 5-min).** Shopify says "real-time" plus "every 10 minutes" inconsistently. Setting a transparent cadence per source — e.g., "Store: 30 s · Facebook: 60 m · GA4: 10 m" — would be uncommon.
- **GA4's "View user snapshot" pattern (step-through individual active visitors with prev/next arrows) is unique** among the 7 competitors. No e-commerce-specific tool replicates it; the closest analog is Putler's Activity Log, which lists events without per-visitor session reconstruction.
- **The BFCM 3D globe is a Shopify brand artifact** (arcs from shop → buyer with bloom-effect dot-matrix readouts on a 128×32 display). Replicating that visual idiom would feel like piggybacking — the shopify-native profile flags this directly.
- **Activity-log dot colors as event-type encoding is a Putler convention** (sales / refunds / disputes / transfers / failures each get a color). Useful precedent if Nexstage ships a streaming event list.
- **Triple Whale's April 2026 on-demand refresh button with "Refreshing Meta…" status cycler** is the first observed UI that surfaces source-of-data provenance during a live refresh. Aligns with the source-badge thesis as applied to refresh-time UX.
- **No competitor's live feed surfaces a "this number disagrees with Meta" alert in real time.** The discrepancy feature (separate profile) is daily-snapshot territory; the live-feed surface treats one source as ground truth.
