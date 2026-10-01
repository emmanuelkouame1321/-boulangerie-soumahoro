<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types d'alerte de stock (cahier §9.2, §9.3 et §22).
 */
enum StockAlertType: string
{
    case Low = 'low';
    case Critical = 'critical';
    case Cumulated = 'cumulated';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Stock faible',
            self::Critical => 'Stock critique',
            self::Cumulated => 'Seuil cumulé atteint',
        };
    }

    /**
     * Sémantique visuelle du composant <x-ui.status-badge />.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Low => 'warning',
            self::Critical => 'danger',
            self::Cumulated => 'info',
        };
    }
}
