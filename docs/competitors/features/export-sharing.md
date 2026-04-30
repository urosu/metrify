---
name: Export & sharing
slug: export-sharing
purpose: Can the merchant get the numbers out of the dashboard and into the hands of an accountant, agency, partner, or Slack channel without screenshots?
nexstage_pages: orders, dashboard, profit, performance, ads, seo, holidays
researched_on: 2026-04-28
competitors_covered: metorik, looker-studio, putler, glew, triple-whale, polar-analytics, lifetimely, beprofit, stripe-sigma, lebesgue
sources:
  - ../competitors/metorik.md
  - ../competitors/looker-studio.md
  - ../competitors/putler.md
  - ../competitors/glew.md
  - ../competitors/triple-whale.md
  - ../competitors/polar-analytics.md
  - ../competitors/lifetimely.md
  - ../competitors/beprofit.md
  - ../competitors/stripe-sigma.md
  - ../competitors/lebesgue.md
---

## What is this feature

Export & sharing is the set of surfaces that let a merchant move data and views *out* of the analytics tool and into someone else's workflow — accountant, agency, Slack channel, board deck, or external warehouse. Three jobs sit underneath the headline:

1. **Pull data out** as CSV / Excel / PDF / API / SQL passthrough so a human or another tool can keep working on it.
2. **Push views out** on a schedule via email, Slack, or webhook — including link-style "public report" sharing where the recipient doesn't have an account.
3. **Embed or co-edit** so a stakeholder sees the live dashboard in another surface (Notion, agency portal, board doc) without round-tripping screenshots.

For SMB Shopify/Woo owners specifically this is the surface where "I trust my numbers" becomes "my accountant trusts my numbers." Every competitor in this profile has a store and ad-platform connection (so they all *have* the data); the differentiator is whether sending it onward takes one click, ten minutes of column-picking, or a paid Partner connector. Reviewers in this category cite exports as the single highest-ROI surface — it's how the tool ends up in the close-of-month workflow rather than as a dashboard that gets opened twice a week.

## Data inputs (what's required to compute or display)

- **Source: All Nexstage tables** — `orders`, `daily_snapshots`, `hourly_snapshots`, `daily_snapshot_products`, `ad_insights`, `gsc_*`, `ga4_*`. Export is a read-side concern; any column on screen needs to be representable in a row-shaped output.
- **Source: User-input** — column selection / order, file format (CSV / XLSX / PDF), schedule cadence (one-off / daily / weekly / monthly), recipient list (email addresses, Slack channel ID, webhook URL), permission scope (workspace-restricted / link-with-secret / public-on-web).
- **Source: User-input** — date range, filters, segment definition that scopes the export. Saved-segment IDs from the segment builder are reused.
- **Source: Workspace config** — currency, timezone, locale (so a Tuesday-morning Slack digest in Sydney lands at the right local hour), branding (logo, store name on the PDF cover).
- **Source: Auth** — workspace membership for protected links; signed-URL secret for public links; OAuth tokens for Slack / Gmail destinations; SMTP fallback for email.
- **Source: Computed** — `data_load_time` analog (latest snapshot timestamp) so the recipient sees freshness.
- **Source: Schema** — for "publish to BigQuery / Snowflake" style passthrough, a stable column contract with types (the Sigma `data_load_time` precedent).

## Data outputs (what's typically displayed)

For each output, name the artifact, format, units, and typical comparisons:

- **Artifact: Exported CSV** — rows × columns matching the on-screen view; UTF-8; user-selectable column subset.
- **Artifact: Exported XLSX** — multi-sheet (one sheet per pinned report or one per period); preserves number formats / currency.
- **Artifact: Exported PDF** — laid-out report with cover, KPI tiles, charts as images, optional appendix table; typically used as the email attachment for "weekly to investors" cadence.
- **Artifact: Scheduled email digest** — HTML body summarising key KPIs vs prior period; subject line includes date range; attachment optional.
- **Artifact: Scheduled Slack message** — text + chart image to a named channel; usually a daily KPI snapshot or anomaly alert.
- **Artifact: Public link** — shareable URL with embedded view; permission scopes (anyone-with-link / anyone-on-web / specific email).
- **Artifact: Embed iframe** — `<iframe src="…">` for Notion / agency portal / internal wiki.
- **Artifact: SQL passthrough / warehouse sync** — Snowflake/Redshift/BigQuery tables refreshed nightly; for users who want to bring their own BI.
- **Artifact: Webhook payload** — JSON POST to a user-supplied URL when a scheduled query finishes.
- **Dimension: Cadence** — one-off, daily, weekly, monthly; delivery time is timezone-aware.
- **Dimension: Recipient** — email list, Slack channel, webhook, public URL.
- **Dimension: Permission** — workspace-only, link-with-secret, public-on-web.

## How competitors implement this

### Metorik ([profile](../competitors/metorik.md))
- **Surface:** Top-level "Exports" page + an export affordance from any report. Separate "Digests" surface in Settings.
- **Visualization:** Drag-and-drop column-picker form (toggle switches per column, drag handles to reorder); not a chart.
- **Layout (prose):** "Drag-and-drop column picker — user reorders/toggles columns to include in CSV. Schedule recurring exports (daily/weekly/etc.) delivered via email or Slack" (metorik.md). WooCommerce custom fields can be added as columns. Digests are configured separately in Settings > Digests as plain-English summaries delivered to email or Slack.
- **Specific UI:** Toggle switches per column, drag handles to reorder; saved export configurations get scheduled cadence and a delivery target (email or Slack). Digests phrased as natural language ("Your store made $X this week, up Y% from last week") rather than chart embeds.
- **Filters:** Inherited from the report or segment the export was launched from; segment-as-first-class-primitive means a saved segment can be auto-recurring CSV exported.
- **Data shown:** Any column from orders, customers, subscriptions, products, variations, categories, coupons, carts; supports Woo custom fields.
- **Interactions:** Save export configuration; schedule recurring; deliver to email or Slack; one-off CSV download. Reviewers explicitly cite it: "Exporting through Metorik has been an absolute game-changer. We can rely on going into Metorik, hitting export, and all correct data being included" (Brian Zarlenga, Output, via metorik.com/love).
- **Why it works:** "Saved segments work everywhere" — once a segment is built, the same saved object can be exported, scheduled, and reused across cohort/customer/product/retention reports without rebuilding.
- **Source:** ../competitors/metorik.md (Exports + Digests sections).

### Looker Studio ([profile](../competitors/looker-studio.md))
- **Surface:** Top toolbar Share button (Google-Drive style); Schedule Email dialog inside Share; Pro adds Team Workspaces.
- **Visualization:** No bespoke export viz — the artifact is the report PDF / CSV itself; Share dialog is a permission grid.
- **Layout (prose):** "Google-Drive-style permissioning (link sharing, specific people, organisation, public on web)" plus a separate "Schedule Email" dialog that "set[s] recurring PDF email delivery to a list of recipients" (looker-studio.md). Embed is supported via report URL `<iframe>`. Pro tier adds organisation-owned content (folders, IAM, audit logs) so reports survive employee turnover.
- **Specific UI:** Share dialog identical to Google Docs — link toggle, "Anyone with the link," "Restricted," "Public on the web," per-email role assignment (Viewer / Editor). Schedule Email picks recipients, frequency (daily/weekly/monthly), pages-to-include checklist, format (PDF). Embed exposes a copy-paste iframe snippet.
- **Filters:** Date-range control inherited from the report; can be set to compare-to-previous-period before the PDF renders.
- **Data shown:** Whatever the report renders — limited to Looker Studio's chart catalogue (21 chart types) and the 5-source blend cap.
- **Interactions:** Real-time co-editing in the editor (Google Docs model), commenting; link share is one-click; PDF schedule is multi-recipient; embed is iframe.
- **Why it works:** "Looker Studio's collaboration features are well-thought-out. Real-time updates, commenting, and notification systems keep our distributed team in sync. The permission system gives us the control we need for different user roles" (Ben Walters, DocuFlow, G2 review, 2026-01-02, via looker-studio.md). "I love the interactive tools like the date range selectors and campaign drop-down filters, allowing stakeholders to adjust and explore the dashboard on their own" (G2 reviewer via Whatagraph 2026 summary).
- **Source:** ../competitors/looker-studio.md (Sharing dialog + Schedule Email surfaces).

### Putler ([profile](../competitors/putler.md))
- **Surface:** Per-list "Export" button (Customers, Transactions, Products); RFM 3-click Mailchimp-export workflow; weekly email reports.
- **Visualization:** No special export viz; CSV download is the artifact. RFM segment export is launched from the colored 11-segment 2D chart.
- **Layout (prose):** Export from any list view; transactions export "with currency conversion + timezone normalization + dedup pre-applied" (putler.md). RFM 2D chart: pick date range → click colored segment region → export to Mailchimp or CSV — 3 clicks total. Weekly email reports go out without requiring login.
- **Specific UI:** "Export" button on Customers, Transactions, Products lists. RFM segment click opens a destination chooser (Mailchimp audience or CSV). Reviewers note bulk export caps: "Can't export more than [a limited number of] customer records at once" (Nicolai G., Capterra, June 10, 2019); "export very large records to CSV is a bit of issue" (yair P., Capterra, May 14, 2019).
- **Filters:** Inherited from the list — Location filter chips ("from continent down to street level"), Product, Status, Type; segment filter for customer lists.
- **Data shown:** Any column on the underlying list; transaction export includes net revenue, refunds, shipping, taxes, fees, discounts, commissions broken out as separate line items.
- **Interactions:** One-click CSV; Mailchimp segment push; Chrome extension surfaces customer card inside helpdesk/CRM tools (read-side embed).
- **Why it works:** Cross-gateway dedup is the value — "automatically identifies and merges duplicate transactions across payment gateways and eCommerce platforms" before the file is generated. Reviewers cite the "single dashboard" framing for hand-off: "I spend a lot of time getting an overview with Excel, but when I got Putler, I have an overview for Amazon, eBay, Etsy, Shopify. I don't need to do Excel anymore" (G2 reviewer cited via Putler's own G2 aggregation).
- **Source:** ../competitors/putler.md (Transactions Dashboard, RFM 2D Chart, Customer Profile sections).

### Glew ([profile](../competitors/glew.md))
- **Surface:** Daily Snapshot email (default surface, no in-app screen); Reports > My Reports (Glew Plus); Schedule per-report; BI Tunnel (SQL passthrough); CSV export from any segment.
- **Visualization:** Daily Snapshot is a tile-style KPI email ("Ecom Daily Flash Dashboard"); custom reports use Looker (bundled).
- **Layout (prose):** "Daily email featuring 15+ KPIs across financial and operational categories with built-in benchmarks and period-over-period comparisons" (glew.md). Scheduled Reports: "build your own dashboards with existing reports and filters, then save and schedule them to be emailed to anyone at any time on any interval" — daily / weekly / monthly cadence; email or Slack delivery. BI Tunnel exposes a dedicated AWS Redshift warehouse with "30,000+ dimensions and 3,000+ tables" via SQL credentials so the user can connect Tableau, PowerBI, Mode, Qlik, etc. Customer Segments include "CSV export of only viewed metrics."
- **Specific UI:** Daily Snapshot tiles; custom-reports drag-and-drop builder (Looker); Email/Slack delivery toggle on each scheduled report. BI Tunnel uses SQL credentials issued per workspace into a per-customer Redshift instance.
- **Filters:** "300+ unique filtering options" per Pro page; custom reports filter by any dimension/metric in the warehouse.
- **Data shown:** Daily Snapshot includes Revenue, orders, AOV, gross profit, gross margin, website visits, conversion rate, refunds, new customers, repeat customers, ad spend, top marketing channel, top-selling product, largest order. Custom reports / BI Tunnel: anything in the warehouse.
- **Interactions:** Schedule any report email/Slack; click-through from Snapshot tile to web app for drill-down; Glew Plus customers can customize tiles, comparison periods, targets, currency conversion via "Creating Custom Daily Snapshots" video flow.
- **Why it works:** Daily Snapshot is treated as the *primary* product surface, not an afterthought — Glew has no mobile app and explicitly substitutes the morning email. Reviewers love "easy to comprehend dashboards at your fingertips with actionable insights" (Jonathan J S., Capterra Oct 2019). Negative pattern: "custom reports may seem restrictive, with an additional cost of $150 per hour for each one" (Shopify-store reviewer cited in search index) and "constantly bothered to upgrade to the pro plan which costs 10x more" (Trustpilot reviewer).
- **Source:** ../competitors/glew.md (Daily Snapshot + Scheduled Reports + BI Tunnel sections).

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Email reports (Founders Dash and up); Slack output destination on Moby Agents; BigQuery / Snowflake bidirectional sync (Advanced+); push notifications; mobile app.
- **Visualization:** Mobile push card / widget on iOS-Android home screen; for warehouse, the artifact is a table contract.
- **Layout (prose):** "Founders Dash (Free Forever) — Summary board, benchmarks, email reports, web analytics" (triple-whale.md) — email reports are floor-tier. Moby Agents have a "output destination selector (dashboard vs email vs Slack)" per agent. BigQuery and Snowflake are listed as "bidirectional — Triple Whale can pipe its data to your warehouse on Advanced+." Mobile app: "Push notifications fire on revenue milestones 'within minutes of the triggering event'"; "Home-screen widgets supported."
- **Specific UI:** Email-report cadence config; Slack channel binding on each agent's output; widget kit on iOS/Android. Reviewers describe the mobile artifact: "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design" (paraphrased consensus across multiple 2026 reviews via workflowautomation.net, headwestguide.com).
- **Filters:** Date-range and store-switcher inherited from desktop; Summary view filters apply to the exported view.
- **Data shown:** Revenue, Net Profit, ROAS, MER, ncROAS, POAS, nCAC, CAC, AOV, LTV (60/90), Total Ad Spend, Sessions, Conversion Rate, Refund Rate, plus per-platform spend/ROAS sub-tiles.
- **Interactions:** Push notification on revenue milestone; widget refresh; agent output to dashboard/email/Slack; BigQuery / Snowflake nightly sync.
- **Why it works:** Mobile + push is the differentiator competitors can't match; for warehouse hand-off, the bidirectional sync collapses "I want to bring my own BI" without losing the dashboard. Negative theme: "The app is okay, but it's full of bugs and the UI is terrible" (BioPower Pet, Shopify App Store, April 2, 2026).
- **Source:** ../competitors/triple-whale.md (Mobile App + Moby Agents + integrations sections).

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Schedules (Slack + Gmail) on every dashboard block; Smart Alerts (Slack/email); Audiences activation (push to Klaviyo); Advertising Signals (push to Meta/Google CAPI); per-customer Snowflake DB; open MCP server.
- **Visualization:** Block-level Slack/email message; for warehouse, dedicated Snowflake schema.
- **Layout (prose):** "Schedule a block to auto-deliver as Slack message or email" (polar-analytics.md) — every dashboard block can be scheduled individually. Smart Alerts: "anomaly detection; configurable, delivered to Slack/email." Data Activations: Audiences (push enriched segments to Klaviyo) and Advertising Signals (server-side conversions back to Meta/Google CAPI). Each customer gets a dedicated Snowflake database with full SQL access on higher tiers — "data ownership on exit." Open MCP server lets external AI agents (Claude, ChatGPT, n8n) query the warehouse with the semantic layer applied.
- **Specific UI:** Per-block schedule dialog; Slack channel + Gmail recipient pickers. Per-customer Snowflake credentials in Settings. MCP endpoint URL plus API key.
- **Filters:** Views (saved bundles of filters spanning multiple data sources, grouped into named "Collections"); the schedule inherits the View. Quirk: "Views combine with 'OR' logic, not 'AND'" — multiple Views union rather than intersect.
- **Data shown:** Block-level — could be a metric card, table, chart, or sparkline; supports Custom Metrics built in the no-code formula builder.
- **Interactions:** Schedule per block; Slack/email destination; Klaviyo Audience push; CAPI server-side conversion enrichment; SQL queries against own Snowflake; MCP query from external agents.
- **Why it works:** Polar's "data ownership" framing is explicitly *anti*-lock-in — Triple Whale "rents you a dashboard," Polar gives you a warehouse. The MCP server is the 2026 evolution: external AI agents become first-class consumers of the same data the dashboard reads. Reviewers cite the package: "Polar solved all of our analytic issues...Their customer support is also next to none" (Vitaly, Shopify App Store, March 2025).
- **Source:** ../competitors/polar-analytics.md (Custom Dashboard + Schedules + Snowflake + MCP sections).

### Lifetimely ([profile](../competitors/lifetimely.md))
- **Surface:** Daily P&L Email/Slack delivery (7am daily, Monday 8am Slack); Custom Dashboards email/Slack delivery; Google Sheets export; QuickBooks Online sync.
- **Visualization:** Daily email is an income-statement digest; no chart-image-as-attachment pattern observed.
- **Layout (prose):** "Daily P&L Email/Slack delivery — Scheduled output, not a screen, but a first-class surface in their messaging" (lifetimely.md). "Delivered to your email inbox and Slack every Monday at 8AM" (marketing copy, verbatim). Custom Dashboards: "Schedule email delivery: daily or weekly at 7am. Slack delivery (Monday 8am called out in marketing copy)." Google Sheets is bidirectional.
- **Specific UI:** Cadence picker (daily / weekly), 7am send time, Slack channel binding. P&L formatted as an income statement — line items descending from revenue → contribution → net. QuickBooks expense lines flow inbound; outbound to Sheets.
- **Filters:** Inherited from the dashboard or report being scheduled.
- **Data shown:** Total sales, COGS, marketing spend, gross margin, contribution margin, net profit, refunds, fees, custom expenses for daily P&L; user-configurable for Custom Dashboards.
- **Interactions:** Email open + Slack message; click-through to web app; export to Sheets for spreadsheet hand-off.
- **Why it works:** The income-statement format makes the email digest accountant-friendly out of the box rather than a marketer-style KPI grid. Multiple reviewers cite simplification: "removes the hassle of calculating a customer's CAC and LTV" (ELMNT Health, Shopify App Store, January 27, 2026).
- **Source:** ../competitors/lifetimely.md (Custom Dashboards + Daily P&L surfaces).

### BeProfit ([profile](../competitors/beprofit.md))
- **Surface:** Email Reporting (automated scheduled); API access (Plus tier only); CSV download from any report.
- **Visualization:** No special export viz; standard CSV / scheduled email; multi-store comparison view exports tabular.
- **Layout (prose):** "Email Reporting — Automated scheduled email reports" (beprofit.md). Multi-Store Comparison view is gated to the $249/mo Plus tier; exports per-shop columns side-by-side. API access is the only programmatic destination, also Plus-tier-gated.
- **Specific UI:** Email cadence config in settings; API key in Plus tier admin. Reviewers complain about cancellation friction in the same surface area: "Worst experience ever, been charging me for months despite contacting them about cancellation" (Clear Cosmetics, Shopify App Store, March 4, 2026); "Company has been paying $720 per year since — never used. They will not respond" (Adrienne Landau, Shopify App Store, April 22, 2026).
- **Filters:** Inherited from the report; multi-shop scope picker.
- **Data shown:** Profit by shop, by platform, by country; per-order P&L; campaign-level marketing reports.
- **Interactions:** Schedule email; download CSV; API call.
- **Why it works:** Multi-platform reach in one tier (Shopify + Woo + Wix + Amazon) makes the export the only place a multichannel SMB sees consolidated profit; this is exactly the value the Plus tier monetizes.
- **Source:** ../competitors/beprofit.md (Email Reporting + API + Multi-Store sections).

### Stripe Sigma ([profile](../competitors/stripe-sigma.md))
- **Surface:** Schedules (daily/weekly/monthly with email or webhook); CSV export (full result set, not capped at the 1,000-row UI preview); Custom Metrics surface (`/custom-metrics`) for query-as-tile promotion; Sigma API; Stripe Data Pipeline for Snowflake/Redshift sync (separate paid product).
- **Visualization:** SQL workbench result table; chart toggle (line/bar) capped at 10,000-row results; otherwise CSV is the artifact.
- **Layout (prose):** Per-query menu inside the editor sets schedule. "Frequency choices: daily, weekly, monthly. Delivery methods: email or webhook" (stripe-sigma.md). Custom Metrics dashboard at `/custom-metrics` lets Sigma reports be promoted into "metric groups" tracked daily; **capped at 20 Sigma reports across all metric groups**. Sharing produces a unique URL; recipients of a shared query get a read-only view and must "make a copy" to modify.
- **Specific UI:** Schedule dialog (frequency + recipients + format); webhook URL field; "Run" → "Export" → "Schedule" → "Publish to Custom Metrics" promotion ladder. Variable `data_load_time` is exposed in queries for date-range parameterization in scheduled queries.
- **Filters:** SQL `WHERE` clause; date-range parameterization via `data_load_time`.
- **Data shown:** Whatever the SQL returns — Sigma exposes raw fact tables (charges, customers, invoices, subscriptions, disputes, payouts, balance transactions).
- **Interactions:** Promotion path: ad-hoc query → saved query → scheduled report → published Custom Metric. Each artifact graduates through four levels of formality without rebuilding.
- **Why it works:** "Sigma gives us legitimate evidence to challenge a chargeback...The new level of data and insight we can get out of Stripe compared to what we could get previously is just night and day" (Jez Bristow, Green Flag, testimonial on stripe.com/sigma). "Stripe Sigma has helped accelerate our financial close process. Instead of manually combining multiple data sources each month, we're now able to run a few simple queries in Sigma" (Kelly Hofmann, Slack, testimonial on stripe.com/sigma). Recurring complaint: "Sigma scheduled query results are not available until 2pm UTC the day after they run" (Chartsy 2026).
- **Source:** ../competitors/stripe-sigma.md (Query editor + Schedules + Custom Metrics + Sharing sections).

### Lebesgue ([profile](../competitors/lebesgue.md))
- **Surface:** MMM Weekly PDF report (asynchronous, email-delivered); Daily / Weekly Email Reports; Business Overview Table CSV download; Facebook CAPI push (Le Pixel Enrichment tier only).
- **Visualization:** PDF (multi-page); HTML email digest; CSV; CAPI is a destination push.
- **Layout (prose):** "MMM (weekly PDF) — Path/location: Marketing Mix Modeling section; deliverable is asynchronous PDF. Not interactive. Weekly PDF contains: budget redistribution recommendations, revenue forecasts, channel saturation insights, model confidence score" (lebesgue.md). Daily / Weekly Email Reports include "pacing data and forecasts." Business Overview Table is "exportable for external analysis." Facebook CAPI push is a paid destination on the Enrichment tier ($149–$1,649/mo).
- **Specific UI:** PDF cover with confidence score + multi-channel budget allocation visualization + current-vs-optimized revenue prediction line. Email-report cadence config in settings. "Color-coded performance indicators (blue for improvements, red for declines)" — note **blue not green** for positive, unusual.
- **Filters:** Inherited from the dashboard; MMM gated by ≥$5K/mo ad spend and ≥3 months history.
- **Data shown:** MMM PDF: budget redistribution, revenue forecasts, channel saturation, confidence score. Email reports: pacing + KPIs.
- **Interactions:** Open PDF; email open; CSV download; CAPI server-side push.
- **Why it works:** The PDF cadence is the only practical way to deliver MMM (computationally expensive enough that even a heavily-resourced competitor can't refresh on demand). Reviewers cite the email cadence as a top reason they use the product: "The metrics and pacing data delivered via email save time" (Marco P., Capterra, January 6, 2025).
- **Source:** ../competitors/lebesgue.md (MMM + Email Reports + Le Pixel CAPI sections).

## Visualization patterns observed (cross-cut)

Synthesizing across the 10 competitors:

- **Scheduled email digest:** 8 competitors (Metorik, Looker Studio, Glew, Triple Whale, Polar, Lifetimely, BeProfit, Stripe Sigma, Lebesgue — all but Putler treat scheduled email as a first-class surface; Putler does have weekly email reports but its export pattern is list-CSV-led). Universally available; most differentiated on cadence config, formatting, and recipient picker.
- **Slack delivery:** 5 competitors (Metorik, Glew, Triple Whale via Moby Agents, Polar, Lifetimely). Polar's per-block scheduling is the most granular; Lifetimely's "Monday 8am" Slack is the most opinionated default.
- **CSV / Excel export from list view:** 8 competitors. Universal table-stakes. Putler reviewers complain about row-count caps; Stripe Sigma uniquely uncaps (UI shows 1,000 rows, CSV returns full result set).
- **PDF as artifact:** 3 competitors (Looker Studio's Schedule Email outputs PDF; Lebesgue's MMM is PDF-only; Glew's Daily Snapshot is HTML email but the print-ready report option exists). PDF is associated specifically with "static report I can attach to an email to a stakeholder."
- **Public link / share-with-permissions:** 1 competitor stands out (Looker Studio — Google-Drive style permissioning). Polar / Triple Whale / others have *some* link sharing but none publish a permission grid as detailed.
- **Embed via iframe:** 1 competitor confirmed (Looker Studio). Others may support it but it's not a marketed feature.
- **SQL passthrough / dedicated warehouse:** 3 competitors (Glew via BI Tunnel + Redshift, Polar via dedicated Snowflake DB, Triple Whale via BigQuery/Snowflake bidirectional, Stripe Sigma via Stripe Data Pipeline as separate add-on). All gated to top tiers.
- **Webhook:** 1 competitor confirmed (Stripe Sigma — scheduled-query webhook delivery alongside email).
- **API:** 4 competitors (BeProfit Plus, Triple Whale, Stripe Sigma, Polar via MCP).
- **MCP server (open AI-agent passthrough):** 1 competitor (Polar Analytics — first observed in this batch). New 2025–2026 pattern.
- **Push notification / mobile widget:** 1 competitor (Triple Whale on iOS / Android with home-screen widgets).
- **Activation push (Klaviyo audience, Meta CAPI):** 3 competitors (Polar Audiences + Advertising Signals; Lebesgue Le Pixel Enrichment CAPI; Triple Whale Sonar Optimize). Activation is a sub-pattern of "data out" — the destination is another tool's audience or pixel rather than a human.
- **Promotion-ladder pattern:** 1 competitor (Stripe Sigma — ad-hoc → saved → scheduled → published Custom Metric). This is structurally novel and worth flagging as a UX pattern not just an export feature.

Visual conventions:
- **Drag-and-drop column picker** for CSV is universal where exposed (Metorik, Polar, Glew); toggle switches per column with drag handles.
- **Slack channel + email recipient pickers** are paired in every scheduling dialog observed.
- **Timezone-aware delivery** ("7am daily," "Monday 8am") is explicit in Lifetimely; implicit elsewhere.
- **No sender-branding control** observed except in negative form — Lebesgue reviewers complain about "No White Labelling Option. Would like to customise the automated reporting" (Robin T., Capterra).

## What users love about this feature (themes + verbatim quotes)

**Theme: One-click hand-off to spreadsheets / accountants**
- "Exporting through Metorik has been an absolute game-changer. We can rely on going into Metorik, hitting export, and all correct data being included." — Brian Zarlenga, General Manager, Output, via metorik.com/love (../competitors/metorik.md)
- "I spend a lot of time getting an overview with Excel, but when I got Putler, I have an overview for Amazon, eBay, Etsy, Shopify. I don't need to do Excel anymore." — G2 reviewer cited via Putler's own G2 aggregation (../competitors/putler.md)
- "Stripe Sigma has helped accelerate our financial close process. Instead of manually combining multiple data sources each month, we're now able to run a few simple queries in Sigma, enabling faster monthly reconciliation for credit card transactions." — Kelly Hofmann, Revenue Accounting, Slack, testimonial on stripe.com/sigma (../competitors/stripe-sigma.md)

**Theme: Email cadence saves time / delivers without login**
- "The metrics and pacing data delivered via email save time." — Marco P., Owner (Online Media), Capterra, January 6, 2025 (../competitors/lebesgue.md)
- "Easy to set KPIs and watch over business reports including your marketing costs, shipping costs, revenue, forecast for sales." — Sasha Z., Founder (Retail), Capterra, September 30, 2025 (../competitors/lebesgue.md)
- "easy to comprehend dashboards at your fingertips with actionable insights" — Jonathan J S., eCommerce Manager, Sporting Goods, Capterra Oct 2019 (../competitors/glew.md)

**Theme: Permissioning / collaboration without screenshots**
- "Looker Studio's collaboration features are well-thought-out. Real-time updates, commenting, and notification systems keep our distributed team in sync. The permission system gives us the control we need for different user roles." — Ben Walters, Technical Writer at DocuFlow, G2 review, 2026-01-02 (../competitors/looker-studio.md)
- "I love the interactive tools like the date range selectors and campaign drop-down filters, allowing stakeholders to adjust and explore the dashboard on their own." — G2 reviewer, cited via Whatagraph 2026 review summary (../competitors/looker-studio.md)
- "Looker Studio makes it incredibly easy to build interactive, shareable dashboards all without coding." — User quoted in AgencyAnalytics 2026 review (../competitors/looker-studio.md)

**Theme: Mobile / push as a "carry the dashboard with you" export**
- "Triple Whale's Summary page on mobile is addictive with real-time profit data, push notifications, and clean design." — paraphrased consensus across multiple 2026 reviews (workflowautomation.net, headwestguide.com) (../competitors/triple-whale.md)
- "Real-time data, clean dashboards, mobile app, and automation consistently save operators 4–8 hours per week." — AI Systems Commerce, 2026 review (../competitors/triple-whale.md)

**Theme: SQL / warehouse access for "bring my own BI"**
- "Before Stripe Sigma, we built our own tool to analyse our Stripe data, but it took our engineers weeks to build, required ongoing work to maintain and update and it wasn't always accurate. Sigma now gives all our teams accurate data without any engineering work." — Tracy Rogers, Data Scientist, ClickFunnels, testimonial on stripe.com/sigma (../competitors/stripe-sigma.md)
- "SQL is quickly turning into the new Excel. Most excel power users at our company have picked up SQL easily." — teej, Hacker News (June 2017) (../competitors/stripe-sigma.md)
- "Glew.io is solving the challenge of consolidating data from multiple platforms into a single source of truth by automating data integration and ensuring accuracy" — G2 review summary, 2025 (../competitors/glew.md)

## What users hate about this feature

**Theme: Row-count caps / bulk-export fragility**
- "Can't export more than [a limited number of] customer records at once." — Nicolai G., Capterra, June 10, 2019 (../competitors/putler.md)
- "export very large records to CSV is a bit of issue" — yair P., Capterra, May 14, 2019 (../competitors/putler.md)
- "the data import was a bit slow" — Verified Reviewer, UX Designer, Capterra, October 31, 2021 (../competitors/putler.md)

**Theme: Stale schedule output / data freshness lag**
- "Sigma scheduled query results are not available until 2pm UTC the day after they run." — Chartsy blog, "What Is Stripe Sigma? Features, Pricing & Limitations" (2026) (../competitors/stripe-sigma.md)
- "Is the data freshness a joke? 2 DAYS to get data into the data warehouse?" — logvol, Hacker News (June 2017) (../competitors/stripe-sigma.md)
- "Two-day lag seems excessively laggy without a business reason." — koolba, Hacker News (June 2017) (../competitors/stripe-sigma.md)
- "Occasional delay in data updates. Some font sizes felt a bit small on 24-inch monitor." — Binaya A., Marketing Officer (Education), Capterra, January 28, 2025 (../competitors/lebesgue.md)

**Theme: Custom-report friction / paid-services upsell**
- "custom reports may seem restrictive, with an additional cost of $150 per hour for each one" — Shopify-store reviewer cited in search index (../competitors/glew.md)
- "constantly bothered to upgrade to the pro plan which costs 10x more" — Trustpilot reviewer (per search index summary) (../competitors/glew.md)
- "Alerts and report templates are lacking." — Itamar S., CEO, Capterra, March 17, 2021 (../competitors/putler.md)

**Theme: Lack of white-label / customisation in scheduled reports**
- "No White Labelling Option. Would like to customise the automated reporting." — Robin T., Marketing Consultant (Apparel), Capterra, September 26, 2025 (../competitors/lebesgue.md)
- "Lack of customisation." — Simon C., CEO (Alternative Medicine), Capterra, September 6, 2024 (../competitors/lebesgue.md)
- "It's not meant to be pretty." — User quoted in AgencyAnalytics 2026 Looker Studio review (re: client-facing white-labeling) (../competitors/looker-studio.md)

**Theme: Cancellation / billing friction tied to schedule continuance**
- "Worst experience ever, been charging me for months despite contacting them about cancellation." — Clear Cosmetics, Shopify App Store, March 4, 2026 (../competitors/beprofit.md)
- "Company has been paying $720 per year since — never used. They will not respond." — Adrienne Landau, Shopify App Store, April 22, 2026 (../competitors/beprofit.md)
- "Money grabbers / Intentionally difficult to cancel subscription" — Trustpilot reviewer (per search index summary) (../competitors/glew.md)

**Theme: SQL barrier despite "no SQL needed" marketing**
- "Sigma requires SQL, a skill that maybe a few people at your company actually have." — Definite blog (2026) (../competitors/stripe-sigma.md)
- "The AI generates SQL, and you're responsible for validating it. When the results look off (and they will, eventually) you need to debug the SQL yourself." — Definite blog, Stripe Sigma alternative guide (2026) (../competitors/stripe-sigma.md)
- "The problem is that Looker Studio is unable to do some stupidly simple sh|t, like adding two scorecards together, without ridiculous workarounds…Looker Studio is a clown car: interesting concept, but not equal to the big boys." — Reddit data analyst, r/GoogleDataStudio cited in Whatagraph 2026 review (../competitors/looker-studio.md)

**Theme: Mobile experience as export gap**
- "You can view only a limited number of reports on mobile." — bloggle.app review, 2024 (../competitors/polar-analytics.md)
- "With no mobile app, BeProfit can't match this flexibility" — TrueProfit comparison page (../competitors/beprofit.md)

## Anti-patterns observed

- **Schedule output stale on arrival.** Stripe Sigma's "scheduled query results are not available until 2pm UTC the day after they run" is the cleanest example — the very surface a finance team relies on for closing the books delivers numbers ~24h+ after the trigger. HN commenters called it "laggy without a business reason." Anti-pattern: scheduling without surfacing freshness in the artifact itself.
- **Custom reports as paid-services upsell.** Glew's "$150/hour for each [custom report]" charge converts what users perceive as a self-service feature into a billable engagement. Reviewers describe the experience as "constantly bothered to upgrade to the pro plan which costs 10x more." Anti-pattern: gating one-off custom reports behind a person rather than a UI.
- **Bulk export silently capped.** Putler reviewers report inability to export more than N customer records, with no clear indicator of the cap. Anti-pattern: list-export that silently truncates.
- **Cancellation friction tied to scheduled output.** BeProfit reviewers report being billed for months after cancellation requests; the export schedule keeps running because the subscription does. Anti-pattern: making cancellation harder than starting because scheduled outputs become a passive-revenue trap.
- **No white-label for the artifact that goes to the client.** Lebesgue and Looker Studio both surface this complaint — the recipient sees the vendor's branding instead of the merchant/agency's. Anti-pattern: scheduled report output is a marketing surface for the *vendor*, not the customer.
- **Locking attribution model into scheduled output without surfacing the model.** Multiple competitors schedule a "ROAS" digest without naming which attribution lens computed it; recipients can't tell whether it's platform-reported or pixel-attributed. Anti-pattern: source-of-truth ambiguity baked into the recurring artifact.
- **Activation pushes (CAPI, Klaviyo audience) priced as separate SKU on top of analytics.** Lebesgue's Le Pixel Enrichment ($149–$1,649/mo) and Polar's tiered Audiences/Advertising Signals are all paywalled separately from the dashboard. Anti-pattern (or business model, depending on the lens): the merchant sees activation as part of "exporting their data" but vendors monetize it as its own product.
- **Looker Studio asset-ownership on Free tier.** Reports belong to individual Google accounts; "if the user leaves the company, reports go with them." Anti-pattern: scheduled exports survive the employee but the report doesn't, breaking continuity for the recipient.

## Open questions / data gaps

- **Public-link permission UI** beyond Looker Studio is not directly documented in any competitor profile. Polar's Schedules deliver to Slack/Gmail but a "share this dashboard with my accountant via link" surface is not described. Worth a logged-in trial across Triple Whale / Polar to confirm whether they have a public-link option at all.
- **Embed iframe** is confirmed for Looker Studio only. Triple Whale, Polar, Glew may support embed (especially for agency portals) but it is not a marketed feature in any profile read.
- **Excel (XLSX) vs CSV** distinction is not surfaced in any competitor profile — every "export" reference defaults to CSV. Whether multi-sheet XLSX is a real competitor feature or a Nexstage-specific opportunity is unclear.
- **Webhook destinations** outside Stripe Sigma are not documented. Whether Triple Whale's BigQuery sync exposes a webhook-on-completion is unknown from public sources.
- **Slack OAuth scope vs incoming-webhook URL.** Profiles describe "Slack delivery" without specifying which integration model — Lifetimely's "Monday 8am" suggests scheduled posts via OAuth bot rather than incoming webhooks, but unconfirmed.
- **Mobile push as "export" lens.** Triple Whale mobile app is documented but whether push notifications are user-configurable per-metric or only fire on revenue milestones is not fully detailed.
- **Looker Studio Free tier asset-ownership risk** — if a Nexstage user pays for a comparable feature, what's our equivalent of Pro's "organisation-owned content"? Not directly observable from any competitor.
- **Verbatim quotes specific to scheduled-export delight or pain are sparse** in most profiles — most quotes are dashboard-level. The quotes captured here are the best available; some are paraphrased or from review-summary articles rather than direct verbatim sources.
- **PDF generation tooling** is not described — whether competitors render PDFs server-side (headless Chromium, wkhtmltopdf) or client-side is not disclosed in any profile.
- **No public observation of MCP usage by anyone other than Polar.** Polar's MCP server is the only "AI-agent-as-export-destination" pattern observed in this batch.

## Notes for Nexstage (observations only — NOT recommendations)

- **Scheduled email digest is universal (8 of 10 competitors); Slack is half (5 of 10).** A Nexstage workspace without scheduled email would be a category outlier — even Putler (which leads with list-CSV) has weekly email reports.
- **Public-link permission grid is a Looker Studio differentiator with no clear analog in the ecommerce-vertical category.** Polar/Glew/Triple Whale ship Slack + email destinations but do not appear to ship "anyone with the link" public dashboard URLs. Whitespace observed.
- **Promotion ladder (ad-hoc → saved → scheduled → published) is uniquely Stripe Sigma's pattern.** Worth examining as a way to map Nexstage's "filter / saved view / pinned tile / scheduled report" hierarchy to a single mental model.
- **Polar's per-block scheduling is the most granular pattern observed.** Most competitors schedule whole dashboards or pre-built reports; Polar lets a single chart on a dashboard go to a Slack channel on its own cadence.
- **MCP server as export destination (Polar) is the new 2026 pattern.** Treats external AI agents as first-class consumers — Nexstage's `MetricSourceResolver` could plausibly become an MCP-served semantic layer in the same way.
- **Lifetimely's Monday-8am-Slack and 7am-daily-email defaults are strongly opinionated** and praised in reviews. Default cadence + default time appear to matter more than configurability — reviewers don't seem to ask for arbitrary cron, they want sensible defaults.
- **Source-disagreement transparency in scheduled output is unobserved.** No competitor names which attribution lens or which source generated the numbers in the email/Slack artifact. Direct gap for Nexstage's 6-source-badge thesis — the badge could carry through into the digest.
- **"Recomputing…" banner from `UpdateCostConfigAction` has no analog in competitor scheduled outputs.** When a cost config change retroactively shifts past numbers, no competitor's scheduled report handles "the number I sent you yesterday is not the number I'd send you today." Worth designing for if Nexstage is going to ship retroactive recompute.
- **Activation push (CAPI / Klaviyo audience) is monetized separately by 3 competitors.** Polar Audiences + Advertising Signals, Triple Whale Sonar, Lebesgue Le Pixel Enrichment all charge for "push your data back out to ad platforms." Unclear whether Nexstage classifies CAPI/audience push as "export" or as a separate product line.
- **CSV row-count silent caps are a concrete review pain point** (Putler reviewers). Whatever Nexstage caps at should display the cap *before* the export starts, not silently truncate.
- **Cancellation friction is reputational poison and tied to scheduled output.** BeProfit reviewers explicitly mention being billed because cancellation didn't stop the recurring digest. In-app, transparent unsubscribe per scheduled artifact is a positive differentiator.
- **PDF is the format finance/board recipients actually want** — Looker Studio Schedule Email outputs PDF; Lebesgue MMM is PDF-only. CSV is for analysts; PDF is for stakeholders. The two artifacts are not interchangeable.
- **No competitor surfaces source provenance in the scheduled artifact itself.** Nexstage's 6 source badges (Real / Store / Facebook / Google / GSC / GA4) could appear in the email/Slack output the same way they appear on screen — observed as a gap in the category, not seen in any of the 10 profiles read.
- **Lebesgue uses blue (not green) for positive deltas in their reports** — conflicts with most competitors' green/red pattern. Worth flagging against Nexstage's color tokens (`--color-source-google`/`--color-source-facebook` are blues; positive-delta semantics may collide).
- **Mobile push notifications on revenue milestones are Triple Whale's distinctive export.** No other competitor in this batch ships mobile push as a first-class output channel. If Nexstage ships mobile, this is the bar.
- **Dedicated warehouse access (Glew BI Tunnel, Polar Snowflake, Triple Whale BigQuery sync) is exclusively a top-tier feature** — every competitor that exposes raw SQL gates it. Nexstage does not have a warehouse story today; if/when added, the existing pricing-tier convention is "warehouse = top tier."
- **Stripe Sigma's `data_load_time` variable in scheduled-query date-range parameterization is a subtle but smart pattern.** Lets the schedule deliver "data through last refresh" rather than "data through `now()`," avoiding off-by-a-few-hours embarrassment. Worth observing for Nexstage's snapshot freshness exposure.
