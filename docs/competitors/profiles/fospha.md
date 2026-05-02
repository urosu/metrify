---
name: Fospha
url: https://www.fospha.com
tier: T3
positioning: Daily MMM "Measurement Operating System" for mid-market and enterprise DTC/retail brands; replaces last-click attribution and quarterly MMM with always-on impression-led modeling
target_market: Mid-to-enterprise DTC + marketplace brands (Gymshark, Huel, Dyson, Lululemon, Urban Outfitters, River Island, Debenhams); $100k+/mo media spend; UK-headquartered with offices in Austin and Mumbai
pricing: Lite $1,500/mo (1 market) → Pro $2,000/mo + % of media spend (up to 3 markets) → Enterprise custom (5 markets); ~$8,250/mo at $550k spend per third-party comparison
integrations: Shopify, Magento, GA4, Meta Ads, Google Ads, TikTok Ads, Snapchat Ads, YouTube, Pinterest, Reddit, Amazon Ads, TikTok Shop, Klaviyo, Smartly (for Prism automation), 100+ data connectors
data_freshness: daily (vs traditional weekly/quarterly MMM)
mobile_app: unknown (no public mention)
researched_on: 2026-04-28
sources:
  - https://www.fospha.com/
  - https://www.fospha.com/platform/overview
  - https://www.fospha.com/solution
  - https://www.fospha.com/pricing
  - https://www.fospha.com/platform/halo
  - https://www.fospha.com/platform/beam
  - https://www.fospha.com/platform/core
  - https://www.fospha.com/platform/prism
  - https://www.fospha.com/product-beta/glow
  - https://www.fospha.com/about-fospha
  - https://www.fospha.com/blog/redesigned-for-performance-how-fosphas-new-interface-shows-more-with-less
  - https://www.fospha.com/case-studies/arne
  - https://www.fospha.com/case-studies/gymshark-smartly
  - https://www.fospha.com/blog/what-happens-when-brands-rethink-measurement-lessons
  - https://www.fospha.com/blog/fospha-and-reddit-partnership-full-funnel-measurement-for-retail-advertisers
  - https://www.featuredcustomers.com/vendor/fospha
  - https://www.g2.com/products/fospha/reviews
  - https://segmentstream.com/blog/articles/fospha-alternatives
  - https://sellforte.com/blog/fospha-vs-sellforte
  - https://www.prnewswire.com/news-releases/fospha-launches-first-mcp-server-powered-by-independent-marketing-measurement-302738616.html
  - https://us.analytics.fospha.com/dashboards/elc_beauty_llc_clinique/kpiHealthCheck
---

## Positioning

Fospha brands itself "the Measurement Operating System for retail commerce" — a UK-headquartered platform (founded 2014 by Blenheim Chalcot) that replaces last-click attribution and traditional quarterly Marketing Mix Modeling with always-on, impression-led, ad-level Daily MMM. The pitch is aimed at mid-market to enterprise DTC and marketplace brands (Gymshark, Huel, Dyson, Lululemon) who already invest heavily in upper-funnel paid social and need a privacy-safe, cookie-less alternative to MTA/pixel tools post-iOS 14.

The product's flagship distinction vs. typical MMM vendors is cadence: model retraining occurs daily, with ad-level granularity, rather than the weekly/monthly/quarterly cycles common in the MMM category. Tagline on homepage: "Measurement that changes what happens **next**."

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Lite | $1,500/mo (£1,200 / €1,300) | Daily MMM at channel + campaign-type granularity, ad-stock modeling, "essential dashboard suite" (Channel Health Check), Shopify + GA4 integrations, 1 market, guided onboarding specialist. Spend range $100k–$500k/mo. | Need ad-level granularity, marketplace data, or >1 market |
| Pro | $2,000/mo + % of media spend (£1,600 + % / €1,800 + %) | Everything in Lite, plus ad-level granularity, post-purchase attribution, Beam forecasting, advanced dashboards, Amazon + TikTok Shop integrations, up to 3 markets, dedicated success manager, peer community access. Spend range $100k–$1m/mo. | $1m+/mo media spend, need automation (Prism), app tracking, brand measurement |
| Enterprise | Custom | Everything in Pro, plus advanced MMM calibration, Prism automation (Smartly integration), custom data infrastructure, app tracking, Glow brand measurement, up to 5 markets, weekly check-ins, QBRs, in-person workshops. $1m+/mo media spend. | — |

Pricing is published on `fospha.com/pricing`, which is unusual for the enterprise MMM category. Per a third-party comparison from Sellforte, the effective rate at $550k/mo media spend is approximately $8,250/mo (roughly 2x Sellforte at higher spend bands). Pro and Enterprise both include a variable "% of media spend" component that is not disclosed on the public pricing page.

## Integrations

**Sources (pulled):**
- Ad platforms: Meta, Google, TikTok, Snapchat, YouTube, Pinterest, Reddit, Amazon Ads (admin access required)
- Web analytics: GA4 (admin access required)
- Ecommerce: Shopify, Magento, "eCommerce platform" (admin access required)
- Marketplaces: Amazon, TikTok Shop (Pro+ only)
- Email: Klaviyo and other ESPs
- App data: tracked separately (Enterprise only)
- 100+ total data connectors per platform overview page

**Destinations (pushed to):**
- Smartly (Pro+ for Prism automated budget allocation)
- Direct ad-platform automation connections marked "coming soon" as of platform pages
- AI agents via the Fospha MCP Server (April 2026 launch — first Model Context Protocol server in marketing measurement, exposes channel attribution, ROAS trends, and saturation forecasts to Claude/ChatGPT/Slack agents)

**Coverage gaps:**
- No GSC integration mentioned anywhere (organic search modeled via Brand PPC + Direct + Referral aggregates)
- No CRM / pipeline attribution (per SegmentStream: "no path to measuring non-ecommerce revenue, no CRM integration for pipeline attribution, no support for custom conversion events")
- No B2B/wholesale, subscription beyond DTC checkout, or custom conversion events
- WooCommerce not mentioned in marketing pages — Shopify is the named ecommerce integration on the Lite tier

## Product surfaces (their app's information architecture)

The platform is split into five named modules. Sub-app URL is `app.us.fospha.com` (per Reddit search result hit on `us.analytics.fospha.com/dashboards/elc_beauty_llc_clinique/kpiHealthCheck`, indicating per-tenant dashboard slugs).

- **Core** — Daily MMM with ad-level granularity, the always-on baseline measurement product
- **Beam** — Incremental forecasting and saturation curves; "where's the next dollar best spent"
- **Halo** — Unified DTC + Amazon + TikTok Shop measurement; cross-channel halo effects
- **Prism** — Automated budget reallocation (Smartly integration); "measurement → action"
- **Glow** (Beta) — Brand-impact measurement linking awareness spend to branded search, AOV, baseline sales
- **Spark AI** — Intelligence layer that surfaces anomalies and performance shifts in plain language across the platform
- **Fospha MCP Server** — Programmatic access for AI agents (April 2026)

Within those modules, named dashboards observed in marketing copy and the redesign blog post:

- **KPI Health Check** — top-level performance overview (URL slug `kpiHealthCheck` confirmed on us.analytics.fospha.com)
- **Channel Health Check** — per-channel breakdown of attributed performance vs. target (mentioned as part of the Lite tier "Essential Dashboard Suite")
- **Spend Strategist** — predictive spend-allocation interface; forecasts ROAS, conversions, new conversions, and revenue at different spend levels per channel
- **Optimization bubble chart** — visualization for analyzing performance patterns (named in the redesign blog)
- **Reporting** dashboards — daily granularity line charts for revenue/spend trends
- **Custom Metrics** admin surface — no-code Admin feature for defining org-specific KPIs (Cost of Sale, Revenue per Visit, Conversion Rate); up to 10 custom metrics that flow across Channel Health Check, Reporting, and Optimization

## Data they expose

### Source: Shopify / Magento / ecommerce
- Pulled: orders, revenue, conversions, web vs. app sales (modeled separately)
- Computed: blended/total ROAS, cost per purchase, baseline vs. paid revenue, last-click vs. Fospha attribution comparisons
- Note per Sellforte: customer feedback indicates Fospha "modeling uses Google Analytics 4 Purchase data" rather than direct Shopify checkout data — unverified directly, but flagged as a customer observation

### Source: Meta Ads / TikTok / Snapchat / YouTube / Pinterest / Reddit
- Pulled: campaign/adset/ad spend, impressions, views, clicks, platform-reported conversions
- Computed: impression-led attribution credit, ad-level MER vs. paid-ROAS targets (specifically called out for Meta and TikTok in Core marketing imagery), blended ROAS, halo contribution to marketplace
- Attribution windows: not surfaced in marketing pages — Bayesian saturation curves and ad-stock decay handle "lookback" implicitly

### Source: Google Ads
- Pulled: PMax, Brand PPC, generic search spend/impressions/clicks
- Computed: separated Brand PPC vs. PMax credit; Last-click vs. Fospha credit comparison shown in marketing imagery

### Source: GA4
- Pulled: sessions, engaged visits, branded search volume (Glow uses this as a leading indicator)
- Required for setup ("admin access to your ad accounts, Google Analytics, and eCommerce platform")

### Source: Amazon (Pro+) and TikTok Shop (Pro+)
- Pulled: marketplace sales, sponsored ad data, organic listings
- Computed: paid vs. organic share within marketplace; halo effect from external paid media into marketplace; "Unified ROAS," "Unified revenue," "Unified conversions" across DTC + marketplace

### Source: Klaviyo / email
- Pulled: email-driven conversions
- Computed: email channel ROI within full-funnel model (shown as a channel in horizontal bar charts alongside PMax, Direct, Referral)

### Modeling layer
- Ensemble of deterministic + correlative + causal models
- Bayesian saturation curves per channel (claimed accuracy: "83% of actual outcomes lying within predicted range")
- Daily hyperparameter optimization
- Model accuracy metrics (RMSE, R²) displayed in the UI for transparency ("glass-box" positioning)
- 24 months of historical data ingested at onboarding; ~28-day go-live

## Key UI patterns observed

Public images on platform pages show stylized renderings of the dashboards rather than full screenshots. Detail below comes from those marketing illustrations plus the redesign blog post and case studies.

### KPI Health Check
- **Path/location:** Top-level entry point (slug `kpiHealthCheck` in tenant URL)
- **Layout (prose):** Per the redesign blog, the screen was "thoughtfully decluttered, creating more visual space for metrics that drive decision-making." Marketing imagery shows weekly performance metrics: visits, conversions, revenue, cost, CPP (cost per purchase), and ROAS displayed as a row of KPI tiles. Below the tiles, channel-by-channel performance comparisons appear as horizontal bar charts.
- **UI elements (concrete):** Refined color palette with high-contrast combinations; fluid typography; "buttons that respond instantly, modals that behave predictably." Tables include enhanced search, column pinning, expandable rows, and drag-and-drop column customization.
- **Interactions:** Standardized navigation patterns across sections; keyboard-navigable; period comparison built into the KPI tiles.
- **Metrics shown:** visits, conversions, revenue, cost, CPP, ROAS — all weekly granularity by default, daily-fresh data
- **Source/screenshot:** UI details from https://www.fospha.com/blog/redesigned-for-performance-how-fosphas-new-interface-shows-more-with-less; URL slug confirmed via web search hit on us.analytics.fospha.com

### Core (Daily MMM dashboard)
- **Path/location:** "Core" module within the platform sidebar
- **Layout (prose):** Marketing illustrations on `/platform/core` show four chart types stacked. Top: horizontal bar chart of channel-attributed value changes with tooltips, with bar values ranging from -2.8k to +52.2k (positive deltas in one color, negative in another). Middle: comparative channel-performance bars across Email, Referral, PMAX, Direct. Below: side-by-side attribution comparison — "Last-click vs. Fospha attribution" — across paid social, Amazon, brand PPC, organic search (literally a 4-bar group per channel showing the attribution gap). Bottom: daily-granularity line charts tracking revenue and spend trends, plus ad-level performance comparing MER vs. paid-ROAS targets specifically for Meta and TikTok ads.
- **UI elements (concrete):** Tooltip on hover shows specific value; color-coded positive/negative deltas; explicit "Last-click vs Fospha" framing as a comparison chart (this is the load-bearing visual story).
- **Interactions:** Drill-down from channel to ad-level granularity (Pro tier+); filter by KPI per the comparison article.
- **Metrics shown:** ROAS, MER, spend, impressions, views, clicks, attributed revenue/conversions per channel, last-click attribution as a parallel reference column.
- **Source/screenshot:** https://www.fospha.com/platform/core (marketing illustrations, not real screenshots)

### Beam (Incremental forecasting)
- **Path/location:** "Beam" module
- **Layout (prose):** Per `/platform/beam`, the headline visualization is a scatter plot of revenue vs. spend with a fitted trend line and confidence-interval shading (the saturation curve). Surrounding it: predictive metric tiles showing forecasted daily revenue ranges (e.g., "$6.5k between $5.5k–$7.5k") and ROAS projections. Accuracy indicators are line graphs tracking model performance within "Good" and "Excellent" ranges.
- **UI elements (concrete):** Scatter plot with confidence-interval shading (not just a line); explicit ranges in metric tiles ("$5.5k–$7.5k" rather than a point estimate); accuracy line graph with named bands ("Good," "Excellent").
- **Interactions:** AI-powered insight callouts warn of revenue drops and corresponding ROAS impacts.
- **Metrics shown:** forecasted daily revenue (with confidence intervals), forecasted ROAS, channel saturation level, RMSE / R² accuracy metrics.
- **Source/screenshot:** https://www.fospha.com/platform/beam

### Spend Strategist
- **Path/location:** Within the Beam/optimization area; described as "a business case on a screen"
- **Layout (prose):** Predictive spend-allocation interface. Forecasts ROAS, conversions, new conversions, and revenue at different spend levels per channel. Used to "uncover headroom in channels and predict future conversions, new conversions, and revenue at different spend levels." The redesign blog calls this "one of their most-used features."
- **UI elements:** Specific UI not described in detail in public sources; likely interactive saturation curve with spend slider per channel based on description.
- **Interactions:** "See what's working, plan ahead, maximize returns" — visualize impact of increasing/decreasing budget per channel.
- **Metrics shown:** ROAS, conversions, new conversions, revenue forecasts at varying spend levels.
- **Source/screenshot:** UI details not available — only feature description seen on marketing page and LinkedIn post by Jamie Bolton.

### Halo (Unified DTC + Marketplace)
- **Path/location:** "Halo" module
- **Layout (prose):** Marketing illustrations on `/platform/halo` show three KPI tiles at top: "Unified ROAS" (example value "7.5 with 11% increase"), "Unified revenue" ("21 million dollars with 15% increase"), and "Unified conversions" with growth indicators. Below: bar charts comparing ROAS by platform across PMax, Email, Meta, TikTok, Snapchat, Amazon, and YouTube. A before/after comparison shows performance with and without the activation status of the Halo product.
- **UI elements:** Percentage-delta indicators next to each KPI; growth arrows; bar chart with consistent platform ordering.
- **Interactions:** Toggle activation states; side-by-side performance comparison.
- **Metrics shown:** Unified ROAS, Unified revenue, Unified conversions, per-platform ROAS, paid-vs-organic share of marketplace revenue.
- **Source/screenshot:** https://www.fospha.com/platform/halo

### Prism (Automation)
- **Path/location:** "Prism" module
- **Layout (prose):** Marketing copy describes a three-stage flow rather than a specific dashboard: (1) Fospha shares ad-level performance data; (2) Smartly receives signals and adjusts budgets toward chosen KPI (ROAS or new customer acquisition); (3) Guardrails prevent over-rotation. UI is implied to be configuration-driven (KPI targets + guardrail settings) rather than a chart-led dashboard.
- **UI elements:** Not described publicly.
- **Interactions:** KPI target selection, guardrail thresholds. Direct ad-platform automation marked "coming soon."
- **Metrics shown:** Recommended budget shifts, before/after performance.
- **Source/screenshot:** UI details not available — only flow description seen on marketing page.

### Glow (Brand measurement, Beta)
- **Path/location:** Beta module — `/product-beta/glow` (page returned 404 on direct fetch but referenced from platform overview)
- **Layout (prose):** Per platform overview and PR materials, displays causal chain from awareness spend → branded search + engaged visits → long-term baseline sales, AOV, and ROAS. Forecasts long-term brand campaign impact "without waiting six to ten months."
- **UI elements:** Bayesian network diagram is implied by the "causal reasoning modeling" pitch but not visually confirmed.
- **Interactions:** Not described publicly.
- **Metrics shown:** Branded search volume, engaged visits, baseline sales lift, AOV lift, long-term ROAS forecast.
- **Source/screenshot:** UI details not available — feature is marked Beta and gated behind Enterprise tier.

### Custom Metrics admin
- **Path/location:** Admin settings; flows through to Channel Health Check, Reporting, and Optimization dashboards
- **Layout (prose):** No-code form for Admin users to define organization-specific KPIs. Up to 10 custom metrics per organization. Examples named: Cost of Sale, Revenue per Visit, Conversion Rate.
- **Interactions:** Daily refresh alongside Fospha's measurement updates.
- **Source/screenshot:** Feature description only; UI not shown.

### Fospha MCP Server (April 2026)
- **Path/location:** External API surface, not a UI
- **Capability:** Allows AI agents (Claude, ChatGPT, Slack, custom agents) to query channel attribution, ROAS trends, and saturation forecasts via natural language. Eliminates CSV exports and dashboard logins. Per PR Newswire announcement: "Agents can ask which paid channel is performing most efficiently."
- **Source:** https://www.prnewswire.com/news-releases/fospha-launches-first-mcp-server-powered-by-independent-marketing-measurement-302738616.html

## What users love (verbatim quotes, attributed)

- "Fospha is the most important tool in our marketing stack. By using Fospha to guide spend, Huel grew new customer revenue by 54% in just 6 months." — Huel customer testimonial, surfaced via Fospha homepage and search results
- "Every time management challenged the numbers, I could open Fospha to prove what was really happening. Over time, Fospha became our source of truth for digital performance." — Rabee Sabha, Digital Marketing Manager at ARNE, Fospha case study
- "Smartly's Predictive Budget Allocation helped us scale paid social with confidence. Combined with Fospha's unified measurement, it eliminated excessive time spent on budget decisions allowing us to focus more on creative strategy and growth." — Daniel Green, Head of Digital Marketing at Gymshark, Fospha case study
- "I would say that it's a no-brainer to invest and test the Fospha platform." — Morgan Decker, Head of DTC Marketing at Andie Swim, Fospha blog
- "You guys are phenomenal partners and are constantly innovating and adding new components." — Morgan Decker, Head of DTC Marketing at Andie Swim, Fospha blog
- "The discrepancy between Last Click and ad platform data was totally astronomical." — Morgan Decker, Head of DTC Marketing at Andie Swim, Fospha blog (framing the problem Fospha solved)
- "Fospha Attribution provided us with the confidence to scale our TikTok advertising." — Natalie Pedrayes, Director of Growth Marketing at CUUP, FeaturedCustomers
- "Attribution is a new way of looking at things which the team are really excited about, especially being able to identify headroom in channels and justify focus on upper funnel." — Thomas May, Head of Growth at Thread, FeaturedCustomers
- "Insightful and great to work with. Before working with Fospha we struggled to know what to do next with our marketing. Fospha insights are really powerful stuff and helped us…" — Claire Powell, CEO at J.W. Hulme Co, FeaturedCustomers (truncated in source)
- "Fospha really have been great thus far and i can't fault them." — G2 reviewer, surfaced via search aggregation
- "Fospha has been a game-changer for us. It's not just about looking at individual channels anymore; it's about understanding the bigger picture. It's like having a football team where every player has a role, and together, they work towards one goal: a conversion." — G2 reviewer, surfaced via search aggregation

## What users hate (verbatim quotes, attributed)

G2 review pages returned 403s on direct fetch, so verbatim cons quotes are limited to what surfaced through search aggregations and third-party comparison articles. Marked accordingly.

- "Weekly / Daily Data Segments are not available; meaning users have to manually filter & copy across metrics, which is very time consuming." — G2 reviewer, surfaced via search aggregation (April 2026)
- "The dashboard can be clunky and lacks certain customization options." — recurring G2 review theme, surfaced via search aggregation
- "Users on G2 consistently flag the inability to build custom reports. Dimensions, filters, breakdowns — Fospha controls what you see." — paraphrase of recurring G2 complaint, summarized in SegmentStream's Fospha alternatives article (2026)
- "Fospha only allows viewing at the ad set level, hindering effective campaign analysis." — G2 reviewer paraphrased in search aggregation (suggests granularity ceiling on lower tiers)
- "The history accessible is only going back 12 months, with any further history requiring an extra cost." — G2 reviewer, surfaced via search aggregation
- "The tool measuring your Meta spend is also Meta's endorsed measurement partner. That creates obvious tension for brands seeking independent reads." — SegmentStream Fospha alternatives article (2026), summarizing user discomfort with Fospha's official measurement partnerships across TikTok, Reddit, Pinterest, Snapchat, Meta, and Google
- "When a model weights impressions heavily, channels that generate massive impression volumes receive disproportionate credit, and without a mechanism to separate visibility from causation, impression-weighted attribution risks rewarding volume over impact." — SegmentStream Fospha alternatives article (2026), critiquing Fospha's core methodology

Note: limited verbatim quotes available for the negative side. Most criticism is paraphrased in third-party comparison articles or aggregated by search engines from the G2 reviews page (which returned 403 on direct fetch). Glassdoor employee reviews exist but were not used as user-feedback sources.

## Unique strengths

- **Daily MMM cadence with ad-level granularity** — most MMM vendors retrain weekly, monthly, or quarterly. Fospha retrains daily and goes down to individual creative/ad performance, which is genuinely distinctive in the MMM category.
- **Glass-box transparency** — RMSE / R² model accuracy metrics surfaced in the UI; saturation curves claimed to have "83% of actual outcomes within predicted range." Most MMM tools hide model internals.
- **First MCP server in marketing measurement** (April 2026) — exposes channel attribution, ROAS trends, and saturation forecasts to AI agents (Claude, ChatGPT, Slack). No other measurement vendor has shipped this as of the research date.
- **Tier-1 enterprise customer roster** — Gymshark, Huel, Dyson, Lululemon, Urban Outfitters, Sweaty Betty, Debenhams, BOOHOOMAN, River Island, Nécessaire. Per Fospha, 200+ customers and $4B in marketing spend under management.
- **Last-click vs. Fospha comparison view as a load-bearing UI element** — Core dashboard surfaces both side-by-side. Direct analog to multi-source attribution comparison thinking.
- **Unified DTC + Amazon + TikTok Shop measurement (Halo)** — explicitly models cross-channel halo effects between marketplace and DTC, which most pixel/MTA tools cannot do at all.
- **28-day onboarding with 24 months of historical data ingested** — fast for an MMM vendor; most enterprise MMM is 8-16 weeks.
- **Polaris Design System adoption (Shopify's design system)** — recent UI redesign explicitly chose Polaris "for familiarity with Shopify users" — a signal of where the buyer persona overlaps.

## Unique weaknesses / common complaints

- **Impression-weighted attribution structurally biases toward high-volume cheap-CPM inventory** — programmatic display, broad TikTok reach, and cheap impression channels can score disproportionately well even without causal contribution. SegmentStream calls this a "structural flaw."
- **Independence concerns from ad-platform partnerships** — Fospha is an officially endorsed measurement partner for Meta, TikTok, Reddit, Pinterest, Snapchat, and Google. Some buyers see this as a conflict-of-interest signal vs. truly independent MMM.
- **Limited reporting customization** — recurring G2 complaint about inability to build custom reports; Fospha controls available dimensions, filters, breakdowns.
- **Dashboard "clunky" before recent redesign** — addressed in 2025/2026 with Polaris adoption, but legacy reviews still surface this complaint.
- **Granularity ceiling on Lite tier** — channel + campaign-type only; ad-level requires Pro ($2k + variable %).
- **12-month history limit** — extending requires additional cost per G2 reviewer.
- **No automated execution from Lite/Pro tiers** — Prism (automation) is Enterprise-only and goes through Smartly rather than direct ad-platform connections (those are "coming soon").
- **Ecommerce-only scope** — no path to B2B/wholesale, custom conversion events outside Shopify checkout, subscription revenue beyond DTC, or CRM/pipeline attribution.
- **GA4-Purchase-data dependency** flagged by Sellforte — third-party comparison claims "feedback from customers switching from Fospha to Sellforte has been that Fospha modeling uses Google Analytics 4 Purchase data" rather than direct Shopify checkout, though this is unconfirmed.
- **No Geo Lift Studies, no incrementality test calibration, no bidding parameter recommendations** — features that some MMM competitors (e.g., Sellforte, Measured) ship.
- **No GSC integration** — organic search is rolled into broader channel aggregates (Brand PPC, Direct, Referral) rather than directly modeled from Google Search Console.

## Notes for Nexstage

- **Daily cadence + ad-level is the entire wedge.** Fospha's positioning vs. typical MMM is *not* methodology — it's update frequency + granularity. Worth keeping in mind for how Nexstage frames data freshness in the source-badge story (Real, Store, Facebook, Google, GSC, GA4).
- **Last-click vs Fospha as a literal side-by-side chart is a direct analog to Nexstage's 6-source-badge thesis.** Fospha specifically renders "Last-click vs Fospha attribution" as a 4-bar comparison group across paid social, Amazon, brand PPC, organic search. This is the same conceptual frame as showing Real / Store / Facebook / Google / GSC / GA4 as parallel lenses on a single metric.
- **No GSC source.** Fospha doesn't surface Google Search Console anywhere in the platform. For Nexstage's GSC source badge to be credible against the upmarket comp set, GSC's role needs to be a deliberate differentiation point, not just parity.
- **Polaris Design System is the deliberate UI choice for a Shopify-adjacent customer base.** Mentioning this because Nexstage's frontend (React 19) faces the same audience. Polaris solves recognition; the redesign blog explicitly cites this rationale.
- **Pricing transparency is unusual at this tier.** Fospha publishes Lite ($1,500) and Pro ($2,000 + %) prices openly. Most MMM competitors (Sellforte, Measured, Northbeam Enterprise) keep pricing opaque. The variable "% of media spend" component on Pro is *not* disclosed publicly though — the headline price is the entry point, the real price is bigger.
- **MCP Server (April 2026) is the first of its kind in the category.** Worth noting as Nexstage thinks about how its data is queried by AI agents going forward.
- **The "% of media spend" pricing axis is upmarket-coded.** Lite at $1.5k flat is the only spend-independent SKU; Pro and Enterprise both scale with media spend, which prices Fospha out of true SMB.
- **UK-first geographic skew is real.** Founded by Blenheim Chalcot, headquartered in London (Scale Space, 58 Wood Lane), customer roster is heavy UK/EU DTC (Gymshark, Huel, BOOHOOMAN, Sweaty Betty, River Island, Debenhams) before US expansion. Austin office is comparatively recent.
- **No public mobile app, no real-time refresh.** Daily is the floor — there is nothing hourly or live in Fospha. Nexstage's hourly snapshots are a real differentiation point for ops-tier users.
- **Glow's Bayesian-network diagram is the only place a non-tabular data viz is implied.** Worth tracking when the Beta exits — could become a category-defining brand-measurement visualization.
- **Spend Strategist and KPI Health Check are the named "money screens"** — the redesign blog calls Spend Strategist "one of the most-used features." If Nexstage eventually builds a planning surface, these are the named comps to study UI patterns from (though full screenshots are not publicly available).
- **No COGS / margin discussion anywhere.** Fospha is a media-measurement product, not a profitability product — they don't talk about COGS, gross margin, AOV, or LTV:CAC outside of Glow's "AOV lift" framing. This is a category-level tell: MMM tools do not own the P&L surface, which is open for tools positioned closer to the Triple Whale / Polar Analytics / Lifetimely axis.
