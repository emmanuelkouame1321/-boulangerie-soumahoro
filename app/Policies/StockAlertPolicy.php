<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les alertes (cahier §22).
 *
 * Consultation : `alert.view`. Acquittement : `alert.acknowledge`.
 * Une alerte ne se supprime pas : elle se résout ou s'acquitte.
 */
class StockAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('alert.view');
    }

    public function view(User $user): bool
    {
        return $user->can('alert.view');
    }

    public function acknowledge(User $user): bool
    {
        return $user->can('alert.acknowledge');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
