---
name: Elevar
url: https://getelevar.com
tier: T2
positioning: Server-side conversion tracking and data layer infrastructure for Shopify D2C brands; replaces fragile pixel/GTM stacks and recovers iOS/cookie-loss attribution data.
target_market: D2C Shopify and Shopify Plus merchants doing $1M+ ARR; "over 6,500 D2C brands" cited (Vuori, Rothy's, SKIMS, Vessi, Thrive Causemetics, SNOW, Cuts Clothing).
pricing: $0 starter (100 orders/mo) → $200 Essentials (1k orders) → $450 Growth (10k orders) → $950 Business (50k orders); per-order overage fees.
integrations: Shopify, Shopify Plus, Headless, Recharge, Zipify, Carthook, Meta CAPI, Google Ads, GA4, TikTok Events API, Klaviyo, Postscript, Pinterest, Snapchat, Microsoft Ads, Reddit, Mixpanel, Piwik PRO, Yotpo, Omnisend, Impact Radius, Awin, CJ Affiliate, Attentive (beta), Smartly (beta), X/Twitter (beta) — 40-50+ destinations total.
data_freshness: real-time (event logs, channel accuracy alerts; "real-time event monitoring")
mobile_app: no (no iOS/Android app observed; web dashboard only)
researched_on: 2026-04-28
sources:
  - https://getelevar.com
  - https://getelevar.com/pricing-and-plans/
  - https://getelevar.com/server-side-tracking/
  - https://getelevar.com/data-layer-gtm-shopify/
  - https://getelevar.com/marketing-tag-monitoring/
  - https://getelevar.com/use-cases/
  - https://getelevar.com/event-builder/
  - https://docs.getelevar.com/docs
  - https://docs.getelevar.com/docs/monitoring-overview
  - https://docs.getelevar.com/docs/destinations-overview
  - https://docs.getelevar.com/docs/elevars-channel-accuracy-report
  - https://docs.getelevar.com/docs/how-does-attribution-feed-work
  - https://apps.shopify.com/gtm-datalayer-by-elevar
  - https://apps.shopify.com/gtm-datalayer-by-elevar/reviews
  - https://apps.shopify.com/gtm-datalayer-by-elevar/reviews?page=2
  - https://www.capterra.com/p/176626/Elevar/
  - https://www.g2.com/products/elevar/reviews
  - https://www.attnagency.com/blog/elevar-shopify-review
  - https://www.conspireagency.com/blogs/shopify/shopify-analytics-showdown-northbeam-vs-triple-whale-vs-elevar
  - https://www.aimerce.ai/blogs/seo/top-5-elevar-alternatives-for-shopify-tracking-in-2026
  - https://reputon.com/shopify/apps/google-tag-manager/gtm-datalayer-by-elevar
---

## Positioning

Elevar sells itself as the foundational tracking layer beneath the modern Shopify marketing stack — not a dashboard or BI tool. The homepage promise is operational, not analytical: "Ensure that 100% of your conversions are tracked and delivered to your marketing channels." A 3rd-party comparison summarises the wedge cleanly: "Elevar is the only one in this trio whose primary job is to push conversion data to your ad and analytics platforms, not just analyze it" (Conspire Agency, 2026).

The product is explicitly positioned as complementary to attribution dashboards rather than competitive with them. The same comparison flags this as a limitation, not a feature: "Not a reporting tool. This is a backend data product- you'll still need an attribution dashboard to make sense of it all." This is unusual in the analytics-adjacent Shopify space — Elevar leans into being plumbing, paired with (not replacing) Triple Whale, Northbeam, or Polar Analytics.

Their differentiation against "just a data layer" framing is reinforced by adjacent monitoring/observability features: a Channel Accuracy Report, Server Events Log, email alerts when delivery rates drop, and a "Tracking Guarantee" (99% conversion delivery, 30-day money-back). They describe the monitoring product as "Pingdom for conversion tags" (Marketing Tag Monitoring page).

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Starter | $0/mo | 100 orders/mo, all core features (data layer, server-side tracking, monitoring), 24h support response, $0.40/order overage | Hit 100 orders/mo |
| Essentials | $200/mo | 1,000 orders/mo, full feature parity with higher tiers, 12h support, $0.15/order overage | Cross 1,000 orders/mo |
| Growth | $450/mo | 10,000 orders/mo, 12h support, $0.04/order overage | Cross 10,000 orders/mo |
| Business | $950/mo | 50,000 orders/mo, 6h support, $0.03/order overage, custom integrations | Cross 50,000 orders/mo or need custom integration |
| Multi-Store | Contact sales | Custom for chains/portfolios | Multi-brand operator |

Notable: "All tiers feature the same core functionality" per the pricing page — they do not paywall the data layer, server-side tracking, or monitoring behind higher tiers. Only differentiator is order volume + support SLA + custom integration access.

Add-ons:
- Expert Installation: $1,000+
- Ongoing Tracking & Tag Support: from $500/mo
- GA4 Tune-up: $1,000+

15-day free trial on all paid plans. No long-term contracts.

## Integrations

**Sources (data in):**
- Shopify / Shopify Plus (primary; Checkout Extensibility, Markets, Pixel API)
- Headless storefronts (custom API implementation)
- Third-party checkouts: Recharge, Zipify, Carthook
- Browser data layer (GTM-compatible)

**Destinations (data out — 40-50+ listed):**
- Ads APIs: Meta CAPI, Google Ads, TikTok Events API, Snapchat, Pinterest, Microsoft Ads, Reddit, X/Twitter (beta), Smartly (beta)
- Analytics: GA4, Mixpanel, Piwik PRO
- Email/SMS: Klaviyo, Postscript, Yotpo, Omnisend, Attentive (beta)
- Affiliate: Impact Radius, Awin, CJ Affiliate
- Other: HubSpot

**Coverage gaps** (vs Nexstage's 6-source thesis):
- Google Search Console — not observed as a source
- No native ingestion of platform ad spend for blended ROAS calc (they push conversions OUT to ad platforms but don't pull spend back in for unified reporting — that's the explicit role left to Triple Whale/Northbeam/Polar in their stack story).

## Product surfaces (their app's information architecture)

Inferred from docs nav structure (`docs.getelevar.com`) and product pages — not all surfaces have public screenshots. Section names quoted from docs nav:

- **Getting Started** — onboarding wizard, source selection, custom events
- **Account Access and Dashboard** — user/team admin, company settings, "reporting"
- **Data Sources** — Shopify config, Markets config, headless setup, non-Shopify implementations
- **Server-Side Tracking** — destination installation, customizations, troubleshooting
- **Destinations** — list/config of 40+ outbound integrations (Meta CAPI, GA4, etc.)
- **Pre-Built Tags** — GTM container setup, web pixel install
- **Event Builder** — point-and-click GTM event creation (also a Chrome extension)
- **Data Layer** — event triggers, variables, sitewide instrumentation
- **Boosted Events** — predictive signals layered on outbound events
- **Session Enrichment** — user identity stitching, returning-user recognition, consent management
- **Consent and Compliance** — GDPR/CCPA, CMP integrations (OneTrust)
- **Monitoring** — Channel Accuracy Report, Server Events Log, email alerts, error code directory
- **Attribution Feed** (in beta per docs) — first-touch / last-touch UTM table from recent Shopify orders
- **Headless Implementation Guide** — separate setup track
- **API** — direct REST implementation for custom platforms
- **Integrations** — destination directory (100+ docs entries by destination)
- **Status Page** — incident tracking
- **Billing** — subscription, invoices, cancellation

Total ~17 distinct top-level surfaces. Note: this is heavily configuration- and observability-oriented; only one analytical surface (Attribution Feed, in beta).

## Data they expose

### Source: Shopify
- **Pulled:** Orders (source-of-truth for conversion delivery monitoring), order tags, sales channel, consent state, click IDs, email/phone, UTM parameters from Shopify's first-touch server-side cookie.
- **Computed:** Channel match rate (% of Shopify orders successfully delivered to each destination), session enrichment / user identity graph (1-year server-set cookie to identify returning users — explicitly contrasted with browser cookies' 7-day expiry).
- **Attribution windows:** First Touch (Shopify cookie) + Last Touch (Elevar data layer, "always resets to the latest set of UTM params used on an inbound link"). Single-touch only — they explicitly exclude Data-Driven and other models, deferring those to GA4.

### Source: Browser / Web Pixel
- **Pulled:** Page views, add-to-cart, view-item, checkout events, form submissions, click events, visibility events, custom events.
- **Computed:** Session enrichment ("stitches events, sessions, and channel attribution to recognize returning anonymous users"), match-rate enhancements (claim "200% or more" improvement on key parameters).

### Source: Third-party checkouts (Recharge, Zipify, Carthook)
- **Pulled:** Purchase events from non-standard Shopify checkouts.
- **Computed:** Same as Shopify — feeds the unified destination push.

### Destination: Meta CAPI
- **Pushed:** Site-wide events (PageView, ViewContent, AddToCart, InitiateCheckout, Purchase) with hashed PII for matching.
- **Computed/observed:** "Boosted Events" — predictive signals layered on top so platforms can optimize for likely outcomes. Marketing claims "10-20% more purchases attributed in their marketing platforms like Facebook and GA4."

### Destination: Google Ads / GA4
- **Pushed:** Enhanced conversions, server-side GA4 events.
- **Computed:** Enhanced match parameters via session enrichment.

### Destination: Klaviyo (and other email/SMS)
- **Pushed:** Browse, add-to-cart, started-checkout events with enriched identity for flow triggering.
- **Computed:** Marketing claims "2-3x performance boost for Klaviyo flows via Session Enrichment" and "50% or greater increase in product view and add-to-cart events" via server-side sync.

## Key UI patterns observed

Most UI details below are from prose descriptions in docs and feature pages. Public screenshots are limited; UI details not captured directly are noted explicitly.

### Channel Accuracy Report
- **Path/location:** Monitoring section, accessed from left-hand sidebar in Elevar dashboard ("navigate via the left-hand menu in the Elevar dashboard").
- **Layout (prose):** Table-style report, one row per configured destination. Columns: **Shopify** (total orders in period), **Ignored** (orders excluded — see hover behavior below), **Success** (orders successfully sent post-ignore-criteria), **% Match** (calculated as `(success + ignored) / total Shopify × 100%`), **Failures** (orders with API error responses).
- **UI elements (concrete):**
  - Hover-to-reveal: hovering "Ignored" cell drills into the Server Events Log to surface specific reasons (sales channel filter, denied consent, missing click IDs/email).
  - Hover-to-reveal: hovering "Failures" cell exposes a "More details" affordance linking to per-error-code drill-down.
  - The metric philosophy: "APIs will essentially give you a 'thumbs up' or 'thumbs down'" when receiving conversions — green/red logic baked into report.
- **Interactions:** Date-range scoping ("within the time period" referenced — exact picker UI not described in public docs); drill-down from hover into Server Events Log.
- **Metrics shown:** Order count by destination, ignore count, success count, % match, failure count.
- **Source:** https://docs.getelevar.com/docs/elevars-channel-accuracy-report — UI screenshot not embedded, layout reconstructed from documentation prose.

### Server Events Log
- **Path/location:** Monitoring > Server Events Log (cross-linked from Channel Accuracy Report hover targets).
- **Layout (prose):** Event-level log; one row per server-side event sent. Per-event status (success/failure/ignored), API response from destination, error code if applicable.
- **UI elements (concrete):** Cross-references an "Error Code Directory" organized by ad platform (Google Ads, Pinterest, Meta, Klaviyo, Snapchat each have dedicated error-code docs).
- **Interactions:** Filter by destination, by status, by error code. Used for ad-hoc verification: "confirm conversion data is flowing correctly to each platform before you rely on it for optimization decisions."
- **Metrics shown:** Per-event timestamp, destination, status, response payload/error.
- **Source:** Referenced across https://docs.getelevar.com/docs/monitoring-overview and search snippets — UI not directly screenshotted in public sources.

### Attribution Feed (beta)
- **Path/location:** Left-hand sidebar > "Attribution Feed".
- **Layout (prose):** Tabular feed. Each row represents a customer pathway (combination of First Touch + Last Touch UTMs). Sortable by revenue.
- **UI elements (concrete):**
  - Sortable column for revenue ("sort by revenue to understand their top conversion pathways by dollar amount rather than just order volume").
  - Excel/CSV export.
  - Beta badge — docs explicitly note "in beta as we add more filtering and channel translation".
- **Interactions:** Row inspection (top pathways), export.
- **Metrics shown:** First Touch UTMs (source/medium/campaign), Last Touch UTMs, Last Touch Organic Referrer (when no UTMs), revenue, order count.
- **Disclaimer captured verbatim from docs:** "not meant to be a replacement for Google Analytics" and "excludes first-touch organic referrals and alternative attribution models like Data-Driven attribution."
- **Source:** https://docs.getelevar.com/docs/how-does-attribution-feed-work

### Destinations Page
- **Path/location:** Top-level dashboard surface; "add a destination in the Elevar dashboard."
- **Layout (prose):** Directory/grid of 40-50 destinations. Each tile represents an integration; some are tagged "beta" (Google Ads, Smartly, X/Twitter, Attentive cited as beta in docs).
- **UI elements (concrete):** Per-destination configuration screen handles "property mapping, authentication, retries, and consent compliance." UI specifics (form fields, mapping interface) not described in public sources — UI details not available beyond functional description.
- **Interactions:** Click destination → configure → enable. Setup claimed at "under 15 minutes" in marketing copy.
- **Source:** https://docs.getelevar.com/docs/destinations-overview — UI details not available beyond feature description.

### Event Builder (Chrome Extension)
- **Path/location:** Browser extension that overlays on the merchant's storefront; companion to the in-app Event Builder.
- **Layout (prose):** Point-and-click overlay on the live website. Hovering highlights DOM elements; clicking selects them as event triggers.
- **UI elements (concrete):**
  - In-page element highlighter on hover.
  - "Visualization of tagged elements" overlay — shows what's already tagged with associated analytics events inline.
  - Configuration drawer/panel for: Configuration Tag (which GA4 property), Event Parameters, User Parameters.
  - Export step that downloads an import file for Google Tag Manager.
- **Interactions:** Hover to highlight, click to select element, configure tag/params in side panel, export to GTM.
- **Marketing copy verbatim:** "If you can move your mouse to a feature on your website and click – then you can create an event with Elevar's Event Builder." "You're literally three clicks away from amazing insights that used to take days to generate."
- **Event types supported:** Form Submissions, Click events, Visibility events, Pageview events.
- **Source:** https://getelevar.com/event-builder/ — workflow described, screenshots not embedded.

### Setup / Configuration Wizard
- **Path/location:** Onboarding flow after install.
- **Layout (prose):** Decision-point screen first: Elevar managed server vs. client-managed GTM server-side container. Then guided configuration of data sources (data layer + Shopify webhooks) and destinations.
- **UI elements (concrete):** Marketing copy describes "point-and-click configuration directly in the Elevar dashboard." Older blog posts reference embedded screenshots ("Screen Shot 2021-12-02 at 11.38.07 AM") but specific UI elements not enumerated.
- **Source:** https://getelevar.com/news/getting-started-with-server-side-tracking-in-elevar/ — UI details not available beyond high-level flow.

### Status Page
- **Path/location:** Standalone status page (incident tracking).
- **Layout (prose):** Standard SaaS status page convention; specifics not detailed.
- **Source:** https://docs.getelevar.com/docs/status-page-elevar-incident-tracking — UI details not available.

### What Elevar does NOT have (notable gaps)
Based on docs nav and feature pages, Elevar does NOT expose:
- A revenue/orders/AOV trend dashboard
- Channel-spend ingestion or blended ROAS computation
- Cohort retention, LTV, or repeat-rate views
- Product-level performance views
- Inventory views
- Ad creative-level performance views (they push conversions back to ad platforms; analysis happens there)
- A mobile app

This is consistent with their stated positioning as infrastructure, not analytics.

## What users love (verbatim quotes, attributed)

- "Our tracking is now much cleaner, giving us more confidence in our data and decisions." — Marie Nicole Clothing, Shopify App Store, April 15, 2026 (5 stars)
- "We are already seeing wins for our paid media results due to increased accuracy." — Serafina, Shopify App Store, April 15, 2026 (5 stars)
- "I honestly cannot think of a better customer support experience than what I just had with Elevar." — Vermont Woods Studios, Shopify App Store, April 3, 2026 (5 stars)
- "One campaign went from a 0.04 ROAS to 3.29, that's an 8,000%+ improvement." — Boveda Official Site, Shopify App Store, January 9, 2026 (5 stars)
- "It's a breeze to set up, and you can use it free until you hit an order threshold." — Ballistic Pizza, Shopify App Store, February 18, 2026 (5 stars)
- "Great peace of mind to know that conversions and pixels are connected properly..." — Lofta, Shopify App Store, November 7, 2024 (5 stars)
- "Fantastic app and experience. Have had a big sales boost with using Elevar..." — Movieposters.com, Shopify App Store, January 20, 2025 (5 stars)
- "I had an issue with tracking for around 14 months...Elevar and spoke with support manager Robin..." — Fallain Fitness, Shopify App Store, May 28, 2025 (5 stars)
- "This app is highly underappreciated. Elevar is more than server-side conversion..." — Arcadia Publishing, Shopify App Store, October 21, 2024 (5 stars)
- "This is the ONLY software we've found that correctly addresses the issues we were having with data" — Vincent M., President, Capterra, September 25, 2019 (5 stars)
- "Top notch company, they really care. Helped us get our data DIALED IN!" — Vincent M., Capterra, September 25, 2019

## What users hate (verbatim quotes, attributed)

- "Customer service could not decisively help with domain-matching problems." — Multiply Apparel, Shopify App Store, April 15, 2026 (1 star)
- "dashboard app looks beautiful at first, but after using it for a while it appears to not be intuitive" — Wildlife Tree, Shopify App Store, September 2, 2020
- "This app charged $750 on our store despite having never been installed" — sidocats, Shopify App Store, December 15, 2021
- "Bad APP, Bad service, Bad support! They are all death if you want to get some support" — HULKMAN Direct, Shopify App Store, January 18, 2023
- "The setup is complicated so you'll need to pay to have them set it up most likely." — G2 reviewer (paraphrased in search snippet, attribution to G2)
- "Like all platforms of this kind it's a bit complicated, but they made the complexity easier" — G2 reviewer (per search snippet)
- "Great app but you need to get the expert set up done..." — Moda Xpress, Shopify App Store, March 12, 2025 (5-star review, but flags setup difficulty)

Recurring criticism themes captured from 3rd-party comparison articles:
- "requires a deep understanding of GTM and data layers" — Aimerce.ai, Top 5 Elevar Alternatives, 2026
- "Elevar...is a passive tool" — Aimerce.ai, 2026 (criticism that it lacks AI-driven active optimization)
- "no longer the only—or even the best—option for every Shopify brand" — Aimerce.ai, 2026
- "When Shopify launched its own server-side Klaviyo integration, some Elevar customers were not proactively notified that it could conflict with their existing Elevar tracking. This caused Klaviyo flows to stop for some merchants." — ATTN Agency, Elevar Review 2026

## Unique strengths

- **Outbound destination breadth.** 40-50+ direct API integrations (Meta CAPI, Google Ads, GA4, TikTok, Klaviyo, Pinterest, Snapchat, Postscript, Microsoft Ads, Reddit, etc.) — wider than any competitor in the data-layer category.
- **Tracking Guarantee with money-back.** "99% conversion accuracy guarantee" with a 30-day money-back promise — unique in the category. Backed by the Channel Accuracy Report providing transparent measurement against Shopify orders as source-of-truth.
- **Tier feature parity.** All paid plans get the same tracking capabilities; pricing scales on order volume + support SLA, not feature paywalls.
- **Session Enrichment with 1-year server-set cookie.** Explicitly contrasted against browser cookies' 7-day expiry; claimed "200% or more" match-rate improvement on key parameters and "50% or greater increase in product view and add-to-cart events" sent to Klaviyo.
- **"Pingdom for conversion tags".** Dedicated monitoring/observability layer (Channel Accuracy Report + Server Events Log + email alerts when tracking falls below threshold) is rare among data-layer competitors.
- **Shopify-platform currency.** Stays current with Shopify Checkout Extensibility, Markets, Pixel API; official Shopify Plus partner; supports third-party checkouts (Recharge, Zipify, Carthook) natively.
- **Customer trust signal.** "Over 6,500 D2C brands" including SKIMS, Vuori, Rothy's, Vessi, Thrive Causemetics, SNOW, Cuts Clothing — heavy logo wall.
- **Boosted Events (predictive signals).** Forward-looking attributes layered on outbound events for ad-platform optimization — newer feature differentiator.

## Unique weaknesses / common complaints

- **Not a reporting tool.** Universally acknowledged — even by 3rd-party reviewers — that Elevar requires being paired with Triple Whale / Northbeam / Polar / Northbeam to get analyst-facing dashboards. The Attribution Feed is in beta and disclaimed as "not meant to be a replacement for Google Analytics."
- **Setup complexity at the deeper end.** Multiple sources say technical setup (especially GTM server-side container path) is hard enough that paid Expert Installation ($1,000+) is commonly purchased. Marketing claims "15 minutes" but reviewers contradict for full configurations.
- **Cost at scale.** $950/mo Business tier flagged as expensive in comparison articles; per-order overage above 50k/mo orders requires sales contact.
- **GTM-centric.** Architecture leans heavily on GTM/data layer concepts; less approachable for non-technical merchants vs. newer "plug-and-play" alternatives (Aimerce, Analyzify).
- **Communication gaps on platform changes.** ATTN Agency cites the Shopify-launched native Klaviyo integration as a case where customers were not proactively warned of conflicts; flows broke for some merchants until manually fixed.
- **No Google Search Console / GA4 ingestion.** They push to GA4 but don't surface GSC data or combine GA4 with platform data for the merchant.
- **No mobile app, no creative analysis, no LTV/cohort views.** Architectural by design — they consciously stay infrastructure — but users wanting a single pane need a second tool.

## Notes for Nexstage

- **Positioning insight: "data delivery layer" framing.** Elevar's most strategic move is acknowledging it is NOT a dashboard product, then leaning hard into a posture of being the foundation that makes other dashboards (Triple Whale, Northbeam, Polar) more accurate. This complements rather than competes with Nexstage's analytics surfaces — but it also means many SMBs end up paying for both Elevar and a dashboard tool. Worth noting in pricing/positioning research: Nexstage could position as "you don't need to bolt Elevar onto us" if our own ingestion is robust enough, OR position as "use Elevar for delivery, Nexstage for analysis."
- **Source-of-truth concept matches Nexstage gotcha.** Elevar uses Shopify orders as source-of-truth for delivery monitoring — same posture as Nexstage's "Real" lens (leftmost in 6-source badge). Their Channel Accuracy Report effectively shows the discrepancy between Real and each ad-platform-attributed source for purchases. Direct conceptual analog to our 6-source badge thesis.
- **Channel Accuracy Report ≈ discrepancy view.** Their flagship monitoring screen is a per-destination % match against Shopify orders. This is what merchants are actually anxious about post-iOS14. Nexstage's discrepancy/source-comparison surfaces could lift the column model: Real / Sent / Ignored / Failure / % Match — clean and decision-relevant.
- **Attribution Feed is in beta — and they admit it.** Their First Touch / Last Touch UTM table is gated as beta and explicitly says "not meant to be a replacement for Google Analytics." Suggests Elevar's analytics ambitions are constrained by their architectural choice; opportunity for Nexstage to own this surface fully rather than treat it as an afterthought.
- **All-tier feature parity is unusual.** Worth considering for Nexstage pricing — Elevar prices on order volume only, not feature gates. Reduces friction for early-stage SMBs to evaluate. Triple Whale and Polar gate features by tier.
- **Setup-complexity gripe is consistent.** A meaningful share of users buy paid setup ($1,000+). If Nexstage's onboarding can be genuinely self-serve for the 80% case, that's a wedge against Elevar.
- **No mobile, no creative analytics, no GSC.** Three big surface gaps that Nexstage can fill natively without competing with Elevar's core (delivery layer).
- **Verbatim destination list is broader than Nexstage current scope.** Klaviyo, Postscript, Attentive, Yotpo, Omnisend (email/SMS) and Impact/Awin/CJ (affiliate) are all destinations Elevar pushes to. None are currently Nexstage sources or destinations. Useful reference for integration roadmap prioritization.
- **Verbatim quote to remember:** Conspire Agency, 2026 — "Elevar is the only one in this trio whose primary job is to push conversion data to your ad and analytics platforms, not just analyze it." Confirms our hypothesis that Elevar is foundation, not competitor, for analytics-positioned products.
- **Public UI screenshots are scarce.** The dashboard appears to be behind login/paywall; documentation describes UI in prose but rarely embeds current screenshots. Limits how concretely we can map their visual language. Most descriptions above are reconstructed from documentation text rather than direct visual observation.
