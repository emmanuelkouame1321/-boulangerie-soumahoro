<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Un écart de clôture au-delà du seuil configuré doit être justifié par
 * une observation (cahier §15 : « une différence importante peut demander
 * une justification »).
 */
final class DayClosingGapJustificationRequiredException extends RuntimeException
{
    public static function for(string $gap, string $threshold): self
    {
        return new self(sprintf(
            'Écart de %s unités constaté à la clôture, au-delà du seuil de %s : ajoutez une observation pour justifier.',
            $gap,
            $threshold
        ));
    }
}
