<?php

declare(strict_types=1);

namespace App\Domain\Production;

use App\Enums\RecipeDeductionMode;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Support\Decimal;
use Illuminate\Support\Collection;

/**
 * Calcul des besoins d'une recette (cahier §5, §6 et §37.1).
 *
 * À partir d'une recette et d'un nombre de lots, le calculateur dérive :
 *  - les ressources nécessaires, quantité par quantité, avec leur mode de
 *    déduction et leur coût unitaire ;
 *  - le rendement théorique (produit fini attendu) ;
 *  - le coût théorique en entiers FCFA ;
 *  - l'écart entre la quantité réelle et la quantité théorique.
 *
 * Tous les calculs sont en décimal exact (BCMath) ; jamais de `float`.
 */
final class RecipeCalculator
{
    /**
     * Ressources nécessaires pour un nombre de lots.
     *
     * @return list<array{
     *     product_id: int,
     *     unit_id: int|null,
     *     planned_quantity: string,
     *     deduction_mode: RecipeDeductionMode,
     *     unit_cost: int,
     *     line_cost: int,
     *     position: int
     * }>
     */
    public function requirements(Recipe $recipe, int|float|string $batches): array
    {
        $batchCount = $this->positive($batches);

        /** @var Collection<int, RecipeItem> $items */
        $items = $recipe->items()->with('product')->get();

        $requirements = [];

        foreach ($items as $index => $item) {
            $quantity = Decimal::multiply((string) $item->quantity, $batchCount, Decimal::SCALE);

            if (! Decimal::isPositive($quantity)) {
                continue;
            }

            $unitCost = $this->unitCost($item);

            $requirements[] = [
                'product_id' => (int) $item->product_id,
                'unit_id' => $item->unit_id !== null ? (int) $item->unit_id : null,
                'planned_quantity' => $quantity,
                'deduction_mode' => $item->deduction_mode,
                'unit_cost' => $unitCost,
                'line_cost' => self::lineCost($quantity, $unitCost),
                'position' => (int) ($item->position ?? $index),
            ];
        }

        return $requirements;
    }

    /**
     * Rendement théorique : produit fini attendu pour un nombre de lots
     * (cahier §37.1).
     */
    public function theoreticalQuantity(Recipe $recipe, int|float|string $batches): string
    {
        return Decimal::multiply((string) $recipe->yield_quantity, $this->positive($batches), Decimal::SCALE);
    }

    /**
     * Coût théorique de production, en entiers FCFA (matières, ressources
     * monétaires et cumulées, cahier §45).
     */
    public function theoreticalCost(Recipe $recipe, int|float|string $batches): int
    {
        $total = 0;

        foreach ($this->requirements($recipe, $batches) as $requirement) {
            $total += $requirement['line_cost'];
        }

        return $total;
    }

    /**
     * Écart = quantité réelle − quantité théorique (cahier §37.1).
     * Un écart négatif signale une production en deçà du rendement.
     */
    public function gap(int|float|string $theoretical, int|float|string $actual): string
    {
        return Decimal::subtract($actual, $theoretical, Decimal::SCALE);
    }

    /**
     * Coût unitaire d'une ressource : montant configuré s'il existe, sinon
     * prix d'achat du produit au moment du calcul (cahier §45).
     */
    private function unitCost(RecipeItem $item): int
    {
        if ($item->unit_cost !== null) {
            return (int) $item->unit_cost;
        }

        return (int) $item->product->purchase_price;
    }

    /**
     * Montant d'une ligne en entiers FCFA, arrondi au plus proche.
     */
    public static function lineCost(int|float|string $quantity, int $unitCost): int
    {
        if ($unitCost <= 0 || ! Decimal::isPositive($quantity)) {
            return 0;
        }

        $raw = bcmul(Decimal::normalize($quantity), (string) $unitCost, 2);

        return (int) bcadd($raw, '0.5', 0);
    }

    /**
     * Nombre de lots ramené à une valeur strictement positive.
     */
    private function positive(int|float|string $batches): string
    {
        $value = Decimal::abs($batches, Decimal::SCALE);

        return Decimal::isPositive($value) ? $value : '0';
    }
}
