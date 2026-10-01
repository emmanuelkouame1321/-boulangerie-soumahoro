<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions (cahier §20.4).
 *
 * Le Super-Admin n'est pas modifiable : ses pouvoirs viennent de
 * Gate::before (wildcard), pas de la base.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        return view('roles.index', [
            'roles' => $roles,
            'catalogueCount' => count((array) config('boulangerie.permissions', [])),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('roles.create', ['permissionGroups' => $this->permissionGroups()]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        /** @var array{name: string, permissions?: list<string>} $data */
        $data = $request->validated();

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        $this->forgetPermissionCache();

        return redirect()
            ->route('settings.roles.index')
            ->with('status', "Le rôle « {$role->name} » a été créé.");
    }

    public function edit(Role $role): View
    {
        // Gate::before contourne toute Policy pour le Super-Admin :
        // la règle « le rôle Super-Admin n'est pas modifiable » est donc
        // appliquée explicitement ici.
        abort_if($role->name === 'super-admin', 403);

        $this->authorize('update', $role);

        return view('roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'selected' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        // Voir RoleController::edit — le wildcard Super-Admin ne peut pas
        // être protégé par une Policy.
        abort_if($role->name === 'super-admin', 403);

        /** @var array{name: string, permissions?: list<string>} $data */
        $data = $request->validated();

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        $this->forgetPermissionCache();

        return redirect()
            ->route('settings.roles.index')
            ->with('status', "Les permissions du rôle « {$role->name} » ont été mises à jour.");
    }

    /**
     * Permissions du catalogue, groupées par module (préfixe avant le point).
     *
     * @return array<string, list<string>>
     */
    private function permissionGroups(): array
    {
        /** @var list<string> $permissions */
        $permissions = array_values((array) config('boulangerie.permissions', []));

        $groups = [];

        foreach ($permissions as $permission) {
            $module = explode('.', $permission)[0];
            $groups[$module][] = $permission;
        }

        ksort($groups);

        return $groups;
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
