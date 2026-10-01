<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Autorisation sur les rôles et permissions (cahier §20.4).
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('role.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('role.view');
    }

    public function create(User $user): bool
    {
        return $user->can('role.manage');
    }

    public function update(User $user, Role $role): bool
    {
        // Le rôle Super-Admin tire ses pouvoirs de Gate::before (wildcard),
        // pas des permissions en base : le modifier n'aurait pas de sens.
        return $role->name !== 'super-admin' && $user->can('role.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        return false;
    }
}
