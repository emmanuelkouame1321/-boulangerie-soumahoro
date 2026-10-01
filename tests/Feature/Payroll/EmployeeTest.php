<?php

declare(strict_types=1);

namespace Tests\Feature\Payroll;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
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

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_a_super_admin_can_see_the_employee_screens(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $this->actingAs($admin)->get(route('employees.create'))->assertOk();
    }

    public function test_an_employee_can_be_created_from_the_screen(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('employees.store'), [
                'first_name' => 'Awa',
                'last_name' => 'Koné',
                'position' => 'worker',
                'contract_type' => 'cdi',
                'base_salary' => 150_000,
                'status' => 'active',
                'hired_on' => '2025-01-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'first_name' => 'Awa',
            'last_name' => 'Koné',
            'bakery_id' => $this->bakery->getKey(),
            'base_salary' => 150_000,
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Awa');
    }

    public function test_the_fiche_renders_with_payroll_history(): void
    {
        $employee = Employee::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'first_name' => 'Kofi',
            'last_name' => 'Traoré',
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Kofi Traoré')
            ->assertSee('Historique de paie');
    }

    public function test_a_production_manager_cannot_reach_the_employees(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('employees.index'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('employees.index'))->assertRedirect('/login');
        $this->get(route('employees.create'))->assertRedirect('/login');
    }
}
