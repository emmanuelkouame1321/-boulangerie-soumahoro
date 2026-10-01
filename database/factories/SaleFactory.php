<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Bakery;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1_000, 50_000);

        return [
            'bakery_id' => Bakery::factory(),
            'customer_id' => null,
            'created_by' => null,
            'validated_by' => null,
            'reference' => 'SALE-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1_000, 9_999),
            'type' => SaleType::Retail,
            'status' => SaleStatus::Validated,
            'sale_date' => now()->toDateString(),
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'paid_total' => $subtotal,
            'idempotency_key' => null,
            'note' => null,
            'validated_at' => now(),
        ];
    }

    public function wholesale(): static
    {
        return $this->state(fn (): array => ['type' => SaleType::Wholesale]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => SaleStatus::Cancelled]);
    }
}
