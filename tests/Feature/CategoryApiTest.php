<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_category_names_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Electronics']);

        $this->postJson('/api/categories', ['name' => 'Electronics'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_categories_are_listed_from_latest_to_oldest(): void
    {
        $oldest = Category::factory()->create(['created_at' => now()->subDays(2)]);
        $latest = Category::factory()->create(['created_at' => now()]);
        $middle = Category::factory()->create(['created_at' => now()->subDay()]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $latest->id)
            ->assertJsonPath('data.1.id', $middle->id)
            ->assertJsonPath('data.2.id', $oldest->id);
    }

    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertConflict();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
