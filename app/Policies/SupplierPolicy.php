<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les fournisseurs (cahier §10 et §20).
 *
 * La consultation relève de `supplier.view`, la gestion de
 * `supplier.manage`. Un fournisseur n'est jamais supprimé physiquement.
 */
class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supplier.view');
    }

    public function view(User $user): bool
    {
        return $user->can('supplier.view');
    }

    public function create(User $user): bool
    {
        return $user->can('supplier.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('supplier.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
