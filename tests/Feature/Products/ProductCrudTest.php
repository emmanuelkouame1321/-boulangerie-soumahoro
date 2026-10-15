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
        Product::factory()->finishedGood()->create(['name' => 'Baguette', 'primary_unit_id' => $this->unit->getKey()]);

        // Onglet par défaut : les produits (produit fini).
        $this->actingAs($this->superAdmin())
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Baguette')
            ->assertDontSee('Farine 50 kg');

        // Onglet matières premières.
        $this->actingAs($this->superAdmin())
            ->get(route('products.index', ['group' => 'materials']))
            ->assertOk()
            ->assertSee('Farine 50 kg')
            ->assertDontSee('Baguette');
    }

    public function test_the_catalogue_is_split_into_products_and_materials(): void
    {
        Product::factory()->create(['name' => 'Farine 50 kg', 'primary_unit_id' => $this->unit->getKey()]);
        Product::factory()->create([
            'name' => 'Glace',
            'type' => ProductType::Resource,
            'primary_unit_id' => $this->unit->getKey(),
        ]);
        Product::factory()->finishedGood()->create(['name' => 'Baguette', 'primary_unit_id' => $this->unit->getKey()]);

        // « Produits » = produit fini uniquement.
        $this->actingAs($this->superAdmin())
            ->get(route('products.index', ['group' => 'products']))
            ->assertOk()
            ->assertSee('Baguette')
            ->assertDontSee('Farine 50 kg')
            ->assertDontSee('Glace');

        // « Matières premières » = tout le reste.
        $this->actingAs($this->superAdmin())
            ->get(route('products.index', ['group' => 'materials']))
            ->assertOk()
            ->assertSee('Farine 50 kg')
            ->assertSee('Glace')
            ->assertDontSee('Baguette');
    }

    public function test_an_unknown_group_falls_back_to_products(): void
    {
        Product::factory()->create(['name' => 'Farine 50 kg', 'primary_unit_id' => $this->unit->getKey()]);
        Product::factory()->finishedGood()->create(['name' => 'Baguette', 'primary_unit_id' => $this->unit->getKey()]);

        $this->actingAs($this->superAdmin())
            ->get(route('products.index', ['group' => 'nonsense']))
            ->assertOk()
            ->assertSee('Baguette')
            ->assertDontSee('Farine 50 kg');
    }

    public function test_the_create_screen_asks_for_the_family(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Famille')
            ->assertSee('Produits')
            ->assertSee('Matières premières')
            ->assertSee('Nature (matières premières)')
            // Par défaut : un produit fini (famille « Produits »).
            ->assertSee('name="type" value="finished_good"', false);
    }

    public function test_the_create_screen_can_be_opened_on_the_materials_family(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('products.create', ['group' => 'materials']))
            ->assertOk()
            ->assertSee('name="type" value="raw_material"', false);
    }

    public function test_the_catalogue_offers_a_family_choice_modal(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Dans quelle famille souhaitez-vous créer ?')
            ->assertSee('Un produit fini, vendu en caisse ou au grossiste.')
            ->assertSee('Matières, consommables, emballages et ressources de production.');
    }

    public function test_each_product_type_has_a_sku_prefix(): void
    {
        $this->assertSame('PF', ProductType::FinishedGood->skuPrefix());
        $this->assertSame('MP', ProductType::RawMaterial->skuPrefix());
        $this->assertSame('SF', ProductType::SemiFinished->skuPrefix());
        $this->assertSame('CONS', ProductType::Consumable->skuPrefix());
        $this->assertSame('EMB', ProductType::Packaging->skuPrefix());
        $this->assertSame('RES', ProductType::Resource->skuPrefix());
    }

    public function test_a_sku_is_generated_from_the_product_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson(route('products.generate-sku', [
                'type' => ProductType::FinishedGood->value,
                'name' => 'Baguette traditionnelle',
            ]))
            ->assertOk()
            ->assertJson(['sku' => 'PF-BAGUETTE-TRADITIONNELLE']);

        $this->actingAs($this->superAdmin())
            ->getJson(route('products.generate-sku', [
                'type' => ProductType::RawMaterial->value,
                'name' => 'Farine 50 kg',
            ]))
            ->assertOk()
            ->assertJson(['sku' => 'MP-FARINE-50-KG']);

        // Accents translittérés.
        $this->actingAs($this->superAdmin())
            ->getJson(route('products.generate-sku', [
                'type' => ProductType::FinishedGood->value,
                'name' => 'Crème brûlée',
            ]))
            ->assertOk()
            ->assertJson(['sku' => 'PF-CREME-BRULEE']);
    }

    public function test_a_generated_sku_avoids_collisions(): void
    {
        Product::factory()->create(['sku' => 'PF-BAGUETTE', 'primary_unit_id' => $this->unit->getKey()]);
        Product::factory()->create(['sku' => 'PF-BAGUETTE-2', 'primary_unit_id' => $this->unit->getKey()]);

        $this->actingAs($this->superAdmin())
            ->getJson(route('products.generate-sku', [
                'type' => ProductType::FinishedGood->value,
                'name' => 'Baguette',
            ]))
            ->assertOk()
            ->assertJson(['sku' => 'PF-BAGUETTE-3']);
    }

    public function test_generating_a_sku_rejects_an_unknown_type(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson(route('products.generate-sku', ['type' => 'nonsense', 'name' => 'Pain']))
            ->assertStatus(422);
    }

    public function test_generating_a_sku_requires_a_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson(route('products.generate-sku', ['type' => ProductType::FinishedGood->value]))
            ->assertStatus(422);
    }

    public function test_generating_a_sku_requires_the_catalogue_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Cashier->value);

        $this->actingAs($user)
            ->getJson(route('products.generate-sku', [
                'type' => ProductType::FinishedGood->value,
                'name' => 'Baguette',
            ]))
            ->assertForbidden();
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
            ->assertRedirect(route('products.index', ['group' => 'products']));

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
