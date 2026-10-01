<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Une perte doit contenir au moins une ligne de quantité positive
 * (cahier §8.2).
 */
final class EmptyLossException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Aucune quantité à enregistrer : indiquez au moins un produit et une quantité.');
    }
}
