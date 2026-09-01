<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_a_product_can_be_created(): void
    {
        $category = Category::factory()->create();
        $suppliers = Supplier::factory(2)->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Wireless Mouse',
            'sku' => 'WM-001',
            'price' => 49.90,
            'quantity' => 10,
            'reorder_level' => 3,
            'supplier_ids' => $suppliers->pluck('id')->all(),
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.sku', 'WM-001')
            ->assertJsonCount(2, 'data.suppliers');
        $this->assertDatabaseHas('products', ['sku' => 'WM-001', 'quantity' => 10]);
        $this->assertDatabaseCount('product_supplier', 2);
    }

    public function test_a_product_can_be_viewed(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', $product->sku);
    }

    public function test_products_are_listed_from_latest_to_oldest_by_default(): void
    {
        $oldest = Product::factory()->create(['created_at' => now()->subDays(2)]);
        $latest = Product::factory()->create(['created_at' => now()]);
        $middle = Product::factory()->create(['created_at' => now()->subDay()]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $latest->id)
            ->assertJsonPath('data.1.id', $middle->id)
            ->assertJsonPath('data.2.id', $oldest->id);
    }

    public function test_products_can_be_filtered_to_low_stock(): void
    {
        Product::factory()->create(['quantity' => 2, 'reorder_level' => 5]);
        Product::factory()->create(['quantity' => 20, 'reorder_level' => 5]);

        $this->getJson('/api/products?low_stock=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_low_stock', true);
    }

    public function test_swagger_boolean_query_values_filter_active_products(): void
    {
        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        $this->getJson('/api/products?is_active=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeProduct->id);

        $this->getJson('/api/products?is_active=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactiveProduct->id);
    }

    public function test_swagger_boolean_query_value_filters_low_stock_products(): void
    {
        Product::factory()->create(['quantity' => 2, 'reorder_level' => 5]);
        Product::factory()->create(['quantity' => 20, 'reorder_level' => 5]);

        $this->getJson('/api/products?low_stock=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_low_stock', true);
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $selectedCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        $selectedProduct = Product::factory()->for($selectedCategory)->create();
        Product::factory()->for($otherCategory)->create();

        $this->getJson("/api/products?category_id={$selectedCategory->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $selectedProduct->id);
    }

    public function test_products_can_be_filtered_by_price_range(): void
    {
        Product::factory()->create(['price' => 9.99]);
        $selectedProduct = Product::factory()->create(['price' => 25.00]);
        Product::factory()->create(['price' => 75.00]);

        $this->getJson('/api/products?min_price=20&max_price=50')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $selectedProduct->id);
    }

    public function test_products_can_be_filtered_by_stock_range(): void
    {
        Product::factory()->create(['quantity' => 2]);
        $selectedProduct = Product::factory()->create(['quantity' => 10]);
        Product::factory()->create(['quantity' => 30]);

        $this->getJson('/api/products?min_stock=5&max_stock=15')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $selectedProduct->id);
    }

    public function test_products_are_paginated(): void
    {
        Product::factory(3)->create();

        $this->getJson('/api/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_product_filter_ranges_are_validated(): void
    {
        $this->getJson('/api/products?min_price=50&max_price=10&min_stock=20&max_stock=5')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price', 'max_stock']);
    }

    public function test_product_is_low_stock_accessor_calculates_stock_status(): void
    {
        $lowStockProduct = Product::factory()->make(['quantity' => 2, 'reorder_level' => 5]);
        $inStockProduct = Product::factory()->make(['quantity' => 10, 'reorder_level' => 5]);

        $this->assertTrue($lowStockProduct->is_low_stock);
        $this->assertFalse($inStockProduct->is_low_stock);
    }

    public function test_a_product_quantity_can_be_updated(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $this->patchJson("/api/products/{$product->id}", [
            'quantity' => 7,
        ])->assertOk()->assertJsonPath('data.quantity', 7);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 7]);
    }

    public function test_product_quantity_cannot_become_negative(): void
    {
        $product = Product::factory()->create(['quantity' => 2]);

        $this->patchJson("/api/products/{$product->id}", [
            'quantity' => -1,
        ])->assertUnprocessable();

        $this->assertSame(2, $product->refresh()->quantity);
    }

    public function test_product_supplier_ids_must_exist_and_be_distinct(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Wireless Mouse',
            'sku' => 'WM-002',
            'price' => 49.90,
            'supplier_ids' => [999, 999],
        ])->assertUnprocessable()->assertJsonValidationErrors('supplier_ids.0');
    }

    public function test_updating_supplier_ids_synchronizes_product_suppliers(): void
    {
        $product = Product::factory()->create();
        $oldSupplier = Supplier::factory()->create();
        $newSuppliers = Supplier::factory(2)->create();
        $product->suppliers()->attach($oldSupplier);

        $this->patchJson("/api/products/{$product->id}", [
            'supplier_ids' => $newSuppliers->pluck('id')->all(),
        ])->assertOk()->assertJsonCount(2, 'data.suppliers');

        $this->assertDatabaseMissing('product_supplier', [
            'product_id' => $product->id,
            'supplier_id' => $oldSupplier->id,
        ]);
        foreach ($newSuppliers as $supplier) {
            $this->assertDatabaseHas('product_supplier', [
                'product_id' => $product->id,
                'supplier_id' => $supplier->id,
            ]);
        }
    }

    public function test_omitting_supplier_ids_keeps_existing_product_suppliers(): void
    {
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $product->suppliers()->attach($supplier);

        $this->patchJson("/api/products/{$product->id}", ['quantity' => 15])->assertOk();

        $this->assertDatabaseHas('product_supplier', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_an_empty_supplier_ids_array_detaches_all_suppliers(): void
    {
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $product->suppliers()->attach($supplier);

        $this->patchJson("/api/products/{$product->id}", ['supplier_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.suppliers');

        $this->assertDatabaseMissing('product_supplier', ['product_id' => $product->id]);
    }

    public function test_a_product_is_soft_deleted(): void
    {
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $product->suppliers()->attach($supplier);

        $this->deleteJson("/api/products/{$product->id}")->assertNoContent();

        $this->assertSoftDeleted($product);
        $this->assertDatabaseHas('product_supplier', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_soft_deleted_products_are_excluded_from_api_queries(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/products/{$product->id}")->assertNotFound();

        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }
}
