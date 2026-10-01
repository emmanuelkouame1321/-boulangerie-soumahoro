<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockReservationService;
use App\Domain\Inventory\StockService;
use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReservationTest extends TestCase
{
    use RefreshDatabase;

    private StockReservationService $reservations;

    private StockMovementService $movements;

    private StockService $stocks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservations = app(StockReservationService::class);
        $this->movements = app(StockMovementService::class);
        $this->stocks = app(StockService::class);
    }

    private function product(): Product
    {
        $unit = Unit::factory()->create(['code' => 'kg', 'label' => 'Kilogramme', 'symbol' => 'kg']);

        return Product::factory()->create(['primary_unit_id' => $unit->getKey()]);
    }

    public function test_reserving_reduces_availability_without_touching_the_physical_stock(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 10);

        $this->reservations->reserve($bakery, $product, 4);

        $this->assertSame('10', $this->stocks->physical($product, $bakery));
        $this->assertSame('4', $this->stocks->reserved($product, $bakery));
        $this->assertSame('6', $this->stocks->available($product, $bakery));
    }

    public function test_a_reservation_cannot_exceed_the_available_stock(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 10);
        $this->reservations->reserve($bakery, $product, 8);

        $this->expectException(InsufficientStockException::class);

        $this->reservations->reserve($bakery, $product, 3);
    }

    public function test_releasing_a_reservation_restores_availability(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 10);

        $reservation = $this->reservations->reserve($bakery, $product, 4);
        $released = $this->reservations->release($reservation);

        $this->assertSame(StockReservationStatus::Released, $released->status);
        $this->assertSame('0', $this->stocks->reserved($product, $bakery));
        $this->assertSame('10', $this->stocks->available($product, $bakery));
    }

    public function test_releasing_twice_is_idempotent(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 10);

        $reservation = $this->reservations->reserve($bakery, $product, 4);
        $this->reservations->release($reservation);
        $this->reservations->release($reservation);

        $this->assertSame('0', $this->stocks->reserved($product, $bakery));
    }

    public function test_consuming_a_reservation_creates_a_single_outbound_movement(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 10);

        $reservation = $this->reservations->reserve($bakery, $product, 6);

        $this->reservations->consume($reservation);

        $this->assertSame('4', $this->stocks->physical($product, $bakery));
        $this->assertSame('0', $this->stocks->reserved($product, $bakery));
        $this->assertSame(StockReservationStatus::Consumed, $reservation->refresh()->status);

        $this->assertSame(
            1,
            StockMovement::query()
                ->where('product_id', $product->getKey())
                ->where('type', StockMovementType::ProductionOut->value)
                ->count()
        );
    }
}
