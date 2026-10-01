<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Domain\Audit\AuditLogger;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

/**
 * Journal d'audit (cahier §32 et §53-10).
 *
 * Qui a fait quoi, sur quel objet, avec quelles anciennes et nouvelles
 * valeurs, depuis quelle IP et sur quel site. Le journal est en insertion
 * seule : les écrans ne le modifient jamais.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);
    }

    private function user(Role $role, ?Bakery $bakery = null): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        if ($bakery !== null) {
            $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);
        }

        return $user;
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'name' => 'Baguette',
            'primary_unit_id' => $this->unit->getKey(),
            'sale_price' => 500,
            'purchase_price' => 300,
        ], $attributes));
    }

    public function test_creating_an_audited_model_writes_an_entry(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $product = $this->actingAs($admin)->product();

        $log = AuditLog::withoutBakeryScope()
            ->where('action', 'product.created')
            ->where('auditable_id', $product->getKey())
            ->firstOrFail();

        $this->assertSame($admin->name, $log->user_name);
        $this->assertSame(Product::class, $log->auditable_type);
        $this->assertSame('Baguette', $log->new_values['name']);
        $this->assertSame('Product #'.$product->getKey(), $log->subjectLabel());
        $this->assertSame('product.created', app(AuditLogger::class)->action($product, 'created'));
    }

    public function test_updating_a_model_records_only_the_changed_values(): void
    {
        $product = $this->product(['sale_price' => 500]);

        $product->update(['sale_price' => 900]);

        $log = AuditLog::withoutBakeryScope()
            ->where('action', 'product.updated')
            ->where('auditable_id', $product->getKey())
            ->firstOrFail();

        $this->assertSame(500, (int) $log->old_values['sale_price']);
        $this->assertSame(900, (int) $log->new_values['sale_price']);
        $this->assertArrayNotHasKey('name', $log->new_values);
    }

    public function test_deleting_a_model_keeps_the_object_reference(): void
    {
        $product = $this->product();

        $product->delete();

        $log = AuditLog::withoutBakeryScope()
            ->where('action', 'product.deleted')
            ->where('auditable_id', $product->getKey())
            ->firstOrFail();

        $this->assertSame('Baguette', $log->old_values['name']);
        $this->assertNull($log->auditable());
        $this->assertStringContainsString('Product #', $log->subjectLabel());
    }

    public function test_sensitive_attributes_are_never_journalised(): void
    {
        $this->assertContains('password', AuditLogger::SENSITIVE);
        $this->assertContains('remember_token', AuditLogger::SENSITIVE);

        $product = $this->product();

        $log = AuditLog::withoutBakeryScope()
            ->where('auditable_id', $product->getKey())
            ->firstOrFail();

        $this->assertIsArray($log->new_values);
        $this->assertArrayNotHasKey('password', $log->new_values);
    }

    public function test_the_audit_screen_lists_entries_and_requires_the_permission(): void
    {
        $admin = $this->user(Role::SuperAdmin);
        $this->actingAs($admin)->product();

        $this->actingAs($admin)
            ->get(route('settings.audit.index'))
            ->assertOk()
            ->assertSee('product.created')
            ->assertSee($admin->name);

        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('settings.audit.index'))
            ->assertForbidden();
    }

    public function test_the_audit_screen_can_isolate_an_empty_result(): void
    {
        $this->actingAs($this->user(Role::SuperAdmin))
            ->get(route('settings.audit.index', ['action' => 'site.never_happened']))
            ->assertOk()
            ->assertSee('Aucune entrée d’audit');
    }

    public function test_creating_and_updating_a_role_are_audited(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('settings.roles.store'), [
                'name' => 'audit-manager',
                'permissions' => ['audit.view', 'report.view'],
            ])
            ->assertRedirect(route('settings.roles.index'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'role.created']);

        $role = SpatieRole::query()->where('name', 'audit-manager')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('settings.roles.update', $role), [
                'name' => 'audit-manager',
                'permissions' => ['audit.view'],
            ])
            ->assertRedirect(route('settings.roles.index'));

        $log = AuditLog::withoutBakeryScope()
            ->where('action', 'role.permissions_updated')
            ->firstOrFail();

        $this->assertContains('report.view', $log->old_values['permissions']);
        $this->assertSame(['audit.view'], $log->new_values['permissions']);
    }

    public function test_replaying_the_same_permissions_does_not_write_an_entry(): void
    {
        $admin = $this->user(Role::SuperAdmin);
        $role = SpatieRole::query()->where('name', 'sales-manager')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('settings.roles.update', $role), [
                'name' => 'sales-manager',
                'permissions' => $role->permissions()->pluck('name')->all(),
            ])
            ->assertRedirect(route('settings.roles.index'));

        $this->assertDatabaseMissing('audit_logs', ['action' => 'role.permissions_updated']);
    }
}
