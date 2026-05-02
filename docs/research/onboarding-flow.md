# Onboarding Flow Research

Research conducted April 2026. Informs the `/onboarding` rebuild in `resources/js/Pages/Onboarding/`.

---

## 1. Onboarding step sequencing (competitor patterns)

### Step order consensus

From teardowns of Triple Whale, Polar, Lifetimely, Peel, Metorik, Putler, Klaviyo:

| Step | Pattern | Notes |
|---|---|---|
| 1 | Welcome / workspace name, currency, timezone | Klaviyo collects this; Polar auto-fills from store OAuth |
| 2 | Connect store (Shopify OAuth or WooCommerce REST key) | Forced step in every tool. "Connect Shopify" is always step 1 in App Store tools |
| 3 | Connect ad platforms (Facebook, Google) | Optional/skippable in all tools. Meta OAuth is the biggest drop-off point |
| 4 | Connect GSC + GA4 | Optional/skippable in all tools |
| 5 | Set costs (COGS upload, default margin) | Optional but flagged "data will be wrong until you do this" (Triple Whale) |
| 6 | Choose historical import window | Present in Putler (90d trial), Polar (1yr trial vs unlimited paid) |
| 7 | Done / import progress | Lifetimely shows explicit progress bar; Metorik populates as it imports |

### Best patterns

- **Putler**: Pre-populated demo dashboard on first screen. "Replace this demo with your data." Never shows empty state.
- **Polar**: Imports up to 1 year of history on free trial; unlimited on paid plans. First-class differentiation.
- **Lifetimely**: Setup Guide checklist survives past the wizard. "30 min for most stores, up to 24 hr for larger." Explicit progress bar.
- **Triple Whale**: Persistent sidebar checklist (Setup Guide) that users can revisit anytime.
- **Klaviyo**: Auto-extracts brand assets (name, currency, country, logo, colors) from connected store. Pre-fills workspace settings.
- **Shopify native**: Recommends show estimated time remaining per step ("Step 2 of 4 — 3 minutes remaining").

### Key onboarding insight

Shopify's own developer docs (2025) recommend: "Setup wizards with a progress indicator are best practice. Show step N of N and time remaining. Keep to ≤5 steps to avoid drop-off."

---

## 2. Historical import window per connector — API limits

### Facebook (Meta) Marketing API

- **Verified ceiling: 37 months** (not 36). The `date_preset=maximum` parameter returns a maximum of **37 months** of insights data.
- Source: Fivetran docs ("The maximum amount of historical data you can sync is 37 months") + Meta developer docs.
- **Exception (June 2025)**: Reach data with age/gender/country breakdowns limited to 13 months. Reach without breakdowns still goes to 37 months.
- **Practical recommendation**: Default import window = **36 months** is safe (well within the 37-month ceiling and avoids any edge-case data gaps near the boundary). Present as "3 years" in the UI.

### Google Ads API

- **No hard historical limit** on performance reporting data (campaigns, spend, clicks, conversions). Google's own docs state data older than 11 years may be removed but this is effectively unlimited for any SMB store.
- Supermetrics docs: "Google Ads — no explicit limit on historical performance data."
- **Practical recommendation**: Default import window = **36 months** (same as Facebook). This is safe for all stores.

### Google Search Console (GSC)

- **Hard ceiling: 16 months.** Google retains a maximum of 16 months of search performance data. Once data passes the 16-month window it is deleted permanently.
- Source: Google Search Console help docs + multiple third-party confirmations (DadSEO, DiggGrowth, dashthis).
- The Search Console Analytics API can only fetch the last 16 months.
- **Practical recommendation**: Default import window = **16 months** (the maximum possible). Label clearly in UI: "GSC stores up to 16 months — we'll import everything available."

### GA4 (Google Analytics 4)

- **Default retention: 2 months** (user and event data). Can be extended to **14 months** in GA4 settings (free tier). Analytics 360 can go to 26, 38, or 50 months.
- Standard reports (aggregated daily tables) are retained indefinitely; exploration reports are limited to 2–14 months.
- **Practical recommendation**: Default import = **14 months** (the max for free GA4). Caveat: depends on the user's GA4 retention setting. Label: "GA4 data availability depends on your property's retention setting (2–14 months for free properties)."

### Shopify Orders API

- **Default: last 60 days only.** Apps must request `read_all_orders` scope to access older orders.
- With `read_all_orders`: **No hard date ceiling** — can go back to the store's very first order.
- Shopify Polaris connector (Polar): "Backfills store history as far as Shopify allows" — effectively unlimited once scope is granted.
- **Practical recommendation**: With `read_all_orders` scope granted during OAuth, default import = **36 months**. Stores are rarely more than 10 years old anyway.

### WooCommerce REST API

- **No date restriction.** The WooCommerce REST API accepts `after` / `before` date filters and returns all orders matching. Paginated at 100 per page, no ceiling on date range.
- **Practical recommendation**: Default import = **36 months**. Can offer "All history" as an option since there's no API limit.

### Summary table

| Connector | API Ceiling | Recommended Default | Trial Default (≤14d trial) |
|---|---|---|---|
| Facebook Ads | 37 months | 36 months | 6 months |
| Google Ads | Effectively unlimited | 36 months | 6 months |
| GSC | 16 months | 16 months (max) | 6 months |
| GA4 | 14 months (free) | 14 months | 6 months |
| Shopify | Unlimited (with scope) | 36 months | 6 months |
| WooCommerce | Unlimited | 36 months | 6 months |

---

## 3. Trial vs paid scope decisions (competitors)

| Tool | Trial length | CC required | Historical data on trial | Historical data on paid |
|---|---|---|---|---|
| Polar Analytics | N/A (CSM-gated) | Demo call | 1 year | Unlimited (as far as Shopify allows) |
| Lifetimely / Amp | 14 days | No | Full sync (30min–24hr) | Full sync |
| Triple Whale | Indefinite free tier | No | Full sync (minutes) | Full sync |
| Metorik | 30 days | No | Full sync (120× monthly order vol cap) | Full sync |
| Putler | 14 days | No | **Last 90 days only on trial** | Unlimited |
| Peel | 7 days | No | Full sync (~24hr) | Full sync |

### Key finding: Putler's trial scope limit

Putler's trial version imports only the **last 90 days** of data per source, which matches their "get value fast" strategy — 90 days is enough to show meaningful metrics and ROAS trends without requiring a full multi-year backfill during a 14-day trial.

### Recommendation for Nexstage

- **Trial (≤14d)**: Import last **6 months** per connector. This gives enough seasonality signal for most SMBs (Black Friday + Christmas if not in trial period) and keeps import time manageable (typically under 10 minutes). Display: "Free trial imports 6 months. Upgrade to import up to 36 months."
- **Paid**: Up to **36 months** per connector (capped at the API ceiling per connector — 16 months for GSC, 14 months for GA4).
- **Rationale**: 6 months > Putler's 90 days (more generous, better first impression), well under Polar's 1 year (avoids long wait on trial). 36 months matches Facebook's practical ceiling and is the figure the user cited.

---

## 4. Progress UX (polling vs streaming, demo data, banners)

### Polling vs streaming

- All tools use **polling** (5–15 second intervals) for import progress — Server-Sent Events or WebSockets are not common here.
- Lifetimely: explicit "N of M orders (X%)" counter polled every 10s.
- Triple Whale: shimmer skeleton tiles that fill in as data arrives (no explicit count in free tier).
- Nexstage current: 5-second polling to `/api/stores/{slug}/import-status` — this is correct.

### Demo data fill while syncing (Putler pattern)

- Putler: pre-populated demo dashboard from the very first screen. Never empty.
- Motion: "Explore the inspiration library while data loads" — redirects user to a useful surface that doesn't require their data.
- **Best pattern for Nexstage**: Show demo dashboard with `DemoBanner` ("This is demo data for Acme Coffee Co — your data is importing") until first sync completes. Dashboard continues to work. Data populates incrementally. Demo data fades when real data lands.

### Banner vs panel

- Triple Whale and Polar both use a persistent **checklist panel** (sidebar/overlay) that survives past the wizard.
- Klaviyo uses a top-of-page banner for sync status.
- **Recommendation**: `DemoBanner` as a sticky top-of-dashboard banner (non-blocking, dismissible after first real data batch lands). Setup checklist sidebar item (future; v2 feature).

---

## 5. Sources

- Facebook API: https://fivetran.com/docs/connectors/applications/facebook-ads/troubleshooting/how-much-historical-data
- Meta API docs: https://developers.facebook.com/docs/marketing-api/insights/best-practices/
- GSC 16 months: https://searchengineland.com/google-search-console-analytics-api-now-has-16-months-of-data-300430
- GSC extended: https://getdadseo.com/blog/google-search-console-historical-data
- GA4 retention: https://support.google.com/analytics/answer/7667196
- Shopify API scope: https://community.shopify.dev/t/api-rest-read-all-orders-for-orders-older-than-60-days/6572
- Polar vs Triple Whale: https://www.polaranalytics.com/alternatives/triple-whale
- Shopify onboarding: https://shopify.dev/docs/apps/design/user-experience/onboarding
- Shopify onboarding best practices: https://www.shopify.com/partners/blog/improving-your-shopify-apps-onboarding-flow
