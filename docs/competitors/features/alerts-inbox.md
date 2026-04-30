---
name: Alerts Inbox
slug: alerts-inbox
purpose: Tell the merchant when a metric they care about has changed enough to warrant attention, surfaced in one place they actually open.
nexstage_pages: dashboard, performance, ads, seo, integrations
researched_on: 2026-04-28
competitors_covered: triple-whale, lebesgue, conjura, daasity, klaviyo, polar-analytics, northbeam, lifetimely, storehero, shopify-native, atria
sources:
  - ../competitors/triple-whale.md
  - ../competitors/lebesgue.md
  - ../competitors/conjura.md
  - ../competitors/daasity.md
  - ../competitors/klaviyo.md
  - ../competitors/polar-analytics.md
  - ../competitors/northbeam.md
  - ../competitors/lifetimely.md
  - ../competitors/storehero.md
  - ../competitors/shopify-native.md
  - ../competitors/atria.md
  - https://www.triplewhale.com/moby-ai
  - https://www.triplewhale.com/blog/triple-whale-product-updates-april-2026
  - https://lebesgue.io/ai-agents
  - https://www.conjura.com/performance-trends-dashboard
  - https://www.polaranalytics.com/business-intelligence
  - https://help.klaviyo.com/hc/en-us/articles/4708299478427
  - https://help.daasity.com/core-concepts/dashboards
---

## What is this feature

An alerts inbox is the surface where the analytics product proactively tells the merchant — "this metric moved enough that you should look." It collapses the gap between "data exists in a dashboard" and "merchant noticed in time to act." The merchant question is some variant of: *"What changed since I last checked, and was it a problem or a win?"* Without an alerts surface, SMB Shopify/Woo owners check the same five KPI tiles every morning at 7am and miss anything that breaks at 2pm on a Tuesday — even though every ad platform, store backend, and email tool already has the raw data needed to flag it.

The difference between "having the data" and "having this feature" is threefold: (1) **detection** — a rule (threshold-based) or model (z-score / forecast residual / Prophet anomaly) decides "this is unusual"; (2) **routing** — the alert lands somewhere the merchant actually checks (in-app inbox, mobile push, Slack, email digest); and (3) **triage** — severity, suggested action, and acknowledgement state so a busy operator can clear the queue. Competitors split sharply on whether the inbox is a first-class IA surface (Triple Whale Lighthouse, now folded into Moby's Anomaly Detection Agent) or a thin notification layer over scheduled emails (Lebesgue, Conjura). The naming convention "Guardian / Sentinel / Sentry" recurs across competitors (Lebesgue's older agent set; Triple Whale's deprecated Lighthouse) — vendors reach for security metaphors when describing it.

## Data inputs (what's required to compute or display)

For each input, name the source + the specific field/event:

- **Source: Computed (any KPI source)** — `daily_snapshots.{revenue, orders, sessions, conversion_rate, ad_spend, mer, cac}` time-series; baseline `mean`/`stddev` over rolling window (7d/14d/28d common), z-score or % delta vs. forecast.
- **Source: Computed** — anomaly threshold rule: `actual_value` outside `[mean − k·stddev, mean + k·stddev]` (k typically 2–3); or static threshold `actual_value < user_defined_floor` / `> user_defined_ceiling`; or pacing rule `cumulative_actual / pro_rated_target < pacing_threshold`.
- **Source: Shopify / WooCommerce** — `orders.created_at` / `orders.financial_status` / `orders.refunds` (for revenue + refund anomalies); `inventory_levels.available` (for low-stock alerts).
- **Source: Meta / Google / TikTok / Snap / Reddit Ads APIs** — `campaigns.spend`, `campaigns.status` (paused unexpectedly), `ads.delivery_status`, `account.spending_limit_remaining` (capped/depleted alerts), `accounts.disapprovals` (policy violations).
- **Source: Klaviyo / email-SMS** — `flow.status`, `flow.revenue`, `campaigns.deliverability` (spam-rate spike, bounce-rate spike).
- **Source: GA4** — `sessions`, `bounce_rate`, `page_speed` (site performance anomalies).
- **Source: GSC** — `queries.clicks` / `queries.impressions` / `queries.position` per property (search-traffic drop alerts).
- **Source: User-input / config** — alert rule definitions: metric, scope (workspace / store / channel / campaign), threshold type (absolute / percent / z-score), severity, delivery channel (in-app / Slack / email / push), recipients, snooze.
- **Source: System events** — `integration.disconnected`, `oauth_token.expired`, `pixel.events_dropped`, `cost_recompute.completed`, `import.failed` (operational alerts).
- **Source: Computed** — RFM segment migration counts (e.g., "12 customers moved Champions → At Risk this week") for retention alerts.
- **Source: User-acknowledgement state** — `alert.acknowledged_at`, `alert.snoozed_until`, `alert.assigned_to_user_id`, `alert.resolution_note`.

## Data outputs (what's typically displayed)

For each output, name the metric, formula, units, and typical comparisons:

- **KPI: Alert count by severity** — `COUNT(*) FILTER (severity = 'critical' / 'warning' / 'info')` across unread alerts; integer; vs. last 7d.
- **Dimension: Alert type** — categorical: anomaly / threshold / pacing / integration-health / inventory / deliverability / policy.
- **Dimension: Severity** — three-state (critical / warning / info) most common; some use binary (urgent / FYI) or color-only (red / yellow / green).
- **Field: Metric** — the underlying metric (Revenue, ROAS, MER, Spend, CTR, Conversion Rate, Sessions, etc.).
- **Field: Direction** — up / down / paused / disconnected.
- **Field: Magnitude** — `actual_value`, `expected_value` (or baseline), `delta_pct = (actual − expected) / expected`, `z_score`.
- **Field: Scope** — workspace / store / ad-account / campaign / adset / ad / product / segment.
- **Field: Detected at** — timestamp; often "minutes ago" or "X hours ago" in UI.
- **Field: Suggested action** — one-line text or deep-link ("Pause this campaign" / "Re-authorize Google Ads" / "Check Klaviyo deliverability").
- **Breakdown: Alerts × time** — alerts-per-day sparkline (rare; only Triple Whale Lighthouse implies it).
- **Breakdown: Alerts × source** — counts per integration (rare; usually ad platforms vs. store vs. email).
- **State: read / unread / acknowledged / snoozed / resolved** — for inbox triage UX.

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Lighthouse (legacy brand, 2024) → Sidebar > Moby Agents > Anomaly Detection Agent + Order/Revenue Pacing Agent + Revenue Anomaly Agent (2025–2026 IA). Push notifications fire on the iOS/Android app within minutes of revenue milestones.
- **Visualization:** Vertical alert-card list (inbox pattern) plus mobile push toasts; AI-Audience tiles attached to the same surface as a sibling block.
- **Layout (prose):** Top: filter strip / severity counts. Main canvas: scrollable list of alert cards, each card stamped with metric + direction + magnitude + suggested action. Right rail or sub-section: Rules Report monitoring user-configured automated rules. Sibling block: 6 pre-built RFM AI Audiences ready to push to Meta. On mobile, alerts surface as native push notifications and home-screen widgets — multiple reviews call this addictive.
- **Specific UI:** "Alert cards with severity / metric / suggested action." Triple Whale's own copy uses the spellings "Orders Anomalie" and "Spend Anomalie" — reviewer-visible artifacts of fast iteration. Push notifications "fire within minutes of the triggering event" (mobile-app marketing). Moby Agents are credit-priced with "no auto overages — credits pause when depleted" (fail-closed billing).
- **Filters:** Severity, alert type (Spend / Orders / Inventory / Revenue), date range, store.
- **Data shown:** Metric, direction, magnitude, anomaly-type tag, suggested action, push-to-Meta CTA (for AI-Audience entries).
- **Interactions:** Acknowledge alert, drill into anomaly cause, push generated audience to Meta, on-demand refresh (April 2026 button), agentic auto-action via Moby Agents.
- **Why it works (from reviews/observations):** Mobile push is the most-praised surface across 2026 reviews — "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design" (workflowautomation.net consensus). Reviewers also note it surpasses Northbeam / Lifetimely / Polar mobile.
- **Source:** [triple-whale.md](../competitors/triple-whale.md), https://www.triplewhale.com/moby-ai, https://www.triplewhale.com/blog/triple-whale-product-updates-april-2026

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Two surfaces. (1) Daily / Weekly Email Reports — scheduled email digest; multiple Capterra reviewers cite this as a top retention reason. (2) AI Agents hub: older marketing referenced a **"Guardian"** agent (also Sentinel / Sentry / Auditor); newer page renames to "Revenue Drop Investigator" — directly implies anomaly investigation.
- **Visualization:** Email digest (scheduled) with KPI table + pacing bars + forecast sparklines; in-app the Guardian/Revenue-Drop-Investigator agent is a tile/card with a launch button.
- **Layout (prose):** Email body: top KPIs with period-over-period delta colored **blue for improvements, red for declines** (note: blue not green for positive — unusual, possibly colorblind-aware). In-app AI Agents hub renders agents as a tile grid; clicking a tile launches a focused workflow that surfaces findings rather than a passive inbox.
- **Specific UI:** "Color-coded performance indicators (blue for improvements, red for declines)" inline in Business Report (per their feature page). Email digests deliver "metrics and pacing data" on a daily/weekly cadence. The Revenue Drop Investigator is Lebesgue's verbal anomaly framing — agent-as-investigator rather than alert-as-row.
- **Filters:** Email digest is template-fixed; in-app date-range and metric pickers feed Business Report.
- **Data shown:** Revenue, first-time revenue, ad spend, COGS, profit, ROAS, pacing vs. plan, forecast deltas.
- **Interactions:** Read email; click to open Business Report; launch Revenue Drop Investigator agent; configure email frequency.
- **Why it works (from reviews/observations):** "Easy to set KPIs and watch over business reports including your marketing costs, shipping costs, revenue, forecast for sales" (Sasha Z., Capterra). "The metrics and pacing data delivered via email save time" (Marco P., Capterra). The email digest is unusually load-bearing for retention.
- **Source:** [lebesgue.md](../competitors/lebesgue.md), https://lebesgue.io/ai-agents

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Performance Trends Dashboard — anomaly detection embedded in the trend chart itself, plus a Daily Email Digest ("Daily performance round-up keeps you and your team in the loop").
- **Visualization:** No dedicated alerts inbox; anomalies are surfaced as **inline annotations on trend lines** (chart-embedded "early warnings"), plus a daily email digest.
- **Layout (prose):** Trend chart canvas with date-range and campaign filters; "early warnings when unexpected performance changes occur, such as drops in conversion rates or spikes in customer acquisition costs" appear as marked points on the trend lines. Daily email digest delivers performance round-up to inboxes.
- **Specific UI:** Anomalies are described in marketing copy as "early warnings" tied to the Performance Trends chart; no separate inbox surface exists. Owly AI ($199+/mo add-on) can answer anomaly questions on demand ("Where am I overspending on ads?") but is not the proactive surface.
- **Filters:** Date range, campaign, channel (chart-level filters; alerts inherit them).
- **Data shown:** Revenue, AOV, sessions, orders, contribution profit, ROAS, CAC — the anomalies decorate whichever metric is on the chart.
- **Interactions:** Hover anomaly point for tooltip; drill into the affected campaign; receive next-day email digest.
- **Why it works (from reviews/observations):** Limited reviews speak to the alert specifically; the broader product earns "Simple to use, seriously rich insights, all action-orientated" (Rapanui, Shopify App Store). Daily email digest is treated as table-stakes retention.
- **Source:** [conjura.md](../competitors/conjura.md), https://www.conjura.com/performance-trends-dashboard

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** No dedicated alerts inbox observed in public docs. Forthcoming "AI-Powered Conversational Analyst" with agents giving "tactical recommendations" is announced but unshipped. **Account Health** dashboard under Utility category covers integration/data-quality monitoring.
- **Visualization:** Prebuilt "Account Health" dashboard (Looker tiles); reverse-ETL Audiences ship segments to Klaviyo / Attentive / Meta nightly — adjacent to alerting but outbound.
- **Layout (prose):** Account Health is a templated dashboard within the Report Library; specific alert UI, severity, or rule config not surfaced in public docs (fully-templated Looker IA, gated behind login).
- **Specific UI:** UI details not available beyond "Account Health" template existing in Utility category. Daasity's strength is warehouse-native depth, not in-app alerting — alerts are presumably built by users via Looker scheduling features rather than a curated inbox.
- **Filters:** N/A (template).
- **Data shown:** Data-quality / integration-health metrics implied; specifics not enumerated publicly.
- **Interactions:** Standard Looker scheduling (email a tile on cadence / threshold) is implied.
- **Why it works (from reviews/observations):** "Lots of great integrations & dashboards" (tentree CA, Shopify App Store) — but no review specifically calls out alerts. The product is enterprise warehouse-first, not operator-alert-first.
- **Source:** [daasity.md](../competitors/daasity.md), https://help.daasity.com/core-concepts/dashboards

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Home dashboard "alerts strip" at top; deliverability hub (sender-reputation alerts on bounce / spam / unsubscribe spikes); per-profile churn-risk badges; Performance Highlights card auto-updated on the 10th of every month.
- **Visualization:** Strip-style alert banner at top of Home; traffic-light color tokens (**green = low, yellow = medium, red = high**) on per-customer churn risk; status pills (Live / Manual / Draft) on flows.
- **Layout (prose):** Top of Home: alerts strip + conversion-metric selector + 180-day period selector. Below: Business Performance Summary card with channel split. The deliverability hub renders sender-reputation by inbox provider as monitored time-series with implicit thresholds. Profile pages embed traffic-light churn-risk badges per customer. Performance Highlights card sits inside Overview Dashboard and Home, refreshing once a month.
- **Specific UI:** **Traffic-light color coding — green for low risk, yellow for medium, red for high** (verbatim per help.klaviyo.com churn docs). Status pills on flows. Peer-benchmark badges rated "Excellent / Fair / Poor" on Campaign Performance card (separate from RFM cohort labels). Email/SMS deliverability line charts of bounce/spam/unsubscribe rates.
- **Filters:** Date range, conversion metric, channel (Email / SMS / Mobile push) — applied globally across alert strip.
- **Data shown:** Open / click / conversion rates, deliverability score, bounce rate, spam rate, unsubscribe rate, peer percentile, churn-risk score per customer.
- **Interactions:** Click a metric in Performance Highlights to drill to its benchmark detail; hover churn badge for definition; add at-risk customer to a segment from inline action.
- **Why it works (from reviews/observations):** "I love being able to go in and see how each message performed with each RFM segment" (Christopher Peek, The Moret Group, klaviyo.com). The traffic-light churn coding is a small but instantly readable pattern. Reviewers also dislike that the broader reporting UI "buries things that should be front and center" (Darren Y., Capterra) — alerts strip is the antidote.
- **Source:** [klaviyo.md](../competitors/klaviyo.md), https://help.klaviyo.com/hc/en-us/articles/4708299478427

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Sidebar > Metric Alerts ("Smart Alerts"). Delivery channels: Slack, Gmail. Schedules surface delivers recurring snapshot reports through the same routes.
- **Visualization:** Per-metric alert configuration form (rule-based or anomaly-detection); Slack/email message render with a metric snapshot; in-app surface lists active alerts and rules.
- **Layout (prose):** Per-metric alert config UI; alerts fire on anomalies via 24/7 monitoring. Delivery is push-out (Slack / email), not in-app inbox-style. The Schedules feature is a sibling — recurring "send me this dashboard at 9am" snapshots rather than threshold-triggered alerts.
- **Specific UI:** "Setup is described as 'manual and AI-driven' — both rule-based and anomaly-detection alerts" (per Polar's own marketing). Marketing example: "a sudden surge in your sales" notification triggers the Slack message. No screenshots of the alerts surface itself in public docs.
- **Filters:** Per-alert metric, threshold type, scope, delivery channel, recipients.
- **Data shown:** Single metric snapshot per alert with delta vs. baseline; full dashboard snapshot for Schedules.
- **Interactions:** Configure alert per metric; route to Slack channel or Gmail address; receive push notification; click through to dashboard.
- **Why it works (from reviews/observations):** "The feature worked like a charm; it's almost like having another team member keeping an eye on things" (bloggle.app reviewer about Smart Alerts, 2024). Customer support is the most mentioned strength across reviews.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md), https://www.polaranalytics.com/business-intelligence

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Top-right **maintenance alerts icon** in nav (referenced in docs as "the maintenance alerts icon" beside the hamburger); Profit Benchmarks panel that gates content until Day 90 of pixel training.
- **Visualization:** Single icon-with-counter pattern in chrome (no dedicated inbox surface observed); Profit Benchmarks shows "real-time" performance vs. benchmark targets — implicit alerting via target-line breaches.
- **Layout (prose):** Maintenance alerts icon lives top-right next to the hamburger menu — operational/system alerts only ("data refresh succeeded / failed / delayed" implied). Profit Benchmarks renders target ROAS / MER / CAC as lines on charts; performance crossing those lines is the implicit alert mechanism. No dedicated marketer-facing anomaly inbox surfaced in public docs.
- **Specific UI:** The maintenance alerts icon is referenced only structurally ("hamburger icon on the top right of your Northbeam dashboard next to the maintenance alerts icon") — no description of click-through behavior, count badging, severity. Profit Benchmarks panel "becomes functional only after you have passed the 90-day learning period."
- **Filters:** N/A in public docs.
- **Data shown:** System/data-health for the maintenance icon; target ROAS / MER / CAC for Profit Benchmarks.
- **Interactions:** UI details not available — only structural reference.
- **Why it works (from reviews/observations):** Reviewers don't cite an alerts surface as a strength. Strength quotes ("Northbeam's data is by far the most accurate and consistent" — Victor M., Capterra) target attribution accuracy, not alerting. Onboarding pain dominates negative reviews.
- **Source:** [northbeam.md](../competitors/northbeam.md), https://docs.northbeam.io/docs/northbeam-30

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Marketing Dashboard — anomaly-detection alerts surface performance spikes/drops inline in the channel comparison table.
- **Visualization:** Inline anomaly indicators on a tabular channel × metric grid (table-embedded, not a separate inbox).
- **Layout (prose):** Channel rows (Facebook, Instagram, Google, TikTok, Snapchat, Pinterest, Microsoft) with reported revenue, spend, CPC, CAC, ROAS columns. Anomaly-detection alerts surface as inline highlights on the rows; pixel data is shown side-by-side with platform-reported numbers for comparison.
- **Specific UI:** "Anomaly-detection alerts surface performance spikes/drops" (per 1800DTC walkthrough). UI details beyond row-level highlight not available — only feature description seen on marketing page.
- **Filters:** Date range, attribution model toggle (first-click vs. last-click).
- **Data shown:** Reported revenue per channel, spend, CPC, CAC, ROAS, pixel-attributed revenue.
- **Interactions:** Filter and re-render; hover row for detail (assumed); attribution-model toggle.
- **Why it works (from reviews/observations):** UI specifics for the alert layer aren't cited verbatim in reviews. The broader product earns retention/LTV-focused praise.
- **Source:** [lifetimely.md](../competitors/lifetimely.md)

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Goal-tracking surface — drift triggers visible alert directly on monthly benchmarks / KPI tiles.
- **Visualization:** Traffic-light dots/cells (**green = on-pace, red = drifted**); binary, NOT three-state. Yellow/amber state explicitly absent in marketing copy.
- **Layout (prose):** Annual goal input → automatic month seeding; each KPI tile or monthly benchmark cell carries a green or red status dot indicating whether actuals are on-pace vs. the pro-rated target.
- **Specific UI:** "Traffic-light dots/cells (green = on-pace, red = drifted) attached to each monthly benchmark or KPI tile" — verbatim. Yellow/amber explicitly absent (the marketing copy specifies "green & red", binary).
- **Filters:** Date / period.
- **Data shown:** Revenue, contribution margin, channel-level ad spend — vs. monthly benchmark.
- **Interactions:** Drift triggers the visual alert directly; no separate inbox.
- **Why it works (from reviews/observations):** Treats alerting as "a status pixel attached to the metric" rather than a separate surface — pull rather than push. Limits operator triage cost but loses event history.
- **Source:** [storehero.md](../competitors/storehero.md)

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** Sidekick — natural-language interface that can build a Shopify Flow automation: "When inventory drops below 10 units, send a Slack alert and tag the product."
- **Visualization:** No alerts inbox in admin. Sidekick acts as an alert-builder — output is a Shopify Flow automation, not an in-app alert UI.
- **Layout (prose):** Sidekick is a chat surface; the user describes the alert in natural language; Sidekick assembles a Shopify Flow that actually executes the alert (Slack message, tag, email). Admin itself has no alerts inbox.
- **Specific UI:** Sidekick prompt example verbatim from Shopify's changelog: "When inventory drops below 10 units, send a Slack alert and tag the product." The product *builds* the alert via Flow rather than *being* the alert surface.
- **Filters:** N/A — alert config is per-Flow.
- **Data shown:** Whatever Flow surfaces in the destination (Slack message, email, tag).
- **Interactions:** Type rule in Sidekick → Flow is built → triggers fire on the rule.
- **Why it works (from reviews/observations):** Profile explicitly notes "**No native predictive / anomaly alerts.** Backward-looking only — no churn prediction, no automatic notification when conversion drops." Shopify pushes alerting to Flow; some Sidekick users report hallucinations (Dawsonx Feb 2026; Rahul-FoundGPT Mar 2026). The shop owner has to know to ask.
- **Source:** [shopify-native.md](../competitors/shopify-native.md)

### Atria ([profile](../competitors/atria.md))
- **Surface:** Radar — Raya's creative grading surface; not strictly an alerts inbox but functionally the same job for creatives.
- **Visualization:** Sortable creative table with **letter grades A–D** per axis (Hook, Retention, CTR, ROAS or Conversion depending on reviewer) + **triage badges (Winner / High Iteration Potential / Iteration Candidate)**. Two primary tabs: Winners and High Iteration Potential.
- **Layout (prose):** Top: Radar Settings — operator picks which key metric Raya grades on. Below: Winners / High Iteration Potential tabs. Main canvas: portfolio table of every creative with letter-grade columns and full metrics (ROAS, CTR, spend, AOV) at a glance. Each row has an "Iterate" CTA wired to a generation workflow that produces an improved variant.
- **Specific UI:** A–D letter grades per axis (third-party-attested; vendor docs don't enumerate the scale). Triage badges on each ad card. "Hover over column headers to understand grade rationale" (official help doc). Help doc prescribes the cadence: "Check Radar weekly. It's the fastest way to know what to scale, what to kill, and what to iterate on."
- **Filters:** Tab between Winners / High Iteration Potential; metric selector for primary grading axis.
- **Data shown:** Letter grade per axis, ROAS, CTR, spend, AOV, AI-identified target personas, prioritized improvement actions ("weak CTAs or unclear value propositions").
- **Interactions:** Click "Iterate" launches AI-generation workflow tuned to the flagged weakness; tab between triage states; click into ad for recommendation detail.
- **Why it works (from reviews/observations):** "The Radar feature and creative breakdowns make it easy to see why certain ads perform and what needs to be adjusted to improve other ones" — G2 reviewer. The grade-plus-action-button collapses analysis-to-action into one click.
- **Source:** [atria.md](../competitors/atria.md)

## Visualization patterns observed (cross-cut)

Synthesized from per-competitor sections — alerting is one of the most fragmented surfaces in the category:

- **Vertical alert-card list (inbox proper):** 1 competitor (Triple Whale Lighthouse / Moby Anomaly Detection Agent). Most "inbox-like" implementation. Mobile push parity.
- **Email/Slack push (no in-app inbox):** 4 competitors (Lebesgue daily/weekly digest, Conjura daily round-up, Polar Analytics Smart Alerts, Daasity inferred via Looker scheduling). Operators check their existing inbox/Slack rather than the analytics tool.
- **Inline annotation on chart/table (no separate surface):** 2 competitors (Conjura Performance Trends, Lifetimely Marketing Dashboard). Anomaly is a marker on the time-series, not a queue.
- **Status-pixel-on-tile (binary green/red):** 1 competitor (StoreHero goal-tracking dots). No alert history, no triage queue.
- **Traffic-light per-row (3-state):** 1 competitor (Klaviyo churn-risk badges, plus Excellent/Fair/Poor benchmark badges).
- **Letter-grade + triage badge (rubric inbox):** 1 competitor (Atria Radar). A–D grade plus Winner/High-Iteration/Iteration-Candidate badge; not anomaly-driven but functionally an alert queue for creative.
- **Maintenance icon in nav chrome (system alerts only):** 1 competitor (Northbeam) — operational health, not marketing anomalies.
- **Natural-language alert builder (no inbox):** 1 competitor (Shopify Sidekick → Flow). Alert lives wherever Flow routes it (Slack, email).

Recurring conventions:
- **Color use:** Red for negative is universal. Positive deltas split — green is dominant (StoreHero, Klaviyo) but Lebesgue uses **blue for positive** (unusual; possibly colorblind-aware). Yellow/amber is the contested third state (Klaviyo uses it for medium churn-risk; StoreHero explicitly skips it).
- **Severity granularity:** Three-state (critical / warning / info) is rare in public docs. Most surfaces are binary (anomaly / no-anomaly) or magnitude-only (raw delta visible, severity left to operator).
- **Routing:** Slack + email are the two universal destinations. Mobile push is a Triple Whale moat.
- **Acknowledge / snooze / resolve state:** Almost never documented publicly. Inferred from Triple Whale's "Acknowledge alert" interaction; absent everywhere else.
- **Detection method:** Threshold-based dominates ("when revenue < X" / "when ROAS < Y"). ML/anomaly-detection is marketed by Triple Whale (Moby Anomaly Detection Agent), Polar (Smart Alerts "manual and AI-driven"), Conjura ("early warnings"), Lifetimely ("anomaly-detection alerts"), but specific algorithm/lookback details are not published anywhere.
- **Naming:** Vendors reach for security/sentinel metaphors — Triple Whale "Lighthouse" (deprecated 2026), Lebesgue "Guardian / Sentinel / Sentry" (older agent set), "Revenue Drop Investigator" (newer).

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: "Another team member keeping an eye on things"**
- "The feature worked like a charm; it's almost like having another team member keeping an eye on things." — bloggle.app reviewer about Polar Analytics Smart Alerts, 2024 (from polar-analytics.md)
- "Easy to set KPIs and watch over business reports including your marketing costs, shipping costs, revenue, forecast for sales." — Sasha Z., Founder (Retail), Capterra, September 30, 2025 (from lebesgue.md)
- "The metrics and pacing data delivered via email save time." — Marco P., Owner (Online Media), Capterra, January 6, 2025 (from lebesgue.md)

**Theme: Mobile push as the "addictive" alert surface**
- "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design." — paraphrased consensus across multiple 2026 reviews (workflowautomation.net, headwestguide.com) (from triple-whale.md)
- "Real-time data, clean dashboards, mobile app, and automation consistently save operators 4–8 hours per week." — AI Systems Commerce, 2026 review (from triple-whale.md)

**Theme: Grade-plus-action-button collapses analysis-to-action**
- "The Radar feature and creative breakdowns make it easy to see why certain ads perform and what needs to be adjusted to improve other ones." — G2 reviewer about Atria Radar (search excerpt, 2026) (from atria.md)
- "These have saved me so much time with ideation and strategy so that I can focus on ad creation." — G2 reviewer praising Inspo + AI Recommendations + Radar + Clone Ads (from atria.md)

**Theme: Embedded per-customer / per-segment alerts beat generic dashboards**
- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group, quoted on Klaviyo.com features page (from klaviyo.md)
- "The platform's ability to handle intricate segmentation and real-time triggers has allowed me to deliver measurable growth." — Marc G., Capterra, March 2026 (from klaviyo.md)

## What users hate about this feature

**Theme: AI-generated insights described as shallow**
- "Insights" being "simply noting that CAC increased and conversion rate dropped off" — Capterra synthesis, Lebesgue (paraphrased reviewer-summary; surfaces complaint that alerts state the obvious without recommending the fix) (from lebesgue.md)
- "Some operators report attribution numbers that feel closer to platform self-reporting than fully independent models." — AI Systems Commerce, 2026 review, Triple Whale (related — operators question whether the alert's underlying number is trustworthy) (from triple-whale.md)
- "Occasional bugs or over-optimistic recommendations still appear in 2025–2026 operator feedback." — AI Systems Commerce, 2026 review, Triple Whale (from triple-whale.md)

**Theme: Buried / hard-to-find alert surfaces**
- "Reporting is clunky and the UI buries things that should be front and center." — Darren Y., Capterra, April 2026, Klaviyo (from klaviyo.md)
- "Modifying reports or navigating menus is a cluster." — BioPower Pet, Shopify App Store, April 2, 2026, Triple Whale (from triple-whale.md)

**Theme: Bugs / crashing in the alert surface**
- "Building with the AI tool Moby is very buggy and crashes more than half the time." — Trustpilot reviewer, Triple Whale (from triple-whale.md)
- "The app is okay, but it's full of bugs and the UI is terrible." — BioPower Pet, Shopify App Store, April 2, 2026, Triple Whale (from triple-whale.md)

**Theme: No native alerts at all (Shopify gap)**
- "**No native predictive / anomaly alerts.** Backward-looking only — no churn prediction, no automatic notification when conversion drops." — shopify-native.md profile observation (from shopify-native.md)
- Sidekick hallucinations: "fabricating SEO/technical data, ignoring negative constraints, and requiring 80+ product audits to clean up the damage" — Dawsonx Feb 2026; Rahul-FoundGPT Mar 2026; Maximus3 Feb 2026 (relevant if alerts are generated by AI) (from shopify-native.md)

**Theme: Paywalled behind premium tier**
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, December 2025, Klaviyo (from klaviyo.md)
- Owly AI rate-limited to "approximately 250 quick questions or 50 comprehensive reports" before upgrade, Conjura (from conjura.md)

## Anti-patterns observed

Concrete examples of bad implementations and why they failed. Cited per competitor:

- **Alerts that just narrate the metric without recommending action.** Lebesgue's Capterra synthesis flags "insights" as shallow — "simply noting that CAC increased and conversion rate dropped off." When the alert is descriptive (something is up) without prescriptive (here is the next step), operators stop reading. (lebesgue.md)
- **Alert surface buried in the IA.** Klaviyo Home has an alerts strip but the broader reporting "buries things that should be front and center" (Darren Y., Capterra). When the alerts surface is two clicks deep, the merchant doesn't open it. (klaviyo.md)
- **Volatile naming / re-org of the alert surface.** Triple Whale's Lighthouse brand has been "quietly absorbed into Moby Agents as Anomaly Detection Agent + Order/Revenue Pacing Agent + Revenue Anomaly Agent" by 2026; Lebesgue's older Guardian/Sentinel/Sentry/Auditor agent set was renamed wholesale to Henri / Revenue Drop Investigator / etc. UI volatility ("UI changes frequently and documentation sometimes lags behind" — Triple Whale 2025–2026 reviewer) trains operators to distrust the surface. (triple-whale.md, lebesgue.md)
- **Binary on-pace/off-pace with no third state.** StoreHero's green-or-red dot omits yellow/amber. Real-world drift is rarely binary; operators lose the "watch this — not yet a fire" middle state. (storehero.md)
- **AI-built alerts that hallucinate.** Shopify Sidekick can build Flow-based alerts via natural language — but Sidekick has multiple documented hallucination incidents in early 2026. When the AI fabricates the rule, the merchant gets noisy or wrong alerts and trust collapses. (shopify-native.md)
- **Alerts without acknowledge / resolve state.** No competitor publicly documents a full triage state machine (read / unread / acknowledged / snoozed / resolved). Most implementations are firehose-style (push the message, hope the user acts). Triple Whale's "Acknowledge alert" is the closest to inbox triage but specifics aren't published. (triple-whale.md)
- **Maintenance/system alerts mixed with marketing anomalies.** Northbeam's nav has a "maintenance alerts icon" but no marketing-anomaly inbox surfaced publicly — operational health and KPI anomalies should not share a queue, but at least one product appears to conflate them by absence of separation. (northbeam.md)
- **Mobile parity gap.** Polar Analytics, Northbeam, Conjura, Daasity, Lebesgue all lack a native mobile app; alerts are email/Slack only. Triple Whale's mobile push is the only first-class mobile alert experience in the category, and reviewers consistently call it out. The absence is a structural anti-pattern. (polar-analytics.md, northbeam.md, conjura.md, daasity.md, lebesgue.md, triple-whale.md)

## Open questions / data gaps

- **Detection algorithms aren't published.** Every competitor that markets "anomaly detection" (Triple Whale, Polar, Conjura, Lifetimely, Lebesgue, Klaviyo, Daasity-future) declines to specify lookback window, baseline-calculation method (rolling mean / Prophet / exponential smoothing), z-score threshold, or seasonality handling. Public sources call it "AI-driven" or "smart" without enumeration.
- **Severity taxonomies aren't enumerated.** Triple Whale uses "alert cards with severity / metric / suggested action" but specific labels (critical / warning / info? red / yellow? P0/P1/P2?) aren't visible publicly. Klaviyo uses three-state traffic-light for churn risk but doesn't generalize this to all alerts.
- **Triage state machine is undocumented.** Acknowledge / snooze / resolve / re-open / assign-to-teammate flows aren't shown publicly for any competitor. Likely behind login / paywall.
- **Rate-limit / digest behavior is opaque.** When does an inbox get flooded? Do anomalies on highly-correlated metrics get bundled? Polar markets "24/7 monitoring" but doesn't say whether 100 alerts/day get squelched into a digest.
- **Lebesgue Guardian's actual behavior** is undocumented — "Guardian" appears only as a name in older marketing alongside Sentinel/Sentry/Auditor; the current public agent list renamed it to Revenue Drop Investigator. UI specifics for either name not surfaced publicly. The name set strongly implies an alerts surface but the product surface itself is hidden.
- **Triple Whale's mobile push payload structure** (alert categories, deep-link routing, severity levels) couldn't be observed from public marketing — would require a paid trial install.
- **Daasity's forthcoming AI-Powered Conversational Analyst** is announced, not shipped — whether it includes a proactive alerts surface is undeclared.
- **Northbeam's "maintenance alerts icon" behavior** is a structural reference only; whether it counts marketing anomalies, system events, or both is undocumented.
- **No competitor publishes alert false-positive rates or precision/recall** for their anomaly detection. The trust calibration story is missing across the category.

## Notes for Nexstage (observations only — NOT recommendations)

- **The category is fragmented on whether alerts are an in-app surface, an email digest, an inline chart annotation, or a status pixel on every tile.** No clear winner. Triple Whale's Lighthouse → Moby Anomaly Detection Agent is the most inbox-like, but it's been renamed twice in 18 months; Polar pushes to Slack/Gmail and skips an in-app inbox; StoreHero embeds the alert into the tile itself. There is no "table-stakes" alerts UX merchants expect — the design space is genuinely open.
- **Mobile push is Triple Whale's clearest moat in this surface.** Multiple 2026 reviewer quotes name it as the addictive part. Polar, Northbeam, Conjura, Daasity, Lebesgue all lack a native mobile app. If Nexstage wants alert-driven retention, mobile push may be the highest-ROI alert delivery channel and the largest SMB-tier whitespace.
- **Three competitors (Triple Whale, Lebesgue) had named alert agents (Lighthouse / Guardian / Sentinel / Sentry / Auditor) that were renamed.** Vendors reach for security metaphors and then iterate them. If Nexstage names an alerts surface, accept that the brand will likely shift; the underlying contract (anomaly → routed message → triage state) is what persists.
- **Color tokens divide.** Lebesgue uses **blue for positive deltas** (not green), Klaviyo uses red/yellow/green for churn risk, StoreHero uses binary green/red. Nexstage's six source-color tokens (`--color-source-{real,store,facebook,google,gsc,ga4}`) include source-blue and source-google-blue — collision risk if alerts adopt blue as a state color. Worth deciding alert state colors against existing source palette early.
- **Severity is rarely three-state in public competitor docs.** Most are binary (anomaly / no-anomaly) or magnitude-only. If Nexstage ships severity, it would be more granular than the typical competitor's surface — easy differentiation lane, but only if rules / thresholds are operator-comprehensible.
- **No competitor publicly documents a triage state machine** (acknowledged / snoozed / resolved / assigned). This is structurally where SaaS B2B inbox UX lives (Linear, Front, Intercom) but ecommerce-analytics tools haven't borrowed it yet. Open lane.
- **Suggested-action one-liners are the bar for "useful alert."** Lebesgue's reviewer-cited weakness ("simply noting that CAC increased") shows what happens when the alert is descriptive only. Atria's "Iterate" CTA shows the opposite — alert wired to a button that does the next thing.
- **Anomaly detection is uniformly oblique about its math.** Every competitor calls it "AI-driven" or "smart" without specifying algorithm, lookback, or false-positive rate. Transparency about *how* the alert was decided (e.g., "z-score > 2.5 over 28d rolling mean") would be unique in the category and aligned with Nexstage's existing source-disagreement-transparency posture.
- **Five of nine implementations route alerts to Slack and/or email.** In-app inbox is rare and usually accompanies (not replaces) Slack/email push. Operators don't open the analytics tool to check alerts; the alerts come to where they already work.
- **Klaviyo's RFM-segment migration counts (Champions → At Risk over a date range) are surfaced as a Sankey** rather than as alerts — but the data is alert-shaped. Migration-driven alerts ("12 customers moved Champions → At Risk this week") are a category gap.
- **Integration-health alerts (token expired, OAuth disconnected, pixel events dropped) are typically only surfaced on the integrations page, not in a unified inbox.** Northbeam's "maintenance alerts icon" is the closest to a system-alerts surface, but it doesn't merge with marketing anomalies. Two queues vs. one queue is an open design decision.
- **Cost/attribution-recompute notifications** (the `RecomputeAttributionJob` "Recomputing…" banner Nexstage already shows) are conceptually adjacent to alerts. No competitor surface treats config-change-driven recalc as an inbox event publicly.
- **The "naming convention drift" risk is real.** Triple Whale's Lighthouse → Moby Anomaly Detection Agent rename + Lebesgue's Guardian → Revenue Drop Investigator rename happened in the same 12-month window. Names that sound clever on a marketing page (Sentry, Watchman, Pulse) age poorly compared to functional names (Alerts, Inbox, Anomalies).
