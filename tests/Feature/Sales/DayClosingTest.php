<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Domain\Sales\DayClosingService;
use App\Enums\ProductType;
use App\Enums\SaleStatus;
use App\Exceptions\DayClosingAlreadyExistsException;
use App\Exceptions\DayClosingGapJustificationRequiredException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOutput;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Cahier §15 : la clôture confronte produit, vendu et restant inventorié,
 * calcule l'écart et n'est enregistrée qu'une fois par site et par date.
 */
class DayClosingTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->bakery = Bakery::factory()->create();
        $this->date = Carbon::parse('2026-10-05');

        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);
        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $unit->getKey(),
            'sale_price' => 500,
        ]);

        $order = ProductionOrder::factory()->validated()->create([
            'bakery_id' => $this->bakery->getKey(),
            'produced_on' => $this->date->toDateString(),
        ]);

        ProductionOutput::factory()->create([
            'production_order_id' => $order->getKey(),
            'product_id' => $this->bread->getKey(),
            'quantity' => 300,
            'produced_on' => $this->date->toDateString(),
        ]);

        $sale = Sale::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'sale_date' => $this->date->toDateString(),
            'status' => SaleStatus::Validated,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $this->bread->getKey(),
            'quantity' => 40,
            'unit_price' => 500,
        ]);
    }

    public function test_the_snapshot_reports_produced_sold_and_expected_remaining(): void
    {
        $snapshot = app(DayClosingService::class)->snapshot($this->bakery, $this->date);

        $this->assertCount(1, $snapshot);
        $this->assertSame('300', $snapshot[0]['produced']);
        $this->assertSame('40', $snapshot[0]['sold']);
        $this->assertSame('260', $snapshot[0]['expected_remaining']);
    }

    public function test_a_closing_computes_the_inventory_gap(): void
    {
        $closing = app(DayClosingService::class)->close(
            bakery: $this->bakery,
            date: $this->date,
            entries: [$this->bread->getKey() => ['remaining' => 250, 'lost' => 4]],
        );

        $this->assertSame(0, bccomp((string) $closing->produced_total, '300', 4));
        $this->assertSame(0, bccomp((string) $closing->sold_total, '40', 4));
        $this->assertSame(0, bccomp((string) $closing->remaining_total, '250', 4));

        // Écart = 250 − 260 = −10.
        $this->assertSame(0, bccomp((string) $closing->gap_total, '-10', 4));

        $item = $closing->items->firstOrFail();
        $this->assertSame(0, bccomp((string) $item->gap, '-10', 4));
        $this->assertSame(0, bccomp((string) $item->lost_quantity, '4', 4));
    }

    public function test_a_day_cannot_be_closed_twice(): void
    {
        $service = app(DayClosingService::class);

        $service->close(
            bakery: $this->bakery,
            date: $this->date,
            entries: [$this->bread->getKey() => ['remaining' => 260]],
        );

        $this->expectException(DayClosingAlreadyExistsException::class);

        $service->close(
            bakery: $this->bakery,
            date: $this->date,
            entries: [$this->bread->getKey() => ['remaining' => 260]],
        );
    }

    public function test_a_gap_beyond_the_threshold_requires_an_observation(): void
    {
        // Écart attendu : 0 − 260 = −260, soit bien au-delà du seuil de 10.
        $this->expectException(DayClosingGapJustificationRequiredException::class);

        app(DayClosingService::class)->close(
            bakery: $this->bakery,
            date: $this->date,
            entries: [$this->bread->getKey() => ['remaining' => 0]],
        );
    }

    public function test_a_gap_beyond_the_threshold_is_accepted_with_an_observation(): void
    {
        $closing = app(DayClosingService::class)->close(
            bakery: $this->bakery,
            date: $this->date,
            entries: [$this->bread->getKey() => ['remaining' => 0]],
            note: 'Inventaire mangé par les rongeurs, constaté à l’ouverture.',
        );

        $this->assertSame(0, bccomp((string) $closing->gap_total, '-260', 4));
        $this->assertNotNull($closing->note);
        $this->assertTrue($closing->hasGap());
    }

    public function test_a_gap_within_the_threshold_needs_no_observation(): void
    {
        // Écart attendu : 255 − 260 = −5, dans le seuil de 10.
        $closing = app(DayClosingService::class)->close(
            bakery: $this->bakery,
            date: $this->date,
            entries: [$this->bread->getKey() => ['remaining' => 255]],
        );

        $this->assertSame(0, bccomp((string) $closing->gap_total, '-5', 4));
        $this->assertNull($closing->note);
    }
}
