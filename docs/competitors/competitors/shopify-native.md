---
name: Shopify Native (built-in Analytics)
url: https://www.shopify.com/analytics
tier: T2
positioning: Built-in analytics surface inside the Shopify admin — the default lens every Shopify merchant already has, replacing the need for a third-party dashboard for store/sales data, but not for cross-channel ad attribution.
target_market: All Shopify merchants (Basic $29/mo through Plus $2,300/mo); SMB to mid-market DTC and B2C; primarily single-store, with multi-store consolidation gated to Plus.
pricing: Bundled with the Shopify subscription. Basic $29/mo, Grow $79/mo, Advanced $299/mo, Plus from $2,300/mo (annual billing). Sidekick AI is free on every plan as of 2026.
integrations: Shopify (native), Shop sales channel, Shopify POS, Shopify Email, Shopify Markets, Shopify Inbox/Chat, app-store integrations (Klaviyo, Meta, Google Ads via UTM), API/GraphQL, BigQuery/Looker only via 3rd-party connectors. No native pull from Meta/Google/TikTok ad spend; no GSC; no GA4.
data_freshness: Real-time for the Overview Dashboard and Live View; reports refresh near real-time after the 2024–25 infrastructure rebuild.
mobile_app: yes (Shopify mobile app — iOS/Android — exposes the analytics dashboard); web-responsive admin.
researched_on: 2026-04-28
sources:
  - https://www.shopify.com/analytics
  - https://www.shopify.com/pricing
  - https://www.shopify.com/sidekick
  - https://www.shopify.com/blog/new-analytics
  - https://www.shopify.com/blog/live-view
  - https://www.shopify.com/news/live-globe-2024
  - https://shopify.engineering/2025-bfcm-live-globe
  - https://shopify.engineering/bfcm-3d-data-visualization
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/overview-dashboard
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/live-view
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/profit-reports
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/customers-reports
  - https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/shopifyql-editor
  - https://help.shopify.com/en/manual/reports-and-analytics/discrepancies
  - https://help.shopify.com/en/manual/shopify-admin/productivity-tools/sidekick
  - https://changelog.shopify.com/posts/customize-your-analytics-dashboard-to-focus-on-key-business-metrics
  - https://changelog.shopify.com/posts/turn-business-questions-into-analytics-reports-with-natural-language-queries
  - https://shopify.dev/docs/api/shopifyql
  - https://community.shopify.com/t/warning-shopify-ai-sidekick-magic-hallucinates-technical-data-and-sabotages-strategic-seo/589483
  - https://community.shopify.com/c/shopify-discussions/live-view-map-keeps-getting-worse/m-p/1908430
  - https://bybtraction.com/shopify-plan-differences-analytics-reports-attribution/
  - https://www.putler.com/shopify-analytics-limitations
  - https://saleshunterthemes.com/blogs/shopify/shopify-sidekick
---

## Positioning

Shopify Native is the analytics layer baked into the Shopify admin — every merchant gets it the moment they sign up, and it covers the merchant's owned commerce data (orders, sessions, customers, products, inventory, profit when COGS is set) end-to-end. Marketing copy frames it as "real-time data updates" with "drag & drop, resize, add, or remove any metric card" so merchants can "see your most critical metrics at a glance." It is positioned not as a competitor to Triple Whale or Polar — it explicitly tells merchants to "connect to analytics integrations available in our app store" for supplemental measurement — but as the always-on default lens. The 2024–25 rebuild and the Winter 2026 release of Sidekick (now free on every plan) reposition it as a conversational, customizable analytics product that increasingly compresses the gap with paid third-party dashboards.

## Pricing & tiers

Analytics is bundled with the Shopify subscription. Per `bybtraction.com` and Shopify's own changelog/help docs:

| Tier | Price (annual) | Analytics included | Common upgrade trigger |
|---|---|---|---|
| Basic | $29/mo ($39 monthly) | Overview dashboard, Live View, Finance reports, Product analytics, sessions attributed to marketing, Sidekick. **Excludes** sales reports, full customer reports, custom reports. | "I need sales-by-channel and customer reports" → Grow |
| Grow | $79/mo ($105 monthly) | Adds full Sales reports, Order reports, standard Customer reports, full marketing attribution including last-click, first-click, and linear models. | "I need profit/COGS and custom reports" → Advanced |
| Advanced | $299/mo ($399 monthly) | Adds Custom Report Builder, ShopifyQL editor, profit reports with COGS, automated report scheduling, saved reports. | Multi-store consolidation, BI integrations → Plus |
| Plus | from $2,300/mo (3-yr term) | Adds multi-store analytics, advanced customer segmentation, "Commerce Analytics" with 60+ standard reports, BigQuery/BI export pathways, Financial Reports (P&L-style summaries, payment reconciliation, tax totals). | — |

Sidekick AI: included with every plan; "features and usage limits vary by plan."

## Integrations

**Sources (pulled natively):**
- Shopify orders, line items, customers, products, refunds, inventory, sessions, checkouts, cart events, discounts, gift cards
- Shopify POS (in-person sales)
- Shop sales channel (mobile app)
- Shopify Email, Shopify Inbox, Shopify Markets
- Marketing campaign UTMs (autogenerated via "shareable links and QR codes")
- Per `shopify.com/analytics`: customer acquisition cost reporting and return-on-ad-spend analysis when ad data is fed back via UTMs and the marketing module

**Destinations / outbound:**
- API access ("Access your data wherever you need it through our API")
- App Store ecosystem (Klaviyo, Triple Whale, Lifetimely, Glew, etc. all read Shopify analytics data)
- BigQuery / Looker / Power BI only via 3rd-party connectors (Supermetrics, Porter Metrics, SyncRange, Coupler, Improvado) — Shopify does not ship a native BigQuery connector

**Coverage gaps (vs. Nexstage's 6-source thesis):**
- No native Meta Ads / Google Ads / TikTok Ads spend ingestion
- No GSC integration
- No GA4 integration
- No native cross-channel ad-platform aggregation; CAC and ROAS rely on UTM tagging or external tools

## Product surfaces (their app's information architecture)

- **Overview Dashboard** — "How is the store performing right now?" — the customizable home of Analytics. Library of metric cards, drag/resize, real-time.
- **Live View** — "What is happening this very second?" — 2D map + 3D globe with visitor and order dots, real-time during BFCM and flash sales.
- **Reports library** — "Pre-built and custom reports across every commerce dimension." 60+ pre-built reports on Plus.
- **ShopifyQL editor** — "Write a query against my own commerce data." Available on Advanced and Plus.
- **Custom Reports / Explorations** — "Build a report from scratch using metrics + dimensions." Advanced/Plus.
- **Sidekick chat** — "Ask in plain English; get a chart or workflow." Free on every plan (2026).
- **Marketing attribution module** — "Which campaign drove this sale?" Inside Marketing > Analyze marketing.
- **Customer reports / Cohorts** — "Who is buying, when did they first buy, are they coming back?"
- **Finance reports** — "What does the P&L look like? Refunds? Taxes? Tips?"
- **Profit reports** — "Gross margin per product/variant where COGS is set." Advanced/Plus.
- **Inventory reports** — "Stock-on-hand, days of inventory, ABC analysis." Advanced+.
- **Acquisition / Behaviour / Retail / Sales reports** — sub-categories of the Reports library.
- **Analytics discrepancies help center** — Shopify ships a documentation surface explicitly explaining why Shopify, GA, and Meta numbers differ.
- **Mobile Shopify app dashboard** — pared-down version of the Overview accessible on iOS/Android.

## Data they expose

### Source: Shopify (orders, sessions, customers, products, inventory)
- **Pulled:** orders (gross, net, total tax, total tip, total shipping, refund), line items, product/variant SKUs, customers (first-order date, total orders), sessions (device, country, referrer, landing page, UTM), checkouts (started, completed, abandoned), inventory snapshot, vendor.
- **Computed:** Total sales, Gross sales, Net sales, AOV, Conversion rate, Sessions, Returning vs One-time customer rate, Cohort retention rate, Gross profit (when COGS set), Margin, Net sales without/with cost recorded, Sales by channel, Sales by traffic referrer, Sales by product, Sales by variant SKU, Sales by vendor, Sales by discount code, Sales by staff, Sessions by location/device/source, US sales tax by jurisdiction.
- **Attribution windows:** Last-click default. Per `bybtraction.com`, Grow+ unlocks "full marketing attribution with model selection," including last-click, first-click, and linear models. No native multi-touch beyond linear.
- **Cohort metrics:** Customer cohort analysis report supports Number of customers, Customer retention rate, Gross sales, Net sales, AOV per cohort. Cohorts grouped by date of first order; presentation includes "a heatmap-style cohort grid, a retention curve, and a detailed cohort table."

### Source: Shop sales channel (Shop app)
- **Pulled:** Shop app traffic, follows, push-notification opens, conversion-rate from Shop channel.
- **Computed:** Channel-level revenue and session attribution surfaced inside the same Sales reports.

### Source: Shopify POS / retail
- **Pulled:** in-person orders, location, staff member.
- **Computed:** Sales by retail location, Sales by POS staff, Retail performance reports.

### Source: Marketing module (UTM-driven)
- **Pulled:** UTM source/medium/campaign on inbound sessions; click-throughs from autogenerated campaign links and QR codes; campaign cost when entered manually.
- **Computed:** "Top channels," "customer acquisition cost," "return on ad spend." All computed from Shopify-side conversion data — no native ingestion of Meta/Google ad spend.

### Source: ShopifyQL
- Lets Advanced/Plus merchants query the same datasets directly: `sales`, `orders`, `products`, `customers`, `sessions`. Built-in time-series + currency + multi-store handling. Sidekick translates plain-English to ShopifyQL.

## Key UI patterns observed

### Overview Dashboard
- **Path/location:** Shopify admin > Analytics (left nav) > Overview / Home.
- **Layout (prose):** Date-range picker + comparison toggle pinned to the top of the canvas; primary canvas is a **grid of metric cards** that the merchant can drag-and-drop, resize, add, or remove. Per the Shopify Changelog: merchants click "Edit Overview," then "choose from a library of metric cards to add or remove metrics" and "reorder metric cards to build a personalized dashboard layout." Per the Help Center wording surfaced in search: "You can add a card to the dashboard by dragging and dropping the card from the sidebar into the dashboard. You can rearrange a card by clicking the ⠿ on the card and then dragging and dropping… You can resize a card by clicking the lower-right corner of the card and dragging it to your preferred size." Below the card grid, the page surfaces top-products and top-traffic-sources tables. The Winter 2025 Editions update added "real-time data updates and improved dashboard flexibility."
- **UI elements (concrete):** ⠿ drag-handle icon on each card; lower-right resize handle on each card; sidebar/library panel of available metric cards in edit mode; card content shows current value, comparison delta against the prior period, and a small inline trend chart for time-series cards. Custom cards can be created from saved Reports and pinned to the dashboard.
- **Interactions:** Date menu opens a calendar with presets ("Last 30 days") and custom range; comparison mode toggles prior-period vs year-over-year; clicking a card drills into the underlying report; cards refresh in real-time after the 2024 infrastructure rebuild; merchant can save the layout per user.
- **Metrics shown (named card library, observed across help and changelog):** Total sales, Gross sales, Net sales, Orders, Sessions, Online store conversion rate, AOV, Returning customer rate, Top products by units sold, Top channels, Top referrers, Sales attributed to marketing, Sessions attributed to marketing, Customers, plus any custom card the merchant pins from the Reports library.
- **Source:** https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/overview-dashboard, https://changelog.shopify.com/posts/customize-your-analytics-dashboard-to-focus-on-key-business-metrics, https://www.shopify.com/blog/new-analytics

### Live View
- **Path/location:** Shopify admin > Analytics > Live View.
- **Layout (prose):** Hero visual is a **3D rotating globe** (with a 2D map toggle). Live dots appear on the surface where activity is happening — **blue dots = recent visitor sessions, purple dots = orders.** Surrounding the globe is a strip of real-time numeric cards.
- **UI elements (concrete):** Per Help Center wording surfaced in search: "blue dots indicate recent visitor sessions, and purple dots indicate orders." Globe is rendered with three.js Points (Shopify Engineering describes using "three.js Points… which allowed them to draw using dots instead" of triangles). The 2D map shows "dashed state lines and state labels." Cards display: "Visitors Right Now" (active in past 5 minutes), "Total sales" (today, gross minus discounts/returns + shipping + taxes), "Total sessions" (since midnight), "Sessions in checkout," "Completed purchases," "Total orders" since midnight. Refresh interval cited as "every 10 minutes" for some metrics on Shopify's blog, with the visitor count itself in a 5-minute rolling window.
- **Interactions:** Click/drag the globe to rotate; hover/tap a dot for visitor or order detail; toggle between globe and 2D map; works on mobile.
- **BFCM connection:** During Black Friday/Cyber Monday Shopify ships a souped-up version of the same engine (the public BFCM globe). Per Shopify Engineering's 2025 BFCM post: "Every arc is a real order. When a merchant makes a sale, it shows up seconds later on this globe… an as-the-crow-flies path from shop to buyer." A 128×32-pixel dot-matrix display "renders live sales stats" with bloom post-processing. The merchant Live View borrows the same visual idiom year-round.
- **Source:** https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/live-view, https://www.shopify.com/blog/live-view, https://shopify.engineering/2025-bfcm-live-globe

### Reports library
- **Path/location:** Analytics > Reports.
- **Layout (prose):** Left-hand category list (Acquisition, Behaviour, Customers, Finance, Inventory, Marketing, Profit, Retail, Sales, Custom) with the report list in the main canvas. Each report opens into a configuration panel pattern: chart at the top, metric+dimension chips on a side panel ("the configuration panel allow[s] users to add or remove metrics and dimensions, change the visualization type, and specify a date range… the various selections populate in the report" dynamically), and a sortable table below. Top-of-page button: "Create custom report."
- **UI elements (concrete):** Visualization-type switcher (line / bar / table); metric chips and dimension chips with add/remove; saved-report and scheduled-export buttons (Advanced+); export to CSV (with the documented 1 MB / 50-record / email-on-large-export caps).
- **Interactions:** Drill-down from a row to a filtered exploration; save a configured report as a new custom report; pin to Overview as a custom card.
- **Metrics shown:** Per `pagefly.io` and Shopify Help, the named reports include — **Sales:** Total sales over time, Sales by product, Sales by product variant SKU, Sales by product vendor, Sales by discount code, Sales by traffic referrer, Sales by channel, Sales by billing location, Sales by POS staff, AOV. **Customers:** First-time vs returning, One-time customers, Returning customers, Customers by location, Customer cohort analysis. **Acquisition:** Sessions over time, Sessions by location, Sessions by device, Sessions by traffic source, Sessions by landing page. **Behaviour:** Cart analysis, Top online store searches, Top pages by sessions. **Marketing:** Sessions attributed to marketing, Sales attributed to marketing, Conversion by first interaction, Conversion by last interaction. **Finance:** Finance summary, Sales report, Payments report, Liabilities, Tips, Gift cards, Taxes, US sales tax. **Profit (Advanced+):** Gross profit by product, Gross profit by variant, Gross profit breakdown card. **Inventory (Advanced+):** Inventory snapshot, ABC analysis, Days of inventory remaining. **Retail:** Sales by retail location, Sales by POS staff.
- **Source:** https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types

### ShopifyQL editor
- **Path/location:** Reports > new exploration / "Open ShopifyQL editor" on any default report.
- **Layout (prose):** Code-editor pane on the left/top with the query (every default report comes pre-populated with its SQL-like ShopifyQL); chart preview pane that re-renders as the query is edited.
- **UI elements (concrete):** Query syntax is `FROM <dataset> SHOW <columns> WHERE <conditions> GROUP BY <dimensions>`. Datasets observed: `sales`, `orders`, `products`, `customers`, `sessions`. Built-in time-series support with date-range comparisons. Sidekick can author the ShopifyQL for the merchant.
- **Interactions:** Run query, save as report, schedule, export.
- **Source:** https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/shopifyql-editor, https://shopify.dev/docs/api/shopifyql

### Sidekick AI (chat)
- **Path/location:** Top-right of the Shopify admin — a "purple glasses icon" opens the chat. Free on every plan as of 2026.
- **Layout (prose):** Chat-style side panel (or full-screen on mobile). Merchant types or speaks (voice chat in beta) a question; Sidekick responds in conversational text, often with an inline chart or table when the answer is data-driven. Slash-commands are surfaced as a popular shortcut.
- **UI elements (concrete):** Slash commands documented include `/product-description`, `/pricing-strategy`, `/social-posts`, `/weekly-summary`, `/email-campaign`, `/shipping-audit`, `/build-collections`. For analytics, Sidekick "translates your questions into ShopifyQL with business friendly explanations of what each report measures." Generated reports are saved as Explorations in Analytics.
- **Interactions:** Multi-turn — context carries across the conversation, so a follow-up like "now break it out by first-time vs returning" refines the previous report. "Sidekick prepares automations and edits, but nothing goes live without your confirmation." Voice + screen-sharing in beta.
- **Documented example prompts:**
  - "What was the best converting traffic source in September?"
  - "Show me the total sales for Canadian customers over the past 60 days."
  - "Show total sales for California customers over the past 60 days, then break it out by first-time vs returning."
  - "How many repeat versus new customers for each traffic source?"
  - "What were my best-selling products this month?"
  - "Why did sales dip on Tuesday?"
  - "When inventory drops below 10 units, send a Slack alert and tag the product." (Builds a Shopify Flow automation.)
- **Plan:** Free on every plan; "features and usage limits vary by plan."
- **Source:** https://www.shopify.com/sidekick, https://changelog.shopify.com/posts/turn-business-questions-into-analytics-reports-with-natural-language-queries, https://help.shopify.com/en/manual/shopify-admin/productivity-tools/sidekick

### Customer cohort analysis
- **Path/location:** Analytics > Reports > Customer cohort analysis.
- **Layout (prose):** Per search of the Help Center: cohort report includes "a heatmap-style cohort grid, a retention curve, and a detailed cohort table." Each row in the grid represents a cohort of customers who made their first purchase in the same time window; columns are subsequent periods.
- **UI elements:** Metric menu lets the merchant switch between Number of customers, Customer retention rate, Gross sales, Net sales, AOV — all rendered into the same grid.
- **Interactions:** Select cohort granularity (week / month / quarter / year); filter by acquisition channel.
- **Source:** https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/customers-reports

### Profit reports
- **Path/location:** Analytics > Reports > Profit (Advanced+).
- **Layout:** "Gross profit by product report displays gross profit by product for the selected date range, considering only variants that have product cost information at the time of sale." A "Gross profit breakdown card" inside the Finance summary report shows net sales, costs, and profit; surfaces both "Net sales without cost recorded" and "Net sales with cost recorded."
- **UI elements:** Inline COGS field on each product variant; reports clearly call out which line items lack a recorded COGS (so they aren't counted toward gross profit).
- **Source:** https://help.shopify.com/en/manual/reports-and-analytics/shopify-reports/report-types/default-reports/profit-reports

### Marketing attribution module
- **Path/location:** Marketing > Campaigns / Marketing > Analyze marketing.
- **UI details not fully observable from public sources** beyond the marketing copy on shopify.com/analytics: "Top channels reporting," "customer acquisition cost reporting," "return on ad spend analysis." Attribution model selection (last-click / first-click / linear) is documented as Grow+ on `bybtraction.com`. UI screenshots beyond marketing pages are paywalled behind the admin.

### Mobile dashboard (Shopify mobile app)
- **Path/location:** Shopify mobile app > Analytics tab.
- UI details not fully observable from public sources — described in the marketing copy as accessible "from any device" so merchants can monitor BFCM in real-time. Card grid mirrors the Overview but in a single-column scroll.

## What users love (verbatim quotes, attributed)

- "The design, flexibility, payment integrations, custom sections options, integrations with Shopify Apps, analytics dashboard everything is perfect and easy to use." — Capterra reviewer (surfaced via search of `capterra.com/p/83891/Shopify/reviews/`, 2026)
- "What I like the most, is that the platform is super intuitive for different levels of e-commerce professionals and entrepreneurs looking to start a business." — Capterra reviewer, 2026
- "the prices in relation to functionality can be somewhat extensive compared to other CMS software on the market, but once you start working on the platform, how intuitive and easy it is makes it possible to pay for the service." — Capterra reviewer, 2026
- "I usually have it open all the time when I am at my computer working on other tasks." — PrimRenditions on Live View, Shopify Community, January 19, 2023
- "[Sidekick] feels like real-time support without having to search through help docs or wait for a reply… definitely one of the most useful features when just starting out." — paraphrase surfaced in `pagefly.io/blogs/shopify/shopify-sidekick`, 2026
- "Sidekick can help you create new pages and content, and within seconds can generate a complete layout that you can customize further… super helpful in spotting areas for improvement and making quick design changes." — `saleshunterthemes.com/blogs/shopify/shopify-sidekick`, 2026

Note: most G2/Capterra reviews of "Shopify" rate the entire platform, not the analytics module specifically. Verbatim quotes praising the analytics product in isolation are limited; merchants tend to praise the bundled-in convenience rather than the analytics depth.

## What users hate (verbatim quotes, attributed)

- "The new map with all the dashed state lines and state labels make it really difficult to see the blue visitor dots." — PrimRenditions on Live View, Shopify Community, January 17, 2023
- "Why would they then make changes to the MAP view, which was fine, and leave the globe view untouched?" — PrimRenditions, Shopify Community, January 17, 2023
- "The frequent occurrence of bot traffic makes it impossible for me to rely on the Visitor stats… I can tell when I have bot traffic because I'll see the same four dots in the exact same locations: CA, KS, IA, and Ireland! So if I see 20 visitors during a period of bot activity, I know I only really have 4 or 5 actual customers." — PrimRenditions, Shopify Community, January 19, 2023
- "the issues with live view prevent me from using it for anything useful." — PrimRenditions, Shopify Community, January 31, 2023
- "The AI has proven to be not only unreliable but actively detrimental to my brand's management." — Dawsonx on Sidekick, Shopify Community, February 24, 2026
- "Support confirmed there is 'no setting' to prevent the AI from hallucinating data or ignoring negative SEO constraints." — Dawsonx, Shopify Community, February 24, 2026
- "Shopify's AI is built for storytelling, not for accurate business management." — Dawsonx, Shopify Community, February 24, 2026
- "If I have to manually audit 80+ products because a 'voluntary' tool silently corrupts my database and ignores SEO constraints…" — Dawsonx, Shopify Community, February 26, 2026
- "Sometimes Sidekick doesn't understand what you want and you have to correct it." — Maximus3, Shopify Community, February 25, 2026
- "Generative AI like Sidekick is probabilistic by design meaning it guess the most likely next word or token." — Rahul-FoundGPT, Shopify Community, March 20, 2026

## Unique strengths

- **Customizable metric-card grid is best-in-class for a built-in dashboard.** Drag, drop, resize, add, remove — including custom cards built from any saved report. Per the changelog and Help Center, this is one of the few native (non-app) dashboards in the Shopify ecosystem with full layout flexibility. Real-time refresh shipped in the Winter 2025 update.
- **Live View 3D globe.** No competitor in the analytics-app category ships a real-time 3D globe with dot-level visitor and order rendering. Blue dots = visitors, purple dots = orders. The visual idiom is shared with the public BFCM globe (which uses arcs from shop-to-buyer with bloom-effect dot-matrix stat readouts) and gives merchants a recognizable "Shopify-feel" experience year-round.
- **Sidekick is free on every plan in 2026.** Natural-language analytics — "What was the best converting traffic source in September?" — generates a chart and a saved Exploration in seconds, with multi-turn refinement carrying conversational context. Builds the underlying ShopifyQL for the merchant.
- **ShopifyQL** — a commerce-aware query language with built-in time-series + currency + multi-store handling, available on Advanced/Plus. Lets power users skip the "write SQL against your data warehouse" step entirely.
- **Zero setup.** Every Shopify merchant has the dashboard live the day they sign up; data flows from the same DB that records the order. No tracking pixel to install, no warehouse to provision.
- **Cohort report ships heatmap, retention curve, AND detailed table** of the same cohort data — three views of one dataset, all built-in.
- **Profit-by-variant** with COGS support natively at the Advanced tier — a tier of granularity many third-party tools paywall.
- **Tight integration with the rest of Shopify admin.** Click a metric card → land in the Reports library → save as a custom card on Overview → schedule the export. The whole loop sits inside one product surface.
- **Free Sidekick voice chat + screen sharing** (beta) — uncommon in the category.

## Unique weaknesses / common complaints

- **Last-click is the default and only model on Basic.** Even on Grow, model selection is limited to last-click / first-click / linear; no multi-touch beyond linear. Merchants on Reddit and Putler's analysis report Direct traffic inflated up to 40% due to UTM gaps, third-party checkout apps, and privacy restrictions.
- **No native ad-spend ingestion.** Marketing reports compute "return on ad spend" only when the merchant either uses Shopify-built campaign links or manually enters spend; there's no native pull from Meta/Google/TikTok ads APIs.
- **No GA4. No GSC.** This leaves the cross-source-attribution gap that drives Shopify merchants to Triple Whale, Polar, Northbeam, etc.
- **Live View bot-noise problem.** Multiple community posts (PrimRenditions, 2023) describe the visitor dots being polluted by bot traffic with no native filtering.
- **Reports paywall is steep at Basic.** Putler summarizes: "basic plan customers get access to dashboard, live view, and basic acquisition reports, but sales reports, full customer reports, order reports, and custom report creation are unavailable." Custom Report Builder + ShopifyQL require Advanced ($299/mo).
- **Static COGS field.** Putler: "COGS field is static" and doesn't update with supplier price changes — manual maintenance required, especially per-variant.
- **Returning-customer definition is too loose.** Shopify defines "returning customer" as anyone who placed >1 order ever — useful for repeat rate at the brand level, less useful for cohort/retention diagnosis (which is why the cohort report exists, but the dashboard cards still use the looser definition).
- **No native cross-channel consolidation.** Amazon, Etsy, eBay sales sit in silos.
- **No native predictive / anomaly alerts.** Backward-looking only — no churn prediction, no automatic notification when conversion drops.
- **CSV export caps.** 1 MB CSV; emailed if > 50 records; max 1,000 rows visible in admin and 10,000 per export. Scheduled exports are Advanced+.
- **Session data history capped** (Putler cites October 2022 as the floor on session history).
- **Sidekick hallucinations.** Multiple community-thread reports (Dawsonx Feb 2026; Rahul-FoundGPT Mar 2026; Maximus3 Feb 2026) describe Sidekick fabricating SEO/technical data, ignoring negative constraints, and requiring 80+ product audits to clean up the damage.
- **"Order deletion" wipes data** with no recovery path — once an order is deleted in admin, its reporting history is permanently erased.

## Notes for Nexstage

- **The customizable-metric-card grid is the bar for "Overview Dashboard" UX.** Drag handle (⠿), corner-resize, library-panel-on-edit, custom cards from saved reports — this is what merchants are already trained to expect on Shopify, so any Nexstage Overview should at minimum match this idiom. Per-card date-range overrides are NOT documented in the changelog; the date picker is dashboard-wide.
- **Live View 3D globe with blue/purple dots.** Direct precedent for a "real-time visitor + order map." If Nexstage ships any kind of real-time map, the color semantics (blue=session, purple=order) are the embedded Shopify convention and changing them would create a re-learning cost.
- **Sidekick is now free on every plan and writes ShopifyQL for analytics queries.** This compresses the "natural-language insights" advantage that Triple Whale's Moby and Polar's AI have charged for. Worth a separate decision doc on how Nexstage's AI surface differentiates from a free, store-context-aware Shopify Sidekick.
- **The 6-source attribution gap is real and well-documented.** Shopify Native does not ingest Meta/Google/TikTok ad spend, GA4, or GSC; CAC and ROAS in the marketing module are Shopify-side computations. This is exactly the cross-source thesis Nexstage's "Real / Store / Facebook / Google / GSC / GA4" badges target.
- **Last-click default + Direct-traffic inflation** is a chronic merchant pain (Putler quotes ~40% misattribution to Direct). Nexstage's `MetricSourceResolver` showing source provenance per metric is a direct counter to this.
- **Cohort report has 3 views of the same data** (heatmap grid + retention curve + detailed table). That's a richer presentation than most third-party cohort views, and a useful design reference for Nexstage's customer/cohort surfaces.
- **Profit-by-variant with COGS is Advanced-tier only.** Many merchants without Advanced are doing COGS in spreadsheets — Nexstage's `ProductCostImportAction` and per-variant COGS support targets that exact gap.
- **Custom dashboard / Custom reports paywall starts at $299/mo Advanced.** Nexstage's pricing should consider that custom-card and custom-report capability is the single biggest reason merchants upgrade Shopify plans, which means the perceived value floor is high.
- **CSV export caps and no native BigQuery connector** are well-documented friction points. If Nexstage ever surfaces a "warehouse export," that is differentiating.
- **Sidekick hallucinations** are a documented pain — community threads show real merchant trust damage. Nexstage's AI features should either constrain outputs to verifiable computed metrics, or clearly mark probabilistic outputs as such, to avoid the same trust collapse.
- **Live View is closed-when-closed** (Putler: "Moment you close it, that data is gone"). Real-time data is not preserved historically. Nexstage's hourly_snapshots model can tell a "we keep your real-time data forever" story.
- **The BFCM 3D globe is a marketing artifact merchants associate with Shopify.** Replicating the visual language of arcs / bloom effects / dot-matrix readouts in a Nexstage real-time surface would feel like piggybacking — a bespoke visual identity is preferable.
- **UI screenshot blocker:** Help Center pages return HTTP 403 to WebFetch and the actual admin is paywalled, so secondary aggregator sites (`pagefly.io`, `ask-luca.com`, `bybtraction.com`, `putler.com`, `improvado.io`) are the primary public source for UI descriptions. Some specific UI details (per-card date-range, exact tooltip behavior, marketing module screen) could not be verified from public sources alone.
