<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Autorisation sur les produits (cahier §4 et §20).
 *
 * Un produit n'est jamais supprimé physiquement (cahier §3.3) : il est
 * désactivé. La suppression est donc toujours refusée.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('product.view');
    }

    public function create(User $user): bool
    {
        return $user->can('product.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('product.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return false;
    }
}
