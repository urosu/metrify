<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Upserts a single WooCommerce product from a webhook payload.
 *
 * Triggered by: ProcessWebhookJob (product.updated event)
 * Reads from:   WooCommerce webhook payload
 * Writes to:    products, product_categories, product_category_product
 *
 * Captures: price, status, stock_status, stock_quantity, product_type, categories.
 * Categories are synced atomically with the product row inside a transaction.
 * If $wcProduct['categories'] is absent or empty, the pivot is left untouched
 * to avoid wiping categories on partial payloads.
 *
 * Related: app/Jobs/ProcessWebhookJob.php (dispatches this action)
 * Related: app/Services/Integrations/WooCommerce/WooCommerceConnector.php (batch sync counterpart)
 * See: PLANNING.md "Product webhooks"
 */
class UpsertWooCommerceProductAction
{
    public function handle(Store $store, array $wcProduct): void
    {
        $externalId = (string) ($wcProduct['id'] ?? '');

        if ($externalId === '') {
            Log::warning('UpsertWooCommerceProductAction: missing product id in payload', [
                'store_id' => $store->id,
            ]);
            return;
        }

        $now = now()->toDateTimeString();

        $imageUrl = ! empty($wcProduct['images'][0]['src'])
            ? (string) $wcProduct['images'][0]['src']
            : null;

        $price = isset($wcProduct['price']) && $wcProduct['price'] !== ''
            ? (float) $wcProduct['price']
            : null;

        $stockQuantity = isset($wcProduct['stock_quantity']) && $wcProduct['stock_quantity'] !== null
            ? (int) $wcProduct['stock_quantity']
            : null;

        $platformUpdatedAt = ! empty($wcProduct['date_modified_gmt'])
            ? Carbon::parse($wcProduct['date_modified_gmt'])->utc()->toDateTimeString()
            : null;

        $productRow = [
            'workspace_id'        => $store->workspace_id,
            'store_id'            => $store->id,
            'external_id'         => $externalId,
            'name'                => mb_substr((string) ($wcProduct['name'] ?? ''), 0, 500),
            'sku'                 => $this->nullableString($wcProduct['sku'] ?? null),
            'price'               => $price,
            'status'              => $this->nullableString($wcProduct['status'] ?? null),
            'image_url'           => $imageUrl,
            'product_url'         => $this->nullableString($wcProduct['permalink'] ?? null),
            'stock_status'        => $this->nullableString($wcProduct['stock_status'] ?? null),
            'stock_quantity'      => $stockQuantity,
            'product_type'        => $this->nullableString($wcProduct['type'] ?? null),
            'platform_updated_at' => $platformUpdatedAt,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        DB::transaction(function () use ($store, $externalId, $productRow, $wcProduct, $now): void {
            // Step 1: upsert the product row.
            DB::table('products')->upsert(
                [$productRow],
                uniqueBy: ['store_id', 'external_id'],
                update: [
                    'name', 'sku', 'price', 'status', 'image_url', 'product_url',
                    'stock_status', 'stock_quantity', 'product_type',
                    'platform_updated_at', 'updated_at',
                ],
            );

            // Step 2: sync categories if provided. Skip if absent to avoid wiping
            // categories when WooCommerce sends partial payloads.
            $wcCategories = $wcProduct['categories'] ?? [];

            if (empty($wcCategories)) {
                return;
            }

            // Step 2a: upsert category rows (name + slug only; parent not in product payload).
            $categoryRows = array_map(fn (array $cat) => [
                'workspace_id'       => $store->workspace_id,
                'store_id'           => $store->id,
                'external_id'        => (string) $cat['id'],
                'name'               => mb_substr((string) ($cat['name'] ?? ''), 0, 255),
                'slug'               => mb_substr((string) ($cat['slug'] ?? ''), 0, 255),
                'parent_external_id' => null,
                'created_at'         => $now,
            ], $wcCategories);

            DB::table('product_categories')->upsert(
                $categoryRows,
                uniqueBy: ['store_id', 'external_id'],
                update: ['name', 'slug'],
            );

            // Step 2b: resolve the local product ID.
            $productId = DB::table('products')
                ->where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->value('id');

            if ($productId === null) {
                return; // Should not happen after the upsert above.
            }

            // Step 2c: resolve local category IDs.
            $externalCategoryIds = array_column($wcCategories, 'id');
            $localCategoryIds    = DB::table('product_categories')
                ->where('store_id', $store->id)
                ->whereIn('external_id', array_map('strval', $externalCategoryIds))
                ->pluck('id')
                ->all();

            // Step 2d: replace pivot rows atomically.
            DB::table('product_category_product')
                ->where('product_id', $productId)
                ->delete();

            if (! empty($localCategoryIds)) {
                $pivotRows = array_map(
                    fn (int $catId) => ['product_id' => $productId, 'category_id' => $catId],
                    $localCategoryIds,
                );

                DB::table('product_category_product')->insert($pivotRows);
            }
        });
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
