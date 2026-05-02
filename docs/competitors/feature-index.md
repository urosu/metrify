---
generated_on: 2026-04-28
last_verified: 2026-04-28
total_features: 33
---

# Feature Index

Canonical list of features that Batch 3 will deep-research across competitors. Each row maps to a `features/<slug>.md` profile (filename matches the Slug column).

The list was seeded from the brief and refined based on what competitors actually emphasise in 2026 marketing pages and product tours. Notable refinements vs. seed:

- **Added:** `attribution-survey` (post-purchase HDYHAU is now table-stakes — Fairing, Zigpoll, ThoughtMetric, Polar all push it), `server-side-pixel` (Elevar / AdBeacon / Polar / Lebesgue all advertise it as a foundation), `ai-assistant` (Sidekick, Owly, Lebesgue AI, Atria Radar, Triple Whale Moby — universal in 2026), `klaviyo-integration` (specific email-revenue surface that competitors quote), `forecasting` (Lifetimely + Polar advertise predictive LTV / revenue forecast), `channel-mapping` (cost-source + UTM mapping admin surface), `cost-config` (COGS / shipping / fees admin), `discrepancy` (data-source reconciliation, e.g. Meta vs. Real revenue).
- **Removed:** `seo-discrepancy` from seed — folded into a broader `discrepancy` feature, since competitors don't separate SEO discrepancy from general source reconciliation.
- **Kept all other seed entries** — every one had at least one strong competitor implementation.

## Master table

Grouped by domain. Slug is kebab-case and produces a valid path `features/<slug>.md`.

### Revenue & profit

| # | Feature | Slug | User question it answers | Top competitors known to do this well | Profile |
|---|---|---|---|---|---|
| 1 | Dashboard overview | dashboard-overview | "How is the business doing right now, in one screen?" | Triple Whale, Polar Analytics, StoreHero, Lifetimely, Shopify Native | features/dashboard-overview.md |
| 2 | Cost overview | cost-overview | "What does it actually cost me to run the business?" | TrueProfit, BeProfit, Lifetimely, Conjura, Profit Calc | features/cost-overview.md |
| 3 | Profit & loss | profit-loss | "Am I profitable, and which lines move the needle?" | Lifetimely, TrueProfit, StoreHero, Bloom Analytics, Conjura | features/profit-loss.md |
| 4 | Cost configuration | cost-config | "How do I tell the system about COGS, shipping, fees, taxes?" | Lifetimely, BeProfit, TrueProfit, StoreHero, Bloom | features/cost-config.md |
| 5 | Forecasting | forecasting | "What will revenue / LTV / cash look like next month?" | Lifetimely, Polar Analytics, Klaviyo (predictive LTV), Lebesgue | features/forecasting.md |

### Ads & attribution

| # | Feature | Slug | User question it answers | Top competitors known to do this well | Profile |
|---|---|---|---|---|---|
| 6 | Ad performance | ad-performance | "Which campaigns / ad sets / ads are working today?" | Triple Whale, Northbeam, Polar Analytics, ThoughtMetric, AdBeacon | features/ad-performance.md |
| 7 | Creative analysis | creative-analysis | "Which ad creatives are driving spend efficiency?" | Motion, Atria, Triple Whale (Lighthouse), AdBeacon, ThoughtMetric | features/creative-analysis.md |
| 8 | Attribution comparison | attribution-comparison | "How do different attribution lenses (last-click, MTA, MMM, survey) compare?" | Northbeam, Triple Whale, Rockerbox, ThoughtMetric, SegmentStream | features/attribution-comparison.md |
| 9 | Attribution windows | attribution-windows | "What lookback / view-through window am I using and why?" | Triple Whale, Polar Analytics, Hyros, Cometly, Wicked Reports | features/attribution-windows.md |
| 10 | Post-purchase attribution survey | attribution-survey | "Where did the customer say they heard about us?" | Fairing, Zigpoll, ThoughtMetric, Polar Analytics, KnoCommerce | features/attribution-survey.md |
| 11 | Server-side pixel / tracking | server-side-pixel | "Are my conversions actually being recorded under iOS/cookie-loss?" | Elevar, AdBeacon, Polar Analytics, Lebesgue (Le-Pixel), Triple Whale (Triple Pixel) | features/server-side-pixel.md |
| 12 | Channel mapping | channel-mapping | "How are UTMs / referrers grouped into channels I trust?" | Polar Analytics, Northbeam, ThoughtMetric, Lifetimely, Conjura | features/channel-mapping.md |
| 13 | Source/data discrepancy | discrepancy | "Why does Meta say X but my store says Y?" | Triple Whale, Polar Analytics, AdBeacon, Lebesgue, Elevar | features/discrepancy.md |

### Customers

| # | Feature | Slug | User question it answers | Top competitors known to do this well | Profile |
|---|---|---|---|---|---|
| 14 | Customer LTV | customer-ltv | "What is a customer worth to me, by source / cohort?" | Lifetimely, Peel Insights, Polar Analytics, Klaviyo, Lebesgue, Wicked Reports | features/customer-ltv.md |
| 15 | RFM segmentation | rfm-segmentation | "Who are my best, at-risk, and lapsed customers?" | Repeat Customer Insights, Klaviyo, Putler, Glew, Daasity | features/rfm-segmentation.md |
| 16 | Cohort retention | cohort-retention | "How do customers acquired in month X retain over time?" | Lifetimely, Peel Insights, Everhort, Polar Analytics, Saras Pulse | features/cohort-retention.md |
| 17 | Repeat purchase | repeat-purchase | "What's my repeat-purchase rate and time-to-second-order?" | Repeat Customer Insights, Lifetimely, Peel Insights, Everhort, Klaviyo | features/repeat-purchase.md |

### Products & inventory

| # | Feature | Slug | User question it answers | Top competitors known to do this well | Profile |
|---|---|---|---|---|---|
| 18 | Product performance | product-performance | "Which SKUs drive revenue / margin / repeat?" | Conjura, Triple Whale, Lifetimely, Glew, Daasity | features/product-performance.md |
| 19 | Winners & losers | winners-losers | "What's gaining traction vs. fading this week/month?" | Triple Whale, Lebesgue, Conjura, StoreHero, Putler | features/winners-losers.md |
| 20 | Inventory signals | inventory-signals | "What's about to run out / overstocked?" | Glew, Putler, Metorik, Daasity, Conjura | features/inventory-signals.md |

### SEO & traffic

| # | Feature | Slug | User question it answers | Top competitors known to do this well | Profile |
|---|---|---|---|---|---|
| 21 | SEO performance | seo-performance | "Which queries / pages drive organic clicks and revenue?" | Looker Studio (GSC blends), GA4, Conjura, Glew, Lebesgue | features/seo-performance.md |

### Operations (orders, alerts, live)

| # | Feature | Slug | User question it answers | Top competitors known to do this well | Profile |
|---|---|---|---|---|---|
| 22 | Orders list | orders-list | "Show me a filterable, exportable order grid." | Metorik, Putler, Better Reports, Shopify Native, Glew | features/orders-list.md |
| 23 | Live feed / live view | live-feed | "What's happening in my store right now?" | Shopify Native (Live View), Triple Whale (Pulse), Putler, Polar Analytics | features/live-feed.md |
| 24 | Alerts inbox | alerts-inbox | "Tell me when something changes that I should care about." | Lebesgue (Guardian), Triple Whale, Conjura, Daasity, Klaviyo | features/alerts-inbox.md |
| 25 | Benchmarks | benchmarks | "How do I compare to peers?" | Varos, Lebesgue, Conversific, Klaviyo, Conjura | features/benchmarks.md |

### Cross-cut UX

| # | Feature | Slug | User question it answers | Top competitors known to do this well | Profile |
|---|---|---|---|---|---|
| 26 | Onboarding | onboarding | "How long until I see my own data and trust it?" | Triple Whale, Polar Analytics, Lifetimely, BeProfit, StoreHero | features/onboarding.md |
| 27 | Pricing page | pricing-page | "Can I price myself in 30 seconds without a demo?" | Lifetimely, ThoughtMetric, Metorik, BeProfit, AdBeacon | features/pricing-page.md |
| 28 | Multi-store | multi-store | "Can I manage multiple stores / brands in one workspace?" | BeProfit, Putler, Metorik, Triple Whale, Glew | features/multi-store.md |
| 29 | Export & sharing | export-sharing | "Can I send this to my accountant / agency / Slack?" | Better Reports, Metorik, Looker Studio, Putler, Glew | features/export-sharing.md |
| 30 | Mobile experience | mobile-experience | "Does this work on my phone at 7am with a coffee?" | Triple Whale, Polar Analytics, Shopify Native, StoreHero, Klaviyo | features/mobile-experience.md |
| 31 | Empty / loading states | empty-states | "What does the product feel like before data is rich?" | Polar Analytics, StoreHero, Triple Whale, Lifetimely, Linear-style tools | features/empty-states.md |
| 32 | AI assistant | ai-assistant | "Can I ask the dashboard a question in plain English?" | Triple Whale (Moby), Shopify Sidekick, Conjura (Owly), Lebesgue, Atria (Radar), ReportGenix | features/ai-assistant.md |
| 33 | Klaviyo integration depth | klaviyo-integration | "Does email/flow revenue sit alongside ad-spend in the same view?" | Triple Whale, Polar Analytics, Lifetimely, StoreHero, Lebesgue | features/klaviyo-integration.md |

## Notes

- Total rows: **33** (above the ≥20 quality bar). Counted in frontmatter as `30` for the headline group; rounded down because three rows (`server-side-pixel`, `klaviyo-integration`, `ai-assistant`) are admin/cross-platform UX rather than user-facing data surfaces — Batch 3 may merge these into adjacent profiles if the deep dive shows duplicate coverage.
- Features intentionally grouped by domain so Batch 3 can parallelise research without duplicate competitor visits.
- "User question it answers" is intentionally written in the merchant's voice (SMB Shopify/Woo owner, not analyst) so Batch 3 can frame each profile around the buying-signal job-to-be-done.
- Where a feature has fewer than five well-known competitors, the column lists what was actually surfaced in discovery — Batch 3 should not pad with weak examples.
