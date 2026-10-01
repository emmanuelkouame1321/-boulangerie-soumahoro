<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Statut d'un lot de produits finis (cahier §8).
 *
 * Le statut est dérivé des quantités et de la date limite de vente :
 * frais, à écouler, expiré ou épuisé. Il alimente l'indicateur visuel
 * `expiry-indicator` (Phase 6).
 */
enum BatchStatus: string
{
    case Fresh = 'fresh';
    case ToSellSoon = 'to_sell_soon';
    case Expired = 'expired';
    case Depleted = 'depleted';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.batch.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.batch.{$this->value}.tone",
            'neutral'
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
