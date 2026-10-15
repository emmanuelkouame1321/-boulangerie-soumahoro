<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Livewire\Search\GlobalSearch;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Recherche globale : la palette ne doit montrer que ce que l'utilisateur a
 * le droit de voir, sur les sites auxquels il est affecté.
 */
class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Bakery $otherBakery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create(['name' => 'Boulangerie Cocody']);
        $this->otherBakery = Bakery::factory()->create(['name' => 'Boulangerie Yopougon']);
    }

    private function user(Role $role, Bakery $bakery): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    /**
     * Ouvre la session sur un site : hors requête HTTP (Livewire::test), le
     * contexte de site n'est pas posé par le middleware.
     */
    private function actingOn(User $user, Bakery $bakery): void
    {
        $this->actingAs($user);

        $context = app(BakeryContext::class);
        $context->setUser($user);
        $context->setCurrent($bakery);
    }

    public function test_the_palette_is_mounted_in_the_application_shell(): void
    {
        $this->actingOn($this->user(Role::Cashier, $this->bakery), $this->bakery);

        // « Terme de recherche globale » n'existe que dans la vue du composant :
        // sa présence prouve que la palette est bien montée dans la coquille.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recherche globale')
            ->assertSee('Terme de recherche globale')
            ->assertSee('Ctrl K');
    }

    public function test_a_term_shorter_than_the_minimum_returns_nothing(): void
    {
        Product::factory()->create(['name' => 'Baguette tradition', 'sku' => 'PF-BAGUETTE-TRAD']);

        $this->actingOn($this->user(Role::SuperAdmin, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'b')
            ->assertDontSee('Baguette tradition')
            ->assertSee('Saisissez au moins 2 caractères');
    }

    public function test_results_are_grouped_by_module(): void
    {
        Product::factory()->create(['name' => 'Baguette tradition', 'sku' => 'PF-BAGUETTE-TRAD']);
        Customer::factory()->create(['bakery_id' => $this->bakery->getKey(), 'name' => 'Baguette Konan']);
        Customer::factory()->create(['bakery_id' => $this->bakery->getKey(), 'name' => 'Traiteur Diallo']);

        $this->actingOn($this->user(Role::SuperAdmin, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'baguette')
            ->assertSee('Produits')
            ->assertSee('Clients')
            ->assertSee('PF-BAGUETTE-TRAD')
            ->assertSee('Baguette Konan')
            ->assertDontSee('Traiteur Diallo');
    }

    public function test_a_module_without_permission_is_never_searched(): void
    {
        Product::factory()->create(['name' => 'Baguette tradition', 'sku' => 'PF-BAGUETTE-TRAD']);
        Sale::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'reference' => 'SALE-BAGUETTE-0001',
        ]);

        // Le caissier n'a ni `product.view` ni `purchasing.view` : le moteur
        // ne doit même pas interroger ces tables.
        $this->actingOn($this->user(Role::Cashier, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'baguette')
            ->assertDontSee('Produits')
            ->assertDontSee('PF-BAGUETTE-TRAD')
            ->assertDontSee("Commandes d'achat")
            ->assertSee('Ventes')
            ->assertSee('SALE-BAGUETTE-0001');
    }

    public function test_results_are_isolated_by_site(): void
    {
        Customer::factory()->create(['bakery_id' => $this->otherBakery->getKey(), 'name' => 'Client Yopougon']);
        Customer::factory()->create(['bakery_id' => $this->bakery->getKey(), 'name' => 'Client Cocody']);

        $this->actingOn($this->user(Role::SalesManager, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'Client')
            ->assertSee('Client Cocody')
            ->assertDontSee('Client Yopougon');
    }

    public function test_a_transfer_is_visible_from_both_of_its_sites_only(): void
    {
        $product = Product::factory()->create(['name' => 'Farine 50 kg']);

        StockTransfer::factory()->create([
            'reference' => 'TRF-COCODY-0001',
            'bakery_id' => $this->bakery->getKey(),
            'destination_bakery_id' => $this->otherBakery->getKey(),
            'product_id' => $product->getKey(),
        ]);

        StockTransfer::factory()->create([
            'reference' => 'TRF-AILLEURS-0001',
            'bakery_id' => $this->otherBakery->getKey(),
            'destination_bakery_id' => Bakery::factory()->create()->getKey(),
            'product_id' => $product->getKey(),
        ]);

        $this->actingOn($this->user(Role::ProductionManager, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'TRF-')
            ->assertSee('TRF-COCODY-0001')
            ->assertDontSee('TRF-AILLEURS-0001');
    }

    public function test_every_group_is_capped(): void
    {
        foreach (range(1, 6) as $index) {
            Product::factory()->create(['name' => 'Baguette '.$index, 'sku' => 'PF-BAGUETTE-'.$index]);
        }

        $this->actingOn($this->user(Role::SuperAdmin, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'Baguette')
            ->assertSee('PF-BAGUETTE-1')
            ->assertSee('PF-BAGUETTE-5')
            ->assertDontSee('PF-BAGUETTE-6');
    }

    public function test_result_labels_are_escaped(): void
    {
        Product::factory()->create([
            'name' => '<script>alert(1)</script>',
            'sku' => 'XSS-0001',
        ]);

        $this->actingOn($this->user(Role::SuperAdmin, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'script')
            ->assertSee('XSS-0001')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_closing_the_palette_clears_the_term(): void
    {
        $this->actingOn($this->user(Role::SuperAdmin, $this->bakery), $this->bakery);

        Livewire::test(GlobalSearch::class)
            ->set('term', 'baguette')
            ->call('clear')
            ->assertSet('term', '');
    }
}
