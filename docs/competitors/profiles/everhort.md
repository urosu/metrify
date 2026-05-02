---
name: Everhort
url: https://everhort.com
tier: T3
positioning: Single-feature cohort LTV analytics for Shopify SMBs trying to measure whether retention tactics are actually moving repeat-purchase velocity.
target_market: Shopify SMB DTC brands with consumable / repeat-purchase products; performance marketers and growth teams.
pricing: Free plan ($0) listed on everhort.com/pricing; Shopify App Store lists tiered $0–$99/mo by customer count, plus custom Enterprise.
integrations: Shopify (native), CSV upload. "More integrations on the way."
data_freshness: daily ("New order data will automatically be imported into Everhort from your Shopify store each day.")
mobile_app: no (web only)
researched_on: 2026-04-28
sources:
  - https://everhort.com/
  - https://everhort.com/shopify
  - https://everhort.com/pricing
  - https://everhort.com/faq
  - https://apps.shopify.com/everhort
  - https://apps.shopify.com/everhort/reviews
  - https://help.everhort.com/
  - https://help.everhort.com/article/8-ltv-by-cohort-chart
  - https://help.everhort.com/article/9-stacked-cohort-area-chart
  - https://help.everhort.com/article/10-ltv-summary
  - https://help.everhort.com/article/13-cohort-retention
  - https://help.everhort.com/article/18-averages-chart
  - https://help.everhort.com/article/19-table-views
  - https://help.everhort.com/article/11-shopify-data-import
  - https://help.everhort.com/article/12-filters
  - https://help.everhort.com/article/15-customer-filters
  - https://help.everhort.com/article/17-order-filters
  - https://blog.everhort.com/2019/08/31/ditch-cohort-retention-charts-and-start-using-cohort-ltv-graphs/
---

## Positioning

Everhort sells a single, narrow feature surface — cohort-based LTV analytics — to Shopify merchants whose business depends on repeat purchases (consumables, subscriptions-adjacent DTC). Their homepage frames the value prop as a corrective to "vanity metrics": "Repeat customers are the **lifeblood** of your business. Take a true reading of their pulse." The product is positioned not as an all-in-one analytics suite but as a focused tool for answering three growth questions: how much value past acquisitions are still producing, what to spend on new acquisition, and whether repeat-customer acquisition is improving over time. Founded 2019 by a team with "20+ years of software development experience in finance and eCommerce" (FAQ); based in Chicago, IL.

## Pricing & tiers

Two pricing surfaces disagree:

**everhort.com/pricing** lists only:

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Free | $0/mo | "Automatic data import from your Shopify store, with more eCommerce platforms coming"; "All reports, including LTV Velocity, Stacked Activity, and Forecasted LTV with CAC targets"; "Customer and advanced order level filtering"; "Unlimited accounts for users"; "Unlimited reporting history" | n/a |

**Shopify App Store listing** (apps.shopify.com/everhort) shows tiered plans gated by total customer count:

| Tier | Price | Customer cap | Trial |
|---|---|---|---|
| Launch | Free | 1,000 | n/a |
| Grow | $49/mo | 10,000 | 30 days |
| Accelerate | $79/mo | 100,000 | 30 days |
| Scale | $99/mo | 250,000 | 30 days |
| Enterprise | Custom | 250,000+ | Contact for quote |

The mismatch suggests the marketing-site `/pricing` page may be stale or that the company is currently giving paid tiers away while building user base. No public note explains the gap.

## Integrations

- **Pulled from (sources):** Shopify (native; "the entire order history of your Shopify store" plus daily incrementals) and CSV upload. The KB explicitly only documents these two sources (`/category/4-data-import` lists three articles: Shopify Data Import, CSV File Format and Structure, CSV Column Assignment).
- **Push destinations:** None observed. Reports export to CSV only ("Every tabular report in Everhort can be downloaded to your computer as CSV file using the link underneath the table." — KB Table Views).
- **Coverage gaps relative to Nexstage scope:** No Meta Ads, no Google Ads, no GA4, no GSC, no WooCommerce, no Klaviyo, no TikTok. Homepage text "More integrations on the way" has been on the site since at least 2019 (blog era) without observable expansion.
- **Cost data:** Pulled via Shopify's per-product "Cost per Item" field for contribution-margin calculations; "If costs aren't available, it default[s] to 100% margin (net revenue)." (KB Shopify Data Import).

## Product surfaces (their app's information architecture)

Everhort's KB documents 6 reports plus filters and import config. Public sources do not show top-nav structure beyond report names, so the surfaces below are inferred from KB article titles in the **Reports** category (`/category/7-reports`):

- **Average LTV by Cohort Chart** — answers "how is cumulative customer LTV trending across cohorts of different acquisition months?"
- **Stacked Cohort Activity Chart** — answers "how much of this month's revenue (or returning-customer count) came from each historical cohort?"
- **Forecasted Average LTV** — answers "based on recent cohort trajectories, what 1/2/3-year LTV should I expect, and what is my CAC ceiling?"
- **Cohort Retention Chart** — answers "what percentage of each cohort comes back in month 2, 3, 4…?"
- **Averages Chart** — answers "how do AOV / items-per-basket / item-value evolve as cohorts age?"
- **Table Views** — every report has a paired tabular view with green/red heatmap-style cell shading and CSV export
- **Filters panel** — three filter axes (Customer, Order, Channel) that apply globally across all reports
- **Shopify connection / data import config** — one-field connection to Shopify, daily incremental pull
- **CSV import config** — column-mapping UI for users without Shopify

Total: ~6 chart screens + filters + import config. Consistent with a T3 single-feature tool.

## Data they expose

### Source: Shopify
- **Pulled:** Full historical order export plus daily incrementals; orders, line items, customers, customer tags, product collections, product types, product properties, discount codes, free-shipping flag, per-product unit cost ("Cost per Item" field).
- **Computed:** Cumulative LTV by cohort (using contribution margin when costs are present, otherwise net revenue), blended-average LTV across recent cohorts, AOV, items-per-basket, average item value, retention % by cohort age, forecasted LTV (linear regression of recent cohort trajectories), CAC target (LTV / 3 industry-standard ratio), payback period.
- **Attribution windows:** None — Everhort is purely first-party order data; there is no marketing attribution layer.

### Source: CSV
- **Pulled:** Mapped manually via column-assignment UI; minimum required fields are documented but not enumerated in publicly accessible KB.
- **Computed:** Same as Shopify path.

### Source: Meta Ads / Google Ads / GA4 / GSC
- Not supported. No public documentation of any ad-platform or analytics integration.

## Key UI patterns observed

### Average LTV by Cohort Chart
- **Path/location:** Reports section. Likely default landing report (top of KB "popular articles" list).
- **Layout (prose):** Single primary line chart. Y-axis is "average _cumulative_ lifetime value (LTV)" using contribution margin where COGS is available. X-axis is "the age of a cohort in months since they were acquired." Each line is one monthly cohort. Below the chart, a tabular view repeats the same data with green/red heatmap shading; CSV download link sits beneath the table.
- **UI elements (concrete):** Line color encodes cohort age — "Darker blue lines indicate older cohorts" and "Lighter blue lines represent newer cohorts." A "light red line displays a blended average of recent monthly cohorts" as a baseline. When filters are active, "a green line represents the blended unfiltered (baseline) average LTV" so users see filtered vs unfiltered side-by-side on the same chart.
- **Interactions:** Filter strip (Customer / Order / Channel) applies globally; CSV export underneath table; toggle to underlying tabular view.
- **Metrics shown:** Cumulative LTV per customer at each month-of-age (e.g., KB cites "$1,103 per customer after 7 months" for one cohort); blended-average LTV line; baseline LTV line when filtered.
- **Source/screenshot:** https://help.everhort.com/article/8-ltv-by-cohort-chart — UI details from KB prose, no screenshot fetched.

### Stacked Cohort Activity Chart
- **Path/location:** Reports section.
- **Layout (prose):** Stacked area chart. Y-axis is the chosen metric (revenue or returning-customer count). X-axis is calendar months. "The oldest cohort acquired in the selected time range will be the first colored band on the bottom, and then each subsequent monthly cohort is stacked on top one by one." Pre-period cohorts default to "a single gray band at the bottom."
- **UI elements (concrete):** Each monthly cohort gets its own band color in the stack. Toggle switch controls whether pre-period cohorts collapse into one gray band or expand into individual bands. Hover behavior reveals "the layer by layer details for that month" — i.e., per-cohort contribution at the hovered timestamp. Click-to-isolate: clicking a single band drills into that one cohort's trajectory in isolation.
- **Interactions:** Hover for layered tooltip; click-band to isolate single cohort; toggle pre-period bundling; tabular view + CSV download underneath.
- **Metrics shown:** Revenue or returning-customer count, decomposed by cohort.
- **Source/screenshot:** https://help.everhort.com/article/9-stacked-cohort-area-chart — KB prose only.

### Forecasted Average LTV (LTV Summary)
- **Path/location:** Reports section.
- **Layout (prose):** Two-column layout — bar chart on the left, metrics table on the right. Bars show projected average LTV at multiple horizons (e.g., "1, 2, and 3 years for a 12-month baseline"). The right-side table lists "estimated CAC (customer acquisition cost) targets along with corresponding payback periods for each of the forecasted LTV time periods."
- **UI elements (concrete):** When filters are applied the bars become side-by-side dual bars: "light green bars show baseline performance, while gray bars represent filtered cohort performance." This is direct visual A/B between filtered cohort and unfiltered baseline.
- **Interactions:** Forecast is computed by "linear regression of the blended average LTV of recent cohort performance" — i.e., fits "the best possible straight line" through cohort trajectories and extrapolates. CAC target uses fixed LTV/CAC = 3 ratio (KB example: "$108 two-year LTV forecast" → "$36 CAC target").
- **Metrics shown:** Forecasted LTV at 1Y/2Y/3Y; recommended CAC ceiling at each horizon; payback period.
- **Source/screenshot:** https://help.everhort.com/article/10-ltv-summary — KB prose only.

### Cohort Retention Chart
- **Path/location:** Reports section.
- **Layout (prose):** Chart type not explicitly named in KB (no confirmation it is heatmap, line, or bar). X-axis is "the age of each cohort in months since their first purchase" (excluding the acquisition month — i.e., starts at month 2). Y-axis defaults to "percentage of returning customers" out of 100%.
- **UI elements (concrete):** Toggle "in the upper right" switches Y-axis between percentage and absolute customer count. Filters apply globally and produce filtered-vs-baseline comparison.
- **Interactions:** %/absolute toggle; global filters; CSV export.
- **Metrics shown:** Repeat-purchase rate per cohort per month-of-age; absolute returning-customer count when toggled.
- **Source/screenshot:** https://help.everhort.com/article/13-cohort-retention — KB prose only. UI details on chart type not available — only feature description seen on KB page.

### Averages Chart
- **Path/location:** Reports section.
- **Layout (prose):** Line chart with one selectable metric at a time. X-axis is cohort age in months (leftmost point = acquisition period). Y-axis is the metric average.
- **UI elements (concrete):** Tab strip in the upper right switches between three metrics: AOV ("net revenue / number of orders"), Items Per Basket ("total line item quantity / number of orders"), Average Item Value ("net revenue / total line item quantity"). Tabular view + CSV download underneath.
- **Interactions:** Metric tab switcher; global filters.
- **Metrics shown:** AOV, IPB, AIV — each plotted across cohort age.
- **Source/screenshot:** https://help.everhort.com/article/18-averages-chart — KB prose only.

### Table Views (cross-cutting pattern)
- **Path/location:** Beneath every chart in every report.
- **Layout (prose):** Tabular companion to each chart. Cell-level color shading vs the blended average baseline.
- **UI elements (concrete):** Heatmap-style cell coloring: "Values that are the same or higher than the corresponding average are shaded green. Values that are less than the average are shaded red." Intensity maps to deviation distance — softer for near-average, stronger for far-from-average, "buckets based on whether they are within 1, 2, or 3+ absolute deviations of the mean." Cells representing "ongoing, incomplete periods display a dashed border" so users don't misread partial-month data.
- **Interactions:** CSV download link "underneath the table" on every report; sort behavior is not documented in KB.
- **Metrics shown:** Mirror the parent chart.
- **Source/screenshot:** https://help.everhort.com/article/19-table-views — KB prose only.

### Filters panel
- **Path/location:** Global; applies across all reports. Active filters surface in "report summary at the top of the screen."
- **Layout (prose):** Three filter type-groups: Customer, Order, Channel. Within-group filters combine with OR semantics; across-group filters combine with AND ("Filters of different types will be combined using logical **AND**.").
- **UI elements (concrete):**
  - **Customer filters:** Single documented filter — "Customers who are tagged" via dropdown of Shopify customer tags.
  - **Order filters:** Five — Product Collections, Product Name (type-ahead dropdown), Product Type, Product Properties (with four matchers: Equals / Does Not Equal / Is Set / Is Not Set), Discounts (any discount, free-shipping, specific code with exact or "Starts With" matching).
  - **Order-filter scope qualifier:** Every order filter additionally lets the user specify whether the purchase needs to occur "on any purchase," "their first purchase," or "a subsequent purchase." This first-vs-subsequent distinction is the closest thing in the product to a repeat-purchase-behavior pivot.
- **Interactions:** "Add Filter" button; active filters render as chips/text in report header; filter changes propagate live to every chart.
- **Metrics shown:** n/a (configuration UI).
- **Source/screenshot:** https://help.everhort.com/article/12-filters, /article/15-customer-filters, /article/17-order-filters.

### Shopify Data Import
- **Path/location:** Onboarding / settings.
- **Layout (prose):** "filling in one field" connects the store. Daily incremental pulls thereafter.
- **UI elements (concrete):** Not documented in public sources beyond "one field."
- **Interactions:** Reads Shopify "Cost per Item" field for COGS; falls back to 100% margin if absent.
- **Source/screenshot:** https://help.everhort.com/article/11-shopify-data-import — UI details not available; only feature description seen on KB page.

## What users love (verbatim quotes, attributed)

Limited reviews available — only 3 active and 1 archived review on the Shopify App Store, no reviews surfaced from G2, Capterra, TrustRadius, Trustpilot, Reddit, or Twitter despite targeted searches.

- "the best LTV cohort analysis app I have found on Shopify" — The Beard Club (United States), Shopify App Store review, September 8, 2020. Same quote referenced on the Everhort homepage as a marketing testimonial attributed to "D. Morse, Director of Performance Marketing, The Beard Club."
- "We've used this tool for several months to track cohort retention rates" and to identify retention drivers; described as "an excellent visualiser of retention." — LUXE Fitness (New Zealand), Shopify App Store review, June 15, 2020.
- "really good LTV analytics capabilities combined with best-in-class, highly responsive support" — Mighty Petz (United States), Shopify App Store review, December 15, 2023. Reviewer specifically recommends it for stores with consumable products requiring repeat purchases.
- "when they made a suggestion to improve the LTV calculation method, it was implemented within 24 hours" — paraphrased reviewer feedback surfaced via search; consistent with the LUXE Fitness and Auriga reviews praising developer responsiveness.
- "strong performance delivery and rapid developer responsiveness to feature suggestions" — Auriga (Spain), Shopify App Store archived review, July 20, 2022.

## What users hate (verbatim quotes, attributed)

Severely limited critical feedback — the app has 100% 5-star ratings across only 3 reviews. Only one mildly critical signal surfaced:

- Notes "the UI could improve" — Auriga (Spain), Shopify App Store archived review, July 20, 2022. The same review otherwise praises performance and responsiveness.

No other critical reviews observable in public sources. No Reddit, G2, Capterra, or Trustpilot threads surfaced for "Everhort." This is consistent with a low-install-volume T3 product whose user base is too small to generate either a robust positive corpus or organic complaints.

## Unique strengths

- **Cohort-comparison-against-baseline pattern is built into every chart.** When a filter is applied, charts overlay a baseline series in a distinct color (e.g., LTV chart: green baseline line + light-red blended-average + cohort-colored series; LTV forecast: light-green baseline bars + gray filtered bars). This is a stronger filter-vs-baseline visual than most all-in-one suites.
- **Dashed-border for incomplete periods in tabular views** — small but unusual UX detail that prevents users from mistaking partial-month data for complete-month data.
- **Stacked cohort activity chart with click-to-isolate band** — uncommon outside dedicated retention tools; users can click a single cohort band and pivot the chart into that cohort's individual trajectory.
- **Forecasted LTV uses transparent linear regression** — KB explicitly states "linear regression of the blended average LTV of recent cohort performance" and applies a fixed LTV/CAC = 3 multiplier. No black-box ML.
- **Per-filter "first purchase" / "subsequent purchase" / "any purchase" qualifier** — the closest thing in the product to a repeat-purchase analysis pivot; lets users separate acquisition-driven from retention-driven cohort segments.

## Unique weaknesses / common complaints

- **Single source: Shopify.** No Woo, no marketing platforms, no GA4, no GSC. Marketing copy "more integrations on the way" has been live since 2019 with no observable expansion.
- **Tiny review corpus.** 3 active Shopify App Store reviews after ~7 years live → low adoption signal.
- **No mobile app.** Web only.
- **No real-time / hourly data.** Daily incrementals only.
- **One customer filter only** ("Customers who are tagged"). No filter on customer geography, signup channel, lifetime spend bucket, etc. — limits cohort segmentation depth.
- **No marketing attribution layer.** No way to tie cohort LTV back to acquisition source; users have to manually correlate cohort start months against external marketing spend reports.
- **Pricing inconsistency** between everhort.com/pricing (free only) and Shopify App Store ($0–$99 tiered) — unclear which is current.
- **UI was flagged as needing improvement** in 2022 (Auriga review); no public evidence of major UI overhaul since.

## Notes for Nexstage

- **No "time-to-second-order" flagship metric observed.** The brief specified time-to-second-order as a flagship metric, but Everhort's public sources do not name or show such a metric. The closest analog is the Cohort Retention Chart (X = months-since-acquisition starting at month 2, Y = % of cohort that returned), which lets a user *read off* "what % returned in month 2" but does not surface days-to-second-order as a single number anywhere. Worth flagging for the brief author — the public framing is "repeat purchase velocity" expressed via cumulative LTV slope, not a discrete time-to-second-order KPI.
- **Cohorts are visualized as line charts and stacked area charts, not heatmaps.** The Average LTV by Cohort chart is a multi-line chart with cohort-age on X and cumulative LTV on Y (one line per acquisition month, blue gradient by recency). Stacked Cohort Activity is a stacked area chart on calendar X. The Cohort Retention Chart's chart type is not named in KB. Heatmap-style coloring exists only in the *tabular companion views* (green/red ±1/2/3 stdev shading) — not in the primary chart visualizations. This is the opposite of the Peel/Triple Whale pattern where retention heatmaps are the headline visual.
- **"Filtered vs baseline overlay" is the core analytical UX motif.** Every report renders the unfiltered baseline alongside the filtered cohort when a filter is active. Direct analog to a "compare segment to baseline" pattern Nexstage may want for cohort/segment views.
- **No source-attribution debate exists in this product.** Because Everhort only ingests order data, there is no GA4/Pixel/Platform reconciliation surface — irrelevant to Nexstage's 6-source-badge thesis. Useful as a negative reference: it shows what a "first-party-only" cohort tool looks like when you strip out all marketing attribution.
- **COGS comes from Shopify "Cost per Item" with silent fallback to 100% margin.** No in-product COGS editor observed. Worth comparing against Lifetimely / Triple Whale, which offer in-product COGS overrides.
- **Forecasting is transparent linear regression with a fixed LTV/CAC = 3 multiplier.** Nexstage's cost-config / forecasting work should note that even this minimal tool ships forecasted LTV; the bar isn't high.
- **CSV-import path exists alongside Shopify** — gives non-Shopify users a back door. Could be relevant to Nexstage's Woo-first positioning if we want a similar "bring your own orders" escape hatch for offline merchants.
- **Filter granularity gap.** Order filters are rich (5 dimensions × 4 matchers × first/subsequent/any qualifier) but customer filters are limited to a single "tagged" dropdown. Asymmetry to note.
- **Blocker: no public dashboard screenshots.** All UI details above are reconstructed from KB prose, not from screenshots. The Shopify App Store listing and homepage marketing imagery were not retrievable as PNGs in this research pass.
