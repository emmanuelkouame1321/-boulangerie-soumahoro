<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Export;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Absence de N+1 sur les écrans de liste (cahier §48).
 *
 * Le contrôle est structurel plutôt que chiffré : le nombre de requêtes
 * d'un écran ne doit dépendre que du nombre de *colonnes* chargées, jamais
 * du nombre de lignes. On mesure donc l'écran avec un jeu de données
 * réduit, puis élargi, et on exige le même nombre de requêtes — un N+1
 * fait mécaniquement échouer la comparaison.
 */
class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
        $this->unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);
    }

    public function test_list_screens_do_not_query_once_per_row(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $screens = [
            'Produits' => [
                route('products.index', ['group' => 'materials']),
                fn (int $count): array => Product::factory()->count($count)->create([
                    'primary_unit_id' => $this->unit->getKey(),
                ])->all(),
            ],
            'Stocks' => [
                route('inventory.index'),
                fn (int $count): array => Stock::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                ])->all(),
            ],
            'Mouvements' => [
                route('inventory.movements'),
                fn (int $count): array => StockMovement::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                ])->all(),
            ],
            'Clients' => [
                route('customers.index'),
                fn (int $count): array => Customer::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                ])->all(),
            ],
            'Ventes' => [
                route('sales.index'),
                fn (int $count): array => Sale::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                    'created_by' => $admin->getKey(),
                ])->all(),
            ],
            'Achats' => [
                route('purchasing.orders.index'),
                fn (int $count): array => PurchaseOrder::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                    'created_by' => $admin->getKey(),
                ])->all(),
            ],
            'Production' => [
                route('production.index'),
                fn (int $count): array => ProductionOrder::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                    'created_by' => $admin->getKey(),
                ])->all(),
            ],
            'Charges' => [
                route('expenses.index'),
                fn (int $count): array => Expense::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                    'created_by' => $admin->getKey(),
                ])->all(),
            ],
            'Alertes' => [
                route('notifications.index'),
                fn (int $count): array => StockAlert::factory()->count($count)->create([
                    'bakery_id' => $this->bakery->getKey(),
                ])->all(),
            ],
            'Audit' => [
                route('settings.audit.index'),
                function (int $count) use ($admin): array {
                    $logs = [];

                    for ($index = 0; $index < $count; $index++) {
                        $logs[] = AuditLog::query()->create([
                            'bakery_id' => $this->bakery->getKey(),
                            'user_id' => $admin->getKey(),
                            'user_name' => $admin->name,
                            'action' => 'product.updated',
                            'auditable_type' => Product::class,
                            'auditable_id' => 1,
                            'ip_address' => '127.0.0.1',
                        ]);
                    }

                    return $logs;
                },
            ],
            'Téléchargements' => [
                route('exports.index'),
                fn (int $count): array => $this->exportRows($count, $admin),
            ],
        ];

        foreach ($screens as $label => [$path, $seed]) {
            $this->assertScreenIsStable($label, $path, $seed, $admin);
        }
    }

    public function test_the_dashboard_does_not_query_once_per_site(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        // Un lot de sites puis un lot plus large : la consolidation du
        // chiffre d'affaires par site ne doit pas se traduire par une
        // requête supplémentaire par site.
        Bakery::factory()->count(3)->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $small = $this->queriesFor(route('dashboard'), $admin);

        Bakery::factory()->count(5)->create();

        $large = $this->queriesFor(route('dashboard'), $admin);

        $this->assertSame(
            $small,
            $large,
            'Tableau de bord : le nombre de requêtes ne doit pas dépendre du nombre de sites.',
        );
    }

    /**
     * @return list<Export>
     */
    private function exportRows(int $count, User $user): array
    {
        $exports = [];

        for ($index = 1; $index <= $count; $index++) {
            $exports[] = Export::withoutBakeryScope()->create([
                'bakery_id' => null,
                'user_id' => $user->getKey(),
                'kind' => 'report:sales',
                'format' => 'csv',
                'label' => 'Ventes '.$index,
                'status' => Export::STATUS_READY,
            ]);
        }

        return $exports;
    }

    /**
     * L'écran répond, et son coût en requêtes ne bouge pas quand le nombre
     * de lignes affichées augmente.
     *
     * La première mesure est ignorée : elle absorbe les requêtes de mise en
     * route (permissions, contexte de site) communes à toute requête.
     *
     * @param  callable(int): array<array-key, mixed>  $seed
     */
    private function assertScreenIsStable(string $label, string $path, callable $seed, User $user): void
    {
        $this->actingAs($user)->get($path)->assertOk();
        $this->queriesFor($path, $user);

        $seed(1);
        $small = $this->queriesFor($path, $user);

        $seed(14);
        $large = $this->queriesFor($path, $user);

        $this->assertSame(
            $small,
            $large,
            "{$label} : le nombre de requêtes ne doit pas dépendre du nombre de lignes.",
        );
    }

    private function queriesFor(string $path, User $user): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get($path)->assertOk();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }
}
