<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Modes d'alerte de stock (cahier §9.3).
 */
enum StockAlertMode: string
{
    case StockThreshold = 'stock_threshold';
    case CumulatedThreshold = 'cumulated_threshold';

    public function label(): string
    {
        return (string) config("boulangerie.alert_modes.{$this->value}", $this->value);
    }
}
