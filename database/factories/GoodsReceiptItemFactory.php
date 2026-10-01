<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptItem>
 */
class GoodsReceiptItemFactory extends Factory
{
    protected $model = GoodsReceiptItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);
        $unitPrice = fake()->numberBetween(1, 30) * 500;

        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_item_id' => null,
            'product_id' => Product::factory(),
            'unit_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ];
    }
}
