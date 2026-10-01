<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Customers\WholesalerRebateService;
use App\Domain\Sites\BakeryContext;
use App\Http\Requests\StoreWholesalerRequest;
use App\Http\Requests\UpdateWholesalerRequest;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Wholesaler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Grossistes (cahier §12).
 *
 * Contrôleur mince : autorisation par WholesalerPolicy, validation par Form
 * Request, et calcul de ristourne délégué à WholesalerRebateService. La
 * fiche grossiste agrège l'historique réel (quantités, CA, régularité).
 */
class WholesalerController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly WholesalerRebateService $rebates,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Wholesaler::class);

        $search = trim((string) $request->query('search', ''));
        $active = (string) $request->query('active', '');

        $wholesalers = Wholesaler::query()
            ->with('bakery')
            ->withCount('rebates')
            ->search($search)
            ->when($active === '1', fn ($query) => $query->where('is_active', true))
            ->when($active === '0', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('wholesalers.index', [
            'wholesalers' => $wholesalers,
            'search' => $search,
            'active' => $active,
            'activeCount' => Wholesaler::query()->where('is_active', true)->count(),
            'canManage' => $request->user()?->can('create', Wholesaler::class) ?? false,
        ]);
    }

    public function create(BakeryContext $context): View
    {
        $this->authorize('create', Wholesaler::class);

        return view('wholesalers.create', [
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
            'customerOptions' => Customer::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'priceModes' => (array) config('boulangerie.wholesaler.price_modes'),
            'paymentConditions' => (array) config('boulangerie.wholesaler_conditions'),
            'wholesaler' => null,
        ]);
    }

    public function store(StoreWholesalerRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', Wholesaler::class);

        $bakery = $this->resolveBakery($context);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de créer le grossiste.');
        }

        $wholesaler = Wholesaler::create($this->attributes($request->validated(), $bakery));

        return redirect()
            ->route('wholesalers.show', $wholesaler)
            ->with('status', "Le grossiste « {$wholesaler->name} » a été créé.");
    }

    public function show(Request $request, Wholesaler $wholesaler): View
    {
        $this->authorize('view', Wholesaler::class);

        $year = (int) $request->query('year', (string) now()->year);

        $aggregate = $this->rebates->aggregate($wholesaler, $year);

        $recentSales = $wholesaler->customer_id === null
            ? collect()
            : Sale::query()
                ->where('customer_id', $wholesaler->customer_id)
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->limit(10)
                ->get();

        return view('wholesalers.show', [
            'wholesaler' => $wholesaler,
            'year' => $year,
            'aggregate' => $aggregate,
            'orderCount' => $this->rebates->orderCount($wholesaler, $year),
            'rebate' => $this->rebates->find($wholesaler, $year),
            'recentSales' => $recentSales,
            'canManage' => $request->user()?->can('update', Wholesaler::class) ?? false,
        ]);
    }

    public function edit(Wholesaler $wholesaler): View
    {
        $this->authorize('update', Wholesaler::class);

        return view('wholesalers.edit', [
            'wholesaler' => $wholesaler,
            'bakeryOptions' => [$wholesaler->bakery_id => $wholesaler->bakery->name],
            'currentBakeryId' => $wholesaler->bakery_id,
            'customerOptions' => Customer::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'priceModes' => (array) config('boulangerie.wholesaler.price_modes'),
            'paymentConditions' => (array) config('boulangerie.wholesaler_conditions'),
        ]);
    }

    public function update(UpdateWholesalerRequest $request, Wholesaler $wholesaler): RedirectResponse
    {
        $this->authorize('update', Wholesaler::class);

        $wholesaler->fill($this->attributes($request->validated(), $wholesaler->bakery, false))->save();

        return redirect()
            ->route('wholesalers.show', $wholesaler)
            ->with('status', "Le grossiste « {$wholesaler->name} » a été mis à jour.");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Bakery $bakery, bool $withBakery = true): array
    {
        $attributes = [
            'customer_id' => $data['customer_id'] ?? null,
            'name' => $data['name'],
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'zone' => $data['zone'] ?? null,
            'client_type' => $data['client_type'] ?? null,
            'price_mode' => $data['price_mode'],
            'usual_quantity' => $data['usual_quantity'],
            'payment_condition' => $data['payment_condition'] ?? null,
            'joined_on' => $data['joined_on'] ?? null,
            'note' => $data['note'] ?? null,
        ];

        if ($withBakery) {
            $attributes['bakery_id'] = $bakery?->getKey();
            $attributes['is_active'] = (bool) ($data['is_active'] ?? true);
        } else {
            $attributes['is_active'] = (bool) ($data['is_active'] ?? false);
        }

        return $attributes;
    }

    private function resolveBakery(BakeryContext $context): ?Bakery
    {
        return $context->current() ?? $context->availableBakeries()->first();
    }
}
