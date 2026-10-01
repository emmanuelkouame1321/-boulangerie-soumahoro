<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les employés (cahier §20.3).
 *
 * Consultation : `employee.view`. Création / mise à jour :
 * `employee.manage`. Aucun employé n'est supprimé physiquement.
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('employee.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
