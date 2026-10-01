<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecipeDeductionMode;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrderItem>
 */
class ProductionOrderItemFactory extends Factory
{
    protected $model = ProductionOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_order_id' => ProductionOrder::factory(),
            'product_id' => Product::factory(),
            'unit_id' => null,
            'deduction_mode' => RecipeDeductionMode::Quantity,
            'planned_quantity' => 1,
            'actual_quantity' => null,
            'unit_cost' => 27_500,
            'line_cost' => 27_500,
            'position' => 0,
        ];
    }
}
