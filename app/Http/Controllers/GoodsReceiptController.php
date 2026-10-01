<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Purchasing\GoodsReceiptService;
use App\Exceptions\GoodsReceiptAlreadyValidatedException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PurchaseOrderNotReceivableException;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Réception fournisseur (cahier §11.3).
 *
 * Le contrôleur ne fait que relayer vers GoodsReceiptService, qui crée les
 * mouvements de stock dans une transaction et refuse toute double
 * validation. Aucune quantité n'est écrite directement ici.
 */
class GoodsReceiptController extends Controller
{
    public function __construct(
        private readonly GoodsReceiptService $receipts,
    ) {}

    public function store(StoreGoodsReceiptRequest $request, PurchaseOrder $order): RedirectResponse
    {
        /** @var list<array{purchase_order_item_id: int, quantity: int|float|string}> $lines */
        $lines = array_values(array_map(static fn (array $item): array => [
            'purchase_order_item_id' => (int) $item['purchase_order_item_id'],
            'quantity' => $item['quantity'],
        ], $request->validated('items')));

        $user = $request->user();

        try {
            $receipt = $this->receipts->receive(
                order: $order,
                lines: $lines,
                user: $user instanceof User ? $user : null,
                note: $request->validated('note'),
            );
        } catch (GoodsReceiptAlreadyValidatedException|PurchaseOrderNotReceivableException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('purchasing.orders.show', $order)
            ->with('status', "Réception {$receipt->reference} validée : le stock a été mis à jour.");
    }
}
