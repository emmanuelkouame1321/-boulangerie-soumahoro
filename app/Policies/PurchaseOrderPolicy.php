<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les commandes fournisseur (cahier §11 et §20).
 *
 * Consultation : `purchasing.view`. Création : `purchasing.create`.
 * Réception : `goods-receipt.create`. Annulation : `purchasing.manage`.
 */
class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.view');
    }

    public function view(User $user): bool
    {
        return $user->can('purchasing.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.create');
    }

    public function receive(User $user): bool
    {
        return $user->can('goods-receipt.create');
    }

    public function cancel(User $user): bool
    {
        return $user->can('purchasing.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
