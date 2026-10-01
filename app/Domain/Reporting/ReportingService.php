<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Enums\StockMovementType;
use App\Support\Decimal;
use App\Support\Format;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rapports et pilotage (cahier §33 et §55).
 *
 * Toutes les agrégations sont faites côté base et respectent l'isolation
 * par site : un `$bakeryId` nul signifie « tous les sites » (vision
 * consolidée du Super-Admin). Les montants sont des entiers FCFA.
 */
final class ReportingService
{
    /**
     * Ventes (cahier §33).
     *
     * @return array{revenue: int, sales: int, quantity: string, retail_revenue: int, wholesale_revenue: int, by_product: list<array{name: string, quantity: string, revenue: int}>, daily: list<array{date: string, revenue: int}>}
     */
    public function sales(?int $bakeryId, string $from, string $to): array
    {
        $totals = $this->salesQuery($bakeryId, $from, $to)
            ->selectRaw('COALESCE(SUM(total), 0) AS revenue')
            ->selectRaw('COUNT(*) AS sales')
            ->first();

        $items = fn (): Builder => DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Validated->value)
            ->whereDate('sales.sale_date', '>=', $from)
            ->whereDate('sales.sale_date', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('sales.bakery_id', $bakeryId));

        $quantity = Decimal::trim(Decimal::normalize((string) $items()->sum('sale_items.quantity')));

        $byProduct = $items()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->selectRaw('products.name AS name')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) AS quantity')
            ->selectRaw('COALESCE(SUM(sale_items.line_total), 0) AS revenue')
            ->groupBy('products.name')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'quantity' => Decimal::trim(Decimal::normalize((string) $row->quantity)),
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        $daily = $this->salesQuery($bakeryId, $from, $to)
            ->selectRaw('sale_date AS date')
            ->selectRaw('COALESCE(SUM(total), 0) AS revenue')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->map(static fn (object $row): array => [
                'date' => (string) $row->date,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        return [
            'revenue' => (int) data_get($totals, 'revenue', 0),
            'sales' => (int) data_get($totals, 'sales', 0),
            'quantity' => $quantity,
            'retail_revenue' => (int) $this->salesQuery($bakeryId, $from, $to)->where('type', SaleType::Retail->value)->sum('total'),
            'wholesale_revenue' => (int) $this->salesQuery($bakeryId, $from, $to)->where('type', SaleType::Wholesale->value)->sum('total'),
            'by_product' => $byProduct,
            'daily' => $daily,
        ];
    }

    /**
     * Production (cahier §33).
     *
     * @return array{produced: string, expected: string, gap: string, cost: int, orders: int, by_product: list<array{name: string, quantity: string}>}
     */
    public function production(?int $bakeryId, string $from, string $to): array
    {
        $orders = fn (): Builder => DB::table('production_orders')
            ->where('status', 'validated')
            ->whereDate('produced_on', '>=', $from)
            ->whereDate('produced_on', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId));

        $totals = $orders()
            ->selectRaw('COUNT(*) AS orders')
            ->selectRaw('COALESCE(SUM(actual_quantity), 0) AS produced')
            ->selectRaw('COALESCE(SUM(theoretical_quantity), 0) AS expected')
            ->selectRaw('COALESCE(SUM(actual_cost), 0) AS cost')
            ->first();

        $byProduct = DB::table('production_outputs')
            ->join('production_orders', 'production_orders.id', '=', 'production_outputs.production_order_id')
            ->join('products', 'products.id', '=', 'production_outputs.product_id')
            ->where('production_orders.status', 'validated')
            ->whereDate('production_orders.produced_on', '>=', $from)
            ->whereDate('production_orders.produced_on', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('production_orders.bakery_id', $bakeryId))
            ->selectRaw('products.name AS name')
            ->selectRaw('COALESCE(SUM(production_outputs.quantity), 0) AS quantity')
            ->groupBy('products.name')
            ->orderByDesc('quantity')
            ->limit(15)
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'quantity' => Decimal::trim(Decimal::normalize((string) $row->quantity)),
            ])
            ->all();

        $produced = Decimal::trim(Decimal::normalize((string) data_get($totals, 'produced', 0)));
        $expected = Decimal::trim(Decimal::normalize((string) data_get($totals, 'expected', 0)));

        return [
            'produced' => $produced,
            'expected' => $expected,
            'gap' => Decimal::subtract($produced, $expected),
            'cost' => (int) data_get($totals, 'cost', 0),
            'orders' => (int) data_get($totals, 'orders', 0),
            'by_product' => $byProduct,
        ];
    }

    /**
     * Stock (cahier §33).
     *
     * @return array{valuation: int, below_threshold: int, tracked: int, by_product: list<array{name: string, quantity: string, value: int, threshold: int|null}>}
     */
    public function stock(?int $bakeryId): array
    {
        $rows = DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('stocks.bakery_id', $bakeryId))
            ->where('stocks.quantity', '>', 0)
            ->selectRaw('products.name AS name')
            ->selectRaw('stocks.quantity AS quantity')
            ->selectRaw('products.purchase_price AS purchase_price')
            ->selectRaw('products.alert_threshold AS alert_threshold')
            ->orderByDesc('stocks.quantity')
            ->limit(30)
            ->get();

        $valuation = 0;
        $below = 0;
        $byProduct = [];

        foreach ($rows as $row) {
            $quantity = Decimal::normalize((string) $row->quantity);
            $value = Decimal::toFrancs($quantity, (int) $row->purchase_price);
            $threshold = $row->alert_threshold !== null ? (int) $row->alert_threshold : null;

            if ($threshold !== null && Decimal::compare($quantity, (string) $threshold) <= 0) {
                $below++;
            }

            $valuation += $value;

            $byProduct[] = [
                'name' => (string) $row->name,
                'quantity' => Decimal::trim($quantity),
                'value' => $value,
                'threshold' => $threshold,
            ];
        }

        return [
            'valuation' => $valuation,
            'below_threshold' => $below,
            'tracked' => count($byProduct),
            'by_product' => $byProduct,
        ];
    }

    /**
     * Achats (cahier §33).
     *
     * @return array{pending_orders: int, ordered_total: int, by_supplier: list<array{name: string, total: int, orders: int}>, by_product: list<array{name: string, quantity: string, total: int}>}
     */
    public function purchases(?int $bakeryId, string $from, string $to): array
    {
        $orders = fn (): Builder => DB::table('purchase_orders')
            ->whereDate('ordered_at', '>=', $from)
            ->whereDate('ordered_at', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId));

        $bySupplier = DB::table('purchase_orders')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereDate('purchase_orders.ordered_at', '>=', $from)
            ->whereDate('purchase_orders.ordered_at', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('purchase_orders.bakery_id', $bakeryId))
            ->selectRaw('suppliers.name AS name')
            ->selectRaw('COALESCE(SUM(purchase_orders.total), 0) AS total')
            ->selectRaw('COUNT(*) AS orders')
            ->groupBy('suppliers.name')
            ->orderByDesc('total')
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'total' => (int) $row->total,
                'orders' => (int) $row->orders,
            ])
            ->all();

        $byProduct = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('products', 'products.id', '=', 'purchase_order_items.product_id')
            ->whereDate('purchase_orders.ordered_at', '>=', $from)
            ->whereDate('purchase_orders.ordered_at', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('purchase_orders.bakery_id', $bakeryId))
            ->selectRaw('products.name AS name')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity), 0) AS quantity')
            ->selectRaw('COALESCE(SUM(purchase_order_items.line_total), 0) AS total')
            ->groupBy('products.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'quantity' => Decimal::trim(Decimal::normalize((string) $row->quantity)),
                'total' => (int) $row->total,
            ])
            ->all();

        return [
            'pending_orders' => $orders()->whereIn('status', ['ordered', 'pending', 'partially_received'])->count(),
            'ordered_total' => (int) $orders()->sum('total'),
            'by_supplier' => $bySupplier,
            'by_product' => $byProduct,
        ];
    }

    /**
     * Pertes (cahier §33).
     *
     * @return array{value: int, quantity: string, by_reason: list<array{reason: string, value: int, quantity: string}>, by_product: list<array{name: string, quantity: string, value: int}>}
     */
    public function losses(?int $bakeryId, string $from, string $to): array
    {
        $records = fn (): Builder => DB::table('loss_records')
            ->whereDate('occurred_on', '>=', $from)
            ->whereDate('occurred_on', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId));

        $totals = $records()
            ->selectRaw('COALESCE(SUM(total_value), 0) AS value')
            ->selectRaw('COALESCE(SUM(total_quantity), 0) AS quantity')
            ->first();

        $byReason = $records()
            ->selectRaw('reason AS reason')
            ->selectRaw('COALESCE(SUM(total_value), 0) AS value')
            ->selectRaw('COALESCE(SUM(total_quantity), 0) AS quantity')
            ->groupBy('reason')
            ->orderByDesc('value')
            ->get()
            ->map(static fn (object $row): array => [
                'reason' => (string) config("boulangerie.loss_reasons.{$row->reason}", (string) $row->reason),
                'value' => (int) $row->value,
                'quantity' => Decimal::trim(Decimal::normalize((string) $row->quantity)),
            ])
            ->all();

        $byProduct = DB::table('loss_items')
            ->join('loss_records', 'loss_records.id', '=', 'loss_items.loss_record_id')
            ->join('products', 'products.id', '=', 'loss_items.product_id')
            ->whereDate('loss_records.occurred_on', '>=', $from)
            ->whereDate('loss_records.occurred_on', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('loss_records.bakery_id', $bakeryId))
            ->selectRaw('products.name AS name')
            ->selectRaw('COALESCE(SUM(loss_items.quantity), 0) AS quantity')
            ->selectRaw('COALESCE(SUM(loss_items.line_value), 0) AS value')
            ->groupBy('products.name')
            ->orderByDesc('value')
            ->limit(15)
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'quantity' => Decimal::trim(Decimal::normalize((string) $row->quantity)),
                'value' => (int) $row->value,
            ])
            ->all();

        return [
            'value' => (int) data_get($totals, 'value', 0),
            'quantity' => Decimal::trim(Decimal::normalize((string) data_get($totals, 'quantity', 0))),
            'by_reason' => $byReason,
            'by_product' => $byProduct,
        ];
    }

    /**
     * Grossistes (cahier §33) : quantité, CA et ristourne théorique.
     *
     * @return list<array{name: string, quantity: string, revenue: int, rebate: int}>
     */
    public function wholesalers(?int $bakeryId, int $year): array
    {
        $wholesalers = DB::table('wholesalers')
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId))
            ->orderBy('name')
            ->get(['id', 'customer_id', 'name']);

        $rows = [];

        foreach ($wholesalers as $wholesaler) {
            $quantity = '0';
            $revenue = 0;
            $rebate = 0;

            if ($wholesaler->customer_id !== null) {
                $revenue = (int) DB::table('sales')
                    ->where('customer_id', $wholesaler->customer_id)
                    ->where('type', SaleType::Wholesale->value)
                    ->where('status', SaleStatus::Validated->value)
                    ->whereYear('sale_date', $year)
                    ->sum('total');

                $quantity = Decimal::trim(Decimal::normalize((string) DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.customer_id', $wholesaler->customer_id)
                    ->where('sales.type', SaleType::Wholesale->value)
                    ->where('sales.status', SaleStatus::Validated->value)
                    ->whereYear('sales.sale_date', $year)
                    ->sum('sale_items.quantity')));

                $rebate = (int) DB::table('wholesaler_rebates')
                    ->where('wholesaler_id', $wholesaler->id)
                    ->where('period_year', $year)
                    ->max('theoretical_value');
            }

            $rows[] = [
                'name' => (string) $wholesaler->name,
                'quantity' => $quantity,
                'revenue' => $revenue,
                'rebate' => $rebate,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['revenue'] <=> $a['revenue']);

        return $rows;
    }

    /**
     * Charges (cahier §33).
     *
     * @return array{total: int, by_category: list<array{name: string, total: int, count: int}>, monthly: list<array{month: string, total: int}>}
     */
    public function expenses(?int $bakeryId, string $from, string $to): array
    {
        $query = fn (): Builder => DB::table('expenses')
            ->whereDate('spent_on', '>=', $from)
            ->whereDate('spent_on', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId));

        $byCategory = DB::table('expenses')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereDate('expenses.spent_on', '>=', $from)
            ->whereDate('expenses.spent_on', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('expenses.bakery_id', $bakeryId))
            ->selectRaw("COALESCE(expense_categories.name, 'Non classé') AS name")
            ->selectRaw('COALESCE(SUM(expenses.amount), 0) AS total')
            ->selectRaw('COUNT(*) AS count')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'total' => (int) $row->total,
                'count' => (int) $row->count,
            ])
            ->all();

        $start = Carbon::parse($from)->startOfMonth();
        $end = Carbon::parse($to)->startOfMonth();
        $monthly = [];

        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addMonth()) {
            $monthly[] = [
                'month' => $cursor->format('m/Y'),
                'total' => (int) $query()
                    ->whereYear('spent_on', $cursor->year)
                    ->whereMonth('spent_on', $cursor->month)
                    ->sum('amount'),
            ];
        }

        return [
            'total' => (int) $query()->sum('amount'),
            'by_category' => $byCategory,
            'monthly' => $monthly,
        ];
    }

    /**
     * Paie (cahier §33).
     *
     * @return array{total_net: int, total_base: int, periods: list<array{label: string, net: int, status: string}>}
     */
    public function payroll(?int $bakeryId, int $year): array
    {
        $query = fn (): Builder => DB::table('payroll_periods')
            ->where('period_year', $year)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId));

        $periods = $query()
            ->orderBy('period_month')
            ->get(['period_month', 'period_year', 'total_net', 'status'])
            ->map(static fn (object $row): array => [
                'label' => sprintf('%02d/%d', (int) $row->period_month, (int) $row->period_year),
                'net' => (int) $row->total_net,
                'status' => (string) $row->status,
            ])
            ->all();

        return [
            'total_net' => (int) $query()->sum('total_net'),
            'total_base' => (int) $query()->sum('total_base'),
            'periods' => $periods,
        ];
    }

    /**
     * Réponses aux 17 questions du cahier §55.
     *
     * @return list<array{question: string, answer: string}>
     */
    public function questions(?int $bakeryId): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $year = (int) now()->year;

        $stock = $this->stock($bakeryId);
        $sales = $this->sales($bakeryId, $monthStart, $today);
        $production = $this->production($bakeryId, $today, $today);
        $losses = $this->losses($bakeryId, $monthStart, $today);
        $expenses = $this->expenses($bakeryId, $monthStart, $today);
        $purchases = $this->purchases($bakeryId, $monthStart, $today);
        $wholesalers = $this->wholesalers($bakeryId, $year);

        $topWholesaler = $wholesalers[0] ?? null;

        $consumption = Decimal::trim(Decimal::normalize((string) DB::table('stock_movements')
            ->where('type', StockMovementType::ProductionOut->value)
            ->whereBetween('occurred_at', [$monthStart.' 00:00:00', $today.' 23:59:59'])
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId))
            ->sum('quantity')));

        return [
            ['question' => 'Combien reste-t-il de matière première en stock ?', 'answer' => Format::stock($stock['by_product'][0]['quantity'] ?? '0').' pour le premier produit — valorisation '.Format::money($stock['valuation'])],
            ['question' => 'Quelle quantité est en commande ?', 'answer' => Format::number($purchases['pending_orders']).' commande(s) en attente pour '.Format::money($purchases['ordered_total'])],
            ['question' => 'Quelles matières sont sous le seuil ?', 'answer' => Format::number($stock['below_threshold']).' produit(s) au seuil ou en dessous'],
            ['question' => "Combien d'unités ont été produites aujourd'hui ?", 'answer' => Format::stock($production['produced'])],
            ['question' => "Combien d'unités devraient être produites ?", 'answer' => Format::stock($production['expected'])],
            ['question' => 'Combien ont réellement été produites ?', 'answer' => Format::stock($production['produced']).' (écart '.Format::signed($production['gap']).')'],
            ['question' => 'Quelle quantité a été vendue ?', 'answer' => Format::stock($sales['quantity']).' sur '.Format::number($sales['sales']).' vente(s)'],
            ['question' => 'Quelle quantité est restée ?', 'answer' => Format::stock($stock['by_product'][0]['quantity'] ?? '0').' pour le premier produit suivi'],
            ['question' => 'Quelle quantité est devenue une perte ?', 'answer' => Format::stock($losses['quantity']).' pour une valeur de '.Format::money($losses['value'])],
            ['question' => 'Quel est le coût de production ?', 'answer' => Format::money($production['cost'])],
            ['question' => 'Quel grossiste a pris le plus de volume cette année ?', 'answer' => $topWholesaler === null ? 'Aucune vente grossiste' : $topWholesaler['name'].' — '.Format::money($topWholesaler['revenue'])],
            ['question' => 'Quelle ristourne est applicable ?', 'answer' => $topWholesaler === null ? '—' : Format::money($topWholesaler['rebate'])],
            ['question' => "Combien ont coûté l'électricité, l'eau et les autres charges ?", 'answer' => Format::money($expenses['total'])],
            ['question' => "Quel est le chiffre d'affaires de chaque boulangerie ?", 'answer' => Format::money($sales['revenue'])],
            ['question' => 'Quelle est la consommation de matières premières ?', 'answer' => Format::stock($consumption)],
            ['question' => 'Quelles opérations ont été effectuées par chaque utilisateur ?', 'answer' => Format::number((int) DB::table('stock_movements')->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId))->distinct()->count('user_id')).' utilisateur(s) ont mouvementé le stock'],
            ['question' => 'Quelle est la situation consolidée de toutes les boulangeries ?', 'answer' => 'CA '.Format::money($sales['revenue']).' • pertes '.Format::money($losses['value']).' • charges '.Format::money($expenses['total'])],
        ];
    }

    /**
     * Chiffre d'affaires d'un site pour une journée (tableaux de bord).
     */
    public function revenueForDay(?int $bakeryId, string $date): int
    {
        return (int) $this->salesQuery($bakeryId, $date, $date)->sum('total');
    }

    /**
     * Chiffre d'affaires des N derniers jours (graphique du tableau de bord).
     *
     * @return list<array{date: string, revenue: int}>
     */
    public function revenueTrend(?int $bakeryId, int $days = 7): array
    {
        $trend = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = now()->subDays($offset);
            $trend[] = [
                'date' => $day->toDateString(),
                'revenue' => $this->revenueForDay($bakeryId, $day->toDateString()),
            ];
        }

        return $trend;
    }

    private function salesQuery(?int $bakeryId, string $from, string $to): Builder
    {
        return DB::table('sales')
            ->where('status', SaleStatus::Validated->value)
            ->whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to)
            ->when($bakeryId !== null, fn (Builder $q): Builder => $q->where('bakery_id', $bakeryId));
    }
}
