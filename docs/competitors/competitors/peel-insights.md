---
name: Peel Insights (Peel Analytics)
url: https://www.peelinsights.com
tier: T1
positioning: Retention/LTV analytics for Shopify + Amazon DTC brands; replaces Google Analytics, Looker/Data Studio, Daasity, OrderMetrics for cohort and customer-LTV reporting.
target_market: Mid-market DTC; $2M-$25M+ annual revenue brands. Tiers gated by monthly orders (6k / 16k / 29k / 62k+). Shopify-native with Amazon and Walmart connectors; not a Woo product.
pricing: Core $179/mo (annual) or $199/mo monthly; Essentials $449/$499; Accelerate $809/$899; Tailored custom. 7-day free trial, no credit card required.
integrations: Shopify, Amazon Seller Central, Walmart, Klaviyo, Attentive, Postscript, Meta Ads, Google Ads, TikTok Ads, Pinterest Ads, Snapchat (coming soon), Google Analytics 4, Recharge, Skio, Smartrr, Awtomic, Bold Subscriptions, Loop Subscriptions, Stay.ai, Fairing (formerly EnquireLabs), KnoCommerce, Slack, Snowflake (data export)
data_freshness: Daily (initial sync 4-24 hours; daily insights/digest cadence)
mobile_app: No (acknowledged gap in third-party reviews)
researched_on: 2026-04-28
sources:
  - https://www.peelinsights.com
  - https://www.peelinsights.com/pricing
  - https://www.peelinsights.com/magic-dash
  - https://www.peelinsights.com/magic-dashboards
  - https://www.peelinsights.com/solutions
  - https://www.peelinsights.com/subscription-analytics
  - https://www.peelinsights.com/integrations
  - https://www.peelinsights.com/post/peel-quickstart-guide
  - https://www.peelinsights.com/post/what-is-rfm-analysis
  - https://www.peelinsights.com/post/rfm-email-roi
  - https://www.peelinsights.com/post/the-rfm-playbook
  - https://www.peelinsights.com/post/product-update-ui-update-for-reporting
  - https://www.peelinsights.com/post/product-update-attribution-10-new-metrics-more
  - https://www.peelinsights.com/post/product-update-new-analysis-templates
  - https://help.peelinsights.com/
  - https://help.peelinsights.com/docs/magic-dashboards
  - https://help.peelinsights.com/docs/magic-dash-faqs
  - https://help.peelinsights.com/docs/what-can-you-ask-the-magic-dash
  - https://help.peelinsights.com/docs/rfm-analysis
  - https://apps.shopify.com/peel-insights
  - https://apps.shopify.com/peel-insights/reviews
  - https://expresscheckout.beehiiv.com/p/introducing-magic-dash-by-peel-insights
  - https://aazarshad.com/resources/peel-insights-review/
  - https://www.smbguide.com/review/peel-insights/
  - https://www.digismoothie.com/app/peel
  - https://www.relaycommerce.io/peel-insights
  - https://ecommercetech.io/apps/peel-insights
---

## Positioning

Peel sells itself to mid-market Shopify (and Amazon/Walmart) DTC operators as the "all-in-one analytics software Shopify brands trust to answer their hardest LTV questions." The headline claim is an "80% reduction in time looking for data" via pre-built retention reports (cohort retention, RFM, market basket, repurchase rate by city, audience overlap). It positions against Google Analytics, Looker/Data Studio, Daasity, and OrderMetrics — explicitly framed as "purpose-built reports unavailable elsewhere" rather than a general BI tool. Since late 2023 the narrative has shifted from "automated analyst" to AI-first via the Magic Dash product, marketed as a "generative BI insights platform" / "AI Retention Strategist" that auto-builds dashboards in response to natural-language questions.

## Pricing & tiers

| Tier | Price (annual / monthly) | Order ceiling | Stores | What's included | Common upgrade trigger |
|---|---|---|---|---|---|
| Core | $179 / $199 | >6,000 monthly orders | 1 | All connectors (Shopify+Amazon, GA4, ad networks, Klaviyo, Attentive, Fairing, Kno, all subscription platforms), RFM, 30+ cohort/product/journey metrics, custom dashboards, Slack/email digests, scheduled CSVs, COGS, multi-touch attribution with UTM, Snowflake SQL access. **No CSM onboarding, no chat support.** | Hits 16k orders/mo or needs >1 store |
| Essentials | $449 / $499 | >16,000 monthly orders | up to 3 | Same as Core + 1 CSM onboarding credit/mo + chat support; extra credits $50 ea | Hits 29k orders/mo or needs >3 stores |
| Accelerate (Popular) | $809 / $899 | >29,000 monthly orders | up to 7 | Same + 2 CSM onboarding credits/mo | Hits 62k orders/mo or unlimited stores |
| Tailored | Custom | ≥62,000 monthly orders | Unlimited | Same + 5 CSM onboarding credits/mo | — |

A "Free" listing is also visible on the Shopify App Store (16k order ceiling, up to 3 stores); a separate review article cites legacy Starter at $50/mo. Pricing is **transparent** on the website (rare in this category) but the entry point ($199) is high relative to competitors aimed at sub-$2M brands. Multiple third-party reviews flag pricing as the primary objection ("a bit more expensive than other analytics applications available, which can be a limitation for start-ups with limited budgets" — smbguide.com).

## Integrations

**Sources (pull):**
- Ecommerce: Shopify (primary), Amazon Seller Central, Walmart
- Subscriptions: Recharge, Skio, Smartrr, Awtomic, Bold, Loop, Stay.ai
- Email/SMS: Klaviyo, Attentive, Postscript
- Ads: Meta, Google Ads, TikTok, Pinterest; Snapchat marked "Coming Soon"
- Web analytics: Google Analytics 4
- Survey/zero-party: Fairing (EnquireLabs), KnoCommerce
- Data warehouse: Snowflake (Core+ tier offers SQL access)

**Destinations (push):**
- Audience export to Klaviyo, Attentive, Postscript, Meta (Facebook Custom Audiences)
- Slack and email digests (Daily Insights Report)
- CSV/Google Sheets/Excel sync (daily refresh) and one-click downloads

**Coverage gaps:**
- **No WooCommerce.** Shopify-native — explicit non-coverage of Woo.
- **No GSC.** Search Console is not in the integration roster.
- **No native ad-platform attribution beyond UTM/GA-style.** Their multi-touch attribution leans on "Google Analytics definition and ads social media sources" + Shopify's first/last visit handling.
- Snapchat ads flagged "coming soon" as of public marketing.

## Product surfaces (their app's information architecture)

Mapped from the help center index, quickstart guide, and product pages:

- **Home / RFM Analysis page** — landing screen post-login; shows RFM 5x5 grid + "North Star" KPI strip (orders per customer, LTV per customer, returning orders %).
- **Essentials tab** — "collection of your most important metrics": Repurchase Rate by Cohort, Market Basket Analysis, retention/product overviews.
- **Dashboards** — list of pre-built and custom dashboards: Customer, Subscriptions, Marketing, Multitouch Attribution. User-created dashboards composed of "Tickers" (single numbers) and "Legends" (graphs).
- **Magic Dash (AI Dashboards)** — natural-language Q&A interface that auto-generates a dashboard with widgets + AI headlines.
- **Templates Library** — pre-made dashboards, "Slices" (special pre-canned reports), and audiences; "install" button applies to your data.
- **Explore (Custom Analysis)** — build-your-own report with multi-filters, metrics, and raw data.
- **Audiences** — customer-segment builder (filters: products, SKUs, tags, channels, locations, campaigns, LTV, discount codes, purchase count) → push to Klaviyo / Attentive / Postscript / Meta or download CSV.
- **Audience Overlap** — Venn-style report comparing audience intersections.
- **Audience Traits** — demographic/behavior breakdown of a chosen audience.
- **Cohort Analysis** — retention + revenue cohorts, monthly/weekly/quarterly grouping; 36+ cohort metrics.
- **Subscription Analytics** — MRR, new subscribers, growth rate, churn, LTV per subscriber cohort, conversion funnel from one-time → subscriber, subscription cohort retention.
- **Product Analytics** — Market Basket Analysis, Customer Purchasing Journey, Product Sales by Vendor, Product Ranking, Product Popularity by Order Number, Products by Source/Campaign & Channel.
- **Attribution** — Orders by Channel, Revenue by Channel, New Revenue by Channel, Returning Revenue by Channel, Payback Period, Multi-Touch Attribution Segments.
- **Marketing Metrics** — 30+ metrics: Ad Spend, Ad Clicks, Ad Impressions, ROAS, ROI, CAC, CPA, Cost Per Session, LTV-to-CAC.
- **Goals** — KPI targets per metric per period.
- **Annotations** — notes overlaid on graphs to mark campaign launches / spikes.
- **Daily Insights Report** — automated Slack/email digest of trending metrics.
- **Drill Downs** — global pattern: clicking any metric/widget surfaces a deeper view.
- **Time Comparison** — period-over-period overlays.
- **Connections / Datasets** — integration management screen ("Add Datasource") with connection-health view.
- **Account / Billing** — accessed via "Pal" mascot avatar at the bottom of the left sidebar.
- **Read-Only Dashboards** — share link for stakeholders without seat.
- **Scheduled Dashboards** — recurring email/Slack delivery.

That is roughly **18-22 distinct surfaces**, consistent with a T1 mid-market product.

## Data they expose

### Source: Shopify
- Pulled: orders, line items, customers, products, refunds, discounts, shipping fees, taxes, customer/product/order tags, metafields.
- Computed: AOV (gross/net/total — three flavors after Dec 2020 UI update), Customer Lifetime Value, LTV by Cohort, LTV by Customer, LTR (Lifetime Revenue), Repeat Orders Rate per Cohort, Repurchase Rate, Customers Returning Rate, Days Since First Order, Customers per Number of Orders, Cohort AOV per Month, Discounts by Cohort, Refunds by Cohort, Gross/Net Sales by Cohort, Gross Margin Amount, Gross Margin Rate, Net Sales Per Customer, RFM scores (1-5 each on R/F/M).
- COGS: per-product manual entry (referenced in FAQ "How do COGS work on Peel?").

### Source: Amazon Seller Central
- Pulled: orders, customers (with caveat — same-customer matching across Shopify and Amazon is acknowledged as imperfect in their FAQ), FBA vs FBM segmentation.
- Computed: same metric library as Shopify.

### Source: Meta / Google / TikTok / Pinterest Ads
- Pulled: ad spend, impressions, clicks, conversions per ad-platform definitions.
- Computed: ROAS, ROI, CAC, CPA, Cost Per Acquisition, Cost Per Session, LTV-to-CAC ratio, Payback Period; all available filtered by channel, platform, campaign, UTM source.

### Source: Google Analytics 4
- Pulled: web sessions, ecommerce transactions.
- Used as one input to multi-touch attribution model alongside Shopify first/last visits.

### Source: Klaviyo / Attentive / Postscript
- Push-only for audiences (no inbound metrics observed in public docs).

### Source: Recharge / Skio / Smartrr / Awtomic / Bold / Loop / Stay.ai
- Pulled: subscription orders, subscription status changes, cancellation reasons (Recharge), MRR-relevant events.
- Computed: 40+ subscription metrics including MRR, churn rate, subscriber LTV, subscriber cohort retention, one-time → subscriber conversion rate, average time between purchases per subscription cohort.

### Source: Fairing / KnoCommerce
- Pulled: post-purchase survey responses (zero-party data) used to slice all of the above.

**Attribution windows:** Multi-Touch Attribution feature; specific lookback windows not published. Documentation describes a model that uses "Google Analytics definition and ads social media sources" plus "Shopify's handling of first and last visits." Trend windows include rolling 7-day and 30-day periods.

## Key UI patterns observed

### Magic Dash (AI Dashboards)

- **Path/location:** Sidebar > Dashboards > "Create a new Dashboard" (selecting the Magic option).
- **Layout (prose):** Conversational input field at the **top-left** of the dashboard page. After user submits a question, the system "selects which reports would tell the story best" and renders a multi-widget dashboard. Generated dashboards include line charts, bar charts, stacked bars, and pie charts; widgets carry titles, descriptions, date ranges, and grouping settings, all editable. After creation the input remains for follow-up questions ("after your Dash is created, you can use the input field located at the top left corner of the page to ask additional questions"). System is "contextual" — supports follow-ups against the prior question or pivots to new ones.
- **Narrative construction (Magic Insights):** Each widget gets an AI-generated **headline** plus a description. Per the help center: "Magic Insights" are AI-created **headlines refreshing every seven days** for any dashboard viewed in the past 7 days. Users toggle insights on/off and request fresh ones with a "New Insights" button. They can also click any AI-suggested headline/description on the right rail and "set it as a headline or description" — overwrite the auto-generated copy with their own. Annotations feed back as additional context to the LLM. The pitch is explicitly **newspaper-style**: "look like newspaper headlines with revenue opportunities being delivered to you everyday" (Express Checkout / Beehiiv interview with Peel CEO Ben). The system "reads the data and the charts, and explains why they're important — in plain speak."
- **Question categories supported (per docs):** Revenue ("What is the correlation between ad spend and lifetime value?"), Retention ("What is my repurchase rate?", "Show me retention by Product type"), Product ("Should I discontinue any of my products?", "What are the top 3 products that new customers purchase?"), Orders & Customers ("What is my AOV?", "Compare the number of orders from new customers vs returning customers for the past 6 months"), Marketing ("Which marketing channels have the best ROAS over the last 30 days?", "What is the LTV of customers coming from paid search compared to email?"). Stated **constraints**: "cannot provide answers to forecasting or predictive queries"; questions must "mirror the way items are labeled in your data"; can answer Market Basket and RFM questions but **not** "Product Journey" or "predictive metrics"; cannot generate an Audience directly from a widget.
- **Data security framing:** Help center states "your data is secure and is not being shared with an AI" (i.e. processing within their own infrastructure) — but the in-product create-flow warning reads "your data is shared with the AI." This contradiction is in their own docs.
- **Source/screenshot:** No public clean screenshots saved; described in https://help.peelinsights.com/docs/magic-dashboards and https://expresscheckout.beehiiv.com/p/introducing-magic-dash-by-peel-insights.

### RFM Analysis (Home Page)

- **Path/location:** Default landing surface post-login (the doc page is titled "RFM Analysis & Home Page").
- **Layout (prose):** Top row is a "North Star" KPI strip showing **orders per customer, LTV per customer, returning orders % (weekly)**. Below it sits the canonical **5×5 grid**. X-axis = Recency bucketed into 5 groups (days since last order). Y-axis = combined Frequency (total orders) + Monetary value (LTR) bucketed into 5 groups. Right of the grid is a **filter panel** with R / F / M toggles that re-pivots the grid to show "the average number of days each of those groups took to come back and repurchase, how many orders on average each group is making, and the average monetary value in LTR for each group."
- **UI elements (concrete):** **Square cells of fixed size** — explicitly "the size of the squares in the RFM Analysis does not change if the number of customers in each square increases or decreases" and "the sections of the grid are not proportionally scaled to the percentage of customers in that group." Cells are **labeled with one of 10 named segments**: Champions, Loyal Customers, Potential Loyalist, New Customers, Promising, Need Attention, About to Sleep, Can't Lose Them, At Risk, Hibernating. Mental model used in marketing copy: "customers start out in the bottom right, and the goal is make to the top right." (Note: this implies bottom-right = new/low-value, top-right = champions; their docs are explicit about the orientation.)
- **Interactions:** **Click any cell** → opens a flow to "make an Audience" (name it, see customer count, push to Klaviyo / Attentive / Meta, or download CSV). Right-side R/F/M filter swaps the metric shown per cell. Specific colors and exact hover-tooltip behavior are **not detailed in public docs** (their public assets show the grid styled but exact hex/hover content not published).
- **Metrics shown:** Per cell — orders per customer, LTV per customer, average days since last order; aggregate for selected segment.
- **Source/screenshot:** https://help.peelinsights.com/docs/rfm-analysis ; https://www.peelinsights.com/post/what-is-rfm-analysis ; https://www.peelinsights.com/post/rfm-email-roi (page captions screenshots as "The RFM Framework" and "RFM Segments on Peel"). UI details on colors and exact hover behavior not available — only verbal description seen on docs/marketing pages. **Do not fabricate visual specifics beyond what is quoted above.**

### Cohort Analysis

- **Path/location:** Sidebar > Cohort Analysis (or accessed via Essentials tab).
- **Layout (prose):** Table-style cohort heatmap grouped by acquisition month/week/quarter (user toggle). Categories: Cohorts Retention (8 metrics), Cohorts Revenue (8 metrics), Subscription Cohorts (17 metrics) — total of 36 cohort-specific metrics organized in a tree on the left. Charts include cohort tables, **cohort curves**, **pacing graphs**, and number "Tickers." Public guides reference screenshots labeled "Customer Retention by Cohort," "Average Lifetime Revenue," "Repurchase Rate by Cohort."
- **UI elements (concrete):** Specific cell colors and saturation gradient not published. Cohorts stack vertically; periods (months 0, 1, 2…) stack horizontally — standard cohort-table convention. Drill into any cohort = "Single Cohort View" surface.
- **Interactions:** Switch grouping (month/week/quarter), drill into any cohort row, save report → add to dashboard, schedule dashboard via email/Slack.
- **Metrics shown:** Customer Retention by Cohort, MoM Retention, Repeat Orders Rate per Cohort, LTV by Cohort, Lifetime Revenue by Cohort, Cohort AOV per Month, Discounts/Refunds by Cohort, Customers Returning Rate, Days Since First Order, Repurchase Rate.
- **Source/screenshot:** https://www.peelinsights.com/post/your-guide-to-cohort-analysis ; https://help.peelinsights.com/ (Cohort Analysis section). UI color details not available from public sources.

### Audiences

- **Path/location:** Sidebar > Audiences.
- **Layout (prose):** Audience builder with filter chips (purchase count, products, SKUs, customer tags, locations, channels, campaigns, discount codes, LTV, order tags). On save the audience appears as a row in a list; clicking opens the Audiences Overview & Page Breakdown.
- **UI elements (concrete):** "Send" button on each audience to push to Klaviyo / Attentive / Postscript / Meta. Audience Overlap report visualizes intersection of two or more audiences (Venn-style; exact rendering not detailed publicly). Audience Traits surfaces demographic/behavioral breakdown.
- **Interactions:** Build → preview customer count → push to destination or download CSV. Click into an audience to see retention/revenue metrics scoped to those customers.
- **Metrics shown:** Customer count, LTR per audience, retention curves filtered to audience, audience-level cohort metrics.
- **Source/screenshot:** Help center "Audiences" tree.

### Custom Dashboards

- **Path/location:** Sidebar > Dashboards.
- **Layout (prose):** Grid of widgets. Two widget primitives: **"Tickers"** (single-number KPI cards) and **"Legends"** (graphs). Pre-built variants shipped: Customer Dashboard, Subscriptions Dashboard, Marketing Dashboard, Multitouch Attribution Dashboard. Users can create unlimited custom dashboards. Multi-Metric Widget exists for combined views.
- **UI elements (concrete):** Chart type picker offers line charts, bar charts, stacked bars, pie charts, cohort tables, cohort curves, pacing graphs, number trackers. Annotation pins can be placed on graphs to mark campaign moments. "Read Only" share mode generates a link viewable without a paid seat.
- **Interactions:** Drag-to-arrange widgets, schedule dashboard via email/Slack, share read-only link, drill from any widget into the underlying report.
- **Metrics shown:** Any of 150+ metrics.
- **Source/screenshot:** https://help.peelinsights.com/docs/dashboards ; https://www.peelinsights.com/post/product-update-create-dashboards.

### Attribution (Multi-Touch + Channel)

- **Path/location:** Sidebar > Attribution (sub-pages: Orders by Channel, Revenue by Channel, New Revenue by Channel, Returning Revenue by Channel, Payback Period, Multi-Touch Attribution Segments).
- **Layout (prose):** Channel breakdown across "Facebook, Instagram, Paid Search, Organic Search, TikTok, Twitter, Pinterest, etc. (13 in total)" per their attribution update post. Rolling 7-day or 30-day windows for linear metrics like revenue and orders. Channel mapping fed from "Google Analytics definition and ads social media sources" plus Shopify first/last visits.
- **UI elements (concrete):** "With two clicks you can determine the LTV/AOV (& a suite of other metrics) by channels" — per their own copy.
- **Interactions:** Toggle channel, swap window, drill into channel for cohort/LTV view of those customers.
- **Metrics shown:** Orders by Channel, Revenue by Channel, ROAS, ROI, CAC, LTV-to-CAC, Payback Period.
- **Source/screenshot:** https://www.peelinsights.com/post/product-update-attribution-10-new-metrics-more.

### Templates Library

- **Path/location:** Sidebar > Templates.
- **Layout (prose):** Grid of pre-built dashboards, "Slices" (special pre-canned reports), and pre-built audiences. Each card has an **"install" button** that materializes the template into the user's account, applied automatically to their dataset.
- **Notable templates added 2024-2026:** Product Ranking, Order Distribution by Order Threshold, Products by Source/Campaign & Channel, Product Popularity by Order Number.
- **Source/screenshot:** https://www.peelinsights.com/post/product-update-new-analysis-templates.

### Daily Insights Report

- **Path/location:** Configured under Daily Report; delivered to Slack channel or email.
- **Layout (prose):** Email/Slack message with trending metrics across sales, marketing, acquisition, retention. Customizable per recipient.
- **Interactions:** Customize the Daily Reports flow lets user pick metrics; can route to a private Slack channel.
- **Source/screenshot:** Help center "Daily Report" docs.

### Explore (Custom Analysis)

- **Path/location:** Sidebar > Explore.
- **Layout (prose):** Build-your-own report builder with multi-filter, metric picker, and raw-data export. Snowflake-connected version available on Core+ for SQL queries against the user's own warehouse.
- **Source/screenshot:** Help center "Explore" doc.

## What users love (verbatim quotes, attributed)

- "Great app for all things retention and cohort analysis! Easy to use but also excellent service (hi, Jordan!) which has enabled me to have custom reports built out to explore problems unique to the business." — Koh (Australia), Shopify App Store review, March 26, 2026.
- "I've been using Peel for a few months now, and I absolutely love it! It's made creating recaps so much easier. Huge shoutout to Rana Waleed Arfan from the Peel team for going above and beyond." — Little Words Project (US), Shopify App Store, September 10, 2024.
- "We recently onboarded with Peel after using many other analytics tools. Peel is one of the first to have a native Skio connection, improving our day-to-day reporting 10x...big shoutout to the Peel team, especially Kateryna." — Canopy (US), Shopify App Store, December 2, 2024.
- "I have loved working in Peel for our customer retention and insights projects... My favorite features are the custom dashboards and audience building." — Saalt (US), Shopify App Store, September 17, 2024.
- "Always super helpful and responsive! Great visualisation options" — DIRTEA (UK), Shopify App Store, September 5, 2025.
- "Great app and really hands-on support from their team for any reporting needs. They've been great partners!" — Biocol Labs (Portugal), Shopify App Store, April 28, 2025.
- "A great service that simplifies a deep-dive into store metrics! Peel allows us to really understand our customer base... Their team is incredibly helpful." — Austin and Kat (US), Shopify App Store, July 7, 2023.
- "Great app! This is by far the most advanced analytics tool for Shopify and Amazon... Their support is great whenever I need custom filters added." — Will Nitze, IQBAR (US), Shopify App Store, July 12, 2023.
- "Their reporting capabilities are so robust that the ability to customize your search seems unlimited." — Bridget Laye, Saalt (testimonial cited on relaycommerce.io).
- "Peel's reports are magic... unlock answers to burning analytical questions." — Ben Yahalom, President, True Classic (homepage testimonial).
- "Within one day... we had dozens of valuable reports and visualizations." — Annie Ricci, Prima (homepage testimonial).
- "Revenue from bundles is up +236% from last year as a result." — Maude (homepage case-study testimonial).

## What users hate (verbatim quotes, attributed)

Public reviews skew uniformly 5-star (34 reviews on Shopify App Store, all 5-star as of April 2026). Criticism is largely **third-party reviewer** commentary rather than direct user reviews:

- "A bit more expensive than other analytics applications available, which can be a limitation for start-ups with limited budgets." — smbguide.com Peel Insights review.
- "The tool doesn't have a mobile app — a real bummer, especially for busy entrepreneurs who are frequently on the go." — smbguide.com.
- "Users must export 'data dumps' rather than formatted views, making [analysis] time-consuming further Excel manipulation difficult." — smbguide.com (paraphrasing user complaints about export limitations).
- "Limited Ad Platform Integration: Only Meta, Google Ads, TikTok, and Pinterest (Snapchat coming soon)." — smbguide.com.
- "Integration with other websites can be challenging" / "Technical Knowledge Required" for some non-stock connectors. — smbguide.com.
- "There's no free plan with segmentation capabilities." — digismoothie.com.
- "They [have] gone through some turnover but the team at Peel now is great. They're very responsive and quick to help with any needs." — Saltair (US), Shopify App Store, September 12, 2024 *(implicit reference to past team-stability complaints; quoted as worded)*.
- "Subscription is described as 'a bit expensive and can be a drawback to many small business users and individuals.'" — smbguide.com summary.

Limited direct-user negative reviews available — Peel curates a heavily positive App Store presence and does not appear on Trustpilot/Capterra with meaningful review volume; G2 listing exists but full review text was 403-blocked from public scrape.

## Unique strengths

- **The 5×5 RFM grid is canonical here.** No other Shopify analytics tool ships RFM as the default landing screen. The 10 named segments (Champions / Loyal Customers / Potential Loyalist / New Customers / Promising / Need Attention / About to Sleep / Can't Lose Them / At Risk / Hibernating) flow directly into one-click audience export to Klaviyo/Attentive/Meta. Square sizes are **fixed** (not weighted by population), which is a deliberate UX choice — they prioritize stable spatial mental model over data-density encoding.
- **Magic Dash narrative format is "newspaper headlines."** Each widget gets an AI headline + description; insights refresh every 7 days for recently-viewed dashboards; users can manually click "New Insights" to regenerate. This is the most explicit "auto-narrate the dashboard" implementation in the category — competitors like Triple Whale lean on chatbot, Lifetimely on tables.
- **Subscription cohort depth (40+ metrics).** One-time → subscriber conversion funnel "right down to SKU, campaign, or discount code"; deep native integrations with all major subscription platforms (Recharge, Skio, Smartrr, Awtomic, Bold, Loop, Stay.ai). Strongest subscription analytics among general-purpose Shopify analytics tools.
- **Amazon parity.** Peel is unusual in treating Amazon Seller Central as a first-class data source equal to Shopify, including FBA/FBM segmentation. Multiple reviewers cite this as differentiator ("the Amazon piece is so rare and hard to find" — IQBAR).
- **Snowflake SQL access on every paid tier.** Even the $199 Core tier includes user-warehouse SQL — that's far cheaper than Daasity-class warehouse-first tools.
- **Templates Library with one-click install.** Pre-built dashboards/Slices/Audiences install directly into the user's data — lower friction than "create from scratch" patterns elsewhere.
- **Three flavors of sales metric (Total/Net/Gross).** Since the Dec 2020 reporting overhaul, every revenue/transaction view exposes all three — addresses the perennial "are returns counted" ambiguity proactively.
- **Read-Only Dashboards.** Share link without a paid seat — uncommon at this price point.

## Unique weaknesses / common complaints

- **No mobile app** — repeatedly flagged in third-party reviews. Web-responsive only.
- **High price floor for SMB.** $199/mo entry, $499/mo realistic working tier. Multiple reviewers call this prohibitive for sub-$2M brands.
- **No Woo, no GSC, no native Shopify-Magento/BigCommerce.** Strictly Shopify+Amazon+Walmart.
- **Manual COGS entry.** No automatic supplier-cost ingestion observed; help center treats COGS as a configuration the user maintains.
- **Multi-touch attribution leans on UTM + GA + Shopify first/last.** No server-side pixel of their own (unlike Triple Whale's Triple Pixel) — a meaningful gap if iOS-loss is the user's primary pain.
- **Magic Dash cannot generate audiences from a widget**, cannot answer forecasting/predictive questions, and cannot do "Product Journey" — explicit limits in the FAQ.
- **Help-center text contradicts in-product warning on AI data sharing** ("your data is secure and is not being shared with an AI" vs. the create-flow caveat "your data is shared with the AI"). Privacy-sensitive buyers will notice.
- **Export is a "data dump"** rather than formatted reports — third-party reviewers say it requires Excel post-processing.
- **Acknowledged team turnover** in 2023-2024 (referenced in user reviews); concentrated CSM dependency.
- **Snapchat ads "coming soon"** — gap relative to Triple Whale and Polar.

## Notes for Nexstage

- **Magic Dash narrative pattern is the closest analog to anything in the "auto-narrated dashboard" space.** Each widget = headline + description, AI-generated, refreshing weekly, user-overridable. Question-driven dashboard creation rather than pre-canned tabs. Worth deep study for any "Insights" or "Auto-Narrative" feature research.
- **The 5×5 RFM grid as a *home* surface is a UX decision worth noting.** They've decided customer-segment health is the single most important landing view, ahead of revenue/AOV cards. Square sizes are fixed (NOT weighted by population), and they explicitly explain this choice — a deliberate choice we'll have to make if we ever ship RFM.
- **Cohort heatmap is monochromatic blue per the assignment brief**, but public docs do **not** quote exact color tokens — only describe screenshots labeled "Customer Retention by Cohort," "Repurchase Rate by Cohort." UI color specifics need to be confirmed via direct product access (paid trial). **Do not assert blue heatmap from this profile alone — it was instructed in the brief but not verified verbatim from public Peel sources.**
- **They show three sales flavors (Total / Net / Gross) everywhere.** Direct analog to our 6-source-badge thesis: instead of one "revenue" number, expose the variants and let the user pick the lens. We expose source-of-truth; they expose computation-of-truth.
- **Audience export to Klaviyo/Attentive/Meta from any segment is one click.** Their RFM and Audiences pages both lead with this. Worth noting if we ever build segments — the destination push is the activation hook.
- **Pricing is transparent on the website** (rare in this category). They publish exact $/month for every tier with the order ceiling. Polar, Daasity, and Triple Whale all hide pricing — Peel's transparency is a positioning lever.
- **They explicitly market against Daasity, OrderMetrics, Looker/Data Studio, and Google Analytics** — same "rip-and-replace" frame Nexstage uses. Their strongest claim is reduced setup time ("install the tool, they pull in the data, and you can get straight to analyzing it").
- **Snowflake SQL access is included on every paid tier.** Indicates a "power user" segment they explicitly cater to. We do not currently match this.
- **Magic Insights refresh every 7 days, only on dashboards viewed in the past 7 days.** Cost-saving heuristic worth noting — they don't refresh AI insights on cold dashboards.
- **Their Multi-Touch Attribution model uses GA + Shopify first/last visits.** This is the same pattern most Shopify-native tools use; if Nexstage builds server-side pixel, it's a real differentiator.
- **No Reddit / Trustpilot / Capterra signal of substance.** Their public reputation is overwhelmingly positive Shopify App Store reviews (34 reviews, 5.0 average) plus some testimonial-style hand-curated quotes on relaycommerce/digismoothie. G2 listing exists but the review page returned 403 to public fetches; deeper G2 quotes would require an authenticated session.
- **"Pal" mascot** lives at the bottom of the left sidebar and gates account/billing — playful brand element worth noting (nothing equivalent in our IA).
- **No screenshots saved.** Public Peel marketing pages serve images via JS-loaded CDNs that WebFetch can't pull. UI color/hover specifics should be verified via a paid trial before being treated as ground truth.

## Blockers encountered

- G2 review page (https://www.g2.com/products/peel-analytics/reviews) returned **403 Forbidden** to WebFetch — only summary text from search-engine snippets available.
- help.peelinsights.com/changelog returned 404 — no public changelog index.
- Magic Dash and RFM grid screenshots are JS-rendered and cannot be saved as PNG without browser execution; described in prose only.
- No Reddit threads with substantive Peel discussion surfaced in searches.
- Trustpilot/Capterra do not have meaningful Peel listings.
