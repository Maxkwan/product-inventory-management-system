<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supplier_can_be_created(): void
    {
        $response = $this->postJson('/api/suppliers', [
            'name' => 'Acme Supplies',
            'contact_name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Acme Supplies')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('suppliers', ['name' => 'Acme Supplies']);
    }

    public function test_supplier_names_must_be_unique(): void
    {
        Supplier::factory()->create(['name' => 'Acme Supplies']);

        $this->postJson('/api/suppliers', ['name' => 'Acme Supplies'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_a_supplier_can_be_viewed_updated_and_deleted(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Old Name']);

        $this->getJson("/api/suppliers/{$supplier->id}")
            ->assertOk()
            ->assertJsonPath('data.products_count', 0);

        $this->patchJson("/api/suppliers/{$supplier->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->deleteJson("/api/suppliers/{$supplier->id}")->assertNoContent();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_deleting_a_supplier_detaches_products_without_deleting_them(): void
    {
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $product->suppliers()->attach($supplier);

        $this->deleteJson("/api/suppliers/{$supplier->id}")->assertNoContent();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_supplier', ['supplier_id' => $supplier->id]);
    }
}
