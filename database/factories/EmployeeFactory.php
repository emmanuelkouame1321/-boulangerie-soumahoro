<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContractType;
use App\Enums\EmployeeStatus;
use App\Models\Bakery;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('+225 07 ## ## ## ##'),
            'address' => fake()->address(),
            'position' => 'worker',
            'contract_type' => ContractType::Cdi,
            'base_salary' => 150_000,
            'hired_on' => now()->subYear()->toDateString(),
            'status' => EmployeeStatus::Active,
            'admin_note' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => EmployeeStatus::Inactive]);
    }
}
