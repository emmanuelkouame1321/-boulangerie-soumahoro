<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Domain\Inventory\StockMovementService;
use App\Enums\BatchStatus;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\StockAlert;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Cahier §22 : le centre d'alertes liste les alertes déclenchées et
 * permet leur acquittement (`alert.acknowledge`), sans jamais exposer
 * une action à un rôle qui n'a pas la permission.
 */
class AlertScreenTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Carbon::setTestNow('2026-10-05 08:00:00');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'type' => ProductType::FinishedGood,
            'primary_unit_id' => $unit->getKey(),
            'purchase_price' => 100,
            'sale_price' => 500,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_the_alert_center_lists_open_alerts(): void
    {
        StockAlert::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->bread->getKey(),
            'type' => StockAlertType::Critical,
            'status' => StockAlertStatus::Open,
            'message' => 'Rupture de stock de « Baguette » (seuil 10 pcs).',
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Rupture de stock')
            ->assertSee('Acquitter');
    }

    public function test_a_sales_manager_can_read_but_not_acknowledge(): void
    {
        StockAlert::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->bread->getKey(),
            'message' => 'Stock faible de « Baguette ».',
        ]);

        $salesManager = $this->user(Role::SalesManager);

        $this->actingAs($salesManager)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Stock faible')
            ->assertDontSee('Acquitter');

        $alert = StockAlert::query()->firstOrFail();

        $this->actingAs($salesManager)
            ->post(route('notifications.acknowledge', $alert))
            ->assertForbidden();

        $this->assertSame(StockAlertStatus::Open, $alert->refresh()->status);
    }

    public function test_a_production_manager_can_acknowledge_an_alert(): void
    {
        $alert = StockAlert::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->bread->getKey(),
            'message' => 'Stock faible de « Baguette ».',
        ]);

        $manager = $this->user(Role::ProductionManager);

        $this->actingAs($manager)
            ->post(route('notifications.acknowledge', $alert))
            ->assertRedirect()
            ->assertSessionHas('status');

        $alert->refresh();
        $this->assertSame(StockAlertStatus::Acknowledged, $alert->status);
        $this->assertSame($manager->getKey(), $alert->acknowledged_by);
        $this->assertNotNull($alert->acknowledged_at);

        // Rejouer l'acquittement reste stable (jamais d'erreur).
        $this->actingAs($manager)
            ->post(route('notifications.acknowledge', $alert))
            ->assertRedirect();

        $this->assertSame(StockAlertStatus::Acknowledged, $alert->refresh()->status);
    }

    public function test_alerts_opened_by_the_expiry_check_are_listed(): void
    {
        app(StockMovementService::class)->apply(
            bakery: $this->bakery,
            product: $this->bread,
            type: StockMovementType::ProductionIn,
            quantity: '10',
            reason: 'Stock de test',
            idempotencyKey: 'test:alerts:expiry',
        );

        ProductionBatch::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->bread->getKey(),
            'initial_quantity' => '10',
            'available_quantity' => '10',
            'produced_on' => '2026-10-04',
            'expires_on' => '2026-10-04',
            'status' => BatchStatus::Fresh,
        ]);

        $this->artisan('boulangerie:expiry-check')->assertSuccessful();

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('unités à traiter');
    }

    public function test_a_user_without_role_is_denied(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notifications.index'))->assertForbidden();

        $alert = StockAlert::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->bread->getKey(),
        ]);

        // Sans site résolu, le scope ferme l'accès (fail-closed) : le
        // binding de route ne trouve pas l'alerte, en 404 avant même le
        // contrôle de permission.
        $this->actingAs($user)
            ->post(route('notifications.acknowledge', $alert))
            ->assertNotFound();
    }
}
