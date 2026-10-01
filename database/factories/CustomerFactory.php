<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bakery;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'name' => fake()->name(),
            'phone' => fake()->numerify('+225 07 ## ## ## ##'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'note' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
