---
name: Metorik
url: https://metorik.com
tier: T3
positioning: WooCommerce-native (now also Shopify) analytics + email automation for SMB merchants who outgrew native reporting but cannot afford Triple Whale / Polar
target_market: SMB Shopify and WooCommerce stores; ~8,000+ stores; founder-led brands; subscription/membership operators (WooCommerce Subscriptions strong)
pricing: From $25/mo Starter (≤100 orders/mo); order-volume-based, scales to ~$250/mo at 5k orders; 30-day free trial, no credit card
integrations: WooCommerce, Shopify, Google Ads, Meta Ads, Microsoft (Bing) Ads, TikTok Ads, Pinterest Ads, ShipStation, Google Sheets/CSV, Zendesk, Help Scout, Gorgias, Intercom, Freshdesk, Groove, Slack
data_freshness: Real-time (webhook-driven) with 5-minute backup pull on last 8h of orders/coupons/customers/products
mobile_app: web-responsive (no dedicated iOS/Android app observed)
researched_on: 2026-04-28
sources:
  - https://metorik.com
  - https://metorik.com/pricing
  - https://metorik.com/features/cohorts
  - https://metorik.com/features/segmenting
  - https://metorik.com/features
  - https://metorik.com/integrations
  - https://metorik.com/analytics-reports
  - https://metorik.com/love
  - https://metorik.com/reviews
  - https://metorik.com/blog/customer-cohort-reports-track-retention-over-time-by-join-date-first-product-and-more
  - https://metorik.com/blog/metorik-engage-has-a-whole-new-drag-and-drop-email-builder-for-your-woocommerce-shopify-emails
  - https://help.metorik.com/article/177-customer-reports
  - https://help.metorik.com/article/139-subscription-cohorts-report
  - https://help.metorik.com/article/160-how-does-metorik-calculate-my-monthly-price
  - https://help.metorik.com/article/74-does-metorik-hurt-my-stores-performance-speed
  - https://help.metorik.com/article/98-data-syncing
  - https://apps.shopify.com/metorik
  - https://apps.shopify.com/metorik/reviews
  - https://wordpress.org/support/plugin/metorik-helper/reviews/
  - https://wordpress.org/plugins/metorik-helper/
  - https://www.commercegurus.com/metorik-review/
  - https://createandcode.com/metorik-review/
  - https://circlo.io/metorik-review/
  - https://www.putler.com/metorik-review/
  - https://www.businessbloomer.com/anyone-here-experience-with-metorik/
  - https://www.conjura.com/blog/metorik-pricing-in-2026-costs-features-and-best-alternatives
---

## Positioning

Metorik is a Melbourne-based (est. 2016) eCommerce analytics + email automation tool that built its name as the de-facto WooCommerce reporting upgrade and has since added Shopify support. It positions itself as the "missing analytics layer" for SMB merchants who find native WooCommerce reports clunky and find Triple Whale / Polar Analytics out of reach. The wedge is breadth-at-low-price: real-time dashboards + 500+ filter segmentation + cohort analysis + abandoned-cart email automation ("Engage") all bundled into a single order-volume-based plan, with the lowest paid tier at $25/mo. They lean heavily on founder-led, human support as differentiation against larger SaaS competitors.

## Pricing & tiers

Metorik uses a single feature plan ("all features included") that scales by order volume. Pricing is calculated using a rolling 3-month average of orders. There is no feature gating between tiers — only volume, store-count, and email-credit limits change.

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Starter / Level 1 | $25/mo | Up to 100 orders/mo, all reports & analytics, 1 store, 10,000 email credits | Hits 100 orders/mo |
| Level 2 | $75/mo | 101–500 orders/mo, up to 5 stores, 25,000 email credits | Hits 500 orders/mo |
| Level 3 | $150/mo | 501–2,000 orders/mo, up to 10 stores, 40,000 email credits | Hits 2,000 orders/mo |
| Level 4 | $250/mo | 2,001–5,000 orders/mo, up to 20 stores, 75,000 email credits | Hits 5,000 orders/mo |
| Higher tiers | Calculator on pricing page extends to 150k+ orders/mo | All features same | Contact sales for top-end |

Notes:
- 30-day free trial, no credit card required.
- For Shopify stores, billing routes through Shopify subscriptions; for Woo, direct credit-card monthly.
- Plan auto-downgrades if order volume drops; "no overage. No bill-shock" is explicit marketing copy.
- Historical data sync is capped at "120x your monthly order limit" (i.e., a Level 1 store can sync 12,000 historical orders).
- Older 3rd-party reviews reference an Engage email add-on as a separate line item ($10–$150 depending on tier — e.g., Putler's review notes "100 orders: $20/month (+$10 for email automation)"). Current Shopify App Store listing bundles email credits into each level, so the add-on appears to have been folded in.

## Integrations

Sources (data pulled in):
- **eCommerce platforms:** WooCommerce (real-time webhooks via Metorik Helper plugin), Shopify (real-time via API + Shopify subscription billing).
- **Ad platforms:** Google Ads, Meta (Facebook/Instagram), Microsoft (Bing) Ads, TikTok Ads, Pinterest Ads. Used to sync ad spend into profit reports.
- **Cost feeds:** Google Sheets / CSV upload (manual), ShipStation (true delivery costs).
- **Analytics:** Google Analytics referenced as a connectable data source on integrations page.

Bidirectional / customer-data:
- **Support tools:** Zendesk, Help Scout, Gorgias, Intercom, Freshdesk, Groove — surface support tickets inside the Metorik customer profile.

Destinations (data pushed out):
- **Slack** — automated digests + scheduled exports to channels.
- **Email** — digests, scheduled CSV exports, Engage transactional/broadcast/cart emails.

Coverage gaps observed:
- **No GSC integration.** No Google Search Console / organic-search data.
- **No Klaviyo/Mailchimp integration** — Engage is their own ESP. No way to send Metorik segments to a third-party ESP for execution.
- **No Snapchat / X / LinkedIn ads** observed.
- **No Amazon / eBay / multi-channel marketplace** support.
- **No native server-side / pixel attribution stack** — they rely on platform-reported numbers from each ad network plus Woo/Shopify's own attribution.

## Product surfaces (their app's information architecture)

- **Home Dashboard** — at-a-glance store summary: net sales, new customers, AOV, LTV, best/worst-selling products, daily/hourly sales, recent events (new customers, new subscriptions, expirations).
- **Custom Dashboards** — user-built dashboards with metric cards (Blended ROAS, Ad Spend, CAC, COGS, Customer LTV); add/remove widgets.
- **Multi-store Dashboard** — combined view across multiple connected Woo/Shopify stores under one subscription.
- **Revenue Report** — gross/net revenue with period comparison and product/customer/order filters.
- **Orders Report** — order analytics by status, payment method, shipping method, customer type (new vs. returning), location.
- **Customer Report** — new customer trends, geographic heatmap with cluster markers, group-by role / billing country / state / city / zip; LTV averages.
- **Customer Profile** — per-customer view with name/email/contact, total spend, AOV, order count, transaction list, private notes, integrated support-ticket pane (Zendesk/Gorgias/Intercom/etc.).
- **Products Report** — sales by product, profit margins, inventory forecasts.
- **Product Profile** — per-product page: first sale date, last sale date, refund count, average daily sales, monthly sales trend.
- **Subscriptions Report** — MRR, churn, LTV, by product/billing cycle (WooCommerce Subscriptions only).
- **Cohorts Report** — heatmap of retention/LTV/order-count by acquisition cohort.
- **Subscription Cohorts Report** — separate cohort heatmap for retained MRR.
- **Retention Report** — orders made over lifetime, time-between-repeat-orders chart (toggleable weeks/days/months/years), items purchased over lifetime.
- **Carts Report** — abandoned, in-progress, and completed carts with recovery campaign performance.
- **Coupons Report** — coupon performance and discounting cost.
- **Refunds Report** — refund rates and refund analysis.
- **Devices Report** — desktop/mobile/tablet split with revenue and AOV.
- **Traffic Sources Report** — referrers, UTM tags, attribution.
- **Forecasts** — sales forecasting.
- **Costs & Profit** — COGS, ad spend, shipping, fees aggregated for true profit.
- **Segments** — segment builder + saved-segment library; reusable across all reports.
- **Engage > Cart Recovery Emails** — abandoned cart automation editor with drag-and-drop builder.
- **Engage > Automations** — transactional and triggered email workflows.
- **Engage > Broadcasts** — bulk campaigns to segmented audiences.
- **Exports** — customizable CSV exports with drag-and-drop column picker, scheduled deliveries.
- **Digests** — plain-English summary digests sent to email or Slack on configurable cadence.
- **Store Settings** — store connection, force-sync tool, webhook configuration, COGS/cost configuration, attribution date toggle (first-order vs. join-date), include/exclude non-paying customers.

## Data they expose

### Source: WooCommerce / Shopify
- **Pulled:** orders, line items, customers (with custom fields), products, variations, refunds, coupons, abandoned carts, subscriptions (Woo only), gift cards (Shopify only), shipping addresses, payment methods, fulfillment status, devices, browsers, OS, referrers, UTM tags.
- **Computed:** AOV, LTV, repeat-rate, cohort retention (by join month, first product, first coupon, country, order count), MRR, churn, time-between-repeat-orders, net profit (after COGS/ad spend/shipping), forecasted sales.
- **Attribution:** First-order date vs. join date toggle in store settings; attribution date determines which cohort a customer falls into.

### Source: Meta Ads, Google Ads, Microsoft Ads, TikTok Ads, Pinterest Ads
- **Pulled:** ad spend at platform level (no granular campaign/adset/ad breakdown surfaced in marketing copy).
- **Computed:** Blended ROAS, blended CAC, total ad spend rolled into profit reports.
- No platform-attributed revenue side-by-side display observed; ad spend appears as a cost input, not a revenue claim.

### Source: ShipStation
- **Pulled:** actual shipping costs per order.
- **Computed:** real shipping-cost line in profit reports (replaces estimated shipping cost).

### Source: Google Sheets / CSV
- Manual cost upload (e.g., COGS by SKU, fixed costs).

### Source: Google Analytics
- Listed as connectable; specific fields not detailed in marketing copy.

### Source: Support tools (Zendesk, Help Scout, Gorgias, Intercom, Freshdesk, Groove)
- Tickets surfaced inside customer profile; not used for analytics computation.

## Key UI patterns observed

### Home Dashboard
- **Path/location:** Default landing page after login.
- **Layout (prose):** Top section shows headline metrics (net sales, new customers, AOV, LTV) as summary cards; below is a "best/worst selling products" panel and a recent-events feed (new customers, new subscriptions, expirations). Marketing imagery on metorik.com depicts metric cards as "isometric" tiles floating on a blue gradient background — that's marketing aesthetic only, not necessarily the in-product view.
- **UI elements (concrete):** Metric summary cards across the top; period-comparison capability — copy describes it as "quickly view how all of those key metrics compare to a different time period." Customizable dashboard (add/remove widgets).
- **Interactions:** Dashboard customization (add/remove widgets); date-range selection; period comparison toggle.
- **Metrics shown:** Net sales, new customers, AOV, customer LTV, lifetime orders per customer, best-selling/worst-selling products.
- **Source:** https://metorik.com/features ; https://metorik.com/analytics-reports ; CommerceGurus and CreateAndCode 3rd-party reviews.

### Cohorts Report (Customer)
- **Path/location:** Reports > Cohorts.
- **Layout (prose):** Classic cohort heatmap: cohort groups in rows (default = join month), time periods as columns (Month 1, Month 2, Month 3, …). Each cell shows the metric for that cohort at that lifetime stage. A summary row at the bottom shows the average across all cohorts at each lifetime point.
- **UI elements (concrete):** Tabular heatmap with cohort labels on left, "month 1, month 2, month 3, etc." across the top. Cell coloring described in their own copy as a heatmap (specific palette not confirmed in public docs — 3rd-party reviews note Metorik's overall site theme is blue/monochrome but a screenshot-confirmed cohort cell color spec was not surfaced in public marketing copy). Each cell can show absolute number or percentage depending on the cohort variant.
- **Interactions:** Switch cohort variant via tabs/dropdown — variants include "Customer Lifetime Profit", "Returning Customers" (retention rate), "Order Count" (uses # of orders as the lifetime axis instead of months), "Billing Country", "First Product Purchased", "First Coupon Used". Group-by toggle (by join month is default, swappable to first product, country, etc.). Segment filter integration — apply a saved segment to the cohort report. Export to CSV.
- **Metrics shown:** LTV (cumulative), retention %, order count, profit by cohort.
- **Source:** https://metorik.com/features/cohorts ; https://metorik.com/blog/customer-cohort-reports-track-retention-over-time-by-join-date-first-product-and-more

### Subscription Cohorts Report
- **Path/location:** Reports > Subscription Cohorts (Woo Subscriptions only).
- **Layout (prose):** Heatmap-style retention table. Cohorts grouped by start week / month / year (user-selectable). Each cell shows MRR retained from the original start amount as a percentage. Bottom row shows weighted-average retention rate for each lifetime month.
- **UI elements (concrete):** Hover-on-cell tooltip reveals underlying numerical values (e.g., dollar amount churned that period). Toggle: MRR view ↔ Subscriber count view. Toggle: % of total start retained ↔ % of previous month retained ↔ raw value retained.
- **Interactions:** Time-grouping switcher (week / month / year). Metric switcher (MRR / subscribers). Display switcher (% from start / % from prior period / absolute value). Cell hover for raw values.
- **Metrics shown:** Retained MRR %, retained subscriber %, churned $ amount, weighted-average retention.
- **Source:** https://help.metorik.com/article/139-subscription-cohorts-report

### Customer Report
- **Path/location:** Reports > Customers.
- **Layout (prose):** Top: summary cards (new customers, total orders from those customers, total revenue). Below: averages section (avg LTV = total spend / customer count, AOV, avg lifetime orders, items per customer). Below that: "Customers grouped by" tables broken down by role / billing country / shipping country / state / city / zip. Geographic heatmap with cluster markers shows customer location density. Below that: secondary retention charts.
- **UI elements (concrete):** Blue-colored numbers in customer-count columns are clickable, drilling into the underlying customer list for that segment. Customer Heatmap is geographic (map-based) with clickable cluster markers that zoom on click. Bar chart titled "Orders made over the lifetime of new customers" with corresponding average order gross. Time-between-repeat-orders chart — toggleable between weeks / days / months / years. Items-purchased-over-lifetime chart.
- **Interactions:** Click blue numbers → drill into customer list. Click map clusters → zoom map. Toggle x-axis unit on time-between-orders chart.
- **Metrics shown:** New customers, orders from new customers, revenue from new customers, avg LTV, AOV, avg lifetime orders, items per customer; per-segment subtotals.
- **Source:** https://help.metorik.com/article/177-customer-reports

### Segment Builder
- **Path/location:** Top-level "Segments" or invoked from any report's filter affordance.
- **Layout (prose):** Filter-builder UI ("more like a conversation than a database query," per their own copy). Users add filter rows; rows can be combined into groups; groups can be ANDed or ORed with advanced logic. Live result count updates "in seconds" as filters are added/edited. Saved segments can be named, shared via URL, and reused across any report.
- **UI elements (concrete):** Marketing screenshots (referenced in their copy as "isometric skew" mockups) show a left-side filter list and a right-side "matching order table" populated with live results. Specific button labels and chip styling not explicit in public copy.
- **Interactions:** Add filter row; group filter rows; apply AND/OR logic at row and group level; save segment with name; share via URL; apply saved segment to any report; auto-recurring scheduled CSV export of segment results.
- **Filter coverage:** 500+ filters across orders, customers, subscriptions, products, variations, categories, coupons, carts; supports WooCommerce custom fields and meta fields; covers shipping methods, payment methods, fulfillment status, frequency/recency/monetary purchase behavior.
- **Source:** https://metorik.com/features/segmenting

### Costs & Profit
- **Path/location:** Costs > Costs & Profit (or via custom dashboard widgets like "Blended ROAS", "COGS").
- **Layout (prose):** Cost breakdown displayed in "little tabs for each cost added to the system, clearly indicating amounts within each tab" (CommerceGurus review description). Cost categories: COGS (per-product), ad spend (synced from Google/Meta/Bing/TikTok/Pinterest), shipping (synced from ShipStation or estimated), payment processing fees, and custom fixed costs.
- **UI elements (concrete):** Per-cost cards/tabs with running totals; integrated into profit calculation across reports.
- **Interactions:** Connect ad platform → spend auto-syncs; connect ShipStation → real shipping costs replace estimates; manual COGS entry per product or bulk via CSV/Google Sheets.
- **Metrics shown:** Gross revenue, COGS, ad spend, shipping cost, fees, net profit, profit margin %; Blended ROAS = revenue / total ad spend.
- **Source:** https://metorik.com/features ; https://help.metorik.com/category/194-cogs ; https://www.commercegurus.com/metorik-review/

### Engage Email Builder (Cart Recovery / Broadcasts / Automations)
- **Path/location:** Engage > Cart Recovery Emails / Automations / Broadcasts.
- **Layout (prose):** Drag-and-drop email builder. Right-side panel of draggable components (heading, text, columns, buttons) plus three e-commerce-specific tools: Order tool (receipt/products/tracking), Cart tool (abandoned cart with one-click recovery link), Product tool (related/bestsellers/random). Body-level styling controls (background color, font family, width); section-level overrides (background color, padding, columns).
- **UI elements (concrete):** "Preview Email" button renders the email with real store data. Test-send to self/team. Variables (merge tags) display real example values inline rather than raw `{variable_name}` placeholders. Auto-generated responsive layout + plain-text version.
- **Interactions:** Drag component from right panel to canvas. Click section to edit styles. {cart_checkout_button} component auto-applies discount code and recovers cart on click. Dynamic one-time coupon generation per email to prevent reuse abuse.
- **Metrics shown:** Per-email recovery rate; sequence-level recovery attribution.
- **Source:** https://metorik.com/blog/metorik-engage-has-a-whole-new-drag-and-drop-email-builder-for-your-woocommerce-shopify-emails ; https://metorik.com/engage/abandoned-cart-emails

### Exports
- **Path/location:** Available from any report or top-level "Exports" page.
- **Layout (prose):** Drag-and-drop column picker — user reorders/toggles columns to include in CSV. Schedule recurring exports (daily/weekly/etc.) delivered via email or Slack. WooCommerce custom fields can be added as columns.
- **UI elements (concrete):** Toggle switches per column; drag handles to reorder.
- **Interactions:** Save export configuration; schedule recurring; deliver to email/Slack; one-off CSV download.
- **Source:** https://metorik.com/features ; https://help.metorik.com/

### Digests
- **Path/location:** Settings > Digests.
- **Layout (prose):** "Plain-English" daily/weekly summaries of key metrics, delivered to email or Slack. Marketing copy emphasizes natural-language phrasing ("Your store made $X this week, up Y% from last week") rather than raw chart embeds.
- **Source:** https://metorik.com/features

UI details NOT confirmed from public sources (logged here for honesty):
- Exact cohort heatmap color palette (monochromatic blue is suggested by overall site theme but not confirmed by a verbatim screenshot caption).
- Specific button labels, chip shapes, dropdown styling in segment builder.
- Whether the cohort report has a literal "Number ↔ Percentage" toggle button (the percentage vs. value toggle is confirmed for *Subscription* cohorts; for *Customer* cohorts the variants show different units inherently — Customer Lifetime Profit shows $, Returning Customers shows %).

## What users love (verbatim quotes, attributed)

- "Shopify's analytics are good for basic analysis, but if you're needing anything more sophisticated, Metorik is hands down, the best option we've found." — Simon McIntyre, Woodruff and Co (Shopify), via metorik.com/love
- "Metorik brings WooCommerce reports to a whole new level. I'm using Metorik for engaging with customers (reviews & abandoned cart emails), it gives me all the needed info for taxes, and it really shows me how I'm doing and where to improve." — Caspar Eberhard, Founder, Appenzeller Gurt, via metorik.com/love
- "There are very few products/pieces of software that are produced to the level of excellence that Metorik offers. Everything works — seamlessly. It integrates — perfectly." — Zac Zelner, PupSocks, via metorik.com/love
- "Not only is it fast and a huge time saver, we used Metorik to create several new customer segments and send targeted email promotions. It paid for itself several times over in the first week." — Robby McCullough, Co-Founder, Beaver Builder, via metorik.com/love
- "Metorik is the missing tool WooCommerce has always needed. eCommerce without advanced reporting is like a car without gas." — Kenn Kelly, CEO, Never Settle, via metorik.com/love
- "I want to attack churn, increase LTV, improve order frequency and maximise transaction volume. With Metorik, I can do all that before I've had my morning coffee!" — John Lamerton, Founder, BIGIdea, via metorik.com/love
- "Exporting through Metorik has been an absolute game-changer. We can rely on going into Metorik, hitting export, and all correct data being included." — Brian Zarlenga, General Manager, Output, via metorik.com/love
- "Connecting Metorik to Meta/TikTok/Google Ads is a game changer." — The Hero Company, Shopify App Store review, June 6, 2025
- "Worked great on woo, works great on shopify!" — ReFerm (Sweden), Shopify App Store review, March 3, 2026
- "The UX is clean, fast and intuitive." — Super Speciosa, Shopify App Store review, June 12, 2024
- "Beautifully designed, making it a pleasure to use daily." — LUMIBEE INTERNATIONAL (Bulgaria), Shopify App Store review, December 17, 2024
- "Most clear and function insights app we've come across." — Carmen Amsterdam (Netherlands), Shopify App Store review, December 17, 2024
- "Simple UI, extremely functional and cost effective." — verified G2 reviewer, March 28, 2025
- "We're having perfect insights on what is happening on our store(s). Without Metorik we would be lost. We're using Metorik basically every moment of the day, for everything." — G2 reviewer (year not specified, surfaced in 2025 search snapshot)
- "I've used it since the very beginning, never a single issue." — Rodolfo Melogli, BusinessBloomer, September 23, 2023
- "Gives me an easy helicopter view of my sales, products and customers." — GILLIAN KYLE (UK), Shopify App Store review, January 9, 2025

## What users hate (verbatim quotes, attributed)

Critical feedback is rare — Metorik holds 5.0/5 across 46 Shopify App Store reviews and 5.0/5 across 20 WordPress.org reviews, with only the G2 listing showing any non-5-star noise. The substantive complaints surface mostly in 3rd-party review articles and pricing-FAQ self-acknowledgments rather than verbatim user quotes.

- "We wish they can be a little faster with new features, but it's completely fine as is given the price point." — G2 review snippet (year not specified, surfaced in 2025 search snapshot)
- "It would be nice if [we] could create different audiences within Metorik that they can later send to Facebook to run some campaigns." — user feedback summarized from G2 (search snippet; exact reviewer not surfaced)
- "I understand if this is frustrating for some store owners, but it's a model that we've found is fair for most customers." — Metorik themselves, in their pricing FAQ at help.metorik.com/article/143, acknowledging that order-volume pricing is a known friction.
- "Some users express a desire for more integrations with other tools to further streamline their workflows." — paraphrased complaint pattern across G2/Capterra summaries (no single attributable verbatim quote found in public reviews).
- "The pricing plan is based on the number of orders, therefore it can be pricey in the long run especially for bigger stores." — putler.com/metorik-review/, summarizing community sentiment (3rd-party article, not direct user quote).
- "NEARLY perfect (a few tweaks here and there)." — anonymized reviewer summarized on metorik.com/reviews — the single non-effusive line in their own testimonial wall, with no specifics.

Recurring criticism patterns from 3rd-party reviews (Putler, Conjura, CommerceGurus) — not verbatim user quotes:
- Originally WooCommerce-only; Shopify support is newer and some Shopify-specific reports (e.g., gift cards) lag in depth.
- No native refund processing — reports on refunds but does not initiate them.
- No website analytics / on-site visitor tracking — relies on Google Analytics integration rather than its own pixel.
- Email-only digest delivery (plus Slack); no SMS, no in-app digest reader.
- Multi-store dashboard requires same-platform stores (cannot mix Woo + Shopify under one consolidated view, per Putler review summary).

## Unique strengths

- **Lowest price point in the category by a wide margin.** Starter at $25/mo undercuts Lifetimely ($49+), TrueProfit, Polar Analytics ($400+), and Triple Whale ($129+). Combined with a 30-day no-CC trial, low friction to evaluate.
- **All-features-included plan model.** No feature gating per tier — only volume gating. Cohorts, segmentation, profit, email automation, multi-store all available at $25/mo Starter.
- **WooCommerce depth is best-in-category.** Metorik Helper plugin reads custom fields, custom meta, WooCommerce Subscriptions data; segment filters expose 500+ criteria including arbitrary custom fields. No Shopify-first competitor matches Woo support.
- **Subscription-business depth.** Subscription cohorts report with MRR/subscriber/% toggles + week/month/year grouping; churn, MRR retention, repeat-rate by sub product. Direct competitor for membership/subscription Woo brands.
- **Segment builder doubles as the entire product's filter system.** Saved segments are first-class citizens that apply across every report — cohort, customer, product, retention. Many competitors silo segments inside one report.
- **Founder-led human support.** Founder Bryce Adams personally responds to support and forum threads (his WordPress.org username `bryceadams` answers nearly every plugin review). Frequently called out as a differentiator vs. "support is seriously second to none" — Anna McCormack, Affordable Wholefoods.
- **8+ years of compounding without rebrand or pivot.** Established 2016, still founder-owned; consistent product narrative makes them trusted in the WP/Woo community.
- **Multi-store consolidation** for same-platform brands at no per-store extra cost (within tier limits).

## Unique weaknesses / common complaints

- **Order-volume pricing model penalizes scale.** Acknowledged as frustrating by Metorik themselves in their pricing FAQ. Brands hitting 10k+ orders/mo move to four-figure pricing without unlocking additional features — pure volume tax.
- **WooCommerce-first DNA shows in Shopify implementation.** Shopify support added later; some Shopify-specific commerce concepts (gift cards, Shopify-native attribution) less deep than Woo equivalents.
- **No dedicated mobile app.** Web-responsive only.
- **No GSC / no organic search data.** Analytics scope is store + ads + email; SEO is excluded.
- **No native pixel / server-side tracking.** Metorik does not own the attribution layer — they read from each ad platform's reported attribution. No Triple Whale-style "Pixel" or Polar-style proprietary attribution layer.
- **No Klaviyo / Mailchimp integration.** Engage is the only ESP destination — segments cannot be activated in third-party email tools, locking users into Metorik's email product if they want segment-driven email.
- **Helper plugin can stress large Woo stores.** Documented sync caveat: webhooks pull last 8h every 5 minutes — fine under 10k orders/mo but flagged as a potential performance risk above that, with a Force Sync escape hatch.
- **Slow feature pace acknowledged.** Multiple users note feature requests take time; one G2 review explicitly says "we wish they can be a little faster with new features."
- **Multi-store cannot mix platforms** in one consolidated dashboard (per Putler review summary; not directly contradicted by Metorik docs).
- **Limited ad-platform granularity in profit reports** — they ingest spend totals but the marketing copy does not surface campaign/adset-level breakdowns the way Triple Whale or Polar do.

## Notes for Nexstage

- **Direct price-point peer.** Metorik at $25/mo is the only major analytics tool actually within reach of true SMB merchants doing <100 orders/mo. If Nexstage wants to undercut Triple Whale on price, Metorik is the floor, not Triple Whale. Position relative to Metorik on *capability* (multi-source attribution lens, GSC), not price.
- **WooCommerce wedge is real and underserved.** Most Shopify-first analytics tools (Lifetimely, Triple Whale, Polar, Northbeam) ignore Woo entirely. Metorik owns this. Nexstage's Woo support is a relevant competitive angle if positioned thoughtfully.
- **Cohort heatmap pattern is the canonical SMB pattern.** Cohort rows × time columns, summary row at the bottom, percentage/number toggle. Subscription cohorts add metric (MRR vs. subscribers) and basis (% from start vs. % from prior period vs. raw value) toggles — useful prior art for our cohort report.
- **Segment-as-first-class-primitive.** Metorik's "saved segments work everywhere" pattern is worth studying for our own filter/segment architecture. Their pitch — "more like a conversation than a database query" — is a reasonable design north star.
- **Engage (email automation) is bundled, not separate.** They've decided email + analytics is one product. We've explicitly chosen not to do email — competitive comparison should make clear we are pure-analytics, not all-in-one.
- **No GSC, no on-site pixel** — both relevant gaps Nexstage's "6 sources" thesis (Real / Store / Facebook / Google / GSC / GA4) directly addresses. Metorik has 4 of the 6 (Store, Facebook, Google ads — but not GSC, not GA4 in any meaningful real-vs-platform comparison sense).
- **Order-volume pricing is universally complained-about-but-tolerated.** Metorik themselves acknowledge this in docs. If Nexstage uses a different axis (per-workspace, per-source, per-seat), it's worth A/B testing the messaging "no order-volume tax".
- **Founder-led-support narrative.** Bryce Adams personally answering every review for 8+ years is part of why this 5/5-everywhere review halo holds. Hard to replicate at scale; possible to mirror tonally early.
- **Cohort report variants list is a useful taxonomy.** Lifetime Profit / Returning (retention rate) / Order Count / Billing Country / First Product / First Coupon — worth comparing to whatever cohort dimensions we plan to expose.
- **Couldn't confirm the cohort heatmap color palette from public sources.** Multiple 3rd-party reviews describe the overall site as monochromatic blue but no public screenshot caption explicitly states the cohort cell color spec. Worth grabbing a real screenshot from a trial account if needed for our UX research.
- **G2 listing was not directly accessible (403 on WebFetch).** Quotes are pulled from G2 search snippets and cross-referenced with metorik.com/love and Shopify App Store reviews. A trial-account direct visit could yield more bilateral feedback.
