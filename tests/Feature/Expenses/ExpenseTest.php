<?php

declare(strict_types=1);

namespace Tests\Feature\Expenses;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_a_super_admin_can_see_the_expense_screens(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('expenses.index'))->assertOk();
        $this->actingAs($admin)->get(route('expenses.create'))->assertOk();
    }

    public function test_an_expense_can_be_recorded_from_the_screen(): void
    {
        $category = ExpenseCategory::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'name' => 'Électricité',
        ]);

        $this->actingAs($this->user(Role::SuperAdmin))
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->getKey(),
                'label' => 'Facture octobre',
                'amount' => 420_000,
                'spent_on' => now()->toDateString(),
                'method' => 'transfer',
                'payee' => 'Compagnie Ivoirienne',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $expense = Expense::query()->firstOrFail();
        $this->assertSame('EXP-'.now()->format('Ym').'-0001', $expense->reference);
        $this->assertSame(420_000, $expense->amount);
        $this->assertSame($this->bakery->getKey(), $expense->bakery_id);
    }

    public function test_a_category_can_be_added_and_deactivated(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('expenses.categories.store'), [
                'code' => 'securite',
                'name' => 'Sécurité',
                'position' => 5,
            ])
            ->assertSessionHas('status');

        $category = ExpenseCategory::query()->where('code', 'securite')->firstOrFail();
        $this->assertTrue($category->is_active);

        $this->actingAs($admin)
            ->post(route('expenses.categories.deactivate', $category))
            ->assertSessionHas('status');

        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_a_production_manager_cannot_reach_the_expenses(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('expenses.index'))
            ->assertForbidden();
    }

    public function test_screens_require_authentication(): void
    {
        $this->get(route('expenses.index'))->assertRedirect('/login');
        $this->get(route('expenses.create'))->assertRedirect('/login');
    }
}
