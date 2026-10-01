<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

/**
 * Autorisation sur les unités de mesure et leurs conversions.
 *
 * La consultation est ouverte à qui a `unit.view` ; la modification est
 * réservée à `unit.manage` (paramétrage sensible).
 */
class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('unit.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('unit.view');
    }

    public function create(User $user): bool
    {
        return $user->can('unit.manage');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('unit.manage');
    }
}
