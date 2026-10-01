<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les productions (cahier §20.2).
 *
 * Consultation : `production.view`. Création : `production.create`.
 * Validation : `production.validate`.
 */
class ProductionOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production.view');
    }

    public function view(User $user): bool
    {
        return $user->can('production.view');
    }

    public function create(User $user): bool
    {
        return $user->can('production.create');
    }

    public function validate(User $user): bool
    {
        return $user->can('production.validate');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
