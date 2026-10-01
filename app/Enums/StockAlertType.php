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
    case ExpirySoon = 'expiry_soon';
    case Expired = 'expired';
    case SignificantLoss = 'significant_loss';
    case ProductionGap = 'production_gap';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Stock faible',
            self::Critical => 'Stock critique',
            self::Cumulated => 'Seuil cumulé atteint',
            self::ExpirySoon => 'Date limite proche',
            self::Expired => 'Produit expiré',
            self::SignificantLoss => 'Perte importante',
            self::ProductionGap => 'Écart de rendement',
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
            self::ExpirySoon => 'warning',
            self::Expired => 'danger',
            self::SignificantLoss => 'danger',
            self::ProductionGap => 'warning',
        };
    }
}
