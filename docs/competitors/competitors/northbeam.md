---
name: Northbeam
url: https://www.northbeam.io
tier: T1
positioning: First-party multi-touch attribution + media mix modeling for mid-market and enterprise DTC brands ($50k+/mo paid media); replaces in-platform reporting and tools like GA4 / Triple Whale at the upper end.
target_market: Mid-market to enterprise DTC ecommerce; Shopify-native plus "any ecommerce platform" on Professional+; brands spending $50k–$500k+/mo across 3+ paid channels.
pricing: Starter $1,500/mo (under $1.5M annual media spend); Professional and Enterprise are custom (eligibility tied to $250k/mo and $500k/mo media spend respectively). Volume axis is pageviews tracked.
integrations: Shopify, Meta/Facebook, Google Ads, Microsoft Advertising, TikTok, Snapchat, Pinterest, Amazon Ads, X/Twitter, Yahoo, Klaviyo, The Trade Desk, Criteo, AdRoll, MediaMath, MNTN, Tatari, Rakuten, Grin, Impact. Magento2 has its own custom path (per reviews).
data_freshness: hourly (per multiple reviews; "updates much more regularly (every hour)")
mobile_app: no (web-responsive only; mentioned as a limitation in reviews)
researched_on: 2026-04-28
sources:
  - https://www.northbeam.io
  - https://www.northbeam.io/pricing
  - https://www.northbeam.io/integrations
  - https://www.northbeam.io/features/creative-analytics
  - https://www.northbeam.io/features/sales-attribution
  - https://www.northbeam.io/features/profit-benchmarks
  - https://www.northbeam.io/features/apex
  - https://www.northbeam.io/features/overview-page
  - https://www.northbeam.io/products/mmm-plus
  - https://www.northbeam.io/case-study/gruns-power-play-how-northbeam-became-the-daily-fix-for-a-fast-growing-brand
  - https://www.northbeam.io/product-news/announcing-metrics-explorer-correlation-analysis-in-northbeam
  - https://docs.northbeam.io/docs/northbeam-30
  - https://docs.northbeam.io/docs/navigating-northbeam
  - https://docs.northbeam.io/docs/overview-page
  - https://docs.northbeam.io/docs/attribution-page
  - https://docs.northbeam.io/docs/creative-analytics
  - https://docs.northbeam.io/docs/product-analytics
  - https://docs.northbeam.io/docs/what-is-northbeam-model-comparison-tool
  - https://docs.northbeam.io/docs/attribution-models
  - https://docs.northbeam.io/docs/attribution-windows
  - https://docs.northbeam.io/docs/credit-allocation-examples
  - https://docs.northbeam.io/docs/metrics-explorer-quickstart-guide
  - https://docs.northbeam.io/docs/northbeam-metrics-101
  - https://docs.northbeam.io/docs/manage-breakdowns
  - https://docs.northbeam.io/docs/paid-social-team
  - https://docs.northbeam.io/docs/northbeam-apex
  - https://www.g2.com/products/northbeam/reviews
  - https://www.capterra.com/p/10003962/Northbeam/
  - https://www.trustpilot.com/review/northbeam.io
  - https://www.headwestguide.com/tools/northbeam
  - https://aazarshad.com/resources/northbeam-review/
  - https://www.attnagency.com/blog/northbeam-shopify-review
  - https://workflowautomation.net/reviews/northbeam
  - https://www.smbguide.com/review/northbeam/
  - https://saleshive.com/vendors/northbeam/
  - https://research.com/software/reviews/northbeam
  - https://www.businesswire.com/news/home/20240814907548/en/Northbeam-Announces-Apex-New-Integration-with-Meta-to-Improve-Ad-Performance
---

## Positioning

Northbeam markets itself as "the marketing intelligence platform for profitable growth" and positions multi-touch attribution as its flagship — explicit taglines on the homepage are "MTA, perfected" and "Better data, better marketing." The pitch is independence from in-platform reporting: a first-party pixel + device graph that decides credit allocation outside Meta/Google's self-reporting, paired with media mix modeling (MMM+) and an Apex bridge that pushes Northbeam-attributed signal back into ad-platform algorithms. Public reviews position it firmly as enterprise/mid-market: "purpose-built for DTC brands spending $50,000+/month on paid media across three or more channels" (Head West Guide, 2026), with multiple reviewers calling it "overkill" or "expensive" for SMB. They claim 800+ companies and >$25B in tracked ad spend.

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Starter | $1,500/mo (per current pricing page; older reviews cite $999/$1,000) | Shopify direct integration; Multi-Touch Attribution; Clicks + Deterministic Views; Apex; Creative Analytics; Correlation Analysis. Monthly billing, billed by data volume. | Annual media spend approaches $1.5M, or need for non-Shopify platforms / flat-rate annual billing. |
| Professional | Custom (third-party reviews quote ~$2,500/mo) | All Starter + enhanced correlation analysis + Media Strategy support. Any ecommerce platform. Predictable flat-rate annual billing. Optional addon: multi-region instances. | Media spend exceeds $250k/mo; need MMM+ or Metrics Explorer. |
| Enterprise | Custom (undisclosed; reviews suggest $5k–$21k+/mo at high pageview volumes) | All Professional + MMM+, enhanced data exports, Metrics Explorer, advanced touchpoint-level exports. Optional addons: multiple instances, highest-frequency data refresh. | $500k+/mo in media spend, multi-region operations, data warehouse needs. |

Pricing is opaque: only Starter has a published number, and Professional/Enterprise require sales contact. The pricing axis is **pageviews tracked** — "what you pay is determined by the amount of data volume Northbeam tracks" (pricing page). No free trial. Reviewers note Northbeam typically requires "3 months upfront" payment and that recently "all support [was stripped] from the platform for clients who pay up to $1k/month, including onboarding" (Capterra reviewer Joey B., Nov 2023).

## Integrations

**Source platforms (data pulled in):**
- Ad platforms: Meta/Facebook, Google Ads, Microsoft Advertising, TikTok, Snapchat, Pinterest, Amazon Ads, X/Twitter, Yahoo, AdRoll, Criteo, MediaMath, MNTN (CTV), Tatari (CTV/podcast), The Trade Desk, Rakuten, Impact, Grin (influencer), Axon by AppLovin
- Ecommerce: Shopify (direct on Starter); "any ecommerce platform" on Professional+ (Magento2 mentioned in user reviews; WooCommerce noted in third-party reviews but not on the official integration page)
- Email/retention: Klaviyo
- First-party pixel: Northbeam's own pixel deployed on the storefront, plus an in-house device graph

**Destination (push-back):**
- Apex pushes ad-level attributed performance back into Meta and Axon by AppLovin so the platforms' bidding/optimization can consume Northbeam's MTA signal as the source of truth.

**Coverage gaps observed in public sources:**
- **GA4** is not listed as an integration; GA4 is positioned as something Northbeam replaces ("attribution model was dismal compared to Google Analytics 4" — critical Trustpilot quote), not augments.
- **Google Search Console (GSC)** is not listed at all. Northbeam has no organic-search-query layer.
- TikTok, Pinterest, Snapchat are listed for ads but creative analytics is also supported on Facebook, Instagram, Snapchat, TikTok, Google, Pinterest, YouTube.
- No Shopify App Store listing observed in research (Northbeam connects via OAuth/API, not app marketplace).

## Product surfaces (information architecture)

The Northbeam 3.0 nav is built around three "home" pages plus several specialized tools, with a left-sidebar pattern and a top-right hamburger that hides admin/utility surfaces. Day 1 vs Day 30/60/90 gating is a real product concept (features unlock as the model trains).

- **Overview Home Page** — "first page you see when you log into Northbeam"; customizable KPI tile dashboard ("design your perfect dashboard to see your entire business in one view") for executives and daily glance.
- **Attribution Home Page** — central hub for media buyers; default view is "Attribution: Revenue" with sections for Sales, New Customers, Returning Customers, Top of Funnel (Demand Capture), Bottom of Funnel (Demand Generation), Organic and Owned Media. Tabs across the top: Sales / Product Analytics / Orders / Creative Analytics. Right rail shows a Profitability panel that activates only after the 90-day learning period.
- **Metrics Explorer** — accessed via "telescope icon on the navigation bar on the left"; correlation-analysis dashboard built around the Pearson Correlation Coefficient, designed for non-click channels (TV, podcast, offline) and cross-channel effects.
- **Creative Analytics** — cross-platform creative grid with thumbnails + metrics on a red-to-green color scale, supporting Facebook, Instagram, Snapchat, TikTok, Google, Pinterest, YouTube.
- **Product Analytics** — scatterplot of products / platforms / campaigns / ads on a ROAS Index vs CAC Index plane, with bubble size = spend.
- **Orders** — tab inside Attribution; raw order-level browse (UI not deeply documented in public sources).
- **Model Comparison Tool** — side-by-side comparison of Northbeam's seven attribution models; accessed via the "hamburger icon on the top right of your Northbeam dashboard next to the maintenance alerts icon."
- **Breakdowns Manager** — accessed from the same top-right ☰; lets users define and override channel/category/targeting/revenue-source groupings (e.g. Paid Prospecting, Performance Max, Branded Search, Online Store vs Amazon).
- **Saved Views** — per-dashboard state save/share functionality (most prominent in Creative Analytics: "Copy dashboard view link to clipboard").
- **Apex** — Settings > Account > Apex; configuration surface for pushing attributed signal back to Meta/Axon. Not a dashboard — it's a connection panel with a "✅ green check" status indicator.
- **MMM+** — separate product/upsell on Enterprise; "browser-based, customizable dashboards" with budget-mix scenarios and forecasts; described as the "industry's first self-service MMM dashboard."
- **Profit Benchmarks** — unlocks at Day 90; computes target ROAS / MER / CAC against actual contribution margins and shows live performance against benchmarks.
- **Touchpoints Export** — raw touchpoint-level export (Enterprise addon) for warehousing.
- **Settings** — gear icon in bottom-left; contains Apex, integrations, account, etc.

Northbeam also publishes "team-specific" recommended views (Paid Social, Paid Search, Executive, Email/SMS/Retention, Offline Channel, Influencer) but these are documented as best-practice configurations of the same canvases, not separate routes.

## Data they expose

### Source: Shopify
- Pulled: orders, transactions, new vs returning split, refunds, shipping, tax, discounts, product/SKU-level revenue. Northbeam pixel runs on the storefront and ties first-party identity into the device graph.
- Computed: AOV (blended/new/returning), ECR (Ecommerce Conversion Rate), CAC (blended/new/returning), MER, "% New Visits", LTV CAC. New vs returning is a first-class lens "unique to Northbeam's platform" per their Paid Social guide.
- Attribution windows (Accrual mode only): 1d, 3d, 7d, 14d, 30d, 90d Click; combined click/view variants (e.g., "7-Day Click / 1-Day View"); LTV (infinite). Cash Snapshot mode does not support windowed attribution.

### Source: Meta Ads
- Pulled: campaign/adset/ad spend, impressions, clicks, native conversions, deterministic view data (via Meta partnership for Clicks + Deterministic Views model).
- Computed: Northbeam-attributed Rev / ROAS / CAC across 7 attribution models, fractional transactions (split across touchpoints, e.g., Facebook 0.6 + Google 0.3 + Snapchat 0.1 = 1 transaction), Apex push-back of ad-level performance into Meta's optimizer.

### Source: Google Ads
- Pulled: clicks, impressions, spend, native conversions.
- Computed: same MTA suite as Meta. No Performance Max-specific surfaces called out beyond the breakdown taxonomy.

### Source: TikTok / Snapchat / Pinterest / Amazon / Microsoft / X / Yahoo / Axon / MNTN / Tatari / The Trade Desk
- Pulled: spend, impressions, clicks, native conversions, creative metadata (where supported). Documentation states data is "pulled via API" with platform-specific clicks, creative data, and native conversion metrics.
- Computed: same Northbeam MTA, ROAS, CAC, MER metrics applied uniformly.

### Source: Klaviyo
- Pulled: email/SMS engagement events, sends, opens (per "pixel tracking helps you track conversions, ad impressions, email opening rates" — Aazar Shad review, Sept 2022).
- Computed: Klaviyo flows treated as touchpoints in MTA model.

### Source: GA4 / GSC
- **Not integrated.** Northbeam positions its first-party pixel as a replacement for GA4-style measurement. GSC is not present in any documentation reviewed.

### Attribution models (the 7)
1. First Touch (simple) — credits first touchpoint; "useful for understanding upper funnel impact."
2. Last Touch (simple) — credits last touchpoint.
3. Last Non-Direct Touch (simple) — credits last non-direct; "great for monitoring Bottom of Funnel performance."
4. Linear (MTA) — equal credit across all touchpoints; recommended for mid-funnel like organic social.
5. Clicks-Only (MTA, proprietary) — equal credit across click touchpoints; Northbeam's daily-optimization recommendation.
6. Clicks + Modeled Views (MTA) — clicks + ML-modeled view contribution; "1 day view only"; "takes 25-30 days to learn from historical data."
7. Clicks + Deterministic Views (MTA) — flagship; uses "platform-verified impression and view data" via direct partnerships with Meta, TikTok, Snapchat, Pinterest, Axon, MNTN, Vibe (launched Oct 2025).

Two accounting modes: **Accrual** (credit on touchpoint date, supports windows) and **Cash Snapshot** (credit on purchase date, for finance reconciliation).

## Key UI patterns observed

### Overview Home Page
- **Path/location:** Default landing page after login, leftmost item in sidebar.
- **Layout (prose):** Top global filter strip applies to all dashboards: Attribution Model selector (Clicks-Only / First Touch / Last Touch / Last Non-Direct Touch / etc.), Window selector ("1-Day Click" through "90-Day Click" and "LTV"), Accounting Mode toggle (Cash Snapshot vs Accrual Performance), Granularity (Monthly / Weekly / Daily), Time Period picker, comparison mode (Previous Period / YoY / custom). Main canvas is a customizable grid of metric tiles created via a "+ ADD TITLES" button. Tiles include standard KPI cards (ROAS, CAC, transaction metrics) and Conversion Lag charts that visualize "what am I spending today? and what impact will this have on revenue in the future?" Users can rearrange tiles and save/share named views.
- **UI elements (concrete):** "+ ADD TILES" affordance for grid customization; Saved Views with rename/share; global filters at top apply across all dashboards (per docs: "Top-level global filters apply these filters across all your dashboards"). Embedded tooltips inside tables for Touchpoints, Revenue, ROAS, CAC, Visitors, Customers (Northbeam 3.0 release notes).
- **Interactions:** Drag-to-rearrange tiles; rename and save view; share view; toggle full-screen on tables and graphs (3.0 feature).
- **Metrics shown:** Hundreds available — "show any number of our hundreds of metrics options." Default tiles include ROAS, CAC, Transactions (New/Returning/Blended), Spend, Visits.
- **Source:** https://docs.northbeam.io/docs/overview-page; https://www.northbeam.io/features/overview-page

### Attribution Home Page
- **Path/location:** Sidebar > Attribution.
- **Layout (prose):** Default view is "Attribution: Revenue." Vertical sectioning down the page: Sales → New Customers → Returning Customers → Top of Funnel (Demand Capture) → Bottom of Funnel (Demand Generation) → Organic and Owned Media. Across the top of the canvas, tab strip: Sales / Product Analytics / Orders / Creative Analytics. A right-side rail hosts a Profitability panel — but it's gated and only "becomes functional only after you have passed the 90-day learning period."
- **UI elements (concrete):** Funnel-stage section dividers (Top vs Bottom of Funnel as named blocks rather than just channel rollup); New vs Returning customer split shown consistently across every section; right-rail Profitability widget that visibly stays empty/locked until Day 90. Inline tooltips on table headers.
- **Interactions:** Filter to single attribution model + window + accounting mode via global filter strip. Drill from channel to campaign to adset to ad. Breakdowns Manager allows redefining row groupings.
- **Metrics shown:** Spend, Attributed Rev (windowed: "Attributed Rev (1d)", "ROAS (7d)", "LTV CAC"), Transactions, New Customer %, ROAS, CAC, MER, ECR, Visits, % New Visits, CPM, CTR, eCPC, eCPNV — each available as Blended / New / Returning variant.
- **Source:** https://docs.northbeam.io/docs/attribution-page

### Creative Analytics
- **Path/location:** Sidebar > Attribution > Creative Analytics tab (also surfaced on the Attribution Home).
- **Layout (prose):** Grid of "creative cards" — each card shows a visual preview of the ad alongside performance metrics. Defaults: last 7 days, attribution mode = Clicks + Modeled Views, window = 1-day click / 1-day view, accounting basis = Accrual (Cash Snapshot is unsupported on this surface). Performance metrics on each card are rendered "on a color scale from red (negative) to green (positive)." Above the grid: search box, "hide inactive ads" toggle, metric-selector control, sort control. Below or alongside the grid: a comparison chart canvas that accepts up to 6 ads and renders them as line or bar graphs for trend overlay.
- **UI elements (concrete):** Creative cards = thumbnail + numeric metrics colored on red→green gradient. Up-to-6-ads multi-select for line/bar comparison chart. "Copy dashboard view link to clipboard" share button. Saved Views menu. Dynamic creatives without static thumbnails: "Dynamic creatives lack visual previews but retain complete performance data."
- **Interactions:** Search to filter; sort by spend (recommended starting point); pivot on metric selector; multi-select ads to overlay in comparison chart; toggle bar vs line; share link copies the entire view state; save view to reuse.
- **Metrics shown:** Recommended sort by Spend; recommended display of CTR, CPM, ECR (1-day), CAC (1-day), ROAS. Platform support: Facebook, Instagram, Snapchat, TikTok, Google, Pinterest, YouTube.
- **Source:** https://docs.northbeam.io/docs/creative-analytics; https://www.northbeam.io/features/creative-analytics

### Product Analytics
- **Path/location:** Sidebar > Attribution > Product Analytics tab.
- **Layout (prose):** Center of the page is a single large scatterplot divided into four colored quadrants. X axis = CAC Index (1–100, 100 = best), Y axis = ROAS Index (1–100, 100 = best return). Each bubble = a product (default), or platform / campaign / ad depending on a four-button toggle row above the plot. Bubble size = ad spend. Below the scatterplot is a row-level data table for the same selection. Above the plot, four "stackable" filter buttons (Product / Platform / Campaign / Ad) that can be checkbox-combined.
- **UI elements (concrete):** Four-quadrant color scheme — Yellow (top-left) = High ROAS, high CAC; Green (top-right) = High ROAS, low CAC ("your best performers"); Red (bottom-left) = Low ROAS, high CAC ("underperformers"); Blue (bottom-right) = Low ROAS, low CAC. Quick-analysis buttons that auto-filter both scatterplot and table to a single quadrant. Index-based 1–100 scoring (not raw ROAS/CAC) — explicit normalization for cross-product comparison.
- **Interactions:** Click quadrant or quick-analysis chip to focus; toggle bubble dimension Product/Platform/Campaign/Ad; row-level filter on the underlying table; hover bubble for details.
- **Metrics shown:** ROAS Index (1–100), CAC Index (1–100), Spend (encoded as bubble size), plus underlying raw metrics in the table.
- **Source:** https://docs.northbeam.io/docs/product-analytics

### Model Comparison Tool
- **Path/location:** Top-right hamburger (☰) icon "next to the maintenance alerts icon" → Model Comparison.
- **Layout (prose):** Surface is "purpose-built for seeing how different models interpret the same data without having to toggle back and forth manually." Side-by-side comparison of attribution models — primary documented use case is "Clicks Only vs Last Non-Direct Touch." Designed to surface "how revenue and transactions shift across models" rather than a single canonical number.
- **UI elements (concrete):** UI specifics not deeply documented in public sources. Confirmed: side-by-side model columns, ability to "export data to CSV and overlay platform data (e.g., Google Ads)." An "Overview Walkthrough Video" is embedded inline in the docs.
- **Interactions:** Compare any two of the 7 models; export to CSV; overlay platform-reported numbers as a third column for reconciliation against Meta/Google self-reporting. UI details beyond this are not available in public docs — only feature description.
- **Metrics shown:** Per model: Attributed Revenue, Transactions; deltas between models highlighted as the primary insight.
- **Source:** https://docs.northbeam.io/docs/what-is-northbeam-model-comparison-tool — note: detailed UI screenshots not publicly available; description is conceptual.

### Metrics Explorer (Correlation Analysis)
- **Path/location:** Sidebar > telescope icon (left rail).
- **Layout (prose):** Step-by-step workflow with control panel on top: Accounting Mode dropdown, Attribution Model & Window dropdown (e.g., "First Touch, 60 days"), Granularity (Daily/Weekly/Monthly), Time Period (e.g., 60 days). Main canvas is a grid of **correlation tiles** — each tile shows the Pearson r between two metrics. Below or alongside the tiles, line charts overlay the two correlated series so users can "identify visual trends" and spot "outlier moments." A quick-start template picker offers preset analyses.
- **UI elements (concrete):** Correlation tile = clickable card displaying a numeric Pearson coefficient; clicking a tile pivots the analysis to that metric pairing as the new center. Multi-metric overlay charts. Saved Views and CSV export. Enterprise tier unlocks "simultaneous multi-correlation analysis."
- **Interactions:** Adjust filters → tiles dynamically refresh; click tile to pivot; visual trend inspection; save view; export.
- **Metrics shown:** Any two selected metrics from the full Northbeam catalog; default highlights cross-channel effects (e.g., Facebook spend vs Amazon revenue).
- **Source:** https://docs.northbeam.io/docs/metrics-explorer-quickstart-guide; https://www.northbeam.io/product-news/announcing-metrics-explorer-correlation-analysis-in-northbeam

### Apex (configuration surface)
- **Path/location:** Settings (gear icon, bottom-left) > Account > Apex.
- **Layout (prose):** Connection / configuration panel — not a dashboard. Vertical form with sections: (1) Platform Selection ("Select the platforms you'd like to enable"), (2) North Star Metric Definition (dropdowns: Revenue type [First-Time/Returning/Blended], Attribution Model, Attribution Window, Accounting Mode [Cash/Accrual]), (3) Meta Connection Fields (four input fields: Token, Data Set ID, Business ID, Test ID). At top of section, an "Enhanced Apex tile" displays a "✅ green check" upon successful connection.
- **UI elements (concrete):** Form-style inputs; verification status indicator; explicit callout that "Apex does not edit or change ads in Ads Manager" — pure data-passing.
- **Interactions:** Configure once; status surface for "fully connected and sharing data with Meta."
- **Metrics shown:** No metrics on this screen; the value flows back into Meta Ads Manager.
- **Source:** https://docs.northbeam.io/docs/northbeam-apex; https://www.northbeam.io/features/apex

### Sales Attribution (marketing-page surface; lives inside Attribution Home)
- **Layout (prose):** Marketing-page renders show a customizable line-chart canvas with channel-level rollup. "Track hundreds of metrics over time in one customizable chart" and "Every channel, campaign, adset and ad in one view." UI specifics beyond the marketing site copy aren't deeply documented; treat this as a sub-mode of the Attribution Home rather than a distinct route.
- **Source:** https://www.northbeam.io/features/sales-attribution

### Profit Benchmarks
- **Path/location:** Unlocks at Day 90 (right-rail panel on Attribution Home + standalone surface).
- **Layout (prose):** Marketing page describes three feature blocks: Performance Targets ("Automatically calculate what performance targets you need to hit for maximum profit"), Cross-Platform Functionality ("See your performance against benchmarks in real-time"), and Growth Strategy. UI specifics not visible in public sources — only feature description on the marketing page. Computes target ROAS / MER / CAC against actual contribution margins.
- **Source:** https://www.northbeam.io/features/profit-benchmarks — UI details not available beyond marketing copy.

### MMM+
- **Layout (prose):** Marketed as "the industry's first self-service MMM dashboard" with "browser-based, customizable dashboards" — users "adjust their models and budget mixes on the fly" and create flexible forecasts and budget scenarios. Ingests native MTA data and exogenous features. Documented as live/dynamic rather than static report exports.
- **UI elements (concrete):** Specific chart, scenario-builder, and budget-allocator UI not visible in public sources.
- **Source:** https://www.northbeam.io/products/mmm-plus — UI details not available beyond marketing copy.

### Breakdowns Manager
- **Path/location:** Top-right ☰ menu > "Manage Breakdowns."
- **Layout (prose):** Tabbed admin page titled "Manage Breakdowns" with an "Add Breakdown" button. Lists all account breakdowns. To "edit" a default breakdown, users create a new one with the same name as the default — the override approach.
- **UI elements (concrete):** Four pre-configured dimension types: Platform (Facebook Ads, Google Ads, TikTok, etc.), Category (Paid Prospecting, Performance Max, etc.), Targeting (Branded Search, Display, Retargeting, etc.), Revenue Source (Online Store, Amazon).
- **Source:** https://docs.northbeam.io/docs/manage-breakdowns

## What users love (verbatim quotes, attributed)

- "Northbeam's data is by far the most accurate and consistent." — Victor M., Founder & CEO (Consumer Goods), Capterra, February 13, 2023
- "It also updates much more regularly (every hour) then some of the other platforms" — Victor M., Capterra, February 13, 2023
- "I check in every day. Our CFO checks in. Our CEO checks in. It's the first look of the day for all of us." — Claire Yi, VP of Growth at Grüns, Northbeam case study (undated, published 2025)
- "Northbeam's C+DV showed us exactly how our Meta views were driving purchases. In the future, this will give us more confidence in allocating our spend across the funnel." — Vessi (cited in Northbeam case study materials)
- "the best attribution platform I've ever used" — G2 reviewer (paraphrase aggregated by third-party review summary; original verbatim not surfaced from G2 directly due to scrape block)
- "pixel tracking helps you track conversions, ad impressions, email opening rates" — Aazar Ali Shad (DTC growth marketer), self-published review at aazarshad.com, September 6, 2022 (updated April 2023)
- "Northbeam's depth of attribution modeling is genuinely best-in-class" — Head West Guide review, 2026

Note: G2 and Trustpilot pages returned 403 to direct scraping; primary verbatim love quotes were sourced from Capterra (which loaded successfully) and the brand's own published case studies. Aggregated reviewer summaries were used where verbatim was unavailable.

## What users hate (verbatim quotes, attributed)

- "They used to have amazing support, but as of recent, they have stripped all support" — Joey B., SEM Consultant (Marketing and Advertising), Capterra, November 14, 2023
- "I used to be a massive northbeam supporter, now I am looking for an alternative" — Joey B., Capterra, November 14, 2023
- "Northbeam recently stripped all support from the platform for clients who pay up to $1k/month, including onboarding." — Capterra reviewer (paraphrase from Joey B. context), November 2023
- "Northbeam's onboarding was really bad" — G2 reviewer cited in third-party aggregator (verbatim originally on G2; G2 page returned 403 in this research session)
- "extremely hard" onboarding "despite paying for a 3-month package" — Trustpilot reviewer (Trustpilot page returned 403; quote aggregated from third-party summaries citing Trustpilot)
- "going back and forth for 29 days and being unable to finish the setup" — G2 reviewer (aggregated from third-party summary citing G2)
- "the attribution model was dismal compared to Google Analytics 4 (GA4), and... failed to deliver the necessary depth and accuracy" — Trustpilot reviewer (aggregated from third-party summary citing Trustpilot)
- "refusal to refund $3,000 despite service shortcomings, even when requesting the refund within a 15-day window" — Trustpilot reviewer (aggregated from third-party summary citing Trustpilot)
- "the company defensively cit[ed] their 'thousands of satisfied customers' instead of addressing issues" — Trustpilot reviewer (aggregated from third-party summary citing Trustpilot)
- "complex to use, particularly for new users, and some of the visual design is still being refined" — Capterra aggregated reviewer summary, 2026
- "Sub $10M in annual revenue... won't benefit" / "Overkill for Small Brands" — Head West Guide review, 2026
- "Will not follow the in-platform methodology" requiring users to "adopt Northbeam's attribution approach" — Head West Guide review, 2026 (framed as a learning-curve drawback)

Limited reviews available with full verbatim attribution because G2 and Trustpilot blocked direct fetch (403). Where verbatim quotes were surfaced, they came from Capterra (Joey B., Victor M.) and from the third-party aggregators that explicitly attributed to G2/Trustpilot reviewers.

## Unique strengths

- **Seven attribution models exposed simultaneously, plus a dedicated Model Comparison Tool that puts them side-by-side with platform self-reporting** — direct support for "don't pick one truth" thinking. Most competitors expose 1–3 models or hide model choice entirely.
- **Clicks + Deterministic Views ("the world's first deterministic view-through attribution model")** — built via direct partnership with Meta, TikTok, Snapchat, Pinterest, Axon, MNTN, Vibe (announced Oct 2025). Vessi case study claims it identified 46.61% more transactions via deterministic view data.
- **Apex push-back to ad platforms** — closes the loop: Northbeam-attributed ad-level performance is fed back into Meta and Axon optimization. Public claim of "as much as 30%" performance lift "without changing your strategies or campaign setups."
- **Self-service MMM+ dashboard** — most MMM offerings are quarterly consulting reports. Northbeam runs MMM as a live, browser-native dashboard with "weekly retraining" and on-the-fly budget scenarios.
- **Index-based scoring on Product Analytics** — instead of forcing comparison across raw ROAS/CAC values, products/campaigns/ads get normalized to a 1–100 index across both axes, plotted in a 4-quadrant scatter. Genuinely distinctive presentation.
- **Day 30/60/90 progressive feature unlock** — Northbeam Apex, Clicks + Modeled Views, Profit Benchmarks unlock sequentially as the model trains. Both an honest signal about ML calibration time and a deliberate onboarding pacing pattern.
- **First-class New vs Returning split** — every metric is available as Blended / New / Returning, and "New Customer %" is positioned as a unique-to-Northbeam metric.
- **Hourly data freshness** — multiple reviewers explicitly contrast this with competitors' daily refreshes.
- **Pearson-correlation Metrics Explorer for non-click channels** — directly targets TV/podcast/offline measurement, a gap most attribution tools punt on.
- **Breakdowns Manager** — user-defined channel groupings (Paid Prospecting / Performance Max / Branded Search / Retargeting / Online Store vs Amazon) decouple analysis taxonomy from raw platform structure.

## Unique weaknesses / common complaints

- **Onboarding is widely reported as painful** — "29 days back and forth," "still not properly onboarded after a month," "extremely hard despite paying for a 3-month package." This is the single most consistent complaint pattern across G2, Capterra, and Trustpilot.
- **Support tier-gating** — Joey B. (Capterra, Nov 2023) explicitly calls out that support was "stripped" for clients paying ≤$1k/mo. Multiple sources independently corroborate this.
- **3-months-upfront billing on Starter** — combined with the support stripping and refund refusals, this generates outsized refund-dispute complaints on Trustpilot.
- **No mobile app** — explicitly called out in workflowautomation.net and others; web-responsive only.
- **Pricing opacity** — only Starter has a public number; Pro/Enterprise require sales contact, and pageview-based scaling means TCO is hard to predict.
- **Steep learning curve / "complex to use"** — especially for users coming from in-platform-aligned tools (Triple Whale users coming over). Northbeam "will not follow the in-platform methodology" is a stated philosophical position, not a bug, but it's a persistent friction.
- **GA4-substitution skepticism** — at least one Trustpilot reviewer claims the Northbeam attribution model was "dismal compared to GA4," contradicting the brand's positioning.
- **Floor for usefulness is high** — multiple independent reviews say below ~$50k/mo media spend or ~$10M annual revenue, the platform doesn't have enough data volume to produce reliable MTA, even though Starter pricing technically opens at $1.5M annual media.
- **No GA4 / GSC integration** — full first-party stack, but customers who want to triangulate against GA4 / GSC have to leave Northbeam.

## Notes for Nexstage

- **Six attribution models exposed simultaneously is core to Northbeam's value prop.** Their "Model Comparison Tool" is the closest analog in the market to Nexstage's 6-source-badge thesis — it explicitly invites the user to NOT pick one truth, to instead see the gap between models. Different axis (model vs source) but the same epistemic posture. Worth deep-diving as a UI reference.
- **Day 30/60/90 progressive unlock is honest about ML calibration.** This is a viable pattern for any cost-recompute or attribution-default flow that takes time to converge — instead of empty states or "Recomputing..." banners, they sell the wait as a feature.
- **Their "Profitability" right-rail panel literally stays empty until Day 90** — concrete UI precedent for gating a panel by data-readiness rather than hiding it. Could inform how Nexstage handles the recompute-banner state.
- **Index normalization (1–100) on Product Analytics scatter** — escape hatch for the universal "ROAS distributions are too wide to plot raw" problem. Worth filing for any product/SKU performance view in Nexstage.
- **Breakdowns Manager (user-defined channel groupings)** — direct analog to Nexstage's channel-mappings UI. Their taxonomy options (Paid Prospecting / Performance Max / Branded / Retargeting / Online Store vs Amazon) are validated taxonomy defaults to consider when seeding `ChannelMappingsSeeder`.
- **No GA4 / GSC integration** — Northbeam treats first-party as replacement, not augmentation. Nexstage's 6-source badges (Real, Store, Facebook, Google, GSC, GA4) is structurally the opposite stance. The fact that Trustpilot has at least one reviewer comparing Northbeam unfavorably to GA4 is signal that "replace GA4" is a contested promise.
- **Apex (push-back to Meta) is structurally interesting** — Northbeam is not just measurement; it's measurement-as-input-to-bidding. Out of scope for Nexstage today but a notable gravitational pull for the high end of the market.
- **Onboarding pain is the universal complaint.** Multi-week setup, pixel deployment, calibration period, no self-serve. For an SMB-targeted product like Nexstage, "onboarded in <60 minutes" is a sharp wedge against this incumbent pattern.
- **Pricing floor effectively excludes SMB.** Northbeam's $1,500/mo Starter is roughly 10–20× a Triple Whale entry tier and 50–100× a typical SMB analytics SaaS. Entire SMB Shopify/Woo segment is structurally underserved by Northbeam.
- **Full-screen toggle on tables and graphs (Northbeam 3.0)** — small feature, big quality-of-life signal. Worth stealing.
- **Inline tooltips on table headers (Touchpoints, Revenue, ROAS, CAC, Visitors, Customers)** — Northbeam 3.0 explicitly calls these out. Direct analog to any Nexstage metric-definition affordance.
- **No screenshots could be saved.** All UI descriptions are sourced from public docs prose, marketing-page copy, third-party reviews, and Northbeam's own announcement posts. The actual product is fully behind a sales-led demo gate.
