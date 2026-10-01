<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Domain\Customers\WholesalerRebateService;
use App\Enums\RebateBasis;
use App\Enums\RebateStatus;
use App\Enums\Role;
use App\Exceptions\RebateAlreadyValidatedException;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Wholesaler;
use App\Models\WholesalerRebate;
use App\Models\WholesalerRule;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RebateTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Customer $customer;

    private Wholesaler $wholesaler;

    private Product $product;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->year = (int) now()->year;

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->product = Product::factory()->create([
            'primary_unit_id' => $unit->getKey(),
            'purchase_price' => 400,
            'sale_price' => 1_200,
            'wholesale_price' => 1_000,
        ]);

        $this->customer = Customer::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'name' => 'Grossiste ABC',
        ]);

        $this->wholesaler = Wholesaler::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'customer_id' => $this->customer->getKey(),
            'name' => 'Grossiste ABC',
        ]);
    }

    private function sale(int $quantity, int $unitPrice, ?string $date = null): Sale
    {
        $sale = Sale::factory()->wholesale()->create([
            'bakery_id' => $this->bakery->getKey(),
            'customer_id' => $this->customer->getKey(),
            'sale_date' => $date ?? now()->toDateString(),
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
        ]);

        return $sale;
    }

    private function rule(RebateBasis $basis, int $min, ?int $max, string $rate): WholesalerRule
    {
        return WholesalerRule::create([
            'bakery_id' => $this->bakery->getKey(),
            'name' => "Palier {$min}",
            'basis' => $basis,
            'min_threshold' => $min,
            'max_threshold' => $max,
            'rate' => $rate,
            'is_active' => true,
            'position' => 0,
        ]);
    }

    private function service(): WholesalerRebateService
    {
        return app(WholesalerRebateService::class);
    }

    public function test_the_rebate_follows_the_configured_tier(): void
    {
        $this->rule(RebateBasis::Quantity, 0, 50, '0');
        $this->rule(RebateBasis::Quantity, 51, 100, '1');
        $this->rule(RebateBasis::Quantity, 101, 200, '2');
        $this->rule(RebateBasis::Quantity, 201, null, '3');

        // 150 unités à 1 000 FCFA : palier 101–200, taux 2 %.
        $this->sale(150, 1_000);

        $rebate = $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity);

        $this->assertSame(0, bccomp($rebate->base_quantity, '150', 4));
        $this->assertSame(150_000, $rebate->base_value);
        $this->assertSame(0, bccomp((string) $rebate->rate, '2', 3));
        $this->assertSame(3_000, $rebate->theoretical_value);
        $this->assertSame(RebateStatus::Projected, $rebate->status);
        $this->assertNotNull($rebate->wholesaler_rule_id);
    }

    public function test_thresholds_are_administrable_without_code_change(): void
    {
        // Un seul palier, insuffisant pour 120 unités : la ristourne est nulle.
        $this->rule(RebateBasis::Quantity, 0, 100, '1');
        $this->sale(120, 1_000);

        $rebate = $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity);
        $this->assertSame(0, $rebate->theoretical_value);

        // Ajouter un palier (données, pas de code) change le résultat.
        $this->rule(RebateBasis::Quantity, 101, null, '3');

        $rebate = $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity);
        $this->assertSame(3_600, $rebate->theoretical_value);
    }

    public function test_the_rebate_is_unique_per_wholesaler_and_year(): void
    {
        $this->rule(RebateBasis::Quantity, 0, null, '2');
        $this->sale(10, 1_000);

        $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity);
        $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity);

        $this->assertSame(1, WholesalerRebate::withoutBakeryScope()->count());
    }

    public function test_a_revenue_basis_selects_the_tier_on_turnover(): void
    {
        $this->rule(RebateBasis::Revenue, 0, 100_000, '1');
        $this->rule(RebateBasis::Revenue, 100_001, null, '2');

        $this->sale(50, 1_000); // 50 000 FCFA

        $rebate = $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Revenue);
        $this->assertSame(500, $rebate->theoretical_value);

        // Un second achat passe le CA au-delà du seuil.
        $this->sale(80, 1_000); // + 80 000 FCFA

        $rebate = $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Revenue);
        $this->assertSame(130_000, $rebate->base_value);
        $this->assertSame(2_600, $rebate->theoretical_value);
    }

    public function test_a_rebate_is_validated_then_settled(): void
    {
        $this->rule(RebateBasis::Quantity, 0, null, '2');
        $this->sale(10, 1_000);

        $user = User::factory()->create();

        $rebate = $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity, $user);
        $rebate = $this->service()->validate($rebate, $user);

        $this->assertSame(RebateStatus::Validated, $rebate->status);
        $this->assertSame(200, $rebate->validated_value);
        $this->assertNotNull($rebate->validated_at);

        $rebate = $this->service()->settle($rebate, $user, null, now());

        $this->assertSame(RebateStatus::Paid, $rebate->status);
        $this->assertSame(200, $rebate->paid_value);
        $this->assertNotNull($rebate->settled_on);
    }

    public function test_a_validated_rebate_is_not_recomputed(): void
    {
        $this->rule(RebateBasis::Quantity, 0, null, '2');
        $this->sale(10, 1_000);

        $user = User::factory()->create();
        $rebate = $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity);
        $this->service()->validate($rebate, $user);

        $this->expectException(RebateAlreadyValidatedException::class);

        $this->service()->compute($this->bakery, $this->wholesaler, $this->year, RebateBasis::Quantity);
    }

    public function test_a_sales_manager_can_compute_a_rebate_from_the_screen(): void
    {
        $this->rule(RebateBasis::Quantity, 0, null, '2');
        $this->sale(10, 1_000);

        $this->actingAs($this->user(Role::SalesManager))
            ->post(route('rebates.compute'), [
                'wholesaler_id' => $this->wholesaler->getKey(),
                'period_year' => $this->year,
                'basis' => RebateBasis::Quantity->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(200, (int) WholesalerRebate::withoutBakeryScope()->value('theoretical_value'));
    }

    public function test_the_rebate_screens_are_protected(): void
    {
        $this->actingAs($this->user(Role::SalesManager))
            ->get(route('rebates.index'))
            ->assertOk();

        // Le caissier n'a pas la permission de consulter les ristournes.
        $this->actingAs($this->user(Role::Cashier))
            ->get(route('rebates.index'))
            ->assertForbidden();

        // Le responsable production non plus.
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('rebates.index'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('rebates.index'))->assertRedirect('/login');
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }
}
