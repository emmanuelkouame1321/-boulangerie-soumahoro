<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation sur la paie (cahier §20.3).
 *
 * Consultation : `payroll.view`. Génération, validation et versement :
 * `payroll.manage`. Aucune période n'est supprimée (historique, §53-11).
 */
class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function validate(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function pay(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
