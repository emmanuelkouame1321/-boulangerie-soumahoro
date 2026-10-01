<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Inventory\StockService;
use App\Enums\DayClosingStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\SaleStatus;
use App\Exceptions\DayClosingAlreadyExistsException;
use App\Exceptions\DayClosingGapJustificationRequiredException;
use App\Models\Bakery;
use App\Models\DayClosing;
use App\Models\Product;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Clôture journalière (cahier §15).
 *
 * À la clôture, le responsable inventorie les produits finis. Le service
 * confronte la quantité produite (sorties de production validées) et la
 * quantité vendue (ventes validées) à l'inventaire saisi, puis calcule
 * l'écart « restant réel − (produit − vendu) ».
 *
 * La clôture est unique par site et par date : une seconde clôture est
 * refusée (cahier §29).
 */
final class DayClosingService
{
    public function __construct(
        private readonly StockService $stocks,
    ) {}

    /**
     * Photographie théorique de la journée : pour chaque produit fini
     * concerné, la quantité produite, vendue et le restant attendu.
     *
     * @return list<array{product: Product, produced: string, sold: string, expected_remaining: string, current_stock: string}>
     */
    public function snapshot(Bakery $bakery, CarbonInterface $date): array
    {
        $produced = $this->producedByProduct($bakery, $date);
        $sold = $this->soldByProduct($bakery, $date);

        $productIds = array_unique(array_merge(array_keys($produced), array_keys($sold)));
        sort($productIds);

        $rows = [];

        foreach ($productIds as $productId) {
            $product = Product::query()->find((int) $productId);

            if ($product === null) {
                continue;
            }

            $producedQuantity = (string) ($produced[$productId] ?? '0');
            $soldQuantity = (string) ($sold[$productId] ?? '0');

            $rows[] = [
                'product' => $product,
                'produced' => Decimal::trim($producedQuantity),
                'sold' => Decimal::trim($soldQuantity),
                'expected_remaining' => Decimal::subtract($producedQuantity, $soldQuantity),
                'current_stock' => $this->stocks->physical($product, $bakery),
            ];
        }

        return $rows;
    }

    /**
     * Enregistre une clôture journalière.
     *
     * @param  array<int, array{remaining?: int|float|string, lost?: int|float|string, returned?: int|float|string}>  $entries  quantités saisies par identifiant produit
     */
    public function close(
        Bakery $bakery,
        CarbonInterface $date,
        array $entries = [],
        ?User $user = null,
        ?string $note = null,
    ): DayClosing {
        return DB::transaction(function () use ($bakery, $date, $entries, $user, $note): DayClosing {
            $existing = DayClosing::withoutBakeryScope()
                ->where('bakery_id', $bakery->getKey())
                ->whereDate('closing_date', $date->toDateString())
                ->first();

            if ($existing !== null) {
                throw DayClosingAlreadyExistsException::for($bakery, $date->toDateString(), $existing);
            }

            $snapshot = $this->snapshot($bakery, $date);

            $this->ensureJustification($snapshot, $entries, $note);

            $closing = DayClosing::create([
                'bakery_id' => $bakery->getKey(),
                'created_by' => $user?->getKey(),
                'validated_by' => $user?->getKey(),
                'reference' => $this->nextReference(),
                'status' => DayClosingStatus::Validated,
                'closing_date' => $date->toDateString(),
                'note' => $note,
                'validated_at' => now(),
            ]);

            $totals = [
                'produced_total' => '0',
                'sold_total' => '0',
                'remaining_total' => '0',
                'lost_total' => '0',
                'returned_total' => '0',
                'gap_total' => '0',
            ];

            foreach ($snapshot as $row) {
                $productId = (int) $row['product']->getKey();
                $entry = $entries[$productId] ?? [];

                $remaining = array_key_exists('remaining', $entry)
                    ? Decimal::abs((string) $entry['remaining'])
                    : Decimal::trim($row['current_stock']);
                $lost = array_key_exists('lost', $entry) ? Decimal::abs((string) $entry['lost']) : '0';
                $returned = array_key_exists('returned', $entry) ? Decimal::abs((string) $entry['returned']) : '0';

                $gap = Decimal::subtract($remaining, $row['expected_remaining']);

                $closing->items()->create([
                    'product_id' => $productId,
                    'produced_quantity' => $row['produced'],
                    'sold_quantity' => $row['sold'],
                    'remaining_quantity' => $remaining,
                    'lost_quantity' => $lost,
                    'returned_quantity' => $returned,
                    'gap' => $gap,
                ]);

                $totals['produced_total'] = Decimal::add($totals['produced_total'], $row['produced']);
                $totals['sold_total'] = Decimal::add($totals['sold_total'], $row['sold']);
                $totals['remaining_total'] = Decimal::add($totals['remaining_total'], $remaining);
                $totals['lost_total'] = Decimal::add($totals['lost_total'], $lost);
                $totals['returned_total'] = Decimal::add($totals['returned_total'], $returned);
                $totals['gap_total'] = Decimal::add($totals['gap_total'], $gap);
            }

            $closing->fill($totals);
            $closing->save();

            return $closing->load('items.product');
        });
    }

    /**
     * Un écart absolu par produit au-delà du seuil configuré exige une
     * observation (cahier §15).
     *
     * @param  list<array{product: Product, produced: string, sold: string, expected_remaining: string, current_stock: string}>  $snapshot
     * @param  array<int, array{remaining?: int|float|string, lost?: int|float|string, returned?: int|float|string}>  $entries
     *
     * @throws DayClosingGapJustificationRequiredException
     */
    private function ensureJustification(array $snapshot, array $entries, ?string $note): void
    {
        if ($note !== null && trim($note) !== '') {
            return;
        }

        $threshold = Decimal::trim((string) config('boulangerie.day_closing.gap_justification_threshold', 10));

        foreach ($snapshot as $row) {
            $productId = (int) $row['product']->getKey();
            $entry = $entries[$productId] ?? [];

            $remaining = array_key_exists('remaining', $entry)
                ? Decimal::abs((string) $entry['remaining'])
                : Decimal::trim($row['current_stock']);

            $gap = Decimal::abs(Decimal::subtract($remaining, $row['expected_remaining']));

            if (Decimal::compare($gap, $threshold) > 0) {
                throw DayClosingGapJustificationRequiredException::for($gap, $threshold);
            }
        }
    }

    /**
     * @return array<int, int|float|string>
     */
    private function producedByProduct(Bakery $bakery, CarbonInterface $date): array
    {
        /** @var array<int, int|float|string> $rows */
        $rows = DB::table('production_outputs')
            ->join('production_orders', 'production_orders.id', '=', 'production_outputs.production_order_id')
            ->where('production_orders.bakery_id', $bakery->getKey())
            ->whereDate('production_orders.produced_on', $date->toDateString())
            ->where('production_orders.status', ProductionOrderStatus::Validated->value)
            ->groupBy('production_outputs.product_id')
            ->selectRaw('production_outputs.product_id as product_id, SUM(production_outputs.quantity) as total')
            ->pluck('total', 'product_id')
            ->all();

        return $rows;
    }

    /**
     * @return array<int, int|float|string>
     */
    private function soldByProduct(Bakery $bakery, CarbonInterface $date): array
    {
        /** @var array<int, int|float|string> $rows */
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.bakery_id', $bakery->getKey())
            ->whereDate('sales.sale_date', $date->toDateString())
            ->where('sales.status', SaleStatus::Validated->value)
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id as product_id, SUM(sale_items.quantity) as total')
            ->pluck('total', 'product_id')
            ->all();

        return $rows;
    }

    private function nextReference(): string
    {
        $prefix = 'CLO-'.now()->format('Ym').'-';

        $sequence = DayClosing::withoutBakeryScope()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (DayClosing::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }
}
