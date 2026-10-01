<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContractType;
use App\Enums\EmployeeStatus;
use App\Models\Bakery;
use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Employés de démonstration (local et testing uniquement).
 */
class EmployeeSeeder extends Seeder
{
    /** @var list<array{first: string, last: string, position: string, salary: int}> */
    private const EMPLOYEES = [
        ['first' => 'Awa', 'last' => 'Koné', 'position' => 'production-manager', 'salary' => 250_000],
        ['first' => 'Kofi', 'last' => 'Traoré', 'position' => 'worker', 'salary' => 150_000],
        ['first' => 'Fatou', 'last' => 'Diabaté', 'position' => 'cashier', 'salary' => 130_000],
        ['first' => 'Ibrahim', 'last' => 'Coulibaly', 'position' => 'driver', 'salary' => 140_000],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $bakery = Bakery::query()->orderBy('id')->first();

        if ($bakery === null) {
            return;
        }

        foreach (self::EMPLOYEES as $definition) {
            Employee::query()->updateOrCreate(
                [
                    'bakery_id' => $bakery->getKey(),
                    'first_name' => $definition['first'],
                    'last_name' => $definition['last'],
                ],
                [
                    'position' => $definition['position'],
                    'contract_type' => ContractType::Cdi,
                    'base_salary' => $definition['salary'],
                    'status' => EmployeeStatus::Active,
                    'hired_on' => now()->subYear()->toDateString(),
                ]
            );
        }
    }
}
