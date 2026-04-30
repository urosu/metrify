---
name: Lifetimely (by AMP)
url: https://useamp.com/products/analytics
tier: T1
positioning: Profit, LTV, and attribution analytics for Shopify (and Amazon add-on) DTC merchants — replaces P&L spreadsheets and stitches LTV / CAC into a single cohort-driven view.
target_market: Shopify-first DTC brands; pricing geared to $30K/mo+ stores; Amazon as paid add-on; primarily English-speaking SMB-to-mid-market.
pricing: Free (50 orders/mo). Paid starts at $149/mo (M tier, 3,000 orders/mo) and scales by monthly order volume to $999/mo Unlimited; +$75/mo Amazon add-on. 14-day free trial. No public annual pricing.
integrations: Shopify, Amazon, Google Ads, Meta (Facebook/Instagram), TikTok, Snapchat, Pinterest, Microsoft Ads, Google Analytics (GA4), Google Sheets, Klaviyo, Sendlane, ReCharge, ShipStation, ShipBob, QuickBooks Online
data_freshness: "Real-time" claimed in marketing; in practice, multiple reviewers report dashboard updates "every few hours" rather than instantaneous. Daily P&L emails sent at fixed AM time.
mobile_app: No native iOS/Android app observed. Dashboard described as "mobile and web device viewing" (responsive web).
researched_on: 2026-04-28
sources:
  - https://useamp.com/products/analytics
  - https://useamp.com/products/analytics/lifetime-value
  - https://useamp.com/products/analytics/profit-loss
  - https://useamp.com/products/analytics/dashboard
  - https://useamp.com/products/analytics/lifetimely-vs-triple-whale
  - https://useamp.com/pricing
  - https://apps.shopify.com/lifetimely-lifetime-value-and-profit-analytics
  - https://apps.shopify.com/lifetimely-lifetime-value-and-profit-analytics/reviews
  - https://help.useamp.com/article/652-product-costs-explained
  - https://help.useamp.com/article/682-cohort-analysis-use-cases
  - https://help.useamp.com/article/643-the-profit-dashboard
  - https://help.useamp.com/collection/637-lifetimely
  - https://1800dtc.com/breakdowns/lifetimely
  - https://www.bloomanalytics.io/blog/trying-to-decide-between-bloom-profit-analytics-and-lifetimely-we-ve-got-you-covered
  - https://appnavigator.io/app/lifetimely-lifetime-value-and-profit-analytics/reviews/1741069
  - https://reputon.com/shopify/apps/dashboard/lifetimely-lifetime-value-and-profit-analytics
  - https://www.crunchbase.com/acquisition/the-commerce-co-acquires-lifetimely--0c9e3c2c
---

## Positioning

Lifetimely (now branded "Lifetimely by AMP" after acquisition by AMP / The Commerce Co — formerly associated with the AfterShip ecosystem narrative but actually rolled into the AMP / Commerce Co umbrella per Crunchbase) sells Shopify DTC operators "real-time, actionable and automated reports on your Shopify and Amazon stores' profit, revenue and expenses." The tagline on the LTV product page is **"Measure CAC and LTV in One Simple Lifetime Value Report"** and the P&L page leads with **"Make the Fastest, Data-Driven Decisions to Increase Profit."** They explicitly position against spreadsheets ("finally replace your daily spreadsheet routine") and against Triple Whale on the axis of "complete business visibility and growth forecasting" vs. Triple Whale's "narrow" marketing-attribution focus.

Their wedge is the depth of LTV cohort + predictive LTV + P&L unification. They concede attribution to Triple Whale rhetorically: "If your main focus is attribution… Triple Whale is likely a better fit. Lifetimely is a more wholistic eCommerce revenue analytics tool."

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Free | $0/mo | All features; 50 orders/mo cap | Hits 50 orders/mo |
| M | $149/mo | All features; up to 3,000 orders/mo; standard support | 3,000 orders/mo |
| L | $299/mo | All features; up to 7,000 orders/mo; **Silver support** | 7,000 orders/mo |
| XL ("Most Popular") | $499/mo | All features; up to 15,000 orders/mo; **Gold support** (dedicated account manager) | 15,000 orders/mo |
| XXL | $749/mo | All features; up to 25,000 orders/mo; **Platinum support** (99.9% SLA) | 25,000 orders/mo |
| Unlimited | $999/mo | Unlimited orders; "Personalized setup and support" | — |
| Amazon Add-On | +$75/mo | Adds Amazon sales/ads import + reports; available on all paid tiers | Adds Amazon channel |

- 14-day free trial on all paid tiers. No public annual pricing.
- Order-volume gating is the only scale axis; **all features are included on every tier** (per the Free tier description "all features included") — this is unusual vs. SaaS competitors that paywall by feature.
- Multiple third-party reviews flag overage charges if you exceed your tier's order limit ("Additional charges if you exceed your order limits" — Bloom Analytics comparison).

## Integrations

**Pulled (sources):**
- **Storefront:** Shopify (required), Amazon (paid add-on). No WooCommerce, BigCommerce, or headless support — Shopify-only is repeatedly called out as a hard limitation.
- **Ads:** Meta (Facebook + Instagram), Google Ads, TikTok, Snapchat, Pinterest, Microsoft Ads, Amazon Ads.
- **Analytics:** Google Analytics (GA4).
- **Email/SMS:** Klaviyo, Sendlane.
- **Subscriptions:** ReCharge.
- **Fulfillment/3PL:** ShipStation, ShipBob, "Addison" (likely Addition logistics).
- **Accounting:** QuickBooks Online.
- **Spreadsheets:** Google Sheets (export/sync).

**Pushed (destinations):**
- Email (daily/weekly P&L digest at 7am or Monday 8am).
- Slack (daily P&L delivered to channel).
- Google Sheets (export).
- Custom dashboards delivered as scheduled email + Slack reports.

**Coverage gaps observed:**
- **No Google Search Console (GSC).** No SEO data ingestion. This is a notable gap vs. Nexstage's 6-source thesis.
- **No native WooCommerce.** Shopify lock-in.
- **GA4 connection exists but is positioned as supporting data, not a primary lens.** Their attribution claims center on their own pixel + UTMs, not GA4 sessions.
- Required vs. optional: Shopify is required at install; everything else is optional add-on.

## Product surfaces (their app's information architecture)

Inventoried from product pages, help docs collection, and 1800DTC's hands-on breakdown:

- **Profit Dashboard (a.k.a. Income Statement)** — Default landing screen. Answers "what was my net profit yesterday/last 7d/MTD?"
- **Lifetime Value Report (Cohort heatmap)** — Answers "how does cumulative spend per customer evolve by cohort, and when does CAC pay back?"
- **LTV Drivers Report** — Auto-ranked correlations: "which products / discount codes / countries / tags push LTV up or down?"
- **Predictive LTV (AI)** — Forward projection of cohort LTV by segment.
- **Custom Dashboards** — Drag/drop/resize KPI grid; role-based starter templates (Founder, eCom Manager, Performance Marketer, CFO, CEO).
- **Marketing board / Daily overview / Boardroom KPIs** — Three pre-built dashboard templates inside Custom Dashboards.
- **Attribution Report** — Channel-level reported revenue, spend, CPC, CAC, ROAS; first- and last-click multi-touch; Lifetimely's own pixel for cross-platform comparison.
- **Cohort Analysis (LTV-coupled)** — Weekly/monthly/yearly cohorts, sliceable by first-touch / last-touch / source / medium / first-product / discount / country / tags.
- **Repurchase Rate Report** — Retention % across multiple time windows.
- **Time Lag Between Orders Report** — Distribution of days between 1st→2nd, 2nd→3rd, etc.
- **Customer Product Journey ("noodle" diagram)** — First product → second product → third product purchase flow.
- **Benchmarks** — 11 metrics compared to anonymised stores in your category; bell-curve distributions with 25th/median/75th percentiles.
- **Cost & Expenses tab** — Product cost CRUD, default COGS margin, shipping costs, custom recurring costs (staff, software).
- **Integrations page** — Connect/disconnect each source.
- **Ask AMP / AMP AI Chat** — Conversational AI assistant for "instant business insights" (tagline: "Not your average AI, AMP AI gets sh*t done!").
- **Daily P&L Email/Slack delivery** — Scheduled output, not a screen, but a first-class surface in their messaging.
- **Settings / Privacy / Benchmark opt-in** — Settings page that includes the anonymised data-sharing opt-in for benchmarks.

## Data they expose

### Source: Shopify
- **Pulled:** orders, line items, customers, products (incl. Shopify's built-in "Cost per item" field, auto-imported), refunds, transaction gateway fees, tags (customer + order tags), discount codes, marketing source/medium attached to orders.
- **Computed on top:** AOV (new customer vs. repeat — separate metrics in Benchmarks), gross margin, contribution margin, net profit, repurchase rate (90d, 180d), accumulated sales per customer, accumulated gross margin per customer, accumulated contribution margin per customer, time-between-orders distributions, product co-purchase paths.
- **Attribution windows:** Cohorts can be weekly / monthly / yearly. Repurchase rates exposed at 90d and 180d in benchmarks. LTV horizons standardised at 3, 6, 9, and 12 months.

### Source: Amazon (paid add-on)
- **Pulled:** Amazon sales orders, ads spend (impressions, clicks, attributed revenue), via Seller Central credentials. Three regional groupings: North America (4 countries), Europe (12 countries), Far East (3 countries).
- **Limitation called out in docs:** "Due to restrictions from Amazon, the time period can only be extended as far back as 3 months" on initial connection.

### Source: Meta Ads (Facebook + Instagram)
- **Pulled:** campaign-level spend, impressions, clicks, platform-reported conversions/ROAS.
- **Computed:** blended CAC, blended ROAS, channel CPC/CPM. Shown alongside Lifetimely's own pixel-attributed revenue for comparison.

### Source: Google Ads
- **Pulled:** spend, clicks, conversions, ROAS at campaign level.
- **Computed:** same blended CAC/ROAS treatment; appears as a column in the Attribution Report.

### Source: TikTok / Snapchat / Pinterest / Microsoft Ads
- **Pulled:** spend + platform-reported attribution. Treated symmetrically as additional channel rows in Attribution.

### Source: Google Analytics (GA4)
- **Pulled:** sessions, traffic source data (used to corroborate / complement pixel attribution). GA4 is listed as an integration but is **not** the primary attribution source — Lifetimely's own pixel is.

### Source: Klaviyo / Sendlane
- **Pulled:** email/SMS revenue attribution; segment-level performance data feeds into channel and cohort reports.

### Source: ReCharge
- **Pulled:** subscription orders, recurring revenue, churn-relevant fields (used in repurchase / LTV calculations for subscription brands).

### Source: ShipStation / ShipBob / Addition
- **Pulled:** actual shipping cost per order — used to populate the shipping line of the income statement at order granularity.

### Source: QuickBooks Online
- **Pulled:** accounting expense lines for the operating-expense section of the P&L.

### Lifetimely Pixel (proprietary)
- First-party JS tracker placed on Shopify storefront; powers the Attribution Report's "reported revenue per channel" and underpins first-click / last-click models. They market this as their answer to iOS 14+ attribution loss but **publish no specific reclaim percentage** (unlike Triple Whale's published numbers).

## Key UI patterns observed

### Profit Dashboard / Income Statement (default landing)
- **Path/location:** Top-level sidebar item; default page on login.
- **Layout (prose):** Per 1800DTC's hands-on breakdown and useamp.com product page, the landing canvas leads with revenue, product costs, marketing costs, and net profit as the four anchor figures, structured as an income-statement-style **stacked vertical layout** rather than a 4-up KPI grid. Below the headline figures, costs are factored in line-by-line (Shopify COGS auto-pulled, transaction gateway fees, shipping from ShipStation/ShipBob, custom recurring costs). The page is described repeatedly as "clean, easy to digest" (1800DTC) — a deliberate spreadsheet-replacement aesthetic.
- **UI elements (concrete):** Income-statement table format (line per cost category, descending from revenue → contribution → net). Top-of-page date-range picker. Color usage from screenshots described as "professional color scheme emphasizing readability" — neutral palette with restrained green/red for deltas (precise tokens not extractable without authenticated dashboard access).
- **Interactions:** Daily/weekly/monthly toggles. Data refreshes at multi-hour intervals (reviewers note "every few hours"; not true real-time despite marketing copy). Email/Slack export of the same view delivered at 7am daily.
- **Metrics shown:** Total sales, COGS, marketing spend, gross margin, contribution margin, net profit, refunds, fees, custom expenses.
- **Source/screenshot:** Marketing imagery on https://useamp.com/products/analytics/profit-loss and https://useamp.com/products/analytics — actual logged-in dashboard not publicly accessible. UI details beyond this paragraph not available — only feature description seen on marketing page.

### P&L Waterfall / Cohort Waterfall Chart
- **Path/location:** Inside the LTV Report (cohort view). Distinct from the Profit Dashboard income-statement view.
- **Layout (prose):** 1800DTC describes this as "a small (but highly useful) feature where you can add a green bar directly on your cohort waterfall chart to show exactly when your CAC payback hits." The waterfall sits inside the cohort view and shows accumulated sales per customer building up over months — i.e., it is a **cohort-waterfall** (cumulative LTV bar built up over months) rather than a P&L-waterfall (revenue→net-profit cost-bridge). The bar is annotated with a horizontal **green CAC-payback marker** that the user positions at their CAC value; the visual then makes the payback month visually obvious.
- **UI elements (concrete):** Vertical accumulating bar segments per month, with a **user-configurable horizontal green line/bar** denoting CAC threshold. Color: green is reserved specifically for the CAC payback annotation in this view.
- **Interactions:** User enters their CAC manually to position the green bar; can filter cohort upstream by product/channel/country/tags before the waterfall renders.
- **Metrics shown:** Cumulative revenue per customer per month-since-acquisition; CAC threshold overlay; implicit "months to payback" by reading where the bar crosses the green line.
- **Source/screenshot:** Described in 1800DTC's review at https://1800dtc.com/breakdowns/lifetimely. UI details beyond described above not directly observable from public sources without paid trial.

### Lifetime Value Report — Cohort heatmap
- **Path/location:** Sidebar > Lifetime Value Report.
- **Layout (prose):** Per useamp.com's LTV product page and the cohort-analysis help article, the core visualization is a **heatmap matrix**: rows are cohort start periods (week/month/year), columns are months-since-first-order, cells are the chosen metric. Filter strip above the matrix lets users slice the entire grid by first-order product, first-order product category, source/medium, marketing channel (first-touch OR last-touch), country, customer tags, order tags, discount codes. Time-period toggle: weekly / monthly / yearly cohorts.
- **UI elements (concrete):** Color-gradient cells ("clean grid structure with color gradients to represent performance variations across customer cohorts" — useamp.com). Specific gradient hex values not published; competitor product pages show a color-gradient (light→saturated) scale where higher accumulated values render in darker/saturated cells. **13+ selectable metrics** can be displayed in the cells (1800DTC).
- **Interactions:** Filter chips at top; cohort timeframe toggle; metric dropdown. Drill-down by clicking a cohort cell is implied but not explicitly confirmed in public sources. Predictive overlay can be toggled on for forward LTV projection.
- **Metrics shown (selectable for the cells):** Accumulated sales per customer, accumulated gross margin per customer, accumulated contribution margin per customer, total repurchasing customers, accumulated orders per customer, plus "13+" total options including predicted LTV at 3/6/9/12mo, repurchase rate, AOV by cohort.
- **Source/screenshot:** https://useamp.com/products/analytics/lifetime-value (marketing screenshot). Color tokens and exact hover behavior not visible without authenticated session.

### LTV Drivers Report
- **Path/location:** Sub-tab of LTV Report.
- **Layout (prose):** Auto-ranked list of dimensions (products, customer tags, discount codes, countries) sorted by correlation strength with LTV. Surfaces "which products, discount codes, countries, tags and more correlate to a higher (or lower) LTV" (useamp.com).
- **UI elements:** Ranked list / table format. UI details not available — only feature description seen on marketing page.
- **Interactions:** Filter by dimension type. Click-through to underlying cohort presumably.
- **Metrics shown:** Correlation indicator + LTV delta vs. baseline per row.
- **Source/screenshot:** https://useamp.com/products/analytics/lifetime-value.

### Custom Dashboards
- **Path/location:** Sidebar > Dashboards.
- **Layout (prose):** Drag/drop/resize widget grid on a "blank canvas interface" (useamp.com). Three starter templates: **Marketing board** (10 most important marketing KPIs broken out by channel), **Daily overview** (daily P&L summary), **Boardroom KPIs** (investor-facing). Role-based templates explicitly named for Founder, eCommerce Manager, Performance Marketer, CFO, CEO.
- **UI elements (concrete):** Metric cards, trend visualizations, data tables. Multiple comparison metrics on a single chart supported. **KPI targets** can be set per metric to track goal progress. Unlimited custom metrics + dashboards.
- **Interactions:** Drag/drop/resize. Schedule email delivery: daily or weekly at 7am. Slack delivery (Monday 8am called out in marketing copy: "Delivered to your email inbox and Slack every Monday at 8AM"). Mobile + web responsive.
- **Metrics shown:** User-configurable. Per useamp.com's category lists: Acquisition (marketing spend, ROAS, CPC, CPM, conversions), Retention (LTV, repeat purchase rate, cohort behavior), Finance (P&L, true profit, CAC), Performance (AOV, blended ad spend, regional sales).
- **Source/screenshot:** https://useamp.com/products/analytics/dashboard.

### Attribution Report
- **Path/location:** Sidebar > Attribution.
- **Layout (prose):** "Centralized marketing command center" (1800DTC). Channel rows (Facebook, Instagram, Google, TikTok, Snapchat, Pinterest, Microsoft) with columns: reported revenue, spend, CPC, CAC, ROAS. Lifetimely's own pixel data shown alongside platform-reported numbers — explicit side-by-side comparison.
- **UI elements (concrete):** Tabular layout with channel rows. Anomaly-detection alerts surface performance spikes/drops. UI details beyond this not available — only feature description seen on marketing page.
- **Interactions:** Filter by date range, attribution model toggle (first-click vs. last-click). Anomaly alerts.
- **Metrics shown:** Reported revenue (per channel), spend, CPC, CAC, ROAS, plus pixel-attributed revenue as a comparison column.
- **Source/screenshot:** Described in 1800DTC breakdown; YouTube tutorial "Quick Guide to Attribution in Lifetimely by AMP" (Nov 2024, https://www.youtube.com/watch?v=hBMlIvhs6G4).

### Customer Product Journey ("noodle" diagram)
- **Path/location:** Customer Behavior Reports group.
- **Layout (prose):** "Intuitive 'noodle' diagrams" (1800DTC) showing customer flow from 1st → 2nd → 3rd → 4th product purchases. Each band of the noodle represents the volume of customers transitioning between specific products.
- **UI elements (concrete):** Sankey-style flow diagram (the "noodle" terminology suggests curved/flowing bands rather than straight Sankey lines). Color-coded by product or category.
- **Interactions:** Filter by cohort, discount, channel, post-purchase survey responses.
- **Metrics shown:** Customer count flowing between purchase positions; conversion rate from purchase N to purchase N+1.
- **Source/screenshot:** Referenced in 1800DTC at https://1800dtc.com/breakdowns/lifetimely. UI details beyond this prose not directly observable from public sources.

### Benchmarks Report
- **Path/location:** Sidebar > Benchmarks (opt-in required).
- **Layout (prose):** 11 metrics displayed as **bell-curve distribution charts** with the user's value plotted against industry median, 25th, and 75th percentiles. Categorised into P&L (net sales, contribution margin, gross margin), Order (new + repeat AOV), Retention (90d + 180d repurchase rates for new + repeat customers), and Acquisition (blended ROAS, blended CAC, marketing spend %).
- **UI elements (concrete):** **Each metric tile is shaded green (top 25% or 25–50%) or yellow (50–75% or bottom 25%)** based on the user's percentile position — direct quote from help docs. A pencil-icon entry point lets users edit their 4-question survey (business model, product type, category, B2B vs B2C).
- **Interactions:** Edit survey → recategorise. Opt-in/out of anonymised data sharing in Settings > Privacy. Overall performance score = average position across all 11 benchmark metrics.
- **Metrics shown:** 11 metrics × 4 data points (user value, median, 25th, 75th).
- **Source/screenshot:** https://help.useamp.com/category — Benchmarks help article (verbatim color rule confirmed).

### Repurchase Rate Report
- **Path/location:** Customer Behavior Reports.
- **Layout (prose):** Retention percentages across multiple time windows. Filterable.
- **UI elements:** UI details not available — only feature description seen on marketing page.
- **Interactions:** Filter by cohort dimensions.
- **Metrics shown:** Repurchase % at 30d / 60d / 90d / 180d / 365d (specific windows inferred from benchmarks article using 90d + 180d).
- **Source/screenshot:** 1800DTC breakdown.

### Time Lag Between Orders
- **Path/location:** Customer Behavior Reports.
- **Layout (prose):** Distribution of days between consecutive orders (e.g., 1st→2nd, 2nd→3rd). Bucketed at 7 / 30 / 90 days per 1800DTC.
- **UI elements:** UI details not available — only feature description seen on marketing page.
- **Interactions:** Filter.
- **Metrics shown:** Customer count per time-lag bucket per order pair.
- **Source/screenshot:** 1800DTC breakdown.

### Cost & Expenses tab
- **Path/location:** Sidebar > Costs / Settings.
- **Layout (prose):** Per-product cost editing UI. Each product row has a **pencil icon** to edit cost individually (per help doc). CSV bulk-import accepts a `SKU` + `product_cost` (+ optional `shipping_cost`) spreadsheet. Default COGS margin % can be set as a fallback when no explicit cost exists.
- **UI elements (concrete):** Inline editable rows. Pencil icon per row. CSV upload widget.
- **Interactions:** Manual edit, CSV upload, default fallback. **Priority hierarchy:** Lifetimely manual cost > Shopify cost-per-item > default COGS margin.
- **Metrics shown:** Product, SKU, cost, shipping cost (optional).
- **Source/screenshot:** https://help.useamp.com/article/652-product-costs-explained (verbatim).
- **Limitations called out in docs:** Newly added products with zero sales don't appear immediately. **Transaction fees and handling costs are explicitly excluded from this scope.** **No variant-level cost granularity is described** — costs are SKU-keyed per-product.

### AMP AI Chat ("Ask AMP")
- **Path/location:** Top nav / persistent chat bubble.
- **Layout (prose):** Conversational chat panel; tagline "Not your average AI, Amp AI gets sh*t done!" Marketed as automation-capable, not just retrieval. UI details beyond this not available — only feature description seen on marketing page.
- **Interactions:** Natural-language query → dashboard answer.
- **Metrics shown:** Variable (anything in the data model).
- **Source/screenshot:** https://useamp.com/products/analytics.

## What users love (verbatim quotes, attributed)

- "Provides insights that are impossible to get anywhere else or without time consuming calculations. (Sam is the best!)" — Topo Designs, Shopify App Store review, March 10, 2026
- "I love this app and the support… the support team is very efficient when it comes to find a solution" — ALLENDE, Shopify App Store review, April 8, 2026
- "tool we rely on every day to make decisions. Great customer support!" — Constantly Varied Gear, Shopify App Store review, March 23, 2026
- "simplified, impactful dashboards that help make decision making easier" — Raycon, Shopify App Store review, March 18, 2026
- "invaluable for understanding of your MER, customer cohorts and LTV" — Radiance Plan, Shopify App Store review, April 2, 2026
- "helped us understand our Lifetime Value better than anything else" — Blessed Be Magick, Shopify App Store review, February 8, 2026
- "removes the hassle of calculating a customer's CAC and LTV" — ELMNT Health, Shopify App Store review, January 27, 2026
- "very good customer support through the chat option" — Tennant Products, Shopify App Store review, April 17, 2026
- "get an answer same day" — Good Dye Young Inc, Shopify App Store review, April 17, 2026

## What users hate (verbatim quotes, attributed)

- "Support is very slow, the app does not load the prices and the price is far too expensive. I now have an alternative that covers everything and is much less complicated, without taking hours to get an answer from support. If I pay 150 euros a month, I expect direct live support. During the time the support answered me, I simply switched the app." — Sellsbydanchic, Shopify App Store 1-star review, May 23, 2025
- "Pretty poor app overall. Expensive and slow. Buggy." — Plushy, Shopify App Store review, March 29, 2022 (cited via Reputon)
- "Overpriced for what it is. Very basic and slow." — TheCustomGoodsCo, Shopify App Store review, May 16, 2022 (cited via Reputon; user migrated to Triple Whale)
- "the additional add on cause the product to be a bit limiting…but overall a useful tool for high level view" — Sur Nutrition, Shopify App Store review, March 19, 2026 (mixed: paywalled add-on friction)
- "After scaling, [Lifetimely] started breaking in ways that actually cost them money. The interface gave the illusion of accuracy, but under the hood, it just wasn't reliable at the SKU level." — paraphrased Reddit/community sentiment surfaced in third-party reviews (specific source not directly accessible; multiple secondary citations)
- "At $149/month for the first paid tier, Lifetimely is a real line item, with stores doing under $30K/month potentially struggling to justify it." — ATTN Agency review, 2026

## Unique strengths

- **Best-in-class LTV cohort UX for a Shopify app.** Repeatedly cited as "best-in-class for a Shopify app, allowing you to segment customers by first purchase date, first product purchased, acquisition channel, geography, and more, with each cohort showing cumulative revenue, repeat purchase rate, and CAC payback period over time." 13+ selectable cohort metrics is unusually deep.
- **CAC-payback green bar overlay on the cohort waterfall.** Concrete, single-purpose UI primitive — tiny feature, high-leverage interpretation. No other competitor in the SMB tier exposes this exact annotation.
- **Predictive LTV at 3/6/9/12-month horizons by cohort segment.** Marketed with a "12% average LTV increase" claim for users (no methodology published, but the headline is unique vs. peers).
- **LTV Drivers Report.** Auto-ranked correlation table for products / discounts / countries / tags by LTV impact — an *insights* surface (not just a chart), generated automatically.
- **Income Statement layout** is structurally accountant-friendly (top-down line items in P&L order) rather than dashboard-card-grid — appeals to CFO/founder personas more than to performance marketers.
- **Benchmarks with green/yellow percentile shading and a 4-question survey for peer-group definition.** 11 metrics, anonymised opt-in. The shading rule is explicit ("green = top 25% or 25–50%; yellow = 50–75% or bottom 25%").
- **All features included on every paid tier**, including Free. Differentiation is purely by order volume + support level — easy upgrade conversation.
- **Customer support is the #1 most-cited praise vector** across reviews (named individuals like "Sam" appear in multiple reviews — high-trust signal). Gold tier ($499/mo) includes a dedicated account manager.
- **Three pre-built dashboard templates** (Marketing board, Daily overview, Boardroom KPIs) with role-based starters (Founder / eCom Manager / Performance Marketer / CFO / CEO) — opinionated default IA.

## Unique weaknesses / common complaints

- **Shopify-only.** No WooCommerce, no BigCommerce, no headless. Amazon is paid add-on only. Hard wall for any merchant on a non-Shopify stack.
- **No GSC integration.** SEO data is not part of the data model. Notable for Nexstage's 6-source thesis.
- **Pricing floor is high relative to merchant size.** $149/mo entry is "a real line item" for sub-$30K/mo stores per ATTN Agency. Recurring complaint: "expensive for what it is."
- **Real-time claim vs. actual freshness.** Marketing says "real-time"; reviewers say "every few hours." Reputational mismatch.
- **Data accuracy at scale flagged in community feedback.** Multiple sources cite SKU-level reliability issues for high-volume merchants ("started breaking in ways that actually cost them money").
- **No variant-level COGS.** Costs are SKU/product-keyed; help docs do not describe variant cost differentiation.
- **Transaction fees and handling costs explicitly out of scope** in the cost-config help doc — must be entered as custom recurring costs, not derived.
- **Order-overage charges** can create unpredictable invoices during sales surges (per Bloom Analytics comparison).
- **Support response speed inconsistent.** Universally praised for quality when reached; multiple 1-star reviews call out slowness ("hours to get an answer"). They acknowledged this and said live chat was rolling out.
- **Cohort and LTV reports paywalled** for some advanced filtering — "Advanced filtering capability has been made available to Pro & Plus customers" post-AMP-acquisition (i.e., gated to upper tiers despite "all features included" framing).
- **Limited public attribution-reclaim numbers.** They have a pixel; they don't publish iOS-loss reclaim percentages (Triple Whale does, which is used against Lifetimely in head-to-heads).

## Notes for Nexstage

- **The "income statement" framing is structurally different from the KPI-card-grid framing that most competitors lead with.** Lifetimely's P&L looks like an accounting statement (line items descending). Worth comparing as a layout primitive for our P&L surface — especially for CFO-leaning users.
- **The CAC-payback green bar on the cohort waterfall is a concrete, copyable UI idea.** A user-configurable horizontal threshold annotation that converts a cohort cumulative chart into a "when do I break even" answer in one glance. Cheap to implement, very high recognition value.
- **All-features-included pricing axis (orders/month only).** Contrasts with most SaaS gating-by-feature. Worth weighing — simpler upgrade conversation, but harder to monetize advanced power features.
- **Benchmarks UX rule (green/yellow percentile shading + 4-question category survey) is a documented pattern.** If we ever build benchmarks, the percentile-binning rule is here verbatim.
- **GSC absence is a structural opening for Nexstage.** Lifetimely has Meta + Google Ads + GA4 + their own pixel, but no Search Console. Our 6-source story (Real, Store, Facebook, Google, GSC, GA4) directly fills a gap they have not addressed.
- **Pixel + GA4 + platform-reported revenue side-by-side in the Attribution report** is a direct analog to our 6-source-badge thesis. They do **3 columns** (pixel / GA4 / platform); we propose 6.
- **COGS import is per-SKU; variant-level cost is not described.** Common merchant complaint vector — review explicitly mentions "wasn't reliable at the SKU level." Variant-level COGS could be a Nexstage differentiator.
- **Transaction fees and handling costs explicitly excluded from scope in their docs.** Both are first-class concepts in Nexstage's cost-config story; this is a real gap.
- **Cohort metric depth = 13+ options.** That's a lot. Worth cataloging which of these are computed vs. which are stored when planning our `daily_snapshots` and cohort schemas.
- **Customer support is their #1 strength.** Operational, not technical. Hard for a small competitor to match unless we lean into AI-assisted support or async docs heavily.
- **Real-time claim is undefended.** "Every few hours" actual freshness creates a credibility wedge — Nexstage's hourly snapshots + explicit data-freshness badges could attack this directly.
- **No mobile app.** Surface gap if Nexstage ever ships mobile.
- **AMP acquisition product fallout to watch:** Post-acquisition, advanced filtering moved behind Pro/Plus tiers; existing users have noted feature reshuffling. Some of the "expensive and limiting" sentiment in 2025–2026 reviews aligns with the post-acquisition pricing/packaging changes — useful context when interpreting their review velocity.
- **AMP AI Chat is positioned as agentic ("gets sh*t done"), not retrieval.** No public details on what actions it takes vs. just summarising. Worth tracking as the AI-assistant baseline for SMB analytics.
