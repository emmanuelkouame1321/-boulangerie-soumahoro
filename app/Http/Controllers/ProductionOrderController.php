<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\StockService;
use App\Domain\Production\ProductionService;
use App\Domain\Production\RecipeCalculator;
use App\Domain\Sites\BakeryContext;
use App\Enums\ProductionOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingGapReasonException;
use App\Exceptions\ProductionAlreadyValidatedException;
use App\Http\Requests\StoreProductionRequest;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Productions (cahier §7).
 *
 * Contrôleur mince : autorisation par ProductionOrderPolicy, validation par
 * Form Request, exécution par ProductionService. Le site cible est vérifié
 * contre les sites accessibles à l'utilisateur. Les erreurs métier
 * (matières insuffisantes, écart non motivé) sont rendues à l'utilisateur
 * sous forme de message clair, jamais de trace technique.
 */
class ProductionOrderController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly ProductionService $production,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProductionOrder::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $statusEnum = ProductionOrderStatus::tryFrom($status);

        $orders = ProductionOrder::query()
            ->with(['recipe.product', 'bakery'])
            ->withCount('items')
            ->search($search)
            ->when($statusEnum !== null, fn ($query) => $query->where('status', $statusEnum->value))
            ->orderByDesc('produced_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('production.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'statusOptions' => $this->statusOptions(),
            'canCreate' => $request->user()?->can('create', ProductionOrder::class) ?? false,
        ]);
    }

    public function create(Request $request, BakeryContext $context): View
    {
        $this->authorize('create', ProductionOrder::class);

        $recipes = Recipe::query()
            ->active()
            ->with(['product.primaryUnit', 'items.product.primaryUnit', 'items.unit'])
            ->orderBy('name')
            ->get();

        $recipeId = (int) $request->query('recipe_id', 0);
        $batches = (string) $request->query('batch_count', '1');

        $selected = $recipes->firstWhere('id', $recipeId);

        return view('production.create', [
            'recipes' => $recipes,
            'recipeOptions' => $recipes->pluck('name', 'id')->all(),
            'selectedRecipe' => $selected,
            'batches' => Decimal::isPositive($batches) ? $batches : '1',
            'preview' => $selected !== null ? $this->preview($selected, $batches, $context) : null,
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
            'gapReasons' => config('boulangerie.production_gap_reasons', []),
        ]);
    }

    /**
     * Aperçu serveur des besoins : matières calculées par RecipeCalculator
     * et stock disponible par ressource. Aucun calcul dans la vue.
     *
     * @return array{
     *     requirements: list<array{label: string, mode: string, quantity: string, unit: string|null, cost: int, available: string|null, sufficient: bool|null}>,
     *     theoretical_quantity: string,
     *     theoretical_cost: int,
     *     output_unit: string|null,
     *     can_produce: bool
     * }
     */
    private function preview(Recipe $recipe, string $batches, BakeryContext $context): array
    {
        $calculator = app(RecipeCalculator::class);
        $stocks = app(StockService::class);

        $bakery = $context->current();

        $requirements = [];
        $canProduce = true;

        foreach ($calculator->requirements($recipe, $batches) as $requirement) {
            $product = Product::query()->find($requirement['product_id']);

            if ($product === null) {
                continue;
            }

            $available = null;
            $sufficient = null;

            if ($bakery !== null && $requirement['deduction_mode']->deductsStock()) {
                $available = $stocks->available($product, $bakery);
                $sufficient = Decimal::compare($available, $requirement['planned_quantity']) >= 0;
                $canProduce = $canProduce && $sufficient;
            }

            $requirements[] = [
                'label' => $product->name,
                'mode' => $requirement['deduction_mode']->label(),
                'quantity' => Decimal::trim($requirement['planned_quantity']),
                'unit' => $product->primaryUnit->symbol,
                'cost' => $requirement['line_cost'],
                'available' => $available,
                'sufficient' => $sufficient,
            ];
        }

        return [
            'requirements' => $requirements,
            'theoretical_quantity' => Decimal::trim($calculator->theoreticalQuantity($recipe, $batches)),
            'theoretical_cost' => $calculator->theoreticalCost($recipe, $batches),
            'output_unit' => $recipe->product?->primaryUnit?->symbol,
            'can_produce' => $canProduce,
        ];
    }

    public function store(StoreProductionRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', ProductionOrder::class);

        $data = $request->validated();

        $bakery = $this->resolveBakery($context, $data['bakery_id'] ?? null);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de lancer la production.');
        }

        $recipe = Recipe::query()
            ->with('items.product')
            ->findOrFail((int) $data['recipe_id']);

        $user = $request->user();

        try {
            $order = $this->production->produce(
                bakery: $bakery,
                recipe: $recipe,
                batches: $data['batch_count'],
                actualQuantity: $data['actual_quantity'],
                gapReason: $data['gap_reason'] ?? null,
                user: $user instanceof User ? $user : null,
                producedAt: isset($data['produced_on']) ? Carbon::parse($data['produced_on']) : null,
                note: $data['note'] ?? null,
            );
        } catch (InsufficientStockException|MissingGapReasonException|ProductionAlreadyValidatedException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('production.show', $order)
            ->with('status', "La production {$order->reference} a été validée.");
    }

    public function show(ProductionOrder $order): View
    {
        $this->authorize('view', ProductionOrder::class);

        $order->load([
            'recipe.product.primaryUnit',
            'bakery',
            'items.product.primaryUnit',
            'items.unit',
            'outputs.product.primaryUnit',
            'batches',
        ]);

        return view('production.show', [
            'order' => $order,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        $options = [];

        foreach (ProductionOrderStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

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
