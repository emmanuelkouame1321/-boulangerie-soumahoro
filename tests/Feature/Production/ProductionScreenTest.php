<?php

declare(strict_types=1);

namespace Tests\Feature\Production;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Enums\ProductType;
use App\Enums\RecipeDeductionMode;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionScreenTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $flour;

    private Product $bread;

    private Recipe $recipe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();

        $sack = Unit::factory()->create(['code' => 'sack', 'label' => 'Sac', 'symbol' => 'sac']);
        $piece = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->flour = Product::factory()->create([
            'name' => 'Farine 50 kg',
            'type' => ProductType::RawMaterial,
            'primary_unit_id' => $sack->getKey(),
            'purchase_price' => 27_500,
        ]);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $piece->getKey(),
            'shelf_life_days' => 1,
        ]);

        $this->recipe = Recipe::factory()->create([
            'product_id' => $this->bread->getKey(),
            'name' => 'Pain traditionnel',
            'yield_quantity' => 300,
        ]);

        $this->recipe->items()->create([
            'product_id' => $this->flour->getKey(),
            'unit_id' => $sack->getKey(),
            'quantity' => 1,
            'deduction_mode' => RecipeDeductionMode::Quantity,
            'unit_cost' => null,
            'position' => 0,
        ]);
    }

    private function stockFlour(string $quantity): void
    {
        app(StockMovementService::class)->apply(
            bakery: $this->bakery,
            product: $this->flour,
            type: StockMovementType::SupplierIn,
            quantity: $quantity,
            reason: 'Stock de test',
            idempotencyKey: "screen:flour:{$this->bakery->getKey()}",
        );
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_a_super_admin_can_see_the_production_screens(): void
    {
        $this->stockFlour('24');

        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('production.index'))->assertOk();
        $this->actingAs($admin)->get(route('production.recipes.index'))->assertOk()->assertSee('Pain traditionnel');
        $this->actingAs($admin)
            ->get(route('production.create', ['recipe_id' => $this->recipe->getKey(), 'batch_count' => 1]))
            ->assertOk()
            ->assertSee('Farine 50 kg');
    }

    public function test_a_production_can_be_validated_from_the_screen(): void
    {
        $this->stockFlour('24');

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'recipe_id' => $this->recipe->getKey(),
                'batch_count' => 1,
                'actual_quantity' => 292,
                'gap_reason' => 'technical_loss',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, ProductionOrder::query()->count());
        $this->assertSame(0, bccomp(app(StockService::class)->physical($this->bread, $this->bakery), '292', 4));

        // L'écran de détail rend le lot créé.
        $order = ProductionOrder::query()->firstOrFail();
        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('production.show', $order))
            ->assertOk()
            ->assertSee('Lots produits');
    }

    public function test_an_insufficient_stock_is_reported_on_the_screen(): void
    {
        // Aucune farine en stock.
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'recipe_id' => $this->recipe->getKey(),
                'batch_count' => 1,
                'actual_quantity' => 300,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, ProductionOrder::query()->count());
    }

    public function test_a_gap_without_a_reason_is_reported_on_the_screen(): void
    {
        $this->stockFlour('24');

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.store'), [
                'bakery_id' => $this->bakery->getKey(),
                'recipe_id' => $this->recipe->getKey(),
                'batch_count' => 1,
                'actual_quantity' => 250,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, ProductionOrder::query()->count());
    }

    public function test_a_cashier_cannot_reach_the_production_screens(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('production.index'))
            ->assertForbidden();

        $this->actingAs($this->user(Role::Cashier))
            ->get(route('production.create'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('production.index'))->assertRedirect('/login');
        $this->get(route('production.create'))->assertRedirect('/login');
    }
}
