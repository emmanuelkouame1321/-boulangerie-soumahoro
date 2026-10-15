<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\ReportingService;
use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Models\Bakery;
use App\Models\StockAlert;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableaux de bord (Design System §15, §16, §17).
 *
 * Trois vues selon le rôle : consolidée (Super-Admin), production et
 * vente. Le contrôleur ne fait que rassembler des agrégats de
 * ReportingService ; il ne calcule aucune règle métier.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting,
    ) {}

    public function index(Request $request, BakeryContext $context): View
    {
        $bakeryId = $context->allowsAllSites()
            ? $context->current()?->getKey()
            : $context->current()?->getKey();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $year = (int) now()->year;

        $user = $context->user();
        $mode = match (true) {
            $user?->hasRole(Role::SuperAdmin->value) === true => 'super-admin',
            $user?->can('production.view') === true => 'production',
            default => 'sales',
        };

        $sales = $this->reporting->sales($bakeryId, $today, $today);
        $production = $this->reporting->production($bakeryId, $today, $today);
        $stock = $this->reporting->stock($bakeryId);

        $alerts = StockAlert::query()
            ->with('product')
            ->where('status', StockAlertStatus::Open->value)
            ->orderByDesc('triggered_at')
            ->limit(6)
            ->get();

        $expiringCount = StockAlert::query()
            ->where('status', StockAlertStatus::Open->value)
            ->whereIn('type', [StockAlertType::ExpirySoon->value, StockAlertType::Expired->value])
            ->count();

        return view('dashboard.index', [
            'mode' => $mode,
            'bakery' => $context->current(),
            'today' => $today,
            'salesToday' => $sales,
            'productionToday' => $production,
            'stock' => $stock,
            'alerts' => $alerts,
            'openAlertCount' => StockAlert::query()->where('status', StockAlertStatus::Open->value)->count(),
            'expiringCount' => $expiringCount,
            'trend' => $this->reporting->revenueTrend($bakeryId, 7),
            'monthRevenue' => $this->reporting->sales($bakeryId, $monthStart, $today)['revenue'],
            'topProducts' => $this->reporting->sales($bakeryId, $today, $today)['by_product'],
            'payrollNet' => $this->reporting->payroll($bakeryId, $year)['total_net'],
            'expensesMonth' => $this->reporting->expenses($bakeryId, $monthStart, $today)['total'],
            'lossesMonth' => $this->reporting->losses($bakeryId, $monthStart, $today)['value'],
            'siteRevenue' => $this->siteRevenue($context, $today),
        ]);
    }

    /**
     * Chiffre d'affaires du jour par site (vision consolidée).
     *
     * Le chiffre d'affaires est agrégé en une requête pour tous les sites
     * (cahier §48) : le coût ne dépend pas du nombre de boulangeries.
     *
     * @return list<array{name: string, revenue: int}>
     */
    private function siteRevenue(BakeryContext $context, string $date): array
    {
        if (! $context->allowsAllSites() || $context->current() !== null) {
            return [];
        }

        $revenues = $this->reporting->revenueForDayBySite($date);

        return Bakery::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Bakery $bakery): array => [
                'name' => $bakery->name,
                'revenue' => $revenues[$bakery->getKey()] ?? 0,
            ])
            ->all();
    }
}
