---
name: Google Analytics 4 (GA4)
url: https://analytics.google.com
tier: T2
positioning: Free, universal web/app analytics from Google with ecommerce events, used by virtually every Shopify/Woo store as the baseline session/attribution layer next to platform-native analytics.
target_market: Any web/app property; SMB through enterprise. Standard tier free with no revenue cap; GA4 360 sold to enterprises (>$50K/yr).
pricing: Free standard tier; GA4 360 starts ~$50K/yr USD list (Google), commonly quoted $150K+/yr in practice on usage-based model.
integrations: Google Ads, Search Console (GSC), Google Merchant Center, BigQuery (free export), DV360, Campaign Manager 360, Firebase, Looker Studio. No native Meta/TikTok/Klaviyo/Shopify ingestion — relies on gtag/GTM events from any source.
data_freshness: Realtime card (last 30 min) + intraday (4-8h std, ~1h on 360) + daily processing (24-48h on standard).
mobile_app: Yes — Google Analytics iOS + Android apps (read-only dashboards, realtime, basic reports).
researched_on: 2026-04-28
sources:
  - https://analytics.google.com
  - https://support.google.com/analytics/answer/9744165
  - https://support.google.com/analytics/answer/10596866 (Attribution)
  - https://support.google.com/analytics/answer/9271392 (Realtime report)
  - https://support.google.com/analytics/answer/7579450 (Explorations)
  - https://support.google.com/analytics/answer/10460557 (Customize navigation)
  - https://support.google.com/analytics/answer/10668965 (Reports snapshot)
  - https://support.google.com/analytics/answer/9805833 (Predictive audiences)
  - https://support.google.com/analytics/answer/11198161 (Data freshness)
  - https://support.google.com/analytics/answer/9383630 (Data thresholds)
  - https://support.google.com/analytics/answer/9823238 (BigQuery export)
  - https://support.google.com/analytics/answer/10737381 (Search Console link)
  - https://www.searchenginejournal.com/google-analytics-4-backlash/411392/
  - https://searchengineland.com/google-analytics-4-we-hate-428942
  - https://measureschool.com/opinions-on-ga4/
  - https://www.optimizesmart.com/ga4-conversion-paths-report/
  - https://measureschool.com/conversion-paths-report-in-ga4/
  - https://www.analyticsmania.com/post/google-analytics-4-explorations/
  - https://easyinsights.ai/blog/how-to-create-and-use-exploration-in-ga4/
  - https://measureschool.com/ga4-realtime-report/
  - https://www.optimizesmart.com/blog/ga4-vs-ga4-360-pricing-limits-billing-and-more/
  - https://medium.com/@accessfuel/why-70-of-shopify-brands-misread-their-ga4-attribution-5b9bb406ede0
  - https://community.shopify.com/t/discrepancies-between-shopify-and-ga4-revenue/382419/13
  - https://www.relevantaudience.com/google-analytics-4-review/
  - https://plausible.io/blog/things-i-hate-about-GA4
---

## Positioning

GA4 is Google's universal web/app analytics platform — the default "free baseline" most Shopify and WooCommerce stores install alongside whatever else they pay for. It is not a merchant-facing ecommerce tool by design: it is an event-collection and reporting layer that happens to ship recommended ecommerce events (`view_item`, `add_to_cart`, `begin_checkout`, `purchase`) and an Ecommerce purchases / Purchase journey funnel report. Its real positioning angle is being free, owned by Google (so it powers Google Ads bidding and audiences), and connected upstream to Search Console + Google Ads. For Nexstage's ICP, GA4 competes by being already-installed and infinite — but is broadly resented for opacity, latency, thresholding, and complexity.

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| GA4 Standard | Free | Unlimited events, all standard reports, Explorations (with row/event caps), free BigQuery export (1M events/day cap on standard properties), realtime, predictive audiences, attribution paths, Search Console link, Google Ads link | Hitting BigQuery export cap, intraday SLA needs (sub-4h), removing data thresholds, larger Exploration row limits |
| GA4 360 | ~$50,000/yr list, frequently $150K+ in practice; usage-based on event volume | Higher quotas, faster intraday processing (~1h vs 4-8h), larger Exploration row limits, sub-properties + roll-ups (billed at half-event), enterprise SLA | Acquired by enterprises with sub-property segregation, heavy DV360/CM360 usage, sampled-data avoidance |

Notes: 360 was historically a fixed-fee model and is migrating to usage-based pricing keyed to event volume, per Cardinal Path / Optimize Smart write-ups. Standard tier has no revenue cap. The Cardinal Path / Optimize Smart sources note "rollup properties and subproperties — come at some additional cost. Events that you collect and then push into either a rollup or subproperty will be billed as if they were half of an event."

## Integrations

**Pulled from (sources):** Web data streams (gtag.js / GTM), iOS data streams (Firebase SDK), Android data streams (Firebase SDK), measurement protocol (server-side). All ecommerce data is whatever the gtag implementation pushes.

**Linked products (push/pull):**
- **Google Ads** — bidirectional (audiences, conversions, attribution)
- **Search Console** — GSC organic queries + landing page metrics surfaced in two extra report tiles
- **BigQuery** — free raw event export, daily + streaming/intraday (1M events/day cap on standard)
- **Display & Video 360, Campaign Manager 360, Search Ads 360** — enterprise/Google-stack only
- **Google Merchant Center** — product feed link
- **Firebase** — for app properties

**Coverage gaps for ecommerce:**
- No native Shopify or WooCommerce order/COGS pull. All ecommerce data must come through gtag events; refunds/returns require explicit `refund` event firing.
- No native Meta Ads / TikTok Ads / Pinterest Ads / Snap Ads ingestion — those clicks land in GA4 only as UTMs.
- No Klaviyo / Postscript / email-platform ingestion.
- No COGS / margin import — there is no concept of cost-per-product in GA4 reporting beyond `purchase_revenue` minus `tax` / `shipping` parameters captured at event time.
- No agency/multi-store roll-up on Standard (sub-properties are 360-only).

## Product surfaces (their app's information architecture)

The default left navigation (after Google's late-2024 / early-2025 layout updates and Library customizations available to Editors/Admins) has these top-level entries:

- **Home** — personalized landing with cards Google selects based on your usage; common cards: Users last 30 min, Revenue, Acquisition, Insights anomalies.
- **Reports** — standard report collections; default "Life cycle" + "User" collections are pinned and others can be published from Library.
  - **Reports snapshot** — 1-16 summary cards (charts/tables) overview, customizable from three templates: User behavior, Sales and revenue, Marketing performance.
  - **Realtime** — last 30 min activity, geo map + insight cards + per-user snapshot timeline.
  - **Acquisition overview / User acquisition / Traffic acquisition** — channel/source/medium/campaign breakdown.
  - **Engagement overview / Events / Conversions (Key events) / Pages and screens / Landing page** — what users do.
  - **Monetization overview / Ecommerce purchases / In-app purchases / Publisher ads / Promotions** — revenue surfaces.
  - **Retention** — cohort retention, lifetime value chart, engagement by cohort.
  - **User attributes / Tech** — demographics, devices, browsers, app versions.
- **Explore (Explorations)** — three-panel ad-hoc analysis canvas. Seven techniques: Free form, Funnel exploration, Path exploration, Segment overlap, Cohort exploration, User explorer, User lifetime.
- **Advertising** — attribution-focused area:
  - **Advertising snapshot / Performance / All channels / Model comparison / Conversion paths (Attribution paths) / Attribution settings**.
- **Admin** — property/account configuration; nested under it: Data streams, Events, Key events (formerly Conversions), Audiences, Custom definitions, Attribution settings, Data retention, Product links (Google Ads, Search Console, BigQuery, Merchant Center, etc.).
- **Library** (bottom-left, Editor/Admin only) — manage report Collections + Topics that appear in the left nav.
- **Insights** (right-rail panel, accessible from many views) — auto-anomaly detection cards.

## Data they expose

### Source: Web/app event streams (gtag, GTM, Firebase, Measurement Protocol)
- Pulled: events + parameters defined by the implementer. Recommended ecommerce events — `view_item_list`, `view_item`, `select_item`, `add_to_cart`, `view_cart`, `remove_from_cart`, `begin_checkout`, `add_payment_info`, `add_shipping_info`, `purchase`, `refund`. Item parameters: `item_id`, `item_name`, `item_brand`, `item_category` (1-5), `item_variant`, `price`, `quantity`, `discount`, `coupon`, `affiliation`, `currency`. Top-level parameters: `transaction_id`, `value`, `tax`, `shipping`, `currency`, `coupon`.
- Computed: Sessions (engaged-session-based), Active users, New users, Engagement rate (replaces bounce rate), Average engagement time, Events per session, Item-list CTR, Cart-to-view rate, Purchase-to-view rate, ARPU, ARPPU, LTV, Predictive metrics (Purchase probability, Churn probability, Predicted revenue).
- Attribution windows: configurable acquisition lookback (7d / 30d) and key-event lookback (30d / 60d / 90d depending on event type) under Admin > Attribution settings.

### Source: Google Ads (linked)
- Pulled: campaigns, ad groups, keyword/audience cost, clicks, impressions; conversions are pushed back from GA4.
- Computed: ROAS shown in Advertising > Performance via cost ÷ value. Cross-channel attribution credits Google Ads alongside other channels using selected model.

### Source: Search Console (linked)
- Pulled: search queries + landing page rows from GSC (16-month window). Surfaced as two extra cards on Acquisition overview: Google Organic Search Traffic (impressions, clicks, CTR by landing page) and Google Organic Search Queries (impressions, clicks, CTR by query).
- Limitations: cannot drill GSC data by GA4 user/session dimensions — only by GSC-native dimensions (Country, Device).

### Source: BigQuery export (Product Link)
- Pulled (out, not in): Raw event-level rows including user_pseudo_id, event_timestamp, event_name, event_params, user_properties, items array, device, geo, traffic_source, ecommerce. Free for everyone; 1M event/day cap on standard properties.

### Attribution models offered
- **Data-driven attribution (DDA)** — default since 2023. ML-distributes credit across up to 50 touchpoints in the 90 days pre-conversion. Requires 400+ key-event conversions and 20,000+ total conversions across all key events in the lookback window to actually run.
- **Paid and organic last click** — 100% credit to last click, ignoring direct.
- **Google paid channels last click** — 100% credit to most recent Google Ads touchpoint; falls back to Paid+organic last click if no Google Ads touch exists.

(GA4 removed first-click, linear, time-decay, and position-based models from the in-product UI in 2023. Reports continue to function but the picker is reduced to these three.)

## Key UI patterns observed

### Reports snapshot (overview)
- **Path/location:** Reports > Reports snapshot (top of Reports collection).
- **Layout (prose):** Top: page header with date-range picker (preset menu + custom calendar) + comparison toggle that adds a second period side-by-side; right side has "Insights" rail launcher. Body: tiled grid of summary cards, 1-16 cards configurable. Default cards include: Users line chart, Users in last 30 min (mini bar timeline + total), New users by First user default channel group (table), Top campaigns (table), Top events by event count (table), Sessions by Default channel group (bar chart), Top pages and screens (table), Key events (table). Each card has a trailing "View [report] >" link to drill to the parent report. Templates available are "User behavior", "Sales and revenue", "Marketing performance".
- **UI elements:** Filled material-design cards with thin grey borders. Cards have title + dimension dropdown for some + chart/table area + footer link. No stoplight indicators, no sparklines per row in tables. Hover on chart points reveals timestamp + value tooltip. Drag-handle icons on cards in edit mode.
- **Interactions:** Edit mode (top-right pencil) allows drag-reorder, "x" to remove, "+ Add cards" to add from a curated card library. Date-range and comparison apply globally to every card.
- **Metrics shown:** Users, New users, Sessions, Average engagement time, Event count, Conversions/Key events, Purchase revenue, Total revenue, Top channel/source/medium dimensions.
- **Source:** [GA4 Reports snapshot help](https://support.google.com/analytics/answer/10668965)

### Realtime report
- **Path/location:** Reports > Realtime.
- **Layout (prose):** Full-bleed top-of-page **3D-style "globe" map** (geo bubble visualization showing concurrent users by country) on the left, "Users in last 30 min" big-number card on the right with mini per-minute bar chart underneath (one bar per minute, ~30 bars total, leftmost = 30 min ago, rightmost = now). Below the hero row, a grid of 6 insight cards: Users by First user source / medium / campaign (with dropdown), Users by Audience (with dropdown), Views by Page title and screen name, Event count by Event name, Conversions by Event name, User property values. Top-right of the page exposes a **"View user snapshot"** button.
- **UI elements:** Each card has a dimension dropdown ("first user medium" vs "source/medium" etc). Hovering a row reveals percentage of total. No drill-on-click into a row from realtime.
- **Interactions:** "View user snapshot" opens a side panel showing one randomly-selected active user's session timeline of events, with arrow buttons to step to next/previous user; clicking an event in the timeline expands its parameters. Up-to-4-way comparison view available; map disappears when comparisons are active.
- **Metrics shown:** Active users last 30 min, users by source/medium/campaign/audience, page views, events, conversions.
- **Source:** [GA4 realtime help](https://support.google.com/analytics/answer/9271392) + measureschool.com walkthrough.

### Ecommerce purchases report
- **Path/location:** Reports > Monetization > Ecommerce purchases.
- **Layout (prose):** Header: date-range + comparison + Search bar. Top of body: line chart of selected metric (default Items viewed) over time. Below: a sortable table with first column dimension switcher (default `Item name`; alternatives: `Item ID`, `Item brand`, `Item category` 1-5, `Item variant`). Default visible columns include `Items viewed`, `Items added to cart`, `Items checked out`, `Items purchased`, `Item revenue`. Pagination at bottom (default 10 rows, expandable).
- **UI elements:** Standard GA4 sortable table; ascending/descending arrows on column headers; row-level mini bar chart in numeric cells (subtle horizontal bar reflecting share of column total). No color-coded delta cells, no source badges. "Edit comparisons" pill in upper-left area lets users add a 2nd cohort filter.
- **Interactions:** Click column header to sort; click row to add as filter (not drill-down). Search box filters rows by name.
- **Metrics shown:** Items viewed, Items added to cart, Items checked out, Items purchased, Cart-to-view rate (computed), Purchase-to-view rate (computed), Item revenue, Item refund amount.
- **Source/screenshot:** UI details mostly described from public help docs / Bigcommerce walkthrough; live UI behind login. (Help: [GA4 ecommerce reports overview](https://support.google.com/analytics/answer/9744165))

### Purchase journey (funnel)
- **Path/location:** Reports > Monetization > Purchase journey (also In-app purchase journey for apps).
- **Layout (prose):** Pre-built 4-step horizontal funnel: Session start → View product → Add to cart → Begin checkout → Purchase, with conversion-rate percentage between steps and an absolute count + drop-off count per step.
- **UI elements:** Wide funnel rectangles colored in shades of blue, with abandoning users represented as outflow arrows angled downward. Below the funnel: a breakdown table showing Sessions / Step completions / Abandonment rate by Device category (default), with dimension switcher.
- **Interactions:** Step labels cannot be edited from this report (you must use Funnel exploration to customize).
- **Source:** Various agency walkthroughs (e.g., measuremindsgroup.com), help docs.

### Explorations canvas (3-panel)
- **Path/location:** Explore (left nav, second from top).
- **Layout (prose):** Three vertical regions side-by-side:
  - **Variables panel** (leftmost, ~200px): Exploration name field, date-range selector, then sections for **Segments** (with "+" plus button to import / build), **Dimensions** (+ button), **Metrics** (+ button). Hard caps shown: 20 dimensions, 20 metrics, 10 segments per exploration.
  - **Tab Settings panel** (middle, ~250px): Technique selector at top (icon row of 7 — Free form, Cohort exploration, Funnel exploration, Segment overlap, Path exploration, User explorer, User lifetime). Drop zones below for Visualization, Segment comparisons, Rows, Columns, Values, Filters — exact zones depend on selected technique.
  - **Canvas** (right, fills remaining width): Up to 10 tabs across the top (`+` to add). Body renders the visualization. Free form supports 6 visualizations: Table, Donut chart, Line chart, Scatter plot, Bar chart, Geo map.
- **UI elements:** Drag-and-drop chips for dimensions/metrics from Variables → Tab Settings drop-zones. Double-click on a Variables item also adds it to the active drop-zone. Right-click on a row in the canvas table reveals contextual menu (Include selection, Exclude selection, Create segment from selection, View users).
- **Interactions:** Save → exploration is private to creator unless explicitly shared. Export to CSV / Google Sheets / TSV / PDF available. Live data refresh on filter change.
- **Metrics shown:** User-defined.
- **Source:** [GA4 Explorations help](https://support.google.com/analytics/answer/7579450), analyticsmania.com guide, easyinsights.ai walkthrough.

### Funnel exploration
- **Path/location:** Explore > Funnel exploration template, OR start blank → choose "Funnel exploration" technique.
- **Layout (prose):** Same Variables/Tab Settings/Canvas frame as other Explorations. Tab Settings adds a "Steps" panel where the user defines an ordered list of step conditions (event = X, page_path contains Y, etc.) with a pencil edit button. Visualization toggle at top of canvas: "Standard funnel" (vertical bar chart with conversion% between bars + abandon-arrows on the side) or "Trended funnel" (line chart of step completion over time).
- **UI elements:** "Make open funnel" toggle (allows users to enter at any step). "Show elapsed time" toggle reveals median time-between-step. Per-step breakdown table beneath the funnel; breakdown dimension chosen via "Breakdown" drop-zone in Tab Settings.
- **Interactions:** Right-click on a step → "View users from this step" opens User explorer pre-filtered. "Next action" toggle below funnel surfaces the most-common next event per step.
- **Source:** measureschool.com funnel exploration tutorial.

### Path exploration
- **Path/location:** Explore > Path exploration.
- **Layout (prose):** Canvas shows a left-anchored event/page node with branching outflow nodes to next-step events, then next-next, etc. Each node is a horizontal bar with event-name + count and a thickness proportional to volume. Configure starting/ending point at top of canvas; can run forward (start node → forward) or backward from a conversion (set end node → backward).
- **UI elements:** Click any node to expand its next-step children (up to 5 nodes per step, sortable by event count or unique users). "Reset" button at top-right.
- **Source:** ridemotive.com / dreamhost.com walkthroughs.

### Advertising > Attribution > Conversion paths (a.k.a. Attribution paths)
- **Path/location:** Advertising > Attribution > Conversion paths.
- **Layout (prose):** Top filter strip: conversion-event selector (defaults to all key events), date-range, "Path length = all touchpoints" filter chip, "+ Add Filter" button. Below: a horizontal **3-segment touchpoint visualization** — three colored stacked bars labeled "Early touchpoints (first 25%)", "Mid touchpoints (middle 50%)", "Late touchpoints (last 25%)", each segmented by Default channel group with channel labels and credit-percentages. Above the bars: an attribution-model dropdown (Data-driven / Paid+organic last click / Google paid last click) and a dimension dropdown (Default channel group / Source / Medium / Campaign). Below the visualization: a sortable table with columns `Default channel group | Conversions | Purchase revenue | Days to conversion | Touchpoints to conversion`.
- **UI elements:** Hovering a colored segment shows the channel's share of credit at that path stage. Hovering a row in the table shows credit distribution across all three stages for that channel. The 3-segment bar collapses Mid if path length < 3 and shows only Late if path length = 1.
- **Interactions:** Switching the model dropdown re-renders the segments live. Clicking a row filters the visualization to that channel's paths.
- **Metrics shown:** Conversions, Purchase revenue, Days to conversion, Touchpoints to conversion, attributed credit per channel × stage.
- **Source:** [optimizesmart.com walkthrough](https://www.optimizesmart.com/ga4-conversion-paths-report/), measureschool.com guide.

### Advertising > Attribution > Model comparison
- **Path/location:** Advertising > Attribution > Model comparison.
- **Layout (prose):** Top: model selector A vs model selector B (defaults to Data-driven vs Cross-channel last click). Body: side-by-side table with rows = Default channel group (or Source/Medium/Campaign via dimension picker) and 3 metric columns per side: Conversions (model A), Conversions (model B), `% change` column. A 4th column "Revenue" repeats the same trio. Color-coded delta cells (green ▲ for >0%, red ▼ for <0%) on the % change columns.
- **UI elements:** The two model dropdowns are sticky at the top. Date range applies to both models simultaneously.
- **Interactions:** Switching either model re-renders both columns; switching dimension re-aggregates rows.
- **Metrics shown:** Conversions count + Revenue per model + delta %.
- **Source:** netpeak.us walkthrough, measureschool.com attribution models guide.

### Audience builder
- **Path/location:** Admin > Audiences > New Audience.
- **Layout (prose):** Modal/page split into left "templates" rail and right canvas. Templates: General (e.g., recently active users, non-purchasers), Template (Demographics, Predictive, Recently active), Predictive (must meet ML model thresholds — surfaced as "Ready to use" badge). The canvas builds a step-based condition group: "Include users when" + condition rows + "AND/OR" stack + optional "And exclude when" block. Each row supports a sequence-step toggle, "for any visit" / "across all sessions" scope picker, and a time-window picker. Right rail: live "Users in last 30 days" estimate updates as conditions change.
- **UI elements:** For predictive audiences, a histogram slider lets the user pick a threshold percentile and shows estimated audience size + likelihood-to-convert score live as the slider moves.
- **Source:** [Predictive audiences help](https://support.google.com/analytics/answer/9805833), analyticsmania.com, hawksem.com.

## What users love (verbatim quotes, attributed)

- "GA4's data model is excellent." — Dana DiTomaso, Kick Point, surveyed in MeasureSchool's "120 Users Share Their Opinions on GA4" report (2023).
- "Enhanced Measurement is very simple to set up, and because GA4 does the heavy lifting for you, it is less prone to data quality issues." — Brian Clifton, Verified Data, MeasureSchool survey, 2023.
- "GA4 has certainly made progress in winning the hearts and minds of the marketers and analysts who responded." — Ken Williams, Further, MeasureSchool survey, 2023.
- "Google Analytics 4 is improving. It is not perfect, but it already offers good benefits and is continually improving." — Antonio Fernandez, Founder & CEO Relevant Audience, August 2022.
- "Once set up properly, it becomes an essential daily tool for data-driven decisions." — paraphrased aggregate G2 reviewer language as summarized in G2 listing meta-summary, 2026 (G2 listing page returned 403 to direct fetch; quote pulled from G2-aggregated synthesis).

(Note: limited verbatim G2 / Capterra quotes available — both review-platform pages were rate-limited / 403'd on direct fetch; Trustpilot has no listing for GA4 since it's not a paid SaaS. Quotes captured here are from public marketer surveys, blog posts, and Twitter — all verifiable.)

## What users hate (verbatim quotes, attributed)

- "GA4 sucks... Let's make a lot of the important data hard to access." — Dave Davies (@beanstalkim), Twitter, June 23, 2021 (cited in Search Engine Journal).
- "the new GA4 just HORRIBLE? It's like it's designed only for retail sites." — Trevor Long (@trevorlong), Twitter, June 23, 2021.
- "Google Analytics 4 is making me cry… I've never seen a tool upgrade that made simple things sooo complicated." — Gill Andrews (@StoriesWithGill), Twitter, June 22, 2021.
- "Pretty much unusable for most folks... the old analytics was better." — Victor Jónsson (@victorjonsson), Twitter, June 22, 2021.
- "It's awful! Try tracking events with GTM and GA4. I'm giving up." — Stephanie Lummis (@stephanielummis), Twitter, June 22, 2021.
- "The data latency is a joke, taking 12-24 hours to report on what is happening prevents this from being an actionable tool." — Ron Weber, Sr Director at Actian, quoted in Search Engine Land, "10 things we hate about Google Analytics 4," 2022.
- "GA4 is a disaster. It is so much harder to use than UA, and completely non-intuitive." — Jason McDonald, SEO consultant, quoted in Search Engine Land, 2022.
- "Some of the features I use every day are missing or extremely complicated to find in GA4." — Elizabeth Rule, Sterling Sky, quoted in Search Engine Land, 2022.
- "There isn't a 'GA4 Lite' for users who only need basic data." — Eb Gargano, Productive Blogging, quoted in Search Engine Land, 2022.
- "The platform still has a lot of bugs… spending more time figuring out why attribution is not properly labeled." — John McAlpin, SEO consultant, quoted in Search Engine Land, 2022.
- "GA4 is just a joke, a bad joke. Worst tool ever." — title of a Google Analytics Community thread (#250707295), Google Support Forum.
- "I was there for G+, Google Talk, Picasa, Google Notebook…. and now… GA4. I have Google PTSD." — Twitter user, surfaced in Search Engine Journal backlash article, 2021.

## Unique strengths

- **Free at any scale.** No revenue cap, no event cap on standard tier. Nothing else in the merchant analytics category does this.
- **Free raw event export to BigQuery** (1M events/day cap on standard) — the only major analytics product with no-cost row-level data export at this volume; this is enterprise-tier for every other vendor.
- **Native Google Ads + Search Console integration.** No other tool can pull GSC organic queries / impressions and surface them inline next to attribution data without a separate connector.
- **Predictive audiences (purchase probability, churn probability, predicted revenue)** — ML-derived, computed by Google, syncable to Google Ads as audiences. Requires ≥1,000 positive + ≥1,000 negative samples over 28 days.
- **Cross-platform unified user identity** (web + iOS + Android) via Firebase + Google signals, joining sessions across devices.
- **Explorations canvas with 7 techniques** (Free form, Funnel, Path, Segment overlap, Cohort, User explorer, User lifetime) — a single ad-hoc analysis surface with depth no SMB-targeted ecommerce analytics tool matches.
- **User snapshot in realtime** — visual replay of one random active user's event timeline; unique among free tools.

## Unique weaknesses / common complaints

- **24-48h data latency on standard properties.** Standard reports are not actionable for intraday decisions; only realtime + a subset of intraday cards refresh quickly.
- **Data thresholding hides rows for low-traffic sites.** Below ~50 users per dimension row, data is suppressed for "privacy" — disproportionately hits SMB sites under 1,000 users/day.
- **Attribution model picker reduced from 7 (UA) to 3 (DDA / Paid-organic last click / Google last click)** — first-touch, linear, time-decay, and position-based were removed in 2023.
- **Data-driven attribution requires 400 conversions/key event + 20K total** — most SMBs don't qualify and silently fall back to last-click-equivalent behavior.
- **Discrepancies vs Shopify orders.** "If Google Analytics misses 20% of conversions, ROAS looks 20% worse than it actually is" — sources widely cite 10-30% client-side tracking loss from ad blockers, consent denials, iOS, and Brave; Medium "70% of Shopify brands misread their GA4 attribution."
- **Steep learning curve.** Recurring complaint that even simple Universal-Analytics tasks (e.g., bounce rate, behavior flow) now require building Explorations.
- **No COGS / margin / profit awareness.** GA4 only knows revenue, tax, and shipping at event level; there is no concept of cost of goods, cost of ads (other than Google Ads cost), or net profit.
- **Sub-properties + roll-ups are 360-only.** Multi-store / agency consolidation requires the enterprise tier.
- **GSC integration is shallow.** GSC data can only be sliced by Country and Device — cannot join GSC clicks to GA4 user/session dimensions.
- **No saved-view permissions or per-team workspaces** beyond Library Collections (which are property-wide).
- **Mobile app is read-only and limited** to a thin slice of standard reports + realtime.

## Notes for Nexstage

- **GA4 is the universal "session/attribution lens" baseline merchants already have.** Nexstage's GA4 source badge needs to surface ≥one metric (sessions, channel-attributed revenue, GSC clicks) where GA4 is the obvious authoritative source, otherwise users will keep one tab open on GA4. Conversely, GA4's discrepancies vs Shopify orders are a wedge — showing Real (Store) and GA4 numbers side-by-side directly addresses the "70% misread attribution" complaint.
- **Their attribution model picker (DDA / Last click paid+organic / Google last click) and the Model Comparison report's side-by-side delta-percentage layout is a direct analog to Nexstage's 6-source badge thesis.** Note their model selector lives at the report level (one toggle re-renders the page), not at the metric level — different mental model from per-KPI source switching.
- **The 3-segment Early/Mid/Late touchpoint bar in Conversion paths is a distinctive viz** worth flagging for any path/journey work; Triple Whale, Northbeam, Polar do not visualize touchpoints this way.
- **Explorations is a power-user surface (7 techniques, 3-panel canvas, drag-drop)** that no SMB-focused ecommerce analytics tool replicates — but the volume of complaints suggests merchants would rather have pre-built reports than canvas-building. The complexity is itself a wedge for Nexstage's "opinionated/curated" positioning.
- **Data thresholding under ~50 users/row directly hits Nexstage's SMB target**. If Nexstage shows merchants their own GA4-suppressed dimensions reconstructed from store-side data, that is a concrete, demonstrable advantage.
- **GA4's predictive audiences (purchase probability, churn probability, predicted revenue) require 1,000+ positive samples** — most Nexstage-target SMBs (under ~$1M ARR) don't qualify, so this is more strength on paper than in practice for the segment.
- **GA4 has no COGS or net-profit concept** — every blended-margin / true-profit-per-order story is a clean "GA4 cannot do this" wedge.
- **The "Google PTSD" trope is real and recurring in DTC Twitter.** Merchants who feel GA4 was forced on them are pre-disposed to try alternatives; positioning around "stop fighting GA4" is plausible.
- **GA4's Search Console integration is the only first-party place merchants see GSC clicks alongside revenue.** If Nexstage pulls GSC directly, the comparison should be: Nexstage joins GSC clicks to actual orders by URL, whereas GA4 keeps GSC siloed (cannot drill by GA user/session dims).
- **Realtime user snapshot (random user timeline replay) is genuinely beloved** — anything Nexstage builds in "live shopper view" should reference this as a baseline UX pattern to either match or improve on.
- **Pricing-wise, "free GA4" is not a competitive threat** — it is assumed installed. Merchants pay for tools that interpret/clean/blend data GA4 cannot. Position Nexstage on top of GA4, not against it.
- **Google has been removing models/features** (linear, first-click, time-decay attribution all gone; bounce rate gone then partially restored; behavior flow gone). Recurring removal feeds merchant distrust — useful framing for Nexstage's commitments to data permanence.
