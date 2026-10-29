<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' Ice Cream',
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50, 1000),
            'stock_quantity' => fake()->numberBetween(0, 100),
        ];
    }
}

