<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\PayrollPeriod;
use RuntimeException;

/**
 * Une période de paie validée ou versée est figée : son historique n'est
 * plus recalculé (cahier §53-11).
 */
final class PayrollPeriodLockedException extends RuntimeException
{
    public static function for(PayrollPeriod $period): self
    {
        return new self(sprintf(
            'La période de paie %s est déjà %s : elle ne peut plus être modifiée.',
            $period->monthLabel(),
            mb_strtolower($period->status->label())
        ));
    }
}
