---
name: WooCommerce Native (built-in Analytics tab)
url: https://woocommerce.com
tier: T2
positioning: Free, in-admin sales reporting bundled with the WooCommerce plugin; the default "Analytics" experience for any self-hosted WordPress + WooCommerce merchant.
target_market: SMB / SOHO self-hosted WooCommerce merchants on WordPress; single-store, single-currency stores. No revenue floor; default for everyone who installs WooCommerce.
pricing: Free (bundled with the WooCommerce core plugin). Paid scaling happens via the WooCommerce extension marketplace, not via tiered Analytics SKUs.
integrations: Native: WooCommerce orders, products, coupons, taxes, downloads, stock, customers. Order-attribution sources captured via "Meta for WooCommerce", "Google for WooCommerce", "TikTok for WooCommerce", "Pinterest" (3rd party feed plugins) — each is a separately installed extension. No native cross-platform ad-spend pull, no native GA4 pull, no native GSC pull.
data_freshness: Default ~12 hours since WooCommerce 10.5 (scheduled batch import, 100 orders / batch). Optional "immediate" mode exists but degrades admin performance.
mobile_app: Yes — official "WooCommerce" iOS / Android app (separate from Analytics tab). Mobile app surfaces a "My Store" stats screen, not the full Analytics report set.
researched_on: 2026-04-28
sources:
  - https://woocommerce.com
  - https://woocommerce.com/document/woocommerce-analytics/
  - https://woocommerce.com/document/woocommerce-analytics/revenue-report/
  - https://woocommerce.com/document/woocommerce-analytics/products-report/
  - https://woocommerce.com/document/woocommerce-analytics/customers-report/
  - https://woocommerce.com/document/woocommerce-analytics/order-attribution-report/
  - https://woocommerce.com/products/woocommerce-analytics/
  - https://woocommerce.com/products/google-listings-and-ads/
  - https://woocommerce.com/products/facebook/
  - https://woocommerce.com/products/tiktok-for-woocommerce/
  - https://developer.woocommerce.com/2026/02/06/woocommerce-10-5-improving-analytics-and-admin-performance/
  - https://developer.woocommerce.com/2026/04/15/woocommerce-10-7/
  - https://wordpress.org/support/topic/woocommerce-analytics-is-hot-garbage-and-i-hate-it/
  - https://wordpress.org/support/topic/woocommerce-analytics-show-totally-wrong-numbers-and-data-wont-reset/
  - https://github.com/woocommerce/woocommerce-admin/issues/6529
  - https://github.com/woocommerce/woocommerce-admin/issues/2855
  - https://github.com/woocommerce/woocommerce-admin/issues/6958
  - https://www.putler.com/woocommerce-report-limitations
  - https://www.databloo.com/blog/woocommerce-analytics/
  - https://www.databloo.com/blog/woocommerce-reporting/
  - https://flexibleinvoices.com/blog/woocommerce-analytics-what-it-has-to-offer/
  - https://woocommerce.com/mobile/
---

## Positioning

WooCommerce Native is not a marketed product — it's the "Analytics" sidebar item that appears for free inside the WordPress admin once the WooCommerce plugin is installed. There is no positioning page, no pricing page, no sales pitch; it ships as part of `wp-admin > Analytics`. Conceptually it's the WooCommerce equivalent of Shopify's built-in Analytics, but with two big differences: (1) it's an open-source plugin rather than a SaaS surface, so refresh cadence and DB performance are the merchant's problem, and (2) anything beyond core sales/orders reporting is pushed to the extension marketplace.

The product replaces nothing — it's the floor. Most third-party reporting tools (Metorik, Putler, Beeketing, Conjura, Lifetimely, etc.) explicitly position themselves as "the analytics WooCommerce should have shipped with."

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| WooCommerce core (Analytics tab) | Free | 11 reports (Revenue, Orders, Products, Variations, Categories, Coupons, Taxes, Downloads, Stock, Customers, Order Attribution); CSV export; date-range comparison | Need ad-spend / ROAS / cost data, multi-channel rollup, COGS profit, LTV — none of which are in core |
| Order Attribution extension ("WooCommerce Analytics" on Marketplace) | Free (early access / beta, 20K+ active installs) | Five attribution reports: Orders by Channel, Source, Campaign, Device, Channel+Source. Last-touch only. Single-currency only. | Multi-currency stores; need ROAS or first-touch / multi-touch attribution |
| Marketing extensions (per-channel) | Free to install — pay-per-click on the underlying ad platform | Google for WooCommerce (free + $500 ad-credit promo), Meta for WooCommerce (Pixel + CAPI), TikTok for WooCommerce, Pinterest feeds via 3rd-party | Wanting cross-channel ad-spend rollup in one screen — does not exist natively even after installing all of them |
| Subscription analytics (paid) | Bundled inside WooCommerce Subscriptions extension (~$199/yr) | MRR / churn / renewal tooling | Recurring revenue stores need MRR/ARR — not in free Analytics tab |

The Analytics tab itself never paywalls a feature. Paywalls live in adjacent extensions (Subscriptions, Memberships, Bookings, Google Analytics Pro by SkyVerge, etc.).

## Integrations

**Pulled from (sources):**
- WooCommerce orders, line items, refunds, customers (registered + guest), products, variations, categories, coupons, taxes, downloads, stock — all native, same DB.
- Order Attribution extension captures last-touch UTM-style data on each order: channel (organic, paid, email, referral, direct), source (google, facebook, instagram), campaign, device.
- Meta for WooCommerce extension: Pixel + Conversions API (server-side), product feed sync, but data lives in Meta Ads Manager, not in WooCommerce Analytics.
- Google for WooCommerce: Merchant Center sync, free product listings, Google Ads campaign creation, conversion tracking via gtag — but ad-spend data lives in Google Ads UI.
- TikTok for WooCommerce: Pixel + Events API. Same story — campaign data lives in TikTok Ads Manager.

**Pushed to (destinations):** None native. CSV export is the only outbound. No API webhook into a BI tool from the Analytics tab itself.

**Coverage gaps observed:**
- No native GA4 ingestion. (Google Analytics Pro by SkyVerge is a separate paid extension; it pushes events to GA, doesn't pull GA reports back.)
- No GSC integration anywhere in the WooCommerce extension catalog.
- No native ad-spend pull. Even after installing Meta for WooCommerce + Google for WooCommerce + TikTok for WooCommerce, the Analytics tab does NOT show spend, impressions, clicks, CPC, or ROAS. Those numbers stay in each platform's own UI.
- No multi-store consolidation. Each WooCommerce install is a silo.
- Single-currency only on the Order Attribution extension (verbatim warning on the marketplace listing).

## Product surfaces (their app's information architecture)

The Analytics tab in `wp-admin > Analytics` exposes:

- **Overview** — landing dashboard with configurable "Performance" stat cards, a "Charts" widget where you pick which metric to chart, and a "Leaderboards" widget (top categories, top coupons, top customers, top products). Drag-to-reorder cards.
- **Revenue** — gross sales / returns / coupons / net sales / taxes / shipping / total sales over time.
- **Orders** — list of orders by date with status / coupon / product / customer-type filters.
- **Products** — per-product items-sold, net sales, orders count, variations count, stock status.
- **Variations** — per-variation breakdown (sub-set of Products).
- **Categories** — per-category items-sold and net-sales rollup.
- **Coupons** — coupon usage and discount amount.
- **Taxes** — tax rate / jurisdiction breakdown.
- **Downloads** — downloadable-product file access log.
- **Stock** — current inventory snapshot (NOT exportable to CSV — confirmed limitation).
- **Customers** — registered + guest list, sortable by Orders / Total Spend / AOV / Last Active / Country.
- **Order Attribution** (separate extension) — five sub-reports: by Channel, by Source, by Campaign, by Device, by Channel+Source.
- **Settings** (Analytics > Settings) — configure which order statuses count as "actualised", choose Date Created vs Date Paid as the report axis, "Import Historical Data", "Clear Analytics Cache".
- **Marketing** (sibling tab, not under Analytics but referenced from it) — extension installer for Meta / Google / TikTok / Mailchimp / Klaviyo. Marketing tab has its own light dashboard with "Campaigns" + "Channels" + "Coupons" + "Recommendations".

That's ~13 surfaces if you count Settings and Marketing as part of the analytics experience.

## Data they expose

### Source: WooCommerce (native DB)
- **Pulled:** orders, order line items, refunds, customers (registered + guest), products, variations, categories, coupons, taxes, downloads, stock levels.
- **Computed:** Gross sales, Returns, Coupons (discount applied), Net Sales `= (product price * quantity) - refunds - coupons` (verbatim from Products report doc), Total Sales, AOV, Items Sold, Orders count, Customer count, New vs Returning split, Total Spend per customer.
- **Attribution windows:** N/A — orders are timestamped at create time; the Settings page lets the merchant pick "Date Created" vs "Date Paid" as the analytics axis. No multi-touch lookback.
- **NOT computed:** Profit, margin, COGS-aware metrics, LTV, RFM, cohort retention, repeat-purchase rate, churn, MER, contribution margin, forecast. WooCommerce 10.3 added a per-product COGS field in the schema, but no native report consumes it (per Putler's gap analysis and confirmed in the WooCommerce changelog).

### Source: Meta Ads (via "Meta for WooCommerce" extension)
- **Pulled:** Pixel events (PageView, AddToCart, Purchase) + server-side Conversions API events. Product feed sync to Commerce Manager.
- **Computed:** None inside WooCommerce Analytics. Spend / ROAS / CPM / CPC live exclusively in Meta Ads Manager. The extension only deduplicates Pixel + CAPI events on a unique event ID.

### Source: Google Ads (via "Google for WooCommerce" extension)
- **Pulled:** Conversion tracking via gtag. Product feed sync to Merchant Center for free listings + Smart Shopping campaigns.
- **Computed:** None inside WooCommerce Analytics. Cost / impressions / clicks live in Google Ads UI.
- **Promo:** "$500 USD in ad credit when you spend your first $500 on Google Ads within 60 days" — ad-credit acquisition pitch.

### Source: TikTok Ads (via "TikTok for WooCommerce" extension)
- **Pulled:** TikTok Pixel + Events API browser/server tracking; "Advanced Matching" optional.
- **Computed:** None in Analytics tab. Spend lives in TikTok Ads Manager.

### Source: Order Attribution (free extension on the Marketplace)
- **Pulled:** last-touch channel/source/campaign/device captured at checkout via JS.
- **Computed:** Orders count + (per docs) gross sales, refunds, coupon usage, AOV — broken down by the five dimensions above.
- **Attribution windows:** Last-touch only, hard-coded. Safari is capped at ITP's 7-day cookie window per Putler's analysis.

### Source: GA4
- Not integrated natively. Third-party extensions (Google Analytics Pro by SkyVerge, MonsterInsights) push events outbound but do not pull GA4 reports back into the Analytics tab.

### Source: GSC
- No native or marketplace-blessed GSC integration. Not part of WooCommerce's analytics surface at all.

## Key UI patterns observed

### Overview dashboard
- **Path/location:** `wp-admin > Analytics > Overview` (default landing).
- **Layout (prose):** Three stacked, draggable widget zones. Top widget is "Performance" — a horizontal row of stat cards, default ~3–6 cards, each card showing a metric label, the period total, the prior-period total in smaller text below, and a percentage delta with a colored arrow (green up / red down). Middle widget is "Charts" — a single line chart, selector dropdown above it lets you pick which metric to render; an interval selector (Day / Week / Month / Quarter / Year) auto-scales to the date range. Bottom widget is "Leaderboards" — four side-by-side mini-tables (Top Products, Top Categories, Top Coupons, Top Customers) each showing top-5 with a tiny inline value. The whole page has a date-range bar at the top.
- **UI elements (concrete):** Date-range picker is a dropdown with named presets (Today, Yesterday, Week to date, Last week, Month to date, Last month, Quarter to date, Year to date, Last year) plus a custom-range calendar. Comparison toggle ("compared to: previous period | previous year | none") sits adjacent. Cards have a small "Choose" / "Customize" gear letting users add/remove metrics. No sparklines on cards (line chart is separate, below).
- **Interactions:** Drag-to-reorder widgets. Click a card → it becomes the highlighted metric in the chart below. Click a leaderboard row → drills into the corresponding report (Products / Categories / Coupons / Customers) pre-filtered to that entity.
- **Metrics shown (default cards):** Total sales, Net sales, Orders, Average order value, Items sold, Returns, Discounted orders, Gross discounted amount, Total interest, Total tax, Order tax, Shipping tax, Shipping, Downloads, Variations sold.
- **Source:** https://woocommerce.com/document/woocommerce-analytics/ + https://flexibleinvoices.com/blog/woocommerce-analytics-what-it-has-to-offer/ (UI details not captured as PNG; described from public docs).

### Revenue report
- **Path/location:** `Analytics > Revenue`.
- **Layout (prose):** Date-range bar + comparison-toggle at top. Below it, a horizontal row of seven "summary number" cards: Gross sales, Returns, Coupons, Net sales, Taxes, Shipping, Total sales. Each card shows the period total + prior-period total + percentage delta. Click a card → it becomes the focused series in the line chart immediately below. The line chart spans full content width and is toggleable between line and bar. Below the chart sits a sortable table with one row per day.
- **UI elements (concrete):** Each summary card has a colored selection ring when active. Chart has a legend, a y-axis for the chosen metric only (no dual-axis), and an x-axis with auto-scaled interval (Day → Week → Month based on range). Table has a column-visibility toggle on the right of the header letting users hide columns; column choices persist to user preferences per report. Pagination at the bottom. CSV "Download" button top-right of the table.
- **Interactions:** Click a card to switch chart metric. Click the Orders count in any table row → routes to the orders edit list pre-filtered to that day. CSV download is async on large date ranges — emailed as a link rather than direct download (a recurring user complaint).
- **Metrics shown:** Gross Sales, Returns, Coupons, Net Sales, Taxes, Shipping, Total Sales, Orders count (table only).
- **Source:** https://woocommerce.com/document/woocommerce-analytics/revenue-report/

### Orders report
- **Path/location:** `Analytics > Orders`.
- **Layout (prose):** Date-range bar + comparison toggle. Summary cards across the top (Orders, Net Sales, AOV, etc.). Line chart in the middle. Sortable table at the bottom with one row per order, sorted by order date descending; refunded orders appear twice — once on the original date and once on the refund date. An "Advanced Filters" affordance sits between the chart and the table.
- **UI elements (concrete):** Filters open as a stacked panel with chip-style filter rows. Each filter row has a left-side dimension dropdown (Order Status / Products / Coupon Codes / Customer Type / Refunds / Tax Rates / Product Attribute) and a right-side value selector. Match-mode radio at the top of the panel: "Match all" vs "Match any". "Add filter" button at the bottom. Refunded-order rows have a return-arrow icon so you can spot duplicates.
- **Interactions:** Sort by Date / Items Sold / Net Sales (only those three are sortable, per docs). Filter to drill in. Click an order ID → routes to the standard WooCommerce order edit screen.
- **Metrics shown per row:** Date, Order #, Status, Customer, Customer Type (new vs returning), Items Sold, Coupon, Net Sales.
- **Source:** https://woocommerce.com/document/woocommerce-analytics/orders-report/

### Products report
- **Path/location:** `Analytics > Products`.
- **Layout (prose):** Same chassis as Revenue/Orders — date-range, summary cards, chart, table. Default table view is "All Products" sorted by Items Sold descending. A "Single product" toggle in the top-left lets users search a single SKU and load just that product's report. A "Compare" mode lets users tick checkboxes next to rows in the table and click a "Compare" button in the table header to render only the selected products in the chart and table.
- **UI elements (concrete):** Search bar with partial-string-match. Per-row checkbox column. Status column shows inventory state (in stock / low / out of stock) as a colored pill. "Comparison" mode renders multiple lines on the chart in distinct colors.
- **Interactions:** Click product title → single product view. Click category cell → drills into Categories report filtered to that category. CSV download.
- **Metrics shown:** Product Title, SKU, Items Sold, Net Sales, Orders, Category, Variations count, Status, Stock.
- **Source:** https://woocommerce.com/document/woocommerce-analytics/products-report/

### Customers report
- **Path/location:** `Analytics > Customers`.
- **Layout (prose):** Date-range bar + summary cards (Total Customers, New, Returning) + chart + table. No per-customer chart on the index — only on the single-customer drill-down view.
- **UI elements (concrete):** Search bar uses partial-string match on names. The advanced filters panel offers Name, Country, Username, Email, Orders count, Total Spend, AOV, Registered date, Last Active date — but verbatim from the docs, "these filters do not allow for a partial match on customers" (only the search bar does). Email column is a `mailto:` link; Name column links to the WP user profile.
- **Interactions:** Sort columns: Name, Last Active, Sign Up, Orders, Total Spend, Country, City, Region, Postal Code. Click a customer → load that customer's individual report (orders timeline + lifetime stats).
- **Metrics shown:** Name, Username, Last Active, Date Registered, Email, Orders, Total Spend, AOV, Country, City, Region, Postal Code.
- **Source:** https://woocommerce.com/document/woocommerce-analytics/customers-report/

### Order Attribution report (separate free extension)
- **Path/location:** `Analytics > Attribution` after the "WooCommerce Analytics" extension is installed.
- **Layout (prose):** Tabbed-or-segmented sub-nav with five views: Channel, Source, Campaign, Device, Channel + Source. Each view renders the standard chassis: date-range bar, summary cards, chart, table.
- **UI elements (concrete):** Channel buckets are pre-defined: Direct, Organic Search, Organic Social, Paid Search, Paid Social, Email, Referral, Other. Source rows show the underlying domain or ad-platform (google, facebook, instagram). Campaign view exposes UTM-campaign values captured at checkout. Device view splits Mobile / Desktop / Tablet. The combined Channel+Source view nests source under channel (e.g., "Paid Ads: Google", "Paid Ads: Facebook").
- **Interactions:** Sort + filter same as other reports. CSV export. No multi-touch path visualization (last-touch only — verbatim documentation).
- **Metrics shown:** Orders, Gross sales, Refunds, Coupons, Net sales, AOV — broken down by the dimension.
- **Caveats noted on the marketplace listing:** "This extension is designed for single-currency stores only. Multi-currency functionality is not supported, which may result in inaccurate analytics for stores operating in multiple currencies." Extension is in "early access (beta) stage" with 20K+ active installs.
- **Source:** https://woocommerce.com/document/woocommerce-analytics/order-attribution-report/ + https://woocommerce.com/products/woocommerce-analytics/

### Stock report
- **Path/location:** `Analytics > Stock`.
- **Layout (prose):** Single sortable table. No chart, no summary cards — purely a tabular snapshot of current inventory.
- **UI elements (concrete):** Status pills for stock state (in stock / low / out of stock).
- **Interactions:** Sort columns. **No CSV export** — confirmed via Putler's gap analysis: "Stock report cannot be exported at all. If you need inventory data in a spreadsheet, you have to use the legacy Products screen, which has its own limitations."
- **Metrics shown:** Product, SKU, Status, Stock.
- **Source:** https://www.putler.com/woocommerce-report-limitations

### Mobile app — "My Store" stats screen
- **Path/location:** Official WooCommerce iOS/Android app, "My Store" tab.
- **Layout (prose):** A summary screen showing sales and top-performing products for a chosen period (Day / Week / Month / Year). Order list is real-time. Push notifications on new orders/reviews require Jetpack.
- **UI elements (concrete):** UI details not available — only feature description seen on the marketing page (https://woocommerce.com/mobile/). The marketing page emphasises "process orders and watch your sales climb in real time" and "key metrics on the go" without naming specific metrics or screens beyond order processing and the My Store stats card.
- **Interactions:** Push notifications, order processing, multi-store management (Jetpack-gated).
- **Metrics shown:** Sales total + top products for the selected period. Mobile does NOT replicate the full Analytics report set.
- **Source:** https://woocommerce.com/mobile/

## What users love (verbatim quotes, attributed)

Limited published reviews exist for "WooCommerce Analytics" as a standalone product (G2 / Capterra index WooCommerce overall, not the Analytics tab). Quotes captured are from WordPress.org support, the marketplace listing, and aggregator articles.

- "Great tool!" — WooCommerce Marketplace listing, noted as a user descriptor of WooCommerce Analytics, captured April 2026.
- "WooCommerce makes it easy to set up products for sale and provides everything required for selling products online — product listings, cart functionality, secure payments, inventory tracking, shipping management, and order processing." — Capterra reviewer (general WooCommerce review touching reporting), 2026.
- "Easy to set up and manage while offering a high level of flexibility and customization, integrating well with WordPress, with a huge plugin ecosystem and very reliable performance." — Capterra summary of WooCommerce reviews, 2026.
- "MonsterInsights' eCommerce report shows revenue, transactions, conversion rate, and average order value at a glance." — WPBeginner contextual quote about why merchants augment the native Analytics tab, 2026 (used here as implicit endorsement of the metric set core surfaces).
- "Powerful insights into each order's last-touch data." — Marketplace listing copy for the Order Attribution extension, captured April 2026.

Limited reviews available — the Analytics tab is bundled, so no dedicated review page exists for it on G2/Capterra/Trustpilot. Most positive sentiment lives in the form of "it's free and it works for the basics" rather than enthusiastic endorsement.

## What users hate (verbatim quotes, attributed)

- "Every single report I try to do forces it as a comparison and I don't want a f***ing comparison report, I just want the date range I select, and that's it. Period. X to y, and that's it. Not x to y, and last year x to y." — @jeffsbesthemp, WordPress.org support, November 2024.
- "This used to be so simple, and now everything is a total chore just to get simple reports. Why did you ruin all the report features woocommerce? Why? I HATE ANALYTICS SOOOOO MUCH!" — @jeffsbesthemp, WordPress.org support, November 2024.
- "A total chore because the csv or excel sheets are EMAILED TO ME NOW instead of just being downloadable the way they were before." — @jeffsbesthemp, WordPress.org support, November 2024.
- "A total chore because other reports connected to this also don't run smooth, like best seller, and revenue based best seller. […] A total chore because it's hot garbage from what it used to be, and I can't even pick the dates easily and the version of the report easily anymore." — @jeffsbesthemp, WordPress.org support, November 2024.
- "My Woocommerce Reports show the correct data (84 orders, 119 products purchased, 14000eur revenue) but when I go to Woocommerce Analytics I see insane numbers (600 orders, 900 products purchased, 98000eur revenue). Shows orders on the dates that had 0 orders." — @krdza93 (Danilo Krdzic), WordPress.org support, July 2023.
- "This is a huge bug in the system. It happens when you delete orders — so deleted orders are still visible in the analytics even if they do not exist anywhere on the website." — @krdza93, WordPress.org support, August 2023.
- "Net Revenue totals on the Revenue and Orders pages match each other, but do not match the Net Revenue on the Product page or Categories page, even though the Product and Category page Net revenues match each other." — GitHub issue #2855, woocommerce-admin, paraphrased verbatim from issue body, captured 2026.
- "When searching all of January, the orders and metrics match exactly what the Overview report shows, but if the date range is changed to the last two days of January, the report results basically double." — GitHub issue #6529, woocommerce-admin, captured 2026.
- "Multi-currency functionality is not supported, which may result in inaccurate analytics for stores operating in multiple currencies." — Marketplace listing for the Order Attribution extension (verbatim warning shown by WooCommerce themselves), captured April 2026.
- "Painfully slow at first run; for under 1k orders with order attribution it takes over 1 hour" — paraphrased complaint pattern surfaced by Putler's analysis of WordPress.org and forum threads, 2026.

The Automattic "Happiness Engineer" reply on the "hot garbage" thread acknowledged the criticism: "Unfortunately there isn't a way to completely disable the 'previous period' at the moment. At most you can hide the graph which compares the previous period." — @mikkamp, WordPress.org support, November 2024. (Confirms the limitation rather than refuting it.)

## Unique strengths

- **Free, bundled, zero install friction.** Every WooCommerce store has it on day one — no separate signup, no API connection, no OAuth dance.
- **Direct DB joins.** Because reports query the same MySQL/MariaDB the store writes to, there is no sync lag for the underlying source-of-truth (lag is in the analytics aggregation layer, not the order data itself).
- **Deeply customizable Overview.** The widget-grid + per-card metric chooser + drag-reorder is more flexible than Shopify's fixed Overview layout.
- **Open extension marketplace.** Anything missing (cohort, LTV, RFM, channel-mapping) can in principle be filled by an extension — Metorik, Putler, Conjura, Lifetimely, MonsterInsights all hook into the same data.
- **Last-touch attribution is now native and free.** The Order Attribution extension covers what merchants previously paid Wicked Reports / Polar / Triple Whale for — at the basic level, last-touch only.
- **CSV export on most reports.** Engineering-friendly escape hatch.
- **Per-user column-visibility persistence.** Hide/show choices per report stick to the WordPress user account — small but appreciated.
- **HPOS (High-Performance Order Storage) + 10.5 batch import + 10.7 N+1 query reduction** show Automattic is investing in scale: WooCommerce 10.7 cut the `/wc/v4/orders` endpoint from 271 queries to 132 — a 51% reduction.

## Unique weaknesses / common complaints

- **No profit / COGS reporting.** Per Putler: "no native profit report. No margin breakdown by product" and "no way to see whether your bestselling product is making you money." A COGS field exists on the product schema (added 10.3) but no report consumes it.
- **No customer LTV.** Per Putler: "no customer lifetime value calculation, no way to identify your highest value buyers" and no scoring/RFM segmentation.
- **No cohort analysis or retention curves.** The Customers report shows new vs returning counts only.
- **No ad-spend ingestion.** Even with Meta + Google + TikTok extensions installed, spend lives in each platform's own UI. No blended ROAS, no MER, no CPA inside Analytics.
- **No GA4 pull and no GSC integration anywhere** in the marketplace.
- **Last-touch only** in the Order Attribution extension — no first-touch, linear, or position-based options.
- **Single-currency only** for the Order Attribution extension, with WooCommerce explicitly warning of "inaccurate analytics" for multi-currency stores.
- **Forced previous-period comparison** with no opt-out — repeatedly cited as a regression vs the legacy "Reports" screen.
- **CSV export is async-emailed** rather than direct download for large ranges, and the **Stock report cannot be exported at all**.
- **Confirmed data-integrity bugs:** failed-then-succeeded payments double-count; deleted orders persist in Analytics tables (`wc_order_stats`, `wc_order_product_lookup`, etc.) until lookup tables are manually emptied; Revenue/Orders Net Sales doesn't match Products/Categories Net Sales (GitHub issue #2855); date-range filter doubles results in some windows (issue #6529).
- **12-hour default refresh** since WooCommerce 10.5 — not real-time, not hourly.
- **Performance-fragile on large catalogs.** WordPress's `posts` + `postmeta` schema isn't designed for 50k+ SKU stores; analytics aggregation is one of the first things that breaks.
- **No multi-store rollup.** Each WooCommerce install is its own silo.
- **No native subscription analytics** (MRR/ARR/churn) — pushed to the paid WooCommerce Subscriptions extension which itself has only a 3.4-star rating per Putler.
- **No funnel / checkout drop-off analysis.**
- **No forecasting** of any kind — explicitly "entirely backward-looking" per Putler.

## Notes for Nexstage

- WooCommerce's 11-report list is the **floor of merchant expectations** for any tool serving Woo merchants. If we don't replicate Revenue / Orders / Products / Variations / Categories / Coupons / Customers, we'll feel like a regression. Order Attribution by Channel/Source/Campaign/Device is a useful 5-dimension breakdown to mirror.
- WooCommerce **does not blend ad-spend into Analytics** even when Meta/Google/TikTok extensions are installed. This is the single clearest "Nexstage opportunity" — one screen showing Real (Store) revenue side-by-side with Facebook spend and Google spend is exactly the gap merchants currently bridge with spreadsheets.
- The **6-source badge thesis** (Real / Store / Facebook / Google / GSC / GA4) is not represented anywhere in WooCommerce native — the Order Attribution extension has channel buckets but no concept of "this number from Store vs this number from Meta Pixel". Worth contrasting head-on in our marketing copy.
- WooCommerce's "Date Created vs Date Paid" axis toggle in Analytics > Settings is a useful UX precedent — merchants explicitly want control over which timestamp the report uses. Worth mirroring for our snapshot pipeline.
- The **"forced comparison" complaint** (jeffsbesthemp thread) is a UX lesson: comparison is good as a default but must be dismissible. Our period-comparison toggle should have an explicit "off" state.
- The **deleted-orders-persist bug** and **Net Sales mismatch across reports** are known WooCommerce data-integrity foot-guns. Our snapshot rebuild path (`SnapshotBuilderService`) sidesteps this by being the only writer — worth highlighting to merchants who've been bitten.
- **CSV export emailed instead of downloaded** is universally hated. Direct download for any reasonable range is a low-effort differentiator.
- **No COGS-aware profit** is the single biggest Putler-cited gap. Our `ProductCostImportAction` + cost-config flow + retroactive `RecomputeAttributionJob` is directly aligned with this complaint.
- **No GSC anywhere in WooCommerce world** — confirms GSC as a real differentiator for us, not a parity feature.
- **Mobile app is real-time for orders but thin on analytics** — "My Store" shows top-line sales + top products only. Web-responsive parity on our analytics views may be enough; native mobile is not table stakes for SMB Woo merchants based on this baseline.
- The Order Attribution extension's **single-currency limitation** is a flag for any multi-currency Woo merchant we onboard — they cannot trust the native attribution numbers and will be highly motivated to switch to a tool that handles FX correctly.
- WooCommerce's recent **performance investments (10.5 batch import, 10.7 N+1 reduction)** show they know analytics is a sore spot. Our positioning shouldn't assume "WooCommerce Analytics is broken forever" — it's improving, but the architectural ceiling (single-store, no ad spend, no GA4/GSC) is still there.
