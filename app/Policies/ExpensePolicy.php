<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les charges (cahier §20.3).
 *
 * Consultation : `expense.view`. Création / mise à jour : `expense.manage`.
 * Une charge n'est jamais supprimée (historique, §53-11).
 */
class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expense.view');
    }

    public function view(User $user): bool
    {
        return $user->can('expense.view');
    }

    public function create(User $user): bool
    {
        return $user->can('expense.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('expense.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
