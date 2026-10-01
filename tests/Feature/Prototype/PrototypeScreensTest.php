<?php

declare(strict_types=1);

namespace Tests\Feature\Prototype;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque écran de « BakeryOS – Prototype (charte caisse).html » doit se
 * rendre pour un Super-Admin, et rester inaccessible aux autres rôles
 * lorsqu'une permission est requise (cahier §31).
 */
class PrototypeScreensTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function screenRouteNames(): array
    {
        return [
            'dashboard',
            'pos',
            'production.index',
            'inventory.index',
            'purchasing.orders.index',
            'sales.index',
            'losses.index',
            'customers.index',
            'wholesalers.index',
            'rebates.index',
            'suppliers.index',
            'employees.index',
            'payroll.index',
            'expenses.index',
            'reports.index',
            'notifications.index',
            'settings.index',
            'settings.users.index',
            'settings.roles.index',
            'settings.audit.index',
            'bakeries.index',
        ];
    }

    public function test_every_prototype_screen_renders_for_a_super_admin(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        foreach ($this->screenRouteNames() as $name) {
            $this->actingAs($user)
                ->get(route($name))
                ->assertOk();
        }
    }

    public function test_a_role_without_the_permission_cannot_reach_a_screen(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::ProductionManager->value);

        // Le Responsable production n'a pas accès à la paie.
        $this->actingAs($user)
            ->get(route('payroll.index'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        foreach ($this->screenRouteNames() as $name) {
            $this->get(route($name))->assertRedirect('/login');
        }
    }
}
