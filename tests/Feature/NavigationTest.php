<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_menu_only_shows_modules_that_exist(): void
    {
        $labels = $this->labels($this->userWithRole(Role::SuperAdmin));

        // Les écrans livrés sont présents…
        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Production', $labels);
        $this->assertContains('Stocks', $labels);
        $this->assertContains('Ventes', $labels);
        $this->assertContains('Boulangeries', $labels);

        // …et chaque entrée du menu pointe vers une URL non vide (aucun lien mort).
        foreach (Navigation::forUser($this->userWithRole(Role::SuperAdmin)) as $group) {
            foreach ($group['items'] as $item) {
                $this->assertNotEmpty($item['href']);
            }
        }
    }

    public function test_a_sales_manager_does_not_see_admin_modules(): void
    {
        $labels = $this->labels($this->userWithRole(Role::SalesManager));

        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Ventes', $labels);

        // Modules d'administration : permissions absentes.
        $this->assertNotContains('Boulangeries', $labels);
        $this->assertNotContains('Utilisateurs', $labels);
        $this->assertNotContains('Audit', $labels);

        // Le Responsable vente n'a pas la permission Production.
        $this->assertNotContains('Production', $labels);
    }

    /**
     * @return list<string>
     */
    private function labels(User $user): array
    {
        $labels = [];

        foreach (Navigation::forUser($user) as $group) {
            foreach ($group['items'] as $item) {
                $labels[] = $item['label'];
            }
        }

        return $labels;
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
