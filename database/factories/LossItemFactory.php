<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LossItem;
use App\Models\LossRecord;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LossItem>
 */
class LossItemFactory extends Factory
{
    protected $model = LossItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);
        $value = fake()->numberBetween(100, 5_000);

        return [
            'loss_record_id' => LossRecord::factory(),
            'product_id' => Product::factory(),
            'production_batch_id' => null,
            'quantity' => $quantity,
            'unit_value' => $value,
            'line_value' => $quantity * $value,
            'comment' => null,
        ];
    }
}
