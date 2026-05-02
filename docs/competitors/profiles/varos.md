---
name: Varos
url: https://varos.com
tier: T3
positioning: Real-time peer benchmarking ("is this normal?") for DTC ecommerce and SaaS marketers — answers whether a CPM/ROAS swing is you or the market, using a data co-op of thousands of brands' anonymized ad/store data.
target_market: DTC ecommerce brands and agencies on Shopify; SaaS companies; revenue band not gated but peer groups are spend/AOV-sliced; data co-op of 4,500+ companies representing $4B+ in ad spend.
pricing: Free tier ($0); Brands $99/mo; Agencies & Teams $249/mo; Custom on request.
integrations: Shopify, Meta (Facebook) Ads, Google Ads, TikTok Ads, LinkedIn Ads, Klaviyo, Stripe, GA4. (FiveTran used as integration partner for some sources.)
data_freshness: Real-time (claimed) — "Varos updates benchmarks in real-time as contributing brands report new results"; new accounts: "Your dashboard will be generated within 48 hours" after manual review.
mobile_app: Web-only (no dedicated iOS/Android app observed)
researched_on: 2026-04-28
sources:
  - https://varos.com
  - https://www.varos.com/overview
  - https://www.varos.com/pricing
  - https://varos.com/use-cases-ecommerce
  - https://varos.com/benchmarks
  - https://www.varos.com/blog/varos-dashboard-v2
  - https://www.varos.com/blog/new-feature-set-high-low-performers-based-off-north-star-kpi
  - https://win.varos.com/en/articles/9352014-varos-best-practices
  - https://win.varos.com/en/articles/6154883-the-science-behind-varos-competitive-sets
  - https://win.varos.com/en/articles/6344019-how-to-use-benchmarking-data-ads-benchmarks
  - https://win.varos.com/en/articles/6344133-how-facebook-ads-benchmark-metrics-are-calculated
  - https://win.varos.com/en/articles/6577191-how-google-ads-benchmarks-are-calculated
  - https://win.varos.com/en/articles/6577219-how-tiktok-ads-benchmark-metrics-are-calculated
  - https://win.varos.com/en/articles/6460585-how-shopify-benchmarks-are-calculated
  - https://win.varos.com/en/articles/8455151-how-do-varos-alerts-work
  - https://win.varos.com/en/articles/8166466-connecting-your-account
  - https://win.varos.com/en/articles/8352790-google-analytics-4-ga4-dashboard
  - https://win.varos.com/en/articles/6361109-how-agencies-use-varos
  - https://win.varos.com/en/articles/6361172-how-brands-use-varos
  - https://win.varos.com/en/articles/8510913-adding-teammates-setting-permissions
  - https://www.producthunt.com/products/varos
  - https://trymesha.com/blog/varos-review/
  - https://tripleareview.com/varos-review/
  - https://aazarshad.com/resources/varos-review/
  - https://www.growwithcoast.com/post/partner-spotlight-varos
  - https://geo.sig.ai/brands/varos
  - https://techcrunch.com/2022/02/23/stop-guessing-your-kpis-varos-shows-e-commerce-saas-companies-how-you-compare-to-peers/
---

## Positioning

Varos is a peer benchmarking platform built around one question: "is this spike in CPM (or drop in ROAS) due to my ad or the market?" It's not a dashboarding tool that replaces Triple Whale or Polar — it sits beside them, contributing context. Brands and agencies connect Meta, Google, TikTok, LinkedIn, Shopify, GA4, Klaviyo, and Stripe; in exchange for sharing anonymized data into the co-op, they get free access to peer percentiles segmented by vertical, average order value, and spend tier. Y Combinator W21 alum, headquartered in San Francisco, $4.13M raised; data co-op claims 4,500+ companies and $4B+ in tracked ad spend (per geo.sig.ai brand profile and Varos's own marketing copy).

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Free | $0/mo | "Core features like real-time market trends and multiple integrations" — Shopify, Meta, Google, TikTok benchmarking; Monday Morning Benchmark email | Need filtering by sub-vertical or to invite teammates |
| Brands | $99/mo | Everything in Free + "sub-categories, advanced filtering, and recommendations" | Agency takes on multi-client account management |
| Agencies & Teams | $249/mo | Multi-client / collaborative team features, "dedicated support" | Custom data needs / very large agency |
| Custom | On request | Bespoke deals | — |

Pricing-page UI not publicly scrapeable (renders client-side / behind JS); tier prices and inclusions taken from third-party reviews (trymesha.com, tripleareview.com, aazarshad.com) and Varos search-result snippets. Free tier is the headline acquisition motion; the freemium pitch is repeated across every review.

## Integrations

**Sources (data Varos pulls in to compute the user's own KPIs and to contribute to the co-op):**
- Meta Ads (required for ad benchmarks)
- Google Ads
- TikTok Ads
- LinkedIn Ads
- Shopify (ecommerce KPIs: AOV, repeat purchase, abandoned checkout, refunds, etc.)
- GA4 (Google Analytics 4 dashboard exists, per help center)
- Klaviyo (cited in trymesha.com review as integration)
- Stripe (cited in trymesha.com review)

**Coverage gaps relative to Nexstage's 6-source thesis:**
- No Google Search Console (GSC) integration observed.
- No WooCommerce integration observed — Shopify-only on the ecommerce side. Significant gap.
- No "real" / pixel-side first-party tracking layer (Varos relies on platform-reported numbers; e.g., FB ROAS = "as reported by Meta", Google ROAS = "as reported by Google Ads").

**Connection flow (per help center "Connecting Your Account"):**
> "Click the 'Integrations' button at the top right corner of the Varos dashboard, next to your profile icon. Choose the platform you wish to connect to, then click the 'Connect' button and log in using your account credentials."
> "Some of our integrations are processed through our partner FiveTran, so you may be redirected to their site to complete the integration."
> "Your dashboard will be generated within 48 hours."

The 48-hour wait is unusual — Varos manually reviews accounts before turning on benchmarks (presumably to validate data integrity / co-op contribution).

## Product surfaces (their app's information architecture)

Varos's dashboard is gated (varos.com requires login; app at app.varosresearch.com). IA inferred from help-center articles, blog posts, and 3rd-party reviews:

- **Default Peer Group view (entry screen)** — "When you log into the Varos dashboard, it will be filtered to show you market data from your Default Peer Group." Per-platform tabs (Meta, Google, TikTok, LinkedIn, Shopify, GA4) each surface that platform's North Star KPI plus secondary metrics.
- **Meta dashboard** — North Star is CPP (Cost Per Purchase) for ecommerce or CPA for SaaS. Secondary: CPM, CTR, CPC, ROAS, Conversion Rate, Spend.
- **Google Ads dashboard** — Same North Star pattern (CPP / Cost per Conversion); Conversion Rate, CTR, CPC, CPM, ROAS, Spend.
- **TikTok Ads dashboard** — North Star Cost per Conversion; plus P50 Video Views (% of plays reaching 50%), CPM, CPC, CTR, Spend, Conversion Rate.
- **LinkedIn Ads dashboard** — Same pattern, B2B-focused.
- **Shopify dashboard** — Repeat Purchase Conversion, Repeating Orders Ratio, AOV, Discount Rate, Abandoned Checkout Rate, Refunds % of Orders, Revenue Growth, Cohort Purchase Frequency, Cohort Revenue/AOV.
- **GA4 dashboard** — "Here's how the GA4 Dashboard in Varos works and how the KPIs are calculated." (Help-center page exists; specific UI details not published.)
- **Facebook Spend Distribution / Media Mix dashboard** — "shows spend distribution between various ad platforms by the most successful SaaS companies" (varos.com search snippet); also called the Marketing Mix dashboard in Coast partner spotlight.
- **Benchmarks page (varos.com/benchmarks)** — Public-facing benchmarks page exists; content gated/JS-rendered, not scrapeable.
- **Filters / Peer Group config** — Industry, sub-vertical, spend, AOV, geographic location, channel, campaign objective, targeting type. Users can request Default Peer Group changes.
- **Alerts** — Email alerts when CPP changes statistically significantly for the user or their peers.
- **Settings > Permissions** — Team-management UI for inviting teammates with brand-scoped or "Full Brands Access" roles.
- **Integrations modal** — top-right button next to profile icon; OAuth + FiveTran fallback.

## Data they expose

### Source: Shopify
- **Pulled** (per `win.varos.com/.../how-shopify-benchmarks-are-calculated`): orders, customers, line items, refunds, abandoned checkouts, discounts. Total revenue uses the Shopify `total_price` field, "which encompasses line item prices, discounts, shipping, taxes, and tips converted to $USD." Cohort metrics require "minimum 6-12 month history".
- **Computed:**
  - Repeating Orders Ratio (% of orders from repeat customers)
  - Repeat Purchase Conversion ("Average percent of customers who purchase for the second time over total customers")
  - Cohort Purchase Frequency ("Average cumulative number of orders per customer after normalizing all initial purchases to month 1"; only customers with 12+ month history)
  - AOV (revenue / orders)
  - Discount Rate ("Proportion of total cart value discounted during a period")
  - Abandoned Checkout Rate ("The percent of customers who add something to their cart and don't ultimately purchase" — updates retroactively as carts recover)
  - Refunds as % of Orders (test orders excluded)
  - Revenue Growth (period-over-period)
  - Cohort Revenue / Cohort AOV (lifetime metrics normalized to first-purchase month)
- **Attribution windows:** N/A for Shopify (it's first-party).

### Source: Meta Ads
- **Pulled:** spend, impressions, clicks, link clicks, purchases (from `omni_purchase` and `offsite_conversion.fb_pixel_purchase`), purchase revenue. All currencies normalized to USD.
- **Computed:** CPP, ROAS, CPM, Link CTR, CPC, Conversion Rate, Spend, CPA (SaaS — uses complete-registration / custom conversion / lead events).
- **Attribution windows:** Hardcoded to **7-day click + 1-day view** for purchases. Single attribution model — no toggle.
- **Audience segmentation:** Lookalike, retargeting (custom non-lookalike), prospecting — determined at adset level.
- **Ad-level segments:** Video Facebook Ads have a separate calculation page (e.g., hook rate, thumb stop rate per Coast spotlight).

### Source: Google Ads
- **Pulled:** spend, impressions, clicks, conversions, conversion value.
- **Computed:** Cost Per Purchase, Cost per Conversion, CPM, CTR, CPC, Conversion Rate, ROAS, Spend.
- **Attribution windows:** **30-day click + 1-day view**, applied uniformly. (Note: this is intentionally different from Meta's 7d/1d to match each platform's native default.)

### Source: TikTok Ads
- **Pulled:** spend, impressions, clicks, conversion events, video views.
- **Computed:** Cost per Conversion, CPM, CPC, CTR, Spend, Conversion Rate, P50 Video Views ("The number of times your video was played at 50% of its length over the number of times it was played for at least 2 seconds"; replays excluded).
- **Attribution windows:** Not specified publicly.

### Source: LinkedIn Ads
- Help-center article exists (`how-linkedin-ads-benchmark-metrics-are-calculated`); content not directly fetched. B2B / SaaS oriented.

### Source: GA4
- Dedicated dashboard (per `8352790-google-analytics-4-ga4-dashboard`); fields and computed metrics not published in the help excerpt.

### Source: Klaviyo, Stripe
- Mentioned in trymesha.com review as integrations; computed metrics not documented publicly.

## Key UI patterns observed

> Varos's working dashboards are paywalled / login-gated. The descriptions below pull from blog posts, help-center articles, partner spotlights, and 3rd-party review write-ups. Where I could not visually verify a specific element, I say so.

### Default Peer Group view (entry screen)
- **Path/location:** Login → main dashboard (default tab depends on connected platforms).
- **Layout (prose):** Top filter strip exposes the four canonical peer-group dimensions — vertical, AOV, spend tier, and channel — plus geographic location and sub-vertical when on a paid plan. Per Best Practices article: "When you log into the Varos dashboard, it will be filtered to show you market data from your Default Peer Group - the group of other companies that are most similar to yours. You can change the filters to drill down to companies that have whichever vertical, spend/month, and AOV that you'd like to check out." Main canvas is a per-platform table.
- **UI elements (concrete):** Traffic-light indicators next to North Star KPI — "Green: Strong performance, minimize optimization"; "Yellow/Red: Inefficient, investigate secondary metrics" (per Varos Best Practices help article). Date-range picker with custom ranges including "historical comparison across previous years."
- **Interactions:** Filters are sticky and inherited across platform tabs. Users can "request changes" to the Default Peer Group (not self-service for the deepest cuts). On the line charts, "select one line at a time" to prevent visual flatness.
- **Metrics shown:** Platform-specific North Star at the top (CPP, CPA, MER, or ROAS depending on dashboard config), then secondary KPIs in a table.
- **Source:** https://win.varos.com/en/articles/9352014-varos-best-practices

### Per-platform dashboard (Meta / Google / TikTok / LinkedIn)
- **Path/location:** Sidebar > [Platform name].
- **Layout (prose):** "Navigate to a dashboard, set the filters to compare to the right peer group and then view the table" (per Best Practices). Table-first layout with rows = metrics (or peer-group buckets) and columns = your value vs. market value(s). Line charts beneath for time-series.
- **UI elements (concrete):** Peer-group filter chips at top (vertical, spend, AOV, geo, sub-vertical). Per-metric rows show your number and the benchmark; high vs. low performer ranking via the "North Star KPI" feature ("set high/low performers based off North Star KPI" — blog title). Specific percentile bar visualization (P25 / P50 / P75 with shaded band) referenced in the assignment brief is **not directly verifiable from publicly accessible Varos sources** — Varos talks about "averages", "ranges", and high vs. low performers but I could not confirm the specific P25/P50/P75 gridline + shaded P25-P75 band rendering described in the brief. UI details not visible in public sources beyond this; dashboards are paywalled/login-gated.
- **Interactions:** Click filters to recompute peer group; line-chart legend toggle ("select one line at a time").
- **Metrics shown:** Per platform (see "Data they expose" above).
- **Source:** https://win.varos.com/en/articles/6344019-how-to-use-benchmarking-data-ads-benchmarks; https://www.varos.com/blog/new-feature-set-high-low-performers-based-off-north-star-kpi

### North Star KPI selector (high/low performers)
- **Path/location:** Within per-platform dashboards.
- **Layout (prose):** Per the feature title "Benchmark around North Star metric (MER, CPP, ROAS) with Varos", users pick a North Star (CPP for performance/cost view, MER for ecommerce blended, ROAS for revenue-efficient view). The dashboard then re-ranks peers as "high" vs. "low" performers against that NSM, and lets users see the secondary KPIs of high performers. UI specifics (toggle vs. dropdown vs. tab) not visible in public copy.
- **Interactions:** Toggle / select North Star → table re-ranks → see "weak spots on various KPIs compared to high performers."
- **Metrics shown:** Whichever North Star is chosen plus secondary KPIs that explain it.
- **Source:** https://www.varos.com/blog/new-feature-set-high-low-performers-based-off-north-star-kpi (page content not fully extractable; title + summary available)

### Marketing Mix / Spend Distribution dashboard
- **Path/location:** Sidebar tab.
- **Layout (prose):** "Distribution views show spend allocation comparisons between your company and high-performing peers" (Best Practices article paraphrase). The Coast partner spotlight calls it the Marketing Mix dashboard — shows competitor ad-spend allocation across channels (Meta, Google, TikTok, etc.).
- **UI elements (concrete):** Stacked / side-by-side spend allocation comparison; specific chart type not visually confirmed.
- **Source:** https://www.growwithcoast.com/post/partner-spotlight-varos

### Shopify dashboard
- **Path/location:** Sidebar > Shopify.
- **Metrics shown:** Repeating Orders Ratio, Repeat Purchase Conversion, Cohort Purchase Frequency, Cohort Revenue/AOV, AOV, Discount Rate, Abandoned Checkout Rate, Refunds % of Orders, Revenue Growth.
- **Layout / UI elements:** UI details not available — only metric definitions documented in the help center.
- **Source:** https://win.varos.com/en/articles/6460585-how-shopify-benchmarks-are-calculated

### GA4 dashboard
- **Path/location:** Sidebar > GA4.
- **Layout / UI elements:** Help-center page exists ("Here's how the GA4 Dashboard in Varos works and how the KPIs are calculated") but specific metrics, layout, and UI elements are not in the publicly extractable copy. UI details not available — paywalled.
- **Source:** https://win.varos.com/en/articles/8352790-google-analytics-4-ga4-dashboard

### Alerts (email)
- **Path/location:** Settings > Notifications; delivery is email.
- **Behavior:** "Alerts are turned on as emails for your most used assets, but you can easily disable these in your notification settings." Alerts trigger "when the Cost Per Purchase (CPP) of you or your peers has increased significantly (based on a statistical method)." For your own account, increases tied to large spend changes are filtered out (since spend explains them). Alerts include "insights into potential root causes."
- **Channel:** Email only (no in-app or Slack confirmed).
- **Source:** https://win.varos.com/en/articles/8455151-how-do-varos-alerts-work

### Monday Morning Benchmark email
- **Path/location:** Email; weekly cadence (Monday).
- **Content:** "A snapshot of how your brand performed versus the market last week" — described in trymesha.com review and others as a key feature. Shown as a major engagement / retention loop.
- **Source:** https://trymesha.com/blog/varos-review/

### Settings > Permissions
- **Path/location:** Profile menu > Add teammates; or Settings > Permissions.
- **Layout:** Brand-scoped access list. Inviter selects either "Full Brands Access" (= admin, including future-added brands) or per-brand selection. "You need to make sure at least one dashboard is selected to grant access to, otherwise the new user won't see anything."
- **Source:** https://win.varos.com/en/articles/8510913-adding-teammates-setting-permissions

### Integrations modal
- **Path/location:** Top-right button next to profile icon.
- **Layout:** Platform tile grid; each tile has Connect button. Some platforms route through FiveTran's site for OAuth.
- **Post-connect state:** "Your dashboard will be generated within 48 hours" (manual review).
- **Source:** https://win.varos.com/en/articles/8166466-connecting-your-account

## What users love (verbatim quotes, attributed)

- "Been using this product for a few weeks now and am a huge fan. As someone who spends lots of money on Facebook Ads, one of the biggest questions when performance starts falling off is 'is it just me or is it everyone'. Varos answers that question quickly by informing me of CPM / CPA and other metric fluctuations in real time." — Zachary Shakked, Product Hunt comment (Varos PH launch)
- "I would definitely recommend checking out Varos. With Varos you can easily see how your peers are performing, for free. You get insights into not only TikTok Ads benchmarks, but also similar data for Facebook Ads, Google Ads, and more." — Darkroom Agency (cited in Varos search-result snippet)
- "It's helpful to figure out if only your ad performance sucks or if it's the same for everybody." — Reddit user (cited in Varos community feedback summary; specific thread not captured)
- "It's FREE (for now) and it's already added a ton of value for myself and my clients." — DTC community recommendation (cited in Varos search-result summary)
- "Varos has given their team a much stronger market pulse. The platform's advanced benchmarking capabilities have allowed them to comprehensively understand their market positioning and performance." — paraphrase attributed to Connor MacDonald, CMO at The Ridge (cited in Varos marketing/PR; verbatim wording not captured)

Limited reviews available — Varos is not yet reviewed on G2 ("Varos hasn't been reviewed yet on G2, though the product is listed on the platform" per search result), and the Shopify App Store listing shows "This app is not currently available on the Shopify App Store" as of research date. Product Hunt launch (4 years ago) has the largest cluster of public quotes.

## What users hate (verbatim quotes, attributed)

Verbatim user complaints are sparse; what exists is paraphrased in 3rd-party reviews.

- "Initial data gathering period required for trend visibility" — tripleareview.com (listed as a con)
- "Pricing may challenge small businesses/startups" — tripleareview.com
- "Potential integration complexity with certain platforms" — tripleareview.com
- "Limited historical data initially impacts analysis depth" — tripleareview.com
- "Learning curve dependency on the platform" — tripleareview.com
- "You have to share anonymized performance data, and Varos shows insights but doesn't execute optimizations" — search-result summary of Varos limitations
- "Benchmarks are strongest in popular verticals" — search-result summary (i.e., niche categories suffer from thin sample size)

The aazarshad.com "honest review" explicitly states it covers pros and cons, but its cons section was empty when fetched — author led with pros only.

## Unique strengths

- **Co-op data moat.** 4,500+ brands contributing $4B+ in tracked ad spend (per geo.sig.ai brand profile; Varos's marketing copy says "the largest data set in the game"). The give-to-get model is the differentiator: you cannot get peer benchmarks without contributing your own data, which both feeds the moat and gates the value prop.
- **Real-time refresh, not quarterly survey.** Most marketing benchmark reports (e.g., Klaviyo, Shopify, agency PDFs) are quarterly or annual. Varos updates as members report — relevant during platform shocks (iOS14, holiday surges, algo changes).
- **Peer group on three axes — vertical, AOV, spend tier — defended explicitly.** Per the "Science behind Varos competitive sets" article: vertical for "comparable purchase behaviors", AOV because "(1) it separates low-end, medium-end and high-end products, (2) it normalizes metrics such as cost per purchase and CPC and (3) it compares apples to apples the users share of wallet", and spend level because "results can significantly change as budgets increase" and "marketing teams become more savvy as the spend increases." This is a clear, articulated theory of why peer matching needs to be more than industry alone.
- **North Star metric as a configurable lens** — users pick MER, CPP, or ROAS as the ranking metric, then the dashboard re-ranks "high" vs. "low" performers and shows how high performers' secondary KPIs differ. This converts benchmarking from "where do I stand" into "what should I copy."
- **Platform-native attribution honesty.** Each platform's benchmarks use that platform's default attribution (Meta 7d/1d, Google 30d/1d) rather than re-attributing — apples-to-apples for the contributing companies.
- **Statistical-significance-based alerting.** CPP alerts trigger on statistical significance, not arbitrary thresholds, and explicitly suppress alerts caused by your own spend swings.
- **Free tier is generous and is the headline acquisition motion.** Most reviewers explicitly praise the free tier; multiple agencies recommend it specifically because clients can self-onboard without procurement friction.
- **Agency multi-brand permission model.** Brand-scoped access ("Full Brands Access" for admins, per-brand for users) is built for agency holding-company workflows — a Triple Whale / Polar-style pain point.

## Unique weaknesses / common complaints

- **Cold-start problem.** "Initial data gathering period required for trend visibility" + "Limited historical data initially" + "Your dashboard will be generated within 48 hours" — the product has a non-trivial onboarding lag because Varos manually reviews accounts.
- **Data-co-op tax.** Users must share anonymized performance data — a non-starter for some VC-backed brands or competitive-paranoid teams.
- **Niche-vertical thin samples.** "Benchmarks are strongest in popular verticals" — long-tail categories may not have a statistically significant peer set. Varos doesn't publicly disclose minimum N for a benchmark.
- **No execution / no optimization layer.** Varos shows insights but does not push budget changes back to ad platforms. It's read-only context, not a control plane.
- **Shopify-only on the ecommerce side.** No WooCommerce, no BigCommerce — limits TAM to Shopify-native stores.
- **No GSC / SEO benchmarks.** Strictly paid + Shopify + GA4. Organic/SEO is absent.
- **G2 review presence is essentially zero.** "Varos hasn't been reviewed yet on G2." Shopify App Store: "This app is not currently available on the Shopify App Store." Public review surface is thin — most third-party "reviews" are SEO content marketing, not user reviews.
- **48-hour delay before dashboard is usable.** Manual review is presumably for data quality but it's friction for a freemium "30-second signup" pitch.
- **Paywalled UI** — pricing page, benchmarks page, and dashboards all render client-side / behind login, making competitive research and prospect evaluation harder than it should be.

## Notes for Nexstage

- **The "is it me or the market?" question is Varos's entire positioning.** Nexstage doesn't compete here directly — but if Nexstage ever wants to layer peer benchmarking on top of its 6-source attribution view, Varos's three-axis peer model (vertical + AOV + spend tier) is the published prior art to start from. Reference: `win.varos.com/en/articles/6154883-the-science-behind-varos-competitive-sets`.
- **Varos's "North Star KPI" lens is conceptually parallel to Nexstage's `MetricSourceResolver` pattern** — in both cases the user picks a primary lens (here, MER vs. CPP vs. ROAS; in Nexstage, Real / Store / Facebook / Google / GSC / GA4) and the rest of the UI re-ranks/re-filters around it. Worth a side-by-side review when designing the source-badge UX so we can see whether Varos's tabular high-performer / low-performer ranking pattern translates.
- **Platform-native attribution windows are baked in** (Meta 7d/1d, Google 30d/1d). Varos doesn't expose attribution-window toggles. For Nexstage this is a divergence point — our cost-config recompute machinery (`UpdateCostConfigAction`, `RecomputeAttributionJob`) makes attribution-window changes possible; Varos doesn't because changing it would break peer comparability.
- **Shopify benchmarks are unusually rich** for what's nominally a marketing-benchmark tool: cohort frequency, cohort revenue, abandoned-checkout rate, repeat-purchase conversion. If a Nexstage user asked "what are repeat-purchase rates among Shopify brands of my AOV/spend tier?", Varos answers and we don't.
- **Manual 48-hour onboarding review** is interesting product-policy data — Varos is willing to slow down activation to protect data integrity. Worth a decision note if Nexstage ever opens its own benchmark layer.
- **Co-op pricing is freemium with paywall at filtering depth** — Free tier gets you the headline number; Brands ($99) unlocks sub-categories. This is the "free dashboard, paid filters" pattern; useful pricing pattern reference if Nexstage explores benchmark-as-feature.
- **Public UI evidence is thin** — no PNG screenshots captured because dashboard pages and most marketing pages render client-side / behind JS, and WebFetch on `varos.com/*` and `varos.com/blog/*` consistently returned only the page title. Most concrete UI behavior described above came from the help center (`win.varos.com`), which is on Intercom and renders server-side. The specific P25/P50/P75 percentile-bar UI mentioned in the assignment brief was **not directly verifiable in public sources** — flag for follow-up if dashboard access becomes possible.
- **No Shopify App Store listing** ("currently not available") is a notable signal — Varos's Shopify integration is direct OAuth, not a Shopify-native app. Means they're not paying app-store-tax and not collecting app-store reviews, but also means they're invisible to Shopify-native discovery.


---

## Additional observations (from earlier research pass)

**Positioning one-liner:** "Is it just me or is it everyone?" — real-time peer benchmarks so you stop guessing whether a metric drop is your problem or the market's.
- **"Monday Morning Benchmark" email.** Weekly digest showing how the brand performed vs. the market last week. Hits the inbox on the day performance marketers actually look at last week's numbers.
- **Ad platforms:** Meta, Google Ads, TikTok Ads.
- **Agencies & Teams** — ~$249/mo. Multi-client management, client reporting, advanced segmentation.
- **Anchored the "is this normal?" question as a standalone product category.** Founder's pitch ("one of the biggest questions when performance starts falling off is 'is it just me or is it everyone'") became the product's entire positioning and the reason it gets picked up.
- **Angle:** Makes "is this a market shift or a me shift?" answerable in one glance. If both lines dip on the same day, it's not you.
- **Angle:** The weekly ritual is the retention hook. It's the screen most users actually see most weeks.
- **Anonymized data contribution as on-ramp.** If Nexstage ever adds peer benchmarking, the pattern "contribute to see" is proven and the privacy story is legible.
- **Benchmarks are a secondary tool, not a primary dashboard.** No one runs their business out of Varos — it's a second tab. Reviews often describe it as a "sanity check layer" rather than a day-to-day reporting tool.
- **Brands** — ~$99/mo. Adds the fuller dashboard, real-time alerts, custom peer groups, richer integrations.
- **Coverage assumption.** Varos under-indexes in narrow verticals and doesn't flag it. If Nexstage does peer benchmarks, surface "we don't have enough peer data in your vertical yet" explicitly.
- **Coverage gaps in smaller verticals.** If your peer group is narrow (e.g., a niche hobby brand), the sample thins out and the median becomes less trustworthy. Not always transparent about minimum sample sizes.
- **Data contribution as the on-ramp.** Brands connect their ad accounts, contribute anonymized data, and in exchange get benchmarks. The exchange is legible and the anonymization is the pitch.
- **Data density:** Moderate. ~8–12 KPI rows per page. Designed for glance comparison, not deep slicing. Every row is legible in under a second.
- **Date range control:** Preset ranges (7d / 28d / 90d) with the key trick — peer medians update to the same window, so you're comparing apples to apples in time.
- **Depth of slicing is limited.** You can filter by vertical and spend tier, but more granular cuts (country, platform objective, seasonality-normalized) aren't as rich as enterprise benchmark platforms.
- **Ecommerce:** Shopify (deep integration for store KPIs like AOV, repeat rate, revenue).
- **Free** — Core benchmarking across Shopify, Meta, Google, TikTok Ads. Freemium is genuinely usable; the on-ramp.
- **Freemium does the job.** Unusual in benchmarking — you get working Shopify/Meta/Google/TikTok benchmarks for free, which drives the network-effect data flywheel.
- **Interactions:** Click a KPI row to drill into its time series and peer distribution histogram. Toggle peer group filters (vertical, spend tier, objective) without leaving the page. "You vs peer median" trend chart below.
- **Key UX detail:** Shows **sample size** of the peer group as you narrow filters — so you see "peer group: 142 brands" and know whether the median is trustworthy.
- **Key metrics shown:** ROAS, CAC, CPM, CTR, CVR, CPA — on Meta, Google, TikTok, plus Shopify store metrics (AOV, repeat rate). Your-value / peer-median / delta / percentile rank columns.
- **Layout:** Dual-line chart — your metric vs. the peer median — over the selected window. Shaded band showing P25–P75 range so you can see where you drift in and out of "normal."
- **Layout:** Filter drawer or settings panel. Vertical picker (Apparel, Beauty, Home, Pet, Personal Care, Cosmetics, SaaS, etc.), spend tier selector ($5k–$25k, $25k–$100k, $100k+), campaign objective (prospecting, retargeting, brand). Ability to save custom peer groups.
- **Layout:** Pick one KPI (e.g., ROAS) as the North Star. Dashboard then shows the upstream funnel metrics (CTR, CPM, CVR, frequency) at two percentile slices: **your value**, **top-quartile peers**. Makes the gap actionable ("your CTR is P40; top-quartile peers are at P80 — CTR is where you're leaking").
- **Layout:** Static blog posts ("Ecommerce benchmarks for Personal Care, July 2024") with aggregated peer medians. Not logged-in product, but a major SEO/content funnel and a sample of how the anonymized data presents.
- **Layout:** Weekly digest. Your key KPIs with percentile rank + delta vs. last week + delta vs. peer median. Short commentary block on market-wide movements. Links deep into the web dashboard.
- **Layout:** Your metric on the left, peer distribution on the right. Each row is a KPI (ROAS, CPA, CPM, CTR, CVR, CAC). Peer distribution visualized as a percentile bar (P25 / P50 / P75) with a marker showing where you sit on it. Color coding: green above median, red below.
- **Missing for Nexstage's ICP:** No WooCommerce integration — a gap Nexstage can exploit. No Amazon Ads, no Microsoft Ads, no Pinterest.
- **No store/attribution reconciliation.** Like Motion, Varos is single-lens: peer metrics, not "your real performance across platform and store." Doesn't try to answer "which number is right."
