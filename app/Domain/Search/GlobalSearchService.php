<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Sites\BakeryContext;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wholesaler;
use App\Support\Format;

/**
 * Recherche globale de l'ERP (palette de commandes de la topbar).
 *
 * Deux règles non négociables, appliquées **côté serveur** :
 *
 *  1. Permissions — un groupe de résultats n'est interrogé que si
 *     l'utilisateur porte la permission du module correspondant. Le
 *     navigateur ne reçoit jamais de ligne qu'il n'aurait pas le droit
 *     d'ouvrir depuis l'écran correspondant.
 *  2. Isolation par site — les modèles rattachés à une boulangerie
 *     passent par le scope global `BakeryScope` (fail-closed). Seul
 *     `StockTransfer` relie deux sites sans scope global : il est donc
 *     filtré explicitement sur les sites de l'utilisateur.
 *
 * Le service ne renvoie ni modèles ni identifiants techniques : chaque
 * résultat est déjà résolu en titre / sous-titre / méta / URL. Le rendu
 * ne peut donc pas exposer autre chose que ce qui a été filtré ici.
 */
final class GlobalSearchService
{
    /**
     * En deçà de ce nombre de caractères, aucune requête n'est lancée :
     * une lettre seule ramènerait la quasi-totalité des tables.
     */
    public const MIN_TERM_LENGTH = 2;

    /**
     * Longueur maximale du terme retenu (le terme vient du navigateur).
     */
    public const MAX_TERM_LENGTH = 64;

    /**
     * Nombre de résultats affichés par groupe.
     */
    public const PER_GROUP_LIMIT = 5;

    public function __construct(
        private readonly BakeryContext $context,
    ) {}

    /**
     * @return list<array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, meta: string, url: string}>}>
     */
    public function search(string $term, User $user): array
    {
        $term = mb_substr(trim($term), 0, self::MAX_TERM_LENGTH);

        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return [];
        }

        $candidates = [
            $user->can('product.view')
                ? $this->group('products', 'Produits', 'package', $this->products($term))
                : null,
            $user->can('customer.view')
                ? $this->group('customers', 'Clients', 'users-round', $this->customers($term))
                : null,
            $user->can('wholesaler.view')
                ? $this->group('wholesalers', 'Grossistes', 'store', $this->wholesalers($term))
                : null,
            $user->can('supplier.view')
                ? $this->group('suppliers', 'Fournisseurs', 'truck', $this->suppliers($term))
                : null,
            $user->can('employee.view')
                ? $this->group('employees', 'Employés', 'user-round', $this->employees($term))
                : null,
            $user->can('sales.view')
                ? $this->group('sales', 'Ventes', 'receipt', $this->sales($term))
                : null,
            $user->can('transfer.view')
                ? $this->group('transfers', 'Transferts', 'boxes', $this->transfers($term))
                : null,
            $user->can('loss.view')
                ? $this->group('losses', 'Pertes', 'trending-down', $this->losses($term))
                : null,
            $user->can('purchasing.view')
                ? $this->group('purchases', "Commandes d'achat", 'clipboard-list', $this->purchases($term))
                : null,
            $user->can('production.view')
                ? $this->group('production', 'Ordres de production', 'factory', $this->production($term))
                : null,
        ];

        /** @var list<array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, meta: string, url: string}>}> $groups */
        $groups = array_values(array_filter(
            $candidates,
            static fn (?array $group): bool => $group !== null
        ));

        return $groups;
    }

    /**
     * Assemble un groupe de résultats, ou `null` s'il est vide : on
     * n'affiche jamais un intitulé de section sans ligne dessous.
     *
     * @param  list<array{title: string, subtitle: string, meta: string, url: string}>  $items
     * @return array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, meta: string, url: string}>}|null
     */
    private function group(string $key, string $label, string $icon, array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'items' => $items,
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function products(string $term): array
    {
        $products = Product::query()
            ->search($term)
            ->orderBy('name')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($products as $product) {
            $items[] = [
                'title' => $product->name,
                'subtitle' => $product->sku,
                'meta' => $product->type->label(),
                // Le catalogue produits n'a pas d'écran « fiche » : on
                // ouvre la liste déjà filtrée sur le produit trouvé.
                'url' => route('products.index', ['search' => $product->sku]),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function customers(string $term): array
    {
        $customers = Customer::query()
            ->search($term)
            ->orderBy('name')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($customers as $customer) {
            $items[] = [
                'title' => $customer->name,
                'subtitle' => (string) ($customer->phone ?? ''),
                'meta' => (string) ($customer->email ?? ''),
                'url' => route('customers.index', ['search' => $customer->name]),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function wholesalers(string $term): array
    {
        $wholesalers = Wholesaler::query()
            ->search($term)
            ->orderBy('name')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($wholesalers as $wholesaler) {
            $items[] = [
                'title' => $wholesaler->name,
                'subtitle' => (string) ($wholesaler->zone ?? ''),
                'meta' => (string) ($wholesaler->phone ?? ''),
                'url' => route('wholesalers.show', $wholesaler),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function suppliers(string $term): array
    {
        $suppliers = Supplier::query()
            ->search($term)
            ->orderBy('name')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($suppliers as $supplier) {
            $items[] = [
                'title' => $supplier->name,
                'subtitle' => (string) ($supplier->contact_name ?? ''),
                'meta' => (string) ($supplier->phone ?? ''),
                'url' => route('suppliers.index', ['search' => $supplier->name]),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function employees(string $term): array
    {
        $employees = Employee::query()
            ->search($term)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($employees as $employee) {
            $items[] = [
                'title' => $employee->fullName(),
                'subtitle' => $employee->position,
                'meta' => $employee->status->label(),
                'url' => route('employees.show', $employee),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function sales(string $term): array
    {
        $sales = Sale::query()
            ->with('customer')
            ->search($term)
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($sales as $sale) {
            $customer = $sale->customer;

            $items[] = [
                'title' => $sale->reference,
                'subtitle' => $customer === null ? 'Vente comptoir' : $customer->name,
                'meta' => Format::money($sale->total),
                'url' => route('sales.show', $sale),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function transfers(string $term): array
    {
        // Le transfert touche deux sites et n'a pas de scope global :
        // l'isolation est posée ici, sur les deux colonnes.
        $siteIds = $this->context->availableBakeries()->pluck('id')->all();

        if ($siteIds === []) {
            return [];
        }

        $transfers = StockTransfer::query()
            ->involvingAny($siteIds)
            ->with(['sourceBakery', 'destinationBakery'])
            ->search($term)
            ->orderByDesc('transferred_on')
            ->orderByDesc('id')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($transfers as $transfer) {
            $source = $transfer->sourceBakery;
            $destination = $transfer->destinationBakery;

            $items[] = [
                'title' => $transfer->reference,
                'subtitle' => sprintf(
                    '%s → %s',
                    $source === null ? '—' : $source->name,
                    $destination === null ? '—' : $destination->name
                ),
                'meta' => $transfer->status->label(),
                'url' => route('inventory.transfers.show', $transfer),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function losses(string $term): array
    {
        $losses = LossRecord::query()
            ->search($term)
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($losses as $loss) {
            $items[] = [
                'title' => $loss->reference,
                'subtitle' => $loss->reason->label(),
                'meta' => Format::money($loss->total_value),
                'url' => route('losses.show', $loss),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function purchases(string $term): array
    {
        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->search($term)
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($orders as $order) {
            $supplier = $order->supplier;

            $items[] = [
                'title' => $order->reference,
                'subtitle' => $supplier === null ? 'Fournisseur inconnu' : $supplier->name,
                'meta' => $order->status->label(),
                'url' => route('purchasing.orders.show', $order),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, meta: string, url: string}>
     */
    private function production(string $term): array
    {
        $orders = ProductionOrder::query()
            ->with('recipe')
            ->search($term)
            ->orderByDesc('produced_on')
            ->orderByDesc('id')
            ->limit(self::PER_GROUP_LIMIT)
            ->get();

        $items = [];

        foreach ($orders as $order) {
            $recipe = $order->recipe;

            $items[] = [
                'title' => $order->reference,
                'subtitle' => $recipe === null ? 'Recette inconnue' : $recipe->name,
                'meta' => $order->status->label(),
                'url' => route('production.show', $order),
            ];
        }

        return $items;
    }
}
