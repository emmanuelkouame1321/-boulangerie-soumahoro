<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'product_id' => Product::factory(),
            'user_id' => null,
            'unit_id' => null,
            'type' => StockMovementType::SupplierIn,
            'quantity' => 1,
            'quantity_after' => null,
            'source_type' => null,
            'source_id' => null,
            'reference' => null,
            'reason' => null,
            'comment' => null,
            'idempotency_key' => null,
            'occurred_at' => now(),
        ];
    }
}
