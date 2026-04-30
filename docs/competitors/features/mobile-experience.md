---
name: Mobile experience
slug: mobile-experience
purpose: Answers "Does this work on my phone at 7am with a coffee?" — whether a merchant can do a meaningful daily-check ritual (revenue, profit, ad pacing, alerts) from a phone before opening a laptop.
nexstage_pages: dashboard, profit, ads, alerts-inbox
researched_on: 2026-04-28
competitors_covered: triple-whale, polar-analytics, shopify-native, storehero, klaviyo, putler, lifetimely, hyros, trueprofit, woocommerce-native, ga4, looker-studio
sources:
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/shopify-native.md
  - ../competitors/storehero.md
  - ../competitors/klaviyo.md
  - ../competitors/putler.md
  - ../competitors/lifetimely.md
  - ../competitors/hyros.md
  - ../competitors/trueprofit.md
  - ../competitors/woocommerce-native.md
  - ../competitors/ga4.md
  - ../competitors/looker-studio.md
  - https://apps.apple.com/us/app/triplewhale/id1511861727
  - https://apps.apple.com/us/app/hyros/id6450372574
  - https://apps.apple.com/us/app/trueprofit-profit-analytics/id1568063007
  - https://woocommerce.com/mobile/
  - https://cloud.google.com/blog/products/business-intelligence/get-looker-studio-pro-for-android-and-ios
---

## What is this feature

The "mobile experience" question is whether the analytics tool actually supports the merchant's daily-check ritual — the ~2 minutes a SMB Shopify/Woo founder spends with their phone over morning coffee, on a school run, or between meetings, looking at "did the store make money yesterday, are ads pacing, is anything broken?" This is a different surface than the desktop work session: it's read-only, glanceable, push-driven, and time-boxed under 60 seconds per app launch. The job-to-be-done is ambient confidence, not analysis.

For SMB Shopify/Woo owners specifically, mobile is where loneliness-of-the-founder hits hardest. They are usually solo or two-person operators with no analyst on the team to send a Monday email; the phone IS the morning report. Having data (every competitor's web app surfaces revenue) is not the same as having this feature: a native app with widgets, push notifications on revenue milestones, and a layout designed for thumb-reach is what merchants actually use day-to-day. The market splits cleanly into three camps — native iOS+Android with widgets and push (Triple Whale, Shopify Native via the Shopify mobile app, Klaviyo), iOS-only afterthoughts (TrueProfit, StoreHero, Hyros), and "responsive web only" (Polar Analytics, Lifetimely, Northbeam, BeProfit, Putler, Conjura, StoreHero web mode, ThoughtMetric, Motion, AdBeacon, Atria, Repeat Customer Insights, Daasity, Stripe Sigma, Wicked Reports, Lebesgue, Elevar, Metorik, SegmentStream, Rockerbox, Varos, Glew). Reviews consistently call out the absence of a native app as a real gap.

## Data inputs (what's required to compute or display)

Mobile is a presentation surface; the inputs are subsets of the dashboard/profit/alerts pipelines, optimised for size and freshness.

- **Source: Computed (daily_snapshots, hourly_snapshots)** — yesterday and today MTD revenue, gross profit, contribution margin, ad spend, ROAS, MER, AOV, conversion rate, sessions
- **Source: Computed (real-time event stream)** — orders/sessions in the last 5 minutes (for "Live View"-style cards)
- **Source: Push pipeline** — revenue-milestone events ("you hit $X today"), anomaly events, order-spike events, ad-spend-pace events
- **Source: User-input** — push-notification preferences (which events fire, quiet hours), home-screen widget choice (which metric the widget displays)
- **Source: OS APIs** — iOS WidgetKit / Android App Widgets (small/medium/large widget surfaces), iOS push-notification token, biometric/PIN unlock, deep-link handlers
- **Source: Per-platform scoped data** — ad spend by platform (Meta/Google/TikTok), top products, top channels — all served as pre-aggregated cards rather than ad-hoc queries

## Data outputs (what's typically displayed)

For each output, name the metric, formula, units, and typical comparisons:

- **KPI: Today's revenue** — `SUM(orders.total_price WHERE created_at >= today)`, USD/local, vs same time yesterday
- **KPI: Today's net profit** — `revenue - cogs - shipping - fees - ad_spend`, USD, vs prior-period delta
- **KPI: MER / blended ROAS** — `revenue / total_ad_spend`, decimal ratio, "real-time" framing
- **KPI: ncROAS / POAS / nCAC** — Triple Whale-named profit-on-ad-spend variants (mobile-app-specific marketing call-out)
- **KPI: Visitors right now** — sessions in last 5 min, integer
- **KPI: Sessions in checkout** — integer
- **KPI: Total orders since midnight** — integer
- **Card: Top products today** — top-N list with units sold + revenue
- **Card: Top channels today** — channel name + revenue + delta
- **Card: 60/90 LTV** — Triple Whale mobile call-out
- **Stream: Activity log** — vertical scrolling list of orders/refunds/disputes (Putler home), or order-events (WooCommerce mobile)
- **Push: Revenue-milestone notification** — text body + metric value + deep-link to detail
- **Push: Anomaly / spend-pace alert** — Triple Whale-style "Spend Anomalie" / "Orders Anomalie"
- **Widget: Home-screen profit/revenue tile** — single-metric, today vs yesterday, refreshes via background sync (TrueProfit, Triple Whale)

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Native iOS app (requires iOS 16.6+) and Android, free for all paid and free-tier customers including the Founders Dash. App Store ID 1511861727. Web app remains the primary surface.
- **Visualization:** mobile-optimized KPI-card stack + home-screen widget kit + push notifications.
- **Layout (prose):** "Mirrors desktop — Summary, Cohorts, Customer Insights, real-time MER/ncROAS/POAS, 60/90 LTVs" per the marketing page. Mobile Summary surfaces the same draggable metric tiles as desktop, served as mobile-optimized cards. Home-screen widgets occupy iOS/Android widget slots with a single hero metric.
- **Specific UI:** Push notifications fire "within minutes of the triggering event" on revenue milestones, anomalies, and pacing events. Mobile marketing call-outs name "real-time MER, ncROAS & POAS" and "60/90 LTVs" as the headline mobile metrics — implying the mobile default lens is profit/efficiency, not raw revenue.
- **Filters:** Date range; store-switcher implied for multi-store users on Advanced+.
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, AOV, 60/90 LTV, Total Ad Spend, Sessions.
- **Interactions:** Pull-to-refresh implied; tap card → detail drill-down; widget tap → app launch deep-link to that metric.
- **Why it works (from reviews/observations):** "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design" — paraphrased consensus across multiple 2026 reviews (workflowautomation.net, headwestguide.com). "Real-time data, clean dashboards, mobile app, and automation consistently save operators 4-8 hours per week" — AI Systems Commerce, 2026 review. The Triple Whale profile flags mobile as a "genuine differentiator… surpasses Northbeam / Lifetimely / Polar mobile."
- **Source:** ../competitors/triple-whale.md ; https://apps.apple.com/us/app/triplewhale/id1511861727

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** No native app. Web-responsive only.
- **Visualization:** no visualization, prose-only — the desktop dashboard renders responsively but a mobile-specific surface does not exist.
- **Layout (prose):** "UI details not available — only the acknowledgment from third-party reviews that 'you can view only a limited number of reports on mobile' and 'mobile reporting could use improvement, especially for monitoring business away from your desk.' No native app exists."
- **Specific UI:** Not applicable; mobile is a constrained version of the same Custom Dashboard canvas (metric cards, sparkline cards, charts), described in reviews as awkward for a phone.
- **Filters:** Same as desktop dashboard (date-range, Views, attribution model picker) but constrained on small screens.
- **Data shown:** Whatever the desktop dashboard renders, downscaled.
- **Interactions:** Same as desktop; performance lag flagged ("Switching between views and reports can be slow sometimes").
- **Why it works (from reviews/observations):** Reviews flag this as a weakness, not a strength. "You can view only a limited number of reports on mobile" — bloggle.app review, 2024.
- **Source:** ../competitors/polar-analytics.md

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Shopify mobile app (iOS/Android) > Analytics tab. Free with every Shopify plan.
- **Visualization:** mobile card grid (single-column scroll mirroring the desktop Overview metric cards) + Live View 3D globe / 2D map.
- **Layout (prose):** "Card grid mirrors the Overview but in a single-column scroll." Live View renders the same 3D rotating globe with blue dots = visitor sessions, purple dots = orders. Sidekick (the conversational AI) is full-screen on mobile.
- **Specific UI:** "Visitors Right Now" (active in past 5 minutes), "Total sales" (today, gross minus discounts/returns + shipping + taxes), "Total sessions" (since midnight), "Sessions in checkout," "Completed purchases," "Total orders" since midnight. Live View "works on mobile" per the docs. Drag/resize/edit-overview is documented for desktop; mobile parity not explicitly confirmed.
- **Filters:** Date range, store-switcher (for Plus / multi-store), comparison toggle.
- **Data shown:** Total sales, Gross sales, Net sales, Orders, Sessions, AOV, Online store conversion rate, Returning customer rate, Top products, Top channels, Top referrers, Sales attributed to marketing.
- **Interactions:** Sidekick voice chat + screen-sharing in beta on mobile. Push notifications via the Shopify app on order/inventory/Flow events. Tap card → drill into the underlying report. The whole admin (orders, products, inventory) lives in the same app, so "check stats" extends naturally into "fulfill order."
- **Why it works (from reviews/observations):** "I usually have it open all the time when I am at my computer working on other tasks" — PrimRenditions on Live View, Shopify Community, January 19, 2023 (referenced for the desktop Live View; the mobile app borrows the same engine). The integrated admin-plus-analytics surface is the strongest reason merchants use it daily.
- **Source:** ../competitors/shopify-native.md

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Native iOS app (Android NOT mentioned in marketing materials). Read-only summary of sales & profit reports.
- **Visualization:** mobile KPI-card stack — "read-only summary of sales & profit reports for delivery on mobile."
- **Layout (prose):** "UI details not available — no App Store screenshots inspected." The iOS app is positioned as one of three delivery channels for the Sales & Profit reports (alongside Slack and email) rather than as a full app experience.
- **Specific UI:** Not directly observable from public sources. The iOS app is referenced under "Email Reports / iOS Mobile App" in the features taxonomy as a delivery surface, not a power-user surface.
- **Filters:** Daily / weekly / monthly cadences; not clear whether ad-hoc date filtering is exposed.
- **Data shown:** Sales, profit (contribution margin), revenue, ad spend — implied parity with the email digest content.
- **Interactions:** Read-only; no Spend Advisor / forecasting / deep drill-downs on mobile.
- **Why it works (from reviews/observations):** No mobile-specific reviews surfaced — review pool is shallow (13 Shopify App Store reviews total). Multiple reviewers cite "real-time analytics alone have saved me HOURS every week" (WowWee.ie, March 24, 2026) without mobile-specific praise.
- **Source:** ../competitors/storehero.md

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Native iOS and Android app — "primarily for campaign monitoring and notifications."
- **Visualization:** card-stack of campaign performance + push notifications on send/delivery/conversion events.
- **Layout (prose):** UI specifics not surfaced in the Klaviyo profile beyond the frontmatter declaration. The mobile app's job is to expose the Home dashboard's "Top-Performing Flows" and "Recent Campaigns" lists with status pills (Live / Manual / Draft / Scheduled / Sending), plus deliver real-time send/delivery/spike notifications.
- **Specific UI:** Status pills mirror the web Home dashboard. The web Home is structured as a vertical scroll with the alerts strip on top + Business Performance Summary + Top-Performing Flows (six rows ranked by conversion or revenue) + Recent Campaigns — a layout that translates naturally to a phone.
- **Filters:** Time-period selector (up to 180 days); conversion-metric selector that re-pivots cards globally.
- **Data shown:** Total revenue, attributed revenue, conversions, opens, clicks, sends, percent change vs prior period; per-flow / per-campaign rows.
- **Interactions:** Tap flow row → flow detail. Push on campaign milestones. Mobile is a campaign-monitoring lens, not an analysis lens.
- **Why it works (from reviews/observations):** No mobile-specific verbatim quotes surfaced. Klaviyo's reporting UI is criticised generally as "clunky and the UI buries things that should be front and center" (Darren Y., Capterra, April 2026) — likely worse on mobile.
- **Source:** ../competitors/klaviyo.md

### Putler ([profile](../competitors/putler.md))
- **Surface:** Web-responsive only; no dedicated iOS/Android app observed.
- **Visualization:** no native viz — the Home Dashboard's "Pulse" zone (current-month focus, daily-sales mini-chart, 3-day trend, YoY comparison, forecasted month-end) is rendered responsively.
- **Layout (prose):** Per the desktop description: "Top of screen is the 'Pulse' zone for the current month: a primary Sales Metrics widget showing this-month-to-date sales, a daily-sales mini-chart, a 3-day trend, current-month target setting, year-over-year comparison vs same month previous year, and a forecasted month-end sales number — all stacked together as one widget." This stacks naturally on a phone but is not optimised for it.
- **Specific UI:** Activity Log "shows a vertical scrolling list with colored dots (event type indicators) and timestamps" — a pattern that maps well to mobile but no app exists.
- **Filters:** Date-picker filter at top of overview region scopes all widgets simultaneously.
- **Data shown:** Pulse-zone Sales Metrics + Activity Log + Three Months Comparison + Did You Know rotating tile.
- **Interactions:** Putler Copilot floating chat invokes a natural-language overlay (chat-on-mobile is a useful input mode but not specifically optimized).
- **Why it works (from reviews/observations):** No mobile-specific praise observed. The Activity Log streaming pattern is the most mobile-friendly surface they have.
- **Source:** ../competitors/putler.md

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** No native iOS/Android app observed. "Mobile and web device viewing" (responsive web).
- **Visualization:** no native viz — drag/drop/resize widget grid on a "blank canvas interface" rendered responsively.
- **Layout (prose):** Three starter templates mentioned for desktop: Marketing board (10 KPIs by channel), Daily overview (daily P&L summary), Boardroom KPIs. The Daily Overview template is the closest analog to a mobile daily-check view.
- **Specific UI:** Not observable for mobile. Schedule-email delivery is the explicit substitute for a mobile app — "Delivered to your email inbox and Slack every Monday at 8AM" — i.e., email is the mobile UX.
- **Filters:** Same as desktop.
- **Data shown:** Whatever the user composes onto the responsive canvas.
- **Interactions:** Email + Slack scheduled delivery (daily / weekly at 7am) compensates for the missing app.
- **Why it works (from reviews/observations):** Reviews cite the email digest favourably; mobile is flagged as a gap. "No mobile app. Surface gap if Nexstage ever ships mobile." (their own profile's "Notes for Nexstage")
- **Source:** ../competitors/lifetimely.md

### Hyros ([profile](../competitors/hyros.md))
- **Surface:** Native iOS app (App Store ID 6450372574, 4.0 stars / 8 ratings). Android also referenced on the home page.
- **Visualization:** widget grid mirroring the web dashboard — Live Stream + Hyros Insights + Recent Reports + Reporting Gap.
- **Layout (prose):** "Mirrors the web dashboard — Live Stream, Hyros Insights, Recent Reports, Reporting Gap widgets; lead drill-down; report runner with attribution-model selector; push notifications for tracking-setup issues."
- **Specific UI:** **Reporting Gap widget** = delta between Hyros-attributed sales and ad-platform-reported sales (a single-pair source-disagreement card on mobile). **Live Stream** = rolling list of incoming sales/leads. **Hyros Insights** = anomaly/opportunity callouts. Push notifications in this app are tracking-setup issues (i.e., "your pixel broke"), not revenue milestones — different push-notification semantics from Triple Whale.
- **Filters:** Date-range, attribution-model selector (per-widget custom-metric).
- **Data shown:** Revenue, ROAS, CPA, leads, calls, sales — segmented by source/campaign/ad. Lead Journey drill-down is the unique mobile feature for high-ticket / call funnels.
- **Interactions:** Pull-to-refresh; widget tap → detail view; export.
- **Why it works (from reviews/observations):** "After installing the new updates...the app crashed. Tried reloading several times and even uninstalled and reinstalled the app." — Apple App Store review of Hyros iOS app. The 4.0/8-rating signal is weak; reviewers complain about post-update crashes.
- **Source:** ../competitors/hyros.md ; https://apps.apple.com/us/app/hyros/id6450372574

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Native iOS app (iPhone, iOS 15.6+, 40.8 MB). NO Android. Free, by "Golden Cloud Technology Company Limited."
- **Visualization:** read-only KPI-tile stack + iOS WidgetKit home-screen widget for at-a-glance profit.
- **Layout (prose):** "Read-only profit tracker. Headline metrics: revenue, net profit, net margin, total costs, AOV, average order profit. Performance charts, cost breakdowns, ad spend analysis (Facebook, Google, TikTok). Multi-store aggregated view. iOS widget integration for at-a-glance profit. Background sync."
- **Specific UI:** KPI tiles, performance chart, cost breakdown, ad-spend section, multi-store rollup. iOS widget surfaces a single profit metric on the home screen. Background sync runs continuously (a Feb 2025 changelog entry fixed a continuous-retry-on-error bug).
- **Filters:** Implied date selector; multi-store aggregator.
- **Data shown:** Revenue, net profit, net margin, total costs, AOV, average order profit, ad spend per platform.
- **Interactions:** Background sync; widget tap → app. **No SKU-level or attribution drill-downs in mobile per the review blog** — "Deeper feature dashboards like SKU reports aren't accessible via mobile app yet" (TrueProfit's own review blog).
- **Why it works (from reviews/observations):** "simple to use, straight to the point" / "better than BeProfit" — Apple App Store iOS review (cited in the App Store listing extraction). Recurring Shopify-App-Store theme: "set-and-forget cost tracking" — owners explicitly use the iOS app for ambient profit-checking.
- **Source:** ../competitors/trueprofit.md ; https://apps.apple.com/us/app/trueprofit-profit-analytics/id1568063007

### WooCommerce Native ([profile](../competitors/woocommerce-native.md))
- **Surface:** Official "WooCommerce" iOS / Android app — separate from the desktop Analytics tab.
- **Visualization:** "My Store" stats screen — sales total + top products for a chosen period (Day / Week / Month / Year).
- **Layout (prose):** "A summary screen showing sales and top-performing products for a chosen period (Day / Week / Month / Year). Order list is real-time. Push notifications on new orders/reviews require Jetpack."
- **Specific UI:** "UI details not available — only feature description seen on the marketing page (https://woocommerce.com/mobile/). The marketing page emphasises 'process orders and watch your sales climb in real time' and 'key metrics on the go' without naming specific metrics or screens beyond order processing and the My Store stats card."
- **Filters:** Day / Week / Month / Year period selector.
- **Data shown:** Sales total + top products only. Mobile does NOT replicate the full Analytics report set.
- **Interactions:** Push notifications, order processing, multi-store management (Jetpack-gated). Order processing is the main job-to-be-done; analytics is secondary.
- **Why it works (from reviews/observations):** Adoption is driven by order-fulfilment use cases, not analytics. The WooCommerce profile notes: "Mobile app is real-time for orders but thin on analytics — 'My Store' shows top-line sales + top products only. Web-responsive parity on our analytics views may be enough; native mobile is not table stakes for SMB Woo merchants based on this baseline."
- **Source:** ../competitors/woocommerce-native.md ; https://woocommerce.com/mobile/

### GA4 ([profile](../competitors/ga4.md))
- **Surface:** Google Analytics iOS + Android apps. Free.
- **Visualization:** read-only dashboard cards + Realtime view + a thin slice of standard reports.
- **Layout (prose):** Per the GA4 profile: "Mobile app is read-only and limited to a thin slice of standard reports + realtime."
- **Specific UI:** Realtime card (last 30 min). Standard reports rendered as scrollable card lists. Explorations canvas is NOT available on mobile.
- **Filters:** Date range.
- **Data shown:** Sessions, users, events, conversions, traffic-source breakdowns from the standard reports surface.
- **Interactions:** Read-only consumption.
- **Why it works (from reviews/observations):** Treated as a baseline; no mobile-specific praise. "Mobile app is read-only and limited" is listed as a unique weakness on the GA4 profile.
- **Source:** ../competitors/ga4.md

### Looker Studio ([profile](../competitors/looker-studio.md))
- **Surface:** iOS + Android apps — Pro tier only ($9/user/project/month). Free tier is web-responsive via the April 2025 "Responsive Reports" 12-column grid feature.
- **Visualization:** "By default, Looker Studio reports are displayed as a web report (original version) in the mobile app. If a mobile friendly version of a report is available, a message at the top of the report will prompt you to switch to that view."
- **Layout (prose):** Mobile-friendly view uses the Responsive Reports 12-column grid feature. Limitations: "reports that include lines (such as line, arrow, elbow, or curved) and reports that have mind map or flow chart-like arrangements" do not preserve detail in mobile-friendly view.
- **Specific UI:** Three-dot menu top right toggles between mobile-friendly and original-web views.
- **Filters:** Whatever the report author wired up.
- **Data shown:** View-only consumption of reports the analyst built on desktop.
- **Interactions:** "List of reports, view-only consumption, switch between original and mobile-friendly view."
- **Why it works (from reviews/observations):** Mobile is a Pro-only paywall — i.e., free-tier merchants get nothing. Adoption pattern is "analyst publishes a Pro report, exec views on phone."
- **Source:** ../competitors/looker-studio.md ; https://cloud.google.com/blog/products/business-intelligence/get-looker-studio-pro-for-android-and-ios

## Visualization patterns observed (cross-cut)

Counted across the 12 competitors above:

- **Native iOS + Android with widgets and push:** 3 competitors (Triple Whale, Shopify Native via the Shopify app, Klaviyo). Triple Whale is the only ecommerce-analytics-first product with this combination; Shopify Native and Klaviyo bundle analytics into apps that exist for other primary jobs (admin, email).
- **Native iOS only:** 3 competitors (TrueProfit, StoreHero, Hyros). All are iPhone-first; Android is either explicitly missing (TrueProfit, StoreHero) or under-marketed.
- **Native iOS + Android, paywalled:** 2 competitors (Looker Studio Pro, GA4 — though GA4's app is free, the underlying GA4 product is broadly free).
- **Web-responsive only:** ~15 competitors (Polar Analytics, Lifetimely, Putler, BeProfit, Northbeam, Conjura, Lebesgue, ThoughtMetric, Motion, AdBeacon, Atria, Repeat Customer Insights, Daasity, Stripe Sigma, Wicked Reports, Metorik, SegmentStream, Rockerbox, Varos, Glew, Elevar, Cometly, Profit Calc, Bloom Analytics, Fairing, Zigpoll). For most of these, the mobile gap is acknowledged as a weakness in third-party reviews.
- **Email-as-mobile:** Lifetimely's "delivered to your email inbox and Slack every Monday at 8AM" is the explicit substitute pattern. StoreHero, Glew (Daily Snapshot), and Putler also use email digests this way.

Visual conventions that recur:

- **Single-column scroll of KPI cards** is the universal mobile pattern (Triple Whale, Shopify Native, TrueProfit, Hyros). No competitor renders multi-column grids on phone.
- **Home-screen widget = single hero metric** (Triple Whale, TrueProfit). No competitor exposes multi-metric widgets on mobile home screens.
- **Push semantics differ by tool's job:** Triple Whale = revenue milestones + anomalies. Hyros = tracking-setup issues. WooCommerce = new orders/reviews. Shopify Native = orders/inventory/Flow events. Klaviyo = campaign send/delivery. There is no shared default for "what gets pushed."
- **Mobile is read-only across all 12.** No competitor exposes write actions on mobile (no ad-budget edits, no cost-config changes, no attribution-model toggles persisted server-side). Triple Whale's desktop Ad Budget Management surface is desktop-only.
- **Live View on mobile = orb + dots.** Shopify Native is alone in shipping a 3D-globe live-view on mobile (blue=session, purple=order). The visual idiom maps from the public BFCM globe.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Push notifications + widgets create the daily-check ritual**
- "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design." — paraphrased consensus across multiple 2026 reviews (workflowautomation.net, headwestguide.com), via [triple-whale profile](../competitors/triple-whale.md)
- "Real-time data, clean dashboards, mobile app, and automation consistently save operators 4-8 hours per week." — AI Systems Commerce, 2026 review, via [triple-whale profile](../competitors/triple-whale.md)
- "simple to use, straight to the point" / "better than BeProfit" — Apple App Store iOS review of TrueProfit (cited verbatim in App Store listing extraction), via [trueprofit profile](../competitors/trueprofit.md)
- "just what I needed to track my costs in real time" — Obnoxious Golf (USA), Shopify App Store, April 15, 2026, via [trueprofit profile](../competitors/trueprofit.md)

**Theme: Live View as ambient dashboard**
- "I usually have it open all the time when I am at my computer working on other tasks." — PrimRenditions on Live View, Shopify Community, January 19, 2023, via [shopify-native profile](../competitors/shopify-native.md)

**Theme: Free across all tiers (no paywall)**
- Triple Whale's mobile app is "free for all paid and free-tier customers" including the Founders Dash, including the iOS widget kit and push notifications (frontmatter, [triple-whale profile](../competitors/triple-whale.md)). This is a stated reason brands stay engaged on the free tier.

**Theme: "Set-and-forget" profit awareness**
- Recurring TrueProfit Shopify-App-Store theme: "set-and-forget cost tracking" with "longevity (multiple reviewers in their 2nd-5th year of use)" — implies the iOS widget is the daily touchpoint for these long-term users, via [trueprofit profile](../competitors/trueprofit.md).

## What users hate about this feature

**Theme: Crashes after updates**
- "After installing the new updates...the app crashed. Tried reloading several times and even uninstalled and reinstalled the app." — Apple App Store review of Hyros iOS app, via [hyros profile](../competitors/hyros.md)

**Theme: Mobile feature parity gaps**
- "Deeper feature dashboards like SKU reports aren't accessible via mobile app yet" — TrueProfit's own review blog admission, via [trueprofit profile](../competitors/trueprofit.md)
- "Mobile app has limited screens. No SKU/attribution drill-downs on mobile." — listed under unique weaknesses, [trueprofit profile](../competitors/trueprofit.md)
- "Mobile app is read-only and limited to a thin slice of standard reports + realtime." — listed under unique weaknesses, [ga4 profile](../competitors/ga4.md)

**Theme: Android missing**
- TrueProfit: "Android missing. iOS-only mobile app." — listed under unique weaknesses, [trueprofit profile](../competitors/trueprofit.md)
- StoreHero: "iOS-only mobile app — Android merchants get nothing." — listed under unique weaknesses, [storehero profile](../competitors/storehero.md)

**Theme: Mobile reporting on responsive-web is underwhelming**
- "You can view only a limited number of reports on mobile." — bloggle.app review, 2024, via [polar-analytics profile](../competitors/polar-analytics.md)
- (paraphrased) "mobile reporting could use improvement, especially for monitoring business away from your desk" — third-party review of Polar Analytics, via [polar-analytics profile](../competitors/polar-analytics.md)
- Northbeam profile frontmatter: "no (web-responsive only; mentioned as a limitation in reviews)" — listed in [northbeam](../competitors/northbeam.md)

**Theme: Bot traffic pollutes mobile Live View**
- "The frequent occurrence of bot traffic makes it impossible for me to rely on the Visitor stats… I can tell when I have bot traffic because I'll see the same four dots in the exact same locations: CA, KS, IA, and Ireland! So if I see 20 visitors during a period of bot activity, I know I only really have 4 or 5 actual customers." — PrimRenditions, Shopify Community, January 19, 2023, via [shopify-native profile](../competitors/shopify-native.md)
- "the issues with live view prevent me from using it for anything useful." — PrimRenditions, Shopify Community, January 31, 2023, via [shopify-native profile](../competitors/shopify-native.md)

## Anti-patterns observed

- **Email-as-mobile-substitute** (Lifetimely): "Delivered to your email inbox and Slack every Monday at 8AM" is the explicit fallback when no native app exists. Users tolerate it but it's a one-shot view, not the persistent ambient lens a widget provides. Lifetimely's profile flags "No mobile app. Surface gap" as a noted weakness.
- **Mobile = thin web port** (Polar Analytics, Putler, Lifetimely, Northbeam, BeProfit and most of the responsive-only cohort): The full desktop dashboard rendered on a phone is slow, dense, and not aligned with the daily-check ritual. Reviews flag this directly.
- **Paywalled mobile** (Looker Studio Pro): Mobile apps gated behind a Pro upgrade ($9/user/project/month) cuts off the SMB free-tier user from any mobile experience. The free tier merely gets a 12-column responsive layout — not an app.
- **iOS-only without Android** (TrueProfit, StoreHero, Hyros marketed as iOS-first): Reviewers explicitly call this a gap. For SMB merchants globally, Android is the majority OS.
- **Push notifications scoped to "tracking is broken" only** (Hyros): Push that only fires on infrastructure issues, not revenue/anomaly events, misses the daily-check job.
- **Mobile feature gating that contradicts the daily-check job** (TrueProfit excludes SKU and attribution screens from mobile; GA4 excludes Explorations; Looker Studio mobile-friendly view drops line/arrow/curved-line charts): The screens excluded are sometimes the screens merchants would actually open at 7am.
- **Live View on mobile without bot filtering** (Shopify Native): The 3D globe is visually compelling but the dots are polluted by bot traffic with no native filter, breaking the trust loop on a high-frequency-glance surface.

## Open questions / data gaps

- **Triple Whale and Klaviyo mobile UI details are paywalled.** Both apps require a logged-in account to capture screenshots; KB/help-centre pages return 403 to WebFetch. Specific layout (card sizing, swipe gestures, widget kit configuration) cannot be verified from public sources alone.
- **StoreHero iOS app has no public App Store screenshots** captured in research. Layout, navigation, and parity with email digest are inferred from the features-page text.
- **Push-notification cadence and triggers across all "yes-mobile" competitors** are inconsistently documented. Triple Whale advertises "within minutes of triggering event" but doesn't enumerate which events fire by default. Klaviyo's mobile push semantics are not surfaced in the profile.
- **Android-app feature parity with iOS** is unverified for Triple Whale (the only Android app in the ecommerce-analytics-first cohort). Reviews don't separately rate iOS vs Android.
- **Whether any competitor exposes write actions on mobile** (e.g., toggle an alert, archive an order, pause an ad) — none observed in profiles, but agencies operating from phones may have access patterns not surfaced in public docs.
- **Mobile experience for agencies / multi-store users** is largely unobserved. StoreHero ships an Agency Multi-Store Dashboard but its mobile representation is undocumented.
- **Specific iOS widget sizes (small / medium / large) supported by Triple Whale and TrueProfit** are not enumerated publicly.

## Notes for Nexstage (observations only — NOT recommendations)

- **3 of 12 competitors ship a true native-app-with-widgets-and-push experience** (Triple Whale, Shopify Native via Shopify app, Klaviyo). Triple Whale is the only one of these where the analytics is the primary job of the app rather than a secondary tab. The other 9 competitors either have a pared-down mobile (TrueProfit, StoreHero, Hyros, GA4, Looker Studio Pro, WooCommerce) or no app at all (Polar, Putler, Lifetimely, plus many others noted in master list).
- **Reviewer consensus is that Triple Whale's mobile app is "addictive" and "surpasses Northbeam / Lifetimely / Polar mobile."** It is the bar for the daily-check ritual in this category. Triple Whale's profile explicitly calls it "a genuine differentiator."
- **iOS-only mobile is a pattern but a documented gripe.** TrueProfit, StoreHero, and Hyros are all iPhone-first; reviewers and competitive notes flag the missing Android tier as a real gap. If Nexstage ships mobile, iOS-first matches the pattern but Android within ~6 months matches reviewer expectation.
- **Email + Slack scheduled delivery is the universal "mobile fallback"** (Lifetimely 7am email, StoreHero daily/weekly/monthly digests, Glew Daily Snapshot, Putler Did You Know rotation). It's tolerated but not loved; users still ask for an app.
- **Source-disagreement on mobile is unprecedented.** Hyros's "Reporting Gap" widget is the only example of side-by-side source-attributed numbers on a mobile dashboard, and even that is a single pair (Hyros vs platform). The 6-source-badge thesis (Real / Store / Facebook / Google / GSC / GA4) does not have an existing mobile precedent — direct whitespace.
- **Mobile is read-only across the entire competitor set.** No write actions, no cost-config changes, no attribution-model persistent toggles, no ad-budget edits. The pattern is "view + drill-down + acknowledge alert."
- **Single-column-card-stack is universal.** No competitor uses multi-column grids on phone; the daily-check ritual is satisfied by a vertical scroll of KPI cards. The Shopify Native Live View 3D globe is the only non-card mobile primitive observed.
- **Push semantics are an unsolved design space.** Triple Whale (revenue milestones), Hyros (tracking issues), Klaviyo (campaign sends), Shopify Native (orders/inventory) all push different events by default, and none of them surface a coherent "what should I push to a SMB merchant on a phone at 7am?" framework. Anomaly-detection-driven push (Lebesgue's Guardian alerts, Triple Whale's Lighthouse-now-Anomaly-Detection-Agent) is moving in this direction.
- **The widget pattern (single hero metric on home screen) is shipped only by Triple Whale and TrueProfit.** Both surface a single number; neither exposes a configurable widget. iOS WidgetKit + Android App Widgets support medium and large sizes that competitors don't seem to use.
- **Polar's web-responsive UI is described in reviews as a weak point, despite Polar otherwise being a T1 competitor.** This is a reminder that "responsive web is good enough" is a defensible-on-paper position that fails review-cycle scrutiny.
- **Shopify Native's mobile is a sleeper threat for any third-party tool.** It comes with the Shopify subscription, exposes the Live View 3D globe, and has Sidekick AI free on every plan. SMB merchants using Shopify already have a passable daily-check surface bundled with their commerce platform — third-party mobile apps need to clearly out-perform it.
- **The 7am-with-coffee user question maps to a 60-second interaction budget.** Across the competitor set, the apps that survive that budget (Triple Whale, TrueProfit widget, Shopify Native Live View) all expose a single hero number first, with everything else as scroll-to-discover. Apps that fail the budget (Polar responsive, Lifetimely responsive) try to render full dashboards.
