# API Payload Examples

Trimmed example JSON for every external API Nexstage integrates with. Only fields we use are shown. Comments map API fields to our DB columns.

---

## 1. Shopify (GraphQL Admin API 2026-04)

### Order Query Response (GraphQL)

```jsonc
{
  "data": {
    "orders": {
      "edges": [
        {
          "node": {
            "id": "gid://shopify/Order/5765413208371",         // extract numeric → orders.platform_order_id = "5765413208371"
            "name": "#1042",                                     // → orders.order_number
            "createdAt": "2026-04-28T14:23:11Z",                // → orders.created_at
            "updatedAt": "2026-04-28T15:01:44Z",                // → orders.platform_updated_at
            "displayFinancialStatus": "PAID",                    // lowercase → orders.financial_status = "paid"
            "displayFulfillmentStatus": "UNFULFILLED",           // lowercase → orders.fulfillment_status = "unfulfilled"
            "currencyCode": "EUR",                               // → orders.currency
            "currentTotalPriceSet": {
              "shopMoney": { "amount": "149.90", "currencyCode": "EUR" }  // string → cast decimal → orders.total_price
            },
            "currentSubtotalLineItemQuantity": 3,                // (informational, not stored directly)
            "totalDiscountsSet": {
              "shopMoney": { "amount": "15.00", "currencyCode": "EUR" }   // → orders.total_discounts
            },
            "totalTaxSet": {
              "shopMoney": { "amount": "24.98", "currencyCode": "EUR" }   // → orders.total_tax
            },
            "totalShippingPriceSet": {
              "shopMoney": { "amount": "5.99", "currencyCode": "EUR" }    // → orders.total_shipping
            },
            "paymentGatewayNames": ["shopify_payments"],         // [0] → orders.payment_gateway
            "customer": {
              "id": "gid://shopify/Customer/7234567890123"       // extract numeric → lookup/create customers record
            },
            "lineItems": {
              "edges": [
                {
                  "node": {
                    "product": { "id": "gid://shopify/Product/8912345678901" },  // extract numeric → order_line_items.platform_product_id
                    "variant": { "id": "gid://shopify/ProductVariant/44123456789012" },  // → order_line_items.platform_variant_id
                    "title": "Organic Cotton Tee",                // → order_line_items.title
                    "sku": "OCT-BLK-L",                          // → order_line_items.sku
                    "quantity": 2,                                 // → order_line_items.quantity
                    "originalUnitPriceSet": {
                      "shopMoney": { "amount": "49.95", "currencyCode": "EUR" }  // → order_line_items.unit_price
                    },
                    "discountAllocations": [
                      { "allocatedAmountSet": { "shopMoney": { "amount": "7.50", "currencyCode": "EUR" } } }
                    ]  // sum → order_line_items.total_discount
                  }
                }
              ]
            },
            "refunds": [
              {
                "id": "gid://shopify/Refund/901234567890",      // extract numeric → refunds.platform_refund_id
                "totalRefundedSet": {
                  "shopMoney": { "amount": "49.95", "currencyCode": "EUR" }  // → refunds.amount
                },
                "refundLineItems": {
                  "edges": [
                    {
                      "node": {
                        "lineItem": { "id": "gid://shopify/LineItem/12345678901234" },
                        "quantity": 1,
                        "subtotalSet": { "shopMoney": { "amount": "49.95", "currencyCode": "EUR" } }
                      }
                    }
                  ]
                }  // → refunds.line_items JSONB
              }
            ],
            "customerJourneySummary": {
              "firstVisit": {
                "utmParameters": {
                  "source": "facebook",                          // → orders.utm_source
                  "medium": "paid",                              // → orders.utm_medium
                  "campaign": "summer_tof_2026",                 // → orders.utm_campaign
                  "content": "carousel_v2",                      // → orders.utm_content
                  "term": null                                   // → orders.utm_term
                },
                "landingPage": "https://store-a.com/products/organic-cotton-tee",  // → orders.landing_site; strip to path → orders.landing_page_path
                "referrerUrl": "https://facebook.com/"           // → orders.referring_site
              },
              "daysToConversion": 2,
              "customerOrderIndex": 1                            // if 1 → orders.is_new_customer = true
            }
          }
        }
      ],
      "pageInfo": { "hasNextPage": true, "endCursor": "eyJsYXN0X2lkIjo1NzY1NDEzMjA4MzcxfQ==" }
    }
  },
  "extensions": {
    "cost": { "requestedQueryCost": 142, "actualQueryCost": 98, "throttleStatus": { "maximumAvailable": 2000, "currentlyAvailable": 1902, "restoreRate": 100 } }
  }
}
```

### Bulk Operation JSONL (line-by-line)

Each line is a JSON object. Child rows reference parent via `__parentId`.

```jsonc
{"id":"gid://shopify/Order/5765413208371","name":"#1042","createdAt":"2026-04-28T14:23:11Z","displayFinancialStatus":"PAID","currentTotalPriceSet":{"shopMoney":{"amount":"149.90","currencyCode":"EUR"}},"totalDiscountsSet":{"shopMoney":{"amount":"15.00","currencyCode":"EUR"}},"totalShippingPriceSet":{"shopMoney":{"amount":"5.99","currencyCode":"EUR"}},"totalTaxSet":{"shopMoney":{"amount":"24.98","currencyCode":"EUR"}},"currencyCode":"EUR","paymentGatewayNames":["shopify_payments"]}
{"id":"gid://shopify/LineItem/12345678901234","title":"Organic Cotton Tee","sku":"OCT-BLK-L","quantity":2,"originalUnitPriceSet":{"shopMoney":{"amount":"49.95","currencyCode":"EUR"}},"__parentId":"gid://shopify/Order/5765413208371"}
{"id":"gid://shopify/LineItem/12345678901235","title":"Linen Shorts","sku":"LS-NAV-M","quantity":1,"originalUnitPriceSet":{"shopMoney":{"amount":"64.95","currencyCode":"EUR"}},"__parentId":"gid://shopify/Order/5765413208371"}
{"id":"gid://shopify/Refund/901234567890","totalRefundedSet":{"shopMoney":{"amount":"49.95","currencyCode":"EUR"}},"__parentId":"gid://shopify/Order/5765413208371"}
```

Parse strategy: read line-by-line, group children by `__parentId` to reconstruct the order with its line items and refunds.

### Webhook: orders/create (REST format)

Shopify webhooks deliver REST payloads even though the admin API is GraphQL.

```jsonc
{
  "id": 5765413208371,                          // → orders.platform_order_id (already numeric)
  "name": "#1042",                               // → orders.order_number
  "created_at": "2026-04-28T14:23:11+02:00",    // → orders.created_at (parse timezone)
  "updated_at": "2026-04-28T15:01:44+02:00",    // → orders.platform_updated_at
  "financial_status": "paid",                     // → orders.financial_status (already lowercase)
  "fulfillment_status": null,                     // null = "unfulfilled" → orders.fulfillment_status
  "currency": "EUR",                              // → orders.currency
  "total_price": "149.90",                        // string → decimal → orders.total_price
  "subtotal_price": "129.92",                     // → orders.subtotal_price
  "total_discounts": "15.00",                     // → orders.total_discounts
  "total_tax": "24.98",                           // → orders.total_tax
  "total_shipping_price_set": {
    "shop_money": { "amount": "5.99", "currency_code": "EUR" }
  },                                              // → orders.total_shipping
  "payment_gateway_names": ["shopify_payments"],
  "source_name": "web",                           // → orders.source_name
  "referring_site": "https://facebook.com/",      // → orders.referring_site
  "landing_site": "/products/organic-cotton-tee?utm_source=facebook&utm_medium=paid",
  "customer": { "id": 7234567890123 },
  "line_items": [
    {
      "id": 12345678901234,
      "product_id": 8912345678901,               // → order_line_items.platform_product_id
      "variant_id": 44123456789012,              // → order_line_items.platform_variant_id
      "title": "Organic Cotton Tee",
      "sku": "OCT-BLK-L",
      "quantity": 2,
      "price": "49.95",                           // → order_line_items.unit_price
      "total_discount": "7.50",                   // → order_line_items.total_discount
      "tax_lines": [{ "price": "8.33", "rate": 0.22, "title": "VAT" }]
    }
  ],
  "discount_codes": [
    { "code": "SUMMER20", "amount": "15.00", "type": "percentage" }
  ],                                              // → orders.discount_codes JSONB
  "billing_address": { "country_code": "SI" },   // → orders.billing_country
  "shipping_address": { "country_code": "SI", "city": "Ljubljana" },
  "test": false                                   // FILTER: skip if test = true
}
```

### Webhook: refunds/create

```jsonc
{
  "id": 901234567890,                            // → refunds.platform_refund_id
  "order_id": 5765413208371,                     // lookup order → refunds.order_id
  "created_at": "2026-04-29T10:15:00+02:00",    // → refunds.created_at
  "note": "Wrong size",                           // → refunds.reason
  "refund_line_items": [
    {
      "id": 1234567890,
      "line_item_id": 12345678901234,
      "quantity": 1,
      "subtotal": 49.95                           // → refunds.line_items JSONB
    }
  ],
  "transactions": [
    { "amount": "49.95", "kind": "refund", "gateway": "shopify_payments" }
  ]                                               // sum refund transactions → refunds.amount
}
```

### Field Mapping Notes

| Shopify Field | Transform | DB Column |
|---|---|---|
| `id` (GID format) | Extract last numeric segment: `gid://shopify/Order/123` → `"123"` | `platform_order_id` |
| `*Set.shopMoney.amount` | String → `decimal(12,2)`. Always use `shopMoney`, never `presentmentMoney`. | various amount columns |
| `displayFinancialStatus` | Uppercase → lowercase (`PAID` → `paid`) | `orders.financial_status` |
| `fulfillment_status: null` (webhook) | Map null → `"unfulfilled"` | `orders.fulfillment_status` |
| `customerJourneySummary.customerOrderIndex` | `1` → `is_new_customer = true`, else `false` | `orders.is_new_customer` |
| `landing_site` (webhook) | Strip query params for `landing_page_path`, keep full for `landing_site` | both columns |

---

## 2. WooCommerce (REST API v3)

### GET /wc/v3/orders/{id} Response

```jsonc
{
  "id": 4821,                                    // → orders.platform_order_id
  "number": "4821",                              // → orders.order_number
  "status": "processing",                        // map → orders.financial_status = "paid"
  "date_created": "2026-04-28T14:23:11",         // → orders.created_at
  "date_modified": "2026-04-28T15:01:44",        // → orders.platform_updated_at
  "currency": "EUR",                              // → orders.currency
  "total": "149.90",                              // → orders.total_price
  "discount_total": "15.00",                      // → orders.total_discounts
  "shipping_total": "5.99",                       // → orders.total_shipping
  "total_tax": "24.98",                           // → orders.total_tax
  "payment_method": "stripe",                     // → orders.payment_gateway
  "payment_method_title": "Credit Card (Stripe)",
  "customer_id": 42,                              // 0 = guest → dedup by billing.email
  "billing": {
    "email": "jana@example.com",                  // → customers.email (guest dedup key)
    "country": "SI",                              // → orders.billing_country
    "first_name": "Jana",
    "last_name": "Novak"
  },
  "shipping": {
    "country": "SI",                              // → orders.shipping_country
    "city": "Ljubljana"                           // → orders.shipping_city
  },
  "line_items": [
    {
      "id": 891,
      "product_id": 234,                          // → order_line_items.platform_product_id
      "variation_id": 567,                        // → order_line_items.platform_variant_id (0 = simple product → null)
      "name": "Organic Cotton Tee - Black / L",   // → order_line_items.title
      "sku": "OCT-BLK-L",                         // → order_line_items.sku
      "quantity": 2,                               // → order_line_items.quantity
      "price": 49.95,                              // → order_line_items.unit_price
      "subtotal": "99.90",
      "total": "92.40",                            // after line discount → order_line_items.total_price
      "taxes": [{ "id": 1, "total": "20.33" }],
      "cost_of_goods_sold": {
        "total_value": "24.00"                     // WC 10.3+ native COGS → order_line_items.unit_cogs = 24.00/2 = 12.00
      }
    }
  ],
  "coupon_lines": [
    { "code": "SUMMER20", "discount": "15.00", "discount_tax": "3.30" }
  ],                                               // → orders.discount_codes JSONB
  "meta_data": [
    { "id": 101, "key": "_wc_order_attribution_utm_source", "value": "facebook" },
    { "id": 102, "key": "_wc_order_attribution_utm_medium", "value": "paid" },
    { "id": 103, "key": "_wc_order_attribution_utm_campaign", "value": "summer_tof_2026" },
    { "id": 104, "key": "_wc_order_attribution_source_type", "value": "utm" },
    { "id": 105, "key": "_wc_order_attribution_session_entry", "value": "https://store-b.com/products/organic-cotton-tee" },
    { "id": 201, "key": "_stripe_fee", "value": "3.14" },       // → orders.transaction_fee
    { "id": 202, "key": "_stripe_net", "value": "146.76" }
  ]
}
```

### Webhook Payload (order.updated)

WooCommerce webhook payloads mirror the API response. Delivered to the URL registered during store connection.

```jsonc
// Headers:
//   X-WC-Webhook-Topic: order.updated
//   X-WC-Webhook-Signature: <HMAC-SHA256 base64>
//   X-WC-Webhook-Source: https://store-b.com/
//
// Body: identical shape to GET /wc/v3/orders/{id} response above
```

### Status Mapping

| WooCommerce `status` | → `orders.financial_status` |
|---|---|
| `processing` | `paid` |
| `completed` | `paid` |
| `on-hold` | `authorized` |
| `pending` | `pending` |
| `failed` | `voided` |
| `cancelled` | `cancelled` |
| `refunded` | `refunded` |

### Field Mapping Notes

| WC Field | Transform | DB Column |
|---|---|---|
| `meta_data[key=_wc_order_attribution_utm_*].value` | Filter array by key prefix | `orders.utm_source`, `utm_medium`, `utm_campaign` |
| `meta_data[key=_stripe_fee].value` | String → decimal | `orders.transaction_fee` (set `transaction_fee_estimated = false`) |
| `meta_data[key=_paypal_transaction_fee].value` | Same — fallback if no Stripe | `orders.transaction_fee` |
| `customer_id: 0` | Guest order — dedup by `billing.email` (lowercased, trimmed) | `customers.platform_customer_id = null` |
| `variation_id: 0` | Simple product, no variant | `order_line_items.platform_variant_id = null` |
| `cost_of_goods_sold.total_value` | WC 10.3+. Divide by `quantity` for per-unit. Also check `_wc_cog_cost` meta for legacy plugins. | `order_line_items.unit_cogs` |
| `payment_method: "cod"` | Cash on Delivery detection | `orders.is_cod = true` |

---

## 3. Meta Marketing API (v25.0)

### GET /act_{id}/insights Response

```jsonc
{
  "data": [
    {
      "campaign_id": "23851234567890123",          // → ad_campaigns.platform_campaign_id
      "campaign_name": "Summer TOF | SI | Broad",  // → ad_campaigns.name (parse with naming_delimiter)
      "adset_id": "23851234567890456",             // → ad_sets.platform_adset_id
      "adset_name": "Broad 18-45",                 // → ad_sets.name
      "ad_id": "23851234567890789",                // → ads.platform_ad_id
      "ad_name": "Carousel V2 | SI | TOF",         // → ads.name
      "date_start": "2026-04-28",                   // → ad_insights.date
      "date_stop": "2026-04-28",
      "spend": "42.17",                             // STRING → cast decimal → ad_insights.spend
      "impressions": "2847",                        // STRING → cast int → ad_insights.impressions
      "clicks": "89",                               // STRING → cast int → ad_insights.clicks
      "reach": "2103",                              // → ad_insights.reach
      "frequency": "1.35",                          // → ad_insights.frequency
      "cpm": "14.81",                               // (computed, not stored)
      "cpc": "0.47",                                // (computed, not stored)
      "ctr": "3.13",                                // (computed, not stored)
      "actions": [
        { "action_type": "link_click", "value": "76" },
        { "action_type": "add_to_cart", "value": "12" },           // → ad_insights.add_to_cart = 12
        { "action_type": "purchase", "value": "3" }                // → ad_insights.purchases = 3
      ],
      "action_values": [
        { "action_type": "purchase", "value": "247.85" }           // STRING → cast decimal → ad_insights.purchase_value
      ],
      "cost_per_action_type": [
        { "action_type": "purchase", "value": "14.06" }            // (computed, not stored — CPA = spend/purchases)
      ],
      "video_p25_watched_actions": [{ "action_type": "video_view", "value": "412" }],   // → ad_insights.video_views_p25
      "video_p50_watched_actions": [{ "action_type": "video_view", "value": "298" }],   // → ad_insights.video_views_p50
      "video_p75_watched_actions": [{ "action_type": "video_view", "value": "187" }],   // → ad_insights.video_views_p75
      "video_p100_watched_actions": [{ "action_type": "video_view", "value": "94" }]    // → ad_insights.video_views_p100
    }
  ],
  "paging": {
    "cursors": { "before": "MAZDZD", "after": "MjQZD" },
    "next": "https://graph.facebook.com/v25.0/act_123/insights?after=MjQZD"
  }
}
```

### Campaign Structure Response

```jsonc
// GET /act_{id}/campaigns?fields=id,name,status,objective,daily_budget,lifetime_budget
{
  "data": [
    {
      "id": "23851234567890123",                   // → ad_campaigns.platform_campaign_id
      "name": "Summer TOF | SI | Broad",           // → ad_campaigns.name
      "status": "ACTIVE",                           // lowercase → ad_campaigns.status = "active"
      "objective": "OUTCOME_SALES",                 // → ad_campaigns.objective = "conversions"
      "daily_budget": "5000",                       // cents string → 50.00 → ad_campaigns.daily_budget
      "lifetime_budget": "0"                        // 0 = not set → null
    }
  ]
}

// GET /act_{id}/adsets?fields=id,name,campaign_id,status,optimization_goal,targeting
{
  "data": [
    {
      "id": "23851234567890456",
      "name": "Broad 18-45",
      "campaign_id": "23851234567890123",           // lookup our campaign → ad_sets.campaign_id
      "status": "ACTIVE",
      "optimization_goal": "OFFSITE_CONVERSIONS",   // → ad_sets.optimization_goal
      "targeting": { "geo_locations": { "countries": ["SI"] }, "age_min": 18, "age_max": 45 }
    }
  ]
}
```

### Ad Creative Response

```jsonc
// GET /act_{id}/ads?fields=id,name,adset_id,status,creative{thumbnail_url,image_url,video_id,body,title,link_url}
{
  "data": [
    {
      "id": "23851234567890789",
      "name": "Carousel V2 | SI | TOF",
      "adset_id": "23851234567890456",
      "status": "ACTIVE",
      "creative": {
        "thumbnail_url": "https://scontent.xx.fbcdn.net/v/t45.1600-4/abc.jpg",  // → ads.creative_thumbnail_url
        "image_url": "https://scontent.xx.fbcdn.net/v/t45.1600-4/full.jpg",      // → ads.creative_image_url
        "video_id": null,                                                          // → ads.creative_video_id
        "body": "Shop our bestselling organic cotton tees. Free shipping over 50 EUR.",  // → ads.creative_body
        "title": "Organic Cotton Tee",                                             // → ads.creative_title
        "link_url": "https://store-a.com/products/organic-cotton-tee"             // → ads.creative_link_url
      }
    }
  ]
}
```

### Parsing Notes

- `actions` and `action_values` are separate arrays. Match by `action_type`.
- `value` fields in both arrays are **strings** — always cast: `(int)$action['value']` for counts, `(float)$actionValue['value']` for revenue.
- Default attribution window entries have **no suffix**. Suffixed entries (`_7d_click`, `_1d_view`) appear when `action_attribution_windows` param is set. Use the unsuffixed entries.
- `daily_budget` and `lifetime_budget` are in **cents** (string). Divide by 100.
- Campaign `status` is UPPER_CASE — lowercase before storing.

---

## 4. Google Ads API (v23.1)

### GAQL SearchStream Response

```jsonc
// Response from SearchStream (campaign-level query)
{
  "results": [
    {
      "campaign": {
        "resourceName": "customers/1234567890/campaigns/11223344",
        "id": "11223344",                            // → ad_campaigns.platform_campaign_id
        "name": "Search | Brand | SI",                // → ad_campaigns.name
        "status": "ENABLED",                          // map: ENABLED→active, PAUSED→paused, REMOVED→archived
        "advertisingChannelType": "SEARCH"            // map → ad_campaigns.campaign_type = "search"
      },
      "metrics": {
        "costMicros": "28450000",                     // divide by 1,000,000 → 28.45 → ad_insights.spend
        "impressions": "1523",                        // → ad_insights.impressions
        "clicks": "67",                               // → ad_insights.clicks
        "conversions": 4.0,                           // float (can be fractional) → round → ad_insights.purchases
        "conversionsValue": 389.60                    // float → ad_insights.purchase_value
      },
      "segments": {
        "date": "2026-04-28"                          // → ad_insights.date
      }
    }
  ]
}

// Ad group level query response
{
  "results": [
    {
      "campaign": {
        "name": "Search | Brand | SI"
      },
      "adGroup": {
        "resourceName": "customers/1234567890/adGroups/55667788",
        "id": "55667788",                            // → ad_sets.platform_adset_id (ad_group → ad_set)
        "name": "Brand Exact",                        // → ad_sets.name
        "status": "ENABLED"
      },
      "adGroupAd": {
        "ad": {
          "id": "99887766",                           // → ads.platform_ad_id
          "type": "RESPONSIVE_SEARCH_AD",
          "finalUrls": ["https://store-a.com/"]       // [0] → ads.creative_link_url
        }
      },
      "metrics": {
        "costMicros": "12300000",                     // 12.30
        "impressions": "845",
        "clicks": "34",
        "conversions": 2.0,
        "conversionsValue": 198.50
      },
      "segments": { "date": "2026-04-28" }
    }
  ]
}
```

### Shopping SKU-Level Response

```jsonc
{
  "results": [
    {
      "segments": {
        "productItemId": "shopify_SI_8912345678901_44123456789012",  // → ad_insights.product_item_id
        "date": "2026-04-28"
      },
      "metrics": {
        "costMicros": "8750000",                     // 8.75
        "impressions": "412",
        "clicks": "18",
        "conversions": 1.0,
        "conversionsValue": 49.95
      }
    }
  ]
}
```

### Field Mapping Notes

| Google Ads Field | Transform | DB Column |
|---|---|---|
| `costMicros` | Divide by 1,000,000 (`28450000` → `28.45`) | `ad_insights.spend` |
| `conversions` | Float (can be 2.5 for data-driven) → store as-is or round | `ad_insights.purchases` |
| `advertisingChannelType` | `SEARCH`→`search`, `DISPLAY`→`display`, `SHOPPING`→`shopping`, `PERFORMANCE_MAX`→`pmax`, `VIDEO`→`video` | `ad_campaigns.campaign_type` |
| `adGroup.*` | Google calls them "ad groups" — we normalize to "ad sets" | `ad_sets.*` |
| `campaign.status` | `ENABLED`→`active`, `PAUSED`→`paused`, `REMOVED`→`archived` | `ad_campaigns.status` |

---

## 5. GA4 Data API (v1beta)

### POST /v1beta/properties/{propertyId}:runReport Response

```jsonc
{
  "dimensionHeaders": [
    { "name": "date" },
    { "name": "sessionDefaultChannelGroup" },
    { "name": "sessionSource" },
    { "name": "sessionMedium" },
    { "name": "landingPagePlusQueryString" },
    { "name": "country" },
    { "name": "deviceCategory" }
  ],
  "metricHeaders": [
    { "name": "sessions", "type": "TYPE_INTEGER" },
    { "name": "engagedSessions", "type": "TYPE_INTEGER" },
    { "name": "totalUsers", "type": "TYPE_INTEGER" },
    { "name": "newUsers", "type": "TYPE_INTEGER" },
    { "name": "ecommercePurchases", "type": "TYPE_INTEGER" },
    { "name": "purchaseRevenue", "type": "TYPE_CURRENCY" },
    { "name": "addToCarts", "type": "TYPE_INTEGER" },
    { "name": "checkouts", "type": "TYPE_INTEGER" },
    { "name": "itemViews", "type": "TYPE_INTEGER" },
    { "name": "bounceRate", "type": "TYPE_FLOAT" }
  ],
  "rows": [
    {
      "dimensionValues": [
        { "value": "20260428" },                       // parse YYYYMMDD → ga4_daily.date
        { "value": "Organic Search" },                 // (channel grouping — not stored, we classify ourselves)
        { "value": "google" },                         // → ga4_daily.source
        { "value": "organic" },                        // → ga4_daily.medium
        { "value": "/products/organic-cotton-tee" },   // → ga4_daily.landing_page
        { "value": "Slovenia" },                       // → iso3166 lookup → ga4_daily.country = "SI"
        { "value": "mobile" }                          // → ga4_daily.device_category
      ],
      "metricValues": [
        { "value": "142" },                            // → ga4_daily.sessions
        { "value": "98" },                             // → ga4_daily.engaged_sessions
        { "value": "128" },                            // → ga4_daily.total_users
        { "value": "87" },                             // → ga4_daily.new_users
        { "value": "5" },                              // → ga4_daily.purchases
        { "value": "487.50" },                         // → ga4_daily.purchase_revenue
        { "value": "18" },                             // → ga4_daily.add_to_carts
        { "value": "9" },                              // → ga4_daily.checkouts_started
        { "value": "312" },                            // → ga4_daily.item_views
        { "value": "0.3099" }                          // → ga4_daily.bounce_rate (already decimal 0-1)
      ]
    }
  ],
  "rowCount": 847,
  "metadata": {
    "samplingMetadatas": [
      {
        "samplesReadCounts": ["4821903"],              // if present → data is sampled
        "samplingSpaceSizes": ["10482710"]             // sample rate = read/space = ~46%
      }
    ],
    "dataLossFromOtherRow": false                      // true = "(other)" row exists — too many dimension combos
  }
}
```

### Field Mapping Notes

| GA4 Field | Transform | DB Column |
|---|---|---|
| `date` dimension | Parse `YYYYMMDD` string → date object | `ga4_daily.date` |
| `country` dimension | Full name ("Slovenia") → `league/iso3166` → `"SI"` | `ga4_daily.country` |
| `landingPagePlusQueryString` | Strip query params for cleaner grouping | `ga4_daily.landing_page` |
| `bounceRate` | Already a 0-1 decimal (0.3099 = 30.99%) | `ga4_daily.bounce_rate` |
| `samplingMetadatas` | If present, log warning — narrow date range to reduce sampling | (monitoring only) |
| All metric values | Returned as strings — cast to int/decimal | various |

---

## 6. Google Search Console (Search Analytics API)

### POST /webmasters/v3/sites/{siteUrl}/searchAnalytics/query Response

Request body:
```jsonc
{
  "startDate": "2026-04-28",
  "endDate": "2026-04-28",
  "dimensions": ["query", "page", "country", "device"],
  "rowLimit": 25000,
  "startRow": 0
}
```

Response:
```jsonc
{
  "rows": [
    {
      "keys": [
        "organic cotton tee",                        // keys[0] → gsc_daily.query
        "https://store-a.com/products/organic-cotton-tee",  // keys[1] → gsc_daily.page; strip host+params → gsc_daily.page_path = "/products/organic-cotton-tee"
        "svn",                                       // keys[2] → uppercase → gsc_daily.country = "SI" (GSC uses ISO 3166-1 alpha-3 lowercase — convert)
        "MOBILE"                                     // keys[3] → lowercase → gsc_daily.device = "mobile"
      ],
      "clicks": 12.0,                                // → gsc_daily.clicks (float → int)
      "impressions": 487.0,                           // → gsc_daily.impressions
      "ctr": 0.024640657,                             // (not stored — computed from clicks/impressions)
      "position": 4.7                                 // → gsc_daily.position
    },
    {
      "keys": [
        "best cotton t-shirt europe",
        "https://store-a.com/collections/tees",
        "deu",
        "DESKTOP"
      ],
      "clicks": 8.0,
      "impressions": 312.0,
      "ctr": 0.025641026,
      "position": 7.2
    }
  ],
  "responseAggregationType": "byPage"
}
```

### Field Mapping Notes

| GSC Field | Transform | DB Column |
|---|---|---|
| `keys[1]` (page URL) | Strip protocol + host + query params → path only | `gsc_daily.page_path` (also store full URL in `gsc_daily.page`) |
| `keys[2]` (country) | 3-letter lowercase (`svn`) → ISO 3166-1 alpha-2 (`SI`) | `gsc_daily.country` |
| `keys[3]` (device) | Uppercase → lowercase (`MOBILE` → `mobile`) | `gsc_daily.device` |
| `clicks`, `impressions` | Float → cast to int | `gsc_daily.clicks`, `gsc_daily.impressions` |
| `position` | Float, keep as decimal | `gsc_daily.position` |
| `startRow` | Paginate in increments of 25,000 until fewer rows than `rowLimit` returned | (pagination logic) |

---

## 7. Klaviyo (Revision 2026-04-15)

### GET /api/campaigns Response (JSON:API format)

```jsonc
// Headers: Authorization: Bearer <access_token>
//          revision: 2026-04-15
{
  "data": [
    {
      "type": "campaign",
      "id": "01HWXY1234ABCDEF",                     // → email_campaigns.platform_campaign_id
      "attributes": {
        "name": "Summer Collection Launch",           // → email_campaigns.name
        "status": "Sent",                             // map: Sent→sent, Scheduled→scheduled, Draft→draft
        "channel": "email",                           // → email_campaigns.channel
        "send_time": "2026-04-25T10:00:00+00:00",   // → email_campaigns.sent_at
        "created_at": "2026-04-20T08:30:00+00:00",
        "updated_at": "2026-04-25T12:00:00+00:00"
      },
      "relationships": {
        "campaign-messages": {
          "data": [{ "type": "campaign-message", "id": "msg_abc123" }]
        }
      },
      "links": { "self": "https://a.klaviyo.com/api/campaigns/01HWXY1234ABCDEF" }
    }
  ],
  "links": {
    "self": "https://a.klaviyo.com/api/campaigns",
    "next": "https://a.klaviyo.com/api/campaigns?page[cursor]=eyJwYWdlIjog..."
  }
}
```

### GET /api/flows Response

```jsonc
{
  "data": [
    {
      "type": "flow",
      "id": "FLOW_ABC123",                           // → email_flows.platform_flow_id
      "attributes": {
        "name": "Welcome Series",                     // → email_flows.name
        "status": "live",                             // → email_flows.status
        "trigger_type": "List",                       // → email_flows.trigger_type
        "created": "2026-01-15T09:00:00+00:00",
        "updated": "2026-04-20T14:30:00+00:00"
      }
    }
  ],
  "links": { "next": null }
}
```

### POST /api/campaign-values-reports Response

```jsonc
// Request body:
// { "data": { "type": "campaign-values-report", "attributes": {
//   "timeframe": { "start": "2026-04-01T00:00:00Z", "end": "2026-04-30T23:59:59Z" },
//   "conversion_metric_id": "METRIC_PLACED_ORDER_UUID",
//   "statistics": ["recipients", "delivered", "opens_unique", "clicks_unique",
//                   "bounced", "unsubscribes", "conversion_uniques", "conversion_value"],
//   "group_by": ["campaign_id", "send_channel"]
// }}}

{
  "data": {
    "type": "campaign-values-report",
    "attributes": {
      "results": [
        {
          "groupings": {
            "campaign_id": "01HWXY1234ABCDEF",       // lookup → email_campaigns.platform_campaign_id
            "send_channel": "email"
          },
          "statistics": {
            "recipients": 12450,                       // → email_campaigns.recipients
            "delivered": 12103,
            "opens_unique": 4821,                      // → email_campaigns.opens
            "clicks_unique": 892,                      // → email_campaigns.clicks
            "bounced": 347,                            // → email_campaigns.bounce_count
            "unsubscribes": 23,                        // → email_campaigns.unsubscribe_count
            "conversion_uniques": 156,                 // → email_campaigns.conversions
            "conversion_value": 14280.50               // → email_campaigns.revenue
          }
        }
      ]
    }
  }
}
```

### GET /api/profiles/{id} — Predictive Analytics

```jsonc
{
  "data": {
    "type": "profile",
    "id": "01HWXY9876PROFILE",
    "attributes": {
      "email": "jana@example.com",
      "first_name": "Jana",
      "last_name": "Novak",
      "predictive_analytics": {
        "predicted_customer_lifetime_value": 487.50,    // → customers.predicted_clv (if syncing Klaviyo profiles)
        "expected_date_of_next_order": "2026-06-15",    // → customers.predicted_next_order_at
        "churn_risk_prediction": "LOW"                  // LOW/MEDIUM/HIGH (informational)
      }
    }
  }
}
```

### Field Mapping Notes

- JSON:API envelope: actual data is always inside `data[].attributes`. Relationships use `data[].relationships`.
- Cursor-based pagination: follow `links.next` until null.
- Reporting API rate limit: 1/s burst, 2/min steady, 225 calls/day — batch date ranges to minimize calls.
- Campaign status: Klaviyo uses capitalized ("Sent", "Draft") — lowercase before storing.
- `conversion_metric_id`: discover the `Placed Order` metric UUID via `GET /api/metrics` once, then cache it.

---

## 8. Stripe (via Laravel Cashier)

Stripe webhooks are handled by Laravel Cashier's `WebhookController`. We listen for specific events via `WebhookReceived` / `WebhookHandled` Laravel events in `EventServiceProvider`.

### checkout.session.completed Webhook

```jsonc
{
  "id": "evt_1PqR2sABCDEF123456",
  "type": "checkout.session.completed",
  "data": {
    "object": {
      "id": "cs_live_a1b2c3d4e5f6",
      "customer": "cus_aBcDeFgH123456",            // → workspaces.stripe_customer_id
      "subscription": "sub_1PqR2sABCDEF789",
      "status": "complete",
      "mode": "subscription",
      "metadata": {
        "workspace_id": "42"                         // set during checkout session creation
      }
    }
  },
  "created": 1714305791
}
```

### customer.subscription.updated Webhook

```jsonc
{
  "id": "evt_1PqR3tABCDEF654321",
  "type": "customer.subscription.updated",
  "data": {
    "object": {
      "id": "sub_1PqR2sABCDEF789",
      "customer": "cus_aBcDeFgH123456",
      "status": "active",                            // → workspaces.stripe_status
      "current_period_end": 1717000000,
      "trial_end": null,                             // → workspaces.trial_ends_at
      "cancel_at_period_end": false,
      "items": {
        "data": [
          {
            "price": {
              "id": "price_1Abc2DefGhIjKl",
              "unit_amount": 3900,                   // cents → 39.00 EUR
              "currency": "eur",
              "recurring": { "interval": "month" }
            }
          }
        ]
      }
    },
    "previous_attributes": {
      "status": "trialing"                           // was trialing, now active
    }
  }
}
```

### invoice.payment_failed Webhook

```jsonc
{
  "id": "evt_1PqR4uABCDEF111222",
  "type": "invoice.payment_failed",
  "data": {
    "object": {
      "id": "in_1PqR4uABCDEF333444",
      "customer": "cus_aBcDeFgH123456",
      "subscription": "sub_1PqR2sABCDEF789",
      "status": "open",                              // invoice still open, payment failed
      "amount_due": 3900,
      "currency": "eur",
      "attempt_count": 1,
      "next_payment_attempt": 1714478591             // Stripe will retry
    }
  }
}
```

### Event Handling Notes

- Cashier handles subscription lifecycle automatically — updates `stripe_status` on the billable model.
- On `invoice.payment_failed`: after 4 retries (Stripe default), subscription moves to `past_due`. Set `workspaces.syncs_paused_at` when status becomes `past_due` or `canceled`.
- On trial end without payment: `customer.subscription.updated` fires with `status: "past_due"`.
- `metadata.workspace_id` is set during `Checkout::create()` to link sessions to workspaces.

---

## 9. Frankfurter API (FX Rates)

### GET /v1/latest?base=EUR&symbols=USD,GBP,SEK

```jsonc
{
  "amount": 1.0,
  "base": "EUR",                                     // → fx_rates.base_currency
  "date": "2026-04-28",                              // → fx_rates.date
  "rates": {
    "USD": 1.0842,                                   // → fx_rates.rate WHERE target_currency = "USD"
    "GBP": 0.8561,                                   // → fx_rates.rate WHERE target_currency = "GBP"
    "SEK": 11.0247                                   // → fx_rates.rate WHERE target_currency = "SEK"
  }
}
```

### Historical Rate: GET /v1/2026-04-15?base=GBP&symbols=USD

```jsonc
{
  "amount": 1.0,
  "base": "GBP",
  "date": "2026-04-15",
  "rates": {
    "USD": 1.2663
  }
}
```

### Sync Notes

- `SyncFxRatesJob` runs daily at 17:00 UTC (after ECB publishes ~16:00 CET).
- Fetch with workspace `reporting_currency` as base, all target currencies that appear in connected stores/ad accounts.
- Weekend/holiday requests automatically return last business day's rate.
- One row per `(base_currency, target_currency, date)` combination — upsert.
- 31 currencies supported (ECB set). Covers EUR, USD, GBP, SEK, DKK, NOK, PLN, CZK, CHF, etc.

---

## 10. Nager.Date API (Public Holidays)

### GET /api/v3/PublicHolidays/2026/SI

```jsonc
[
  {
    "date": "2026-01-01",                            // → holidays.date
    "localName": "novo leto",
    "name": "New Year's Day",                        // → holidays.name
    "countryCode": "SI",                             // → holidays.country
    "fixed": true,
    "global": true,
    "counties": null,
    "launchYear": null,
    "types": ["Public"]                              // → holidays.type = "public"
  },
  {
    "date": "2026-02-08",
    "localName": "Presernov dan",
    "name": "Preseren Day",
    "countryCode": "SI",
    "fixed": true,
    "global": true,
    "counties": null,
    "launchYear": 1945,
    "types": ["Public"]
  },
  {
    "date": "2026-04-06",
    "localName": "velikonocni ponedeljek",
    "name": "Easter Monday",
    "countryCode": "SI",
    "fixed": false,
    "global": true,
    "counties": null,
    "launchYear": null,
    "types": ["Public"]
  },
  {
    "date": "2026-12-25",
    "localName": "Bozic",
    "name": "Christmas Day",
    "countryCode": "SI",
    "fixed": true,
    "global": true,
    "counties": null,
    "launchYear": null,
    "types": ["Public"]
  }
]
```

### Sync Notes

- `SyncStatutoryHolidaysJob` runs weekly on Sundays at 08:00 UTC.
- Fetch for each workspace's country (derived from `reporting_timezone` or billing address).
- Store with `type = 'public'`, `is_ecommerce_event = false`.
- Ecommerce events (Black Friday, Cyber Monday, etc.) are seeded separately — not from this API.
- No API key required. Free, covers 100+ countries.
- Upsert by `(date, country, name)` to avoid duplicates.
