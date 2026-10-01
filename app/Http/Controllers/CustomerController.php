<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Sites\BakeryContext;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Bakery;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Clients (cahier §14).
 *
 * Contrôleur mince : autorisation par CustomerPolicy, validation par Form
 * Request. Aucun client n'est supprimé physiquement (désactivation ou
 * suppression douce).
 */
class CustomerController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
            ->with('bakery')
            ->withCount('sales')
            ->search($search)
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
            'canManage' => $request->user()?->can('create', Customer::class) ?? false,
        ]);
    }

    public function create(BakeryContext $context): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', [
            'bakeryOptions' => $context->availableBakeries()->pluck('name', 'id')->all(),
            'currentBakeryId' => $context->current()?->getKey(),
        ]);
    }

    public function store(StoreCustomerRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $data = $request->validated();

        $bakery = $this->resolveBakery($context);

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de créer le client.');
        }

        $customer = Customer::create([
            'bakery_id' => $bakery->getKey(),
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'note' => $data['note'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()
            ->route('customers.index')
            ->with('status', "Le client « {$customer->name} » a été créé.");
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', Customer::class);

        return view('customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        $data = $request->validated();

        $customer->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'note' => $data['note'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $customer->is_active),
        ])->save();

        return redirect()
            ->route('customers.index')
            ->with('status', "Le client « {$customer->name} » a été mis à jour.");
    }

    private function resolveBakery(BakeryContext $context): ?Bakery
    {
        return $context->current() ?? $context->availableBakeries()->first();
    }
}
