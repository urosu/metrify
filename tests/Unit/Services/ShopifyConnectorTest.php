<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ShopifyException;
use App\Services\Integrations\Shopify\ShopifyGraphQlClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HTTP-layer tests for ShopifyGraphQlClient.
 *
 * Covers typed exception mapping (401, 5xx, GraphQL errors), successful response
 * parsing, cursor-based pagination, and getShop field mapping.
 * No database required — Http::fake() stubs all Shopify Admin API calls.
 *
 * Webhook HMAC validation is covered by VerifyShopifyWebhookSignatureTest.
 */
class ShopifyConnectorTest extends TestCase
{
    private const DOMAIN      = 'test-shop.myshopify.com';
    private const TOKEN       = 'shpat_test_token';
    private const API_VERSION = '2026-04';

    private function makeGqlClient(): ShopifyGraphQlClient
    {
        return new ShopifyGraphQlClient(self::DOMAIN, self::TOKEN, self::API_VERSION);
    }

    private function gqlEndpoint(): string
    {
        return 'https://' . self::DOMAIN . '/admin/api/' . self::API_VERSION . '/graphql.json*';
    }

    // -------------------------------------------------------------------------
    // HTTP errors
    // -------------------------------------------------------------------------

    public function test_throws_shopify_exception_on_401(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response(null, 401),
        ]);

        $this->expectException(ShopifyException::class);

        $this->makeGqlClient()->query('{ shop { name } }');
    }

    public function test_401_exception_carries_http_status(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response(null, 401),
        ]);

        try {
            $this->makeGqlClient()->query('{ shop { name } }');
            $this->fail('Expected ShopifyException');
        } catch (ShopifyException $e) {
            $this->assertSame(401, $e->httpStatus);
        }
    }

    public function test_throws_shopify_exception_on_500(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response(null, 500),
        ]);

        $this->expectException(ShopifyException::class);

        $this->makeGqlClient()->query('{ shop { name } }');
    }

    public function test_throws_on_graphql_errors_in_200_response(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response([
                'errors' => [
                    ['message' => 'Field does not exist'],
                    ['message' => 'Argument missing'],
                ],
            ], 200),
        ]);

        try {
            $this->makeGqlClient()->query('{ nonExistentField }');
            $this->fail('Expected ShopifyException');
        } catch (ShopifyException $e) {
            $this->assertStringContainsString('Field does not exist', $e->getMessage());
            $this->assertStringContainsString('Argument missing', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Successful response parsing
    // -------------------------------------------------------------------------

    public function test_returns_data_key_on_success(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response([
                'data' => ['shop' => ['name' => 'My Test Shop', 'currencyCode' => 'EUR', 'ianaTimezone' => 'Europe/Berlin']],
            ], 200),
        ]);

        $result = $this->makeGqlClient()->query('{ shop { name currencyCode ianaTimezone } }');

        $this->assertArrayHasKey('shop', $result);
        $this->assertSame('My Test Shop', $result['shop']['name']);
    }

    public function test_returns_empty_array_when_data_key_missing(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response(['extensions' => []], 200),
        ]);

        $result = $this->makeGqlClient()->query('{ shop { name } }');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // getShop — field mapping
    // -------------------------------------------------------------------------

    public function test_get_shop_returns_name_currency_timezone(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response([
                'data' => ['shop' => ['name' => 'Acme Store', 'currencyCode' => 'USD', 'ianaTimezone' => 'America/New_York']],
            ], 200),
        ]);

        $info = $this->makeGqlClient()->getShop();

        $this->assertSame('Acme Store', $info['name']);
        $this->assertSame('USD', $info['currency']);
        $this->assertSame('America/New_York', $info['timezone']);
    }

    public function test_get_shop_uppercases_currency(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response([
                'data' => ['shop' => ['name' => 'Store', 'currencyCode' => 'eur', 'ianaTimezone' => 'UTC']],
            ], 200),
        ]);

        $info = $this->makeGqlClient()->getShop();

        $this->assertSame('EUR', $info['currency']);
    }

    public function test_get_shop_throws_on_401(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response(null, 401),
        ]);

        $this->expectException(ShopifyException::class);

        $this->makeGqlClient()->getShop();
    }

    // -------------------------------------------------------------------------
    // Cursor-based pagination
    // -------------------------------------------------------------------------

    public function test_paginate_follows_cursor_across_pages(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::sequence()
                ->push([
                    'data' => [
                        'orders' => [
                            'edges'    => [
                                ['node' => ['id' => 'gid://shopify/Order/1', 'name' => '#1001']],
                                ['node' => ['id' => 'gid://shopify/Order/2', 'name' => '#1002']],
                            ],
                            'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cursor_abc'],
                        ],
                    ],
                ], 200)
                ->push([
                    'data' => [
                        'orders' => [
                            'edges'    => [
                                ['node' => ['id' => 'gid://shopify/Order/3', 'name' => '#1003']],
                            ],
                            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        ],
                    ],
                ], 200),
        ]);

        $allEdges = [];
        foreach ($this->makeGqlClient()->paginate(
            'query($cursor:String){ orders(first:50,after:$cursor){ edges { node { id name } } pageInfo { hasNextPage endCursor } } }',
            [],
            fn ($d) => $d['orders'],
        ) as $edges) {
            $allEdges = array_merge($allEdges, $edges);
        }

        $this->assertCount(3, $allEdges);
        $this->assertSame('#1001', $allEdges[0]['node']['name']);
        $this->assertSame('#1003', $allEdges[2]['node']['name']);
    }

    public function test_paginate_sends_exactly_two_requests_for_two_pages(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::sequence()
                ->push([
                    'data' => [
                        'orders' => [
                            'edges'    => [['node' => ['id' => 'gid://shopify/Order/1']]],
                            'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cursor_1'],
                        ],
                    ],
                ], 200)
                ->push([
                    'data' => [
                        'orders' => [
                            'edges'    => [['node' => ['id' => 'gid://shopify/Order/2']]],
                            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        ],
                    ],
                ], 200),
        ]);

        foreach ($this->makeGqlClient()->paginate(
            'query($cursor:String){ orders(first:50,after:$cursor){ edges { node { id } } pageInfo { hasNextPage endCursor } } }',
            [],
            fn ($d) => $d['orders'],
        ) as $edges) {
            // consume pages
        }

        Http::assertSentCount(2);
    }

    public function test_paginate_stops_after_single_page_when_no_next_page(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response([
                'data' => [
                    'orders' => [
                        'edges'    => [['node' => ['id' => 'gid://shopify/Order/1']]],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ], 200),
        ]);

        $pages = 0;
        foreach ($this->makeGqlClient()->paginate(
            'query($cursor:String){ orders(first:50,after:$cursor){ edges { node { id } } pageInfo { hasNextPage endCursor } } }',
            [],
            fn ($d) => $d['orders'],
        ) as $edges) {
            $pages++;
        }

        $this->assertSame(1, $pages);
        Http::assertSentCount(1);
    }

    public function test_paginate_yields_nothing_when_edges_empty(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::response([
                'data' => [
                    'orders' => [
                        'edges'    => [],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ], 200),
        ]);

        $allEdges = [];
        foreach ($this->makeGqlClient()->paginate(
            'query($cursor:String){ orders(first:50,after:$cursor){ edges { node { id } } pageInfo { hasNextPage endCursor } } }',
            [],
            fn ($d) => $d['orders'],
        ) as $edges) {
            $allEdges = array_merge($allEdges, $edges);
        }

        $this->assertSame([], $allEdges);
    }

    public function test_paginate_throws_on_api_error_mid_pagination(): void
    {
        Http::fake([
            $this->gqlEndpoint() => Http::sequence()
                ->push([
                    'data' => [
                        'orders' => [
                            'edges'    => [['node' => ['id' => 'gid://shopify/Order/1']]],
                            'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cursor_abc'],
                        ],
                    ],
                ], 200)
                ->push(null, 401),
        ]);

        $this->expectException(ShopifyException::class);

        foreach ($this->makeGqlClient()->paginate(
            'query($cursor:String){ orders(first:50,after:$cursor){ edges { node { id } } pageInfo { hasNextPage endCursor } } }',
            [],
            fn ($d) => $d['orders'],
        ) as $edges) {
            // consume first page — second page should throw
        }
    }
}
