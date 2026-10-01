<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Inventory\StockMovementService;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Enums\StockMovementType;
use App\Exceptions\EmptySaleException;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Ventes au détail (cahier §14, §27, §28 et §29).
 *
 * L'enregistrement d'une vente est une opération critique :
 *  - elle est transactionnelle ;
 *  - elle crée un mouvement de sortie de stock par ligne via
 *    `StockMovementService` (seul écrivain du stock), qui refuse un stock
 *    négatif ;
 *  - elle est idempotente : une clé `sale:{id}:item:{id}` par mouvement et
 *    une clé d'enregistrement rendent la vente rejouable sans double
 *    déduction.
 *
 * Les montants sont des entiers FCFA. La vente aux grossistes nominative
 * relève de la Phase 7 (module Grossistes).
 */
final class SalesService
{
    public function __construct(
        private readonly StockMovementService $movements,
    ) {}

    /**
     * Enregistre et valide une vente.
     *
     * @param  list<array{product_id: int, quantity: int|float|string, unit_price?: int, discount?: int, unit_id?: int|null}>  $lines
     */
    public function record(
        Bakery $bakery,
        array $lines,
        ?Customer $customer = null,
        ?string $paymentMethod = null,
        ?int $paidAmount = null,
        ?User $user = null,
        ?CarbonInterface $soldAt = null,
        ?string $note = null,
        ?string $idempotencyKey = null,
        SaleType $type = SaleType::Retail,
    ): Sale {
        $key = $idempotencyKey !== null && trim($idempotencyKey) !== '' ? trim($idempotencyKey) : null;

        if ($key !== null) {
            $existing = $this->findByIdempotencyKey($key);

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use (
                $bakery, $lines, $customer, $paymentMethod, $paidAmount, $user, $soldAt, $note, $key, $type
            ): Sale {
                $soldAt ??= now();

                $sale = Sale::create([
                    'bakery_id' => $bakery->getKey(),
                    'customer_id' => $customer?->getKey(),
                    'created_by' => $user?->getKey(),
                    'validated_by' => $user?->getKey(),
                    'reference' => $this->nextReference(),
                    'type' => $type,
                    'status' => SaleStatus::Validated,
                    'sale_date' => $soldAt->toDateString(),
                    'subtotal' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'paid_total' => 0,
                    'idempotency_key' => $key,
                    'note' => $note,
                    'validated_at' => now(),
                ]);

                $subtotal = 0;
                $discount = 0;
                $position = 0;

                foreach ($lines as $line) {
                    $product = Product::query()->findOrFail((int) ($line['product_id'] ?? 0));
                    $quantity = Decimal::abs((string) ($line['quantity'] ?? 0));

                    if (! Decimal::isPositive($quantity)) {
                        continue;
                    }

                    $defaultPrice = $type === SaleType::Wholesale
                        ? $product->wholesale_price
                        : $product->sale_price;

                    $unitPrice = array_key_exists('unit_price', $line) && $line['unit_price'] !== null
                        ? max(0, (int) $line['unit_price'])
                        : $defaultPrice;

                    $lineDiscount = max(0, (int) ($line['discount'] ?? 0));
                    $gross = self::lineTotal($quantity, $unitPrice);
                    $lineTotal = max(0, $gross - $lineDiscount);

                    $item = $sale->items()->create([
                        'product_id' => $product->getKey(),
                        'unit_id' => $line['unit_id'] ?? $product->primary_unit_id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount' => $lineDiscount,
                        'line_total' => $lineTotal,
                        'unit_cost' => (int) $product->purchase_price,
                        'position' => $position,
                    ]);

                    $this->movements->apply(
                        bakery: $bakery,
                        product: $product,
                        type: StockMovementType::SaleOut,
                        quantity: $quantity,
                        unit: $product->primaryUnit,
                        user: $user,
                        idempotencyKey: "sale:{$sale->getKey()}:item:{$item->getKey()}",
                        reference: $sale->reference,
                        reason: 'Vente au détail',
                        source: $sale,
                        occurredAt: $soldAt,
                    );

                    $subtotal += $gross;
                    $discount += $lineDiscount;
                    $position++;
                }

                if ($position === 0) {
                    throw EmptySaleException::create();
                }

                $sale->subtotal = $subtotal;
                $sale->discount = $discount;
                $sale->total = max(0, $subtotal - $discount);

                if ($paymentMethod !== null) {
                    $amount = max(0, $paidAmount ?? $sale->total);

                    $sale->payments()->create([
                        'bakery_id' => $bakery->getKey(),
                        'customer_id' => $customer?->getKey(),
                        'created_by' => $user?->getKey(),
                        'method' => $paymentMethod,
                        'amount' => $amount,
                        'paid_at' => $soldAt,
                    ]);

                    $sale->paid_total = $amount;
                }

                $sale->save();

                return $sale->refresh();
            });
        } catch (UniqueConstraintViolationException $exception) {
            if ($key !== null) {
                $existing = $this->findByIdempotencyKey($key);

                if ($existing !== null) {
                    return $existing;
                }
            }

            throw $exception;
        }
    }

    /**
     * Montant d'une ligne en entiers FCFA, arrondi au plus proche.
     */
    public static function lineTotal(int|float|string $quantity, int $unitPrice): int
    {
        if ($unitPrice <= 0 || ! Decimal::isPositive($quantity)) {
            return 0;
        }

        $raw = bcmul(Decimal::normalize($quantity), (string) $unitPrice, 2);

        return (int) bcadd($raw, '0.5', 0);
    }

    private function findByIdempotencyKey(string $key): ?Sale
    {
        return Sale::withoutBakeryScope()
            ->where('idempotency_key', $key)
            ->first();
    }

    private function nextReference(): string
    {
        $prefix = 'SALE-'.now()->format('Ym').'-';

        $sequence = Sale::withoutBakeryScope()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Sale::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }
}
