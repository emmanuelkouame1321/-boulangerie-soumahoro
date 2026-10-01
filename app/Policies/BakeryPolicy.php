<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Bakery;
use App\Models\User;

/**
 * Autorisation sur les boulangeries (cahier §19.2 et §20).
 *
 * Seul le Super-Admin dispose de ces permissions — la création et la
 * modification des sites ne sont pas déléguées aux responsables
 * opérationnels. Le contournement par l'interface est donc impossible :
 * la vérification a lieu côté serveur.
 */
class BakeryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bakery.view');
    }

    public function view(User $user, Bakery $bakery): bool
    {
        return $user->can('bakery.view');
    }

    public function create(User $user): bool
    {
        return $user->can('bakery.create');
    }

    public function update(User $user, Bakery $bakery): bool
    {
        return $user->can('bakery.update');
    }

    public function delete(User $user, Bakery $bakery): bool
    {
        // Une boulangerie n'est pas supprimée : elle est désactivée
        // (cahier §3.3, aucune suppression physique d'une opération
        // métier critique).
        return false;
    }
}
