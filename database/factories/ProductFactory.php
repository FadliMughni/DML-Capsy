<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = 'Produk Karet '.fake()->unique()->bothify('??-###');

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'material' => fake()->randomElement(['NBR', 'EPDM', 'Natural Rubber', 'Silicone']),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'hardness' => '70 Shore A',
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
