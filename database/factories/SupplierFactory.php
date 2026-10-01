<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'phone' => fake()->numerify('07 00 00 ## ##'),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
            'identification' => strtoupper(fake()->bothify('RCCM-####-??')),
            'categories' => fake()->randomElement(['Farine, levure', 'Emballages', 'Sel, améliorant']),
            'payment_terms' => fake()->randomElement(['Comptant', '30 jours', '15 jours']),
            'delivery_delay_days' => fake()->numberBetween(1, 15),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
