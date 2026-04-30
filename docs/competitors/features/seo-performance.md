---
name: SEO performance
slug: seo-performance
purpose: Show merchants which organic search queries and landing pages drive clicks, sessions, and ultimately revenue — joining GSC search-side data to store-side orders.
nexstage_pages: seo
researched_on: 2026-04-28
competitors_covered: looker-studio, ga4, conjura, glew, lebesgue, putler, triple-whale, polar-analytics, storehero, shopify-native, woocommerce-native, beprofit, varos, atria, conjura
sources:
  - ../competitors/looker-studio.md
  - ../competitors/ga4.md
  - ../competitors/conjura.md
  - ../competitors/glew.md
  - ../competitors/lebesgue.md
  - ../competitors/putler.md
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/storehero.md
  - ../competitors/shopify-native.md
  - ../competitors/woocommerce-native.md
  - ../competitors/beprofit.md
  - ../competitors/varos.md
  - ../competitors/atria.md
  - https://support.google.com/analytics/answer/10737381
  - https://support.google.com/analytics/answer/9744165
  - https://www.putler.com/web-analytics/
  - https://www.glew.io/integrations
---

## What is this feature

SEO performance answers the merchant question **"Which queries and pages bring me organic traffic, and is that traffic actually buying?"** It is the surface that joins Google Search Console (GSC) search-impression data — query, page, country, device, impressions, clicks, CTR, average position — with downstream store-side data (sessions, orders, revenue) so a merchant can see, on one screen, that "running shoes size 12" drove 412 organic clicks, 38 sessions, and $1,820 in revenue last month.

The category gap is enormous and obvious. Almost every direct ecommerce competitor ships a paid-attribution / profit / cohort surface; almost none ship a real SEO surface. The "having data" vs "having this feature" distinction is sharp here: merchants can always log into GSC, GA4, and Shopify in three tabs, but joining query → page → order in a single view is rare. Of the 30+ competitor profiles reviewed, only **3 expose any GSC data inside the product** (Triple Whale, Glew, Putler), and only **Putler** describes a UI surface that joins GSC queries to purchases. The rest defer SEO entirely to GA4 + GSC + Looker Studio. For SMB Shopify/Woo owners — who are price-sensitive and tab-fatigued — this is a clear whitespace.

## Data inputs (what's required to compute or display)

- **Source: Google Search Console** — `query`, `page`, `country`, `device`, `impressions`, `clicks`, `ctr`, `position` (average). Both `Site Impression` and `URL Impression` tables. 16-month max history. (See `../competitors/ga4.md` and `../competitors/looker-studio.md` for confirmed schema.)
- **Source: GA4 (linked to GSC)** — `sessions`, `engaged_sessions`, `engagement_rate`, `purchase_revenue`, `transactions` sliced by `landing_page` and `session_source` = `google` / `medium` = `organic`. Two GA4 cards expose GSC: "Google Organic Search Traffic" (impressions/clicks/CTR by landing page) and "Google Organic Search Queries" (impressions/clicks/CTR by query). GA4 cannot drill GSC data by user/session dimensions — only by GSC-native Country and Device.
- **Source: Shopify / WooCommerce orders** — `orders.id`, `orders.total_price`, `orders.line_items`, `orders.landing_site` / `orders.referring_site`, `orders.utm_source` / `utm_medium` / `utm_campaign`. Used to attribute orders to organic landing pages.
- **Source: Computed** — `revenue_per_click = order_revenue ÷ gsc_clicks` (when joined by URL + date), `revenue_per_query` (joined by GA4 landing page), `brand_vs_non_brand` split (regex on query containing brand name).
- **Source: User-input** — brand-name regex / blocklist for brand-vs-non-brand classification; URL pattern rules to fold variants (e.g. `?utm=...` query strings) into canonical pages.

## Data outputs (what's typically displayed)

- **KPI: Total organic clicks** — `SUM(gsc.clicks)`, count, vs prior-period delta.
- **KPI: Total organic impressions** — `SUM(gsc.impressions)`, count.
- **KPI: Average CTR** — `SUM(clicks) / SUM(impressions)` with `NULLIF` guard, %, vs prior period.
- **KPI: Average position** — `AVG(position)` weighted by impressions, decimal.
- **KPI: Organic-attributed revenue** — `SUM(orders.total_price WHERE session_source='google' AND medium='organic')`, USD.
- **Dimension: Query** — string, top N by clicks/impressions.
- **Dimension: Page (URL)** — string, top N by clicks.
- **Dimension: Country / Device** — GSC-native breakdowns.
- **Dimension: Brand vs non-brand** — derived from regex on `query`.
- **Breakdown: Query × clicks × impressions × CTR × position × revenue** — table.
- **Breakdown: Page × clicks × sessions × orders × revenue** — table.
- **Slice: CTR vs position scatter** — one dot per query, x = avg position, y = CTR; identifies "easy wins" (good position but poor CTR).
- **Slice: Brand vs non-brand revenue split** — pie/bar.
- **Slice: Top-rising / top-falling queries** — period-over-period delta.

## How competitors implement this

### Looker Studio ([profile](../competitors/looker-studio.md))
- **Surface:** Report Editor canvas with native Search Console connector; users build their own page or use Google's "Search Console Report" template from the Template Gallery.
- **Visualization:** Multi-component canvas — typically scorecards (KPI tiles for clicks / impressions / CTR / avg position), a time-series line chart, two ranked tables (Top Queries, Top Landing Pages), a CTR-vs-position scatter, and a geo map for country breakdown.
- **Layout (prose):** Top: a global date-range control + comparison toggle ("compare to previous period / previous year"). Below: scorecard row with the four GSC KPIs. Center: a paired tables block — Top Queries (left) / Top Pages (right). Bottom: CTR-vs-position scatter and country geo map.
- **Specific UI:** GSC source-icon shows in column header text — column labels read "Search Console Clicks", "Search Console Impressions" etc. No source badging beyond the column-name convention. Calculated-field "fx" badge on derived fields (e.g. branded-keyword regex). Date-range control renders as a button on the canvas, click opens a calendar popover with preset list (Today, Yesterday, Last 7/28/30/90 days, YTD, Last year, etc.) on the left and dual-month calendar on the right.
- **Filters:** Date range, query regex (custom), country, device, page regex, brand-vs-non-brand (calculated field).
- **Data shown:** Per `../competitors/looker-studio.md` — "query, page, country, device, impressions, clicks, CTR, average position. Uses Site Impression and URL Impression tables." Computed templates surface "top queries by impressions, top landing pages by clicks, brand vs non-brand split (via regex calc field), CTR vs position scatter."
- **Interactions:** Cross-filter on row click (a query click filters all other charts on the page); date-range applies globally; export to PDF or CSV; schedule recurring email.
- **Why it works (from reviews/observations):** "I love the interactive tools like the date range selectors and campaign drop-down filters, allowing stakeholders to adjust and explore the dashboard on their own." — G2 reviewer in `../competitors/looker-studio.md`. Free + native GSC connector + drag-drop = the canonical DIY SEO dashboard.
- **Source:** `../competitors/looker-studio.md`; supermetrics + Google template galleries.

### GA4 ([profile](../competitors/ga4.md))
- **Surface:** Reports > Acquisition overview (after linking Search Console to the GA4 property in Admin > Product links). Two extra cards appear once linked.
- **Visualization:** Two table cards inside the Acquisition overview tile grid — "Google Organic Search Traffic" (rows = landing page; columns = impressions, clicks, CTR) and "Google Organic Search Queries" (rows = query; columns = impressions, clicks, CTR, average position). Plus a publishable "Search Console" report Collection (off by default) that adds a dedicated tab.
- **Layout (prose):** Top: page header with date-range picker and comparison toggle. Body: tiled grid of summary cards. The two GSC cards sit alongside the rest of Acquisition cards (channel grouping, source/medium, etc.). Each card has a footer "View [report] >" link to a fuller table view.
- **Specific UI:** Standard GA4 sortable table; ascending/descending arrows on column headers; row-level mini bar chart in numeric cells (subtle horizontal bar reflecting share of column total). No color-coded delta cells. **Hard limitation surfaced inline:** "GSC data can only be sliced by Country and Device — cannot join GSC clicks to GA4 user/session dimensions" (per `../competitors/ga4.md`). Translation: a merchant cannot ask "of the users who clicked this query, how many bought?" — the join is broken inside GA4 itself.
- **Filters:** Date range, comparison toggle, country, device. **No** session/user-level filtering allowed on GSC rows.
- **Data shown:** Query, page, impressions, clicks, CTR, average position; from a 16-month rolling window after linking.
- **Interactions:** Click row to add as a filter (not drill-down). Sort columns. Add comparison cohort.
- **Why it works (from reviews/observations):** Free, already-installed, native — but quotes in `../competitors/ga4.md` undercut it: "GSC integration is shallow… cannot drill GSC data by GA4 user/session dimensions." And in general: "Data thresholding hides rows for low-traffic sites" — disproportionately hits SMB SEO long-tail queries.
- **Source:** `../competitors/ga4.md`; help.google.com Search Console report docs.

### Putler ([profile](../competitors/putler.md))
- **Surface:** Sidebar > Audience (Audience Dashboard).
- **Visualization:** Composite dashboard combining a built-in web-analytics layer with GA4 + GSC pulls — described as "revenue per visitor by channel, conversion percentages by source, pages ranked by revenue impact, and search keywords connected to purchase outcomes." UI specifics not extensively documented publicly; the surface format is verbal-only in published material.
- **Layout (prose):** UI details not directly available — only feature description seen on marketing page (`putler.com/web-analytics/`).
- **Specific UI:** UI details not available — only feature description observed.
- **Filters:** Implied date range; specific filter UI not documented.
- **Data shown:** Per `../competitors/putler.md` — "Sessions, visitors, pageviews, bounce rate, visit duration, traffic sources/UTMs, device data, conversion rate by source, revenue per visitor by channel, GSC keywords joined to purchases."
- **Interactions:** Not documented in public materials.
- **Why it works (from reviews/observations):** Putler is one of only two products in the entire competitor set that joins GSC queries to downstream transactions inside the product. Reviewers praise consolidation generally — "I have been using Putler for quite some time, and it has become an integral part of my everyday work" — Maoz Lustig, wordpress.org plugin review, December 17, 2025. No reviewer specifically calls out the SEO surface — likely because UI details are thin.
- **Source:** `../competitors/putler.md`.

### Glew ([profile](../competitors/glew.md))
- **Surface:** Settings > Integrations lists Google Search Console; the surface where GSC data is exposed in-app is not documented publicly. Most likely flows into the Looker-powered Custom Reports module (Glew Plus tier).
- **Visualization:** No visualization observed. Per `../competitors/glew.md`: "Listed as an integration; specific fields/metrics not detailed in public marketing pages."
- **Layout (prose):** Not observed in public sources.
- **Specific UI:** Not observed — UI details are gated behind sales-led demo and behind the Glew Plus tier (custom reports).
- **Filters:** Not observed.
- **Data shown:** Not detailed in public materials. Likely the standard GSC field set (query, page, clicks, impressions, CTR, position) accessible via Looker.
- **Interactions:** Not observed.
- **Why it works:** Cannot evaluate — UI not documented and not surfaced in public reviews.
- **Source:** `../competitors/glew.md`.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** GSC listed under "Data & Analytics" integration group. No standalone SEO dashboard observed.
- **Visualization:** No visualization, no dedicated surface — connector exists, dashboarding does not.
- **Layout (prose):** Not observed — `../competitors/triple-whale.md` notes: "GSC is in their integration list but conspicuously under-featured ('Data & Analytics' group only; no marketing for an SEO/GSC dashboard)."
- **Specific UI:** Not observed.
- **Filters:** Not observed.
- **Data shown:** Per `../competitors/triple-whale.md` — "queries, impressions, clicks, position. Listed under 'Data & Analytics' integration group; depth of GSC dashboarding not visible publicly."
- **Interactions:** Not observed.
- **Why it works:** Cannot evaluate — Triple Whale has the connector but the SEO surface is not part of their marketing or product narrative; Triple Whale is paid-attribution-first.
- **Source:** `../competitors/triple-whale.md`.

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** "SEO reports" listed in the Free Shopify App Store tier ("Ad / Web / SEO reports only"); GA4-derived web/SEO module rolled into the Marketing Reports surface.
- **Visualization:** No dedicated GSC surface — GA4 sessions feed Marketing Reports without a standalone SEO dashboard. Per `../competitors/storehero.md`: "SEO module exists but it's referenced as part of the broader marketing bundle; no GSC-specific data pulls described in public materials."
- **Layout (prose):** Not observed; folded into Marketing Reports.
- **Specific UI:** Not observed.
- **Filters:** Date range, channel — inherited from Marketing Reports.
- **Data shown:** GA4 sessions/landing-page data; GSC fields not described.
- **Interactions:** Not observed.
- **Why it works:** Cannot evaluate independently — SEO is bundled into a generic marketing report.
- **Source:** `../competitors/storehero.md`.

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** None. Confirmed gap. Per `../competitors/conjura.md`: "No Google Search Console / GSC. SEO/search-query data not in the integration list."
- **Visualization:** Not applicable — feature absent.
- **Layout (prose):** Not applicable.
- **Specific UI:** Not applicable.
- **Filters:** Not applicable.
- **Data shown:** GA4 sessions only, no query-level data.
- **Interactions:** Not applicable.
- **Why it works:** N/A.
- **Source:** `../competitors/conjura.md`.

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** None. Per `../competitors/lebesgue.md`: "No GSC integration observed in any source." Lebesgue ingests GA4 sessions for audit purposes only and explicitly positions Le Pixel "vs. GA4" — SEO is out of scope.
- **Visualization:** Not applicable — feature absent.
- **Layout (prose):** Not applicable.
- **Specific UI:** Not applicable.
- **Filters:** Not applicable.
- **Data shown:** Not applicable.
- **Interactions:** Not applicable.
- **Why it works:** N/A.
- **Source:** `../competitors/lebesgue.md`.

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** None. Per `../competitors/polar-analytics.md`: "No GSC (Google Search Console) — not listed anywhere in connector documentation" and "No GSC connector observed."
- **Visualization:** Not applicable — feature absent.
- **Specific UI:** Not applicable.
- **Source:** `../competitors/polar-analytics.md`.

### Shopify Native ([profile](../competitors/shopify-native.md))
- **Surface:** None for GSC. Closest analog is the **"Top online store searches"** report under Reports > Behaviour, which surfaces *internal* site-search queries (what users typed in the storefront search box) — not Google organic queries.
- **Visualization:** Standard Shopify report chassis — date-range bar, summary cards, line chart, sortable table — with rows = internal search query, columns = sessions/conversions.
- **Layout (prose):** Default Shopify reports layout per `../competitors/shopify-native.md`: title + date-range + summary metrics row + chart + table.
- **Specific UI:** Standard Shopify Polaris design system (table with column-header sort arrows; CSV export button top-right).
- **Filters:** Date range; sort + filter on table columns.
- **Data shown:** Internal site-search queries, sessions, conversions tied to those searches.
- **Interactions:** Sort, filter, CSV export.
- **Why it works:** Useful for storefront merchandising but answers a different question — does **not** address "which Google queries drive my organic clicks".
- **Source gap:** Per `../competitors/shopify-native.md` — "No GSC. No GA4." This is a clean Nexstage gap vs. Shopify Native.
- **Source:** `../competitors/shopify-native.md`.

### WooCommerce Native ([profile](../competitors/woocommerce-native.md))
- **Surface:** None. Per `../competitors/woocommerce-native.md`: "No native or marketplace-blessed GSC integration. Not part of WooCommerce's analytics surface at all" and "No GSC integration anywhere in the WooCommerce extension catalog." The Order Attribution extension exposes channel buckets (Direct, Organic Search, Organic Social, Paid Search, Paid Social, Email, Referral, Other) — but this is UTM-derived and contains zero query-level data.
- **Visualization:** Channel-only breakdown table (no query, no page-level GSC data).
- **Specific UI:** Standard WooCommerce Analytics chassis.
- **Source:** `../competitors/woocommerce-native.md`.

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** None. Per `../competitors/beprofit.md`: "No GA4 integration observed" and "No Google Search Console (GSC) integration observed — BeProfit is purely a paid-channel + on-site profit tool."
- **Visualization:** Not applicable — feature absent.
- **Source:** `../competitors/beprofit.md`.

### Varos ([profile](../competitors/varos.md))
- **Surface:** None. Per `../competitors/varos.md`: "No Google Search Console (GSC) integration observed" and "No GSC / SEO benchmarks. Strictly paid + Shopify + GA4. Organic/SEO is absent."
- **Visualization:** Not applicable — feature absent.
- **Source:** `../competitors/varos.md`.

### Atria ([profile](../competitors/atria.md))
- **Surface:** None. Per `../competitors/atria.md`: "No Shopify / WooCommerce / BigCommerce. No GA4. No GSC."
- **Visualization:** Not applicable — feature absent.
- **Source:** `../competitors/atria.md`.

## Visualization patterns observed (cross-cut)

Across the 14 competitors reviewed, only 3 expose GSC data inside the product (Looker Studio, Putler, Glew); GA4 exposes GSC data via its native link but not as a true joined surface; Triple Whale has the connector wired but no SEO dashboard. **9 of 14 do not surface SEO data at all.**

- **Multi-component canvas (Looker Studio):** the only competitor with a fully-realized SEO dashboard pattern — KPI scorecards + time-series + paired ranked tables (Top Queries / Top Pages) + CTR-vs-position scatter + country geo map. Sets the visual baseline for the category but requires the merchant to assemble it themselves.
- **Tile/card-based summary (GA4):** two cards inside Acquisition overview — Top Organic Search Traffic table + Top Organic Search Queries table. No drill-down past country/device. Shallowest implementation that still exposes GSC.
- **Joined web-analytics dashboard (Putler):** described verbally as combining sessions + GSC keywords + revenue per visitor — but UI details unobserved publicly; cannot reverse-engineer the layout.
- **Connector-only (Triple Whale, Glew):** GSC is plumbed in but no merchant-facing dashboard observed.
- **No surface (Conjura, Lebesgue, Polar, Shopify Native, Woo Native, BeProfit, Varos, Atria, StoreHero):** absent.

Visual conventions that recurred where SEO surfaces existed:
- **Paired ranked tables** (queries on one side, pages on the other) is the only pattern shared by both implementations that show layout (Looker Studio + GA4).
- **CTR vs avg-position scatter** appears in Looker Studio templates (Supermetrics + Google) but not in GA4 native.
- **No source-of-truth badging** anywhere — Looker Studio columns are labeled by source name ("Search Console Clicks"), GA4 simply puts GSC in the Acquisition Overview without a distinct icon, Putler doesn't surface badging at all. The 6-source-badge thesis (`../competitors/looker-studio.md` notes: "No source-of-truth badging exists. Looker Studio columns are labelled by data source name only") is unrepresented anywhere in the SEO category.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: GSC-native connector convenience (Looker Studio)**
- "Data Template currently I'm using the GA4 Template and Google webmaster." — Rahul K., G2 review, cited in `../competitors/looker-studio.md`.
- "Looker Studio makes it incredibly easy to build interactive, shareable dashboards all without coding." — User quoted in AgencyAnalytics 2026 review, cited in `../competitors/looker-studio.md`.

**Theme: Sharing dashboards with stakeholders**
- "I love the interactive tools like the date range selectors and campaign drop-down filters, allowing stakeholders to adjust and explore the dashboard on their own." — G2 reviewer, cited in `../competitors/looker-studio.md`.

**Theme: GA4 data model fundamentals (where GSC link lives)**
- "GA4's data model is excellent." — Dana DiTomaso, Kick Point, cited in `../competitors/ga4.md`.
- "Enhanced Measurement is very simple to set up, and because GA4 does the heavy lifting for you, it is less prone to data quality issues." — Brian Clifton, Verified Data, cited in `../competitors/ga4.md`.

**Theme: Putler as a single source of operational truth (which includes SEO via GSC join)**
- "I have been using Putler for quite some time, and it has become an integral part of my everyday work with clients." — maozlustig, wordpress.org, December 17, 2025, cited in `../competitors/putler.md`.
- "Putler has been my trusted data companion for a decade." — Ekaterina S., Capterra, October 7, 2025, cited in `../competitors/putler.md`.

(Note: across all competitor profiles, **zero verbatim quotes specifically praise an SEO surface**. Even where the feature exists, reviewers praise the wider product — never the SEO-specific UI. This is itself a finding: SEO is not a marketed-and-loved feature anywhere in this category.)

## What users hate about this feature

**Theme: GA4 latency makes SEO insights stale**
- "The data latency is a joke, taking 12-24 hours to report on what is happening prevents this from being an actionable tool." — Ron Weber, Sr Director at Actian, cited in `../competitors/ga4.md`.

**Theme: GA4 thresholding hides long-tail queries**
- Per `../competitors/ga4.md`: "Data thresholding hides rows for low-traffic sites. Below ~50 users per dimension row, data is suppressed for 'privacy' — disproportionately hits SMB sites under 1,000 users/day." This directly hits SMB SEO long-tail queries — the exact data SMB merchants most need to see.

**Theme: GA4 GSC join is shallow**
- Per `../competitors/ga4.md`: "GSC integration is shallow. GSC data can only be sliced by Country and Device — cannot join GSC clicks to GA4 user/session dimensions." Merchants cannot ask "of users who came from this query, how many bought."

**Theme: Looker Studio performance + cost at scale**
- "Performance issues with large Dataset such as loading dashboard." — Ashok S., G2 review, cited in `../competitors/looker-studio.md`.
- "What I like least is that for platforms external to Google, it is often necessary to have independent payment connectors." — Raul S., G2 review, cited in `../competitors/looker-studio.md`. (Relevant because joining Shopify orders to GSC clicks in Looker Studio requires a paid Shopify Partner connector.)

**Theme: GA4 complexity for SEO-native users**
- "Some of the features I use every day are missing or extremely complicated to find in GA4." — Elizabeth Rule, Sterling Sky, cited in `../competitors/ga4.md`. (Sterling Sky is a local SEO agency — telling that SEO practitioners themselves struggle.)
- "Spending more time figuring out why attribution is not properly labeled." — John McAlpin, SEO consultant, cited in `../competitors/ga4.md`.

**Theme: Sidekick AI fabricates SEO data inside Shopify Native**
- "Support confirmed there is 'no setting' to prevent the AI from hallucinating data or ignoring negative SEO constraints." — Dawsonx, Shopify Community, February 24, 2026, cited in `../competitors/shopify-native.md`.
- "If I have to manually audit 80+ products because a 'voluntary' tool silently corrupts my database and ignores SEO constraints…" — Dawsonx, February 26, 2026, cited in `../competitors/shopify-native.md`.

## Anti-patterns observed

- **Ship the connector, hide the dashboard (Triple Whale, Glew).** Both list GSC in their integration directory; neither documents an actual SEO surface. Merchants who connect GSC expecting a dashboard get… nothing visible. `../competitors/triple-whale.md`: "GSC is in their integration list but conspicuously under-featured." Anti-pattern: integration as marketing checkbox without product follow-through.
- **Bundle SEO into "Marketing Reports" without GSC depth (StoreHero).** `../competitors/storehero.md` notes the SEO module "is referenced as part of the broader marketing bundle; no GSC-specific data pulls described." Result: SEO becomes a tab label without any of the data merchants actually need (queries, pages, position).
- **Defer to "the user's own GA4 + GSC + Looker Studio stack" (Conjura, Lebesgue, Polar, BeProfit, Varos, Atria).** All flag SEO as out-of-scope. The implicit anti-pattern: by treating SEO as someone else's problem, the merchant is forced into 3+ tabs to reconcile organic clicks with revenue. Reviews consistently mention tab-switching fatigue but no profile contains a verbatim quote tied to SEO specifically — because no competitor has earned it.
- **Threshold suppression on long-tail queries (GA4).** GA4's privacy thresholding silently hides rows below ~50 users — exactly the long-tail organic queries SMB merchants most want to see. Anti-pattern: applying enterprise-data-thresholds to SMB-data-volumes makes the surface unusable at the level merchants need.
- **Internal site-search confused with Google search (Shopify Native).** Shopify's "Top online store searches" report tracks queries typed into the storefront's own search box — not Google organic queries. Reports labeled "search" without disambiguation create merchant confusion about what's actually being measured.
- **Hallucinating AI on top of an already-thin SEO layer (Shopify Sidekick).** Per `../competitors/shopify-native.md`, Sidekick fabricates SEO/technical data and ignores negative constraints, requiring 80+ product audits to clean up. Layering generative AI on a surface where the underlying data is missing produces confidently wrong outputs.

## Open questions / data gaps

- **Putler's SEO UI is undocumented.** The Audience Dashboard is the only competitor surface that joins GSC queries to purchases inside the product — but `../competitors/putler.md` notes "UI details not available — only feature description seen on marketing page." A live demo or screenshot capture is needed to see how Putler actually visualizes the join.
- **Glew's GSC depth is gated.** GSC is listed but `../competitors/glew.md` notes: "specific fields/metrics not detailed in public marketing pages." Live demo + Glew Plus tier access would be needed to see the surface.
- **Triple Whale's GSC connector behaviour.** Connector exists but no SEO surface is published. Unknown whether GSC fields appear as ad-hoc metrics in the Custom Dashboard / Willy AI flow, or whether they're effectively dormant.
- **Revenue-per-query attribution methodology.** Where competitors do pull GSC, none publish how they join GSC `query` to a downstream order. The two-hop join (`query → landing_page → session → order`) requires both GSC and GA4 (or session-level tracking), and the join key (URL path canonicalization) is a non-trivial data engineering problem. No competitor profile documents how this is done.
- **MonsterInsights and Plausible-style tools.** Mentioned in feature index notes but not present in the competitor folder; they likely surface a thinner SEO layer (referrer-only, no GSC join). Not investigated within this profile.
- **CTR-vs-position scatter prevalence.** Confirmed in Supermetrics Looker Studio templates; unclear whether GA4 / Putler / Glew surface this view at all.
- **Brand-vs-non-brand split methodology.** Looker Studio does this via user-defined regex on a calculated field; no other competitor publishes how (or whether) they implement this critical SEO segmentation.

## Notes for Nexstage (observations only — NOT recommendations)

- **Most ecommerce competitors have THIN SEO coverage. This is a genuine category whitespace.** Of 14 competitors examined, only 3 expose GSC data inside the product (Triple Whale, Glew, Putler), and only **Putler** describes a UI that joins GSC queries to purchases. 9 of 14 simply don't ship SEO at all. The dominant merchant workflow today is "GSC + GA4 + Shopify in three tabs"; this is widely complained about but no vertical ecommerce analytics tool has solved it.
- **The "having data" vs "having a feature" distinction is the sharpest in this category.** GSC is free, GA4 + GSC link is free, Looker Studio + GSC connector is free — yet merchants still describe the workflow as painful (latency, thresholding, shallow joins, calculated-field complexity). The product opportunity is synthesis, not data availability.
- **Looker Studio is the canonical DIY alternative.** A merchant choosing not to buy Nexstage's SEO surface will most likely default to a Supermetrics-published Looker Studio template. Per `../competitors/looker-studio.md`: "Recognise this as the actual decision point in messaging, not Triple Whale or Polar." For SEO specifically, Looker Studio is the only credible $0 path — and it tops out at the 5-source blend cap and 12-hour Partner-connector freshness.
- **GA4's GSC integration has known load-bearing weaknesses** that map cleanly to a Nexstage advantage: (a) cannot drill GSC by user/session dims; (b) thresholds long-tail queries below ~50 users; (c) 24-48h latency on standard properties. A Nexstage SEO surface that joins GSC `query` → GA4 `landing_page` → store-side `orders.id` directly answers all three.
- **Source-of-truth badging is unrepresented in this category.** Looker Studio uses column-name labels ("Search Console Clicks") but no badge; GA4 and Putler don't badge at all. The 6-source-badge thesis (Real / Store / Facebook / Google / GSC / GA4) has clean differentiation lane in SEO — particularly because the "GSC" badge has no analog in any vertical competitor.
- **Putler is the closest direct competitor on this surface.** The only product that pulls GSC + GA4 + store transactions and joins them in a single dashboard. UI details are undocumented publicly, suggesting either a thin surface or marketing under-investment. Worth a follow-up via Putler's free trial to capture screenshots if SEO is a Nexstage product priority.
- **"No GSC" is the cleanest published gap across the category.** Per `../competitors/conjura.md`, `../competitors/lebesgue.md`, `../competitors/polar-analytics.md`, `../competitors/beprofit.md`, `../competitors/varos.md`, `../competitors/atria.md`, `../competitors/woocommerce-native.md`, `../competitors/shopify-native.md` — all explicitly do not ingest GSC. Eight competitor profiles flagged "no GSC" as a structural gap relative to Nexstage's 6-source thesis. This is the single most-cited structural Nexstage advantage in the entire competitor research set.
- **No competitor visualizes brand-vs-non-brand.** Looker Studio templates do this via a user-built regex calculated field — i.e., the merchant has to write the regex themselves. An out-of-the-box brand/non-brand split (with workspace-level brand keyword config) is unrepresented and a plausible UX win.
- **No competitor visualizes "easy wins" (good position + poor CTR queries).** Looker Studio's CTR-vs-position scatter implies this geometrically but doesn't label quadrants. A quadranted scatter explicitly labeling "rewrite title tag" / "promote in nav" / "optimize meta description" zones would be unrepresented in the category.
- **GA4's 16-month GSC history limit is a hard ceiling** for any SEO surface that pulls GSC via the GA4 link rather than direct GSC connection. Direct GSC API access is required for longer histories — worth verifying which path Nexstage uses for the `gsc` source.
- **Internal site-search ≠ organic search.** Shopify Native's "Top online store searches" report is a separate question and doesn't compete with GSC. A Nexstage SEO surface should not conflate the two — they answer different merchant questions.
- **Reviewers do not praise SEO surfaces specifically.** Across 14 profiles' verbatim quote sections, zero quotes praise an SEO/GSC dashboard by name. Even Looker Studio reviewers who use GSC templates praise the tool generically. If Nexstage ships a great SEO surface, this is potentially blue-ocean reviewer territory — no incumbent has earned a "love" quote here.
