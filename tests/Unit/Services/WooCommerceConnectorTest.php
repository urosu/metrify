<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Http\Middleware\VerifyWebhookSignature;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\Integrations\WooCommerce\WooCommerceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Tests for WooCommerceConnector and VerifyWebhookSignature middleware.
 *
 * Covers:
 * - HMAC-SHA256 webhook signature validation (valid, tampered body, tampered sig, missing header)
 * - WooCommerceClient multi-page product pagination (simulates syncProducts page loop)
 * - Error path: store not found → 404
 * - Missing webhook secret → 500
 *
 * Webhook secret is stored on the stores.webhook_secret_encrypted column.
 */
class WooCommerceConnectorTest extends TestCase
{
    use RefreshDatabase;

    private const DOMAIN = 'mystore.example.com';
    private const KEY    = 'ck_test_key';
    private const SECRET = 'cs_test_secret';
    private const WEBHOOK_SECRET = 'wh_secret_abc123';

    private function storeBaseUrl(string $domain = self::DOMAIN): string
    {
        return "https://{$domain}/wp-json/wc/v3";
    }

    // -------------------------------------------------------------------------
    // VerifyWebhookSignature — valid HMAC
    // -------------------------------------------------------------------------

    public function test_valid_wc_hmac_passes_and_attaches_store(): void
    {
        $workspace = Workspace::factory()->create();
        $store     = Store::factory()->create([
            'workspace_id'             => $workspace->id,
            'platform'                 => 'woocommerce',
            'webhook_secret_encrypted' => Crypt::encryptString(self::WEBHOOK_SECRET),
        ]);

        $body     = '{"id":1001,"status":"completed"}';
        $expected = base64_encode(hash_hmac('sha256', $body, self::WEBHOOK_SECRET, true));

        $request  = $this->makeWcRequest($store->id, $body, $expected);
        $response = $this->callMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($store->id, $request->attributes->get('webhook_store')->id);
    }

    // -------------------------------------------------------------------------
    // VerifyWebhookSignature — tampered payload
    // -------------------------------------------------------------------------

    public function test_tampered_body_returns_401(): void
    {
        $workspace = Workspace::factory()->create();
        $store     = Store::factory()->create([
            'workspace_id'             => $workspace->id,
            'platform'                 => 'woocommerce',
            'webhook_secret_encrypted' => Crypt::encryptString(self::WEBHOOK_SECRET),
        ]);

        $originalBody = '{"id":1001,"status":"completed"}';
        $tamperedBody = '{"id":9999,"status":"completed"}';
        $signatureFor = base64_encode(hash_hmac('sha256', $originalBody, self::WEBHOOK_SECRET, true));

        $request  = $this->makeWcRequest($store->id, $tamperedBody, $signatureFor);
        $response = $this->callMiddleware($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_wrong_signature_returns_401(): void
    {
        $workspace = Workspace::factory()->create();
        $store     = Store::factory()->create([
            'workspace_id'             => $workspace->id,
            'platform'                 => 'woocommerce',
            'webhook_secret_encrypted' => Crypt::encryptString(self::WEBHOOK_SECRET),
        ]);

        $body    = '{"id":1001}';
        $request = $this->makeWcRequest($store->id, $body, 'bm90YXJlYWxzaWduYXR1cmU=');

        $this->assertSame(401, $this->callMiddleware($request)->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // VerifyWebhookSignature — error paths
    // -------------------------------------------------------------------------

    public function test_missing_signature_header_still_returns_401(): void
    {
        $workspace = Workspace::factory()->create();
        $store     = Store::factory()->create([
            'workspace_id'             => $workspace->id,
            'platform'                 => 'woocommerce',
            'webhook_secret_encrypted' => Crypt::encryptString(self::WEBHOOK_SECRET),
        ]);

        // Empty signature → hash_equals('expected', '') → false → 401
        $request  = $this->makeWcRequest($store->id, '{}', '');
        $response = $this->callMiddleware($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_no_credentials_row_returns_500(): void
    {
        $workspace = Workspace::factory()->create();
        // No webhook secret on the store — middleware should return 500
        $store     = Store::factory()->create([
            'workspace_id'             => $workspace->id,
            'webhook_secret_encrypted' => null,
        ]);

        $request  = $this->makeWcRequest($store->id, '{}', 'any_hmac==');
        $response = $this->callMiddleware($request);

        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_unknown_store_returns_404(): void
    {
        $request  = $this->makeWcRequest(99999, '{}', 'any_hmac==');
        $response = $this->callMiddleware($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // WooCommerceClient — product pagination (simulates syncProducts page loop)
    // -------------------------------------------------------------------------

    public function test_product_sync_fetches_multiple_pages(): void
    {
        $page1Products = array_fill(0, 3, ['id' => 1, 'name' => 'Product A', 'status' => 'publish', 'sku' => '', 'price' => '29.99', 'stock_status' => 'instock', 'stock_quantity' => 10, 'images' => [], 'categories' => [], 'type' => 'simple', 'slug' => 'product-a', 'permalink' => null, 'date_modified_gmt' => null]);
        $page2Products = array_fill(0, 2, ['id' => 2, 'name' => 'Product B', 'status' => 'publish', 'sku' => '', 'price' => '49.99', 'stock_status' => 'instock', 'stock_quantity' => 5, 'images' => [], 'categories' => [], 'type' => 'simple', 'slug' => 'product-b', 'permalink' => null, 'date_modified_gmt' => null]);

        Http::fake([
            $this->storeBaseUrl() . '/products*' => Http::sequence()
                ->push($page1Products, 200, ['X-WP-TotalPages' => '2'])
                ->push($page2Products, 200, ['X-WP-TotalPages' => '2']),
        ]);

        $client = new WooCommerceClient(self::DOMAIN, self::KEY, self::SECRET);

        // Simulate the syncProducts page loop
        $total      = 0;
        $page       = 1;
        $totalPages = 1;

        do {
            $result     = $client->fetchProductsPage(null, $page);
            $totalPages = $result['total_pages'];
            $total     += count($result['products']);
            $page++;
        } while ($page <= $totalPages);

        $this->assertSame(5, $total);
        Http::assertSentCount(2);
    }

    public function test_product_pagination_stops_on_empty_first_page(): void
    {
        Http::fake([
            $this->storeBaseUrl() . '/products*' => Http::response([], 200, ['X-WP-TotalPages' => '0']),
        ]);

        $client = new WooCommerceClient(self::DOMAIN, self::KEY, self::SECRET);

        $result = $client->fetchProductsPage(null, 1);

        $this->assertSame([], $result['products']);
        $this->assertSame(0, $result['total_pages']);
    }

    public function test_product_pagination_handles_single_page(): void
    {
        $products = [
            ['id' => 10, 'name' => 'Single Product', 'status' => 'publish', 'sku' => '', 'price' => '15.00', 'stock_status' => 'instock', 'stock_quantity' => null, 'images' => [], 'categories' => [], 'type' => 'simple', 'slug' => 'single-product', 'permalink' => null, 'date_modified_gmt' => null],
        ];

        Http::fake([
            $this->storeBaseUrl() . '/products*' => Http::response($products, 200, ['X-WP-TotalPages' => '1']),
        ]);

        $client = new WooCommerceClient(self::DOMAIN, self::KEY, self::SECRET);
        $result = $client->fetchProductsPage(null, 1);

        $this->assertCount(1, $result['products']);
        $this->assertSame(1, $result['total_pages']);
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    private function makeWcRequest(int $storeId, string $body, string $signature): Request
    {
        $request = Request::create("/api/webhooks/woocommerce/{$storeId}", 'POST');
        $request->initialize(
            query:      [],
            request:    [],
            attributes: [],
            cookies:    [],
            files:      [],
            server:     ['CONTENT_TYPE' => 'application/json', 'REQUEST_METHOD' => 'POST'],
            content:    $body,
        );
        $request->headers->set('X-WC-Webhook-Signature', $signature);
        $request->headers->set('X-WC-Webhook-Topic', 'order.created');

        $mockRoute = Mockery::mock();
        $mockRoute->shouldReceive('parameter')->with('id', null)->andReturn((string) $storeId);
        $request->setRouteResolver(fn () => $mockRoute);

        return $request;
    }

    private function callMiddleware(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return (new VerifyWebhookSignature())->handle($request, fn ($r) => new Response('ok', 200));
    }
}
