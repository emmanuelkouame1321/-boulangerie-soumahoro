<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockMovementException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Écriture du stock — unique point d'entrée (cahier §3.2, §27, §28 et §29).
 *
 * Chaque variation de stock :
 *  - est transactionnelle ;
 *  - verrouille la ligne de stock (`lockForUpdate`) pour éviter les
 *    courses ;
 *  - refuse de rendre le stock négatif, sauf autorisation administrative
 *    (`config('boulangerie.guards.allow_negative_stock')`) ;
 *  - est idempotente lorsqu'une clé est fournie : rejouer la même requête
 *    ne crée pas un second mouvement ;
 *  - déclenche le recalcul des alertes.
 *
 * Aucun autre code ne doit écrire `stocks.quantity`.
 */
final class StockMovementService
{
    public function __construct(
        private readonly StockService $stocks,
        private readonly StockAlertService $alerts,
    ) {}

    /**
     * Applique un mouvement de stock.
     *
     * @param  int|float|string  $quantity  quantité strictement positive, dans l'unité principale
     * @param  string|null  $idempotencyKey  clé rendant l'opération rejouable sans double effet
     */
    public function apply(
        Bakery $bakery,
        Product $product,
        StockMovementType $type,
        int|float|string $quantity,
        ?Unit $unit = null,
        ?User $user = null,
        ?string $idempotencyKey = null,
        ?string $reference = null,
        ?string $reason = null,
        ?string $comment = null,
        ?Model $source = null,
        ?CarbonInterface $occurredAt = null,
    ): StockMovement {
        $amount = Decimal::abs($quantity, Decimal::SCALE);

        if (! Decimal::isPositive($amount)) {
            throw InvalidStockMovementException::nonPositiveQuantity();
        }

        $key = $idempotencyKey !== null && trim($idempotencyKey) !== '' ? trim($idempotencyKey) : null;

        if ($key !== null) {
            $existing = $this->findByIdempotencyKey($key);

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use (
                $bakery, $product, $type, $amount, $unit, $user, $key,
                $reference, $reason, $comment, $source, $occurredAt
            ): StockMovement {
                $stock = $this->stocks->lockForUpdate($bakery, $product);

                $signed = Decimal::multiply($amount, (string) $type->sign(), Decimal::SCALE);
                $newPhysical = Decimal::add((string) $stock->quantity, $signed, Decimal::SCALE);

                if (! (bool) config('boulangerie.guards.allow_negative_stock', false)
                    && Decimal::compare($newPhysical, '0', Decimal::SCALE) === -1) {
                    throw InsufficientStockException::for(
                        $product,
                        $bakery,
                        $amount,
                        (string) $stock->quantity
                    );
                }

                $stock->quantity = $newPhysical;
                $stock->save();

                $movement = StockMovement::create([
                    'bakery_id' => $bakery->getKey(),
                    'product_id' => $product->getKey(),
                    'user_id' => $user?->getKey(),
                    'unit_id' => $unit?->getKey(),
                    'type' => $type->value,
                    'quantity' => $amount,
                    'quantity_after' => $newPhysical,
                    'source_type' => $source !== null ? $source->getMorphClass() : null,
                    'source_id' => $source?->getKey(),
                    'reference' => $reference,
                    'reason' => $reason,
                    'comment' => $comment,
                    'idempotency_key' => $key,
                    'occurred_at' => $occurredAt ?? now(),
                ]);

                $this->alerts->recalculate($product, $bakery);

                return $movement;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Deux soumissions concurrentes portant la même clé : la
            // première a gagné, la seconde renvoie son résultat.
            if ($key !== null) {
                $existing = $this->findByIdempotencyKey($key);

                if ($existing !== null) {
                    return $existing;
                }
            }

            throw $exception;
        }
    }

    private function findByIdempotencyKey(string $key): ?StockMovement
    {
        return StockMovement::withoutBakeryScope()
            ->where('idempotency_key', $key)
            ->first();
    }
}
