<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Bakery;
use App\Models\DayClosing;
use RuntimeException;

/**
 * Une clôture existe déjà pour ce site et cette date (cahier §15 et §29) :
 * elle ne peut pas être rejouée.
 */
final class DayClosingAlreadyExistsException extends RuntimeException
{
    public static function for(Bakery $bakery, string $date, ?DayClosing $closing = null): self
    {
        return new self(sprintf(
            'Une clôture existe déjà pour le site « %s » à la date du %s%s.',
            $bakery->name,
            $date,
            $closing !== null ? " ({$closing->reference})" : ''
        ));
    }
}
