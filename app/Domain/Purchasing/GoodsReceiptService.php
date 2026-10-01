<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Inventory\StockMovementService;
use App\Enums\GoodsReceiptStatus;
use App\Enums\StockMovementType;
use App\Exceptions\GoodsReceiptAlreadyValidatedException;
use App\Exceptions\PurchaseOrderNotReceivableException;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Réception fournisseur (cahier §11.3, §27, §28 et §29).
 *
 * La validation d'une réception est une opération critique :
 *  - elle est transactionnelle ;
 *  - elle verrouille la réception et la commande (`lockForUpdate`) ;
 *  - elle crée un mouvement d'entrée de stock par ligne via
 *    `StockMovementService` (seul écrivain du stock) ;
 *  - elle est idempotente : chaque mouvement porte une clé
 *    `goods-receipt:{id}:item:{id}`, et une réception déjà validée
 *    refuse une seconde validation.
 *
 * Après validation : `stock existant + quantité reçue = nouveau stock`.
 */
final class GoodsReceiptService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly PurchaseOrderService $orders,
    ) {}

    /**
     * Enregistre puis valide immédiatement une réception (parcours écran).
     *
     * @param  list<array{purchase_order_item_id: int, quantity: int|float|string}>  $lines
     */
    public function receive(
        PurchaseOrder $order,
        array $lines,
        ?User $user = null,
        ?string $note = null,
    ): GoodsReceipt {
        $receipt = $this->create($order, $lines, $user, $note);

        return $this->validate($receipt, $user);
    }

    /**
     * Enregistre une réception en brouillon, sans effet sur le stock.
     *
     * @param  list<array{purchase_order_item_id: int, quantity: int|float|string}>  $lines
     */
    public function create(
        PurchaseOrder $order,
        array $lines,
        ?User $user = null,
        ?string $note = null,
    ): GoodsReceipt {
        return DB::transaction(function () use ($order, $lines, $user, $note): GoodsReceipt {
            if (! $order->status->isReceivable()) {
                throw PurchaseOrderNotReceivableException::for($order);
            }

            $order->load('items');
            /** @var Collection<int, PurchaseOrderItem> $indexed */
            $indexed = $order->items->keyBy('id');

            $receipt = GoodsReceipt::create([
                'bakery_id' => $order->bakery_id,
                'purchase_order_id' => $order->getKey(),
                'created_by' => $user?->getKey(),
                'reference' => $this->nextReceiptReference(),
                'status' => GoodsReceiptStatus::Draft,
                'received_at' => now(),
                'note' => $note,
            ]);

            $hasLine = false;

            foreach ($lines as $line) {
                $orderItem = $indexed->get((int) ($line['purchase_order_item_id'] ?? 0));

                if ($orderItem === null) {
                    continue;
                }

                $quantity = Decimal::abs((string) ($line['quantity'] ?? 0));

                if (! Decimal::isPositive($quantity)) {
                    continue;
                }

                $receipt->items()->create([
                    'purchase_order_item_id' => $orderItem->getKey(),
                    'product_id' => $orderItem->product_id,
                    'unit_id' => $orderItem->unit_id,
                    'quantity' => $quantity,
                    'unit_price' => $orderItem->unit_price,
                ]);

                $hasLine = true;
            }

            if (! $hasLine) {
                throw PurchaseOrderNotReceivableException::empty($order);
            }

            return $receipt->load('items');
        });
    }

    /**
     * Valide une réception : crée les entrées de stock, met à jour la
     * commande. Refuse une seconde validation (non rejouable).
     */
    public function validate(GoodsReceipt $receipt, ?User $user = null): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $user): GoodsReceipt {
            $locked = GoodsReceipt::withoutBakeryScope()
                ->whereKey($receipt->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->validated_at !== null || $locked->status->isValidated()) {
                throw GoodsReceiptAlreadyValidatedException::for($locked);
            }

            $order = PurchaseOrder::withoutBakeryScope()
                ->whereKey($locked->purchase_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $bakery = $locked->bakery()->firstOrFail();

            foreach ($locked->items()->get() as $item) {
                $this->movements->apply(
                    bakery: $bakery,
                    product: $item->product()->firstOrFail(),
                    type: StockMovementType::SupplierIn,
                    quantity: (string) $item->quantity,
                    unit: $item->unit,
                    user: $user,
                    idempotencyKey: "goods-receipt:{$locked->getKey()}:item:{$item->getKey()}",
                    reference: $locked->reference,
                    reason: 'Réception fournisseur',
                    source: $locked,
                    occurredAt: $locked->received_at,
                );

                if ($item->purchase_order_item_id !== null) {
                    $this->incrementReceivedQuantity((int) $item->purchase_order_item_id, (string) $item->quantity);
                }
            }

            $locked->status = GoodsReceiptStatus::Validated;
            $locked->validated_at = now();
            $locked->validated_by = $user?->getKey();
            $locked->save();

            $this->orders->recalculateStatus($order);

            return $locked;
        });
    }

    /**
     * Cumule la quantité reçue sur la ligne de commande, en décimal exact
     * et sous verrou pour éviter toute perte de mise à jour.
     */
    private function incrementReceivedQuantity(int $orderItemId, string $quantity): void
    {
        $orderItem = PurchaseOrderItem::query()
            ->whereKey($orderItemId)
            ->lockForUpdate()
            ->first();

        if ($orderItem === null) {
            return;
        }

        $orderItem->received_quantity = Decimal::add((string) $orderItem->received_quantity, $quantity);
        $orderItem->save();
    }

    private function nextReceiptReference(): string
    {
        $prefix = 'GR-'.now()->format('Ym').'-';

        $sequence = GoodsReceipt::withoutBakeryScope()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (GoodsReceipt::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }
}
