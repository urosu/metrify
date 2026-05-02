---
name: TrueProfit
url: https://trueprofit.io
tier: T1
positioning: Auto-tracked real-time net-profit analytics for Shopify SMBs and dropshippers; replaces profit spreadsheets and competes head-on with BeProfit / Lifetimely / Profit Calc.
target_market: Shopify (and Shopify-native TikTok Shop) merchants from new dropshippers up through enterprise; ~5,857 installs concentrated in US (34%), Spain (11%), UK (8%), Australia (6%); apparel-heavy (33%) with most stores under 25 SKUs.
pricing: $35-$200/mo on four published Shopify-billed tiers, gated by orders/mo with $0.07-$0.30 per-extra-order overage capped at $300-$1,000.
integrations: Shopify, TikTok Shop, Facebook Ads, Google Ads, TikTok Ads, Bing Ads, Snapchat Ads, Pinterest, Amazon, Shopify Payments, PayPal, Stripe, ShipStation, ShipBob, Shippo, ShippingEasy, Shipwire, Printful, Printify, Gelato, CJ Dropshipping, Klaviyo, Google Analytics
data_freshness: real-time (mobile app marketing notes "every 15 minutes"; profit recalc job documented in changelog)
mobile_app: yes (iOS only — iPhone, iOS 15.6+, 40.8 MB; no Android observed)
researched_on: 2026-04-28
sources:
  - https://trueprofit.io
  - https://trueprofit.io/pricing
  - https://apps.shopify.com/trueprofit
  - https://apps.shopify.com/trueprofit/reviews
  - https://trueprofit.io/solutions/profit-dashboard
  - https://trueprofit.io/solutions/product-analytics
  - https://trueprofit.io/solutions/marketing-attribution
  - https://trueprofit.io/solutions/customer-lifetime-value
  - https://trueprofit.io/solutions/expense-tracking
  - https://trueprofit.io/blog/what-is-trueprofit
  - https://trueprofit.io/blog/trueprofit-review
  - https://trueprofit.io/blog/trueprofit-vs-beprofit
  - https://help.trueprofit.io/en/
  - https://apps.apple.com/us/app/trueprofit-profit-analytics/id1568063007
  - https://storeleads.app/reports/shopify/app/trueprofit
  - https://www.ringly.io/blog/trueprofit-alternatives
  - https://reputon.com/shopify/apps/analytics/trueprofit
---

## Positioning

TrueProfit positions itself as the "#1 Net Profit Analytics For Shopify" — the homepage headline reads literally that, with the supporting line "Net profit is the final truth to look at" and "the key that reveals the unseen reality of your true business performance." The product is framed as a spreadsheet replacement: it auto-pulls every cost (COGS, shipping, transaction fees, taxes, custom expenses) and every revenue/ad-spend stream into one real-time net-profit number, then layers product-level, customer-level, and channel-level views on top. It is Shopify-only (and Shopify-hosted TikTok Shop) — listed alternatives like BeProfit and Sellerboard explicitly outflank it on multi-platform breadth, and Ringly's "TrueProfit alternatives" piece notes merchants leave "due to pricing, order limits, or the need for features like multi-platform support."

## Pricing & tiers

Pricing is fully public and Shopify-billed, with order-volume gating + per-extra-order overage that is itself capped per tier. Verbatim feature names from the pricing page:

| Tier | Price | Order cap (overage) | What's included | Common upgrade trigger |
|---|---|---|---|---|
| Basic | $35/mo | 300 orders ($0.30/extra, max $300 surcharge) | "Realtime profit dashboard", "Customer Lifetime Value", "Quantity-based COGS", "Custom Costs", up to 5 COGS Zones, 1 marketing channel account, 3 team members | Crossing 300 orders/mo or needing Product Analytics / P&L |
| Advanced | $60/mo | 600 orders ($0.20/extra, max $500 surcharge) | Basic + "Product Analytics", "P&L Report", up to 10 COGS Zones, 2 marketing channel accounts, 5 team members, "Customizable Email Reports", "Shopify Shipping Auto-Sync" | Need a 2nd ad platform connected, or scaling team / COGS zones |
| Ultimate | $100/mo | 1,500 orders ($0.10/extra, max $700 surcharge) | Advanced + "Ad Sync Custom Rules", "Unlimited COGS Zones", 5 marketing channel accounts, 10 team members | Need >2 ad accounts or international fulfillment with many zones |
| Enterprise | $200/mo | 3,500 orders ($0.07/extra, max $1,000 surcharge) | Ultimate + "Marketing Attribution", "Unlimited Team Members", "Prioritized Support", 10 marketing channel accounts | Need cross-channel attribution (gated entirely to top tier) |

Notable structural choice: **Marketing Attribution is paywalled to the $200/mo Enterprise tier** — every lower tier sees ad spend and ROAS but not the attribution screen. All plans include a 14-day free trial. Reviewers frequently dispute the per-order overage as the real upgrade trigger (see complaints).

Note: The original brief mentioned a $25-$499 range; the live pricing page on 2026-04-28 shows $35-$200 with capped overage rather than a $499 ceiling. No $499 tier was observed.

## Integrations

**Sources (data pulled in):**
- **Ecommerce platform:** Shopify (required), TikTok Shop (separate onboarding flow per help-center category "TrueProfit for TikTok Shop")
- **Ad platforms (auto-sync):** Facebook Ads Manager, Google Ads, TikTok Ads Manager, Bing Ads, Snapchat Ads, Pinterest, Amazon Ads, X (per blog feature page; not listed on the current Shopify App Store summary)
- **Payment / fees:** Shopify Payments, PayPal, Stripe (transaction-fee tracking)
- **Shipping carriers:** Shippo, ShipBob, ShipStation, ShippingEasy, Shipwire (auto-sync of shipping costs)
- **Print-on-demand / dropship:** Printful, Printify, Gelato, CJ Dropshipping (auto-COGS sync)
- **Email / analytics:** Klaviyo, Google Analytics (mentioned in blog feature listings; help-center categorisation places ad-platform pixels separately from email tools)

**Coverage gaps observed:**
- **No WooCommerce.** Shopify-only. Multiple alternatives articles call this out as the #1 reason to switch to BeProfit/Sellerboard.
- **No GSC (Google Search Console).** Not listed anywhere in features, help, or pricing.
- **No GA4-as-source-of-truth.** GA4 is a connectable integration but not positioned as an attribution lens — TrueProfit's attribution claim is "server-side tracking" + ad-platform pixels.
- **No Amazon Seller Central / non-ad Amazon storefront.** Amazon shows up as an ad platform only.

**Destinations (push):** Email reports (CSV/PDF P&L), CSV export of orders/products. No webhook/API integrations advertised on public pages.

## Product surfaces (their app's information architecture)

The product is structured around seven named feature surfaces (verbatim from the homepage / pricing card / blog) plus a TikTok Shop variant and a mobile app:

- **Profit Dashboard** — "Get an instant overview of your business performance" — the home screen; live net profit, revenue, margin, AOV, ROAS, orders, plus performance-over-time chart and cost-breakdown chart.
- **Product Analytics** — "Identify most and least profitable products" — SKU/variant-level profit table with margin %, ad spend per product, page views, ATC rate, conversion rate. Gated to Advanced tier+.
- **Marketing Attribution** — "Give credit to the true profitable marketing channels" — last-click vs. assisted view per ad/channel with full funnel metrics. Gated to Enterprise tier only.
- **P&L Report** — "Get a high-level overview of your P&L over any given timeframe" — accountant-style statement, exportable, schedulable email delivery on Advanced+.
- **Customer Lifetime Value** — "Unlock customer true value for smarter retention strategies" — totals + repurchase rate + LTV + CAC + LTV:CAC ratio, filterable by country.
- **Expense Tracking** — "Track every expense to unlock accurate profit insights" — COGS, COGS Zones, custom recurring/one-time costs, transaction fees, shipping, taxes.
- **Integrations** — connection management for ad/shipping/payment/POD platforms.
- **TikTok Shop's Net Profit** — separate onboarding-and-tracking surface for native TikTok Shop sellers (not Shopify+TikTok-Ads — actual TikTok Shop storefronts).
- **Mobile app (iOS)** — read-only profit + cost tracking dashboard; deeper SKU/attribution screens are explicitly not in mobile per the review blog ("Deeper feature dashboards like SKU reports aren't accessible via mobile app yet").
- **Multi-store switcher** — unified all-store rollup view + per-store views; positioned as a differentiator vs. BeProfit.
- **Settings / Team / Billing** — account, COGS Zones, custom costs configuration, team member management (3-unlimited by tier), Shopify billing.

## Data they expose

### Source: Shopify
- **Pulled:** orders, line items, customers, products + variants, refunds, transaction fees (Shopify Payments), discounts, shipping. Auto-detected from Shopify install.
- **Computed:** Net profit (per order, per product, per store), gross revenue, profit margin, AOV, "average order profit", repurchase rate, LTV, CAC, LTV:CAC ratio, total customers.
- **Cost layer:** Quantity-based COGS, COGS Zones (geographic — by delivery destination, capped 5/10/unlimited by tier), unlimited COGS periods (historical adjustments), per-variant overrides.
- **Refund/return handling:** A 2-star reviewer (Apollo Moda, May 2024) flagged that Shopify's financial summary "counts all return requests as issued refunds" and that TrueProfit inherits this — the team reportedly told them changing it "isn't practical." This is documented limitation, not a fixed bug.

### Source: Meta Ads (Facebook + Instagram)
- **Pulled:** campaign/adset/ad spend, impressions, clicks, CTR, ATC events, cost-per-ATC, purchases, purchase value, conversion rate, CPM, CPC, ROAS.
- **Computed:** Net profit per ad/adset/campaign, net profit margin per ad, blended ROAS at the dashboard level. Last-click and "Assisted Purchases" views are exposed.
- **Attribution windows:** Public pages do not specify (no 7d-click / 1d-view declarations). The marketing-attribution page emphasizes "server-side tracking" as resilient to iOS 14 / cookie loss, but doesn't cite a reclaim percentage.

### Source: Google Ads
- **Pulled:** spend, impressions, clicks, conversions, conversion value (per ad/adgroup/campaign).
- **Computed:** Channel-level ROAS and net profit. Same attribution model as Meta (last-click + assisted).

### Source: TikTok Ads
- **Pulled:** spend, impressions, clicks, conversions; available on every paid tier as one of the marketing channel slots.
- **Computed:** ROAS, net profit attribution.

### Sources: Bing, Snapchat, Pinterest, Amazon Ads, X
- **Pulled:** spend, impressions, clicks, basic conversion tracking from each ad platform's API.
- **Note:** These eat into the per-tier "marketing channel accounts" cap (1/2/5/10).

### Source: Klaviyo / GA4
- Listed as integrations on blog comparison pages but never described as data sources for attribution. Treated as auxiliary connectors rather than first-class lenses (no "Klaviyo lens" or "GA4 lens" UI hinted at).

### Source: Shipping carriers (Shippo, ShipBob, ShipStation, ShippingEasy, Shipwire)
- **Pulled:** Actual per-order shipping costs, automatically associated to orders.
- **Computed:** True shipping cost per order; replaces formula-based shipping estimation.
- **Note:** Multiple positive reviews praise this auto-sync; one March 2026 review (Interior Delights, USA) noted ShipStation/ShipBob "syncs shipping costs effectively" but flagged a limitation on "item pick charges" not being captured separately.

## Key UI patterns observed

The TrueProfit website does NOT publish public screenshots that read clearly via WebFetch (they appear to use lazy-loaded image carousels). The descriptions below are reconstructed from prose on solution pages, blog walkthroughs, the iOS App Store listing, and recurring review references. Where UI specifics are not available, I say so — no fabrication.

### Profit Dashboard
- **Path/location:** Default landing screen after login; primary nav item.
- **Layout (prose):** Top of page shows live net-profit number prominently (the "$495,345" example used in their walkthrough blog) with gross revenue, profit margin, and AOV displayed alongside. Below the KPIs sits a **dynamic line graph for performance over time** (user picks which metric — revenue, orders, ROAS, net profit — and toggles day/week/month). Below that, a **cost breakdown chart** (categorical breakdown across packaging, fulfillment, marketing fees, transaction fees, custom costs). A separate **Orders vs. Ad Spend per Order** chart was added in a 2025 update per their changelog. Multi-store users see a store-switcher (likely top-bar) that toggles between aggregated rollup and per-store views.
- **UI elements (concrete):** Large primary KPI number; supporting KPI tiles; line chart with metric-picker; categorical cost-breakdown chart (type unspecified — likely stacked bar or donut, not confirmed); date-range filter; store switcher. Specific colors, sparkline presence, and stoplight indicators are NOT confirmed from public sources.
- **Interactions:** Metric picker on the line graph; date-range selection; store switcher (rollup vs. single store); table is described as "customizable" and "logically arranged" — exact column reordering or save-view behavior is not confirmed.
- **Metrics shown:** Net profit, gross revenue, profit margin, AOV, ROAS, orders, "average order profit", total costs.
- **Source/screenshot:** UI details from https://trueprofit.io/solutions/profit-dashboard and https://trueprofit.io/blog/what-is-trueprofit. No clean public screenshot captured.

### Product Analytics
- **Path/location:** Sidebar nav; gated to Advanced tier+.
- **Layout (prose):** Tabular SKU/variant view with **per-product net profit margin** displayed as a percentage (the walkthrough blog cites "58.95% and 45.23%" as live examples). Per-row breakdown includes ad spend allocated to that product, page views, add-to-cart rate, conversion rate. Designed to enable "winner/loser product identification" — the framing implies sortable columns and probably a top/bottom split, but column-level UI is not pictured.
- **UI elements (concrete):** SKU/variant rows, margin % column, cost-breakdown columns (COGS, shipping, ad spend, fees), funnel-metric columns (views, ATC, CVR). Variant-level granularity confirmed.
- **Interactions:** Sortable columns implied. Drill-down behavior (click row → product detail) is not confirmed from public sources. Filtering by date range and store assumed but not confirmed.
- **Metrics shown:** Net profit per product, profit margin %, ad spend per product, COGS, shipping, page views, ATC rate, conversion rate.
- **Source/screenshot:** UI details from https://trueprofit.io/solutions/product-analytics and the blog. UI details not available — only feature description seen on marketing page.

### Marketing Attribution
- **Path/location:** Sidebar nav; gated to Enterprise tier ($200/mo) only.
- **Layout (prose):** Two attribution lens toggle: **"Last-clicked Purchases"** and **"Assisted Purchases"**. Per-channel and per-ad table with the full funnel metric set. Server-side-tracked data underpins the numbers.
- **UI elements (concrete):** Per-channel/per-ad row with these columns named verbatim by the solution page: impressions, spending, clicks, click-through rate, add-to-cart events, cost per ATC, purchase count, purchase value, cost per purchase, conversion rate, revenue, total cost, "Net profit," net profit margin.
- **Interactions:** Toggle between last-click and assisted views. Drill-down (channel → campaign → ad) is the implied IA but not screenshot-confirmed.
- **Metrics shown:** Listed verbatim above. Notably no MER/blended-ROAS column distinct from per-channel ROAS is named on the page.
- **Source/screenshot:** UI details from https://trueprofit.io/solutions/marketing-attribution. No public screenshot.

### P&L Report
- **Path/location:** Sidebar nav; gated to Advanced tier+.
- **Layout (prose):** Accountant-style P&L statement — "high-level overview of your P&L over any given timeframe." Schedulable email delivery (daily/weekly/monthly) is offered on Advanced via the "Customizable Email Reports" feature. CSV export available.
- **UI elements (concrete):** Date-range picker; line items presumably grouped (Revenue → Discounts/Refunds → Net Revenue → COGS → Gross Profit → Operating Costs → Net Profit) but exact line-ordering is not pictured publicly.
- **Interactions:** Time-range selector; export to CSV; email-delivery scheduling.
- **Metrics shown:** Standard P&L lines.
- **Source/screenshot:** UI details from https://trueprofit.io/solutions/p-and-l-report (page returned 404 on direct fetch — feature described in pricing card and blog). UI details not available — only feature description seen on marketing pages.

### Customer Lifetime Value
- **Path/location:** Sidebar nav; available on Basic+.
- **Layout (prose):** A consolidated CLV dashboard surfacing five primary measurements as KPI tiles or summary blocks: **total customers, repurchase rate, LTV, CAC, LTV:CAC ratio**. Country-filter chip enables geographic CLV comparison.
- **UI elements (concrete):** Five-metric KPI block; country filter. Cohort-grid UI is NOT advertised — Lifetimely is consistently positioned in 3rd-party comparison pages as the cohort-analysis specialist; TrueProfit "integrates LTV into its profit dashboard for real-time decision-making" rather than offering predictive cohort modeling.
- **Interactions:** Country filter to "compare how customers in different countries spend".
- **Metrics shown:** Total customers, repurchase rate, LTV, CAC, LTV:CAC ratio.
- **Source/screenshot:** UI details from https://trueprofit.io/solutions/customer-lifetime-value. UI details not screenshot-confirmed.

### Expense Tracking / Cost Settings
- **Path/location:** Settings area; foundational setup workflow.
- **Layout (prose):** Multi-tab cost configuration covering COGS (per-product, per-variant, with unlimited historical periods, CSV import, and auto-sync from Shopify or CJ Dropshipping), **COGS Zones** (geographic — set different COGS by delivery destination), shipping cost rules ("by location, product, quantity, or weight"), transaction-fee tracking by gateway (PayPal, Stripe, Shopify Payments), and custom costs (recurring agency fees, one-time payments, labor).
- **UI elements (concrete):** Per-product/per-variant cost rows; CSV import flow; period-based historical COGS editor; zone-rule builder; custom-cost row entry with recurring/one-time toggle.
- **Interactions:** CSV upload; zone-rule creation; auto-sync toggles per integration; period-history adjustments.
- **Metrics shown:** N/A — configuration surface, not a reporting screen.
- **Source/screenshot:** UI details from https://trueprofit.io/solutions/expense-tracking and help.trueprofit.io "Cost Settings" category.

### Mobile App (iOS)
- **Path/location:** Standalone iPhone app (Apple App Store, free, by "Golden Cloud Technology Company Limited").
- **Layout (prose):** Read-only profit tracker. Headline metrics: revenue, net profit, net margin, total costs, AOV, average order profit. Performance charts, cost breakdowns, ad spend analysis (Facebook, Google, TikTok). Multi-store aggregated view. iOS widget integration for at-a-glance profit. Background sync.
- **UI elements (concrete):** KPI tiles, performance chart, cost breakdown, ad-spend section, multi-store rollup.
- **Interactions:** Background sync (a Feb 2025 changelog entry fixed continuous-retry-on-error behavior). No SKU-level or attribution drill-downs in mobile per the review blog.
- **Metrics shown:** Revenue, net profit, net margin, total costs, AOV, average order profit, ad spend per platform.
- **Source/screenshot:** https://apps.apple.com/us/app/trueprofit-profit-analytics/id1568063007 — no Android.

## What users love (verbatim quotes, attributed)

- "not having a profit calculator is biggest mistake a shop can do" — Carholics (Finland), Shopify App Store, March 11, 2026
- "tells you exactly where you are loosing money and how to fix it" — Frome (Canada), Shopify App Store, February 4, 2026
- "Great app for keeping an eye on your main metrics" — GetUp Alarm (UK), Shopify App Store, March 9, 2026
- "just what I needed to track my costs in real time" — Obnoxious Golf (USA), Shopify App Store, April 15, 2026
- "Durra has been very helpful and support is always quick to fix any issues" — Houselore (UK), Shopify App Store, April 15, 2026
- "Amazing support from Vani" — BlushFashionstore (Netherlands), Shopify App Store, February 27, 2026
- "Gabriel was awesome!" — Avtec Surgical (USA), Shopify App Store, February 17, 2026 (4+ years using app)
- "simple to use, straight to the point" / "better than BeProfit" — Apple App Store iOS review (cited verbatim in App Store listing extraction)

Recurring themes in praise: **support response time** (multiple reviews name individual support reps — Durra, Vani, Gabriel, Grace), **shipping-cost auto-sync accuracy** (ShipStation/ShipBob), **set-and-forget cost tracking**, and longevity (multiple reviewers in their 2nd-5th year of use).

## What users hate (verbatim quotes, attributed)

- "Attention all business owners! It's essential to double-check the accuracy of your refund versus returns data. Shopify's financial summary counts all return requests as issued refunds, which can be misleading. Not all return requests are accepted, and not all approved returns end up refunded. Stay vigilant to ensure more precise results. I've discussed this concern with the TrueProfit team, but they believe making changes now isn't practical. So, choose wisely. Accurate net profit reporting is crucial for all of us." — Apollo Moda (USA, ~1 month using app), Shopify App Store, May 3, 2024 (2-star)
- "AVOID! There's better Profit Apps out there!" — Shopify App Store reviewer, January 20, 2026 (1-star, full text not displayed in public listing)
- "Worst experience ever" — Shopify App Store reviewer, July 22, 2024 (1-star)
- "the pricing plans, it's expensive and they take a % from each [order]" — Shopify App Store 1-star reviewer (cited via Reputon aggregation, May 2021-June 2022 window)
- "The transaction fees are calculated by a formula although this can be pulled directly from Shopify [...] Beprofit handle this correctly" — Shopify App Store 1-star reviewer (cited via Reputon aggregation)
- "is not for new stores, the cost is high + they take a cut from each order" — Shopify App Store 1-star reviewer (cited via Reputon aggregation)
- (Trueprofit's own review blog, citing weaknesses) — "Can feel complex for brand-new stores with very low order volume", "Learning curve in setting up advanced cost rules", "Deeper feature dashboards like SKU reports aren't accessible via mobile app yet" — trueprofit.io/blog/trueprofit-review

Recurring themes in criticism: **per-order overage fees on top of base pricing** (cited as the real cost surprise), **transaction-fee formulaic calculation drift** (vs. Shopify's actual fee feed), **refund/return double-count tracing back to Shopify's financial summary**, **complexity for brand-new stores**, and **mobile app feature parity gaps**.

Limited public negative-review text available — the Shopify App Store displays 5 one-star and 1 two-star reviews out of 758 total (98% five-star), and the actual one-star review bodies are not all surfaced in the public listing or aggregator scrapes.

## Unique strengths

- **Shopify-billed pricing transparency.** Every tier, order cap, overage rate, and overage cap is published on the public pricing page. Most competitors at this price point keep at least one detail opaque.
- **Per-extra-order pricing with a hard ceiling.** $0.07-$0.30/extra order, but capped at $300-$1,000 surcharge — limits worst-case bills, which is unusual in the category.
- **COGS Zones (geographic per-destination COGS).** "Effortlessly create zones to manage COGS by delivery destination" — this addresses the realistic case where a Shopify dropshipper has different unit costs in EU vs US fulfillment. Cap is 5/10/unlimited by tier.
- **Unlimited COGS history periods.** Historical COGS tracking for any product/variant — useful for accurate retroactive margin recalculation.
- **#1 install share in Shopify profit category.** 5,857 stores, 5.0/5 with 758 reviews, 46.5% YoY install growth (storeleads.app, April 2026). This is the social-proof moat.
- **Two-mode attribution out of the box.** Last-clicked vs. Assisted Purchases — both surfaced as a simple toggle, not buried in settings.
- **Auto-sync of carrier shipping costs.** ShipStation/ShipBob/Shippo/ShippingEasy/Shipwire all natively pull actual shipping cost per order, not formula-based.
- **Support is the single most-cited praise vector.** Reviewers name individual support reps by first name across countries, suggesting personalized and fast support is a deliberate moat.
- **Multi-store rollup.** All-stores aggregated view + per-store toggle, on every tier.
- **Mobile app with iOS widget.** Free, lightweight, profit-only — Lifetimely and BeProfit do not advertise comparable mobile apps.

## Unique weaknesses / common complaints

- **Shopify-only.** No WooCommerce. Multi-platform sellers churn to BeProfit/Sellerboard.
- **Marketing Attribution is paywalled at $200/mo.** Every tier under Enterprise sees only ad spend and per-channel ROAS, never the attribution screen — significant feature paywall vs. competitors that include attribution at lower tiers.
- **Per-order overage on top of base price.** Repeated complaint vector — small stores feel they're being taxed proportionally to growth.
- **Transaction fees calculated by formula, not pulled from Shopify Payments.** Documented complaint with explicit "BeProfit does this correctly" comparison from a 1-star reviewer.
- **Refund/return data inherits Shopify's "all return requests = refunds" treatment.** TrueProfit reportedly told a reviewer changing this "isn't practical."
- **No GSC. No GA4-as-attribution-lens.** SEO/organic-search visibility is absent from the product entirely. GA4 is connectable but not surfaced as a data lens.
- **Mobile app has limited screens.** No SKU/attribution drill-downs on mobile.
- **Android missing.** iOS-only mobile app.
- **No predictive LTV / cohort grid.** Lifetimely is positioned in every comparison page as the predictive-LTV / cohort specialist; TrueProfit's CLV is current-state ratios, not cohort-based forecasting.
- **No native API / webhook integrations advertised** — limits power users / agencies wanting to push data to BI.
- **Steep first-time setup of cost rules.** TrueProfit's own review blog admits "Learning curve in setting up advanced cost rules."
- **Item-pick charges from 3PLs not separately captured** (per Interior Delights, March 2026, who praised the rest of the integration).

## Notes for Nexstage

- **Direct positioning collision.** TrueProfit owns the "auto-tracked real-time net profit for Shopify" phrase. Nexstage's WooCommerce coverage and the GSC/GA4 lenses are concrete differentiators TrueProfit cannot match without a roadmap quarter.
- **Pricing benchmark.** $35 entry is the SMB anchor in this category; $200 ceiling for "everything including attribution" is a useful number to keep in mind. The order-volume gating with capped overage is a structurally interesting model worth evaluating vs. seat- or workspace-based pricing.
- **Attribution paywall is a moat AND a vulnerability.** TrueProfit gates last-click vs assisted attribution at $200/mo. Nexstage's "6 source badges as default lens" approach can be framed against this — every Nexstage user sees multi-source attribution out of the gate, no upsell.
- **Two-attribution-mode UI ("Last-clicked" vs "Assisted") is a direct precedent** for surfacing model choice as a top-level toggle. This is a UI vocabulary worth studying for our source-lens chip pattern.
- **COGS Zones (geographic per-destination COGS) is a feature we don't currently spec.** Worth adding to cost-config feature research — Shopify dropshippers with EU+US fulfillment care about it.
- **Unlimited COGS periods (historical adjustments) maps cleanly** to our `RecomputeAttributionJob` + retroactive recalc pattern. Same problem space, same solution.
- **Refund/return double-count is a known industry problem.** TrueProfit explicitly chose not to solve it. Nexstage solving it correctly (return-request vs. issued-refund split) would be a quoteable differentiator.
- **Transaction fee accuracy is a litmus test.** The 1-star formula-vs-Shopify-feed complaint is canon in this category — Nexstage should pull Shopify Payments fees from the actual feed, not estimate.
- **Support velocity is the praise vector.** Individual support reps named by first name in reviews. Brand differentiation through human support is real here.
- **Mobile is iOS-only and feature-incomplete.** Nexstage doesn't ship mobile yet, but if/when it does, Android + feature parity (SKU/attribution screens) is open territory.
- **No public screenshots fetchable.** TrueProfit's marketing pages use lazy-loaded image carousels that don't render via WebFetch; clean UI captures would require a logged-in trial account or App Store gallery scraping.
- **TikTok Shop is treated as a separate onboarding flow / IA branch**, not a unified "channel" inside the main dashboard. Worth noting if Nexstage adds TikTok Shop later — IA decision point.
- **GSC, GA4, and WooCommerce together cover three TrueProfit gaps simultaneously.** Worth validating as a positioning angle: "TrueProfit for the rest of your stack."
