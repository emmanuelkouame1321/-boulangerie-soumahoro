<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockMovementException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Réservations de stock (cahier §9.1 et §37.3).
 *
 * Réserver immobilise une quantité sans la consommer : le physique reste
 * inchangé, le disponible baisse. Libérer la restitue ; consommer la
 * transforme en sortie de stock via `StockMovementService` (jamais une
 * écriture parallèle).
 */
final class StockReservationService
{
    public function __construct(
        private readonly StockService $stocks,
        private readonly StockMovementService $movements,
    ) {}

    public function reserve(
        Bakery $bakery,
        Product $product,
        int|float|string $quantity,
        ?Model $source = null,
        ?string $reference = null,
        ?CarbonInterface $expiresAt = null,
    ): StockReservation {
        $amount = Decimal::abs($quantity, Decimal::SCALE);

        if (! Decimal::isPositive($amount)) {
            throw InvalidStockMovementException::nonPositiveQuantity();
        }

        return DB::transaction(function () use ($bakery, $product, $amount, $source, $reference, $expiresAt): StockReservation {
            $stock = $this->stocks->lockForUpdate($bakery, $product);

            $available = $stock->availableQuantity();

            if (Decimal::compare($available, $amount, Decimal::SCALE) === -1) {
                throw InsufficientStockException::forReservation($product, $bakery, $amount, $available);
            }

            $stock->reserved_quantity = Decimal::add((string) $stock->reserved_quantity, $amount, Decimal::SCALE);
            $stock->save();

            return StockReservation::create([
                'bakery_id' => $bakery->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => $amount,
                'source_type' => $source !== null ? $source->getMorphClass() : null,
                'source_id' => $source?->getKey(),
                'status' => StockReservationStatus::Active->value,
                'reference' => $reference,
                'expires_at' => $expiresAt,
            ]);
        });
    }

    /**
     * Libère une réservation active. Idempotent : libérer deux fois une
     * réservation déjà libérée ne change rien (cahier §29).
     */
    public function release(StockReservation $reservation): StockReservation
    {
        if (! $reservation->status->isActive()) {
            return $reservation;
        }

        return DB::transaction(function () use ($reservation): StockReservation {
            $this->decrementReserved($reservation);

            $reservation->update(['status' => StockReservationStatus::Released->value]);

            return $reservation->refresh();
        });
    }

    /**
     * Consomme une réservation : la quantité réservée est libérée puis
     * sortie du stock en un seul mouvement, dans une seule transaction.
     */
    public function consume(
        StockReservation $reservation,
        StockMovementType $type = StockMovementType::ProductionOut,
        ?User $user = null,
        ?string $reference = null,
    ): StockMovement {
        if (! $reservation->status->isActive()) {
            throw InvalidStockMovementException::alreadyConsumed();
        }

        return DB::transaction(function () use ($reservation, $type, $user, $reference): StockMovement {
            $this->decrementReserved($reservation);

            $reservation->update(['status' => StockReservationStatus::Consumed->value]);

            return $this->movements->apply(
                bakery: $reservation->bakery,
                product: $reservation->product,
                type: $type,
                quantity: (string) $reservation->quantity,
                user: $user,
                idempotencyKey: "stock-reservation:{$reservation->getKey()}:consume",
                reference: $reference ?? $reservation->reference,
                source: $reservation,
            );
        });
    }

    private function decrementReserved(StockReservation $reservation): void
    {
        $stock = $this->stocks->lockForUpdate($reservation->bakery, $reservation->product);

        $remaining = Decimal::subtract(
            (string) $stock->reserved_quantity,
            (string) $reservation->quantity,
            Decimal::SCALE
        );

        $stock->reserved_quantity = Decimal::isNegative($remaining, Decimal::SCALE) ? '0' : $remaining;
        $stock->save();
    }
}
