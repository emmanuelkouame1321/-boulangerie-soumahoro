<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fournisseurs (cahier §10).
 *
 * Contrôleur mince : autorisation par SupplierPolicy, validation par
 * Form Requests, persistance par Eloquent. Un fournisseur est désactivé,
 * jamais supprimé.
 */
class SupplierController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $search = trim((string) $request->query('search', ''));
        $state = (string) $request->query('state', '');

        $query = Supplier::query()
            ->search($search)
            ->withCount('purchaseOrders');

        if ($state === 'active') {
            $query->where('is_active', true);
        } elseif ($state === 'inactive') {
            $query->where('is_active', false);
        }

        $suppliers = $query
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'search' => $search,
            'state' => $state,
            'stateOptions' => ['active' => 'Actifs', 'inactive' => 'Inactifs'],
            'canManage' => $request->user()?->can('create', Supplier::class) ?? false,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('suppliers.create', [
            'supplier' => new Supplier(['is_active' => true]),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('status', "Le fournisseur « {$supplier->name} » a été créé.");
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', Supplier::class);

        return view('suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('status', "Le fournisseur « {$supplier->name} » a été mis à jour.");
    }
}
