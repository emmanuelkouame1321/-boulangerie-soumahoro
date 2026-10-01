<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionBatch>
 */
class ProductionBatchFactory extends Factory
{
    protected $model = ProductionBatch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(50, 300);

        return [
            'bakery_id' => Bakery::factory(),
            'production_order_id' => null,
            'production_output_id' => null,
            'product_id' => Product::factory(),
            'reference' => 'LOT-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1_000, 9_999),
            'initial_quantity' => $quantity,
            'available_quantity' => $quantity,
            'sold_quantity' => 0,
            'lost_quantity' => 0,
            'produced_on' => now()->toDateString(),
            'expires_on' => now()->addDay()->toDateString(),
            'status' => BatchStatus::Fresh,
            'note' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_on' => now()->subDay()->toDateString(),
            'status' => BatchStatus::Expired,
        ]);
    }
}
