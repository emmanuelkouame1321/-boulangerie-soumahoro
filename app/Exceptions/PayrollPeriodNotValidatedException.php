<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\PayrollPeriod;
use RuntimeException;

/**
 * Une période de paie doit être validée avant d'enregistrer les versements
 * (cahier §17).
 */
final class PayrollPeriodNotValidatedException extends RuntimeException
{
    public static function for(PayrollPeriod $period): self
    {
        return new self(sprintf(
            'La période de paie %s doit être validée avant d’enregistrer les versements.',
            $period->monthLabel()
        ));
    }
}
