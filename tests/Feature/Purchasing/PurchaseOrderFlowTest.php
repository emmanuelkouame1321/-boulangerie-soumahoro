<?php

declare(strict_types=1);

namespace Tests\Feature\Purchasing;

use App\Domain\Inventory\StockService;
use App\Domain\Purchasing\GoodsReceiptService;
use App\Domain\Purchasing\PurchaseOrderService;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\GoodsReceiptAlreadyValidatedException;
use App\Exceptions\PurchaseOrderNotReceivableException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critère de sortie de la Phase 3 (cahier §11.3 et §50) : une réception
 * validée augmente le stock automatiquement et ne peut pas être validée
 * deux fois.
 */
class PurchaseOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Supplier $supplier;

    private Unit $unit;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->bakery = Bakery::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->unit = Unit::factory()->create(['code' => 'sac', 'label' => 'Sac', 'symbol' => 'sac']);
        $this->product = Product::factory()->create([
            'name' => 'Farine 50 kg',
            'primary_unit_id' => $this->unit->getKey(),
        ]);
    }

    private function order(int|float|string $quantity = 20, int $unitPrice = 27_500): PurchaseOrder
    {
        return app(PurchaseOrderService::class)->create(
            bakery: $this->bakery,
            supplier: $this->supplier,
            lines: [
                ['product_id' => $this->product->getKey(), 'quantity' => $quantity, 'unit_price' => $unitPrice],
            ],
        );
    }

    private function assertPhysicalStock(string $expected): void
    {
        $physical = app(StockService::class)->physical($this->product, $this->bakery);

        $this->assertSame(0, bccomp($physical, $expected, 4), "Stock attendu {$expected}, obtenu {$physical}.");
    }

    public function test_an_order_is_created_with_its_total(): void
    {
        $order = $this->order(20, 27_500);

        $this->assertSame(PurchaseOrderStatus::Ordered, $order->status);
        $this->assertSame(550_000, $order->total);
        $this->assertCount(1, $order->items);
        $this->assertStringStartsWith('PO-', $order->reference);
    }

    public function test_validating_a_receipt_increases_the_stock_exactly_once(): void
    {
        $order = $this->order(20);
        $item = $order->items->firstOrFail();

        $receipt = app(GoodsReceiptService::class)->receive(
            $order,
            [['purchase_order_item_id' => $item->getKey(), 'quantity' => 20]],
        );

        $this->assertTrue($receipt->isValidated());
        $this->assertPhysicalStock('20');
        $this->assertDatabaseCount('stock_movements', 1);

        $movement = StockMovement::withoutBakeryScope()->firstOrFail();
        $this->assertSame(StockMovementType::SupplierIn, $movement->type);
        $this->assertSame($receipt->getMorphClass(), $movement->source_type);
        $this->assertSame($receipt->getKey(), $movement->source_id);

        $this->assertSame(PurchaseOrderStatus::Validated, $order->fresh()->status);
        $this->assertSame(0, bccomp((string) $order->fresh()->items->firstOrFail()->received_quantity, '20', 4));
    }

    public function test_a_receipt_cannot_be_validated_twice(): void
    {
        $order = $this->order(20);
        $item = $order->items->firstOrFail();

        $receipt = app(GoodsReceiptService::class)->receive(
            $order,
            [['purchase_order_item_id' => $item->getKey(), 'quantity' => 20]],
        );

        $threw = false;

        try {
            app(GoodsReceiptService::class)->validate($receipt);
        } catch (GoodsReceiptAlreadyValidatedException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Une réception validée doit refuser une seconde validation.');
        $this->assertPhysicalStock('20');
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_a_partial_reception_marks_the_order_partially_received(): void
    {
        $order = $this->order(20);
        $item = $order->items->firstOrFail();

        $receipts = app(GoodsReceiptService::class);

        $receipts->receive($order, [['purchase_order_item_id' => $item->getKey(), 'quantity' => 12]]);

        $this->assertPhysicalStock('12');
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $order->fresh()->status);

        $receipts->receive($order, [['purchase_order_item_id' => $item->getKey(), 'quantity' => 8]]);

        $this->assertPhysicalStock('20');
        $this->assertSame(PurchaseOrderStatus::Validated, $order->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_the_source_movement_points_back_to_the_receipt(): void
    {
        $order = $this->order(5);
        $item = $order->items->firstOrFail();

        $receipt = app(GoodsReceiptService::class)->receive(
            $order,
            [['purchase_order_item_id' => $item->getKey(), 'quantity' => 5]],
        );

        $movement = StockMovement::withoutBakeryScope()->firstOrFail();

        $this->assertTrue($movement->source->is($receipt));
    }

    public function test_a_receive_without_any_quantity_is_refused(): void
    {
        $order = $this->order(10);
        $item = $order->items->firstOrFail();

        $this->expectException(PurchaseOrderNotReceivableException::class);

        app(GoodsReceiptService::class)->receive(
            $order,
            [['purchase_order_item_id' => $item->getKey(), 'quantity' => 0]],
        );
    }

    public function test_the_stock_is_isolated_per_site(): void
    {
        $order = $this->order(10);
        $item = $order->items->firstOrFail();

        app(GoodsReceiptService::class)->receive(
            $order,
            [['purchase_order_item_id' => $item->getKey(), 'quantity' => 10]],
        );

        $other = Bakery::factory()->create();

        $this->assertSame('0', app(StockService::class)->physical($this->product, $other));
    }
}
