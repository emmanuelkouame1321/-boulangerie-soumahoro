<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Domain\Sales\SalesService;
use App\Enums\BatchStatus;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Exceptions\LotExpiredException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Cahier §8, §41 et §50 : les ventes consomment les lots dans l'ordre
 * FEFO (du plus proche de la date limite au plus lointain) et refusent
 * clairement un lot expiré.
 */
class LotConsumptionTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Carbon::setTestNow('2026-10-05 09:00:00');

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'sku' => 'PF-BAGUETTE-LOT',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $unit->getKey(),
            'sale_price' => 500,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function stock(string $quantity): void
    {
        app(StockMovementService::class)->apply(
            bakery: $this->bakery,
            product: $this->bread,
            type: StockMovementType::ProductionIn,
            quantity: $quantity,
            reason: 'Stock de test',
            idempotencyKey: "test:lots:{$this->bakery->getKey()}",
        );
    }

    private function batch(string $available, string $expiresOn): ProductionBatch
    {
        return ProductionBatch::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->bread->getKey(),
            'initial_quantity' => $available,
            'available_quantity' => $available,
            'produced_on' => '2026-10-04',
            'expires_on' => $expiresOn,
            'status' => BatchStatus::Fresh,
        ]);
    }

    private function sell(int $quantity): void
    {
        app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => $quantity]],
        );
    }

    private function physical(): string
    {
        return app(StockService::class)->physical($this->bread, $this->bakery);
    }

    public function test_a_sale_consumes_the_batch_closest_to_its_expiry_date_first(): void
    {
        $this->stock('30');

        $soon = $this->batch('10', '2026-10-06');
        $later = $this->batch('20', '2026-10-20');

        $this->sell(12);

        $soon->refresh();
        $later->refresh();

        // Le lot le plus proche est consommé en premier.
        $this->assertSame(0, bccomp((string) $soon->available_quantity, '0', 4));
        $this->assertSame(0, bccomp((string) $soon->sold_quantity, '10', 4));
        $this->assertSame(0, bccomp((string) $later->available_quantity, '18', 4));
        $this->assertSame(0, bccomp((string) $later->sold_quantity, '2', 4));

        $this->assertSame(BatchStatus::Depleted, $soon->status);
        $this->assertSame(BatchStatus::Fresh, $later->status);
        $this->assertSame(0, bccomp($this->physical(), '18', 4));
    }

    public function test_an_expired_lot_cannot_be_sold(): void
    {
        $this->stock('10');
        $expired = $this->batch('10', '2026-10-04');

        try {
            $this->sell(5);
            $this->fail('La vente aurait dû être refusée : le lot est expiré.');
        } catch (LotExpiredException $exception) {
            $this->assertStringContainsString('date limite est dépassée', $exception->getMessage());
        }

        // Rollback complet : ni vente, ni mouvement, ni lot touché.
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(0, bccomp($this->physical(), '10', 4));
        $this->assertSame(0, bccomp((string) $expired->refresh()->available_quantity, '10', 4));
    }

    public function test_only_the_fresh_part_is_sellable_when_a_lot_has_expired(): void
    {
        $this->stock('15');

        $expired = $this->batch('5', '2026-10-04');
        $fresh = $this->batch('10', '2026-10-10');

        $this->sell(10);

        $fresh->refresh();
        $this->assertSame(0, bccomp((string) $fresh->available_quantity, '0', 4));
        $this->assertSame(0, bccomp($this->physical(), '5', 4));

        // Les 5 restants sont physiquement là, mais expirés : refusés.
        try {
            $this->sell(5);
            $this->fail('La vente aurait dû être refusée : seul du stock expiré reste.');
        } catch (LotExpiredException $exception) {
            $this->assertStringContainsString('date limite est dépassée', $exception->getMessage());
        }

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(0, bccomp($this->physical(), '5', 4));
        $this->assertSame(0, bccomp((string) $expired->refresh()->available_quantity, '5', 4));
    }

    public function test_a_product_without_lots_is_sold_from_stock_as_before(): void
    {
        $this->stock('10');

        $this->sell(4);

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(0, bccomp($this->physical(), '6', 4));
        $this->assertDatabaseCount('production_batches', 0);
    }
}
