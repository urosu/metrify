---
name: Multi-store
slug: multi-store
purpose: Lets a single user (or agency) connect, switch between, and aggregate metrics across two or more stores/brands without logging in and out.
nexstage_pages: workspace-settings, dashboard, integrations, store-setup
researched_on: 2026-04-28
competitors_covered: beprofit, putler, metorik, triple-whale, glew, polar-analytics, storehero, lifetimely, daasity
sources:
  - ../competitors/beprofit.md
  - ../competitors/putler.md
  - ../competitors/metorik.md
  - ../competitors/triple-whale.md
  - ../competitors/glew.md
  - ../competitors/polar-analytics.md
  - ../competitors/storehero.md
  - ../competitors/lifetimely.md
  - ../competitors/daasity.md
  - https://www.glew.io/solutions/brands
  - https://www.glew.io/faqs
  - https://intercom.help/polar-app/en/articles/5563128-understanding-views
  - https://www.putler.com/cross-platform-analytics
---

## What is this feature

Multi-store answers the question "Can I look at all my brands at once, and can I focus on one when I need to?" The merchants asking this are agency operators (one console for 5–50 client stores), portfolio brand operators (a parent company running 2–10 sibling DTC brands often on different currencies), and merchants whose architecture is split across platforms (a Shopify storefront plus a WooCommerce subscription site, plus Amazon, plus Stripe-billed B2B). Native source platforms always have "data" per store — Shopify Analytics has 1 store; PayPal sees its own settlements — but the *feature* is the synthesis: a store/brand switcher in the chrome, a per-store-vs-aggregated toggle, currency normalization, and permission scoping so an agency CSM can be added to brand A without seeing brand B.

The difference between "having data" and "having this feature" is about three concrete UX problems: (1) **dedup of cross-platform orders** when one order shows up in Shopify + Stripe + PayPal at once; (2) **currency consolidation** when one brand sells in EUR and another in USD; (3) **permission isolation** so adding a fractional CMO to one brand doesn't expose the other brand's revenue. Glew pioneered the agency-flavoured "All Brands aggregated vs per-brand" toggle as a default mental model; Putler made cross-payment-gateway dedup the entire product wedge. The rest of the field copied unevenly.

## Data inputs (what's required to compute or display)

- **Source: Workspace/account model** — `workspace.id`, `store.id`, `store.platform` (`shopify`/`woocommerce`/`bigcommerce`/`amazon`/...), `store.currency`, `store.timezone`, `store.connected_at`, `user_workspace.role`
- **Source: Shopify / WooCommerce / BigCommerce / Amazon / Wix** — per-store `orders`, `products`, `customers`, `refunds`, `currency`, `country` ingested under the same workspace identifier
- **Source: Payment gateways (Putler-class)** — `stripe.charges`, `paypal.transactions`, `braintree.transactions` with merge keys (order ID, email, amount, timestamp window) used to dedup cross-platform double-counts
- **Source: Ad platforms** — `meta_ads.spend`, `google_ads.spend`, `tiktok_ads.spend` per ad-account; ad accounts are typically connected per-store-per-platform, so the schema needs `(workspace_id, store_id, platform_account_id)` keys
- **Source: User input** — base currency selection (`workspace.base_currency`), FX rate source (manual, daily ECB, etc.), per-brand label / logo (used in white-labelled agency rollups), per-user role (Admin / Manager / Accountant / Marketing / Read-only)
- **Source: Computed** — `aggregated_revenue = SUM(store_revenue × fx_to_base_currency)`; `dedup_revenue = total - duplicate_transactions`
- **Source: Computed** — current "lens" — selected via store-switcher dropdown — produces an `active_store_ids[]` filter applied to every query

This section is the schema requirements list for any agent extending Nexstage's `WorkspaceScope` model to support multi-store-within-workspace or multi-workspace-per-user.

## Data outputs (what's typically displayed)

- **KPI: Aggregated revenue across brands** — `SUM(store_revenue × fx_to_base)`, base currency, vs prior-period delta, broken down by brand
- **KPI: Per-brand revenue / margin / orders** — same metrics scoped to one `store_id`
- **Dimension: Store / Brand** — string, ~2–50 distinct values per workspace
- **Dimension: Platform** — Shopify / WooCommerce / Amazon / Wix / Stripe / PayPal — useful for agencies whose clients aren't all on the same platform
- **Dimension: Country (per brand)** — geographic split inside one brand or across the portfolio
- **Breakdown: Revenue × brand × time** — table or stacked bar
- **Breakdown: Profit × brand × channel** — wide table where each brand row shows channel contribution
- **Slice: Aggregated vs single brand** — top-level toggle that re-scopes the entire dashboard

## How competitors implement this

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Top-level "Multi-Store View / Comparison" entry on the Plus tier ($249/mo); store switcher in the dashboard scope selector.
- **Visualization:** Side-by-side comparison table (shop rows × metric columns) plus a high-level aggregate roll-up. Per Shopify App Store screenshot #6 caption: "Compare your shop's performance across countries, sales channels, and shops." Also a "Profit by country / shop / platform" multi-dimensional pivot per screenshot #8.
- **Layout (prose):** "Per third-party summary: 'view and compare your profit from orders, products, countries, platforms, and shops in one place' — implies a table with shop rows + comparison columns plus an aggregate roll-up." (BeProfit profile.) Actual screen layout not directly observed in public sources.
- **Specific UI:** Shop-switcher chip in the chrome; multi-shop aggregate toggle; per-shop columns side-by-side. Specific control geometry not observable from public sources.
- **Filters:** Date range, country, sales channel, shop, platform.
- **Data shown:** "Profit by shop, by platform, by country."
- **Interactions:** "Switch between shops, compare them, and view high-level aggregate data across all your shops and platforms" (third-party summary cited in profile).
- **Why it works (from reviews/observations):** Multi-store comparison is BeProfit's #1 paywall — Plus plan ($249/mo) is gated specifically on this. Reviewers in Capterra and Shopify App Store cite the unified view as the reason for upgrade. Multi-platform reach (Shopify + WooCommerce + Wix + Amazon under one BeProfit account) is broader than Lifetimely (Shopify-only).
- **Source:** [../competitors/beprofit.md](../competitors/beprofit.md) — "Multi-Store Comparison view (Plus tier only)" section.

### Putler ([profile](../competitors/putler.md))
- **Surface:** Multi-store consolidation is the *entire product positioning* — Home Dashboard already runs across all connected stores by default. Filter strip on each dashboard scopes by location/product/status/amount.
- **Visualization:** Unified KPI tiles ("Pulse" zone) with Activity Log streaming events from every connected store; Three Months Comparison widget side-by-sides last-90 vs preceding-90 across the portfolio; Transactions Dashboard renders a single colour-coded list (sales green / refunds red) merged from Shopify + Woo + Stripe + PayPal + Etsy + Amazon + eBay.
- **Layout (prose):** "Top of screen is the 'Pulse' zone for the current month: a primary Sales Metrics widget showing this-month-to-date sales, a daily-sales mini-chart, a 3-day trend, current-month target setting, year-over-year comparison vs same month previous year, and a forecasted month-end sales number — all stacked together as one widget." Adjacent Activity Log streams events across all stores. (Putler profile.)
- **Specific UI:** Filter strip with four dimensions — Location ("from continent down to street level"), Product, Status (completed / refunded / pending / failed), Type (sale / refund / etc.). Search bar accepts customer name, email, or transaction ID across all sources. Sales rendered green, refunds red — same colour rule across stores so the user reads the merged feed without thinking about which platform an event came from.
- **Filters:** Date range, location (geographic), product, status, type, payment gateway.
- **Data shown:** Aggregate revenue, transaction count, fees, taxes across all connected sources; dedup'd to avoid double-counting Shopify-paid-via-Stripe.
- **Interactions:** Single search query returns matches from any connected store/gateway "in seconds, not after a loading screen." Click row → transaction detail → in-app refund button (works for PayPal, Stripe, Shopify).
- **Why it works (from reviews/observations):** "I spend a lot of time getting an overview with Excel, but when I got Putler, I have an overview for Amazon, eBay, Etsy, Shopify. I don't need to do Excel anymore." — G2 reviewer. "Putler is great for combining sales stats, finding customer data, getting things sorted if you use multiple payment platforms especially. Paypal and Stripe, good dashboard." — Jake (@hnsight_wor), wordpress.org, July 25, 2025. The reconciliation layer is the product, not a feature.
- **Source:** [../competitors/putler.md](../competitors/putler.md) — "Home Dashboard", "Transactions Dashboard", and "Unique strengths" sections.

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** "Multi-store Dashboard" — combined view across multiple Woo or Shopify stores under one subscription. Tier-gated by store count: Level 2 = 5 stores, Level 3 = 10 stores, Level 4 = 20 stores.
- **Visualization:** No visualization specifically observed for the multi-store roll-up screen in public sources — Metorik docs describe its existence but not the chart/table form.
- **Layout (prose):** UI details for the multi-store dashboard not directly described in Metorik's public marketing or help pages — "Combined view across multiple connected Woo/Shopify stores under one subscription" is the only product-page description.
- **Specific UI:** Not observable from public sources.
- **Filters:** Store, date range; segment-builder filters apply across stores.
- **Data shown:** Aggregated KPIs across stores (net sales, AOV, LTV, customers).
- **Interactions:** Add stores up to tier limit; consolidated reports across same-platform stores.
- **Why it works (from reviews/observations):** Pricing axis includes store count without feature gating — "all-features-included plan model" with multi-store available on every tier above Starter. Important caveat from Putler's review summary: **Metorik's multi-store dashboard requires same-platform stores — cannot mix Woo + Shopify in one consolidated view.**
- **Source:** [../competitors/metorik.md](../competitors/metorik.md) — "Multi-store Dashboard" surface bullet and "Unique weaknesses" section.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Multi-store reporting gated to **Advanced tier** ($259–$389/mo entry band). Store-switcher control is referenced as part of the Summary Dashboard chrome alongside the date range; aggregated lens lives within the same dashboard.
- **Visualization:** Same Summary Dashboard layout as single-store mode — collapsible sections by data integration, draggable KPI tile grid — but data is rolled up across the connected stores when multi-store reporting is enabled.
- **Layout (prose):** "Top date-range and store-switcher controls (period-comparison toggle implied by 'vs prior period' delta language). Body is organized as collapsible sections by data integration — by default sections include Pinned, Store Metrics, Meta, Google, Klaviyo, Web Analytics (Triple Pixel), and Custom Expenses." (Triple Whale profile.) Multi-store rolls into that same surface; specific aggregated-vs-per-store toggle UI is not observable from public KB pages (KB.triplewhale.com 403'd to WebFetch in research).
- **Specific UI:** Store-switcher in the top chrome; pricing-page bullet calls out "multi-store reporting" as an Advanced-tier-and-above feature. Specific control geometry not observable from public sources.
- **Filters:** Date range, store, integration; section-level visibility toggle.
- **Data shown:** All Summary KPIs (Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV) rolled up across stores.
- **Interactions:** Switch lens; pin tiles to the "Pinned" section; pivot tile grid to a dense single table; on-demand data refresh button (April 2026) refreshes all connected integrations across stores.
- **Why it works (from reviews/observations):** Multi-store is paywalled high — Triple Whale lands brands on the Founders Dash free tier per single store, then upsells to Advanced for portfolio rollup. UI volatility is a documented cost: "The feature set is expanding rapidly, which means the UI changes frequently and documentation sometimes lags behind." — Derek Robinson / Noah Reed, workflowautomation.net, 2025–2026.
- **Source:** [../competitors/triple-whale.md](../competitors/triple-whale.md) — "Pricing & tiers" Advanced row + "Summary Dashboard" section.

### Glew ([profile](../competitors/glew.md))
- **Surface:** Top menu store-name dropdown → "Add Store"; multi-brand consolidation marketed as the headline use case on `/solutions/brands`. Aggregation across brands appears automatic when multiple stores are connected.
- **Visualization:** No visualization specifically observed for the per-brand-vs-aggregated toggle UI — only feature-level descriptions in marketing copy and FAQ. The aggregated dashboard view is described as "an instant, unified view of sales, marketing, customers and products" but the specific chart/table forms used to compare brands are not in public sources.
- **Layout (prose):** Per FAQ: "Want to add another store to your Glew account? Just log into Glew, click on your store name in the menu, then click 'Add Store.'" Aggregation across brands appears automatic when multiple stores are connected — Glew positions multi-brand consolidation as "aggregate data across multiple brands and stores into a single dashboard for a comprehensive overview." (Glew profile.)
- **Specific UI:** Store-name dropdown in top menu functions as both selector and "Add Store" entry-point. **The exact UI mechanism for the per-brand vs aggregated toggle was NOT directly observed in any public source** — Glew's `/solutions/brands` page emphasises the value but does not show the toggle/switch UI in screenshots.
- **Filters:** Store, date range, channel; "300+ unique filtering options" claimed on Glew Pro page; pre-built customer segments scoped per-brand or rolled up.
- **Data shown:** Sales, marketing, customer, product KPIs aggregated across brands; LTV Profitability by Channel; Net Profit by Channel; revenue / orders / margin per brand.
- **Interactions:** Click store name → switch brand or add a new one; aggregated dashboards view + per-brand drill via store-name dropdown. "Multi-store synchronization within single accounts" per Glew Pro page.
- **Why it works (from reviews/observations):** Glew is the "All Brands aggregated vs per-brand" pioneer per the assignment and per their own positioning. Multi-brand-as-default-mental-model differentiates them from Triple Whale / Polar where multi-store is more recent. "Glew came through for bundling analytics and reporting" — agency reviewer (multi-site DTC agency), Capterra. Glew Plus bundles a Looker license + dedicated AWS Redshift warehouse per customer, queryable via BI Tunnel, which is the agency-tier moat.
- **Source:** [../competitors/glew.md](../competitors/glew.md) — "Multi-Brand / Multi-Store Switcher" section. **Caveat noted in profile: UI details for the multi-brand toggle are not available in public sources.**

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** "Views" — saved bundle of filters spanning multiple data sources, grouped into named "Collections." Common Collections: by store, by country/region, by product, by sales channel. Plus a dedicated "Polar for Agencies" surface for agency-specific multi-brand views.
- **Visualization:** Views drive the data-source switcher dropdown at the top of every dashboard; selecting a View re-filters the entire dashboard. No store-comparison visualization unique to multi-store — the same dashboard library renders.
- **Layout (prose):** "A View is a saved bundle of filters spanning multiple data sources, grouped into named 'Collections.' Users select a View from a dropdown and the entire dashboard re-filters." (Polar profile, "Views (saved-filter system)" section.)
- **Specific UI:** Two filter scopes — **Global Filters** (apply uniformly across all sources) and **Individual Filters** (per-source rules with operators "is / is not / is in list / is not in list"). Currency adjustment is part of a View. Filter dimensions span 15+ platforms; Shopify alone exposes 40+ dimensions. Important quirk explicitly documented: "**Views combine with 'OR' logic, not 'AND.'** Multiple active Views union their results rather than intersect; docs warn users to put all filters into a single View if they need AND semantics."
- **Filters:** Store, country/region, product, sales channel, currency — combined inside a View; switching View swaps the entire dashboard scope.
- **Data shown:** Any metric/dimension in Polar's semantic layer ("hundreds of pre-built metrics and dimensions") rolled up or filtered by the active View.
- **Interactions:** Pick View from dropdown → dashboard re-renders; save/share Views per Collection; agencies use Views as their multi-brand toggle.
- **Why it works (from reviews/observations):** Polar's Snowflake-per-customer architecture means multi-store data lives in one warehouse — Views are just saved WHERE clauses on top. Universal inclusions across all plans include "unlimited users, unlimited historical data" so adding a new agency CSM doesn't change pricing. **The OR-logic gotcha is a real UX trap** — Polar's own docs warn users about it.
- **Source:** [../competitors/polar-analytics.md](../competitors/polar-analytics.md) — "Views (saved-filter system)" section + "Polar for Agencies" surface bullet.

### StoreHero ([profile](../competitors/storehero.md))
- **Surface:** "Agency Multi-Store Dashboard" — agency-tier feature; aggregated view across client stores. White-labelled with agency branding. Replaces "spreadsheets or tab-switching between client logins" per the agency landing page.
- **Visualization:** No visualization specifically observed for the multi-store grid in public sources — the agency landing page describes the value but does not show a screenshot of the aggregated screen.
- **Layout (prose):** "White-labelled (agency branding), unified view aggregating sales/ad spend/profit across multiple client accounts. Per the agency page, replaces 'spreadsheets or tab-switching' between client logins." (StoreHero profile.)
- **Specific UI:** White-labelled chrome (agency logo replaces StoreHero brand). Specific control geometry, store-switcher position, aggregate-toggle pattern NOT observable from public screenshots.
- **Filters:** Store, date range, channel; same filter set as single-store dashboard.
- **Data shown:** Sales, ad spend, contribution margin, ROAS, breakeven ROAS rolled up across client stores.
- **Interactions:** Switch between client stores; aggregated view; agency-tier pricing is "Book a Demo" only — not self-serve.
- **Why it works (from reviews/observations):** Contribution-margin-first framing applied portfolio-wide is StoreHero's wedge for agencies. "Using StoreHero has been fantastic — the platform is excellent and it really gives the agency and business owner a clear snapshot of the store's financial health." — Dylan Rogers, Madcraft Agency (agency-page testimonial). No public agency pricing — the agency page leads with "Book a Demo" and treats agencies as a sales conversation.
- **Source:** [../competitors/storehero.md](../competitors/storehero.md) — "Agency Multi-Store Dashboard" surface section.

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Multi-store is **not a primary surface** in Lifetimely's IA. Shopify is required at install; Amazon is a paid +$75/mo add-on per store. Pricing axis is monthly orders — adding a second Shopify store is not a documented feature path on their pricing page.
- **Visualization:** Not observed in public sources.
- **Layout (prose):** Not observed — Lifetimely's product pages do not describe a multi-store / multi-brand dashboard mode. The Income Statement P&L view is described as scoped to a single store.
- **Specific UI:** Not observed.
- **Filters:** Date range, cohort, channel — within a single store.
- **Data shown:** P&L, LTV, cohorts — single store.
- **Interactions:** Connect Shopify (required); connect Amazon as paid add-on.
- **Why it works (from reviews/observations):** Lifetimely is **Shopify-only**, with Amazon as a +$75/mo add-on. "No WooCommerce, no BigCommerce, no headless. Amazon is paid add-on only. Hard wall for any merchant on a non-Shopify stack." (Lifetimely profile, "Unique weaknesses" section.) Multi-store-as-portfolio is not part of the product narrative.
- **Source:** [../competitors/lifetimely.md](../competitors/lifetimely.md) — "Pricing & tiers" + "Unique weaknesses" sections.

### Daasity ([profile](../competitors/daasity.md))
- **Surface:** "Multi-store consolidation across all Shopify integrations into a unified UOS schema." — Daasity's data warehouse architecture treats multi-store as a default at the schema level; Home Dashboard tabs are organised by department (Ecommerce / Marketing / Retail) not by store, so multi-store rolls up automatically into the department lens.
- **Visualization:** Three vertically-stacked sections in the **Company Overview (Omnichannel)** dashboard: (1) Top KPIs Current Period with Channel Mix % (top 5 channels by contribution + rolled-up "all others"); (2) **Weekly Sales by Channel stacked chart** (horizontal axis = week start dates fiscal/calendar, vertical axis = total dollar sales, current period and prior/year-ago period as separately coloured bands, optional YoY overlay line); (3) Weekly Detail Table with columns Week, Total Sales, YoY %, Change vs Prior Week, Channel Sales (both $ and %).
- **Layout (prose):** "Top-line KPIs segmented by channel, with three sub-tabs labelled Ecommerce, Marketing, Retail — each containing the key metrics for the respective department. Click-through links from each KPI tile drill into the corresponding specialized dashboard." (Daasity profile, "Home Dashboard" section.) Multi-store rolls into department dashboards by default — there is no separate "multi-store dashboard."
- **Specific UI:** **Daily Flash** and **Daily Flash vs. Plan** dashboards use **Store Type** and **Store Integration Name** filters as the multi-store axis; defaults to "ALL Store Integrations combined." Filter changes do not auto-apply: "When you Toggle the Dashboard Filters the Data on the Dashboards will update after you click the Refresh Button." Hourly Flash is Shopify-only.
- **Filters:** Store Type, Store Integration Name (combined / per-store), date range, channel, currency-adjusted base.
- **Data shown:** Net sales (currency-adjusted), YoY %, PoP %, channel mix %, weekly sales by channel/store; multi-store rolled up to UOS unified schema.
- **Interactions:** Toggle store filter → click Refresh button to re-render; default "ALL Store Integrations combined."
- **Why it works (from reviews/observations):** Daasity collapses what would otherwise be a Fivetran + dbt + Looker + Hightouch stack into one product. Customer roster (Manscaped, Rothy's, Poppi, SC Johnson, Tweezerman, Béis, Method) skews $5M–$1B+ omnichannel — the multi-store wedge is omnichannel (DTC + Amazon + retail/wholesale + syndicated panel) more than multi-brand. "I've used Glew, TripleWhale, Lifetimely and more and this is by far the best tool I've used." — Béis (operates two 8-figure stores), Shopify App Store, March 3, 2022.
- **Source:** [../competitors/daasity.md](../competitors/daasity.md) — "Home Dashboard", "Company Overview (Omnichannel)", and "Flash Dashboards" sections.

## Visualization patterns observed (cross-cut)

- **Store-switcher dropdown in top chrome:** 5 competitors (Glew, Triple Whale, Polar Analytics, BeProfit, StoreHero) — the dominant control geometry. Glew uses the store-name dropdown that doubles as "Add Store" entry-point.
- **Filter-only multi-store axis (no separate dashboard):** 3 competitors (Daasity via Store Integration Name filter, Polar via Views, Putler via Filter strip). Multi-store is treated as another saved filter, not a separate surface.
- **Dedicated "Multi-Store Comparison" surface:** 1 competitor (BeProfit) — separate top-level entry, side-by-side shop columns + aggregate row, paywalled to Plus.
- **No visualization, prose-only:** 4 competitors (Metorik, Glew per-brand-vs-aggregated toggle, StoreHero agency dashboard, Lifetimely n/a) — public sources describe the existence of the multi-store surface but not the chart/table form. This is unusually opaque.
- **Department-organised IA (sidesteps multi-store entirely):** 1 competitor (Daasity) — Home tabs are Ecommerce / Marketing / Retail instead of Brand A / Brand B / Brand C; multi-store rolls up into the department lens automatically.
- **Stacked bar with multi-period band coloring + YoY overlay line** (Daasity Company Overview): 1 implementation; useful for portfolio revenue-by-week-by-brand if extended with a brand dimension.
- **Color rule consistency across stores:** Putler's universal "sales = green, refunds = red" rule applied to a merged multi-source feed is the only documented colour-discipline pattern. The user reads the merged feed without thinking about which platform an event came from.

Notable absence: **no competitor publicly screenshots a per-brand-vs-aggregated toggle UI**. Glew is the named pioneer of this pattern but the actual control geometry is behind a sales-led demo gate. BeProfit's screenshot #6 caption ("Compare your shop's performance across countries, sales channels, and shops") is the closest public artefact and even that doesn't show the toggle itself.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: One overview replaces a stack of platform tabs**
- "I spend a lot of time getting an overview with Excel, but when I got Putler, I have an overview for Amazon, eBay, Etsy, Shopify. I don't need to do Excel anymore." — G2 reviewer, [putler profile](../competitors/putler.md)
- "Putler is great for combining sales stats, finding customer data, getting things sorted if you use multiple payment platforms especially. Paypal and Stripe, good dashboard." — Jake (@hnsight_wor), wordpress.org plugin review, July 25, 2025, [putler profile](../competitors/putler.md)
- "All my Woo sales and customer analytics consolidated in one place. Used every day for years." — Fishbottle, wordpress.org plugin review, [putler profile](../competitors/putler.md)
- "I've used Glew, TripleWhale, Lifetimely and more and this is by far the best tool I've used. The customer support is unparalleled and they can actually get me answers to questions I've been trying to get at for months." — Béis (operates two 8-figure stores), Shopify App Store, March 3, 2022, [daasity profile](../competitors/daasity.md)

**Theme: Cross-payment-gateway dedup as the value-add**
- "Tried various other platforms. Like Baremetrics (also btw if there's one to skip, it's them). Putler is great for combining sales stats… if you use multiple payment platforms especially." — Jake (@hnsight_wor), wordpress.org, July 25, 2025, [putler profile](../competitors/putler.md)
- "Now I have a single source of truth that saves me hours weekly." — Waqas Q., Capterra, May 29, 2025, [putler profile](../competitors/putler.md)

**Theme: Agency-side visibility across client stores**
- "Using StoreHero has been fantastic — the platform is excellent and it really gives the agency and business owner a clear snapshot of the store's financial health." — Dylan Rogers, Madcraft Agency, [storehero profile](../competitors/storehero.md)
- "Glew came through for bundling analytics and reporting" — agency reviewer (multi-site DTC agency), Capterra (paraphrased), [glew profile](../competitors/glew.md)
- "easy to comprehend dashboards at your fingertips with actionable insights" — Jonathan J S., eCommerce Manager, Capterra Oct 2019, [glew profile](../competitors/glew.md)

**Theme: Multi-platform reach in one tier (Shopify + Woo + Amazon + Wix)**
- "BeProfit is by far the most accurate and analytical one we've used." — Braneit Beauty, Shopify App Store, February 12, 2026, [beprofit profile](../competitors/beprofit.md)
- "Worked great on woo, works great on shopify!" — ReFerm (Sweden), Shopify App Store review, March 3, 2026, [metorik profile](../competitors/metorik.md)

## What users hate about this feature

**Theme: Multi-store gated to top tier — steep cliff**
- "BeProfit's multi-store overview requires their $149 Ultimate plan" — TrueProfit comparison page (note: actual Plus tier $249 per current Shopify listing); cited in [beprofit profile](../competitors/beprofit.md)
- "Multi-store gated to top tier. Anything beyond a single shop forces the $249/mo Plus plan — a steep cliff vs Lifetimely's per-shop pricing." — [beprofit profile](../competitors/beprofit.md), "Unique weaknesses" section

**Theme: Same-platform-only roll-up (cannot mix Woo + Shopify)**
- "Multi-store cannot mix platforms in one consolidated dashboard (per Putler review summary; not directly contradicted by Metorik docs)." — [metorik profile](../competitors/metorik.md), "Unique weaknesses" section

**Theme: Filter-combination semantics are non-obvious and create wrong numbers**
- "Views OR-vs-AND gotcha. Multiple Views combine with OR, not AND — Polar's own docs warn users to put all filters in one View instead. This is non-obvious and likely creates incorrect numbers for users who don't read the docs." — [polar-analytics profile](../competitors/polar-analytics.md), "Unique weaknesses" section
- "Switching between views and reports can be slow sometimes" — bloggle.app review, 2024, [polar-analytics profile](../competitors/polar-analytics.md)

**Theme: Pricing escalates faster than store count grows**
- "Pricing is based on revenue tiers, which means costs increase as your store grows — can get expensive at scale." — Rachel Lopez (Meridian Designs), workflowautomation.net, January 31, 2026, [triple-whale profile](../competitors/triple-whale.md)
- "Order-volume pricing model penalizes scale. Acknowledged as frustrating by Metorik themselves in their pricing FAQ. Brands hitting 10k+ orders/mo move to four-figure pricing without unlocking additional features — pure volume tax." — [metorik profile](../competitors/metorik.md), "Unique weaknesses" section
- "the platform can be expensive if not using it to its full power" — G2 review pattern, 2025, [glew profile](../competitors/glew.md)

**Theme: Cancellation and billing friction (especially for agencies adding/removing client stores)**
- "Worst experience ever, been charging me for months despite contacting them about cancellation." — Clear Cosmetics, Shopify App Store, March 4, 2026, [beprofit profile](../competitors/beprofit.md)
- "Money grabbers" / "Intentionally difficult to cancel subscription" — Trustpilot reviewer (per search index summary), [glew profile](../competitors/glew.md)
- "Aggressive billing / cancellation friction is a documented pattern on Trustpilot — refusal to cancel without 'exit interview', continued billing past cancellation date, prorated refund refusals when software was buggy." — [glew profile](../competitors/glew.md), "Unique weaknesses" section

**Theme: White-labelling / agency UI is shallow**
- BeProfit competitive page characterising StoreHero as missing "industry benchmarks, customizable dashboards" for agency tiers — [storehero profile](../competitors/storehero.md), "Unique weaknesses" (competitor-authored attack noted as biased but plausible on dashboard customisation)

## Anti-patterns observed

- **Same-platform-only multi-store consolidation (Metorik):** Multi-store dashboard requires that all connected stores be on the same platform. A merchant with one Shopify storefront and one WooCommerce subscription site cannot see them merged. Putler explicitly competes on this gap. Anti-pattern because the merchant's mental model is "my business" not "my Shopify business" — platform is an implementation detail.
- **Filter combination using OR instead of AND, with no UI signal (Polar Analytics):** Multiple active Views *union* their results. The product's own docs warn users to put all filters in one View. Anti-pattern because users get silently-wrong numbers; the wrongness is undetectable from the dashboard UI.
- **Manual refresh required after filter change (Daasity Flash dashboards):** "When you Toggle the Dashboard Filters the Data on the Dashboards will update after you click the Refresh Button." Anti-pattern because users can stare at stale data thinking the filter applied. Reviewers cite this as one of the platform's friction points.
- **Multi-store paywalled at the top tier with a steep cliff (BeProfit):** Single shop on Basic / Pro / Ultimate ($49–$149/mo); unlimited shops only on Plus ($249/mo). Merchants growing past one shop face a 67% price jump. Anti-pattern because the upgrade conversation becomes "do I need this whole Plus bundle for the multi-store toggle?"
- **Adding a store requires reconfiguring everything (no public source confirms this — flagged as observed pattern via reviewer themes):** Glew Trustpilot noise about "constantly bothered to upgrade" and Capterra reviews about "didn't work with the SOFTWARE THAT THEY SAID IT WOULD WORK WITH" suggest multi-store onboarding regressions during account expansion.
- **Per-brand vs aggregated toggle UX completely undocumented (Glew, StoreHero):** The named pioneers of agency-friendly multi-brand dashboards do not publicly screenshot the toggle. Suggests either the pattern is shallow (one dropdown) or the implementation is inconsistent enough to hide. Anti-pattern by absence — buyers cannot evaluate the UX without a demo.

## Open questions / data gaps

- **Glew's per-brand-vs-aggregated toggle UI is not in any public source.** FAQ confirms the menu-based "Add Store" / store-name-dropdown pattern, but the actual switch UI sits behind the demo gate. Direct screenshot would require a Glew sales demo.
- **Putler's cross-platform dedup heuristic is not documented publicly.** They claim "automatically identify and merge duplicate transactions across payment gateways and eCommerce platforms" but the matching keys (order ID? amount + timestamp? email?) are not exposed.
- **Triple Whale's store-switcher UI specifics are unobservable.** KB.triplewhale.com 403'd to WebFetch in research; multi-store-reporting feature on Advanced tier is confirmed by the pricing page but the UI control geometry is not captured.
- **Polar's "Polar for Agencies" surface is mentioned but not depth-described.** The Views system is documented; the agency-multi-brand layer on top is mentioned only on the pricing page.
- **StoreHero agency dashboard has no public screenshot.** Agency landing page is `Book a Demo` only.
- **Currency normalisation handling differs across competitors but is rarely documented.** Putler claims 36+ currencies with single-base-currency conversion; Polar bundles currency adjustment inside Views; BeProfit's "Compare Countries report" implies multi-currency but no FX-rate-source description is published.
- **Permission scoping for multi-brand setups is barely covered in any profile.** Putler mentions "Admin / Manager / Accountant / Marketing roles" with "unlimited team members" included; Polar advertises "unlimited users"; Glew has "unlimited users". No competitor publicly documents per-brand role scoping (i.e. "user X is admin on brand A, read-only on brand B"). Worth a paid-eval account or sales-call to verify.
- **Whether "All Brands aggregated" view applies dedup across brand boundaries** (e.g., a customer who shops on brand A and brand B counted once or twice in LTV) is not documented for any competitor.

## Notes for Nexstage (observations only — NOT recommendations)

- **5 of 9 competitors put the multi-store control in the top chrome as a store-switcher dropdown.** Glew, Triple Whale, Polar, BeProfit, StoreHero. None publicly screenshot the per-brand-vs-aggregated toggle itself, suggesting the UI is shallower than the marketing positioning implies.
- **Putler's "filter strip + filter chips" pattern is the only documented multi-store UX where merging across platforms (Shopify + Woo + Stripe + PayPal + Amazon + eBay + Etsy) is the *default* rather than a feature flag.** Their colour rule (sales green, refunds red) applied universally across the merged feed is the cleanest cross-store visual-discipline observation.
- **Daasity's department-organised IA (Ecommerce / Marketing / Retail tabs) sidesteps the multi-store toggle entirely** — multi-store is a filter on top of department dashboards, not a separate dashboard. This is an architectural alternative to the per-brand-vs-aggregated toggle Glew pioneered. Worth noting against Nexstage's `WorkspaceScope` model: the question becomes "does Nexstage have a primary lens of 'workspace' or 'department'?"
- **Multi-store gating at the top pricing tier is universal (BeProfit Plus $249/mo, Triple Whale Advanced $259+/mo, Glew Plus quote-based).** Metorik is the exception — multi-store on every paid tier with store-count caps. If Nexstage prices per-workspace rather than per-store, that's directly contrary to every competitor's monetization wedge.
- **Same-platform-only multi-store (Metorik) is the documented anti-pattern.** Putler wedges hard against it. Nexstage's workspace model would naturally support cross-platform consolidation since `orders` is platform-agnostic at the snapshot level.
- **Currency normalisation is barely documented.** Putler is the only competitor that publishes a number ("36+ currencies"); Polar bundles it into Views with no FX-rate-source disclosure. Direct opportunity for Nexstage to be explicit: which FX rate, sourced from where, applied at what timestamp.
- **Permission scoping per brand is unobserved across every profile.** "Unlimited users" is the marketing answer; per-brand role scoping is the actual user need for agencies. Nexstage's `user_workspace.role` model maps onto this directly if the per-store layer is added.
- **"Add Store" as an in-chrome verb (Glew's menu pattern) is the dominant onboarding flow** — store-list dropdown that doubles as the add-entry-point. No separate "Settings > Stores" page is the canonical pattern (though all of them likely have one).
- **The "All Brands aggregated vs per-brand" toggle is described in marketing copy across Glew, BeProfit, StoreHero, Triple Whale, Daasity — but never publicly screenshotted.** This is a category-wide opacity. If Nexstage ships and screenshots this UI clearly, that becomes a marketing-page differentiator with low effort.
- **OR-vs-AND filter semantics is a UX trap (Polar).** Any saved-filter / segment / view system Nexstage builds for multi-store inherits this risk. Worth a single explicit decision in `docs/decisions/` before shipping.
- **White-label / agency mode is paywalled and demo-only at every competitor that offers it (StoreHero agency, Glew Plus).** No public pricing for agency-tier multi-brand. Gap for Nexstage if pricing is published transparently.
- **Lifetimely is a counter-example — they don't have a multi-store story at all.** Shopify-only, Amazon as +$75 add-on, no multi-Shopify-store consolidation. They've ceded this segment by choice. Suggests there's a real customer cohort (single-brand DTC operators) that doesn't need multi-store and doesn't pay for it — useful for Nexstage tier design.
