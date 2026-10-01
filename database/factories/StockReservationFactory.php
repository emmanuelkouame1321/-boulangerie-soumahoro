<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockReservationStatus;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockReservation>
 */
class StockReservationFactory extends Factory
{
    protected $model = StockReservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'source_type' => null,
            'source_id' => null,
            'status' => StockReservationStatus::Active,
            'reference' => null,
            'expires_at' => null,
        ];
    }
}
