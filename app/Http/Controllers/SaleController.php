<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Sales\SalesService;
use App\Domain\Sites\BakeryContext;
use App\Enums\ProductType;
use App\Exceptions\EmptySaleException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\LotExpiredException;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Ventes au détail (cahier §14).
 *
 * Contrôleur mince : autorisation par SalePolicy, validation par Form
 * Request, enregistrement par SalesService. Le site cible est vérifié
 * contre les sites accessibles à l'utilisateur. Un stock insuffisant est
 * rendu sous forme de message clair.
 */
class SaleController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly SalesService $sales,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $search = trim((string) $request->query('search', ''));
        $date = (string) $request->query('date', '');

        $sales = Sale::query()
            ->with(['customer', 'bakery', 'createdBy'])
            ->withCount('items')
            ->search($search)
            ->when($date !== '', fn ($query) => $query->whereDate('sale_date', $date))
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'search' => $search,
            'date' => $date,
            'canCreate' => $request->user()?->can('create', Sale::class) ?? false,
            'paymentMethods' => PaymentMethod::query()->active()->ordered()->pluck('label', 'code')->all(),
        ]);
    }

    public function create(BakeryContext $context): View
    {
        $this->authorize('create', Sale::class);

        return view('sales.create', [
            'customerOptions' => Customer::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'productOptions' => Product::query()
                ->active()
                ->where('type', ProductType::FinishedGood->value)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(static fn (Product $product): array => [
                    $product->getKey() => "{$product->name} — ".Format::money($product->sale_price),
                ])
                ->all(),
            'paymentMethods' => PaymentMethod::query()->active()->ordered()->pluck('label', 'code')->all(),
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
        ]);
    }

    public function store(StoreSaleRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', Sale::class);

        $data = $request->validated();

        $bakery = $this->resolveBakery($context, $data['bakery_id'] ?? null);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant d’enregistrer la vente.');
        }

        $customer = isset($data['customer_id']) ? Customer::query()->find((int) $data['customer_id']) : null;

        /** @var list<array{product_id: int, quantity: int|float|string, unit_price?: int|null, discount?: int|null}> $lines */
        $lines = array_values(array_map(static fn (array $item): array => [
            'product_id' => (int) $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => isset($item['unit_price']) ? (int) $item['unit_price'] : null,
            'discount' => isset($item['discount']) ? (int) $item['discount'] : null,
        ], $data['items']));

        $user = $request->user();

        try {
            $sale = $this->sales->record(
                bakery: $bakery,
                lines: $lines,
                customer: $customer,
                paymentMethod: $data['payment_method'] ?? null,
                paidAmount: isset($data['paid_amount']) ? (int) $data['paid_amount'] : null,
                user: $user instanceof User ? $user : null,
                soldAt: isset($data['sold_at']) ? Carbon::parse($data['sold_at']) : null,
                note: $data['note'] ?? null,
            );
        } catch (InsufficientStockException|EmptySaleException|LotExpiredException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', "La vente {$sale->reference} a été enregistrée.");
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', Sale::class);

        $sale->load(['customer', 'bakery', 'createdBy', 'items.product.primaryUnit', 'items.unit', 'payments']);

        return view('sales.show', [
            'sale' => $sale,
        ]);
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
