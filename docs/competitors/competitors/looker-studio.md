---
name: Looker Studio (formerly Google Data Studio)
url: https://lookerstudio.google.com
tier: T2
positioning: Free DIY dashboard builder by Google for analysts, marketers and agencies who want to assemble their own ecommerce reports from GA4/Google Ads/Search Console + paid third-party connectors for Shopify and Meta.
target_market: SMB to mid-market; agencies, in-house analysts, "Google-stack" merchants on Shopify or Woo. Global. Strongest fit when ad spend is mostly Google and store data lives in Sheets/BigQuery.
pricing: Free tier (unlimited reports, viewers); Pro at $9/user/project/month annual.
integrations: GA4, Google Ads, Search Console, BigQuery, Google Sheets, YouTube Analytics, Campaign Manager 360, Display & Video 360 (native, free); Shopify, Meta Ads, TikTok Ads, Klaviyo, HubSpot, Salesforce via paid Partner connectors (Supermetrics, Porter Metrics, Catchr, Windsor.ai, Coupler.io, Funnel, Two Minute Reports). No native Shopify or Woo connector. No native Meta connector.
data_freshness: 12 hours default for Google marketing connectors (adjustable 15 min – 12 hr on first-party connectors; not changeable on Partner connectors). Extracted data sources update only manually.
mobile_app: Looker Studio iOS + Android apps (Pro only); Free tier is web-responsive via April 2025 "Responsive Reports" 12-column grid feature.
researched_on: 2026-04-28
sources:
  - https://lookerstudio.google.com
  - https://datastudio.google.com/overview
  - https://docs.cloud.google.com/looker/docs/studio/looker-studio-pro-subscription-overview
  - https://docs.cloud.google.com/looker/docs/studio/types-of-charts-in-looker-studio
  - https://docs.cloud.google.com/looker/docs/studio/manage-data-freshness
  - https://www.g2.com/products/looker-studio/reviews
  - https://www.g2.com/products/looker-studio/reviews?qs=pros-and-cons
  - https://whatagraph.com/reviews/looker-studio
  - https://agencyanalytics.com/blog/looker-studio-review
  - https://coefficient.io/looker-studio-limitations-and-workarounds
  - https://www.swydo.com/blog/looker-studio-limitations/
  - https://supermetrics.com/template-gallery/looker-studio-shopify-overview-report
  - https://supermetrics.com/template-gallery/looker-studio-ecommerce-dashboard
  - https://portermetrics.com/en/tutorial/looker-studio/connect-shopify/
  - https://lookerstudiomasterclass.com/lessons/01-03-user-interface-explained
  - https://lookerstudiomasterclass.com/blog/looker-studio-pro-vs-free
  - https://cloud.google.com/blog/products/business-intelligence/get-looker-studio-pro-for-android-and-ios
  - https://www.databloo.com/blog/responsive-reports-looker-studio/
  - https://blog.coupler.io/shopify-looker-studio-templates/
  - https://www.catchr.io/template/looker-studio-templates/shopify
---

## Positioning

Looker Studio is Google's free, browser-based reporting and dashboard tool — the rebranded successor to Google Data Studio. The marketing site frames it with the tagline **"Your data is beautiful. Use it."** and the value prop **"Unlock the power of your data with interactive dashboards and beautiful reports that inspire smarter business decisions. It's easy and free."** It does not target ecommerce specifically; it positions horizontally against Power BI / Tableau as a generic BI canvas, then leans on its free price and native Google connectors to win analysts, agencies and Google-stack SMBs. For the Nexstage audience it is the canonical "do it yourself" alternative — merchants who would rather glue GA4 + Shopify + Meta together in a free Google tool than pay $99-299/mo for a dedicated ecommerce analytics SaaS.

## Pricing & tiers

| Tier | Price | What's included | Common upgrade trigger |
|---|---|---|---|
| Free (Looker Studio) | $0 | Unlimited reports + viewers, all chart types, all native Google connectors (GA4, Google Ads, Search Console, BigQuery, Sheets, YouTube), email scheduling, embed, public sharing, Responsive Reports | Reports living on individual Google accounts (asset-loss risk); needing org-level governance, audit logs or mobile app |
| Pro (Looker Studio Pro) | $9 / user / project / month, annual billing (30-day free trial) | Everything in Free + organisation-owned content via Google Cloud project, team workspaces with folder permissions, IAM, SSO, audit logging, customer-managed encryption keys, iOS + Android apps, Pro support | Agencies with multi-client governance, mid-market with SSO/IAM/compliance needs |

Looker Studio Pro is **not** sold per ecommerce store or per workspace — it is per Google Cloud project per user, which is unusual for the SMB ecommerce category.

Hidden cost: any non-Google source (Shopify, Meta Ads, TikTok, Klaviyo, HubSpot) typically requires a Partner connector — Supermetrics from ~$39/mo, Windsor.ai from ~$25/mo (~$19/mo annual), Catchr from ~$21.67/mo, Porter Metrics with a free tier capped at 3 accounts and 30 days history, Funnel.io from ~€108/mo. So a Shopify+Meta+Google merchant ends up paying **per connector per source**, which often exceeds the headline price of a vertical tool like Triple Whale or Polar.

## Integrations

**Native, free, first-party (Google):** GA4, Universal Analytics (legacy), Google Ads, Search Console, BigQuery, Google Sheets, YouTube Analytics, Campaign Manager 360, Display & Video 360, Search Ads 360, Cloud SQL/MySQL/PostgreSQL/Spanner direct, file upload, Google Cloud Storage, Looker semantic models. Native connectors refresh on a 12-hour cycle; some allow tightening to 15 min.

**Partner connectors (paid, ~700+ in directory):** Shopify (no native — must use Supermetrics, Porter Metrics, Coupler.io, Catchr, Windsor.ai, Funnel.io, Two Minute Reports, Dataslayer, etc.), Meta Ads, TikTok Ads, LinkedIn Ads, Pinterest Ads, Microsoft Ads (Bing), Klaviyo, HubSpot, Salesforce, Mailchimp, WooCommerce, Stripe, Amazon Ads, Snapchat. Partner connectors are stuck on the 12-hour freshness cap.

**Coverage gaps from a Nexstage-equivalent perspective:**
- No native Shopify connector — every ecommerce merchant must wire in a Partner.
- No native WooCommerce connector — same constraint.
- No native Meta Ads connector — has to be paid Partner.
- No COGS / cost-of-goods primitive — must be uploaded as a separate Google Sheet and blended (5-source blend cap).
- No attribution model selector (last-click vs first-click vs data-driven) at the report level — inherits whatever the source system serves.
- Push destinations: read-only canvas — Looker Studio is purely a viewer, no writebacks or push to email/Slack apart from scheduled email PDF/CSV.

## Product surfaces (their app's information architecture)

- **Home (Reports tab)** — list of all reports the user owns or has had shared with them; large tile of templates above the fold.
- **Home (Data Sources tab)** — list of every connected data source the user has created or accessed.
- **Home (Explorer tab)** — ad-hoc exploration sandbox on top of an existing data source.
- **Template Gallery** — Google-curated templates ("Acme Marketing Template", "World Population", "Search Console Report") plus partner-published ones.
- **Report Editor (canvas)** — main authoring surface; drag-and-drop charts onto a freeform page.
- **Data Source Editor** — field-level schema; rename fields, change types, set aggregation, add calculated fields, add parameters.
- **Connector Gallery** — searchable directory of Google + Partner connectors with category filters.
- **View Mode** — read-only consumption view for end users; hides editor chrome.
- **Sharing dialog** — Google-Drive-style permissioning (link sharing, specific people, organisation, public on web).
- **Schedule Email** — set recurring PDF email delivery to a list of recipients.
- **Pro: Team Workspaces** — folder-tree of organisation-owned reports, with role-based access.
- **Pro: Admin Console** — IAM, audit logs, project-level billing.
- **Pro: Mobile App (iOS / Android)** — list of reports, view-only consumption, switch between original and mobile-friendly view.

## Data they expose

Looker Studio is source-agnostic — it does not impose a schema. What appears depends entirely on which connector the user wires up. The breakdown below describes what is typically pulled in an ecommerce setup based on Partner-connector documentation and template galleries.

### Source: Shopify (via Partner connector, e.g. Supermetrics, Porter Metrics, Catchr, Coupler.io)
- Pulled: orders, line items, customers, products, vendors, refunds, inventory levels, multi-location inventory, abandoned checkouts, transaction fees, customer addresses, tags. Coupler.io / Windsor.ai also expose order_status_url, financial_status, fulfillment_status.
- Computed in templates: total sales, gross sales, net sales, average order value (AOV), orders count, units sold, top 10 best-selling products, sales by vendor, MoM order growth, returns ratio, new vs returning customer split, sales by country/city, inventory value, inventory days-of-supply.
- Attribution windows: not handled — Shopify orders are date-stamped at order creation; one Shopify-specific cancellation pitfall is documented: "an order placed in January and cancelled in February will result in +$10 in January and -$10 in February in Shopify, but most Looker Studio connectors will treat this as $0 in January" (lookerstudiobible.com).

### Source: Meta Ads (via Partner connector)
- Pulled: campaign / adset / ad spend, impressions, clicks, reach, frequency, CPM, CPC, conversions, conversion value, ROAS, breakdown by age/gender/placement/country/device.
- Computed in templates: blended ROAS (Shopify revenue ÷ Meta spend), CPA, CTR, CVR, frequency-vs-CPM scatter, creative performance ranking.
- Attribution windows: inherited from the Meta API — typically 7-day-click, 1-day-view by default; Looker Studio does not let the report viewer toggle the window unless the connector exposes it as a dimension.

### Source: Google Ads (native)
- Pulled: campaign / ad group / keyword / ad spend, impressions, clicks, conversions, conversion value, CTR, average CPC, search impression share, quality score, audience.
- Computed in templates: ROAS, CPA, search vs PMax split, branded vs non-branded keyword spend, geographic performance map.
- Attribution windows: Google Ads' own model (last-click, data-driven, position-based) — selected at the Google Ads property level, not in Looker Studio.

### Source: GA4 (native)
- Pulled: sessions, users, new users, engaged sessions, engagement rate, average engagement time, events, event count, conversions, ecommerce items (item_name, item_id, item_category, quantity, item_revenue), purchase events, transactions, source/medium/campaign, landing page, country, device.
- Computed in templates: conversion rate, revenue per user, cart-to-purchase funnel, channel grouping pie, landing-page table.
- Attribution windows: GA4 default cross-channel data-driven; configured inside GA4 admin, not in Looker Studio.

### Source: Search Console (native)
- Pulled: query, page, country, device, impressions, clicks, CTR, average position. Uses Site Impression and URL Impression tables.
- Computed in templates: top queries by impressions, top landing pages by clicks, brand vs non-brand split (via regex calc field), CTR vs position scatter.

### Source: BigQuery / Sheets (native)
- Custom — anything the analyst chooses to land in BigQuery (e.g. raw Shopify orders dump, COGS table, attribution model output) becomes a queryable source.

## Key UI patterns observed

### Home / Reports tab
- **Path/location:** lookerstudio.google.com root after login.
- **Layout (prose):** Top horizontal nav with Google Looker Studio logo and account avatar. Three primary tabs in a row directly under the nav: **Reports**, **Data Sources**, **Explorer**. Above the report list sits a **Template gallery strip** — a horizontally scrolling row of large template cards with a thumbnail screenshot, name and "Use template" button. Below that, a list/grid view of "Recent reports" with columns Name | Owner | Last opened by me. Top-left "Create" button opens a popover with Report / Data source / Explorer.
- **UI elements (concrete):** Material Design (Google) styling: white background, blue primary action buttons, Roboto type, low-contrast 1px row dividers, small avatar circles per owner.
- **Interactions:** Click report row to open in viewer; right-click for "Open in new tab", "Make a copy", "Move to trash". Search bar (top center) searches by report or data source name.
- **Source/screenshot:** Marketing copy at lookerstudio.google.com / datastudio.google.com/overview; UI walkthrough at lookerstudiomasterclass.com/lessons/01-03-user-interface-explained.

### Report Editor (canvas)
- **Path/location:** Open any report → top-right "Edit" toggle.
- **Layout (prose):** Top **menu bar** (File, Edit, View, Insert, Page, Arrange, Resource, Help). Top **toolbar** below the menu bar: Add page, Add data, Add chart (dropdown of all chart types), Add control (dropdown: date range, dropdown list, fixed-size list, input box, advanced filter, slider, checkbox), Insert image / text / line / shape, Theme and layout, Refresh data, Share. **Left rail (Pages list)** lists each page in the report — drag to reorder. **Center canvas** is freeform (or 12-column responsive grid in Responsive Reports mode) — anything outside the canvas frame won't render in View mode. **Right side panel ("Properties panel")** is contextual: when no element is selected it shows the page properties, when a chart is selected it splits into two tabs **Setup** (data source, dimension wells, metric wells, sort, filters, default date range, interactions) and **Style** (font, colour, axis, legend, conditional formatting, chart-specific formatting like reference lines).
- **UI elements (concrete):** Drag-handle dimension/metric chips coloured green (dimensions) and blue (metrics). Calculated-field "fx" badge. Conditional formatting rules as a vertical list with colour swatch + condition + value. Filter dialogue is a builder UI with Include/Exclude radio + match-type dropdown.
- **Interactions:** Click chart → chip-based dimension/metric editor in right rail; cross-filter on click (a row click in one table filters all other charts on the page); date-range selector applies globally if placed at page level; hover tooltip shows raw value + dimension name.
- **Source/screenshot:** datastudio.google.com/overview; lookerstudiomasterclass.com/lessons/01-03-user-interface-explained.

### Chart catalogue (Insert menu)
- **Path/location:** Insert > [chart type] from Report Editor toolbar.
- **Layout (prose):** Twenty-one distinct chart types: Scorecard (single metric, with compact / non-compact variant), Table (with optional bar-in-cell or heatmap-in-cell variants), Pivot Table, Time Series (with sparkline and smoothed variants), Bar / Column (stacked, 100%-stacked, grouped), Pie (with donut variant), Combo (mixed bar + line), Geo Chart, Google Maps (bubble / filled / heat / line), Area (stacked / 100%-stacked), Scatter (with bubble), Bullet, Gauge, Tree Map, Sankey, Waterfall, Boxplot, Candlestick, Timeline, Funnel, Community Visualizations (third-party developer charts).
- **UI elements (concrete):** Each chart type has its own Setup panel with required wells: Dimension, Breakdown Dimension, Metric, Sort. Optional comparison date range generates a "% delta vs previous period" column when enabled.
- **Source/screenshot:** docs.cloud.google.com/looker/docs/studio/types-of-charts-in-looker-studio.

### Date range control + filter controls
- **Path/location:** Toolbar > Add control > Date range / Drop-down list / Fixed-size list / Input box / Advanced filter / Slider / Checkbox / Data control.
- **Layout (prose):** Date range control renders as a button on the canvas displaying the current range; click → opens a calendar popover with **preset list on the left** (Today, Yesterday, Last 7 days, Last 28 days, Last 30 days, Last 90 days, Year to date, Last year, This week, Last week, This month, Last month, etc.) and **two-month dual calendar on the right** for custom selection. Drop-down list control renders as a button; clicking opens a popover with a search box at the top + checkbox list of available values.
- **Interactions:** All controls on the same page filter all charts on the same page by default (cross-filter scope is configurable via "Group" assignment). "Compare to previous period / previous year" toggle inside the date range control adds delta columns to charts.
- **Source/screenshot:** support.google.com/looker-studio/answer/11335992 + lookercourses.com/date-range-control-or-how-to-select-specific-dates-in-looker-studio.

### Shopify Overview Template (Supermetrics-published, free)
- **Path/location:** Template Gallery → Shopify Overview Report; or supermetrics.com/template-gallery/looker-studio-shopify-overview-report.
- **Layout (prose):** Per template description, the dashboard tracks "total sales, orders, average order value, and customers, while analyzing custom acquisition channels and geographical distribution." Multi-page report with Sales / Product / Inventory / Customer pages. Sales analysis: total sales development, MoM order growth, AOV trend, top vendors. Product page: top 10 best-selling products by timeframe. Inventory page: remaining inventory quantities, total inventory value, inventory value over time, products with highest and lowest stock. Customer page: orders by customer, total sales by customer, orders-vs-returns ratio, new-customer acquisition tracking.
- **UI elements (concrete):** Cannot inspect detailed canvas without using the connector — published descriptions confirm scorecards (KPI tiles), time-series, ranked tables, and geo maps.
- **Interactions:** Date range control at top filters all charts (standard Looker Studio cross-filter pattern). Filtering by country and city is offered ("granular filtering by both country and city, applicable to units sold, multi-location inventory data, stock availability, and return quantity rates").
- **Source/screenshot:** supermetrics.com/template-gallery/looker-studio-shopify-overview-report; catchr.io/template/looker-studio-templates/shopify.

### Ecommerce Profit / Multi-channel template (Supermetrics + Funnel.io)
- **Path/location:** Various Partner-published templates; funnel.io/blog/data-studio-templates-for-ecommerce-marketers.
- **Layout (prose):** Blends Shopify revenue with Meta Ads + Google Ads spend. KPI tiles for "total revenue, profit on ad spend, and contribution margin, alongside Google Ads metrics like ad spend, showing exactly how much more profit your ads generated." Channel-level table for ROAS / CPA / CVR by campaign. Conversion-funnel page: GA4 ecommerce events progressing from item view → add-to-cart → begin-checkout → purchase, with per-stage drop-off rates.
- **UI elements (concrete):** Standard Looker Studio scorecards and tables. No source-of-truth badging — column headers identify the data source by name (e.g. "Shopify Revenue", "Meta Spend").
- **Interactions:** Cross-filter on campaign click; manual blend joins Shopify + Meta on date as the join key (not on order_id or pixel_id — meaning attribution is calendar-aligned, not click-aligned).
- **Source/screenshot:** funnel.io/blog/data-studio-templates-for-ecommerce-marketers; supermetrics.com/template-gallery/looker-studio-ecommerce-dashboard.

### Mobile App (Pro)
- **Path/location:** iOS App Store / Google Play "Looker Studio" — Pro subscription required.
- **Layout (prose):** "By default, Looker Studio reports are displayed as a web report (original version) in the mobile app. If a mobile friendly version of a report is available, a message at the top of the report will prompt you to switch to that view. You can switch between mobile friendly and original version by selecting the three-dot menu at the top right side of the screen" (Google support article 13549432).
- **UI elements (concrete):** Mobile-friendly view uses the Responsive Reports 12-column grid feature introduced April 2025. Limitations: "reports that include lines (such as line, arrow, elbow, or curved) and reports that have mind map or flow chart-like arrangements" do not preserve detail in mobile-friendly view.
- **Source/screenshot:** cloud.google.com/blog/products/business-intelligence/get-looker-studio-pro-for-android-and-ios; databloo.com/blog/responsive-reports-looker-studio.

## What users love (verbatim quotes, attributed)

- "Data Template currently I'm using the GA4 Template and Google webmaster." — Rahul K., G2 review (cited in Whatagraph 2026 review compilation)
- "...you can easily transform numbers into graphics." — Estephany R., G2 review (cited in Whatagraph 2026 review compilation)
- "You can create really powerful and complete dashboards totally for free." — Luis, Capterra review (cited in Whatagraph 2026 review compilation)
- "Looker Studio's collaboration features are well-thought-out. Real-time updates, commenting, and notification systems keep our distributed team in sync. The permission system gives us the control we need for different user roles." — Ben Walters, Technical Writer at DocuFlow, G2 review, 2026-01-02
- "I love the interactive tools like the date range selectors and campaign drop-down filters, allowing stakeholders to adjust and explore the dashboard on their own." — G2 reviewer, cited via Whatagraph 2026 review summary
- "A free and flexible BI tool for quick dashboards and planning." — Rohan S., Data Engineer (Mid-Market), G2 review, 2025-08-29
- "Looker Studio makes it incredibly easy to build interactive, shareable dashboards all without coding." — User quoted in AgencyAnalytics 2026 review
- "What would have taken me days or months took the team mere hours. It's even better than we expected! The team was able to quickly and efficiency with very little direction create financial visuals and interactive dashboards for our small business using Google Looker." — Trustpilot reviewer (looker-studio.net listing)

## What users hate (verbatim quotes, attributed)

- "Performance issues with large Dataset such as loading dashboard." — Ashok S., G2 review (cited in Whatagraph 2026 review compilation)
- "It's good for quick and cheap data visualization that can be discarded next week without much hassle…It's terrible in many other aspects (mainly data governance imo)." — Reddit user, r/BusinessIntelligence (cited in Whatagraph 2026 review compilation)
- "The problem is that Looker Studio is unable to do some stupidly simple sh|t, like adding two scorecards together, without ridiculous workarounds…Looker Studio is a clown car: interesting concept, but not equal to the big boys." — Reddit data analyst, r/GoogleDataStudio (cited in Whatagraph 2026 review compilation)
- "What I like least is that for platforms external to Google, it is often necessary to have independent payment connectors." — Raul S., G2 review (cited in Whatagraph 2026 review compilation)
- "Customer support for Looker Studio from Google is minimal." — G2 reviewer (cited in Whatagraph 2026 review compilation)
- "They don't have any dedicated technical support which I feel is a must…" — G2 reviewer (cited in Whatagraph 2026 review compilation)
- "I find it to be frustratingly slow. Depending on the number of fields and tables in your database it can be unwieldy to create dashboards." — Reddit user (cited in BlazeSQL 2026 alternatives roundup)
- "It's not meant to be pretty." — User quoted in AgencyAnalytics 2026 Looker Studio review (re: client-facing white-labeling)

## Unique strengths

- **Genuinely free at the floor.** Unlimited reports, unlimited viewers, unlimited charts, full chart catalogue, scheduled email — no other dashboard tool with first-party Google connectors matches this. The Free tier is the only viable "$0 ecommerce dashboard" path that does not require self-hosting.
- **First-party Google connectors with no row caps.** GA4, Google Ads, Search Console and BigQuery flow in natively without paid middleware — the only competitor that comes close is Looker Studio's own enterprise sibling Looker.
- **21 native chart types including Sankey, Waterfall, Boxplot, Funnel, Bullet, Gauge, Tree Map, Candlestick.** Wider chart catalogue than most ecommerce-vertical SaaS (Triple Whale, Polar, Lifetimely typically ship 5-8 chart primitives).
- **Massive template ecosystem.** Hundreds of free templates from Supermetrics, Porter Metrics, Catchr, Windsor.ai, Coupler.io, Funnel, Two Minute Reports specifically for Shopify and ecommerce — copy-paste-customize gets a non-technical merchant to a working dashboard in minutes.
- **Google Drive-style sharing model.** Permissions, link-sharing, real-time co-editing and embed are native and familiar; analyst-to-stakeholder handoff is essentially zero-friction inside a Google Workspace.
- **Reach.** 4,500+ G2 reviews at 4.4/5 — the largest installed base in the BI/dashboard category. Almost every digital agency, freelance analyst and Google-stack ecom team has touched it.
- **Free Responsive Reports (April 2025).** 12-column grid + flexible vertical sections automatically adjust the canvas for tablet/phone — closes the long-running mobile gap on the Free tier.

## Unique weaknesses / common complaints

- **Performance degrades with scale.** "Of 13 reviews on G2 in 2025, 5 of them flagged performance issues. That's 38% of real users with the same Looker Studio complaint" (Coefficient.io and Swydo write-ups). Slowdown commonly hits beyond ~100k rows; Google itself recommends limiting to fewer than 25 components per dashboard.
- **No native ecommerce platform connector.** Shopify, WooCommerce, Meta Ads, TikTok Ads, Klaviyo all require paid Partner connectors (~$25-44/mo each) or limited free-tier community connectors.
- **5-source blend cap.** "Looker Studio limits data blending to five sources per blend, and this 5-source blending limit applies on both the free tier and Looker Studio Pro" — DataSlayer 2026 limitations article. For a merchant blending Shopify + Meta + Google + GA4 + GSC + TikTok + Klaviyo + COGS sheet, the cap is reached fast.
- **Each chart limited to 10 dimensions and metrics.** Documented limit; forces splitting analysis across multiple components.
- **Partner connectors capped at 12-hour refresh.** Cannot tighten freshness even on Pro; near-real-time ecommerce dashboards are not possible without paying for and hosting the data layer in BigQuery.
- **No native attribution modelling.** Inherits whatever model GA4 / Meta / Google Ads serves; no ability to pick last-click vs first-click vs MMM vs incrementality at the dashboard level.
- **Asset ownership risk on Free tier.** Reports belong to individual Google accounts; if the user leaves the company, reports go with them. Pro at $9/user/project/month addresses this but introduces non-trivial onboarding (Google Cloud project, IAM, billing).
- **Minimal customer support.** Recurring complaint on G2 and Reddit; Google does not staff a CSM for Free, and Pro support is documented as ticket-based.
- **Steep learning curve for non-analysts.** Calculated fields, blends, parameter sets, regex CASE statements feel "engineering-grade" to first-time users; consistently flagged as overwhelming in 2026 G2 reviews.
- **Security incidents.** "In 2023, hackers began using Looker Studio to launch phishing attacks, and as of June 2025, the security issue hadn't been patched." (Cited in 2025 Whatagraph review summary.)
- **Cancellation accounting nuance.** "An order placed in January and cancelled in February will result in +$10 in January and -$10 in February in Shopify, but most Looker Studio connectors will treat this as $0 in January" — published gotcha specific to Shopify+Looker Studio combos.

## Notes for Nexstage

- **Looker Studio is the canonical "build it yourself" alternative.** A merchant who decides not to buy Nexstage will most often default to "I'll just throw a Looker Studio dashboard together with the GA4 connector" — recognise this as the actual decision point in messaging, not Triple Whale or Polar.
- **The 5-source blend cap is a real Nexstage advantage to flag.** Nexstage's resolver model collapses 6 sources into one canvas natively; in Looker Studio the user hits the blend ceiling at exactly the same scope.
- **No source-of-truth badging exists.** Looker Studio columns are labelled by data source name only ("Shopify Revenue", "Meta Spend") — the 6-source-badge thesis (Real, Store, Facebook, Google, GSC, GA4) has no analogue in Looker Studio. Direct differentiator.
- **Free tier really is free, but ecommerce reality forces a Partner connector.** A Shopify+Meta+Google+Klaviyo merchant on Looker Studio is typically paying ~$50-100/mo for connectors anyway. That moves the price comparison against Nexstage from "$0 vs $X" to "$50-100 in connectors + setup time vs $X all-in".
- **Templates are the user-acquisition surface.** Supermetrics, Porter Metrics, Catchr, Funnel, Windsor.ai, Coupler.io all publish free Shopify/ecommerce templates as lead magnets for their paid connectors — the template gallery itself is essentially a connector-vendor marketing channel. Worth studying their template KPI choices (sales, AOV, top products, MoM growth, returns ratio, inventory value, country geo, customer cohorts) as the SMB-merchant baseline expectation.
- **Cancellation/refund accounting:** the documented "Jan +$10 / Feb -$10 → blended $0" gotcha is exactly the kind of accuracy issue our `daily_snapshots` writer should sidestep cleanly; could become an explicit "we get cancellations right" talking point.
- **Data freshness is 12h by default for marketing connectors, locked at 12h for Partner connectors.** Nexstage's hourly_snapshots / near-real-time freshness is a defensible product-level differentiator — Looker Studio cannot match this without BigQuery streaming infrastructure that is well outside SMB scope.
- **Mobile app is Pro-only.** Free-tier mobile is web-responsive only (Responsive Reports). For merchants who check their store from a phone constantly, a native mobile app on the floor tier is a Nexstage differentiator.
- **No COGS / cost-config primitive.** Looker Studio expects the user to upload their own COGS sheet and blend it in — the "Recomputing…" banner UX from `UpdateCostConfigAction` has no analogue; cost config is a complete category-defining feature relative to the DIY path.
- **No retroactive recompute.** When source data backfills or a connector reconciles, Looker Studio does not have an attribution-rebuild concept — historical numbers shift silently. Worth highlighting Nexstage's `RecomputeAttributionJob` + banner pattern as the trustworthy alternative.
- **Reach signal:** 4,500+ G2 reviews. The category awareness for "build dashboards myself" is enormous; the Nexstage thesis must beat it on time-to-insight, not on raw flexibility.
