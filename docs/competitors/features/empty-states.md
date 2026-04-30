---
name: Empty / loading states
slug: empty-states
purpose: Answer "what does the product feel like before data is rich?" — how merchants experience the app between install and the first useful number.
nexstage_pages: onboarding, dashboard, performance, profit, customers, products, seo, integrations
researched_on: 2026-04-28
competitors_covered: polar-analytics, storehero, triple-whale, lifetimely, northbeam, conjura, bloom-analytics
sources:
  - ../competitors/polar-analytics.md
  - ../competitors/storehero.md
  - ../competitors/triple-whale.md
  - ../competitors/lifetimely.md
  - ../competitors/northbeam.md
  - ../competitors/conjura.md
  - ../competitors/bloom-analytics.md
---

## What is this feature

"Empty states" in an ecommerce analytics product are not a single screen — they are the entire UX of the gap between install and trustworthy data. Three things must happen before a dashboard is useful: (1) historical data has to be back-filled (Shopify orders, Meta/Google ad spend, Klaviyo events, GA4 sessions); (2) a first-party pixel, if one exists, has to capture enough sessions to model attribution; and (3) the merchant has to enter store-specific config (COGS, shipping rules, channel mappings) that the platforms don't supply. Until all three are done, every KPI tile, cohort heatmap, and attribution chart is structurally wrong or empty.

For SMB Shopify/Woo owners this matters disproportionately. Unlike Northbeam's enterprise customer who tolerates a 30-day "calibration period," an SMB founder evaluates the tool on day 1 inside a 14-day trial. The competitors that win this segment treat the empty state as a product surface in its own right — onboarding checklists, sample-data demo modes, day-N feature gating, and skeleton loaders that explicitly explain *why* a number isn't there yet. The competitors that lose treat it as an afterthought ("data still syncing…"), and review pools fill with "couldn't get it working" complaints. The difference between "having data" (orders exist in Shopify) and "having this feature" (a calibrated, configured, attributed dashboard) is roughly 7–90 days of elapsed time depending on the tool, and the empty-state UX is what carries the merchant across that gap without churn.

## Data inputs (what's required to compute or display)

- **Source: Shopify** — `orders.created_at` (oldest = earliest backfill cutoff), `orders.line_items` (per-SKU), `products.variants.cost` (Shopify "Cost per item" field, used as default COGS), `customers` (cohort start date)
- **Source: Meta / Google / TikTok / etc. Ads APIs** — `account.id`, `account.date_first_active`, `campaigns.spend` per day back to chosen lookback
- **Source: First-party pixel** (Triple Pixel / Polar Pixel / Bloom Pixel / Lifetimely Pixel / Northbeam pixel) — `events.purchase`, `events.session`, `device_graph.identity` over a calibration window of 5–30 days
- **Source: Klaviyo / Recharge / Amazon** — connection state (boolean), oldest event timestamp
- **Source: User-input** — `cogs_per_product` (when missing), `shipping_rule_set`, `channel_mapping_overrides`, `attribution_window_default`, annual goal target
- **Source: Computed** — `data_readiness_score` (function of days-since-pixel-install + COGS-coverage % + ad-account-connections), `model_calibration_state` (Day 0 / 30 / 60 / 90 for Northbeam-style ML attribution)
- **Source: System** — `sync_progress` (rows ingested vs estimated total), `last_refresh_timestamp` per integration, `webhook_health` per source

## Data outputs (what's typically displayed)

- **Status: Onboarding step state** — boolean per step (Connect Shopify ✓, Connect Meta ✓, Configure COGS ◦, Install Pixel …), often surfaced as a checklist with progress bar
- **KPI: Days since pixel install** — integer; gates feature unlock at Day 7 / 30 / 60 / 90
- **KPI: COGS coverage** — `% of SKUs with non-null cost`, used as a "data quality" badge
- **Status: Sync progress** — `rows ingested / estimated total`, often as a progress bar with ETA
- **Status: Per-integration health** — green/red dot or "Connected / Reconnect / Not connected" pill per source
- **Empty placeholder: Skeleton tile** — KPI tile with shimmer/grey block where the value would sit + microcopy ("Your data will appear here")
- **Sample data overlay** — entire dashboard rendered with synthetic numbers + a banner ("Sample data — connect your store to see real numbers")
- **Day-N gated panel** — entire feature visibly present in the IA but disabled, with a "Unlocks at Day 90" label
- **Recompute banner** — full-width strip on every page when cost-config or attribution-default changes trigger a backfill ("Recomputing… new numbers in ~10 min")
- **Tutorial overlay** — modal or coachmark sequence pointing at first KPI tile, attribution model picker, etc., on first login

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Founders Dash (free tier landing) + Summary Dashboard on first login; Pixel calibration period gates Attribution.
- **Visualization:** KPI-tile grid rendered with platform-reported numbers from Day 0 (Shopify + ad APIs ingest immediately); attribution columns gated by pixel calibration period of 5–7 days (2–3 days for $4M+ stores).
- **Layout (prose):** Top: date-range + store-switcher + on-demand refresh button (added April 2026, with a "Refreshing Meta…" status cycler). Body: collapsible sections grouped by integration (Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics, Custom Expenses) — sections are visible-but-empty until the corresponding integration is connected. Right rail: persistent Moby Chat for natural-language hand-holding while empty.
- **Specific UI:** New (April 2026) on-demand refresh button with a real-time status cycler ("Refreshing Meta…" / "Refreshing Google…") rather than an opaque spinner. KPI tiles render the headline number plus period-vs-period delta from Day 0 once Shopify is connected; no skeleton shimmer observed in public screenshots — empty sections appear collapsed rather than greyed-out.
- **Filters:** Date range, store, platform sections (collapse/expand). No "show only sections with data" filter observed.
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, AOV, Sessions; sub-tiles per platform appear as the integration connects.
- **Interactions:** Drag-and-drop tile reorder once data loads; pin tile to "Pinned" section. Mobile push notification fires on the first revenue milestone "within minutes" — used as a hook to bring users back into the empty/early state.
- **Why it works (from reviews/observations):** "Its Founders dash is something that all Shopify brands should be using. It's free and puts all the metrics you need to know about your high level business performance in a single place." — Head West Guide review, 2026. The free-forever tier is itself an empty-state strategy: get the merchant past the "is this even worth setting up?" gate.
- **Source:** [triple-whale.md](../competitors/triple-whale.md) — Summary Dashboard, Founders Dash, Pixel calibration period sections.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Custom Dashboards canvas + per-customer Snowflake provisioning during onboarding; Ask Polar AI as an empty-state hand-holder.
- **Visualization:** Block grid of empty Key Indicator Sections, Sparkline Cards, and Tables/Charts that the user composes; comparison arrows ("improvement / decline") render automatically off the dashboard date range only when prior-period data exists.
- **Layout (prose):** Top right: date-range selector. Left rail: folder tree of dashboards. Main canvas: a vertical stack of user-added blocks. Recommended pattern from docs is a row of Metric Cards across the top with charts and tables below.
- **Specific UI:** Comparison indicators (up/down arrow + delta) auto-render off the dashboard date range — until enough history exists, the indicators are absent rather than showing "0%". Help docs note "Installation took just minutes, and we began seeing data flowing in within a few hours" (Dan John, May 2025) — Polar leans on dedicated success manager + Slack channel + 1:1 onboarding included on every plan to cover the empty state, rather than in-product guidance.
- **Filters:** Views (saved filter bundles) — empty until configured. The OR-vs-AND combination gotcha is documented as a UX trap that surfaces when users have multiple Views active.
- **Data shown:** All dashboard blocks empty until Snowflake provisions and the semantic layer ingests; "hundreds of pre-built metrics" become available progressively as connectors complete.
- **Interactions:** Ask Polar (NL chat) generates a fully editable Custom Report instead of a frozen answer — works as an "I don't know what to look at first" entry point during the empty phase.
- **Why it works (from reviews/observations):** "The level of support you get from the polar team is outstanding, really willing to help." — Gardenesque, June 2024. Polar's empty-state strategy is *people*, not UI — concierge support is sold as the product.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md) — Custom Dashboard, Views, Ask Polar sections.

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Onboarding wizard → Cost Settings configuration screen → Unified Dashboard.
- **Visualization:** Sequential cost-config form (5 cost buckets — product, shipping, fulfillment/packaging, transaction fees, marketing) before contribution-margin metrics can render; Goals & Forecasting traffic-light cells (green/red) that stay un-coloured until the annual goal is entered.
- **Layout (prose):** Onboarding lands the user on Cost Settings, not the dashboard — they are explicitly blocked from contribution margin until the 5 cost buckets are filled. Once configured, dashboard renders; goals module prompts annual goal entry, then auto-generates seasonally-adjusted month-by-month benchmarks.
- **Specific UI:** Two-state traffic-light system on Goals (green = on-pace, red = drifted) — explicitly described in StoreHero copy as "green & red," no amber/yellow middle. Until the annual goal is entered, the entire benchmark grid is empty placeholder cells. Cost Settings has a configuration form per cost category — bulk import via CSV is implied but UI specifics not public.
- **Filters:** None on the empty state itself; date-range filter on dashboard once data flows.
- **Data shown:** Until cost buckets are filled, contribution margin = $0 across every screen. Spend Advisor's "next $100 → profit" simulator is structurally unusable until COGS exists.
- **Interactions:** 1:1 onboarding call included on every paid plan (bundled into base pricing, not Elite Support add-on); Academy with platform-deep-dive courses available pre-onboarding. iOS app delivers daily/weekly/monthly summaries via push once data is flowing.
- **Why it works (from reviews/observations):** "the platform is excellent and it really gives the agency and business owner a clear snapshot of the store's financial health" — Dylan Rogers, Madcraft Agency. The forced-config gate frames the empty state as "we can't lie to you yet, finish setup."
- **Source:** [storehero.md](../competitors/storehero.md) — Cost Settings, Goals & Forecasting, Spend Advisor sections.

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Profit Dashboard (income-statement layout) + Costs/Settings tab + Cohort heatmap.
- **Visualization:** Income-statement-style stacked vertical layout (revenue → COGS → marketing → contribution → net) — empty rows are visible-but-zero rather than hidden, so the structure is legible even before data lands. Cohort heatmap cells render as grey/light gradient until cohorts mature.
- **Layout (prose):** Top: date-range picker. Body: line-by-line income-statement table that descends from revenue to net profit. Cost rows show $0 (or warn) until each cost category is configured.
- **Specific UI:** Per-product cost editor with **pencil icon** per row + CSV bulk-import widget; explicit fallback hierarchy documented as "Lifetimely manual cost > Shopify cost-per-item > default COGS margin." This means even before the user does anything, the income statement uses Shopify's `cost_per_item` if present, then a default margin %, so the empty state is never literally blank — it's "directionally informed." Cohort heatmap uses 13+ selectable metrics; until cohort age reaches the metric's window (e.g., 90d / 180d repurchase), cells stay light.
- **Filters:** Time-period toggle (daily/weekly/monthly), date range. Benchmarks tab requires opt-in to anonymized data sharing before showing peer percentiles — that opt-in itself is a deliberate gate.
- **Data shown:** Income-statement skeleton always visible; cohort metrics fill in as time passes; LTV at 3/6/9/12-month horizons unavailable until cohorts age that long. Predictive LTV (AI) fills in earlier as a forward projection.
- **Interactions:** Daily P&L email at 7am + Slack delivery Monday at 8am — re-engages the merchant during the empty phase. 14-day trial across all paid tiers; free tier (50 orders/mo) lets users explore the empty product without payment.
- **Why it works (from reviews/observations):** "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, Jan 2026. The income-statement framing reads as "this is just how a P&L looks, the numbers will fill in" — accountant-friendly empty state.
- **Source:** [lifetimely.md](../competitors/lifetimely.md) — Profit Dashboard, Cost & Expenses tab, Cohort heatmap sections.

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Attribution Home Page (right-rail Profitability panel) + Apex configuration + Day 30/60/90 progressive feature unlock.
- **Visualization:** Visibly locked panels that stay structurally present in the IA but display as empty/disabled until the model calibrates. Profit Benchmarks unlocks at Day 90 with target ROAS / MER / CAC against contribution margins.
- **Layout (prose):** Attribution Home: vertical sectioning (Sales → New Customers → Returning Customers → Top of Funnel → Bottom of Funnel → Organic and Owned Media). Right side rail: a Profitability panel that **becomes functional only after the 90-day learning period** — the panel is visible all along but doesn't compute until Day 90.
- **Specific UI:** Day-N gating is the dominant empty-state pattern. Apex configuration screen shows a "✅ green check" status indicator only on successful Meta connection — a binary verified/unverified state in place of a progress bar. Northbeam 3.0 added inline tooltips on every table header (Touchpoints, Revenue, ROAS, CAC, Visitors, Customers) — these double as definition aids during the empty phase when users don't yet have intuition for the columns. Clicks + Modeled Views model is documented to "take 25-30 days to learn from historical data" — the empty-state copy is honest about the wait.
- **Filters:** Global filter strip at top (Attribution Model / Window / Accounting Mode / Granularity / Time Period / Comparison) applies across all dashboards — even when half are still calibrating.
- **Data shown:** Day 0: simple last-click + first-click models. Day 7: Clicks-Only MTA. Day 25–30: Clicks + Modeled Views. Day 90: Clicks + Deterministic Views + Profit Benchmarks + right-rail Profitability panel.
- **Interactions:** No mobile app. Onboarding is gated by 3-months-upfront Starter billing + sales call — the empty-state experience is concierge-led but reviewed as painful ("29 days back and forth," "extremely hard despite paying for a 3-month package"). The progressive unlock is sold as "honest about ML calibration" but reads to SMB users as "I paid $1,500 and I can't see the numbers yet."
- **Why it works (from reviews/observations):** "Northbeam's data is by far the most accurate and consistent." — Victor M., Capterra, Feb 2023. Day-90 unlock as honest signal works for enterprise; for SMB it's a churn risk.
- **Source:** [northbeam.md](../competitors/northbeam.md) — Attribution Home Page, Day 30/60/90 progressive unlock, Apex sections.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Performance Overview (post-onboarding default) + Owly AI as conversational entry point.
- **Visualization:** Daily-refreshed KPI cards with product imagery integrated alongside metric numbers (a deliberate "show photos so the empty space doesn't feel empty" choice). LTV Heatmap uses color-intensity cells that fade from light → dark as cohort data accumulates.
- **Layout (prose):** Performance Overview is described in marketing as "the beating heart" — a daily snapshot. Top filters (store, channel, territory) work even before all integrations connect. Below: KPI cards intermixed with product imagery.
- **Specific UI:** Help docs explicitly teach merchants to read three pattern types in the LTV heatmap (horizontal / vertical / diagonal patterns) — a pedagogical empty-state companion that compensates for unfamiliarity. Daily email digest ("Daily performance round-up") arrives even when only a handful of orders have processed. Customer Table has pre-built saved views ("loyal customers, new shoppers, high spenders, close to churn") that surface immediately, so the table never appears blank — the segments are pre-defined even before customer data is rich.
- **Filters:** Store / channel / territory; pre-built saved views per dashboard (Product Table: "unprofitable products, slow movers, items selling out"; Order Table: "new customer orders, unprofitable orders and most profitable").
- **Data shown:** Six primary KPIs — Contribution Profit, ROAS, CAC, Order Volume, Revenue, Channel Performance. Daily refresh cadence (nightly batch) means the empty state lasts at least overnight after any new connection.
- **Interactions:** Owly AI ($199+/mo add-on) accepts plain-English questions like "Which products are driving traffic but not sales?" — works as guidance during early/empty period. Initial historical pull behaviour not publicly documented; reviewers call setup "initially overwhelming" due to data complexity.
- **Why it works (from reviews/observations):** "Excellent customer support, especially during setup. Jim was very helpful with creating the reports we need." — Relish, Aug 2025. Pre-built saved views make even an empty Customer Table look opinionated rather than blank.
- **Source:** [conjura.md](../competitors/conjura.md) — Performance Overview, LTV Analysis, Customer Table, Owly AI sections.

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Onboarding flow with progress bar → Overview Dashboard → Product Costs / Shipping Costs settings.
- **Visualization:** Progress-bar onboarding sequence; once landed, KPI cards each show "$value, %change From Last Month" — period-comparison delta is part of the tile primitive even before history exists, so the tile structure renders from Day 1 with $0/0% placeholders.
- **Layout (prose):** Onboarding step has explicit progress indicator. Overview lands with four blocks: Revenue-to-Profit summary cards, Margin Overview cards, Marketing Performance cards, Customer & Revenue cards. Below: 4 trend charts (Revenue-to-Profit %, Profit Margins Trend, Marketing Performance Trend, Customer Type Trend) that appear empty (no series) until 2+ days of data exist for the comparison.
- **Specific UI:** Initial historical pull = "last three months to start off the reporting. The historical data is then collected in monthly batches." Documented caveat: "syncing can take between a few minutes to several hours." Bloom Pixel install is a 3-step embed flow with explicit "if you have existing UTM parameters" vs "no UTM parameters" branching + a verification step — empty-state behaviour during pixel-not-yet-firing is explicit. Shipping costs use a 4-layer fallback (rules → 3PL integration → Shopify auto-sync → manual edit) so even a brand-new install has shipping cost coverage from Shopify auto-sync; the empty state is never truly empty.
- **Filters:** Date range with daily/weekly/monthly/yearly + comparison toggle (vs prior year or same historical period).
- **Data shown:** From Day 0 (after Shopify connect): GMV, Net Revenue, COGS (if `cost_per_item` populated in Shopify), CM1/CM2/CM3, MER. Marketing metrics gated by ad-account connection. Klaviyo email-campaign profit gated by Grow tier ($40/mo).
- **Interactions:** Free tier on Shopify App Store reduces commitment friction; "no penalties for exceeding [order] limits" messaging removes one source of empty-state anxiety. Slack updates available at Grow tier.
- **Why it works (from reviews/observations):** "No more digging through spreadsheets — just instant, actionable data." — Baron Barclay, Mar 2025. The 4-layer cost fallback means most merchants see a directionally-correct contribution margin within hours.
- **Source:** [bloom-analytics.md](../competitors/bloom-analytics.md) — Onboarding, Overview Dashboard, Product Costs, Shipping Costs sections.

## Visualization patterns observed (cross-cut)

- **Day-N progressive feature unlock:** 1 competitor (Northbeam) — explicit Day 30 / 60 / 90 panels that are visible-but-empty in the IA until the model trains. Reviewed as "honest about ML calibration time" (analyst) but "I paid $1,500 and I can't see numbers" (SMB customer).
- **Onboarding checklist with progress bar:** 2 competitors (Bloom Analytics, StoreHero) — explicit step-state UI before the dashboard unlocks; StoreHero forces cost-config completion before contribution margin renders.
- **Skeleton/placeholder KPI tile:** Implied in 5/7 competitors (Triple Whale, Polar, Lifetimely, Conjura, Bloom) — KPI tiles render structurally with `$0` or null + delta % placeholder, then fill in. No public screenshots showed shimmer/grey-block treatment specifically; empty rendering tends to be "0 + grey delta" rather than animated shimmer.
- **Sample-data demo mode:** **0 competitors observed in this batch.** None of the seven publish a "tour the dashboard with synthetic data first" mode. Triple Whale's Founders Dash (free forever) is the closest analog — get to real data fast rather than fake data.
- **Tutorial overlay / coachmark:** Not directly observed in public screenshots for any of the seven. Polar relies on dedicated CSM, StoreHero on Academy + 1:1 onboarding, Northbeam on sales-led setup, Lifetimely on docs + chat support — all *people-and-docs*, not *in-product overlay*.
- **Concierge / human-led empty-state:** 4 competitors (Polar, StoreHero, Northbeam, Lifetimely) bundle 1:1 onboarding or dedicated success manager into base or low-tier pricing. Reviewed as the #1 strength for Polar and Lifetimely; reviewed as the #1 weakness for Northbeam (paid concierge that under-delivers).
- **Visible-but-locked panel:** 1 competitor (Northbeam) — the right-rail Profitability widget that "becomes functional only after you have passed the 90-day learning period" is the cleanest example of "show users where the feature lives even before it works."
- **Recompute / refresh banner with status cycler:** 1 competitor (Triple Whale, April 2026) — on-demand refresh button cycles "Refreshing Meta…" / "Refreshing Google…" rather than an opaque spinner.
- **Pre-built saved views as empty-state filler:** 1 competitor (Conjura) — Customer Table / Product Table / Order Table all ship with pre-defined segments ("loyal customers," "unprofitable products," "high-shipping-cost orders") so empty tables look pre-configured.
- **Cost-fallback cascade:** 2 competitors (Lifetimely 3-layer, Bloom 4-layer) — instead of "configure COGS or see nothing," they cascade through Shopify `cost_per_item` → default margin %, so the empty state is never literally blank.
- **Daily / weekly digest emails during empty phase:** 4 competitors (Lifetimely 7am daily, Bloom Slack, StoreHero email/Slack, Conjura daily round-up) — re-engagement during the multi-day empty/early period.

Color conventions: where green/red appear in empty-state UI, they are reserved for status (green = connected/on-pace, red = disconnected/drifted). StoreHero explicitly uses two-state (no amber). Lifetimely's CAC payback green-bar overlay is the only observed "green = positive annotation, not status" treatment.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Concierge / human-led setup carries the empty state**
- "The level of support you get from the polar team is outstanding, really willing to help." — Gardenesque, [polar-analytics.md](../competitors/polar-analytics.md)
- "Excellent customer support, especially during setup. Jim was very helpful with creating the reports we need." — Relish (UK), [conjura.md](../competitors/conjura.md)
- "Best analytics tool I've ever used. The onboarding calls have greatly helped" — anonymous US reviewer, [polar-analytics.md](../competitors/polar-analytics.md)
- "I love this app and the support… the support team is very efficient when it comes to find a solution" — ALLENDE, [lifetimely.md](../competitors/lifetimely.md)

**Theme: Get to a real number fast**
- "Installation took just minutes, and we began seeing data flowing in within a few hours." — Dan John (Italy), [polar-analytics.md](../competitors/polar-analytics.md)
- "No more digging through spreadsheets — just instant, actionable data." — Baron Barclay Bridge Supply, [bloom-analytics.md](../competitors/bloom-analytics.md)
- "Bloom has been a great tool for real time analytics of our shopify [store]." — World Rugby Shop, [bloom-analytics.md](../competitors/bloom-analytics.md)

**Theme: Free / generous starter that lets you stay in the empty state without paying**
- "Its Founders dash is something that all Shopify brands should be using. It's free and puts all the metrics you need to know about your high level business performance in a single place." — Head West Guide, [triple-whale.md](../competitors/triple-whale.md)
- "Must-have tool, yet at a very affordable price." — OMOYÉ (France), [bloom-analytics.md](../competitors/bloom-analytics.md)

**Theme: Pre-built opinionated views so the empty product looks pre-configured**
- "Simple to use, seriously rich insights, all action-orientated." — Rapanui Clothing, [conjura.md](../competitors/conjura.md)
- "simplified, impactful dashboards that help make decision making easier" — Raycon, [lifetimely.md](../competitors/lifetimely.md)

## What users hate about this feature

**Theme: Concierge that under-delivers**
- "They used to have amazing support, but as of recent, they have stripped all support" — Joey B., SEM Consultant, [northbeam.md](../competitors/northbeam.md)
- "Northbeam recently stripped all support from the platform for clients who pay up to $1k/month, including onboarding." — Capterra reviewer, [northbeam.md](../competitors/northbeam.md)
- "Many questions are met with 'I'll need to ask someone else.'" — Zamage, [triple-whale.md](../competitors/triple-whale.md)
- "Support is very slow, the app does not load the prices and the price is far too expensive… If I pay 150 euros a month, I expect direct live support. During the time the support answered me, I simply switched the app." — Sellsbydanchic, [lifetimely.md](../competitors/lifetimely.md)

**Theme: Long, painful onboarding before any data appears**
- "Northbeam's onboarding was really bad" — G2 reviewer, [northbeam.md](../competitors/northbeam.md)
- "going back and forth for 29 days and being unable to finish the setup" — G2 reviewer, [northbeam.md](../competitors/northbeam.md)
- "extremely hard" onboarding "despite paying for a 3-month package" — Trustpilot reviewer, [northbeam.md](../competitors/northbeam.md)
- "Initially overwhelming" due to data complexity and multiple stakeholder involvement — Kira H., [conjura.md](../competitors/conjura.md)

**Theme: ML/pixel calibration period feels like the product doesn't work**
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed, [triple-whale.md](../competitors/triple-whale.md)
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson, [triple-whale.md](../competitors/triple-whale.md)

**Theme: "Real-time" promised, "every few hours" delivered**
- "Switching between views and reports can be slow sometimes" — bloggle.app review, [polar-analytics.md](../competitors/polar-analytics.md)
- "Most Polar Analytics customers leave the platform because of: delayed data retrieval, incomplete metrics and documentation, and occasional slow loading times." — comparison-article synthesis, [polar-analytics.md](../competitors/polar-analytics.md)
- (Lifetimely real-time-vs-actual: marketing says "real-time"; reviewers say "every few hours") — paraphrased, [lifetimely.md](../competitors/lifetimely.md)

**Theme: UI changes during empty phase erode trust**
- "The feature set is expanding rapidly, which means the UI changes frequently and documentation sometimes lags behind." — Derek Robinson / Noah Reed, [triple-whale.md](../competitors/triple-whale.md)

## Anti-patterns observed

- **Forcing the merchant into a 90-day wait without progressive unlocking** (Northbeam) — Day-90 Profitability panel is honest but, for sub-$1k/mo Starter customers, reads as "I paid and can't see numbers." Multiple Trustpilot reviewers cite refund disputes during this window.
- **Marketing "real-time" while shipping daily/few-hours batches** (Lifetimely, Conjura, partly Polar) — sets up a credibility wedge as soon as the user notices the timestamp. Lifetimely is the loudest example; Conjura admits "refreshed nightly" in their own help docs.
- **No sample-data demo mode at all in the SMB tier** (entire batch) — every competitor requires a real Shopify connection before showing any dashboard. The "open demo" CTA on Bloom resolves to a Shopify-shop-domain install gate (per Bloom profile). Direct gap.
- **Sales-call gate before any product touch** (Northbeam, top-tier Polar, Conjura enterprise) — empty state extends to the *pre-install* phase. SMB merchants treat this as disqualifying.
- **Dashboard UI that visibly shows tile widgets but the date-range comparison is empty for the first period** — common to all 7. Not an anti-pattern in itself but creates the universal "0% From Last Month" placeholder issue. Bloom's KPI cards are the most honest about it (showing the placeholder explicitly); Triple Whale collapses empty sections instead.
- **Hidden cost-config requirements** — StoreHero's force-the-config-first approach trades early empty-dashboard pain for later "why is contribution margin wrong?" pain. Lifetimely's 3-layer fallback (manual > Shopify cost > default %) is the inverse choice. Both are defensible; the anti-pattern is doing neither and showing structurally wrong margins.
- **Pixel calibration with no in-product Day-N indicator** (Triple Whale, Lifetimely, Bloom, Polar) — none of the four expose a visible "your pixel is X days into a Y-day calibration" progress UI. Northbeam is alone in surfacing the calibration state explicitly.
- **Recompute banner missing entirely on most products** — only Triple Whale's April 2026 on-demand refresh ("Refreshing Meta…") is documented. Cost-config changes that retroactively rewrite history (Nexstage's `RecomputeAttributionJob` pattern) have no observable competitor analog UI.

## Open questions / data gaps

- **No public screenshots of true skeleton/shimmer loaders.** Every screenshot in every competitor profile shows the dashboard *with data*. The genuine empty state (Day 0, just-installed, KPI tiles greyed-out) is invisible in marketing material — by design. A free-tier signup on Triple Whale + Bloom would be the cheapest way to capture this.
- **No competitor publishes a "data readiness score" UI.** The implicit data-readiness state (sync %, pixel calibration day, COGS coverage) is internal to most products. Northbeam's Day 30/60/90 unlock is the closest visible artifact.
- **Sample-data demo modes (Linear-style "play with our app before you sign up")** were called out in the assignment but **not observed in any of the seven profiles.** "Linear-style tools" is a UX archetype outside the ecommerce-analytics category; it does not currently appear in the competitor set.
- **Tutorial overlays / coachmarks** — not described in any of the seven profiles. Polar's Ask Polar AI and Triple Whale's Moby Chat are the closest analogs, but they are general assistants, not first-run guided tours.
- **G2 / Trustpilot 403'd for several competitors** (Polar, Triple Whale, Northbeam) — verbatim "couldn't get past setup" complaints are likely under-represented in the love/hate quote pool.
- **Mobile empty-state UX entirely unknown.** Triple Whale and StoreHero are the only competitors with native mobile apps; neither profile documents what the mobile empty state looks like on Day 0.

## Notes for Nexstage (observations only — NOT recommendations)

- **Day-N feature gating is the only documented "honest empty state" pattern in the category, and it's owned by Northbeam.** Northbeam's right-rail Profitability panel that "becomes functional only after the 90-day learning period" is a concrete UI primitive Nexstage's `RecomputeAttributionJob` "Recomputing…" banner directly resembles. The thing Northbeam gets criticised for at SMB tier (long wait, paid up front) does not apply to a Nexstage workflow that recomputes in minutes — but the *visible-but-locked* IA pattern is portable.
- **None of the seven competitors ship a sample-data demo mode.** The brief flagged this as worth checking; it is genuinely an open lane in this category. "Linear-style tour the app with fake data" is not industry standard for ecommerce analytics, possibly because the value-prop *is* the merchant's own numbers.
- **Cost-fallback cascades (Lifetimely 3-layer, Bloom 4-layer) make the empty state never-literally-empty.** Nexstage's cost-config pattern can match this — Shopify `cost_per_item` first, then default margin %, then user override — without forcing StoreHero's "block the dashboard until cost-config is done" gate.
- **Pre-built saved views (Conjura) make empty tables look opinionated rather than blank.** Customer Table ships with "loyal customers / new shoppers / high spenders / close to churn" segments. For Nexstage's `OrdersController` / customer surfaces, pre-seeded segments are a low-cost empty-state mitigation.
- **The "Recomputing…" banner has no observable competitor analog UI.** Triple Whale's April 2026 on-demand refresh ("Refreshing Meta…" status cycler) is the closest. Cost-config and attribution-default changes that retroactively rewrite history are a Nexstage-specific UX problem; no competitor publishes a pattern Nexstage can copy.
- **Source-disagreement during the empty/early phase is structurally invisible elsewhere.** Triple Whale's pixel needs 5–7 days; Polar's Snowflake provisioning happens during onboarding; Lifetimely's pixel reclaim numbers aren't published. A 6-source-badge UI that says "Real and Facebook agree at $12k; GA4 says $9k; GSC has 0 days of history yet" makes the empty/calibration state visible *as data*, not as friction. No competitor exposes this.
- **Concierge support is a paid moat that SMB analytics competitors lean on heavily.** Polar (dedicated CSM + Slack on every plan), StoreHero (1:1 onboarding bundled into base), Lifetimely (Sam-the-support-rep is named in reviews), Conjura (Jim is named). Nexstage's hands-off SMB self-serve approach trades concierge cost for product-led empty-state UX — which means the empty state has to do more work than it does for any of these four.
- **Triple Whale's free-forever Founders Dash is the most generous "stay in the empty state without paying" wedge in the category.** 12-month lookback, 10 users, mobile, benchmarks, web analytics — all $0. Sets a high bar for any free/trial tier Nexstage would publish.
- **Two-state vs three-state status indicators:** StoreHero is explicit ("green & red," no amber). Onboarding/data-readiness UI in Nexstage will face the same fork — green/red is faster to read; green/yellow/red carries more information. No clear winner from competitor evidence.
- **Daily/weekly digest emails during empty phase are universal among the SMB-targeted set** (Lifetimely 7am, Bloom Slack, StoreHero, Conjura daily round-up). Re-engagement during the 1–14 day "data is filling in" window is table-stakes; pure in-product UX cannot be the only empty-state strategy.
- **Pixel calibration day-counter is an unmet UI need.** Every product with a first-party pixel (Triple Whale, Polar, Lifetimely, Bloom, Northbeam) has a calibration period. Only Northbeam surfaces it as visible Day-N gating. The gap is the same gap Nexstage's `MetricSourceResolver` could fill — telling the user *which sources are mature enough to trust right now*.
