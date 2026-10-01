<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOutput;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOutput>
 */
class ProductionOutputFactory extends Factory
{
    protected $model = ProductionOutput::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_order_id' => ProductionOrder::factory(),
            'product_id' => Product::factory(),
            'unit_id' => null,
            'quantity' => 300,
            'unit_cost' => 0,
            'produced_on' => now()->toDateString(),
        ];
    }
}
