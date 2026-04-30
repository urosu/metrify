---
name: Daasity
url: https://www.daasity.com
tier: T1
positioning: Warehouse-native omnichannel data platform for consumer brands; replaces a small data team for $5M+ DTC/retail merchants who need ELT, semantic models, and dashboards in one stack.
target_market: Consumer brands $5M-$1B+ revenue, omnichannel (DTC + Amazon + retail/wholesale + syndicated). 1,600+ brands; processed $10B in 2024 sales data. Heavy CPG/beauty/apparel concentration.
pricing: Opaque, sales-led. Shopify App Store lists $1,899/month entry usage-based on rolling 3-month annualized revenue; third-party trackers cite "starts at $199/month" for the lightest Growth tier. Custom enterprise pricing dominates; implementation services priced separately.
integrations: Shopify, Shopify Plus, Shopify B2B, Magento, BigCommerce, Salesforce Commerce Cloud, Newstore, Walmart Marketplace, Amazon Vendor Central, Whole Foods, Meta/Facebook Ads, Google Ads, Google Analytics, GA4 API, Bing Ads, Pinterest, Snapchat, TikTok, Criteo, AppLovin, Northbeam, Rockerbox, Impact Radius, Pepperjam, Klaviyo, Attentive, Postscript, Iterable, Ometria, Fairing, KnoCommerce, Yotpo, Okendo, Alchemer, Gorgias, Zendesk, Thankful, NetSuite, ShipBob, ShipStation, Fulfil, Extensiv, Order Desk, BackinStock, Recharge, Skio, Stay AI, Loop Returns, Narvar, Algolia, RetailNext, SPINS, Nielsen, Snowflake, BigQuery, Redshift, Postgres, MySQL, MS SQL, MongoDB. Destinations: Klaviyo, Attentive, Facebook Ads custom audiences, Google Ads customer match, Snowflake, BigQuery.
data_freshness: Mostly daily/nightly. Hourly Flash dashboard exists exclusively for Shopify data and refreshes hourly. Other dashboards display "LAST COMPLETE DAY DATA" refreshed nightly. No sub-hourly real-time.
mobile_app: No (web-only; embedded analytics interface, not mobile-optimized).
researched_on: 2026-04-28
sources:
  - https://www.daasity.com/
  - https://www.daasity.com/why-daasity
  - https://www.daasity.com/ecommerce-analytics
  - https://www.daasity.com/integrations
  - https://www.daasity.com/feature/attribution
  - https://www.daasity.com/post/marketing-attribution-dashboard
  - https://www.daasity.com/post/from-dashboards-to-ai-analysts-how-daasity-is-transforming-analytics
  - https://help.daasity.com
  - https://help.daasity.com/core-concepts/dashboards
  - https://help.daasity.com/core-concepts/dashboards/report-library
  - https://help.daasity.com/advanced/marketing-attribution/attribution-overview
  - https://help.daasity.com/advanced/marketing-attribution/survey-based-attribution
  - https://help.daasity.com/core-concepts/dashboards/report-library/omnichannel/company-overview
  - https://help.daasity.com/core-concepts/dashboards/report-library/ecommerce-performance/flash-dashboards
  - https://help.daasity.com/core-concepts/dashboards/report-library/ecommerce-performance/site-analytics-and-attribution
  - https://help.daasity.com/core-concepts/dashboards/report-library/retention-marketing/retention
  - https://apps.shopify.com/daasity
  - https://apps.shopify.com/daasity/reviews
  - https://www.aisystemscommerce.com/post/daasity-review-ecommerce-data-platform
---

## Positioning

Daasity sells itself as "Enterprise Level Analytics, No Data Engineering" — a warehouse-native omnichannel data platform for consumer brands that combines ELT, prebuilt semantic models, an embedded BI front-end, and reverse-ETL activation. The pitch is explicitly not "another Shopify dashboard": the company brands itself as a "modular data platform" that collapses what would otherwise be a Fivetran + dbt + Looker + Hightouch stack into a vertically integrated product, and is "100% focused on consumer product brands" with a "Natively DTC" approach extended to retail/wholesale and syndicated data (Nielsen, SPINS). It competes with Triple Whale / Northbeam / Polar Analytics on the surface, but positions a tier higher: the third-party review at aisystemscommerce.com explicitly notes Daasity "complements attribution platforms such as Northbeam or Rockerbox by adding true profitability layers, retail deductions, and syndicated benchmarks," and warns it can "feel like overkill for pure Shopify DTC brands under $5M."

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Growth (Shopify App Store entry) | $1,899/month, billed monthly, 14-day free trial | Standard dashboards, prebuilt explores, "embedded Looker experience," limited connectors | Need for more data sources, custom modeling, multi-store consolidation |
| Custom / Enterprise | Opaque, "contact sales" | Direct Looker access, custom data models, professional services implementation, GitHub access for transformation scripts | Brands moving to $25M+ revenue, multi-channel (retail/wholesale), syndicated data needs |

Pricing is **opaque and sales-led**. The Shopify App Store lists $1,899/month "usage-based on annualized rolling 3-month revenue average." Third-party listing aggregators (sourceforge, softwaresuggest) reference a $199/month entry, but this is not visible on Daasity's own site. The homepage pricing tagline is "Less than the cost of a data engineer, more effective than doing it yourself." The aisystemscommerce review states: "Pricing remains fully custom and depends on data volume, connector count, and services. Teams expecting SaaS-style transparent tiers may need a demo and scoping call before budgeting." Implementation services are priced separately and have been a flashpoint in negative reviews (see "What users hate" below).

## Integrations

**Sources (extractors)** are organized into seven categories on the integrations page:

- **E-commerce & marketplaces:** Shopify, Shopify Plus, Shopify B2B, Magento, BigCommerce, Salesforce Commerce Cloud, Newstore, Walmart Marketplace, Amazon Vendor Central, Whole Foods.
- **Advertising & marketing:** Google Ads, Google Analytics, GA4 API, Bing Ads, Pinterest, Snapchat, TikTok, Criteo, AppLovin, Emplifi, Impact Radius, Pepperjam, Rockerbox, Northbeam (i.e. Daasity ingests from rival attribution platforms).
- **Customer comms:** Attentive, Iterable, Klaviyo, Ometria, Postscript.
- **Reviews / surveys / support:** Alchemer, Fairing, Gorgias, KnoCommerce, Okendo, Thankful, Yotpo, Zendesk.
- **Order & inventory:** BackinStock, Extensiv, Fulfil, NetSuite, Order Desk, ShipBob, ShipStation.
- **Subscriptions:** Recharge, Skio, Stay AI.
- **Returns:** Loop Returns, Narvar.
- **Databases / files:** Azure SQL, BigQuery, MS SQL, MongoDB, MySQL, Postgres, Redshift, CSV, Excel.
- **Other:** RetailNext, SPINS, Nielsen.

**Destinations (reverse-ETL):** Attentive, Facebook custom audiences, Google Ads customer match, Klaviyo, plus Snowflake/BigQuery as warehouse targets.

**Notable gaps for Nexstage's scope:**
- **No WooCommerce.** Daasity's source list is conspicuously Shopify/Magento/BigCommerce/Salesforce Commerce. WooCommerce is not surfaced as a supported extractor on the public integrations page.
- **No Google Search Console (GSC).** GA4 is supported via the GA4 API; GSC is absent from the public list.
- **Meta is referred to as "Facebook"** on destinations and is implied as a source (the marketing pages show Meta as supported), though it isn't called out by name as prominently as on competitors' pages.
- **No native TikTok Shop / Shopee / Lazada** — focus is Western consumer brands.

## Product surfaces (their app's information architecture)

Top-level navigation (per `help.daasity.com` overview):

- **Home Dashboard** — KPI snapshot segmented by channel (e-commerce, retail, marketing) with click-through to specialized dashboards.
- **Collections** — "Your hub for organizing, accessing, and sharing dashboards and reports"; functions like folders (per-team or per-topic).
- **Templates Library** — Pre-configured dashboards built on unified data models or source-specific explores; accessed via left-menu or "New Dashboard → From Template."
- **Explore & Data Dictionary** — Drag-and-drop query builder (no SQL required) over Daasity's prebuilt schemas.
- **Data Management** — Integration setup, custom metrics, data quality monitoring.
- **Audiences** — Reverse-ETL segment activation to Klaviyo / Attentive / Meta / Google Ads.
- **Custom Dashboards** — User-built reports from Explore.

The **Report Library** (Templates) is the IA spine. It is organized by **department/team**, not by data source — this is the distinctive IA the assignment flagged. Categories and dashboards verbatim from `help.daasity.com/core-concepts/dashboards/report-library`:

**Omnichannel**
- Company Overview

**Retail Analytics** (URS / URMS unified retail schemas)
- Sales Performance (URS) — standard sales dashboard
- Brand Performance (URMS)
- Key Drivers (URMS)
- Market Opportunity (URMS)
- Pricing Analysis (URMS)
- Promotional Comparison (URMS)
- Promotional Efficiency (URMS)
- Promotional "Bump Chart" (URMS)
- Trade Promotion Performance

**Ecommerce Performance**
- Flash Dashboards (Daily / Weekly / Daily-vs-Plan / Hourly variants)
- Orders and Revenue
- Inventory
- Fulfilment Status Options
- Operations
- Product
- Product Repurchase Rate
- Site Analytics & Attribution
- Site Funnels
- Shopify Sales Report

**Acquisition Marketing**
- Attribution Deep Dive
- LTV & RFM
- Marketing
- Vendor-Reported Marketing Performance

**Retention Marketing**
- Notifications (Email/SMS)
- Retention
- Customer Performance (Email/SMS)

**Utility**
- Account Health

**Data Source Dashboards** (1:1 mirrors of source platforms)
- Amazon Settlement Report
- Amazon All Orders Report
- Amazon Seller Central Business Reports
- Gorgias
- Klaviyo Campaign & Flow Performance
- Loop Returns
- Okendo Reviews
- ShipBob
- Shipstation
- Subscription
- Walmart Marketplace Account Sales Report
- Yotpo

This yields ~40 distinct prebuilt surfaces — well above the T1 norm of 8-20.

## Data they expose

### Source: Shopify / Magento / BigCommerce
- Pulled: orders, line items, customers, products, refunds, inventory, fulfilment status, discount codes, transactions.
- Computed: AOV, UPT (Units Per Transaction), gross sales, net sales (currency-adjusted), repurchase rate, time between orders, RFM segment tags (recency/frequency/monetary), HVC (high-value-customer) segments, churn cohorts, channel mix %.
- Multi-store consolidation across all Shopify integrations into a unified UOS schema.

### Source: Amazon (Vendor Central + Seller Central)
- Pulled: settlement reports, all-orders reports, business reports (sessions / page views / buy box %), inventory health, traffic.
- Computed: Amazon-vs-DTC channel comparison; cross-channel cannibalization analysis.
- Distinct dashboards (Settlement, All Orders, Business Reports) preserve raw Amazon report semantics.

### Source: Meta Ads / Google Ads / TikTok / Pinterest / Snapchat / Bing / Criteo / AppLovin
- Pulled: campaign / adset / ad spend, impressions, clicks, vendor-reported conversions, vendor-reported ROAS.
- Computed: blended marketing efficiency, CPA, CPO, ROAS across **eight attribution models** (see Attribution section), gross-margin-aware variants, "vendor-reported vs Daasity-attributed" deltas.

### Source: Google Analytics (UA + GA4)
- Pulled: sessions, pageviews, traffic source dimensions (UTM source/medium/campaign/content/term), conversions.
- Computed: First-Click, Last-Click, Assisted, Last-Click + Assisted, Last Ad Click, Last Marketing Click models keyed off UTM dimensions.

### Source: Klaviyo / Attentive / Postscript / Iterable
- Pulled: campaign + flow performance, sends, opens, clicks, attributed revenue.
- Computed: Customer Performance (Email/SMS) cohort views.

### Source: Fairing / KnoCommerce (post-purchase surveys)
- Pulled: verbatim survey responses ("How did you hear about us?").
- Computed: Survey-Based Channel and Survey-Based Vendor dimensions on every order; orders without survey data show NULL.

### Source: Discount codes (Shopify orders)
- Pulled: discount codes attached to each order.
- Computed: Discount Code Attribution — "assigns credit to different channels and vendors based on the discount code value associated with an order"; designed for podcasts/influencers using promo codes.

### Source: SPINS / Nielsen (syndicated retail panel)
- Pulled: total US MULO, total US Natural, total US xAOC market data; competitive brand share.
- Computed: market opportunity vs brand performance benchmarks (URMS schema dashboards).

### Attribution windows / models (eight total per Daasity):
1. First-Click
2. Last-Click
3. Assisted ("assigns full credit ($) for an order to all channels and vendors that had a non-last-click touchpoint prior to purchase")
4. Last-Click + Assisted (combined)
5. Last Ad Click
6. Last Marketing Click
7. Survey-Based (Fairing-driven)
8. Vendor-Reported (raw platform-claimed)
Plus: **Custom Attribution** — "uses a waterfall approach to sift through multiple attribution data sources" with user-defined priority ranking, and **Discount Code Attribution** as a parallel dimension.

## Key UI patterns observed

### Home Dashboard
- **Path/location:** Top-level / "Home" landing screen.
- **Layout (prose):** Single page containing top-line KPIs segmented by channel, with three sub-tabs labelled **Ecommerce**, **Marketing**, **Retail** — each containing the key metrics for the respective department. Click-through links from each KPI tile drill into the corresponding specialized dashboard.
- **UI elements (concrete):** Embedded Looker tiles (Growth-tier users interact via "Daasity web application, which is an embedded Looker experience"); KPI cards; channel-segmented metric groupings; period comparison.
- **Interactions:** Click-through drill-down to operational dashboards; date-range filtering; channel filtering.
- **Metrics shown:** Channel-segmented KPIs across e-commerce, marketing, retail (specific labels not surfaced in public docs).
- **Source:** `help.daasity.com/core-concepts/dashboards`. UI screenshots are present in docs but require login to view full layout.

### Company Overview (Omnichannel)
- **Path/location:** Templates Library > Omnichannel > Company Overview.
- **Layout (prose):** Three vertically-stacked sections. (1) **Top KPIs (Current Period)**: Total Dollar (Net) Sales currency-adjusted, YoY or Period-over-Period % change, **Channel Mix %** showing top 5 channels by contribution plus a rolled-up "all others." (2) **Weekly Sales by Channel** stacked chart: horizontal axis = week start dates (fiscal or calendar), vertical axis = total dollar sales, current period and prior/year-ago period as separately colored bands, optional YoY overlay line. (3) **Weekly Detail Table** with columns Week, Total Sales, YoY %, Change vs Prior Week, Channel Sales (both $ and % mix), exportable.
- **UI elements:** Stacked bars with multi-period band coloring; YoY overlay line; exportable table with mixed $-and-% columns.
- **Metrics shown:** Net sales (currency-adjusted), YoY %, PoP %, channel mix %, weekly sales by channel.
- **Source:** `help.daasity.com/core-concepts/dashboards/report-library/omnichannel/company-overview`.

### Flash Dashboards (Daily / Weekly / Daily-vs-Plan / Hourly)
- **Path/location:** Templates Library > Ecommerce Performance > Flash Dashboards (four variants).
- **Layout (prose):** Four near-identical dashboards distinguished by comparison period, sharing the same data model. Daily Flash and Daily Flash vs. Plan use **Store Type** and **Store Integration Name** filters; Weekly Flash uses a **Traffic, Order, Revenue Filter**. Hourly Flash is Shopify-only.
- **UI elements:** Filter strip; refresh button (filter changes do not auto-apply — "When you Toggle the Dashboard Filters the Data on the Dashboards will update after you click the Refresh Button"); 4-5-4 retail calendar comparison logic baked into period definitions.
- **Interactions:** Filter toggle then explicit refresh; default filters set to "ALL Store Integrations combined"; Hourly Flash refreshes hourly, others nightly with "LAST COMPLETE DAY DATA."
- **Metrics shown:** Sales / orders / traffic / revenue at daily, weekly, hourly granularity; Daily-vs-Plan adds plan-attainment columns.
- **Source:** `help.daasity.com/core-concepts/dashboards/report-library/ecommerce-performance/flash-dashboards`.

### Attribution Deep Dive
- **Path/location:** Templates Library > Acquisition Marketing > Attribution Deep Dive.
- **Layout (prose):** Built on the "Marketing Attribution explore" which rolls up order-level attribution to channel/vendor level. The marketing blog post describes the dashboard as comparing **eight attribution models** side-by-side, with a discount-code performance section "monitoring multiple discount codes and their contributions to net sales" feeding into customizable attribution logic, and a **"Customizing Attribution Logic"** ranking/prioritization control where users rank methods (e.g. "Survey-Based attribution as a default option integrated with third-party tools like Fairing").
- **UI elements:** A **"Dynamic Attribution Method" filter-only field** lets users switch between attribution methods inside a single report without rebuilding the report. **"Assisted lift" comparison** visualization shows how non-last-click channels contribute to total sales. Discount code performance tables list "dozens of tracked codes." Channel definitions are user-customizable (mark a channel as ad vs. marketing).
- **Interactions:** Toggle between models via the Dynamic Attribution Method filter; rank models for Custom Attribution waterfall; filter by metric (CPA / CPO / gross margin / net sales / ROAS / orders / new-customer orders); UTM dimension drill-down for GA-based models.
- **Metrics shown:** CPA, CPO, gross margin, net sales, gross sales, ROAS, orders, new-customer orders — by channel × vendor × attribution model.
- **Source:** `help.daasity.com/advanced/marketing-attribution/attribution-overview`, `daasity.com/post/marketing-attribution-dashboard`. UI details: only **partial** — public pages confirm the model toggle and discount-code section but **don't enumerate explicit Pixel / Survey / Promo / MTA tab labels**; instead the four attribution lenses (pixel-via-GA, survey, discount-code, vendor-reported) appear to be **dimensions/dynamic-filter values within one explore**, not separate sub-tabs as the assignment hypothesized. The Survey-Based view exposes three explicit dimensions in the Order Attribution view: **Survey Response** (verbatim text), **Survey-Based Channel**, **Survey-Based Vendor**.

### Site Analytics & Attribution
- **Path/location:** Templates Library > Ecommerce Performance > Site Analytics & Attribution.
- **Layout (prose):** Single dashboard built on the **Traffic explore**. A top-of-dashboard **"Linked Store Type" filter** toggles between Amazon and ecommerce data, because most segmented visualizations apply to GA data and not Amazon. Pulls from Universal Analytics / GA4 plus Amazon Business Reports (when "Sales and Traffic" is enabled).
- **UI elements:** Single global filter at top; tile-based Looker layout (specifics not surfaced publicly).
- **Source:** `help.daasity.com/core-concepts/dashboards/report-library/ecommerce-performance/site-analytics-and-attribution`. UI details not available — only feature description.

### Retention Dashboard
- **Path/location:** Templates Library > Retention Marketing > Retention.
- **Layout (prose):** Three section blocks. (1) **Performance by Customer Segment** — two comparative charts side-by-side showing current and prior month for gross sales, orders, AOV, units per order, average unit revenue. Customers are tagged into RFM segments at month start and remain static through that month. (2) **Time Between Orders** — repurchase interval visualizations driving campaign cadence decisions. (3) **Customer Movement & Historical Performance** — cohort segment-transition tracking (single-buyer → multi-buyer → HVC) and churn/lapsed-customer monitoring.
- **UI elements:** Comparative side-by-side period charts; cohort movement visualization; segment tags (Non-buyer, Single buyer, Multi-buyer, HVC, Churning, Lapsed).
- **Interactions:** Wholesale orders/customers excluded from retention calculations.
- **Metrics shown:** Gross sales, orders, AOV, units per order, average unit revenue, time between orders, segment transition rates.
- **Source:** `help.daasity.com/core-concepts/dashboards/report-library/retention-marketing/retention`.

### LTV & RFM
- **Path/location:** Templates Library > Acquisition Marketing > LTV & RFM.
- **Layout (prose):** Documentation surfaces only one explicit section — a **"Layer Cake Graph"** that "breaks down your customers by the quarter that they were acquired" and stacks each cohort's revenue contribution over time. Other sections exist (per the dashboard image) but aren't enumerated in public docs.
- **Source:** `help.daasity.com/core-concepts/dashboards/report-library/acquisition-marketing/ltv-and-rfm`. UI details only partially available.

### Templates Library / Collections
- **Path/location:** Left-side navigation menu.
- **Layout (prose):** Left rail lists Template Library and Collections (folder-style). "New Dashboard" button in the top-right opens a "From Template" picker. Custom dashboards are built via the Explore drag-and-drop interface.
- **Source:** `help.daasity.com/core-concepts/dashboards`.

### Audiences (reverse-ETL)
- **Path/location:** Top-level nav > Audiences.
- **Layout (prose):** Segment builder over RFM/LTV outputs; pushes segments **nightly** into Klaviyo, Attentive, Meta custom audiences, Google Ads customer match. UI specifics not surfaced publicly; functionally analogous to Hightouch / Census but bundled.
- **Source:** `daasity.com/ecommerce-analytics`, search summary noting "high-LTV or RFM segments nightly into Klaviyo, Attentive, Meta, and Google Ads."

### AI Conversational Analyst (in-progress)
- **Path/location:** Likely a chat surface (location not yet documented publicly).
- **Layout (prose):** Daasity describes a forthcoming "AI-Powered Conversational Analyst": "Ask questions and get instant answers from your data, no more digging through dashboards required." Positioned as a set of AI agents giving "tactical recommendations." A **Promotion Predictor** capability ("model and forecast campaign impact before deployment") was mentioned in the October rollout.
- **Source:** `daasity.com/post/from-dashboards-to-ai-analysts-how-daasity-is-transforming-analytics`. UI details not available — only positioning.

## What users love (verbatim quotes, attributed)

- "I've used Glew, TripleWhale, Lifetimely and more and this is by far the best tool I've used. The customer support is unparalleled and they can actually get me answers to questions I've been trying to get at for months." — Béis (operates two 8-figure stores), Shopify App Store, March 3, 2022.
- "An extremely intuitive and valuable tool" with appreciation for "the ability to centralize each of our data feeds into one all-encompassing view." — Helinox, Shopify App Store, August 4, 2020.
- "Lots of great integrations & dashboards" with praise for the support team's helpfulness in creating custom reports. — tentree CA, Shopify App Store, June 9, 2022.
- "Basically a no brainer" after connecting multiple data sources. — Detox Mode, Shopify App Store, May 22, 2020.
- "We use [Daasity data] for internal decisions, board discussions, and investor presentations." — bioClarity, Shopify App Store, May 21, 2020.
- "The data warehouse Daasity built has been a huge value." — Detox Market CEO, quoted on `daasity.com/why-daasity`.
- Positive notes on premium-support investment giving "a more complete view of performance at every stage of the funnel for our Shopify site" and helping organize "Shopify + Amazon data all in one place." — ResBiotic, Shopify App Store, February 27, 2023.

## What users hate (verbatim quotes, attributed)

- "The implementation was possibly the worst I have ever experienced" with "an almost complete lack of project management and QC," noting "the entire team we were working with, including their cofounder, left the company suddenly" and the customer "terminated our contract" with unexpected additional costs after six months of work. — The Foggy Dog, Shopify App Store, July 26, 2025.
- Mixed review noting **UX could improve** despite praise for data manipulation capabilities. — Keto Hana, Shopify App Store, November 8, 2022.
- "Steep learning curve and coding-intensive nature... challenging for quick prototyping." — paraphrased pattern across G2 reviews (G2 page returned 403 to direct fetch; pattern surfaces in aggregated review summaries).
- "Not all marketing platforms are yet automated, requiring manual entry, and some reports don't refresh in real time." — paraphrased complaint from G2/SourceForge aggregations.
- "Pricing remains fully custom and depends on data volume, connector count, and services. Teams expecting SaaS-style transparent tiers may need a demo and scoping call before budgeting." — aisystemscommerce.com review, 2026.
- Can "feel like overkill for pure Shopify DTC brands under $5M or teams that prefer building everything in-house." — aisystemscommerce.com review, 2026.

(Reddit/Twitter/DTC-X coverage is sparse for Daasity; the brand sits in an enterprise-CPG niche and doesn't show up loudly in r/shopify or r/dtc threads. Limited public reviews available outside the 52-review Shopify App Store listing.)

## Unique strengths

- **Department-organized IA**, not source-organized. The Home dashboard tabs are **Ecommerce / Marketing / Retail** — three departments, each with their own KPIs. The full Report Library is grouped by acquisition / retention / retail / ecommerce / data-source, not by Shopify / Meta / Google. This answers user questions ("how is retention?") rather than data-source questions ("show me Klaviyo data").
- **Eight attribution models in one explore**, switchable via a "Dynamic Attribution Method" filter without redesigning the report. Plus a **Custom Attribution waterfall** that lets brands rank fallback sources (e.g. survey → discount-code → GA last-click) for canonical attribution.
- **Discount Code Attribution as a first-class dimension** — explicitly aimed at podcast/influencer/promo-code campaigns where pixel and UTM tracking fail.
- **Survey-Based Attribution exposes verbatim survey response** as a queryable dimension (Survey Response, Survey-Based Channel, Survey-Based Vendor). Most competitors only expose the derived channel.
- **URMS / SPINS / Nielsen syndicated retail data** built into core schemas (Total US MULO, Total US Natural, Total US xAOC). No DTC-only competitor (Triple Whale, Northbeam, Polar) integrates panel data at this depth.
- **Reverse-ETL bundled** (Audiences pushes segments to Klaviyo / Attentive / Meta custom audiences / Google customer match nightly). Polar and Triple Whale have similar features; Northbeam does not.
- **Embedded warehouse architecture** — the data lives in customer-owned Snowflake/BigQuery (or Daasity-managed equivalents). This is the "data ownership and flexibility" angle competitors don't match.
- **Customer roster credibility:** Manscaped, Rothy's, Poppi, SC Johnson, Tweezerman, Béis, Method, Yogi Tea, Honest Kitchen, Fashion Nova — heavyweight CPG and digitally-native brands.
- **Hourly Flash dashboard** (Shopify-only) is the only sub-daily refresh in the product; gives intraday visibility for flash-sale / launch days.

## Unique weaknesses / common complaints

- **Custom-implementation horror stories.** The July 2025 Foggy Dog review is brutal and recent — "possibly the worst I have ever experienced," cofounder departures mid-engagement, contract termination. Pattern of "shines when you can pay for premium support, dies when you can't."
- **Pricing opacity.** Public marketing has no pricing page; only the Shopify App Store entry exposes the $1,899/month figure. Buyers cannot self-serve.
- **Steep learning curve.** Repeated complaint that the platform is coding-intensive and not friendly for "quick prototyping" — the embedded Looker UX, while powerful, is a barrier for non-technical operators.
- **Refresh latency.** Most dashboards show last-complete-day data with nightly refresh. Filter changes require a manual refresh-button click. No real-time except Hourly Flash.
- **WooCommerce absent** from the public integrations list — Daasity is a Shopify/Magento/BigCommerce/Salesforce shop.
- **GSC absent** — GA4 is supported but Google Search Console isn't on the integrations page.
- **"Overkill for sub-$5M brands."** The pricing and complexity assume $5M-$1B+ scale; the platform is explicitly not aimed at SMBs.
- **Reverse-ETL is nightly only** — no real-time segment activation.

## Notes for Nexstage

- **Department-organized IA is Daasity's most distinctive UX choice and is directly relevant to Nexstage's information architecture.** Their Home dashboard splits into Ecommerce / Marketing / Retail tabs — three personas, three sets of KPIs, one screen. This is an alternative to the 6-source-badge lens model: users pick a department lens rather than a data-source lens. Worth contrasting in IA decisions.
- **Eight-model attribution toggle inside one explore is the pattern users actually want.** The "Dynamic Attribution Method" filter-only field switches model without rebuilding the report. The promised Pixel / Survey / Promo / MTA tab structure does NOT explicitly exist as named tabs in public docs — instead the four "lenses" (GA-based pixel models, Fairing survey, Discount Code, Vendor-Reported) coexist as **filterable dimensions in a single Marketing Attribution explore**. Nexstage's 6-source badges are conceptually similar but more granular per-metric.
- **Discount Code Attribution as a dimension** is a feature gap most competitors lack. Worth considering for Nexstage's attribution config (podcasts/influencers are a real channel for SMBs).
- **Survey response stored verbatim** as a queryable dimension is a useful pattern — keeps the raw response accessible for ad-hoc digging without baking transformation logic into the warehouse.
- **WooCommerce gap is a real wedge.** Daasity does not support Woo. SMB merchants on Woo are entirely uncatered to by enterprise-tier players.
- **"Less than the cost of a data engineer" is the value-prop frame Daasity uses for pricing** — i.e. they anchor against a $120-180k headcount, not against Triple Whale's $129/mo. Different anchor than Nexstage's SMB positioning.
- **Embedded Looker (Growth tier) → direct Looker (Enterprise)** is a tiering gimmick: same backend, different power-user surface. Nexstage's Inertia/React custom UI side-steps this entirely.
- **AI Conversational Analyst is announced, not shipped** as of the source post — a roadmap signal, not a feature in the product yet.
- **Reverse-ETL is bundled in Audiences** — this is a category overlap with Hightouch/Census that no Nexstage competitor at SMB tier has solved. Activating segments to Klaviyo/Meta is a natural extension Nexstage might consider once core analytics ship.
- **The 4-5-4 Retail Calendar baked into Flash dashboards** is a CPG/retail convention SMBs typically don't need, but signals how deep their CPG focus runs.
- **No public screenshots usable for `_screens/` capture** — most dashboard images live behind login or in marketing pages with watermarked/decorative imagery, and per task constraints PNGs were not captured. UI descriptions in this profile are sourced from public docs prose, marketing blog posts, and review-site descriptions.
