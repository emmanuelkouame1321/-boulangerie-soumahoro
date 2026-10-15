<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use App\Support\Configuration;
use App\Support\Navigation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Cache des données de configuration (cahier §48).
 *
 * Ce qui est mémorisé n'est jamais recalculé sans raison : l'arbre de
 * navigation (existence des routes) et les structures dérivées de la
 * configuration (plan de permissions groupé, rôles). Les clés sont
 * indexées par le contenu configuré et par la table des routes : une
 * modification produit une nouvelle clé, donc aucune purge manuelle n'est
 * nécessaire — et rien de périmé ne peut être servi.
 */
class ConfigurationCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        Navigation::forget();
        Configuration::forget();
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach(Bakery::factory()->create()->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_the_navigation_tree_is_resolved_once_and_cached(): void
    {
        $this->assertFalse(Cache::has(Navigation::cacheKey()));

        $groups = Navigation::forUser($this->user(Role::SuperAdmin));

        $this->assertTrue(Cache::has(Navigation::cacheKey()));
        $this->assertNotEmpty($groups);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $this->assertNotEmpty($item['href']);
                $this->assertIsBool($item['active']);
            }
        }
    }

    public function test_the_navigation_tree_drops_entries_whose_route_does_not_exist(): void
    {
        config(['navigation.groups' => [[
            'label' => 'Test',
            'items' => [
                ['label' => 'Écran fantôme', 'path' => 'ecran-fantome', 'icon' => 'box', 'permission' => null],
                ['label' => 'Dashboard', 'path' => 'dashboard', 'icon' => 'layout-dashboard', 'permission' => null],
            ],
        ]]]);

        $groups = Navigation::forUser($this->user(Role::SuperAdmin));

        $this->assertCount(1, $groups);
        $this->assertSame(['Dashboard'], array_column($groups[0]['items'], 'label'));
    }

    public function test_the_navigation_cache_key_follows_the_configuration(): void
    {
        $before = Navigation::cacheKey();

        config(['navigation.groups' => []]);

        $this->assertNotSame($before, Navigation::cacheKey());
    }

    public function test_the_permission_catalogue_is_grouped_by_module_and_cached(): void
    {
        $this->assertFalse(Cache::has(Configuration::cacheKey('permission-groups')));

        $groups = Configuration::permissionGroups();

        $this->assertTrue(Cache::has(Configuration::cacheKey('permission-groups')));
        $this->assertSame(['report.view', 'report.view-commercial', 'report.export'], $groups['report']);

        // Le regroupement couvre exactement le catalogue, dans un autre ordre.
        $flattened = array_merge(...array_values($groups));
        sort($flattened);

        $catalogue = Configuration::permissionCatalogue();
        sort($catalogue);

        $this->assertSame($catalogue, $flattened);
    }

    public function test_the_configuration_cache_key_follows_the_configuration(): void
    {
        $before = Configuration::cacheKey('permissions');

        config(['boulangerie.permissions' => ['report.view']]);

        $this->assertNotSame($before, Configuration::cacheKey('permissions'));
        $this->assertSame(['report.view'], Configuration::permissionCatalogue());
    }

    public function test_role_labels_and_permissions_come_from_the_cached_configuration(): void
    {
        $this->assertSame('Super-Admin', Role::SuperAdmin->label());
        $this->assertSame(['*'], Role::SuperAdmin->permissions());
        $this->assertSame('Responsable production', Role::ProductionManager->label());
        $this->assertContains('inventory.view', Role::ProductionManager->permissions());

        // Un rôle hors configuration retombe sur le titre de son nom.
        $this->assertSame('Audit Manager', Configuration::role('audit-manager')['label']);
        $this->assertSame([], Configuration::role('audit-manager')['permissions']);
    }

    public function test_the_menu_is_still_filtered_by_permission_when_served_from_cache(): void
    {
        // Premier appel : l'arbre est résolu (table des routes) et mémorisé…
        $adminLabels = $this->labels(Navigation::forUser($this->user(Role::SuperAdmin)));

        $this->assertTrue(Cache::has(Navigation::cacheKey()));
        $this->assertContains('Audit', $adminLabels);

        // …le suivant relit l'arbre depuis le cache, sans élargir les droits.
        $salesLabels = $this->labels(Navigation::forUser($this->user(Role::SalesManager)));

        $this->assertContains('Ventes', $salesLabels);
        $this->assertNotContains('Audit', $salesLabels);
        $this->assertNotContains('Boulangeries', $salesLabels);
    }

    public function test_the_roles_screen_still_lists_the_cached_catalogue(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('settings.roles.create'))
            ->assertOk()
            ->assertSee('report.view-commercial');
    }

    /**
     * @param  list<array{label: string|null, items: list<array<string, mixed>>}>  $groups
     * @return list<string>
     */
    private function labels(array $groups): array
    {
        $labels = [];

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $labels[] = (string) $item['label'];
            }
        }

        return $labels;
    }
}
