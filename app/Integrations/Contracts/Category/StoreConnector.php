<?php

namespace App\Integrations\Contracts\Category;

use App\Integrations\Contracts\Base\Connectable;
use App\Integrations\Contracts\Base\Syncable;
use App\Models\Store;

/**
 * Contract for ecommerce store integrations (Shopify, WooCommerce, future BigCommerce, etc.).
 *
 * Covers the full store data lifecycle: bulk imports, real-time webhooks,
 * and periodic reconciliation to catch missed events.
 *
 * Implementations handle platform-specific pagination (Shopify bulk JSONL,
 * WooCommerce page-based REST) internally.
 */
interface StoreConnector extends Connectable, Syncable
{
    /**
     * Import orders (and nested line items, refunds, customers).
     *
     * Upserts Order, OrderLineItem, Refund, and Customer records.
     * Uses the platform's most efficient method (bulk ops for Shopify,
     * paginated REST for WooCommerce).
     *
     * @param  \DateTimeInterface|null  $since  Only import orders modified after this timestamp.
     *                                          Null = full import (initial sync).
     */
    public function importOrders(Store $store, ?\DateTimeInterface $since = null): void;

    /**
     * Import products and their variants.
     *
     * Upserts Product, ProductVariant, and CogsEntry records.
     * Includes COGS data where the platform provides it
     * (Shopify unitCost, WooCommerce cost_of_goods_sold).
     */
    public function importProducts(Store $store): void;

    /**
     * Import customers with deduplication.
     *
     * Upserts Customer records. Handles platform-specific quirks:
     * - WooCommerce: dedup guests by billing.email
     * - Shopify: uses customer ID, enriches with marketing consent + tags
     */
    public function importCustomers(Store $store): void;

    /**
     * Process an incoming order webhook payload.
     *
     * Handles order.created, order.updated, and order.cancelled events.
     * Upserts Order + nested LineItems, Refunds, and Customer in a single
     * database transaction.
     *
     * @param  array<string, mixed>  $payload  Raw webhook payload from the platform
     */
    public function processOrderWebhook(Store $store, array $payload): void;

    /**
     * Verify a webhook's HMAC signature.
     *
     * Each platform has its own signing scheme:
     * - Shopify: X-Shopify-Hmac-SHA256 (HMAC-SHA256, base64)
     * - WooCommerce: X-WC-Webhook-Signature (HMAC-SHA256, base64)
     *
     * @param  string  $body             Raw request body
     * @param  string  $signatureHeader  Value of the platform's signature header
     * @param  string  $secret           The webhook signing secret for this store
     */
    public function verifyHmac(string $body, string $signatureHeader, string $secret): bool;

    /**
     * Reconcile orders that may have been missed or updated.
     *
     * Re-fetches orders modified since the last sync timestamp and upserts
     * any that differ from our local copy. Catches missed webhooks,
     * admin edits, and delayed fulfillment status changes.
     *
     * Called by ReconcileStoreOrdersJob (daily 01:30).
     *
     * @return int  Number of orders that were re-synced (updated or newly created).
     */
    public function reconcile(Store $store): int;
}
