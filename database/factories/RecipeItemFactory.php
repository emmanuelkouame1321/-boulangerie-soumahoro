<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecipeDeductionMode;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeItem>
 */
class RecipeItemFactory extends Factory
{
    protected $model = RecipeItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'product_id' => Product::factory(),
            'unit_id' => null,
            'quantity' => 1,
            'deduction_mode' => RecipeDeductionMode::Quantity,
            'unit_cost' => null,
            'position' => 0,
        ];
    }

    public function monetary(int $unitCost = 2_000): static
    {
        return $this->state(fn (): array => [
            'deduction_mode' => RecipeDeductionMode::Monetary,
            'quantity' => 1,
            'unit_cost' => $unitCost,
        ]);
    }

    public function cumulated(int $unitCost = 2_000): static
    {
        return $this->state(fn (): array => [
            'deduction_mode' => RecipeDeductionMode::Cumulated,
            'quantity' => 1,
            'unit_cost' => $unitCost,
        ]);
    }
}
