<?php

declare(strict_types=1);

namespace Tests\Feature\Payroll;

use App\Domain\Payroll\PayrollService;
use App\Enums\PayrollStatus;
use App\Enums\Role;
use App\Exceptions\PayrollPeriodLockedException;
use App\Exceptions\PayrollPeriodNotValidatedException;
use App\Models\Bakery;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Employee $awa;

    private Employee $kofi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();

        $this->awa = Employee::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'first_name' => 'Awa',
            'last_name' => 'Koné',
            'base_salary' => 150_000,
        ]);

        $this->kofi = Employee::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'first_name' => 'Kofi',
            'last_name' => 'Traoré',
            'base_salary' => 90_000,
        ]);
    }

    private function service(): PayrollService
    {
        return app(PayrollService::class);
    }

    public function test_generating_a_period_creates_one_line_per_active_employee(): void
    {
        $period = $this->service()->openPeriod($this->bakery, 2026, 10);
        $this->service()->generate($period);

        $this->assertSame(2, PayrollItem::withoutBakeryScope()->count());
        $this->assertSame(240_000, (int) $period->fresh()->total_base);
        $this->assertSame(240_000, (int) $period->fresh()->total_net);
    }

    public function test_the_net_is_computed_from_base_bonuses_advances_and_deductions(): void
    {
        $period = $this->service()->openPeriod($this->bakery, 2026, 10);
        $this->service()->generate($period);

        $item = PayrollItem::withoutBakeryScope()
            ->where('employee_id', $this->awa->getKey())
            ->firstOrFail();

        $this->service()->updateItem($item, [
            'bonuses' => 20_000,
            'advances' => 10_000,
            'deductions' => 5_000,
        ]);

        // 150 000 + 20 000 − 10 000 − 5 000.
        $this->assertSame(155_000, (int) $item->fresh()->net_salary);
    }

    public function test_the_net_is_never_negative(): void
    {
        $period = $this->service()->openPeriod($this->bakery, 2026, 10);
        $this->service()->generate($period);

        $item = PayrollItem::withoutBakeryScope()
            ->where('employee_id', $this->kofi->getKey())
            ->firstOrFail();

        $this->service()->updateItem($item, ['advances' => 500_000]);

        $this->assertSame(0, (int) $item->fresh()->net_salary);
    }

    public function test_a_period_is_unique_per_site_and_month(): void
    {
        $first = $this->service()->openPeriod($this->bakery, 2026, 10);
        $second = $this->service()->openPeriod($this->bakery, 2026, 10);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, PayrollPeriod::withoutBakeryScope()->count());
    }

    public function test_a_validated_period_is_frozen(): void
    {
        $user = User::factory()->create();
        $period = $this->service()->openPeriod($this->bakery, 2026, 10);
        $this->service()->generate($period);
        $this->service()->validate($period, $user);

        $item = PayrollItem::withoutBakeryScope()->firstOrFail();

        $this->expectException(PayrollPeriodLockedException::class);

        $this->service()->updateItem($item, ['bonuses' => 1_000]);
    }

    public function test_a_period_can_be_paid_after_validation(): void
    {
        $user = User::factory()->create();
        $period = $this->service()->openPeriod($this->bakery, 2026, 10);
        $this->service()->generate($period);
        $this->service()->validate($period, $user);

        $period = $this->service()->pay($period, 'cash', now(), $user);

        $this->assertSame(PayrollStatus::Paid, $period->status);
        $this->assertNotNull($period->paid_at);
        $this->assertSame(2, $period->payments()->count());
        $this->assertSame(240_000, (int) $period->payments()->sum('amount'));

        // L'historique des lignes reste intact.
        $this->assertSame(2, PayrollItem::withoutBakeryScope()->count());
    }

    public function test_paying_a_draft_period_is_refused(): void
    {
        $user = User::factory()->create();
        $period = $this->service()->openPeriod($this->bakery, 2026, 10);
        $this->service()->generate($period);

        $this->expectException(PayrollPeriodNotValidatedException::class);

        $this->service()->pay($period, 'cash', now(), $user);
    }

    public function test_a_super_admin_can_see_the_payroll_screens(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('payroll.index'))->assertOk();

        $period = $this->service()->openPeriod($this->bakery, 2026, 10);
        $this->service()->generate($period);

        $this->actingAs($admin)
            ->get(route('payroll.show', $period))
            ->assertOk()
            ->assertSee('Awa Koné');
    }

    public function test_a_period_can_be_prepared_from_the_screen(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('payroll.periods.store'), [
                'period_year' => 2026,
                'period_month' => 10,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, PayrollPeriod::withoutBakeryScope()->count());
        $this->assertSame(2, PayrollItem::withoutBakeryScope()->count());
    }

    public function test_a_production_manager_cannot_reach_the_payroll(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('payroll.index'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('payroll.index'))->assertRedirect('/login');
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }
}
