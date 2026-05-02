# Service Contracts

Complete PHP signatures for all internal services, value objects, base classes, and middleware. No implementation bodies -- just types, parameters, return types, docblocks, and thrown exceptions.

Cross-reference: `coding-spec.md` section 21, `non-obvious-issues.md` build order, `tech-stack.md` architecture patterns.

---

## 1. CurrencyConverterService

```php
<?php

namespace App\Services;

use App\Enums\CurrencyCode;
use App\Exceptions\CurrencyMismatchException;
use App\Exceptions\FxRateNotFoundException;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;

/**
 * Converts monetary amounts between currencies using the fx_rates table.
 *
 * FX lookup uses LATERAL JOIN with "WHERE date <= :date ORDER BY date DESC LIMIT 1"
 * to automatically fall back to Friday's rate on weekends (ECB publishes weekdays only).
 *
 * Rounding: banker's rounding (PHP_ROUND_HALF_EVEN) to 2 decimal places.
 *
 * Build order: #4 — before any money handling.
 */
class CurrencyConverterService
{
    /**
     * Convert an amount from one currency to another on a specific date.
     *
     * Uses the most recent FX rate on or before the given date (weekend fallback).
     * Returns the converted amount with banker's rounding to 2 decimals.
     *
     * @param  string|float  $amount        The amount to convert (string recommended for precision).
     * @param  CurrencyCode  $fromCurrency  Source currency.
     * @param  CurrencyCode  $toCurrency    Target currency.
     * @param  CarbonImmutable  $date       The date for rate lookup (order date, upload date, etc.).
     * @return Money  Converted amount in the target currency.
     *
     * @throws FxRateNotFoundException  When no FX rate exists for the pair on or before the date.
     */
    public function convert(
        string|float $amount,
        CurrencyCode $fromCurrency,
        CurrencyCode $toCurrency,
        CarbonImmutable $date,
    ): Money;

    /**
     * Attempt conversion, returning null on failure instead of throwing.
     *
     * Used during order sync where a missing rate should not crash the entire job
     * (non-obvious-issues.md #148). The order's total_price_converted is set to NULL
     * and dashboard handles it with COALESCE.
     *
     * @param  string|float  $amount
     * @param  CurrencyCode  $fromCurrency
     * @param  CurrencyCode  $toCurrency
     * @param  CarbonImmutable  $date
     * @return Money|null  Null when no FX rate is available.
     */
    public function tryConvert(
        string|float $amount,
        CurrencyCode $fromCurrency,
        CurrencyCode $toCurrency,
        CarbonImmutable $date,
    ): ?Money;

    /**
     * Get the raw FX rate for a currency pair on a given date.
     *
     * @throws FxRateNotFoundException
     */
    public function getRate(
        CurrencyCode $fromCurrency,
        CurrencyCode $toCurrency,
        CarbonImmutable $date,
    ): string;
}
```

---

## 2. ChannelClassifierService

```php
<?php

namespace App\Services;

use App\Enums\Channel;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Classifies orders into marketing channels using a priority-based rule engine.
 *
 * Resolution order:
 *   1. Click-ID detection: gclid->paid_search, fbclid->paid_social,
 *      ttclid->paid_social, msclkid->paid_search
 *   2. Walk channel_mappings by priority (first-match-wins, workspace rules override global)
 *   3. Referrer-only fallback: match domain against in-memory lists
 *      (SEARCH_DOMAINS, SOCIAL_DOMAINS, VIDEO_DOMAINS, SHOPPING_DOMAINS)
 *   4. source=(direct) + medium=(none) -> direct. Everything else -> unassigned.
 *
 * Channel mappings are loaded ONCE at construction (non-obvious-issues.md #113).
 * Constructor accepts optional pre-loaded rules for bulk import performance.
 *
 * Build order: #7 — before any order sync.
 */
class ChannelClassifierService
{
    /**
     * @param  Collection<int, \App\Models\ChannelMapping>|null  $rules
     *         Pre-loaded channel mappings. When null, loads from DB at construction
     *         (workspace rules + global defaults, ordered by priority).
     */
    public function __construct(?Collection $rules = null);

    /**
     * Classify an order into a marketing channel.
     *
     * Examines the order's UTM parameters, click IDs (from touchpoints JSONB),
     * and referring site. Returns the matched Channel enum value.
     *
     * @param  Order  $order  Must have utm_source, utm_medium, utm_campaign,
     *                        landing_page, referrer, and touchpoints loaded.
     * @return Channel  One of the 17 valid channels (paid_search, paid_social,
     *                  organic_search, direct, unassigned, etc.).
     */
    public function classify(Order $order): Channel;

    /**
     * Classify from raw UTM/referrer data without a persisted Order model.
     *
     * Used during webhook processing before the order is saved.
     *
     * @param  string|null  $utmSource
     * @param  string|null  $utmMedium
     * @param  string|null  $utmCampaign
     * @param  string|null  $referrer
     * @param  string|null  $landingPage
     * @param  array<string, mixed>|null  $clickIds  e.g., ['gclid' => 'abc', 'fbclid' => 'xyz']
     * @return Channel
     */
    public function classifyFromParams(
        ?string $utmSource,
        ?string $utmMedium,
        ?string $utmCampaign,
        ?string $referrer,
        ?string $landingPage,
        ?array $clickIds = null,
    ): Channel;

    /**
     * Reload channel mappings from the database.
     *
     * Called by ReclassifyOrdersJob after channel mapping rules are updated.
     */
    public function refreshRules(): void;
}
```

---

## 3. SnapshotBuilder

```php
<?php

namespace App\Services;

use App\DTOs\SnapshotData;
use App\Models\Store;
use App\Models\Workspace;
use Carbon\CarbonImmutable;

/**
 * Builds daily_snapshots rows using a pipeline pattern.
 *
 * Pipeline pipes (in order):
 *   OrdersPipe -> RefundsPipe -> AdSpendPipe -> GA4Pipe -> GSCPipe
 *   -> EmailPipe -> FxConversionPipe -> UpsertPipe
 *
 * Each pipe receives and returns a SnapshotData DTO.
 * All queries use raw SQL aggregates (SUM, COUNT) -- never Eloquent hydration
 * (non-obvious-issues.md #95: memory limit safety).
 *
 * Ad spend columns populated ONLY on workspace-level snapshot (store_id=NULL).
 * Workspace aggregate recomputes percentages from weighted components,
 * NEVER sums percentages across stores.
 *
 * Build order: after migrations, seeders, sync jobs.
 */
class SnapshotBuilder
{
    public function __construct(
        private CurrencyConverterService $currencyConverter,
    );

    /**
     * Build a daily snapshot for a single store on a single date.
     *
     * Runs the full pipeline: orders, refunds, ad spend (zeros for per-store),
     * GA4, GSC, email, FX conversion, upsert.
     *
     * All amounts are converted to the workspace's reporting currency.
     * Date boundaries use AT TIME ZONE with the workspace's reporting_timezone.
     *
     * @param  Workspace  $workspace
     * @param  Store      $store
     * @param  CarbonImmutable  $date  The snapshot date (in workspace timezone).
     * @return SnapshotData  The assembled DTO (also upserted to daily_snapshots).
     *
     * @throws \App\Exceptions\FxRateNotFoundException  Propagated if FX conversion fails
     *                                                  and tryConvert is not used.
     */
    public function buildDaily(
        Workspace $workspace,
        Store $store,
        CarbonImmutable $date,
    ): SnapshotData;

    /**
     * Build the workspace-level aggregate snapshot (store_id = NULL).
     *
     * Sums per-store numerics for revenue/orders/costs columns.
     * Ad spend columns computed INDEPENDENTLY from ad_insights table
     * (not summed from per-store rows which have 0).
     * Recomputes ratios from raw totals.
     *
     * @param  Workspace  $workspace
     * @param  CarbonImmutable  $date
     * @return SnapshotData
     */
    public function buildWorkspaceAggregate(
        Workspace $workspace,
        CarbonImmutable $date,
    ): SnapshotData;

    /**
     * Build snapshots for a date range (used after initial import).
     *
     * Dispatches individual date jobs for parallelism via Horizon.
     *
     * @param  Workspace  $workspace
     * @param  Store      $store
     * @param  CarbonImmutable  $startDate
     * @param  CarbonImmutable  $endDate
     */
    public function buildRange(
        Workspace $workspace,
        Store $store,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): void;
}
```

### SnapshotData DTO

```php
<?php

namespace App\DTOs;

/**
 * Data transfer object flowing through the SnapshotBuilder pipeline.
 *
 * Each pipe reads and writes fields on this DTO. Immutable after UpsertPipe
 * writes it to the database. All monetary fields are strings for precision.
 */
final class SnapshotData
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly ?int $storeId,
        public readonly string $date,

        // Revenue
        public string $grossSales = '0',
        public string $totalDiscounts = '0',
        public string $refundAmount = '0',
        public string $netRevenue = '0',
        public string $newCustomerRevenue = '0',

        // Costs
        public string $cogs = '0',
        public string $shippingCost = '0',
        public string $handlingCost = '0',
        public string $transactionFees = '0',
        public string $platformFees = '0',
        public string $returnShippingCost = '0',

        // Ad spend (workspace-level only, per-store = 0)
        public string $adSpendMeta = '0',
        public string $adSpendGoogle = '0',
        public string $adSpendTiktok = '0',
        public string $adSpendOther = '0',

        // Counts
        public int $ordersCount = 0,
        public int $unitsSold = 0,
        public int $newCustomers = 0,
        public int $returningCustomers = 0,

        // Analytics
        public int $sessions = 0,
        public int $itemViews = 0,
        public int $addToCarts = 0,
        public int $checkoutsStarted = 0,

        // SEO
        public int $gscClicks = 0,
        public int $gscImpressions = 0,

        // Email (attribution, not additive to net_revenue)
        public string $emailRevenue = '0',
        public int $emailSends = 0,
        public int $emailOpens = 0,
        public int $emailClicks = 0,

        // Tax (memo, not revenue)
        public string $totalTax = '0',
    );
}
```

### Pipe Interface

```php
<?php

namespace App\Services\SnapshotPipes;

use App\DTOs\SnapshotData;

/**
 * Contract for a single step in the SnapshotBuilder pipeline.
 */
interface SnapshotPipe
{
    /**
     * Process the snapshot data and pass it to the next pipe.
     *
     * @param  SnapshotData  $data  The accumulating snapshot data.
     * @param  \Closure(SnapshotData): SnapshotData  $next
     * @return SnapshotData
     */
    public function handle(SnapshotData $data, \Closure $next): SnapshotData;
}
```

---

## 4. AdNameParserService

```php
<?php

namespace App\Services;

/**
 * Extracts structured dimensions from ad/campaign/adset names using
 * the workspace's delimiter and dimension slot configuration.
 *
 * Called during ad sync after campaign/adset/ad upsert.
 * Result stored in parsed_dimensions JSONB column.
 */
class AdNameParserService
{
    /**
     * Parse an ad name into dimension key-value pairs.
     *
     * @param  string  $name            The campaign/adset/ad name to parse.
     * @param  string  $delimiter       The workspace's naming delimiter (e.g., " | ", " - ", "_").
     * @param  list<string>  $dimensionSlots  Ordered dimension names
     *                                        (e.g., ['country', 'funnel_stage', 'audience', 'creative']).
     * @return array<string, string>  Parsed dimensions. Empty slots are omitted.
     *                                 e.g., ['country' => 'DE', 'funnel_stage' => 'TOF', 'audience' => 'Broad']
     */
    public function parse(string $name, string $delimiter, array $dimensionSlots): array;

    /**
     * Check whether an ad name complies with the workspace naming convention.
     *
     * Returns true if the name has at least as many delimited segments as
     * there are dimension slots. Used for compliance badges in Creative Analysis.
     *
     * @param  string  $name
     * @param  string  $delimiter
     * @param  list<string>  $dimensionSlots
     * @return bool
     */
    public function isCompliant(string $name, string $delimiter, array $dimensionSlots): bool;
}
```

---

## 5. BackfillCogsOnOrdersJob

```php
<?php

namespace App\Jobs;

use App\Models\Workspace;
use Carbon\CarbonImmutable;

/**
 * Re-walks order_line_items, looks up cogs_entries by effective_date,
 * and updates unit_cogs. Then dispatches snapshot rebuild for affected dates.
 *
 * Triggered by: COGS CSV upload, manual COGS edit, store sync with new costs.
 * Batches updates in groups of 500 line items per transaction (#81).
 *
 * ShouldBeUnique keyed on workspace_id to prevent interleaved writes
 * from concurrent CSV uploads (#131).
 *
 * On-demand job (not scheduled). See coding-spec.md section 25.
 */
class BackfillCogsOnOrdersJob extends WorkspaceAwareJob
{
    /** @var int */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /** @var int */
    public int $timeout = 600;

    /**
     * @param  int  $workspaceId
     * @param  CarbonImmutable|null  $since  Only backfill orders on or after this date.
     *                                       Null = full workspace backfill.
     */
    public function __construct(
        int $workspaceId,
        public readonly ?CarbonImmutable $since = null,
    );

    /**
     * Re-walk order_line_items, match against cogs_entries by SKU + effective_date
     * (latest effective_from wins per #49), update unit_cogs.
     * Then dispatch BuildSnapshotsForDateRange for affected dates.
     */
    public function handle(): void;

    /**
     * Unique ID for ShouldBeUnique: prevents concurrent backfills per workspace.
     */
    public function uniqueId(): string;
}
```

---

## 6. RefreshOAuthTokensJob

```php
<?php

namespace App\Jobs;

use App\Integrations\Contracts\Support\OAuthHandler;

/**
 * Global job (NOT WorkspaceAwareJob) that refreshes expiring OAuth tokens.
 *
 * Queries all integration tables (stores, ad_accounts, analytics_properties,
 * search_properties, email_accounts) for tokens expiring within 48 hours.
 * Delegates per-platform refresh to the appropriate OAuthHandler implementation.
 *
 * Scheduled daily at 05:00 UTC (coding-spec.md section 25).
 * Also triggered on-demand when a 401 is received during sync.
 *
 * Logs each refresh attempt to activity log for audit trail (#97).
 */
class RefreshOAuthTokensJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable, SerializesModels;

    /** @var int */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /** @var int */
    public int $timeout = 300;

    /**
     * Query all integration tables for tokens expiring within 48 hours
     * and refresh each via the platform-specific OAuthHandler.
     *
     * On refresh failure: log warning, mark integration status as 'expired',
     * fire system_alert so user is prompted to re-authorize.
     *
     * @throws \App\Exceptions\OAuthTokenExpiredException  Logged per-integration, does not halt job.
     */
    public function handle(): void;

    /**
     * Unique ID: only one global refresh job at a time.
     */
    public function uniqueId(): string;
}
```

---

## 7. Money (Value Object)

```php
<?php

namespace App\ValueObjects;

use App\Enums\CurrencyCode;
use App\Exceptions\CurrencyMismatchException;
use App\Exceptions\FxRateNotFoundException;
use Carbon\CarbonImmutable;

/**
 * Immutable monetary value with currency. Uses string arithmetic for precision.
 *
 * Banker's rounding (PHP_ROUND_HALF_EVEN) to 2 decimal places on all operations.
 * Prevents mixing currencies — arithmetic between different currencies throws.
 * Convert explicitly via convertTo() before combining.
 *
 * Expected drift: ~50 currency units per 10K orders — acceptable for analytics (#38).
 */
final readonly class Money
{
    /**
     * @param  string  $amount    Decimal string (e.g., '129.99'). Stored as string for precision.
     * @param  CurrencyCode  $currency  ISO 4217 currency code.
     */
    public function __construct(
        public string $amount,
        public CurrencyCode $currency,
    );

    /**
     * Create a zero-value Money in the given currency.
     */
    public static function zero(CurrencyCode $currency): self;

    /**
     * Create from a float (converts to string internally).
     */
    public static function fromFloat(float $amount, CurrencyCode $currency): self;

    /**
     * Add another Money value. Currencies must match.
     *
     * @throws CurrencyMismatchException  When currencies differ.
     */
    public function add(self $other): self;

    /**
     * Subtract another Money value. Currencies must match.
     *
     * @throws CurrencyMismatchException  When currencies differ.
     */
    public function subtract(self $other): self;

    /**
     * Multiply by a scalar (e.g., quantity). Result keeps the same currency.
     *
     * @param  string|float|int  $multiplier
     */
    public function multiply(string|float|int $multiplier): self;

    /**
     * Calculate a percentage of this amount.
     *
     * @param  string|float  $percent  e.g., 15.5 for 15.5%
     * @return self  The percentage amount (not the remainder).
     */
    public function percentage(string|float $percent): self;

    /**
     * Convert to a different currency using CurrencyConverterService.
     *
     * @param  CurrencyCode  $targetCurrency
     * @param  CarbonImmutable  $date  Date for FX rate lookup.
     * @return self  New Money in the target currency.
     *
     * @throws FxRateNotFoundException  When no FX rate exists for the pair.
     */
    public function convertTo(CurrencyCode $targetCurrency, CarbonImmutable $date): self;

    /**
     * Whether the amount is zero.
     */
    public function isZero(): bool;

    /**
     * Whether the amount is negative.
     */
    public function isNegative(): bool;

    /**
     * Compare to another Money value. Currencies must match.
     *
     * @return int  -1, 0, or 1 (like spaceship operator).
     *
     * @throws CurrencyMismatchException
     */
    public function compareTo(self $other): int;

    /**
     * Format for display (e.g., "1,234.56"). Does NOT include currency symbol.
     */
    public function formatted(): string;

    /**
     * Get the raw float value (for database storage / computation).
     */
    public function toFloat(): float;
}
```

---

## 8. DateRange (Value Object)

```php
<?php

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Immutable date range with comparison period, granularity, and timezone awareness.
 *
 * Session persistence: selected preset is stored in the session and restored
 * on next page load. Always inclusive (start and end are both included).
 *
 * Build order: #5 — before any controller (every page uses it).
 */
final readonly class DateRange
{
    /**
     * @param  CarbonImmutable  $start            Range start (inclusive, in workspace tz).
     * @param  CarbonImmutable  $end              Range end (inclusive, in workspace tz).
     * @param  CarbonImmutable  $comparisonStart  Previous period start (equal-length).
     * @param  CarbonImmutable  $comparisonEnd    Previous period end.
     * @param  string  $preset                    The preset key (e.g., '30d', 'mtd', 'custom').
     * @param  'day'|'week'|'month'  $granularity Auto-selected: <=14d->day, <=90d->week, >90d->month.
     * @param  bool  $comparisonEnabled           Whether comparison period is active.
     */
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public CarbonImmutable $comparisonStart,
        public CarbonImmutable $comparisonEnd,
        public string $preset,
        public string $granularity,
        public bool $comparisonEnabled = true,
    );

    /**
     * Build a DateRange from an HTTP request.
     *
     * Reads ?range, ?start, ?end query params. Falls back to session, then $default.
     * Persists the selected preset to session.
     *
     * Handles presets: today, yesterday, 7d, 30d, 90d, 365d, mtd, qtd, ytd,
     * last_month, last_quarter, lifetime, bfcm, custom.
     *
     * Lifetime preset: earliest snapshot date to today. Falls back to today-29
     * if no snapshots exist (#151).
     *
     * @param  Request  $request
     * @param  string  $default  Default preset when nothing in session (default: '30d').
     * @param  string|null  $tz  Timezone override. Null = workspace reporting_timezone or UTC.
     *                           Accept as parameter to avoid crash outside HTTP context (#112).
     * @return self
     */
    public static function fromRequest(
        Request $request,
        string $default = '30d',
        ?string $tz = null,
    ): self;

    /**
     * Generate SQL date range conditions with AT TIME ZONE conversion.
     *
     * Returns a raw SQL fragment: "date >= :start AT TIME ZONE :tz AND date <= :end AT TIME ZONE :tz"
     * with bound parameters.
     *
     * @param  string  $timezone  The workspace's reporting_timezone.
     * @param  string  $column    The date column to filter (default: 'date').
     * @return array{sql: string, bindings: array<string, mixed>}
     */
    public function toSqlDateRange(string $timezone, string $column = 'date'): array;

    /**
     * Number of days in the range (inclusive).
     */
    public function days(): int;

    /**
     * Whether this range spans a single day.
     */
    public function isSingleDay(): bool;

    /**
     * Get the range as an array for Inertia shared data.
     *
     * @return array{start: string, end: string, comparisonStart: string,
     *               comparisonEnd: string, preset: string, granularity: string,
     *               comparisonEnabled: bool}
     */
    public function toArray(): array;
}
```

---

## 9. Touchpoint (Value Object)

```php
<?php

namespace App\ValueObjects;

use App\Enums\Channel;
use Carbon\CarbonImmutable;

/**
 * Immutable representation of a single touchpoint from orders.touchpoints JSONB array.
 *
 * Each touchpoint captures one marketing interaction that led to a conversion.
 * Guard with jsonb_array_length(touchpoints) > 0 before accessing (#106).
 */
final readonly class Touchpoint
{
    public function __construct(
        public ?string $source,
        public ?string $medium,
        public ?string $campaign,
        public ?string $content,
        public ?string $term,
        public ?string $referrer,
        public ?string $landingPage,
        public ?CarbonImmutable $clickedAt,
        public Channel $channel,
    );

    /**
     * Create from a single element of the orders.touchpoints JSONB array.
     *
     * @param  array<string, mixed>  $data  Raw JSONB element.
     * @return self
     */
    public static function fromArray(array $data): self;

    /**
     * Parse all touchpoints from an order's JSONB array.
     *
     * Returns empty collection when touchpoints is null or empty.
     *
     * @param  array<int, array<string, mixed>>|null  $jsonbArray
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function fromJsonbArray(?array $jsonbArray): \Illuminate\Support\Collection;

    /**
     * Serialize back to array for JSONB storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

---

## 10. FilterSet (Value Object)

```php
<?php

namespace App\ValueObjects;

use App\Exceptions\InvalidFilterException;

/**
 * Immutable set of filter rules from saved_views.filters JSONB.
 *
 * All rules are combined with AND. Operators: eq, lt, gt, lte, gte,
 * in, not_in, contains, between.
 *
 * Validates: field names against an allowlist (prevents column enumeration, #70),
 * operator-value compatibility (#144: in/not_in max 100 items, between size 2,
 * scalar operators max 255 chars).
 */
final readonly class FilterSet
{
    /**
     * @param  list<array{field: string, operator: string, value: mixed}>  $rules
     */
    public function __construct(
        public array $rules,
    );

    /**
     * Create from saved_views.filters JSONB column.
     *
     * Parses the {"field_operator": value} grammar (e.g., {"contribution_margin_lt": 0}).
     *
     * @param  array<string, mixed>|null  $jsonb  Raw JSONB from the database.
     * @return self  Empty FilterSet when input is null.
     */
    public static function fromJsonb(?array $jsonb): self;

    /**
     * Validate all rules for field-operator-value compatibility.
     *
     * Checks: field in allowlist, operator valid for field type,
     * value size constraints per operator.
     *
     * @param  list<string>  $allowedFields  Whitelist of queryable field names.
     * @return bool
     *
     * @throws InvalidFilterException  With details about which rule(s) failed.
     */
    public function validate(array $allowedFields): bool;

    /**
     * Generate parameterized SQL WHERE clause.
     *
     * Returns a fragment suitable for use with DB::whereRaw().
     * All values are parameterized (never interpolated).
     *
     * @return array{sql: string, bindings: list<mixed>}
     */
    public function toSqlWhere(): array;

    /**
     * Whether the filter set is empty (no rules).
     */
    public function isEmpty(): bool;

    /**
     * Get the number of rules.
     */
    public function count(): int;

    /**
     * Serialize back to the {"field_operator": value} grammar for JSONB storage.
     *
     * @return array<string, mixed>
     */
    public function toJsonb(): array;
}
```

---

## 11. WorkspaceAwareJob (Base Class)

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Abstract base class for all jobs that operate within a workspace context.
 *
 * Requires workspace_id in constructor. handle() wraps execution in
 * WorkspaceContext so all queries through WorkspaceScope are automatically
 * scoped. ShouldBeUnique keyed on class:workspace_id (#130).
 *
 * Build order: #2 — before any queue job.
 *
 * Default retry: 3 attempts with [60, 300, 900] second backoff.
 * Default timeout: 300 seconds. Override in subclasses as needed.
 */
abstract class WorkspaceAwareJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /** @var int */
    public int $timeout = 300;

    /**
     * @param  int  $workspaceId  The workspace this job operates within.
     */
    public function __construct(
        public readonly int $workspaceId,
    );

    /**
     * Execute the job within workspace context.
     *
     * Sets WorkspaceContext singleton, then calls process().
     * Checks syncs_paused_at before proceeding (belt + suspenders for
     * subscription state, #100). If paused, silently returns.
     *
     * @throws \RuntimeException  When workspace not found.
     */
    final public function handle(): void;

    /**
     * The actual job logic. Subclasses implement this instead of handle().
     *
     * WorkspaceContext is already set when this method is called.
     * All Eloquent queries with HasWorkspace trait are automatically scoped.
     */
    abstract protected function process(): void;

    /**
     * Unique ID: class name + workspace_id.
     * Prevents concurrent runs of the same job type for the same workspace.
     */
    public function uniqueId(): string;

    /**
     * Handle job failure. Logs to sync_logs and fires system_alert.
     *
     * @param  \Throwable  $exception
     */
    public function failed(\Throwable $exception): void;
}
```

---

## 12. WorkspaceScope (Global Scope)

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that adds WHERE workspace_id = ? to all queries on models
 * with the HasWorkspace trait.
 *
 * Reads workspace_id from the WorkspaceContext singleton.
 * Throws RuntimeException when context is not set (prevents accidental
 * cross-workspace data leaks in queue jobs, #16).
 *
 * Exception: ChannelMapping has nullable workspace_id — uses manual queries,
 * not this scope.
 *
 * Build order: #3 — before any controller.
 */
class WorkspaceScope implements Scope
{
    /**
     * Apply the workspace constraint to the query builder.
     *
     * @param  Builder  $builder
     * @param  Model  $model
     *
     * @throws \RuntimeException  When WorkspaceContext is not set.
     */
    public function apply(Builder $builder, Model $model): void;
}
```

### HasWorkspace Trait

```php
<?php

namespace App\Models\Traits;

use App\Models\Scopes\WorkspaceScope;
use App\Models\Workspace;

/**
 * Trait for models with workspace_id column (29 models).
 *
 * Boots WorkspaceScope, auto-sets workspace_id on creating event,
 * and provides the workspace() relationship.
 */
trait HasWorkspace
{
    /**
     * Boot: register WorkspaceScope and auto-fill workspace_id on create.
     */
    public static function bootHasWorkspace(): void;

    /**
     * Belongs-to relationship to the workspace.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Workspace, static>
     */
    public function workspace(): \Illuminate\Database\Eloquent\Relations\BelongsTo;
}
```

---

## 13. SetActiveWorkspace (Middleware)

```php
<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolves {workspace:slug} from the route, verifies the authenticated user
 * is a member, and sets the WorkspaceContext singleton.
 *
 * Always returns 404 (never 403) for workspaces the user isn't a member of
 * to prevent slug enumeration (#18).
 *
 * Registered as 'workspace' middleware alias.
 * Build order: #3 — before any controller.
 */
class SetActiveWorkspace
{
    public function __construct(
        private WorkspaceContext $context,
    );

    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *         When workspace not found OR user is not a member.
     */
    public function handle(Request $request, Closure $next): mixed;
}
```

---

## 14. VerifyWebhookHmac (Middleware)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Verifies webhook HMAC signatures for incoming platform webhooks.
 *
 * Detects platform from URL prefix to dispatch to the correct verification:
 *
 * Shopify:
 *   - Header: X-Shopify-Hmac-SHA256
 *   - Identifies store via X-Shopify-Shop-Domain header
 *   - HMAC-SHA256 of raw body with webhook_signing_secret as key
 *   - Signature is base64-encoded
 *
 * WooCommerce:
 *   - Header: X-WC-Webhook-Signature
 *   - Identifies store by decoding {store} Hashid from URL
 *   - HMAC-SHA256 of raw body with consumer_secret as key
 *   - Signature is base64-encoded
 *
 * Rejects with 401 if signature is missing or invalid.
 * Webhook routes bypass CSRF middleware (section 43).
 * Non-negotiable security requirement (#15).
 *
 * Also checks stores.sync_status — if 'disconnected', returns 200
 * but discards data (#55).
 */
class VerifyWebhookHmac
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException  401 on invalid/missing signature.
     */
    public function handle(Request $request, Closure $next): mixed;

    /**
     * Verify a Shopify webhook signature.
     *
     * @param  string  $body       Raw request body.
     * @param  string  $hmacHeader Value of X-Shopify-Hmac-SHA256.
     * @param  string  $secret     The store's webhook_signing_secret.
     * @return bool
     */
    protected function verifyShopifyHmac(string $body, string $hmacHeader, string $secret): bool;

    /**
     * Verify a WooCommerce webhook signature.
     *
     * @param  string  $body       Raw request body.
     * @param  string  $signature  Value of X-WC-Webhook-Signature.
     * @param  string  $secret     The store's consumer_secret.
     * @return bool
     */
    protected function verifyWooCommerceHmac(string $body, string $signature, string $secret): bool;
}
```

---

## Exceptions

```php
<?php

namespace App\Exceptions;

/** Thrown when no FX rate is found for a currency pair on or before the given date. */
class FxRateNotFoundException extends \RuntimeException {}

/** Thrown when arithmetic is attempted between Money objects with different currencies. */
class CurrencyMismatchException extends \LogicException {}

/** Thrown when a FilterSet rule references an invalid field or operator-value combination. */
class InvalidFilterException extends \InvalidArgumentException {}

/** Thrown when an integration connection fails (OAuth, key validation, etc.). */
class IntegrationConnectionException extends \RuntimeException {}

/** Thrown when a data sync fails (API errors, timeouts, etc.). */
class IntegrationSyncException extends \RuntimeException {}

/** Thrown when an integration hits a rate limit that cannot be retried. */
class IntegrationRateLimitException extends \RuntimeException {}

/** Thrown when an OAuth flow fails (state mismatch, token exchange error). */
class OAuthException extends \RuntimeException {}

/** Thrown when a refresh token itself is expired and re-authorization is needed. */
class OAuthTokenExpiredException extends OAuthException {}

/** Thrown when an FX rate provider API is unavailable or returns invalid data. */
class FxRateProviderException extends \RuntimeException {}

/** Thrown when a data export fails. */
class ExportException extends \RuntimeException {}
```

---

## Enums (Referenced)

```php
<?php

namespace App\Enums;

/** ISO 4217 codes supported by ECB + common ecommerce currencies. */
enum CurrencyCode: string
{
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    // ... 30 total — validated in workspace settings (#145)
}

/** 17 valid marketing channels. */
enum Channel: string
{
    case PaidSearch = 'paid_search';
    case PaidSocial = 'paid_social';
    case PaidVideo = 'paid_video';
    case PaidShopping = 'paid_shopping';
    case CrossNetwork = 'cross_network';
    case Display = 'display';
    case Email = 'email';
    case Sms = 'sms';
    case Affiliate = 'affiliate';
    case MobilePush = 'mobile_push';
    case OrganicSearch = 'organic_search';
    case OrganicSocial = 'organic_social';
    case OrganicVideo = 'organic_video';
    case OrganicShopping = 'organic_shopping';
    case Referral = 'referral';
    case Direct = 'direct';
    case Unassigned = 'unassigned';
}
```

---

## WorkspaceContext (Singleton)

```php
<?php

namespace App\Services;

use App\Models\Workspace;

/**
 * Singleton holding the active workspace for the current request or job.
 *
 * Set by SetActiveWorkspace middleware (HTTP) or WorkspaceAwareJob (queue).
 * Read by WorkspaceScope and the workspace() helper function.
 *
 * Registered as singleton in AppServiceProvider.
 */
class WorkspaceContext
{
    /**
     * Set the active workspace.
     */
    public function set(Workspace $workspace): void;

    /**
     * Get the active workspace ID, or null if not set.
     */
    public function id(): ?int;

    /**
     * Get the active workspace, or null if not set.
     */
    public function get(): ?Workspace;
}
```

### Helper Functions

```php
/** Get the active workspace, or null outside workspace context. */
function workspace(): ?Workspace;

/** Get the authenticated user's role in the active workspace (null-safe). */
function workspace_role(User $user): ?string;
```
