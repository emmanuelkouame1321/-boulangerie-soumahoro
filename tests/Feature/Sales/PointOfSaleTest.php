<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Livewire\Sales\PointOfSale;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointOfSaleTest extends TestCase
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

        app(StockMovementService::class)->apply(
            bakery: $this->bakery,
            product: $this->bread,
            type: StockMovementType::SupplierIn,
            quantity: '50',
            reason: 'Stock de test',
            idempotencyKey: 'pos:test',
        );
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_catalog_is_rendered(): void
    {
        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            ->assertSee('Baguette')
            ->assertSee('Panier');
    }

    public function test_adding_and_changing_quantity_updates_the_cart(): void
    {
        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->bread->getKey())
            ->assertSet('cart.0.product_id', $this->bread->getKey())
            ->assertSet('cart.0.qty', 1)
            ->call('changeQuantity', $this->bread->getKey(), 1)
            ->assertSet('cart.0.qty', 2)
            ->call('removeItem', $this->bread->getKey())
            ->assertSet('cart', []);
    }

    public function test_payment_cannot_open_on_an_empty_cart(): void
    {
        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            ->call('openPayment')
            ->assertSet('paymentOpen', false);
    }

    public function test_validating_a_payment_clears_the_cart_and_deducts_stock(): void
    {
        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->bread->getKey())
            ->call('openPayment')
            ->assertSet('paymentOpen', true)
            ->call('validatePayment', 'cash')
            ->assertSet('paymentOpen', false)
            ->assertSet('cart', []);

        $this->assertSame(0, bccomp(app(StockService::class)->physical($this->bread, $this->bakery), '49', 4));
        $this->assertDatabaseCount('sales', 1);
    }
}
