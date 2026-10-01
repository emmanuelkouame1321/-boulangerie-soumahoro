<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les comptes utilisateurs (cahier §20.4).
 *
 * Un compte n'est jamais supprimé : il est désactivé (cahier §3.3).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('user.view');
    }

    public function create(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('user.manage');
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }
}
