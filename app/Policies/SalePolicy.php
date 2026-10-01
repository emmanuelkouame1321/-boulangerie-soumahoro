<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les ventes (cahier §20.3).
 *
 * Consultation : `sales.view`. Création : `sales.create`. Annulation :
 * `sales.close-day` (responsable de vente).
 */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view');
    }

    public function view(User $user): bool
    {
        return $user->can('sales.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function cancel(User $user): bool
    {
        return $user->can('sales.close-day');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
