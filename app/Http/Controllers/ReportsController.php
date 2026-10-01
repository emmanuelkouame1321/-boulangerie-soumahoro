<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\ReportingService;
use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Models\Bakery;
use App\Support\ReportExport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rapports et pilotage (cahier §33, §55 et Design System §83, §84).
 *
 * Le Super-Admin peut choisir la période et le site (ou tous les sites) ;
 * un utilisateur rattaché reste limité à son site courant. Les exports sont
 * sans dépendance : CSV, classeur SpreadsheetML et vue imprimable.
 */
class ReportsController extends Controller
{
    /** @var list<string> */
    private const KINDS = ['sales', 'stock', 'production', 'losses', 'purchases', 'wholesalers', 'expenses', 'payroll'];

    public function __construct(
        private readonly ReportingService $reporting,
    ) {}

    public function index(Request $request, BakeryContext $context): View
    {
        [$bakeryId, $from, $to] = $this->period($request, $context);

        $year = (int) date('Y', (int) strtotime($from));

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'site' => (string) $request->query('site', ''),
            'bakeryOptions' => $this->bakeryOptions($context),
            'reports' => $this->catalogue(),
            'summary' => [
                'revenue' => $this->reporting->sales($bakeryId, $from, $to)['revenue'],
                'sales' => $this->reporting->sales($bakeryId, $from, $to)['sales'],
                'production' => $this->reporting->production($bakeryId, $from, $to)['produced'],
                'losses' => $this->reporting->losses($bakeryId, $from, $to)['value'],
                'expenses' => $this->reporting->expenses($bakeryId, $from, $to)['total'],
                'payroll' => $this->reporting->payroll($bakeryId, $year)['total_net'],
            ],
            'questions' => $this->reporting->questions($bakeryId),
            'trend' => $this->reporting->revenueTrend($bakeryId, 14),
        ]);
    }

    public function show(Request $request, BakeryContext $context, string $kind): View
    {
        abort_unless(in_array($kind, self::KINDS, true), 404);

        [$bakeryId, $from, $to] = $this->period($request, $context);

        return view('reports.show', [
            'kind' => $kind,
            'label' => $this->catalogue()[$kind]['label'],
            'description' => $this->catalogue()[$kind]['description'],
            'from' => $from,
            'to' => $to,
            'site' => (string) $request->query('site', ''),
            'bakeryOptions' => $this->bakeryOptions($context),
            'data' => $this->data($kind, $bakeryId, $from, $to),
            'canExport' => $request->user()?->can('report.export') ?? false,
        ]);
    }

    public function export(Request $request, BakeryContext $context, string $kind): Response
    {
        abort_unless(in_array($kind, self::KINDS, true), 404);

        [$bakeryId, $from, $to] = $this->period($request, $context);

        $data = $this->data($kind, $bakeryId, $from, $to);
        $tables = $this->tables($kind, $data);
        $format = (string) $request->query('format', 'csv');
        $filename = 'rapport-'.$kind.'-'.$from.'_'.$to;

        if ($format === 'xls') {
            return ReportExport::xls($filename.'.xls', $this->catalogue()[$kind]['label'], $tables['headers'], $tables['rows']);
        }

        if ($format === 'pdf') {
            return response()->view('reports.print', [
                'label' => $this->catalogue()[$kind]['label'],
                'from' => $from,
                'to' => $to,
                'tables' => $tables,
            ]);
        }

        return ReportExport::csv($filename.'.csv', $tables['headers'], $tables['rows']);
    }

    /**
     * @return array{0: int|null, 1: string, 2: string}
     */
    private function period(Request $request, BakeryContext $context): array
    {
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());

        $user = $context->user();
        $isSuperAdmin = $user?->hasRole(Role::SuperAdmin->value) ?? false;

        if ($isSuperAdmin) {
            $site = (string) $request->query('site', '');

            return [$site === '' ? null : (int) $site, $from, $to];
        }

        return [$context->current()?->getKey(), $from, $to];
    }

    /**
     * @return array<int|string, string>
     */
    private function bakeryOptions(BakeryContext $context): array
    {
        $user = $context->user();

        if (! ($user?->hasRole(Role::SuperAdmin->value) ?? false)) {
            return [];
        }

        /** @var array<int|string, string> $options */
        $options = ['' => 'Tous les sites'] + Bakery::query()->orderBy('name')->pluck('name', 'id')->all();

        return $options;
    }

    /**
     * @return array<string, array{label: string, description: string, icon: string}>
     */
    private function catalogue(): array
    {
        return [
            'sales' => ['label' => 'Ventes', 'description' => "Chiffre d'affaires, quantités, ventes par produit et au détail / grossiste.", 'icon' => 'receipt'],
            'stock' => ['label' => 'Stock', 'description' => 'Stock actuel, valorisation et produits sous le seuil.', 'icon' => 'package'],
            'production' => ['label' => 'Production', 'description' => 'Quantités produites, attendues, écart et coût.', 'icon' => 'factory'],
            'losses' => ['label' => 'Pertes', 'description' => 'Valeur et quantités des pertes, par motif et par produit.', 'icon' => 'trash-2'],
            'purchases' => ['label' => 'Achats', 'description' => 'Achats par fournisseur et par produit, commandes en attente.', 'icon' => 'truck'],
            'wholesalers' => ['label' => 'Grossistes', 'description' => 'Volume, chiffre d’affaires et ristourne par grossiste.', 'icon' => 'store'],
            'expenses' => ['label' => 'Charges', 'description' => 'Charges par catégorie et évolution mensuelle.', 'icon' => 'wallet'],
            'payroll' => ['label' => 'Paie', 'description' => 'Masse salariale et périodes de paie.', 'icon' => 'banknote'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function data(string $kind, ?int $bakeryId, string $from, string $to): array
    {
        $year = (int) date('Y', (int) strtotime($from));

        return match ($kind) {
            'sales' => $this->reporting->sales($bakeryId, $from, $to),
            'stock' => $this->reporting->stock($bakeryId),
            'production' => $this->reporting->production($bakeryId, $from, $to),
            'losses' => $this->reporting->losses($bakeryId, $from, $to),
            'purchases' => $this->reporting->purchases($bakeryId, $from, $to),
            'wholesalers' => ['rows' => $this->reporting->wholesalers($bakeryId, $year)],
            'expenses' => $this->reporting->expenses($bakeryId, $from, $to),
            'payroll' => $this->reporting->payroll($bakeryId, $year),
            default => [],
        };
    }

    /**
     * Table tabulaire d'un rapport pour l'export.
     *
     * @param  array<string, mixed>  $data
     * @return array{headers: list<string>, rows: list<list<int|float|string|null>>}
     */
    private function tables(string $kind, array $data): array
    {
        $headers = [];
        $rows = [];

        if ($kind === 'sales' || $kind === 'production' || $kind === 'losses' || $kind === 'purchases') {
            $headers = ['Produit', 'Quantité', $kind === 'sales' ? 'Chiffre d’affaires' : 'Valeur'];
            foreach ($data['by_product'] ?? [] as $row) {
                $rows[] = [
                    $row['name'],
                    $row['quantity'],
                    $row['revenue'] ?? $row['value'] ?? $row['total'] ?? 0,
                ];
            }

            if ($kind === 'purchases') {
                $headers = ['Fournisseur', 'Commandes', 'Montant'];
                $rows = [];
                foreach ($data['by_supplier'] ?? [] as $row) {
                    $rows[] = [$row['name'], $row['orders'], $row['total']];
                }
            }

            return ['headers' => $headers, 'rows' => $rows];
        }

        if ($kind === 'stock') {
            $headers = ['Produit', 'Quantité', 'Valeur'];
            foreach ($data['by_product'] ?? [] as $row) {
                $rows[] = [$row['name'], $row['quantity'], $row['value']];
            }

            return ['headers' => $headers, 'rows' => $rows];
        }

        if ($kind === 'expenses') {
            $headers = ['Catégorie', 'Nombre', 'Montant'];
            foreach ($data['by_category'] ?? [] as $row) {
                $rows[] = [$row['name'], $row['count'], $row['total']];
            }

            return ['headers' => $headers, 'rows' => $rows];
        }

        if ($kind === 'wholesalers') {
            $headers = ['Grossiste', 'Quantité', 'Chiffre d’affaires', 'Ristourne'];
            foreach ($data['rows'] ?? [] as $row) {
                $rows[] = [$row['name'], $row['quantity'], $row['revenue'], $row['rebate']];
            }

            return ['headers' => $headers, 'rows' => $rows];
        }

        if ($kind === 'payroll') {
            $headers = ['Période', 'Net', 'Statut'];
            foreach ($data['periods'] ?? [] as $row) {
                $rows[] = [$row['label'], $row['net'], $row['status']];
            }

            return ['headers' => $headers, 'rows' => $rows];
        }

        return ['headers' => ['Information'], 'rows' => []];
    }
}
