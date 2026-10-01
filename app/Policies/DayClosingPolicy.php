<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur les clôtures journalières (cahier §15 et §20.3).
 *
 * Consultation et création : `sales.close-day`.
 */
class DayClosingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.close-day');
    }

    public function view(User $user): bool
    {
        return $user->can('sales.close-day');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.close-day');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
