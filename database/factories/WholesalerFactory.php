<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bakery;
use App\Models\Wholesaler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Wholesaler>
 */
class WholesalerFactory extends Factory
{
    protected $model = Wholesaler::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'customer_id' => null,
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'phone' => fake()->numerify('+225 07 ## ## ## ##'),
            'address' => fake()->address(),
            'zone' => fake()->randomElement(['Centre', 'Cocody', 'Yopougon', 'Marcory']),
            'client_type' => 'Grossiste',
            'price_mode' => 'wholesale',
            'usual_quantity' => '100.0000',
            'payment_condition' => 'monthly',
            'is_active' => true,
            'joined_on' => Carbon::now()->subMonths(6)->toDateString(),
            'note' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
