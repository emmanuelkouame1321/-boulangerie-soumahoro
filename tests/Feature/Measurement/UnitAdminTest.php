<?php

declare(strict_types=1);

namespace Tests\Feature\Measurement;

use App\Enums\Role;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_list_units(): void
    {
        Unit::factory()->create(['label' => 'Kilogramme', 'code' => 'kg', 'symbol' => 'kg']);

        $this->actingAs($this->superAdmin())
            ->get(route('units.index'))
            ->assertOk()
            ->assertSee('Kilogramme');
    }

    public function test_super_admin_can_create_a_unit(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('units.store'), [
                'code' => 'tray',
                'label' => 'Plateau',
                'symbol' => 'plateau',
                'kind' => 'container',
                'position' => 5,
                'is_active' => true,
            ])
            ->assertRedirect(route('units.index'));

        $this->assertDatabaseHas('units', ['code' => 'tray', 'label' => 'Plateau']);
    }

    public function test_a_conversion_can_be_added_and_removed(): void
    {
        $user = $this->superAdmin();

        $sack = Unit::factory()->create(['code' => 'sack', 'label' => 'Sac']);
        $kg = Unit::factory()->create(['code' => 'kg', 'label' => 'Kilogramme']);

        $this->actingAs($user)
            ->post(route('units.conversions.store'), [
                'from_unit_id' => $sack->getKey(),
                'to_unit_id' => $kg->getKey(),
                'factor' => 50,
            ])
            ->assertRedirect(route('units.index'));

        $conversion = UnitConversion::query()->firstOrFail();

        $this->assertSame('50.00000000', (string) $conversion->factor);

        $this->actingAs($user)
            ->delete(route('units.conversions.destroy', $conversion))
            ->assertRedirect(route('units.index'));

        $this->assertDatabaseCount('unit_conversions', 0);
    }

    public function test_a_role_without_permission_cannot_reach_units(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::ProductionManager->value);

        $this->actingAs($user)
            ->get(route('units.index'))
            ->assertForbidden();
    }
}
