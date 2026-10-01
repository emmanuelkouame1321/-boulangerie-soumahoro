<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les ristournes (cahier §20.3).
 *
 * Consultation : `rebate.view`. Administration des paliers, calcul,
 * validation et versement : `rebate.manage`. Une ristourne n'est jamais
 * supprimée (historique, §53-11).
 */
class WholesalerRebatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rebate.view');
    }

    public function view(User $user): bool
    {
        return $user->can('rebate.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rebate.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('rebate.manage');
    }

    public function validate(User $user): bool
    {
        return $user->can('rebate.manage');
    }

    public function settle(User $user): bool
    {
        return $user->can('rebate.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
