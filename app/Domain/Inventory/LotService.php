<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\BatchStatus;
use App\Exceptions\LotExpiredException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Support\Decimal;
use Carbon\CarbonInterface;

/**
 * Lots de produits finis (cahier §8).
 *
 * Les lots suivent la règle FEFO (« first expired, first out ») : une
 * vente consomme d'abord le lot le plus proche de sa date limite et
 * n'**jamais** un lot expiré — un lot expiré ne peut plus être vendu
 * (cahier §41). La consommation verrouille les lignes de lot dans
 * l'ordre « lots puis stock », identique au chemin de perte, pour
 * éviter les blocages mutuels.
 *
 * Le statut dérivé (frais / à écouler / expiré / épuisé) est calculé à
 * partir des quantités et de la date limite, selon la règle de
 * péremption configurable (`boulangerie.expiry.conversion`).
 */
final class LotService
{
    public function __construct(private readonly StockService $stocks) {}

    /**
     * Consomme une vente sur les lots du produit, du lot le plus proche
     * de la date limite au plus lointain (FEFO), en excluant les lots
     * expirés.
     *
     * @return list<array{batch: ProductionBatch, quantity: string}>
     *
     * @throws LotExpiredException si le déficit provient de lots expirés
     */
    public function sellFromLots(Bakery $bakery, Product $product, string $quantity, CarbonInterface $date): array
    {
        return $this->consume($bakery, $product, $quantity, $date, false, false);
    }

    /**
     * Impute une perte sur les lots du produit (FEFO, lots expirés
     * compris) quand aucun lot n'est désigné explicitement : le stock et
     * les lots restent cohérents.
     *
     * @return list<array{batch: ProductionBatch, quantity: string}>
     */
    public function attributeToLots(Bakery $bakery, Product $product, string $quantity, CarbonInterface $date): array
    {
        return $this->consume($bakery, $product, $quantity, $date, true, true);
    }

    /**
     * Statut dérivé d'un lot (cahier §8) : expiré par la date, sinon
     * épuisé, sinon à écouler (seuil `boulangerie.expiry.soon_days`),
     * sinon frais.
     */
    public function deriveStatus(ProductionBatch $batch, CarbonInterface $date): BatchStatus
    {
        $today = $date->copy()->startOfDay();

        if (ExpiryService::isExpiredOn($batch, $today)) {
            return BatchStatus::Expired;
        }

        if (! Decimal::isPositive(Decimal::trim((string) $batch->available_quantity))) {
            return BatchStatus::Depleted;
        }

        $soonDays = (int) config('boulangerie.expiry.soon_days', 1);

        if ($batch->expires_on !== null && $batch->expires_on->lte($today->addDays($soonDays))) {
            return BatchStatus::ToSellSoon;
        }

        return BatchStatus::Fresh;
    }

    /**
     * @return list<array{batch: ProductionBatch, quantity: string}>
     */
    private function consume(
        Bakery $bakery,
        Product $product,
        string $quantity,
        CarbonInterface $date,
        bool $includeExpired,
        bool $isLoss,
    ): array {
        $today = $date->copy()->startOfDay();

        $lots = ProductionBatch::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('product_id', $product->getKey())
            ->where('available_quantity', '>', 0)
            // FEFO : date limite la plus proche en premier, lots sans
            // date limite en dernier.
            ->orderByRaw('CASE WHEN expires_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($lots->isEmpty()) {
            // Produit non tracé par lot : le contrôle revient au stock.
            return [];
        }

        $physical = $this->stocks->physical($product, $bakery);
        $expiredAvailable = '0';
        $allAvailable = '0';

        /** @var ProductionBatch $lot */
        foreach ($lots as $lot) {
            $available = Decimal::trim((string) $lot->available_quantity);
            $allAvailable = Decimal::add($allAvailable, $available);

            if (ExpiryService::isExpiredOn($lot, $today)) {
                $expiredAvailable = Decimal::add($expiredAvailable, $available);
            }
        }

        if (! $includeExpired) {
            $freshAvailable = Decimal::subtract($allAvailable, $expiredAvailable);
            $untracked = Decimal::subtract($physical, $allAvailable);
            $untracked = Decimal::compare($untracked, '0') < 0 ? '0' : $untracked;
            $sellable = Decimal::add($freshAvailable, $untracked);

            if (Decimal::compare($quantity, $sellable) > 0) {
                // Le déficit est imputable aux lots expirés : refus clair
                // (cahier §41). Sinon c'est un stock insuffisant, et
                // `StockMovementService` lèvera l'erreur dédiée.
                if (Decimal::compare($expiredAvailable, '0') > 0
                    && Decimal::compare($quantity, $physical) <= 0) {
                    throw LotExpiredException::for($product);
                }

                return [];
            }
        }

        $remaining = $quantity;
        $consumed = [];

        /** @var ProductionBatch $lot */
        foreach ($lots as $lot) {
            if (Decimal::compare($remaining, '0') <= 0) {
                break;
            }

            if (! $includeExpired && ExpiryService::isExpiredOn($lot, $today)) {
                continue;
            }

            $available = Decimal::trim((string) $lot->available_quantity);
            $take = Decimal::compare($remaining, $available) < 0 ? $remaining : $available;

            $lot->available_quantity = Decimal::subtract($available, $take);

            if ($isLoss) {
                $lot->lost_quantity = Decimal::add(Decimal::trim((string) $lot->lost_quantity), $take);
            } else {
                $lot->sold_quantity = Decimal::add(Decimal::trim((string) $lot->sold_quantity), $take);
            }

            $lot->status = $this->deriveStatus($lot, $today);
            $lot->save();

            $consumed[] = ['batch' => $lot, 'quantity' => $take];
            $remaining = Decimal::subtract($remaining, $take);
        }

        return $consumed;
    }
}
