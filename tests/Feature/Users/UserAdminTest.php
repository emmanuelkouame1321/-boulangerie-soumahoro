<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gestion des utilisateurs : comptes, rôles et affectation multi-sites
 * (cahier §20.4).
 */
class UserAdminTest extends TestCase
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

    public function test_super_admin_can_list_users(): void
    {
        User::factory()->create(['name' => 'Awa Kouassi']);

        $this->actingAs($this->superAdmin())
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertSee('Awa Kouassi');
    }

    public function test_super_admin_can_create_a_user_with_sites(): void
    {
        $bakery = Bakery::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('settings.users.store'), [
                'name' => 'Jean Kouassi',
                'email' => 'jean@soumahoro.test',
                'password' => 'motdepasse1',
                'password_confirmation' => 'motdepasse1',
                'roles' => [Role::SalesManager->value],
                'bakeries' => [$bakery->getKey()],
                'default_bakery_id' => $bakery->getKey(),
            ])
            ->assertRedirect(route('settings.users.index'));

        $user = User::query()->where('email', 'jean@soumahoro.test')->firstOrFail();

        $this->assertTrue($user->hasRole(Role::SalesManager->value));
        $this->assertTrue($user->bakeries()->where('bakeries.id', $bakery->getKey())->exists());
        $this->assertDatabaseHas('bakery_user', [
            'user_id' => $user->getKey(),
            'bakery_id' => $bakery->getKey(),
            'is_default' => true,
        ]);
    }

    public function test_default_site_must_be_one_of_the_assigned_sites(): void
    {
        $assigned = Bakery::factory()->create();
        $other = Bakery::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('settings.users.store'), [
                'name' => 'Test',
                'email' => 'test@soumahoro.test',
                'password' => 'motdepasse1',
                'password_confirmation' => 'motdepasse1',
                'bakeries' => [$assigned->getKey()],
                'default_bakery_id' => $other->getKey(),
            ])
            ->assertSessionHasErrors('default_bakery_id');
    }

    public function test_a_role_without_permission_cannot_reach_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::ProductionManager->value);

        $this->actingAs($user)
            ->get(route('settings.users.index'))
            ->assertForbidden();
    }
}
