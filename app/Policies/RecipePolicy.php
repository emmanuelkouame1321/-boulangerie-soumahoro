<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les recettes (cahier §20.2).
 *
 * Consultation : `recipe.view`. Gestion : `recipe.manage`.
 */
class RecipePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('recipe.view');
    }

    public function view(User $user): bool
    {
        return $user->can('recipe.view');
    }

    public function create(User $user): bool
    {
        return $user->can('recipe.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('recipe.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
