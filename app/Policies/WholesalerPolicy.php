<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les grossistes (cahier §20.3).
 *
 * Consultation : `wholesaler.view`. Création / mise à jour :
 * `wholesaler.manage`. Aucun grossiste n'est supprimé physiquement.
 */
class WholesalerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('wholesaler.view');
    }

    public function view(User $user): bool
    {
        return $user->can('wholesaler.view');
    }

    public function create(User $user): bool
    {
        return $user->can('wholesaler.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('wholesaler.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
