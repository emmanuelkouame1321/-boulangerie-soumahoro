<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Mouvement de stock invalide (cahier §41).
 *
 * Message destiné à l'utilisateur : jamais de détail technique.
 */
final class InvalidStockMovementException extends RuntimeException
{
    public static function nonPositiveQuantity(): self
    {
        return new self('La quantité d’un mouvement de stock doit être strictement positive.');
    }

    public static function alreadyConsumed(): self
    {
        return new self('Cette réservation n’est plus active : elle a déjà été libérée ou consommée.');
    }
}
