<?php

declare(strict_types=1);

namespace Tests\Feature\Sites;

use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Livewire\Sites\SiteSwitcher;
use App\Models\Bakery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BakeryContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_opens_on_the_consolidated_view(): void
    {
        Bakery::factory()->count(2)->create();

        $this->actingAs($this->superAdmin())->get('/dashboard')->assertOk();

        $context = app(BakeryContext::class);

        $this->assertNull($context->current());
        $this->assertTrue($context->allowsAllSites());
        // Vision consolidée : les requêtes ne sont pas filtrées sur un site.
        $this->assertFalse($context->shouldScopeQueries());
    }

    public function test_a_regular_user_is_scoped_to_their_default_bakery(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);

        $default = Bakery::factory()->create(['name' => 'Site par défaut']);
        $other = Bakery::factory()->create(['name' => 'Autre site']);

        $user->bakeries()->sync([
            $default->getKey() => ['is_default' => true],
            $other->getKey() => ['is_default' => false],
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $context = app(BakeryContext::class);

        $this->assertNotNull($context->current());
        $this->assertSame($default->getKey(), $context->current()->getKey());
        // Un responsable est toujours cantonne a son site : fail-closed.
        $this->assertTrue($context->shouldScopeQueries());
        $this->assertFalse($context->allowsAllSites());
    }

    public function test_a_user_without_any_bakery_sees_no_site_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);

        Bakery::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $context = app(BakeryContext::class);

        $this->assertNull($context->current());
        // Aucun site accessible : on ferme l'acces plutot que de l'ouvrir.
        $this->assertTrue($context->shouldScopeQueries());
    }

    public function test_switching_site_updates_the_context(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);

        $first = Bakery::factory()->create();
        $second = Bakery::factory()->create();

        $user->bakeries()->sync([
            $first->getKey() => ['is_default' => true],
            $second->getKey() => ['is_default' => false],
        ]);

        $this->actingAs($user);

        Livewire::test(SiteSwitcher::class)
            ->call('select', $second->getKey())
            ->assertRedirect(route('dashboard'));

        $this->assertSame($second->getKey(), app(BakeryContext::class)->current()?->getKey());
    }

    public function test_a_user_cannot_switch_to_a_site_they_are_not_assigned_to(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);

        $mine = Bakery::factory()->create();
        $foreign = Bakery::factory()->create();

        $user->bakeries()->sync([$mine->getKey() => ['is_default' => true]]);

        $this->actingAs($user);

        // L'identifiant vient du navigateur : il n'est jamais fait confiance.
        Livewire::test(SiteSwitcher::class)
            ->call('select', $foreign->getKey())
            ->assertForbidden();
    }

    public function test_only_a_super_admin_can_select_the_consolidated_view(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);

        $bakery = Bakery::factory()->create();
        $user->bakeries()->sync([$bakery->getKey() => ['is_default' => true]]);

        $this->actingAs($user);

        // La vision consolidée est un privilège de Super-Admin.
        Livewire::test(SiteSwitcher::class)
            ->call('select', BakeryContext::ALL_SITES)
            ->assertForbidden();
    }

    public function test_the_switcher_is_rendered_in_the_topbar(): void
    {
        $bakery = Bakery::factory()->create(['name' => 'Boulangerie Cocody']);

        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Boulangerie Cocody');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }
}
