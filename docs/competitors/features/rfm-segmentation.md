---
name: RFM segmentation
slug: rfm-segmentation
purpose: Identify a store's best, at-risk, and lapsed customers by scoring each on Recency, Frequency, and Monetary value, then turning the resulting cohorts into named, marketable segments.
nexstage_pages: customers (acquisition / customer-list / segment-detail surfaces)
researched_on: 2026-04-28
competitors_covered: repeat-customer-insights, klaviyo, putler, glew, daasity, peel-insights, lifetimely, lebesgue, triple-whale, metorik, conjura
sources:
  - ../competitors/repeat-customer-insights.md
  - ../competitors/klaviyo.md
  - ../competitors/putler.md
  - ../competitors/glew.md
  - ../competitors/daasity.md
  - ../competitors/peel-insights.md
  - ../competitors/lifetimely.md
  - ../competitors/lebesgue.md
  - ../competitors/triple-whale.md
  - ../competitors/metorik.md
  - ../competitors/conjura.md
  - https://www.littlestreamsoftware.com/articles/grading-shopify-customers-rfm-segmentation/
  - https://www.littlestreamsoftware.com/articles/how-rfm-is-used-by-the-customer-grid-to-segment-customers-into-behavior-groups/
  - https://help.klaviyo.com/hc/en-us/articles/17797889315355
  - https://help.klaviyo.com/hc/en-us/articles/17797937793179
  - https://klaviyo.tech/the-research-behind-our-new-rfm-feature-4c38be17b184
  - https://www.putler.com/blog/rfm-analysis/
  - https://help.peelinsights.com/docs/rfm-analysis
  - https://www.peelinsights.com/post/what-is-rfm-analysis
  - https://www.glew.io/articles/new-feature-customer-segments
---

## What is this feature

RFM (Recency, Frequency, Monetary) segmentation is the customer-analysis primitive that answers the merchant question "Who are my best, at-risk, and lapsed customers?" Every order in the store carries three scoring dimensions per customer: how recently they last bought, how often they buy, and how much they spend. The score-triple gets bucketed (typically into 1-5 quintiles or 1-3 thirds), and each customer is assigned to one of a fixed number of named cohorts — Champions, Loyal, At Risk, Hibernating, Lost, etc. The output is a marketing list, not a chart: merchants want to push the "Champions" segment to a VIP flow, send "At Risk" a win-back coupon, and stop spending acquisition dollars on "Lost".

For SMB Shopify/Woo owners the difference between "having data" (every store platform exposes per-customer order count + last-order date + total spend) and "having this feature" is whether the tool *names* the cohorts, *renders* them as a single spatial visual the merchant can scan in five seconds, and *pushes* the segment to Klaviyo/Attentive/Meta as an audience. Native Shopify and WooCommerce expose none of this — Shopify tags customers as "Returning vs New" and stops there; the WooCommerce native profile explicitly lists "no scoring/RFM segmentation" as a gap (`../competitors/woocommerce-native.md`). The category has converged on a 5×5 spatial grid (or, in Klaviyo's case, six fixed cohorts driven by 1-3 scoring) with named cells, a click-to-audience flow, and — in two cases — a migration-flow visual showing how customers shifted between cohorts over time.

## Data inputs (what's required to compute or display)

- **Source: Shopify / WooCommerce** — `customers.id`, `orders.customer_id`, `orders.created_at`, `orders.total_price`, `orders.financial_status` (to exclude refunds/cancellations from frequency counts), `orders.refunds`, `orders.source_name` (Shopify acquisition channel — Repeat Customer Insights uses this as the ONLY channel dimension, gated by tier).
- **Source: Computed (per customer, per workspace)** — `recency_days = today − max(orders.created_at)`, `frequency = count(distinct orders)`, `monetary = sum(orders.total_price) − sum(orders.refunds.amount)`.
- **Source: Computed (per customer, per workspace, scored)** — `r_score = ntile(5, recency_days asc)`, `f_score = ntile(5, frequency desc)`, `m_score = ntile(5, monetary desc)`. Quintile boundaries are *relative-to-this-store percentiles*, never global benchmarks (Repeat Customer Insights documents the rule explicitly: `"5: Top 20% / 4: Top 21–40% / 3: Middle 20% / 2: Bottom 21–40% / 1: Bottom 20%"`, `../competitors/repeat-customer-insights.md`). Klaviyo uses 1-3 thirds instead of 1-5 quintiles, producing a 27-cell space mapped to six fixed cohorts (`../competitors/klaviyo.md`).
- **Source: Computed (cohort label)** — deterministic mapping from `(r,f,m)` triplet to a named segment, e.g. `(5,5,5) = Champions`, `(1,1,1) = Lost / Inactive`. Each tool ships a fixed taxonomy: 6 (Klaviyo), 10 (Peel), 11 (Putler), ~30 (Repeat Customer Insights via three RFM-pair grids).
- **Source: Computed (letter grade — Repeat Customer Insights only)** — `grade = weighted(r_score × 0.5, f_score × 0.25, m_score × 0.25)` mapped to A–F; explicit doc quote: `"Recency is the most powerful factor so it makes up the majority of the letter grade"` (`../competitors/repeat-customer-insights.md`).
- **Source: Computed (segment migration — Klaviyo, Daasity)** — diff between `(r,f,m)_at_t0` and `(r,f,m)_at_t1` per customer; aggregated to `(start_segment → end_segment) edge counts` for the Sankey ribbon dataset.
- **Source: User-input (segment activation)** — destination credentials: Shopify customer-tag namespace (Repeat Customer Insights), Klaviyo list ID, Attentive segment ID, Meta Custom Audience ID, Mailchimp audience ID (Putler, Glew). No user-input thresholds — bucketing is automatic.
- **Source: User-input (filter overlay — Peel, Glew)** — purchase count, products purchased, SKUs, customer tags, locations, channels, campaigns, discount codes, LTV thresholds; layered on top of RFM cells to slice further (`../competitors/peel-insights.md`, `../competitors/glew.md`).

## Data outputs (what's typically displayed)

- **Spatial grid** — 5×5 grid (Peel, Repeat Customer Insights, Putler at 6×6) where each cell is a named segment. Axes: Recency on one axis, combined Frequency+Monetary on the other (Peel, Putler), or Recency × Monetary / Recency × Frequency / Frequency × Monetary as three separate paired views (Repeat Customer Insights "Customer Grid").
- **Cohort distribution bar chart** — Klaviyo's "Compare Distribution of Customers" — bar chart of segment size at start of period vs end of period.
- **Migration Sankey** — Klaviyo only — left-side start-of-period segments → right-side end-of-period segments; ribbons are customer counts that moved.
- **Letter grade** — Repeat Customer Insights only — A–F per customer, recency-weighted; explicit operator-readable signal.
- **Per-segment median table** — Klaviyo's "Median Performance" — median Days Since Purchase, median Purchase Order Number, median Placed Order Revenue per segment.
- **Segment customer count** — count + % of total per cell/cohort, with hover tooltip exposing exact numbers (Klaviyo, Putler).
- **Per-segment KPIs** — orders per customer, LTV per customer, average days since last order (Peel "right-side R/F/M filter").
- **Audience push action** — every implementation surfaces a "send to" or "export" button on each cell or segment; destinations vary by competitor (Klaviyo, Attentive, Postscript, Meta Custom Audience, Shopify customer tag, Mailchimp, CSV).
- **Recommended action / marketing advice** — Repeat Customer Insights and Putler attach prescriptive copy ("retain", "win back", "don't waste budget on Lost") to each cell. Generic per-segment marketing recipe.
- **Trend chart of segment population over time** — Repeat Customer Insights "Customer Grid History" tracks "how segment populations shift over time."

## How competitors implement this

### Repeat Customer Insights ([profile](../competitors/repeat-customer-insights.md))
- **Surface:** Sidebar > Customer Grid (the flagship), Customer Grading (letter-grade roll-up), Customer Grid History (population trend).
- **Visualization:** 5×5 cell grid + sortable customer table with letter-grade column. Three separate 5×5 grids for the three RFM pairings (Recency × Monetary, Recency × Frequency, Frequency × Monetary).
- **Layout (prose):** The Customer Grid is a 5×5 matrix on Recency × Monetary as the primary pairing. Each axis is scored 1-5; each of the 25 cells maps to a named behavior segment. Across the three pairings combined the docs claim "30+" named groups. Customer Grading Report renders a per-customer A–F grade derived from the three RFM digits with documented weighting: `"Recency is the most powerful factor so it makes up the majority of the letter grade"`. Doc example: `"a score of 435 would probably be a B customer"`.
- **Specific UI:** Cells are clickable — `"If you then click on that segment name, you'll see details about that segment as well as advice on how to market to them"` (`../competitors/repeat-customer-insights.md`). Cell coloring, density encoding, and per-cell counts are NOT documented in public material — UI details not available beyond the cell-and-segment-name structure. Letter grades described as `"a quick visual indicator"` with no published color spec.
- **Filters:** Date drill-down (all-time / current year / previous year on entry tier; 4 years + quarterly on Growth tier; per-quarter + annualized on Peak tier). Acquisition source filter (3 / 11 / 41 channels by tier — degenerate channel attribution off Shopify `order.source_name`).
- **Data shown:** RFM scores 1-5 on each axis; segment names per cell (examples: "Loyal", "Potential Loyal", "Promising New" — full 30+ list never enumerated publicly). Per-customer A–F grade with underlying RFM triplet (e.g., 515).
- **Interactions:** Click cell → segment detail with marketing advice. Push-to-Shopify-tag and push-to-Klaviyo gated to Growth tier ($99/mo+). Daily auto-recompute: `"automatically adjust as needed as new customer behavior comes in every day"`.
- **Why it works (from reviews/observations):** "COHORT ANALYSIS automatically created from your store is unbelievable and a must-have for any startup focused on growth." — pantys, Shopify App Store, June 14, 2019 (`../competitors/repeat-customer-insights.md`). The single-letter grade is unique in the category; multiple reviewers cite the segmentation depth as a category-leader for retention analytics on Shopify.
- **Source:** `../competitors/repeat-customer-insights.md`; https://www.littlestreamsoftware.com/articles/how-rfm-is-used-by-the-customer-grid-to-segment-customers-into-behavior-groups/; https://www.littlestreamsoftware.com/articles/grading-shopify-customers-rfm-segmentation/

### Klaviyo ([profile](../competitors/klaviyo.md))
- **Surface:** Marketing Analytics > Customer insights > RFM analysis (or Advanced KDP > Intelligence > Customer insights > RFM analysis). Behind the $100/mo Marketing Analytics add-on.
- **Visualization:** Three stacked cards — bar-chart distribution + Sankey migration diagram + median-performance table. Six fixed cohorts; NOT a 5×5 grid.
- **Layout (prose):** Top: calendar pickers for start and end of report range. Main canvas is three stacked cards. **Compare Distribution of Customers** — three tabs: *Customers* tab is a bar chart of group sizes at start vs end of period (hover reveals exact profile counts and percentage); *Added or Dropped* tab is per-group bar chart with **teal segments for added profiles, red segments for dropped profiles**; *Percentage Change* tab is a static table with totals, percentages, and change deltas. **Group Change Over Time** card is a Sankey diagram with start-date groups on the left and end-date groups on the right; ribbons are customer counts that migrated. **Median Performance** card has tabs to switch between start-date and end-date snapshots, displaying a static table with median Days Since Purchase, median Purchase Order Number, and median Placed Order Revenue per group.
- **Specific UI:** Six fixed cohort labels — **Champions, Loyal, Recent, Needs Attention, At Risk, Inactive**. Each profile gets a 1-3 score on R, F, and M (e.g., 333 is Champion; 111 is Inactive). The 27-cell triplet space is mapped to six mutually exclusive cohorts (Klaviyo's engineering blog confirms the design: `"six mutually exclusive cohorts"` was a deliberate research-driven choice). Sankey ribbons hover to reveal migration counts.
- **Filters:** Calendar pickers for report start/end date. Drill into Customer Insights to build segments from "Current RFM group" or "Previous RFM group" properties.
- **Data shown:** Group size (count + %), profiles added/dropped per group, group-to-group migration count, median Days Since Purchase, median Purchase Order Number, median Placed Order Revenue.
- **Interactions:** Date pickers recalculate all cards. Hover Sankey ribbons for migration counts. Click into Customer Insights to drill into segments built from the RFM-group properties; segments are first-class Klaviyo audiences usable in any flow/campaign.
- **Why it works (from reviews/observations):** "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group, quoted on Klaviyo.com features page (`../competitors/klaviyo.md`). The Sankey is the differentiator quoted in user reviews; it's the storytelling layer ("how did my Champions migrate to At Risk?") that flat distribution charts can't deliver.
- **Source:** `../competitors/klaviyo.md`; https://help.klaviyo.com/hc/en-us/articles/17797889315355; https://help.klaviyo.com/hc/en-us/articles/17797937793179; https://klaviyo.tech/the-research-behind-our-new-rfm-feature-4c38be17b184

### Putler ([profile](../competitors/putler.md))
- **Surface:** Sidebar > Customers > RFM section within the Customers Dashboard.
- **Visualization:** 6×6 = 36-cell 2D matrix, overlaid with 11 named colored segment regions (not a 5×5 grid; deliberate compression of the full 5×5×5 = 125-cell space).
- **Layout (prose):** A 2D matrix with Recency (0-5) on the X-axis and combined Frequency+Monetary score (0-5) on the Y-axis. Each segment is rendered as a distinct colored region. Putler's docs say `"Giving a distinct color to each segment will allow easier recall"` — color encodes segment identity, not density.
- **Specific UI:** 11 named segments — **Champions (top-right), Loyal Customers, Potential Loyalist, Recent Customers, Promising, Customers Needing Attention, About To Sleep, At Risk, Can't Lose Them, Hibernating, Lost (bottom-left)**. Specific palette not documented in public sources; Putler's docs only say segments are colored "to encode urgency/value." Counts of customers per segment are surfaced as overlay numerics (inferred from "click on any RFM segment" interaction).
- **Filters:** Date range; combines with the dashboard's global date picker.
- **Data shown:** Customer counts per segment, segment-level revenue, recommended actions per segment ("retain", "win back", etc.).
- **Interactions:** `"Users can click on any RFM segment within the chart to view the specific customers within that segment"`. Three-click workflow: pick date range → click segment → export to Mailchimp or CSV. Each segment carries recommended actions.
- **Why it works (from reviews/observations):** "Putler is great for combining sales stats, finding customer data, getting things sorted… powerful customer segmentation and data consolidation across WooCommerce, PayPal, and Stripe." — Jake (@hnsight_wor), wordpress.org plugin review, July 25, 2025 (`../competitors/putler.md`). Compression to 11 named clusters from 125 cells is cited as a deliberate UX simplification.
- **Source:** `../competitors/putler.md`; https://www.putler.com/blog/rfm-analysis/

### Glew ([profile](../competitors/glew.md))
- **Surface:** Customers > Segments. RFM is integrated into the broader Customer Segments 2.0 builder, not surfaced as a dedicated grid view.
- **Visualization:** Sortable customer table + filter chips with RFM scoring as one filter dimension among many. No spatial grid observed in public sources.
- **Layout (prose):** Pre-built segment library + custom segment builder. Pre-built segments include status segments (`"Active, At Risk, Lost"`) and lifetime-based segments (`"Never Purchased, Single Purchase, Multi-Purchase"`). RFM scoring (Recency, Frequency, Monetary) is one of 55+ filter metrics; percentile-based filtering exists for "high-value customer tiers".
- **Specific UI:** Filter chips combine across ecommerce + loyalty + support sources. RFM is a built-in filter, not the primary lens — Glew prioritizes the cross-source segment builder over the canonical grid. UI details for the segment builder canvas not directly observed in public sources (sales-led demo gate).
- **Filters:** Cross-platform filtering across Shopify + Loyalty Lion + Yotpo + Zendesk + Klaviyo. Percentile-based filters for value tiers.
- **Data shown:** Per-segment KPIs vary; Loyalty Lion segments expose `"Participation Rate, Points Approved and Points Spent"`.
- **Interactions:** Build segment → push to Klaviyo as audience (bidirectional sync). CSV export of "only viewed metrics".
- **Why it works (from reviews/observations):** "exceptional reporting capabilities, transforming data visualization and streamlining business analytics effortlessly" — G2 review summary, 2025 (`../competitors/glew.md`). Glew's RFM is buried inside a deep filter builder rather than visualized as a grid — strength is breadth, weakness is no spatial mental model.
- **Source:** `../competitors/glew.md`; https://www.glew.io/articles/new-feature-customer-segments

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** Templates Library > Acquisition Marketing > LTV & RFM dashboard. Plus Templates Library > Retention Marketing > Retention dashboard which uses RFM segment tags applied at month start.
- **Visualization:** Embedded Looker tiles. The RFM dashboard surfaces a "Layer Cake Graph" cohort-stacking visual (acquisition-quarter cohorts stacked over time) — public docs do not enumerate the RFM grid sub-section. Customers are tagged into segments (Non-buyer, Single buyer, Multi-buyer, HVC, Churning, Lapsed) at month start and remain static through that month.
- **Layout (prose):** The Retention dashboard has three sections: (1) **Performance by Customer Segment** — two side-by-side comparative charts of current vs prior month for gross sales, orders, AOV, units per order, average unit revenue. (2) **Time Between Orders**. (3) **Customer Movement & Historical Performance** — cohort segment-transition tracking (single-buyer → multi-buyer → HVC) plus churn/lapsed-customer monitoring.
- **Specific UI:** Embedded Looker UI; UI details not directly observed in public docs beyond section names. The "Customer Movement" section appears to be a segment-transition visualization analogous to Klaviyo's Sankey but is not described as a Sankey explicitly in public sources.
- **Filters:** Linked Store Type, fiscal vs calendar period, wholesale orders excluded from retention calculations by default.
- **Data shown:** Gross sales, orders, AOV, units per order, average unit revenue, time between orders, segment transition rates.
- **Interactions:** Click into any segment row to view customer list. Audiences tab pushes RFM segments nightly to Klaviyo / Attentive / Meta Custom Audiences / Google Ads Customer Match.
- **Why it works (from reviews/observations):** Reviews praise Daasity for centralizing data and enabling custom segment work; UX pain is the Looker-embedded learning curve (`../competitors/daasity.md`). RFM here is a building block in an enterprise warehouse rather than a merchant-friendly grid.
- **Source:** `../competitors/daasity.md`; https://help.daasity.com/core-concepts/dashboards/report-library/retention-marketing/retention

### Peel Insights ([profile](../competitors/peel-insights.md))
- **Surface:** Default landing surface post-login (the doc page is titled "RFM Analysis & Home Page"). The 5×5 grid is the *home* dashboard — a UX decision unique among the cohort-tools cohort.
- **Visualization:** 5×5 grid with **fixed-size square cells** (deliberate non-weighted layout). North Star KPI strip above the grid.
- **Layout (prose):** Top row is a "North Star" KPI strip showing **orders per customer, LTV per customer, returning orders % (weekly)**. Below it sits the 5×5 grid. X-axis = Recency bucketed into 5 groups (days since last order). Y-axis = combined Frequency (total orders) + Monetary value (LTR) bucketed into 5 groups. Right of the grid is a filter panel with R / F / M toggles that re-pivots the grid to show "the average number of days each of those groups took to come back and repurchase, how many orders on average each group is making, and the average monetary value in LTR for each group." Mental model used in marketing copy: `"customers start out in the bottom right, and the goal is make to the top right"` — bottom-right = new/low-value, top-right = champions.
- **Specific UI:** **Square cells of fixed size** — explicitly documented: `"the size of the squares in the RFM Analysis does not change if the number of customers in each square increases or decreases"` and `"the sections of the grid are not proportionally scaled to the percentage of customers in that group"` (`../competitors/peel-insights.md`). Cells labeled with one of 10 named segments — **Champions, Loyal Customers, Potential Loyalist, New Customers, Promising, Need Attention, About to Sleep, Can't Lose Them, At Risk, Hibernating**. Specific colors and exact hover-tooltip behavior not detailed in public docs — UI details for cell colors not available beyond the verbal description.
- **Filters:** Right-side R / F / M toggle re-pivots which metric the cells display (orders / LTV / days-since-last). No date filter on the RFM grid itself in public docs.
- **Data shown:** Per cell — orders per customer, LTV per customer, average days since last order; aggregate metrics for the selected segment.
- **Interactions:** Click any cell → opens a flow to "make an Audience" (name it, see customer count, push to Klaviyo / Attentive / Meta, or download CSV). One-click audience export is the activation path.
- **Why it works (from reviews/observations):** "Peel's reports are magic… unlock answers to burning analytical questions." — Ben Yahalom, President, True Classic (`../competitors/peel-insights.md`). The fixed-cell layout is a deliberate trade-off: stable spatial mental model > data-density encoding. Reviewers cite the audience-builder and custom dashboards as favorites.
- **Source:** `../competitors/peel-insights.md`; https://help.peelinsights.com/docs/rfm-analysis; https://www.peelinsights.com/post/what-is-rfm-analysis

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Customer Behavior Reports group, surfacing as filters on Cohort Analysis and the LTV Drivers report rather than as a dedicated grid.
- **Visualization:** No dedicated RFM grid observed. Customer-behavior reports lean on cohort heatmaps and the "noodle" Sankey product-journey diagram.
- **Layout (prose):** RFM-style segmentation surfaces as filter dimensions on cohort and LTV reports — first-touch / last-touch / source / medium / first-product / discount / country / tags. The Cohort Analysis heatmap is the primary customer-segmentation lens; RFM is implicit in the cohort filtering rather than a named output.
- **Specific UI:** No 5×5 grid in public sources. UI details for any RFM-named view not available — only feature description seen on marketing pages and 1800DTC's hands-on breakdown.
- **Filters:** Cohort timeframe (weekly / monthly / yearly), first-product, channel (first or last touch), country, tags, discount codes.
- **Data shown:** 13+ selectable cohort metrics (accumulated sales per customer, accumulated gross margin per customer, repurchase rate, AOV by cohort, etc.). RFM-style scoring not exposed as a published score per customer.
- **Interactions:** Filter chips at top of cohort report; metric dropdown.
- **Why it works (from reviews/observations):** "invaluable for understanding of your MER, customer cohorts and LTV" — Radiance Plan, Shopify App Store review, April 2, 2026 (`../competitors/lifetimely.md`). Lifetimely's cohort-first framing is praised; RFM specifically is a gap users go to Klaviyo or Repeat Customer Insights to fill.
- **Source:** `../competitors/lifetimely.md`

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** Not observed in public sources.
- **Visualization:** Not observed.
- **Layout (prose):** Not observed in public sources.
- **Specific UI:** Not observed in public sources for an RFM-specific view. Lebesgue's customer-analytics surfaces are dominated by benchmarks and the Guardian alerts module rather than a named RFM grid.
- **Filters:** Not observed for an RFM surface.
- **Data shown:** Not observed for an RFM surface.
- **Interactions:** Not observed for an RFM surface.
- **Why it works (from reviews/observations):** Not applicable — feature not surfaced in public Lebesgue documentation reviewed.
- **Source:** `../competitors/lebesgue.md` (no RFM section observed in profile).

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Lighthouse > AI Audiences sub-section (folded into the Anomaly Detection Agent / Moby surface in 2025-2026).
- **Visualization:** Tile-based "pre-built segment" cards rather than a grid. Six pre-built RFM tiles ("`6 already built segments`" verbatim).
- **Layout (prose):** AI Audiences auto-builds RFM segments and pushes them to Meta. Surfaces as cards inside an alert/audience inbox, not as a spatial grid.
- **Specific UI:** Pre-built RFM audience tiles (`"6 already built segments"` per `../competitors/triple-whale.md`). Audience Sync feature pushes RFM and behavioral audiences to ad platforms — Meta first, Microsoft Ads added April 2026.
- **Filters:** Not deeply documented in public sources for the audience tiles specifically.
- **Data shown:** Customer-count per audience tile; specific metrics not enumerated publicly.
- **Interactions:** One-click push to Meta (and Microsoft Ads); audience used for retargeting / lookalikes / suppression.
- **Why it works (from reviews/observations):** Triple Whale's RFM is positioned as an *activation* surface (segments → ad platform) rather than an *analysis* surface. Aligns with their "marketer-first" positioning. UI details for the audience tiles not directly verified beyond marketing copy.
- **Source:** `../competitors/triple-whale.md`

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Sidebar > Segments. RFM exposed as filter dimensions in the segment builder (no dedicated RFM grid).
- **Visualization:** Filter-builder UI with AND/OR row logic — not a spatial grid. Segment results render as a sortable customer table.
- **Layout (prose):** Add filter row → group filter rows → apply AND/OR logic → save segment with name → share via URL → apply saved segment to any report. RFM dimensions live within a 500+ filter library: "shipping methods, payment methods, fulfillment status, frequency/recency/monetary purchase behavior" (`../competitors/metorik.md`).
- **Specific UI:** Standard segment builder; row-based filter chips; saved segments appear as a list. UI details for the RFM-specific filter rows not enumerated in public docs beyond the named dimension.
- **Filters:** 500+ filters including frequency / recency / monetary as named dimensions; coupons, custom fields, meta fields, shipping methods, payment methods.
- **Data shown:** Customer count matching segment; segment can be applied to any other report (Costs & Profit, cohort, etc.).
- **Interactions:** Auto-recurring scheduled CSV export of segment results. URL-share of segment.
- **Why it works (from reviews/observations):** Metorik's segment builder is the WooCommerce-native answer; depth (500+ filters) is the strength but no canonical RFM grid means users build their own definitions of "Champions". 
- **Source:** `../competitors/metorik.md`

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Customer Analytics section — pre-built customer views with named segments rather than a scoring grid.
- **Visualization:** Customer table with pre-built segment views. No 5×5 grid; segments are filter chips.
- **Layout (prose):** Pre-built views: `"loyal customers, new shoppers, high spenders, or those close to churn"` (`../competitors/conjura.md`). Each view is a saved filter on the customer table; clicking a row drills to the individual customer profile with full purchase history and spending patterns.
- **Specific UI:** Per-row customer columns (total revenue per customer, number of orders, first purchase date, most recent purchase date, AOV, LTV, repurchase rate). Drill into customer profile. Comparison view across acquisition channel, first product purchased, territory.
- **Filters:** Behavior, value, location, acquisition source. Save and reuse filters.
- **Data shown:** Per-customer LTV, order count, first/last purchase date, AOV, repurchase rate.
- **Interactions:** Export segment to CRM/email platform: `"Push high-value or at-risk customer lists into your CRM or email platform for personalized retargeting and loyalty campaigns"` (`../competitors/conjura.md`).
- **Why it works (from reviews/observations):** Conjura's RFM is named-views over a customer table — lower friction than a grid, but no spatial mental model. Praise tends toward the depth of the customer profile drill-down rather than the segmentation surface itself.
- **Source:** `../competitors/conjura.md`

## Visualization patterns observed (cross-cut)

- **5×5 spatial grid (named cells):** 2 competitors — Peel Insights, Repeat Customer Insights. Both use fixed-size cells (Peel documents this explicitly; Repeat Customer Insights does not document cell sizing publicly but describes the grid as a 5×5 matrix with named cells).
- **6×6 spatial grid (named cells, colored regions):** 1 competitor — Putler (with 11 named segments overlaid).
- **Six fixed cohorts + Sankey migration + bar-distribution:** 1 competitor — Klaviyo. The Sankey is the unique element; the six cohorts replace the grid entirely.
- **Pre-built segment tiles (no grid):** 2 competitors — Triple Whale (6 tiles in AI Audiences), Conjura (named views over a customer table).
- **Filter-builder over customer table (RFM as one of many filters):** 3 competitors — Glew (55+ filter metrics with RFM percentile filters), Metorik (500+ filters including frequency/recency/monetary), Lifetimely (RFM-style filters on cohort/LTV reports).
- **Letter grade A–F per customer:** 1 competitor — Repeat Customer Insights. Recency-weighted; uniquely operator-readable.
- **Segment migration over time / Sankey:** 1 competitor — Klaviyo. Daasity has a "Customer Movement" section that may be analogous but is not described as a Sankey explicitly in public sources.
- **No RFM surface observed:** 1 competitor — Lebesgue.

Recurring conventions across implementations:
- **Quintile / tertile bucketing relative to this store's own customer base** is universal — none use global benchmarks. Klaviyo uses 1-3 (27 cells → 6 cohorts); the others use 1-5 (125 cells → 10 / 11 / 30 named segments).
- **Champions / Loyal / At Risk / Hibernating / Lost (or Inactive)** is the cross-cut naming convention. Klaviyo's "Champions, Loyal, Recent, Needs Attention, At Risk, Inactive" and Peel's 10-segment list overlap heavily; Putler's 11-segment list extends with "Can't Lose Them, About To Sleep, Promising".
- **Click cell → audience push** is the universal activation flow. Destinations: Klaviyo (every competitor), Shopify customer tags (Repeat Customer Insights), Attentive / Postscript (Peel, Daasity), Meta Custom Audiences (Klaviyo, Peel, Daasity, Triple Whale), Mailchimp (Putler, Glew), CSV (everyone).
- **Cell density is NOT typically encoded by size.** Peel deliberately fixes cell size: `"the sections of the grid are not proportionally scaled to the percentage of customers in that group"`. Putler colors cells by *segment identity*, not density. Repeat Customer Insights does not document cell coloring at all. The deliberate non-encoding choice is the dominant pattern.
- **No public competitor exposes per-cell density via heatmap intensity** (verified in public sources for Peel, Repeat Customer Insights, Putler, Klaviyo). Counts are surfaced via hover tooltips or overlay numerics.
- **Daily auto-recompute** is implied across the cohort tools — Repeat Customer Insights documents `"automatically adjust as needed as new customer behavior comes in every day"`; Klaviyo retrains predictive CLV "at least once a week"; the rest don't publish a cadence.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Auto-built cohorts with prescriptive next-action**
- "COHORT ANALYSIS automatically created from your store is unbelievable and a must-have for any startup focused on growth." — pantys, Shopify App Store, June 14, 2019 (`../competitors/repeat-customer-insights.md`)
- "Repeat customer insights is a great tool that we use to better understand cohort data and segmentation. Eric, the founder is extremely responsive." — Package Free, Shopify App Store, May 22, 2020 (`../competitors/repeat-customer-insights.md`)
- "A must-have app for generating business insights and understanding customer loyalty / repeat shopping behavior beyond basic Shopify analytics." — 8020nl, Shopify App Store, April 12, 2018 (`../competitors/repeat-customer-insights.md`)

**Theme: Per-segment activation in messaging**
- "I love being able to go in and see how each message performed with each RFM segment." — Christopher Peek, The Moret Group, quoted on Klaviyo.com features page (`../competitors/klaviyo.md`)
- "I have loved working in Peel for our customer retention and insights projects… My favorite features are the custom dashboards and audience building." — Saalt, Shopify App Store, September 17, 2024 (`../competitors/peel-insights.md`)

**Theme: Single-source consolidation of customer view**
- "It's a game-changing dashboard for viewing sales-related data." — Matt B., Capterra, February 24, 2025 (`../competitors/putler.md`)
- "All my Woo sales and customer analytics consolidated in one place. Used every day for years. Fast and effortless sales research, powerful customer segmentation and data consolidation across WooCommerce, PayPal, and Stripe." — Fishbottle, wordpress.org plugin review (`../competitors/putler.md`)

**Theme: Segment depth across data sources**
- "exceptional reporting capabilities, transforming data visualization and streamlining business analytics effortlessly" — G2 review summary, 2025 (`../competitors/glew.md`)
- "Glew.io is solving the challenge of consolidating data from multiple platforms into a single source of truth by automating data integration and ensuring accuracy" — G2 review summary, 2025 (`../competitors/glew.md`)

## What users hate about this feature

**Theme: Activation paywalled / gated to upper tiers**
- Repeat Customer Insights gates Shopify customer-tag and Klaviyo push to Growth tier ($99/mo+); Entrepreneur ($59) users can analyze but not activate (inferred friction; `../competitors/repeat-customer-insights.md`).
- "Advanced analytics module" requires separate payment; "features that should be baked into the core product." — Sam Z., Capterra, December 2025 (`../competitors/klaviyo.md`) — Klaviyo's RFM is gated to the $100/mo Marketing Analytics add-on on top of the base Email plan.
- "There's no free plan with segmentation capabilities." — digismoothie.com, on Peel Insights (`../competitors/peel-insights.md`).
- "custom reports may seem restrictive, with an additional cost of $150 per hour for each one" — Shopify-store reviewer, on Glew (`../competitors/glew.md`).

**Theme: UI / cognitive load**
- "The dashboard can sometimes feel overwhelming with so many parameters." — Ekaterina S., Capterra, October 7, 2025 (`../competitors/putler.md`).
- "Reporting is clunky and the UI buries things that should be front and center." — Darren Y., Capterra, April 2026 (`../competitors/klaviyo.md`).
- "It's slow! It takes forever to load... Support is slow and useless" — Paul B., Manager, Retail, Capterra May 2024 (`../competitors/glew.md`).

**Theme: Export friction / activation downstream**
- "Can't export more than [a limited number of] customer records at once." — Nicolai G., Capterra, June 10, 2019 (`../competitors/putler.md`).
- "Users must export 'data dumps' rather than formatted views, making [analysis] time-consuming further Excel manipulation difficult." — smbguide.com paraphrasing user complaints (`../competitors/peel-insights.md`).
- "export very large records to CSV is a bit of issue" — yair P., Capterra, May 14, 2019 (`../competitors/putler.md`).

**Theme: Empty state for small/new stores**
- Predictive analytics gated by data thresholds (500+ customers with orders, 180+ days history, 3+ repeat purchasers) — small/new stores see empty CLV / churn cards (`../competitors/klaviyo.md`).

## Anti-patterns observed

- **Filter-builder without canonical grid as the default surface.** Glew and Metorik bury RFM as one of 55-500 filter dimensions; merchants get power but no spatial mental model and no "show me my best customers" answer in five seconds. Peel and Repeat Customer Insights make the grid the *home page*; the buried-filter pattern is the inverse.
- **Tile gallery with no axes.** Triple Whale's "6 already built segments" tiles in Lighthouse / AI Audiences ship as standalone cards rather than positioned on an R-vs-F+M grid. Strength: low-friction activation. Weakness: no diagnostic affordance — the merchant cannot see which segment any given customer belongs to or how cohorts shift.
- **No published cell-density encoding.** Universally observed: no competitor publicly documents heatmap intensity within their RFM grid. Peel explicitly *rejects* density encoding (`"the sections of the grid are not proportionally scaled to the percentage of customers"`). The "fixed cell, named region, click to drill" convention is what users expect.
- **No migration view.** All grid-based implementations (Peel, Repeat Customer Insights, Putler) show a *snapshot*. Only Klaviyo ships a Sankey of cohort migration over time. Without it, merchants can't answer "did my Champions just churn?" — the snapshot is silent on motion.
- **Buried behind a paywall.** Klaviyo's RFM is paywalled at $100/mo Marketing Analytics on top of base Email; Repeat Customer Insights' activation is at $99/mo Growth. The lowest entry tiers in the category surface RFM at all is Putler at $20/mo (every feature, every tier — single counter-example).
- **No segment library enumerated publicly.** Repeat Customer Insights claims `"30+ segments"` and `"150+ customer segments"` in marketing copy; the full segment library is never published. Buyers can't see what they're getting before signing up.

## Open questions / data gaps

- **Cell coloring rules for the 5×5 grid.** Peel, Repeat Customer Insights, Putler all describe colored or named cells but none publish exact tokens (hex, gradient direction, hover-state behavior). Confirmed in profiles — UI details for cell color and hover not available without a paid trial.
- **Putler's 6×6 = 36-cell layout details.** The 11 named segments overlay the matrix but the per-cell rendering (does each segment fill multiple cells? Is it irregular?) is not documented in public sources.
- **Repeat Customer Insights "Customer Grid History"** — described as tracking "how segment populations shift over time" but the visualization type (line chart? stacked area? Sankey?) is not specified in public docs.
- **Daasity's "Customer Movement & Historical Performance"** section is not enumerated visually in public sources; whether it's a Sankey analog to Klaviyo's, or a different visual, requires a Daasity demo.
- **Klaviyo's six-cohort mapping rule.** Klaviyo documents the 1-3 R/F/M scoring and the six fixed cohorts but does not publish the explicit `(r,f,m) → cohort` lookup table; the mapping is documented narratively (Champions = 333, Inactive = 111) without enumerating all 27 triplets.
- **Lifetimely's RFM treatment.** Public sources describe RFM-like filtering on cohort/LTV reports but no named "RFM" surface — whether a dedicated grid exists behind the login is unverified.
- **Lebesgue.** No RFM surface observed in public profile material; whether the product has one at all is unconfirmed.

## Notes for Nexstage (observations only — NOT recommendations)

- **The 5×5 grid with named cells is table-stakes** for any retention-focused customer-segmentation product (Peel, Repeat Customer Insights, Putler all ship it; native Shopify and Woo ship nothing). Klaviyo's six-cohort design is the deliberate counter-example, justified on their engineering blog as a research-driven simplification.
- **Champions / Loyal / At Risk / Hibernating / Lost is the canonical naming convention** across Klaviyo (6 names), Peel (10 names), Putler (11 names), Repeat Customer Insights (30+ names) — deviating from these terms creates a learning-curve tax for users coming from Klaviyo or Repeat Customer Insights.
- **Cell-size encoding is a deliberate UX decision.** Peel explicitly fixes cell size and documents *why* (stable spatial mental model > population encoding). Putler colors cells by segment identity, not density. No public competitor encodes density in cell area or saturation. The norm is "named region, fixed size, hover for count."
- **Migration / Sankey is Klaviyo's differentiator.** It is the only "motion" visual in the category — every other implementation is a snapshot. If Nexstage wants RFM to be storytelling rather than diagnostic, the migration ribbon is the bar.
- **Letter-grade A–F (Repeat Customer Insights only) is unique** as an operator-readable single-glyph customer-health summary. Recency-weighted (50/25/25). No other competitor surfaces customer health as a school-style grade.
- **Activation is the universal exit path.** Every implementation surfaces a "push to" flow (Klaviyo, Shopify customer tag, Meta Custom Audience, Attentive, Postscript, Mailchimp, CSV). The grid is the *what*; the audience push is the *what now*. Tools that paywall activation behind upper tiers (Repeat Customer Insights at $99, Klaviyo at $100 add-on) draw consistent friction complaints.
- **Quintile bucketing is relative to the store's own customer base, not to a global benchmark.** Universal across implementations. Klaviyo uses 1-3 instead of 1-5 (research-justified to compress 125 cells to 27 → 6 cohorts).
- **Daily auto-recompute** is the documented cadence for the only competitor (Repeat Customer Insights) that publishes one. Klaviyo's predictive CLV retrains weekly. Aligns with `daily_snapshots` cadence in Nexstage's data model — RFM scores would naturally fit alongside LTV at the customer level.
- **No competitor blends RFM with paid-source attribution.** Repeat Customer Insights goes furthest with `order.source_name` channel filter (3-41 channels by tier). None of the grid-based tools surface "RFM × Meta-acquired customers" as a primary lens. Direct gap for Nexstage's 6-source thesis if customer-level source attribution were exposed alongside the grid.
- **Empty state thresholds are honest but unfriendly.** Klaviyo gates predictive features behind "500+ customers with orders, 180+ days history, 3+ repeat purchasers"; small new stores see blank cards. Worth thinking about explicit empty-state design — RFM falls apart with <50 customers.
- **Putler's compression to 11 named segments at 6×6 is a simplification path** — the alternative to either 5×5 (25 cells) or six-cohort (Klaviyo). Possible middle ground if Nexstage wants more granularity than Klaviyo without Repeat Customer Insights' "30+" sprawl.
- **WooCommerce native ships zero of this** (`../competitors/woocommerce-native.md` explicitly: `"no scoring/RFM segmentation"`). Nexstage's Woo support is a structural opening — the only Woo-native option for RFM today is Metorik's filter-builder, which has no spatial grid.
- **Triple Whale's RFM lives in the alerts/audience inbox**, not in a customer-analytics screen. That's a different IA philosophy (RFM as activation primitive, not analytical surface) — relevant if Nexstage's customer surfaces ever fold into an alerts-driven UI.
