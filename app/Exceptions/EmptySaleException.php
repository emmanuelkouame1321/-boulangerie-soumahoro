<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Une vente sans ligne exploitable ne peut pas être enregistrée
 * (cahier §14 et §41).
 */
final class EmptySaleException extends RuntimeException
{
    public static function create(): self
    {
        return new self('La vente ne contient aucun article : ajoutez au moins une ligne.');
    }
}
