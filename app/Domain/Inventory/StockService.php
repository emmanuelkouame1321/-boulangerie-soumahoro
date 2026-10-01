<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lecture du stock (cahier §9 et §37).
 *
 * Le stock disponible vaut « physique − réservé » (cahier §37.3). Le
 * statut (disponible / faible / critique) découle du seuil d'alerte du
 * produit. La consommation cumulée alimente le mode d'alerte cumulé.
 *
 * Aucune écriture ici : la seule écriture du stock passe par
 * `StockMovementService` (cahier §3.2).
 */
final class StockService
{
    /**
     * Ligne de stock d'un produit sur un site, sans verrou.
     */
    public function find(Product $product, Bakery $bakery): ?Stock
    {
        return $this->query($bakery, $product)->first();
    }

    /**
     * Verrouille la ligne de stock d'un produit sur un site (cahier §28).
     *
     * `insertOrIgnore` absorbe la course de création : la ligne existe
     * forcément avant le verrou. À n'appeler que dans une transaction.
     */
    public function lockForUpdate(Bakery $bakery, Product $product): Stock
    {
        Stock::withoutBakeryScope()->insertOrIgnore([
            'bakery_id' => $bakery->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => 0,
            'reserved_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->query($bakery, $product)->lockForUpdate()->firstOrFail();
    }

    /**
     * @return Builder<Stock>
     */
    private function query(Bakery $bakery, Product $product): Builder
    {
        return Stock::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('product_id', $product->getKey());
    }

    /**
     * Stock physique (cahier §37.2).
     */
    public function physical(Product $product, Bakery $bakery): string
    {
        $stock = $this->find($product, $bakery);

        return $stock === null ? '0' : Decimal::trim((string) $stock->quantity);
    }

    /**
     * Quantité immobilisée par des réservations actives.
     */
    public function reserved(Product $product, Bakery $bakery): string
    {
        $stock = $this->find($product, $bakery);

        return $stock === null ? '0' : Decimal::trim((string) $stock->reserved_quantity);
    }

    /**
     * Stock disponible = physique − réservé (cahier §37.3).
     */
    public function available(Product $product, Bakery $bakery): string
    {
        $stock = $this->find($product, $bakery);

        return $stock === null ? '0' : Decimal::trim($stock->availableQuantity());
    }

    /**
     * Statut d'alerte d'un stock, dérivé de la table
     * `config('boulangerie.statuses.stock')` :
     * `critical`, `low` ou `available`.
     */
    public function status(Product $product, Bakery $bakery): string
    {
        return $this->statusFor($product, $this->physical($product, $bakery));
    }

    /**
     * Statut d'après une quantité physique déjà connue (évite une requête
     * par ligne dans les listes).
     */
    public function statusFor(Product $product, string $physical): string
    {
        if (Decimal::compare($physical, '0') <= 0) {
            return 'critical';
        }

        if ($product->alert_threshold === null) {
            return 'available';
        }

        return Decimal::compare($physical, (string) $product->alert_threshold) <= 0 ? 'low' : 'available';
    }

    /**
     * Consommation cumulée d'un produit sur un site, sur la période
     * demandée (cahier §9.3). Somme des mouvements qui diminuent le stock.
     *
     * @param  string|null  $period  clé de config('boulangerie.cumulation_periods')
     */
    public function cumulatedConsumption(
        Product $product,
        Bakery $bakery,
        ?string $period = null,
        ?CarbonInterface $at = null
    ): string {
        $query = StockMovement::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('product_id', $product->getKey())
            ->whereIn('type', self::outMovementTypes());

        $start = $this->periodStart($product, $bakery, $period, $at);

        if ($start !== null) {
            $query->where('occurred_at', '>=', $start);
        }

        $sum = $query->sum('quantity');

        return Decimal::normalize(is_numeric($sum) ? $sum : 0);
    }

    private function periodStart(
        Product $product,
        Bakery $bakery,
        ?string $period,
        ?CarbonInterface $at
    ): ?CarbonInterface {
        $now = $at ?? now();

        return match ($period) {
            'day' => $now->copy()->startOfDay(),
            'week' => $now->copy()->startOfWeek(),
            'month' => $now->copy()->startOfMonth(),
            'since_last_restock' => $this->lastRestockAt($product, $bakery),
            default => null,
        };
    }

    /**
     * Date du dernier réapprovisionnement (entrée fournisseur), qui sert
     * de point de départ au cumul « depuis dernier réapprovisionnement ».
     */
    private function lastRestockAt(Product $product, Bakery $bakery): ?CarbonInterface
    {
        $movement = StockMovement::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('product_id', $product->getKey())
            ->where('type', StockMovementType::SupplierIn->value)
            ->orderByDesc('occurred_at')
            ->first();

        return $movement?->occurred_at;
    }

    /**
     * @return list<string>
     */
    private static function outMovementTypes(): array
    {
        return array_values(array_map(
            static fn (StockMovementType $type): string => $type->value,
            array_filter(
                StockMovementType::cases(),
                static fn (StockMovementType $type): bool => $type->sign() === -1
            )
        ));
    }
}
