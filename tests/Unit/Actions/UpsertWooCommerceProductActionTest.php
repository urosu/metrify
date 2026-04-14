<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpsertWooCommerceProductAction;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpsertWooCommerceProductActionTest extends TestCase
{
    use RefreshDatabase;

    private UpsertWooCommerceProductAction $action;
    private Workspace $workspace;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::factory()->create(['reporting_currency' => 'EUR']);
        $this->store     = Store::factory()->create(['workspace_id' => $this->workspace->id]);
        $this->action    = app(UpsertWooCommerceProductAction::class);

        app(WorkspaceContext::class)->set($this->workspace->id);
    }

    private function makeWcProduct(array $overrides = []): array
    {
        return array_merge([
            'id'               => 101,
            'name'             => 'Test Product',
            'sku'              => 'SKU-TEST',
            'price'            => '29.99',
            'status'           => 'publish',
            'stock_status'     => 'instock',
            'stock_quantity'   => 10,
            'type'             => 'simple',
            'permalink'        => 'https://mystore.example.com/test-product',
            'date_modified_gmt' => '2026-04-01T10:00:00',
            'images'           => [['src' => 'https://mystore.example.com/img/product.jpg']],
            'categories'       => [],
        ], $overrides);
    }

    public function test_product_upserted_with_core_fields(): void
    {
        $this->action->handle($this->store, $this->makeWcProduct());

        $this->assertDatabaseHas('products', [
            'store_id'       => $this->store->id,
            'workspace_id'   => $this->workspace->id,
            'external_id'    => '101',
            'name'           => 'Test Product',
            'sku'            => 'SKU-TEST',
            'status'         => 'publish',
            'stock_status'   => 'instock',
            'stock_quantity' => 10,
            'product_type'   => 'simple',
        ]);

        $product = DB::table('products')->where('external_id', '101')->first();
        $this->assertEqualsWithDelta(29.99, (float) $product->price, 0.001);
    }

    public function test_missing_product_id_returns_early_with_warning(): void
    {
        Log::spy();

        $this->action->handle($this->store, ['name' => 'No ID Product']);

        $this->assertDatabaseCount('products', 0);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_idempotent_upsert(): void
    {
        $product = $this->makeWcProduct();

        $this->action->handle($this->store, $product);
        $this->action->handle($this->store, $product);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_categories_upserted_and_pivot_created(): void
    {
        $this->action->handle($this->store, $this->makeWcProduct([
            'categories' => [
                ['id' => 5, 'name' => 'Shoes',       'slug' => 'shoes'],
                ['id' => 6, 'name' => 'Running Gear', 'slug' => 'running-gear'],
            ],
        ]));

        $this->assertDatabaseCount('product_categories', 2);

        $productId = DB::table('products')->where('external_id', '101')->value('id');
        $this->assertNotNull($productId);

        $pivotCount = DB::table('product_category_product')
            ->where('product_id', $productId)
            ->count();
        $this->assertSame(2, $pivotCount);
    }

    public function test_category_pivot_replaced_on_re_upsert(): void
    {
        // First: 2 categories
        $this->action->handle($this->store, $this->makeWcProduct([
            'categories' => [
                ['id' => 5, 'name' => 'Shoes',   'slug' => 'shoes'],
                ['id' => 6, 'name' => 'Sandals',  'slug' => 'sandals'],
            ],
        ]));

        // Second: 1 different category
        $this->action->handle($this->store, $this->makeWcProduct([
            'categories' => [
                ['id' => 7, 'name' => 'Boots', 'slug' => 'boots'],
            ],
        ]));

        $productId = DB::table('products')->where('external_id', '101')->value('id');

        $pivotCount = DB::table('product_category_product')
            ->where('product_id', $productId)
            ->count();
        $this->assertSame(1, $pivotCount);

        // The remaining pivot should link to the 'Boots' category
        $catId = DB::table('product_categories')
            ->where('external_id', '7')
            ->value('id');
        $this->assertDatabaseHas('product_category_product', [
            'product_id'  => $productId,
            'category_id' => $catId,
        ]);
    }

    public function test_empty_categories_does_not_wipe_pivot(): void
    {
        // Create product with categories
        $this->action->handle($this->store, $this->makeWcProduct([
            'categories' => [['id' => 5, 'name' => 'Shoes', 'slug' => 'shoes']],
        ]));

        $productId = DB::table('products')->where('external_id', '101')->value('id');

        // Re-upsert with empty categories (partial payload)
        $this->action->handle($this->store, $this->makeWcProduct(['categories' => []]));

        // Pivot should be untouched
        $pivotCount = DB::table('product_category_product')
            ->where('product_id', $productId)
            ->count();
        $this->assertSame(1, $pivotCount);
    }

    public function test_null_stock_quantity_stored_as_null(): void
    {
        $this->action->handle($this->store, $this->makeWcProduct(['stock_quantity' => null]));

        $product = DB::table('products')->where('external_id', '101')->first();
        $this->assertNull($product->stock_quantity);
    }

    public function test_product_type_stored(): void
    {
        $this->action->handle($this->store, $this->makeWcProduct(['type' => 'variable']));

        $this->assertDatabaseHas('products', [
            'external_id'  => '101',
            'product_type' => 'variable',
        ]);
    }

    public function test_image_url_stored_from_first_image(): void
    {
        $this->action->handle($this->store, $this->makeWcProduct([
            'images' => [
                ['src' => 'https://mystore.example.com/img/main.jpg'],
                ['src' => 'https://mystore.example.com/img/alt.jpg'],
            ],
        ]));

        $this->assertDatabaseHas('products', [
            'external_id' => '101',
            'image_url'   => 'https://mystore.example.com/img/main.jpg',
        ]);
    }
}
