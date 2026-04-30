---
name: Wicked Reports
url: https://wickedreports.com
tier: T2
positioning: First-party multi-touch attribution platform for ecommerce/subscription brands ($5M-$50M) that weights credit by customer LTV instead of last-click, replacing platform-native ad reporting.
target_market: eCommerce brands $5M-$50M revenue; digital agencies (5-50 clients); subscription/info-product/repeat-purchase brands; Shopify-primary plus BigCommerce/Stripe; US-centric.
pricing: From $499/mo (Measure tier) — scales by annual revenue band ($0-$2.5M shown publicly); add-ons $199/mo each (Advanced Signal, 5 Forces AI); Enterprise from $4,999/mo
integrations: Shopify, BigCommerce, Stripe, Amazon, Meta Ads, Google Ads, Microsoft Ads, TikTok, Pinterest, Snapchat, Klaviyo, ReCharge, Zapier, Webhooks, generic API/CRM
data_freshness: daily (multiple syncs/day reported by reviewers; 1-2 hour lag during peaks)
mobile_app: no — web-responsive only (no iOS/Android app observed in public listings)
researched_on: 2026-04-28
sources:
  - https://wickedreports.com
  - https://www.wickedreports.com/pricing
  - https://www.wickedreports.com/integrations
  - https://www.wickedreports.com/brand
  - https://www.wickedreports.com/funnel-vision
  - https://www.wickedreports.com/the-attribution-time-machine
  - https://www.wickedreports.com/features-overview
  - https://www.wickedreports.com/wicked-recharge
  - https://www.wickedreports.com/testimonials
  - https://www.wickedreports.com/blog/the-wicked-guide-to-marketing-attribution-models
  - https://help.wickedreports.com/guide-to-cohort-and-customer-lifetime-value-reporting
  - https://www.cuspera.com/products/wicked-reports-x-4588
  - https://www.cometly.com/post/wicked-reports-vs-other-attribution-tools
  - https://marketingtoolpro.com/wicked-reports-review/
  - https://www.smbguide.com/review/wicked-reports/
  - https://www.flyingvgroup.com/wicked-reports-vs-hyros/
  - https://www.g2.com/products/wicked-reports/reviews
---

## Positioning

Wicked Reports sells itself as the attribution system "that finds new customers" — its homepage tagline reframes attribution away from total ROAS reporting and toward separating new-customer acquisition from retargeting noise. The platform is pitched not as a dashboard but as "an operating system for new customer acquisition" (Wicked brand page) targeting $5M-$50M ecommerce brands and agencies that have outgrown platform-native attribution but aren't ready for enterprise MMM. Its distinctive angle inside the attribution category is LTV-weighted cohort credit: instead of judging a campaign on first-order ROAS, Wicked re-prices that campaign as the underlying cohort rebuys, subscribes, or churns — with patents-pending applied to "subscription revenue attribution" specifically.

## Pricing & tiers

Pricing is published openly for the $0-$2.5M annual revenue band. Higher revenue bands are quoted on request and the page makes clear price scales with revenue band, not seats.

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Measure | $499/mo | FunnelVision (pre-built views), Cohort & LTV reports, lifetime lookback/lookforward, major ad platform + standard cart + standard CRM integrations | Need API/outbound API or international/multi-currency |
| Scale | $699/mo | Everything in Measure + API integrations (CRM/cart) + Outbound API + currency conversion + international time-zone data loading | Want bundled 5 Forces AI + Advanced Signal instead of paying $199 + $199 add-ons |
| Maximize | $999/mo | Everything in Scale + 5 Forces AI included + Advanced Signal (Meta CAPI) included + Custom Conversions + Amazon Revenue Integration | Cross $2.5M annual revenue / need enterprise SLA |
| Enterprise | from $4,999/mo | Everything in Maximize + priority support & implementation + enterprise terms / security reviews + optional dedicated servers + custom SLA | n/a |
| Add-on: Advanced Signal (Meta CAPI) | $199/mo | Available on Measure & Scale only | Already covered in Maximize |
| Add-on: 5 Forces AI | $199/mo | Available on Measure & Scale only | Already covered in Maximize |

Note: Third-party listings (cometly.com, others) cite "starts around $250/mo," which appears to refer to legacy/older pricing. The pricing page as of April 2026 starts at $499/mo (Measure). One reviewer source (marketingtoolpro.com) referenced $247/mo as "starting" but this is inconsistent with the live pricing page.

## Integrations

**Pulled from (sources):**
- Ad platforms: Meta (Facebook + Instagram), Google Ads, Microsoft Ads (Bing), TikTok, Pinterest, Snapchat
- Carts / commerce: Shopify, BigCommerce, Stripe, Amazon
- Subscription: ReCharge (with patents-pending subscription attribution)
- CRM / email: Klaviyo (named first-class), HubSpot (cited in third-party reviews), generic CRM via API
- Custom: REST API, Zapier, Webhooks

**Pushed to (destinations):**
- Meta CAPI (via "Advanced Signal" add-on) — sends segmented new-customer purchase events
- Google Ads Conversion API — feeds conversion data with click value updates over a 90-day window
- Outbound API (Scale tier and above)

**Coverage gaps observed (vs. Nexstage source set):**
- **No Google Search Console (GSC) integration** — not mentioned anywhere on the integrations page or features overview
- **No GA4 integration** — Wicked positions itself as a replacement for GA-style analytics rather than augmenting GA4; not pulled
- WooCommerce not listed on the integrations page (third-party reviews mention WooCommerce works "via integrations" — likely API/Zapier, not native)

**Required vs optional:** Page states "Wicked Reports integrates directly with your Shopping Cart, Ad platforms, and CRM platforms" — implying at least one source from each of those three categories is required for the attribution model to function. UTM tagging on all paid links is functionally required (multiple reviewers note this as a friction point).

## Product surfaces (their app's information architecture)

The product surfaces below are inferred from feature pages, the help center, and third-party walkthroughs. UI screenshots are not directly accessible without a paid login; descriptions are reviewer prose plus marketing-page illustrations.

- **Customer Cohort Report** — answers "what is the LTV of the cohort I acquired in month X, broken down by attributed source?"
- **New Lead Cohort Report** — answers "which channels acquire opt-ins/leads that later convert, vs. cheap leads that never buy?"
- **Product Cohort Report** — answers "which first-product purchase yields the highest downstream LTV?" (uses "product purchase month" as cohort variable; integration-dependent)
- **Customer LTV Report** — legacy report listing "all customers for a given time range with their accumulated customer lifetime value" plus attributed marketing credit sources
- **First Click ROI Attribution Report** — answers "if I credit only the first click, which campaigns are positive ROI?"
- **Last Click ROI Attribution Report** — answers same question with last-click model
- **Linear ROI Attribution Report** — equal-credit MTA across touchpoints, reconciled to revenue
- **Full Impact ROI Attribution Report** — magnifies credit across all touchpoints (does NOT reconcile revenue — a "what influenced this sale" view)
- **New Lead ROI / ReEngaged Lead ROI Reports** — split top-of-funnel cold-traffic credit from list-based re-engagement credit
- **FunnelVision** — segments clicks into TOF/MOF/BOF; shows side-by-side comparison of Wicked-attributed ROAS vs. Facebook-reported ROAS per campaign
- **Attribution Time Machine** — infinite lookback/lookforward; lets users dial custom view-through impact and lookback windows on the fly
- **5 Forces AI dashboard** — weekly Scale / Chill / Kill verdict per campaign with justification text (add-on or Maximize tier)
- **Advanced Signal (Meta CAPI) configuration** — controls for new-customer-only event sending to Meta
- **Wicked Coach** — automated assistant surfacing growth opportunities and ad-spend waste
- **Custom Conversions builder** — define conversion steps between first click and sale (Maximize tier)
- **Auto-Tagging configuration** — UTM creation across ad platforms
- **Customer journey detail (per-customer drilldown)** — clickable customer record showing entire tracked journey: inbound link clicks with timestamps, attribution credits, marketing contributions to LTV
- **Custom Marketing Attribution Models** — "build new models from scratch" + customize existing model parameters
- **Email Reporting / Scheduled Reports** — automated ROI & LTV email reporting cadence
- **"Netflix Style Easy Button"** — a curated browse-style entry into pre-built attribution analysis "goals" / use cases

That is roughly 18 surfaces — high for the category, reflecting Wicked's multi-report rather than single-dashboard philosophy.

## Data they expose

### Source: Shopify (and BigCommerce / Stripe / Amazon)
- **Pulled:** orders, order IDs, line items, customers, repeat-purchase data; CRM-record matching by email; refunds inferred via "Flexible Revenue Accounting" (gross vs. net payments view)
- **Computed:** new vs. repeat customer split per order; nCAC (new customer acquisition cost); customer LTV across infinite lookback; cohort retention; "PayPal Synthetic Tracking" (claimed gap-fill for PayPal orders); "Facebook Triple-Send" (server-side event redundancy); product-level cohort LTV
- **Attribution windows:** infinite lookback and lookforward (the "Attribution Time Machine"); explicitly contrasts with Facebook's 7-day default

### Source: Meta Ads
- **Pulled:** campaign / ad set / ad spend, impressions, clicks, conversions, ROAS as Meta reports it
- **Computed:** Wicked-attributed revenue at campaign/ad-set/ad/creative level; new-customer-only ROAS (vs. blended); side-by-side comparison columns of Wicked-attributed ROAS vs. Facebook-reported ROAS in FunnelVision; "Customized Meta View-Through Conversion Impact" — user-tunable how much view-through inflates ROAS/CAC
- **Pushed:** new-customer purchase events via Conversion API (Advanced Signal add-on)

### Source: Google Ads
- **Pulled:** spend, impressions, clicks, conversions
- **Computed:** Wicked-attributed ROAS, first-click vs. last-click splits
- **Pushed:** Google Ads Conversion API — feeds conversion data with click-value updates over a rolling 90-day window as traffic converts

### Source: Klaviyo (and other CRM)
- **Pulled:** opt-ins, list membership, email events tied to customer records
- **Computed:** New Lead vs. ReEngaged Lead split; cold-traffic optin attribution that "captures Klaviyo email optins that have delayed Shopify sales conversions"; opt-in to first-purchase delay measurement

### Source: ReCharge (subscription-specific)
- **Pulled:** new and recurring subscription payments
- **Computed:** "Automatic ROI, ROAS, and LTV calculations from ReCharge revenue"; new vs. repeat payment distinction; continuous metric refresh as subscriptions rebill (so a campaign that looked unprofitable on first order can flip to profitable as cohort rebills); churn-aware logic
- **Differentiator quote (Wicked):** "Continuous update of revenue and ROI when subscriptions rebill" reveals "cold traffic top of the funnel winning campaigns that look unprofitable with the delayed subscription rebills"

### Source: Pinterest, TikTok, Snapchat, Microsoft Ads
- Spend / clicks / conversions pulled; surfaced through the same attribution-model reports as Meta/Google. Less marketing emphasis.

### Source: Amazon
- Revenue integration ("Amazon Revenue Integration") gated to the Maximize tier — implies basic order/revenue pull, not a full Amazon Ads attribution model.

## Key UI patterns observed

**Caveat:** Wicked Reports is paywalled — there is no free tier, public sandbox, or interactive demo. Most UI observations below are reconstructed from marketing pages, the help center, third-party reviews (marketingtoolpro.com, smbguide.com, cuspera.com), and the testimonials page. Where a screen could not be observed in detail it is flagged.

### Customer Cohort / LTV Report

- **Path/location:** Reports section (one of four cohort-family reports: Customer Cohort, New Lead Cohort, Product Cohort, Customer LTV)
- **Layout (prose):** Cohort matrix — rows are acquisition months, columns are subsequent months of accumulated revenue per cohort. Each cell shows lifetime value built up by that cohort by that month. Above the matrix is a filter strip allowing breakdown by "attributed source, campaign, ad, email, and targeting that generated the new lead or customer" (help center, verbatim). Individual customer records are clickable from related views.
- **UI elements (concrete):** Help-center copy describes a cohort matrix and clickable customer drilldown but does not specify exact column count, color encoding, or whether deltas are shown. Marketing-page illustration on the ReCharge integration page references a "customer cohort report visualization" with new vs. repeat distinction.
- **Interactions:** Filter cohort by attributed source / campaign / ad / email / targeting; click a customer record to open the full journey detail; schedule the report via email (limited reviewer description).
- **Metrics shown:** Cumulative customer LTV per cohort over time; attributed marketing credit sources per cohort; subscription rebill revenue (when ReCharge integrated).
- **Source:** https://help.wickedreports.com/guide-to-cohort-and-customer-lifetime-value-reporting ; https://www.wickedreports.com/wicked-recharge

### FunnelVision

- **Path/location:** Top-level feature; user enters via "Netflix Style Easy Button" or directly from the attribution model menu.
- **Layout (prose):** Full-funnel view that "automatically segment[s] clicks by the top, middle and bottom of your funnel" (verbatim, funnel-vision page). A core visual claim is **side-by-side comparison columns of Wicked-attributed ROAS vs. Facebook-reported ROAS**, per campaign — a direct two-source compare that's analogous in spirit to Nexstage's multi-source-badge thesis.
- **UI elements (concrete):** TOF / MOF / BOF segmentation labels per click; "Cold Traffic" tag for conversions occurring more than 7 days before sale; toggleable lookback windows; toggleable view-through impact slider.
- **Interactions:** "Customized Meta View-Through Conversion Impact" is user-adjustable on the fly. Custom Conversions definable mid-funnel. Compare-mode toggle to overlay Facebook's reported numbers.
- **Metrics shown:** ROAS (Wicked-attributed), ROAS (Facebook-reported), spend, conversions, CAC at each funnel stage, cold-traffic ROAS.
- **Source/screenshot:** https://www.wickedreports.com/funnel-vision (illustration referenced as "Funnel Vision.svg" in source); details from features-overview page.

### Attribution Time Machine

- **Path/location:** Underlies all attribution reports; surfaced as its own marketing concept.
- **Layout (prose):** Per-visitor journey reconstruction — "displays the inbound marketing link clicks and timestamps behind every identified visitor" (Attribution Time Machine page, verbatim). When a conversion occurs, all stored prior clicks are stitched to the order ID and email profile.
- **UI elements (concrete):** Timestamped click history per customer/order; full audit trail tying revenue back to specific OrderIDs and Email profiles. Marketing copy emphasizes the audit-proof angle: "we report only the truth we can verify and back up with your actual OrderIDs and Email profiles."
- **Interactions:** Customizable lookback / lookforward window settings; "delayed conversion credit even when campaign has ended."
- **Metrics shown:** Click-by-click journey, attribution timestamps, OrderID matching, email-profile matching.
- **Source:** https://www.wickedreports.com/the-attribution-time-machine

### 5 Forces AI verdict view

- **Path/location:** Add-on on Measure/Scale, included on Maximize.
- **Layout (prose):** Weekly verdicts surface per campaign — three-state output: **Scale / Chill / Kill** — each with justification text "you can defend" (verbatim, brand page).
- **UI elements (concrete):** Three-state pill or badge labeling per campaign (specific visual rendering not observed in public sources). nCAC threshold settings are user-defined inputs that drive the verdict.
- **Interactions:** Weekly cadence (recurring report); user-tunable nCAC thresholds.
- **Metrics shown:** nCAC vs. user threshold, recommended action, justification text.
- **Source:** https://www.wickedreports.com/brand

### Per-customer journey drilldown

- **Path/location:** Reachable from cohort reports and the customer LTV report by clicking a record.
- **Layout (prose):** Vertical timeline-style view showing "their entire tracked customer journey" (help center) including marketing attribution contributions to LTV. UI specifics not observable from public sources.
- **UI elements:** UI details not available — only feature description seen on the help-center page.
- **Source:** https://help.wickedreports.com/guide-to-cohort-and-customer-lifetime-value-reporting

### Attribution Models comparison view

- **Path/location:** Switchable from any ROI report.
- **Layout (prose):** Reviewer (marketingtoolpro.com) describes one-click model switching: "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time." Six models documented: First Click, Last Click, Linear, Full Impact, New Lead, ReEngaged Lead.
- **UI elements (concrete):** Per reviewers, "drag-and-drop dashboard tiles," "color-coded dashboards," "green for growth and red for issues" (marketingtoolpro.com).
- **Interactions:** One-click model swap; numbers refresh live.
- **Metrics shown:** ROAS, CAC, attributed revenue, conversions per campaign — same metric set, different model lens.
- **Source:** https://marketingtoolpro.com/wicked-reports-review/

### Auto-Tagging / UTM configuration

- **Path/location:** Setup section.
- **Layout:** UI details not available — only feature description seen.
- **Note:** Functionally required for tracking. "Wicked Reports is limited by the requirement of adding UTM codes to all advertising materials" (Cuspera review aggregator). Auto-Tagging exists to mitigate this for major ad platforms.
- **Source:** https://www.wickedreports.com/features-overview

### Email / scheduled reporting

- **Path/location:** Reports menu.
- **Layout (prose):** Reports are highly customizable based on filters set in the dashboard before scheduling; arrive in inbox at chosen cadence.
- **UI elements:** UI details not available.
- **Source:** https://www.wickedreports.com/blog/roi-reporting-emailed (referenced)

## What users love (verbatim quotes, attributed)

- "Wicked allows us to accurately eliminate the advertising which is not working or refine the conversion on another piece." — **Karen C, Owner / Marketing Strategist** (Cuspera aggregated review)
- "Being able to track engagement, visits, conversions and everything you can possibly imagine, across multiple platforms is something that we have been dreaming about for years." — **anonymous reviewer** (Cuspera)
- "We found it hard to rely on FB or Google ad platforms to accurately measure ROI since we had longer sales cycles. Wicked Reports offered us more accurate ROI on our ad spend, and now we see the impact through the attribution models." — **Mark D, Director of Paid Advertising** (smbguide.com review)
- "We needed a platform that can pull all data points in one centralized area for easy viewing. Wicked Reports offers exactly that. Plus, navigating through the reports is easy, and the recommendations are excellent in strategy building." — **Jenna G, Client Success Manager** (smbguide.com)
- "When we use the iOS tracking, we often miss data and it leads us to turning off marketing campaigns. However, with Wicked Reports, the multiple attribution data solves the missing data and we're able to see there were sales made." — **Michelle P, Agency Owner** (smbguide.com)
- "Color-coded dashboards make reviewing my performance simple and fast. Each section uses visual cues — like green for growth and red for issues." — **reviewer, marketingtoolpro.com** (2025)
- "Switching attribution models, like First Click and Time Decay, took one click. The numbers swapped in real time." — **reviewer, marketingtoolpro.com** (2025)
- "Wicked Reports allows us to optimize Facebook ads to those with the highest ROI and just not the cheapest lead. Nothing else on the market remotely compares. It's a total game changer!" — **Ralph Burns, Tier 11 CEO** (Wicked testimonials page)
- "[Got] 500% ROI from some of the traffic sources" when analyzing "lifetime value of customers over time." — **Henry Reith, Marketing Consultant** (Wicked testimonials page)
- "Customer service and support has been excellent." — **Depesh Mandalia, AdSignals CEO** (Wicked testimonials page)
- "Over the period we have been using Wicked, their support has gotten a TON better too." — **G2 reviewer** (paraphrased from G2 aggregated summary; not a fully verbatim direct review)

## What users hate (verbatim quotes, attributed)

- "The interface can feel overwhelming for newcomers." — **reviewer, marketingtoolpro.com** (2025)
- "Some data updates lagged by an hour or two" during peak periods. — **reviewer, marketingtoolpro.com** (2025)
- "Outdated user interface design" — **smbguide.com review** (limitations section, 2025)
- "Wicked Reports is limited by the requirement of adding UTM codes to all advertising materials." — **Cuspera aggregated review** (recurring complaints section)
- "There is a learning curve, though there is a lot of documentation and help available, with users still discovering new reports and charts even after years of use." — **G2 aggregated summary** (paraphrased from multiple reviews; 2026)
- "The user interface can be clunky, which may hinder navigation." — **G2 aggregated summary** (recurring complaint, 2026)
- "Some complaints revolve around the one-day-lag for the data whenever you launch a campaign." — **review aggregator, surfacing recurring user complaint**
- "The limitations of their API make automation challenging for some use cases, particularly with specific data columns like first and last click date." — **review aggregator**
- "Customer service during onboarding has received mixed reviews, and manual ad spend input is sometimes required for certain integrations." — **smbguide.com aggregated** (2025)
- "I wish the onboarding provided more step-by-step visual guides or tooltips." — **reviewer, marketingtoolpro.com** (2025)

Note: Capterra direct review page returned 404; G2 page returned 403; TrustRadius returned "not enough ratings." Direct verbatim review counts on those listings are limited. Most quotes above are from secondary review aggregators that themselves quote Wicked users; tertiary attribution flagged where applicable.

## Unique strengths

- **LTV-weighted attribution as a first-class concept, not a feature buried in a setting.** The Customer Cohort / Product Cohort / New Lead Cohort report family is the spine of the product. Reports re-attribute campaign credit *as cohorts rebuy or rebill* — so a campaign with a poor first-order ROAS can flip to "Scale" once 90 days of subscription rebills accumulate. No competitor in the SMB ecom category prices this as the headline feature.
- **Subscription/ReCharge attribution patents-pending.** "Continuous update of revenue and ROI when subscriptions rebill" — the only attribution platform in this tier that explicitly differentiates new-payment vs. recurring-payment events from ReCharge and re-prices campaigns accordingly.
- **Side-by-side Wicked-attributed vs. Facebook-reported ROAS in FunnelVision** — a direct two-source compare baked into the UI. Closest analog in the category to Nexstage's multi-source-badge thesis (though Wicked exposes only Wicked-vs-Platform, not the full 6-source set).
- **Infinite lookback / lookforward window** — explicitly contrasted against Facebook's 7-day default; the "Attribution Time Machine" stitches future clicks back to historical click data with full audit trail to OrderID + email.
- **Three-state weekly verdict (Scale / Chill / Kill) with justification text** — opinionated AI output rather than a recommendation feed; "you can defend" is the explicit framing.
- **Cohort breakdown by acquisition source** — users can filter cohorts by "attributed source, campaign, ad, email, and targeting that generated the new lead or customer" — i.e., LTV is sliced by what acquired the customer, not just by acquisition month.

## Unique weaknesses / common complaints

- **No GA4 or GSC integration** — Wicked positions as a replacement, not an additive layer. For Nexstage's 6-source thesis (which includes GA4 and GSC explicitly), Wicked is intentionally narrower.
- **No mobile app** — only web-responsive; testimonials and feature overviews list none, no App Store / Play Store presence found.
- **UI repeatedly described as clunky / outdated / overwhelming** — multiple sources independently. "Outdated user interface design" (smbguide.com), "interface can be clunky" (G2 aggregated), "interface can feel overwhelming for newcomers" (marketingtoolpro.com).
- **Steep onboarding** — multiple reviewers describe a learning curve measured in hours-to-weeks, not minutes; "users still discovering new reports and charts even after years of use."
- **UTM tagging required end-to-end** — repeatedly cited friction; auto-tagging exists but is not automatic for non-major channels.
- **Data lag** — "1-day-lag for the data whenever you launch a campaign" cited across reviews; some peak-period 1-2 hour lags.
- **API limitations** — automation around first/last click date columns specifically called out as limited.
- **Pricing opacity above $2.5M revenue band** — only the $0-$2.5M band is published. Higher bands are quoted on request.
- **Pricing feels high to small merchants** — entry tier $499/mo is well above most Triple Whale / Lifetimely / Glew alternatives in the SMB category.
- **No Shopify App Store listing of significance** — absent from the typical Shopify-app-review-thread mentions; Wicked is sold direct, not via the app store, which limits the public review surface (and is why G2 and Capterra direct review pages are sparse).

## Notes for Nexstage

- **LTV-attribution visual differentiation, captured:** Wicked's distinctive UI move is the **cohort-matrix-with-attribution-filter** — rows = acquisition month, columns = LTV accumulation over time, *with a top-bar filter chip that re-slices the cohort by acquired-source/campaign/ad/email*. The breakthrough is that LTV reads as a 2D matrix while attribution acts as a slicer, rather than attribution being a separate report. That's the "different visually" angle worth diagramming for downstream synthesis. Source: help.wickedreports.com/guide-to-cohort-and-customer-lifetime-value-reporting.
- **Subscription-specific re-pricing is the moat.** Their patents-pending claim is on continuous revenue/ROI re-computation as ReCharge subscriptions rebill — meaning a campaign's ROAS number *changes over time even with no new spend* as the cohort matures. This is the structural innovation. Nexstage's "ratios are never stored, computed on the fly" rule is conceptually adjacent, but Wicked goes further by retroactively re-pricing past-period KPIs as cohort revenue accumulates. Worth deciding whether Nexstage's snapshot model can support this or if a separate "cohort overlay" lens is needed.
- **Two-source compare in FunnelVision** is the closest analog in the SMB category to Nexstage's 6-source-badge thesis — but Wicked only does Wicked-vs-Platform, not Real / Store / Facebook / Google / GSC / GA4. Direct visual reference for our source-comparison UI work.
- **No GA4, no GSC** — Wicked deliberately omits these. Gives Nexstage a positioning angle: "your full marketing stack including organic search and analytics," not just paid attribution.
- **Recurring complaint pattern is "clunky old UI."** Modern, fast Inertia/React UI is a credible differentiation axis — multiple reviewers across sources independently call this out.
- **Pricing band model.** Wicked scales by annual revenue band (only the $0-$2.5M band is shown publicly). This is a different scale axis than Triple Whale (per-store + revenue) or Lifetimely (per-store flat). Worth noting in pricing-strategy research.
- **5 Forces AI "Scale / Chill / Kill" three-state verdict** is an opinionated alternative to a recommendation feed — direct analog to thinking about how Nexstage might surface AI verdicts (binary / three-state / continuous score).
- **Add-on pricing model:** $199/mo each for Advanced Signal and 5 Forces AI on lower tiers, included on higher. Worth noting if Nexstage considers a similar gating pattern for AI features.
- **Onboarding friction is a published weakness.** Wicked's "minimum 1+ month of tracking" gate before advanced reports unlock is structural to the cohort logic but creates a "wait to see value" problem. Nexstage's historical-import-on-connect approach (`StartHistoricalImportAction`) gets to first-value faster — worth highlighting in differentiation work.
- **No public sandbox / demo / free tier.** All UI observations are reviewer-prose secondhand. If pixel-accurate teardown is needed, a paid evaluation account would be required.
- **Blocker:** G2 (403), Capterra (404 on direct review URL), and TrustRadius (no ratings) all blocked or empty. Most verbatim quotes sourced via secondary aggregators (Cuspera, smbguide, marketingtoolpro). Counts noted in the love/hate sections accordingly.
