<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 5, 200),
            'estimated_cost' => $this->faker->randomFloat(2, 1, 100),
            'active' => true,
            'image' => null,
            'notes' => $this->faker->sentence(),
        ];
    }
}
