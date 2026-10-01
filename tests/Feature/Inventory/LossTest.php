<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\LossService;
use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Enums\BatchStatus;
use App\Enums\LossOrigin;
use App\Enums\LossReason;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Enums\StockMovementType;
use App\Exceptions\EmptyLossException;
use App\Exceptions\InsufficientStockException;
use App\Models\Bakery;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Support\Format;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Cahier §8.2, §15, §22 et §50 : une perte diminue automatiquement les
 * produits concernés, reste valorisée et tracée, et déclenche une
 * alerte au-delà du seuil configuré.
 */
class LossTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Carbon::setTestNow('2026-10-05 10:00:00');

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'sku' => 'PF-BAGUETTE-PERTE',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $unit->getKey(),
            'purchase_price' => 100,
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
            idempotencyKey: "test:loss:{$this->bakery->getKey()}",
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

    private function physical(): string
    {
        return app(StockService::class)->physical($this->bread, $this->bakery);
    }

    public function test_a_manual_loss_decreases_the_stock_and_records_its_value(): void
    {
        $this->stock('100');
        $batch = $this->batch('50', '2026-10-12');

        $loss = app(LossService::class)->record(
            bakery: $this->bakery,
            reason: LossReason::Damage,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 20]],
            note: 'Casse constatée à l’ouverture.',
        );

        $this->assertStringStartsWith('LOSS-202610-', $loss->reference);
        $this->assertSame(LossOrigin::Manual, $loss->origin);
        $this->assertSame(LossReason::Damage, $loss->reason);
        $this->assertSame(0, bccomp((string) $loss->total_quantity, '20', 4));
        $this->assertSame(2_000, $loss->total_value);

        // Le stock diminue et le mouvement est historisé.
        $this->assertSame(0, bccomp($this->physical(), '80', 4));

        $movement = StockMovement::query()->where('type', StockMovementType::LossOut->value)->firstOrFail();
        $this->assertSame($loss->reference, $movement->reference);
        $this->assertSame(LossReason::Damage->label(), $movement->reason);

        // Sans lot désigné, la perte est imputée aux lots en FEFO.
        $batch->refresh();
        $this->assertSame(0, bccomp((string) $batch->available_quantity, '30', 4));
        $this->assertSame(0, bccomp((string) $batch->lost_quantity, '20', 4));
        $this->assertSame(BatchStatus::Fresh, $batch->status);

        $item = $loss->items()->firstOrFail();
        $this->assertNull($item->production_batch_id);
        $this->assertSame(100, $item->unit_value);
        $this->assertSame(2_000, $item->line_value);
    }

    public function test_a_loss_cannot_exceed_the_stock(): void
    {
        $this->stock('5');

        try {
            app(LossService::class)->record(
                bakery: $this->bakery,
                reason: LossReason::Unsold,
                lines: [['product_id' => $this->bread->getKey(), 'quantity' => 10]],
            );

            $this->fail('La perte aurait dû être refusée : stock insuffisant.');
        } catch (InsufficientStockException) {
            $this->assertSame(0, bccomp($this->physical(), '5', 4));
            $this->assertDatabaseCount('loss_records', 0);
            $this->assertDatabaseCount('stock_movements', 1);
        }
    }

    public function test_an_empty_loss_is_refused(): void
    {
        $this->expectException(EmptyLossException::class);

        app(LossService::class)->record(
            bakery: $this->bakery,
            reason: LossReason::Other,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 0]],
        );
    }

    public function test_a_significant_loss_opens_an_alert(): void
    {
        $this->stock('400');

        $loss = app(LossService::class)->record(
            bakery: $this->bakery,
            reason: LossReason::ProductionDefect,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 300]],
        );

        $alert = StockAlert::query()
            ->where('type', StockAlertType::SignificantLoss->value)
            ->firstOrFail();

        $this->assertSame(StockAlertStatus::Open, $alert->status);
        $this->assertSame($this->bread->getKey(), $alert->product_id);
        $this->assertStringContainsString($loss->reference, (string) $alert->message);
        $this->assertStringContainsString(Format::money(30_000), (string) $alert->message);
    }

    public function test_the_loss_screens_work_for_the_sales_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->stock('50');

        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)->get(route('losses.index'))->assertOk();
        $this->actingAs($user)->get(route('losses.create'))->assertOk()->assertSee('Baguette');

        $this->actingAs($user)
            ->post(route('losses.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'product_id' => $this->bread->getKey(),
                'quantity' => 10,
                'reason' => LossReason::Unsold->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $loss = LossRecord::query()->firstOrFail();
        $this->assertSame(0, bccomp($this->physical(), '40', 4));

        $this->actingAs($user)
            ->get(route('losses.show', $loss))
            ->assertOk()
            ->assertSee($loss->reference);
    }

    public function test_a_role_without_loss_permission_is_denied(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::ProductionManager->value);

        $this->actingAs($user)->get(route('losses.index'))->assertForbidden();
        $this->actingAs($user)->get(route('losses.create'))->assertForbidden();

        $this->actingAs($user)
            ->post(route('losses.store'), [
                'product_id' => $this->bread->getKey(),
                'quantity' => 1,
                'reason' => LossReason::Other->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('loss_records', 0);
    }
}
