<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bakery;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'expense_category_id' => null,
            'reference' => 'EXP-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'label' => fake()->sentence(2),
            'amount' => fake()->numberBetween(5_000, 500_000),
            'spent_on' => now()->toDateString(),
            'payee' => fake()->company(),
            'method' => 'cash',
            'attachment_path' => null,
            'comment' => null,
            'created_by' => null,
        ];
    }
}
