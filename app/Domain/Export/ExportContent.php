<?php

declare(strict_types=1);

namespace App\Domain\Export;

use App\Domain\Reporting\ReportingService;
use App\Models\AuditLog;
use App\Models\Export;
use App\Models\StockMovement;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Contenu des exports (cahier §33, §34, §32 et §48).
 *
 * Centralise la construction « en-têtes + lignes » des trois familles
 * d'export : les 8 rapports, le grand livre des mouvements et le
 * journal d'audit. Consommé par `GenerateExport` (queue) et par
 * `ReportsController` pour la vue imprimable.
 */
class ExportContent
{
    public const KIND_MOVEMENTS = 'movements';

    public const KIND_AUDIT = 'audit';

    private const REPORT_PREFIX = 'report:';

    public function __construct(private readonly ReportingService $reporting) {}

    /**
     * Catalogue des rapports (cahier §33).
     *
     * @return array<string, array{label: string, description: string, icon: string}>
     */
    public function catalogue(): array
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
     * @return list<string>
     */
    public function reportKinds(): array
    {
        return array_keys($this->catalogue());
    }

    public function label(string $kind): string
    {
        if (str_starts_with($kind, self::REPORT_PREFIX)) {
            // Un rapport retiré du catalogue depuis la demande ne doit pas
            // faire échouer la création de l'export : le job le marquera en
            // échec avec un message lisible.
            $report = substr($kind, strlen(self::REPORT_PREFIX));

            return $this->catalogue()[$report]['label'] ?? $report;
        }

        if ($kind === self::KIND_MOVEMENTS) {
            return 'Mouvements de stock';
        }

        if ($kind === self::KIND_AUDIT) {
            return 'Journal d’audit';
        }

        return $kind;
    }

    /**
     * Construit le contenu d'un export enregistré : libellé, en-têtes et
     * lignes. Les filtres viennent de `export.filters` ; le site de
     * l'export borne le grand livre et l'audit (null = consolidation).
     *
     * @param  array<string, mixed>  $filters
     * @return array{label: string, headers: list<string>, rows: list<list<int|float|string|null>>}
     *
     * @throws InvalidArgumentException
     */
    public function build(string $kind, array $filters, ?int $exportBakeryId): array
    {
        if (str_starts_with($kind, self::REPORT_PREFIX)) {
            $report = substr($kind, strlen(self::REPORT_PREFIX));

            if (! in_array($report, $this->reportKinds(), true)) {
                throw new InvalidArgumentException("Rapport inconnu : {$report}");
            }

            $from = (string) ($filters['from'] ?? now()->startOfMonth()->toDateString());
            $to = (string) ($filters['to'] ?? now()->toDateString());
            $bakeryId = isset($filters['site']) && $filters['site'] !== '' ? (int) $filters['site'] : null;

            return [
                'label' => $this->label($kind),
                ...$this->tables($report, $this->reportData($report, $bakeryId, $from, $to)),
            ];
        }

        if ($kind === self::KIND_MOVEMENTS) {
            return ['label' => $this->label($kind), ...$this->movementRows($filters, $exportBakeryId)];
        }

        if ($kind === self::KIND_AUDIT) {
            return ['label' => $this->label($kind), ...$this->auditRows($filters, $exportBakeryId)];
        }

        throw new InvalidArgumentException("Export inconnu : {$kind}");
    }

    /**
     * Données d'un rapport, dans le périmètre demandé.
     *
     * @return array<string, mixed>
     */
    public function reportData(string $kind, ?int $bakeryId, string $from, string $to): array
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
     * Table tabulaire d'un rapport (impression et export).
     *
     * @param  array<string, mixed>  $data
     * @return array{headers: list<string>, rows: list<list<int|float|string|null>>}
     */
    public function tables(string $kind, array $data): array
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

    /**
     * Grand livre des mouvements, filtré comme l'écran (§34).
     *
     * @param  array<string, mixed>  $filters
     * @return array{headers: list<string>, rows: list<list<int|float|string|null>>}
     */
    private function movementRows(array $filters, ?int $exportBakeryId): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = (string) ($filters['type'] ?? '');
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');

        $query = StockMovement::query()
            ->with(['product.primaryUnit', 'user', 'unit'])
            ->when($exportBakeryId !== null, fn (Builder $query) => $query->where('bakery_id', $exportBakeryId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('product', function (Builder $product) use ($search): void {
                    $product->where(function (Builder $query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
                });
            })
            ->when($type !== '', fn (Builder $query) => $query->where('type', $type))
            ->when($from !== '', fn (Builder $query) => $query->whereDate('occurred_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('occurred_at', '<=', $to))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $headers = ['Date', 'Heure', 'Produit', 'SKU', 'Type', 'Quantité', 'Unité', 'Stock après', 'Par', 'Motif'];
        $rows = [];

        foreach ($query->get() as $movement) {
            $symbol = data_get($movement, 'unit.symbol') ?? data_get($movement, 'product.primaryUnit.symbol');

            $rows[] = [
                $movement->occurred_at->format('d/m/Y'),
                $movement->occurred_at->format('H:i'),
                data_get($movement, 'product.name') ?? '—',
                data_get($movement, 'product.sku') ?? '',
                $movement->type->label(),
                ($movement->type->increasesStock() ? '' : '-').Format::stock($movement->quantity),
                $symbol ?? '',
                Format::stock($movement->quantity_after),
                data_get($movement, 'user.name') ?? '—',
                $movement->reason ?? $movement->reference ?? '',
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Journal d'audit (§32), filtré comme l'écran.
     *
     * @param  array<string, mixed>  $filters
     * @return array{headers: list<string>, rows: list<list<int|float|string|null>>}
     */
    private function auditRows(array $filters, ?int $exportBakeryId): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $action = (string) ($filters['action'] ?? '');
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');

        $query = AuditLog::query()
            ->when($exportBakeryId !== null, fn (Builder $query) => $query->where('bakery_id', $exportBakeryId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                /** @var Builder<AuditLog> $query */
                $query->search($search);
            })
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->when($from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $headers = ['Date', 'Heure', 'Utilisateur', 'IP', 'Action', 'Objet', 'Site', 'Anciennes valeurs', 'Nouvelles valeurs'];
        $rows = [];

        foreach ($query->get() as $log) {
            $rows[] = [
                $log->created_at->format('d/m/Y'),
                $log->created_at->format('H:i:s'),
                $log->user_name ?? 'Système',
                $log->ip_address ?? '—',
                $log->action,
                $log->subjectLabel(),
                data_get($log, 'bakery.name') ?? 'Tous les sites',
                $log->old_values === null ? '' : json_encode($log->old_values, JSON_UNESCAPED_UNICODE),
                $log->new_values === null ? '' : json_encode($log->new_values, JSON_UNESCAPED_UNICODE),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}
