<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DayClosing;
use App\Models\DayClosingItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DayClosingItem>
 */
class DayClosingItemFactory extends Factory
{
    protected $model = DayClosingItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $produced = fake()->numberBetween(50, 300);
        $sold = fake()->numberBetween(0, $produced);

        return [
            'day_closing_id' => DayClosing::factory(),
            'product_id' => Product::factory(),
            'produced_quantity' => $produced,
            'sold_quantity' => $sold,
            'remaining_quantity' => $produced - $sold,
            'lost_quantity' => 0,
            'returned_quantity' => 0,
            'gap' => 0,
        ];
    }
}
