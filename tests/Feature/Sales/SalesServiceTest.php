<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Domain\Sales\SalesService;
use App\Enums\ProductType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cahier §14, §28 et §29 : une vente déduit le stock de produits finis une
 * seule fois et ne peut jamais le rendre négatif.
 */
class SalesServiceTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'sku' => 'PF-BAGUETTE',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $unit->getKey(),
            'sale_price' => 500,
        ]);
    }

    private function stock(string $quantity): void
    {
        app(StockMovementService::class)->apply(
            bakery: $this->bakery,
            product: $this->bread,
            type: StockMovementType::SupplierIn,
            quantity: $quantity,
            reason: 'Stock de test',
            idempotencyKey: "test:sales:{$this->bakery->getKey()}",
        );
    }

    private function physical(): string
    {
        return app(StockService::class)->physical($this->bread, $this->bakery);
    }

    public function test_a_sale_deducts_the_stock_and_records_a_payment(): void
    {
        $this->stock('10');

        $sale = app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 4]],
            paymentMethod: 'cash',
        );

        $this->assertSame(SaleStatus::Validated, $sale->status);
        $this->assertSame(2_000, $sale->subtotal);
        $this->assertSame(2_000, $sale->total);
        $this->assertSame(2_000, $sale->paid_total);
        $this->assertSame(0, bccomp($this->physical(), '6', 4));
        $this->assertDatabaseCount('payments', 1);

        $movement = StockMovement::withoutBakeryScope()
            ->where('type', StockMovementType::SaleOut->value)
            ->firstOrFail();
        $this->assertSame(StockMovementType::SaleOut, $movement->type);
        $this->assertTrue($movement->source->is($sale));
    }

    public function test_a_discount_reduces_the_total(): void
    {
        $this->stock('10');

        $sale = app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 2, 'discount' => 200]],
            paymentMethod: 'cash',
        );

        $this->assertSame(1_000, $sale->subtotal);
        $this->assertSame(200, $sale->discount);
        $this->assertSame(800, $sale->total);
    }

    public function test_a_sale_cannot_make_the_stock_negative(): void
    {
        $this->stock('3');

        $this->expectException(InsufficientStockException::class);

        app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 5]],
            paymentMethod: 'cash',
        );
    }

    public function test_replaying_the_idempotency_key_does_not_deduct_twice(): void
    {
        $this->stock('10');

        $lines = [['product_id' => $this->bread->getKey(), 'quantity' => 4]];

        $first = app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: $lines,
            paymentMethod: 'cash',
            idempotencyKey: 'sale-abc',
        );

        $second = app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: $lines,
            paymentMethod: 'cash',
            idempotencyKey: 'sale-abc',
        );

        $this->assertTrue($first->is($second));
        $this->assertSame(0, bccomp($this->physical(), '6', 4));
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_a_named_customer_can_be_attached(): void
    {
        $this->stock('10');
        $customer = Customer::factory()->create(['bakery_id' => $this->bakery->getKey()]);

        $sale = app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 1]],
            customer: $customer,
            paymentMethod: 'cash',
        );

        $this->assertSame($customer->getKey(), $sale->customer_id);
    }

    public function test_the_stock_is_isolated_per_site(): void
    {
        $this->stock('10');

        app(SalesService::class)->record(
            bakery: $this->bakery,
            lines: [['product_id' => $this->bread->getKey(), 'quantity' => 4]],
            paymentMethod: 'cash',
        );

        $other = Bakery::factory()->create();
        $this->assertSame('0', app(StockService::class)->physical($this->bread, $other));

        // Une vente n'est jamais visible hors de son site par un utilisateur scoped.
        $this->assertSame(1, Sale::withoutBakeryScope()->count());
    }
}
