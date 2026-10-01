<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\StockMovementService;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryScreenTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->unit = Unit::factory()->create(['code' => 'kg', 'label' => 'Kilogramme', 'symbol' => 'kg']);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function product(string $name = 'Farine 50 kg'): Product
    {
        return Product::factory()->create([
            'name' => $name,
            'primary_unit_id' => $this->unit->getKey(),
            'alert_threshold' => 10,
        ]);
    }

    public function test_super_admin_can_see_the_real_stock_screen(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        app(StockMovementService::class)->apply($bakery, $product, StockMovementType::SupplierIn, 24);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Farine 50 kg');
    }

    public function test_super_admin_can_see_the_movements_ledger(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        app(StockMovementService::class)->apply($bakery, $product, StockMovementType::SupplierIn, 12);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('inventory.movements'))
            ->assertOk()
            ->assertSee('Entrée fournisseur');
    }

    public function test_the_movements_ledger_can_be_exported(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        app(StockMovementService::class)->apply($bakery, $product, StockMovementType::SupplierIn, 12);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('inventory.movements.export', ['format' => 'csv']))
            ->assertOk()
            ->assertDownload('mouvements-de-stock.csv');

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('inventory.movements.export', ['format' => 'xls']))
            ->assertOk()
            ->assertSee('<Workbook', false);
    }

    public function test_the_movements_export_requires_the_inventory_permission(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('inventory.movements.export'))
            ->assertForbidden();
    }

    public function test_super_admin_can_adjust_a_stock(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('inventory.adjustments.store'), [
                'bakery_id' => $bakery->getKey(),
                'product_id' => $product->getKey(),
                'type' => StockMovementType::AdjustmentIn->value,
                'quantity' => 5,
                'reason' => 'Inventaire initial',
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->getKey(),
            'bakery_id' => $bakery->getKey(),
            'type' => StockMovementType::AdjustmentIn->value,
            'reason' => 'Inventaire initial',
        ]);
    }

    public function test_an_adjustment_requires_a_reason(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('inventory.adjustments.store'), [
                'bakery_id' => $bakery->getKey(),
                'product_id' => $product->getKey(),
                'type' => StockMovementType::AdjustmentIn->value,
                'quantity' => 5,
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_a_role_without_inventory_permission_cannot_reach_the_stock_screen(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('inventory.index'))
            ->assertForbidden();
    }

    public function test_a_role_without_adjust_permission_cannot_post_an_adjustment(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('inventory.adjustments.store'), [
                'bakery_id' => $bakery->getKey(),
                'product_id' => $product->getKey(),
                'type' => StockMovementType::AdjustmentIn->value,
                'quantity' => 5,
                'reason' => 'Tentative',
            ])
            ->assertForbidden();
    }

    public function test_a_production_manager_can_consult_the_stock(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('inventory.index'))
            ->assertOk();
    }
}
