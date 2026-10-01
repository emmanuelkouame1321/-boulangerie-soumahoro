<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Une période de paie doit contenir au moins une ligne avant d'être
 * validée (cahier §17).
 */
final class EmptyPayrollPeriodException extends RuntimeException
{
    public static function create(): self
    {
        return new self('La période de paie ne contient aucun employé : générez les lignes avant de valider.');
    }
}
