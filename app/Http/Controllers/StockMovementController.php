<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $movements = StockMovement::query()
            ->with(['product.primaryUnit', 'user', 'unit', 'bakery'])
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
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('inventory.movements', [
            'movements' => $movements,
            'search' => $search,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'typeOptions' => $this->typeOptions(),
        ]);
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
