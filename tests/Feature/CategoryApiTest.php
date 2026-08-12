<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_names_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Electronics']);

        $this->postJson('/api/categories', ['name' => 'Electronics'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertConflict();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
