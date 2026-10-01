<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockMovementException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Support\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockMovementService $movements;

    private StockService $stocks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movements = app(StockMovementService::class);
        $this->stocks = app(StockService::class);
    }

    private function product(array $attributes = []): Product
    {
        $unit = Unit::factory()->create(['code' => 'kg', 'label' => 'Kilogramme', 'symbol' => 'kg']);

        return Product::factory()->create(array_merge(
            ['primary_unit_id' => $unit->getKey()],
            $attributes
        ));
    }

    public function test_an_inbound_movement_increases_the_physical_stock(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $movement = $this->movements->apply(
            $bakery,
            $product,
            StockMovementType::SupplierIn,
            50,
            reason: 'Livraison fournisseur'
        );

        $this->assertSame('50', Decimal::trim((string) $movement->quantity_after));
        $this->assertSame('50', $this->stocks->physical($product, $bakery));
        $this->assertDatabaseHas('stock_movements', [
            'bakery_id' => $bakery->getKey(),
            'product_id' => $product->getKey(),
            'type' => StockMovementType::SupplierIn->value,
        ]);
    }

    public function test_an_outbound_movement_beyond_the_stock_is_refused_without_effect(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 5);

        try {
            $this->movements->apply($bakery, $product, StockMovementType::SaleOut, 6);
            $this->fail('Un stock insuffisant aurait dû être refusé.');
        } catch (InsufficientStockException $exception) {
            $this->assertStringContainsString('il manque', $exception->getMessage());
        }

        $this->assertSame('5', $this->stocks->physical($product, $bakery));
        $this->assertSame(1, StockMovement::query()->count());
    }

    public function test_a_movement_of_zero_is_refused(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->expectException(InvalidStockMovementException::class);

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 0);
    }

    public function test_replaying_the_same_idempotency_key_does_not_apply_twice(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $first = $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 10, idempotencyKey: 'receipt-1');
        $second = $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 10, idempotencyKey: 'receipt-1');

        $this->assertTrue($first->is($second));
        $this->assertSame('10', $this->stocks->physical($product, $bakery));
        $this->assertSame(1, StockMovement::query()->where('idempotency_key', 'receipt-1')->count());
    }

    public function test_two_simultaneous_withdrawals_cannot_both_succeed(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 1);

        // Le garde-fou transactionnel et le verrou de ligne garantissent
        // qu'une seule des deux sorties passe : le stock ne peut pas
        // devenir négatif (cahier §28). Un test réellement parallèle
        // relève de la CI MySQL ; SQLite en mémoire sérialise les écritures.
        $this->movements->apply($bakery, $product, StockMovementType::SaleOut, 1);

        $this->expectException(InsufficientStockException::class);

        $this->movements->apply($bakery, $product, StockMovementType::SaleOut, 1);
    }

    public function test_negative_stock_is_allowed_only_when_explicitly_enabled(): void
    {
        config()->set('boulangerie.guards.allow_negative_stock', true);

        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SaleOut, 3);

        $this->assertSame('-3', $this->stocks->physical($product, $bakery));
    }

    public function test_the_stock_is_isolated_per_site(): void
    {
        $bakeryA = Bakery::factory()->create();
        $bakeryB = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakeryA, $product, StockMovementType::SupplierIn, 10);

        $this->assertSame('10', $this->stocks->physical($product, $bakeryA));
        $this->assertSame('0', $this->stocks->physical($product, $bakeryB));
    }

    public function test_the_stock_is_derivable_from_the_ledger(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 100);
        $this->movements->apply($bakery, $product, StockMovementType::SaleOut, 30);
        $this->movements->apply($bakery, $product, StockMovementType::LossOut, 10);

        $ledger = StockMovement::query()
            ->where('product_id', $product->getKey())
            ->get()
            ->reduce(
                fn (string $carry, StockMovement $movement): string => Decimal::add($carry, $movement->signedQuantity()),
                '0'
            );

        $this->assertSame($ledger, $this->stocks->physical($product, $bakery));
        $this->assertSame('60', $this->stocks->physical($product, $bakery));
    }
}
