<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $suppliers = Supplier::factory(10)->create();

        Category::factory(5)->create()->each(function (Category $category) use ($suppliers): void {
            Product::factory(8)->for($category)->create()->each(
                fn (Product $product) => $product->suppliers()->attach(
                    $suppliers->random(fake()->numberBetween(1, 3))->pluck('id')
                )
            );
        });
    }
}
