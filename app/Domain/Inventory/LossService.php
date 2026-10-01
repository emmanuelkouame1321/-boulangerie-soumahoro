<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\LossOrigin;
use App\Enums\LossReason;
use App\Enums\ProductType;
use App\Enums\StockAlertType;
use App\Enums\StockMovementType;
use App\Exceptions\EmptyLossException;
use App\Exceptions\InsufficientLotException;
use App\Models\Bakery;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\User;
use App\Support\Decimal;
use App\Support\Format;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Pertes (cahier §8.2, §15, §22 et §50).
 *
 * Une perte est une opération critique :
 *  - transactionnelle et dans l'ordre de verrouillage « lots puis
 *    stock », identique aux ventes et à la péremption ;
 *  - traçable : référence `LOSS-{Ym}-{NNNN}`, motif, origine, auteur,
 *    valorisation FCFA figée au moment de la perte ;
 *  - jamais supprimée : la correction est une nouvelle opération.
 *
 * La conversion automatique des lots expirés (origine `expiry`) porte
 * une clé d'idempotence par lot et par date : deux exécutions du job
 * ne produisent qu'une seule perte (cahier §29).
 */
final class LossService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly LotService $lots,
        private readonly StockAlertService $alerts,
    ) {}

    /**
     * Enregistre une perte.
     *
     * @param  list<array{product_id: int, quantity: int|float|string, batch_id?: int|null, comment?: string|null}>  $lines
     *
     * @throws EmptyLossException
     * @throws InsufficientLotException
     */
    public function record(
        Bakery $bakery,
        LossReason $reason,
        array $lines,
        ?User $user = null,
        ?CarbonInterface $occurredOn = null,
        ?string $note = null,
        LossOrigin $origin = LossOrigin::Manual,
    ): LossRecord {
        $occurredOn ??= now();

        $valid = [];

        foreach ($lines as $line) {
            $quantity = Decimal::abs((string) ($line['quantity'] ?? 0));

            if (! Decimal::isPositive($quantity)) {
                continue;
            }

            $valid[] = [
                'product_id' => (int) ($line['product_id'] ?? 0),
                'quantity' => $quantity,
                'batch_id' => isset($line['batch_id']) ? (int) $line['batch_id'] : null,
                'comment' => isset($line['comment']) ? trim((string) $line['comment']) : null,
            ];
        }

        if ($valid === []) {
            throw EmptyLossException::create();
        }

        return DB::transaction(function () use ($bakery, $reason, $valid, $user, $occurredOn, $note, $origin): LossRecord {
            $record = LossRecord::create([
                'bakery_id' => $bakery->getKey(),
                'reference' => $this->nextReference(),
                'reason' => $reason,
                'origin' => $origin,
                'occurred_on' => $occurredOn->toDateString(),
                'note' => $note,
                'total_quantity' => '0',
                'total_value' => 0,
                'created_by' => $user?->getKey(),
            ]);

            $totalQuantity = '0';
            $totalValue = 0;
            $threshold = (int) config('boulangerie.loss.significant_threshold', 0);

            foreach ($valid as $line) {
                $product = Product::query()->findOrFail($line['product_id']);
                $quantity = $line['quantity'];
                $batch = null;

                if ($line['batch_id'] !== null) {
                    // Verrou pris avant le stock : ordre « lots puis
                    // stock » identique aux autres chemins critiques.
                    $batch = ProductionBatch::withoutBakeryScope()
                        ->where('bakery_id', $bakery->getKey())
                        ->whereKey($line['batch_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (Decimal::compare($quantity, (string) $batch->available_quantity) > 0) {
                        throw InsufficientLotException::for($batch, $quantity);
                    }
                }

                $unitValue = $this->unitValue($product);
                $lineValue = Decimal::toFrancs($quantity, $unitValue);

                $item = $record->items()->create([
                    'product_id' => $product->getKey(),
                    'production_batch_id' => $batch?->getKey(),
                    'quantity' => $quantity,
                    'unit_value' => $unitValue,
                    'line_value' => $lineValue,
                    'comment' => $line['comment'],
                ]);

                // Aucun lot désigné : imputation FEFO pour que les lots
                // et le stock restent cohérents.
                if ($batch === null && $product->type === ProductType::FinishedGood) {
                    $this->lots->attributeToLots($bakery, $product, $quantity, $occurredOn);
                }

                $idempotencyKey = $origin === LossOrigin::Expiry && $batch !== null
                    ? 'batch-expiry:'.$batch->getKey().':'.$occurredOn->toDateString()
                    : "loss:{$record->getKey()}:item:{$item->getKey()}";

                $movement = $this->movements->apply(
                    bakery: $bakery,
                    product: $product,
                    type: StockMovementType::LossOut,
                    quantity: $quantity,
                    unit: $product->primaryUnit,
                    user: $user,
                    idempotencyKey: $idempotencyKey,
                    reference: $record->reference,
                    reason: $reason->label(),
                    comment: $line['comment'],
                    source: $record,
                    occurredAt: $occurredOn,
                );

                // Un rejeu détecté par la clé n'applique pas deux fois la
                // déduction sur le lot (filet, jamais atteint en usage
                // normal puisque la conversion refuse un lot déjà vide).
                if ($batch !== null && $movement->wasRecentlyCreated) {
                    $batch->available_quantity = Decimal::subtract(
                        (string) $batch->available_quantity,
                        $quantity
                    );
                    $batch->lost_quantity = Decimal::add(
                        Decimal::trim((string) $batch->lost_quantity),
                        $quantity
                    );
                    $batch->status = $this->lots->deriveStatus($batch, $occurredOn);
                    $batch->save();
                }

                if ($lineValue >= $threshold && $threshold > 0) {
                    $this->alerts->open(
                        $bakery,
                        $product,
                        StockAlertType::SignificantLoss,
                        sprintf(
                            'Perte importante : %s de « %s » (%s, %s).',
                            Format::money($lineValue),
                            $product->name,
                            $record->reference,
                            $reason->label()
                        ),
                        Decimal::trim($quantity),
                    );
                }

                $totalQuantity = Decimal::add($totalQuantity, $quantity);
                $totalValue += $lineValue;
            }

            $record->fill([
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
            ])->save();

            return $record->load('items.product');
        });
    }

    /**
     * Valorisation unitaire d'une perte (entiers FCFA), figée au moment
     * de l'enregistrement (`boulangerie.loss.valuation_field`).
     */
    private function unitValue(Product $product): int
    {
        $field = (string) config('boulangerie.loss.valuation_field', 'purchase_price');

        return max(0, (int) $product->getAttribute($field));
    }

    private function nextReference(): string
    {
        $prefix = 'LOSS-'.now()->format('Ym').'-';

        $sequence = LossRecord::withoutBakeryScope()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (LossRecord::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }
}
