<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContractType;
use App\Models\Bakery;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeContract>
 */
class EmployeeContractFactory extends Factory
{
    protected $model = EmployeeContract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'employee_id' => Employee::factory(),
            'reference' => 'CONTRACT-'.fake()->unique()->numerify('######'),
            'type' => ContractType::Cdi,
            'base_salary' => 150_000,
            'started_on' => now()->subYear()->toDateString(),
            'ended_on' => null,
            'is_active' => true,
            'note' => null,
        ];
    }
}
