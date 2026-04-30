---
name: Onboarding
slug: onboarding
purpose: How long until a merchant sees their own data in the product and trusts that the numbers are right.
nexstage_pages: onboarding, store-setup, integrations, dashboard (first-run state), empty-states
researched_on: 2026-04-28
competitors_covered: triple-whale, polar-analytics, lifetimely, beprofit, storehero, northbeam, conjura, lebesgue, cometly, varos, elevar, thoughtmetric, fairing, zigpoll, putler
sources:
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/lifetimely.md
  - ../competitors/beprofit.md
  - ../competitors/storehero.md
  - ../competitors/northbeam.md
  - ../competitors/conjura.md
  - ../competitors/lebesgue.md
  - ../competitors/cometly.md
  - ../competitors/varos.md
  - ../competitors/elevar.md
  - ../competitors/thoughtmetric.md
  - ../competitors/fairing.md
  - ../competitors/zigpoll.md
  - ../competitors/putler.md
---

## What is this feature

Onboarding is the path between "I just installed the app" and "I trust the dashboard enough to make a decision from it." For an ecommerce analytics SaaS the merchant question is not "did the install succeed" — it's "when will the numbers stabilise, and how do I know which numbers are ready?" SMB Shopify/Woo owners come from one of two contexts: (1) they already know Shopify Analytics / GA4 / Meta Ads Manager and want a unified view, so they expect signup-to-first-screen in minutes; or (2) they bounced off a heavyweight tool (Triple Whale, Northbeam) where setup took weeks and want self-serve.

The competitor landscape splits along three axes that show up in every onboarding flow: **OAuth order** (Shopify-first via app-store install vs ads-first via standalone site), **wizard depth** (single-click connect vs multi-step cost/COGS/UTM configuration), and **time-to-trust** (data flows immediately vs ML/pixel calibration period of 25-90 days). The data is always present in source platforms — what onboarding *adds* is the decision to ship a first-run experience that admits the data isn't fully trustworthy yet, or hides that fact behind a "data syncing…" banner.

## Data inputs (what's required to compute or display)

Onboarding itself doesn't compute analytics; it gates *every other feature's* inputs. The required state changes are:

- **Source: Shopify** — OAuth scopes (`read_orders`, `read_products`, `read_customers`, `read_inventory`); historical backfill window (Shopify allows full history; Lifetimely/BeProfit cap by tier-volume); webhook subscriptions for live updates
- **Source: WooCommerce** — REST API key + secret; alternatively a plugin install for webhook delivery
- **Source: Meta Ads** — OAuth via Facebook Login; ad-account IDs; long-lived token refresh
- **Source: Google Ads** — OAuth; manager-account-aware permission grant; customer ID picker
- **Source: GA4** — OAuth; property ID picker; data-stream selection
- **Source: GSC** — OAuth; site-property picker (verified domain owner only)
- **Source: User-input (cost config)** — `cogs_per_product` (when `orders.line_items.cost` missing), shipping rate, payment-processor fee %, custom recurring expenses
- **Source: User-input (channel mapping)** — UTM source/medium → channel taxonomy (often pre-seeded)
- **Source: Computed / system state** — `pixel_install_status`, `data_freshness_timestamp`, `historical_backfill_pct_complete`, `attribution_model_calibration_days_remaining`
- **Source: Pixel/snippet (some competitors)** — JS snippet injection into `theme.liquid` or GTM container; webhook-based purchase events

Frontend onboarding controllers also need a notion of **completion state per step** (Shopify connected ✓, ads connected ✓, COGS entered ✓, pixel verified ✓) and a **next-action recommendation**.

## Data outputs (what's typically displayed)

- **Step list / checklist** — ordered set of connect/configure tasks with state (pending / in-progress / complete / skipped). 5-9 steps typical.
- **Per-source connection card** — logo + status pill (Connected / Disconnected / Error) + last-sync timestamp + "Reconnect" affordance.
- **Time estimate** — "Live in 5 minutes" / "Setup in <10 minutes" / "Dashboard in 48 hours" / "Day 30/60/90 unlock". Marketed prominently; rarely matches reality.
- **Empty-state on dashboards** — illustration + "No data yet — Shopify is syncing your last 90 days, this typically takes 1-3 hours" with a refresh button or auto-poll.
- **Calibration banner** — visible while pixel / attribution model trains: "Attribution data may not be reliable for the first 2-3 weeks while the pixel collects baseline data."
- **Progress indicator on historical backfill** — % complete; date range visible so far.
- **Locked-feature placeholder** — Northbeam's "Profitability" panel literally renders empty until Day 90.
- **Post-onboarding: first email/Slack digest** — Lifetimely sends a daily P&L; Cometly sends an AI Performance Report. The first scheduled digest is itself an onboarding milestone.

## How competitors implement this

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Two parallel entry points — (1) Shopify App Store install ("Founders Dash" free tier, 12-month lookback, 10 users, mobile, benchmarks); (2) `triplewhale.com/free` standalone signup. After install, Settings > Integrations > Data-In API / Pixel installer.
- **Visualization:** Step-list checklist + per-integration card grid. The new "Automated Pixel Installation" CLI auto-injects pixel code for headless/custom stores (no JSON published).
- **Layout (prose):** Top: workspace name + user avatar. Left rail: standard sidebar. Main canvas: integration tiles arranged in groups (Storefronts, Ad Platforms, Email/SMS, Subscriptions, Logistics). "60+ one-click connections" copy is the hero line. After the user connects Shopify, a Summary dashboard renders with placeholder/sample data shape but real values where available.
- **Specific UI:** "On-Demand Data Refresh" button on Summary and Attribution pages added April 2026 — visible status cycling display ("Refreshing Meta…"). Pixel install marketed at "less than 10 minutes."
- **Filters:** None on the integration screen itself.
- **Data shown:** Source name, status, last-sync, lookback window, "Reconnect" CTA.
- **Interactions:** OAuth pop-up per integration; pixel snippet copy-paste OR CLI auto-inject; Triple Pixel needs "5–7 days (or 2–3 days for $4M+ stores) to stabilize" per workflowautomation.net.
- **Why it works (from reviews/observations):** "Best app i've used to track profit/loss great for beginners!" — Elyso, Shopify App Store, February 2, 2026. The Founders Dash free tier is a Trojan horse — multiple reviewers cite it as the reason they kept using the product. Reviewers warn "first 2-3 weeks of data may not be reliable" — Hannah Reed, workflowautomation.net, November 20, 2025.
- **Source:** [triple-whale.md](../competitors/triple-whale.md)

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Shopify App Store install OR `polaranalytics.com` signup. Help Center / Onboarding is Intercom-hosted with 167 articles, plus a dedicated CSM + Slack channel for paid customers.
- **Visualization:** Step-list checklist + connector grid (45+ connectors). UI specifics not directly observable from public sources.
- **Layout (prose):** OAuth-first for Shopify, then a connector picker for ads/email/subscriptions. Custom connectors require Polar support intervention — they cannot be self-served.
- **Specific UI:** "Installation took just minutes, and we began seeing data flowing in within a few hours" — Dan John (Italy), Shopify App Store, May 2025. Polar Pixel (server-side first-party) is real-time post-install.
- **Filters:** N/A on the connector screen.
- **Data shown:** Per-connector status; data freshness ("hourly refresh standard; intraday refresh as paid add-on").
- **Interactions:** OAuth per source; "Setup is described as 'manual and AI-driven'" for Smart Alerts; dedicated CSM proactively schedules onboarding calls on paid plans.
- **Why it works (from reviews/observations):** "Best analytics tool I've ever used. The onboarding calls have greatly helped" — anonymous US reviewer, cited in Polar's alternatives/triple-whale page. "Polar is easy to setup and offers tons of value, KPI's and metrics out of the box" — anonymous Denmark reviewer.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md)

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Shopify App Store install (required); Amazon as paid add-on ($75/mo); 14-day free trial on all paid tiers. Free tier (50 orders/mo) for permanent low-volume use.
- **Visualization:** Integrations page with per-source connect/disconnect tiles. No published wizard screenshot.
- **Layout (prose):** Shopify-required at install ("Shopify is required at install; everything else is optional add-on"). Cost & Expenses tab is a separate post-install configuration: product cost CRUD, default COGS margin, shipping costs, custom recurring costs. Auto-imports Shopify's built-in "Cost per item" field where present.
- **Specific UI:** Three pre-built dashboard templates (Marketing board, Daily overview, Boardroom KPIs) with role-based starters (Founder / eCom Manager / Performance Marketer / CFO / CEO) — gives the first-run user a chooser instead of a blank canvas.
- **Filters:** Tier-volume gating (50 / 3,000 / unlimited orders/mo) only.
- **Data shown:** Per-source status; "Due to restrictions from Amazon, the time period can only be extended as far back as 3 months" on initial Amazon connection.
- **Interactions:** OAuth per source; manual COGS entry (Shopify field auto-imported when populated); 14-day trial then auto-conversion.
- **Why it works (from reviews/observations):** "All features included on every paid tier" reduces decision friction. Customer support (named individual "Sam") is the #1 most-cited praise vector. Negative onboarding reviews focus on initial cost-data entry burden, not the connect flow itself.
- **Source:** [lifetimely.md](../competitors/lifetimely.md)

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Shopify App Store / WooCommerce listing; 14-day free trial on all paid tiers. Annual billing saves 20%.
- **Visualization:** No first-run wizard observable from public sources. Settings > Costs is a 5-section settings tree (Fulfillment, Processing Fees, Calculation Preferences, Marketing Platforms, Custom Operational Expenses, Products Costs).
- **Layout (prose):** OAuth Shopify, then the user is dropped into the dashboard with empty/placeholder cost numbers. To fill them, the user navigates to Settings > Costs and works through the 5-section tree — multiple reviewers describe this as the painful part.
- **Specific UI:** "Predicted Cost" feature (referenced in FAQ) calculates a return-estimation curve over multiple days (e.g. "10% of order value first day, 7% second day") so returns don't have to be entered manually.
- **Filters:** Per-shop scope (multi-shop only on $249/mo Plus tier).
- **Data shown:** Per-source connection status; per-cost-bucket configuration completeness.
- **Interactions:** OAuth + manual cost entry; "Calculation Preferences" reportedly buggy in early-2026 reviews.
- **Why it works (from reviews/observations):** Negative — "Initial setup data-entry burden — multiple reviewers note product-cost / expense entry is heavy upfront; not a '5-minute install' tool." Subscription/billing/cancellation friction post-trial generates 1-star reviews ("$720/year billed for an unused install with no response from support").
- **Source:** [beprofit.md](../competitors/beprofit.md)

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** Shopify App Store (16 May 2025 launch, 13 reviews all 5-star) AND `storehero.ai` direct signup. 14-day trial on Starter, 7-day on Growth. **1:1 onboarding bundled into base pricing** (every paid plan), Elite Support adds DTC-specialist check-ins.
- **Visualization:** Cost-Settings configuration screen with form fields per cost bucket (product costs, shipping, fulfillment/packaging, transaction fees, marketing).
- **Layout (prose):** "configuration screen, accessed during onboarding and revisited as needed." Form-based layout. Profit Platform tier explicitly includes "1:1 onboarding, 24/7 email support" — concierge is part of the product, not an add-on.
- **Specific UI:** Academy module ("Cost Structure") layered on top of the in-app config — pedagogical content embedded into onboarding rather than buried in a help center.
- **Filters:** Revenue-bracket pricing tiers ($0–1M up to $20M+).
- **Data shown:** Per-cost-bucket entry status; revenue-bracket plan match.
- **Interactions:** OAuth Shopify + WooCommerce + ad platforms + GA4 + Klaviyo + Recharge; CSM-led onboarding call; iOS app for ongoing report delivery; native MCP / Claude integration listed as a top-level nav item.
- **Why it works (from reviews/observations):** "Hands-on onboarding & DTC-specialist support as a paid SKU (Elite Support tier) — productizes consultative help that competitors offer ad-hoc." All 13 Shopify App Store reviews are 5-star (small sample but a strong NPS signal).
- **Source:** [storehero.md](../competitors/storehero.md)

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** No Shopify App Store listing observed (connects via OAuth/API, not app marketplace). No free trial — typically requires 3 months upfront billing. Recent (Nov 2023) reviewer report that "all support [was stripped] from the platform for clients who pay up to $1k/month, including onboarding."
- **Visualization:** **Day 30/60/90 progressive feature unlock** is the literal product mechanic — features unlock sequentially as the model trains. Right-rail Profitability widget visibly stays empty/locked until Day 90.
- **Layout (prose):** Onboarding is a multi-week sales-engineer-driven configuration of pixel deployment, ad-platform OAuth, breakdowns manager, attribution-model selection, and Apex (push-back to Meta). The Attribution Home loads on Day 1 with a Profitability right-rail panel that "becomes functional only after you have passed the 90-day learning period." Clicks + Modeled Views (MTA) "takes 25-30 days to learn from historical data." Profit Benchmarks unlocks at Day 90.
- **Specific UI:** Locked panel placeholder — explicitly empty until Day 90, not hidden. Inline tooltips on each gated feature explain why the wait is happening. "✅ green check" status indicator on the Apex configuration tile when Meta connection is verified.
- **Filters:** N/A on the onboarding flow itself.
- **Data shown:** Per-source connection status; per-feature unlock countdown (implicit — the model marks elapsed days).
- **Interactions:** Manual sales-engineer setup; pixel verification; OAuth ad platforms; Apex push-back configuration. Feature-unlock is automatic on calendar elapse + minimum data threshold.
- **Why it works (from reviews/observations):** Mostly negative — "Northbeam's onboarding was really bad" (G2 reviewer); "extremely hard onboarding despite paying for a 3-month package" (Trustpilot); "going back and forth for 29 days and being unable to finish the setup" (G2). The Day 30/60/90 unlock is honest but the support-stripped tier makes it feel like punishment rather than pacing.
- **Source:** [northbeam.md](../competitors/northbeam.md)

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Shopify App Store + standalone site; 14-day free trial on all paid Shopify plans; "dedicated onboarding, account manager" bundled into Growth tier ($59.99/mo or $49.99/mo annual).
- **Visualization:** Connector picker; LTV-heatmap interpretation guidance is itself onboarding content.
- **Layout (prose):** Standard OAuth Shopify install, then connect ads / GA4 / marketplaces. Help docs explicitly teach users to read horizontal/vertical/diagonal patterns in the LTV heatmap — pedagogical onboarding most competitors skip.
- **Specific UI:** Cohort heatmap with help-doc explanation embedded; "Initially overwhelming" UI is a documented review theme (Kira H., G2, October 2022).
- **Filters:** GMV-bracket pricing selector ($0–2M, $2–5M, $5–10M, $10–20M, $20M+).
- **Data shown:** Per-source status; per-dashboard interpretation guide.
- **Interactions:** OAuth-first; "Some of our more unique data sources didn't have a pre-built Conjura data connector. Custom-built connectors took a little longer." — Andy B., Capterra, January 2019.
- **Why it works (from reviews/observations):** "Excellent customer support, especially during setup. Jim was very helpful with creating the reports we need." — Relish (UK), Shopify App Store, August 2025. Pedagogical inline content (interpretation guidance) is unusual.
- **Source:** [conjura.md](../competitors/conjura.md)

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Shopify App Store; revenue-banded Le Pixel pricing ($99–$1,649/mo); Le Pixel requires Advanced or Ultimate Analytics plan — onboarding gates the pixel install behind a tier upgrade.
- **Visualization:** Reddit Pixel install guide published (suggesting separate per-platform install walkthroughs). UI specifics not directly observable.
- **Layout (prose):** OAuth Shopify + ad platforms; pixel snippet install for Le Pixel attribution. Server-side / CAPI is upsold as the Enrichment tier ($149–$1,649/mo).
- **Specific UI:** Henri AI chat sidebar — first-run user can ask natural-language questions instead of building dashboards from scratch.
- **Filters:** Revenue tier; sub-50-orders/mo "Advanced-Small" tier hidden on website but visible on Shopify App Store.
- **Data shown:** Per-source connection status; pixel install verification.
- **Interactions:** OAuth + pixel install; AI cross-device matching kicks in once pixel collects baseline.
- **Why it works (from reviews/observations):** Le Pixel "AI cross-device matching, complete customer journeys" promised post-install; calibration period not explicitly published but implied by the "AI" framing.
- **Source:** [lebesgue.md](../competitors/lebesgue.md)

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** Standalone site signup (`cometly.com`); usage-tied pricing scaling with ad spend; 14-day free trial cited by tripleareview.com. Onboarding has a dedicated nav surface per kaleo.design case study.
- **Visualization:** **Setup wizard** — explicitly named as "guided steps and integrations for Shopify and Facebook." 7-step Shopify integration walkthrough.
- **Layout (prose):** Setup wizard for the Cometly Pixel: (1) install pixel in `theme.liquid` before `</head>`; (2) create a Purchase webhook in Cometly under Integrations > Webhooks; (3) copy the webhook URL into Shopify (Settings > Notifications > Webhooks); (4) map fields (First Name, Last Name, Email, Phone, `current_total_price`, IP, `comet_token`, `fingerprint`); (5) test end-to-end via the Event Log; (6) choose between Cometly's Meta CAPI or Shopify-native Meta CAPI (not both); (7) optionally enable Google Conversion API.
- **Specific UI:** Pixel install code block; webhook URL with copy button; field-mapping table; Event Log table for verifying test events with required identity fields populated.
- **Filters:** N/A.
- **Data shown:** Per-step completion state; Event Log live test results.
- **Interactions:** Copy/paste pixel snippet; configure webhook in two systems; verify in Event Log; choose between competing CAPI paths.
- **Why it works (from reviews/observations):** "Quick setup that can be live in minutes" (kaleo.design). Counter-evidence: "Complex setup, outdated documentation, and a lack of pricing transparency as drawbacks" (gethookd.ai). "Server-side tracking is the default install path, not a paid upgrade" — onboarding front-loads the pixel install rather than offering it as an upsell.
- **Source:** [cometly.md](../competitors/cometly.md)

### Varos ([profile](../competitors/varos.md))
- **Surface:** Standalone signup, free tier available. Critical onboarding distinction: dashboard generation is gated behind a 48-hour manual review.
- **Visualization:** Connect-platforms screen + post-connect waiting state.
- **Layout (prose):** "Post-connect state: 'Your dashboard will be generated within 48 hours' (manual review)." Cold-start problem is acknowledged: "Initial data gathering period required for trend visibility" + "Limited historical data initially."
- **Specific UI:** No dashboard rendered until manual review completes.
- **Filters:** N/A.
- **Data shown:** Per-source connection status; "dashboard pending" message.
- **Interactions:** OAuth ad platforms; wait 48h.
- **Why it works (from reviews/observations):** Negative — the cold-start problem is the dominant complaint pattern. Acceptable for benchmarks-as-the-product (need peer dataset) but feels broken to a user expecting instant dashboards.
- **Source:** [varos.md](../competitors/varos.md)

### Elevar ([profile](../competitors/elevar.md))
- **Surface:** Shopify App Store; 15-day free trial on all paid plans; no long-term contracts; paid Expert Installation ($1,000+) commonly purchased for the technical GTM-server-side path.
- **Visualization:** Per-destination configuration tile — click destination → configure → enable.
- **Layout (prose):** Per-destination grid (Meta CAPI, Google, TikTok, Klaviyo, etc.). Each tile is configured separately. Setup claimed at "under 15 minutes" in marketing copy; reviewers contradict for full configurations.
- **Specific UI:** Channel match rate (% of Shopify orders successfully delivered to each destination) shown post-install — a verification mechanism merchants can read at a glance.
- **Filters:** Plan tier (gates server-side features).
- **Data shown:** Per-destination match rate; identity-graph status.
- **Interactions:** OAuth + destination configuration + GTM container edits (advanced).
- **Why it works (from reviews/observations):** "Setup complexity at the deeper end. Multiple sources say technical setup (especially GTM server-side container path) is hard enough that paid Expert Installation ($1,000+) is commonly purchased."
- **Source:** [elevar.md](../competitors/elevar.md)

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Standalone signup; supports Shopify, WooCommerce, BigCommerce, Magento (rare in the category). $99/mo entry; "every feature included at every tier."
- **Visualization:** Not directly observable from public sources.
- **Layout (prose):** OAuth-first per platform; multi-platform store support means the wizard branches by storefront type at step 1.
- **Specific UI:** Marketing pitch is explicit: "simpler setup, easier to use, faster time to value, deeper insights, broader integrations" — vs. Triple Whale, Northbeam, Wicked Reports, Cometly, Polar.
- **Filters:** Storefront type at first step.
- **Data shown:** Per-source status.
- **Interactions:** OAuth-first.
- **Why it works (from reviews/observations):** Speed and simplicity is the loud differentiator vs. Triple-Whale-class incumbents.
- **Source:** [thoughtmetric.md](../competitors/thoughtmetric.md)

### Fairing ([profile](../competitors/fairing.md))
- **Surface:** Shopify App Store; 14-day trial on every paid tier ($15/$49/$149).
- **Visualization:** Survey-builder wizard (post-purchase survey is the product) + automatic Shopify metafield write-back.
- **Layout (prose):** OAuth Shopify, then survey builder. "Historical data is backfilled when you enable the integration, so you're not starting from zero."
- **Specific UI:** Backfill is explicitly marketed — survey responses appear on historical orders retroactively.
- **Filters:** Per-tier order-volume cap.
- **Data shown:** Per-survey response volume; backfill completion.
- **Interactions:** OAuth + survey design; analytics dashboards "automatically refresh every 30 minutes" with last-refresh timestamp shown.
- **Why it works (from reviews/observations):** Backfill quote eliminates the "I just installed and there's no data" empty state — a deliberate product choice.
- **Source:** [fairing.md](../competitors/fairing.md)

### Zigpoll ([profile](../competitors/zigpoll.md))
- **Surface:** Shopify App Store; tagline "Live in 5 minutes — no developer needed."
- **Visualization:** Survey-template picker → preview → publish.
- **Layout (prose):** Click-through wizard. Pitch is squarely SMB Shopify.
- **Specific UI:** Template-picker as the first-run experience.
- **Filters:** N/A.
- **Data shown:** Survey-by-survey status.
- **Interactions:** Pick template → preview → publish.
- **Why it works (from reviews/observations):** "5 minutes — no developer needed" is the entire wedge. Time-to-first-screen is the value prop.
- **Source:** [zigpoll.md](../competitors/zigpoll.md)

### Putler ([profile](../competitors/putler.md))
- **Surface:** Standalone signup; 14-day free trial, no credit card required; "Fully-featured", "unlimited accounts", "reports based on previous 3 months" during trial.
- **Visualization:** Connector grid; trial reports from previous 3 months auto-populated.
- **Layout (prose):** OAuth-first across Shopify/Stripe/PayPal/WooCommerce. Trial dashboards immediately render against historical data — no waiting period.
- **Specific UI:** Pre-populated 3-month historical view at trial start.
- **Filters:** Per-source filtering (multi-store from start).
- **Data shown:** Auto-populated dashboards; sync status (5-minute refresh cadence claimed; 15-30 min PayPal lag observed).
- **Interactions:** OAuth + auto-render dashboards.
- **Why it works (from reviews/observations):** No-credit-card trial + immediate historical data + multi-account-from-day-1 reduces signup friction. PayPal sync delays are a recurring complaint contradicting "real-time" marketing.
- **Source:** [putler.md](../competitors/putler.md)

## Visualization patterns observed (cross-cut)

- **Connector-grid checklist:** ~10 competitors (Triple Whale, Polar, Lifetimely, BeProfit, StoreHero, Conjura, Lebesgue, Elevar, Putler, ThoughtMetric) — universal default. Per-source tile with logo + status pill + "Connect"/"Reconnect" CTA. No competitor differentiates strongly here.
- **Step-numbered wizard:** 3 competitors (Cometly's 7-step Shopify pixel walkthrough, Zigpoll's 5-minute template picker, Polar's CSM-guided onboarding call). Used when there's a clear linear sequence (pixel install) or when the product wants to constrain choices.
- **Locked-feature placeholder (Day-N unlock):** 1 competitor (Northbeam) — Profitability widget visibly empty until Day 90; Clicks + Modeled Views unlocks at Day 25-30. Honest pacing of ML calibration; only viable if marketed as a feature, not a defect.
- **Calibration banner:** 2 competitors (Triple Whale's "first 2-3 weeks unreliable" caveat in reviewer testimonials; Lebesgue's AI cross-device matching implies similar). Often surfaced via reviewer warnings rather than first-party UI.
- **Backfilled-on-install:** 2 competitors (Fairing for survey responses on historical orders; Putler for 3-month historical at trial start). Eliminates the cold-start empty state.
- **Pedagogical inline content:** 2 competitors (Conjura's LTV heatmap interpretation guide; StoreHero's Academy module). Content embedded into the dashboard rather than buried in help center.
- **Manual-review gating:** 1 competitor (Varos, "Your dashboard will be generated within 48 hours"). Acceptable for benchmark products needing peer dataset; reads as broken otherwise.
- **Pre-built dashboard role-templates:** 1 competitor (Lifetimely — Founder / eCom Manager / Performance Marketer / CFO / CEO starters). Replaces "blank canvas" with "opinionated default."

Visual conventions: green checkmarks for completed steps; OAuth pop-ups branded by source; status pills (green = Connected, red = Error, grey = Disconnected); "Live in N minutes" copy as a hero claim across at least 6 of the 15 competitors covered.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Speed to first data**
- "Installation took just minutes, and we began seeing data flowing in within a few hours." — Dan John (Italy), Shopify App Store, May 2025 (from [polar-analytics.md](../competitors/polar-analytics.md))
- "Polar is easy to setup and offers tons of value, KPI's and metrics out of the box" — anonymous Denmark reviewer, Shopify App Store (from [polar-analytics.md](../competitors/polar-analytics.md))
- "Best app i've used to track profit/loss great for beginners!" — Elyso, Shopify App Store, February 2, 2026 (from [triple-whale.md](../competitors/triple-whale.md))

**Theme: Concierge / human-led onboarding**
- "Best analytics tool I've ever used. The onboarding calls have greatly helped" — anonymous US reviewer (from [polar-analytics.md](../competitors/polar-analytics.md))
- "Excellent customer support, especially during setup. Jim was very helpful with creating the reports we need." — Relish (UK), Shopify App Store, August 2025 (from [conjura.md](../competitors/conjura.md))

**Theme: All features included from day one**
- All Lifetimely features included on every paid tier (including Free) — differentiation is purely by order volume + support level — easy upgrade conversation. (from [lifetimely.md](../competitors/lifetimely.md))
- ThoughtMetric: "No gated features. No premium add-ons. No paying extra to see the metrics that matter." (from [thoughtmetric.md](../competitors/thoughtmetric.md))

**Theme: Free-tier as Trojan horse**
- "Its Founders dash is something that all Shopify brands should be using. It's free and puts all the metrics you need to know about your high level business performance in a single place." — Head West Guide review, 2026 (from [triple-whale.md](../competitors/triple-whale.md))

## What users hate about this feature

**Theme: Multi-week setup with no self-serve path**
- "Northbeam recently stripped all support from the platform for clients who pay up to $1k/month, including onboarding." — Capterra reviewer (paraphrase from Joey B. context), November 2023 (from [northbeam.md](../competitors/northbeam.md))
- "Northbeam's onboarding was really bad" — G2 reviewer cited in third-party aggregator (from [northbeam.md](../competitors/northbeam.md))
- "extremely hard onboarding despite paying for a 3-month package" — Trustpilot reviewer (from [northbeam.md](../competitors/northbeam.md))
- "going back and forth for 29 days and being unable to finish the setup" — G2 reviewer (from [northbeam.md](../competitors/northbeam.md))
- "Steep onboarding — multiple reviewers describe a learning curve measured in hours-to-weeks, not minutes; users still discovering new reports and charts even after years of use." (from [wicked-reports.md](../competitors/wicked-reports.md))

**Theme: Manual COGS / cost entry burden**
- "Initial setup data-entry burden — multiple reviewers note product-cost / expense entry is heavy upfront; not a '5-minute install' tool." (from [beprofit.md](../competitors/beprofit.md))
- BeProfit: "Settings > Costs > {Fulfillment, Processing Fees, Calculation Preferences, Marketing Platforms} split plus Custom Operational Expenses / Products Costs is a 5-section settings tree." (from [beprofit.md](../competitors/beprofit.md))

**Theme: Calibration period feels like the product is broken**
- "Attribution models require a learning period. The first 2-3 weeks of data may not be reliable as the pixel collects baseline data." — Hannah Reed (Atlas Engineering), workflowautomation.net, November 20, 2025 (from [triple-whale.md](../competitors/triple-whale.md))
- "Some attribution data conflicts with other measurement tools, and resolving discrepancies requires statistical understanding." — Derek Robinson, workflowautomation.net, March 16, 2026 (from [triple-whale.md](../competitors/triple-whale.md))

**Theme: Cold-start with no historical data**
- "Cold-start problem. 'Initial data gathering period required for trend visibility' + 'Limited historical data initially' + 'Your dashboard will be generated within 48 hours' — the product has a non-trivial onboarding lag because Varos manually reviews accounts." (from [varos.md](../competitors/varos.md))

**Theme: Complex pixel/server-side install**
- "Setup complexity at the deeper end. Multiple sources say technical setup (especially GTM server-side container path) is hard enough that paid Expert Installation ($1,000+) is commonly purchased." (from [elevar.md](../competitors/elevar.md))
- "Complex setup, outdated documentation, and a lack of pricing transparency as drawbacks." — gethookd.ai review aggregator (from [cometly.md](../competitors/cometly.md))
- "Tried to get help for about 2 months, and had to try multiple different solutions that might work." — Trustpilot reviewer summary (from [cometly.md](../competitors/cometly.md))

**Theme: Free trial → surprise charge**
- BeProfit: "Multiple 2025-2026 1-star reviews cite charges continuing months after cancellation requests; one reviewer reports $720/year billed for an unused install with no response from support." (from [beprofit.md](../competitors/beprofit.md))
- "I signed up for free trial for 14 days, after 14 days, 1 month subscription fees for pro plan deducted automatically from my debit card, I did not plan to continue as I don't find it useful for me, tried to contact support and Mr. Ray the CEO by email to try discuss refund, and no reply at all even when I followed up with many emails!" — Mohammad F., Sitejabber, 1-star review, April 28, 2025 (from [atria.md](../competitors/atria.md))

## Anti-patterns observed

- **48-hour manual-review gate before any dashboard renders (Varos):** users connect platforms, get a "your dashboard will be generated within 48 hours" message, and bounce. The cold-start cost is hidden as a feature when in practice it reads as broken software. (from [varos.md](../competitors/varos.md))
- **Cost-config buried in a 5-section settings tree (BeProfit):** Settings > Costs split into Fulfillment / Processing Fees / Calculation Preferences / Marketing Platforms + Custom Operational Expenses + Products Costs. Multiple reviewers cite setup burden. The data-entry IS the onboarding. (from [beprofit.md](../competitors/beprofit.md))
- **Pixel-install upsold as a paid tier (Lebesgue):** Le Pixel attribution requires Advanced or Ultimate Analytics plan. The thing that makes the data trustworthy is gated behind a tier upgrade, so the free/cheap-tier experience is structurally less accurate. (from [lebesgue.md](../competitors/lebesgue.md))
- **Multi-week setup with no self-serve path + support stripped at <$1k/mo (Northbeam):** "29 days back and forth," "still not properly onboarded after a month." The Day 30/60/90 unlock is intellectually honest but combined with stripped low-tier support feels like punishment. (from [northbeam.md](../competitors/northbeam.md))
- **Two competing CAPI paths in the wizard (Cometly):** step 6 forces the user to choose between Cometly's Meta CAPI and Shopify-native Meta CAPI ("not both"). A user without context can't make this choice; "complex setup, outdated documentation" complaint pattern. (from [cometly.md](../competitors/cometly.md))
- **Auto-charge after trial with no clear cancellation path (BeProfit, Atria):** 1-star reviews specifically about post-trial billing surprises rather than the product itself. The onboarding flow's last step is technically the trial-conversion step, and competitors lose customers to it. (from [beprofit.md](../competitors/beprofit.md), [atria.md](../competitors/atria.md))
- **Marketing claim "Live in 5/10/15 minutes" contradicted by reviewer experience (Triple Whale, Elevar):** "less than 10 minutes" pixel install vs "first 2-3 weeks unreliable" calibration; "under 15 minutes" Elevar setup vs "$1,000+ Expert Installation" purchased commonly. The headline number is for the connect step, not the trust step. (from [triple-whale.md](../competitors/triple-whale.md), [elevar.md](../competitors/elevar.md))
- **Required pixel install in `theme.liquid` for non-technical merchants (Cometly, Lebesgue):** copy-paste a snippet before `</head>` is the Cometly step 1. SMB Shopify merchants without a developer struggle. Triple Whale's "Automated Pixel Installation" CLI is a deliberate counter — but only for headless/custom stores. (from [cometly.md](../competitors/cometly.md), [lebesgue.md](../competitors/lebesgue.md))

## Open questions / data gaps

- **No competitor publishes screenshots of their actual first-run wizard.** Most onboarding UI detail is reconstructed from help-center articles or kaleo.design case study. Free-tier signup walkthroughs would close this gap (Triple Whale Founders Dash, Polar 14-day trial, Lifetimely free, Putler 14-day no-credit-card trial are the easiest to capture).
- **Triple Whale Compass / MMM onboarding** — gated behind Pro tier; no public detail on how merchants reach the unlock state for MMM specifically.
- **Northbeam Day 30/60/90 unlock UX detail** — the "Profitability right-rail panel literally stays empty until Day 90" is documented but the visual treatment (placeholder copy, illustration, countdown vs. blank panel) is not described in public sources.
- **Polar Analytics CSM-led onboarding call structure** — referenced in reviews but no agenda/template published.
- **StoreHero Academy module sequence** — bundled into base pricing per marketing copy; the actual lesson order and length is not visible without a paid trial.
- **Cometly time-to-first-trustworthy-data after pixel install** — no explicit calibration window quoted; merchants describe "EMQ went from 4.5 to 9.4 overnight" but EMQ is Meta's metric, not a calibration period.
- **Whether any competitor offers a "data-readiness dashboard"** that explicitly tells the merchant which numbers are ready to trust vs. still calibrating. Northbeam is closest with the locked Profitability panel; no one else surfaces this as a first-class user concept.

## Notes for Nexstage (observations only — NOT recommendations)

- **OAuth order is consistently Shopify-first across SMB-targeted competitors.** Triple Whale, Polar, Lifetimely, BeProfit, StoreHero, Conjura, Lebesgue, Elevar, Putler, Fairing, Zigpoll all install via Shopify App Store as the canonical entry. Standalone-site signups (Cometly, ThoughtMetric, Northbeam, Varos) skew toward ads-heavy buyer personas. Implication: the storefront connection is the trust-anchor, not the ads connection.
- **Northbeam's literal Day 30/60/90 unlock is the only example of "honest pacing as a feature" in the corpus.** Profitability panel stays empty until Day 90; Clicks + Modeled Views (MTA) takes 25-30 days. Universally panned as onboarding ("29 days back and forth") but the *concept* of progressive feature reveal as a function of data maturity is unique. Direct UI precedent for any Nexstage flow that depends on `RecomputeAttributionJob` or pixel calibration.
- **Calibration period vs. data freshness vs. time-to-first-screen are three different latencies; competitors collapse them into one "live in N minutes" claim.** Triple Whale: pixel installs in 10 minutes, but first 2-3 weeks unreliable. Polar: data flows in hours, but custom connectors require support intervention. Putler: trial dashboards immediate against 3-month historical, but PayPal sync lags 15-30 min. The 6-source-badge framing already separates these latencies per source — implication: each source could surface its own readiness state ("Real ✓ — last hour", "Facebook calibrating — 18 days remaining", "GA4 ✓ — last 4h").
- **Backfill-on-install is rare but loved.** Fairing's "historical data is backfilled when you enable the integration, so you're not starting from zero" + Putler's 3-month historical at trial start are the only two competitors that explicitly prevent the cold-start empty state. Most others render an "empty state" or sample-data-shape until the first sync completes.
- **Cost-config is the single biggest manual-entry burden.** BeProfit's 5-section settings tree, StoreHero's Academy module pairing with a config screen, Lifetimely's separate Cost & Expenses tab — all converge on the pattern that the connect step is fast but the cost/COGS step is slow. Lifetimely auto-imports Shopify's "Cost per item" field where present; that's the cheapest way to reduce burden. Maps to Nexstage's `UpdateCostConfigAction` triggering retroactive recalc.
- **"Concierge onboarding bundled into base pricing" appears at StoreHero, Polar, Conjura.** Productizing the human onboarding call is a strong NPS lever — every "the onboarding calls have greatly helped" verbatim quote in this corpus comes from a competitor that bundles a call. Counter-pattern: Northbeam stripped support from <$1k/mo tiers and gets crucified for it.
- **Pixel install is the highest-friction onboarding step in the corpus.** Cometly's 7-step Shopify pixel walkthrough, Lebesgue's tier-gated Le Pixel, Elevar's $1,000+ Expert Installation upsell, Triple Whale's CLI auto-inject for headless stores — every competitor that requires a pixel has accumulated review pain. Nexstage's import-via-API path (no pixel required) is structurally lighter; this is a wedge that doesn't require building anything.
- **Free trial → auto-charge is a recurring cancellation-friction anti-pattern (BeProfit, Atria).** Multiple 1-star reviews specifically about the trial-end billing rather than the product. Implies the trial-conversion UX is an onboarding *exit* surface that competitors under-invest in.
- **6 of 15 competitors use a "Live in N minutes" hero claim (Cometly, Zigpoll, Triple Whale pixel, Elevar, Putler, ThoughtMetric).** No competitor publishes a "you'll have 30 days of trustworthy data in N days" claim — i.e. nobody markets time-to-trust, only time-to-connect. Whitespace for any product willing to be honest about the difference.
- **Lifetimely's pre-built dashboard role-templates (Founder / eCom Manager / Performance Marketer / CFO / CEO) replace the blank-canvas first-run state.** Concrete pattern for any Nexstage onboarding step that lands the user on a dashboard.
- **Conjura embeds interpretation guidance (horizontal/vertical/diagonal heatmap-pattern explanation) directly into the dashboard.** Pedagogical content as part of the surface, not a help-center hop. Almost no competitor does this.
- **Triple Whale's Founders Dash free-forever tier (12-month lookback, 10 users, mobile, benchmarks, web analytics) is the most generous free tier in the corpus.** Lifetimely's 50-orders/mo free is the runner-up. Free-tier-as-Trojan-horse is consistently cited as the conversion path; reviewers repeatedly mention upgrading after the free tier proved the value.
