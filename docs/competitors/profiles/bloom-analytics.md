---
name: Bloom Analytics
url: https://bloomanalytics.io
tier: T1
positioning: Profit-first analytics for Shopify SMBs that replaces spreadsheet-based COGS/expense tracking with auto-synced dashboards plus light marketing attribution
target_market: Shopify SMB merchants (founders, marketers, agencies, operators); multi-store / multi-currency ready; global (reviews from US, NL, FR, DE, SE, PT)
pricing: Free + paid tiers $20 / $40 / $80 per month; order-volume axis but no overage penalties
integrations: Shopify, Google Ads, Meta Ads, Bing Ads, TikTok Ads, Pinterest Ads, Snapchat Ads, GA4, Klaviyo, Bold Subscriptions, ShipStation, ShipHero, FedEx, Slack, Shopify Markets, Amazon (announced)
data_freshness: real-time (auto-sync via Shopify API; "Bloom syncs data in real time"); initial historical pull = last 3 months, then monthly batches
mobile_app: unknown (no iOS/Android app referenced; web-responsive Shopify embedded app)
researched_on: 2026-04-28
sources:
  - https://bloomanalytics.io
  - https://www.bloomanalytics.io/pricing
  - https://www.bloomanalytics.io/dashboard
  - https://www.bloomanalytics.io/profit-analytics
  - https://www.bloomanalytics.io/shopify-profit-and-loss-dashboard
  - https://www.bloomanalytics.io/blog/10-best-shopify-profit-apps-in-2026
  - https://www.bloomanalytics.io/blog/top-10-analytics-apps-for-shopify-in-2026
  - https://www.bloomanalytics.io/blog/trying-to-decide-between-bloom-profit-analytics-and-lifetimely-we-ve-got-you-covered
  - https://apps.shopify.com/bloom-analytics
  - https://apps.shopify.com/bloom-analytics/reviews
  - https://docs.bloomanalytics.io/sitemap.md
  - https://docs.bloomanalytics.io/overview-dashboard.md
  - https://docs.bloomanalytics.io/profit-table.md
  - https://docs.bloomanalytics.io/product-profits.md
  - https://docs.bloomanalytics.io/order-profits.md
  - https://docs.bloomanalytics.io/product-analysis.md
  - https://docs.bloomanalytics.io/cohort-analysis.md
  - https://docs.bloomanalytics.io/integrations.md
  - https://docs.bloomanalytics.io/data-synchronization.md
  - https://docs.bloomanalytics.io/multi-store.md
  - https://docs.bloomanalytics.io/bloom-pixel/enable-bloom-pixel-3-steps.md
  - https://docs.bloomanalytics.io/shipping-costs-setup.md
---

## Positioning

Bloom is a Shopify-native profit analytics app aimed at SMB merchants who currently track COGS, ad spend, shipping, and operating expenses in spreadsheets. The hero pitch is "Uncover Your Real Shopify Profit without Spreadsheets, Math, and Madness." The product replaces BeProfit / TrueProfit / Lifetimely / SimplyCost in the "true profit calculator" category, but extends into light marketing attribution (Bloom Pixel + UTM-based) at the top tier. Provenance: built by the team behind Report Pundit and Data Export, two long-running Shopify reporting apps — homepage callout reads "from the makers of Report Pundit & Data Export". The angle is "affordable, honest pricing" with a free starter tier, explicitly priced to undercut Lifetimely ($149-$749/mo) and Triple Whale ($129+/mo).

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Free | $0 | Shopify App Store free plan available | Custom COGS / multi-store / Klaviyo |
| Sprout | $20/mo | Dashboard, detailed profit table, order-level profit, marketing metrics, custom COGS, custom shipping cost, Shopify shipping auto-sync, metrics dashboard, email summary, custom settings, visitor metrics | Need product-level profit / customer LTV / Klaviyo email campaign profits |
| Grow | $40/mo | Everything in Sprout + Shopify Markets, product profit analytics, product intelligence, Klaviyo email campaign profits, customer lifetime value, Slack updates | Need marketing attribution & country-level ROAS |
| Flourish | $80/mo | Everything in Grow + marketing attribution, VIP support, onboarding manager, country ROAS & profit | Top tier |

All paid plans include a 14-day free trial. Pricing scales with monthly order volume but the brand explicitly markets "no penalties for exceeding limits — you don't get locked out or hit with surprise charges." Coming soon (per pricing page): "Marketing Intelligence (adding soon)," "Profit Forecast (adding soon)," "AI Insights (adding soon)," and Amazon integration.

## Integrations

**Sources pulled:**
- **Shopify** (required, primary platform): orders, line items, customers, products, refunds, transactions, payouts, Shopify shipping costs (auto-sync), Shopify Markets
- **Ad platforms:** Google Ads, Meta/Facebook Ads, Bing Ads (Microsoft Ads), TikTok Ads, Pinterest Ads, Snapchat Ads
- **Analytics:** Google Analytics 4 (GA4)
- **Email:** Klaviyo (campaign-level profit attribution)
- **Subscriptions:** Bold Subscriptions
- **Shipping/3PL:** ShipStation, ShipHero, FedEx
- **Workspace push:** Slack (alerts/digests)
- **Amazon** announced as upcoming integration in pricing page roadmap

**Coverage gaps observed:**
- **No GSC integration** — Bloom does not pull organic search data from Google Search Console
- **No WooCommerce** — Shopify-exclusive
- **No Microsoft Clarity / heatmaps** — competitors like Lucky Orange fill this gap
- **No Amazon Ads / Walmart / TikTok Shop** — only DTC channels
- **No ReCharge or Recharge-style subscriptions** beyond Bold (Lifetimely supports ReCharge per Bloom's own comparison page)

## Product surfaces (their app's information architecture)

Reconstructed from the docs sitemap (https://docs.bloomanalytics.io/sitemap.md) plus dashboard marketing pages.

- **Onboarding / Getting Started** — guided setup with progress bar, lands on the main dashboard after install
- **Overview Dashboard** — top-level "360 snapshot of revenue, costs & margin"; the default landing page
- **My Metrics** — user-customizable dashboard; drag-and-drop from a 150+ metric library (Sales / Profit / Marketing / Customer)
- **Profit Map** — visual interactive tree-graph showing how each metric flows into net profit (homepage describes "Visualize Profit Drivers at a Glance")
- **Profit Table / Profit Report** — pivot-style table with metrics on rows, time periods on columns, "Total" column on the right; expandable sub-rows
- **Product Profits** — per-product/variant profitability with 20+ metrics
- **Product Analysis** — product-strategy view (price, type, tags, stock) with profit overlay
- **Order Profits** — per-order profit breakdown with Gateway/Shipping/COGS/Handling/Channel Fee/Tariff cost columns
- **Cohort Analysis** — cohort retention / accumulated sales per customer / cohort sales / cohort transactions
- **Customer Metrics** — new vs returning, AOV, customer LTV
- **Marketing Metrics** — channel ROAS, MER, POAS, CAC, BEROAS, country-level ROAS (Flourish tier)
- **Marketing Attribution** (Flourish only) — UTM-based attribution dashboard; campaign + ad-level
- **Bloom Pixel** — proprietary tracking pixel installed via Shopify app embed + checkout extension
- **Bloom Pixel Custom Domain** — option to host pixel under merchant's custom subdomain
- **Settings: Product Costs** — per-product COGS entry / bulk import
- **Settings: Shipping Costs** — multi-tier setup (rules, integrations, Shopify shipping auto-sync, manual edit). Shipping rules support: by country, by products, by fulfillment center, by shipping method
- **Settings: Custom Operating Expense** — recurring/one-off operating expense entry
- **Settings: Add Custom Revenue** — manually injected revenue line items
- **Settings: Order Settings** — order-level tax/discount/refund handling rules
- **Settings: Multiple Shops** — main-store/connected-store model with shop key + reporting currency
- **Settings: Integrations** — connect ad platforms, GA4, Klaviyo, FedEx, ShipStation, ShipHero, Slack, Bold

That is roughly 18-20 distinct surfaces — consistent with a T1 competitor.

## Data they expose

### Source: Shopify
- **Pulled:** orders, line items, customers, products, variants, refunds, discounts, shipping (Shopify shipping cost auto-sync), gateway/payment transactions, payouts, tax, Shopify Markets metadata
- **Computed:** Net Revenue, GMV, COGS, fulfillment costs, CM1/CM2/CM3 contribution margins (with %), Gross Profit, Gross Margin %, Net Profit, AOV, units sold, refund/discount impact
- **Order-level cost columns explicitly listed:** Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost
- **Initial historical sync:** "data from the last three months to start off the reporting. The historical data is then collected in monthly batches."
- **Refresh:** real-time API sync; "Bloom syncs data in real time"

### Source: Meta Ads / Google Ads / Bing Ads / TikTok Ads / Pinterest Ads / Snapchat Ads
- **Pulled:** spend, impressions, clicks, platform-attributed conversions, ROAS at campaign level
- **Computed:** MER (Marketing Efficiency Ratio), aMER (new-customer ROAS), MPR (Marketing Profit Ratio / POAS), aMPR (new-customer POAS), CAC, BEROAS (breakeven ROAS), country-level ROAS (Flourish tier)
- **Attribution windows:** not explicitly published; UTM-based attribution via Bloom Pixel for the Flourish tier

### Source: GA4
- Pulled but specific fields not documented publicly. Listed alongside ad-platform integrations.

### Source: Klaviyo
- **Pulled:** email campaign performance
- **Computed:** "Klaviyo email campaign profits" — net profit attributed to specific Klaviyo campaigns (Grow tier+)

### Source: Bloom Pixel (proprietary)
- **Bloom claims:** "Proprietary tracking technology that gives a 99% accurate view of your customers' purchase journey" (from Bloom-vs-Lifetimely comparison page)
- Setup: 3 steps — (1) enable App Embed in theme editor, (2) enable checkout-analytics block on checkout + thank-you page, (3) configure UTM parameters on Meta/Google/TikTok/Klaviyo/Snapchat/Pinterest ads
- Optional custom-domain hosting for first-party pixel

### Source: ShipStation / ShipHero / FedEx
- Pulled actual shipping cost per order; integrates into the shipping-cost rule engine (rules + integration + Shopify shipping + manual edit form a 4-layer fallback)

## Key UI patterns observed

### Overview Dashboard
- **Path/location:** Default landing page after onboarding; left-panel sidebar with sections including "Profit Analysis," "Product Metrics," "Customer Metrics," "Marketing Metrics"
- **Layout (prose):** Marketing copy describes "360 snapshot of revenue, costs & margin." The docs confirm a stacked-section layout with four primary blocks: (1) Revenue-to-Profit summary cards, (2) Margin Overview cards with percentages, (3) Marketing Performance cards, (4) Customer & Revenue cards. Below the cards sits a stack of trend charts and a Top 5 products list.
- **UI elements (concrete):** KPI cards each show "the metric value for the selected period" plus "the percentage change compared to the previous period." Marketing site shows example cards rendering "$68.28K, 10.3% From Last Month" for Net Profit and "$420K, 5.2% compare to last week" for Ad Spend — implying period-comparison delta is rendered inline as percentage with directional copy (no color descriptor confirmed).
- **Charts:** Four trend charts named in docs:
  1. Revenue-to-Profit % chart (revenue distribution across cost categories as percentages)
  2. Profit Margins Trend (CM1, CM2, CM3 over time)
  3. Marketing Performance Trend (MER, aMER, MPR, aMPR as lines + CAC as bars — mixed line/bar combo chart)
  4. Customer Type Trend (new vs repeat customer revenue and order contribution)
- Marketing copy elsewhere mentions "Spline Area charts" as the visualization style.
- **Interactions:** Date range picker (daily/weekly/monthly/yearly); period comparison vs prior year or same historical period; cumulative analysis option
- **Metrics shown:** Net Revenue, COGS, Cost to Fulfill Orders, Marketing Costs, Operating Expenses, Net Profit, Total Revenue, CM1 / CM2 / CM3 (with %), MER, aMER, MPR, aMPR, CAC, New Customers, New Customers %, BEROAS, AOV
- **Source:** https://docs.bloomanalytics.io/overview-dashboard.md

### My Metrics (custom dashboard)
- **Path/location:** Sidebar; promoted as "150+ Shopify, ad, and cost metrics" library
- **Layout (prose):** Marketing copy: "Drag the ones that impact profit into your custom dashboard." Toggle between "number widgets and charts" — implying each metric supports two render modes. Marketing categories drawn from: Sales, Profit, Marketing, Customer.
- **UI elements:** Drag-and-drop widget canvas, toggle for widget vs chart per tile
- **Interactions:** Per-widget chart/number toggle, period selection, comparison
- **Source/screenshot:** UI details described in marketing prose only (https://www.bloomanalytics.io/dashboard) — no public screenshot inspected.

### Profit Map
- **Path/location:** Sidebar > Profit Analytics
- **Layout (prose):** Per Bloom's own Lifetimely comparison: "A visual, interactive tree graph that lays out exactly how various metrics … impact your net profit." Homepage describes "Visualize Profit Drivers at a Glance" and consolidates "revenue alongside every expense — ad spend, product costs, shipping, and operating overhead so you instantly see how net profit is calculated."
- **UI elements:** Tree-graph nodes/branches (specific node count, orientation, hover state not documented publicly). The docs sitemap does NOT have a `profit-map.md` page (404 confirmed) — feature is positioned as marketing-page material, suggesting it may be branded differently inside the app or covered under "overview-dashboard."
- **Interactions:** Marketing copy mentions interactivity but specifics (drill-down, hover tooltip behavior) not documented publicly
- **Metrics shown:** Revenue + every cost line + net profit
- **Source/screenshot:** UI details NOT available — only marketing-page description seen.

### Profit Table / Profit Report
- **Path/location:** Sidebar > Profit Analytics
- **Layout (prose):** Pivot table where COLUMNS are time periods (driven by group-by selection) plus a final "Total" column summarizing the date range, and ROWS are metrics. Many rows are expandable to reveal sub-metrics.
- **UI elements:** Expandable parent rows, negative numbers rendered with minus signs, all amounts in account-level "Reporting Currency"
- **Interactions:** Date range picker; group-by toggle (day / week / month); row expansion
- **Metrics shown (in 5 vertical tiers):**
  1. Revenue (7 categories: GMV, gross revenue, discounts, returns, shipping, net revenue, etc.)
  2. Cost (product cost, COGS, fulfillment costs with 6 subcategories)
  3. Contribution Margins (CM1, CM2, CM3 plus percentage variants)
  4. Marketing (6 indicators including MER, CAC, MPR)
  5. Net Profit (5 final profitability metrics)
- **Source:** https://docs.bloomanalytics.io/profit-table.md

### Product Profits
- **Path/location:** Sidebar > Product Metrics
- **Layout (prose):** Standard wide table with column-selector menu in top-right, filter icon nearby. Toggle "View by product variants" expands rows to variant level. 20+ metric columns.
- **UI elements:** Column selector (top-right corner), filter icon, variant-toggle, export menu
- **Interactions:** Filter by Product Name, Variant Name, Product Type, SKU, Tags, Status, ACTIVE flag; export to Excel / CSV / PDF; manual campaign-link entry for Product ROAS attribution
- **Metrics shown:** Units Sold, Units Refunded, Gross Sales, Discounts, Refunds, Net Sales, Product COGS, Inventory Cost, Total Sales, Gross Profit, Gross Margin %, Net Profit, Ad Spend, Campaign Link, Product ROAS, Inventory Quantity, Inventory Value
- **Source:** https://docs.bloomanalytics.io/product-profits.md

### Product Analysis
- **Path/location:** Sidebar > Product Metrics
- **Layout:** Product-attribute focused table (separate from Product Profits)
- **UI elements:** Date range, column filter, column visibility toggle, rows-per-page + row-size adjustments, export to Excel/CSV/PDF
- **Metrics shown:** Product Title, Variant Title, Price, Product Type, Tags, Stock Quantity, Net Sales, COGS, Gross Profit, Gross Margin
- **Source:** https://docs.bloomanalytics.io/product-analysis.md

### Order Profits
- **Path/location:** Sidebar (Order Profits)
- **Layout (prose):** Per-order table with date-range filter and order-name search dropdown. Customizable column selector.
- **UI elements:** Order-name search dropdown, column selector, date range picker, export, "edit operational costs and reimport updated files for recalculation" workflow
- **Metrics shown:** Created At, Items, Gross Sales, Discounts, Refunds, Net Sales, Tax, Total Sales, Shopify Gross Profit, Shopify Gross Margin %, Contribution Margin 1 (+%), Contribution Margin 2 (+%), Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost
- **Source:** https://docs.bloomanalytics.io/order-profits.md

### Cohort Analysis
- **Path/location:** Sidebar > Customer Metrics
- **Layout (prose):** Triangular cohort table where rows = cohort grouping (e.g., May 2023 cohort) and columns = numbered post-acquisition periods showing cumulative customer value
- **UI elements:** Cohort grouping dropdown (weekly / monthly / quarterly / yearly), date range (default past 6 months), metric dropdown
- **Interactions:** Switch between four metrics: Accumulated Sales per Customer, Customers, Cohort Sales, Cohort Transactions
- **Metrics shown:** Cohort label, New Customers, First Order avg value, period-N repeat purchase value
- **Source:** https://docs.bloomanalytics.io/cohort-analysis.md

### Multi-Store
- **Path/location:** Settings > Multiple Shops
- **Layout (prose):** Main-store / connected-store model. Connected stores reveal a "Shop Key" string the user copies, then pastes into the main store's "Connect Store" dialog.
- **UI elements:** Shop Key reveal field, "Connect Store" button, Reporting Currency selector
- **Behavior split:** Profit Analysis / Profit Metrics pages roll up consolidated across all stores; other pages display individually per store
- **Source:** https://docs.bloomanalytics.io/multi-store.md

### Bloom Pixel setup (in-app embed flow)
- **Path/location:** Shopify theme editor (App Embeds) + checkout editor + integrations panel
- **Layout (prose):** Two-toggle install (theme embed + checkout-analytics block) followed by per-channel UTM parameter configuration screens (one each for Meta, Google, TikTok, Klaviyo, Snapchat, Pinterest)
- **Interactions:** Per-channel guidance branches: "if you have existing UTM parameters" vs "if you have no existing UTM parameters" + a verification step
- **Source:** https://docs.bloomanalytics.io/bloom-pixel/enable-bloom-pixel-3-steps.md

## What users love (verbatim quotes, attributed)

Source pool: Shopify App Store reviews (15 total, all 5-star at time of research). Limited reviews available outside the App Store; no Reddit threads found via `site:reddit.com` search.

- "It gives us much more clarity on our numbers, helps us make better decisions." — CAPS (Netherlands), Shopify App Store, April 13, 2026
- "The support is AMAZING as well — I have never had a better experience!" — Curio Blvd (United States), Shopify App Store, April 2, 2026
- "Must-have tool, yet at a very affordable price. It gives you all the [insights]…" — OMOYÉ (France), Shopify App Store, December 12, 2025
- "We now know exactly what we make from every sale. Thanks John and [team]." — kicksshop.nl (Netherlands), Shopify App Store, January 19, 2026
- "Great app! Very interactive UI. Gives you full insight in product data…" — BRUNS (Sweden), Shopify App Store, January 28, 2026
- "Love the app, and Ziyan from Customer Support was super helpful." — MedShop Direct (United States), Shopify App Store, December 30, 2025
- "Bloom has been a great tool for real time analytics of our shopify [store]." — World Rugby Shop (United States), Shopify App Store, January 22, 2025
- "No more digging through spreadsheets — just instant, actionable data." — Baron Barclay Bridge Supply (United States), Shopify App Store, March 11, 2025
- "I've been looking for a tool like this for years. It provides clear insights, which is incredibly valuable for your webshop." — kicksshop.nl (Netherlands), via AppNavigator review listing, January 19, 2026
- "Pretty complete for tracking profits in a simple way. Lately their [improvements]…" — Tempus Mods (Portugal), Shopify App Store, May 30, 2025

## What users hate (verbatim quotes, attributed)

Limited critical reviews available — Shopify App Store rating is 5.0/5 across 15 reviews and no negative reviews surfaced in public sources. No Reddit, Trustpilot, G2, or Capterra listings found at time of research. The closest signals to constructive criticism:

- (None directly observed.) Reviewer signals like "merchants satisfied with Bloom's features and eager for full data synchronization" (general summary, exact quote not surfaced) hint at impatience with initial historical-sync turnaround, consistent with Bloom's documented "syncing can take between a few minutes to several hours" caveat.

Note: With only 15 App Store reviews and 50,000+ claimed merchants, review coverage is extremely thin — possible the bulk are on the free plan or not review-prompted. Treat the 5.0 rating as low signal.

## Unique strengths

- **Price floor.** $20 entry tier is materially below BeProfit ($35-$200/mo entry per Bloom's own blog) and Lifetimely ($149/mo entry); Bloom positions explicit "no penalties for exceeding limits" against Lifetimely's overage charges.
- **Shopify-native pedigree.** Built by the team behind Report Pundit and Data Export — two long-running Shopify reporting apps. Homepage tag "from the makers of Report Pundit & Data Export" is a credibility signal small competitors lack.
- **Profit Map (visual tree-graph).** Differentiated layout pattern claimed; Bloom calls out "interactive tree graph that lays out exactly how various metrics … impact your net profit" as their signature view — a more spatial alternative to BeProfit/TrueProfit's flat-table P&L.
- **Bloom Pixel with claimed 99% accuracy** for purchase-journey attribution, with optional custom-domain hosting (first-party). Quoted accuracy figure published on their own comparison page.
- **Layered shipping-cost engine.** 4-layer fallback (rule-based → 3PL integration → Shopify shipping auto-sync → manual edit) with rules by country / product / fulfillment center / shipping method — unusually granular for the SMB tier.
- **Klaviyo email campaign profit attribution** at the $40 tier — most $20-tier-equivalent competitors do not offer email-level profit.
- **CM1/CM2/CM3 explicit contribution margin tiering** in the dashboard (gross profit → after fulfillment → after marketing). Fewer SMB competitors use the explicit "CM1/2/3" naming convention; this is closer to how Lifetimely and Drivepoint frame margins.
- **Multi-currency reporting currency selector** at the workspace level — simpler model than Triple Whale's per-store reporting currency.

## Unique weaknesses / common complaints

- **Review depth is shallow.** 15 App Store reviews against a "50,000+ merchants" claim; effectively zero presence on Reddit, G2, Capterra, Trustpilot, or YouTube walkthroughs. Public scrutiny is minimal.
- **No GSC integration** — organic search performance is invisible. Direct gap vs Nexstage's 6-source thesis (Real / Store / Facebook / Google / GSC / GA4).
- **Marketing attribution is paywalled at the top tier ($80/mo Flourish).** Country-level ROAS, multi-touch attribution, and full Bloom Pixel UTM rollouts are all gated behind the highest plan.
- **No public access to the dashboard demo without installing.** The "Open demo app" CTA links to `app.bloomanalytics.io/dashboards` but resolves to a Shopify-shop-domain install gate, blocking deep UI inspection without a real Shopify store.
- **Profit Map visual layout is undocumented in public docs.** No `profit-map.md` page exists in the docs sitemap (404 confirmed); the feature is described only on marketing pages without screenshots inspected here.
- **Self-comparison content is the dominant SEO play.** Bloom's blog hosts the "10 best Shopify profit apps in 2026" article framed against TrueProfit, BeProfit, Triple Whale, Lifetimely — credible-looking listicle SEO, but readers should treat as attorney-of-record positioning.
- **Roadmap items still labeled "(adding soon)":** Marketing Intelligence, Profit Forecast, AI Insights, Amazon integration. Suggests current product still narrower than top-tier marketing.
- **No mobile app** referenced; product is a Shopify embedded app only.

## Notes for Nexstage

- **Direct lineage of "true profit" segment.** Bloom is the most explicit Nexstage analog among Shopify-only profit calculators. Their CM1/CM2/CM3 + MER/aMER/MPR/aMPR/CAC/BEROAS metric vocabulary is the de-facto language SMB Shopify merchants are already trained on. We should expect leads to arrive fluent in these acronyms.
- **Order-level cost columns are an exact specification target.** Bloom's per-order columns (Gateway Cost, Shipping Cost, Product Variant COGS, Handling Cost, Channel Fee, Tariff Cost) form a concrete reference list for our order-cost editor. Note: "Tariff Cost" is a 2025-2026 addition — relevant given current trade policy.
- **Shipping-cost engine architecture worth studying.** Their 4-layer fallback (rules → 3PL integration → Shopify auto-sync → manual edit), with rules dimensioned by country / product / fulfillment center / shipping method, is more sophisticated than typical SMB tools. If we're competing on "real" profit accuracy, our shipping cost story has to at least match this.
- **Profit Map (tree-graph) is their headline visual.** It's the one UI moment Bloom positions as proprietary vs BeProfit/TrueProfit's flat tables. Our `MetricSourceResolver` + 6-source-badge thesis is a different visual concept — but worth sketching out an analogous "where does the money go" decomposition view since Bloom has trained the market to expect one.
- **Bloom Pixel + Bloom Custom Domain (first-party pixel).** They sell a 99% accuracy claim on purchase-journey attribution. We should decide whether Nexstage's source-of-truth thesis (cross-source reconciliation) competes with this pitch directly or sidesteps it.
- **Klaviyo email-campaign profit at the $40 tier.** This unlocks at lower price than most. If we promote channel-level ROAS but treat Klaviyo as an afterthought, mid-market merchants may downgrade us mentally.
- **No GSC at all in the Bloom stack.** Their roadmap doesn't list GSC. This is a defensible Nexstage moat in the SMB segment if positioned correctly.
- **Pricing axis is order-volume but soft-capped.** "No overage penalties" is a marketing weapon they use against Lifetimely. Our cost-config model should not lock or rate-limit at thresholds without an equivalent answer.
- **150+ metric drag-and-drop "My Metrics" dashboard.** Implies a metric-catalog pattern — useful reference if we build a customizable dashboard later. Toggle-per-widget between number-widget and chart is a small but notable interaction detail.
- **Multi-store consolidation model.** "Main store + Shop Key paste" is a functional but clunky onboarding pattern; our `WorkspaceScope`-based model is structurally cleaner. Consider this an onboarding UX advantage if we surface it well.
- **Marketing-attribution paywall.** Bloom only fully unlocks attribution at $80/mo. If Nexstage exposes the resolver-driven per-source attribution lens at lower tiers, that's a defensible price/positioning gap.
- **Coming-soon "AI Insights" and "Profit Forecast"** suggest Bloom's near-term roadmap leans into the same trends our `claude-sonnet-4-6` model integrations could pre-empt.
