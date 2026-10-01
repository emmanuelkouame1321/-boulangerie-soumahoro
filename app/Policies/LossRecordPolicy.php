<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les pertes (cahier §20 et §50).
 *
 * Consultation : `loss.view`. Déclaration : `loss.create`. Aucune
 * suppression : une perte est une opération historique.
 */
class LossRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('loss.view');
    }

    public function view(User $user): bool
    {
        return $user->can('loss.view');
    }

    public function create(User $user): bool
    {
        return $user->can('loss.create');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
