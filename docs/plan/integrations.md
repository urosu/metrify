# Nexstage Integration Specs

Exact API versions, endpoints, rate limits, data fields, and sync strategies for each data source.

---

## Sync Architecture Overview
```
                    ┌─────────────────────────┐
                    │     Sync Scheduler       │
                    │  (Laravel Horizon jobs)  │
                    └────────┬────────────────┘
                             │
         ┌───────────┬───────┼───────┬──────────┬──────────┐
         ▼           ▼       ▼       ▼          ▼          ▼
    ┌─────────┐ ┌────────┐ ┌─────┐ ┌─────┐ ┌──────┐ ┌────────┐
    │ Shopify │ │  Woo   │ │Meta │ │G.Ads│ │ GA4  │ │Klaviyo │
    │ GraphQL │ │REST v3 │ │v25  │ │v23.1│ │v1beta│ │rev-API │
    └────┬────┘ └───┬────┘ └──┬──┘ └──┬──┘ └──┬───┘ └───┬────┘
         │          │         │       │       │         │
         ▼          ▼         ▼       ▼       ▼         ▼
    ┌──────────────────────────────────────────────────────────┐
    │              Raw Data Tables (PostgreSQL)                │
    │   orders, products, customers, ad_insights, ga4, gsc     │
    └────────────────────────┬─────────────────────────────────┘
                             │
                     Snapshot Builder (nightly)
                             │
                    ┌────────▼────────┐
                    │ daily_snapshots  │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │   Redis Cache    │
                    └─────────────────┘
```

---

## 1. Shopify
### API Details

| Setting | Value |
|---------|-------|
| Protocol | **GraphQL Admin API** (REST is legacy since Oct 2024, required for new apps since Apr 2025) |
| Version | `2026-04` (latest stable) |
| Auth | OAuth 2.0 app install flow |
| Rate limits | Leaky bucket: **100 pts/sec (Standard), 200 (Advanced), 1000 (Plus), 2000 (Enterprise)**. Max single query cost: 1000 pts. |

### Data to Fetch

**Orders** (primary sync target):
- `id`, `name`, `createdAt`, `updatedAt`, `displayFinancialStatus`, `displayFulfillmentStatus`
- `currentTotalPriceSet`, `currentSubtotalLineItemsQuantity`, `totalDiscountsSet`, `totalTaxSet`, `totalShippingPriceSet`
- `currencyCode`, `paymentGatewayNames`
- `lineItems` (nested): `product.id`, `variant.id`, `title`, `sku`, `quantity`, `originalUnitPriceSet`, `discountAllocations`
- `refunds` (nested): `totalRefundedSet`, `refundLineItems`
- `customerJourneySummary`: `firstVisit { utmParameters { source, medium, campaign, content, term }, landingPage, referrerUrl }`, `daysToConversion`, `customerOrderIndex`
- `customer.id`

**Products + Inventory:**
- `products`: `id`, `title`, `vendor`, `productType`, `tags`, `status`, `featuredImage.url`
- `variants` (nested): `id`, `sku`, `price`, `compareAtPrice`, `inventoryQuantity`, `weight`, `weightUnit`, `barcode`
- `inventoryItem.unitCost` (COGS — on InventoryItem, requires separate field or nested query)

**Customers:**
- `id`, `email`, `firstName`, `lastName`, `numberOfOrders`, `amountSpent { amount currencyCode }`, `createdAt`, `tags`, `emailMarketingConsent { marketingState }`, `defaultAddress { country, city, province }`

**Transaction Fees:**
- `shopifyPaymentsAccount.balanceTransactions`: `amount`, `fee`, `net`, `type`, `sourceId`, `transactionDate`
- **Shopify Payments only** — third-party gateways don't expose fees

### Bulk Import Queries

Up to **5 concurrent bulk queries per shop** (since 2026-01). Each uses `bulkOperationRunQuery` mutation — async, returns JSONL file. Subscribe to `bulk_operations/finish` webhook or poll via `bulkOperation(id:)` every 10-15s.

**JSONL format:** Each line is a JSON object. Nested connections produce child rows with `__parentId` linking to parent. Parse line-by-line, group by `__parentId`.

**4 bulk queries + 1 paginated:**
1. **Orders** — with lineItems, refunds, customerJourneySummary. Scope: `read_orders`. Note: `refundLineItems` needs `first: 50` (nested inside non-connection parent).
2. **Products** — with variants + `variant.inventoryItem.unitCost` (COGS). Scope: `read_products`, `read_inventory`.
3. **Customers** — Note: API 2026-04 uses `numberOfOrders` (not `ordersCount`), `amountSpent` (not `totalSpentV2`), `emailMarketingConsent.marketingState` (not `acceptsMarketing`).
4. **Inventory items** — with per-location levels. Only needed if multi-location tracking; products query #2 already fetches unitCost.
5. **Balance transactions** — CANNOT use bulk ops (`shopifyPaymentsAccount` is not a connection). Paginate manually: `balanceTransactions(first: 100, after: $cursor)`. Each page ~4 points. Scope: `read_shopify_payments_payouts`.

All `*Set` fields are `MoneyBag` — use `shopMoney.amount` (string, cast to decimal).

### Sync Strategy

**Ongoing sync:**
- **Webhooks** for real-time: `ORDERS_CREATE`, `ORDERS_UPDATED`, `ORDERS_CANCELLED`, `ORDERS_DELETE`, `REFUNDS_CREATE`, `PRODUCTS_UPDATE`, `INVENTORY_LEVELS_UPDATE`, `APP_UNINSTALLED`
- **Hourly reconciliation query**: `orders(query: "updated_at:>'[last_sync_time]'")` — catches missed webhooks
- Webhooks are not exactly-once — always deduplicate by order ID + updatedAt

**GraphQL cost optimization:**
- Request only needed fields (each field = 1 point, each connection page = 2 points)
- Typical 50-item order page with line items costs ~100-150 points
- At Standard plan (100 pts/sec): ~40 pages/min

### Field Mapping Notes
- All `*Set` fields (e.g., `currentTotalPriceSet`) are `MoneyBag` objects. Use `shopMoney.amount` (store's base currency), NOT `presentmentMoney` (customer's local currency). `amount` is a string — cast to decimal.
- `displayFinancialStatus` returns UPPER_CASE (`PAID`, `PARTIALLY_REFUNDED`). Store as lowercase in `orders.financial_status`.
- Customer `orders_count`/`total_spent` removed from API 2025-01. During parallel bulk import, customers arrive before orders — leave stats at 0. Post-import job (#2 in non-obvious-issues) recomputes from orders.

### Shopify-Specific Gotchas
- COGS (`unitCost`) lives on `InventoryItem`, not Product/Variant — requires fetching inventory items separately or in bulk
- `customerJourneySummary` needs `read_orders` scope
- Refunds are nested on orders but partial refunds have their own `refundLineItems`
- `totalShippingPriceSet` is what customer paid; actual carrier cost is NOT in Shopify API

---

## 2. WooCommerce
### API Details

| Setting | Value |
|---------|-------|
| Protocol | REST API v3 |
| Base path | `/wp-json/wc/v3/` |
| Auth | Consumer key + secret (Basic auth over HTTPS) |
| Rate limits | **None from WooCommerce itself** — depends on hosting. Safe default: 2-3 concurrent requests, 1s delay between pages. |

### Data to Fetch

**Orders:**
- `id`, `number`, `date_created`, `status`, `currency`, `total`, `discount_total`, `shipping_total`, `total_tax`, `payment_method`, `payment_method_title`, `customer_id`
- `line_items[]`: `product_id`, `variation_id`, `name`, `sku`, `quantity`, `price`, `subtotal`, `total`, `taxes[]`, `cost_of_goods_sold.total_value` (WC 10.3+)
- `coupon_lines[]`: `code`, `discount`, `discount_tax`
- `billing.email`, `billing.country`, `shipping.country`, `shipping.city`
- **UTM attribution** (meta_data keys, WC 8.5+): `_wc_order_attribution_utm_source`, `_wc_order_attribution_utm_medium`, `_wc_order_attribution_utm_campaign`, `_wc_order_attribution_source_type`, `_wc_order_attribution_session_entry`
- **Payment fees** (meta_data): Stripe: `_stripe_fee`, `_stripe_net`. PayPal: `_paypal_transaction_fee`.

**Refunds:**
- `GET /wc/v3/orders/{id}/refunds` — each refund has `amount`, `reason`, `line_items[]` with negative totals

**Products:**
- `id`, `name`, `type` (simple/variable), `status`, `sku`, `price`, `regular_price`, `sale_price`, `stock_quantity`, `weight`, `categories[]`, `tags[]`, `images[]`
- `cost_of_goods_sold.values[].effective_value` (WC 10.3+ native COGS; nested in `values` array)
- Legacy: `_wc_cog_cost` meta key from third-party plugins — read both
- Variations: `GET /wc/v3/products/{id}/variations` — same fields per variation

**Customers:**
- `id` (0 for guests), `email`, `first_name`, `last_name`, `orders_count`, `total_spent`, `billing.country`, `billing.city`
- **Guests**: `customer_id = 0`, dedup by `billing.email` (lowercased, trimmed)

### Sync Strategy

**Initial import:**
- Paginate: `GET /wc/v3/orders?per_page=100&page=1&orderby=date&order=asc`
- Max `per_page` is 100. Use page-based pagination.
- Check `X-WP-Total` header for total count
- 2-3 concurrent requests, respect hosting limits

**Ongoing sync:**
- **Incremental**: `GET /wc/v3/orders?modified_after=2026-04-29T00:00:00Z&orderby=modified&order=asc&dates_are_gmt=true`
- **Webhooks**: `order.created`, `order.updated` via WC webhook system (Action Scheduler). Can fail silently — always pair with periodic `modified_after` polling as fallback.

### Field Mapping Notes
- Attribution meta: `meta_data` is an array of `{"id": int, "key": string, "value": string}` objects. Filter by key name (e.g., `_wc_order_attribution_utm_source`) and extract `.value`.
- Payment fees: `_stripe_fee`, `_stripe_net`, `_paypal_transaction_fee` are in the same `meta_data[]` array.
- COGS: `cost_of_goods_sold.values[].effective_value` on line items (WC 10.3+). Also check `_wc_cog_cost` legacy meta key.

### WooCommerce-Specific Gotchas
- No cursor-based pagination — page-based is fragile on mutations
- Hosting varies wildly (shared hosting chokes at 5 concurrent requests)
- Webhooks delivered via Action Scheduler, can fail silently
- Guest customers are common (id=0) — must use billing.email
- Caching plugins (WP Super Cache, etc.) can serve stale API data
- `total_spent` and `orders_count` only populated for registered users

---

## 3. Meta (Facebook) Ads
### API Details

| Setting | Value |
|---------|-------|
| Protocol | Graph API |
| Version | **v25.0** (released Feb 2026) |
| Auth | OAuth 2.0 with long-lived token (60-day expiry, auto-refresh) |
| Rate limits | Points-based scoring (read=1pt, write=3pts). Budget varies by tier (dev: 60, standard: 9000) with 300s decay windows. Check `x-business-use-case-usage` header. Back off at 75%. |

### Data to Fetch

**Structure (one-time sync + periodic refresh):**
- Campaigns: `id`, `name`, `status`, `objective`, `daily_budget`, `lifetime_budget`
- Ad Sets: `id`, `name`, `campaign_id`, `status`, `optimization_goal`, `targeting` (JSON)
- Ads: `id`, `name`, `adset_id`, `creative{thumbnail_url,image_url,video_id,body,title,link_url}`, `status`

**Daily Insights (primary sync):**
- Endpoint: `GET /act_{id}/insights` with `level=ad` (returns ad-level rows only; use `level=campaign` or `level=adset` for those levels separately)
- Fields: `spend`, `impressions`, `clicks`, `cpm`, `cpc`, `ctr`, `reach`, `frequency`, `actions`, `action_values`, `cost_per_action_type`, `video_p25_watched_actions`, `video_p50_watched_actions`, `video_p75_watched_actions`, `video_p100_watched_actions`
- Params: `time_increment=1` (daily rows), `time_range` for date window
- **Conversions**: in `actions` array, look for `action_type=purchase`. Revenue in `action_values` array, same key.
- Primary key: `ad_id + date_start`

**Creative thumbnails:**
- Fetched from `ad.creative` fields or `GET /{video_id}?fields=thumbnails`

### Sync Strategy

**Schedule:** Hourly.

**Date windows per fetch:**
- Today + last 3 days: hourly (corrections settle within 24-48h)
- Last 7 days: once daily (1-day click attribution finalizes ~72h)
- Last 28 days: once daily (covers full 7d-click/1d-view default window)
- Full re-sync: weekly or on-demand only

**Async reports:** Use `POST /act_{id}/insights` (async) for >30 day ranges. Poll for completion. Always use async in production.

**Batch API:** `POST /` with `batch` param bundles up to 50 requests in one HTTP call.

**Parsing conversions:** `actions` array contains `[{"action_type": "purchase", "value": "5"}, ...]` (value is a string, cast to int). Revenue in separate `action_values` array: `[{"action_type": "purchase", "value": "249.99"}, ...]` (also string, cast to decimal). Multiple entries may exist with attribution suffixes (`_7d_click`, `_1d_view`). Use the default attribution window entries (no suffix).

**Parallel:** Parallelize per ad account (each has independent rate budget). 3-5 concurrent workers.

**Historical limit:** 37 months.

---

## 4. Google Ads
### API Details

| Setting | Value |
|---------|-------|
| Protocol | gRPC / REST |
| Version | **v23.1** (current stable; v19 sunset Feb 2026) |
| Auth | OAuth 2.0 with refresh token |
| Rate limits | 15,000 operations/day per developer token (basic access; a single request can contain multiple operations). Standard access: unlimited. |

### Data to Fetch

**Use `SearchStream`** (single request, streamed response, no pagination). Better than `Search` which paginates at 10K rows.

**GAQL queries by level:**

Campaign-level:
```sql
SELECT campaign.id, campaign.name, campaign.status,
       campaign.advertising_channel_type, metrics.cost_micros,
       metrics.impressions, metrics.clicks, metrics.conversions,
       metrics.conversions_value, segments.date
FROM campaign
WHERE segments.date DURING LAST_30_DAYS
```

Ad-level:
```sql
SELECT campaign.name, ad_group.name, ad_group_ad.ad.id,
       ad_group_ad.ad.type, ad_group_ad.ad.final_urls,
       metrics.cost_micros, metrics.impressions, metrics.clicks,
       metrics.conversions, metrics.conversions_value, segments.date
FROM ad_group_ad
WHERE segments.date DURING LAST_30_DAYS
```

Shopping SKU-level:
```sql
SELECT segments.product_item_id, metrics.cost_micros,
       metrics.impressions, metrics.clicks, metrics.conversions,
       metrics.conversions_value, segments.date
FROM shopping_performance_view
WHERE segments.date DURING LAST_30_DAYS
```

**Performance Max:** Campaign-level metrics only. `asset_group_product_group_view` gives some product data. No keyword/placement breakdown.

**Note:** `cost_micros` must be divided by 1,000,000 to get actual currency amount.

**Campaign type mapping:** `campaign.advertising_channel_type` → our `campaign_type` column: `SEARCH` → `search`, `DISPLAY` → `display`, `SHOPPING` → `shopping`, `PERFORMANCE_MAX` → `pmax`, `VIDEO` → `video`. Lowercase + alias.

### Sync Strategy

Same schedule as Meta: hourly (last 3 days), plus daily correction re-fetch of last 14 days (data stabilizes in ~3-14 days).

Parallelize per customer ID. Use SearchStream exclusively.

---

## 5. Google Analytics 4
### API Details

| Setting | Value |
|---------|-------|
| Protocol | REST (GA4 Data API) |
| Version | **v1beta** (still beta, not GA stable) |
| Auth | OAuth 2.0 |
| Rate limits | **Token-based:** 200,000 tokens/property/day, 40,000/property/hour. 10 concurrent requests per property. Use `batchRunReports` to reduce token consumption. |
| Data lag | 24-48 hours. Sync T-2 (two days back). |

### Data to Fetch

**Dimensions:** `date`, `sessionDefaultChannelGroup`, `sessionSource`, `sessionMedium`, `sessionCampaignName`, `landingPagePlusQueryString`, `country`, `deviceCategory`

**Metrics:** `sessions`, `engagedSessions`, `totalUsers`, `newUsers`, `ecommercePurchases`, `purchaseRevenue`, `conversions`, `bounceRate`, `averageSessionDuration`

**Endpoint:** `POST /v1/properties/{propertyId}:runReport`

### Sync Strategy

- **Schedule:** Daily at 06:00 UTC
- **Window:** Fetch T-2 (reliable data). Re-fetch T-3 for corrections.
- Use `batchRunReports` (up to 5 reports in one API call) to reduce quota
- Prefer fewer, wider reports over many small ones
- Check `dataLossFromOtherRow` and `samplingMetadatas` in response to detect sampling
- Pagination: `limit` + `offset`, max 250,000 rows per request (default 10,000)
- **Initial import:** Chunk by month. ~14 months of data available.

### GA4 Gotchas
- Sampling kicks in above ~10 million events per query (not sessions — that was Universal Analytics) — keep date ranges narrow
- No real-time data that's reliable — always use T-2
- Property-level quotas, not account-level

---

## 6. Google Search Console
### API Details

| Setting | Value |
|---------|-------|
| Protocol | REST |
| Endpoint | `POST /webmasters/v3/sites/{siteUrl}/searchAnalytics/query` |
| Auth | OAuth 2.0 (shared credentials with GA4) |
| Rate limits | ~1,200 requests/min per project. Not a bottleneck. |
| Data lag | 2-3 days. T-3 is fully settled. |
| Historical limit | **16 months** rolling |

### Data to Fetch

**Dimensions:** `query`, `page`, `country`, `device`, `date`

**Metrics:** `clicks`, `impressions`, `ctr`, `position`

**Max rows per request:** 25,000. Use `startRow` for pagination.

### Sync Strategy

- **Initial import:** Iterate day-by-day for 16 months (max ~480 requests). Pull full 16 months immediately on connect.
- **Daily sync:** Fetch last 5 days (T-1 through T-5) with upsert to catch revisions — GSC data lag is 2-3 days, 5-day window ensures corrections are captured.
- **Schedule:** Daily at 07:00 UTC (after GA4 sync).
- Request all 5 dimensions for full granularity.

---

## 7. Klaviyo
### API Details

| Setting | Value |
|---------|-------|
| Protocol | REST |
| Version | Revision-based header (current: **`2026-04-15`**) |
| Auth | **OAuth 2.0** (authorization code flow with PKCE). Private API keys still work but Klaviyo requires OAuth for marketplace apps. Scopes: `metrics:read, profiles:read, profiles:write, campaigns:read, flows:read, events:read, lists:read, lists:write, segments:read, segments:write`. Write scopes needed for segment push to Klaviyo. Access tokens expire 1h; refresh tokens valid until uninstall or 90d non-use. |
| Rate limits | 75 req/sec most endpoints (burst up to 700). Metric-aggregates: ~3/sec. |

### Data to Fetch

**Structure (one-time + periodic refresh):**
- `GET /api/campaigns` — `id`, `name`, `send_time`, `status`, `channel` (email/sms)
- `GET /api/flows` — `id`, `name`, `status`, `trigger_type`

**Metrics — Use Reporting API** (not metric-aggregates — Reporting API matches Klaviyo UI numbers):

Step 1: Discover `Placed Order` metric ID via `GET /api/metrics` (paginated, cache the UUID).

Step 2 — Campaign stats:
```
POST /api/campaign-values-reports
Body: { "data": { "type": "campaign-values-report", "attributes": {
  "timeframe": { "start": "...", "end": "..." },
  "conversion_metric_id": "<placed_order_id>",
  "statistics": ["recipients", "delivered", "opens_unique", "clicks_unique",
    "bounced", "unsubscribes", "conversion_uniques", "conversion_value"],
  "group_by": ["campaign_id", "send_channel"]
}}}
```
Response: `data.attributes.results[]` each with `groupings` + `statistics` as numbers.

Step 3 — Flow stats: `POST /api/flow-values-reports` — same structure with `flow_id`. Add `flow_message_id` to `group_by` for per-message breakdown.

**Rate limit for reporting endpoints: 1/s burst, 2/m steady, 225 calls/day — very restrictive.**

**Profile Predictive Data:**
- `GET /api/profiles/{id}` — `predictive_analytics`: `predicted_clv`, `expected_date_of_next_order`, `churn_risk_prediction`
- Requires 500+ customers, 180+ days history
- Updates weekly — poll on schedule

**Attribution:**
- Last-touch within configurable window (default: 5-day click, 5-day open for email; 5-day click for SMS; 24h for push)
- Changes take up to 36h to apply

### Sync Strategy

- **Schedule:** Hourly for events/webhooks. Daily for Reporting API calls (225/day limit).
- **Webhooks:** `POST /api/webhooks`. Topics: `event:klaviyo.<event_name>`. Call Get Webhook Topics to discover available topics per account.
- **Initial pull:** Paginate `/api/events` by metric name. Cursor-based (`page[cursor]`). Budget ~2 hours for large accounts.
- **Page size:** `page[size]=100` (max).

---

## 7b. TikTok Ads (v2 — do not build at MVP)
REST Marketing API v1.3, OAuth 2.0, ~10 QPS. Interfaces ready at `AdPlatformConnector`.

**Bing Ads:** Deferred. Channel mapping rules classify Bing traffic (utm_source patterns, `msclkid` click ID) but no Bing Ads API integration at MVP. Bing-attributed orders get channel classification (`paid_search`) but show N/A for platform-specific ad spend/ROAS.

---

## 8. PageSpeed / CrUX

### API Details

| Setting | Value |
|---------|-------|
| CrUX API | `https://chromeuxreport.googleapis.com/v1/records:queryRecord` |
| PageSpeed Insights | `https://www.googleapis.com/pagespeedonline/v5/runPagespeed` |
| Auth | API key (no OAuth needed) |
| Rate limits | CrUX: 150 queries/min. PSI: 400 queries/100 seconds. |

### Data to Fetch

**CrUX (field data, real users):**
- Metrics: `largest_contentful_paint`, `interaction_to_next_paint`, `cumulative_layout_shift`, `first_contentful_paint`, `time_to_first_byte`
- Percentiles: p75 values
- Requires minimum ~75 unique origins in 28-day window

**PageSpeed Insights (lab data, Lighthouse fallback):**
- Performance score (0-100), individual metric scores
- Opportunities list with potential savings
- Used when CrUX has insufficient data

### Sync Strategy

- **Schedule:** Weekly per URL (not daily — scores don't change fast)
- Fetch top 50 URLs by traffic (from GA4 sessions data)
- Both mobile + desktop strategies
- Fall back to Lighthouse when CrUX returns insufficient data

---

## 9. Currency Exchange Rates

### Frankfurter API v2 (55 central banks, 200+ currencies)

| Setting | Value |
|---------|-------|
| Base URL | `https://api.frankfurter.dev/v2` |
| Auth | None (free, no API key) |
| Data source | 55 central banks (ECB + others), daily updates |
| Cross-rates | Native — `?base=GBP&quotes=USD` works directly |
| Self-hosting | `docker run -d -p 8080:8080 lineofflight/frankfurter` |

**Endpoints:**
- Latest: `GET /v2/rates?base=EUR&quotes=USD,GBP,SEK`
- Historical: `GET /v2/rates?base=GBP&quotes=USD&date=2026-04-15`
- Date range: `GET /v2/rates?base=EUR&quotes=USD&from=2026-01-01&to=2026-03-31`
- Single pair: `GET /v2/rate/GBP/USD`
- All currencies: `GET /v2/currencies`

**Response (array, not nested object):** `[{"date":"2026-04-30", "base":"GBP", "quote":"USD", "rate":1.27}]`

Weekend/holiday requests return last business day's rate automatically.

**Sync:** `SyncFxRatesJob` daily at 17:00 UTC. Fetch all rates with workspace reporting currency as base. Store in `fx_rates` table. Convert order amounts during sync using transaction-day rate.

---

## Historical Data Limits
| Platform | History Available | Action on Connect |
|----------|-----------------|-------------------|
| **Shopify** | Unlimited (all orders since store creation) | Bulk import everything |
| **WooCommerce** | Unlimited (data in customer's DB) | Paginate all history |
| **Google Ads** | Unlimited (life of account) | Import all |
| **Meta Ads** | **37 months** (daily breakdowns) | Import 37mo; store locally to build beyond |
| **TikTok Ads** (v2) | Unlimited (no documented cap) | Import all |
| **Klaviyo** | Unlimited (all events since creation) | Import all |
| **GA4** | **~14 months** (standard; configurable 2 or 14mo) | Import max window; **store locally — data disappears from API** |
| **GSC** | **16 months** rolling | Import 16mo; **store locally — data disappears after 16mo** |

**Critical:** GA4 and GSC data disappears from their APIs over time. We MUST sync regularly and store locally to preserve history beyond their rolling windows. Once GSC data is >16 months old, it's gone forever if we didn't already pull it.

**Shopify caveat:** Inventory *level* history is NOT available via API — only current snapshot. Inventory velocity must be computed from order_line_items (units sold per day).

---

## Parallel Fetch Strategy
Parallel fetch and full sync schedule → `coding-spec.md` section 25.

**Note:** Google Ads API uses "ad_group" terminology. Our database normalizes to the `ad_sets` table. During sync, map: `ad_group.id` → `platform_adset_id`, `ad_group.name` → `ad_sets.name`.
