<?php

declare(strict_types=1);

namespace Tests\Feature\Production;

use App\Enums\ProductType;
use App\Enums\RecipeDeductionMode;
use App\Enums\Role;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeManagementTest extends TestCase
{
    use RefreshDatabase;

    private Unit $sack;

    private Product $flour;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->sack = Unit::factory()->create(['code' => 'sack', 'label' => 'Sac', 'symbol' => 'sac']);

        $this->flour = Product::factory()->create([
            'name' => 'Farine 50 kg',
            'type' => ProductType::RawMaterial,
            'primary_unit_id' => $this->sack->getKey(),
            'purchase_price' => 27_500,
        ]);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $this->sack->getKey(),
        ]);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->bread->getKey(),
            'name' => 'Pain de mie',
            'reference' => 'RCP-MIE',
            'yield_quantity' => 200,
            'production_time_minutes' => 90,
            'shelf_life_days' => 3,
            'allowed_loss_percent' => 2,
            'is_active' => '1',
            'note' => 'Recette de test',
            'items' => [
                [
                    'product_id' => $this->flour->getKey(),
                    'unit_id' => $this->sack->getKey(),
                    'quantity' => '1.5',
                    'deduction_mode' => RecipeDeductionMode::Quantity->value,
                    'unit_cost' => null,
                ],
            ],
        ], $overrides);
    }

    public function test_super_admin_can_open_the_creation_screen(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('production.recipes.create'))
            ->assertOk()
            ->assertSee('Nouvelle recette')
            ->assertSee('Rendement par lot')
            ->assertSee('Ressources par lot');
    }

    public function test_production_manager_can_create_a_recipe_with_items(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->post(route('production.recipes.store'), $this->payload())
            ->assertRedirect(route('production.recipes.index'))
            ->assertSessionHas('status');

        $recipe = Recipe::query()->where('reference', 'RCP-MIE')->firstOrFail();

        $this->assertSame('Pain de mie', $recipe->name);
        $this->assertSame(200, (int) $recipe->yield_quantity);
        $this->assertTrue($recipe->is_active);

        $this->assertSame(1, $recipe->items()->count());

        $item = $recipe->items()->firstOrFail();
        $this->assertSame(RecipeDeductionMode::Quantity, $item->deduction_mode);
        $this->assertSame($this->flour->getKey(), $item->product_id);
        $this->assertSame(0, $item->position);
    }

    public function test_item_positions_follow_the_submitted_order(): void
    {
        $resource = Product::factory()->create([
            'type' => ProductType::Resource,
            'primary_unit_id' => $this->sack->getKey(),
        ]);

        $payload = $this->payload([
            'items' => [
                [
                    'product_id' => $this->flour->getKey(),
                    'quantity' => '1',
                    'deduction_mode' => RecipeDeductionMode::Quantity->value,
                ],
                [
                    'product_id' => $resource->getKey(),
                    'quantity' => '1',
                    'deduction_mode' => RecipeDeductionMode::Monetary->value,
                    'unit_cost' => 2_000,
                ],
            ],
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.recipes.store'), $payload)
            ->assertRedirect();

        $recipe = Recipe::query()->firstOrFail();

        $this->assertSame(2, $recipe->items()->count());
        $this->assertSame($this->flour->getKey(), (int) $recipe->items()->where('position', 0)->value('product_id'));
        $this->assertSame($resource->getKey(), (int) $recipe->items()->where('position', 1)->value('product_id'));
    }

    public function test_the_finished_product_must_be_a_finished_good(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.recipes.store'), $this->payload([
                'product_id' => $this->flour->getKey(),
            ]))
            ->assertSessionHasErrors('product_id');
    }

    public function test_at_least_one_resource_is_required(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.recipes.store'), $this->payload(['items' => []]))
            ->assertSessionHasErrors('items');
    }

    public function test_a_finished_good_cannot_be_used_as_a_resource(): void
    {
        $other = Product::factory()->create([
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $this->sack->getKey(),
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.recipes.store'), $this->payload([
                'items' => [[
                    'product_id' => $other->getKey(),
                    'quantity' => '1',
                    'deduction_mode' => RecipeDeductionMode::Quantity->value,
                ]],
            ]))
            ->assertSessionHasErrors('items.0.product_id');
    }

    public function test_a_monetary_resource_requires_a_unit_cost(): void
    {
        $resource = Product::factory()->create([
            'type' => ProductType::Resource,
            'primary_unit_id' => $this->sack->getKey(),
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.recipes.store'), $this->payload([
                'items' => [[
                    'product_id' => $resource->getKey(),
                    'quantity' => '1',
                    'deduction_mode' => RecipeDeductionMode::Monetary->value,
                ]],
            ]))
            ->assertSessionHasErrors('items.0.unit_cost');
    }

    public function test_the_reference_must_be_unique(): void
    {
        Recipe::factory()->create(['reference' => 'RCP-MIE']);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('production.recipes.store'), $this->payload())
            ->assertSessionHasErrors('reference');
    }

    public function test_a_recipe_can_be_duplicated_from_an_existing_one(): void
    {
        $source = Recipe::factory()->create([
            'product_id' => $this->bread->getKey(),
            'name' => 'Pain traditionnel',
            'reference' => 'RCP-PAIN',
            'yield_quantity' => 300,
        ]);

        RecipeItem::factory()->create([
            'recipe_id' => $source->getKey(),
            'product_id' => $this->flour->getKey(),
            'unit_id' => $this->sack->getKey(),
            'quantity' => 1,
            'deduction_mode' => RecipeDeductionMode::Quantity,
            'position' => 0,
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('production.recipes.create', ['from' => $source->getKey()]))
            ->assertOk()
            ->assertSee('Recette initialisée depuis')
            ->assertSee('Pain traditionnel');
    }

    public function test_super_admin_can_open_the_edit_screen(): void
    {
        $recipe = Recipe::factory()->create([
            'product_id' => $this->bread->getKey(),
            'name' => 'Pain traditionnel',
            'reference' => 'RCP-PAIN',
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('production.recipes.edit', $recipe))
            ->assertOk()
            ->assertSee('Modifier la recette')
            ->assertSee('Pain traditionnel')
            ->assertSee('Enregistrer les modifications');
    }

    public function test_production_manager_can_update_the_header_and_replace_the_resources(): void
    {
        $recipe = Recipe::factory()->create([
            'product_id' => $this->bread->getKey(),
            'name' => 'Ancien nom',
            'reference' => 'RCP-OLD',
            'yield_quantity' => 100,
        ]);

        RecipeItem::factory()->create([
            'recipe_id' => $recipe->getKey(),
            'product_id' => $this->flour->getKey(),
            'quantity' => 1,
            'deduction_mode' => RecipeDeductionMode::Quantity,
            'position' => 0,
        ]);

        $this->actingAs($this->user(Role::ProductionManager))
            ->put(route('production.recipes.update', $recipe), [
                'product_id' => $this->bread->getKey(),
                'name' => 'Nouveau nom',
                'reference' => 'RCP-OLD',
                'yield_quantity' => 250,
                'items' => [
                    [
                        'product_id' => $this->flour->getKey(),
                        'unit_id' => $this->sack->getKey(),
                        'quantity' => '2',
                        'deduction_mode' => RecipeDeductionMode::Quantity->value,
                        'unit_cost' => null,
                    ],
                ],
            ])
            ->assertRedirect(route('production.recipes.index'))
            ->assertSessionHas('status');

        $recipe->refresh();

        $this->assertSame('Nouveau nom', $recipe->name);
        $this->assertSame(250, (int) $recipe->yield_quantity);
        $this->assertSame(1, $recipe->items()->count());
        $this->assertSame(
            $this->flour->getKey(),
            (int) $recipe->items()->where('position', 0)->value('product_id')
        );
    }

    public function test_updating_a_recipe_can_deactivate_it(): void
    {
        $recipe = Recipe::factory()->create([
            'product_id' => $this->bread->getKey(),
            'is_active' => true,
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->put(route('production.recipes.update', $recipe), [
                'product_id' => $this->bread->getKey(),
                'name' => $recipe->name,
                'yield_quantity' => $recipe->yield_quantity,
                'is_active' => '0',
                'items' => [[
                    'product_id' => $this->flour->getKey(),
                    'quantity' => '1',
                    'deduction_mode' => RecipeDeductionMode::Quantity->value,
                ]],
            ])
            ->assertRedirect(route('production.recipes.index'));

        $this->assertFalse($recipe->refresh()->is_active);
    }

    public function test_keeping_the_own_reference_is_allowed_but_another_recipes_reference_is_rejected(): void
    {
        $recipe = Recipe::factory()->create([
            'product_id' => $this->bread->getKey(),
            'reference' => 'RCP-KEEP',
        ]);

        // La référence de la recette courante reste acceptée.
        $this->actingAs($this->user(Role::SuperAdmin))
            ->put(route('production.recipes.update', $recipe), [
                'product_id' => $this->bread->getKey(),
                'name' => 'Toujours la même',
                'reference' => 'RCP-KEEP',
                'yield_quantity' => 100,
                'items' => [[
                    'product_id' => $this->flour->getKey(),
                    'quantity' => '1',
                    'deduction_mode' => RecipeDeductionMode::Quantity->value,
                ]],
            ])
            ->assertRedirect(route('production.recipes.index'));

        // La référence d'une autre recette est refusée.
        Recipe::factory()->create(['reference' => 'RCP-AUTRE']);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->put(route('production.recipes.update', $recipe), [
                'product_id' => $this->bread->getKey(),
                'name' => 'Conflit',
                'reference' => 'RCP-AUTRE',
                'yield_quantity' => 100,
                'items' => [[
                    'product_id' => $this->flour->getKey(),
                    'quantity' => '1',
                    'deduction_mode' => RecipeDeductionMode::Quantity->value,
                ]],
            ])
            ->assertSessionHasErrors('reference');
    }

    public function test_a_cashier_cannot_manage_recipes(): void
    {
        $recipe = Recipe::factory()->create(['product_id' => $this->bread->getKey()]);

        $this->actingAs($this->user(Role::Cashier))
            ->get(route('production.recipes.create'))
            ->assertForbidden();

        $this->actingAs($this->user(Role::Cashier))
            ->get(route('production.recipes.edit', $recipe))
            ->assertForbidden();

        $this->actingAs($this->user(Role::Cashier))
            ->post(route('production.recipes.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs($this->user(Role::Cashier))
            ->put(route('production.recipes.update', $recipe), $this->payload())
            ->assertForbidden();

        $this->assertSame(1, Recipe::query()->count());
    }

    public function test_the_screens_require_authentication(): void
    {
        $recipe = Recipe::factory()->create(['product_id' => $this->bread->getKey()]);

        $this->get(route('production.recipes.create'))->assertRedirect('/login');
        $this->post(route('production.recipes.store'), [])->assertRedirect('/login');
        $this->get(route('production.recipes.edit', $recipe))->assertRedirect('/login');
        $this->put(route('production.recipes.update', $recipe), [])->assertRedirect('/login');
    }
}
