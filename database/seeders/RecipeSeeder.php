<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Enums\RecipeDeductionMode;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

/**
 * Recettes de démonstration (local et testing uniquement).
 *
 * Amorce la nomenclature du pain du cahier §5.1 : 1 sac de farine donne
 * 300 pains, avec les ressources physiques, monétaires (glace) et cumulées
 * (fagot). Idempotent : rejouer le seeder ne duplique rien.
 */
class RecipeSeeder extends Seeder
{
    /**
     * Ressources du pain : produit, mode de déduction, quantité par lot,
     * coût unitaire FCFA (null = prix d'achat du produit).
     *
     * @var list<array{sku: string, mode: RecipeDeductionMode, quantity: string, cost: int|null}>
     */
    private const PAIN_ITEMS = [
        ['sku' => 'MP-FARINE', 'mode' => RecipeDeductionMode::Quantity, 'quantity' => '1', 'cost' => null],
        ['sku' => 'MP-LEVURE', 'mode' => RecipeDeductionMode::Quantity, 'quantity' => '1', 'cost' => null],
        ['sku' => 'MP-AMELIORANT', 'mode' => RecipeDeductionMode::Quantity, 'quantity' => '1', 'cost' => null],
        ['sku' => 'MP-SEL', 'mode' => RecipeDeductionMode::Quantity, 'quantity' => '0.5', 'cost' => null],
        ['sku' => 'RES-GLACE', 'mode' => RecipeDeductionMode::Monetary, 'quantity' => '1', 'cost' => 2_000],
        ['sku' => 'RES-FAGOT', 'mode' => RecipeDeductionMode::Cumulated, 'quantity' => '1', 'cost' => 2_000],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $bread = Product::query()->where('sku', 'PF-BAGUETTE')->first();

        if ($bread === null) {
            return;
        }

        $recipe = Recipe::query()->updateOrCreate(
            ['reference' => 'RCP-PAIN'],
            [
                'product_id' => $bread->getKey(),
                'name' => 'Pain traditionnel',
                'yield_quantity' => 300,
                'production_time_minutes' => 120,
                'shelf_life_days' => 1,
                'allowed_loss_percent' => 3,
                'is_active' => true,
            ]
        );

        if ($recipe->items()->exists()) {
            return;
        }

        foreach (self::PAIN_ITEMS as $position => $definition) {
            $product = Product::query()->where('sku', $definition['sku'])->first();

            if ($product === null || $product->type === ProductType::FinishedGood) {
                continue;
            }

            $recipe->items()->create([
                'product_id' => $product->getKey(),
                'unit_id' => $product->primary_unit_id,
                'quantity' => $definition['quantity'],
                'deduction_mode' => $definition['mode'],
                'unit_cost' => $definition['cost'],
                'position' => $position,
            ]);
        }
    }
}
