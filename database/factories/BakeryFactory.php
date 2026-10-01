<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BakeryStatus;
use App\Models\Bakery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bakery>
 */
class BakeryFactory extends Factory
{
    protected $model = Bakery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BLG-'.fake()->unique()->numberBetween(100, 999),
            'name' => 'Boulangerie '.fake()->city(),
            'address' => fake()->streetAddress(),
            'phone' => fake()->phoneNumber(),
            'manager_id' => null,
            'status' => BakeryStatus::Active,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => BakeryStatus::Inactive]);
    }
}
