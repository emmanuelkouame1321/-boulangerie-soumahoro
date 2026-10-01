<?php

declare(strict_types=1);

namespace Tests\Feature\Production;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Domain\Production\ProductionService;
use App\Domain\Production\RecipeCalculator;
use App\Enums\BatchStatus;
use App\Enums\ProductType;
use App\Enums\RecipeDeductionMode;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingGapReasonException;
use App\Exceptions\ProductionAlreadyValidatedException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critère de sortie de la Phase 4 (cahier §7 et §50) : une production
 * validée diminue les matières, augmente le stock de produits finis,
 * calcule l'écart et crée un lot — une seule fois.
 */
class ProductionFlowTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Unit $sack;

    private Unit $piece;

    private Product $flour;

    private Product $bread;

    private Recipe $recipe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->bakery = Bakery::factory()->create();
        $this->sack = Unit::factory()->create(['code' => 'sack', 'label' => 'Sac', 'symbol' => 'sac']);
        $this->piece = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->flour = Product::factory()->create([
            'name' => 'Farine 50 kg',
            'type' => ProductType::RawMaterial,
            'primary_unit_id' => $this->sack->getKey(),
            'purchase_price' => 27_500,
            'shelf_life_days' => null,
        ]);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $this->piece->getKey(),
            'sale_price' => 500,
            'shelf_life_days' => 2,
        ]);

        $this->recipe = Recipe::factory()->create([
            'product_id' => $this->bread->getKey(),
            'name' => 'Pain traditionnel',
            'yield_quantity' => 300,
            'shelf_life_days' => 1,
        ]);

        $this->recipe->items()->create([
            'product_id' => $this->flour->getKey(),
            'unit_id' => $this->sack->getKey(),
            'quantity' => 1,
            'deduction_mode' => RecipeDeductionMode::Quantity,
            'unit_cost' => null,
            'position' => 0,
        ]);
    }

    private function stockTheFlour(string $quantity = '24'): void
    {
        app(StockMovementService::class)->apply(
            bakery: $this->bakery,
            product: $this->flour,
            type: StockMovementType::SupplierIn,
            quantity: $quantity,
            reason: 'Stock de test',
            idempotencyKey: "test:flour:{$this->bakery->getKey()}",
        );
    }

    private function physical(Product $product): string
    {
        return app(StockService::class)->physical($product, $this->bakery);
    }

    public function test_the_recipe_calculates_requirements_and_yield(): void
    {
        $calculator = app(RecipeCalculator::class);

        $requirements = $calculator->requirements($this->recipe, 2);

        $this->assertCount(1, $requirements);
        $this->assertSame('2', $requirements[0]['planned_quantity']);
        $this->assertSame(27_500, $requirements[0]['unit_cost']);
        $this->assertSame(55_000, $requirements[0]['line_cost']);

        $this->assertSame('600', $calculator->theoreticalQuantity($this->recipe, 2));
        $this->assertSame(55_000, $calculator->theoreticalCost($this->recipe, 2));
        $this->assertSame('-8', $calculator->gap('300', '292'));
    }

    public function test_a_validated_production_consumes_materials_and_creates_finished_stock(): void
    {
        $this->stockTheFlour('24');

        $order = app(ProductionService::class)->produce(
            bakery: $this->bakery,
            recipe: $this->recipe,
            batches: 1,
            actualQuantity: 292,
            gapReason: 'technical_loss',
        );

        $this->assertTrue($order->isValidated());
        $this->assertSame(0, bccomp((string) $order->gap, '-8', 4));
        $this->assertSame(0, bccomp((string) $order->actual_quantity, '292', 4));

        // Matières consommées, produit fini entré.
        $this->assertSame(0, bccomp($this->physical($this->flour), '23', 4));
        $this->assertSame(0, bccomp($this->physical($this->bread), '292', 4));

        // Un lot créé, avec sa date limite de vente (recette : 1 jour).
        $batch = ProductionBatch::withoutBakeryScope()->firstOrFail();
        $this->assertSame(0, bccomp((string) $batch->initial_quantity, '292', 4));
        $this->assertSame(BatchStatus::Fresh, $batch->status);
        $this->assertSame(
            $order->produced_on->copy()->addDay()->toDateString(),
            $batch->expires_on->toDateString()
        );

        // Coût réel = 1 sac × 27 500.
        $this->assertSame(27_500, $order->actual_cost);
    }

    public function test_a_production_cannot_be_validated_twice(): void
    {
        $this->stockTheFlour('24');

        $service = app(ProductionService::class);

        $order = $service->produce(
            bakery: $this->bakery,
            recipe: $this->recipe,
            batches: 1,
            actualQuantity: 300,
        );

        $threw = false;

        try {
            $service->validate($order, 300);
        } catch (ProductionAlreadyValidatedException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Une production validée doit refuser une seconde validation.');
        $this->assertSame(0, bccomp($this->physical($this->flour), '23', 4));
        $this->assertSame(0, bccomp($this->physical($this->bread), '300', 4));
        $this->assertDatabaseCount('production_batches', 1);

        $productionOut = StockMovement::withoutBakeryScope()
            ->where('type', StockMovementType::ProductionOut->value)
            ->count();
        $this->assertSame(1, $productionOut);
    }

    public function test_a_gap_without_a_reason_is_refused(): void
    {
        $this->stockTheFlour('24');

        $service = app(ProductionService::class);

        $order = $service->create($this->bakery, $this->recipe, 1);

        $this->expectException(MissingGapReasonException::class);

        $service->validate($order, 292);
    }

    public function test_insufficient_materials_block_the_production(): void
    {
        // Aucun stock de farine : la production doit être bloquée net.
        $this->expectException(InsufficientStockException::class);

        app(ProductionService::class)->produce(
            bakery: $this->bakery,
            recipe: $this->recipe,
            batches: 1,
            actualQuantity: 300,
        );
    }

    public function test_the_source_movement_points_back_to_the_production(): void
    {
        $this->stockTheFlour('24');

        $order = app(ProductionService::class)->produce(
            bakery: $this->bakery,
            recipe: $this->recipe,
            batches: 1,
            actualQuantity: 300,
        );

        $movement = StockMovement::withoutBakeryScope()
            ->where('type', StockMovementType::ProductionOut->value)
            ->firstOrFail();

        $this->assertTrue($movement->source->is($order));
        $this->assertSame('production:'.$order->getKey().':output', StockMovement::withoutBakeryScope()
            ->where('type', StockMovementType::ProductionIn->value)
            ->firstOrFail()
            ->idempotency_key);
    }

    public function test_the_stock_and_batches_are_isolated_per_site(): void
    {
        $this->stockTheFlour('24');

        app(ProductionService::class)->produce(
            bakery: $this->bakery,
            recipe: $this->recipe,
            batches: 1,
            actualQuantity: 300,
        );

        $other = Bakery::factory()->create();

        $this->assertSame('0', app(StockService::class)->physical($this->bread, $other));

        // Un ordre n'est jamais visible hors de son site.
        $this->assertSame(1, ProductionOrder::query()->count());
    }
}
