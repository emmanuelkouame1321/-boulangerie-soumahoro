<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Origine d'un registre de perte (cahier §8.2).
 *
 * `manual` : déclarée par un utilisateur ;
 * `expiry` : produite automatiquement par la conversion des lots
 * expirés (job planifié, §40).
 */
enum LossOrigin: string
{
    case Manual = 'manual';
    case Expiry = 'expiry';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Déclarée',
            self::Expiry => 'Automatique (péremption)',
        };
    }
}
