<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\StockService;
use App\Domain\Sites\BakeryContext;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran Stocks — stock temps réel par produit et par site (cahier §9 et
 * §44).
 *
 * Contrôleur mince : le statut d'un stock est dérivé du seuil du produit
 * ; les requêtes héritent du scoping par site (fail-closed hors site
 * courant pour un non Super-Admin).
 */
class InventoryController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly StockService $stocks,
    ) {}

    public function index(Request $request, BakeryContext $context): View
    {
        $this->authorize('viewAny', Stock::class);

        $search = trim((string) $request->query('search', ''));
        $state = (string) $request->query('state', '');

        $base = Stock::query()
            ->with(['product.primaryUnit', 'product.category', 'bakery'])
            ->whereHas('product', function (Builder $query) use ($search): void {
                $this->applyProductSearch($query, $search);
            });

        $counts = [
            'tracked' => (clone $base)->count(),
            'low' => (clone $base)
                ->where('quantity', '>', 0)
                ->whereHas('product', fn (Builder $query) => $this->lowStockConstraint($query))
                ->count(),
            'critical' => (clone $base)->where('quantity', '<=', 0)->count(),
            'reserved' => (clone $base)->sum('reserved_quantity'),
        ];

        $query = clone $base;

        match ($state) {
            'critical' => $query->where('quantity', '<=', 0),
            'low' => $query
                ->where('quantity', '>', 0)
                ->whereHas('product', fn (Builder $q) => $this->lowStockConstraint($q)),
            'available' => $query
                ->where('quantity', '>', 0)
                ->where(function (Builder $q): void {
                    $q->whereHas('product', fn (Builder $p) => $p->whereNull('alert_threshold'))
                        ->orWhereHas('product', fn (Builder $p) => $p->whereNotNull('alert_threshold')
                            ->whereColumn('products.alert_threshold', '<', 'stocks.quantity'));
                }),
            default => null,
        };

        $stocks = $query
            ->orderBy(Product::select('name')->whereColumn('products.id', 'stocks.product_id'))
            ->orderBy('bakery_id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Stock $stock): array => [
                'stock' => $stock,
                'product' => $stock->product,
                'quantity' => (string) $stock->quantity,
                'reserved' => (string) $stock->reserved_quantity,
                'available' => $stock->availableQuantity(),
                'status' => $this->stocks->statusFor($stock->product, (string) $stock->quantity),
            ]);

        return view('inventory.index', [
            'stocks' => $stocks,
            'search' => $search,
            'state' => $state,
            'stateOptions' => [
                'available' => 'Disponible',
                'low' => 'Stock faible',
                'critical' => 'Critique',
            ],
            'counts' => $counts,
            'canAdjust' => $request->user()?->can('adjust', Stock::class) ?? false,
            'productOptions' => $this->productOptions(),
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
        ]);
    }

    /**
     * Recherche produit, appliquée dans une relation `product`.
     *
     * @param  Builder<Product>  $query
     */
    private function applyProductSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function lowStockConstraint(Builder $query): void
    {
        $query->whereNotNull('alert_threshold')
            ->whereColumn('products.alert_threshold', '>=', 'stocks.quantity');
    }

    /**
     * @return array<int, string>
     */
    private function productOptions(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->where('type', '!=', ProductType::Resource->value)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
