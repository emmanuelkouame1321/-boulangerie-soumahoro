<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
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

    public function test_a_sales_manager_can_create_a_customer(): void
    {
        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('customers.store'), [
                'name' => 'Boutique du marché',
                'phone' => '+225 07 01 02 03 04',
            ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('customers', [
            'name' => 'Boutique du marché',
            'bakery_id' => $this->bakery->getKey(),
        ]);
    }

    public function test_a_customer_requires_a_name(): void
    {
        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('customers.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_a_customer_can_be_updated(): void
    {
        $customer = Customer::factory()->create(['bakery_id' => $this->bakery->getKey()]);

        $this->actingAs($this->user(Role::SalesManager))
            ->put(route('customers.update', $customer), ['name' => 'Client renommé'])
            ->assertRedirect(route('customers.index'));

        $this->assertSame('Client renommé', $customer->fresh()->name);
    }

    public function test_a_role_without_permission_cannot_reach_the_list(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('customers.index'))
            ->assertOk();

        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('customers.index'))
            ->assertForbidden();
    }

    public function test_a_cashier_cannot_manage_customers(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('customers.create'))
            ->assertForbidden();
    }
}
