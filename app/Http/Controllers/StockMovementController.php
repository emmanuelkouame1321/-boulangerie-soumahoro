<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Support\Format;
use App\Support\ReportExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Écran Mouvements — grand livre de stock, filtrable et paginé (cahier
 * §3.2, §33 et §34).
 *
 * Le grand livre n'est jamais modifié ni supprimé : l'écran est en
 * lecture seule.
 */
class StockMovementController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Stock::class);

        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $movements = $this->filteredQuery($request)
            ->with(['product.primaryUnit', 'user', 'unit', 'bakery'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('inventory.movements', [
            'movements' => $movements,
            'search' => $search,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'typeOptions' => $this->typeOptions(),
            'exportQuery' => array_filter([
                'search' => $search,
                'type' => $type,
                'from' => $from,
                'to' => $to,
            ], static fn (string $value): bool => $value !== ''),
        ]);
    }

    /**
     * Export du grand livre (cahier §33, Design System §84) — CSV ou
     * classeur SpreadsheetML, sans dépendance (ADR-020).
     */
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', Stock::class);

        $format = (string) $request->query('format', 'csv');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $filename = 'mouvements-de-stock'.($from !== '' || $to !== '' ? '-'.$from.'_'.$to : '');

        $headers = ['Date', 'Heure', 'Produit', 'SKU', 'Type', 'Quantité', 'Unité', 'Stock après', 'Par', 'Motif'];
        $rows = [];

        $query = $this->filteredQuery($request)->with(['product.primaryUnit', 'user', 'unit']);

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

        if ($format === 'xls') {
            return ReportExport::xls($filename.'.xls', 'Mouvements de stock', $headers, $rows);
        }

        return ReportExport::csv($filename.'.csv', $headers, $rows);
    }

    /**
     * Filtres partagés entre l'écran et l'export du grand livre.
     *
     * @return Builder<StockMovement>
     */
    private function filteredQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        return StockMovement::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('product', function (Builder $product) use ($search): void {
                    $term = trim($search);

                    $product->where(function (Builder $query) use ($term): void {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('sku', 'like', "%{$term}%");
                    });
                });
            })
            ->when($type !== '', fn (Builder $query) => $query->where('type', $type))
            ->when($from !== '', fn (Builder $query) => $query->whereDate('occurred_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('occurred_at', '<=', $to))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $options = [];

        foreach (StockMovementType::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
