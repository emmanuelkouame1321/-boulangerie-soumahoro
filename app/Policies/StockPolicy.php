<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur le stock (cahier §9 et §20).
 *
 * La consultation relève de `inventory.view` ; toute correction manuelle
 * relève de `inventory.adjust`. Le stock n'est jamais supprimé.
 */
class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function adjust(User $user): bool
    {
        return $user->can('inventory.adjust');
    }
}
