<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Enums\ProductType;
use App\Enums\Role;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
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

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    public function test_super_admin_can_list_products(): void
    {
        Product::factory()->create(['name' => 'Farine 50 kg', 'primary_unit_id' => $this->unit->getKey()]);

        $this->actingAs($this->superAdmin())
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Farine 50 kg');
    }

    public function test_super_admin_can_create_a_product(): void
    {
        $category = ProductCategory::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('products.store'), [
                'sku' => 'PF-BAGUETTE',
                'name' => 'Baguette',
                'product_category_id' => $category->getKey(),
                'type' => ProductType::FinishedGood->value,
                'primary_unit_id' => $this->unit->getKey(),
                'secondary_unit_id' => null,
                'purchase_price' => 0,
                'sale_price' => 500,
                'wholesale_price' => 350,
                'alert_threshold' => 30,
                'alert_mode' => 'stock_threshold',
                'shelf_life_days' => 1,
                'is_active' => true,
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['sku' => 'PF-BAGUETTE', 'sale_price' => 500]);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'PF-BAGUETTE', 'primary_unit_id' => $this->unit->getKey()]);

        $this->actingAs($this->superAdmin())
            ->post(route('products.store'), [
                'sku' => 'PF-BAGUETTE',
                'name' => 'Autre',
                'type' => ProductType::RawMaterial->value,
                'primary_unit_id' => $this->unit->getKey(),
                'purchase_price' => 0,
                'sale_price' => 0,
                'wholesale_price' => 0,
                'alert_mode' => 'stock_threshold',
            ])
            ->assertSessionHasErrors('sku');
    }

    public function test_super_admin_can_update_a_product(): void
    {
        $product = Product::factory()->create(['primary_unit_id' => $this->unit->getKey()]);

        $this->actingAs($this->superAdmin())
            ->put(route('products.update', $product), [
                'sku' => $product->sku,
                'name' => 'Nom mis à jour',
                'type' => ProductType::RawMaterial->value,
                'primary_unit_id' => $this->unit->getKey(),
                'purchase_price' => 1000,
                'sale_price' => 0,
                'wholesale_price' => 0,
                'alert_mode' => 'stock_threshold',
                'is_active' => true,
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->getKey(), 'name' => 'Nom mis à jour']);
    }

    public function test_a_role_without_permission_cannot_reach_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::ProductionManager->value);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertForbidden();
    }
}
