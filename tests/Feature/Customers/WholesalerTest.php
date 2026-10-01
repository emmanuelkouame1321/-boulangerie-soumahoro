<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use App\Models\Wholesaler;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WholesalerTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
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

    public function test_a_super_admin_can_see_the_wholesaler_screens(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('wholesalers.index'))->assertOk();
        $this->actingAs($admin)->get(route('wholesalers.create'))->assertOk();
    }

    public function test_a_wholesaler_can_be_created_from_the_screen(): void
    {
        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('wholesalers.store'), [
                'name' => 'Boulangerie ABC',
                'price_mode' => 'wholesale',
                'usual_quantity' => 120,
                'zone' => 'Centre',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('wholesalers', [
            'name' => 'Boulangerie ABC',
            'bakery_id' => $this->bakery->getKey(),
            'usual_quantity' => '120.0000',
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('wholesalers.index'))
            ->assertOk()
            ->assertSee('Boulangerie ABC');
    }

    public function test_the_fiche_renders_for_a_wholesaler(): void
    {
        $wholesaler = Wholesaler::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'name' => 'Hôtel XYZ',
        ]);

        $this->actingAs($this->user(Role::SalesManager))
            ->get(route('wholesalers.show', $wholesaler))
            ->assertOk()
            ->assertSee('Hôtel XYZ')
            ->assertSee('Ristourne');
    }

    public function test_a_production_manager_cannot_reach_the_wholesaler_screens(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('wholesalers.index'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('wholesalers.index'))->assertRedirect('/login');
        $this->get(route('wholesalers.create'))->assertRedirect('/login');
    }
}
