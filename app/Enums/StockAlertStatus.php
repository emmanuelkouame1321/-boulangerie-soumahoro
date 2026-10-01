<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cycle de vie d'une alerte de stock (cahier §22).
 */
enum StockAlertStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Acknowledged => 'Acquittée',
            self::Resolved => 'Résolue',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::Acknowledged => 'warning',
            self::Resolved => 'success',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Resolved;
    }
}
