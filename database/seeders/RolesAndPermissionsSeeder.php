<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Amorçage du plan de contrôle (cahier §20).
 *
 * Source de vérité : config/boulangerie.php. Ajouter un rôle ou une
 * permission se fait là-bas, puis on relance ce seeder. Idempotent.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissionNames() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Role::cases() as $role) {
            $model = SpatieRole::findOrCreate($role->value, 'web');

            // Le wildcard '*' du Super-Admin est porté par Gate::before,
            // pas par une permission en base.
            $permissions = array_values(array_filter(
                $role->permissions(),
                static fn (string $permission): bool => $permission !== '*'
            ));

            $model->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Union des permissions de tous les rôles, hors wildcard du Super-Admin.
     *
     * @return list<string>
     */
    private function permissionNames(): array
    {
        $permissions = [];

        foreach (Role::cases() as $role) {
            foreach ($role->permissions() as $permission) {
                if ($permission === '*') {
                    continue;
                }

                $permissions[$permission] = true;
            }
        }

        // Permissions de module utilisées par la navigation et le middleware
        // de route, même si aucun rôle ne les porte encore explicitement.
        /** @var list<string> $declared */
        $declared = config('boulangerie.permissions', []);

        foreach ($declared as $permission) {
            $permissions[$permission] = true;
        }

        return array_keys($permissions);
    }
}
