<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les clients (cahier §20.3).
 *
 * Consultation : `customer.view`. Gestion : `customer.manage`.
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer.view');
    }

    public function view(User $user): bool
    {
        return $user->can('customer.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customer.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('customer.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
