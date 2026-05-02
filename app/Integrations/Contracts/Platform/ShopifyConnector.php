<?php

namespace App\Integrations\Contracts\Platform;

use App\Integrations\Contracts\Category\StoreConnector;
use App\Models\Store;

/**
 * Shopify-specific extensions to StoreConnector.
 *
 * Adds capabilities unique to Shopify's GraphQL Admin API:
 * - Bulk operations (JSONL-based async imports)
 * - Inventory-level tracking (per-location)
 * - Balance transaction fees (Shopify Payments only)
 * - HMAC webhook verification
 */
interface ShopifyConnector extends StoreConnector
{
    /**
     * Run a bulk operation query for the given resource type.
     *
     * Uses Shopify's bulkOperationRunQuery mutation, which returns results
     * as a downloadable JSONL file. Up to 5 concurrent bulk ops per shop.
     *
     * @param  'orders'|'products'|'customers'|'inventory'  $resource
     * @param  \DateTimeInterface|null  $since  Filter for incremental bulk ops (orders only).
     */
    public function bulkImport(Store $store, string $resource, ?\DateTimeInterface $since = null): void;

    /**
     * Import inventory levels per location.
     *
     * Upserts ProductVariant.inventory_quantity. Uses bulk operation
     * for multi-location stores or inline query for single-location.
     */
    public function importInventory(Store $store): void;

    /**
     * Import Shopify Payments balance transactions.
     *
     * Creates transaction fee records linked to orders. Cannot use bulk ops
     * (shopifyPaymentsAccount is not a connection) -- paginates manually.
     * Only available for stores using Shopify Payments.
     *
     * @param  \DateTimeInterface|null  $since  Only fetch transactions after this date.
     */
    public function importBalanceTransactions(Store $store, ?\DateTimeInterface $since = null): void;

    /**
     * Verify a Shopify webhook's HMAC signature.
     *
     * @param  string  $body        Raw request body
     * @param  string  $hmacHeader  Value of X-Shopify-Hmac-SHA256 header
     * @param  string  $secret      The app's webhook signing secret
     */
    public function verifyHmac(string $body, string $hmacHeader, string $secret): bool;

    /**
     * Handle a product webhook (product create/update).
     *
     * Upserts Product + ProductVariant records.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processProductWebhook(Store $store, array $payload): void;

    /**
     * Handle an inventory level webhook.
     *
     * Updates ProductVariant.inventory_quantity for the affected location.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processInventoryWebhook(Store $store, array $payload): void;
}
