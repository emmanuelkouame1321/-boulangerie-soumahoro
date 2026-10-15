<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesScreenTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
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
            idempotencyKey: "screen:sales:{$this->bakery->getKey()}",
        );
    }

    private function user(Role $role, bool $attachBakery = true): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        if ($attachBakery) {
            $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);
        }

        return $user;
    }

    public function test_a_super_admin_can_see_the_sales_screens(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('sales.index'))->assertOk();
        $this->actingAs($admin)->get(route('sales.create'))->assertOk()->assertSee('Baguette');
        $this->actingAs($admin)->get(route('sales.closings.index'))->assertOk();
    }

    public function test_the_sales_list_uses_the_mobile_card_layout(): void
    {
        $this->stock('10');

        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('sales.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'payment_method' => 'cash',
                'items' => [['product_id' => $this->bread->getKey(), 'quantity' => 2]],
            ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee('data-card-table', false)
            ->assertSee("--dt-label-2: 'Client'", false);
    }

    public function test_a_sale_can_be_recorded_from_the_screen(): void
    {
        $this->stock('10');

        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('sales.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $this->bread->getKey(), 'quantity' => 4],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $sale = Sale::query()->firstOrFail();
        $this->assertSame(2_000, $sale->total);
        $this->assertSame(0, bccomp(app(StockService::class)->physical($this->bread, $this->bakery), '6', 4));

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee($sale->reference);
    }

    public function test_an_insufficient_stock_is_reported_on_the_screen(): void
    {
        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('sales.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $this->bread->getKey(), 'quantity' => 5],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_a_cashier_can_record_a_sale(): void
    {
        $this->stock('10');

        $this->actingAs($this->user(Role::Cashier))
            ->get(route('sales.index'))
            ->assertOk();

        $this->actingAs($this->user(Role::Cashier))
            ->post(route('sales.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'payment_method' => 'cash',
                'items' => [['product_id' => $this->bread->getKey(), 'quantity' => 1]],
            ])
            ->assertSessionHas('status');
    }

    public function test_a_cashier_cannot_reach_the_day_closings(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('sales.closings.index'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('sales.index'))->assertRedirect('/login');
        $this->get(route('sales.create'))->assertRedirect('/login');
    }
}
