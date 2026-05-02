# Fact-Check Corrections

Every specific claim verified against official sources. Items marked WRONG have been corrected in the plan docs.

---

## Integration API Errors (integrations.md, coding-spec.md)

| # | Claim | Verdict | Correct Value | Source |
|---|-------|---------|---------------|--------|
| 1 | Shopify rate limits: 100/200/1000/2000 pts/sec | **WRONG** | **50 Standard, 100 Advanced, 500 Plus**. Every number was doubled. | shopify.dev/docs/api/usage/limits |
| 2 | Meta API v22.0 released Feb 2026 | **WRONG** | v22.0 was Jan 2025. **Current is v25.0** (Feb 2026). Off by 3 major versions. | developers.facebook.com |
| 3 | Meta level=ad returns all levels in one call | **WRONG** | level=ad returns ad-level only. Need separate calls per level. | developers.facebook.com/docs/marketing-api/insights |
| 4 | Google Ads API v19 is current | **WRONG** | v19 sunset Feb 2026. **Current is v23.1**. | ads-developers.googleblog.com |
| 5 | GA4 Data API v1 stable | **WRONG** | Still **v1beta**. Not GA v1. | developers.google.com/analytics |
| 6 | GA4: 10,000 requests/day | **WRONG** | Uses **token-based quota**: 200,000 tokens/property/day, 40,000/property/hour. 10K was old Universal Analytics. | developers.google.com/analytics/devguides/reporting/data/v1/quotas |
| 7 | Klaviyo revision: 2024-10-15 | **WRONG** | Current is **2026-04-15**. Was 18 months stale. | developers.klaviyo.com |
| 8 | Klaviyo 75 req/s is universal | **WRONG** | 75/s is L-tier only. Endpoints have different tiers (XS-XL). | developers.klaviyo.com/en/docs/rate_limits |
| 9 | Klaviyo predictive requires CDP plan | **WRONG** | Included in **all Marketing plans**. Requires 500+ customers, 180+ days history. | help.klaviyo.com |
| 10 | WC COGS native since 9.5 | **WRONG** | Experimental ~9.7-9.9, **production in 10.3** (Oct 2025). | developer.woocommerce.com |
| 11 | Meta 37 months for all breakdowns | **PARTIAL** | 37mo for aggregates, but **Reach limited to 13mo** with breakdowns. | developers.facebook.com |
| 12 | Google Ads 15K req/day | **PARTIAL** | 15K for Basic Access only. **Standard Access is unlimited.** | developers.google.com |
| 13 | WC REST API v3, no v4 | **PARTIAL** | v4 exists internally but v3 is the public documented version. Mostly correct. | developer.woocommerce.com |

## Database Schema Errors (database-schema.md)

| # | Claim | Verdict | Correct Value |
|---|-------|---------|---------------|
| 14 | Shopify financial_status: paid, partially_refunded, refunded, pending | **INCOMPLETE** | Missing: `authorized`, `expired`, `partially_paid`, `voided` |
| 15 | Shopify customers.orders_count, total_spent | **WRONG** | **Removed in API version 2025-01.** Must compute from orders. |
| 16 | Shopify accepts_marketing | **DEPRECATED** | Still works but `email_marketing_consent` object is the current field. |
| 17 | WC COGS structure: cost_of_goods_sold.effective_value | **WRONG** | Actual: `cost_of_goods_sold.values[].effective_value` (nested in `values` array). |
| 18 | WC product types: simple, variable, grouped | **INCOMPLETE** | Missing: `external`. |

## Tech Stack Errors (tech-stack.md)

| # | Claim | Verdict | Correct Value |
|---|-------|---------|---------------|
| 19 | Deferred props, partial reloads, WhenVisible are Inertia v3 features | **WRONG** | These were introduced in **Inertia v2.0**. v3 continues to support them. |
| 20 | ApexCharts has NO native waterfall | **WRONG** | Current ApexCharts has `rangeBar` which supports waterfall. |
| 21 | ECharts version not specified | **MISSING** | Latest is **ECharts 6.0.0**. vue-echarts is v8.0.1. |

## Market/Competitor Claim Errors (feature-list.md, research docs)

| # | Claim | Verdict | Correct Value |
|---|-------|---------|---------------|
| 22 | COD penetration Italy 40-55% | **WRONG** | Actual: **4-11%** of transactions. We confused "stores offering COD" with "orders paid via COD". |
| 23 | COD Greece 50-65% | **PARTIAL** | Range is 25-63% depending on sector. Plausible but uncertain. |
| 24 | COD Romania 60-70% | **VERIFIED** | 60-65% confirmed by ARMO. |
| 25 | Mobile CWV pass rate: 42% | **WRONG** | 2025 data shows **48%**. 42% was ~2023. |
| 26 | Mobile cart abandonment 85% | **HIGH** | Sources say 76-84%. More defensible: **~80%**. |
| 27 | Desktop cart abandonment 70% | **HIGH** | Sources say 64-72%. More defensible: **~67%**. |
| 28 | Server-side tracking recovers 37% more conversions | **IMPRECISE** | Industry range is **20-40%**. 37% is within range but shouldn't be cited as exact. |
| 29 | 100ms = 1% conversion loss | **MISATTRIBUTED** | From Amazon ~2006, not universal. Should cite Amazon specifically. |
| 30 | Polar Analytics syncs hourly | **WRONG** | Base plan syncs **daily at 1 AM**. Hourly only on higher tiers. Shopify data every 15 min on new pipeline. |
| 31 | TrueProfit every 15 minutes | **UNVERIFIABLE** | No official source confirms this interval. |
| 32 | Only Putler joins GSC to purchases | **OUTDATED** | Other tools (Usermaven, BigQuery approaches) now offer GSC dashboards. |
| 33 | Triple Whale pricing $129-$149 | **WRONG** | Free + $129/mo Growth + $199/mo Pro + $279/mo Enterprise. No $149 tier. |
| 34 | GA4 data "disappears" after 14 months | **MISLEADING** | Only affects Explorations/user-level data. Aggregate standard reports persist. GA4 360 retains up to 50 months. |

---

## Summary

**Round 1 totals: ~80 claims checked.** 56% correct, 31% wrong, 13% imprecise.

---

## Round 2 Corrections (packages, OAuth, SQL, APIs, research docs)

### SQL Logic Bugs (coding-spec.md)

| # | Bug | Impact | Fix |
|---|-----|--------|-----|
| 35 | Cohort query: `EXTRACT(MONTH FROM AGE(...))` returns 2 not 14 for 14-month span | **All multi-year cohort data collapsed** | Use `EXTRACT(YEAR FROM AGE(...))*12 + EXTRACT(MONTH FROM AGE(...))` |
| 36 | Cohort query: missing filter for cancelled orders | Inflated retention | Add `AND o.financial_status NOT IN ('refunded','voided','cancelled')` |
| 37 | Revenue-per-query: double-counts revenue when multiple GSC queries match same order | Revenue inflated | Deduplicate orders or distribute revenue across queries |
| 38 | Revenue-per-query: same-day join misses most search-driven revenue | Attributes almost nothing | Remove date join; use landing_page_path match within date range (not same day) |
| 39 | Revenue-per-query: regexp_replace without 'g' flag only strips first match | Query params remain | Add 'g' flag or use two chained calls |
| 40 | Operational costs: integer division `days_in_range / days_in_month` yields 0 | OPEX always zero | Cast to `::numeric`. Also need per-month expansion for multi-month ranges. |
| 41 | RFM: NTILE assigns 1 to best (lowest recency) but we want 5 = best | **Scores inverted** | Use `ORDER BY recency_days DESC` or compute `6 - ntile_value` |
| 42 | Inventory velocity: doesn't exclude refunded orders | Velocity understated | Filter `financial_status NOT IN ('refunded','voided')` |
| 43 | Ad spend attribution: division by zero when subtotal_price = 0 | Crash | Guard with `NULLIF(subtotal_price, 0)` |
| 44 | Ad spend attribution: spend lost when campaign has spend but zero orders | Understated costs | Track as "unattributed spend" bucket |

### OAuth & Auth Corrections (onboarding.md, integrations.md)

| # | Issue | Fix |
|---|-------|-----|
| 45 | Klaviyo is NOT private-API-key-only | Has full OAuth support. Apps must migrate to OAuth by June 2025. Use OAuth with granular scopes. |
| 46 | Missing Shopify webhook: REFUNDS_CREATE | Add for real-time refund tracking |
| 47 | Missing Shopify webhook: INVENTORY_ITEMS_UPDATE | Add for COGS/cost changes |
| 48 | Meta: use System User tokens (non-expiring) for server-side | User tokens expire in 60 days and can't be refreshed |
| 49 | Google: one OAuth consent covers Ads + GA4 + GSC | Combine scopes in single flow |
| 50 | Shopify scopes list doesn't need `read_marketing_events` | `read_orders` is sufficient for customerJourneySummary |

### Package & Version Corrections (tech-stack.md)

| # | Issue | Fix |
|---|-------|-----|
| 51 | PHP version: said 8.5, but Laravel 13.3+ needs 8.4 minimum for Symfony 8 | **PHP 8.4 minimum, 8.5 works too** |
| 52 | Inertia v3 is still in beta | Note this — may want to use stable v2 instead |
| 53 | TanStack Table v9 is alpha | Use **v8** for production |
| 54 | vue-echarts v8 is beta (v8.1.0-beta.2) | Use v8.0.1 stable |
| 55 | Use phpredis extension instead of predis | 2-5x faster, Laravel 13 defaults to it |

### External API Corrections (integrations.md)

| # | Issue | Fix |
|---|-------|-----|
| 56 | exchangerate.host is no longer free (100 req/mo) | Remove as option. Use ECB (truly free). |
| 57 | ECB has no JSON endpoint | Returns XML or CSV only. Parse XML. |
| 58 | Calendarific doesn't guarantee ecommerce events | Black Friday etc. may not be in data. Must seed ourselves. |
| 59 | Nager.Date Docker self-hosting now requires sponsorship | Use public API instead. |
| 60 | CrUX "75 unique origins" threshold | Not publicly documented. 75 refers to p75 percentile, not sample count. |

### Research Doc Corrections

| # | Issue | Fix |
|---|-------|-----|
| 61 | NNG "recommends 16px" | NNG never made this specific recommendation. Say "industry standard" instead. |
| 62 | "Below 13px error rates increase" | Directionally correct but no single source. Say "readability degrades below ~14px" without citing a specific study. |
| 63 | Stocky "28-day rolling average" | Unverifiable from public docs. Note as approximate. Also: **Stocky is being sunset August 2026.** |
| 64 | Cogsy "60/40 weighting" | Unverifiable. Remove specific percentages. |
| 65 | superadmin-panel.md still says "Filament v3" | Already fixed to v5 in workspace-architecture.md but research doc still says v3. |

---

**Round 2 totals: ~30 additional claims checked. 14 VERIFIED, 11 WRONG, 5 UNVERIFIABLE.**

**Cumulative after round 2: ~110 claims checked. 59 verified, 36 wrong, 15 imprecise/unverifiable.**

---

## Round 3 Corrections (schema gaps, defaults, UI claims, billing, P&L)

### Schema Gaps (database-schema.md)

| # | Gap | Severity | Fix |
|---|-----|----------|-----|
| 66 | No `hourly_snapshots` table for today-so-far widget | High | Query orders directly for today's intra-day data. Ad spend lags until next sync. Document this. |
| 67 | Per-source REVENUE not in daily_snapshots (only per-source SPEND) | Medium | Add `revenue_meta`, `revenue_google`, `revenue_ga4` to snapshots — or accept multi-table join |
| 68 | Funnel time-between-steps needs event-level GA4 data we don't store | High | **Drop feature from MVP.** GA4 Data API doesn't provide per-session event timestamps in aggregate reports. |
| 69 | `email_flows` lacks daily granularity (only cumulative) | Low | Accept cumulative-only for MVP. Add `email_flow_daily` table later. |
| 70 | No `uptime_incidents` table (incidents computed from consecutive checks) | Low | Compute at query time for MVP. |

### P&L Tax Treatment (coding-spec.md, feature-list.md)

| # | Issue | Fix |
|---|-------|-----|
| 71 | Sales tax treated as "pass-through, shown for info" inside P&L | **WRONG per accounting standards.** Sales tax is a liability, not revenue. Should be EXCLUDED from Net Revenue (subtracted before Gross Profit). Show as memo line only. Matches Lifetimely's approach. |

### Pricing Model (workspace-architecture.md)

| # | Issue | Fix |
|---|-------|-----|
| 72 | 0.4% of revenue becomes uncompetitive above ~€500K/yr GMV | At €83K/mo GMV, we'd charge €332/mo vs Triple Whale's $179-259. Document this risk. Consider cap or tiered pricing. **User decision needed.** |

### Multi-Store Customer Dedup (workspace-architecture.md)

| # | Issue | Fix |
|---|-------|-----|
| 73 | Doc says "no competitor deduplicates cross-store" — this is WRONG | Putler and Glew DO merge customers cross-store by email. Our MVP: no dedup. v2: email-based merge. Fix the doc claim. |

### Defaults & Thresholds (coding-spec.md)

| # | Issue | Fix |
|---|-------|-----|
| 74 | RFM minimum: 5 customers | Too low for meaningful quintiles. Raise to ~100. For 20-100 customers use simplified 3-tier. |
| 75 | COGS 20% threshold | Arbitrary. Make configurable (default 20%, user can change). Document as our default. |
| 76 | Channel mapping defaults | Use GA4's documented default channel grouping rules (~820 recognized sources). Don't invent our own. |
| 77 | Date range presets | Ship: Today, Yesterday, 7d, 30d (default), 90d, 365d, MTD, QTD, YTD, Last month, Last quarter, BFCM, Custom |
| 78 | Creative fatigue: add absolute frequency floor | Add secondary trigger: frequency > 3.0 (industry standard absolute threshold) |

### UI/UX Claim Corrections (pages-outline.md)

| # | Issue | Fix |
|---|-------|-----|
| 79 | "Skeleton shimmer loaders, never spinners" | Wrong — Stripe and Linear both use spinners for small actions. Change to "Prefer skeletons for content loading; use small spinner for action feedback." |
| 80 | "Horizontal bar funnel beats Sankey — consensus" | Overstated. GA4 uses vertical bars. No consensus. Change to "Horizontal bars for our linear funnel (Plausible pattern). Sankey for branching flows (v2)." |
| 81 | Drawer width 480-560px attributed to Stripe/Linear | Our design choice, not verified from their published specs. Label as our decision. |
| 82 | KPI 28-32px claimed from Stripe/Linear | Linear actually uses 20-22px. Stripe closer to 28-32px. Label as our decision, not competitor-verified. |

### Shopify Currency Handling (integrations.md, workspace-architecture.md)

| # | Issue | Fix |
|---|-------|-----|
| 83 | How to handle Shopify multi-currency with Markets | Use `shop_money` (store's default currency), NOT `presentment_money` (customer's local). Then convert from shop currency to workspace reporting currency. |

---

**Round 3 totals: ~35 additional claims checked. 20 issues found.**
**Cumulative: ~145 claims verified. 56 wrong or misleading, all corrected or flagged.**
