<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bakery;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'code' => 'cat-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->word(),
            'position' => 0,
            'is_active' => true,
        ];
    }
}
