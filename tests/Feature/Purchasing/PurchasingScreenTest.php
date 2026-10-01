<?php

declare(strict_types=1);

namespace Tests\Feature\Purchasing;

use App\Domain\Inventory\StockService;
use App\Domain\Purchasing\PurchaseOrderService;
use App\Enums\PurchaseOrderStatus;
use App\Enums\Role;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingScreenTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Supplier $supplier;

    private Product $product;

    private PurchaseOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
        $this->supplier = Supplier::factory()->create(['name' => 'Minoterie Abidjan']);
        $unit = Unit::factory()->create(['code' => 'sac', 'label' => 'Sac', 'symbol' => 'sac']);
        $this->product = Product::factory()->create([
            'name' => 'Farine 50 kg',
            'primary_unit_id' => $unit->getKey(),
        ]);

        $this->order = app(PurchaseOrderService::class)->create(
            bakery: $this->bakery,
            supplier: $this->supplier,
            lines: [
                ['product_id' => $this->product->getKey(), 'quantity' => 20, 'unit_price' => 27_500],
            ],
        );
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_a_super_admin_can_see_the_purchasing_screens(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('purchasing.orders.index'))->assertOk()->assertSee($this->order->reference);
        $this->actingAs($admin)->get(route('purchasing.orders.create'))->assertOk();
        $this->actingAs($admin)->get(route('purchasing.orders.show', $this->order))->assertOk()->assertSee('Enregistrer une réception');
    }

    public function test_a_goods_receipt_can_be_validated_from_the_screen(): void
    {
        $item = $this->order->items->firstOrFail();

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('purchasing.receipts.store', $this->order), [
                'items' => [
                    ['purchase_order_item_id' => $item->getKey(), 'quantity' => 20],
                ],
            ])
            ->assertRedirect(route('purchasing.orders.show', $this->order))
            ->assertSessionHas('status');

        $this->assertSame('20', app(StockService::class)->physical($this->product, $this->bakery));
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_a_reception_cannot_be_validated_twice_from_the_screen(): void
    {
        $item = $this->order->items->firstOrFail();
        $payload = ['items' => [['purchase_order_item_id' => $item->getKey(), 'quantity' => 20]]];

        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->post(route('purchasing.receipts.store', $this->order), $payload);

        $this->actingAs($admin)
            ->post(route('purchasing.receipts.store', $this->order), $payload)
            ->assertSessionHas('error');

        $this->assertSame('20', app(StockService::class)->physical($this->product, $this->bakery));
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_a_production_manager_can_manage_orders(): void
    {
        $manager = $this->user(Role::ProductionManager);
        $manager->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        $this->actingAs($manager)->get(route('purchasing.orders.index'))->assertOk();
        $this->actingAs($manager)->get(route('purchasing.orders.show', $this->order))->assertOk();
        $this->actingAs($manager)
            ->post(route('purchasing.orders.cancel', $this->order))
            ->assertRedirect(route('purchasing.orders.show', $this->order));

        $this->assertSame(PurchaseOrderStatus::Cancelled, $this->order->fresh()->status);
    }

    public function test_a_cashier_cannot_reach_the_purchasing_screens(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('purchasing.orders.index'))
            ->assertForbidden();
    }

    public function test_a_cashier_cannot_create_an_order(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('purchasing.orders.create'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('purchasing.orders.index'))->assertRedirect('/login');
        $this->get(route('suppliers.index'))->assertRedirect('/login');
    }
}
