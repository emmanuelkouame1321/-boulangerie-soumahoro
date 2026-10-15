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
use Illuminate\Support\Facades\Storage;
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

    public function test_the_checkout_screen_exposes_a_cart_for_mobile(): void
    {
        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            ->assertSeeHtml('x-data="{ cartOpen: false }"')
            ->assertSeeHtml('x-show="cartOpen"')
            ->assertSeeHtml('x-on:click="cartOpen = true"')
            ->assertSee('Payer');
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

    public function test_the_product_card_is_a_structured_container(): void
    {
        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            // Conteneur visible : bordure, fond de surface et ombre de carte.
            ->assertSeeHtml('rounded-card border border-line bg-surface')
            // Visuel carré pleine largeur, plus lisible que la pastille de 72 px.
            ->assertSeeHtml('aspect-square w-full')
            // Le prix vit dans le corps de la carte, il ne chevauche plus le visuel.
            ->assertSeeHtml('numeric text-[13px] leading-none font-bold text-brand-700');
    }

    public function test_a_product_photo_replaces_the_generic_illustration(): void
    {
        Storage::fake('public');

        $path = 'products/baguette.png';
        Storage::disk('public')->put($path, 'contenu');
        $this->bread->update(['photo_path' => $path]);

        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            ->assertSee($this->bread->photoUrl(), false)
            // Sans photo, la carte affiche l'emoji de la famille.
            ->assertDontSee('🍞');
    }

    public function test_a_product_without_a_photo_keeps_the_emoji(): void
    {
        $this->actingAs($this->cashier());

        Livewire::test(PointOfSale::class)
            ->assertSee('🍞')
            ->assertDontSee('<img', false);
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
