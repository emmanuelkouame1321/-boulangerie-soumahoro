<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Catégories de produits (cahier §4.2).
 *
 * Source de vérité : config/boulangerie.php. Idempotent.
 */
class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{code: string, name: string, position: int}> $categories */
        $categories = config('boulangerie.product_categories', []);

        foreach ($categories as $category) {
            ProductCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'position' => $category['position'],
                    'is_active' => true,
                ]
            );
        }
    }
}
