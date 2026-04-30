---
name: Rockerbox
url: https://rockerbox.com
tier: T3
positioning: Independent multi-touch attribution + MMM + incrementality platform for mid-market and enterprise multi-channel brands; replaces platform-reported metrics with a unified, deduplicated view including hard-to-track channels (TV, OTT, podcast, direct mail, sponsorships)
target_market: Mid-market to enterprise DTC and multi-channel brands; $1M+ annual marketing spend typical; ecosystem-agnostic (Shopify supported but not exclusive); US-centric customer roster
pricing: Opaque, custom. Public starting price reported at $2,000/mo (GetApp); Vendr transaction data shows annual contracts in mid-five to low-six figures for mid-market and mid-six figures for enterprise. Modular product mix (Collect, Track, Export, Journey, Testing, MMM) priced separately.
integrations: 100+ integrations including Shopify, Meta Ads, Google Ads, Bing, Apple Search Ads, TikTok, Pinterest, Snapchat, LinkedIn, Reddit, The Trade Desk, DV360, Criteo, AdRoll, Segment, Impact.com, Rakuten, CJ Affiliate, Pebble Post, LS Direct, Postie, MNTN, Tatari, Hulu, Comcast, Spotify, iHeartRadio, Pandora, AppsFlyer, Branch, Adjust, Singular; data warehouse export to BigQuery, Snowflake, Redshift, Google Sheets
data_freshness: Daily for digital sources; near-real-time for MTA reporting per marketing copy; TV/OTT log files updated as feeds arrive; MMM is periodic model retraining (not real-time)
mobile_app: No native mobile app observed. Web-responsive dashboard only.
researched_on: 2026-04-28
sources:
  - https://rockerbox.com
  - https://www.rockerbox.com/plans
  - https://www.rockerbox.com/tv-and-ott
  - https://www.rockerbox.com/mmm
  - https://www.rockerbox.com/marketing-analysis
  - https://www.rockerbox.com/blog/sponsorships-marketing-attribution
  - https://www.rockerbox.com/blog/rockerbox-reimagined-a-deep-dive-on-the-new-and-updated-functionality
  - https://www.rockerbox.com/blog/g2-high-performer-attribution
  - https://www.rockerbox.com/blog/exciting-news-were-joining-doubleverify
  - https://help.rockerbox.com
  - https://help.rockerbox.com/category/q2bwxh2tak-rockerbox-ui
  - https://help.rockerbox.com/article/aij61vd747-performance-at-a-glance-rockerbox-home-page
  - https://help.rockerbox.com/article/ard4c2tnaf-multi-touch-attribution-mta-model-overview
  - https://help.rockerbox.com/article/qvsgzja6rn-funnel-position-view
  - https://help.rockerbox.com/article/y3rqyeh61z-channel-overlap-overview
  - https://help.rockerbox.com/article/6l82ckvtmf-user-behavior-view-customers-paths
  - https://help.rockerbox.com/article/x1yz9tmm69-mmm-scenario-planner
  - https://help.rockerbox.com/article/18s9mc7c4g-podcasts
  - https://www.featuredcustomers.com/vendor/rockerbox/testimonials
  - https://www.capterra.com/p/177885/Rockerbox-Attribution-Platform/
  - https://www.getapp.com/marketing-software/a/rockerbox-attribution-platform/
  - https://www.g2.com/products/rockerbox/reviews
  - https://www.vendr.com/marketplace/rockerbox
  - https://www.weareqry.com/blog/marketing-attribution-tools-northbeam-vs-rockerbox-vs-triple-whale
  - https://www.cometly.com/post/rockerbox-vs-other-attribution-tools
  - https://segmentstream.com/blog/articles/rockerbox-alternatives
  - https://improvado.io/blog/improvado-vs-rockerbox
  - https://www.axios.com/2025/02/26/doubleverify-acquire-rockerbox-85-million
  - https://doubleverify.com/company/newsroom/doubleverify-to-acquire-rockerbox-adding-outcome-measurement-and-attribution-capabilities-to-its-suite-of-performance-measurement-and-optimization-solutions
---

## Positioning

Rockerbox sells itself as "The Platform of Record for All Marketing Measurement," combining MTA, MMM, and incrementality testing in one product (per homepage). It targets mid-market and enterprise marketing teams who want an independent, vendor-neutral measurement layer that replaces "biased platform-specific attribution with objective truth" — particularly for brands running channels that platform pixels don't see (linear TV, OTT, podcasts, direct mail, sponsorships, influencer). Customer roster cited on homepage: Away Travel, Weight Watchers, Burton, Staples, Unilever, Loews, Greenlight, FIGS, Hum, Baublebar, Gorjana. Notably enterprise — there is no SMB/Shopify-merchant pitch on the marketing site. Acquired by DoubleVerify on March 13, 2025 for ~$82.6M ($85M headline); SMB roadmap unclear post-acquisition.

## Pricing & tiers

Pricing is **opaque**. Rockerbox's `/plans` page describes a modular product structure ("You select your mix of products, built to fit your company") with no list prices.

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Custom (modular) | Public floor: $2,000/mo (GetApp); typical mid-market annual contracts mid-5 to low-6 figures (Vendr) | User selects from six modules: Collect, Track, Export (Data Foundation) + Journey, Testing, MMM (Analysis) | Adding offline channels, MMM, or incrementality testing on top of base MTA |

Per Vendr's marketplace listing: "Enterprise pricing typically starts at $2,000+ per month… annual contracts for mid-market brands typically range from the mid-five figures to low-six figures, while enterprise deployments with significant marketing spend can reach mid-six figures or higher." Pricing factors include monthly marketing spend, number of channels, data volume (conversions/touchpoints/month), and managed-service vs. self-serve tier. No public free tier; GetApp lists "Free trial: Available" but the marketing site only offers "Talk to an Expert" / "Request a Demo."

## Integrations

**Sources (pull):** 100+ integrations (Rockerbox marketing copy claims "100+"; help-doc category claims "150+ custom integrations"). Coverage spans:
- Paid Search: Google Ads, Bing, Apple Search Ads
- Paid Social: Meta, TikTok, Pinterest, Snapchat, LinkedIn, Reddit
- Display & Native: The Trade Desk, DV360, Criteo, AdRoll
- Affiliate: Impact.com, Rakuten, CJ Affiliate
- Direct Mail: PebblePost, LS Direct, Postie
- Linear TV / OTT: MNTN, Tatari, Hulu, Comcast
- Streaming Audio / Podcasts: Spotify Ad Analytics, iHeartRadio, Pandora
- Mobile MMPs: AppsFlyer, Branch, Adjust, Singular
- Email / CDPs: Segment
- Ecommerce: Shopify

**Destinations (push):** Google Sheets, BigQuery, Snowflake, Redshift, ad-hoc CSV exports.

**Coverage gaps observed:**
- WooCommerce not listed in help-docs integrations index — only Shopify is named for ecommerce
- GSC (Google Search Console) and GA4 are not surfaced as named integrations on the public site or help docs (organic-search measurement appears to come from paid-search integrations + first-party pixel)
- Klaviyo not listed in observed integration index

## Product surfaces (their app's information architecture)

Per the redesigned UI (the "Reimagined" blog post identifies six top-level tabs) and the help-docs UI category, the IA inventory is:

- **Home / Performance at a Glance** — High-level "marketing health" snapshot: spend distribution by channel, trending ROAS and CPA over time, revenue trend
- **Data** — Data Foundation surfaces (Collect, Track, Export configuration)
- **Channels** — Per-channel performance views including Platform-Reported Performance (raw vendor metrics), Rockerbox De-duplicated View (first-party attributed), and Conversion Comparison (side-by-side)
- **Attribution / Cross-Channel Attribution Report** — Primary cross-channel comparison view; toggle attribution models (modeled multi-touch, even weight, last touch, first touch, full credit); time-period comparison; filter by customer attributes
- **Funnel** — Three sub-views: Marketing Paths (user-behavior view), Funnel Position, Channel Overlap
- **Experiments / Testing** — Incrementality testing setup and readouts (managed offering)
- **MMM** — Multiple sub-views: MMM Marketing Performance, MMM Channel Overview, MMM Scenario Planner, MMM Model Comparison, MMM Model Selection
- **Spend Benchmarks** — Industry/peer benchmarking on spend mix and trends ("how companies across multiple sizes and industries are varying their channel spend over time")
- **TV / OTT-specific reports** — Results "by geo, time or program" for linear/OTT log-file ingestion
- **Saved Views** — User-saved filter combinations across most reporting views (excludes search terms, breakdown customizations, column edits)

T3 expectation is 2-3 surface breakdowns; Rockerbox actually has substantial IA depth (~10 distinct surfaces) but public UI documentation is shallow on visual specifics.

## Data they expose

### Source: Shopify
- Pulled: Conversions/orders via Rockerbox pixel + direct Shopify integration (revenue, conversion events). No product-level COGS/margin handling surfaced in public docs.
- Computed: First-party attributed revenue (deduplicated across channels), CPA, ROAS, blended ROAS, conversion paths
- Attribution windows: Configurable; specific window options not detailed publicly

### Source: Meta Ads
- Pulled: Campaign/ad-set/ad-level spend, impressions, clicks, platform-reported conversions
- Computed: Modeled multi-touch attributed conversions, deduplicated revenue, fractional credit per touchpoint via logistic-regression MTA model. Three user cohorts feed the model: "Users exposed to marketing who converted / Users exposed to marketing who didn't convert / Users who converted without marketing touchpoints" (MTA help doc).

### Source: Google Ads
- Pulled: Same campaign/ad-set/ad-level performance + spend
- Computed: Same MTA-credit model. Branded paid-search adwords tied to podcast show/host names "get reclassified as podcast touchpoints" (podcast help doc) — interesting cross-channel reclassification logic.

### Source: TV / OTT
- Pulled: Log-level reports — "Time of airing, Media outlet (e.g., BBC America), Program name (e.g., Planet Earth), DMA for linear ads, Full impression log files for OTT platforms"
- Computed: User-level conversion paths tying TV airings to converters; results "by geo, time or program"; reruns models after TV log ingest to compute marketing lift

### Source: Podcasts (Sponsorships)
- Pulled via six methods: promo codes, vanity URLs, post-purchase HDYHAU surveys, show-notes URLs, branded paid-search reclassification, and direct partners (Spotify Ad Analytics)
- Computed: Blended CPA across podcast spend + multi-touch attribution; "Reporting on Podcast and Influencer touchpoints in a cross-channel path to conversion with a blended CPA"

### Source: Direct Mail / Influencer / Affiliate
- Pulled: Spend feeds + conversion signals via partner integrations (PebblePost, LS Direct, Postie for DM; Impact.com, Rakuten, CJ for affiliate)
- Computed: Multi-touch credit alongside paid digital; influencer view supports "total spend, including discounts offered" and "true CPA and ROAS of purchases driven by influencers"

### Source: GA4 / GSC
- Not listed as named integrations in public docs. Notable absence given Nexstage's six-source thesis includes both.

### Attribution windows / lookbacks
- Specific window/lookback values not published in the help-doc article on MTA. Article notes new customers see "artificially shortened conversion timeframes initially, as historical touchpoints may not be captured before implementation."

## Key UI patterns observed

### Home / Performance at a Glance
- **Path/location:** Top-level "Home" tab (sidebar/top-nav), default landing page
- **Layout (prose):** "A quick snapshot of your marketing health" (Reimagined blog). Top of page exposes a filters strip with controls for: filtered tiers, new vs. repeat user segmentation, attribution-type selection, and date range. Below the filter bar, the page shows spend distribution across channels, trending ROAS and CPA over time (line/trend charts), and revenue change over time. The home-page help doc emphasizes "blended ROAS, CPA trends, and spend by channel at a glance."
- **UI elements (concrete):** Trending lines for CPA and ROAS displayed alongside a spend visualization. Saved-views feature lets users persist filter selections (but not column edits or breakdown customizations). The combination is positioned as a diminishing-returns detector ("when you are scaling spend to identify changes in CPA/ROAS in relation to spend").
- **Interactions:** Filter changes, attribution-type toggle, date-range adjustments. Save view to favorites.
- **Metrics shown:** Blended ROAS, CPA, revenue, spend by channel, trend over time
- **Source/screenshot:** UI details from https://help.rockerbox.com/article/aij61vd747-performance-at-a-glance-rockerbox-home-page and https://www.rockerbox.com/blog/rockerbox-reimagined-a-deep-dive-on-the-new-and-updated-functionality. No public screenshot available.

### Cross-Channel Attribution Report
- **Path/location:** Attribution tab → Cross-Channel Attribution Report
- **Layout (prose):** Per Rockerbox's "Reimagined" post, the Attribution Report is the primary cross-channel comparison surface. Users can "see which marketing channels are most effective at driving purchases or other actions" and "customize what you see by toggling between attribution models and filtering by customer attributes." Includes a "Time Period Comparison" feature. The structure pairs Platform-Reported Performance with Rockerbox De-duplicated View and a Conversion Comparison side-by-side analysis — three named sub-views that explicitly contrast platform-reported vs. first-party attributed numbers.
- **UI elements (concrete):** Attribution-model toggle with five named options: "modeled multi-touch, even weight, last touch, first touch, full credit." Trend graphs combined with granular ad-level breakdowns. Customer-attribute filters (new vs. repeat).
- **Interactions:** Switch attribution model in-place; compare time periods; column customization; drill from channel down to ad level.
- **Metrics shown:** Spend, impressions, clicks, conversions (platform-reported), conversions (Rockerbox-modeled), CPA, ROAS, attribution-model breakdowns
- **Source/screenshot:** https://www.rockerbox.com/blog/rockerbox-reimagined-a-deep-dive-on-the-new-and-updated-functionality. No public screenshot captured.

### Marketing Paths (User Behavior View)
- **Path/location:** Funnel tab → Marketing Paths
- **Layout (prose):** Cohorts users who converted in a chosen window and visualizes "the most common paths to conversion for new and returning users." Shows touchpoints for each cohort across channels, including touchpoints that occurred outside the selected window. Surfaces "the highest earning paths and the paths with high revenue but low volume" for budget-reallocation decisions.
- **UI elements (concrete):** Public docs do not describe whether the visualization is a Sankey, an alluvial diagram, or a path-list table. No screenshot publicly available. New customers may see "artificially shortened" paths early on.
- **Interactions:** Time-frame selection; new vs. returning user filter; cohort selection
- **Metrics shown:** Path frequency, path revenue, path conversion volume, time-to-convert
- **Source/screenshot:** https://help.rockerbox.com/article/6l82ckvtmf-user-behavior-view-customers-paths — UI details not available; only feature description seen on help doc.

### Funnel Position
- **Path/location:** Funnel tab → Funnel Position
- **Layout (prose):** Two visualization modes. (1) "Channel Mix by Funnel Stage": stacked or column visualization where each column (First / Middle / Last touch) sums to 100% vertically — showing the share each channel holds of all first/middle/last touches in the period. (2) "Channel Role Distribution": each row sums to 100% horizontally — showing what percentage of a single channel's touchpoints occur at first, middle, or last position. Excludes single-touchpoint paths and Direct conversions.
- **UI elements (concrete):** Three named buckets — Beginning, Middle, End. Vertical-100% mode and horizontal-100% mode toggle. Visualization type (bar, area, table) not specified in public docs.
- **Interactions:** Toggle between the two normalization modes; date range; new/returning filter
- **Metrics shown:** Touchpoint share by funnel stage per channel; channel-internal distribution across stages
- **Source/screenshot:** https://help.rockerbox.com/article/qvsgzja6rn-funnel-position-view — UI visualization specifics not described in public source.

### Channel Overlap
- **Path/location:** Funnel tab → Channel Overlap
- **Layout (prose):** Helps users "assess what other marketing channels users are interacting with when they have a given channel on their path to conversion." Surfaces channel co-occurrence data to identify redundancy or complementarity.
- **UI elements (concrete):** Public help-doc article does not describe the interface — column names, layout, and visualization type are not disclosed. UI details not available — only feature description seen on marketing/help pages.
- **Interactions:** Channel selection (focal channel); time range
- **Metrics shown:** Co-occurrence rates between channels; whether overlap "is helping or hurting you"
- **Source/screenshot:** https://help.rockerbox.com/article/y3rqyeh61z-channel-overlap-overview — interface details not publicly documented.

### Channel Performance: Platform-Reported vs. Rockerbox De-duplicated vs. Conversion Comparison
- **Path/location:** Channels tab
- **Layout (prose):** Three sub-views explicitly named: (1) Platform-Reported Performance — raw vendor metrics ("displays metrics as reported by advertising platforms"); (2) Rockerbox De-duplicated View — first-party attributed performance with deduplication applied; (3) Conversion Comparison — side-by-side analysis of conversion data across the two methodologies. This is the closest analog to a multi-source-badge approach: an explicit Platform vs. First-party vs. Comparison structure baked into the IA.
- **UI elements (concrete):** Toggle between the three named views. Attribution-method toggle (multi-touch / even weight / last / first / full credit). Trend graphs + granular ad-level breakdown.
- **Interactions:** Switch view; toggle attribution model; drill to ad level
- **Metrics shown:** Platform-reported conversions/revenue, Rockerbox-modeled conversions/revenue, deltas/comparison
- **Source/screenshot:** https://www.rockerbox.com/blog/rockerbox-reimagined-a-deep-dive-on-the-new-and-updated-functionality, https://help.rockerbox.com/category/q2bwxh2tak-rockerbox-ui

### MMM Scenario Planner
- **Path/location:** MMM tab → Scenario Planner
- **Layout (prose):** Step-driven flow: configure objective, set constraints, set optional budget cap, generate forecast. "A user-friendly interface guides you through setting objectives, constraints, and budget allocations" (Rockerbox blog). Constraint default: 30% per-channel change tolerance; user-selectable steps at 15%, 30%, 50%, 100%. Output: model-recommended channel mix with projected revenue/efficiency.
- **UI elements (concrete):** Constraint sliders/selector with discrete preset values (15/30/50/100%); per-channel constraint refinement; "Generate Forecast" CTA produces a plan view comparing baseline vs. proposed allocation
- **Interactions:** Set global constraint, override per channel, set budget cap (optional), Generate Forecast
- **Metrics shown:** Proposed channel-level spend, projected revenue, projected ROAS, delta vs. baseline
- **Source/screenshot:** https://help.rockerbox.com/article/x1yz9tmm69-mmm-scenario-planner

### MMM Model Comparison / Model Selection
- **Path/location:** MMM tab
- **Layout (prose):** Distinct surfaces for comparing different MMM model runs and selecting which model is "active" for downstream views. UI specifics not disclosed publicly.
- **Source/screenshot:** https://help.rockerbox.com/category/q2bwxh2tak-rockerbox-ui — UI details not available in public sources.

### Spend Benchmarks
- **Path/location:** Top-level Spend Benchmarks tab
- **Layout (prose):** Industry/peer-benchmarking surface showing "how companies across multiple sizes and industries are varying their channel spend over time." Distinct from the per-account dashboards — this is cohort/peer data.
- **UI elements (concrete):** Not described in public sources beyond the feature framing
- **Source/screenshot:** https://www.rockerbox.com/blog/rockerbox-reimagined-a-deep-dive-on-the-new-and-updated-functionality

### TV / OTT Report
- **Path/location:** Likely under Channels or Attribution; specific tab placement not confirmed
- **Layout (prose):** TV/OTT-specific results viewable "by geo, time or program." Ingests log-level data from Tatari, Hulu, DV360, Comcast and ties airings to user-level conversion paths.
- **Metrics shown:** Spend, airings, impressions, attributed conversions/revenue per program/network/DMA
- **Source/screenshot:** https://www.rockerbox.com/tv-and-ott — described in marketing copy; UI not pictured publicly.

## What users love (verbatim quotes, attributed)

Note: G2 review pages return 403 to programmatic fetch; Capterra has only one verified review; Trustpilot/Reddit DTC threads with verbatim Rockerbox quotes are scarce. Limited reviews available.

- "I've really enjoyed working with the team at Rockerbox. They've been able to optimize towards any KPI we throw that them, and have shown great DR results using their recency technology." — Becca Freeman, Digital Marketing Manager, Baublebar (FeaturedCustomers, vendor-published testimonial, undated)
- "[Rockerbox] has allowed us to feel confident in scaling and seeing how our dollars influence the entire customer journey. We have so much more intelligence in terms of optimizing campaigns holistically and knowing where these campaigns fit within the customer journey." — Kyle Brucculeri, VP eCommerce, Gorjana (FeaturedCustomers, vendor-published testimonial, undated)
- "The customer service at Rockerbox is top notch...their team went out of their way to build [a feature] within 2 days of [the] ask." — Mike W., Digital Acquisition Manager, Apparel & Fashion (Capterra review, December 7, 2018; rated 4.0/5 overall, 5.0/5 ease of use, 9/10 likelihood to recommend)
- "Annihilates everything else in the same price range and being able to bug @saralivingston with questions is a solid value." — G2 reviewer (cited in third-party search summaries; original G2 page returns 403)
- "Must have tool for making informed marketing decisions" — G2 reviewer cited by Rockerbox (G2 High Performer blog post, attributing to a reviewer who praised non-click-based Facebook attribution)
- "Simply the best attribution platform" — G2 reviewer cited by Rockerbox (G2 High Performer blog, praising Rockerbox staying current with new ad platforms)
- "Critical infrastructure for smarter marketing" — G2 reviewer cited by Rockerbox (G2 High Performer blog, praising Hosted Snowflake tables for raw-data access without ETL)

Aggregate ratings (third-party, dates as labeled):
- G2 (per third-party summaries): 93% 5-star, 97% likelihood to recommend, 98% quality-of-support, 84% ease-of-use
- Capterra: 4.0/5 (n=1)
- GetApp: 4.0/5 overall, 4.0/5 ease of use, 4.0/5 features, 0.0/5 value for money, 0.0/5 customer support, 1.0/10 likelihood to recommend (n=1; thin)

## What users hate (verbatim quotes, attributed)

Limited reviews available; most critical commentary comes from third-party comparison articles rather than verbatim user reviews.

- "The 'bucket' terminology they use could be confusing to some, but nothing that couldn't be learned." — Mike W., Digital Acquisition Manager, Apparel & Fashion (Capterra, December 7, 2018)
- "Limited visibility into how Rockerbox assigns attribution credit." — SegmentStream third-party review summary citing G2 community discussions (2026), describing recurring complaints about model transparency
- "Expensive for mid-market companies" / "better suited for larger organizations" — third-party summary of G2 review themes (Cometly, 2026)
- "The interface is pretty complex and may be hard to use for smaller businesses and advertisers." — third-party comparison article summarizing user feedback (Cometly, 2026)
- "Implementation involves mapping data sources, configuring channel groupings, and integrating tracking across platforms. Ongoing use demands someone who can interpret multi-touch attribution outputs." — SegmentStream alternatives article (2026), characterizing the analyst-dependency complaint
- "Attribution dashboards that show performance without recommending or executing budget changes leave teams stuck in a weekly spreadsheet cycle." — SegmentStream alternatives article (2026), characterizing the "measurement without optimization" complaint
- "More onboarding investment, more data sophistication required, and a steeper learning curve." — QRY comparison article (2026), Rockerbox cons section

Caveat: The strongest negative signal is paraphrased rather than quoted from primary review sources. Direct G2 review text was inaccessible (403). Reddit threads about Rockerbox are sparse — DTC-Twitter and DTC-Reddit chatter skews to Triple Whale and Northbeam. The thin direct-review corpus itself is a finding (Rockerbox doesn't generate the volume of public review chatter that consumer-facing SMB tools do).

## Unique strengths

- **Three methodologies in one platform.** MTA + MMM + incrementality testing under one roof, with explicit MMM Model Comparison and Model Selection surfaces. Most SMB attribution tools ship MTA only.
- **Hard-to-track channel coverage is a real moat.** Linear TV log-file ingestion (DMA, program, network, time-of-airing); OTT impression logs; podcast attribution via six discrete capture methods (promo codes, vanity URLs, HDYHAU surveys, show-notes links, branded-search reclassification, direct partner integration); direct mail (PebblePost, LS Direct, Postie); influencer/sponsorship spend unification with payout-structure handling. Few competitors publish this depth.
- **Explicit Platform-Reported vs. First-Party de-duplicated comparison view** baked into the IA (Channel Performance has three named sub-views: Platform-Reported, Rockerbox De-duplicated, Conversion Comparison). This is the most direct multi-source-badge analog observed in the category.
- **Five-way attribution-model toggle** (modeled multi-touch / even weight / last touch / first touch / full credit) on the same screen, enabling side-by-side methodology comparison without separate reports.
- **Funnel Position dual-normalization** (vertical 100% by stage vs. horizontal 100% by channel) — two distinct ways to read the same funnel data.
- **Hosted Snowflake tables / data-warehouse export** to BigQuery, Snowflake, Redshift, Google Sheets — enterprise-grade data-out story; cited by reviewers as differentiated.
- **Spend Benchmarks** peer/industry cohort view — sits outside per-account data; rare among SMB attribution tools.
- **MMM Scenario Planner with explicit constraint controls** — preset constraint steps (15/30/50/100%) and per-channel overrides, plus optional budget cap; closer to a planning tool than a pure-reporting MMM.

## Unique weaknesses / common complaints

- **Enterprise-only target market.** Implementation, pricing, and ongoing analyst burden are sized for $1M+ annual marketing-spend brands with dedicated analytics resources. Multiple third-party sources flag SMB unsuitability.
- **Opaque pricing.** No public list pricing. Mid-market customers report needing custom quotes that commonly negotiate "15-30% below initial quotes for multi-year commitments" (Vendr).
- **Measurement without optimization.** Critique pattern: dashboards report; teams still export to spreadsheets and adjust bids manually. No native bid-adjustment or platform write-back observed.
- **Model transparency gaps.** Recurring complaint that users can't easily audit "how was that number calculated?" for the MTA logistic-regression credit assignment.
- **DoubleVerify acquisition uncertainty.** DoubleVerify is an ad-verification / brand-safety enterprise vendor; integration of Rockerbox into DV's portfolio raises questions about whether DTC-focused roadmap continues. AdExchanger reported the deal was announced alongside a "disappointing Q4" — investor pressure may shape DV's choices for Rockerbox.
- **Steeper learning curve / "complex" UI** flagged in multiple comparison articles. "Bucket" terminology is one specific friction example from a Capterra reviewer.
- **No native mobile app.** Web-responsive only — at odds with DTC-operator expectation of mobile dashboards (Triple Whale-style).
- **Thin public review corpus.** G2 page returns 403 to programmatic access; Capterra has 1 review since 2018; SourceForge has 0; Reddit DTC chatter is sparse. For a 2013-founded company this is unusually low volume — indicates enterprise sales-led GTM rather than self-serve.

## Notes for Nexstage

- **Three named comparison views (Platform-Reported / Rockerbox De-duplicated / Conversion Comparison) are a direct precedent for our six-source-badge thesis.** Rockerbox makes the Platform vs. First-party split a first-class IA citizen — worth studying their copy ("Stop tracking duplicate conversions, get a single source of truth for conversion counts across marketing channels") for how to frame multi-source presentation.
- **Five-way attribution-model toggle on a single screen** (multi-touch / even weight / last / first / full credit) is more granular than what most SMB tools surface. If we ship attribution-model toggles, this is the maximalist reference point.
- **Funnel Position's two normalization modes (vertical-100% by stage vs. horizontal-100% by channel)** is a clean UX pattern — same data, two readings, single toggle. Cheap to implement; high analytical leverage.
- **TV/OTT and podcast attribution are out-of-scope for Nexstage's SMB Shopify/Woo focus**, but Rockerbox's six-method podcast capture (promo code, vanity URL, HDYHAU survey, show notes, branded-search reclassification, direct partner) is the canonical reference if we ever extend offline. The branded-search-to-podcast reclassification logic is particularly interesting — it implies cross-channel touchpoint reassignment, which we don't currently do.
- **GA4 and GSC are conspicuously absent from Rockerbox's named integration list.** They expect customers to ship organic-search measurement via paid-search adapters + first-party pixel. This is a real gap we cover and Nexstage's six-source pitch directly fills.
- **Their /plans page is a "modular product chooser" rather than a tier ladder** — interesting GTM pattern but ill-suited to SMB self-serve. Pricing opacity itself is a competitive opening for us.
- **Saved Views excludes column edits and breakdown customizations** — explicit limitation called out in their own help docs. If we ship saved views, including column state in the saved view payload would beat them on a small-but-noticed point.
- **MMM Scenario Planner constraint presets (15/30/50/100%) with per-channel overrides** is a good UX shortcut for budget-reallocation tools — discrete steps are easier to reason about than free-form sliders.
- **Spend Benchmarks (peer/industry cohort view) is a distinct surface, not an overlay on the dashboard** — note the IA decision: benchmarks live in their own tab. Worth weighing if/when we ship benchmarks: own-tab vs. inline comparison.
- **Acquisition risk is real for SMBs evaluating Rockerbox.** The DV deal closed March 2025 (~$82.6M); 50 Rockerbox employees joined a 1,000-person ad-verification org. Multiple commentators explicitly question whether DTC roadmap survives. SMB merchants who picked Rockerbox for its independence are a churn-vulnerable cohort.
- **Customer roster (Away, FIGS, Burton, Weight Watchers, Unilever, Loews) is enterprise-skewed.** Rockerbox is not a meaningful direct competitor for Nexstage's $1M-$10M GMV Shopify/Woo SMB target; treat as a feature/UX reference point and an "if they grow up they leave us for…" future-state competitor, not a head-to-head.
- **No screenshots captured.** All UI descriptions sourced from marketing copy + help-doc text. G2 returns 403; the marketing site uses CDN-hosted images that aren't reliably extractable. Worth a manual demo-request if deeper UI fidelity becomes important downstream.
