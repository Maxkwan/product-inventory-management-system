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

    public function test_products_can_be_filtered_to_low_stock(): void
    {
        Product::factory()->create(['quantity' => 2, 'reorder_level' => 5]);
        Product::factory()->create(['quantity' => 20, 'reorder_level' => 5]);

        $this->getJson('/api/products?low_stock=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_low_stock', true);
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
}
