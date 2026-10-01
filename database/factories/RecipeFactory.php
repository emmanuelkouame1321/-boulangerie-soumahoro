<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Recette '.fake()->unique()->word(),
            'reference' => 'RCP-'.fake()->unique()->numberBetween(1_000, 9_999),
            'yield_quantity' => 300,
            'production_time_minutes' => 60,
            'shelf_life_days' => 1,
            'allowed_loss_percent' => null,
            'is_active' => true,
            'note' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
