<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Purchasing\PurchaseOrderService;
use App\Domain\Sites\BakeryContext;
use App\Enums\ProductType;
use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Commandes fournisseur (cahier §11).
 *
 * Contrôleur mince : autorisation par PurchaseOrderPolicy, validation par
 * Form Request, création par PurchaseOrderService. Le site cible est
 * vérifié contre les sites accessibles à l'utilisateur.
 */
class PurchaseOrderController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly PurchaseOrderService $orders,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $statusEnum = PurchaseOrderStatus::tryFrom($status);

        $orders = PurchaseOrder::query()
            ->with(['supplier', 'bakery'])
            ->withCount('items')
            ->search($search)
            ->when($statusEnum !== null, fn ($query) => $query->where('status', $statusEnum->value))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('purchasing.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'statusOptions' => $this->statusOptions(),
            'canCreate' => $request->user()?->can('create', PurchaseOrder::class) ?? false,
        ]);
    }

    public function create(BakeryContext $context): View
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchasing.orders.create', [
            'supplierOptions' => $this->supplierOptions(),
            'productOptions' => $this->productOptions(),
            'unitOptions' => $this->unitOptions(),
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, BakeryContext $context): RedirectResponse
    {
        $data = $request->validated();

        $bakery = $this->resolveBakery($context, $data['bakery_id'] ?? null);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de créer la commande.');
        }

        $supplier = Supplier::query()->findOrFail((int) $data['supplier_id']);

        /** @var list<array{product_id: int, quantity: int|float|string, unit_price?: int, unit_id?: int|null}> $lines */
        $lines = array_values(array_map(static fn (array $item): array => [
            'product_id' => (int) $item['product_id'],
            'unit_id' => isset($item['unit_id']) ? (int) $item['unit_id'] : null,
            'quantity' => $item['quantity'],
            'unit_price' => (int) ($item['unit_price'] ?? 0),
        ], $data['items']));

        $user = $request->user();

        $order = $this->orders->create(
            bakery: $bakery,
            supplier: $supplier,
            lines: $lines,
            user: $user instanceof User ? $user : null,
            note: $data['note'] ?? null,
            expectedAt: isset($data['expected_at']) ? Carbon::parse($data['expected_at']) : null,
        );

        return redirect()
            ->route('purchasing.orders.show', $order)
            ->with('status', "La commande {$order->reference} a été créée.");
    }

    public function show(PurchaseOrder $order): View
    {
        $this->authorize('view', PurchaseOrder::class);

        $order->load([
            'supplier',
            'bakery',
            'items.product.primaryUnit',
            'items.unit',
            'receipts' => fn ($query) => $query->orderByDesc('received_at'),
        ]);

        return view('purchasing.orders.show', [
            'order' => $order,
            'canReceive' => $order->isReceivable(),
            'canCancel' => $order->status->isReceivable(),
        ]);
    }

    public function cancel(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('cancel', PurchaseOrder::class);

        if ($order->status->isClosed()) {
            return back()->with('error', 'Cette commande est déjà clôturée.');
        }

        $this->orders->cancel($order);

        return redirect()
            ->route('purchasing.orders.show', $order)
            ->with('status', "La commande {$order->reference} a été annulée.");
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        $options = [];

        foreach (PurchaseOrderStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function supplierOptions(): array
    {
        return Supplier::query()
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
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

    /**
     * @return array<int, string>
     */
    private function unitOptions(): array
    {
        return Unit::query()
            ->active()
            ->ordered()
            ->get()
            ->pluck('label', 'id')
            ->all();
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
