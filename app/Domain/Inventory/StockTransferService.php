<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\InsufficientLotException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockTransferException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\StockTransfer;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Transfert de stock entre deux sites (cahier §19.2 et §19.3).
 *
 * `Boulangerie A → Boulangerie B` en deux temps :
 *  1. **expédition** — un mouvement `transfer_out` sort le stock de A ;
 *     le lot, s'il est suivi, sort des lots de A ;
 *  2. **réception** — un mouvement `transfer_in` entre le stock en B, et
 *     le lot reçu renaît chez B avec la même date limite.
 *
 * Le stock reste la propriété exclusive de `StockMovementService` : ce
 * service ne fait qu'orchestrer le document, les lots et les statuts.
 *
 * Une opération est rejouable sans double effet : chaque mouvement porte
 * une clé d'idempotence dérivée du transfert (`transfer:{id}:out` /
 * `...:in`), et le statut est verrouillé puis vérifié dans la transaction.
 */
final class StockTransferService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly LotService $lots,
    ) {}

    /**
     * Prépare un transfert (brouillon : aucun stock n'a bougé).
     *
     *
     * @throws InvalidStockTransferException
     */
    public function create(
        Bakery $source,
        Bakery $destination,
        Product $product,
        int|float|string $quantity,
        ?ProductionBatch $batch = null,
        ?CarbonInterface $transferredOn = null,
        ?User $user = null,
        ?string $reason = null,
        ?string $note = null,
    ): StockTransfer {
        $amount = Decimal::abs($quantity, Decimal::SCALE);

        if (! Decimal::isPositive($amount)) {
            throw InvalidStockTransferException::nonPositiveQuantity();
        }

        if ((int) $source->getKey() === (int) $destination->getKey()) {
            throw InvalidStockTransferException::sameSite();
        }

        $transferredOn ??= now();

        if ($batch !== null) {
            $this->assertBatchBelongsToSource($batch, $source, $product, $amount);
        }

        return DB::transaction(fn (): StockTransfer => StockTransfer::create([
            'reference' => $this->nextReference($transferredOn),
            'bakery_id' => $source->getKey(),
            'destination_bakery_id' => $destination->getKey(),
            'product_id' => $product->getKey(),
            'unit_id' => $product->primary_unit_id,
            'production_batch_id' => $batch?->getKey(),
            'quantity' => $amount,
            'status' => StockTransferStatus::Draft,
            'transferred_on' => $transferredOn->toDateString(),
            'reason' => $reason,
            'note' => $note,
            'created_by' => $user?->getKey(),
        ]));
    }

    /**
     * Expédie le transfert : le stock quitte le site expéditeur.
     *
     * @throws InvalidStockTransferException
     * @throws InsufficientLotException
     * @throws InsufficientStockException
     */
    public function send(StockTransfer $transfer, ?User $user = null, ?CarbonInterface $at = null): StockTransfer
    {
        $at ??= now();

        return DB::transaction(function () use ($transfer, $user, $at): StockTransfer {
            $locked = $this->lock($transfer);

            if (! $locked->isDraft()) {
                throw InvalidStockTransferException::notDraft($locked);
            }

            $source = $locked->sourceBakery()->firstOrFail();
            $product = $locked->product()->firstOrFail();
            $quantity = (string) $locked->quantity;

            // Ordre de verrouillage « lots puis stock », identique aux
            // ventes, aux pertes et à la péremption.
            $batch = $this->lockBatch($locked);

            if ($batch !== null && Decimal::compare($quantity, (string) $batch->available_quantity) > 0) {
                throw InsufficientLotException::for($batch, $quantity);
            }

            $movement = $this->movements->apply(
                bakery: $source,
                product: $product,
                type: StockMovementType::TransferOut,
                quantity: $quantity,
                unit: $product->primaryUnit,
                user: $user,
                idempotencyKey: "transfer:{$locked->getKey()}:out",
                reference: $locked->reference,
                reason: $locked->reason ?? 'Transfert inter-sites',
                source: $locked,
                occurredAt: $at,
            );

            if ($movement->wasRecentlyCreated) {
                if ($batch !== null) {
                    $this->lots->transferFromBatch($batch, $quantity, $at);
                } elseif ($product->type === ProductType::FinishedGood) {
                    // Aucun lot désigné : imputation FEFO pour que les lots
                    // du site expéditeur restent cohérents avec son stock.
                    $this->lots->transferFromLots($source, $product, $quantity, $at);
                }
            }

            $locked->fill([
                'status' => StockTransferStatus::Sent,
                'sent_by' => $user?->getKey(),
                'sent_at' => $at,
            ])->save();

            return $locked->refresh();
        });
    }

    /**
     * Réceptionne le transfert : le stock entre chez le destinataire.
     *
     * @throws InvalidStockTransferException
     */
    public function receive(StockTransfer $transfer, ?User $user = null, ?CarbonInterface $at = null): StockTransfer
    {
        $at ??= now();

        return DB::transaction(function () use ($transfer, $user, $at): StockTransfer {
            $locked = $this->lock($transfer);

            if (! $locked->isSent()) {
                throw $locked->isDraft()
                    ? InvalidStockTransferException::notSent($locked)
                    : InvalidStockTransferException::alreadyFinal($locked);
            }

            $destination = $locked->destinationBakery()->firstOrFail();
            $origin = $locked->sourceBakery()->firstOrFail();
            $product = $locked->product()->firstOrFail();
            $quantity = (string) $locked->quantity;

            // Même ordre de verrouillage qu'à l'expédition : lots puis stock.
            $batch = $this->lockBatch($locked);

            $movement = $this->movements->apply(
                bakery: $destination,
                product: $product,
                type: StockMovementType::TransferIn,
                quantity: $quantity,
                unit: $product->primaryUnit,
                user: $user,
                idempotencyKey: "transfer:{$locked->getKey()}:in",
                reference: $locked->reference,
                reason: $locked->reason ?? 'Transfert inter-sites',
                source: $locked,
                occurredAt: $at,
            );

            if ($movement->wasRecentlyCreated && $batch !== null) {
                $this->lots->createFromTransfer(
                    $destination,
                    $product,
                    $quantity,
                    $batch,
                    $origin,
                    $locked->reference,
                );
            }

            $locked->fill([
                'status' => StockTransferStatus::Received,
                'received_by' => $user?->getKey(),
                'received_at' => $at,
            ])->save();

            return $locked->refresh();
        });
    }

    /**
     * Annule un brouillon — aucun mouvement n'a été créé, le document
     * reste comme trace de la préparation.
     *
     * @throws InvalidStockTransferException
     */
    public function cancel(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer): StockTransfer {
            $locked = $this->lock($transfer);

            if (! $locked->isDraft()) {
                throw InvalidStockTransferException::notDraft($locked);
            }

            $locked->fill(['status' => StockTransferStatus::Cancelled])->save();

            return $locked->refresh();
        });
    }

    /**
     * Le lot désigné appartient-il bien au site expéditeur, pour ce
     * produit, et contient-il la quantité demandée ?
     *
     * @throws InvalidStockTransferException
     * @throws InsufficientLotException
     */
    private function assertBatchBelongsToSource(
        ProductionBatch $batch,
        Bakery $source,
        Product $product,
        string $amount,
    ): void {
        $owned = ProductionBatch::withoutBakeryScope()
            ->whereKey($batch->getKey())
            ->where('bakery_id', $source->getKey())
            ->where('product_id', $product->getKey())
            ->first();

        if ($owned === null) {
            throw InvalidStockTransferException::batchNotInSource();
        }

        if (Decimal::compare($amount, (string) $owned->available_quantity) > 0) {
            throw InsufficientLotException::for($owned, $amount);
        }
    }

    /**
     * Verrouille le transfert pour toute la durée de la transaction : deux
     * expéditions simultanées ne peuvent pas se croiser.
     *
     * @throws ModelNotFoundException
     */
    private function lock(StockTransfer $transfer): StockTransfer
    {
        return StockTransfer::query()
            ->whereKey($transfer->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockBatch(StockTransfer $transfer): ?ProductionBatch
    {
        if ($transfer->production_batch_id === null) {
            return null;
        }

        return ProductionBatch::withoutBakeryScope()
            ->whereKey($transfer->production_batch_id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Référence `TRF-{Ym}-{NNNN}`, unique même en cas de création
     * concurrente (la boucle vérifie l'existence, l'index unique tranche).
     */
    private function nextReference(CarbonInterface $date): string
    {
        $prefix = 'TRF-'.$date->format('Ym').'-';

        $sequence = StockTransfer::query()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (StockTransfer::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
