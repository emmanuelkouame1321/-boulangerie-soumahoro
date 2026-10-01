<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bakery;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'bakery_id' => Bakery::factory(),
            'customer_id' => null,
            'created_by' => null,
            'method' => 'cash',
            'amount' => fake()->numberBetween(1_000, 50_000),
            'reference' => null,
            'paid_at' => now(),
            'note' => null,
        ];
    }
}
