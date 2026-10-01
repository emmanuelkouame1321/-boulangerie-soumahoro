<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductionOrderStatus;
use App\Models\Bakery;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    protected $model = ProductionOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'recipe_id' => Recipe::factory(),
            'created_by' => null,
            'validated_by' => null,
            'reference' => 'PRD-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1_000, 9_999),
            'status' => ProductionOrderStatus::Draft,
            'batch_count' => 1,
            'theoretical_quantity' => 300,
            'actual_quantity' => null,
            'gap' => null,
            'gap_reason' => null,
            'theoretical_cost' => 0,
            'actual_cost' => null,
            'produced_on' => now()->toDateString(),
            'validated_at' => null,
            'note' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductionOrderStatus::Validated,
            'actual_quantity' => 300,
            'gap' => 0,
            'actual_cost' => 0,
            'validated_at' => now(),
        ]);
    }
}
