<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Produits et matières premières de démonstration (local et testing
 * uniquement). Permet de valider la fiche produit et les écrans sans
 * saisie manuelle. Jamais exécuté en production.
 */
class ProductSeeder extends Seeder
{
    /**
     * Produits de démonstration. Les codes d'unité et de catégorie sont
     * résolus dynamiquement : un identifiant absent est simplement ignoré.
     */
    private const PRODUCTS = [
        ['sku' => 'MP-FARINE', 'name' => 'Farine 50 kg', 'type' => ProductType::RawMaterial, 'unit' => 'sack', 'category' => 'raw', 'purchase' => 27_500, 'sale' => 0, 'wholesale' => 0, 'threshold' => 10, 'shelf_life' => null],
        ['sku' => 'MP-LEVURE', 'name' => 'Levure', 'type' => ProductType::RawMaterial, 'unit' => 'pack', 'category' => 'raw', 'purchase' => 1_500, 'sale' => 0, 'wholesale' => 0, 'threshold' => 10, 'shelf_life' => null],
        ['sku' => 'MP-SEL', 'name' => 'Sel', 'type' => ProductType::RawMaterial, 'unit' => 'sack', 'category' => 'raw', 'purchase' => 4_000, 'sale' => 0, 'wholesale' => 0, 'threshold' => 5, 'shelf_life' => null],
        ['sku' => 'MP-AMELIORANT', 'name' => 'Améliorant', 'type' => ProductType::RawMaterial, 'unit' => 'pack', 'category' => 'raw', 'purchase' => 2_000, 'sale' => 0, 'wholesale' => 0, 'threshold' => 6, 'shelf_life' => null],
        ['sku' => 'RES-GLACE', 'name' => 'Glace', 'type' => ProductType::Resource, 'unit' => 'bundle', 'category' => 'raw', 'purchase' => 2_000, 'sale' => 0, 'wholesale' => 0, 'threshold' => null, 'shelf_life' => null],
        ['sku' => 'RES-FAGOT', 'name' => 'Fagot', 'type' => ProductType::Resource, 'unit' => 'bundle', 'category' => 'raw', 'purchase' => 2_000, 'sale' => 0, 'wholesale' => 0, 'threshold' => null, 'shelf_life' => null],
        ['sku' => 'PF-BAGUETTE', 'name' => 'Baguette', 'type' => ProductType::FinishedGood, 'unit' => 'piece', 'category' => 'bread', 'purchase' => 0, 'sale' => 500, 'wholesale' => 350, 'threshold' => 30, 'shelf_life' => 1],
        ['sku' => 'PF-CROISSANT', 'name' => 'Croissant', 'type' => ProductType::FinishedGood, 'unit' => 'piece', 'category' => 'pastry', 'purchase' => 0, 'sale' => 800, 'wholesale' => 550, 'threshold' => 25, 'shelf_life' => 2],
        ['sku' => 'PF-CAKE', 'name' => 'Cake nature', 'type' => ProductType::FinishedGood, 'unit' => 'piece', 'category' => 'cake', 'purchase' => 0, 'sale' => 3_500, 'wholesale' => 2_800, 'threshold' => 4, 'shelf_life' => 5],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('ProductSeeder ignoré : environnement non local.');

            return;
        }

        if (Unit::query()->count() === 0 || ProductCategory::query()->count() === 0) {
            return;
        }

        /** @var array<string, int> $units */
        $units = Unit::query()->pluck('id', 'code')->all();

        /** @var array<string, int> $categories */
        $categories = ProductCategory::query()->whereNotNull('code')->pluck('id', 'code')->all();

        foreach (self::PRODUCTS as $definition) {
            if (! isset($units[$definition['unit']])) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['sku' => $definition['sku']],
                [
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'primary_unit_id' => $units[$definition['unit']],
                    'product_category_id' => $categories[$definition['category']] ?? null,
                    'purchase_price' => $definition['purchase'],
                    'sale_price' => $definition['sale'],
                    'wholesale_price' => $definition['wholesale'],
                    'alert_threshold' => $definition['threshold'],
                    'alert_mode' => 'stock_threshold',
                    'shelf_life_days' => $definition['shelf_life'],
                    'is_active' => true,
                ]
            );
        }
    }
}
