---
name: Pricing page
slug: pricing-page
purpose: Can a SMB Shopify/Woo merchant self-price the product and start a trial in under a minute, without being routed to "Book a demo"?
nexstage_pages: marketing/pricing, billing
researched_on: 2026-04-28
competitors_covered: lifetimely, thoughtmetric, metorik, beprofit, adbeacon, triple-whale, polar-analytics, trueprofit, northbeam, hyros, conjura, putler, bloom-analytics, profit-calc, cometly, elevar, fairing, zigpoll
sources:
  - ../competitors/lifetimely.md
  - ../competitors/thoughtmetric.md
  - ../competitors/metorik.md
  - ../competitors/beprofit.md
  - ../competitors/adbeacon.md
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/trueprofit.md
  - ../competitors/northbeam.md
  - ../competitors/hyros.md
  - ../competitors/conjura.md
  - ../competitors/putler.md
  - ../competitors/bloom-analytics.md
  - ../competitors/profit-calc.md
  - ../competitors/cometly.md
  - ../competitors/elevar.md
  - ../competitors/fairing.md
  - ../competitors/zigpoll.md
  - https://useamp.com/pricing
  - https://thoughtmetric.io/pricing
  - https://metorik.com/pricing
  - https://beprofit.co/pricing/
  - https://www.adbeacon.com/adbeacon-pricing-ecommerce-brands/
  - https://hyros.com/pricing-ai-tracking
  - https://conjura.com/pricing
  - https://www.putler.com/pricing
  - https://www.bloomanalytics.io/pricing
  - https://www.trueprofit.io/pricing
---

## What is this feature

The pricing page is the highest-stakes single screen in any analytics SaaS purchase flow for SMB Shopify/Woo merchants. It answers a binary qualification question — "can I afford this, and can I start today?" — and any friction (gated tiers, "contact sales", revenue-banded calculator that hides the number, conflicting prices on the marketing site vs the Shopify App Store) drives the merchant to the next-cheapest competitor on the list. For SMBs spending <$10K/mo on tooling out-of-pocket, the price-evaluation window is 30 seconds; pages that require a demo to surface a number disqualify themselves at this stage.

The structural distinction is between **self-serve, fully-public, all-features-on-every-tier** pricing (Lifetimely, ThoughtMetric, Metorik, Profit Calc, TrueProfit, Putler, Elevar) and **sales-led, GMV/ad-spend-banded, partially-hidden** pricing (Northbeam, Hyros, Polar Analytics, Cometly, top tiers of Triple Whale and Conjura). The two models reach different ICPs: SMBs ($30K–$3M/yr GMV) reject sales-led; brands above $5M GMV are conditioned to expect it. The pricing page itself communicates this segmentation before the merchant reads a single feature bullet.

## Data inputs (what's required to compute or display)

For each input, the source + the specific field/event:

- **Source: Shopify App Store billing API** — `app_subscription.line_items.plan_handle`, `app_subscription.trial_days`, currency. Required when the merchant installs via Shopify (Shopify takes a 15% rev-share that pushes app-store-listed prices ~15-30% above direct-billed prices — see Zigpoll discrepancy).
- **Source: Direct Stripe / native billing** — `subscription.plan.amount`, `subscription.plan.interval` (monthly vs annual), `subscription.status`, `subscription.trial_end`.
- **Source: User-input — workspace order volume** — `workspace.last_3mo_avg_orders` (Metorik bands), `workspace.last_12mo_gmv` (Triple Whale, Polar bands), `workspace.last_30d_tracked_revenue` (AdBeacon, Hyros bands), `workspace.last_30d_pageviews` (ThoughtMetric band), `workspace.last_30d_ad_spend` (Cometly band).
- **Source: User-input — interactive calculator** — slider position (GMV / orders / pageviews / ad spend) → derived monthly price; toggles for monthly vs annual billing; toggles for currency.
- **Source: Computed** — `effective_per_brand_price = total_price / brand_count` (AdBeacon agency framing); `annual_savings = (monthly × 12) − annual_price` (Lifetimely 14% / ThoughtMetric 18% / Annual Anchor 20% / Metorik standard).
- **Source: Computed — volume-cap ladder** — `next_tier_trigger = ceil(current_volume / tier_cap) × tier_cap` (drives "you'll hit the next tier in N orders" copy).
- **Source: Marketing-CMS feature matrix** — per-tier boolean grid of features ("Marketing Attribution: Basic ✗ / Advanced ✗ / Ultimate ✓ / Plus ✓") for paywalled-feature competitors; flat "every feature included" for ThoughtMetric/Metorik/Lifetimely/Profit Calc/Elevar.

## Data outputs (what's typically displayed)

For each output, the metric, formula, units, and typical comparisons:

- **KPI: Headline price per tier** — string, USD/mo (sometimes EUR or local), monthly with annual-equivalent in small print or toggle.
- **Dimension: Scale axis** — one of {orders/mo, GMV/last-12mo, tracked-revenue/mo, pageviews/mo, ad-spend/mo, brand-count, store-count, response-count}. Determines slider/selector type.
- **Dimension: Billing frequency** — `monthly | annual` toggle, with discount % shown.
- **Breakdown: Feature × tier matrix** — checkmark grid (rows = features, columns = tiers); core viz for paywalled-feature competitors.
- **Slice: Per-add-on / overage / surcharge** — explicit columns for $/extra-order overage (TrueProfit, Elevar), tier overage caps, "no surprise bills" copy.
- **CTA: Trial → Install → Connect store** — primary button label and trial length (14d most common; 30d Metorik/AdBeacon; 15d Profit Calc/Elevar).
- **Microcopy: Money-back / cancel / no-CC** — "No credit card required" (Metorik, Fairing free), "30-day money-back" (AdBeacon agency), "No contracts" (Zigpoll, Elevar).
- **Comparison column: vs Triple Whale / vs Northbeam** — secondary table or dedicated `/vs-` landing page (AdBeacon, ThoughtMetric, Polar, Conjura all run this play).

## How competitors implement this

### Lifetimely (by AMP) ([profile](../competitors/lifetimely.md))
- **Surface:** `useamp.com/pricing` — public marketing site.
- **Visualization:** Horizontal 6-column tier card grid (Free / M / L / XL / XXL / Unlimited) plus a separate Amazon Add-On card.
- **Layout (prose):** Top: tagline + 14-day trial pill. Main canvas: six tier cards in a row, each with price, order-cap headline, support-level badge (Standard / Silver / Gold / Platinum), feature bullet list. Below cards: Amazon Add-On callout at +$75/mo. The XL tier ($499/mo) is highlighted as "Most Popular".
- **Specific UI:** Each tier card shows a monthly orders ceiling (50, 3K, 7K, 15K, 25K, Unlimited) as the dominant scale signal, with **"all features included" repeated on every tier** — feature matrix is flat. Support level (Standard → Platinum 99.9% SLA) is the only delta besides volume.
- **Filters:** None — no slider, no calculator. Static grid.
- **Data shown:** $/mo per tier, order cap, support tier, Amazon add-on, 14-day-trial CTA.
- **Interactions:** "Start free trial" CTA per card; no monthly/annual toggle (no public annual pricing).
- **Why it works (from reviews/observations):** Tier ladder is read in 5 seconds; the named support levels (Silver/Gold/Platinum) anchor higher tiers without requiring feature gating. Recurring complaint: "$149/month for the first paid tier… stores doing under $30K/month potentially struggling to justify it" (ATTN Agency review) — the floor is the friction, not the layout.
- **Source:** [profile](../competitors/lifetimely.md), https://useamp.com/pricing.

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** `thoughtmetric.io/pricing` — public.
- **Visualization:** Interactive pageview slider feeding a single live-updating price card.
- **Layout (prose):** Top: pageview slider with discrete stops (50K / 100K / 200K / …). Main canvas: single tier card whose `$X/mo` headline mutates as the slider moves; feature list below stays constant. Below: explicit "Every feature is included in every plan. No add-ons, or surprise charges" reassurance block.
- **Specific UI:** Slider (drag handle, dollar amount adjusts in real time). The headline pricing string is "$99–$1,000/mo (based on pageview tiers)". Annual toggle yields **18% discount**. Soft-cap policy is shown as copy: "If you exceed your plan's pageview limit two months in a row, we'll notify you and work with you to upgrade."
- **Filters:** Pageview slider (only filter); monthly ↔ annual toggle.
- **Data shown:** Live $/mo, full feature list (constant), trial CTA, soft-cap reassurance.
- **Interactions:** Drag slider → price updates; click "Start free trial" → 14-day trial.
- **Why it works:** Reviews praise it directly — "Incredible cost; highly accurate data addresses attribution issues" (Jake D., Capterra Jul 2022). The slider + flat-feature combination removes both the "what tier do I need?" and "what features am I missing?" questions in one move.
- **Source:** [profile](../competitors/thoughtmetric.md), https://thoughtmetric.io/pricing.

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** `metorik.com/pricing` — public; Shopify-billed for Shopify stores, direct CC for Woo.
- **Visualization:** Order-volume calculator with tier ladder (Level 1 / 2 / 3 / 4 / higher) — slider-style price reveal.
- **Layout (prose):** Top: 30-day no-CC-trial banner. Main canvas: tier ladder (Starter $25 → Level 4 $250) with order-volume bands (≤100, 101–500, 501–2K, 2K–5K) and feature parity copy. Calculator extends to 150K+ orders/mo. Below: explicit FAQ ("How does Metorik calculate my monthly price?") with rolling-3-month-average rule.
- **Specific UI:** Auto-downgrade rule called out in marketing: **"no overage. No bill-shock"**. Multi-store inclusion is shown per-tier (1 / 5 / 10 / 20 stores). Email credits per tier (10K / 25K / 40K / 75K).
- **Filters:** Order-volume slider/calculator; currency follows store.
- **Data shown:** $/mo, order band, store count cap, email credits, all-features-included.
- **Interactions:** Slider; click "Start trial" — 30-day no credit card.
- **Why it works:** Lowest entry price in category ($25) and zero credit-card friction. "Worked great on woo, works great on shopify!" — ReFerm, Shopify App Store, Mar 2026. The pricing FAQ self-acknowledges friction: "I understand if this is frustrating for some store owners, but it's a model that we've found is fair for most customers" — Metorik themselves, help.metorik.com/article/143.
- **Source:** [profile](../competitors/metorik.md), https://metorik.com/pricing.

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** `beprofit.co/pricing/` + Shopify App Store listing.
- **Visualization:** 4-column tier card grid (Basic / Pro / Ultimate / Plus) with a feature-paywall matrix.
- **Layout (prose):** Top: monthly/annual toggle (annual = 20% off). Main canvas: four tier cards, each capped at fixed orders/mo (450 / 900 / 1,700 / unlimited). Below: feature-paywall callouts — UTM Attribution, LTV Cohort, P&L Report, Pricing Simulators are explicitly tier-locked.
- **Specific UI:** **Multi-shop is gated to the $249/mo Plus tier only** — every lower tier is single-shop. P&L gated to Pro+, UTM Attribution + LTV cohorts gated to Ultimate+. POAS (Profit on Ad Spend) listed as headline metric on every tier.
- **Filters:** Monthly ↔ annual toggle.
- **Data shown:** $/mo, $/yr, order cap, shop count, feature checkmarks per tier.
- **Interactions:** Toggle billing frequency; "Start 14-day trial" per card.
- **Why it works (and where it doesn't):** Card layout is conventional; reviews highlight cancellation friction as the after-purchase problem, not the pricing-page UX itself: "Worst experience ever, been charging me for months despite contacting them about cancellation" — Clear Cosmetics, Shopify App Store, Mar 2026. The structural complaint is the multi-store cliff: "BeProfit's multi-store overview requires their $149 Ultimate plan" (per TrueProfit comparison page) — itself an out-of-date reading; current Plus is $249.
- **Source:** [profile](../competitors/beprofit.md), https://beprofit.co/pricing/.

### AdBeacon ([profile](../competitors/adbeacon.md))
- **Surface:** Two distinct pricing pages — `/adbeacon-pricing-ecommerce-brands/` and `/adbeacon-pricing-agencies/`.
- **Visualization:** Tracked-revenue band table (7 brand tiers from <$50K to $500K+) + separate agency card grid (3 tiers).
- **Layout (prose):** Brands page: top hero with three columns — **Monthly | Annual Anchor | Annual Scaling** — and the central marketing claim "fixed pricing regardless of revenue tracked" called out for Annual Scaling. Tier rows enumerate each tracked-revenue band with three corresponding prices. Below: feature-parity statement ("Integrations, Reporting Suite, Audience Builder, Dashboard Builder, Creative Dashboard, Education Suite" included at every tier). Annual-Anchor adds onboarding/strategy/Slack-channel access. Agencies page: 3-card grid with effective $150/brand framing and unlimited seats.
- **Specific UI:** Annual Scaling tier is marketed explicitly as "the answer to scale-with-revenue pricing" — direct shot at Triple Whale. Agency framing computes "$150/month per brand" against Triple Whale's "$1,100/month base + add-ons".
- **Filters:** None on brand page (table is static); agency page splits monthly vs annual.
- **Data shown:** $/mo per band × billing model; per-brand effective rate; feature-parity copy; testimonials about flat-rate.
- **Interactions:** "Get Started" CTA per row; 30-day free trial.
- **Why it works:** Vendor-published quote "We have all our accounts at a fixed rate. Forever" is the entire thesis — the page is built around this single claim. Friction noted in reviews: "No free trial or plan" listed as con on smbguide.com (this contradicts AdBeacon's own page advertising 30-day trial — **a discrepancy that itself appears in reviews**, suggesting the trial isn't surfaced clearly enough on the brand pricing page).
- **Source:** [profile](../competitors/adbeacon.md), https://www.adbeacon.com/adbeacon-pricing-ecommerce-brands/.

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** `triplewhale.com/pricing` — public, GMV-banded.
- **Visualization:** GMV-band selector + 5-tier feature matrix (Founders Dash / Free / Starter / Advanced / Pro).
- **Layout (prose):** Top: GMV band selector (Up to $2M / $2–3M / $3–5M / $5–7M / $7M+). Main canvas: five tier cards whose prices change with the GMV band. Feature matrix below distinguishes which features are tier-gated (multi-touch attribution, Creative Analytics, Cohorts, Compass MMM).
- **Specific UI:** Two **free tiers** ("Founders Dash" + a separate "Free basic plan") with overlapping but non-identical feature lists. Add-ons (Conversion Analytics, Retention, Compass, Moby AI Pro credits) are billed separately and not surfaced inline. Annual = "Get two months free".
- **Filters:** GMV band selector.
- **Data shown:** $/mo per tier × GMV band; feature matrix; "unlimited users" repeated; add-on disclaimers.
- **Interactions:** Click GMV band → tier cards reprice; click "Get Started" → onboarding flow.
- **Why it works (and the recurring complaint):** The headline tier price differs by source — Conjura cites $1,129/mo for $5–7M GMV; Tekpon's older listing shows "$129 / $199 / $279" tiers that don't match the current page. The tier names and pricing have changed multiple times — this is the basis for "pricing creep" complaints documented across reviews. The multi-tier-rename pattern itself is an anti-pattern (see below).
- **Source:** [profile](../competitors/triple-whale.md).

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** `polaranalytics.ai/pricing` + a separate `pricing.polaranalytics.ai` calculator subdomain.
- **Visualization:** Sales-led — feature bullets only on `/pricing`; calculator subdomain returns no public pricing data.
- **Layout (prose):** Top: plan name cards (Audiences, Polar MCP, AI-Analytics, Polar Suite, Core, Custom). Each card has a feature bullet list and a "Contact us / Book a demo" CTA. **No prices displayed on the main pricing page.** Calculator subdomain exists but is not crawlable.
- **Specific UI:** Shopify App Store listing **does** surface four named tiers with starting prices ($470 / $648 / $810 / $1,020) — the App Store is the only place the numbers are visible to merchants without a sales conversation. Third-party reverse-engineered GMV pricing (Conjura, Apr 2025) shows ≤$5M = $720/mo; $5–7M = $1,020/mo; $20–25M = $2,770/mo; $75–100M = $7,970/mo.
- **Filters:** None public; the calculator-subdomain selector exists but is gated.
- **Data shown:** Plan names + feature bullets; no prices unless reached via Shopify App Store.
- **Interactions:** "Book a demo" CTA on every card.
- **Why it works (or doesn't):** **Sticker shock at scale** — Conjura comparison cites Polar at $12,240/yr vs Conjura $7,990/yr at $6M GMV. Polar's own Triple Whale comparison page admits Polar starts higher (~$400/mo) but argues it ends up cheaper at scale. The mismatch between marketing-site (hidden) and Shopify-listing (visible) prices is itself the friction.
- **Source:** [profile](../competitors/polar-analytics.md).

### TrueProfit ([profile](../competitors/trueprofit.md))
- **Surface:** Shopify App Store listing — Shopify-billed only.
- **Visualization:** 4-tier card grid (Basic $35 / Advanced $60 / Ultimate $100 / Enterprise $200).
- **Layout (prose):** Each tier card lists order cap (300 / 600 / 1,500 / 3,500) and **per-extra-order overage** ($0.30 / $0.20 / $0.10 / $0.07) with explicit overage cap ($300 / $500 / $700 / $1,000 surcharge per period). Feature matrix highlights the paywall: **Marketing Attribution gated entirely to the $200/mo Enterprise tier**.
- **Specific UI:** Per-order overage is the most explicit in the category — published $/order with surcharge ceiling. Feature lock copy: "Up to 5 COGS Zones" / "Up to 10 COGS Zones" / "Unlimited COGS Zones" — paywalls a structural admin primitive.
- **Filters:** None (static).
- **Data shown:** $/mo, order cap, $/extra-order overage, $/period surcharge cap, feature checkmarks.
- **Interactions:** Click tier → Shopify subscribe flow.
- **Why it works (and where it bites):** "Reviewers frequently dispute the per-order overage as the real upgrade trigger" (per TrueProfit profile). Original brief cited a $25–$499 range; live page on 2026-04-28 shows $35–$200 — pricing does drift, but unlike competitors the overage axis is fully transparent.
- **Source:** [profile](../competitors/trueprofit.md).

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** `northbeam.com/pricing` — sales-led above Starter.
- **Visualization:** Three tier cards (Starter / Professional / Enterprise) — only Starter shows a number.
- **Layout (prose):** Starter card: $1,500/mo (older reviews cite $999). Professional + Enterprise cards: feature lists + "Contact sales". Volume axis is **pageviews tracked**.
- **Specific UI:** **No free trial** — pricing-page friction reinforced by Capterra reviews complaining about "3 months upfront" payment policy and recent stripping of support for sub-$1K/mo accounts.
- **Filters:** None.
- **Data shown:** Starter $/mo only; tier feature bullets.
- **Interactions:** "Contact sales" / "Book demo" CTAs.
- **Why it works (or doesn't):** Above-SMB by design. The page filters out anyone below ~$1.5M annual ad spend.
- **Source:** [profile](../competitors/northbeam.md).

### Hyros ([profile](../competitors/hyros.md))
- **Surface:** `hyros.com/pricing-ai-tracking` — sales-led, revenue-banded.
- **Visualization:** Single tier shown publicly (Up to $20K monthly revenue = $230/mo annual-billed); rest of ladder is "demo required".
- **Layout (prose):** Public table shows only the entry tier; subsequent rows are gated. Third-party sources (SegMetrics, LGG Media) reverse-engineer the full rate card — and the two third-party rate cards **disagree on every band**, suggesting Hyros negotiates and re-prices regularly.
- **Specific UI:** Annual-billed monthly figures only; no monthly-only price published.
- **Filters:** None — must "Book a demo" to see anything past $20K tracked revenue.
- **Data shown:** Single $/mo number; all other prices behind demo gate.
- **Why it works (or doesn't):** Opaque-by-design; aimed at $1M+/mo revenue brands with conditioned demo expectations. Disqualifies SMBs at the page level.
- **Source:** [profile](../competitors/hyros.md).

### Conjura ([profile](../competitors/conjura.md))
- **Surface:** Three different pricing surfaces — `conjura.com/pricing`, Shopify App Store listing, and "vs Triple Whale" comparison page.
- **Visualization:** Two tier ladders that **don't reconcile**.
- **Layout (prose):** Website tiers: Essentials $19.99 / Growth $59.99 / Scale $129.99 (monthly). Shopify App Store: Free / Grow $299 / Grow+ $499. Comparison-page references $799–$899 starting at $10M GMV. GMV bracket selector ($0–2M / $2–5M / $5–10M / $10–20M / $20M+) on the website but no per-bracket pricing displayed.
- **Specific UI:** Owly AI add-on starts at $199/mo with usage units ("approximately 250 quick questions or 50 comprehensive reports"). **The website tiers, App Store tiers, and comparison-page tiers do not reconcile** — strongly suggests the public website prices are a low-end bracket only and real enterprise pricing is gated.
- **Filters:** GMV bracket selector (visible but no price reveal).
- **Why it works (or doesn't):** **Price-list discrepancy is itself a documented anti-pattern** — merchants comparing the Conjura website ($19.99) vs Shopify App Store ($299) reasonably distrust both. Per the Conjura profile: "Pricing is opaque in practice."
- **Source:** [profile](../competitors/conjura.md).

### Putler ([profile](../competitors/putler.md))
- **Surface:** `putler.com/pricing` — fully public.
- **Visualization:** Revenue-band ladder (12 tiers from <$10K to $5M+).
- **Layout (prose):** Tier rows from "Up to $10K = $20/mo" up to "$3M–$5M = $2,250/mo"; $5M+ contact sales. **All features included at every tier** — only revenue-volume gates change.
- **Specific UI:** Auto-bills up/down with prorated credits based on observed monthly revenue. "Unlimited team" / "unlimited accounts" called out at every tier.
- **Filters:** None — full ladder rendered.
- **Why it works (or doesn't):** Reviewers complain it's "dense and opaque on edge cases" — "pricing lacks transparency" (Patrick C., Capterra Oct 2023). 12-row ladder reads as comprehensive but slows the 30-second decision.
- **Source:** [profile](../competitors/putler.md).

### Bloom Analytics ([profile](../competitors/bloom-analytics.md))
- **Surface:** Shopify App Store + `bloomanalytics.io/pricing`.
- **Visualization:** 4-tier card grid (Free / Sprout $20 / Grow $40 / Flourish $80).
- **Layout (prose):** Tier ladder gates Klaviyo email-revenue, Customer LTV, Marketing Attribution, and Country ROAS to higher tiers. Marketing copy: "no penalties for exceeding limits — you don't get locked out or hit with surprise charges."
- **Specific UI:** Roadmap features visible on the pricing page itself — "Marketing Intelligence (adding soon)," "Profit Forecast (adding soon)," "AI Insights (adding soon)" — communicates active development inline.
- **Filters:** None.
- **Why it works:** Lowest non-Metorik entry ($20). Public, predictable, paywalls are explicit.
- **Source:** [profile](../competitors/bloom-analytics.md).

### Profit Calc ([profile](../competitors/profit-calc.md))
- **Surface:** Shopify App Store listing.
- **Visualization:** 4-tier card grid (Basic $29 / Advanced $49 / Pro $99 / Unlimited $199).
- **Layout (prose):** Order-volume gated (500 / 1,500 / 3,000 / unlimited). **Every tier includes the full feature set** (ad syncing, P&L, multi-store, VAT, COD).
- **Specific UI:** 20% annual discount; 15-day free trial.
- **Filters:** Annual ↔ monthly.
- **Why it works:** Pure-volume-axis pricing with flat features. "Annual-billed monthly" prices are shown alongside monthly to anchor the discount.
- **Source:** [profile](../competitors/profit-calc.md).

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** `cometly.com/pricing` — sales-led.
- **Visualization:** Feature-bullet cards with no dollar amounts.
- **Layout (prose):** Tier names + feature lists + "Get Started / Book your demo" CTAs. softwaresuggest.com surfaces explicit prices ($500 / $1,000 / $5,000+) but **the cometly.com page itself shows no numbers**.
- **Specific UI:** Older 3rd-party sources (aazarshad.com, gethookd.ai) cite legacy "Pro $199/mo" tier that no longer exists — another instance of the price-creep + invisible-price-list pattern.
- **Why it works (or doesn't):** Disqualifies SMBs immediately. Any merchant evaluating in <30 seconds bounces.
- **Source:** [profile](../competitors/cometly.md).

### Elevar ([profile](../competitors/elevar.md))
- **Surface:** `getelevar.com/pricing-and-plans/` — public.
- **Visualization:** 4-tier card grid (Starter $0 / Essentials $200 / Growth $450 / Business $950) + Multi-Store contact.
- **Layout (prose):** Order-volume gated (100 / 1K / 10K / 50K). **All tiers feature the same core functionality** — only differentiator is order volume + support SLA + custom integration access. Per-order overage shown explicitly: $0.40 / $0.15 / $0.04 / $0.03.
- **Specific UI:** **Free Starter at 100 orders/mo with full feature parity** — most generous flat-feature free tier in the data-delivery category. Add-ons listed inline (Expert Installation $1,000+, GA4 Tune-up $1,000+, Ongoing Tag Support from $500/mo).
- **Filters:** None.
- **Why it works:** Per Nexstage notes on Elevar profile: "All-tier feature parity is unusual" — drives evaluation friction down to the volume-band decision only.
- **Source:** [profile](../competitors/elevar.md).

### Fairing ([profile](../competitors/fairing.md))
- **Surface:** `fairing.co/pricing` — public.
- **Visualization:** 5-tier card grid (Free / $15 / $49 / $149 / Enterprise) + Data Sync add-on.
- **Layout (prose):** Order-volume gated (100 / 200 / 500 / 5K / 30K+). Data Sync add-on $299/mo (BigQuery + API). All tiers include all features; only volume + premium-support level changes.
- **Specific UI:** "No surprises" + "No Credit Card Required" copy on the free tier.
- **Why it works (and the friction):** Public + flat-feature; **but multiple reviewers report aggressive year-over-year increases for legacy customers** — the price-page snapshot is honest, the renewal experience isn't.
- **Source:** [profile](../competitors/fairing.md).

### Zigpoll ([profile](../competitors/zigpoll.md))
- **Surface:** Two surfaces — `zigpoll.com/pricing` and Shopify App Store listing.
- **Visualization:** 4-tier card grid on each surface (Lite / Standard / Advanced / Ultimate).
- **Layout (prose):** Direct site: $0 / $29 / $97 / $194. Shopify App Store: Free / $39 / $129 / $259. **Shopify-listed paid tiers are 30–35% higher than direct-site prices** — explicit price-list discrepancy across surfaces. Likely Shopify's 15% rev-share padding plus rounding. Annual = 25% off.
- **Specific UI:** Branching logic and presentation logic gated to paid plans.
- **Why it works (or doesn't):** Direct-vs-App-Store price gap is itself a documented merchant-trust hazard — anyone reading both surfaces sees the same product priced two different ways.
- **Source:** [profile](../competitors/zigpoll.md).

## Visualization patterns observed (cross-cut)

Synthesizing the per-competitor sections by viz type:

- **Static tier-card grid (3–6 cards):** 9 competitors (Lifetimely, BeProfit, AdBeacon-brands, TrueProfit, Bloom, Profit Calc, Elevar, Fairing, Zigpoll). The dominant pattern. Reads in 5–10 seconds.
- **Slider / interactive calculator:** 3 competitors (ThoughtMetric pageview slider, Metorik orders calculator, Triple Whale GMV-band selector that reprices the cards). Highest-rated by reviewers when prices stay visible.
- **Long revenue-band ladder (8+ rows):** 2 competitors (Putler 12 rows, Hyros 8 rows). Comprehensive but slows the decision; Putler reviewers call it "dense."
- **Sales-led / mostly-hidden:** 4 competitors (Northbeam, Polar Analytics on `/pricing`, Cometly, Hyros above $20K rev). One number visible if any.
- **Multi-surface with discrepancies (anti-pattern):** 3 competitors (Conjura website vs App Store vs comparison-page; Polar website vs App Store; Zigpoll direct vs App Store).

Visual conventions that recur:
- **Annual/monthly toggle:** ~70% of public-pricing competitors. Discount range 14% (Lifetimely) → 18% (ThoughtMetric) → 20% (BeProfit, AdBeacon Anchor, Profit Calc) → 25% (Zigpoll).
- **"Most Popular" highlight on a middle tier:** Lifetimely XL, BeProfit Pro, AdBeacon Annual Anchor.
- **Overage policy visible on the card:** TrueProfit (per-order $/overage published), Elevar (per-order published), ThoughtMetric (soft-cap "two months in a row" copy), Metorik ("no overage. No bill-shock"). Absent on Lifetimely (overage exists but hidden until invoice — a documented friction).
- **Trial CTA on every card:** Universal among self-serve. 14 days dominant; 30 days at Metorik (no CC) and AdBeacon; 15 days at Profit Calc and Elevar; 90-day money-back at AdBeacon agency.
- **Color use:** No clear convention — most competitors use brand-neutral cards. Triple Whale Founders Dash differentiated by a "Free Forever" pill.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Flat-feature clarity (every feature on every tier)**
- "Every feature is included in every plan. No add-ons, or surprise charges." — ThoughtMetric pricing page, [profile](../competitors/thoughtmetric.md)
- "Incredible cost; highly accurate data addresses attribution issues" — Jake D., CMO (Consumer Goods), Capterra, July 2022, [thoughtmetric profile](../competitors/thoughtmetric.md)
- "Well-thought-out attribution tool; lots of data inexpensively" — Jen W., Director of Marketing, Capterra, December 2022, [thoughtmetric profile](../competitors/thoughtmetric.md)
- "Simple UI, extremely functional and cost effective." — verified G2 reviewer, March 28, 2025, [metorik profile](../competitors/metorik.md)

**Theme: Flat-rate / no-revenue-tax framing**
- "We moved our whole agency over to AdBeacon last year. One of the big reasons, when we scaled using other attribution partners we incurred higher costs. Not with AdBeacon. We have all our accounts at a fixed rate. Forever." — Agency testimonial, AdBeacon homepage, [profile](../competitors/adbeacon.md)

**Theme: No bill shock, no credit card**
- "no overage. No bill-shock" — Metorik marketing copy, [profile](../competitors/metorik.md)
- "If you exceed your plan's pageview limit two months in a row, we'll notify you and work with you to upgrade to the next tier based on your usage." — ThoughtMetric pricing page, [profile](../competitors/thoughtmetric.md)
- "no penalties for exceeding limits — you don't get locked out or hit with surprise charges." — Bloom Analytics pricing page, [profile](../competitors/bloom-analytics.md)
- "No surprises" + "No Credit Card Required" — Fairing free tier, [profile](../competitors/fairing.md)

**Theme: Price visible without a demo**
- "easy setup, good service, and accurate data. what else do you need!?" — Woolly Clothing Co, Shopify App Store, August 2025, [thoughtmetric profile](../competitors/thoughtmetric.md)
- "Worked great on woo, works great on shopify!" — ReFerm, Shopify App Store, March 2026, [metorik profile](../competitors/metorik.md)

## What users hate about this feature

**Theme: Sticker shock at scale**
- "At $149/month for the first paid tier, Lifetimely is a real line item, with stores doing under $30K/month potentially struggling to justify it." — ATTN Agency review, 2026, [lifetimely profile](../competitors/lifetimely.md)
- "Overpriced for what it is. Very basic and slow." — TheCustomGoodsCo, Shopify App Store review, May 16, 2022 (cited via Reputon; user migrated to Triple Whale), [lifetimely profile](../competitors/lifetimely.md)
- "Pretty poor app overall. Expensive and slow. Buggy." — Plushy, Shopify App Store review, March 29, 2022, [lifetimely profile](../competitors/lifetimely.md)
- "The pricing plan is based on the number of orders, therefore it can be pricey in the long run especially for bigger stores." — putler.com/metorik-review/, summarizing community sentiment, [metorik profile](../competitors/metorik.md)
- Polar Analytics: "$12,240/yr Polar vs $7,990/yr Conjura at ~$6M GMV" — Conjura comparison; Polar is documented as **sticker shock** territory above $5M GMV, [polar-analytics profile](../competitors/polar-analytics.md)

**Theme: Opaque pricing / forced demo**
- "pricing lacks transparency" — Patrick C., Capterra Oct 2023, [putler profile](../competitors/putler.md)
- "Pricing is opaque in practice — the website tiers ($19.99–$129.99) and Shopify App Store tiers ($299–$499) and comparison-page tiers ($799–$899) do not reconcile" — [conjura profile](../competitors/conjura.md)
- "Pricing is intentionally opaque — only the entry tier is shown publicly; everything else requires a sales call" — [hyros profile](../competitors/hyros.md)
- "Reviewers note Northbeam typically requires '3 months upfront' payment and that recently 'all support [was stripped] from the platform for clients who pay up to $1k/month, including onboarding'" — Joey B., Capterra, Nov 2023, [northbeam profile](../competitors/northbeam.md)

**Theme: Unpredictable invoices / overage surprises**
- "Multiple third-party reviews flag overage charges if you exceed your tier's order limit" — [lifetimely profile](../competitors/lifetimely.md)
- "Worst experience ever, been charging me for months despite contacting them about cancellation." — Clear Cosmetics, Shopify App Store, March 4, 2026, [beprofit profile](../competitors/beprofit.md)
- "Company has been paying $720 per year since — never used. They will not respond." — Adrienne Landau, Shopify App Store, April 22, 2026, [beprofit profile](../competitors/beprofit.md)
- "Reviewers frequently dispute the per-order overage as the real upgrade trigger" — [trueprofit profile](../competitors/trueprofit.md)

**Theme: Pricing creep / re-shaping tiers**
- "3rd-party Tekpon listing references three older tier names ('Growth $129 / Pro $199 / Enterprise $279') that don't match the current `triplewhale.com/pricing` page — Triple Whale has clearly re-priced and re-shaped tiers multiple times; this is the basis for 'pricing creep' complaints" — [triple-whale profile](../competitors/triple-whale.md)
- "multiple reviewers report aggressive year-over-year increases for legacy customers" — [fairing profile](../competitors/fairing.md)
- "Older 3rd-party reviews (aazarshad.com, gethookd.ai, sourceforge.net) cite a Pro plan starting at $199/mo … This appears to be a legacy tier (pre-rebrand) that may no longer be quoted to new customers" — [cometly profile](../competitors/cometly.md)

**Theme: Cross-surface price discrepancies (Shopify vs direct vs comparison page)**
- "The Shopify-listed paid tiers are 30-35% higher than the direct-site prices" — [zigpoll profile](../competitors/zigpoll.md)
- Conjura: website $19.99 → App Store $299 → comparison-page $799 — three surfaces, three prices for what's framed as the same product. [conjura profile](../competitors/conjura.md)
- Polar Analytics: "/pricing" shows no numbers; Shopify App Store starts at $470. [polar-analytics profile](../competitors/polar-analytics.md)

## Anti-patterns observed

Concrete examples of bad implementations and why they failed.

- **Sales-gated pricing for sub-mid-market ICPs:** Cometly's `/pricing` page lists feature bullets only with "Get Started / Book your demo" CTAs — no numbers. Hyros shows one tier ($230/mo) and gates everything else. Northbeam shows Starter $1,500 then "Contact sales". For a 30-second SMB evaluation, this is auto-disqualifying. Per [hyros profile](../competitors/hyros.md): "Disqualifies SMBs at the page level."
- **Multi-surface price discrepancy (Conjura, Zigpoll, Polar):** Same product with three or four prices across website, Shopify App Store, and comparison pages. Per [conjura profile](../competitors/conjura.md): "do not reconcile, suggesting the public pricing page may be a low-end bracket only, with real enterprise pricing gated behind sales." Trust hazard — merchants reading both surfaces distrust both numbers.
- **Re-shaped tier names without grandfathering visibility:** Triple Whale's tier names ("Growth $129 / Pro $199 / Enterprise $279") have been replaced multiple times. Old comparison articles still cite the old prices, leaving merchants confused about which is current. [triple-whale profile](../competitors/triple-whale.md).
- **Hidden overage charges:** Lifetimely's overage policy isn't surfaced on the pricing card — only third-party reviews mention it ("Multiple third-party reviews flag overage charges"). TrueProfit publishes per-order overage on the card itself; Lifetimely does not. The Lifetimely pattern produces the "expensive for what it is" review quote cluster.
- **Marketing Attribution paywalled to top tier:** TrueProfit gates "Marketing Attribution" entirely to the $200 Enterprise tier — three lower tiers see ad spend but cannot see attribution. Per [trueprofit profile](../competitors/trueprofit.md): "Marketing Attribution is paywalled to the $200/mo Enterprise tier — every lower tier sees ad spend and ROAS but not the attribution screen." For an analytics product, this is the load-bearing feature being gated, which then drives review complaints once merchants discover the gate post-purchase.
- **Multi-store cliff at top tier only (BeProfit Plus $249):** Single-shop on Basic/Pro/Ultimate, "Unlimited Shops" only on Plus. Cliff is steep for two-shop merchants who must pay 5x to add one store.
- **Two free tiers with overlapping but different feature sets (Triple Whale "Founders Dash" + "Free basic plan"):** Adds decision friction. Merchants can't tell which to pick.
- **Calculator subdomain that returns no public data (Polar `pricing.polaranalytics.ai`):** Implies pricing is computable but renders sales-led friction even harder. Marketing-funnel paradox: the existence of a calculator implies transparency; the unreachable calculator confirms opacity.
- **Trial reassurance buried below the fold (AdBeacon):** Brand pricing page advertises 30-day trial but smbguide.com con list says "No free trial or plan" — meaning even reviewers reading the page miss the trial signal. Trial must be on the card, not in a footer.

## Open questions / data gaps

- Most competitors' actual pricing-page **screenshots** are not extractable without a session — descriptions above synthesize HTML scrapes, third-party comparison articles, and Shopify App Store listings. Pixel-level details (button labels, exact toggle styling, badge color) are observable only for vendors whose pricing page renders fully via WebFetch.
- The slider mechanics on ThoughtMetric (discrete stops vs continuous) and Triple Whale's GMV-band selector were not directly inspected — I infer from "tier table updates as you change band" copy.
- Polar Analytics' `pricing.polaranalytics.ai` calculator subdomain was inaccessible via WebFetch — the actual UX of the calculator is unknown.
- Lifetimely's order-overage charge mechanics (per-order $? per-period surcharge cap?) are not published; only third-party reviews mention overage exists.
- The exact rule by which Triple Whale's pricing changed across tier-rename cycles (Tekpon's old tiers $129/$199/$279 vs current Starter $179–$299, Advanced $259–$389, Pro $749) is not reconstructable — pricing-history archive data was not pulled.
- Hyros's full rate card is reverse-engineered from two third-party sources (SegMetrics Aug 2024 vs LGG Media more-recent) which **disagree on every band** — true current pricing is unknown without a sales call.

## Notes for Nexstage (observations only — NOT recommendations)

- **9 of 18 competitors observed publish a fully self-serve, all-features-on-every-tier pricing page** (Lifetimely, ThoughtMetric, Metorik, Profit Calc, TrueProfit *with attribution gated*, Putler, Bloom, Elevar, Fairing). 4 are sales-led above the entry tier (Northbeam, Polar, Cometly, Hyros). The remaining 5 are hybrid or have multiple surfaces with discrepancies. Self-serve is the dominant SMB pattern; sales-led correlates with mid-market+ ICP positioning.
- **Flat-features-with-volume-axis-only is the most-praised pattern** in reviews. ThoughtMetric, Metorik, Lifetimely, and Elevar all use it; reviewers consistently cite "no add-ons, no surprise charges" framing as a primary reason to choose. AdBeacon uses this as their entire marketing thesis ("fixed pricing regardless of revenue tracked"). The trade-off: harder to monetize advanced power features without a feature axis.
- **Volume-axis selection varies by ICP positioning.** Orders/mo (Metorik, BeProfit, TrueProfit, Profit Calc, Elevar, Fairing, Lifetimely) targets SMB. Tracked-revenue/mo (AdBeacon, Hyros) targets mid-market. GMV/last-12mo (Triple Whale, Polar, Conjura) targets above-mid. Pageviews/mo (ThoughtMetric, Northbeam) is rarer and orthogonal. Ad-spend/mo (Cometly) is rarest. Brand-count/seat (AdBeacon agency tier) is the only non-volume axis observed.
- **Shopify App Store listing prices are systematically 15–35% higher than direct-billed prices** for the same product (Zigpoll +30–35%, Polar surfaces only via App Store, Conjura's App Store tiers are 15× the direct-website tiers). This is a structural Shopify-rev-share artifact that vendors handle differently — some absorb it, some pass through. Worth deciding our policy explicitly if we list on the Shopify App Store.
- **The 30-second self-price test** maps cleanly to a presence/absence of: (1) headline price visible without scroll, (2) volume-band selector with live price update, (3) trial CTA on every card, (4) overage rule visible on the card. Competitors with all 4 (ThoughtMetric, Metorik, Profit Calc, Elevar, Fairing) have the highest review velocity for "easy to evaluate." Competitors missing any one (Lifetimely's hidden overage, BeProfit's multi-shop cliff, AdBeacon's poorly-surfaced trial) generate review complaints downstream.
- **Annual discount range:** 14% (Lifetimely; inferred from "two months free") → 18% (ThoughtMetric) → 20% (BeProfit, AdBeacon Anchor, Profit Calc) → 25% (Zigpoll). 20% is the modal anchor.
- **Triple Whale and Conjura both publish a "vs competitor" comparison page** with a price column. AdBeacon's entire `/triple-whale-alternatives/` SEO playbook is built around this. ThoughtMetric and Polar do the same. Comparison pages are a structural part of pricing positioning — not the pricing page itself, but adjacent.
- **Free-tier presence varies sharply.** Lifetimely (50 orders), Triple Whale (Founders Dash + Free), Conjura (App Store Free), Bloom (App Store), Elevar (Starter $0, 100 orders), Fairing (100 orders). Metorik has 30-day no-CC trial instead. ThoughtMetric: 14-day trial only. AdBeacon: 30-day trial. Hyros, Northbeam, Cometly: no free trial. Free tier correlates with Shopify App Store discoverability strategy.
- **"No bill shock" / "no overage" / "no surprise charges" copy** appears on Metorik, ThoughtMetric, Bloom, Fairing, AdBeacon. This is now table-stakes reassurance copy for the SMB ICP — its absence on Lifetimely and BeProfit corresponds to the cancellation-friction review cluster.
- **Multi-surface discrepancies (Conjura, Zigpoll, Polar) are an anti-pattern with documented merchant-trust cost.** If Nexstage lists on the Shopify App Store with a different price than the direct site, the discrepancy will surface in third-party comparison articles within months.
- **Sales-led pricing is a deliberate ICP filter, not a UX failure.** Northbeam, Hyros, Cometly, Polar are not trying to convert sub-$1M revenue brands — their pricing-page friction is the qualification mechanism. The decision is upstream of pricing-page design.
