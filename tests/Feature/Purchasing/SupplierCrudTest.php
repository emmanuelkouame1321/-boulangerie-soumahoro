<?php

declare(strict_types=1);

namespace Tests\Feature\Purchasing;

use App\Enums\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_a_super_admin_can_create_a_supplier(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('suppliers.store'), [
                'name' => 'Minoterie du Nord',
                'contact_name' => 'Koffi Ange',
                'phone' => '07 00 00 40 04',
                'email' => 'contact@minoterie-nord.test',
                'categories' => 'Farine',
                'payment_terms' => '30 jours',
                'delivery_delay_days' => 7,
                'is_active' => '1',
            ])
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Minoterie du Nord',
            'contact_name' => 'Koffi Ange',
        ]);
    }

    public function test_a_supplier_requires_a_name(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('suppliers.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_a_super_admin_can_update_a_supplier(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Ancien nom']);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->put(route('suppliers.update', $supplier), [
                'name' => 'Nouveau nom',
                'is_active' => '1',
            ])
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->getKey(),
            'name' => 'Nouveau nom',
        ]);
    }

    public function test_a_role_without_supplier_permission_cannot_reach_the_list(): void
    {
        $this->actingAs($this->user(Role::SalesManager))
            ->get(route('suppliers.index'))
            ->assertForbidden();
    }

    public function test_a_role_without_supplier_manage_cannot_create_a_supplier(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->post(route('suppliers.store'), ['name' => 'Tentative'])
            ->assertForbidden();
    }

    public function test_a_production_manager_can_manage_suppliers(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('suppliers.index'))
            ->assertOk();
    }
}
