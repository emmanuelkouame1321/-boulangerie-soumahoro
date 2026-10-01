<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Unit;
use RuntimeException;

/**
 * Conversion d'unités impossible (cahier §4.3).
 *
 * Message destiné à l'utilisateur : jamais de détail technique.
 */
final class UnitConversionException extends RuntimeException
{
    public static function between(Unit $from, Unit $to): self
    {
        return new self(sprintf(
            'Aucune conversion n’est configurée entre « %s » et « %s ».',
            $from->label,
            $to->label
        ));
    }

    public static function unusableFactor(Unit $from, Unit $to): self
    {
        return new self(sprintf(
            'La conversion entre « %s » et « %s » est invalide.',
            $from->label,
            $to->label
        ));
    }
}
