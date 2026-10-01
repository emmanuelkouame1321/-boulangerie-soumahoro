<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Livewire\Sales\PointOfSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointOfSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_is_rendered(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create());

        Livewire::test(PointOfSale::class)
            ->assertSee('Baguette')
            ->assertSee('Panier');
    }

    public function test_adding_and_changing_quantity_updates_the_cart(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create());

        Livewire::test(PointOfSale::class)
            ->call('addToCart', 'SKU-BAGUETTE')
            ->assertSet('cart.0.sku', 'SKU-BAGUETTE')
            ->assertSet('cart.0.qty', 1)
            ->call('changeQuantity', 'SKU-BAGUETTE', 1)
            ->assertSet('cart.0.qty', 2)
            ->call('removeItem', 'SKU-BAGUETTE')
            ->assertSet('cart', []);
    }

    public function test_payment_cannot_open_on_an_empty_cart(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create());

        Livewire::test(PointOfSale::class)
            ->call('openPayment')
            ->assertSet('paymentOpen', false);
    }

    public function test_validating_a_payment_clears_the_cart(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create());

        Livewire::test(PointOfSale::class)
            ->call('addToCart', 'SKU-BAGUETTE')
            ->call('openPayment')
            ->assertSet('paymentOpen', true)
            ->call('validatePayment', 'cash')
            ->assertSet('paymentOpen', false)
            ->assertSet('cart', []);
    }
}
