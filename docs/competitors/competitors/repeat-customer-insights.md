---
name: Repeat Customer Insights
url: https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights
tier: T3
positioning: Customer-retention analytics for CPG / consumables Shopify merchants — RFM grading, cohorts, and 30+ auto-segments synced to Shopify tags or Klaviyo.
target_market: SMB Shopify CPG / consumables brands (US-centric customer list — MANA, Pier 1, Prolon, Steven Smith Teamaker, Teabox, The Sock Drawer, Package Free, Pacas, Andie). No Woo / multi-platform.
pricing: $59 / $99 / $249 per month, 14-day free trial. Scale axis = sales-channel breadth, history depth, and feature gating (tagging on Growth+, API on Peak).
integrations: Shopify (only ingest), Shopify customer tags (push), Klaviyo (push), Recharge / CartHook / Zipify / Subscriptions / eBay / Amazon / Facebook acquisition-source segmentation (read-only via Shopify order source_name).
data_freshness: unknown — marketed as "automatic data import"; daily-grade adjustment described ("automatically adjust as needed as new customer behavior comes in every day"). No real-time claim.
mobile_app: no — web-responsive admin only.
researched_on: 2026-04-28
sources:
  - https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights
  - https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights/pricing/
  - https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights/features/
  - https://apps.shopify.com/repeat-customer-insights
  - https://apps.shopify.com/repeat-customer-insights/reviews
  - https://www.littlestreamsoftware.com/articles/how-customer-segmentor-uses-rfm-segmenting-for-shopify-stores/
  - https://www.littlestreamsoftware.com/articles/grading-shopify-customers-rfm-segmentation/
  - https://www.littlestreamsoftware.com/articles/how-the-rfm-analysis-scores-customer-behavior-from-1-to-5/
  - https://www.littlestreamsoftware.com/articles/how-rfm-is-used-by-the-customer-grid-to-segment-customers-into-behavior-groups/
  - https://www.littlestreamsoftware.com/articles/how-repeat-customer-insights-creates-30-segments-automatically-in-shopify/
  - https://www.littlestreamsoftware.com/articles/trends-with-customer-grades/
  - https://www.littlestreamsoftware.com/articles/measuring-how-the-products-in-the-first-order-influence-customer-repurchases/
  - https://www.littlestreamsoftware.com/articles/using-the-cohort-revenue-report-to-see-how-your-customers-are-buying-over-time/
  - https://www.littlestreamsoftware.com/knowledge-base/
  - https://reputon.com/shopify/apps/analytics/repeat-customer-insights
---

## Positioning

Repeat Customer Insights is a niche, single-developer Shopify app (built since 2016 by Eric Davis at Little Stream Software in Henderson, NV) that markets itself as "Customer analysis for CPG Shopify stores." It exists to do one thing well: take a Shopify store's order/customer history and turn it into RFM grades, behavior segments, cohort tables, and product-influence reports — with an explicit retention-vs-acquisition narrative ("focus on [loyal customers] to grow your revenue and save on customer acquisition"). It does not touch ad data, attribution, GA4, or GSC; it replaces nothing on the paid-media side and instead competes adjacent to retention-analytics tools (Lifetimely, Repeat, Reorder).

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Entrepreneur | $59/mo | Store-wide metrics + weekly email; RFM segmenting with A-F grades; Customer Grid auto-segmenting; "Focus Pages dedicated to specific problem areas"; Cohort Report (last 12 monthly cohorts); Order Sequencing Analysis (2 yrs); First Product Analysis (top 100 products only); date drill-down: all-time + current/previous year; segment by 3 Shopify acquisition sources; CSV export | Need full cohort history or want to push tags back into Shopify |
| Growth | $99/mo | All Entrepreneur + customer tagging (push to Shopify/Klaviyo); full historic cohort report; date drill-down: all-time + last 4 years + 5 years of quarters; segment by 11 acquisition sources (incl. Draft Orders, Subscriptions, CartHook, eBay, Facebook, Recharge, Amazon, Zipify); First Product Analysis for all products + variants; variant + product/variant reorder analysis | Want quarterly drilldowns going back further, or need API access |
| Peak | $249/mo | All Growth + historic store-analysis drilldowns; historic order-sequencing analysis; date drill-down: annualized yearly + every quarter; segment by 41–50 acquisition sources; "Premium support and advice"; "API access to analyzed data and reports (in beta)" | Multi-channel CPG brand needs full per-channel segmentation + advisory |

All tiers carry a 14-day free trial. No customer- or order-volume caps are published; pricing is flat per tier and billed via Shopify. There is no free tier and no annual discount mentioned.

## Integrations

**Sources (ingest, all read-only):**
- **Shopify** — orders, customers, products/variants, order `source_name` for channel attribution. The whole product is a Shopify-only ingest; the homepage states "automatic data import from Shopify."
- No Meta Ads, Google Ads, GA4, GSC, TikTok, Klaviyo metrics, Pinterest, Amazon ads, or any cost data. There is no COGS / margin module observed.

**Destinations (push):**
- **Shopify customer tags** — segment membership written back as Shopify customer tags (Growth tier+).
- **Klaviyo** — segment membership pushed for use in flows/segments. Marketing copy: "automatically builds customer segments… and sends that data to Shopify via tagging or Klaviyo for an integrated view."

**Coverage gaps relative to Nexstage:** no ad data, no GA4, no GSC, no Woo, no margin/COGS, no LTV-against-CAC because there is no CAC source. Multi-store is supported via "Linked Accounts" but treated as a back-office feature, not a workspace primitive.

## Product surfaces (their app's information architecture)

Aggregated from the features page, knowledge base, and pricing-tier feature lists. The app is a single Shopify-embedded admin (URL: `repeat-customer-insights.littlestreamsoftware.com`).

- **Store Analysis** — store-wide metrics + benchmarking; "storewide metrics and analyses to be confident you're heading in the right direction." This is the home/landing dashboard.
- **Customer Segmenting (RFM)** — the auto-segmentation engine; lists "150+ customer segments" in marketing copy and "30+" named segments in the deeper docs.
- **Customer Grid** — the flagship visualization: a 5×5 grid of two RFM dimensions; customers placed by paired score; segment names like "Loyal" / "Potential Loyal" / "Promising New" overlaid on the cells.
- **Customer Grading Report** — A-F grade roll-up per customer; recency-weighted; used as "a quick visual indicator."
- **Customer Grid History** — tracks how segment populations shift over time.
- **Cohort Analysis / Cohort Revenue Report** — month-acquired cohorts × elapsed-month columns; cell = revenue from that cohort that month.
- **Order Sequencing Analysis** — order-position playback (1st, 2nd, 3rd order, etc.) showing behavior-shift inflection points across customer lifetime.
- **First Product Analysis (Nth Product Analysis)** — for each product first-ordered, shows Total LTV and Repeat Purchase Rate of customers whose first order included it.
- **Product Reorders / Variant Reorder Analysis** — which SKUs/variants are repeatedly purchased by loyal customers.
- **Customer Purchase Latency** — interval distribution between repeat orders.
- **Focus Pages** — the pricing page describes these as "dedicated to specific problem areas." The features page enumerates them by problem-name rather than dashboard-name:
  - **Who Are Loyal** — "Find who your more loyal customers are so you can better retain them in the long-term."
  - **1-to-2 Customer Analysis** — second-order conversion strategy view.
  - **Average Customer Analysis** — typical ordering patterns.
  - **Winter Holiday** — seasonal-vs-baseline comparison.
  - **Downturn** — efficiency view for surviving slow periods.
- **Metrics** — raw metrics catalog "available for independent analysis."
- **Customer Export** — CSV export with email delivery.
- **Email Subscriptions** — daily/weekly/monthly digest delivery for Customer Grades, Order Sequencing, Cohorts, and Customer Exports.
- **Linked Accounts** — multi-store management.
- **API** (Peak only, "in beta") — programmatic access to analyzed data.

This gives ~14–17 distinct surfaces, but the IA is flat: most are reports under a single sidebar, not a layered hub-and-spoke product.

## Data they expose

### Source: Shopify
- **Pulled:** orders, line items, customers, products, variants, order `source_name` (used as the channel/acquisition-source dimension — the basis for the "3/11/41 sales channels" tier gating).
- **Computed:**
  - Recency / Frequency / Monetary scores (1–5 quintiles per dimension, relative to that store's other customers — see scoring section below).
  - Combined RFM score string (e.g., 515 = R5 F1 M5).
  - Letter grade A–F (recency-weighted summary of the 3 RFM digits).
  - Customer Grid placement (one cell on a 5×5 grid using two of the three RFM scores).
  - 30+ named auto-segments (intersections like RF, FM, RM, plus full RFM for some customers; each customer typically lands in 3–4 segments).
  - LTV per customer; AOV; Repeat Purchase Rate (RPR); customer purchase latency.
  - Cohort revenue: cohort_month × elapsed_month grid with cumulative lifetime total column.
  - First Product Analysis: per-first-product Total LTV and Repeat Purchase Rate.
  - Order sequencing: per-order-position behavior across customer lifetime.
- **Attribution windows / lookbacks:** order-level grouping by `source_name` only. No multi-touch attribution. Lookback depth is gated by tier — 12 months of cohorts on Entrepreneur, full history on Growth/Peak; 2 years of order sequencing on Entrepreneur, 5 years on Growth.

There are no other ingest sources. There is no ad-platform data, no GA4, no GSC, no Klaviyo metrics flowing back in, no margin/COGS, no inventory.

## Key UI patterns observed

UI details below are pieced from marketing screenshots (referenced but not directly accessible without an install), the features page prose, and the Ilana Davis case study mention that "the redesigned dashboard uses a layout similar to Shopify's standard admin panel… with a 1×4 grid featuring context and charts." The app is Shopify-embedded; no public deep-link screenshots beyond hero images on the marketing pages.

### Store Analysis (home dashboard)
- **Path/location:** Default landing page after install.
- **Layout (prose):** Per the Ilana Davis redesign reference, the dashboard adopts Shopify Polaris-style framing with a "1×4 grid featuring context and charts." The marketing page describes this as "storewide metrics and analyses." Beyond that, public sources do not show the full canvas.
- **UI elements (concrete):** UI details not available — only feature description seen on the marketing page; the redesign source confirms Polaris-aligned cards in a 1×4 grid but does not enumerate which 4 cards.
- **Interactions:** Date drill-down selector (all-time / current year / previous year on Entrepreneur; quarterly + 4-year history on Growth; per-quarter + annualized on Peak). Email-digest subscription toggle.
- **Metrics shown:** Store-wide metrics including (named on marketing pages) AOV, LTV, Repeat Purchase Rate, plus benchmarking against industry and against the store's own historical baseline.
- **Source/screenshot:** https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights (hero) and the Ilana Davis case-study reference (no direct image fetched).

### Customer Grid
- **Path/location:** Sidebar > Customer Grid (one of the named reports in the features list).
- **Layout (prose):** A 5×5 matrix. Two of the three RFM dimensions form the axes (the docs describe Recency × Monetary as one example, and RF / FM / RM as the three available pairings). Each axis is scored 1–5. Each of the 25 cells maps to a behavior segment; the docs say behavior segments collapse the 125 RFM permutations into ~30 named groups across the three pairings combined. Customers are placed in one cell per grid based on their paired scores; the article example places "a customer with 1 Recency and 5 Monetary in the bottom row (Recency = 1) and the right-most column (Monetary = 5)."
- **UI elements (concrete):** Cells are clickable: per the docs, "If you then click on that segment name, you'll see details about that segment as well as advice on how to market to them." Beyond that, cell coloring, density visualization, and customer counts per cell are not described in public material.
- **Interactions:** Click a cell to drill into the segment detail page, which contains both segment description and prescriptive marketing advice. Push-to-Shopify-tag and push-to-Klaviyo are surfaced from segment views (Growth tier+).
- **Metrics shown:** RFM scores 1–5 on each axis; segment names (examples: "Loyal," "Potential Loyal," "Promising New" — full 30+ list not published).
- **Source/screenshot:** https://www.littlestreamsoftware.com/articles/how-rfm-is-used-by-the-customer-grid-to-segment-customers-into-behavior-groups/

### Customer Grading Report
- **Path/location:** Sidebar > Customer Grading.
- **Layout (prose):** Per-customer letter A–F. Grades are derived from the three RFM digits with explicit weighting: "Recency is the most powerful factor so it makes up the majority of the letter grade." Doc example: "a score of 435 would probably be a B customer."
- **UI elements (concrete):** Grades described as "a quick visual indicator." No color spec, no badge style described in public material.
- **Interactions:** Grades "automatically adjust as needed as new customer behavior comes in every day" — implying daily refresh. Email subscription available for grade-change digests. Trend interpretation is editorial: the article on grade trends recommends watching distribution shifts ("Seeing a lot of C's and equal A's and F's? Sounds like things are stable and maybe a bit stagnant").
- **Metrics shown:** Letter grade A–F; underlying RFM triplet (e.g., 515).
- **Source/screenshot:** https://www.littlestreamsoftware.com/articles/grading-shopify-customers-rfm-segmentation/, https://www.littlestreamsoftware.com/articles/trends-with-customer-grades/

### Cohort Revenue Report
- **Path/location:** Sidebar > Cohorts.
- **Layout (prose):** Classic cohort triangle. Rows = cohort acquisition month (e.g., "2014-12"). Columns = elapsed months since first order (Month 0, Month 1, …). Each cell = revenue that cohort generated in that elapsed month. Final column = lifetime cohort revenue. Doc example: "2014-12 cohort shows $1,098.80 in orders in December of 2014 (Month 0), then $169.92 in Month 1 (January 2015)… $4,392.09 across all months."
- **UI elements (concrete):** "A month will be blank if there were no orders for that cohort in that month or if the date is in the future." The article explicitly does not describe color coding (no documented heatmap intensity in public sources).
- **Interactions:** Tier-gated lookback — Entrepreneur is capped at 12 monthly cohorts; Growth/Peak unlock all historic cohorts.
- **Metrics shown:** Revenue per cohort × elapsed month; lifetime cohort total.
- **Source/screenshot:** https://www.littlestreamsoftware.com/articles/using-the-cohort-revenue-report-to-see-how-your-customers-are-buying-over-time/

### First Product Analysis (Nth Product Analysis)
- **Path/location:** Sidebar > Product Analysis > First Product.
- **Layout (prose):** Tabular. Each row = a product (or variant on Growth+). Per row, two headline metrics: Repeat Purchase Rate and Total LTV. Doc example: "10 customers ordered a red shirt at $10 each. Then 5 of those customers came back and bought something else for $20… 50% Repeat Purchase Rate (5 of 10 made a second purchase)… and $200 Total LTV."
- **UI elements (concrete):** Limit on Entrepreneur tier: top 100 products only; Growth/Peak unlock all products + variants.
- **Interactions:** Scope filter for products vs variants (Growth+). Sort/filter UI not detailed publicly.
- **Metrics shown:** Repeat Purchase Rate, Total LTV.
- **Source/screenshot:** https://www.littlestreamsoftware.com/articles/measuring-how-the-products-in-the-first-order-influence-customer-repurchases/

### Focus Pages (Who Are Loyal, 1-to-2, Average Customer, Winter Holiday, Downturn)
- **Path/location:** Sidebar > Focus Pages.
- **Layout (prose):** Each Focus Page is a problem-scoped dashboard rather than a generic report — the pricing page calls them "Focus Pages dedicated to specific problem areas." Each page bundles relevant metrics and prescriptive advice for one merchant question (who is loyal, how to convert 1st→2nd order, how to compare holiday cohorts vs baseline, how to operate efficiently in a downturn, what an average customer looks like).
- **UI elements (concrete):** UI details not available — only feature descriptions seen on marketing page. No screenshots accessible publicly.
- **Interactions:** Unknown beyond the implied date-range scoping shared with the rest of the app.
- **Metrics shown:** Varies per page; not enumerated publicly.
- **Source/screenshot:** https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights/features/

### Order Sequencing Analysis
- **Path/location:** Sidebar > Order Sequencing.
- **Layout (prose):** Per-order-position view ("playback of past orders"). Tracks how customer behavior changes across order #1, #2, #3, … and identifies "loyalty inflection points." Lookback gated: 2 years on Entrepreneur, 5 years on Growth, full history on Peak.
- **UI elements (concrete):** Not described in public sources.
- **Interactions:** Email-digest subscription supported.
- **Metrics shown:** Behavior shifts by order position (specific metric list not published).
- **Source/screenshot:** https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights/features/

### Auto-segment push (Shopify tags / Klaviyo)
- **Path/location:** Embedded in segment views; gated to Growth tier+.
- **Layout (prose):** Toggle/sync action that writes the membership of an auto-segment to Shopify customer tags and/or to a Klaviyo list/segment. Marketing copy: "automatically builds customer segments so your marketing hits the right customers at the right time and sends that data to Shopify via tagging or Klaviyo."
- **UI elements (concrete):** Specific UI not shown publicly — feature mentioned only as a tier capability.
- **Interactions:** Membership presumably refreshes daily alongside grade recompute (implied, not documented).
- **Metrics shown:** N/A — control surface, not a report.
- **Source/screenshot:** Pricing page tier gating; https://www.littlestreamsoftware.com/shopify-apps/repeat-customer-insights/pricing/

## What users love (verbatim quotes, attributed)

Limited reviews available — only 14 reviews on the Shopify App Store, all 5-star. Quotes pulled from Shopify App Store and Reputon's mirrored review feed.

- "COHORT ANALYSIS automatically created from your store is unbelievable and a must-have for any startup focused on growth." — pantys (Brazil), Shopify App Store, June 14, 2019
- "This app is a game-changer when it comes to diving into customer data. Not only that, but the customer service from the developer is amazing." — The Sock Drawer (United States), Shopify App Store, February 16, 2021
- "Repeat customer insights is a great tool that we use to better understand cohort data and segmentation. Eric, the founder is extremely responsive." — Package Free (United States), Shopify App Store, May 22, 2020
- "The data is there but to have an app quickly show us the results with the ability to dig deeper, has been a game changer." — MadeOn Skin Care, Shopify App Store, July 5, 2019
- "Great app to keep track of your customer cohorts and stay on top of LTV." — Pacas (United States), Shopify App Store, May 15, 2023
- "A must-have app for generating business insights and understanding customer loyalty / repeat shopping behavior beyond basic Shopify analytics." — 8020nl (United States), Shopify App Store, April 12, 2018
- "Really well-thought out app by an excellent developer." — The Reluctant Trading Experiment (United States), Shopify App Store, December 28, 2018
- "Very great and easy to use app — awesome contact!! Helpful insights!" — Inno Nature (Germany), Shopify App Store, June 11, 2018
- "This is a great app and Eric in particular is doing a great job helping us get the very most out of it!" — Andie (United States), Shopify App Store, September 23, 2019
- "Excellent, quick customer service!" — Human Unlimited (United States), Shopify App Store, July 31, 2018
- "Developer is super responsive if you need anything." — Kindred Bravely (United States), Shopify App Store, September 1, 2017

## What users hate (verbatim quotes, attributed)

No critical reviews observed in public sources. The app sits at 5.0/5 across all 14 Shopify App Store reviews; G2, Capterra, TrustRadius, and Trustpilot listings are not present (the product is too small for those panels). Reddit search returned no threads specifically critiquing Repeat Customer Insights — DTC discussion in r/shopify and r/ecommerce centers on larger players (Lifetimely, Triple Whale). Limited reviews available; absence of negative quotes likely reflects sample size and niche audience rather than universal satisfaction.

Inferred friction points from feature gating (NOT from user quotes — flagged as observations only):
- Customer tagging is paywalled at Growth ($99/mo); Entrepreneur users can see segments but cannot push them.
- Sales-channel breadth is rationed across tiers (3 / 11 / 41), which forces multi-channel CPG brands toward Peak.
- API access is Peak-only ($249/mo) and labeled "in beta."

## Unique strengths

- **Single tightly-defined vertical (CPG / consumables on Shopify) executed by one developer** — feature scope and language ("survive downturns," "winter holiday," "1-to-2 conversion") all read as written for a CPG operator, not a generic ecommerce SaaS.
- **The A–F letter grade is unusually approachable** — recency-weighted single-letter summary of an RFM triplet; no other competitor in the analytics tier surfaces customer health as a single school-style grade. The trend article ("Seeing a lot of C's and equal A's and F's? Sounds like things are stable and maybe a bit stagnant") makes it operator-readable rather than analyst-readable.
- **Customer Grid is a 5×5 spatial UI for RFM rather than a sortable table** — explicit two-axis 1–5 grid with cell-level segment naming (~30 named groups across the three RFM pairings) and click-through to per-segment marketing advice.
- **Push-to-tag is the activation layer** — Shopify customer tags + Klaviyo, gated at Growth, turn the tool from analytics-only into a workflow trigger; segments become marketable lists.
- **Founder-led support is a category leader for niche tools** — every review references Eric Davis by name. Multiple reviews single him out for daily marketing emails ("Eric's Daily Shopify Tips") and direct responsiveness.
- **Quintile-based RFM is honestly explained**: "5: Top 20% / 4: Top 21–40% / 3: Middle 20% / 2: Bottom 21–40% / 1: Bottom 20%" — explicit relative-to-this-store percentile method, which some competitors hide.
- **Lookback depth as a paid axis** — pricing tiers gate cohort history (12 months → all history) and order sequencing (2 → 5 → all) instead of capping by orders/customers.

## Unique weaknesses / common complaints

- **Shopify-only.** No Woo, no platform-agnostic SaaS posture.
- **Single ingest source.** No ad data, no GA4, no GSC. The product cannot blend retention with acquisition in any way except via Shopify's `source_name` channel string.
- **No COGS/margin module.** All metrics are revenue-based; net profit is not computed.
- **Tiny review surface.** 14 reviews after ~10 years on the Shopify App Store implies a small install base — risk for buyers needing assurance / community.
- **No mobile app** and no published real-time freshness claim — described only as automatic daily updates.
- **Tagging gated behind $99/mo tier.** Free-tier users can analyze but not activate.
- **API is Peak-only and "in beta"** — programmatic access is locked at $249/mo and not GA.
- **30+ auto-segments and "150+ customer segments" are quoted but never enumerated in public docs** — buyers can't see the segment library before signing up.
- **No public deep dashboard screenshots beyond marketing hero images** — UX is opaque without a trial install.

## Notes for Nexstage

- **The A–F letter grade is a packaging idea worth studying.** Nexstage doesn't have a comparable customer-health single-glyph summary. The recency-weighted grade collapses three quintile scores into one operator-readable indicator, with daily auto-recompute. Worth noting for any future customer-health surface — the metric dictionary doesn't currently catalog this.
- **The Customer Grid (5×5 RFM matrix with named segments + click-through to marketing advice) is a different paradigm from sortable customer tables.** Most retention-analytics competitors render RFM as filters on a customer list; this one renders it as a spatial 25-cell matrix. Relevant to any segmentation UI we eventually build.
- **Push-to-tag (Shopify) + push-to-Klaviyo as the only "destinations"** — RCI is a one-way write to two systems and otherwise read-only. Nexstage's surface is broader, but the pattern of "write segment back to Shopify customer tags" is a known SMB-Shopify expectation we should be aware of.
- **Acquisition-source segmentation is read off Shopify `order.source_name`**, gated by tier (3 / 11 / 41 channels). This is a degenerate channel-attribution model — no UTMs, no GA4, no MMM. Useful as the floor of "what the cheapest competitor does"; our ChannelMappings-based model is materially richer.
- **Focus Pages = problem-named dashboards rather than dimension-named dashboards.** Their nav has surfaces like "Who Are Loyal," "1-to-2 Customer Analysis," "Downturn," "Winter Holiday" — not "Customers," "Orders," "Cohorts." This is a different IA philosophy from Nexstage's source-/lens-based navigation; worth flagging for the IA decision record.
- **Pricing scales by lookback depth and by acquisition-source breadth, not by order volume.** Different from most analytics SaaS pricing axes (per-order, per-revenue, per-seat). Notable for our own pricing decision.
- **Niche vertical positioning ("CPG Shopify stores") is the entire wedge.** Even with one developer and 14 reviews over a decade, the language and feature set are visibly tuned to consumables — Winter Holiday Focus Page, "1-to-2" conversion vocabulary, latency analysis (reorder cadence is the CPG core metric). Nexstage's broader ecommerce SMB positioning means we won't speak this dialect natively; not a flaw, just a positioning observation.
- **Cohort report is described without any color-coding spec in public sources** — they rely on raw numeric cells in a triangle. Nexstage's existing cohort UI choices (heatmap intensity, etc.) should be cross-referenced against this minimal-baseline competitor.
- **No screenshots accessible without install.** Marketing pages reference imagery but the only public UI hint is the Ilana Davis redesign note about a Polaris-style "1×4 grid featuring context and charts" on Store Analysis. If deeper UI capture is needed, an actual install on a dev store is the only path.
