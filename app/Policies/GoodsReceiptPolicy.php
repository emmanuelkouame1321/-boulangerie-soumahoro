<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les réceptions fournisseur (cahier §11.3 et §20).
 *
 * La consultation suit `purchasing.view`, la validation suit
 * `goods-receipt.create`. Une réception validée ne peut plus l'être.
 */
class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.view');
    }

    public function view(User $user): bool
    {
        return $user->can('purchasing.view');
    }

    public function validate(User $user): bool
    {
        return $user->can('goods-receipt.create');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
