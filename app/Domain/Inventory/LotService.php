<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\BatchStatus;
use App\Enums\LotConsumption;
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
        return $this->consume($bakery, $product, $quantity, $date, LotConsumption::Sold);
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
        return $this->consume($bakery, $product, $quantity, $date, LotConsumption::Lost);
    }

    /**
     * Sort des lots la quantité expédiée vers un autre site (FEFO, lots
     * expirés exclus : on ne déplace pas un produit qui n'est plus
     * vendable — cahier §19.3 et §41).
     *
     * @return list<array{batch: ProductionBatch, quantity: string}>
     */
    public function transferFromLots(Bakery $bakery, Product $product, string $quantity, CarbonInterface $date): array
    {
        return $this->consume($bakery, $product, $quantity, $date, LotConsumption::Transferred);
    }

    /**
     * Sort une quantité d'un lot désigné explicitement (transfert d'un lot
     * précis, cahier §19.3). Le lot reste au grand livre du site
     * expéditeur comme historique : seule sa quantité disponible diminue.
     */
    public function transferFromBatch(ProductionBatch $batch, string $quantity, CarbonInterface $date): void
    {
        $available = Decimal::trim((string) $batch->available_quantity);
        $taken = Decimal::compare($quantity, $available) < 0 ? Decimal::trim($quantity) : $available;

        $batch->available_quantity = Decimal::subtract($available, $taken);
        $batch->transferred_quantity = Decimal::add(
            Decimal::trim((string) $batch->transferred_quantity),
            $taken
        );
        $batch->status = $this->deriveStatus($batch, $date);
        $batch->save();
    }

    /**
     * Fait renaître chez le destinataire le lot reçu, avec les mêmes dates
     * de production et de limite de vente : sans cela le produit
     * arriverait sans traçabilité et pourrait être vendu après péremption.
     *
     * Le lot d'origine n'est jamais déplacé : l'historique du site
     * expéditeur reste intact (cahier §53-11).
     */
    public function createFromTransfer(
        Bakery $bakery,
        Product $product,
        string $quantity,
        ProductionBatch $source,
        Bakery $origin,
        string $reference,
    ): ProductionBatch {
        $today = now();
        $producedOn = $source->produced_on;
        $expiresOn = $source->expires_on;

        $batch = ProductionBatch::create([
            'bakery_id' => $bakery->getKey(),
            'product_id' => $product->getKey(),
            'reference' => $reference,
            'initial_quantity' => $quantity,
            'available_quantity' => $quantity,
            'produced_on' => $producedOn instanceof CarbonInterface
                ? $producedOn->toDateString()
                : $today->toDateString(),
            'expires_on' => $expiresOn instanceof CarbonInterface ? $expiresOn->toDateString() : null,
            'status' => BatchStatus::Fresh,
            'note' => sprintf(
                'Reçu de %s (lot %s).',
                $origin->name,
                $source->reference
            ),
        ]);

        $batch->status = $this->deriveStatus($batch, $today);
        $batch->save();

        return $batch;
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
        LotConsumption $reason,
    ): array {
        $today = $date->copy()->startOfDay();
        $includeExpired = $reason->includesExpired();

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

            match ($reason) {
                LotConsumption::Sold => $lot->sold_quantity = Decimal::add(
                    Decimal::trim((string) $lot->sold_quantity),
                    $take
                ),
                LotConsumption::Lost => $lot->lost_quantity = Decimal::add(
                    Decimal::trim((string) $lot->lost_quantity),
                    $take
                ),
                LotConsumption::Transferred => $lot->transferred_quantity = Decimal::add(
                    Decimal::trim((string) $lot->transferred_quantity),
                    $take
                ),
            };

            $lot->status = $this->deriveStatus($lot, $today);
            $lot->save();

            $consumed[] = ['batch' => $lot, 'quantity' => $take];
            $remaining = Decimal::subtract($remaining, $take);
        }

        return $consumed;
    }
}
