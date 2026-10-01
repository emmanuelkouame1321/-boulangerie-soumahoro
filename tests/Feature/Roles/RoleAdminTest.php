<?php

declare(strict_types=1);

namespace Tests\Feature\Roles;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

/**
 * Rôles et permissions (cahier §20.4).
 */
class RoleAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    public function test_super_admin_can_list_roles(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('settings.roles.index'))
            ->assertOk()
            ->assertSee('sales-manager');
    }

    public function test_super_admin_can_create_a_role(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('settings.roles.store'), [
                'name' => 'audit-manager',
                'permissions' => ['audit.view', 'report.view'],
            ])
            ->assertRedirect(route('settings.roles.index'));

        $role = SpatieRole::query()->where('name', 'audit-manager')->firstOrFail();

        $this->assertTrue($role->hasPermissionTo('audit.view'));
        $this->assertTrue($role->hasPermissionTo('report.view'));
    }

    public function test_permissions_can_be_updated(): void
    {
        $role = SpatieRole::query()->where('name', 'sales-manager')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->put(route('settings.roles.update', $role), [
                'name' => 'sales-manager',
                'permissions' => ['sales.view'],
            ])
            ->assertRedirect(route('settings.roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo('sales.view'));
        $this->assertFalse($role->fresh()->hasPermissionTo('loss.create'));
    }

    public function test_the_super_admin_role_cannot_be_modified(): void
    {
        $role = SpatieRole::query()->where('name', 'super-admin')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->get(route('settings.roles.edit', $role))
            ->assertForbidden();
    }

    public function test_a_role_without_permission_cannot_reach_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::ProductionManager->value);

        $this->actingAs($user)
            ->get(route('settings.roles.index'))
            ->assertForbidden();
    }
}
