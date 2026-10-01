<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Sites\BakeryContext;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Correction manuelle d'un stock (cahier §3.2 et §3.3).
 *
 * Aucune écriture directe : l'ajustement devient un mouvement de stock
 * daté, motivé et attribué à son auteur.
 */
class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockMovementService $movements,
    ) {}

    public function store(StoreStockAdjustmentRequest $request, BakeryContext $context): RedirectResponse
    {
        $data = $request->validated();

        $bakery = $this->resolveBakery($context, $data['bakery_id'] ?? null);

        if ($bakery === null) {
            return back()->with('error', 'Sélectionnez une boulangerie avant d’ajuster un stock.');
        }

        $product = Product::query()->findOrFail((int) $data['product_id']);
        $type = StockMovementType::from((string) $data['type']);
        $user = $request->user();

        try {
            $this->movements->apply(
                bakery: $bakery,
                product: $product,
                type: $type,
                quantity: (string) $data['quantity'],
                user: $user instanceof User ? $user : null,
                reference: $data['reference'] ?? null,
                reason: (string) $data['reason'],
                comment: $data['comment'] ?? null,
            );
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventory.index')
            ->with('status', sprintf(
                'Ajustement enregistré : %s de %s sur « %s ».',
                $type->label(),
                $product->name,
                $bakery->name
            ));
    }

    /**
     * Le site cible doit figurer parmi ceux que l'utilisateur peut ouvrir :
     * un identifiant venu du navigateur n'est jamais fait confiance.
     */
    private function resolveBakery(BakeryContext $context, mixed $bakeryId): ?Bakery
    {
        $available = $context->availableBakeries();

        $targetId = $bakeryId !== null
            ? (int) $bakeryId
            : $context->current()?->getKey();

        if ($targetId === null) {
            return null;
        }

        return $available->firstWhere('id', $targetId);
    }
}
