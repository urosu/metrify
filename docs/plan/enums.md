# Nexstage PHP 8.5 Backed Enums

All enums use `string` backing type matching database varchar values. Cast via `$casts` on models.

## 1. FinancialStatus — `orders.financial_status`
```php
enum FinancialStatus: string
{
    case Pending            = 'pending';
    case Authorized         = 'authorized';
    case PartiallyPaid      = 'partially_paid';
    case Paid               = 'paid';
    case PartiallyRefunded  = 'partially_refunded';
    case Refunded           = 'refunded';
    case Voided             = 'voided';
    case Cancelled          = 'cancelled';
    case Expired            = 'expired';

    public function isExcludedFromRevenue(): bool // 8+ queries
    { return in_array($this, [self::Refunded, self::Voided, self::Cancelled]); }
}
```
Transitions: `pending → authorized → paid → partially_refunded → refunded` | `authorized → voided` | `pending → expired/cancelled`
WC mapping: `pending→pending`, `on-hold→authorized`, `processing/completed→paid`, `refunded→refunded`, `failed→voided`, `cancelled→cancelled`, `checkout-draft→skip`, unknown→`pending`+warn.
Shopify: `displayFinancialStatus` returns UPPER_CASE — lowercase before storing.

## 2. FulfillmentStatus — `orders.fulfillment_status`
```php
enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Partial     = 'partial';
    case Fulfilled   = 'fulfilled';
    case Restocked   = 'restocked';
}
```
Transitions: `unfulfilled → partial → fulfilled → restocked`
WC mapping: `pending/processing/on-hold→unfulfilled`, `completed→fulfilled`, `refunded→restocked`, `cancelled/failed→unfulfilled`

## 3. SyncStatus — `stores/ad_accounts/analytics_properties/search_properties/email_accounts.sync_status`
```php
enum SyncStatus: string
{
    case Pending      = 'pending';      // connected, awaiting first sync
    case Syncing      = 'syncing';      // initial import in progress
    case Active       = 'active';       // healthy, recurring syncs
    case Paused       = 'paused';       // user-paused or subscription expired
    case Error        = 'error';        // failed, will retry
    case Disconnected = 'disconnected'; // removed or token revoked
}
```
Transitions: `pending → syncing → active ⇄ paused` | `active → error → active` | `any → disconnected → pending` (reconnect)

## 4. StorePlatform — `stores.platform`
```php
enum StorePlatform: string
{
    case Shopify     = 'shopify';
    case WooCommerce = 'woocommerce';
}
```

## 5. AdPlatform — `ad_accounts.platform`, `ad_campaigns.platform`, `ad_insights.platform`
```php
enum AdPlatform: string
{
    case Meta   = 'meta';
    case Google = 'google';
    case TikTok = 'tiktok'; // v2
}
```

## 6. AdLevel — `ad_insights.level`
```php
enum AdLevel: string
{
    case Campaign = 'campaign';
    case AdSet    = 'ad_set';
    case Ad       = 'ad';
}
```

## 7. CampaignType — `ad_campaigns.campaign_type` (nullable)
```php
enum CampaignType: string
{
    case Search        = 'search';
    case Shopping      = 'shopping';
    case Display       = 'display';
    case Video         = 'video';
    case Pmax          = 'pmax';           // Google Performance Max
    case AdvantagePlus = 'advantage_plus'; // Meta Advantage+
    case App           = 'app';
    case Other         = 'other';
}
```
**Google Ads mapping:** `SEARCH→search`, `DISPLAY→display`, `SHOPPING→shopping`, `PERFORMANCE_MAX→pmax`, `VIDEO→video`, else `other`.

## 8. Channel — `orders.channel`, `channel_mappings.channel`
```php
enum Channel: string
{
    case PaidSearch      = 'paid_search';     case PaidSocial    = 'paid_social';
    case PaidVideo       = 'paid_video';      case PaidShopping  = 'paid_shopping';
    case CrossNetwork    = 'cross_network';   case Display       = 'display';
    case Email           = 'email';           case Sms           = 'sms';
    case Affiliate       = 'affiliate';       case MobilePush    = 'mobile_push';
    case OrganicSearch   = 'organic_search';  case OrganicSocial = 'organic_social';
    case OrganicVideo    = 'organic_video';   case OrganicShopping = 'organic_shopping';
    case Referral        = 'referral';        case Direct        = 'direct';
    case Unassigned      = 'unassigned';

    public function isPaid(): bool
    {
        return in_array($this, [self::PaidSearch, self::PaidSocial, self::PaidVideo,
            self::PaidShopping, self::CrossNetwork, self::Display]);
    }
}
```
277 rules in `ChannelClassifierService` (first-match-wins). See `docs/research/channel-mapping.md`.

## 9. WorkspaceRole — `workspace_users.role`
```php
enum WorkspaceRole: string
{
    case Owner  = 'owner';  // full access, non-removable
    case Admin  = 'admin';  // full access, capability flags ignored
    case Member = 'member'; // restricted by can_access_* flags
}
```

## 10. AlertSeverity — `alerts.severity`, `alert_rules.severity`
```php
enum AlertSeverity: string
{
    case Critical = 'critical';
    case Warning  = 'warning';
    case Info     = 'info';
}
```

## 11. AlertType — `alerts.type`
```php
enum AlertType: string
{
    case MetricAnomaly      = 'metric_anomaly';
    case SpeedDrop          = 'speed_drop';
    case LowStock           = 'low_stock';
    case RfmMigration       = 'rfm_migration';
    case SourceDisagreement = 'source_disagreement';
    case IntegrationError   = 'integration_error';
    case SystemAlert        = 'system_alert';
}
```

## 12. CogsSource — `order_line_items.cogs_source` (how COGS was resolved)
```php
enum CogsSource: string
{
    case Explicit         = 'explicit';          // variant/product cogs_entry matched
    case StoreDefault     = 'store_default';     // store-level fallback
    case WorkspaceDefault = 'workspace_default'; // workspace default_cogs_margin_pct
    case None             = 'none';              // no COGS data available
}
```

## 13. CogsEntrySource — `cogs_entries.source` (how COGS entry was created)
```php
enum CogsEntrySource: string
{
    case Manual      = 'manual';
    case Csv         = 'csv';
    case Shopify     = 'shopify';     // inventoryItem.unitCost
    case WooCommerce = 'woocommerce'; // cost_of_goods_sold or _wc_cog_cost
    case Supplier    = 'supplier';    // v2
}
```

## 14. AttributionModel — `workspaces.attribution_model`
```php
enum AttributionModel: string
{
    case LastClick  = 'last_click';
    case FirstClick = 'first_click';
    case Linear     = 'linear';
    // position_based, time_decay: v2
}
```

## 15. Plan — `workspaces.plan`
```php
enum Plan: string
{
    case Trialing = 'trialing';
    case Active   = 'active';
    case PastDue  = 'past_due';
    case Canceled = 'canceled';
}
```

## 16. RfmSegment — `customers.rfm_segment`
```php
enum RfmSegment: string
{
    case Champions         = 'champions';
    case Loyal             = 'loyal';
    case PotentialLoyalists = 'potential_loyalists';
    case AtRisk            = 'at_risk';
    case NeedsAttention    = 'needs_attention';
    case AboutToSleep      = 'about_to_sleep';
    case Hibernating       = 'hibernating';
}
```
3-tier (20-100 customers): Best=Champions+Loyal, Average=PotentialLoyalists+NeedsAttention, AtRisk=AboutToSleep+Hibernating. <20: skip.

## 17. ExportFormat
```php
enum ExportFormat: string { case Csv = 'csv'; case Xlsx = 'xlsx'; case Pdf = 'pdf'; }
```

## 18. ExportStatus — `exports.status`
```php
enum ExportStatus: string { case Queued = 'queued'; case Processing = 'processing'; case Completed = 'completed'; case Failed = 'failed'; }
```

## 19. DigestFrequency — `digest_schedules.frequency`
```php
enum DigestFrequency: string { case Daily = 'daily'; case Weekly = 'weekly'; }
```

## 20. OperationalCostFrequency — `operational_costs.frequency`
```php
enum OperationalCostFrequency: string { case Monthly = 'monthly'; case Weekly = 'weekly'; case Daily = 'daily'; case OneTime = 'one_time'; }
```

## 21. FeeType — `platform_fee_rules.fee_type`
```php
enum FeeType: string { case Percentage = 'percentage'; case FlatMonthly = 'flat_monthly'; }
```

## 22. IntegrationType — `sync_logs.integration_type` (polymorphic, not morphTo)
```php
enum IntegrationType: string
{
    case Store             = 'store';
    case AdAccount         = 'ad_account';
    case AnalyticsProperty = 'analytics_property';
    case SearchProperty    = 'search_property';
    case EmailAccount      = 'email_account';
}
```

## 23. DeviceCategory — `ga4_daily.device_category`, `gsc_daily.device`
```php
enum DeviceCategory: string { case Desktop = 'desktop'; case Mobile = 'mobile'; case Tablet = 'tablet'; }
```

## 24. SegmentOperator — `customer_segments.rules` JSONB, `saved_views.filters` JSONB
```php
enum SegmentOperator: string
{
    case Gt       = 'gt';
    case Gte      = 'gte';
    case Lt       = 'lt';
    case Lte      = 'lte';
    case Eq       = 'eq';
    case In       = 'in';
    case NotIn    = 'not_in';
    case Between  = 'between';
    case Contains = 'contains'; // saved_views only
}
```
Compat: country/channel/rfm_segment accept `eq/in/not_in` only. Numeric fields: all ops.

## 25. SegmentField — `customer_segments.rules` JSONB validation
```php
enum SegmentField: string
{
    case OrdersCount  = 'orders_count';
    case TotalSpent   = 'total_spent';
    case RfmSegment   = 'rfm_segment';
    case FirstOrderAt = 'first_order_at';
    case LastOrderAt  = 'last_order_at';
    case Country      = 'country';
    case Channel      = 'channel';
}
```

## 26. WebhookTopic — Shopify registered webhook topics
```php
enum WebhookTopic: string
{
    case OrdersCreate          = 'orders_create';
    case OrdersUpdated         = 'orders_updated';
    case OrdersCancelled       = 'orders_cancelled';
    case OrdersDelete          = 'orders_delete';
    case RefundsCreate         = 'refunds_create';
    case ProductsUpdate        = 'products_update';
    case InventoryLevelsUpdate = 'inventory_levels_update';
    case AppUninstalled        = 'app_uninstalled';

    public function shopifyValue(): string { return strtoupper($this->value); } // SCREAMING_SNAKE for API
}
```

## 27. OAuthProvider
```php
enum OAuthProvider: string
{
    case Facebook = 'facebook'; // Meta Ads
    case Google   = 'google';   // Google Ads, GA4, GSC (shared creds)
    case Klaviyo  = 'klaviyo';
    case Shopify  = 'shopify';
}
```

## 28. AlertCondition — `alert_rules.condition`
```php
enum AlertCondition: string { case Above = 'above'; case Below = 'below'; case ChangePct = 'change_pct'; }
```

## 29. PageType — `saved_views.page`, `exports.page`
```php
enum PageType: string
{
    case Orders    = 'orders';
    case Products  = 'products';
    case Campaigns = 'campaigns';
    case Customers = 'customers';
}
```
`shared_links.page` also allows dashboard/profit/marketing (free-form varchar).

## 30. SharedLinkType — derived from `shared_links.is_live`
```php
enum SharedLinkType: string { case Frozen = 'frozen'; case Live = 'live'; } // false/true
```

## 31. CurrencyCode — `workspaces.reporting_currency`, `fx_rates.*_currency`
ECB via Frankfurter API (EUR base + 30 targets, 32 total).
```php
enum CurrencyCode: string
{
    case EUR = 'EUR'; case USD = 'USD'; case GBP = 'GBP'; case CHF = 'CHF';
    case SEK = 'SEK'; case NOK = 'NOK'; case DKK = 'DKK'; case PLN = 'PLN';
    case CZK = 'CZK'; case HUF = 'HUF'; case RON = 'RON'; case BGN = 'BGN';
    case HRK = 'HRK'; case ISK = 'ISK'; case TRY = 'TRY'; case AUD = 'AUD';
    case CAD = 'CAD'; case NZD = 'NZD'; case JPY = 'JPY'; case CNY = 'CNY';
    case HKD = 'HKD'; case SGD = 'SGD'; case KRW = 'KRW'; case THB = 'THB';
    case INR = 'INR'; case BRL = 'BRL'; case MXN = 'MXN'; case ZAR = 'ZAR';
    case ILS = 'ILS'; case IDR = 'IDR'; case MYR = 'MYR'; case PHP = 'PHP';

    /** 29 of 32 allowed as reporting currency (IDR/MYR/PHP excluded). */
    public static function reportingCurrencies(): array
    {
        return array_filter(self::cases(), fn ($c) => !in_array($c, [self::IDR, self::MYR, self::PHP]));
    }
}
```
---

## Database-Only Status Values (no dedicated enum needed)

| Column | Values |
|--------|--------|
| `ad_campaigns/ad_sets/ads.status` | `active`, `paused`, `archived` |
| `products.status` | `active`, `draft`, `archived` |
| `email_campaigns.channel` | `email`, `sms` |
| `email_campaigns.status` | `sent`, `scheduled`, `draft` |
| `email_flows.status` | `live`, `manual`, `draft` |
| `sync_logs.status` | `started`, `completed`, `failed`, `partial` |
| `annotations.category` | `sale`, `campaign`, `price_change`, `mailing`, `other` |
| `holidays.type` | `ecommerce`, `public`, `religious` |
| `operational_costs.category` | `saas`, `rent`, `salary`, `marketing`, `other` |
| `page_speeds.strategy` | `mobile`, `desktop` |
| `page_speeds.source` | `crux`, `lighthouse` |
| `alert_rules.scope` | `workspace`, `store`, `channel`, `campaign` |
| `alert_rules.channel` | `in_app`, `email` (slack: v2) |
| `digest_schedules.delivery_channel` | `email` (slack: v2) |
