---
name: Post-purchase attribution survey
slug: attribution-survey
purpose: Capture and surface the customer's self-reported answer to "Where did you hear about us?" as a first-class attribution lens alongside pixel/UTM-based sources.
nexstage_pages: acquisition, attribution, channel-mapping
researched_on: 2026-04-28
competitors_covered: fairing, zigpoll, thoughtmetric, polar-analytics, daasity, northbeam, triple-whale, segmentstream, lifetimely, peel-insights, rockerbox
sources:
  - ../competitors/fairing.md
  - ../competitors/zigpoll.md
  - ../competitors/thoughtmetric.md
  - ../competitors/polar-analytics.md
  - ../competitors/daasity.md
  - ../competitors/northbeam.md
  - ../competitors/triple-whale.md
  - ../competitors/segmentstream.md
  - ../competitors/lifetimely.md
  - ../competitors/peel-insights.md
  - ../competitors/rockerbox.md
  - https://docs.fairing.co/docs/question-stream
  - https://docs.fairing.co/docs/comparison-analytics
  - https://docs.fairing.co/docs/time-series-analytics
  - https://help.daasity.com/advanced/marketing-attribution/survey-based-attribution
  - https://thoughtmetric.io/customer_surveys
---

## What is this feature

The post-purchase attribution survey is a one-question (sometimes few-question) widget that fires on the order-confirmation / thank-you page after checkout and asks the customer "How did you hear about us?" (HDYHAU). The merchant question being answered is intentionally orthogonal to pixel and UTM data — instead of "what platform claimed credit," it captures "what the human says they remember." For SMB Shopify/Woo merchants whose paid-ads, organic-search, podcast, influencer, and word-of-mouth channels collide in a single funnel obscured by iOS-14 / cookie loss, the survey is the cheapest available zero-party signal for hard-to-track channels (podcasts, billboards, friends, TV) that pixel-based attribution structurally cannot see.

The difference between "having data" and "having this feature" is the synthesis layer: every Shopify checkout extensibility plugin can show a question, but a real attribution-survey product (i) rotates / throttles the question stream so customers never see a 12-question form, (ii) classifies free-text responses (often via LLM) into canonical channels, (iii) merges the response onto the order record so it can be sliced by LTV / cohort / product / region, and (iv) surfaces it as a *parallel column* to pixel/UTM-attributed revenue so merchants can see the disagreement. Fairing is the category-defining example; Triple Whale, Polar, ThoughtMetric, and Lifetimely have all built native survey modules; Daasity, Peel, and Northbeam ingest Fairing/Kno responses as a queryable dimension.

## Data inputs (what's required to compute or display)

- **Source: Survey widget (storefront)** — `question_id`, `question_text`, `response_value` (single/multi-select), `response_text` (free-text), `customer_id`, `order_id`, `timestamp`, `device`, `language`. Fairing notes the widget renders on the Shopify Thank-You / Order Status pages or via Web SDK on Woo / SFCC / headless.
- **Source: Shopify / Woo orders** — `order_id`, `total_price`, `currency`, `customer_type` (new vs returning), `country`, `discount_codes`, `line_items`, `product_type`. Per `../competitors/fairing.md`, Fairing writes responses back to Shopify as **order metafields** so they're queryable in ShopifyQL/Sidekick.
- **Source: UTM / referrer payload** — `utm_source` (first + last touch), `utm_medium`, `utm_campaign`, `original_referrer`, `landing_page`, `session_id`, device + geo. Per `../competitors/zigpoll.md`, Zigpoll captures all of these alongside the response so survey vs UTM can be cross-referenced.
- **Source: Channel mapping config** — user-defined mapping of free-text response → canonical channel (Direct, Paid Social, Influencer, Podcast, Friend, …). Multi-Tier Attribution in Fairing uses parent/child source hierarchy.
- **Source: AI classifier** — LLM-driven categorisation of free-text "Other" responses. SegmentStream's measurement-engine page describes "LLM classification of free-text survey responses" as TECH 3 ("Self-Reported Reattribution"). Fairing markets a "Recategorize Responses" tool. Per `../competitors/fairing.md` the AI confidence-chip UX is "not publicly verifiable — UI details not available."
- **Source: Question rotation rules** — `targeting_rule` (new vs returning, location, purchase history, frequency), `priority`, `status` (live / paused / draft), `randomize_options` flag.
- **Source: Webhook destinations** — Klaviyo profile properties, Meta CAPI custom events, Shopify metafields, BigQuery (Fairing $299/mo Data Sync add-on).
- **Source: Computed** — `response_rate = responses / views`; `nps = %promoters - %detractors`; `ltv_by_response = SUM(revenue WHERE response = X) / COUNT(DISTINCT customers WHERE response = X)`; `survey_attributed_revenue = SUM(orders.total_price WHERE response_channel = X)`.

## Data outputs (what's typically displayed)

- **KPI: Response rate** — `responses/views`, %, claimed 40-80% on Fairing, 50% headline on Zigpoll.
- **KPI: Total responses** — count, vs prior period delta.
- **KPI: NPS score** — `%promoters - %detractors`, integer.
- **Dimension: Response value** — string, ~5-15 distinct values per question after recategorisation.
- **Dimension: Channel (canonicalised)** — mapped from response value.
- **Dimension: Customer type** — new vs returning.
- **Breakdown: Response × time** — line chart, top-5 responses over time.
- **Breakdown: Response × question** — comparison cross-tab.
- **Breakdown: Response × LTV / order count / revenue** — table.
- **Breakdown: Survey response vs UTM source for same orders** — side-by-side reconciliation.
- **Slice: Per-product, per-cohort, per-region, per-discount-code.**

## How competitors implement this

### Fairing ([profile](../competitors/fairing.md))
- **Surface:** Top-level sidebar — Question Stream | Live Feed | Responses | Customer View | Analytics (Time Series / Comparison / NPS / LTV / Last Click and UTM Report / Multi-Tier Attribution) | AI Insights | Integrations.
- **Visualization:** Top-5 line-chart for time series; cross-tab table for Comparison; three-bucket count strip + single big-number for NPS; side-by-side columnar comparison for Survey vs UTM (multi-tier source hierarchy).
- **Layout (prose):** Time Series — line chart of "top 5 responses charted over time"; X-axis = order date, Y-axis = response % by default (switchable to count); aggregation toggle Day / Week / Month above; last-refresh timestamp ("automatically refreshes every 30 minutes"); response-selector list to the right capped at 5 simultaneous series. Comparison — cross-tab table "always sorted in descending order by Response Count"; rows that didn't answer the second question render as a literal "No Response" cell. NPS — top: count + percent breakdown of Detractors (0-6) / Passives (7-8) / Promoters (9-10), single big-number NPS; bottom: Responses / Views / Response Rate row; tab-switch to time-series of NPS over time.
- **Specific UI:** **Question Stream cards** with Live / Paused / Draft pills, response count, pause/live toggle switch. **Auto Advance** progresses customers through questions; **Randomize Response Options** shuffles answer order to mitigate position bias. **2-step Response Clarification** branching ("If you saw us on social, which platform?"). **Bulk Recategorize Responses** for free-text "Other" buckets. The "AI confidence chip" referenced in third-party hints is **not publicly verifiable — UI details not available** (per `../competitors/fairing.md` "DO NOT FABRICATE").
- **Filters:** Date range, customer type (new vs returning), location, purchase history, frequency, language.
- **Data shown:** Response %, response count, response rate, views, NPS score, LTV by response, multi-tier source rollup.
- **Interactions:** Templates dropdown clones a starting question; targeting rules per question; date-range picker; aggregation toggle; chart-toggle Chart button; CAPI push fires when a response is recorded; Shopify order metafield write-back makes responses queryable in ShopifyQL/Sidekick.
- **Why it works (from reviews/observations):** "Really easy to set up, and has made it really easy to know which influencers and creators are driving sales." — Vaer Watches, Shopify App Store, April 10, 2026 (`../competitors/fairing.md`). "Easy to launch, integrates with every system…one of the most scalable ways to get customer insights." — SURI, Shopify App Store, April 10, 2026.
- **Source:** `../competitors/fairing.md`; https://docs.fairing.co/docs/question-stream; https://docs.fairing.co/docs/time-series-analytics; https://docs.fairing.co/docs/comparison-analytics; https://docs.fairing.co/docs/nps-reporting.

### Zigpoll ([profile](../competitors/zigpoll.md))
- **Surface:** Sidebar — Polls | Slides | Participants | Insights (Z-GPT chat) | Reports | Integrations | Installation.
- **Visualization:** Per-question auto-generated chart (chart-format icons in bottom-right of each card switch between bar / pie / list). Z-GPT summary card. CSV-only Reports surface (no charts in export tab).
- **Layout (prose):** Per-survey dashboard renders one auto-generated chart per question, with date-range picker above. Z-GPT summary card appears once ~25 responses are accumulated, refreshed weekly. Insights tab is a survey-selector dropdown at the top + chat input below.
- **Specific UI:** **Visibility switch** toggle (live/paused) per survey. **Chart-format icons in the bottom right corner of each chart** swap visualization. Export icon "beneath the date range" downloads the chart "for reporting or adding to slide decks." Branching/conditional skip-logic editor on paid plans only. Synthetic responses (100-5,000/mo) generate fake survey completions for empty-state preview.
- **Filters:** Date range, survey selector, customer-type/page-rule targeting at survey-config time.
- **Data shown:** Response counts per option, distribution, NPS, open-ended response samples, AI-summarised themes; full payload includes "UTMs (first + last touch), original referrer, landing page, discount codes, session-level data, device + geo info, customer + order data."
- **Interactions:** Click chart-format icon to switch viz; export per-chart; chat with Z-GPT in plain English; MCP exposes data to Claude/ChatGPT (Advanced+).
- **Why it works:** "adds a layer of attribution to my orders that I was struggling to understand" — The Plants Project, Shopify App Store review, Jan 13, 2026 (`../competitors/zigpoll.md`). "you can see the entire pathway: what the customer clicked on or searched to find your site" — Jones (US), Shopify App Store review, Feb 26, 2026.
- **Source:** `../competitors/zigpoll.md`; https://docs.zigpoll.com/analytics-ai-and-reporting.

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Product > Data & Integrations > Customer Surveys (configuration); responses surface inside Customer Analytics / Marketing Attribution.
- **Visualization:** Configuration form for the question; response viewer layout (donut? table? segmented bar?) **not observable from public sources**.
- **Layout (prose):** Configuration screen for a post-purchase "How did you hear about us?" question with custom response options, custom question text, "fully translatable for use in any region." Survey signal is folded directly into the default Multi-Touch attribution model alongside pixel data — it isn't a separate analytics tab, it's a *signal source* for the existing attribution dashboards.
- **Specific UI:** Custom response options editor; custom question text; per-region translation. Hard-to-track channel pitch: "podcasts or billboards." Response viewer screenshot referenced as "ThoughtMetric Ecommerce Marketing Analytics Screenshot" — specific layout not observable.
- **Filters:** Same as Marketing Attribution (date range, attribution model, 7/14/30/60/90-day window).
- **Data shown:** Survey response options, attributed revenue per response, multi-touch credit blended with pixel.
- **Interactions:** Configure once; survey responses then propagate into the Marketing Attribution + Customer Analytics dashboards as an additional signal in the default Multi-Touch model.
- **Why it works:** "Great overall view of attribution with post purchase survey. Support has been super helpful and responsive. And affordable compared to other attribution software we have looked at." — Bloomers Intimates, Shopify App Store, March 2024 (`../competitors/thoughtmetric.md`).
- **Source:** `../competitors/thoughtmetric.md`; https://thoughtmetric.io/customer_surveys.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Acquisition / Attribution surface — survey responses appear as a column alongside platform-reported and Polar Pixel columns in the side-by-side view.
- **Visualization:** Side-by-side columnar comparison (3 columns: Platform / GA4 / Polar Pixel; survey is referenced via Fairing integration as another lens). 9-10 attribution-model picker.
- **Layout (prose):** Per swankyagency.com walkthrough cited in `../competitors/polar-analytics.md`: "compare and contrast performance being reported by advertising platforms, GA4 and Polar." Survey-based responses (via Fairing native integration) feed into the same explore as a parallel data column.
- **Specific UI:** Attribution-model dropdown (First Click, Last Click, Linear, U-Shaped, Time Decay, Paid Linear, Full Paid Overlap, Full Paid Overlap + Facebook Views, Full Impact). Drill from channel → campaign → ad → order → customer journey.
- **Filters:** Date range, attribution model, View (saved-filter bundle), country/store/channel.
- **Data shown:** Spend, attributed revenue, ROAS, CAC, conversions per model, with Platform / GA4 / Polar / (survey via Fairing) columns.
- **Interactions:** Switch attribution model from a dropdown; the same KPI block re-renders. Survey acts as a separate "lens" not a default column.
- **Why it works:** "Their multi-touch attribution and incrementality testing have been especially valuable for us." — Chicory, Shopify App Store, September 2025 (`../competitors/polar-analytics.md`).
- **Source:** `../competitors/polar-analytics.md`.

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Templates Library > Acquisition Marketing > Attribution Deep Dive (built on Marketing Attribution explore).
- **Visualization:** Looker-embedded explore — a single filterable table with **"Dynamic Attribution Method"** filter that toggles between 8 models (1 First-Click, 2 Last-Click, 3 Assisted, 4 Last-Click+Assisted, 5 Last Ad Click, 6 Last Marketing Click, **7 Survey-Based**, 8 Vendor-Reported). Discount-code performance section. "Assisted lift" comparison visualisation.
- **Layout (prose):** Built on the "Marketing Attribution explore" which rolls up order-level attribution to channel/vendor level. Eight attribution models compared side-by-side. The "Customizing Attribution Logic" control lets users rank methods (e.g. Survey-Based as a default option integrated with third-party tools like Fairing). The four "lenses" (pixel-via-GA, survey, discount-code, vendor-reported) coexist as **filterable dimensions in a single explore**, not separate sub-tabs.
- **Specific UI:** **Dynamic Attribution Method filter-only field** switches model without rebuilding report. **Survey-Based view exposes three explicit dimensions** in the Order Attribution view: **Survey Response (verbatim text), Survey-Based Channel, Survey-Based Vendor**. **Custom Attribution waterfall** lets brands rank fallback sources (e.g. survey → discount-code → GA last-click) for canonical attribution.
- **Filters:** CPA / CPO / gross margin / net sales / ROAS / orders / new-customer orders; UTM dimension drill-down for GA-based models; date range; channel × vendor × attribution model.
- **Data shown:** CPA, CPO, gross margin, net sales, gross sales, ROAS, orders, new-customer orders — by channel × vendor × attribution model. Verbatim survey response text is queryable.
- **Interactions:** Toggle model via Dynamic Attribution Method filter; rank models for Custom Attribution waterfall; export to CSV/Snowflake.
- **Why it works:** Reviewers praise "the ability to centralize each of our data feeds into one all-encompassing view" — Helinox, Shopify App Store, August 4, 2020 (`../competitors/daasity.md`).
- **Source:** `../competitors/daasity.md`; https://help.daasity.com/advanced/marketing-attribution/survey-based-attribution.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Post-Purchase Survey (configuration). Responses surface inside Summary / Sonar / multi-touch attribution dashboards.
- **Visualization:** UI details not deeply observed — public docs describe the survey as a tier-gated builder ("Standard" survey on Free; "Advanced" survey on Starter+).
- **Layout (prose):** Free tier exposes 1-question post-purchase survey on the Founders Dash. Standard post-purchase survey is included in Free; Advanced post-purchase survey unlocks at Starter ($179-$299/mo entry). The survey-attributed revenue rolls into the multi-touch attribution model on Starter+.
- **Specific UI:** Survey-builder; question library; per-tier feature gate. Detailed widget UI not observable — "UI details not available — only feature description seen on marketing page."
- **Filters:** Date range; attribution-model picker; segmentation builder (Advanced+).
- **Data shown:** Response distribution, multi-touch credit blended with Triple Pixel data on Starter+.
- **Interactions:** Configure survey; responses feed Sonar Send retargeting and the Audience Sync push to ad platforms.
- **Why it works (from reviews/observations):** Survey is bundled into the broader product; reviewers don't break out the survey specifically as a praised feature, but the multi-touch model that consumes it is regularly cited as best-in-class.
- **Source:** `../competitors/triple-whale.md`.

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Not a Northbeam-built widget — Northbeam ingests survey data via partner integrations (Rockerbox, Fairing pipelines) rather than running its own surveys.
- **Visualization:** Survey data appears as one of seven attribution models in Model Comparison Tool side-by-side with platform self-reporting; UI details for survey-specific column not separately documented.
- **Layout (prose):** "Compare any two of the 7 models; export to CSV; overlay platform-reported numbers as a third column for reconciliation against Meta/Google self-reporting." Survey credit is one of the available signal sources but not surfaced as a distinct screen.
- **Specific UI:** Model Comparison side-by-side (`../competitors/northbeam.md`); UI specifics for the survey-only column not deeply documented in public sources.
- **Filters:** Date range, attribution model, accounting mode (Cash / Accrual), platform.
- **Data shown:** Per-model attributed revenue and transactions; survey credit folded into the broader model output.
- **Interactions:** Compare any two of the 7 models; CSV export; overlay platform numbers.
- **Why it works:** Northbeam's reviewers don't single out survey functionality — the praise centres on deterministic-views and Apex push-back. Survey is a peripheral signal in their stack.
- **Source:** `../competitors/northbeam.md`.

### SegmentStream ([profile](../competitors/segmentstream.md))
- **Surface:** Project Configuration > Self-reported attribution (post-purchase survey config).
- **Visualization:** Configuration screen; downstream the responses appear inside the Attribution table (configurable metric × dimension explorer) as a model option.
- **Layout (prose):** Per the docs sitemap, "Self-reported attribution (post-purchase survey config)" sits as a sibling to Attribution models inside Project Configuration. The measurement-engine page describes "LLM classification of free-text survey responses" as TECH 3 ("Self-Reported Reattribution"), measuring "word of mouth, podcasts, offline" influence.
- **Specific UI:** Configuration page only — UI for the response viewer not separately documented.
- **Filters:** Same as Attribution table — date range, attribution model, custom dimensions, include/exclude filters.
- **Data shown:** Free-text responses post-classification; attributed revenue per LLM-canonicalised channel.
- **Interactions:** Configure question; LLM classifies free-text; responses feed cross-channel attribution table; CSV export.
- **Why it works:** SegmentStream positions LLM-classification of survey free-text as a numbered measurement technique (TECH 3) — survey is treated as a first-class signal source, not a sidebar feature.
- **Source:** `../competitors/segmentstream.md`.

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Customer Behavior Reports > Customer Product Journey ("noodle" diagram); also a filter dimension across other reports.
- **Visualization:** Sankey-style "noodle" flow diagram of customer 1st → 2nd → 3rd → 4th product purchases; post-purchase-survey response is a *filter* on this diagram (not its primary axis).
- **Layout (prose):** Color-coded Sankey-style bands by product or category; each band volume = customer count transitioning between purchase positions.
- **Specific UI:** "Noodle" curved/flowing bands rather than straight Sankey lines. Survey responses surface only as a filter — Lifetimely does not have a standalone survey analytics tab in public docs.
- **Filters:** Cohort, discount, channel, **post-purchase survey responses**.
- **Data shown:** Customer count per band; conversion rate from purchase N to N+1 — sliced by survey response.
- **Interactions:** Filter the diagram by survey-response value to see "what do customers who said 'Instagram' buy 2nd?".
- **Why it works:** Lifetimely's reviewers praise the noodle diagram as "intuitive" — survey-response slicing extends an already-loved viz with zero-party context.
- **Source:** `../competitors/lifetimely.md`.

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Survey is an ingested data source (Fairing / KnoCommerce) rather than a Peel-built widget — used as a slicing dimension across all dashboards (RFM, Magic Dash, retention, LTV).
- **Visualization:** No standalone survey screen — survey response is a dimension on top of every existing chart (Magic Dash widgets, RFM grid, retention curves).
- **Layout (prose):** Peel ingests Fairing/Kno responses and exposes them as a queryable dimension. There is no dedicated "Surveys" tab — the responses slice the existing 5×5 RFM grid, Magic Dash auto-generated charts, and cohort retention curves.
- **Specific UI:** Survey response surfaces as a filter pill alongside the standard date / channel / cohort filters; UI details for the survey-specific filter chip not separately documented.
- **Filters:** Date range, cohort, channel, segment, survey response.
- **Data shown:** Any Peel metric × survey response (LTV, repurchase rate, AOV).
- **Interactions:** Add survey response as a dimension to any Magic Dash question ("What is the LTV of customers coming from podcast vs paid search?").
- **Why it works:** Peel's "Magic Dash" Q&A model directly supports survey-as-a-lens questions because survey is one of the dimensions the LLM can pivot on.
- **Source:** `../competitors/peel-insights.md`.

### Rockerbox ([profile](../competitors/rockerbox.md))
- **Surface:** Survey is one of six podcast-attribution methods; not a standalone surface.
- **Visualization:** Not a separate viz — survey is one of six capture methods (promo codes, vanity URLs, **HDYHAU surveys**, show-notes URLs, branded paid-search reclassification, direct partners) feeding the standard channel-level attribution surface.
- **Layout (prose):** Six discrete capture methods are merged into a single podcast-channel rollup. Branded-search-to-podcast reclassification logic implies cross-channel touchpoint reassignment using survey + UTM joins.
- **Specific UI:** No dedicated survey screen in public docs. UI details not available — only feature description.
- **Filters:** Standard attribution filters (date, model, channel).
- **Data shown:** Channel-level attributed revenue with HDYHAU survey as one of six contributing signals.
- **Interactions:** Six-method capture is invisible to the user — they see consolidated podcast attribution.
- **Why it works:** Rockerbox's "six-method podcast capture" is the canonical reference for offline/dark-funnel attribution; survey is one piece of a broader puzzle, not the headline.
- **Source:** `../competitors/rockerbox.md`.

## Visualization patterns observed (cross-cut)

- **Top-N line chart over time:** 2 competitors (Fairing's Time Series, Zigpoll's per-question auto-charts). Top-5 cap on Fairing prevents chart noise — explicit "Chart" button with a 5-series limit.
- **Cross-tab table (response × dimension):** 2 competitors (Fairing Comparison Analytics, Daasity's Attribution Deep Dive table). "No Response" rendered as a literal cell value in Fairing — non-trivial UX detail.
- **Side-by-side columnar attribution comparison (Survey vs UTM/Pixel/GA4):** 4 competitors (Fairing's Last Click and UTM Report, Polar's Platform/GA4/Polar Pixel columns, Daasity's Dynamic Attribution Method, Northbeam's Model Comparison Tool). Most common pattern in mid-market+ competitors and the closest analog to Nexstage's 6-source-badge thesis.
- **Three-bucket strip + big-number (NPS):** 1 competitor (Fairing) — Detractors / Passives / Promoters segmented count strip + single-number NPS score.
- **Sankey / "noodle" flow with survey-response filter:** 1 competitor (Lifetimely) — survey is a filter on a customer-journey Sankey, not its axis.
- **Auto-generated per-question chart with format toggle:** 1 competitor (Zigpoll) — chart-format icon in bottom-right of each card switches between bar / pie / list per question.
- **AI summary card (LLM-classified themes / weekly digest):** 3 competitors (Zigpoll Z-GPT card, Fairing AI Insights weekly email, SegmentStream LLM TECH 3). Refresh cadences vary (weekly Mon-AM email for Fairing; weekly in-app for Zigpoll; on-the-fly for SegmentStream).
- **Survey response as a filter dimension (no standalone screen):** 3 competitors (Peel Insights, Lifetimely, Rockerbox) — treats survey as a slicing key across other dashboards rather than a destination surface.
- **Configuration-only with no analytics destination shown:** 1 competitor (ThoughtMetric — survey config exists; response viewer UI not observable).

Color use is mostly absent from public docs (none of the competitors document explicit hex tokens for survey UI). Recurring conventions: pause/live status pills on questions; status switches (live / paused / draft); count + percent dual rendering on response options.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Ease of setup and rapid time-to-insight**
- "Really easy to set up, and has made it really easy to know which influencers and creators are driving sales." — Vaer Watches, Shopify App Store, April 10, 2026 (`../competitors/fairing.md`)
- "Easy to launch, integrates with every system…one of the most scalable ways to get customer insights." — SURI, Shopify App Store, April 10, 2026 (`../competitors/fairing.md`)
- "It's so easy to edit questions and launch new ones. No complaints!" — wool&, Shopify App Store, April 13, 2026 (`../competitors/fairing.md`)
- "super easy to setup, but the number of customers who fill the forms is high enough" for meaningful decisions; "support is fantastic." — Pipa Skin Care, Shopify App Store review, Feb 11, 2026 (`../competitors/zigpoll.md`)

**Theme: Filling the attribution-blind-spot for hard-to-track channels**
- "Attribution is extremely hard to pinpoint... Zigpoll can integrate with pretty much any platform" with "customer service is unparalleled" and 24-hour response times. — Shopify App Store review, Feb 26, 2026 (`../competitors/zigpoll.md`)
- "adds a layer of attribution to my orders that I was struggling to understand" — The Plants Project, Shopify App Store review, Jan 13, 2026 (`../competitors/zigpoll.md`)
- "TM has really helped us understand what's working and what's not; trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time." — WIDI CARE, Shopify App Store, December 2024 (`../competitors/thoughtmetric.md`)
- "provides an easy way for our customers online to tell us how" they found the business; "Nothing - no bad things to report here." — Marco P., Owner, Health/Wellness/Fitness, Capterra, Jan 3, 2024 (`../competitors/zigpoll.md`)

**Theme: Survey-as-data-source rather than survey-as-feature**
- "Fairing is really simple to get set up and their team is great to work with. Don't take the ease of use to mean the product is not sophisticated, it is and getting the data into our Looker instance has been easy too" — ThirdLove, Shopify App Store, November 15, 2024 (`../competitors/fairing.md`)
- "Great overall view of attribution with post purchase survey. Support has been super helpful and responsive. And affordable compared to other attribution software we have looked at." — Bloomers Intimates, Shopify App Store, March 2024 (`../competitors/thoughtmetric.md`)
- "Fairing has been a game-changer for our post-purchase surveys, giving us insights that truly help us understand our customers on a deeper level." — Liquid Death, Shopify App Store, November 14, 2024 (`../competitors/fairing.md`)

**Theme: Side-by-side reconciliation against pixel data**
- Marketing case study quoted in `../competitors/fairing.md`: "while their pixel data showed a positive ROAS of 2.3x for their podcast campaign, survey data revealed an additional 31% increase in conversions that weren't captured by tracking technology, allowing them to increase their podcast budget by 40%."
- "you can see the entire pathway: what the customer clicked on or searched to find your site" — Jones (US), Shopify App Store review, Feb 26, 2026 (`../competitors/zigpoll.md`)

## What users hate about this feature

**Theme: AI / LLM classification quality is unreliable**
- "The AI is extremely inaccurate. So much that it's nearly useless." — Redmond Life (US), Shopify App Store 1-star review, Apr 14, 2026 (`../competitors/zigpoll.md`)
- "AI-generated summaries as unreliable and 'junk'" — paraphrased complaint pattern surfaced in Shopify App Store reviews, April 2026 (`../competitors/zigpoll.md`)

**Theme: Reporting depth is shallow / CSV-export-only**
- "reporting features could be more advanced for comprehensive data analysis" — G2 reviewer summary, 2026 (`../competitors/zigpoll.md`)
- "Analytics could improve with better segmentation and trend tracking, and integrations could be more seamless with tools like CRM systems" — G2 reviewer summary, 2026 (`../competitors/zigpoll.md`)

**Theme: Pricing volatility / paywalled warehouse access**
- "Beware of sudden price changes. They told us we must pay 347% more this year and 1,381% more in year 2." — Orlando Informer, Shopify App Store, September 20, 2024 (`../competitors/fairing.md`)
- BigQuery / API access is a $299/mo add-on on top of plan price (`../competitors/fairing.md`).
- Branching/presentation logic gated behind paid plans (`../competitors/zigpoll.md`).

**Theme: Survey lives separately from the rest of the analytics suite**
- "Lack of native ad-spend ingestion — has to be paired with Triple Whale / Northbeam / a warehouse to be useful for ROAS-style decisions." — recurring complaint theme on Fairing review aggregators (`../competitors/fairing.md`).
- "Self-reported attribution only. Zigpoll asks customers 'How did you hear about us?' — does not pull or model ad-platform spend, so it cannot produce ROAS or blended attribution." — `../competitors/zigpoll.md`.

## Anti-patterns observed

- **Conflicting price lists between marketing site and Shopify App Store:** Zigpoll lists $29 / $97 / $194 on `zigpoll.com/pricing` but $39 / $129 / $259 on the Shopify App Store. No public explanation. Confuses prospects (`../competitors/zigpoll.md`).
- **Email-only AI digest with weekly cadence + 25-response floor:** Fairing's AI Insights ships as a Monday-morning email, not an in-app feed. Zigpoll's Z-GPT requires ~25 responses to populate and refreshes weekly. The combination of small samples and slow refresh is the failure mode behind the "AI is junk" 1-star reviews (`../competitors/zigpoll.md`, `../competitors/fairing.md`).
- **Survey trapped behind a paid tier of an analytics suite:** Triple Whale gates "advanced post-purchase survey" behind Starter ($179-$299/mo) — the free tier only gets a single question. Customers who want to A/B questions or rotate copy must upgrade (`../competitors/triple-whale.md`).
- **Configuration screen with no documented response-viewer UI:** ThoughtMetric exposes the survey-config screen but the response-viewer layout is "not observable from public sources" — UI details not available (`../competitors/thoughtmetric.md`). Treat as opacity, not a model to copy.
- **Free-text "Other" responses left as a black box:** Most tools dump free-text into a column. Fairing's "Bulk Recategorize Responses" tooling and SegmentStream's LLM TECH 3 are the two acknowledged answers; without one of these the "Other" bucket eats the long tail of word-of-mouth signal (`../competitors/fairing.md`, `../competitors/segmentstream.md`).
- **Confidence-chip UX hint that may not exist publicly:** The "AI classification confidence chip on responses" referenced as a research target was **not directly observed in Fairing's public docs** — recategorization tooling is documented but the docs page body for it is gated. Per `../competitors/fairing.md`: "DO NOT FABRICATE."

## Open questions / data gaps

- **AI confidence-chip UX on responses:** Not publicly verifiable for Fairing; either behind login or recently unannounced. Same gap exists for Zigpoll Z-GPT classification — no public screenshot of a per-response confidence indicator. Re-research after a demo / paid trial.
- **Question-rotation / throttle algorithm:** Fairing markets a "Question Stream" that "persists until logic fulfills" but the actual rotation algorithm (round-robin? priority-weighted? ML-driven?) is not documented publicly. Same opacity for Zigpoll page-rule targeting and Triple Whale advanced surveys.
- **Free-text classification model details:** SegmentStream calls out LLM classification as TECH 3 but does not publish model name, accuracy benchmarks, or confidence-threshold defaults. Fairing's Bulk Recategorize is documented as a tool but not as an algorithm.
- **Side-by-side survey-vs-pixel surface in Fairing's own UI:** Marketing copy describes "Last Click and UTM Report (comparison against UTM data)" and "Multi-Tier Attribution" but no UI screenshot exists in public sources — only the analog implementation in Daasity's "Attribution Deep Dive" KB article confirms the side-by-side pattern (`../competitors/fairing.md`).
- **Response-viewer layout for ThoughtMetric and Triple Whale:** Both expose survey configuration but neither has documented its response-viewer UI publicly.
- **Polar's survey treatment:** Polar integrates Fairing natively but does not appear to expose a dedicated survey lens in its side-by-side attribution comparison. Whether "Survey" shows up as a 4th column or only as a filter is unclear from public docs (`../competitors/polar-analytics.md`).
- **Klaviyo / Meta CAPI write-back format:** Fairing pushes responses to Klaviyo profile properties and Meta CAPI custom events but the field-naming convention and event schema are not enumerated publicly.

## Notes for Nexstage (observations only — NOT recommendations)

- **Survey is treated as a parallel attribution lens by 4/11 competitors profiled** (Fairing, Polar, Daasity, Northbeam) — the side-by-side columnar comparison of Survey vs UTM/Pixel/GA4 is the dominant mid-market pattern and the closest direct analog in the market to Nexstage's 6-source-badge thesis (Real / Store / Facebook / Google / GSC / GA4). Adding "Survey" as a 7th source lens is a recurring discussion in the profiles' "Notes for Nexstage" sections.
- **3/11 competitors treat survey-response as a filter dimension only** (Peel, Lifetimely, Rockerbox) — no dedicated "Surveys" tab; the response slices existing dashboards. This is a lower-build-cost pattern than building a full survey suite.
- **Question rotation / throttle is the differentiator between a survey *feature* and a survey *product*:** Fairing's "Question Stream" model — questions persist until logic fulfills, decoupled from each other, with Auto Advance and Randomize Response Options — is the most-praised UX detail and the wedge separating Fairing from a generic Typeform/Google-Forms hand-roll.
- **AI free-text classification has a real failure mode at small sample / slow refresh:** Zigpoll's Z-GPT (~25-response floor, weekly refresh) and Fairing's AI Insights (weekly Mon-AM email) both draw 1-star reviews citing inaccuracy. SegmentStream positions LLM classification as a numbered measurement technique (TECH 3) — a more honest framing. Anything Nexstage ships in this area should default to high-confidence-only display and document the threshold.
- **Verbatim response storage as a queryable dimension is Daasity's standout pattern:** "Survey-Based view exposes Survey Response (verbatim text), Survey-Based Channel, Survey-Based Vendor" as three explicit dimensions on every order. Most competitors expose only the canonicalised channel; Daasity keeps the raw response accessible.
- **Shopify metafield write-back is unique to Fairing:** writing responses back to native Shopify order metafields makes the data queryable in Shopify Analytics / ShopifyQL / Sidekick without leaving the Shopify admin. No other survey tool in the set does this.
- **Custom Attribution waterfall (rank-fallback) is Daasity-only:** brands rank methods (e.g. survey → discount-code → GA last-click) for canonical attribution. This is a richer alternative to a single attribution-model dropdown and an open lane for SMB tools.
- **Question Stream timing model is novel for Nexstage to consider** — decoupled questions that persist until logic fulfills, rather than a fixed N-question form. Per `../competitors/fairing.md`'s own "Notes for Nexstage."
- **Triple Whale free-tier 1-question survey is a fair acquisition wedge** — a single question in the free tier (Founders Dash) demonstrates value before any pixel install. Pricing parity with Zigpoll's free tier (100 responses) and Fairing's free tier (100 orders).
- **6/11 competitors offer no native survey product** (Polar, Daasity, Peel, Northbeam, Rockerbox, Lifetimely) and instead ingest Fairing/Kno responses via integrations. This is the dominant build-vs-buy pattern in the analytics-suite category — survey functionality is treated as upstream infrastructure rather than core product.
- **Branching / 2-step Response Clarification is universal** — Fairing's "If you saw us on social, which platform?" parent/child pattern is the canonical UX across Fairing, Zigpoll (paid plans only), and ThoughtMetric. Linear surveys ship by default; branching is a quality differentiator.
- **NPS reporting is bundled in Fairing's analytics tab** even though NPS is not strictly attribution — the same Time Series / Comparison machinery is reused with a 0-10 numeric question type. Suggests survey UX should be question-type-agnostic rather than HDYHAU-specific.
- **"AI confidence chip on responses" referenced as a Fairing research target is NOT publicly verifiable** — explicit warning in `../competitors/fairing.md`: "DO NOT FABRICATE." If Nexstage ships this UX, the referent should be SegmentStream's TECH 3 LLM classification or a self-built equivalent, not a copy of an unobserved Fairing chip.
