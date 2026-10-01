<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);
        $unitPrice = fake()->numberBetween(1, 20) * 250;

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'unit_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => 0,
            'line_total' => $quantity * $unitPrice,
            'unit_cost' => 0,
            'position' => 0,
        ];
    }
}
