<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\WooCommerceAuthException;
use App\Exceptions\WooCommerceConnectionException;
use App\Exceptions\WooCommerceRateLimitException;
use App\Services\Integrations\WooCommerce\WooCommerceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HTTP-layer tests for WooCommerceClient.
 *
 * These are the closest thing to integration tests in this project — they
 * exercise the actual URL building, header parsing, pagination logic, error
 * mapping, and SSRF guard by faking the HTTP layer with Http::fake().
 *
 * No database required (uses RefreshDatabase only for environment isolation).
 */
class WooCommerceClientTest extends TestCase
{
    private const DOMAIN = 'mystore.example.com';
    private const KEY    = 'ck_test_key';
    private const SECRET = 'cs_test_secret';

    private function makeClient(string $domain = self::DOMAIN): WooCommerceClient
    {
        return new WooCommerceClient($domain, self::KEY, self::SECRET);
    }

    private function storeBaseUrl(string $domain = self::DOMAIN): string
    {
        return "https://{$domain}/wp-json/wc/v3";
    }

    // -------------------------------------------------------------------------
    // validateAndGetMetadata
    // -------------------------------------------------------------------------

    public function test_returns_store_metadata_on_success(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/system_status' => Http::response([
                'environment' => ['site_title' => 'My Test Store', 'timezone' => 'Europe/Berlin'],
                'settings'    => ['currency' => 'EUR'],
            ], 200),
        ]);

        $meta = $this->makeClient()->validateAndGetMetadata();

        $this->assertSame('My Test Store', $meta['name']);
        $this->assertSame('EUR', $meta['currency']);
        $this->assertSame('Europe/Berlin', $meta['timezone']);
    }

    public function test_throws_auth_exception_on_401(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/system_status' => Http::response(null, 401),
        ]);

        $this->expectException(WooCommerceAuthException::class);

        $this->makeClient()->validateAndGetMetadata();
    }

    public function test_throws_connection_exception_on_500(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/system_status' => Http::response(null, 500),
        ]);

        $this->expectException(WooCommerceConnectionException::class);

        $this->makeClient()->validateAndGetMetadata();
    }

    // -------------------------------------------------------------------------
    // fetchOrderCount
    // -------------------------------------------------------------------------

    public function test_returns_total_from_x_wp_total_header(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/orders*' => Http::response(
                [['id' => 1]],
                200,
                ['X-WP-Total' => '142'],
            ),
        ]);

        $count = $this->makeClient()->fetchOrderCount('2026-01-01');

        $this->assertSame(142, $count);
    }

    public function test_fetch_order_count_throws_rate_limit_exception_on_429(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/orders*' => Http::response(null, 429, ['Retry-After' => '30']),
        ]);

        $this->expectException(WooCommerceRateLimitException::class);

        $this->makeClient()->fetchOrderCount('2026-01-01');
    }

    // -------------------------------------------------------------------------
    // fetchModifiedOrders — pagination
    // -------------------------------------------------------------------------

    public function test_auto_paginates_multiple_pages(): void
    {
        $page1 = array_fill(0, 3, ['id' => 1, 'status' => 'completed']);
        $page2 = array_fill(0, 2, ['id' => 2, 'status' => 'completed']);

        Http::fake([
            $this->storeBaseUrl() . '/orders*' => Http::sequence()
                ->push($page1, 200, ['X-WP-TotalPages' => '2', 'X-WP-Total' => '5'])
                ->push($page2, 200, ['X-WP-TotalPages' => '2', 'X-WP-Total' => '5']),
        ]);

        $orders = $this->makeClient()->fetchModifiedOrders('2026-01-01T00:00:00');

        $this->assertCount(5, $orders);
    }

    public function test_returns_empty_array_for_no_orders(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/orders*' => Http::response([], 200, ['X-WP-TotalPages' => '1']),
        ]);

        $orders = $this->makeClient()->fetchModifiedOrders('2026-01-01T00:00:00');

        $this->assertSame([], $orders);
    }

    public function test_fetch_modified_orders_throws_on_error(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/orders*' => Http::response(null, 503),
        ]);

        $this->expectException(WooCommerceConnectionException::class);

        $this->makeClient()->fetchModifiedOrders('2026-01-01T00:00:00');
    }

    // -------------------------------------------------------------------------
    // fetchProductsPage
    // -------------------------------------------------------------------------

    public function test_returns_products_and_total_pages(): void
    {
        $products = [
            ['id' => 10, 'name' => 'Shoe A'],
            ['id' => 11, 'name' => 'Shoe B'],
        ];

        Http::fake([
            $this->storeBaseUrl() . '/products*' => Http::response(
                $products,
                200,
                ['X-WP-TotalPages' => '3'],
            ),
        ]);

        $result = $this->makeClient()->fetchProductsPage(null, 1);

        $this->assertCount(2, $result['products']);
        $this->assertSame(3, $result['total_pages']);
    }

    // -------------------------------------------------------------------------
    // registerWebhooks
    // -------------------------------------------------------------------------

    public function test_registers_4_webhook_topics(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/webhooks' => Http::sequence()
                ->push(['id' => 10], 201)
                ->push(['id' => 11], 201)
                ->push(['id' => 12], 201)
                ->push(['id' => 13], 201),
        ]);

        $result = $this->makeClient()->registerWebhooks(99, 'secret123');

        $this->assertArrayHasKey('order.created', $result);
        $this->assertArrayHasKey('order.updated', $result);
        $this->assertArrayHasKey('order.deleted', $result);
        $this->assertArrayHasKey('product.updated', $result);
        $this->assertSame(10, $result['order.created']);
        $this->assertSame(13, $result['product.updated']);

        Http::assertSentCount(4);
    }

    public function test_register_webhooks_throws_on_api_failure(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/webhooks' => Http::response(null, 422),
        ]);

        $this->expectException(WooCommerceConnectionException::class);

        $this->makeClient()->registerWebhooks(99, 'secret123');
    }

    // -------------------------------------------------------------------------
    // deleteWebhooks
    // -------------------------------------------------------------------------

    public function test_sends_delete_for_each_webhook_id(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/webhooks/*' => Http::response(['id' => 1, 'status' => 'deleted'], 200),
        ]);

        $this->makeClient()->deleteWebhooks([
            'order.created' => 10,
            'order.updated' => 11,
        ]);

        Http::assertSentCount(2);
    }

    public function test_ignores_404_on_delete(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/webhooks/*' => Http::response(null, 404),
        ]);

        // Should not throw
        $this->makeClient()->deleteWebhooks(['order.created' => 10]);

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // SSRF guard — tests run in 'testing' environment (not 'local')
    // -------------------------------------------------------------------------

    public function test_rejects_ip_address_domain(): void
    {
        $this->expectException(WooCommerceConnectionException::class);

        $client = new WooCommerceClient('192.168.1.1', self::KEY, self::SECRET);
        // Trigger baseUrl() by calling any public method
        $client->fetchOrderCount('2026-01-01');
    }

    public function test_rejects_localhost_domain(): void
    {
        $this->expectException(WooCommerceConnectionException::class);

        $client = new WooCommerceClient('localhost', self::KEY, self::SECRET);
        $client->fetchOrderCount('2026-01-01');
    }

    public function test_rejects_internal_hostname(): void
    {
        $this->expectException(WooCommerceConnectionException::class);

        $client = new WooCommerceClient('store.internal', self::KEY, self::SECRET);
        $client->fetchOrderCount('2026-01-01');
    }

    public function test_rejects_domain_with_port_number(): void
    {
        $this->expectException(WooCommerceConnectionException::class);

        $client = new WooCommerceClient('mystore.example.com:8080', self::KEY, self::SECRET);
        $client->fetchOrderCount('2026-01-01');
    }
}
