---
name: Profit Calc
url: https://profitcalc.io
tier: T1
positioning: One-click Shopify profit dashboard for dropshippers and small DTC merchants who don't want spreadsheets
target_market: SMB Shopify merchants (dropshipping, print-on-demand, multi-currency, multi-store); revenue band roughly $0–$5M/yr based on order tiers (500–unlimited orders/mo)
pricing: $29/mo Basic (500 orders) → $49 Advanced (1,500) → $99 Pro (3,000) → $199 Unlimited; 20% off annual; 15-day free trial
integrations: Shopify, Meta Ads, Google Ads, TikTok Ads, Pinterest Ads, Snapchat Ads, Bing Ads, Klaviyo, AliExpress, CJ Dropshipping, Printful, Printify, ShipHero, ShipBob, Shippo, Easyship
data_freshness: real-time (per Shopify App Store reviewers describing "data sync in real time"); COGS sync described as "daily" in feature copy
mobile_app: web-responsive (marketing copy: "Mobile-ready profit reports & dashboards for quick daily checks"); no dedicated iOS/Android app observed
researched_on: 2026-04-28
sources:
  - https://profitcalc.io
  - https://home.profitcalc.io/pricing
  - https://home.profitcalc.io/features
  - https://home.profitcalc.io/about
  - https://apps.shopify.com/profit-calc
  - https://apps.shopify.com/profit-calc/reviews
  - https://profit-calc.helpscoutdocs.com/
  - https://profit-calc.helpscoutdocs.com/article/117-profit-calc-tutorial
  - https://reputon.com/shopify/apps/dashboard/profit-calc
  - https://www.growave.io/apps/profit-calc-profit-calculator
  - https://trueprofit.io/blog/shopify-profit-tracker-apps
---

## Positioning

Profit Calc is the entry-level "one-click profit dashboard" for Shopify SMBs — explicitly aimed at dropshippers, print-on-demand sellers, and operators who'd otherwise be running Google Sheets to track net margin. The tagline on the homepage is "Know your real Shopify profit"; the App Store tagline is "Know your real profit instantly. Automatic profit calculations, ad spend sync and powerful analytics." It replaces the spreadsheet — the marketing repeatedly hammers "no spreadsheets" — and competes most directly with TrueProfit and BeProfit. It is positioned around simplicity and price (entry tier $29/mo), not depth: third-party comparison roundups call it "a good choice if you want simplicity and affordability, especially as a dropshipper using AliExpress" (TrueProfit blog, "Top 7 Best Shopify Profit Tracker Apps").

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Basic | $29/mo (or $278/yr, 20% off) | Up to 500 orders/mo, 15-day free trial, all integrations, all dashboards | Hits 500 orders/mo |
| Advanced | $49/mo (or $470/yr) | Up to 1,500 orders/mo | Hits 1,500 orders/mo |
| Pro | $99/mo (or $950/yr) | Up to 3,000 orders/mo | Hits 3,000 orders/mo |
| Unlimited | $199/mo (or $1,910/yr) | Unlimited orders | — |

Notes:
- Pricing is gated **purely on monthly order volume**, not feature differentiation — every tier appears to include the full feature set (ad syncing, P&L, multi-store, VAT, COD).
- 15-day free trial on every tier. "No credit card required" per home.profitcalc.io.
- Multi-store: each connected Shopify store requires a separate subscription per the marketing/help docs.
- Some third-party listings (Growave) reference a "$1 for the first 3 months on Entry plan" promo — not confirmed on the official pricing page on 2026-04-28.
- Older third-party listings (e.g., Reputon, growave.io snapshot) still show a single legacy "$29.95/mo" tier; the four-tier structure is the current model on the Shopify App Store and home.profitcalc.io.
- TrueProfit's own competitive page lists Profit Calc as ranging "$29 - $149/month," likely a stale data point — the current top tier is Unlimited at $199.

## Integrations

**Sources (data pulled in):**
- **Stores:** Shopify (only — no WooCommerce, no Amazon, no BigCommerce observed)
- **Ad platforms:** Meta Ads, Google Ads, TikTok Ads, Pinterest Ads, Snapchat Ads, Bing/Microsoft Ads
- **Email:** Klaviyo
- **Dropship suppliers:** AliExpress (with a Chrome extension for cost capture), CJ Dropshipping
- **Print-on-demand:** Printful, Printify
- **3PL / shipping:** ShipHero, ShipBob, Shippo, Easyship
- **Payments / fees (deducted automatically):** Shopify Payments, PayPal, Stripe, Klarna, iDeal, Mollie

**Destinations (data pushed out):** None observed. No Slack, no email digests, no API/webhook documentation surfaced in the public help center.

**Coverage gaps:**
- **No GA4 or GSC integration** — purely Shopify + ads + suppliers. Sessions, organic traffic, search query data are absent from the model.
- **No TikTok Shop, Amazon, eBay, Etsy** — single-channel (Shopify) only.
- **No WooCommerce** — direct relevance to Nexstage: this is half the SMB ecommerce market they explicitly ignore.
- **No CRM / post-purchase / SMS** beyond Klaviyo.
- **No "blended attribution" or pixel server-side tracking** marketed (unlike TripleWhale's Triple Pixel or Lifetimely's tracker).

## Product surfaces (their app's information architecture)

Inferred from the homepage screenshots (4 dashboard images), the App Store listing screenshots (6), and the Help Scout knowledge-base section structure (13 categories). Public pages do not show the in-app sidebar directly; UI inference is bounded.

- **Dashboard** — "real-time central dashboard to understand your store's financials." The default landing surface; aggregates orders, COGS, fees, taxes, ad spend into one net profit view.
- **Order Breakdown** — "detailed profit metrics and analytics broken down by each order." Per-order P&L drill-down.
- **Graph Breakdown** — "easily understand your store's performance in digestible graphs." Time-series / chart-centric view of profit and component metrics.
- **CLTV Analysis** — "Customer Lifetime Value calculated with just a click of the button." Cohort/customer view.
- **Product Analytics** — "discover each of the profitability of your products over any period of time." Per-SKU profitability.
- **Settings — COGS / Cost per item** — Shopify cost-per-item config, custom COGS rules by country, quantity, and date.
- **Settings — Shipping costs** — manual or imported shipping cost config.
- **Settings — Transaction fees** — configure payment gateways (Shopify Payments, PayPal, Stripe, Klarna, iDeal, Mollie).
- **Settings — Monthly expenses** — fixed/recurring opex line items added manually.
- **Settings — VAT** — EU VAT configuration (7 articles in the knowledge base).
- **Ad Accounts** — connection management for Meta, Google, TikTok, Snapchat, Bing, Pinterest (37 KB articles).
- **Integrations** — AliExpress (via Chrome extension), CJ Dropshipping, Printful, Printify, 3PLs.
- **Multi-store dashboard** — toggle between aggregate and per-store views (each store still billed separately).

The KB structure suggests no inbox / alerts / collaboration / experiments / audience-builder surfaces — this is a reporting tool, not a workflow tool.

## Data they expose

### Source: Shopify
- **Pulled:** orders, line items, products, COGS (Shopify "cost per item" field), refunds, chargebacks, transaction fees (calculated, not pulled — see complaints), customers (for CLTV), multi-currency FX.
- **Computed:** Net profit, gross margin, profit margin, AOV, refund rate, P&L (full statement), CLTV, per-product profitability.
- **Attribution windows:** Not surfaced in public pages; profit is calculated per-order, time-bucketed by order date.
- **Notable:** Custom COGS rules by country, quantity, and date — supports historical accuracy when costs change. FX uses both real-time and historical exchange rates.

### Source: Meta Ads
- **Pulled:** ad spend (campaign/ad-set/ad granularity unspecified in public copy), ROAS.
- **Computed:** Blended ROAS (spend ÷ Shopify revenue), profit-after-ad-spend.
- **Attribution windows:** Not stated. The product appears to use platform-reported ROAS and Shopify revenue without offering Meta's attribution-window selection.

### Source: Google Ads
- **Pulled:** spend, ROAS.
- **Computed:** same as Meta — incremental contribution to profit-after-ad-spend.

### Source: TikTok / Pinterest / Snapchat / Bing
- **Pulled:** spend (ROAS where the platform exposes it).
- **Computed:** rolled into total ad-spend bucket for blended profit.

### Source: Klaviyo
- **Pulled:** Listed as an integration on the App Store; the help center has no Klaviyo category surfaced separately, suggesting limited depth (likely revenue attribution flag rather than full email cohort analysis). Not confirmed.

### Source: AliExpress / CJ Dropshipping / Printful / Printify
- **Pulled:** product cost (per order), shipping cost. AliExpress requires a Chrome extension to capture supplier costs.
- **Computed:** True per-order COGS for dropshippers without manual entry.

### Source: ShipHero / ShipBob / Shippo / Easyship
- **Pulled:** actual shipping cost per order from 3PL.
- **Computed:** Replaces estimated shipping with billed shipping for accurate margin.

### NOT a source: GA4, GSC, server-side pixel
None of the standard web-analytics or organic-search sources are integrated. Profit Calc lives entirely on platform-reported and Shopify-reported numbers.

## Key UI patterns observed

UI details are inferred from six App Store screenshots, four homepage dashboard images, and the marketing/help-center copy. **No in-app live exploration was possible** — the app is paywalled behind a Shopify install and account, and there are no public demo videos with deep UI walkthroughs surfaced beyond the YouTube tutorial referenced from Help Scout (article 117), whose page content was not retrievable. Specific layouts (column counts, exact KPI ordering, card sizes) are described as marketing claims, not observed pixel-level facts.

### Main Profit Dashboard
- **Path/location:** Default landing page after install / sign-in.
- **Layout (prose):** Per the App Store screenshot caption, this is a "one-click true profit dashboard: orders, COGS, fees, ads, taxes." Marketing describes it as a single canvas combining net profit headline plus the cost stack feeding into it. Concrete card counts, sparklines, or grid structure are not visible in public-page renderings beyond the thumbnail.
- **UI elements (concrete):** UI details not available — only feature description seen on marketing page. The App Store screenshot is too small/compressed to read individual values; what is visible is a tile-based dashboard layout, white background, and a "P&L" style cost-waterfall sense.
- **Interactions:** "One-click" framing suggests no required configuration to land on this page beyond connecting Shopify + ad accounts + COGS. Filters and date-range pickers are implied by the existence of "over any period of time" copy on Product Analytics, but their exact shape is not observed.
- **Metrics shown:** Per marketing: net profit, orders, COGS, fees, ad spend, taxes. Per App Store screenshot 2: ROAS, AOV, refunds, chargebacks, VAT, fees, P&L.
- **Source/screenshot:** App Store screenshot 1 ("One-click true profit dashboard: orders, COGS, fees, ads, taxes") at https://apps.shopify.com/profit-calc.

### Detailed Reporting / Analytics view
- **Path/location:** Implied secondary screen / tab from Dashboard.
- **Layout (prose):** App Store screenshot 2 caption: "Deep profit analytics: ROAS, AOV, refunds, VAT, fees, P&L report." Suggests a multi-section page with KPI rows for each named metric. Not observable below screenshot resolution.
- **UI elements (concrete):** UI details not available — only feature description seen on marketing page.
- **Interactions:** Implied date range picker and store-switcher (multi-store dashboards are marketed).
- **Metrics shown:** ROAS, AOV, refunds, VAT, fees, P&L statement.
- **Source/screenshot:** https://apps.shopify.com/profit-calc (screenshot 2).

### Order Breakdown
- **Path/location:** Reports menu (per Help Scout "Reports" category, 13 articles).
- **Layout (prose):** Marketing copy: "detailed profit metrics and analytics broken down by each order." Suggests a row-per-order table with columns expanding from order ID through profit components.
- **UI elements (concrete):** UI details not available — only feature description seen on marketing page.
- **Interactions:** Likely sortable / filterable by date and likely drilldownable to per-order P&L.
- **Metrics shown per order:** revenue, COGS, shipping, transaction fees, allocated ad spend (or none — see weakness below), net profit. Per the WASABI Knives review, transaction fees are calculated by formula (not pulled from Shopify directly), which is the only specific UI-related complaint surfaced.
- **Source/screenshot:** No clean screenshot found.

### Graph Breakdown
- **Path/location:** Reports menu.
- **Layout (prose):** Marketing copy: "easily understand your store's performance in digestible graphs." Time-series visualization of profit and components.
- **UI elements (concrete):** UI details not available — only feature description seen on marketing page. No specific chart-type, axis, color, or hover behavior is observable from public sources.
- **Interactions:** Likely period switching.
- **Metrics shown:** Implied — net profit, revenue, ad spend over time.
- **Source/screenshot:** No clean screenshot found.

### Customer Lifetime Value (CLTV) Analysis
- **Path/location:** Reports menu.
- **Layout (prose):** Marketing copy: "your CLTV calculated with just a click of the button." A one-screen report — depth of cohort segmentation (by acquisition month, by channel, by product) is not surfaced in public copy.
- **UI elements (concrete):** UI details not available.
- **Interactions:** Single-click compute implied; no explicit cohort-grid or retention-curve language used (contrast: Lifetimely's product is built around cohort retention curves).
- **Metrics shown:** CLTV; possibly average order frequency, repeat rate (not confirmed).
- **Source/screenshot:** No public screenshot found.

### Product Analytics
- **Path/location:** Reports menu.
- **Layout (prose):** Marketing copy: "discover each of the profitability of your products over any period of time." Per-SKU table sortable on profit / margin / unit volume.
- **UI elements (concrete):** UI details not available.
- **Interactions:** Period selection ("over any period of time") and presumably product/SKU filtering.
- **Metrics shown:** Per-product profit, units sold, margin %.
- **Source/screenshot:** No public screenshot found.

### Ad-Spend Integrations panel
- **Path/location:** Settings → Ad Accounts (per Help Scout structure, 37 articles in this category).
- **Layout (prose):** Per App Store screenshot 3 caption: "Facebook, Google, Bing, TikTok, Snapchat, & Pinterest Syncing." Connection-card grid is the typical pattern — confirmed by integration-logo strip on homepage.
- **UI elements (concrete):** UI details not available beyond logo grid.
- **Interactions:** OAuth connect per platform; account selection.
- **Metrics shown:** Connection status; spend totals per platform on the Dashboard.
- **Source/screenshot:** https://apps.shopify.com/profit-calc (screenshot 3).

### COGS / Cost rules screen
- **Path/location:** Settings → Cost Of Goods Sold (49 articles in this Help Scout category — the largest by far, signalling COGS config is the most complex / support-heavy area).
- **Layout (prose):** App Store screenshot 5 caption: "Custom COGS rules by country & quantity, date for exact margins." Implies a rules-table UI where the user defines tiers (qty breakpoints), country overrides, and date-based versions of cost.
- **UI elements (concrete):** UI details not available beyond the screenshot caption.
- **Interactions:** Add/edit cost rules; bulk import from Shopify "cost per item" field; AliExpress Chrome extension auto-pulls cost into a hidden source field.
- **Metrics shown:** Configured cost per SKU per dimension.
- **Source/screenshot:** https://apps.shopify.com/profit-calc (screenshot 5).

### Multi-store / multi-currency consolidation
- **Path/location:** Top-level (store switcher in header, inferred).
- **Layout (prose):** Marketing copy: "View multiple stores together or separately within one report." "Multi-currency accuracy: real-time + historical FX rates for clean global profit."
- **UI elements (concrete):** UI details not available — store-switcher mechanism not pictured in public screenshots.
- **Interactions:** Toggle aggregate vs per-store view.
- **Metrics shown:** Same KPI set as single-store, summed across stores.
- **Source/screenshot:** No public screenshot found.

### Mobile view
- **Path/location:** Same web app, responsive layout.
- **Layout (prose):** App Store screenshot 6 caption: "Mobile-ready profit reports & dashboards for quick daily checks." No native iOS/Android app — this is responsive web only. Contrast TrueProfit, which markets a native mobile app and uses that as a competitive differentiator.
- **UI elements (concrete):** UI details not available beyond the existence of a mobile-responsive layout.
- **Source/screenshot:** App Store screenshot 6.

## What users love (verbatim quotes, attributed)

Reviews are skewed heavily positive — 5.0 rating with 53 of 54 reviews at 5 stars (Shopify App Store, retrieved 2026-04-28). The negativity-distribution is the lowest of the major profit-tracker apps.

- "Set-up is easy, love that the data sync in real time, and support is extremely responsive" — Good Bacteria, Shopify App Store, March 5, 2026
- "helps me keep track of my profit without ever having to be confused" — divaree, Shopify App Store, March 11, 2026
- "The setup is really quick and easy, and the metrics provided are extremely useful" — Insane Army, Shopify App Store, January 20, 2026
- "bang for the buck, Profit Calc comes up on top, by a long shot" — Navy Humor, Shopify App Store, October 16, 2025
- "simplicity can be effective…accomplish everything you need. Customer service response time is awesome" — Corporate Giggles, Shopify App Store, edited February 8, 2026
- "Saves so much time and gives me a true picture of my actual profit" — leighanndobbsbooks, Shopify App Store, May 11, 2025
- "brilliant…support team…answer your questions so quickly" — Case Monkey, Shopify App Store, October 18, 2025
- "ProfitCalc is amazing. It's so easy to use" (with founder Jamie's support called "very helpful and quick") — Relievery, Shopify App Store, July 12, 2025
- "Terrific app! Instantly coalesced a mess of channels" — Alex Fox Books, Shopify App Store, May 9, 2025
- "Great tool, use it everyday. Support is also spot on." — Dollar Dad Club, Shopify App Store, December 4, 2025
- "Been using the app for over 4 years now. Worth it!" — Massage Chair Heaven, Shopify App Store, December 19, 2024
- "Spectacularly easy way to see exactly what's happening in your store." — Cindy Nichols Store, Shopify App Store, May 14, 2025
- "Great app with also great customer service…tracks my profits really good" — ByFianna.de, Shopify App Store (cited via Reputon aggregator), August 2022
- "The CEO himself created a new feature upon my request" — My Charming Fox, Shopify App Store (cited via Reputon aggregator), October 2022
- "The #1 profit app, and the price is perfect" — Toy Hut, Shopify App Store (cited via Reputon aggregator), November 2022

Recurring praise themes: ease of setup, founder Jamie's hands-on support (named in multiple reviews), price-to-value ratio, real-time syncing, simplicity over feature bloat.

## What users hate (verbatim quotes, attributed)

Limited critical reviews available — the app has only 1 non-five-star review on the App Store (a 4-star). The most substantive criticism is older, surfaced via Reputon's aggregated review feed.

- "In future releases, it would be great to be able to customize the main dashboard more" — Insane Army (4-star portion), Shopify App Store, January 20, 2026
- "transaction fees are calculated by a formula although this can be pulled directly from Shopify" (resulting in incorrect fee calculations; reviewer compared unfavorably to BeProfit on this point) — WASABI Knives, Shopify App Store, May 2021 (cited via Reputon)

That is effectively the entire critical-review corpus available on public surfaces. There is **no Reddit thread, no Trustpilot listing, no negative G2/Capterra review** about Profit Calc surfaced in research — the app is small enough that it doesn't have a critic's record outside the App Store. Limited criticism available; likely a function of (a) niche-but-loyal SMB base, (b) review prompts skewing satisfied users, and (c) a small total review count (54).

Implicit weaknesses surfaced by competitor comparison content:
- "TrueProfit offers a mobile app for iOS and Android, while [other tools] cannot match this flexibility without a mobile app." — TrueProfit comparison content (third-party, attribution to TrueProfit's own marketing — bias caveat).
- TrueProfit's "Top 7" roundup positions Profit Calc as best for "simplicity and affordability, especially as a dropshipper using AliExpress" — implying it's chosen *down* from richer tools, not up to them.

## Unique strengths

- **Lowest entry price in the category.** $29/mo Basic is materially below TrueProfit and BeProfit's entry tiers for comparable order volumes.
- **Order-volume-only pricing tiers.** Every plan includes the full feature set — no feature paywalling, only a volume cap. Unusual for the category and a clear simplicity signal.
- **Largest dropship/POD supplier integration list.** AliExpress (with Chrome extension), CJ Dropshipping, Printful, Printify, ShipHero, ShipBob, Shippo, Easyship — broader than most rivals at this price point. Especially the AliExpress Chrome-extension capture is dropshipper-native.
- **Custom COGS rules by country + quantity + date.** This rule-tier flexibility is unusual; the help center's COGS category is the largest (49 articles), suggesting this is a real product surface, not just marketing.
- **Founder-led support.** Multiple reviewers name "Jamie" (founder) by name; "the CEO himself created a new feature upon my request" is quoted. Strong DTC trust signal at this scale.
- **Multi-currency with both real-time and historical FX rates.** Many lower-end profit trackers only support one or the other.
- **VAT and COD handling baked in.** Important for the EU dropship segment where COD orders are still common in markets like Romania, Italy, Poland.

## Unique weaknesses / common complaints

- **Single-platform: Shopify only.** No WooCommerce, no Amazon, no eBay, no BigCommerce. The entire WooCommerce-on-Wordpress SMB segment is excluded.
- **No GA4 / GSC / sessions data.** Profit lens only — no traffic, organic, or behavioral data. There is no model for "channel × profit" beyond what Meta/Google/TikTok report themselves.
- **Transaction fees calculated by formula, not pulled from Shopify.** Singular but specific complaint (WASABI Knives review) — when fees are formulaic, edge cases (Shop Pay Installments, currency conversion on Stripe, partial refunds) drift from actuals.
- **No native mobile app.** Web responsive only — explicit competitive disadvantage vs. TrueProfit.
- **Multi-store requires per-store subscription.** A merchant running 3 Shopify stores at Basic pays $87/mo, not $29.
- **Limited dashboard customization.** The single 4-star review from January 2026 calls this out specifically.
- **Small review base (54 reviews) for an app launched December 2019.** Five years and only ~1 review per month — either niche penetration or low solicitation.
- **No published attribution-window controls.** Profit Calc takes ad-platform numbers at face value; no "view-through window," no "click-only," no comparison vs pixel — which is a major differentiator for upmarket tools (TripleWhale).
- **No team/collaboration features observed.** No comment threads, no share-views, no role permissions visible in marketing or KB.
- **No export / API documented.** No webhook or API surfaced in the help center; data appears report-locked to the in-app dashboard.

## Notes for Nexstage

- **Pricing simplicity is their wedge.** $29 entry, four tiers, all-features-everywhere, only orders-per-month gating. If Nexstage wants to credibly outflank Profit Calc on price-to-value at the SMB end, the cost stack has to fit a $29–$49 baseline. Anything starting at $99+ won't show up in the same consideration set for the dropshipper persona.
- **They explicitly do not solve for GA4 or GSC.** The 6-source thesis (Real / Store / Facebook / Google / GSC / GA4) is **structurally outside their model.** A merchant comparing Nexstage to Profit Calc will see 4 sources they don't get from Profit Calc. This is a defensible wedge — but only if the value of GSC + GA4 is communicated clearly to a dropshipper, who may not currently care.
- **WooCommerce gap is large.** Profit Calc has zero Woo support. For Nexstage's Woo half, Profit Calc isn't even a competitor — TrueProfit/BeProfit aren't either at this price. There may be no SMB-priced profit-tracker for Woo, which is the actual market opening.
- **COGS rules by country/quantity/date is sophisticated.** Their largest help-center category (49 articles). Nexstage's COGS import design should at minimum match this rule-tier expressiveness — Profit Calc users coming over will compare directly.
- **Founder-led support is a moat we can't outhire at SMB scale.** Multiple reviewers name "Jamie" personally; this is a function of small team + small user base. Worth noting that as Profit Calc grows, this advantage is structurally fragile — and Nexstage's higher ceiling could outlast it.
- **Transaction-fee formula complaint is a recurring class of bug for this category.** Pulling from Shopify directly (per the WASABI Knives suggestion) is the user expectation. Nexstage should pull `Order.transactions[]` truth, not estimate.
- **No real "lens" or attribution-source toggle.** Profit Calc shows one number. The 6-source-badge UX is a different mental model entirely — closer to TripleWhale's pixel/GA/platform stack than to Profit Calc's flat "true profit" frame.
- **Multi-store via separate subs is a friction point.** Nexstage's workspace model (one workspace, multiple connected stores) is a meaningfully better experience and could be marketed against this directly.
- **Mobile-app gap.** TrueProfit positions iOS/Android as a wedge; Profit Calc concedes it. Whether this matters for Nexstage's persona (likely a desktop merchant) is an open product question.
- **Dashboard customization is the single repeat improvement ask** — even with n=1 in critical reviews, it's worth treating as signal because of how clean the rest of the review stream is.
- **The product surface count is small (~5 reports + settings).** This is a deliberately thin product. Nexstage's IA is much wider — that breadth is a "trade-up" sell at the upper tier of this segment, but at the entry tier it can feel like complexity vs. Profit Calc's "one click."
