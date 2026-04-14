<?php

declare(strict_types=1);

namespace App\Contracts;

use Carbon\Carbon;

/**
 * Contract for all store platform connectors (WooCommerce, Shopify, etc.).
 *
 * Implemented by:
 *   - App\Services\Integrations\WooCommerce\WooCommerceConnector (Phase 0)
 *   - App\Services\Integrations\Shopify\ShopifyConnector (Phase 3)
 *
 * Each implementation encapsulates the full fetch + persist cycle for one platform.
 * Sync jobs are thin wrappers that handle lifecycle (logging, failure chain, scheduling).
 *
 * Related: app/Services/Integrations/WooCommerce/WooCommerceConnector.php
 * See: PLANNING.md "StoreConnector Interface"
 */
interface StoreConnector
{
    /**
     * Verify the platform credentials are valid and the store is reachable.
     */
    public function testConnection(): bool;

    /**
     * Fetch orders modified since the given timestamp and upsert them.
     *
     * @return int Number of orders processed.
     */
    public function syncOrders(Carbon $since): int;

    /**
     * Fetch products (modified since store.last_synced_at, or all on first run) and upsert them.
     *
     * @return int Number of products upserted.
     */
    public function syncProducts(): int;

    /**
     * Fetch refunds created/modified since the given timestamp, upsert into refunds table,
     * and update orders.refund_amount + orders.last_refunded_at.
     *
     * @return int Number of refund records upserted.
     */
    public function syncRefunds(Carbon $since): int;

    /**
     * Register platform webhooks for this store and write entries to store_webhooks.
     *
     * @return array<string, int>  Map of topic → platform webhook ID.
     */
    public function registerWebhooks(): array;

    /**
     * Remove all active platform webhooks for this store and soft-delete store_webhooks rows.
     */
    public function removeWebhooks(): void;

    /**
     * Return store metadata from the platform (name, currency, timezone).
     *
     * @return array{name: string, currency: string, timezone: string}
     */
    public function getStoreInfo(): array;
}
