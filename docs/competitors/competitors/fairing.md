---
name: Fairing
url: https://fairing.co
tier: T3
positioning: Post-purchase attribution survey platform for Shopify Plus / mid-market DTC brands; replaces "How did you hear about us?" surveys hand-rolled in Typeform/Google Forms and supplements pixel/MTA tools (Triple Whale, Northbeam, Rockerbox).
target_market: DTC ecommerce, predominantly Shopify (Basic to Plus, Shopify Plus certified); also supports Salesforce Commerce Cloud and WooCommerce via Web SDK. Brand reach quoted at "3,000 DTC brands and 2,000 Shopify Plus brands."
pricing: Free up to 100 orders/mo; $15/mo for 101-200 orders; $49/mo for up to 500 orders; $149/mo up to 5K orders; Enterprise (custom) above 30K orders. $299/mo Data Sync add-on for BigQuery + API access.
integrations: Shopify, WooCommerce, Salesforce Commerce Cloud, Klaviyo, Meta CAPI, Google Analytics 4, Google Tag Manager, TikTok, Segment, Triple Whale, Northbeam (via Rockerbox/Measured), Rockerbox, Measured, Polar Analytics, Daasity, Disco, Elevar, Podscribe, Recharge, Shopify Flow, Workmagic, Saras Pulse, Alloy, Klar, Funnel, Source Medium, Wonderment, Hazel, Google Sheets, BigQuery, Snowflake.
data_freshness: Near real-time for response capture; analytics dashboards "automatically refresh every 30 minutes" with last-refresh timestamp shown below the aggregation controls. Weekly AI Insights email Monday mornings.
mobile_app: No native iOS/Android app observed; web-responsive admin only.
researched_on: 2026-04-28
sources:
  - https://fairing.co
  - https://fairing.co/pricing
  - https://fairing.co/products/attribution-surveys
  - https://fairing.co/product/
  - https://fairing.co/blog/fairing-data-now-lives-in-shopify-analytics
  - https://fairing.co/blog/attribution-surveys-measure-what-dashboards-miss
  - https://fairing.co/blog/the-complete-guide-to-attribution-surveys
  - https://fairing.co/blog/the-rise-of-pixel-based-attribution-in-podcast-advertising
  - https://fairing.co/blog/what-is-a-fairing
  - https://docs.fairing.co/docs
  - https://docs.fairing.co/docs/shopify
  - https://docs.fairing.co/docs/shopify-analytics
  - https://docs.fairing.co/docs/question-stream
  - https://docs.fairing.co/docs/time-series-analytics
  - https://docs.fairing.co/docs/comparison-analytics
  - https://docs.fairing.co/docs/nps-reporting
  - https://docs.fairing.co/docs/ai-insights
  - https://docs.fairing.co/docs/integrations-catalog/
  - https://apps.shopify.com/fairing
  - https://apps.shopify.com/fairing/reviews
  - https://podscribe.com/blog/announcing-fairing-integration
  - https://kb.triplewhale.com/en/articles/6377642-fairing-integration
  - https://help.daasity.com/visualize/dashboards/standard-dashboards/acquisition-marketing-dashboards/attribution-deep-dive/marketing-attribution/survey-based-attribution
  - https://www.attnagency.com/blog/fairing-shopify-review
  - https://analyzify.com/shopify-apps/fairing-post-purchase-surveys
  - https://www.elumynt.com/podcast/matt-bahr-founder-ceo-of-fairing-discusses-ecommerce-attribution-and-post-purchase-surveys
  - https://www.crunchbase.com/organization/enquirelabs
---

## Positioning

Fairing (formerly Enquire Labs, founded 2018-2020 in New York, CEO Matt Bahr) is a post-purchase survey platform built specifically for marketing measurement and attribution. The headline tagline is "In-moment attribution surveys, purpose-built to improve your marketing measurement." The product is positioned as a complement — not a replacement — to pixel-based attribution: their blog argues "While pixels capture clicks, human memory captures intent, emotion, and influence" and that surveys "illuminate dark funnel moments by asking customers to reconstruct their journey, revealing the invisible touchpoints that influenced the purchase decision." Fairing explicitly targets "high-growth ecommerce brands" — co-founder Matt Bahr stated the company doesn't price for "low-growth merchants" — with the heaviest concentration on Shopify Plus.

Within Nexstage's competitive map Fairing is upstream of analytics suites: brands run Fairing alongside Triple Whale, Northbeam, Polar, or Daasity, and pipe survey-based attribution back into those dashboards via native integrations.

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Free | $0/mo | All features included; 0-100 orders/mo; "No surprises"; "No Credit Card Required" | Crossing 100 orders/mo |
| $15 plan | $15/mo | All features; 101-200 orders/mo; 14-day trial | Crossing 200 orders/mo |
| $49 plan | $49/mo | All features; up to 500 orders/mo; 14-day trial; unlimited questions, responses, translations | Crossing 500 orders/mo |
| $149 plan | $149/mo | All features; up to 5K orders/mo; 14-day trial | Crossing 5K orders/mo |
| Enterprise | Custom | All features; 30K+ orders/mo; Premium support (live chat, email, dedicated Slack, dedicated CSM, QBRs); option to remove Fairing branding | Multi-store, white-label, BI sync needs |
| Data Sync add-on | $299/mo | BigQuery integration + API access (sits on top of any plan) | Need to pipe responses into a warehouse / Looker / Hex |

Pricing is fully public on the marketing site. The pricing axis is monthly Shopify order volume (transactions), not seats or stores. Multiple reviewers report aggressive year-over-year increases for legacy customers (see "What users hate" below).

## Integrations

Per Fairing's integrations catalog there are **28 integrations** in three buckets:

- **Native (direct):** Alloy, Daasity, Disco, Elevar, Google Analytics 4, Google Tag Manager, Klaviyo, Meta CAPI, Podscribe, Polar Analytics, Recharge, Rockerbox, Saras Pulse, Segment, Shopify Flow, Triple Whale, Workmagic.
- **API:** Funnel, Google Sheets Extension, Klar, Measured, Source Medium, TikTok, Wonderment.
- **Other:** Hazel.

**Sources (data Fairing pulls from):** Shopify is the primary order/customer source; the Web SDK ingests from any storefront (Salesforce Commerce Cloud, WooCommerce, headless React/Next.js via GTM). Recharge is a survey-display source.

**Destinations (data Fairing writes to):** Klaviyo (response data appended to customer profiles), Meta CAPI ("Send custom events to Meta via Conversions API (CAPI) when a Fairing response is received"), Shopify metafields on order objects, Shopify Analytics, BigQuery / Snowflake (via $299 Data Sync), Google Sheets, Triple Whale, Northbeam (via Rockerbox/Measured), Daasity, Polar Analytics, Podscribe.

**Coverage gaps relative to Nexstage:** No native Google Search Console; no native ad-spend ingestion (Meta Ads / Google Ads spend, impressions, CPC); no ad-creative metadata; no first-party order-level COGS — Fairing is exclusively a survey-attribution layer, not a full analytics suite.

## Product surfaces (their app's information architecture)

Compiled from docs.fairing.co navigation and the Question Stream / Analytics & Insights / Integrations sections:

- **Question Stream** — survey builder; "ask multiple questions with programmable timing & context"; questions persist across customer activity "until they fulfill the logic."
- **Question Templates** — pre-built starting points across attribution, NPS, demographics.
- **Targeting Rules** — control display by customer segment (new vs returning, location, purchase history, frequency).
- **Response Clarification (2-step)** — branching follow-ups under a parent answer ("If you saw us on social, which platform?").
- **Live Feed** — real-time response monitoring stream.
- **Responses** — tabular response management with export and recategorization.
- **Recategorize Responses** — "bulk recategorization" of free-text "Other" responses into canonical buckets.
- **Customer View** — individual respondent profile.
- **Analytics — Time Series** — line chart of top-5 responses over time, day/week/month aggregation.
- **Analytics — Comparison** — side-by-side response cuts (new vs returning customer; Q1 vs Q2 cross-tab).
- **Analytics — NPS Reporting** — Detractors / Passives / Promoters split + NPS score over time.
- **Analytics — Discount Code report** — promo code attribution.
- **Analytics — Lifetime Value (LTV)** — LTV by survey response source.
- **Analytics — Last Click and UTM Report** — survey response vs UTM cross-comparison.
- **Multi-Tier Attribution** — granular source breakdown with parent / child source hierarchy.
- **AI Insights** — weekly Monday-morning email summary, "examines over 40 data points and highlights meaningful changes."
- **Data Export** — CSV / API.
- **Data Sync** — warehouse pipe (BigQuery, Snowflake) — paid add-on.
- **Integrations** — 28-integration catalog.
- **Settings / Team** — user account management, accessibility statement.

T3 IA inventory of roughly 18-20 distinct surfaces, but most are sub-tabs of three primary nav sections: Question Stream, Analytics, Integrations.

## Data they expose

### Source: Shopify
- **Pulled:** orders, line items, customers, products, billing/shipping country, customer type (new vs returning), order date, discount codes, transaction value.
- **Computed:** response rate (responses / views), per-question response counts, percent of total, LTV by response cohort, NPS score, multi-tier attribution rollup.
- **Attribution windows:** survey is captured at moment of purchase, so attribution is a single in-moment self-report rather than a click/view window. Survey responses are also written back as **metafield key-value pairs on Shopify order objects** so users can "Query survey data inside Shopify Analytics, alongside native Shopify dimensions like product type, billing country, and more." "Historical data is backfilled when you enable the integration, so you're not starting from zero."

### Source: Web SDK (non-Shopify storefronts)
- **Pulled:** survey responses, transaction value, traffic UTMs.
- **Computed:** same response/comparison/LTV reports.

### Source: Meta Ads (via Meta CAPI integration)
- **Pulled:** none (Fairing does not ingest spend/impressions/clicks).
- **Pushed:** custom Fairing response events sent to Meta CAPI for audience segmentation.

### Source: Google Ads
- Not directly integrated as a data source. UTMs from Google Ads land via Shopify order context only.

### Source: Klaviyo
- **Pushed:** "Sync your Fairing response data to Klaviyo customer profiles automatically." Used as a destination for segmentation.

### Source: Triple Whale / Northbeam / Rockerbox / Polar / Daasity
- All are **destinations** — Fairing pushes attribution responses into these dashboards rather than pulling pixel/MTA data back. Daasity exposes a "Survey-Based Attribution" dashboard that ingests Fairing data and renders the "Attribution Deep Dive" view alongside last-click and MTA.

### Source: Podscribe
- Bidirectional integration — "you can now view pixel, promo, and post-purchase survey results all in one place" inside Podscribe (not Fairing).

## Key UI patterns observed

### Question Stream (survey builder)
- **Path/location:** Top-level sidebar entry "Question Stream."
- **Layout (prose):** From docs only — survey list view with status pills (Live / Paused / Draft), each question card shows the prompt, response count, and a pause/live toggle switch. Inside an individual question, the editor exposes a prompt field, response options list (single-select / multi-select / NPS / open text / numeric / 6 question types per Analyzify), targeting rules panel, follow-up clarification panel, and a preview pane that renders the live post-purchase widget.
- **UI elements (concrete):** "Preview functionality, pause/live toggle switches, analytics tabs, response tables, export capabilities, and a chat support widget" (per docs). "Auto Advance" automatically progresses customers through questions; "Randomize Response Options" shuffles answer order to mitigate position bias.
- **Interactions:** Templates dropdown to clone a starting question; targeting rules support new-vs-returning, location, purchase history, frequency; 2-step clarification adds a child question keyed to a specific parent answer; multi-language translations applied per question.
- **Metrics shown:** Live response count, response rate, status.
- **Source/screenshot:** No public screenshots; description from docs.fairing.co/docs/question-stream.

### Time Series Analytics
- **Path/location:** Analytics tab > Time Series toggle.
- **Layout (prose):** Single line chart showing "your top 5 responses charted over time." X-axis is order date; Y-axis is response percentage by default, switchable to absolute response count. Aggregation toggle for Day / Week / Month sits above the chart. Below the aggregation controls is a last-refresh timestamp ("automatically refreshes every 30 minutes").
- **UI elements (concrete):** Tooltips on hover that "summarize the data shown for that aggregation bucket." Response selector list to the right of the chart — to add a new response to the chart, "unselect at least one response from the already charted responses, select your desired response and click the 'Chart' button" (capped at 5 simultaneous series).
- **Interactions:** Date-range picker, aggregation toggle, response chart-toggle (Chart button), percent-vs-count switch.
- **Metrics shown:** Response percentage over time, response count over time, top 5 response series.
- **Source/screenshot:** docs.fairing.co/docs/time-series-analytics.

### Comparison Analytics
- **Path/location:** Analytics tab > "Add comparison" control.
- **Layout (prose):** Cross-tab table. Two comparison types: (1) Customer Type — splits a question's responses into new vs returning columns; (2) Question + Response — pivots one question's responses against a second single-response question.
- **UI elements (concrete):** "The table is always sorted in descending order by Response Count." Rows that didn't answer the second question render as a literal "No Response" value: "If a customer did not response to the second question, the Comparison Value will show 'No Response.'"
- **Interactions:** Add Comparison dropdown, type picker, second-question picker, sort by count.
- **Metrics shown:** Response count by intersection, percent.
- **Source/screenshot:** docs.fairing.co/docs/comparison-analytics.

### NPS Reporting
- **Path/location:** Analytics tab > NPS view (only appears when an NPS-typed question exists).
- **Layout (prose):** Top: count + percent breakdown of "Detractors (0-6), Passives (7-8) and Promoters (9-10)" with the NPS score = "% Promoters - % Detractors" displayed as a single big number. Below: standard survey metrics row (Responses, Views, Response Rate). Switching to Time Series renders "your NPS score charted over time" with date-range picker and Day / Week / Month aggregation.
- **UI elements (concrete):** Three-bucket count strip with color-coded segmentation (red / yellow / green is the convention but not confirmed); single big-number NPS; tooltips on Time Series points that summarize the bucket.
- **Interactions:** Date-range picker, aggregation toggle, score-vs-distribution switch.
- **Metrics shown:** % Detractors, % Passives, % Promoters, NPS score, Responses, Views, Response Rate.
- **Source/screenshot:** docs.fairing.co/docs/nps-reporting.

### Lifetime Value (LTV) report
- **Path/location:** Analytics tab > Lifetime Value.
- **Layout (prose):** UI details not available — only feature description seen on marketing page and docs nav. Marketed as "Lifetime Value (LTV) Analytics" that compares LTV across response cohorts (i.e. "Instagram" customers vs "Friend" customers vs "Podcast" customers). Implied table: rows = response value (channel/source), columns = cohort LTV / order count / revenue.
- **UI elements (concrete):** Not observed in public sources.
- **Interactions:** Not observed.
- **Metrics shown:** LTV by source, cohort orders, cohort revenue (inferred from product copy).
- **Source/screenshot:** docs.fairing.co/docs nav + fairing.co/pricing feature list. UI details not available.

### Attribution / "Last Click and UTM Report" (a.k.a. Attribution Deep Dive equivalent)
- **Path/location:** Analytics tab > Attribution.
- **Layout (prose):** Marketing copy describes "Last Click and UTM Report (comparison against UTM data)" and "Multi-Tier Attribution (granular source breakdown)." Implied side-by-side: a table where each survey response source has a parallel UTM-attributed column for the same orders, exposing the gap between self-reported and pixel-reported attribution. The Daasity "Attribution Deep Dive" knowledge-base article confirms the side-by-side pattern: "Survey-Based attribution will assign an order to the channel and vendor based on customer responses to survey questions like 'How did you hear about us?'" rendered alongside last-click attribution.
- **UI elements (concrete):** Not observed in Fairing's own UI screenshots; the side-by-side analog is documented in the Daasity dashboard that ingests Fairing data.
- **Interactions:** Not observed in detail.
- **Metrics shown:** Survey response source, UTM-derived source, order count, revenue, multi-tier (parent / child) source rollup.
- **Source/screenshot:** fairing.co/products/attribution-surveys + help.daasity.com Survey-Based Attribution KB article. UI details on Fairing's own screen not available — only feature description seen on marketing page.

### Responses (response management table)
- **Path/location:** Top-level sidebar entry "Responses."
- **Layout (prose):** UI details not available from public docs — the docs page exists but its body is gated. Description is limited to "response tables, export capabilities" per the docs nav. Inferred: table of individual responses with question, answer, customer, order, timestamp; bulk actions for recategorization.
- **UI elements (concrete):** Not directly observed.
- **Interactions:** Bulk recategorization ("organizing and analyzing 'Other' responses into actionable insights"), CSV export.
- **Metrics shown:** Per-response question, answer, customer, order ID, timestamp.
- **Source/screenshot:** docs.fairing.co/docs/responses (body not publicly readable). UI details not available.

### AI Insights
- **Path/location:** Side navigation entry "AI Insights."
- **Layout (prose):** Configuration screen — pick an attribution question (single or multi-response), then receive a weekly Monday-morning email digest. The email is "a concise summary of your week-over-week attribution trends" that "examines over 40 data points and highlights meaningful changes." In-app the AI Insights tab lets users "modify their selected question or manage their subscription."
- **UI elements (concrete):** Question picker dropdown, subscribe/unsubscribe toggle, list of past digests (inferred). The "AI classification confidence chip on responses" called out in the assignment was not directly observed in Fairing's public docs — recategorization tooling is documented but the docs page body for it is gated, and the confidence-chip UX is referenced only by third parties. Treat as unverified UI detail.
- **Interactions:** Pick question, subscribe, view emailed digest.
- **Metrics shown:** Channel performance fluctuations, emerging attribution sources, device behavior shifts across channels, week-over-week response comparisons.
- **Source/screenshot:** docs.fairing.co/docs/ai-insights.

### Shopify Analytics integration (an exposed surface, not a Fairing screen)
- **Path/location:** Shopify admin > Analytics, after enabling Fairing's Shopify Analytics integration with `write_orders` scope.
- **Layout (prose):** Fairing writes responses to Shopify order metafields, surfacing them as queryable dimensions in Shopify Analytics, ShopifyQL, and Shopify Sidekick (AI assistant). Use cases described: group orders by product type × survey response; pivot by billing country × response.
- **UI elements (concrete):** Native Shopify Analytics chart and table widgets — Fairing data appears as additional dimension chips.
- **Interactions:** Filter, group, pivot inside Shopify Analytics. ShopifyQL queries include Fairing metafields. Sidekick natural-language queries can reference the metafields.
- **Metrics shown:** All native Shopify metrics × Fairing response dimensions.
- **Source/screenshot:** fairing.co/blog/fairing-data-now-lives-in-shopify-analytics. No Fairing-side screenshot.

## What users love (verbatim quotes, attributed)

- "Really easy to set up, and has made it really easy to know which influencers and creators are driving sales." — Vaer Watches, Shopify App Store, April 10, 2026
- "Easy to launch, integrates with every system...one of the most scalable ways to get customer insights." — SURI, Shopify App Store, April 10, 2026
- "It's so easy to edit questions and launch new ones. No complaints!" — wool&, Shopify App Store, April 13, 2026
- "Fairing has been a game-changer for our post-purchase surveys, giving us insights that truly help us understand our customers on a deeper level. The setup process was seamless, and the data we're now collecting has been invaluable for refining our strategy..." — Liquid Death, Shopify App Store, November 14, 2024
- "Simple to use, but it has really sophisticated features if you want to dig below the surface." — Bedfolk, Shopify App Store, October 31, 2024
- "Fairing is really simple to get set up and their team is great to work with. Don't take the ease of use to mean the product is not sophisticated, it is and getting the data into our Looker instance has been easy too - though for quick analysis, I go straight to the dashboard" — ThirdLove, Shopify App Store, November 15, 2024

## What users hate (verbatim quotes, attributed)

Limited critical reviews available — the Shopify App Store rating is 5.0 out of 5 with 193 reviews (98% five-star, 1% four-star, 1% one-star), so most criticism is concentrated in a handful of low-ratings:

- "Beware of sudden price changes. They told us we must pay 347% more this year and 1,381% more in year 2." — Orlando Informer, Shopify App Store, September 20, 2024
- Third-party summary: "users have reported that they were told they must pay 347% more that year and 1,381% more in year 2, and experienced deactivation of data access shortly after being charged" — paraphrased in itsfundoingmarketing.com / Reputon recaps of Shopify reviews, 2025.

Common complaint themes recurring across review aggregators (Reputon, ecommercetech.io, Analyzify):
- Pricing increases for legacy customers without warning.
- Lack of native ad-spend ingestion — has to be paired with Triple Whale / Northbeam / a warehouse to be useful for ROAS-style decisions.
- BigQuery / API access is a $299/mo add-on on top of plan price.

## Unique strengths

- **In-moment, post-purchase capture is their entire wedge.** Surveys fire on the Shopify Thank You / Order Status pages with "survey completion rates upwards of 60%" claimed in their own blog and "40-80% response rates" claimed in marketing copy across third-party aggregators.
- **Question Stream timing model.** Their core differentiator is that questions are decoupled — they don't all fire at once. Questions persist "until they fulfill the logic" so different customers see different question subsets and rotation prevents survey fatigue.
- **Shopify Plus certified.** This matters for mid-market procurement and is a hard moat against scrappy competitors.
- **Shopify Analytics metafield write-back.** Fairing data writes to native Shopify order metafields, making it queryable in Shopify Analytics / ShopifyQL / Sidekick without leaving the Shopify admin. Few other survey tools do this.
- **Deep Klaviyo + Meta CAPI destinations.** Response data is automatically appended to Klaviyo profiles for segmentation, and CAPI events fire to Meta when a response is recorded — turning attribution into ad-targeting feedback loops.
- **Side-by-side survey-vs-pixel attribution narrative.** Marketing emphasizes pixels miss "the messy, human journey" and a quoted DTC case study: "while their pixel data showed a positive ROAS of 2.3x for their podcast campaign, survey data revealed an additional 31% increase in conversions that weren't captured by tracking technology, allowing them to increase their podcast budget by 40%."
- **Bulk Recategorization for "Other."** Most survey tools dump open-text into a black box; Fairing markets dedicated tooling for bucketing free-text into canonical sources.

## Unique weaknesses / common complaints

- **Not a full analytics suite.** No ad-spend ingestion, no GSC, no GA4 sessions ingestion (only push). Ad spend / ROAS analysis must be done in another tool.
- **Pricing volatility.** Multiple reports of mid-cycle and renewal-time price hikes "347% more" / "1,381% more" cited verbatim.
- **Warehouse access is paywalled.** $299/mo Data Sync add-on for BigQuery + API access on top of any base plan; raw API access not in the base product.
- **Mobile UX limited.** Surveys themselves render on the customer's mobile post-purchase screen, but the merchant admin is web-only.
- **Free / low tier feels promotional.** $15/mo plan only covers up to 200 orders/mo — anything above small-shop volume sits at $49 or $149.
- **Limited dashboard depth visible publicly.** Most analytics screens (Responses, LTV report, Multi-Tier Attribution, Recategorization) have docs pages that are either gated or thin; product evaluation requires booking a demo.
- **No GSC / paid ads pull.** Confirmed gap relative to Nexstage's 6-source thesis.

## Notes for Nexstage

- **Survey-vs-pixel side-by-side is a real, marketed pattern.** Their attribution screen positions self-reported source against UTM-attributed source on the same orders. This is a direct analog to Nexstage's 6-source-badge thesis where Real / Store / Facebook / Google / GSC / GA4 are shown as parallel lenses. Worth referencing when designing the Acquisition / Channel surfaces.
- **Question Stream timing model is novel for Nexstage to consider.** Decoupled questions that persist until logic fulfills, rather than a fixed N-question form, is a different mental model from typical onboarding flows — relevant if Nexstage ever ships an in-product survey or qualitative-data layer.
- **Shopify metafield write-back is a useful pattern.** Fairing's choice to push response data back into Shopify order metafields means their data is queryable in Shopify Analytics natively. Nexstage may want to consider similar write-back for derived metrics (e.g. predicted CAC, LTV cohort tag) so merchants can use them in their own Shopify reports.
- **AI confidence chip on responses (assignment hint) is not publicly verifiable.** The assignment notes a "distinctive UX" of an AI classification confidence chip. Fairing's docs reference Recategorize Responses and AI Insights but the body of the recategorization page is gated and the confidence-chip pattern is not visible in any indexed marketing page or third-party walkthrough. Either it is behind login or it is a recent unannounced beta. Flag for re-research after a demo.
- **Pricing complaints are reputational.** Multiple legacy customers cite mid-contract hikes. Nexstage should price-anchor and document a clear price-protection policy if competing on the same trust dimension.
- **No GSC, no native ad-spend.** Fairing concedes the entire "blended performance dashboard" surface to Triple Whale / Northbeam / Polar / Nexstage. Fairing is complementary, not competitive, for an analytics suite — but their attribution UX patterns (side-by-side, multi-tier source rollup, LTV-by-source) are direct reference material.
- **AI Insights = weekly email, not in-app feed.** Nexstage's product surfaces could lean harder into in-app anomaly detection rather than email digests; Fairing's email-only model is a low bar to clear.
- **Question template library is a teach-the-customer wedge.** Fairing ships 25+ pre-built question templates — analogous to Nexstage shipping pre-built attribution / channel-mapping templates would lower onboarding friction.
- **AI classification confidence chip — UI details not available; only feature description seen on marketing page. DO NOT FABRICATE.**
