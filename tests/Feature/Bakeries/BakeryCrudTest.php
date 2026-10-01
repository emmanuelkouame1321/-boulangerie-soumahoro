<?php

declare(strict_types=1);

namespace Tests\Feature\Bakeries;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BakeryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_cannot_reach_the_bakery_admin(): void
    {
        $this->get('/settings/bakeries')->assertRedirect('/login');
    }

    public function test_super_admin_can_list_bakeries(): void
    {
        Bakery::factory()->create(['name' => 'Boulangerie Cocody']);

        $this->actingAs($this->superAdmin())
            ->get('/settings/bakeries')
            ->assertOk()
            ->assertSee('Boulangerie Cocody');
    }

    public function test_super_admin_can_create_a_bakery(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/settings/bakeries', [
                'code' => 'BLG-NEW',
                'name' => 'Boulangerie Nouvelle',
                'address' => 'Rue du Marché',
                'phone' => '+225 07 00 00 09',
                'status' => 'active',
                'manager_id' => null,
                'notes' => null,
            ])
            ->assertRedirect('/settings/bakeries');

        $this->assertDatabaseHas('bakeries', [
            'code' => 'BLG-NEW',
            'name' => 'Boulangerie Nouvelle',
        ]);
    }

    public function test_the_code_must_be_unique(): void
    {
        Bakery::factory()->create(['code' => 'BLG-DUP']);

        $this->actingAs($this->superAdmin())
            ->post('/settings/bakeries', [
                'code' => 'BLG-DUP',
                'name' => 'Doublon',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_super_admin_can_update_a_bakery(): void
    {
        $bakery = Bakery::factory()->create(['name' => 'Ancien nom']);

        $this->actingAs($this->superAdmin())
            ->put("/settings/bakeries/{$bakery->id}", [
                'code' => $bakery->code,
                'name' => 'Nouveau nom',
                'status' => 'inactive',
                'address' => null,
                'phone' => null,
                'manager_id' => null,
                'notes' => null,
            ])
            ->assertRedirect('/settings/bakeries');

        $bakery->refresh();

        $this->assertSame('Nouveau nom', $bakery->name);
        $this->assertSame('inactive', $bakery->status->value);
    }

    public function test_a_bakery_is_never_deleted(): void
    {
        $bakery = Bakery::factory()->create();

        $this->actingAs($this->superAdmin())
            ->delete("/settings/bakeries/{$bakery->id}")
            ->assertForbidden();

        $this->assertNotSoftDeleted($bakery);
    }

    public function test_a_sales_manager_cannot_administer_bakeries(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);

        $this->actingAs($user)->get('/settings/bakeries')->assertForbidden();
        $this->actingAs($user)->get('/settings/bakeries/create')->assertForbidden();
        $this->actingAs($user)
            ->post('/settings/bakeries', ['code' => 'BLG-X', 'name' => 'X', 'status' => 'active'])
            ->assertForbidden();
    }

    public function test_a_production_manager_cannot_administer_bakeries(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::ProductionManager->value);

        $this->actingAs($user)->get('/settings/bakeries')->assertForbidden();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }
}
