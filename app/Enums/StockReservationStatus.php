<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cycle de vie d'une réservation de stock (cahier §9.1).
 */
enum StockReservationStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Consumed = 'consumed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Released => 'Libérée',
            self::Consumed => 'Consommée',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
