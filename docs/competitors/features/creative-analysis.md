---
name: Creative analysis
slug: creative-analysis
purpose: Tells the merchant which ad creatives are driving spend efficiency, which are fatiguing, and which to iterate or kill next.
nexstage_pages: ads, performance
researched_on: 2026-04-28
competitors_covered: motion, atria, triple-whale, adbeacon, thoughtmetric, northbeam, polar-analytics, cometly
sources:
  - ../competitors/motion.md
  - ../competitors/atria.md
  - ../competitors/triple-whale.md
  - ../competitors/adbeacon.md
  - ../competitors/thoughtmetric.md
  - ../competitors/northbeam.md
  - ../competitors/polar-analytics.md
  - ../competitors/cometly.md
  - https://motionapp.com/solutions/creative-reporting-tool
  - https://motionapp.com/solutions/creative-testing-tool
  - https://motionapp.com/library/talk/go-deeper-with-creative-insights/
  - https://intercom.help/atria-e5456f8f6b7b/en/articles/13862195-meet-raya-your-ai-creative-strategist
  - https://docs.northbeam.io/docs/creative-analytics
  - https://www.northbeam.io/features/creative-analytics
  - https://intercom.help/polar-app/en/articles/8888083-understanding-creative-studio
  - https://www.triplewhale.com/analytics
  - https://www.adbeacon.com/platform/
  - https://thoughtmetric.io/creative_performance
---

## What is this feature

Creative analysis answers a single operational question for SMB Shopify/Woo merchants spending on Meta, TikTok, Google, or YouTube: "Of the creatives I have running, which ones are paying their way and which ones should I cut or remix?" Every ad platform exposes creative-level metrics natively (spend, ROAS, CTR, hook rate). What converts that "having data" into "having the feature" is (a) the creative thumbnail anchored to the row instead of an opaque ad ID, (b) cross-platform consolidation so video tests on Meta + TikTok sit in one ranking, (c) decision artifacts (letter grades, triage badges, win/iterate/kill verdicts) that compress 8 metric columns into one action, and (d) AI-generated next-creative suggestions wired to a button.

For a merchant running 30-100 active ads across 2-3 platforms, the spreadsheet path takes a half-day weekly. The feature exists because that half-day is where creative iteration cadence actually lives or dies, and because the gap between platform-reported ROAS and store-side ROAS is sharpest at the creative tier (small N per ad, attribution noise dominates). SMBs especially feel the pain — they don't have a media-buyer-plus-strategist team to triage in real time.

## Data inputs (what's required to compute or display)

- **Source: Meta Ads API** — `ads.creative_id`, `ads.creative.image_url` / `video_id` / `thumbnail_url`, `ads.body` / `title` / `link_url`, `ads.spend`, `ads.impressions`, `ads.clicks`, `ads.actions[purchase]`, `ads.action_values[purchase]`, `ads.video_p25_watched_actions` … `video_p100_watched_actions`, `ads.video_avg_time_watched_actions`, `ads.inline_link_clicks`, `ads.outbound_clicks`, age + gender breakdowns, placement breakdowns
- **Source: TikTok Ads API** — `ad.video_id`, `ad.cover_image_url`, `ad.spend`, `ad.impressions`, `ad.clicks`, `ad.conversions`, `ad.video_play_actions`, second-by-second video retention curve (TikTok exposes this; Meta does not)
- **Source: Google Ads / YouTube API** — `ad.id`, `ad_group_ad.image_url`, `ad.video_id`, `metrics.cost_micros`, `metrics.impressions`, `metrics.clicks`, `metrics.conversions`, `metrics.video_views`, `metrics.video_quartile_p25_rate` … `p100_rate`
- **Source: Pinterest / Snapchat / LinkedIn Ads** — same five-metric baseline (spend, impressions, clicks, conversions, creative asset URL)
- **Source: Shopify / WooCommerce** — `orders.id`, `orders.total_price`, `orders.created_at`, `orders.utm_*` and `orders.landing_site` for store-side attribution so creative ROAS can be cross-checked against platform-reported ROAS
- **Source: First-party pixel (when shipped)** — `event.click_id` (fbclid / ttclid / gclid), `event.purchase_value`, `event.timestamp` for click-only attribution at creative level
- **Source: Computed** — `hook_rate = video_p3s_views / impressions`, `hold_rate = video_p100 / video_p3s`, `thumbstop_rate = video_p3s / impressions`, `cpa = spend / conversions`, `roas = revenue / spend`, `ctr = clicks / impressions`, `frequency = impressions / reach`
- **Source: Computed (verdict layer)** — letter grade per axis (school-style A-F mapped from quantile thresholds), triage classification (Winner / High Iteration Potential / Iteration Candidate / Kill), fatigue flag (frequency rising + CTR declining)
- **Source: AI-generated** — auto-applied creative tags (hook style, persona, format, offer type, messaging angle), spoken-word transcription of video creatives, AI-generated iteration briefs

## Data outputs (what's typically displayed)

- **Card / row primary visual: creative thumbnail** — image preview or video poster frame; videos sometimes hover-to-play. This is the anchor — the rest of the row attaches to it.
- **KPI per creative: Spend** — `SUM(spend)`, USD, sortable; recommended primary sort by Northbeam docs.
- **KPI per creative: ROAS** — `revenue / spend`, ratio, color-scaled (red→green) on most surfaces
- **KPI per creative: CPA** — `spend / conversions`, USD, color-scaled
- **KPI per creative: CTR** — `clicks / impressions`, percentage
- **KPI per creative: Hook rate / Thumbstop rate** — `3s_views / impressions`, percentage (Meta + TikTok only)
- **KPI per creative: Hold rate** — `p100 / p3s`, percentage (video only)
- **KPI per creative: Frequency** — `impressions / reach`, used to flag fatigue
- **Verdict: Letter grade** — A through F (Atria attests A-D; gethookd cites broader scale), per axis (Hook, Retention, CTR, ROAS or Conversion)
- **Verdict: Triage badge** — Winner / High Iteration Potential / Iteration Candidate / Kill
- **Dimension: Creative tag** — auto-applied across categories (Asset type, Visual format, Persona, Messaging angle, Seasonality, Offer type, Hook tactic, Headline tactic per Motion's 8-category schema)
- **Dimension: Platform** — Meta / TikTok / YouTube / LinkedIn / Pinterest / Snap / Google
- **Dimension: Launch cohort** — when the creative went live; drives "Launch Analysis" surface
- **Breakdown: Demographic** — impressions × {age cohort 13-17, 18-24, 25-34, 35-44, 45-54, 55-64, 65+} × {male, female, unknown}
- **Breakdown: Video retention curve** — y = % retained, x = video timestamp (Meta = quartiles, TikTok = second-by-second)
- **Breakdown: Source-disagreement view** — platform-reported ROAS vs store-side ROAS vs first-party pixel ROAS, per creative (only AdBeacon's Chrome overlay and Triple Whale's pixel implement this concretely at creative tier)
- **Slice: Creative × Funnel stage / Product / Messaging / Creator / Format** — Motion's "Comparative Analysis" axis set
- **Slice: Per launch cohort** — scaling / declining / early-winner status

## How competitors implement this

### Motion ([profile](../competitors/motion.md))
- **Surface:** Sidebar > Creative Analytics > {Top Performing Reports | Comparative Analysis | Launch Analysis | Ad Leaderboard | Winning Combinations}
- **Visualization:** Thumbnail-anchored sortable grid (signature "creative cards alongside metrics" pattern) + per-ad Creative Insights modal containing a video-retention line chart and a stacked-bar demographic breakdown
- **Layout (prose):** "Top: date-range + naming-convention filter + tag chips. Main canvas: sortable list/stack of ad rows where each row leads with the creative thumbnail (image or auto-playing video) and exposes ROAS / CPA / hook rate / watch time / spend in adjacent columns. Bottom: snapshot/share button. Modal: two-section vertical stack — top is a video CTR/retention line chart (Meta = quartile curve, TikTok = second-by-second plot point per second), bottom is grouped/stacked bars by age cohort split male/female/unknown."
- **Specific UI:** "Creative thumbnails are displayed alongside performance metrics in a stack/list view rather than a metrics-only table" — vendor copy verbatim. "Color-coded reports and intuitive charts." Click any thumbnail anywhere in the app → Creative Insights modal opens with retention chart on top.
- **Filters:** Date range, performance metric, naming-convention values, AI tags, ad account (single account per report), funnel stage, product, messaging, creator, format
- **Data shown:** Spend, impressions, clicks, conversions, ROAS, CPA, CTR, hook rate, watch time, conversion rate, hook/watch/click/convert scores, AI tags
- **Interactions:** Click thumbnail → modal; sort columns; filter chips; "Snapshot" button generates frozen-or-live shareable public URL (no login); "AI Task" button runs creative-diversity review or analysis, output filed to Inbox; Agent Chat for follow-up questions
- **Why it works (from reviews/observations):** "Having the actual creative displayed next to our metrics has also opened up a whole new world of what we can do in terms of strategy and driving insights" — David Adesman, ATTN Agency. "Motion links performance data with the actual creatives in a clean dashboard, making it much easier to answer questions like 'Which hook style is working?' or 'Which UGC format is driving the best ROAS?' without exporting a ton of data" — G2 reviewer aggregation.
- **Source:** [motion.md](../competitors/motion.md); https://motionapp.com/library/talk/go-deeper-with-creative-insights/

### Atria ([profile](../competitors/atria.md))
- **Surface:** Sidebar > Analytics > select ad account > Radar
- **Visualization:** Letter-grade rubric table (A-D per axis) + triage-badge classification + per-row "Iterate" CTA wired to AI generation. Plus an Inspo card grid for competitor swipe.
- **Layout (prose):** "Top: Radar Settings strip where the operator picks the key metric Raya grades against. Below: two primary tabs — Winners and High Iteration Potential — with Iteration Candidate as a third triage class consistently cited by third-party reviewers. Main canvas: portfolio table where every creative is a row with letter-grade columns (Hook, Retention, CTR, plus ROAS or Conversion depending on source) alongside ROAS / CTR / spend / AOV columns 'visible at a glance.' Each row terminates in an Iterate button."
- **Specific UI:** Letter grades A-D per axis; triage badges shaped as text labels (Winner / High Iteration Potential / Iteration Candidate). "Hover over column headers to understand grade rationale" — official help doc. Clicking into an ad reveals an AI-recommendation detail with named target personas (e.g. "Eco-conscious Consumers"), prioritized improvement actions, and concrete weakness flags ("weak CTAs or unclear value propositions"). The "Iterate" button launches an AI iteration workflow that generates an improved variant tuned to the flagged weakness.
- **Filters:** Date range, ad account, key-metric customization (Radar Settings), tags, brand profile
- **Data shown:** Letter grade × axis, ROAS, CTR, spend, AOV, AI-identified personas, specific improvement flags, transcribed video copy
- **Interactions:** Tab between Winners / High Iteration. Click "Iterate" → variant generation → 1-click bulk Meta upload. Clone a competitor's ad from Inspo → auto-filled brief → batch generation. AI-search across the 5M+ Ad Library corpus by spoken transcript or visual element.
- **Why it works (from reviews/observations):** "The Radar feature and creative breakdowns make it easy to see why certain ads perform and what needs to be adjusted to improve other ones" — G2 reviewer. "Atria is Meta ads manager on steroids" — Chris Cunningham, ClickUp. Help doc literally prescribes the cadence: "Check Radar weekly. It's the fastest way to know what to scale, what to kill, and what to iterate on."
- **Source:** [atria.md](../competitors/atria.md); https://intercom.help/atria-e5456f8f6b7b/en/articles/13862195-meet-raya-your-ai-creative-strategist

### Triple Whale ([profile](../competitors/triple-whale.md))
- **Surface:** Sidebar > Analytics > Creative Analysis (also branded as "Creative Cockpit"); Moby Creative Strategy Agent runs autonomously alongside.
- **Visualization:** Cross-platform thumbnail grid with sortable per-creative metric columns; "Creative Strategy Agent" surfaces fatiguing-creative alerts as separate inbox cards. Lighthouse-era anomaly cards have been folded into Moby Agents (Anomaly Detection, Revenue Anomaly).
- **Layout (prose):** "Sidebar > Analytics > Creative Analysis. Body: creative thumbnail cards/grid with sortable metrics columns; filter by platform (Meta / TikTok / Google), segment by ad set or campaign. Right rail / floating button: Moby Chat for natural-language follow-up. Creative Strategy Agent writes outputs back to dashboards or email."
- **Specific UI:** UI details not directly verified — KB.triplewhale.com 403'd to fetch in source research. Per third-party (Conspire) the surface is "creative dashboard… one of the many reasons why many rate Triplewhale so highly" with "cross-platform thumbnails + per-creative metrics including ROAS, CTR, AOV." Aazarshad cites "easy-to-understand visualizations… without requiring any complicated configuration."
- **Filters:** Date range, platform, attribution-model selector (Triple Pixel vs platform-reported vs first/last-click vs Total Impact), campaign / ad set scoping
- **Data shown:** Spend, ROAS (multiple lenses simultaneously), CTR, AOV, impressions, conversions; pixel-attributed revenue side-by-side with platform-reported revenue
- **Interactions:** Sort, filter, drill from creative to ad set; Moby Chat to query "which creative drove the most new customers last week?"; Creative Strategy Agent fires autonomous fatigue alerts; Snapshot share to dashboards / email / Slack.
- **Why it works (from reviews/observations):** "Triple Whale is the gold standard and leading platform for multi channel analytics" — Brixton, Shopify App Store. "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports" — Steve R., Capterra.
- **Source:** [triple-whale.md](../competitors/triple-whale.md); https://www.triplewhale.com/analytics

### AdBeacon ([profile](../competitors/adbeacon.md))
- **Surface:** Sidebar > Creative Dashboard; Creative Comparisons as a sibling surface; Chrome extension overlay inside Meta Ads Manager
- **Visualization:** Filterable creative grid with thumb-stop / fatigue / engagement columns (UI not directly observable, paywalled); side-by-side platform-reported vs AdBeacon-tracked columns inside the Meta Ads Manager Chrome overlay
- **Layout (prose):** "Sidebar nav surfaces 'Creative Dashboard' and 'Creative Comparisons' as separate items. Marketing copy: 'Filter your best-performing, most profitable Facebook ad creatives with a click.' Performance tracking across all creatives includes thumb-stopping rates, ad fatigue, engagement. Specific UI not observable beyond marketing copy."
- **Specific UI:** Chrome extension overlay (the only directly described surface) injects a panel into facebook.com/adsmanager at Ad Set + Ad level (no Campaign level). Side-by-side columns: Meta-reported metrics next to AdBeacon-tracked metrics, with an inline attribution-model toggle (First Click / Last Click / Linear / Full Impact) and a per-account dropdown for agencies. Customizable metric chooser.
- **Filters:** Ad set, date, campaign, attribution model (4 options), tracked client account
- **Data shown:** Profit per creative, thumb-stop rate, ad fatigue indicator, engagement, ROAS by creative; in the overlay: tracked purchases, attribution-based revenue, orders, ROAS, custom KPIs
- **Interactions:** Filter to surface profitable creatives; switch attribution model in real-time inside Meta Ads Manager; toggle between connected client accounts
- **Why it works (from reviews/observations):** "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey" — Agency testimonial, AdBeacon homepage. The overlay is the visual proof of their click-only-vs-platform-reported thesis.
- **Source:** [adbeacon.md](../competitors/adbeacon.md); https://www.adbeacon.com/the-adbeacon-chrome-extension-independent-attribution-inside-meta-ads-manager/

### ThoughtMetric ([profile](../competitors/thoughtmetric.md))
- **Surface:** Product > Analytics > Creative
- **Visualization:** Sortable creative list (thumbnail-vs-row layout not observable from public sources); side-by-side Meta vs Google Ads bar charts for comparative analysis
- **Layout (prose):** "Sortable creative list with 'Side-by-side ad performance comparison in unified dashboard.' Two filter axes called out: a sort selector ('Sort creatives by ROAS, sales, CPA metrics instantly') and a visual-format toggle ('Toggle between video and image ads'). Whether creatives render as a thumbnail grid vs. a row list is not observable from public sources."
- **Specific UI:** "Bar charts for comparative analysis," platform-tagged Meta and Google Ads visualisations on widget mockups. UI details not available — only feature description seen on marketing page.
- **Filters:** Sort by ROAS / sales / CPA, video vs image toggle, platform toggle (Meta / Google Ads / TikTok / Pinterest), 5 attribution models, 7/14/30/60/90-day attribution window
- **Data shown:** ROAS, Sales volume, CPA, MER, Spend allocation
- **Interactions:** Sort by performance KPI, video vs image filter, drill from creative into ad set / ad. Conversion API push back to Meta / Google / TikTok feeds enriched first-party events to ad-platform optimization.
- **Why it works (from reviews/observations):** "Dead simple integration; beautiful reports showing insights, not data overload" — Stockton F., Capterra. "TM has really helped us understand what's working and what's not; trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time" — WIDI CARE, Shopify App Store.
- **Source:** [thoughtmetric.md](../competitors/thoughtmetric.md); https://thoughtmetric.io/creative_performance

### Northbeam ([profile](../competitors/northbeam.md))
- **Surface:** Sidebar > Attribution > Creative Analytics tab (also surfaced on the Attribution Home Page)
- **Visualization:** Thumbnail-anchored creative-card grid with metrics rendered "on a color scale from red (negative) to green (positive)." Below the grid: a comparison line/bar chart canvas accepting up to 6 ads for trend overlay.
- **Layout (prose):** "Top: search box + 'hide inactive ads' toggle + metric-selector control + sort control. Main canvas: grid of creative cards — each card shows a visual preview of the ad alongside performance metrics colored on a red→green gradient. Below or alongside the grid: a comparison chart canvas that accepts up to 6 ads and renders them as line or bar graphs for trend overlay."
- **Specific UI:** Defaults: last 7 days, attribution mode = Clicks + Modeled Views, window = 1-day click / 1-day view, accounting basis = Accrual (Cash Snapshot is unsupported on this surface). Up-to-6-ads multi-select for line/bar comparison chart. "Copy dashboard view link to clipboard" share button. Saved Views menu. "Dynamic creatives lack visual previews but retain complete performance data" — explicit edge case.
- **Filters:** Search, hide-inactive toggle, metric-selector pivot, sort (recommended starting point: by Spend), date range, attribution model, attribution window
- **Data shown:** Recommended display of CTR, CPM, ECR (1-day), CAC (1-day), ROAS. Cross-platform: Facebook, Instagram, Snapchat, TikTok, Google, Pinterest, YouTube.
- **Interactions:** Search to filter; sort by spend; pivot on metric selector; multi-select up to 6 ads to overlay in comparison chart; toggle bar vs line; share link copies the entire view state; save view to reuse
- **Why it works (from reviews/observations):** Cross-platform creative grid with red-to-green scale is widely cited as the deepest pre-Motion implementation. Northbeam supports the broadest platform set in this category (7 platforms in one creative grid).
- **Source:** [northbeam.md](../competitors/northbeam.md); https://docs.northbeam.io/docs/creative-analytics

### Polar Analytics ([profile](../competitors/polar-analytics.md))
- **Surface:** Sidebar > Paid Marketing > Creative Studio
- **Visualization:** Comparison workspace, **Meta-only**: bar chart (default), card view (thumbnails as cards), or "Performance Over Time" multi-line trend chart
- **Layout (prose):** "Top of screen: a creative-type dropdown (images / videos / copy / landing pages). User chooses up to 5 creatives to compare. Two selection modes side-by-side: 'Edit Selection' (manual) or 'Sort top performers' (auto). Body: three view toggles — Chart View (default bar chart with selected metrics side-by-side), Card View (creative thumbnails as cards instead of bars), Performance Over Time (multi-line trend chart with hover tooltips showing date-specific values per creative)."
- **Specific UI:** Metrics dropdown picks up to 4 metrics simultaneously (Clicks, Impressions, ROAS, etc.). Sort direction toggle (highest / lowest first). "Reorder x-axis by any selected metric ascending/descending." Hover for date-level detail in trend view.
- **Filters:** Creative type (images / videos / copy / landing pages), 4-metric selection, sort direction, date range
- **Data shown:** Configurable from up to 4 of: spend, impressions, clicks, CTR, CPC, ROAS, conversions
- **Interactions:** Edit Selection (manual) or Sort top performers (auto); switch between Chart / Card / Performance Over Time views; hover trend line for date-specific values
- **Why it works (from reviews/observations):** Limited verbatim review data — Polar Analytics G2 / Trustpilot / Capterra all returned 403 to fetch. The 5-creatives-and-4-metrics constraint is unusual: most competitors are unbounded. Polar's deliberate cap forces the operator into a bounded comparison frame.
- **Source:** [polar-analytics.md](../competitors/polar-analytics.md); https://intercom.help/polar-app/en/articles/8888083-understanding-creative-studio

### Cometly ([profile](../competitors/cometly.md))
- **Surface:** Sub-surface within Cometly's Ads Manager (creative-level performance reachable via drill-down from campaign → ad set → individual ad → creative)
- **Visualization:** No standalone creative dashboard — creative-level performance is the leaf of a campaign drill-down table. Reports module includes a named "Creative" report block.
- **Layout (prose):** "Drill-down from campaign → ad set → individual ad → creative-level performance. Bulk actions in the same table: 'Manage budgets, pause under performers, and scale winners directly from Cometly without switching ad platforms' — i.e. the table is read-write, mutating the upstream ad platform via API."
- **Specific UI:** Read-write table; AI chat alongside surfaces "AI recommendations to optimize spend, creatives, and targeting." Reports module exposes "Creative" performance tracking as a named block with chart + customer-journey visualization.
- **Filters:** Date range, period-comparison toggle, advanced filtering, grouping by time/source/URL/ad/campaign/country
- **Data shown:** Spend, impressions, clicks, conversions, revenue (Cometly-attributed), ROAS, CPA, custom metrics built from formulas, "continuous LTV tracking per customer"
- **Interactions:** Drill from campaign down to creative; bulk pause / scale / budget edits in-table; AI chat for recommendations
- **Why it works (from reviews/observations):** No verbatim creative-specific review data surfaced. Cometly's strength is the read-write campaign table; creative-level analysis is shallower than Meta+Google parity competitors, and the 2024-era tripleareview comparison stated "advanced features like… creative testing are not available for Google Ads." Marketing copy in 2026 implies parity but the gap may persist.
- **Source:** [cometly.md](../competitors/cometly.md)

## Visualization patterns observed (cross-cut)

- **Thumbnail-anchored creative grid:** 5 competitors (Motion, Northbeam, Triple Whale, Polar Card View, AdBeacon — and ThoughtMetric likely, but unobservable). Motion is the explicit category benchmark — "Motion's UI/UX have set the standard in the space" per third-party aggregation; Northbeam ships the same pattern with the additional red→green color-scale layer. Universally praised.
- **Letter-grade rubric (A-F-style):** 1 competitor (Atria's Radar), but the pattern is so distinctive — and the school-grade vocabulary so much more actionable for SMB operators than 4-decimal ROAS — that it stands as a category-defining differentiator. Combined with triage badges (Winner / High Iteration / Iteration Candidate) it becomes a verdict layer on top of the metric layer.
- **Triage badges:** 1 competitor (Atria) explicitly. Triple Whale's Creative Strategy Agent functions as an implicit version (fatiguing-creative alerts), but without the visible badge taxonomy. The badge-on-the-card pattern is rare and concentrated.
- **AI tags / auto-classification:** 2 competitors (Motion's 8-category schema; Atria's hooks/personas/themes/USPs). Both auto-apply tags rather than requiring manual tagging — explicitly cited as a labor-saver.
- **AI-generated iteration suggestions wired to a button:** 1 competitor (Atria's "Iterate" CTA → variant generation → 1-click Meta upload). Triple Whale's Moby 2 (rolling out April 2026) adds ad-creative generation + direct Meta publishing; Motion's AI Tasks generate analysis but not creatives. Atria is alone in closing the analyze→generate→upload loop in one button as of April 2026.
- **Side-by-side platform-reported vs first-party-tracked columns at creative tier:** 2 competitors (AdBeacon Chrome extension overlay, Triple Whale Creative Cockpit with Triple Pixel). This is the source-disagreement view at the creative grain — and it's directly analogous to the Nexstage 6-source-badge thesis applied to a creative row.
- **Video retention curve (per-ad):** 1-2 competitors (Motion's Creative Insights modal explicitly; TikTok second-by-second is unique to Motion; Meta quartile curve appears in Motion and likely elsewhere but not directly observable). The TikTok second-by-second granularity is unique.
- **Demographic age × gender breakdown (per-ad):** 1 competitor (Motion's Creative Insights modal — grouped/stacked bar chart by age cohort split male/female/unknown). Not observed elsewhere as a standalone in-modal view.
- **Multi-creative bounded comparison (≤6 ads):** 2 competitors (Northbeam — up to 6 ads in line/bar overlay; Polar — up to 5 creatives × 4 metrics). Bounded comparison is a deliberate design choice contra Motion's unbounded ranking lists.
- **Color convention:** Red→green gradient on metric cells is universal where directly observable (Northbeam explicitly; Motion "color-coded reports"). No alternative color scheme observed.
- **Cross-platform consolidation:** Northbeam (7 platforms) > Motion (4: Meta + TikTok + YouTube + LinkedIn) > Triple Whale (cross-platform creative cockpit, exact platform list not observable). Polar's Creative Studio is **Meta-only** despite supporting other platforms elsewhere — explicit gap.
- **Read-write creative table:** 1 competitor (Cometly — "manage budgets, pause under performers, and scale winners directly from Cometly without switching ad platforms"). Triple Whale has direct ad-platform controls but exposed as separate Ad Budget Management surface, not embedded in Creative Analysis. Most others are read-only.

## What users love about this feature (themes + verbatim quotes from competitor profiles)

**Theme: Creative-next-to-the-metric removes spreadsheet pain**

- "Having the actual creative displayed next to our metrics has also opened up a whole new world of what we can do in terms of strategy and driving insights." — David Adesman, ATTN Agency, [motion.md](../competitors/motion.md)
- "Motion links performance data with the actual creatives in a clean dashboard, making it much easier to answer questions like 'Which hook style is working?' or 'Which UGC format is driving the best ROAS?' without exporting a ton of data." — G2 reviewer aggregation, [motion.md](../competitors/motion.md)
- "Our agency loves using Motion! It gives us direct insights into which ad creatives are performing and how we can conduct creative sprints to iterate on top performers." — Krista Karpan, Product Hunt review, [motion.md](../competitors/motion.md)
- "Motion was the missing link in helping our media buyers and creatives see eye-to-eye on ad performance." — Cody Plofker, Jones Road Beauty, [motion.md](../competitors/motion.md)

**Theme: Verdict layer compresses the decision**

- "The Radar feature and creative breakdowns make it easy to see why certain ads perform and what needs to be adjusted to improve other ones." — G2 reviewer, [atria.md](../competitors/atria.md)
- "Atria is Meta ads manager on steroids." — Chris Cunningham, ClickUp, [atria.md](../competitors/atria.md)
- "These have saved me so much time with ideation and strategy so that I can focus on ad creation." — G2 reviewer praising Inspo + AI Recommendations + Radar + Clone Ads, [atria.md](../competitors/atria.md)
- "Motion solves for analysis paralysis by providing digestible insights which makes it easy to work with creative teams and streamline the creative iteration process." — Josh Yelle, Director of Paid Social, Wpromote, [motion.md](../competitors/motion.md)

**Theme: Source disagreement made visible at creative grain**

- "Meta traditionally over-reports… AdBeacon showed a .93, and Meta's showing a 3.23. Through attribution, we can see the entire customer journey… they (customers) love that." — Agency testimonial, [adbeacon.md](../competitors/adbeacon.md)
- "TM has really helped us understand what's working and what's not; trusting attribution from ad platforms will lead you to make budgeting mistakes, they over attribute all the time. That's why ThoughtMetric is a must!" — WIDI CARE, [thoughtmetric.md](../competitors/thoughtmetric.md)
- "The transparency of the data, Triple Whale demonstrated that we had 3 times the amount of users on site vs Klaviyo's metrics." — Steve R., Capterra, [triple-whale.md](../competitors/triple-whale.md)

**Theme: Action-coupled AI (analysis → generate → publish)**

- "Just stumbled upon Atria, and it's lowkey changing my ad game. One click, and I'm saving Meta and TikTok ads like a pro. The AI stuff is cool—automagic video transcriptions and organized boards." — G2 reviewer, [atria.md](../competitors/atria.md)
- "10X my growth output." — Aazar Ali Shad, [atria.md](../competitors/atria.md)
- "Apart from the incredible attribution modelling, the AI powered 'Moby' is incredible for generating reports." — Steve R., [triple-whale.md](../competitors/triple-whale.md)

**Theme: Visual aesthetic itself is a feature**

- "The look & feel of Motion is ✨✨✨" — Carlo Thissen, tl;dv (Product Hunt), [motion.md](../competitors/motion.md)
- "Super excited about what Motion is building... Creative is the path to growth. Motion is empowering teams to win." — Ben Jabbawy, Privy, [motion.md](../competitors/motion.md)

## What users hate about this feature

**Theme: Spend-scaled / opaque pricing locks out creative analytics for SMB**

- "I almost had a heart attack. Motion would eat into our margins way more than we're comfortable with." — Josh Graham, Founder @ Alpha Inbound, [motion.md](../competitors/motion.md)
- "The price is very high." — Paul Fairbrother, Creative Strategist, [motion.md](../competitors/motion.md)
- "Pricing increases as you increase ad spend :(" — Foreplay's Motion comparison page, [motion.md](../competitors/motion.md)
- "Requires sales call to get started" / "Long Contracts" — Foreplay's Motion comparison page, [motion.md](../competitors/motion.md)
- "The $159 starting price is a barrier for solo creators." — traksource.com on Atria, [atria.md](../competitors/atria.md)
- "Pricing is too high for beginners and solo marketers." — Trustpilot/G2 summary on Atria, [atria.md](../competitors/atria.md)

**Theme: Single-account / single-platform scoping breaks reality**

- "Each Motion report is scoped to a single ad account" — i.e., reports do not consolidate cross-account data into one view — Superads aggregation, [motion.md](../competitors/motion.md)
- "Users find it frustrating that you can only use one account per platform, as on Meta, they would like to be able to use two accounts at once." — G2 reviewer aggregation, [motion.md](../competitors/motion.md)
- "Creative Studio is Meta-only. TikTok/Google video creative not analyzed in the same surface despite TikTok and Google Ads being supported elsewhere." — observation in [polar-analytics.md](../competitors/polar-analytics.md)

**Theme: AI generates output that doesn't match reality**

- "Some users reported that the Clone Ad tool's AI significantly altered the look of their products, with results that do not even closely resemble the original products." — G2 review summary, [atria.md](../competitors/atria.md)
- "The software did not provide meaningful or actionable data to identify or scale top-performing creatives, and AI-generated ads were below usable quality standards." — Trustpilot reviewer, [atria.md](../competitors/atria.md)
- "The AI transcriber is excellent, but it can struggle with highly stylized TikTok ads that have extremely loud, chaotic background music or very fast, muddled speech." — G2 reviewer, [atria.md](../competitors/atria.md)

**Theme: Performance / loading / lag**

- "Too slow, laggy, a little pricey, lacks some functionality like DTC variation." — AdCreatiiv, Product Hunt, [motion.md](../competitors/motion.md)
- "Users report that Motion can be slow at times, and often face technical issues with slow loading times, bugs, and limited metrics." — G2 reviewer aggregation, [motion.md](../competitors/motion.md)
- "Besides some slow loading for heavier dashboards, everything has been great." — User cited in third-party review summary on AdBeacon, [adbeacon.md](../competitors/adbeacon.md)

**Theme: Trial-to-paid friction (Atria-specific but worth flagging)**

- "I signed up for free trial for 14 days, after 14 days, 1 month subscription fees for pro plan deducted automatically from my debit card… no reply at all even when I followed up with many emails!" — Mohammad F., Sitejabber, [atria.md](../competitors/atria.md)
- "The cancel button on their site doesn't work, with users trying many times on different browsers and still getting charged over a hundred dollars." — Trustpilot reviewer, [atria.md](../competitors/atria.md)

## Anti-patterns observed

- **Single-platform Creative Studio (Polar Analytics):** Polar's Creative Studio is Meta-only despite the rest of the product supporting TikTok, Google, and YouTube. The result is a tool merchants can't use to compare a TikTok video against its Meta cousin — exactly the side-by-side decision the feature is meant to enable. [polar-analytics.md](../competitors/polar-analytics.md)
- **Single-ad-account-scoped reports (Motion):** "Each Motion report is scoped to a single ad account." Agencies and multi-store brands have to switch reports rather than blend. Creative analysis without consolidation breaks the canonical "rank all my ads" question. [motion.md](../competitors/motion.md)
- **Creative drilldown buried in campaign hierarchy (Cometly):** Creative-level performance is the leaf of a campaign-level table rather than a first-class surface. Result: the merchant has to know the campaign before they can find the creative — backwards from how the question is asked ("which creative is winning?"). [cometly.md](../competitors/cometly.md)
- **Letter grades without published rubric (Atria):** Atria's A-D scale is third-party-attested; the official help center never publishes the grade thresholds. Without rubric transparency, "this ad is a B" is a black box, undermining the whole verdict-layer pretension. [atria.md](../competitors/atria.md)
- **Hidden source disagreement at creative tier:** Most competitors show ROAS as a single number on a creative card. Only AdBeacon (Chrome overlay) and Triple Whale (Pixel + platform side-by-side) actually expose the platform-vs-store-vs-pixel disagreement at creative grain. The default of one number hides the disagreement that IS the information. [adbeacon.md](../competitors/adbeacon.md), [triple-whale.md](../competitors/triple-whale.md)
- **Dynamic creatives with no thumbnail:** Northbeam explicitly notes: "Dynamic creatives lack visual previews but retain complete performance data." Whatever the workaround, this is the failure mode of a thumbnail-first design — Meta's DCO ads have no static asset to display. Worth surfacing in product docs rather than letting users discover. [northbeam.md](../competitors/northbeam.md)
- **AI iteration that distorts the source product (Atria Clone Ad):** Auto-generation that "significantly altered the look of their products" is worse than no generation — it produces unusable output that costs the operator review time. Action-coupled AI cuts both ways. [atria.md](../competitors/atria.md)

## Open questions / data gaps

- **Atria's exact letter-grade rubric and color treatment.** Sources disagree on the fourth grading axis (max-productive cites Conversion; gethookd cites ROAS). Atria's own help center does not enumerate. A paid trial is the only path to ground truth on grade thresholds, badge shapes, and hover behavior. [atria.md](../competitors/atria.md)
- **Triple Whale's Creative Cockpit UI.** KB.triplewhale.com 403'd to WebFetch; the column layout, sort defaults, and how Creative Strategy Agent outputs surface in-context are reconstructed from third-party reviews only. Direct screenshot capture would require a free-tier signup. [triple-whale.md](../competitors/triple-whale.md)
- **AdBeacon's main Creative Dashboard UI** (not the Chrome overlay). Paywalled; only feature description seen on marketing pages. Specific chart types, filter chrome, and whether thumbnails render at all not directly observable. [adbeacon.md](../competitors/adbeacon.md)
- **ThoughtMetric's creative grid layout.** Marketing pages use stylized widget mockups; whether creatives render as a thumbnail grid vs. a row list is not observable from public sources. [thoughtmetric.md](../competitors/thoughtmetric.md)
- **Motion AI Tags 8th category.** Seven of the eight categories are named in public sources (Asset type, Visual format, Persona, Messaging angle, Seasonality, Offer type, Hook tactic, Headline tactic — but the eighth not explicitly identified). [motion.md](../competitors/motion.md)
- **TikTok second-by-second retention chart implementations beyond Motion.** Only Motion's public talk transcript directly described this; whether Northbeam, Triple Whale, or Atria expose the same TikTok-specific granularity is not observable.
- **AI iteration suggestion implementations beyond Atria.** Triple Whale Moby 2 (April 2026 rollout) adds creative generation but production-readiness and UI are not yet observable. Cometly's "AI recommendations to optimize creatives" is in marketing copy only, no UI detail. [triple-whale.md](../competitors/triple-whale.md), [cometly.md](../competitors/cometly.md)
- **Whether any competitor exposes creative tags as filterable dimensions in the grid header.** Motion implies this ("filter by tags"); Atria implies this for hook/persona/theme/USP. Whether tag chips appear inline on the creative card or only as filter rail items is not directly observable.

## Notes for Nexstage (observations only — NOT recommendations)

- **The thumbnail-anchored grid is universal among the strongest implementations.** Motion is the category benchmark; Northbeam mirrors it with the red→green color scale; Triple Whale and AdBeacon claim it (UI unobservable). Polar Card View, ThoughtMetric, and Atria all converge on creative-thumbnail-as-row-anchor. Tables without thumbnails are not observed in this feature.
- **Cross-platform consolidation is uneven.** Northbeam (7 platforms) is the only competitor confirmed to consolidate Meta + TikTok + Google + YouTube + Pinterest + Snap + LinkedIn in one creative grid. Motion supports 4 (and explicitly cannot blend across them in one report). Polar's Creative Studio is Meta-only. The whitespace for any new entrant is "true cross-platform creative ranking" — and it requires merchants to give us all those ad-platform connections, which Nexstage may or may not have today.
- **The verdict layer (letter grade + triage badge) is a single-competitor pattern (Atria) with strong reception.** It compresses 8 metric columns into one action verb. The Nexstage 6-source badge thesis is *orthogonal* — source badges classify which lens you're viewing; Atria's badges classify which creatives to scale/iterate/kill. Both are categorical badges on a row but they answer different questions.
- **Action-coupled AI is bimodal: Atria's "Iterate" button generates a variant; Motion's "AI Task" generates an analysis.** Reviews are warmer on the former. The bar Atria sets is "the AI output is wired to a button that does the next thing," not "here's a paragraph of insight."
- **Source disagreement at creative tier is implemented by 2 competitors (AdBeacon, Triple Whale) and is praised in reviews as the one thing that converted skeptics.** "Meta showed 3.23, AdBeacon showed .93" — the disagreement IS the information. The Nexstage 6-source thesis applied at creative grain (Real / Store / Facebook / Google ROAS for the same ad row) has no full-fidelity competitor implementation.
- **Bounded comparison (Polar's 5×4, Northbeam's 6-ad overlay) is a deliberate constraint, not a limitation.** It forces the merchant into a comparison frame instead of an infinite ranking list. Worth weighing against the Motion-style "rank everything" default.
- **Pricing is the recurring complaint.** Motion's "I almost had a heart attack" and Atria's auto-upgrade controversy are the loudest. Both use multidimensional pricing axes (spend × seats × storage × credits × brand-follow slots for Atria; ad-spend cap for Motion). ThoughtMetric's flat-pricing model is the explicit counter-example.
- **Dynamic creatives without thumbnails are an unsolved edge case.** Northbeam ships graceful degradation ("retain complete performance data"); other competitors don't acknowledge it. Meta's DCO and Advantage+ creative ads will trigger this fallback path.
- **Creative tagging is auto-applied where it exists (Motion, Atria), never manual.** Manual tagging UX is not observed in this feature space — operators don't tolerate it. If Nexstage ever adds creative tags, automation is table-stakes.
- **No competitor exposes GSC or organic search alongside paid creative.** Creative analysis is uniformly paid-channel. The "creative" frame may be inseparable from "paid" in users' minds.
- **No competitor surfaces creative on a mobile app first-class.** Triple Whale and Polar ship mobile apps, but their creative-cockpit surfaces aren't called out as mobile-optimized. Motion has no mobile app at all. The phone-at-7am use case for creative analysis appears unowned.
