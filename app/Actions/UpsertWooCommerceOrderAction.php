<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\FxRateNotFoundException;
use App\Models\Order;
use App\Models\OrderCoupon;
use App\Models\OrderItem;
use App\Models\Store;
use App\Services\Fx\FxRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Maps a raw WooCommerce order payload to our schema and upserts the order + items + coupons.
 *
 * Precondition: WorkspaceContext MUST be set by the calling job before invoking
 * this action. The action uses workspace-scoped Eloquent queries internally.
 *
 * FX conversion:
 *   - On success: total_in_reporting_currency is populated.
 *   - On FxRateNotFoundException: total_in_reporting_currency is left NULL.
 *     RetryMissingConversionJob handles NULLs nightly — never treat NULL as 0.
 *
 * Order items:
 *   WooCommerce sends the full item list on every webhook/sync. We delete the
 *   existing items for the order and re-insert inside a single transaction.
 *   This is idempotent and handles item additions/removals correctly. The
 *   expression unique index on order_items (order_id, product_external_id,
 *   COALESCE(variant_name, '')) cannot be used with Eloquent::upsert() when
 *   variant_name is nullable, so delete+insert within a transaction is the
 *   correct pattern for this table.
 *
 * Coupons:
 *   order_coupons are replaced atomically (delete + insert) on every upsert,
 *   same as order_items. discount_type is not available in coupon_lines and
 *   is stored as NULL.
 */
class UpsertWooCommerceOrderAction
{
    public function __construct(
        private readonly FxRateService $fx,
    ) {}

    /**
     * @param array<string, mixed> $wcOrder  Raw WooCommerce REST API order object.
     */
    public function handle(Store $store, string $reportingCurrency, array $wcOrder): void
    {
        $externalId = (string) $wcOrder['id'];

        $occurredAt = Carbon::parse($wcOrder['date_created_gmt'] ?? 'now')->utc();

        $orderCurrency = (string) ($wcOrder['currency'] ?? $store->currency);
        $total         = (float) ($wcOrder['total'] ?? 0);

        $totalInReporting = $this->convertTotal($total, $orderCurrency, $reportingCurrency, $occurredAt, $store->id, $externalId);

        // Index meta_data once; shared by all UTM + source_type lookups below.
        $metaMap = $this->buildMetaMap($wcOrder);

        $orderRow = [
            'workspace_id'                => $store->workspace_id,
            'store_id'                    => $store->id,
            'external_id'                 => $externalId,
            'external_number'             => $this->nullableString($wcOrder['number'] ?? null),
            'status'                      => $this->mapStatus((string) ($wcOrder['status'] ?? '')),
            'currency'                    => $orderCurrency,
            'total'                       => $total,
            'subtotal'                    => (float) ($wcOrder['subtotal'] ?? 0),
            'tax'                         => (float) ($wcOrder['total_tax'] ?? 0),
            'shipping'                    => (float) ($wcOrder['shipping_total'] ?? 0),
            'discount'                    => (float) ($wcOrder['discount_total'] ?? 0),
            'total_in_reporting_currency' => $totalInReporting,
            'customer_email_hash'         => $this->hashEmail($wcOrder['billing']['email'] ?? ''),
            'customer_country'            => $this->nullableString($wcOrder['billing']['country'] ?? null),
            'customer_id'                 => $this->nullableString($wcOrder['customer_id'] ?? null),
            'payment_method'              => $this->nullableString($wcOrder['payment_method'] ?? null),
            'payment_method_title'        => $this->nullableString($wcOrder['payment_method_title'] ?? null),
            'shipping_country'            => $this->nullableString($wcOrder['shipping']['country'] ?? null),
            // UTM + attribution fields. meta_data is indexed once into a flat map and
            // shared across all lookups to avoid O(n×k) iteration.
            // Why: WC 8.5+ ships built-in Order Attribution with _wc_order_attribution_*
            // keys. Earlier plugins used bare _utm_* keys. We prefer the native format.
            // source_type (organic_search/direct/utm/referral) is a WC-native-only field
            // with no legacy equivalent.
            'utm_source'                  => $this->utmFromMetaMap($metaMap, 'utm_source'),
            'utm_medium'                  => $this->utmFromMetaMap($metaMap, 'utm_medium'),
            'utm_campaign'                => $this->utmFromMetaMap($metaMap, 'utm_campaign'),
            'utm_content'                 => $this->utmFromMetaMap($metaMap, 'utm_content'),
            'utm_term'                    => $this->utmFromMetaMap($metaMap, 'utm_term'),
            'source_type'                 => $metaMap['_wc_order_attribution_source_type'] ?? null,
            'raw_meta'                    => $this->buildRawMeta($wcOrder),
            'raw_meta_api_version'        => 'wc/v3',
            'occurred_at'                 => $occurredAt->toDateTimeString(),
            'synced_at'                   => now()->toDateTimeString(),
            'created_at'                  => now()->toDateTimeString(),
            'updated_at'                  => now()->toDateTimeString(),
        ];

        DB::transaction(function () use ($store, $externalId, $orderRow, $wcOrder): void {
            // Upsert the order. created_at is excluded from update to preserve
            // the original ingestion timestamp on re-syncs.
            Order::upsert(
                [$orderRow],
                uniqueBy: ['store_id', 'external_id'],
                update: [
                    'external_number', 'status', 'currency', 'total', 'subtotal',
                    'tax', 'shipping', 'discount', 'total_in_reporting_currency',
                    'customer_email_hash', 'customer_country', 'customer_id',
                    'payment_method', 'payment_method_title', 'shipping_country',
                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'source_type',
                    'raw_meta', 'raw_meta_api_version',
                    'occurred_at', 'synced_at', 'updated_at',
                ],
            );

            // Retrieve the order PK — needed to associate items.
            $orderId = Order::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->value('id');

            if ($orderId === null) {
                throw new \RuntimeException(
                    "Order not found after upsert: store={$store->id}, external_id={$externalId}"
                );
            }

            // Replace all items atomically. WooCommerce sends the full line item
            // list on every event, so we can safely replace rather than diff.
            OrderItem::where('order_id', $orderId)->delete();

            $itemRows = $this->buildItemRows($orderId, $store, $wcOrder['line_items'] ?? []);

            if (! empty($itemRows)) {
                OrderItem::insert($itemRows);
            }

            // Replace coupons atomically. WooCommerce sends the full coupon_lines
            // on every event. discount_type is not in coupon_lines (only on the
            // coupon object itself); stored as NULL.
            OrderCoupon::where('order_id', $orderId)->delete();

            $couponRows = $this->buildCouponRows($orderId, $wcOrder['coupon_lines'] ?? []);

            if (! empty($couponRows)) {
                DB::table('order_coupons')->insert($couponRows);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function convertTotal(
        float  $total,
        string $orderCurrency,
        string $reportingCurrency,
        Carbon $occurredAt,
        int    $storeId,
        string $externalId,
    ): ?float {
        try {
            return $this->fx->convert($total, $orderCurrency, $reportingCurrency, $occurredAt);
        } catch (FxRateNotFoundException $e) {
            Log::warning('UpsertWooCommerceOrderAction: FX rate not found; total_in_reporting_currency set to NULL', [
                'store_id'            => $storeId,
                'external_id'         => $externalId,
                'order_currency'      => $orderCurrency,
                'reporting_currency'  => $reportingCurrency,
                'date'                => $occurredAt->toDateString(),
            ]);

            return null;
        }
    }

    private function mapStatus(string $wcStatus): string
    {
        return match ($wcStatus) {
            'completed'  => 'completed',
            'processing' => 'processing',
            'refunded'   => 'refunded',
            'cancelled'  => 'cancelled',
            default      => 'other',
        };
    }

    private function hashEmail(string $email): ?string
    {
        $normalised = trim(strtolower($email));

        return $normalised !== '' ? hash('sha256', $normalised) : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * Build a key→value map from the order's top-level meta_data array.
     *
     * Iterates once; all UTM and source_type lookups share the result.
     *
     * @param  array<string, mixed> $wcOrder
     * @return array<string, string>
     */
    private function buildMetaMap(array $wcOrder): array
    {
        $map = [];

        foreach ($wcOrder['meta_data'] ?? [] as $meta) {
            $key = (string) ($meta['key'] ?? '');
            $val = $meta['value'] ?? null;

            // Why: WooCommerce meta values are occasionally arrays (e.g. serialised plugin
            // data, nested attribution objects). Casting an array to string throws a fatal
            // "Array to string conversion" error. Skip any non-scalar value silently —
            // only string/int/float/bool meta values are useful for UTM attribution.
            if ($key !== '' && is_scalar($val) && $val !== '' && $val !== false) {
                $map[$key] = (string) $val;
            }
        }

        return $map;
    }

    /**
     * Extract a UTM parameter from a pre-built meta map.
     *
     * Checks WooCommerce 8.5+ native attribution keys first, then falls back to
     * legacy third-party plugin keys (_utm_*).
     *
     * Why WC native first: stores on WC 8.5+ (Jan 2024+) emit
     * _wc_order_attribution_utm_* keys by default. The legacy _utm_* keys
     * come from older plugins and may conflict or be absent on modern stores.
     *
     * @param array<string, string> $metaMap Pre-built from buildMetaMap()
     * @param string                $param   e.g. 'utm_source', 'utm_medium'
     */
    private function utmFromMetaMap(array $metaMap, string $param): ?string
    {
        // WC 8.5+ native format: _wc_order_attribution_utm_source etc.
        $nativeKey = '_wc_order_attribution_' . $param;

        if (isset($metaMap[$nativeKey])) {
            return $metaMap[$nativeKey];
        }

        // Legacy plugin format: _utm_source etc.
        return $metaMap['_' . $param] ?? null;
    }

    /**
     * Build the rows to insert into order_items for a single order.
     *
     * WooCommerce can occasionally send two line_item entries for the same
     * product+variant within one order (e.g. split lines on partial refunds).
     * The unique index on (order_id, product_external_id, COALESCE(variant_name,''))
     * would reject the second INSERT. We merge duplicates by summing quantity
     * and line_total, keeping other fields from the last occurrence.
     *
     * @param  array<int, array<string, mixed>> $lineItems
     * @return array<int, array<string, mixed>>
     */
    private function buildItemRows(int $orderId, Store $store, array $lineItems): array
    {
        // Key: "product_external_id|variant_name" — mirrors the unique index key.
        $deduped = [];
        $now     = now()->toDateTimeString();

        foreach ($lineItems as $item) {
            $productExternalId = (string) ($item['product_id'] ?? 0);
            $variantName       = $this->extractVariantName($item);
            $dedupeKey         = $productExternalId . '|' . ($variantName ?? '');

            if (isset($deduped[$dedupeKey])) {
                // Merge duplicate line: accumulate quantity and line_total.
                $deduped[$dedupeKey]['quantity']   += (int) ($item['quantity'] ?? 0);
                $deduped[$dedupeKey]['line_total'] += (float) ($item['total'] ?? 0);
            } else {
                $deduped[$dedupeKey] = [
                    'order_id'            => $orderId,
                    // workspace_id and store_id intentionally omitted — order_items has no
                    // such columns. Tenant isolation flows through the parent order.
                    'product_external_id' => $productExternalId,
                    'product_name'        => (string) ($item['name'] ?? ''),
                    'variant_name'        => $variantName,
                    'sku'                 => $this->nullableString($item['sku'] ?? null),
                    'quantity'            => (int) ($item['quantity'] ?? 0),
                    'unit_price'          => (float) ($item['price'] ?? 0),
                    'line_total'          => (float) ($item['total'] ?? 0),
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
            }
        }

        return array_values($deduped);
    }

    /**
     * Build the raw_meta JSONB payload from supplementary order fields.
     *
     * Captures fee_lines and customer_note — fields not promoted to dedicated
     * columns but needed for future diagnostics (e.g. custom fee detection).
     *
     * @param array<string, mixed> $wcOrder
     */
    private function buildRawMeta(array $wcOrder): ?string
    {
        $meta = [];

        if (! empty($wcOrder['fee_lines'])) {
            $meta['fee_lines'] = $wcOrder['fee_lines'];
        }

        if (isset($wcOrder['customer_note']) && $wcOrder['customer_note'] !== '') {
            $meta['customer_note'] = $wcOrder['customer_note'];
        }

        return ! empty($meta) ? json_encode($meta) : null;
    }

    /**
     * Build the rows to insert into order_coupons for a single order.
     *
     * @param  array<int, array<string, mixed>> $couponLines
     * @return array<int, array<string, mixed>>
     */
    private function buildCouponRows(int $orderId, array $couponLines): array
    {
        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($couponLines as $coupon) {
            $code = trim((string) ($coupon['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $rows[] = [
                'order_id'        => $orderId,
                'coupon_code'     => $code,
                'discount_amount' => (float) ($coupon['discount'] ?? 0),
                // discount_type is not available in coupon_lines; resolved from /coupons/{id} only.
                'discount_type'   => null,
                'created_at'      => $now,
            ];
        }

        return $rows;
    }

    /**
     * Derive the variant name from a WooCommerce line item's meta_data.
     *
     * Returns null for non-variation items (variation_id === 0).
     * For variations: concatenates the display_value of all non-internal
     * meta entries (display_key not starting with '_').
     *
     * @param array<string, mixed> $item
     */
    private function extractVariantName(array $item): ?string
    {
        if (($item['variation_id'] ?? 0) === 0) {
            return null;
        }

        $parts = [];

        foreach ($item['meta_data'] ?? [] as $meta) {
            $displayKey   = (string) ($meta['display_key'] ?? '');
            $displayValue = (string) ($meta['display_value'] ?? '');

            if ($displayKey === '' || str_starts_with($displayKey, '_') || $displayValue === '') {
                continue;
            }

            $parts[] = $displayValue;
        }

        return ! empty($parts) ? implode(', ', $parts) : null;
    }
}
