<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\ExpiryService;
use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Enums\BatchStatus;
use App\Enums\LossOrigin;
use App\Enums\LossReason;
use App\Enums\ProductType;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Cahier §8.2, §22, §29, §40 et §50 : les dates limites sont calculées,
 * les expirés identifiés, les invendus transformés en pertes selon la
 * règle configurée — idempotemment.
 */
class ExpiryTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Carbon::setTestNow('2026-10-05 08:00:00');

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
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
            idempotencyKey: "test:expiry:{$this->bakery->getKey()}",
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

    public function test_the_expiry_check_converts_an_expired_lot_into_a_loss(): void
    {
        $this->stock('15');
        $batch = $this->batch('15', '2026-10-04');

        $this->artisan('boulangerie:expiry-check')->assertSuccessful();

        $loss = LossRecord::query()->firstOrFail();

        $this->assertSame(LossReason::Expiry, $loss->reason);
        $this->assertSame(LossOrigin::Expiry, $loss->origin);
        $this->assertStringStartsWith('LOSS-202610-', $loss->reference);
        $this->assertSame(0, bccomp((string) $loss->total_quantity, '15', 4));
        $this->assertSame(1_500, $loss->total_value);

        $item = $loss->items()->firstOrFail();
        $this->assertSame($this->bread->getKey(), $item->product_id);
        $this->assertSame($batch->getKey(), $item->production_batch_id);
        $this->assertSame(100, $item->unit_value);
        $this->assertSame(1_500, $item->line_value);

        // Le lot est conservé comme historique, vidé et marqué expiré.
        $batch->refresh();
        $this->assertSame(0, bccomp((string) $batch->available_quantity, '0', 4));
        $this->assertSame(0, bccomp((string) $batch->lost_quantity, '15', 4));
        $this->assertSame(BatchStatus::Expired, $batch->status);

        // Le stock a diminué exactement une fois.
        $this->assertSame(0, bccomp($this->physical(), '0', 4));

        $movement = StockMovement::query()
            ->where('type', StockMovementType::LossOut->value)
            ->firstOrFail();
        $this->assertSame(
            'batch-expiry:'.$batch->getKey().':2026-10-05',
            $movement->idempotency_key
        );
    }

    public function test_running_the_expiry_check_twice_records_a_single_loss(): void
    {
        $this->stock('10');
        $this->batch('10', '2026-10-04');

        $this->artisan('boulangerie:expiry-check')->assertSuccessful();
        $this->artisan('boulangerie:expiry-check')->assertSuccessful();

        $this->assertDatabaseCount('loss_records', 1);
        $this->assertDatabaseCount('loss_items', 1);
        $this->assertSame(1, StockMovement::query()->where('type', StockMovementType::LossOut->value)->count());
        $this->assertSame(0, bccomp($this->physical(), '0', 4));
    }

    public function test_batches_are_flagged_to_sell_soon_and_a_soon_alert_opens(): void
    {
        $this->stock('30');

        $soon = $this->batch('10', '2026-10-06');
        $fresh = $this->batch('20', '2026-10-20');

        app(ExpiryService::class)->refresh($this->bakery, Carbon::now());

        $this->assertSame(BatchStatus::ToSellSoon, $soon->refresh()->status);
        $this->assertSame(BatchStatus::Fresh, $fresh->refresh()->status);

        $alert = StockAlert::query()
            ->where('type', StockAlertType::ExpirySoon->value)
            ->firstOrFail();

        $this->assertSame(StockAlertStatus::Open, $alert->status);
        $this->assertStringContainsString($soon->reference, (string) $alert->message);
    }

    public function test_an_expired_batch_is_flagged_and_raises_an_alert(): void
    {
        $this->stock('10');
        $expired = $this->batch('10', '2026-10-04');

        app(ExpiryService::class)->refresh($this->bakery, Carbon::now());

        $this->assertSame(BatchStatus::Expired, $expired->refresh()->status);

        $alert = StockAlert::query()
            ->where('type', StockAlertType::Expired->value)
            ->firstOrFail();

        $this->assertSame(StockAlertStatus::Open, $alert->status);
        $this->assertStringContainsString('à traiter', (string) $alert->message);
    }

    public function test_the_expired_alert_resolves_once_the_lot_is_converted(): void
    {
        $this->stock('10');
        $this->batch('10', '2026-10-04');

        $this->artisan('boulangerie:expiry-check')->assertSuccessful();

        $alert = StockAlert::query()
            ->where('type', StockAlertType::Expired->value)
            ->firstOrFail();
        $this->assertSame(StockAlertStatus::Open, $alert->status);

        // Le lot est traité : la condition disparaît, l'alerte se referme.
        $this->artisan('boulangerie:expiry-check')->assertSuccessful();

        $this->assertSame(StockAlertStatus::Resolved, $alert->refresh()->status);
    }

    public function test_the_conversion_date_rule_is_configurable(): void
    {
        config()->set('boulangerie.expiry.conversion', 'day_after_expiry_date');

        $this->stock('20');
        $today = $this->batch('10', '2026-10-05');
        $yesterday = $this->batch('10', '2026-10-04');

        app(ExpiryService::class)->convertExpired($this->bakery, Carbon::now());

        // La date limite du jour est encore vendable : seul le lot
        // expiré hier est converti.
        $this->assertSame(0, bccomp((string) $today->refresh()->available_quantity, '10', 4));
        $this->assertSame(0, bccomp((string) $yesterday->refresh()->available_quantity, '0', 4));
        $this->assertSame(1, LossRecord::query()->count());
    }

    public function test_the_scheduler_runs_the_expiry_check(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('boulangerie:expiry-check', Artisan::output());
    }

    private function physical(): string
    {
        return app(StockService::class)->physical($this->bread, $this->bakery);
    }
}
