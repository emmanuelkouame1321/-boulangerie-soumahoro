<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bakery;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'product_id' => Product::factory(),
            'quantity' => 0,
            'reserved_quantity' => 0,
        ];
    }

    public function withQuantity(int|string $quantity): static
    {
        return $this->state(fn (): array => ['quantity' => $quantity]);
    }
}
