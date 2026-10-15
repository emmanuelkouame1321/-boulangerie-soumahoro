<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\LotService;
use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Domain\Inventory\StockTransferService;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Support\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cohérence des données de stock (cahier §3.2, §8 et §28).
 *
 * Ces tests ne vérifient pas un écran mais les **invariants** qui doivent
 * tenir après une séquence d'opérations réelles :
 *  1. `stocks.quantity` (matérialisé) égale toujours la somme signée du
 *     grand livre `stock_movements` — la matérialisation est un cache du
 *     grand livre, jamais une seconde source de vérité ;
 *  2. un lot suit `initial = disponible + vendu + perdu + transféré` ;
 *  3. aucun stock ne devient négatif.
 *
 * Ils reproduisent la réconciliation qui sert au diagnostic en production.
 */
class StockCoherenceTest extends TestCase
{
    use RefreshDatabase;

    private StockMovementService $movements;

    private StockTransferService $transfers;

    private LotService $lots;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movements = app(StockMovementService::class);
        $this->transfers = app(StockTransferService::class);
        $this->lots = app(LotService::class);
    }

    private function product(ProductType $type = ProductType::RawMaterial): Product
    {
        $unit = Unit::factory()->create(['code' => 'kg', 'label' => 'Kilogramme', 'symbol' => 'kg']);

        return Product::factory()->create([
            'type' => $type,
            'primary_unit_id' => $unit->getKey(),
        ]);
    }

    /**
     * Le stock matérialisé de chaque ligne correspond à la somme signée des
     * mouvements. Un écart signale une écriture hors `StockMovementService`
     * ou un mouvement perdu.
     */
    private function assertMaterializedStockMatchesLedger(): void
    {
        $stocks = Stock::withoutBakeryScope()->get();

        $this->assertNotEmpty($stocks, 'Aucune ligne de stock à réconcilier.');

        foreach ($stocks as $stock) {
            $ledger = StockMovement::withoutBakeryScope()
                ->where('bakery_id', $stock->bakery_id)
                ->where('product_id', $stock->product_id)
                ->get()
                ->reduce(static function (string $carry, StockMovement $movement): string {
                    return $movement->type->increasesStock()
                        ? Decimal::add($carry, (string) $movement->quantity)
                        : Decimal::subtract($carry, (string) $movement->quantity);
                }, '0');

            $this->assertSame(
                Decimal::trim($ledger),
                Decimal::trim((string) $stock->quantity),
                sprintf(
                    'Désaccord grand livre / stock matérialisé (site %d, produit %d).',
                    $stock->bakery_id,
                    $stock->product_id,
                ),
            );
        }
    }

    private function assertLotInvariantsHold(): void
    {
        foreach (ProductionBatch::withoutBakeryScope()->get() as $batch) {
            $total = Decimal::add(
                Decimal::add((string) $batch->available_quantity, (string) $batch->sold_quantity),
                Decimal::add((string) $batch->lost_quantity, (string) $batch->transferred_quantity),
            );

            $this->assertSame(
                Decimal::trim((string) $batch->initial_quantity),
                Decimal::trim($total),
                "Invariant du lot {$batch->reference} rompu.",
            );
        }
    }

    private function assertNoNegativeStock(): void
    {
        foreach (Stock::withoutBakeryScope()->get() as $stock) {
            $this->assertGreaterThanOrEqual(
                0,
                (float) $stock->quantity,
                sprintf('Stock négatif (site %d, produit %d).', $stock->bakery_id, $stock->product_id),
            );
        }
    }

    public function test_the_materialized_stock_matches_the_ledger_across_mixed_operations(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        // Entrées, sorties, ajustement, puis transfert inter-sites.
        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);
        $this->movements->apply($source, $product, StockMovementType::AdjustmentOut, 10);
        $this->movements->apply($source, $product, StockMovementType::SaleOut, 20);
        $this->movements->apply($source, $product, StockMovementType::LossOut, 5);

        $transfer = $this->transfers->create($source, $destination, $product, 15);
        $transfer = $this->transfers->send($transfer);
        $this->transfers->receive($transfer);

        $this->movements->apply($destination, $product, StockMovementType::AdjustmentIn, 3);

        $stocks = app(StockService::class);

        $this->assertSame('50', Decimal::trim($stocks->physical($product, $source)));
        $this->assertSame('18', Decimal::trim($stocks->physical($product, $destination)));

        $this->assertMaterializedStockMatchesLedger();
        $this->assertNoNegativeStock();
    }

    public function test_the_lot_invariant_holds_across_sales_losses_and_transfers(): void
    {
        $source = Bakery::factory()->create();
        $product = $this->product(ProductType::FinishedGood);

        $batch = ProductionBatch::factory()->create([
            'bakery_id' => $source->getKey(),
            'product_id' => $product->getKey(),
            'initial_quantity' => 100,
            'available_quantity' => 100,
            'sold_quantity' => 0,
            'lost_quantity' => 0,
            'transferred_quantity' => 0,
        ]);

        // Le stock physique conditionne l'imputation FEFO d'une vente.
        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $today = now();

        $this->lots->sellFromLots($source, $product, '20', $today);
        $this->lots->attributeToLots($source, $product, '5', $today);

        // Les deux imputations précédentes ont modifié le lot en base :
        // on relit avant la sortie par transfert, comme le service le fait
        // sous verrou au moment de l'expédition.
        $batch->refresh();

        $this->lots->transferFromBatch($batch, '15', $today);

        $batch->refresh();

        $this->assertSame('60', Decimal::trim((string) $batch->available_quantity));
        $this->assertSame('20', Decimal::trim((string) $batch->sold_quantity));
        $this->assertSame('5', Decimal::trim((string) $batch->lost_quantity));
        $this->assertSame('15', Decimal::trim((string) $batch->transferred_quantity));

        $this->assertLotInvariantsHold();
    }

    public function test_a_received_transfer_recreates_a_coherent_lot_at_the_destination(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product(ProductType::FinishedGood);

        $batch = ProductionBatch::factory()->create([
            'bakery_id' => $source->getKey(),
            'product_id' => $product->getKey(),
            'initial_quantity' => 80,
            'available_quantity' => 80,
        ]);

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 80);

        $transfer = $this->transfers->create($source, $destination, $product, 30, $batch);
        $transfer = $this->transfers->send($transfer);
        $this->transfers->receive($transfer);

        // Le lot d'origine reste au grand livre du site expéditeur…
        $this->assertSame('50', Decimal::trim((string) $batch->refresh()->available_quantity));
        $this->assertSame('30', Decimal::trim((string) $batch->transferred_quantity));

        // …et le lot recréé chez le destinataire est équilibré.
        $received = ProductionBatch::withoutBakeryScope()
            ->where('bakery_id', $destination->getKey())
            ->where('reference', $transfer->reference)
            ->firstOrFail();

        $this->assertSame('30', Decimal::trim((string) $received->available_quantity));

        $this->assertLotInvariantsHold();
        $this->assertMaterializedStockMatchesLedger();
    }
}
