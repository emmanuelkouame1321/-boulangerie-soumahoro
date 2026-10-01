<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Enums\PurchaseOrderStatus;
use App\Models\Bakery;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Commandes fournisseur (cahier §11.1 et §11.2).
 *
 * Une commande est créée avec ses lignes dans une transaction. Les totaux
 * sont recalculés depuis les lignes (montants en entiers FCFA). Le statut
 * découle de l'avancement des réceptions.
 */
final class PurchaseOrderService
{
    /**
     * Crée une commande et ses lignes.
     *
     * @param  list<array{product_id: int, quantity: int|float|string, unit_price?: int, unit_id?: int|null}>  $lines
     */
    public function create(
        Bakery $bakery,
        Supplier $supplier,
        array $lines,
        ?User $user = null,
        PurchaseOrderStatus $status = PurchaseOrderStatus::Ordered,
        ?string $note = null,
        ?CarbonInterface $expectedAt = null,
        ?CarbonInterface $orderedAt = null,
    ): PurchaseOrder {
        return DB::transaction(function () use (
            $bakery, $supplier, $lines, $user, $status, $note, $expectedAt, $orderedAt
        ): PurchaseOrder {
            $order = PurchaseOrder::create([
                'bakery_id' => $bakery->getKey(),
                'supplier_id' => $supplier->getKey(),
                'created_by' => $user?->getKey(),
                'reference' => $this->nextOrderReference(),
                'status' => $status,
                'ordered_at' => $orderedAt?->toDateString() ?? ($status === PurchaseOrderStatus::Draft ? null : now()->toDateString()),
                'expected_at' => $expectedAt?->toDateString(),
                'total' => 0,
                'note' => $note,
            ]);

            foreach ($lines as $line) {
                $quantity = Decimal::abs((string) $line['quantity']);

                if (! Decimal::isPositive($quantity)) {
                    continue;
                }

                $unitPrice = max(0, (int) ($line['unit_price'] ?? 0));

                $item = new PurchaseOrderItem([
                    'product_id' => (int) $line['product_id'],
                    'unit_id' => isset($line['unit_id']) ? (int) $line['unit_id'] : null,
                    'quantity' => $quantity,
                    'received_quantity' => 0,
                    'unit_price' => $unitPrice,
                ]);

                $item->line_total = self::lineTotal($quantity, $unitPrice);
                $order->items()->save($item);
            }

            $this->recalculateTotal($order);

            return $order->refresh();
        });
    }

    /**
     * Recalcule le total de la commande depuis ses lignes.
     */
    public function recalculateTotal(PurchaseOrder $order): PurchaseOrder
    {
        $total = 0;

        foreach ($order->items()->get() as $item) {
            $lineTotal = self::lineTotal((string) $item->quantity, (int) $item->unit_price);

            if ($item->line_total !== $lineTotal) {
                $item->line_total = $lineTotal;
                $item->save();
            }

            $total += $lineTotal;
        }

        $order->total = $total;
        $order->save();

        return $order;
    }

    /**
     * Met à jour le statut de la commande d'après l'avancement des
     * réceptions : « validée » lorsque toutes les lignes sont couvertes,
     * « partiellement livrée » sinon (cahier §11.1).
     */
    public function recalculateStatus(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status === PurchaseOrderStatus::Cancelled) {
            return $order;
        }

        $items = $order->items()->get();

        if ($items->isEmpty()) {
            return $order;
        }

        $fullyReceived = true;

        foreach ($items as $item) {
            if (! $item->isFullyReceived()) {
                $fullyReceived = false;
                break;
            }
        }

        if ($fullyReceived) {
            $order->status = PurchaseOrderStatus::Validated;
            $order->validated_at ??= now();
        } else {
            $order->status = PurchaseOrderStatus::PartiallyReceived;
        }

        $order->save();

        return $order;
    }

    /**
     * Annule une commande (jamais de suppression physique, cahier §3.3).
     */
    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        $order->status = PurchaseOrderStatus::Cancelled;
        $order->save();

        return $order;
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

    private function nextOrderReference(): string
    {
        $prefix = 'PO-'.now()->format('Ym').'-';

        $sequence = PurchaseOrder::withoutBakeryScope()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (PurchaseOrder::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }
}
