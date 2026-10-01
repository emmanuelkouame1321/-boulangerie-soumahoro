<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bakery;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

/**
 * Catégories de charges d'amorçage (cahier §18).
 *
 * Reprises de `config('boulangerie.expense_categories')` puis
 * administrables depuis l'écran Charges.
 */
class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $bakery = Bakery::query()->orderBy('id')->first();

        if ($bakery === null) {
            return;
        }

        foreach ((array) config('boulangerie.expense_categories', []) as $definition) {
            /** @var array{code: string, name: string, position: int} $definition */
            ExpenseCategory::query()->updateOrCreate(
                [
                    'bakery_id' => $bakery->getKey(),
                    'code' => $definition['code'],
                ],
                [
                    'name' => $definition['name'],
                    'position' => $definition['position'],
                    'is_active' => true,
                ]
            );
        }
    }
}
