<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Domain\Inventory\StockMovementService;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Export;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Exports en arrière-plan (cahier §48 et §53-13).
 *
 * Un export est créé en `pending`, produit par un job en queue, puis
 * téléchargeable 7 jours depuis l'écran Téléchargements. Le fichier
 * n'appartient qu'à son demandeur, et `boulangerie:exports-prune` nettoie
 * les fichiers expirés.
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'primary_unit_id' => $unit->getKey(),
            'sale_price' => 500,
        ]);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    private function sale(int $quantity, int $unitPrice): void
    {
        $total = $quantity * $unitPrice;

        $sale = Sale::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'type' => 'retail',
            'sale_date' => now()->toDateString(),
            'subtotal' => $total,
            'total' => $total,
            'paid_total' => $total,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $this->bread->getKey(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $total,
        ]);
    }

    private function export(array $attributes = []): Export
    {
        return Export::withoutBakeryScope()->create(array_merge([
            'bakery_id' => $this->bakery->getKey(),
            'user_id' => $this->user(Role::SuperAdmin)->getKey(),
            'kind' => 'report:sales',
            'format' => 'csv',
            'label' => 'Ventes',
            'filters' => [],
            'status' => Export::STATUS_READY,
        ], $attributes));
    }

    public function test_a_sales_report_is_generated_in_the_background_then_downloaded(): void
    {
        Storage::fake('local');

        $this->sale(13, 500);

        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('exports.store'), [
                'kind' => 'report:sales',
                'format' => 'csv',
                'filters' => [
                    'from' => now()->startOfMonth()->toDateString(),
                    'to' => now()->toDateString(),
                ],
            ])
            ->assertRedirect(route('exports.index'));

        $export = Export::withoutBakeryScope()->latest('id')->firstOrFail();

        // File d'attente synchrone en test : le job a déjà produit le fichier.
        $this->assertSame(Export::STATUS_READY, $export->status);
        $this->assertSame('exports/export-'.$export->getKey().'.csv', $export->path);
        $this->assertTrue($export->isReady());
        $this->assertFalse($export->isExpired());
        Storage::disk('local')->assertExists((string) $export->path);

        $this->actingAs($admin)
            ->get(route('exports.download', $export))
            ->assertOk()
            ->assertDownload();

        $this->assertStringContainsString(
            'Baguette',
            Storage::disk('local')->get((string) $export->path),
        );
    }

    public function test_an_excel_export_uses_the_spreadsheet_writer(): void
    {
        Storage::fake('local');

        $this->sale(4, 500);

        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('exports.store'), ['kind' => 'report:sales', 'format' => 'xls'])
            ->assertRedirect(route('exports.index'));

        $export = Export::withoutBakeryScope()->latest('id')->firstOrFail();

        $this->assertSame(Export::STATUS_READY, $export->status);

        $this->actingAs($admin)
            ->get(route('exports.download', $export))
            ->assertOk()
            ->assertDownload();

        $this->assertStringContainsString(
            '<Workbook',
            Storage::disk('local')->get((string) $export->path),
        );
    }

    public function test_the_exports_screen_lists_only_own_exports(): void
    {
        $mine = $this->export(['label' => 'Mes ventes', 'user_id' => $this->user(Role::SuperAdmin)->getKey()]);
        $other = $this->export(['label' => 'Ventes du voisin', 'user_id' => $this->user(Role::SuperAdmin)->getKey()]);

        $this->actingAs(User::query()->findOrFail($mine->user_id))
            ->get(route('exports.index'))
            ->assertOk()
            ->assertSee('Mes ventes')
            ->assertDontSee('Ventes du voisin');

        $this->assertNotSame($mine->user_id, $other->user_id);
    }

    public function test_an_export_is_private_to_its_requester(): void
    {
        $export = $this->export();

        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('exports.download', $export))
            ->assertForbidden();
    }

    public function test_an_expired_export_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('exports/export-expired.csv', 'Produit');

        $owner = $this->user(Role::SuperAdmin);
        $export = $this->export([
            'user_id' => $owner->getKey(),
            'path' => 'exports/export-expired.csv',
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($export->isExpired());

        $this->actingAs($owner)
            ->get(route('exports.download', $export))
            ->assertStatus(410);
    }

    public function test_the_prune_command_removes_expired_exports_and_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('exports/export-old.csv', 'Produit');

        $export = $this->export([
            'path' => 'exports/export-old.csv',
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('boulangerie:exports-prune')->assertSuccessful();

        $this->assertDatabaseMissing('exports', ['id' => $export->getKey()]);
        Storage::disk('local')->assertMissing('exports/export-old.csv');
    }

    public function test_the_prune_command_keeps_a_fresh_export(): void
    {
        $export = $this->export(['expires_at' => now()->addDays(7)]);

        $this->artisan('boulangerie:exports-prune')->assertSuccessful();

        $this->assertDatabaseHas('exports', ['id' => $export->getKey()]);
    }

    public function test_an_unknown_kind_is_rejected(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('exports.store'), ['kind' => 'inconnu', 'format' => 'csv'])
            ->assertNotFound();
    }

    public function test_an_unknown_report_fails_the_export_without_crashing(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('exports.store'), ['kind' => 'report:inconnu', 'format' => 'csv'])
            ->assertRedirect(route('exports.index'));

        $export = Export::withoutBakeryScope()->latest('id')->firstOrFail();

        $this->assertSame(Export::STATUS_FAILED, $export->status);
        $this->assertNotNull($export->error);
    }

    public function test_a_site_manager_exports_only_their_own_site(): void
    {
        Storage::fake('local');

        $production = $this->user(Role::ProductionManager);

        app(StockMovementService::class)->apply($this->bakery, $this->bread, StockMovementType::SupplierIn, 12);

        $this->actingAs($production)
            ->post(route('exports.store'), ['kind' => 'movements', 'format' => 'csv'])
            ->assertRedirect(route('exports.index'));

        $export = Export::withoutBakeryScope()->latest('id')->firstOrFail();

        $this->assertSame($this->bakery->getKey(), $export->bakery_id);
        $this->assertSame((string) $this->bakery->getKey(), $export->filters['site']);
        $this->assertStringContainsString(
            'Baguette',
            Storage::disk('local')->get((string) $export->path),
        );

        // L'écran Téléchargements est isolé par site : le demandeur voit son export.
        $this->actingAs($production)
            ->get(route('exports.index'))
            ->assertOk()
            ->assertSee('Mouvements de stock');
    }

    public function test_the_movements_export_requires_the_inventory_permission(): void
    {
        $this->actingAs($this->user(Role::Cashier))
            ->post(route('exports.store'), ['kind' => 'movements', 'format' => 'csv'])
            ->assertForbidden();
    }

    public function test_the_exports_screen_requires_authentication(): void
    {
        $this->get(route('exports.index'))->assertRedirect('/login');
    }
}
